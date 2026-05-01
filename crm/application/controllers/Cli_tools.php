<?php

defined('BASEPATH') or exit('No direct script access allowed');

// One-time operational controller. See D:\GithubRepos\AGENTS.md before changing import/broadcast workflows.
class Cli_tools extends App_Controller
{
    public function __construct()
    {
        parent::__construct();

        if (! $this->input->is_cli_request()) {
            show_404();
        }

        $this->load->model('authentication_model');
    }

    public function send_set_password_for_created($jsonFile = 'tmp_import_created_emails_all.json')
    {
        $jsonPath = FCPATH . ltrim((string) $jsonFile, '/\\');
        if (! is_file($jsonPath)) {
            echo json_encode([
                'success' => false,
                'message' => 'Created-email JSON file not found.',
                'path' => $jsonPath,
            ], JSON_PRETTY_PRINT), PHP_EOL;

            return;
        }

        $json = json_decode((string) file_get_contents($jsonPath), true);
        $emails = $json['emails'] ?? $json;
        if (! is_array($emails)) {
            echo json_encode([
                'success' => false,
                'message' => 'Created-email JSON is invalid.',
                'path' => $jsonPath,
            ], JSON_PRETTY_PRINT), PHP_EOL;

            return;
        }

        $stats = [
            'rows_scanned' => 0,
            'sent' => 0,
            'failed' => 0,
            'skipped_invalid' => 0,
            'skipped_missing_contact' => 0,
            'skipped_inactive' => 0,
        ];
        $errors = [];

        foreach ($emails as $email) {
            $stats['rows_scanned']++;
            $email = strtolower(trim((string) $email));
            if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $stats['skipped_invalid']++;
                continue;
            }

            $this->db->where('email', $email);
            $contact = $this->db->get(db_prefix() . 'contacts')->row();
            if (! $contact) {
                $stats['skipped_missing_contact']++;
                continue;
            }

            if ((int) $contact->active !== 1) {
                $stats['skipped_inactive']++;
                continue;
            }

            try {
                if ($this->authentication_model->set_password_email($email)) {
                    $stats['sent']++;
                } else {
                    $stats['failed']++;
                    $errors[] = [
                        'email' => $email,
                        'message' => 'set_password_email returned false.',
                    ];
                }
            } catch (Throwable $e) {
                $stats['failed']++;
                $errors[] = [
                    'email' => $email,
                    'message' => $e->getMessage(),
                ];
            }
        }

        echo json_encode([
            'success' => true,
            'file' => basename($jsonPath),
            'stats' => $stats,
            'errors' => array_slice($errors, 0, 20),
        ], JSON_PRETTY_PRINT), PHP_EOL;
    }
}
