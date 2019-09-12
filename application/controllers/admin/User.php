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
        $data = $this->Model_user->data();

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
            TODO: check duplicate phone and email
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
        $name = $this->_isExists('name', $data);
        $phone = $this->_isExists('phone', $data);
        $email = $this->_isExists('email', $data);
        $verified = $this->_isExists('verified', $data);
        $password = $this->_isExists('pass$password', $data);

        $user_to_create = array(
            'name'  => $name,
            'phone'  => $phone,
            'email'  => $email,
            'verified'  => $verified,
            'password'  => md5($password),
            'created_by'  => $this->subject_id
        );
                
        $result = $this->Model_user->create($user_to_create);
        
        if (!$result['status'])
        {
            return $this->set_response($result['message'], self::HTTP_INTERNAL_SERVER_ERROR);
        }

        return $this->set_response($result, self::HTTP_OK);
    }

    public function index_put($id)
    {
        $config = array(
            array(
                'field' => 'name',
                'label' => 'Name',
                'rules' => 'required' 
            ),
            array(
                'field' => 'phone',
                'label' => 'Phone',
                'rules' => 'required' 
            ),
        );

        $data = $this->put();
        $this->form_validation->set_data($data);
        $this->form_validation->set_rules($config);
        
        if ($this->form_validation->run() == FALSE)
        {
            return $this->set_response($this->form_validation->error_array(), self::HTTP_BAD_REQUEST);
        }

        // grab all post data
        $name = $this->_isExists('name', $data);
        $phone = $this->_isExists('phone', $data);
        $email = $this->_isExists('email', $data);
        $verified = $this->_isExists('verified', $data);
        $password = $this->_isExists('password', $data);

        $user_to_update = array(
            'name'  => $name,
            'phone'  => $phone,
            'email'  => $email,
            'verified'  => $verified,
            'updated_by'  => $this->subject_id
        );

        if (isset($password) && strlen(trim($password)) > 0) {
            $user_to_update = array_merge($user_to_update, array(
                'password'  => md5($password)
            ));
        }
                
        $result = $this->Model_user->update((int)$id, $user_to_update);
        
        if (!$result['status'])
        {
            return $this->set_response($result['message'], self::HTTP_INTERNAL_SERVER_ERROR);
        }

        return $this->set_response($result, self::HTTP_OK);
    }
}