<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Profile extends MY_Controller{
    public function __construct()
    {
        $this->refer_role = 'user';
        parent::__construct();

        $this->load->model('Model_user');

        $this->load->library('form_validation');
    }

    public function index_get()
    {
        $user_id = $this->subject_id;

        $sql = "SELECT `name`, `gender`, `email`, `no_ktp`, `no_npwp`, `address`, `province`, `city`, `postal_code`, `bank_name`, `bank_account_beneficiary`, `bank_account_number` FROM `User` where `id` = ? LIMIT 1";
        $user = $this->db->query($sql, array(intval($user_id)))->row();

        if (!$user)
        {
            return $this->set_response('User tidak ditemukan', self::HTTP_BAD_REQUEST);
        }

        return $this->set_response($user, self::HTTP_OK);
    }

    public function index_put()
    {
        $user_id = $this->subject_id;

        $data = $this->put();
        $validation_result = $this->validate_common($data);
        if ($validation_result !== TRUE)
        {
            /* return rest error array */
            return $validation_result;
        }

        $cols = array(
            'name', 'gender', 'email', 'no_ktp', 'no_npwp', 'address', 'province', 'city', 'postal_code', 'bank_name', 'bank_account_beneficiary', 'bank_account_number'
        );

        $sql = "SELECT `id` FROM `User` WHERE `id` = ? LIMIT 1";
        $user = $this->db->query($sql, array($user_id))->row();
        if (!$user)
        {
            return $this->set_response('User tidak ditemukan', self::HTTP_BAD_REQUEST);
        }

        foreach ($cols as $col) {
            $$col = trim($this->put($col));
        }

        $gender = intval($gender);
        $province = intval($province);
        $city = intval($city);

        try {
            $now = date("Y-m-d H:i:s");
            $this->db->trans_begin();

            $datas = array(
              "name" => $name,
              "gender"  => $gender,
              "email"   => $email,
              "no_ktp"  => $no_ktp,
              "no_npwp" => $no_npwp,
              "address" => $address,
              "province"    => $province,
              "city"    => $city,
              "postal_code" => $postal_code,
              "bank_name"   => $bank_name,
              "bank_account_beneficiary"    => $bank_account_beneficiary,
              "bank_account_number" => $bank_account_number,
              "updated_by"  => $this->subject_id,
              "updated_at"  => $now,
              "is_profile_completed"    => 1,
            );
            $this->db->update('User', $datas, array('id' => intval($user->id)));

            if ($this->db->trans_status() === FALSE) {
              $this->db->trans_rollback();

              return $this->response('Database error', self::HTTP_INTERNAL_SERVER_ERROR);
            } else {
                $this->db->trans_commit();

                return $this->response($user->id, self::HTTP_OK);
            }
        } catch (Exception $e) {
            return $this->set_response('Gagal mengubah data', self::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    private function validate_common($data = array())
    {
        $config = array(
            array(
                'field' => 'name',
                'label' => 'Nama',
                'rules' => 'required' 
            ),
            array(
                'field' => 'gender',
                'label' => 'Jenis Kelamin',
                'rules' => 'required' 
            ),
            array(
                'field' => 'email',
                'label' => 'Email',
                'rules' => 'required' 
            ),
            array(
                'field' => 'no_ktp',
                'label' => 'No. KTP',
                'rules' => 'required' 
            ),
            array(
                'field' => 'address',
                'label' => 'Alamat',
                'rules' => 'required' 
            ),
            array(
                'field' => 'province',
                'label' => 'Provinsi',
                'rules' => 'required' 
            ),
            array(
                'field' => 'city',
                'label' => 'Kota',
                'rules' => 'required' 
            ),
            array(
                'field' => 'bank_name',
                'label' => 'Nama Bank',
                'rules' => 'required' 
            ),
            array(
                'field' => 'bank_account_beneficiary',
                'label' => 'Pemilik Rekening Terdaftar',
                'rules' => 'required' 
            ),
            array(
                'field' => 'bank_account_number',
                'label' => 'No. Rekening',
                'rules' => 'required' 
            ),
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
