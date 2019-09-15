<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Product extends MY_Controller{
    private $shareHost = 'https://refer.co.id/user/c/';

    public function __construct()
    {
        $this->refer_role = 'user';
        parent::__construct();
    }

    public function index_get()
    {
        $sql = "SELECT PU.`promo_id`, PU.`product_id`, COUNT(*) AS `cnt` FROM `Promo` P, `Promo_User` PU WHERE P.`id` = PU.`promo_id` AND NOW() BETWEEN P.`period_start` AND P.`period_end` AND P.`status` = 1 AND PU.`status` IN (1,4) GROUP BY 1, 2";
        $promoRedeemed = $this->db->query($sql)->result_array();

        $sql = "SELECT PR.`promo_id`, PR.`product_id`, PR.`code`, PR.`shared_count` FROM `Promo` P, `Promo_Referrer` PR WHERE P.`id` = PR.`promo_id` AND NOW() BETWEEN P.`period_start` AND P.`period_end` AND P.`status` = 1 AND PR.`user_id` = ?";
        $promoShareCode = $this->db->query($sql,array($this->subject_id))->result_array();

        $sql = "SELECT
        A.`id`, B.`id` AS `product_id`, B.`name` AS `product_name`, B.`description` AS `product_description`, B.`estimated_price`,
        B.`image`, A.`total_item`, '0' AS `total_item_left`,
        P.`id` AS `promo_id`, P.`name` AS `promo_name`, P.`promo_type`, P.`promo_value`, P.`referral_commission`, P.`referral_share_count`, P.`description` AS `promo_description`, '' AS `share_url`, '0' AS `shared_count`
            FROM `Promo_Product` A, `Promo` P, `Product` B
        WHERE
        A.`product_id` = B.`id` AND A.`promo_id` = P.`id` AND
        NOW() BETWEEN P.`period_start` AND P.`period_end` AND P.`status` = 1 AND
        B.`status` = 1";
        $products = $this->db->query($sql)->result_array();

        $productIDsInvalid = array();
        $codeBatch = array();
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
            } else {
                $products[$k]['total_item_left'] = $v['total_item'];
            }

            if ($promoShareCode) {
                $search = array(
                    'promo_id'=>$v['promo_id'],
                    'product_id'=>$v['product_id'],
                );

                $key = $this->multidimensional_search($promoShareCode,$search);
                if (false !== $key) {
                    $products[$k]['share_url'] = $this->shareHost . base64_encode($promoShareCode[$key]['code']);
                    $products[$k]['shared_count'] = $promoShareCode[$key]['shared_count'];
                }
            }

            if (!$products[$k]['share_url']) {
                $newCode = $this->generate_code();
                $products[$k]['share_url'] = $this->shareHost . base64_encode($newCode);
                $codeBatch[] = array(
                    "promo_id"=>$v['promo_id'],
                    "product_id"=>$v['product_id'],
                    "code"=>$newCode,
                    "user_id" => $this->subject_id,
                );
            }
        }
        if ($productIDsInvalid) {
            foreach ($productIDsInvalid as $k) {
                unset($products[$k]);
            }
            $products = array_values($products);
        }
        if ($codeBatch) {
            $this->db->insert_batch('Promo_Referrer', $codeBatch);
        }

        if ($products) {
            array_multisort(array_map(function($element) {
                return $element['product_name'];
            }, $products), SORT_ASC, $products);
        }

        $this->response($products, self::HTTP_OK);
    }

    //id adalah promo_product_id
    public function index_put($id=0)
    {
        $id = intval($id);
        if ($id<1) {
          $this->response('Invalid Promo Produk', self::HTTP_BAD_REQUEST);
        }

        $sql = "SELECT `product_id`, `promo_id` FROM `Promo_Product` WHERE `id` = ?";
        $promoProduct = $this->db->query($sql, array($id))->row();
        if (!$promoProduct) {
            $this->response('Promo Produk tidak ditemukan', self::HTTP_BAD_REQUEST);
        }

        // check product stil valid
        $sql = "SELECT `id` FROM `Product` WHERE `id` = ? AND `status` = 1 LIMIT 1";
        $product = $this->db->query($sql, array($promoProduct->product_id))->row();
        if (!$product) {
            $this->response("Produk tidak ditemukan", self::HTTP_BAD_REQUEST);
        }

        // check promo stil valid
        $sql = "SELECT `id`, `referral_share_count` FROM `Promo` WHERE `id` = ? AND NOW() BETWEEN `period_start` AND `period_end` AND `status` = 1 LIMIT 1";
        $promo = $this->db->query($sql, array($promoProduct->promo_id))->row();
        if (!$promo) {
            $this->response("Promo tidak ditemukan", self::HTTP_BAD_REQUEST);
        }

        try {
            $sql = "UPDATE `Promo_Referrer` SET `shared_count` = `shared_count` + 1 WHERE `promo_id` = ? AND `product_id` = ? AND `user_id` = ?";
            $this->db->query($sql, array($promoProduct->promo_id, $promoProduct->product_id, $this->subject_id));

            $sql = "SELECT `shared_count` FROM `Promo_Referrer` WHERE `promo_id` = ? AND `product_id` = ? AND `user_id` = ? LIMIT 1";
            $promoReferrer = $this->db->query($sql, array($promoProduct->promo_id, $promoProduct->product_id, $this->subject_id))->row();
            if (!$promoReferrer) {
                $this->response("Promo Produk Referrer tidak ditemukan", self::HTTP_BAD_REQUEST);
            }
            // insert ke table promo user
            if ($promoReferrer->shared_count >= $promo->referral_share_count) {
                $sql = "SELECT `id`, `status`, `referrer_id` FROM `Promo_User` WHERE `promo_id` = ? AND `product_id` = ? AND `user_id` = ? LIMIT 1";
                $promoUser = $this->db->query($sql, array($promoProduct->promo_id, $promoProduct->product_id, $this->subject_id))->row();
                if (!$promoUser) {
                    $datas = array(
                        "promo_id" => $promoProduct->promo_id,
                        "product_id" => $promoProduct->product_id,
                        "referrer_id" => $this->subject_id, //karena dirisendiri
                        "user_id" => $this->subject_id,
                        "date" => date("Y-m-d H:i:s"),
                        "code" => "0", //karena dirisendiri
                        "commission_value" => 0, //karena dirisendiri
                        "created_by" => $this->subject_id,
                    );
                    $this->db->insert('Promo_User', $datas);
                    $this->response($promoReferrer->shared_count, self::HTTP_OK);
                } else {
                    //apapun statusnya yg pernah ada asal bukan descline,
                    if ($promoUser->status == '3') {
                        $datas = array(
                            "status" => 0,
                            "referrer_id" => $this->subject_id, //karena dirisendiri
                            "user_id" => $this->subject_id,
                            "date" => date("Y-m-d H:i:s"),
                            "code" => "0", //karena dirisendiri
                            "commission_value" => 0, //karena dirisendiri
                            "created_by" => $this->subject_id,
                        );
                        $this->db->update('Promo_User', $datas, array('id' => $promoUser->id));
                        $this->response($promoReferrer->shared_count, self::HTTP_OK);
                    }
                }
            }
            $this->response($promoReferrer->shared_count, self::HTTP_OK);
        } catch(Exception $e) {
            $this->response('Error while processing data', self::HTTP_INTERNAL_SERVER_ERROR);
        }
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
        usleep(rand(1000,2000));
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