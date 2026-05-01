<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * One-off operational CLI tasks for the CRM.
 * Future agents: read D:\GithubRepos\AGENTS.md before changing this workflow.
 */
class Bulk_tickets extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();

        if (! $this->input->is_cli_request()) {
            show_404();
        }

        $this->load->model('tickets_model');
    }

    public function onboarding_invitation()
    {
        $predefinedReplyId = 94;
        $subject           = 'Onboarding Invitation - US Capital Private Bank, ETO';
        $departmentName    = 'Customer Service';
        $statusName        = 'Staff Initiated';

        $openedBy = $this->db
            ->select('staffid')
            ->from(db_prefix() . 'staff')
            ->where('active', 1)
            ->order_by('admin', 'DESC')
            ->order_by('staffid', 'ASC')
            ->limit(1)
            ->get()
            ->row();

        if (! $openedBy) {
            echo "No active staff member found to open tickets.\n";

            return;
        }

        $department = $this->db
            ->select('departmentid')
            ->from(db_prefix() . 'departments')
            ->where('name', $departmentName)
            ->limit(1)
            ->get()
            ->row();

        if (! $department) {
            echo "Department not found: {$departmentName}\n";

            return;
        }

        $status = $this->db
            ->query(
                'SELECT ticketstatusid, name FROM ' . db_prefix() . 'tickets_status WHERE LOWER(REPLACE(name, "-", " ")) = ? LIMIT 1',
                [strtolower(str_replace('-', ' ', $statusName))]
            )
            ->row();

        if (! $status) {
            echo "Ticket status not found: {$statusName}\n";

            return;
        }

        $predefinedReply = $this->tickets_model->get_predefined_reply($predefinedReplyId);

        if (! $predefinedReply || empty($predefinedReply->message)) {
            echo "Predefined reply {$predefinedReplyId} not found or empty.\n";

            return;
        }

        $message = $predefinedReply->message;

        $contacts = $this->db
            ->select('id, userid, firstname, lastname, email')
            ->from(db_prefix() . 'contacts')
            ->where('email !=', '')
            ->where('email IS NOT NULL', null, false)
            ->get()
            ->result();

        $staff = $this->db
            ->select('staffid, firstname, lastname, email')
            ->from(db_prefix() . 'staff')
            ->where('active', 1)
            ->where('email !=', '')
            ->where('email IS NOT NULL', null, false)
            ->get()
            ->result();

        $seenEmails        = [];
        $createdTickets    = 0;
        $skippedDuplicates = 0;
        $failedTickets     = 0;

        foreach ($contacts as $contact) {
            $email = trim((string) $contact->email);

            if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                continue;
            }

            $emailKey = strtolower($email);
            if (isset($seenEmails[$emailKey])) {
                $skippedDuplicates++;

                continue;
            }

            $seenEmails[$emailKey] = true;

            $data = [
                'userid'      => (int) $contact->userid,
                'contactid'   => (int) $contact->id,
                'name'        => trim($contact->firstname . ' ' . $contact->lastname) ?: $email,
                'email'       => $email,
                'subject'     => $subject,
                'message'     => $message,
                'department'  => (int) $department->departmentid,
                'status'      => (int) $status->ticketstatusid,
                'priority'    => 0,
                'service'     => 0,
                'assigned'    => 0,
                'project_id'  => 0,
            ];

            if ($this->tickets_model->add($data, (int) $openedBy->staffid)) {
                $createdTickets++;
            } else {
                $failedTickets++;
            }
        }

        foreach ($staff as $member) {
            $email = trim((string) $member->email);

            if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                continue;
            }

            $emailKey = strtolower($email);
            if (isset($seenEmails[$emailKey])) {
                $skippedDuplicates++;

                continue;
            }

            $seenEmails[$emailKey] = true;

            $data = [
                'name'       => trim($member->firstname . ' ' . $member->lastname) ?: $email,
                'email'      => $email,
                'subject'    => $subject,
                'message'    => $message,
                'department' => (int) $department->departmentid,
                'status'     => (int) $status->ticketstatusid,
                'priority'   => 0,
                'service'    => 0,
                'assigned'   => 0,
                'project_id' => 0,
            ];

            if ($this->tickets_model->add($data, (int) $openedBy->staffid)) {
                $createdTickets++;
            } else {
                $failedTickets++;
            }
        }

        echo "Bulk onboarding invitation complete.\n";
        echo "Status used: {$status->name} (#{$status->ticketstatusid})\n";
        echo "Department: {$departmentName} (#{$department->departmentid})\n";
        echo "Created tickets: {$createdTickets}\n";
        echo "Skipped duplicate emails: {$skippedDuplicates}\n";
        echo "Failed tickets: {$failedTickets}\n";
    }
}
