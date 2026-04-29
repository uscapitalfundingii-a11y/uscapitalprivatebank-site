<?php

defined('BASEPATH') or exit('No direct script access allowed');
require_once(APPPATH . 'libraries/import/App_import.php');

class Import_customers extends App_import
{
    protected $notImportableFields = [];

    private $countryFields = ['country', 'billing_country', 'shipping_country'];

    protected $requiredFields = ['firstname', 'lastname', 'email'];

    public function __construct()
    {
        $this->notImportableFields = hooks()->apply_filters('not_importable_clients_fields', ['userid', 'id', 'is_primary', 'password', 'datecreated', 'last_ip', 'last_login', 'last_password_change', 'active', 'new_pass_key', 'new_pass_key_requested', 'leadid', 'default_currency', 'profile_image', 'default_language', 'direction', 'show_primary_contact', 'invoice_emails', 'estimate_emails', 'project_emails', 'task_emails', 'contract_emails', 'credit_note_emails', 'ticket_emails', 'addedfrom', 'registration_confirmed', 'last_active_time', 'email_verified_at', 'email_verification_key', 'email_verification_sent_at']);

        if (get_option('company_is_required') == 1) {
            $this->requiredFields[] = 'company';
        }

        $this->addImportGuidelinesInfo('When "Merge existing customers" is enabled, the importer updates existing customers by exact email match first, then by phone + company or phone + contact name. Otherwise duplicate email rows won\'t be imported.', true);

        $this->addImportGuidelinesInfo('Make sure you configure the default contact permission in <a href="' . admin_url('settings?group=clients') . '" target="_blank">Setup->Settings->Customers</a> to get the best results like auto assigning contact permissions and email notification settings based on the permission.');

        parent::__construct();
    }

    public function perform()
    {
        $this->initialize();

        $databaseFields      = $this->getImportableDatabaseFields();
        $totalDatabaseFields = count($databaseFields);
        $headerMappings      = $this->resolveHeaderMappings($databaseFields);
        $useHeaderMappings   = count($headerMappings['database']) > 0;

        foreach ($this->getRows() as $rowNumber => $row) {
            $insert    = [];
            $duplicate = false;
            $i         = $totalDatabaseFields;

            if ($useHeaderMappings) {
                $insert = $this->mapInsertFromHeaders($row, $databaseFields, $headerMappings['database'], $duplicate);
            } else {
                for ($i = 0; $i < $totalDatabaseFields; $i++) {
                    if (!isset($row[$i])) {
                        continue;
                    }

                    $insert[$databaseFields[$i]] = $this->prepareDatabaseValue(
                        $databaseFields[$i],
                        $row[$i],
                        $row,
                        $databaseFields,
                        $duplicate
                    );
                }
            }

            if ($duplicate) {
                continue;
            }

            $insert = $this->trimInsertValues($insert);

            if (count($insert) > 0) {
                $id = null;
                $contactId = null;
                $existingMatch = $this->shouldMergeExistingCustomers() ? $this->findExistingCustomerMatch($insert) : null;

                if (!$this->isSimulation()) {
                    $insert['datecreated']           = date('Y-m-d H:i:s');
                    $insert['donotsendwelcomeemail'] = true;

                    if ($this->ci->input->post('default_pass_all')) {
                        $insert['password'] = $this->ci->input->post('default_pass_all', false);
                    }

                    if (!$this->shouldMergeExistingCustomers() && $this->shouldAddContactUnderCustomer($insert)) {
                        $this->addContactUnderCustomer($insert);

                        continue;
                    }

                    if ($existingMatch) {
                        [$id, $contactId] = $this->mergeIntoExistingCustomer($existingMatch, $insert);
                    } else {
                        $insert['is_primary'] = 1;
                        $id                   = $this->ci->clients_model->add($insert, true);

                        if ($id) {
                            $contactId = get_primary_contact_user_id($id);

                            if ($this->ci->input->post('groups_in[]')) {
                                $this->insertCustomerGroups($this->ci->input->post('groups_in[]'), $id);
                            }

                            if (staff_cant('view', 'customers')) {
                                $assign['customer_admins']   = [];
                                $assign['customer_admins'][] = get_staff_user_id();
                                $this->ci->clients_model->assign_admins($assign, $id);
                            }
                        }
                    }
                } else {
                    $this->simulationData[$rowNumber] = $this->formatValuesForSimulation($insert);
                    if ($existingMatch) {
                        $this->simulationData[$rowNumber]['Import Action'] = 'Merge existing customer';
                    } else {
                        $this->simulationData[$rowNumber]['Import Action'] = 'Create new customer';
                    }
                }

                if ($id || $this->isSimulation()) {
                    $this->incrementImported();
                }

                if ($useHeaderMappings) {
                    $this->handleMappedCustomFieldsInsert($id, $row, $rowNumber, $headerMappings['custom']);
                } else {
                    $this->handleCustomFieldsInsert($id, $row, $i, $rowNumber, 'customers');
                }
            }

            if ($this->isSimulation() && $rowNumber >= $this->maxSimulationRows) {
                break;
            }
        }
    }

