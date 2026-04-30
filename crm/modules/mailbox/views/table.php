<?php

defined('BASEPATH') or exit('No direct script access allowed');

$mailbox_staff_id = function_exists('mailbox_get_selected_staff_id') ? mailbox_get_selected_staff_id() : get_staff_user_id();
$isSentLikeGroup = in_array($group, ['sent', 'draft'], true);
$primaryColumn = $isSentLikeGroup ? db_prefix().'mail_inbox.to' : 'sender_name';

$aColumns = [
    $primaryColumn,
    'subject',
    'date_received',
];

$sIndexColumn = 'id';
$sTable = db_prefix().'mail_inbox';

$join = [];
$where = [];
array_push($where, 'AND to_staff_id = '.(int) $mailbox_staff_id);

if ($group === 'inbox') {
    array_push($where, " AND trash = 0 AND folder = 'inbox'");
} elseif ($group === 'starred') {
    array_push($where, ' AND trash = 0 AND stared = 1');
} elseif ($group === 'important') {
    array_push($where, ' AND trash = 0 AND important = 1');
} elseif ($group === 'trash') {
    array_push($where, " AND (trash = 1 OR folder = 'trash')");
} elseif ($group === 'sent') {
    array_push($where, " AND trash = 0 AND folder = 'sent'");
} elseif ($group === 'draft') {
    array_push($where, " AND trash = 0 AND folder = 'draft'");
}

$additionalSelect = [
    'id',
    'sender_name',
    db_prefix().'mail_inbox.to as mailbox_to',
    'subject',
    'body',
    'date_received',
    'has_attachment',
    'stared',
    'important',
    db_prefix().'mail_inbox.read as mailbox_read',
    'folder',
    'trash',
];

$result = data_tables_init(
    $aColumns,
    $sIndexColumn,
    $sTable,
    $join,
    $where,
    $additionalSelect
);

$output = $result['output'];
$rResult = $result['rResult'];

foreach ($rResult as $aRow) {
    $row = [];
    $read = ((int) $aRow['mailbox_read'] === 1) ? '' : 'bold';
    $starred = 'fa-star';
    $msg_starred = _l('mailbox_add_star');
    $important = 'fa-bookmark';
    $msg_important = _l('mailbox_mark_as_important');

    if ((int) $aRow['stared'] === 1) {
        $starred = 'fa-star orange';
        $msg_starred = _l('mailbox_remove_star');
    }

    if ((int) $aRow['important'] === 1) {
        $important = 'fa fa-bookmark green';
        $msg_important = _l('mailbox_mark_as_not_important');
    }

    $has_attachment = '';
    if ((int) $aRow['has_attachment'] > 0) {
        $has_attachment = '<i class="fa fa-paperclip pull-right" data-toggle="tooltip" title="'._l('mailbox_file_attachment').'" data-original-title="fa-paperclip"></i>';
    }

    $deleteActionGroup = $group;
    if ($aRow['folder'] === 'trash' || (int) $aRow['trash'] === 1) {
        $deleteActionGroup = 'trash';
    }

    $row[] = '<div class="checkbox"><input type="checkbox" value="'.$aRow['id'].'"><label></label></div>
                <a class="btn btnIcon" data-toggle="tooltip" title="" data-original-title="'.$msg_starred.'" onclick="update_field(\''.$deleteActionGroup.'\',\'starred\','.$aRow['stared'].','.$aRow['id'].',\'inbox\');"><i class="fa '.$starred.' grey"></i></a>
                <a class="btn btnIcon" data-toggle="tooltip" title="" data-original-title="'.$msg_important.'" onclick="update_field(\''.$deleteActionGroup.'\',\'important\','.$aRow['important'].','.$aRow['id'].',\'inbox\');"><i class="fa '.$important.' grey"></i></a>
                <a class="btn btnIcon" data-toggle="tooltip" title="" data-original-title="'._l('mailbox_delete').'" onclick="update_field(\''.$deleteActionGroup.'\',\'trash\',1,'.$aRow['id'].',\'inbox\');"><i class="fa fa-trash grey"></i></a>';

    $content = '<a href="'.admin_url().'mailbox/inbox/'.$aRow['id'].'">';
    $primaryText = $isSentLikeGroup ? $aRow['mailbox_to'] : $aRow['sender_name'];

    $row[] = $content.'<span class="'.$read.'">'.html_escape($primaryText).'</span></a>';
    $row[] = $content.'<span class="'.$read.'">'.html_escape($aRow['subject']).' - </span><span class="text-muted">'.clear_textarea_breaks(text_limiter((string) $aRow['body'], 2, '...')).'</span>'.$has_attachment.'</a>';
    $row[] = $content.'<span class="'.$read.'">'._dt($aRow['date_received']).'</span></a>';

    $output['aaData'][] = $row;
}
