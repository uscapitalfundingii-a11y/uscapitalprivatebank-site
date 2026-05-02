<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Version_332 extends CI_Migration
{
    public function up()
    {
        $CI = &get_instance();

        if (!$CI->db->field_exists('base_price', db_prefix() . 'items')) {
            $CI->load->dbforge();
            $CI->dbforge->add_column(db_prefix() . 'items', [
                'base_price' => [
                    'type'       => 'DECIMAL',
                    'constraint' => '15,' . get_decimal_places(),
                    'null'       => true,
                    'after'      => 'rate',
                ],
            ]);
        }
    }
}
