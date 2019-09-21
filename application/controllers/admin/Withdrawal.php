<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Withdrawal extends MY_Controller {
    public function __construct()
    {
        parent::__construct();
    }

    public function index_get()
    {
        $sql = "SELECT UC.`id`, `user_req_date`, `user_req_nominal`, UC.`user_id`, U.`name` AS `user_name`, U.`phone` AS `user_phone`, `admin_app_date`, `admin_app_note`, UC.`admin_id`, A.`name` AS `admin_name`, UC.`status`,
        CASE WHEN UC.`status` = 0 THEN 'New'
        WHEN UC.`status` = 1 THEN 'Approved'
        WHEN UC.`status` = 2 THEN 'Declined'
        END AS `status_name`,
        U.`bank_name`, U.`bank_account_beneficiary`, U.`bank_account_number`, U.`no_ktp`, U.`no_npwp`
        FROM `User_Commission` UC
        LEFT JOIN `User` U
        ON UC.`user_id` = U.`id`
        LEFT JOIN `Admin` A
        ON UC.`admin_id` = A.`id`";

        $withdraw = $this->db->query($sql)->result_array();

        return $this->set_response($withdraw, self::HTTP_OK);
    }

    public function index_put($withdrawal_id = null)
    {
        if (!$withdrawal_id)
        {
            return $this->set_response('Withdrawal tidak ditemukan', self::HTTP_BAD_REQUEST);
        }

        if ($this->put('status') === NULL)
        {
            return $this->set_response('Status harus diisi', self::HTTP_BAD_REQUEST);
        }

        $cols = array(
            'note', 'status'
        );

        foreach ($cols as $col) {
            $$col = trim($this->put($col));
        }

        $status = intval($status);

        if (!in_array($status,array(1, 2, 3))) {
            return $this->set_response('Invalid Status', self::HTTP_BAD_REQUEST);
        }

        $sql = "SELECT `id` from `User_Commission` WHERE `id` = ?";
        $withdrawal = $this->db->query($sql, array(intval($withdrawal_id)))->row();

        if (!$withdrawal)
        {
            return $this->set_response('Withdrawal tidak ditemukan', self::HTTP_BAD_REQUEST);
        }

        foreach ($cols as $col) {
            $$col = trim($this->put($col));
        }

        try {
            $now = date("Y-m-d H:i:s");
            $this->db->trans_begin();

            $datas = array(
              "status" => $status,
              "admin_app_date"  => $now,
              "admin_app_note"  => $note,
              "admin_id"  => $this->subject_id,
              "updated_at"  => $now,
            );

            $this->db->update('User_Commission', $datas, array('id' => $withdrawal->id));

            if ($this->db->trans_status() === FALSE) {
              $this->db->trans_rollback();

              return $this->set_response('Database error', self::HTTP_INTERNAL_SERVER_ERROR);
            } else {
                $this->db->trans_commit();

                return $this->set_response($withdrawal->id, self::HTTP_OK);
            }
        } catch(Exception $e) {
            return $this->set_response('Gagal memproses data', self::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function balance_get($user_id = null)
    {
        if (!$user_id)
        {
            return $this->set_response('User tidak ditemukan', self::HTTP_BAD_REQUEST);
        }

        $summary = $this->calc_balance($user_id);

        if (!$summary)
        {
            return $this->set_response('User tidak ditemukan', self::HTTP_BAD_REQUEST);
        }

        return $this->set_response($summary, self::HTTP_OK);
    }

    private function calc_balance($user_id)
    {
        $sql = "SELECT SUM(IFNULL(`CR`, 0)) AS `total_cr`, SUM(IFNULL(`DB`, 0)) AS `total_db`, SUM(IFNULL(`DB`, 0)) - SUM(IFNULL(`CR`, 0)) AS `balance` FROM (SELECT NULL AS `CR`, `commission_value` AS `DB` FROM `Promo_User`
            WHERE `user_id` != `referrer_id`
            AND `status` = 1
            AND `referrer_id` = ?
            UNION ALL
            SELECT `admin_app_nominal` AS `CR`, NULL AS `DB` FROM `User_Commission`
            WHERE `user_id` = ?
            AND `status` = 1) AS `Summary`
        ";

        $summary = $this->db->query($sql, array($user_id, $user_id))->row();

        return $summary;
    }
}