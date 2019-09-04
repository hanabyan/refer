<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Model_Promo extends CI_Model {
    protected $table = 'Promo';
    protected $table_pro_product = 'Promo_Product';
    protected $table_pro_user = 'Promo_User';
    protected $table_pro_referrer = 'Promo_Referrer';

    public function get_user($where)
    {
        $query = $this->db->get_where($this->table, $where, 1, 0);
        
        return $query->row();
    }

    public function create_user($data = array())
    {
        $result['status'] = FALSE;

        $this->db->trans_start();

        if ($this->db->insert($this->table, $data))
        {
            $result['id'] = $this->db->insert_id();
            $result['status'] = TRUE;
            $this->db->trans_complete(); // trans complete will end access to get $this->db functions
        } else {
            $error = $this->db->error();
            $result['message'] = $error['message'];            
        }

        return $result;
    }

    public function get_merchants()
    {
        $query = $this->db->get($this->table);

        return $query->result_array();
    }
}