    public function formatFieldNameForHeading($field)
    {
        if (strtolower($field) == 'title') {
            return 'Position';
        }

        return parent::formatFieldNameForHeading($field);
    }

    protected function email_formatSampleData()
    {
        return uniqid() . '@example.com';
    }

    protected function failureRedirectURL()
    {
        return admin_url('clients/import');
    }

    protected function afterSampleTableHeadingText($field)
    {
        $contactFields = [
            'firstname', 'lastname', 'email', 'contact_phonenumber', 'title',
        ];

        if (in_array($field, $contactFields)) {
            return '<br /><span class="text-info">' . _l('import_contact_field') . '</span>';
        }
    }

    private function insertCustomerGroups($groups, $customer_id)
    {
        foreach ($groups as $group) {
            $this->ci->db->insert(db_prefix() . 'customer_groups', [
                                                    'customer_id' => $customer_id,
                                                    'groupid'     => $group,
                                                ]);
        }
    }

    private function shouldAddContactUnderCustomer($data)
    {
        return (isset($data['company']) && $data['company'] != '' && $data['company'] != '/')
        && (total_rows(db_prefix() . 'clients', ['company' => $data['company']]) === 1);
    }

    private function addContactUnderCustomer($data)
    {
        $contactFields = $this->getContactFields();
        $this->ci->db->where('company', $data['company']);

        $existingCompany = $this->ci->db->get(db_prefix() . 'clients')->row();
        $tmpInsert       = [];

        foreach ($data as $key => $val) {
            foreach ($contactFields as $tmpContactField) {
                if (isset($data[$tmpContactField])) {
                    $tmpInsert[$tmpContactField] = $data[$tmpContactField];
                }
            }
        }
        $tmpInsert['donotsendwelcomeemail'] = true;

        if (isset($data['contact_phonenumber'])) {
            $tmpInsert['phonenumber'] = $data['contact_phonenumber'];
        }

        $this->ci->clients_model->add_contact($tmpInsert, $existingCompany->userid, true);
    }

    private function getContactFields()
    {
        return $this->ci->db->list_fields(db_prefix() . 'contacts');
    }

    private function isDuplicateContact($email)
    {
        return total_rows(db_prefix() . 'contacts', ['email' => $email]);
    }

    private function shouldMergeExistingCustomers()
    {
        return $this->ci->input->post('merge_existing') === '1';
    }

