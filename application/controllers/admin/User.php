<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class User extends MY_Controller {
    public function __construct()
    {
        parent::__construct();

        $this->load->model('Model_user');

        $this->load->library('form_validation');
    }

    public function index_get()
    {
        $data = $this->Model_user->get_users();

        return $this->set_response($data, self::HTTP_OK);
    }

    /**
     * 
     */
    public function index_post()
    {
        /*
            TODO: check duplicate user
            TODO: untuk status, tergantung jenis usernya
            TODO: untuk type, harus ngecek credential 
            TODO: referral code akan digeneratekah, untuk jenis user referral
        */

        $config = array(
            array(
                'field' => 'name',
                'label' => 'Name',
                'rules' => 'required' 
            ),
            array(
                'field' => 'password',
                'label' => 'Password',
                'rules' => 'required|min_length[6]' 
            ),
            array(
                'field' => 'phone',
                'label' => 'Phone',
                'rules' => 'required' 
            ),
        );

        $data = $this->post();
        $this->form_validation->set_data($data);
        $this->form_validation->set_rules($config);
        
        if ($this->form_validation->run() == FALSE)
        {
            return $this->set_response($this->form_validation->error_array(), self::HTTP_BAD_REQUEST);
        }

        // grab all post data
        $username = $this->_isExists('username', $data);
        $password = $this->_isExists('password', $data);
        $name = $this->_isExists('name', $data);
        $phone = $this->_isExists('phone', $data);
        $email = $this->_isExists('email', $data);
        $status = $this->_isExists('status', $data);
        $type = $this->_isExists('type', $data);

        // TODO: need more logic here
        $merchant = TRUE;
        if ($merchant)
        {
            $type = 2;
        }

        $user_to_create = array(
            'username'  => $username,
            'password'  => md5($password),
            'name'  => $name,
            'phone'  => $phone,
            'email'  => $email,
            'status'  => $status,
            'type'  => $type,
        );
                
        $result = $this->Model_user->create_user($user_to_create);
        
        if (!$result['status'])
        {
            return $this->set_response($result['message'], self::HTTP_INTERNAL_SERVER_ERROR);
        }

        return $this->set_response($result, self::HTTP_OK);
    }
}