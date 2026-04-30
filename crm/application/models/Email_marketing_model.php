<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Email_marketing_model extends App_Model
{
    private string $templatesTable;
    private string $campaignsTable;

    public function __construct()
    {
        parent::__construct();

        $this->templatesTable = db_prefix() . 'email_marketing_templates';
        $this->campaignsTable = db_prefix() . 'email_marketing_campaigns';

        $this->ensure_tables_exist();
    }

    public function get_templates()
    {
        return $this->db->order_by('updated_at', 'DESC')->get($this->templatesTable)->result_array();
    }

    public function get_template($id)
    {
        return $this->db->where('id', $id)->get($this->templatesTable)->row_array();
    }

    public function get_campaign($id)
    {
        return $this->db->where('id', $id)->get($this->campaignsTable)->row_array();
    }

    public function count_recipients($startFromContactId = 0)
    {
        $this->db->from(db_prefix() . 'contacts');
        $this->db->where('active', 1);
        $this->db->where('TRIM(COALESCE(email, "")) !=', '');
        if ($startFromContactId > 0) {
            $this->db->where('id >=', $startFromContactId);
        }

        return (int) $this->db->count_all_results();
    }

    public function save_template($name, $subject, $message)
    {
        $name = trim((string) $name);
        if ($name === '') {
            $name = trim((string) $subject);
        }

        $existing = $this->db
            ->where('subject', $subject)
            ->where('message', $message)
            ->limit(1)
            ->get($this->templatesTable)
            ->row_array();

        $payload = [
            'name'       => $name,
            'subject'    => $subject,
            'message'    => $message,
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        if ($existing) {
            $this->db->where('id', $existing['id'])->update($this->templatesTable, $payload);

            return (int) $existing['id'];
        }

        $payload['created_by'] = get_staff_user_id();
        $payload['created_at'] = date('Y-m-d H:i:s');
        $this->db->insert($this->templatesTable, $payload);

        return (int) $this->db->insert_id();
    }

    public function create_campaign($data)
    {
        $startFrom = max(0, (int) ($data['start_from_contact_id'] ?? 0));
        $batchSize = max(1, min(500, (int) ($data['batch_size'] ?? 25)));
        $cooldown  = max(1, min(3600, (int) ($data['cooling_seconds'] ?? 10)));
        $total     = $this->count_recipients($startFrom);

        $templateId = $this->save_template(
            $data['template_name'] ?? $data['subject'],
            $data['subject'],
            $data['message']
        );

        $payload = [
            'template_id'               => $templateId,
            'subject'                   => $data['subject'],
            'message'                   => $data['message'],
            'start_from_contact_id'     => $startFrom,
            'last_processed_contact_id' => 0,
            'batch_size'                => $batchSize,
            'cooling_seconds'           => $cooldown,
            'total_recipients'          => $total,
            'processed_count'           => 0,
            'sent_count'                => 0,
            'failed_count'              => 0,
            'status'                    => $total > 0 ? 'running' : 'completed',
            'created_by'                => get_staff_user_id(),
            'created_at'                => date('Y-m-d H:i:s'),
            'updated_at'                => date('Y-m-d H:i:s'),
        ];

        $this->db->insert($this->campaignsTable, $payload);

        return (int) $this->db->insert_id();
    }

    public function process_campaign_batch($campaignId)
    {
        $campaign = $this->get_campaign($campaignId);
        if (!$campaign) {
            return ['success' => false, 'message' => 'Campaign not found.'];
        }

        if ($campaign['status'] === 'completed') {
            return ['success' => true, 'campaign' => $this->decorate_campaign($campaign)];
        }

        $contacts = $this->get_next_contacts_batch($campaign);
        if (count($contacts) === 0) {
            $this->db->where('id', $campaignId)->update($this->campaignsTable, [
                'status'     => 'completed',
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

            return ['success' => true, 'campaign' => $this->decorate_campaign($this->get_campaign($campaignId))];
        }

        $this->load->model('emails_model');

        $processed = (int) $campaign['processed_count'];
        $sent      = (int) $campaign['sent_count'];
        $failed    = (int) $campaign['failed_count'];
        $lastId    = (int) $campaign['last_processed_contact_id'];

        foreach ($contacts as $contact) {
            $processed++;
            $lastId = (int) $contact['id'];

            if (!valid_email($contact['email'])) {
                $failed++;
                continue;
            }

            if ($this->emails_model->send_simple_email($contact['email'], $campaign['subject'], $campaign['message'])) {
                $sent++;
            } else {
                $failed++;
            }
        }

        $status = $processed >= (int) $campaign['total_recipients'] ? 'completed' : 'running';

        $this->db->where('id', $campaignId)->update($this->campaignsTable, [
            'last_processed_contact_id' => $lastId,
            'processed_count'           => $processed,
            'sent_count'                => $sent,
            'failed_count'              => $failed,
            'status'                    => $status,
            'updated_at'                => date('Y-m-d H:i:s'),
        ]);

        return ['success' => true, 'campaign' => $this->decorate_campaign($this->get_campaign($campaignId))];
    }

    public function decorate_campaign(array $campaign)
    {
        $campaign['remaining_count'] = max(0, (int) $campaign['total_recipients'] - (int) $campaign['processed_count']);
        $campaign['progress_percent'] = (int) $campaign['total_recipients'] > 0
            ? round(((int) $campaign['processed_count'] / (int) $campaign['total_recipients']) * 100, 2)
            : 100;

        return $campaign;
    }

    private function get_next_contacts_batch(array $campaign)
    {
        $this->db->select('id, firstname, lastname, email');
        $this->db->from(db_prefix() . 'contacts');
        $this->db->where('active', 1);
        $this->db->where('TRIM(COALESCE(email, "")) !=', '');

        $startFrom = max((int) $campaign['start_from_contact_id'], 0);
        $lastId    = (int) $campaign['last_processed_contact_id'];

        if ($lastId > 0) {
            $this->db->where('id >', $lastId);
        } elseif ($startFrom > 0) {
            $this->db->where('id >=', $startFrom);
        }

        $this->db->order_by('id', 'ASC');
        $this->db->limit((int) $campaign['batch_size']);

        return $this->db->get()->result_array();
    }

    private function ensure_tables_exist()
    {
        $charset = $this->db->char_set ?: 'utf8mb4';
        $collation = $this->db->dbcollat ?: 'utf8mb4_unicode_ci';

        $this->db->query("
            CREATE TABLE IF NOT EXISTS `{$this->templatesTable}` (
                `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `name` VARCHAR(191) NOT NULL,
                `subject` VARCHAR(255) NOT NULL,
                `message` MEDIUMTEXT NOT NULL,
                `created_by` INT UNSIGNED NOT NULL DEFAULT 0,
                `created_at` DATETIME NOT NULL,
                `updated_at` DATETIME NOT NULL,
                PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET={$charset} COLLATE={$collation};
        ");

        $this->db->query("
            CREATE TABLE IF NOT EXISTS `{$this->campaignsTable}` (
                `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `template_id` INT UNSIGNED NULL DEFAULT NULL,
                `subject` VARCHAR(255) NOT NULL,
                `message` MEDIUMTEXT NOT NULL,
                `start_from_contact_id` INT UNSIGNED NOT NULL DEFAULT 0,
                `last_processed_contact_id` INT UNSIGNED NOT NULL DEFAULT 0,
                `batch_size` INT UNSIGNED NOT NULL DEFAULT 25,
                `cooling_seconds` INT UNSIGNED NOT NULL DEFAULT 10,
                `total_recipients` INT UNSIGNED NOT NULL DEFAULT 0,
                `processed_count` INT UNSIGNED NOT NULL DEFAULT 0,
                `sent_count` INT UNSIGNED NOT NULL DEFAULT 0,
                `failed_count` INT UNSIGNED NOT NULL DEFAULT 0,
                `status` VARCHAR(30) NOT NULL DEFAULT 'running',
                `created_by` INT UNSIGNED NOT NULL DEFAULT 0,
                `created_at` DATETIME NOT NULL,
                `updated_at` DATETIME NOT NULL,
                PRIMARY KEY (`id`),
                KEY `status_idx` (`status`),
                KEY `last_processed_contact_id_idx` (`last_processed_contact_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET={$charset} COLLATE={$collation};
        ");
    }
}