    private function findExistingCustomerMatch($data)
    {
        $email = strtolower(trim((string) ($data['email'] ?? '')));
        if ($email !== '') {
            $this->ci->db->select('id, userid');
            $this->ci->db->where('LOWER(email)', $email);
            $this->ci->db->limit(1);
            $contact = $this->ci->db->get(db_prefix() . 'contacts')->row();

            if ($contact) {
                return [
                    'customer_id' => (int) $contact->userid,
                    'contact_id'  => (int) $contact->id,
                    'matched_by'  => 'email',
                ];
            }
        }

        $incomingPhone = $this->normalizePhoneForMatch($data['contact_phonenumber'] ?? $data['phonenumber'] ?? '');
        if ($incomingPhone === '') {
            return null;
        }

        $company = $this->normalizeTextForMatch($data['company'] ?? '');
        if ($company !== '') {
            $this->ci->db->select('userid, company, phonenumber');
            $this->ci->db->where('LOWER(company)', $company);
            $clients = $this->ci->db->get(db_prefix() . 'clients')->result();

            foreach ($clients as $client) {
                $candidatePhones = [$client->phonenumber];
                $primaryContact  = $this->getPrimaryContactForCustomer((int) $client->userid);
                if ($primaryContact) {
                    $candidatePhones[] = $primaryContact->phonenumber;
                }

                foreach ($candidatePhones as $candidatePhone) {
                    if ($this->normalizePhoneForMatch($candidatePhone) === $incomingPhone) {
                        return [
                            'customer_id' => (int) $client->userid,
                            'contact_id'  => $primaryContact ? (int) $primaryContact->id : null,
                            'matched_by'  => 'company_phone',
                        ];
                    }
                }
            }
        }

        $firstName = $this->normalizeTextForMatch($data['firstname'] ?? '');
        $lastName  = $this->normalizeTextForMatch($data['lastname'] ?? '');

        if ($firstName !== '' || $lastName !== '') {
            $this->ci->db->select('id, userid, firstname, lastname, phonenumber');
            if ($firstName !== '') {
                $this->ci->db->where('LOWER(firstname)', $firstName);
            }
            if ($lastName !== '') {
                $this->ci->db->where('LOWER(lastname)', $lastName);
            }
            $contacts = $this->ci->db->get(db_prefix() . 'contacts')->result();

            foreach ($contacts as $contact) {
                if ($this->normalizePhoneForMatch($contact->phonenumber) === $incomingPhone) {
                    return [
                        'customer_id' => (int) $contact->userid,
                        'contact_id'  => (int) $contact->id,
                        'matched_by'  => 'name_phone',
                    ];
                }
            }
        }

        return null;
    }

    private function mergeIntoExistingCustomer($existingMatch, $insert)
    {
        $customerId = (int) $existingMatch['customer_id'];
        $contactId  = !empty($existingMatch['contact_id']) ? (int) $existingMatch['contact_id'] : null;

        if (!$contactId) {
            $primaryContact = $this->getPrimaryContactForCustomer($customerId);
            $contactId      = $primaryContact ? (int) $primaryContact->id : null;
        }

        $contactFields = $this->getContactFields();
        $contactUpdate = [];
        $clientUpdate  = [];

        foreach ($insert as $field => $value) {
            if ($value === '' || $value === null) {
                continue;
            }

            if (in_array($field, $contactFields)) {
                $targetField = $field === 'contact_phonenumber' ? 'phonenumber' : $field;
                $contactUpdate[$targetField] = $value;
            } else {
                $clientUpdate[$field] = $value;
            }
        }

        unset($clientUpdate['datecreated'], $clientUpdate['donotsendwelcomeemail'], $clientUpdate['is_primary']);
        unset($contactUpdate['donotsendwelcomeemail'], $contactUpdate['is_primary']);

        if ($contactId) {
            $existingContact = $this->ci->clients_model->get_contact($contactId);
            $contactUpdate   = $this->filterContactMergeFields($contactUpdate, $existingContact, $existingMatch['matched_by'] ?? '');

            if (!empty($contactUpdate)) {
                $this->ci->clients_model->update_contact($contactUpdate, $contactId);
            }
        }

        if (!empty($clientUpdate)) {
            $this->ci->clients_model->update($clientUpdate, $customerId);
        }

        if ($this->ci->input->post('groups_in[]')) {
            $this->insertCustomerGroups($this->ci->input->post('groups_in[]'), $customerId);
        }

        return [$customerId, $contactId];
    }

