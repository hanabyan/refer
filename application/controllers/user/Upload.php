<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Upload extends MY_Controller {
    public function __construct()
    {
        $this->refer_role = 'user';
        parent::__construct();

        $this->load->helper(array('form', 'url'));
        $this->load->helper('url');
    }

    public function receipt_post()
    {
        // config
        // $width = 400;
        // $height = 400;
        $upload_dir = 'uploads/images/';
        $name = strtotime('now');

		$image_info = getimagesize($_FILES["image"]['tmp_name']);
		$image_width = $image_info[0];
        $image_height = $image_info[1];
        
        // if ($image_width > $width || $image_height > $height)
        // {
        //     return $this->set_response('The image you are attempting to upload doesn\'t fit into the allowed dimensions. (Expected 400 x 400)', self::HTTP_BAD_REQUEST);
        // }

        //for overwrite file
        // $config['overwrite']            = FALSE;
        $config['file_name']            = $name;
        // $config['max_width']            = 400;
        // $config['max_height']           = 400;
 
        $config['upload_path'] = './' . $upload_dir;
        $config['allowed_types']        = 'gif|jpg|png|jpeg';
        $config['max_size']             = 1048000;
        
        $this->load->library('upload', $config);

        if ( !$this->upload->do_upload('image') ) {
            return $this->set_response($this->upload->display_errors(), self::HTTP_BAD_REQUEST);
        }
        else {
            $result = $this->upload->data();
            $image_path = base_url() . $upload_dir . $result['file_name'];

            return $this->set_response(array('image_path' => $image_path), self::HTTP_OK);
        }
    }
}