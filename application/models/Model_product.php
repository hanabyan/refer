<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Model_Product extends CI_Model {
    protected $table = 'Product';
    protected $table_client = 'Client';
    protected $table_category = 'Categories';

    public function data($page = 0, $limit = 0, $where = array(), $search = '', $order = array(), $as_array = TRUE, $groupby = '')
    {
        $this->db->select($this->table . '.*');
        $this->db->select($this->table_client . '.company_name as client_name');
        $this->db->select($this->table_category . '.name as category_name');
        $this->db->from($this->table);
        $this->db->join($this->table_client, $this->table_client . '.id = ' . $this->table . '.client_id', 'left');
        $this->db->join($this->table_category, $this->table_category . '.id = ' . $this->table . '.category_id', 'left');

        if ($limit > 0) {
            $this->db->limit($limit, $page * $limit - $limit);
        }
        
		if(is_array($where))
		{
			if(count($where) > 0)
			{
				foreach ($where as $key => $value) {
                    $this->db->where($this->table . '.' . $key, $value);
				}
			}
		}
		else
		{
			if($where != '')
			{
				$this->db->where($this->table . '.id', $where);
			}
		}

		if(is_array($order))
		{
			if(count($order) > 0)
			{
				// foreach ($order as $key => $value) {
				// 	if ($key == 'start_price')
				// 	{
				// 		$this->db->order_by($key, $value);
				// 	}else{
				// 		$this->db->order_by($this->table . '.' . $key, $value);
				// 	}
				// }
			}
			else
			{
				$this->db->order_by($this->table . '.created_at', 'DESC');
			}
		}
		else
		{
			if($order != '')
			{
				$this->db->order_by($order);
			}
			else
			{
				$this->db->order_by($this->table . '.created_at', 'DESC');
			}
		}

		$query = $this->db->get();

		if($as_array)
		{
			return $query->result_array();
		}
		else
		{
			return $query->result();
		}
    }

	public function get($where = array(), $as_array = TRUE)
	{
		$get = $this->data(1, 1, $where, '', '', $as_array);
		if(empty($get[0]))
		{
			return null;
		}
		else
		{
			return $get[0];
		}
    }
    
    public function create($data = array())
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

    public function update($id = 0, $data = array(), $where_by = 'id')
	{
		$result['status'] = FALSE;
		$this->db->trans_start();
		$this->db->where($where_by, $this->db->escape($id));
        $this->db->update($this->table, $data);
		if ($this->db->trans_status() === FALSE)
		{
            $error = $this->db->error();
            $result['message_number'] = $error['code'];
            $result['message'] = $error['message'];
		}
        else
        {
			$result['status'] = TRUE;
		    $this->db->trans_complete();
        }

		return $result;
	}
}