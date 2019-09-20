<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class City extends MY_Controller{
    public function __construct()
    {
        $this->refer_role = 'user';
        parent::__construct();

        $this->load->model('Model_city');
    }

    public function index_get($province_id = null)
    {
        $where = array();

        if ($province_id)
        {
            $where = array_merge($where, array(
                'province_id'  => $province_id,
            ));
        }

        $data = $this->Model_city->data(null, null, $where);

        return $this->set_response($data, self::HTTP_OK);
    }
}
