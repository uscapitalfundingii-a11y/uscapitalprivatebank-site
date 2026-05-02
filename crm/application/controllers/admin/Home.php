<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Home extends AdminController
{
    public function index()
    {
        close_setup_menu();

        $data['title'] = 'Admin Control Panel';
        $this->load->view('admin/home', $data);
    }
}
