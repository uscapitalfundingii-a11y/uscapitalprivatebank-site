<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Proof_of_funds_pdf extends App_pdf
{
    protected $proposal;

    public function __construct($proposal)
    {
        parent::__construct();

        $this->proposal = $proposal;
        $this->SetTitle('Proof of Funds');

        // Load language file if needed
        $this->ci->lang->load('proof_of_funds_lang', $this->ci->session->userdata('language'));
    }

    protected function prepare()
    {
        $data['proposal'] = $this->proposal;

        // You can add more dynamic data here if needed

        $html = $this->ci->load->view('themes/perfex/proof_of_funds_template', $data, true);

        $this->pdf->WriteHTML($html);

        return $this->pdf;
    }
}
