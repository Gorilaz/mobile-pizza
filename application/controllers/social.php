<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Social extends WMDS_Controller {

	public function index()
	{
            header('Content-Type: application/javascript');
            $appId = $this->db->select('value')->where('type', 'facebook_app_id')->get('sitesetting')->row();
            $this->twiggy->set('appId', $appId->value );
            $this->twiggy->template('social-ids')->display();
	}
}
