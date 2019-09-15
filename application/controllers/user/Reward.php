<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Reward extends MY_Controller{
    private $shareHost = 'https://refer.co.id/user/c/';

    public function __construct()
    {
        $this->refer_role = 'user';
        parent::__construct();
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

    public function index_get()
    {
        // $sql = "SELECT PU.`promo_id`, PU.`product_id`, COUNT(*) AS `cnt` FROM `Promo` P, `Promo_User` PU WHERE P.`id` = PU.`promo_id` AND NOW() BETWEEN P.`period_start` AND P.`period_end` AND P.`status` = 1 AND PU.`status` IN (1,4) GROUP BY 1, 2";
        // $promoRedeemed = $this->db->query($sql)->result_array();

        $sql = "SELECT
        A.`id`, A.`status`, IFNULL(A.`status_update`,'') AS `validate_time`, IFNULL(A.`status_comment`,'') AS `note`,
        IFNULL(A.`receipt_image`,'') AS `receipt_image`, B.`id` AS `product_id`, B.`name` AS `product_name`,
        B.`description` AS `product_description`, B.`estimated_price`, B.`image`,
        P.`id` AS `promo_id`, P.`name` AS `promo_name`, P.`promo_type`, P.`promo_value`, P.`referral_commission`, P.`referral_share_count`, P.`description` AS `promo_description`, '' AS `share_url`, '0' AS `shared_count`
            FROM `Promo_User` A, `Promo` P, `Product` B
        WHERE
          A.`user_id` = ? AND A.`product_id` = B.`id` AND A.`promo_id` = P.`id` AND
          NOW() BETWEEN P.`period_start` AND P.`period_end` AND P.`status` = 1 AND B.`status` = 1";
        $products = $this->db->query($sql, array($this->subject_id))->result_array();

        $productIDsInvalid = array();
        $codeBatch = array();
        foreach ($products as $k => $v) {
            if ($v['status'] == '3') { // yg deceline di remove aja
              $productIDsInvalid[] = $k;
              continue;
            }
            // if ($promoRedeemed) {
            //     $search = array(
            //         'promo_id'=>$v['promo_id'],
            //         'product_id'=>$v['product_id'],
            //     );

            //     $key = $this->multidimensional_search($promoRedeemed,$search);
            //     if (false !== $key) {
            //         if ($promoRedeemed[$key]['cnt'] >= $v['total_item']) {
            //             $productIDsInvalid[] = $k;
            //         } else {
            //             $products[$k]['total_item_left'] = $v['total_item'] - $promoRedeemed[$key]['cnt'];
            //         }
            //     }
            // } else {
            //     $products[$k]['total_item_left'] = $v['total_item'];
            // }
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

    public function index_post()
    {
        $resp = array(
          'promo'=>array(),
          'product'=>array(),
          'reward'=>0,
          'message'=>'',
        );

        $code = $this->post('code');
        $code = $this->clean_code($code);
        if (!$code) {
            $resp['message'] = "Kode salah";
            $this->response('Kode salah', self::HTTP_BAD_REQUEST);
        }

        try {
            // check data referrer to get referrer id, promo id, and product id
            $sql = "SELECT `promo_id`, `product_id`, `user_id` FROM `Promo_Referrer` WHERE `code` = ? LIMIT 1";
            $referrer = $this->db->query($sql, array($code))->row();
            if (!$referrer) {
                $resp['message'] = "Kode salah";
                $this->response('Kode salah', self::HTTP_BAD_REQUEST);
            }

            // check product stil valid
            $sql = "SELECT `id`, `name`, `description`, `estimated_price`, `image` FROM `Product` WHERE `id` = ? AND `status` = 1 LIMIT 1";
            $product = $this->db->query($sql, array($referrer->product_id))->row();
            if (!$product) {
                $resp['message'] = "Produk tidak ditemukan";
                $this->response($resp, self::HTTP_BAD_REQUEST);
            }
            $resp['product']=$product;

            // check promo stil valid
            $sql = "SELECT `id`, `name`, `description`, `promo_type`, `promo_value`, `period_start`, `period_end`, `referral_commission` FROM `Promo` WHERE `id` = ? AND NOW() BETWEEN `period_start` AND `period_end` AND `status` = 1 LIMIT 1";
            $promo = $this->db->query($sql, array($referrer->promo_id))->row();
            if (!$promo) {
                $resp['message'] = "Promo tidak ditemukan";
                $this->response($resp, self::HTTP_BAD_REQUEST);
            }
            $resp['promo']=$promo;

            // chek promo product masih kurang dari limit apa blum
            $sql = "SELECT COUNT(*) AS `redeemed` FROM `Promo_User` WHERE `promo_id` = ? AND `product_id` = ? AND `status` IN (1,4)";
            $promoRedeem = $this->db->query($sql, array($referrer->promo_id,$referrer->product_id))->row();
            if ($promoRedeem) {
                $sql = "SELECT `total_item` FROM `Promo_Product` WHERE `product_id`=? AND `promo_id`=? LIMIT 1";
                $promoProduct = $this->db->query($sql, array($referrer->product_id,$referrer->promo_id))->row();
                if (!$promoProduct) {
                    $resp['message'] = "Promo Produk tidak ditemukan";
                    $this->response($resp, self::HTTP_BAD_REQUEST);
                }
                if ($promoRedeem->redeemed >= $promoProduct->total_item ) {
                    $resp['message'] = "Promo Produk sudah tidak sudah";
                    $this->response($resp, self::HTTP_BAD_REQUEST);
                }
            }

            $sql = "SELECT `id`, `status`, `referrer_id` FROM `Promo_User` WHERE `promo_id` = ? AND `product_id` = ? AND `user_id` = ? LIMIT 1";
            $promoUser = $this->db->query($sql, array($referrer->promo_id, $referrer->product_id, $this->subject_id))->row();
            if (!$promoUser) {
                $datas = array(
                    "promo_id" => $referrer->promo_id,
                    "product_id" => $referrer->product_id,
                    "referrer_id" => $referrer->user_id,
                    "user_id" => $this->subject_id,
                    "date" => date("Y-m-d H:i:s"),
                    "code" => $code,
                    "commission_value" => $promo->referral_commission,
                    "created_by" => $this->subject_id,
                );
                $this->db->insert('Promo_User', $datas);
                $lastId = $this->db->insert_id();
                $resp['reward'] = $lastId;
                $this->response($resp, self::HTTP_OK);
            } else {
                if ($promoUser->status == '0') {
                  // jika dari referer yg berbeda, diupdate aja refererya
                  if ($promoUser->referrer_id != $referrer->user_id) {
                    $datas = array(
                        "referrer_id" => $referrer->user_id
                    );
                    $this->db->update('Promo_User', $datas, array('id' => $promoUser->id));
                  }
                  $resp['reward'] = $promoUser->id;
                  $this->response($resp, self::HTTP_OK);
                } else {
                  $resp['reward'] = $promoUser->id;
                  $resp['message'] = "Promo Produk sudah diklaim";
                  $this->response('Produk sudah diklaim', self::HTTP_BAD_REQUEST);
                }
            }
        } catch(Exception $e) {
            $this->response('Gagal memproses data', self::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    //id adalah promo_user_id
    public function index_put($id=0)
    {
        $id = intval($id);
        if ($id<1) {
          $this->response('Invalid Reward', self::HTTP_BAD_REQUEST);
        }
        $imageURL = $this->put('image_url');
        if (!$imageURL) {
            $this->response('Gambar harus diisi', self::HTTP_BAD_REQUEST);
        }

        try {
            $sql = "SELECT `id`, `promo_id`, `product_id`, `status`, `referrer_id`, IFNULL(`status_update`,'') AS `status_update` FROM `Promo_User` WHERE `id` = ? AND `user_id` = ? LIMIT 1";
            $promoUser = $this->db->query($sql, array($id, $this->subject_id))->row();
            if (!$promoUser) {
              $this->response('Promo Produk tidak ditemukan', self::HTTP_BAD_REQUEST);
            }
            // check ke absahan status
            if ($promoUser->status == '1') {
              $this->response('Promo Produk sudah diklaim', self::HTTP_BAD_REQUEST);
            }
            if ($promoUser->status == '3') {
              $this->response('Promo Produk tidak ditemukan', self::HTTP_BAD_REQUEST);
            }

            // check product stil valid
            $sql = "SELECT `id` FROM `Product` WHERE `id` = ? AND `status` = 1 LIMIT 1";
            $product = $this->db->query($sql, array($promoUser->product_id))->row();
            if (!$product) {
                $this->response('Produk tidak ditemukan', self::HTTP_BAD_REQUEST);
            }

            // check promo stil valid
            $sql = "SELECT `id` FROM `Promo` WHERE `id` = ? AND NOW() BETWEEN `period_start` AND `period_end` AND `status` = 1 LIMIT 1";
            $promo = $this->db->query($sql, array($promoUser->promo_id))->row();
            if (!$promo) {
                if (!$promoUser->status_update) {
                  $this->response('Promo tidak ditemukan', self::HTTP_BAD_REQUEST);
                }
            }

            $datas = array(
              "receipt_image" => $imageURL,
              "status"=>4,
            );
            $this->db->update('Promo_User', $datas, array('id' => $promoUser->id));
            $this->response($promoUser->id, self::HTTP_OK);
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