    private function filterContactMergeFields($contactUpdate, $existingContact, $matchedBy)
    {
        if (!$existingContact) {
            return $contactUpdate;
        }

        if ($matchedBy !== 'email' && !empty($existingContact->email)) {
            unset($contactUpdate['email']);
        }

        foreach (['firstname', 'lastname', 'title'] as $field) {
            if (
                isset($contactUpdate[$field]) &&
                isset($existingContact->{$field}) &&
                trim((string) $existingContact->{$field}) !== '' &&
                trim((string) $existingContact->{$field}) !== '/' &&
                $matchedBy !== 'email'
            ) {
                unset($contactUpdate[$field]);
            }
        }

        return $contactUpdate;
    }

    private function formatValuesForSimulation($values)
    {
        // ATM only country fields
        foreach ($this->countryFields as $country_field) {
            if (array_key_exists($country_field, $values)) {
                if (!empty($values[$country_field]) && is_numeric($values[$country_field])) {
                    $country = $this->getCountry(null, $values[$country_field]);
                    if ($country) {
                        $values[$country_field] = $country->short_name;
                    }
                }
            }
        }

        return $values;
    }

    private function mapInsertFromHeaders($row, $databaseFields, $headerMappings, &$duplicate)
    {
        $insert = [];
        $headerRow = $this->getHeaderRow();

        foreach ($databaseFields as $field) {
            if (!array_key_exists($field, $headerMappings)) {
                continue;
            }

            $columnIndex = $headerMappings[$field];

            if (!isset($row[$columnIndex])) {
                continue;
            }

            $sourceHeader = $headerRow[$columnIndex] ?? '';
            $insert[$field] = $this->prepareDatabaseValue($field, $row[$columnIndex], $row, $databaseFields, $duplicate, $headerMappings, $sourceHeader);
        }

        return $insert;
    }

    private function prepareDatabaseValue($field, $value, $row, $databaseFields, &$duplicate, $headerMappings = [], $sourceHeader = '')
    {
        $value = $this->checkNullValueAddedByUser($value);

        if (in_array($field, ['firstname', 'lastname'])) {
            $splitName = $this->splitFullNameValue($value, $sourceHeader);
            if ($splitName !== null) {
                $value = $field === 'firstname' ? $splitName['firstname'] : $splitName['lastname'];
            }
        }

        if (in_array($field, $this->requiredFields) &&
            $value == '' &&
            $field != 'company'
            && $field != 'email') {
            return '/';
        }

        if (in_array($field, $this->countryFields)) {
            return $this->countryValue($value);
        }

        if ($field == 'email') {
            if (!$this->shouldMergeExistingCustomers()) {
                $duplicate = $this->isDuplicateContact($value);
            }

            return $value;
        }

        if ($field == 'stripe_id') {
            if (empty($value) || (!empty($value) && !startsWith($value, 'cus_'))) {
                return null;
            }

            return $value;
        }

        if ($field == 'contact_phonenumber' && is_automatic_calling_codes_enabled() && !empty($value)) {
            $countryValue = $this->resolveCustomerCountryValue($row, $databaseFields, $headerMappings);

            if (!empty($countryValue)) {
                $customerCountry = $this->getCountry(null, $this->countryValue($countryValue));

                if ($customerCountry) {
                    $callingCode = '+' . ltrim($customerCountry->calling_code, '+');

                    if (startsWith($value, $customerCountry->calling_code)) {
                        return '+' . $value;
                    }

                    if (!startsWith($value, $callingCode)) {
                        return $callingCode . $value;
                    }
                }
            }
        }

        return $value;
    }

