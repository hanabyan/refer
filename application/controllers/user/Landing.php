<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Landing extends MY_Controller{
    protected $is_guarded = false;

    public function __construct()
    {
        $this->refer_role = 'user';
        parent::__construct();
    }

    public function index_get()
    {
        $code = $this->input->get('code');
        $code = $this->clean_code($code);
        if (!$code) {
            $this->response('Kode salah', self::HTTP_BAD_REQUEST);
        }

        try {
            // check data referrer to get referrer id, promo id, and product id
            $sql = "SELECT `promo_id`, `product_id`, `user_id` FROM `Promo_Referrer` WHERE `code` = ? LIMIT 1";
            $referrer = $this->db->query($sql, array($code))->row();
            if (!$referrer) {
                $this->response('Kode salah', self::HTTP_BAD_REQUEST);
            }

            // check promo stil valid
            $sql = "SELECT `id`, `name`, `description`, `promo_type`, `promo_value`, `period_start`, `period_end` FROM `Promo` WHERE `id` = ? AND NOW() BETWEEN `period_start` AND `period_end` AND `status` = 1 LIMIT 1";
            $promo = $this->db->query($sql, array($referrer->promo_id))->row();
            if (!$promo) {
                $this->response('Promo tidak ditemukan', self::HTTP_BAD_REQUEST);
            }

            //check valid product
            $sql = "SELECT `id`, `name`, `description`, `estimated_price`, `image` FROM `Product` WHERE `id` = ? LIMIT 1";
            $product = $this->db->query($sql, array($referrer->product_id))->row();
            if (!$product) {
                $this->response('Produk tidak ditemukan', self::HTTP_BAD_REQUEST);
            }

            $sql = "UPDATE `Promo_Referrer` SET `shared_count` = `shared_count` + 1 WHERE `code` = ?";
            $this->db->query($sql, array($code));
            $this->response(array("promo"=>$promo,"product"=>$product), self::HTTP_OK);
        } catch(Exception $e) {
            $this->response('Gagal memproses data', self::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    private function clean_code($code)
    {
        if (!$code) {
            return false;
        }
        $code = trim($code);
        if (!$code) {
            return false;
        }
        $code = strtoupper($code);
        $pattern = '/[^A-Z0-9]/';
        $replacement = '';
        $code = preg_replace($pattern, $replacement, $code);
        if (!$code) {
            return false;
        }
        return $code;
    }
}