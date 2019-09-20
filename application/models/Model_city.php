<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Model_city extends CI_Model {
    protected $table = 'M_City';

    public function data($page = 0, $limit = 0, $where = array(), $search = '', $order = array(), $as_array = TRUE, $groupby = '')
    {
        $this->db->select($this->table . '.*');
        $this->db->from($this->table);

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
				$this->db->order_by($this->table . '.city_name');
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
				$this->db->order_by($this->table . '.city_name');
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

    public function get($where)
    {
        $query = $this->db->get_where($this->table, $where, 1, 0);
        
        return $query->row();
    }
}