    private function resolveCustomerCountryValue($row, $databaseFields, $headerMappings = [])
    {
        if (!empty($headerMappings) && array_key_exists('country', $headerMappings)) {
            return $row[$headerMappings['country']] ?? null;
        }

        $customerCountryIndex = array_search('country', $databaseFields);

        if ($customerCountryIndex === false) {
            return null;
        }

        return $row[$customerCountryIndex] ?? null;
    }

    private function resolveHeaderMappings($databaseFields)
    {
        $headerRow = $this->getHeaderRow();

        if (empty($headerRow)) {
            return ['database' => [], 'custom' => []];
        }

        $normalizedHeaders = [];

        foreach ($headerRow as $index => $header) {
            $normalizedHeaders[$this->normalizeHeader($header)] = $index;
        }

        $postedMappings = $this->resolvePostedFieldMappings($normalizedHeaders);

        $databaseMappings = [];
        foreach ($databaseFields as $field) {
            if (isset($postedMappings['database'][$field])) {
                $databaseMappings[$field] = $postedMappings['database'][$field];
                continue;
            }

            foreach ($this->headerAliasesForDatabaseField($field) as $alias) {
                if (array_key_exists($alias, $normalizedHeaders)) {
                    $databaseMappings[$field] = $normalizedHeaders[$alias];
                    break;
                }
            }
        }

        $customMappings = $postedMappings['custom'];
        foreach ($this->getCustomFields() as $field) {
            if (isset($customMappings[$field['id']])) {
                continue;
            }

            $normalizedCustomField = $this->normalizeHeader($field['name']);
            if (array_key_exists($normalizedCustomField, $normalizedHeaders)) {
                $customMappings[$field['id']] = $normalizedHeaders[$normalizedCustomField];
            }
        }

        $requiredMatches = array_intersect_key(array_flip($this->requiredFields), $databaseMappings);
        $hasCombinedNameMapping = isset($normalizedHeaders['full_name']) || isset($normalizedHeaders['name']);
        $hasEmailMapping = isset($databaseMappings['email']);
        $hasSplitNameMappings = isset($databaseMappings['firstname']) && isset($databaseMappings['lastname']);

        if (!$hasEmailMapping || (!$hasSplitNameMappings && !$hasCombinedNameMapping)) {
            return ['database' => [], 'custom' => []];
        }

        return ['database' => $databaseMappings, 'custom' => $customMappings];
    }

    private function resolvePostedFieldMappings($normalizedHeaders)
    {
        $rawMappings = $this->ci->input->post('field_mappings');

        if (!is_array($rawMappings) || count($rawMappings) === 0) {
            return ['database' => [], 'custom' => []];
        }

        $databaseMappings = [];
        $customMappings   = [];

        foreach ($rawMappings as $sourceHeader => $targetField) {
            $sourceHeader = $this->normalizeHeader($sourceHeader);
            $targetField  = trim((string) $targetField);

            if ($sourceHeader === '' || $targetField === '' || !array_key_exists($sourceHeader, $normalizedHeaders)) {
                continue;
            }

            $columnIndex = $normalizedHeaders[$sourceHeader];

            if (startsWith($targetField, 'custom_field:')) {
                $customFieldId = (int) str_replace('custom_field:', '', $targetField);
                if ($customFieldId > 0) {
                    $customMappings[$customFieldId] = $columnIndex;
                }

                continue;
            }

            $databaseMappings[$targetField] = $columnIndex;
        }

        return ['database' => $databaseMappings, 'custom' => $customMappings];
    }

    private function headerAliasesForDatabaseField($field)
    {
        $aliases = [
            $this->normalizeHeader($field),
            $this->normalizeHeader($this->formatFieldNameForHeading($field)),
        ];

        $additionalAliases = [
            'firstname'           => ['first_name', 'given_name', 'full_name', 'name'],
            'lastname'            => ['last_name', 'surname', 'family_name', 'full_name', 'name'],
            'email'               => ['email_address'],
            'contact_phonenumber' => ['phone', 'phone_number', 'mobile', 'mobile_number', 'contact_phone'],
            'phonenumber'         => ['company_phone', 'business_phone'],
            'address'             => ['street', 'street_address', 'address_line_1', 'address_line1'],
            'zip'                 => ['postal_code', 'postcode', 'zip_code', 'postal'],
            'country'             => ['country_name'],
            'company'             => ['organization', 'organisation', 'company_name', 'business_name'],
        ];

        if (isset($additionalAliases[$field])) {
            $aliases = array_merge($aliases, $additionalAliases[$field]);
        }

        return array_values(array_unique($aliases));
    }

