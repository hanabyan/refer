<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Promo extends MY_Controller {
    public function __construct()
    {
        parent::__construct();

        $this->load->model('Model_promo');
    }

    public function index_get($id=0)
    {
        $id = intval($id);
        if ($id<1) {
            $sql = "SELECT `id`, `name`, `description`, `promo_type`, `promo_value`, `period_start`, `period_end`, `unlimited`, `referral_commission`, `referral_share_count`, `status` FROM `Promo` WHERE `status` = 1 ORDER BY `id` DESC";
            $data = $this->db->query($sql)->result_array();
            $this->response($data, self::HTTP_OK);
        } else {
            $sql = "SELECT `id`, `name`, `description`, `promo_type`, `promo_value`, `period_start`, `period_end`, `unlimited`, `referral_commission`, `referral_share_count`, `status` FROM `Promo` WHERE `id` = ? AND `status` = 1";
            $promo = (array) $this->db->query($sql, array($id))->row();
            if ($promo) {
				$this->response($promo, self::HTTP_OK);
			} else {
				$this->response("Invalid Promo", self::HTTP_BAD_REQUEST);
			}
        }
    }

    public function product_get($id=0)
    {
        $id = intval($id);
        if ($id<1) {
            $this->response("Invalid Promo", self::HTTP_BAD_REQUEST);
        }
        $sql = "SELECT B.*, C.`name` AS `category_name`, D.`company_name` AS `client_name` FROM `Promo_Product` A, `Product` B, `Categories` C, `Client` D WHERE A.`product_id` = B.`id` AND B.`category_id` = C.`id` AND B.`client_id` = D.`id` AND A.`promo_id` = ? ORDER BY B.`name`";
        $data = $this->db->query($sql,array($id))->result_array();

        $this->response($data, self::HTTP_OK);
    }

    public function product_put($id=0)
    {
        $id = intval($id);
        if ($id<1) {
            $this->response("Invalid Promo", self::HTTP_BAD_REQUEST);
        }
        $sql = "SELECT `id` FROM `Promo` WHERE `id` = ? AND `status` = 1 LIMIT 1";
        $ori = $this->db->query($sql, array($id))->row();
        if (!$ori) {
            $this->response('Invalid Promo', self::HTTP_BAD_REQUEST);
        }

        $cols = array(
            'product'
        );
        foreach ($cols as $col) {
            $$col = $this->put($col);
        }
        if (!is_array($product)) {
            $this->response('Invalid Product Promo', self::HTTP_BAD_REQUEST);
        }

        try {
            $this->db->trans_begin();
            $ids = $batch = array();
            foreach ($product as $p) {
                $batch[] = array(
                    'promo_id' =>$id,
                    'product_id' => intval($p['id']),
                    'total_item' => intval($p['total_item']),
                );
            }
            if ($id) {
                $this->db->delete('Promo_Product', array('promo_id' => $id));
                $this->db->insert_batch('Promo_Product', $batch);
            }

            if ($this->db->trans_status() === FALSE) {
                $this->db->trans_rollback();
                $this->response('Database error', self::HTTP_INTERNAL_SERVER_ERROR);
            } else {
                $this->db->trans_commit();
                $this->response($id, self::HTTP_OK);
            }

        } catch(Exception $e) {
            $this->response('Error while processing data', self::HTTP_INTERNAL_SERVER_ERROR);
        }

        $sql = "SELECT B.*, C.`name` AS `category_name`, D.`company_name` AS `client_name` FROM `Promo_Product` A, `Product` B, `Categories` C, `Client` D WHERE A.`product_id` = B.`id` AND B.`category_id` = C.`id` AND B.`client_id` = D.`id` AND A.`promo_id` = ? ORDER BY B.`name`";
        $data = $this->db->query($sql,array($promoId))->result_array();

        $this->response($data, self::HTTP_OK);
    }

    public function index_post()
	{
		$cols = array(
            'name', 'description', 'promo_type', 'promo_value', 'period_start', 'period_end', 'unlimited', 'referral_commission', 'referral_share_count'
        );
        foreach ($cols as $col) {
            $$col = trim($this->post($col));
        }

        try {
            $start_date = date('Y-m-d H:i:s', strtotime($period_start));
            $end_date = date('Y-m-d H:i:s', strtotime($period_end));
        } catch (Exception $e) {
            $this->response('Invalid Period', self::HTTP_BAD_REQUEST);
        }

        $datas = array();
        foreach ($cols as $col) {
            $datas[$col] = $$col;
        }
        $datas["created_by"] = $this->subject_id;
        try {
            $this->db->insert('Promo', $datas);
            $lastId = $this->db->insert_id();

            $this->response($lastId, self::HTTP_OK);
        } catch(Exception $e) {
            $this->response('Error while processing data', self::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function index_put($id=0)
	{
        $id = intval($id);
        if ($id<1) {
			$this->response('Invalid Promo', self::HTTP_BAD_REQUEST);
        }
        $sql = "SELECT `id` FROM `Promo` WHERE `id` = ? AND `status` = 1 LIMIT 1";
        $ori = $this->db->query($sql, array($id))->row();
        if (!$ori) {
            $this->response('Invalid Promo', self::HTTP_BAD_REQUEST);
        }

		$cols = array(
            'name', 'description', 'promo_type', 'promo_value', 'period_start', 'period_end', 'unlimited', 'referral_commission', 'referral_share_count'
        );
        foreach ($cols as $col) {
            $$col = trim($this->put($col));
        }

        try {
            $start_date = date('Y-m-d H:i:s', strtotime($period_start));
            $end_date = date('Y-m-d H:i:s', strtotime($period_end));
        } catch (Exception $e) {
            $this->response('Invalid Period', self::HTTP_BAD_REQUEST);
        }

        $datas = array();
        foreach ($cols as $col) {
            $datas[$col] = $$col;
        }
        $datas["updated_by"] = $this->subject_id;
        try {
            $this->db->update('Promo', $datas, array('id' => $ori->id));
            $this->response($ori->id, self::HTTP_OK);
        } catch(Exception $e) {
            $this->response('Error while processing data', self::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}