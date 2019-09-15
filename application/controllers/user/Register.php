<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Register extends MY_Controller {
  protected $is_guarded = false;
    public function __construct()
    {
        $this->refer_role = 'user';
        parent::__construct();
    }

    public function index_post()
    {
      $cols = array(
        'name', 'password', 'email', 'phone', 'dob'
      );
      foreach ($cols as $col) {
          $$col = trim($this->post($col));
      }

      $phone = $this->cleanPhone($phone);
      if (!$name || !$password || !$phone || !$dob) {
        return $this->response("Form harus diisi", self::HTTP_BAD_REQUEST);
      }
      if (strlen($password)<6) {
        return $this->response("Password minimal 6 karakter", self::HTTP_BAD_REQUEST);
      }
      if (FALSE === DateTime::createFromFormat('Y-m-d', $dob)) {
        return $this->response("Format Tanggal Lahir salah", self::HTTP_BAD_REQUEST);
      }
      if ($email && FALSE === filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return $this->response("Format email salah", self::HTTP_BAD_REQUEST);
      }
      $sql = "SELECT `id` FROM `User` WHERE `phone` = ? LIMIT 1";
      $userPhone = $this->db->query($sql, array($phone))->row();
      if ($userPhone) {
        return $this->response("No. Handphone sudah terdaftar", self::HTTP_BAD_REQUEST);
      }
      if ($email) {
        $sql = "SELECT `id` FROM `User` WHERE `email` = ? LIMIT 1";
        $userEmail = $this->db->query($sql, array($email))->row();
        if ($userEmail) {
          return $this->response("Email sudah terdaftar", self::HTTP_BAD_REQUEST);
        }
      }

      try {
          $this->db->trans_begin();
          $datas = array(
            'name' => $name,
            'phone' => $phone,
            'email' => ($email?$email:NULL),
            'dob' => $dob,
            'password' => MD5($password)
          );
          if ($this->db->insert('User', $datas)) {
            $lastId = $this->db->insert_id();

            $datasOTP = array(
              'user_id' => $lastId,
              'code' => "111111",
              'sent_time' => date('Y-m-d H:i:s')
            );
            $this->db->insert('User_OTP', $datasOTP);

            if ($this->db->trans_status() === FALSE) {
              $this->db->trans_rollback();
              $this->response('Database error', self::HTTP_INTERNAL_SERVER_ERROR);
            } else {
                $this->db->trans_commit();
                $this->response($lastId, self::HTTP_OK);
            }
          } else {
            $this->response('Gagal memproses data', self::HTTP_INTERNAL_SERVER_ERROR);
          }
      } catch(Exception $e) {
          $this->response('Gagal memproses data', self::HTTP_INTERNAL_SERVER_ERROR);
      }
    }

    public function verify_put($id=0)
    {
        $id = intval($id);
        if ($id<1) {
          $this->response('Invalid User ID', self::HTTP_BAD_REQUEST);
        }

        $otp = $this->put('code');
        $otp = $this->cleanOTP(trim($otp));
        if (!$otp) {
          return $this->response("Invalid kode", self::HTTP_BAD_REQUEST);
        }

        $sql = "SELECT `id` FROM `User_OTP` WHERE `user_id` = ? AND `code` = ? LIMIT 1";
        $otpLog = $this->db->query($sql, array($id,$otp))->row();
        if (!$otpLog) {
          $this->response('Kode tidak ditemukan', self::HTTP_BAD_REQUEST);
        }

        $sql = "SELECT `id`, `verified` FROM `User` WHERE `id` = ? AND `status` = 1";
        $user = $this->db->query($sql, array($id))->row();
        if (!$user) {
          $this->response('User tidak ditemukan', self::HTTP_BAD_REQUEST);
        }

        try {
            $datas = array('verified'=>1);
            $this->db->update('User', $datas, array('id' => $id));
            $this->response($id, self::HTTP_OK);
        } catch(Exception $e) {
            $this->response('Gagal memproses data', self::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    private function cleanOTP($code="")
    {
      if (!$code) {
        return false;
      }
      $code = preg_replace('/\D/', '', $code);
      if (!$code || strlen($code) < 6) {
        return false;
      }
      return $code;
    }

    private function cleanPhone($phone="")
    {
      if (!$phone) {
        return false;
      }
      $phone = preg_replace('/\D/', '', $phone);
      if (!$phone || strlen($phone) < 5) {
        return false;
      }
      if (substr($phone, 0,2) === "00") {
        return false;
      }
      if (substr($phone, 0,3) === "620") {
        return false;
      }
      if (substr($phone, 0,4) === "6200") {
        return false;
      }
      if (substr($phone, 0,1) === "0") {
        return $phone;
      }
      if (substr($phone, 0,2) === "62") {
        return "0".substr($phone, 2);
      }
      return "+".$phone;
    }

    private function generate_otp($length = 6)
    {
        $input = '0123456789';
        $input_length = strlen($input);
        $random_string = '';
        for($i = 0; $i < $length; $i++) {
            $random_character = $input[mt_rand(0, $input_length - 1)];
            $random_string .= $random_character;
        }
        return $random_string;
    }
}