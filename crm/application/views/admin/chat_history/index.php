<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<style>
    .uscap-chat-history-page .panel_s {
        overflow: hidden;
    }

    .uscap-chat-history-layout {
        display: grid;
        grid-template-columns: minmax(280px, 360px) minmax(0, 1fr);
        gap: 18px;
    }

    .uscap-chat-history-list,
    .uscap-chat-history-thread {
        border: 1px solid #dbe5f1;
        border-radius: 12px;
        background: #fff;
        min-height: 560px;
        overflow: hidden;
    }

    .uscap-chat-history-list {
        max-height: 760px;
        overflow-y: auto;
    }

    .uscap-chat-history-person {
        display: block;
        padding: 14px 16px;
        border-bottom: 1px solid #eef3f8;
        color: #24364d;
    }

    .uscap-chat-history-person:hover,
    .uscap-chat-history-person:focus,
    .uscap-chat-history-person.active {
        background: #eef7ff;
        color: #102033;
        text-decoration: none;
    }

    .uscap-chat-history-person strong,
    .uscap-chat-history-empty strong {
        display: block;
        color: #102033;
        font-weight: 900;
    }

    .uscap-chat-history-person span,
    .uscap-chat-history-person small {
        display: block;
        color: #65758b;
        line-height: 1.4;
    }

    .uscap-chat-history-thread-head {
        padding: 16px 18px;
        border-bottom: 1px solid #dbe5f1;
        background: linear-gradient(180deg, #f7fbff, #eef5fc);
    }

    .uscap-chat-history-thread-body {
        max-height: 680px;
        padding: 18px;
        overflow-y: auto;
    }

    .uscap-chat-history-message {
        margin-bottom: 14px;
        padding: 13px 14px;
        border: 1px solid #dbe5f1;
        border-left: 4px solid #1f6feb;
        border-radius: 10px;
        background: #fbfdff;
    }

    .uscap-chat-history-message.is-client {
        border-left-color: #c8a24d;
        background: #fffdf5;
    }

    .uscap-chat-history-message.is-staff {
        border-left-color: #16a36f;
        background: #f5fffb;
    }

    .uscap-chat-history-message-meta {
        display: flex;
        flex-wrap: wrap;
        justify-content: space-between;
        gap: 8px;
        margin-bottom: 7px;
        color: #65758b;
        font-size: 12px;
        font-weight: 800;
    }

    .uscap-chat-history-message-body {
        color: #27374d;
        line-height: 1.55;
        overflow-wrap: anywhere;
        white-space: pre-wrap;
    }

    .uscap-chat-history-empty {
        padding: 28px;
        color: #65758b;
    }

    @media (max-width: 991px) {
        .uscap-chat-history-layout {
            grid-template-columns: 1fr;
        }

        .uscap-chat-history-list,
        .uscap-chat-history-thread {
            min-height: auto;
        }
    }
</style>
<div id="wrapper">
    <div class="content uscap-chat-history-page">
        <div class="row">
            <div class="col-md-12">
                <div class="panel_s">
                    <div class="panel-body">
                        <h4 class="bold no-margin">Chat History</h4>
                        <p class="text-muted mtop10">
                            Read-only record of CRM chat conversations, grouped by client and staff/AI member for audit and customer-care review.
                        </p>

                        <ul class="nav nav-tabs mtop20" role="tablist">
                            <li role="presentation" class="<?php echo $tab === 'clients' ? 'active' : ''; ?>">
                                <a href="<?php echo admin_url('chat_history?tab=clients'); ?>">Client Chat History</a>
                            </li>
                            <li role="presentation" class="<?php echo $tab === 'staff' ? 'active' : ''; ?>">
                                <a href="<?php echo admin_url('chat_history?tab=staff'); ?>">Staff Chat History</a>
                            </li>
                        </ul>

                        <div class="tab-content">
                            <div class="tab-pane active">
                                <form method="get" action="<?php echo admin_url('chat_history'); ?>" class="mtop20 mbot20">
                                    <input type="hidden" name="tab" value="<?php echo html_escape($tab); ?>">
                                    <div class="input-group">
                                        <input type="text" name="search" value="<?php echo html_escape($search); ?>" class="form-control" placeholder="Search by name, company, email, or ID">
                                        <span class="input-group-btn">
                                            <button type="submit" class="btn btn-primary">Search</button>
                                            <a href="<?php echo admin_url('chat_history?tab=' . $tab); ?>" class="btn btn-default">Reset</a>
                                        </span>
                                    </div>
                                </form>

                                <?php if ($tab === 'clients') { ?>
                                    <?php if (!$client_table_exists) { ?>
                                        <div class="alert alert-warning">The client chat table is not available yet.</div>
                                    <?php } ?>
                                    <div class="uscap-chat-history-layout">
                                        <div class="uscap-chat-history-list">
                                            <?php if (!empty($client_conversations)) { ?>
                                                <?php foreach ($client_conversations as $conversation) {
                                                    $label = trim(($conversation['firstname'] ?? '') . ' ' . ($conversation['lastname'] ?? ''));
                                                    $label = $label !== '' ? $label : $conversation['client_key'];
                                                    $company = !empty($conversation['company']) ? $conversation['company'] : '';
                                                    $active = $client_key === $conversation['client_key'];
                                                    $href = admin_url('chat_history?tab=clients&client=' . urlencode($conversation['client_key']) . '&search=' . urlencode($search));
                                                ?>
                                                    <a class="uscap-chat-history-person <?php echo $active ? 'active' : ''; ?>" href="<?php echo $href; ?>">
                                                        <strong><?php echo html_escape($label); ?></strong>
                                                        <?php if (!empty($conversation['email'])) { ?>
                                                            <span><?php echo html_escape($conversation['email']); ?></span>
                                                        <?php } ?>
                                                        <?php if ($company !== '') { ?>
                                                            <span><?php echo html_escape($company); ?></span>
                                                        <?php } ?>
                                                        <small>
                                                            <?php echo (int) $conversation['message_count']; ?> messages,
                                                            <?php echo (int) $conversation['staff_count']; ?> staff/AI participants,
                                                            latest <?php echo html_escape(_dt($conversation['last_time'])); ?>
                                                        </small>
                                                    </a>
                                                <?php } ?>
                                            <?php } else { ?>
                                                <div class="uscap-chat-history-empty">
                                                    <strong>No client chat history found.</strong>
                                                    <span>Client conversations will appear here after chat records exist.</span>
                                                </div>
                                            <?php } ?>
                                        </div>
                                        <div class="uscap-chat-history-thread">
                                            <div class="uscap-chat-history-thread-head">
                                                <strong><?php echo $selected_client ? html_escape($selected_client) : 'Select a client'; ?></strong>
                                                <div class="text-muted">Full client conversation history across staff and AI participants.</div>
                                            </div>
                                            <div class="uscap-chat-history-thread-body">
                                                <?php if (!empty($client_messages)) { ?>
                                                    <?php foreach ($client_messages as $message) {
                                                        $isClient = strpos((string) $message['sender_id'], 'client_') === 0;
                                                    ?>
                                                        <div class="uscap-chat-history-message <?php echo $isClient ? 'is-client' : 'is-staff'; ?>">
                                                            <div class="uscap-chat-history-message-meta">
                                                                <span><?php echo html_escape($message['sender_label']); ?> to <?php echo html_escape($message['reciever_label']); ?></span>
                                                                <span><?php echo html_escape($message['time_sent_formatted']); ?></span>
                                                            </div>
                                                            <div class="uscap-chat-history-message-body"><?php echo html_escape($message['clean_message']); ?></div>
                                                        </div>
                                                    <?php } ?>
                                                <?php } else { ?>
                                                    <div class="uscap-chat-history-empty">
                                                        <strong>No conversation selected.</strong>
                                                        <span>Choose a client from the left to view the stored chat transcript.</span>
                                                    </div>
                                                <?php } ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php } else { ?>
                                    <?php if (!$staff_table_exists) { ?>
                                        <div class="alert alert-warning">The staff chat table is not available yet.</div>
                                    <?php } ?>
                                    <div class="uscap-chat-history-layout">
                                        <div class="uscap-chat-history-list">
                                            <?php if (!empty($staff_conversations)) { ?>
                                                <?php foreach ($staff_conversations as $conversation) {
                                                    $label = trim(($conversation['firstname'] ?? '') . ' ' . ($conversation['lastname'] ?? ''));
                                                    $label = $label !== '' ? $label : 'Staff #' . $conversation['staffid'];
                                                    $active = (int) $staff_id === (int) $conversation['staffid'];
                                                    $href = admin_url('chat_history?tab=staff&staff=' . (int) $conversation['staffid'] . '&search=' . urlencode($search));
                                                ?>
                                                    <a class="uscap-chat-history-person <?php echo $active ? 'active' : ''; ?>" href="<?php echo $href; ?>">
                                                        <strong><?php echo html_escape($label); ?></strong>
                                                        <?php if (!empty($conversation['email'])) { ?>
                                                            <span><?php echo html_escape($conversation['email']); ?></span>
                                                        <?php } ?>
                                                        <small>
                                                            <?php echo (int) $conversation['message_count']; ?> staff messages,
                                                            latest <?php echo html_escape(_dt($conversation['last_time'])); ?>
                                                        </small>
                                                    </a>
                                                <?php } ?>
                                            <?php } else { ?>
                                                <div class="uscap-chat-history-empty">
                                                    <strong>No staff chat history found.</strong>
                                                    <span>Staff and AI conversations will appear here after chat records exist.</span>
                                                </div>
                                            <?php } ?>
                                        </div>
                                        <div class="uscap-chat-history-thread">
                                            <div class="uscap-chat-history-thread-head">
                                                <strong><?php echo $selected_staff ? html_escape($selected_staff) : 'Select a staff member or agent'; ?></strong>
                                                <div class="text-muted">Full internal staff/AI chat history for the selected participant.</div>
                                            </div>
                                            <div class="uscap-chat-history-thread-body">
                                                <?php if (!empty($staff_messages)) { ?>
                                                    <?php foreach ($staff_messages as $message) { ?>
                                                        <div class="uscap-chat-history-message is-staff">
                                                            <div class="uscap-chat-history-message-meta">
                                                                <span><?php echo html_escape($message['sender_label']); ?> to <?php echo html_escape($message['reciever_label']); ?></span>
                                                                <span><?php echo html_escape($message['time_sent_formatted']); ?></span>
                                                            </div>
                                                            <div class="uscap-chat-history-message-body"><?php echo html_escape($message['clean_message']); ?></div>
                                                        </div>
                                                    <?php } ?>
                                                <?php } else { ?>
                                                    <div class="uscap-chat-history-empty">
                                                        <strong>No staff member selected.</strong>
                                                        <span>Choose a staff member or AI agent from the left to view internal chat history.</span>
                                                    </div>
                                                <?php } ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php } ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php init_tail(); ?>
</body>
</html>
