<?php
/**
 * TODO:
 *  create auther for user referrer as well
 *  create refresh token
 *  consider issuer and audience as security concern
 *  set authorization to bearer
 */
defined('BASEPATH') OR exit('No direct script access allowed');

class Admin extends MY_Controller {
    protected $is_guarded = false;

    function __construct()
    {
        // Construct the parent class
        parent::__construct();

        $this->load->model('Model_user');
    }

    public function login_post()
    {
        $email = $this->post('email');
        $password = $this->post('password');

        if ( !$email || !$password)
        {
            return $this->set_response('Fill your email or password', self::HTTP_BAD_REQUEST);
        }
        
        $user = $this->Model_user->get_user(array('email' => $email));

        if (!$user || $user->password !== md5($password))
        {
            return $this->set_response('Wrong email or password', self::HTTP_UNAUTHORIZED);
        }

        // should only admin type
        if ((int) $user->type !== 1)
        {
            return $this->set_response('Unauthorized Access!', self::HTTP_UNAUTHORIZED, false);
        }

        $date = new DateTime();
        $token = AUTHORIZATION::generateToken(array(
            'sub'   => $user->user_id,
            'role'  => 'admin',
            'iat'   => $date->getTimeStamp(),
            'exp'   => $date->getTimeStamp() + ($this->config->item('token_timeout') * 60),
        ));

        return $this->set_response(array('token' => $token), self::HTTP_OK);
    }
}