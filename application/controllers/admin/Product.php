<?php
/**
 * TODO: sementara validasi dimatikan
 */
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
        $id = $this->input->get('id');
        $id = trim($id);
        if ($id) {
            $excludeProduct = array_map("intval", explode(",",$id));
            $excludeProduct = array_unique($excludeProduct);
            $sql = "SELECT B.*, C.`name` AS `category_name`, D.`company_name` AS `client_name` FROM `Product` B, `Categories` C, `Client` D WHERE B.`category_id` = C.`id` AND B.`client_id` = D.`id` AND B.`id` NOT IN ? AND B.`status` = 1 ORDER BY B.`name`";
            $data = $this->db->query($sql,array($excludeProduct))->result_array();
        } else {
            $data = $this->Model_product->data();
        }

        return $this->set_response($data, self::HTTP_OK);
    }

    public function index_post()
    {
        $data = $this->post();
        $validation_result = $this->validate_common($data);
        // if ($validation_result !== TRUE)
        // {
        //     /* return rest error array */
        //     return $validation_result;
        // }

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

        $product_to_create = array(
            'name'  => $name,
            'description'  => $description,
            'estimated_price'  => $estimated_price,
            'image'  => $image,
            'sku'  => $sku,
            'category_id'  => $category_id,
            'client_id'  => $client_id,
            'status'  => 1,
            'created_by'  => $this->subject_id,
            'created_at'    => date('Y-m-d H:i:s')
        );

        $result = $this->Model_product->create($product_to_create);

        if (!$result['status'])
        {
            return $this->set_response($result['message'], self::HTTP_BAD_REQUEST);
        }

        $detail_product = $this->Model_product->get(array('id'  => $result['id']));

        return $this->set_response($detail_product, self::HTTP_OK);
    }

    public function index_put($id = null)
    {
        if (!$id)
        {
            return $this->set_response('Client id can not be null', self::HTTP_BAD_REQUEST);
        }

        $data = $this->put();
        // $validation_result = $this->validate_common($data);
        // if ($validation_result !== TRUE)
        // {
        //     /* return rest error array */
        //     return $validation_result;
        // }

        $name = $this->_isExists('name', $data);
        $description = $this->_isExists('description', $data);
        $estimated_price = $this->_isExists('estimated_price', $data);
        $image = $this->_isExists('image', $data);
        $sku = $this->_isExists('sku', $data);
        $category_id = $this->_isExists('category_id', $data);
        $client_id = $this->_isExists('client_id', $data);

        $product_to_update = array(
            'name'  => $name,
            'description'  => $description,
            'estimated_price'  => $estimated_price,
            'image'  => $image,
            'sku'  => $sku,
            'category_id'  => $category_id,
            'client_id'  => $client_id,
            'updated_by'  => $this->subject_id,
            'updated_at'    => date('Y-m-d H:i:s')
        );

        $result = $this->Model_product->update((int)$id, $product_to_update);

        if (!$result['status'])
        {
            return $this->set_response($result['message'], self::HTTP_BAD_REQUEST);
        }

        $detail_product = $this->Model_product->get(array('id' => $id));

        return $this->set_response($detail_product, self::HTTP_OK);
    }

    private function validate_common($data = array())
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
            //     'label' => 'SKU',
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

        return true;
    }

    public function index_delete($id = null)
    {
        if (!$id)
        {
            return $this->set_response('Produk tidak ditemukan', self::HTTP_BAD_REQUEST);
        }

        $sql = "SELECT `id` FROM `Product` WHERE `id` = ?";
        $product = $this->db->query($sql, array(intval($id)))->row();

        if (!$product)
        {
            return $this->set_response('Produk tidak ditemukan', self::HTTP_BAD_REQUEST);
        }

        try {
            $this->db->update('Product', array('status'  => 9), array('id'  => $product->id));
            return $this->set_response($product->id, self::HTTP_OK);
        } catch (Exception $e)
        {
            return $this->set_response('Gagal menghapus produk', self::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}