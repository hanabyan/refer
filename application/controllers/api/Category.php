<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * category_type
 *  1: business
 *  2: product
 */

class Category extends MY_Controller {
    public function __construct()
    {
        parent::__construct();

        $this->load->model('Model_category');

        $this->load->library('form_validation');
    }

    public function business_get()
    {
        $data = $this->get_category_by_id(1);

        return $this->set_response($data, self::HTTP_OK);
    }

    public function business_post()
    {
        $name = $this->post('name');
        $description = $this->post('description');
        
        $payload = array(
            'name'  => $name,
            'description'  => $description,
            'category_type'  => 1,
        );

        $this->create_category($payload);
    }

    public function product_get()
    {
        $data = $this->get_category_by_id(2);

        return $this->set_response($data, self::HTTP_OK);
    }

    public function product_post()
    {
        
    }

    private function get_category_by_id($id)
    {
        return $this->Model_category->data(null, null, array('category_type' => $id));
    }

    private function create_category($data = array())
    {
        $config = array(
            array(
                'field' => 'name',
                'label' => 'Name',
                'rules' => 'required' 
            ),
        );

        $this->form_validation->set_data($data);
        $this->form_validation->set_rules($config);

        if ($this->form_validation->run() == FALSE)
        {
            return $this->set_response($this->form_validation->error_array(), self::HTTP_BAD_REQUEST);
        }

        $result = $this->Model_category->create(array_merge($data, array(
            'created_by'    => $this->subject_id,
            'created_at'    => date('Y-m-d H:i:s'),
        )));

        if (!$result['status'])
        {
            return $this->set_response($result['message'], self::HTTP_INTERNAL_SERVER_ERROR);
        }

        $created_data = $this->Model_category->get(array(
            'category_type'  => $data['category_type'],
            'id'    => $result['id'],
        ));

        return $this->set_response($created_data, self::HTTP_OK);
    }
}