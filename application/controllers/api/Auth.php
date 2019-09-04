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
        parent::__construct();

        $this->load->model('Model_user');
    }

    /**
     * For admin only
     * @params in
     *  str email
     *  str email
     */
    public function login_post()
    {
        $email = $this->post('email');
        $password = $this->post('password');

        if ( !$email || !$password)
        {
            return $this->set_response('Fill your email or password', self::HTTP_BAD_REQUEST);
        }
        
        $user = $this->Model_user->get_user_admin(array('email' => $email));

        if (!$user || $user->password !== md5($password))
        {
            return $this->set_response('Wrong email or password', self::HTTP_UNAUTHORIZED);
        }

        // TODO: consider issuer and audience as security concern
        $date = new DateTime();
        $token = AUTHORIZATION::generateToken(array(
            'sub'   => $user->id,
            'iat'   => $date->getTimeStamp(),
            'exp'   => $date->getTimeStamp() + (60*60*1),
        ));

        $response = array(
            'token' => $token,
            'name'  => $user->name,
            'email'  => $user->email,
        );

        return $this->set_response($response, self::HTTP_OK);
    }
}