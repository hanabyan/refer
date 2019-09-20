<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Province extends MY_Controller{
    public function __construct()
    {
        $this->refer_role = 'user';
        parent::__construct();

        $this->load->model('Model_province');
    }

    public function index_get()
    {
        $data = $this->Model_province->data();

        return $this->set_response($data, self::HTTP_OK);
    }
}
