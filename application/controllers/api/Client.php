<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Client extends MY_Controller {
    public function __construct()
    {
        parent::__construct();

        $this->load->model('Model_client');

        $this->load->library('form_validation');
    }

    /**
     * return:
     *      int client_id
     *      str company_name
     *      int business_category_id
     *      str business_category_name
     *      str pic_name
     *      str pic_mobile
     *      str pic_email
     *      str website
     *      str business_address
     */
    public function index_get()
    {
        $data = $this->Model_client->data();

        return $this->set_response($data, self::HTTP_OK);
    }

    /**
     * SET DEFAULT status, password
     * user identifier username
     */
    // TODO: check user exists
    public function index_post()
    {
        $data = $this->post();
        $validation_result = $this->validate_common($data);
        if ($validation_result !== TRUE)
        {
            /* return rest error array */
            return $validation_result;
        }

        /**
         * collect data
         */

        $company_name = $this->_isExists('company_name', $data);
        $business_category_id = $this->_isExists('business_category_id', $data);
        $website = $this->_isExists('website', $data);
        $business_address = $this->_isExists('business_address', $data);
        $pic_name = $this->_isExists('pic_name', $data);
        $mobile_phone = $this->_isExists('mobile_phone', $data);
        $email = $this->_isExists('email', $data);
        $username = $this->_isExists('username', $data);
        $password = $this->_isExists('password', $data);
        $business_address = $this->_isExists('business_address', $data);

        // TODO: log created_at, created_by

        $client_to_create = array(
            'company_name'  => $company_name,
            'business_category_id'  => $business_category_id,
            'website'  => $website,
            'business_address'  => $business_address,
            'pic_name'  => $pic_name,
            'mobile_phone'  => $mobile_phone,
            'email'  => $email,
            'username'  => $username,
            'business_address'  => $business_address,
            'password'  => md5($password),
            'status'  => 1,
            'created_at'    => date('Y-m-d H:i:s'),
            'created_by'    => $this->subject_id
        );

        $result = $this->Model_client->create($client_to_create);

        if (!$result['status'])
        {
            $error_message = 'Something went wrong';

            if ($result['message_number'] == 1062)
            {
                $error_message = 'Can not create new client, username already taken';
            }

            return $this->set_response($error_message, self::HTTP_INTERNAL_SERVER_ERROR);
        }

        return $this->set_response($result, self::HTTP_OK);
    }

    public function index_put($id = null)
    {
        if (!$id)
        {
            return $this->set_response('Client id can not be null', self::HTTP_BAD_REQUEST);
        }

        $data = $this->put();
        $validation_result = $this->validate_common($data);
        if ($validation_result !== TRUE)
        {
            /* return rest error array */
            return $validation_result;
        }

        $company_name = $this->_isExists('company_name', $data);
        $business_category_id = $this->_isExists('business_category_id', $data);
        $website = $this->_isExists('website', $data);
        $business_address = $this->_isExists('business_address', $data);
        $pic_name = $this->_isExists('pic_name', $data);
        $mobile_phone = $this->_isExists('mobile_phone', $data);
        $email = $this->_isExists('email', $data);
        $username = $this->_isExists('username', $data);
        $password = $this->_isExists('password', $data);
        $business_address = $this->_isExists('business_address', $data);   
        
        $client_payload = array(
            'company_name'  => $company_name,
            'business_category_id'  => $business_category_id,
            'website'  => $website,
            'business_address'  => $business_address,
            'pic_name'  => $pic_name,
            'mobile_phone'  => $mobile_phone,
            'email'  => $email,
            'username'  => $username,
            'business_address'  => $business_address,
            'password'  => md5($password),
            'status'  => 1,
            'updated_at'    => date('Y-m-d H:i:s'),
            'updated_by'    => $this->subject_id
        );

        $result = $this->Model_client->update((int)$id, $client_payload);

        if (!$result['status'])
        {
            $error_message = 'Something went wrong';

            if ($result['message_number'] == 1062)
            {
                $error_message = 'Can not update username, it already taken';
            }

            return $this->set_response($error_message, self::HTTP_INTERNAL_SERVER_ERROR);
        }

        $updated_client = $this->Model_client->get(array( 'id'  => $id ));

        return $this->set_response($updated_client, self::HTTP_OK);
    }

    private function validate_common($data = array())
    {
        $config = array(
            array(
                'field' => 'company_name',
                'label' => 'Company Name',
                'rules' => 'required' 
            ),
            array(
                'field' => 'business_category_id',
                'label' => 'Category',
                'rules' => 'required' 
            ),
            array(
                'field' => 'pic_name',
                'label' => 'PIC Name',
                'rules' => 'required' 
            ),
            array(
                'field' => 'mobile_phone',
                'label' => 'PIC Phone',
                'rules' => 'required' 
            ),
            array(
                'field' => 'email',
                'label' => 'PIC Email',
                'rules' => 'required' 
            ),
            array(
                'field' => 'username',
                'label' => 'Username',
                'rules' => 'required' 
            ),
            // array(
            //     'field' => 'password',
            //     'label' => 'Password',
            //     'rules' => 'required' 
            // ),
        );

        $this->form_validation->set_data($data);
        $this->form_validation->set_rules($config);

        if ($this->form_validation->run() == FALSE)
        {
            return $this->set_response($this->form_validation->error_array(), self::HTTP_BAD_REQUEST);
        }

        return true;
    }
}