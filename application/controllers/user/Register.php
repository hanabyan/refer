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
          $datas = array(
            'name' => $name,
            'phone' => $phone,
            'email' => ($email?$email:NULL),
            'dob' => $dob,
            'password' => MD5($password)
          );
          $this->db->insert('User', $datas);
          $lastId = $this->db->insert_id();
          $this->response($lastId, self::HTTP_OK);
      } catch(Exception $e) {
          $this->response('Gagal memproses data', self::HTTP_INTERNAL_SERVER_ERROR);
      }
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
}