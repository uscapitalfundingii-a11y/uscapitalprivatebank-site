<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Company_context_model extends App_Model
{
    public function get_companies($activeOnly = true)
    {
        if ($activeOnly) {
            $this->db->where('active', 1);
        }

        return $this->db
            ->order_by('sort_order', 'ASC')
            ->order_by('name', 'ASC')
            ->get(db_prefix() . 'company_context_companies')
            ->result_array();
    }

    public function get_accessible_companies($staffId, $admin = false)
    {
        if ($admin) {
            return $this->get_companies(true);
        }

        return $this->db
            ->select('c.*')
            ->from(db_prefix() . 'company_context_companies c')
            ->join(db_prefix() . 'company_context_staff cs', 'cs.company_id = c.id AND cs.can_view = 1', 'inner')
            ->where('c.active', 1)
            ->where('cs.staffid', (int) $staffId)
            ->order_by('c.sort_order', 'ASC')
            ->get()
            ->result_array();
    }

    public function get_company($companyId)
    {
        return $this->db
            ->where('id', (int) $companyId)
            ->get(db_prefix() . 'company_context_companies')
            ->row_array();
    }

    public function get_company_by_slug($slug)
    {
        return $this->db
            ->where('slug', $this->normalise_slug($slug))
            ->get(db_prefix() . 'company_context_companies')
            ->row_array();
    }

    public function get_current_company()
    {
        $companyId = (int) $this->session->userdata(COMPANY_CONTEXT_SESSION_KEY);
        if ($companyId <= 0) {
            return null;
        }

        return $this->get_company($companyId);
    }

    public function resolve_company_from_metadata($metadata)
    {
        $metadata = is_array($metadata) ? $metadata : [];
        if (!empty($metadata['company_id']) && is_numeric($metadata['company_id'])) {
            $company = $this->get_company((int) $metadata['company_id']);
            if ($company) {
                return $company;
            }
        }

        foreach (['source_company', 'source_brand', 'source_site'] as $field) {
            if (empty($metadata[$field])) {
                continue;
            }
            $company = $this->match_company((string) $metadata[$field]);
            if ($company) {
                return $company;
            }
        }

        return null;
    }

    public function match_company($value)
    {
        $value = trim(strtolower((string) $value));
        if ($value === '') {
            return null;
        }

        $slug = $this->normalise_slug($value);
        $companies = $this->get_companies(true);
        foreach ($companies as $company) {
            $companySlug = strtolower((string) $company['slug']);
            $companyName = strtolower((string) $company['name']);
            $domain = strtolower((string) $company['primary_domain']);
            $aliases = $this->domain_aliases($company['domain_aliases'] ?? '');

            if ($slug === $companySlug || $value === $companyName) {
                return $company;
            }
            if ($domain !== '' && strpos($value, $domain) !== false) {
                return $company;
            }
            foreach ($aliases as $alias) {
                if ($alias !== '' && strpos($value, $alias) !== false) {
                    return $company;
                }
            }
            if ($companySlug !== '' && strpos($slug, $companySlug) !== false) {
                return $company;
            }
        }

        return null;
    }

    public function domain_aliases($aliases)
    {
        $parts = preg_split('/[\r\n,;]+/', (string) $aliases);
        $clean = [];
        foreach ($parts as $part) {
            $part = strtolower(trim($part));
            $part = preg_replace('/^https?:\/\//', '', $part);
            $part = preg_replace('/^www\./', '', $part);
            $part = trim($part, "/ \t\n\r\0\x0B");
            if ($part !== '') {
                $clean[] = $part;
            }
        }

        return array_values(array_unique($clean));
    }

    public function normalise_slug($value)
    {
        $value = strtolower(trim((string) $value));
        $value = preg_replace('/^https?:\/\//', '', $value);
        $value = preg_replace('/^www\./', '', $value);
        $value = preg_replace('/[^a-z0-9]+/', '-', $value);
        $value = trim($value, '-');

        return $value;
    }

    public function set_current_company($companyId)
    {
        $companyId = (int) $companyId;
        if ($companyId <= 0) {
            $this->session->unset_userdata(COMPANY_CONTEXT_SESSION_KEY);
            return true;
        }

        $company = $this->get_company($companyId);
        if (!$company) {
            return false;
        }

        $this->session->set_userdata(COMPANY_CONTEXT_SESSION_KEY, $companyId);
        return true;
    }

    public function upsert_record_map($companyId, $relType, $relId, $data = [])
    {
        $companyId = (int) $companyId;
        $relId = (int) $relId;
        $relType = trim((string) $relType);
        if ($companyId <= 0 || $relId <= 0 || $relType === '') {
            return false;
        }

        $table = db_prefix() . 'company_context_record_map';
        $existing = $this->db
            ->where('company_id', $companyId)
            ->where('rel_type', $relType)
            ->where('rel_id', $relId)
            ->get($table)
            ->row_array();

        $payload = [
            'company_id'  => $companyId,
            'rel_type'    => substr($relType, 0, 60),
            'rel_id'      => $relId,
            'source_site' => isset($data['source_site']) ? substr((string) $data['source_site'], 0, 191) : null,
            'source_path' => isset($data['source_path']) ? substr((string) $data['source_path'], 0, 255) : null,
            'origin'      => isset($data['origin']) ? substr((string) $data['origin'], 0, 80) : null,
        ];

        if ($existing) {
            $payload['updated_at'] = date('Y-m-d H:i:s');
            $this->db->where('id', (int) $existing['id'])->update($table, $payload);
            return (int) $existing['id'];
        }

        $payload['created_at'] = date('Y-m-d H:i:s');
        $this->db->insert($table, $payload);
        return (int) $this->db->insert_id();
    }

    public function get_staff_lanes($companyId = 0)
    {
        if ((int) $companyId < 0) {
            return [];
        }

        $this->db
            ->select('cs.*,c.name as company_name,c.slug as company_slug,s.firstname,s.lastname,s.email,s.active')
            ->from(db_prefix() . 'company_context_staff cs')
            ->join(db_prefix() . 'company_context_companies c', 'c.id = cs.company_id', 'inner')
            ->join(db_prefix() . 'staff s', 's.staffid = cs.staffid', 'left');

        if ((int) $companyId > 0) {
            $this->db->where('cs.company_id', (int) $companyId);
        }

        return $this->db
            ->order_by('c.sort_order', 'ASC')
            ->order_by('cs.is_default_owner', 'DESC')
            ->order_by('s.firstname', 'ASC')
            ->get()
            ->result_array();
    }

    public function get_recent_company_tickets($companyId = 0, $limit = 50)
    {
        if ((int) $companyId < 0) {
            return [];
        }

        $this->db
            ->select('m.*,c.name as company_name,t.subject,t.status,t.assigned,t.date,t.lastreply,ts.name as status_name,cl.company as client_name')
            ->from(db_prefix() . 'company_context_record_map m')
            ->join(db_prefix() . 'company_context_companies c', 'c.id = m.company_id', 'inner')
            ->join(db_prefix() . 'tickets t', 't.ticketid = m.rel_id AND m.rel_type = "ticket"', 'inner', false)
            ->join(db_prefix() . 'tickets_status ts', 'ts.ticketstatusid = t.status', 'left')
            ->join(db_prefix() . 'clients cl', 'cl.userid = t.userid', 'left');

        if ((int) $companyId > 0) {
            $this->db->where('m.company_id', (int) $companyId);
        }

        return $this->db
            ->order_by('m.created_at', 'DESC')
            ->limit((int) $limit)
            ->get()
            ->result_array();
    }

    public function update_company($companyId, $data)
    {
        $allowed = [
            'name',
            'public_label',
            'primary_domain',
            'domain_aliases',
            'support_email',
            'default_from_email',
            'reply_to_email',
            'bounce_email',
            'allowed_sender_domains',
            'mailbox_owner_staffid',
            'support_url',
            'primary_color',
            'secondary_color',
            'accent_color',
            'logo_url',
            'default_department_id',
            'default_staffid',
            'active',
            'sort_order',
        ];

        $payload = [];
        foreach ($allowed as $field) {
            if (array_key_exists($field, $data)) {
                $payload[$field] = $data[$field];
            }
        }

        if (!$payload) {
            return false;
        }

        $payload['updated_at'] = date('Y-m-d H:i:s');
        return $this->db->where('id', (int) $companyId)->update(db_prefix() . 'company_context_companies', $payload);
    }
}
