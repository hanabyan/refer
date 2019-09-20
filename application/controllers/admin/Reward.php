<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Reward extends MY_Controller {
    private $shareHost = 'https://refer.co.id/user/c/';

    public function __construct()
    {
         parent::__construct();
    }

    public function log_get()
    {
        $rewardID = $this->input->get("id");
        $rewardID = intval($rewardID);

        if ($rewardID) {
          $sql = "SELECT A.`receipt_time`, A.`receipt_place`, A.`note`, A.`status_from`, A.`status_to`, A.`created_at`,
          U.`name` AS `user_name`, C.`name` AS `admin_name`
            FROM `Promo_User_Log` A, `Promo_User` B, `User` U, `Admin` C
          WHERE
            A.`promo_user_id` = B.`id` AND B.`user_id` = U.`id` AND A.`created_by` = C.`id` AND
            A.`promo_user_id` = ?";
          $logs = $this->db->query($sql,array($rewardID))->result_array();
        } else {
          $sql = "SELECT A.`receipt_time`, A.`receipt_place`, A.`note`, A.`status_from`, A.`status_to`, A.`created_at`,
          U.`name` AS `user_name`, C.`name` AS `admin_name`
            FROM `Promo_User_Log` A, `Promo_User` B, `User` U, `Admin` C
          WHERE
            A.`promo_user_id` = B.`id` AND B.`user_id` = U.`id` AND A.`created_by` = C.`id`";
          $logs = $this->db->query($sql)->result_array();
        }

        if ($logs) {
            array_multisort(array_map(function($logs) {
                return $logs['created_at'];
            }, $logs), SORT_DESC, $logs);
        }

        $this->response($logs, self::HTTP_OK);
    }

    public function index_get()
    {
        $sql = "SELECT
        A.`id`, A.`status`, IFNULL(A.`receipt_image`,'') AS `receipt_image`,
        U.`id` AS `user_id`, U.`name` AS `user_name`, U.`phone` AS `user_phone`,
        B.`id` AS `product_id`, B.`name` AS `product_name`,
        B.`description` AS `product_description`, B.`estimated_price`, B.`image`,
        P.`id` AS `promo_id`, P.`name` AS `promo_name`, P.`promo_type`, P.`promo_value`, P.`referral_commission`, P.`referral_share_count`, P.`description` AS `promo_description`, A.`updated_at`, B.`sku`
            FROM `Promo_User` A, `Promo` P, `Product` B, `User` U
        WHERE
          A.`product_id` = B.`id` AND A.`promo_id` = P.`id` AND A.`user_id` = U.`id` AND
          NOW() BETWEEN P.`period_start` AND P.`period_end` AND P.`status` = 1 AND B.`status` = 1";
        $products = $this->db->query($sql)->result_array();
        if ($products) {
            array_multisort(array_map(function($element) {
                return $element['id'];
            }, $products), SORT_DESC, $products);
        }

        $this->response($products, self::HTTP_OK);
    }

    //id adalah promo_user_id
    public function index_put($id=0)
    {
        $id = intval($id);
        if ($id<1) {
          $this->response('Invalid Reward', self::HTTP_BAD_REQUEST);
        }
        $cols = array(
          'status', 'note', 'receipt_time', 'receipt_place'
        );
        foreach ($cols as $col) {
            $$col = trim($this->put($col));
        }

        $status = intval($status);
        if (!in_array($status,array(1,2,3))) {
          $this->response("Invalid Status", self::HTTP_BAD_REQUEST);
        }
        if ($status !== 1 && !$note) {
          $this->response("Note harus diisi", self::HTTP_BAD_REQUEST);
        }
        if (FALSE === DateTime::createFromFormat('Y-m-d H:i:s', $receipt_time)) {
          $this->response("Format Receipt Time salah", self::HTTP_BAD_REQUEST);
        }

        $sql = "SELECT `id`, `promo_id`, `product_id`, `status` FROM `Promo_User` WHERE `id` = ? LIMIT 1";
        $promoUser = $this->db->query($sql, array($id))->row();
        if (!$promoUser) {
          $this->response('Reward tidak ditemukan', self::HTTP_BAD_REQUEST);
        }

        try {
            $now = date("Y-m-d H:i:s");
            $this->db->trans_begin();

            $datas = array(
              "status" => $status,
              "updated_by"=>$this->subject_id,
              "status_comment" => $note,
              "status_by"=>$this->subject_id,
              "status_update"=>$now,
            );
            $this->db->update('Promo_User', $datas, array('id' => $promoUser->id));

            $logs = array(
              "promo_user_id" => $promoUser->id,
              "receipt_time"=>$receipt_time,
              "receipt_place" => $receipt_place,
              "note"=>$note,
              "status_from" => $promoUser->status,
              "status_to"=>$status,
              "created_by"=>$this->subject_id,
            );
            $this->db->insert('Promo_User_Log', $logs);

            if ($this->db->trans_status() === FALSE) {
              $this->db->trans_rollback();
              $this->response('Database error', self::HTTP_INTERNAL_SERVER_ERROR);
            } else {
                $this->db->trans_commit();
                $this->response($promoUser->id, self::HTTP_OK);
            }
        } catch(Exception $e) {
            $this->response('Gagal memproses data', self::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}