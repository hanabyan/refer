<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Product extends MY_Controller {
    public function __construct()
    {
        parent::__construct();

        $this->load->model('Model_product');

        $this->load->library('form_validation');
    }

    public function index_get()
    {
        $data = $this->Model_product->data();

        return $this->set_response($data, self::HTTP_OK);
    }

    public function index_post()
    {
        $config = array(
            array(
                'field' => 'name',
                'label' => 'Product Name',
                'rules' => 'required' 
            ),
            array(
                'field' => 'description',
                'label' => 'Product Description',
                'rules' => 'required' 
            ),
            array(
                'field' => 'estimated_price',
                'label' => 'Estimated Price',
                'rules' => 'required' 
            ),
            // array(
            //     'field' => 'image',
            //     'label' => 'Image',
            //     'rules' => 'required' 
            // ),
            // array(
            //     'field' => 'sku',
            //     'label' => 'Image',
            //     'rules' => 'required' 
            // ),
            array(
                'field' => 'category_id',
                'label' => 'Category',
                'rules' => 'required' 
            ),
            array(
                'field' => 'client_id',
                'label' => 'Client',
                'rules' => 'required' 
            ),
        );

        $data = $this->post();
        $this->form_validation->set_data($data);
        $this->form_validation->set_rules($config);

        if ($this->form_validation->run() == FALSE)
        {
            return $this->set_response($this->form_validation->error_array(), self::HTTP_BAD_REQUEST);
        }

        /**
         * collect data
         */

        //  TODO: sku to check again
        $name = $this->_isExists('name', $data);
        $description = $this->_isExists('description', $data);
        $estimated_price = $this->_isExists('estimated_price', $data);
        $image = $this->_isExists('image', $data);
        $sku = $this->_isExists('sku', $data);
        $category_id = $this->_isExists('category_id', $data);
        $client_id = $this->_isExists('client_id', $data);

        // TODO: log created_at, created_by

        $product_to_create = array(
            'name'  => $name,
            'description'  => $description,
            'estimated_price'  => $estimated_price,
            'image'  => $image,
            'sku'  => $sku,
            'category_id'  => $category_id,
            'client_id'  => $client_id,
            'status'  => 1,
            'created_at'    => date('Y-m-d H:i:s')
        );

        $result = $this->Model_product->create($product_to_create);

        if (!$result['status'])
        {
            return $this->set_response($result['message'], self::HTTP_INTERNAL_SERVER_ERROR);
        }

        $detail_product = $this->Model_product->get(array('id'  => $result['id']));

        return $this->set_response($detail_product, self::HTTP_OK);
    }
}