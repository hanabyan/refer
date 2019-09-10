<?php
/**
 * TODO:
 *  create auther for user referrer as well
 *  create refresh token
 *  consider issuer and audience as security concern
 *  set authorization to bearer
 */
defined('BASEPATH') OR exit('No direct script access allowed');

class Auth extends MY_Controller {
    protected $is_guarded = false;

    function __construct()
    {
        $this->refer_role = 'user';
        parent::__construct();
    }

    /**
     * For admin only
     * @params in
     *  str email
     *  str email
     */
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

    public function login_post()
    {
        $phone = trim($this->post('phone'));
        $phone = $this->cleanPhone($phone);
        $password = trim($this->post('password'));

        if ( !$phone || !$password ) {
            return $this->response('No. Handphone atau Password salah', self::HTTP_BAD_REQUEST);
        }
        if (strlen($password) < 6) {
          return $this->response('No. Handphone atau Password salah', self::HTTP_BAD_REQUEST);
        }

        $sql = "SELECT `id`, `name`, `phone`, `password` FROM `User` WHERE `phone` = ? LIMIT 1";
        $user = $this->db->query($sql, array($phone))->row();

        if (!$user || FALSE === hash_equals($user->password, md5($password)) ) {
            return $this->response('No. Handphone atau Password salah', self::HTTP_UNAUTHORIZED);
        }

        // TODO: consider issuer and audience as security concern
        $date = new DateTime();
        $token = AUTHORIZATION::generateToken(array(
            'sub'   => $user->id,
            'iat'   => $date->getTimeStamp(),
            'exp'   => $date->getTimeStamp() + (60*60*1),
        ),'user');

        $response = array(
            'token' => $token,
            'name'  => $user->name,
            'email'  => $user->phone,
        );

        return $this->response($response, self::HTTP_OK);
    }
}