    private function normalizeHeader($value)
    {
        $value = strtolower(trim((string) $value));
        $value = preg_replace('/[^a-z0-9]+/', '_', $value);

        return trim($value, '_');
    }

    private function splitFullNameValue($value, $sourceHeader = '')
    {
        $normalizedHeader = $this->normalizeHeader($sourceHeader);

        if (!in_array($normalizedHeader, ['full_name', 'name'])) {
            return null;
        }

        $value = trim(preg_replace('/\s+/', ' ', (string) $value));

        if ($value === '') {
            return [
                'firstname' => '',
                'lastname'  => '',
            ];
        }

        $parts = explode(' ', $value, 2);

        return [
            'firstname' => $parts[0] ?? '',
            'lastname'  => $parts[1] ?? '',
        ];
    }

    private function getPrimaryContactForCustomer($customerId)
    {
        $this->ci->db->where('userid', $customerId);
        $this->ci->db->where('is_primary', 1);
        $this->ci->db->limit(1);

        return $this->ci->db->get(db_prefix() . 'contacts')->row();
    }

    private function normalizePhoneForMatch($value)
    {
        return preg_replace('/\D+/', '', (string) $value);
    }

    private function normalizeTextForMatch($value)
    {
        return strtolower(trim((string) $value));
    }

    private function handleMappedCustomFieldsInsert($rel_id, $row, $rowNumber, $customMappings)
    {
        foreach ($this->getCustomFields() as $field) {
            if (!isset($customMappings[$field['id']])) {
                continue;
            }

            $value = $row[$customMappings[$field['id']]] ?? '';

            if ($this->isSimulation()) {
                $this->simulationData[$rowNumber][$field['name']] = $value;
                continue;
            }

            if ($value != '' && $value !== 'NULL' && $value !== 'null') {
                if ($field['type'] === 'link' && !\app\services\utilities\Str::isHtml($value)) {
                    $value = sprintf('<a href="%s" target="_blank">%s</a>', $value, $value);
                }

                $this->ci->db->insert(db_prefix() . 'customfieldsvalues', [
                    'relid'   => $rel_id,
                    'fieldid' => $field['id'],
                    'value'   => trim($value),
                    'fieldto' => 'customers',
                ]);
            }
        }
    }

    private function getCountry($search = null, $id = null)
    {
        if ($search) {
            $searchSlug = slug_it($search);

            if (empty($search)) {
                return null;
            }

            if ($country = $this->ci->app_object_cache->get('import-country-search-' . $searchSlug)) {
                return $country;
            }

            $this->ci->db->where('iso2', $search);
            $this->ci->db->or_where('short_name', $search);
            $this->ci->db->or_where('long_name', $search);
        } else {
            if (empty($id)) {
                return null;
            }

            if ($country = $this->ci->app_object_cache->get('import-country-id-' . $id)) {
                return $country;
            }

            $this->ci->db->where('country_id', $id);
        }

        $country = $this->ci->db->get(db_prefix() . 'countries')->row();

        if ($search) {
            $this->ci->app_object_cache->add('import-country-search-' . $searchSlug, $country);
        } else {
            $this->ci->app_object_cache->add('import-country-id-' . $id, $country);
        }

        return $country;
    }

    private function countryValue($value)
    {
        if ($value != '') {
            if (!is_numeric($value)) {
                $country = $this->getCountry($value);
                $value   = $country ? $country->country_id : 0;
            }
        } else {
            $value = 0;
        }

        return $value;
    }
}
