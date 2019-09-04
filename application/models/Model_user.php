<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Model_user extends CI_Model {
    protected $table = 'User';
    protected $table_admin = 'Admin';

    public function get_user($where)
    {
        $query = $this->db->get_where($this->table, $where, 1, 0);
        
        return $query->row();
    }

    public function get_user_admin($where)
    {
        $query = $this->db->get_where($this->table_admin, $where, 1, 0);
        
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