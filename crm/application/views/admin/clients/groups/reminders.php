<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php
$reminderCalendarDays = [];
$remindersByDay       = [];
$calendarStart        = new DateTime(date('Y-m-d'));
$calendarEnd          = (clone $calendarStart)->modify('+30 days');

if (isset($client)) {
    $CI = &get_instance();
    $calendarReminders = $CI->db
        ->select(db_prefix() . 'reminders.id, ' . db_prefix() . 'reminders.description, ' . db_prefix() . 'reminders.date, ' . db_prefix() . 'reminders.isnotified, ' . db_prefix() . 'staff.firstname, ' . db_prefix() . 'staff.lastname')
        ->from(db_prefix() . 'reminders')
        ->join(db_prefix() . 'staff', db_prefix() . 'staff.staffid = ' . db_prefix() . 'reminders.staff', 'left')
        ->where(db_prefix() . 'reminders.rel_id', $client->userid)
        ->where(db_prefix() . 'reminders.rel_type', 'customer')
        ->where(db_prefix() . 'reminders.date >=', $calendarStart->format('Y-m-d 00:00:00'))
        ->where(db_prefix() . 'reminders.date <=', $calendarEnd->format('Y-m-d 23:59:59'))
        ->order_by(db_prefix() . 'reminders.date', 'asc')
        ->get()
        ->result_array();

    foreach ($calendarReminders as $reminder) {
        $dayKey = date('Y-m-d', strtotime($reminder['date']));
        if (!isset($remindersByDay[$dayKey])) {
            $remindersByDay[$dayKey] = [];
        }
        $remindersByDay[$dayKey][] = $reminder;
    }

    $cursor = clone $calendarStart;
    while ($cursor <= $calendarEnd) {
        $reminderCalendarDays[] = clone $cursor;
        $cursor->modify('+1 day');
    }
}
?>
<h4 class="customer-profile-group-heading">
    <?= _l('client_reminders_tab'); ?>
</h4>
<?php if (isset($client)) { ?>
<div class="uscap-reminder-header">
    <div>
        <p class="text-muted tw-mb-0">
            View this customer's upcoming reminder schedule for the next 30 days.
        </p>
    </div>
    <a href="#" data-toggle="modal"
        data-target=".reminder-modal-customer-<?= e($client->userid); ?>"
        class="btn btn-primary uscap-add-reminder-btn">
        <i class="fa-regular fa-bell"></i>
        Add Reminder
    </a>
</div>

<div class="uscap-reminder-calendar">
    <div class="uscap-reminder-calendar-title">
        <div>
            <span class="uscap-calendar-kicker">Month Ahead</span>
            <h5><?= e($calendarStart->format('M j')); ?> - <?= e($calendarEnd->format('M j, Y')); ?></h5>
        </div>
        <span class="uscap-calendar-count">
            <?= e(count($calendarReminders)); ?> upcoming
        </span>
    </div>
    <div class="uscap-reminder-calendar-grid">
        <?php foreach ($reminderCalendarDays as $day) {
            $dayKey      = $day->format('Y-m-d');
            $dayReminders = $remindersByDay[$dayKey] ?? [];
            $isToday     = $dayKey === date('Y-m-d');
        ?>
        <div class="uscap-reminder-day<?= $isToday ? ' is-today' : ''; ?><?= !empty($dayReminders) ? ' has-reminders' : ''; ?>">
            <div class="uscap-reminder-day-head">
                <span><?= e($day->format('D')); ?></span>
                <strong><?= e($day->format('j')); ?></strong>
            </div>
            <?php if (!empty($dayReminders)) { ?>
                <?php foreach (array_slice($dayReminders, 0, 2) as $reminder) { ?>
                <div class="uscap-reminder-chip<?= (int) $reminder['isnotified'] === 1 ? ' is-complete' : ''; ?>">
                    <span><?= e(date('g:i A', strtotime($reminder['date']))); ?></span>
                    <strong><?= e(trim(($reminder['firstname'] ?? '') . ' ' . ($reminder['lastname'] ?? '')) ?: _l('staff')); ?></strong>
                </div>
                <?php } ?>
                <?php if (count($dayReminders) > 2) { ?>
                <div class="uscap-reminder-more">+<?= e(count($dayReminders) - 2); ?> more</div>
                <?php } ?>
            <?php } else { ?>
            <span class="uscap-reminder-empty">Open</span>
            <?php } ?>
        </div>
        <?php } ?>
    </div>
</div>

<?php render_datatable([_l('reminder_description'), _l('reminder_date'), _l('reminder_staff'), _l('reminder_is_notified')], 'reminders');
    $this->load->view('admin/includes/modals/reminder', ['id' => $client->userid, 'name' => 'customer', 'members' => $members, 'reminder_title' => _l('set_reminder')]);
} ?>
