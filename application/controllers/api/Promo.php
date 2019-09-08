<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Promo extends MY_Controller {
    public function __construct()
    {
        parent::__construct();

        $this->load->model('Model_promo');
    }

    public function index_get()
    {
        $data = $this->Model_promo->data();

        return $this->set_response($data, self::HTTP_OK);
    }
}