<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Home extends AdminController
{
    public function index()
    {
        close_setup_menu();

        $data['title'] = 'CRM Command Dashboard';
        $data['dashboard'] = $this->build_dashboard_payload();
        $this->load->view('admin/home', $data);
    }

    private function build_dashboard_payload()
    {
        // Emergency load-shed: keep the staff landing page useful without
        // running many count queries while the shared host is overloaded.
        return [
            'hero_metrics' => [
                [
                    'label' => 'Support Queue',
                    'value' => 'Open',
                    'caption' => 'client tickets and requests',
                    'href' => admin_url('tickets'),
                    'icon' => 'fa-life-ring',
                    'tone' => 'blue',
                ],
                [
                    'label' => 'Customers',
                    'value' => 'Open',
                    'caption' => 'client records and contacts',
                    'href' => admin_url('clients'),
                    'icon' => 'fa-users',
                    'tone' => 'green',
                ],
                [
                    'label' => 'Leads',
                    'value' => 'Open',
                    'caption' => 'new opportunities',
                    'href' => admin_url('leads'),
                    'icon' => 'fa-bolt',
                    'tone' => 'purple',
                ],
                [
                    'label' => 'Tasks',
                    'value' => 'Open',
                    'caption' => 'staff follow-up work',
                    'href' => admin_url('tasks'),
                    'icon' => 'fa-triangle-exclamation',
                    'tone' => 'orange',
                ],
            ],
            'workload' => [
                ['label' => 'Support Tickets', 'value' => 0, 'total' => 1, 'href' => admin_url('tickets'), 'tone' => 'blue'],
                ['label' => 'Tasks', 'value' => 0, 'total' => 1, 'href' => admin_url('tasks'), 'tone' => 'orange'],
                ['label' => 'Projects', 'value' => 0, 'total' => 1, 'href' => admin_url('projects'), 'tone' => 'green'],
                ['label' => 'Leads', 'value' => 0, 'total' => 1, 'href' => admin_url('leads'), 'tone' => 'purple'],
            ],
            'finance' => [
                ['label' => 'Invoices', 'value' => 'Open', 'href' => admin_url('invoices'), 'icon' => 'fa-file-invoice-dollar'],
                ['label' => 'Estimates', 'value' => 'Open', 'href' => admin_url('estimates'), 'icon' => 'fa-calculator'],
                ['label' => 'Proposals', 'value' => 'Open', 'href' => admin_url('proposals'), 'icon' => 'fa-file-signature'],
                ['label' => 'Contracts', 'value' => 'Open', 'href' => admin_url('contracts'), 'icon' => 'fa-file-contract'],
                ['label' => 'Expenses', 'value' => 'Open', 'href' => admin_url('expenses'), 'icon' => 'fa-receipt'],
                ['label' => 'Reports', 'value' => 'Open', 'href' => admin_url('reports'), 'icon' => 'fa-chart-line'],
            ],
            'operations' => [
                ['label' => 'Appointments', 'value' => 'Open', 'href' => admin_url('appointment_manager'), 'icon' => 'fa-calendar-check'],
                ['label' => 'Chat', 'value' => 'Open', 'href' => admin_url('prchat/Prchat_Controller/chat_full_view'), 'icon' => 'fa-comments'],
                ['label' => 'Mailbox', 'value' => 'Open', 'href' => admin_url('mailbox/folder/inbox'), 'icon' => 'fa-envelope'],
                ['label' => 'Staff', 'value' => 'Open', 'href' => admin_url('staff'), 'icon' => 'fa-user-tie'],
                ['label' => 'Knowledge base', 'value' => 'FAQ', 'href' => admin_url('knowledge_base'), 'icon' => 'fa-book-open'],
                ['label' => 'Email settings', 'value' => 'Setup', 'href' => admin_url('settings?group=email'), 'icon' => 'fa-gear'],
            ],
            'quick_actions' => [
                ['label' => 'Support Tickets', 'href' => admin_url('tickets'), 'icon' => 'fa-headset', 'desc' => 'Reply, assign, and resolve client requests'],
                ['label' => 'Customers', 'href' => admin_url('clients'), 'icon' => 'fa-address-book', 'desc' => 'Open client records and contacts'],
                ['label' => 'Leads', 'href' => admin_url('leads'), 'icon' => 'fa-filter-circle-dollar', 'desc' => 'Review new opportunities'],
                ['label' => 'Projects', 'href' => admin_url('projects'), 'icon' => 'fa-diagram-project', 'desc' => 'Track active delivery work'],
                ['label' => 'Tasks', 'href' => admin_url('tasks'), 'icon' => 'fa-list-check', 'desc' => 'Clear staff follow-up work'],
                ['label' => 'Mailbox', 'href' => admin_url('mailbox/folder/inbox'), 'icon' => 'fa-inbox', 'desc' => 'Read and route CRM email'],
            ],
        ];

        $today = date('Y-m-d');

        $openTickets      = $this->count_rows('tickets', 'merged_ticket_id IS NULL AND status IN (1,2,4)');
        $allTickets       = $this->count_rows('tickets', 'merged_ticket_id IS NULL');
        $activeCustomers  = $this->count_rows('clients', 'active=1');
        $contacts         = $this->count_rows('contacts', 'active=1');
        $newLeads         = $this->count_rows('leads', 'junk=0 AND lost=0 AND status=0');
        $activeLeads      = $this->count_rows('leads', 'junk=0 AND lost=0');
        $overdueTasks     = $this->count_rows('tasks', 'status != 5 AND duedate IS NOT NULL AND duedate < "' . $today . '"');
        $openTasks        = $this->count_rows('tasks', 'status != 5');
        $activeProjects   = $this->count_rows('projects', 'status IN (1,2)');
        $allProjects      = $this->count_rows('projects');
        $unpaidInvoices   = $this->count_rows('invoices', 'status NOT IN (2,5)');
        $overdueInvoices  = $this->count_rows('invoices', 'status NOT IN (2,5) AND duedate IS NOT NULL AND duedate < "' . $today . '"');
        $openEstimates    = $this->count_rows('estimates', 'status IN (1,2,3)');
        $openProposals    = $this->count_rows('proposals', 'status IN (1,2,3)');
        $activeContracts  = $this->count_rows('contracts', 'trash=0');
        $expensesThisMonth = $this->count_rows('expenses', 'date >= "' . date('Y-m-01') . '"');
        $upcomingAppointments = $this->count_rows('appmgr_appointments', 'appointment_date >= "' . $today . '"');
        $unreadChat       = $this->count_rows('chatclientmessages', 'reciever_id=' . get_staff_user_id() . ' AND viewed=0');
        $staffOnlineReady = $this->count_rows('staff', 'active=1');

        return [
            'hero_metrics' => [
                [
                    'label' => 'Open Support',
                    'value' => $openTickets,
                    'caption' => 'tickets need a CRM response',
                    'href' => admin_url('tickets'),
                    'icon' => 'fa-life-ring',
                    'tone' => 'blue',
                ],
                [
                    'label' => 'Active Customers',
                    'value' => $activeCustomers,
                    'caption' => $contacts . ' active contacts',
                    'href' => admin_url('clients'),
                    'icon' => 'fa-users',
                    'tone' => 'green',
                ],
                [
                    'label' => 'Leads To Work',
                    'value' => $activeLeads,
                    'caption' => $newLeads . ' brand new',
                    'href' => admin_url('leads'),
                    'icon' => 'fa-bolt',
                    'tone' => 'purple',
                ],
                [
                    'label' => 'Overdue Work',
                    'value' => $overdueTasks + $overdueInvoices,
                    'caption' => $overdueTasks . ' tasks, ' . $overdueInvoices . ' invoices',
                    'href' => admin_url('tasks'),
                    'icon' => 'fa-triangle-exclamation',
                    'tone' => 'orange',
                ],
            ],
            'workload' => [
                ['label' => 'Support Tickets', 'value' => $openTickets, 'total' => max($allTickets, 1), 'href' => admin_url('tickets'), 'tone' => 'blue'],
                ['label' => 'Tasks', 'value' => $openTasks, 'total' => max($openTasks + $overdueTasks, 1), 'href' => admin_url('tasks'), 'tone' => 'orange'],
                ['label' => 'Projects', 'value' => $activeProjects, 'total' => max($allProjects, 1), 'href' => admin_url('projects'), 'tone' => 'green'],
                ['label' => 'Leads', 'value' => $activeLeads, 'total' => max($activeLeads + $activeCustomers, 1), 'href' => admin_url('leads'), 'tone' => 'purple'],
            ],
            'finance' => [
                ['label' => 'Unpaid invoices', 'value' => $unpaidInvoices, 'href' => admin_url('invoices'), 'icon' => 'fa-file-invoice-dollar'],
                ['label' => 'Overdue invoices', 'value' => $overdueInvoices, 'href' => admin_url('invoices?status=overdue'), 'icon' => 'fa-clock'],
                ['label' => 'Open estimates', 'value' => $openEstimates, 'href' => admin_url('estimates'), 'icon' => 'fa-calculator'],
                ['label' => 'Open proposals', 'value' => $openProposals, 'href' => admin_url('proposals'), 'icon' => 'fa-file-signature'],
                ['label' => 'Contracts', 'value' => $activeContracts, 'href' => admin_url('contracts'), 'icon' => 'fa-file-contract'],
                ['label' => 'Expenses this month', 'value' => $expensesThisMonth, 'href' => admin_url('expenses'), 'icon' => 'fa-receipt'],
            ],
            'operations' => [
                ['label' => 'Appointments', 'value' => $upcomingAppointments, 'href' => admin_url('appointment_manager'), 'icon' => 'fa-calendar-check'],
                ['label' => 'Chat unread', 'value' => $unreadChat, 'href' => admin_url('prchat/Prchat_Controller/chat_full_view'), 'icon' => 'fa-comments'],
                ['label' => 'Mailbox inbox', 'value' => 'Open', 'href' => admin_url('mailbox/folder/inbox'), 'icon' => 'fa-envelope'],
                ['label' => 'Staff active', 'value' => $staffOnlineReady, 'href' => admin_url('staff'), 'icon' => 'fa-user-tie'],
                ['label' => 'Knowledge base', 'value' => 'FAQ', 'href' => admin_url('knowledge_base'), 'icon' => 'fa-book-open'],
                ['label' => 'Email settings', 'value' => 'Setup', 'href' => admin_url('settings?group=email'), 'icon' => 'fa-gear'],
            ],
            'quick_actions' => [
                ['label' => 'Support Tickets', 'href' => admin_url('tickets'), 'icon' => 'fa-headset', 'desc' => 'Reply, assign, and resolve client requests'],
                ['label' => 'Customers', 'href' => admin_url('clients'), 'icon' => 'fa-address-book', 'desc' => 'Open client records and contacts'],
                ['label' => 'Leads', 'href' => admin_url('leads'), 'icon' => 'fa-filter-circle-dollar', 'desc' => 'Review new opportunities'],
                ['label' => 'Projects', 'href' => admin_url('projects'), 'icon' => 'fa-diagram-project', 'desc' => 'Track active delivery work'],
                ['label' => 'Tasks', 'href' => admin_url('tasks'), 'icon' => 'fa-list-check', 'desc' => 'Clear staff follow-up work'],
                ['label' => 'Mailbox', 'href' => admin_url('mailbox/folder/inbox'), 'icon' => 'fa-inbox', 'desc' => 'Read and route CRM email'],
            ],
        ];
    }

    private function count_rows($table, $where = null)
    {
        $table = db_prefix() . $table;

        if (! $this->db->table_exists($table)) {
            return 0;
        }

        if ($where) {
            $this->db->where($where, null, false);
        }

        return (int) $this->db->count_all_results($table);
    }
}
