<?php

defined('BASEPATH') or exit('No direct script access allowed');

$aColumns = [
    'name',
    'date',
    'email_to',
    'email',
    'subject',
    'message',
    'status',
    ];

$sWhere = [];
if ($this->ci->input->post('activity_log_date')) {
    array_push($sWhere, 'AND date LIKE "' . $this->ci->db->escape_like_str(to_sql_date($this->ci->input->post('activity_log_date'))) . '%" ESCAPE \'!\'');
}

$sIndexColumn = 'id';
$sTable       = db_prefix().'tickets_pipe_log';
$result       = data_tables_init($aColumns, $sIndexColumn, $sTable, [], $sWhere);
$output       = $result['output'];
$rResult      = $result['rResult'];
foreach ($rResult as $aRow) {
    $row = [];
    for ($i = 0; $i < count($aColumns); $i++) {
        $_data = $aRow[$aColumns[$i]];
        if ($aColumns[$i] == 'date') {
            $_data = e(_dt($_data));
        } elseif ($aColumns[$i] == 'message') {
            $_data = html_entity_decode((string) $_data, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $_data = strip_tags($_data);
            $_data = preg_replace('/\s+/u', ' ', $_data);
            $_data = trim($_data);
            if (mb_strlen($_data) > 700) {
                $_data = mb_substr($_data, 0, 700) . '...';
            }
            $_data = e($_data);
        } else {
            $_data = e($_data);
        }
        $row[] = $_data;
    }
    $output['aaData'][] = $row;
}
