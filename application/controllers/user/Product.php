<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Product extends MY_Controller{
    private $shareHost = 'https://refer.co.id/user/';

    public function __construct()
    {
        $this->refer_role = 'user';
        parent::__construct();
    }

    public function claim_post()
    {
        $code = $this->post('code');
        $code = $this->clean_code($code);
        if (!$code) {
            $this->response('Kode salah', self::HTTP_BAD_REQUEST);
        }
        $imageURL = $this->post('image_url');
        if (!$imageURL) {
            $this->response('Gambar harus diisi', self::HTTP_BAD_REQUEST);
        }

        try {
            // check data referrer to get referrer id, promo id, and product id
            $sql = "SELECT `promo_id`, `product_id`, `user_id` FROM `Promo_Referrer` WHERE `code` = ? LIMIT 1";
            $referrer = $this->db->query($sql, array($code))->row();
            if (!$referrer) {
                $this->response('Kode salah', self::HTTP_BAD_REQUEST);
            }

            // check promo stil valid
            $sql = "SELECT `id`,`referral_commission` FROM `Promo` WHERE `id` = ? AND NOW() BETWEEN `period_start` AND `period_end` AND `status` = 1 LIMIT 1";
            $promo = $this->db->query($sql, array($referrer->promo_id))->row();
            if (!$promo) {
                $this->response('Promo tidak ditemukan', self::HTTP_BAD_REQUEST);
            }

            // check product stil valid
            $sql = "SELECT `id` FROM `Product` WHERE `id` = ? LIMIT 1";
            $product = $this->db->query($sql, array($referrer->product_id))->row();
            if (!$product) {
                $this->response('Produk tidak ditemukan', self::HTTP_BAD_REQUEST);
            }

            $sql = "SELECT `id`, `status`, `referrer_id` FROM `Promo_User` WHERE `promo_id` = ? AND `product_id` = ? AND `user_id` = ? LIMIT 1";
            $promoUser = $this->db->query($sql, array($referrer->promo_id, $referrer->product_id, $this->subject_id))->row();
            if (!$promoUser) {
                $datas = array(
                    "promo_id" => $referrer->promo_id,
                    "product_id" => $referrer->product_id,
                    "referrer_id" => $referrer->user_id,
                    "user_id" => $this->subject_id,
                    "receipt_image" => $imageURL,
                    "date" => date("Y-m-d H:i:s"),
                    "code" => $code,
                    "commission_value" => $promo->referral_commission,
                    "created_by" => $this->subject_id,
                );
                $this->db->insert('Promo_User', $datas);
                $lastId = $this->db->insert_id();
                $this->response($lastId, self::HTTP_OK);
            } else {
                if ($promoUser->referrer_id != $referrer->user_id) {
                    $this->response('Produk sudah diklaim', self::HTTP_BAD_REQUEST);
                }
                if ($promoUser->status == '1' || $promoUser->status == '3') {
                    $this->response('Produk sudah diklaim', self::HTTP_BAD_REQUEST);
                }
                $datas = array(
                    "receipt_image" => $imageURL
                );
                $this->db->update('Promo_User', $datas, array('id' => $promoUser->id));
                $this->response($promoUser->id, self::HTTP_OK);
            }
        } catch(Exception $e) {
            $this->response('Gagal memproses data', self::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function share_get()
    {
        $promoID = $this->input->get('promo_id');
        $promoID = intval(trim($promoID));
        $prodID = $this->input->get('product_id');
        $prodID = intval(trim($prodID));

        if ($promoID < 1 || $prodID < 1) {
            $this->response('Promo produk salah', self::HTTP_BAD_REQUEST);
        }
        $this->subject_id = intval($this->subject_id);
        if ($this->subject_id < 1) {
            $this->response('User tidak ditemukan', self::HTTP_BAD_REQUEST);
        }

        $sql = "SELECT `code` FROM `Promo_Referrer` WHERE `promo_id` = ? AND `product_id` = ? AND `user_id` = ? LIMIT 1";
        $product = $this->db->query($sql, array($promoID, $prodID, $this->subject_id))->row();
        if ($product) {
            $this->response($this->shareHost . base64_encode($product->code), self::HTTP_OK);
        } else {
            $sql = "SELECT `id` FROM `Promo` WHERE `id` = ? AND NOW() BETWEEN `period_start` AND `period_end` AND `status` = 1 LIMIT 1";
            $promo = $this->db->query($sql, array($promoID))->row();
            if ($promo) {
                $code = "";
                $valid = true;
                while($valid) {
                    $code = $this->generate_code();
                    $sql = "SELECT `code` FROM `Promo_Referrer` WHERE `code` = ? LIMIT 1";
                    $cek = $this->db->query($sql, array($code))->row();
                    if (!$cek) { // code is unique
                        try {
                            $datas = array(
                                'code' => $code,
                                'promo_id' => $promoID,
                                'product_id' => $prodID,
                                'user_id' => $this->subject_id,
                            );
                            $this->db->insert('Promo_Referrer', $datas);
                            $valid = false;
                        } catch(Exception $e) {
                            $valid = false;
                            $this->response('Gagal memproses data', self::HTTP_INTERNAL_SERVER_ERROR);
                        }
                    }
                    usleep(100000);
                }
                if ($code) {
                    $this->response($this->shareHost . base64_encode($code), self::HTTP_OK);
                } else {
                    $this->response('Gagal memproses data', self::HTTP_INTERNAL_SERVER_ERROR);
                }
            } else {
                $this->response('Promo tidak ditemukan', self::HTTP_BAD_REQUEST);
            }
        }
    }

    public function promo_get()
    {
        $sql = "SELECT PU.`promo_id`, PU.`product_id`, COUNT(*) AS `cnt` FROM `Promo` P, `Promo_User` PU WHERE P.`id` = PU.`promo_id` AND NOW() BETWEEN P.`period_start` AND P.`period_end` AND P.`status` = 1 AND PU.`status` IN (0,1) GROUP BY 1, 2";
        $promoRedeemed = $this->db->query($sql)->result_array();

        $sql = "SELECT PR.`promo_id`, PR.`product_id`, PR.`code` FROM `Promo` P, `Promo_Referrer` PR WHERE P.`id` = PR.`promo_id` AND NOW() BETWEEN P.`period_start` AND P.`period_end` AND P.`status` = 1 AND PR.`user_id` = ?";
        $promoShareCode = $this->db->query($sql,array($this->subject_id))->result_array();

        $sql = "SELECT
        B.`id` AS `product_id`, B.`name` AS `product_name`, B.`description` AS `product_description`, B.`estimated_price`,
        B.`image`, A.`total_item`, A.`total_item` AS `total_item_left`,
        P.`id` AS `promo_id`, P.`name` AS `promo_name`, P.`promo_type`, P.`promo_value`, P.`description` AS `promo_description`,
        '' AS `share_url`
            FROM `Promo_Product` A, `Promo` P, `Product` B
        WHERE
        A.`product_id` = B.`id` AND A.`promo_id` = P.`id` AND
        NOW() BETWEEN P.`period_start` AND P.`period_end` AND P.`status` = 1 AND
        B.`status` = 1";
        $products = $this->db->query($sql)->result_array();

        $productIDsInvalid = array();
        foreach ($products as $k => $v) {
            if ($promoRedeemed) {
                $search = array(
                    'promo_id'=>$v['promo_id'],
                    'product_id'=>$v['product_id'],
                );

                $key = $this->multidimensional_search($promoRedeemed,$search);
                if (false !== $key) {
                    if ($promoRedeemed[$key]['cnt'] >= $v['total_item']) {
                        $productIDsInvalid[] = $k;
                    } else {
                        $products[$k]['total_item_left'] = $v['total_item'] - $promoRedeemed[$key]['cnt'];
                    }
                }
            }

            if ($promoShareCode) {
                $search = array(
                    'promo_id'=>$v['promo_id'],
                    'product_id'=>$v['product_id'],
                );

                $key = $this->multidimensional_search($promoShareCode,$search);
                if (false !== $key) {
                    $products[$k]['share_url'] = $this->shareHost . base64_encode($promoShareCode[$key]['code']);
                }
            }
        }
        if ($productIDsInvalid) {
            foreach ($productIDsInvalid as $k) {
                unset($products[$k]);
            }
            $products = array_values($products);
        }

        if ($products) {
            array_multisort(array_map(function($element) {
                return $element['product_name'];
            }, $products), SORT_ASC, $products);
        }

        $this->response($products, self::HTTP_OK);
    }

    private function multidimensional_search($parents, $searched)
    {
        if (empty($searched) || empty($parents)) {
          return false;
        }

        foreach ($parents as $key => $value) {
          $exists = true;
          foreach ($searched as $skey => $svalue) {
            $exists = ($exists && IsSet($parents[$key][$skey]) && $parents[$key][$skey] == $svalue);
          }
          if($exists){ return $key; }
        }

        return false;
    }

    private function generate_code($length = 8)
    {
        $input = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $input_length = strlen($input);
        $random_string = '';
        for($i = 0; $i < $length; $i++) {
            $random_character = $input[mt_rand(0, $input_length - 1)];
            $random_string .= $random_character;
        }

        return $random_string;
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