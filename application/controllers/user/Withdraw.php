<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Withdraw extends MY_Controller{
  private $user_reward;

  public function __construct()
    {
        $this->refer_role = 'user';
        parent::__construct();

        $this->user_reward = intval($this->subject_id);
    }

    public function index_get()
    {
        $sql = "SELECT * FROM (
          SELECT PU.`code` AS `id`, PU.`status_update` AS `date`, CONCAT('Komisi Penjualan: ', PU.`code`) AS `description`, IFNULL(P.`sku`,'') AS `sku`, P.`estimated_price`, 'cr' AS `type`, U.`name` AS `username`, PU.`commission_value` AS `amount`
          FROM `Promo_User` PU, `Product` P, `User` U
          WHERE
          PU.`product_id` = P.`id` AND PU.`user_id` = U.`id` AND
          PU.`user_id` != PU.`referrer_id` AND PU.`referrer_id` IN (?) AND
          PU.`status` = 1
          UNION ALL
          SELECT UC.`id`, UC.`admin_app_date` AS `date`, 'Tarik Dana' AS `description`, '' AS `sku`, '' AS `estimated_price`, 'db' AS `type`, '' AS `name`, UC.`admin_app_nominal` AS `amount`
          FROM `User_Commission` UC WHERE
          UC.`user_id` = ? AND UC.`status` = 1
          ) AS `H` ORDER BY H.`date` DESC";
        $logs = $this->db->query($sql, array($this->subject_id, $this->subject_id))->result_array();

        $this->response($logs, self::HTTP_OK);
    }

    public function summary_get()
    {
      $in = $this->IN();
      $out = $this->OUT();

      $summary = array(
        'IN' => $in,
        'OUT' => $out
      );
      $this->response($summary, self::HTTP_OK);
    }

    private function IN()
    {
      if ($this->user_reward < 1) {
        return 0;
      }

      $sql = "SELECT IFNULL(SUM(PU.`commission_value`),0) AS `amount` FROM `Promo_User` PU
        WHERE
        PU.`user_id` != PU.`referrer_id` AND PU.`referrer_id` IN (?) AND
        PU.`status` = 1";
      $totalIN = $this->db->query($sql, array($this->user_reward))->row();

      return $totalIN->amount;
    }

    private function OUT()
    {
      if ($this->user_reward < 1) {
        return 0;
      }

      $sql = "SELECT IFNULL(SUM(UC.`admin_app_nominal`),0) AS `amount` FROM `User_Commission` UC WHERE
        UC.`user_id` = ? AND UC.`status` = 1";
      $totalOUT = $this->db->query($sql, array($this->user_reward))->row();

      return $totalOUT->amount;
    }

    private function balance()
    {
      if ($this->user_reward < 1) {
        return 0;
      }

      $in = $this->IN();
      $out = $this->OUT();

      return ($in-$out);
    }

    public function index_post()
    {
        $cols = array(
          'user_req_nominal'
        );
        foreach ($cols as $col) {
            $$col = trim($this->post($col));
        }

        $user_req_nominal = intval($user_req_nominal);
        if ($user_req_nominal<1) {
          $this->response('Nominal salah', self::HTTP_BAD_REQUEST);
        }

        $balance = $this->balance();
        if ($user_req_nominal > $balance) {
          $this->response('Saldo tidak cukup', self::HTTP_BAD_REQUEST);
        }

        try {

          $now = date('Y-m-d H:i:s');
          $datas = array(
            "user_req_date" => $now,
            "user_req_nominal" => $user_req_nominal,
            "user_id" => $this->subject_id,
            "admin_app_nominal" => $user_req_nominal
          );
          $this->db->insert('User_Commission', $datas);
          $lastId = $this->db->insert_id();
          $this->response($lastId, self::HTTP_OK);

        } catch(Exception $e) {
            $this->response('Gagal memproses data', self::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}