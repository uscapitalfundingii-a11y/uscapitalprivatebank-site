<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Dashboard extends AdminController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('dashboard_model');
    }

    // This is admin dashboard view
    public function index()
    {
        try {
            close_setup_menu();

            // Emergency load-shed: keep authenticated staff out of the heavy Perfex
            // dashboard while the shared DreamHost host is overloaded.
            redirect(admin_url('home'));
            return;

            $this->load->model('departments_model');
            $this->load->model('todo_model');
            $data['departments'] = $this->departments_model->get();

            $data['todos'] = $this->todo_model->get_todo_items(0);
            // Only show last 5 finished todo items
            $this->todo_model->setTodosLimit(5);
            $data['todos_finished']            = $this->todo_model->get_todo_items(1);
            $data['upcoming_events_next_week'] = $this->dashboard_model->get_upcoming_events_next_week();
            $data['upcoming_events']           = $this->dashboard_model->get_upcoming_events();
            $data['title']                     = _l('dashboard_string');

            $this->load->model('contracts_model');
            $data['expiringContracts'] = $this->contracts_model->get_contracts_about_to_expire(get_staff_user_id());

            $this->load->model('currencies_model');
            $data['currencies']    = $this->currencies_model->get();
            $data['base_currency'] = $this->currencies_model->get_base_currency();
            $data['activity_log']  = $this->misc_model->get_activity_log();
            // Tickets charts
            $tickets_awaiting_reply_by_status     = $this->dashboard_model->tickets_awaiting_reply_by_status();
            $tickets_awaiting_reply_by_department = $this->dashboard_model->tickets_awaiting_reply_by_department();

            $data['tickets_reply_by_status']              = json_encode($tickets_awaiting_reply_by_status);
            $data['tickets_awaiting_reply_by_department'] = json_encode($tickets_awaiting_reply_by_department);

            $data['tickets_reply_by_status_no_json']              = $tickets_awaiting_reply_by_status;
            $data['tickets_awaiting_reply_by_department_no_json'] = $tickets_awaiting_reply_by_department;

            $data['projects_status_stats'] = json_encode($this->dashboard_model->projects_status_stats());
            $data['leads_status_stats']    = json_encode($this->dashboard_model->leads_status_stats());
            $data['google_ids_calendars']  = $this->misc_model->get_google_calendar_ids();
            $data['bodyclass']             = 'dashboard invoices-total-manual';
            $this->load->model('announcements_model');
            $data['staff_announcements']             = $this->announcements_model->get();
            $data['total_undismissed_announcements'] = $this->announcements_model->get_total_undismissed_announcements();

            $this->load->model('projects_model');
            $data['projects_activity'] = $this->projects_model->get_activity('', hooks()->apply_filters('projects_activity_dashboard_limit', 20));
            add_calendar_assets();
            $this->load->model('utilities_model');
            $this->load->model('estimates_model');
            $data['estimate_statuses'] = $this->estimates_model->get_statuses();

            $this->load->model('proposals_model');
            $data['proposal_statuses'] = $this->proposals_model->get_statuses();

            $wps_currency = 'undefined';
            if (is_using_multiple_currencies()) {
                $wps_currency = $data['base_currency']->id;
            }
            $data['weekly_payment_stats'] = json_encode($this->dashboard_model->get_weekly_payments_statistics($wps_currency));

            $data['dashboard'] = true;

            $data['user_dashboard_visibility'] = get_staff_meta(get_staff_user_id(), 'dashboard_widgets_visibility');

            if (! $data['user_dashboard_visibility']) {
                $data['user_dashboard_visibility'] = [];
            } else {
                $data['user_dashboard_visibility'] = unserialize($data['user_dashboard_visibility']);
            }
            $data['user_dashboard_visibility'] = json_encode($data['user_dashboard_visibility']);

            $data['tickets_report'] = [];
            if (is_admin()) {
                $data['tickets_report'] = (new app\services\TicketsReportByStaff())->filterBy('this_month');
            }

            $data['uscap_dashboard_stats'] = $this->get_uscap_dashboard_stats();

            $data = hooks()->apply_filters('before_dashboard_render', $data);
            $this->load->view('admin/dashboard/dashboard', $data);
        } catch (Throwable $e) {
            $this->log_dashboard_emergency($e);
            $this->load->view('admin/dashboard/emergency', [
                'title' => 'CRM Admin Recovery',
                'error_reference' => date('Y-m-d H:i:s'),
            ]);
        }
    }

    private function log_dashboard_emergency(Throwable $e)
    {
        $line = sprintf(
            "[%s] %s in %s:%s\n%s\n\n",
            date('c'),
            $e->getMessage(),
            $e->getFile(),
            $e->getLine(),
            $e->getTraceAsString()
        );

        @file_put_contents(FCPATH . 'admin_dashboard_emergency.log', $line, FILE_APPEND);
    }

    private function get_uscap_dashboard_stats()
    {
        $openTicketWhere = 'merged_ticket_id IS NULL AND status IN (1,2,4)';
        if (!is_admin() && get_option('staff_access_only_assigned_departments') == 1) {
            $staffDepartments = $this->departments_model->get_staff_departments(get_staff_user_id(), true);
            if (count($staffDepartments) > 0) {
                $openTicketWhere .= ' AND department IN (' . implode(',', array_map('intval', $staffDepartments)) . ')';
            }
        }

        $overdueTaskWhere = 'status != ' . Tasks_model::STATUS_COMPLETE . ' AND duedate IS NOT NULL AND duedate < "' . date('Y-m-d') . '"';
        if (staff_cant('view', 'tasks')) {
            $overdueTaskWhere .= ' AND id IN (SELECT taskid FROM ' . db_prefix() . 'task_assigned WHERE staffid = ' . get_staff_user_id() . ')';
        }

        $stats = [
            [
                'label' => 'Tickets Needing Reply',
                'value' => total_rows(db_prefix() . 'tickets', $openTicketWhere),
                'href'  => admin_url('tickets'),
                'icon'  => 'fa-life-ring',
                'tone'  => 'danger',
            ],
            [
                'label' => 'New Leads',
                'value' => total_rows(db_prefix() . 'leads', 'junk=0 AND lost=0 AND status=0'),
                'href'  => admin_url('leads'),
                'icon'  => 'fa-filter',
                'tone'  => 'info',
            ],
            [
                'label' => 'Customers',
                'value' => total_rows(db_prefix() . 'clients', 'active=1'),
                'href'  => admin_url('clients'),
                'icon'  => 'fa-building',
                'tone'  => 'primary',
            ],
            [
                'label' => 'Overdue Tasks',
                'value' => total_rows(db_prefix() . 'tasks', $overdueTaskWhere),
                'href'  => admin_url('tasks?filter=overdue'),
                'icon'  => 'fa-list-check',
                'tone'  => 'warning',
            ],
            [
                'label' => 'This Week Events',
                'value' => count($this->dashboard_model->get_upcoming_events()),
                'href'  => admin_url('utilities/calendar'),
                'icon'  => 'fa-calendar-days',
                'tone'  => 'success',
            ],
            [
                'label' => 'Mailbox',
                'value' => 'Open',
                'href'  => admin_url('mailbox/folder/inbox'),
                'icon'  => 'fa-envelope',
                'tone'  => 'default',
            ],
        ];

        return hooks()->apply_filters('uscap_dashboard_stats', $stats);
    }

    // Chart weekly payments statistics on home page / ajax
    public function weekly_payments_statistics($currency)
    {
        if ($this->input->is_ajax_request()) {
            echo json_encode($this->dashboard_model->get_weekly_payments_statistics($currency));

            exit();
        }
    }

    // Chart monthly payments statistics on home page / ajax
    public function monthly_payments_statistics($currency)
    {
        if ($this->input->is_ajax_request()) {
            echo json_encode($this->dashboard_model->get_monthly_payments_statistics($currency));

            exit();
        }
    }

    public function ticket_widget($type)
    {
        $data['tickets_report'] = (new app\services\TicketsReportByStaff())->filterBy($type);
        $this->load->view('admin/dashboard/widgets/tickets_report_table', $data);
    }
}
