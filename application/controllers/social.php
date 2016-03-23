<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Social extends WMDS_Controller {

	public function index()
	{
            header('Content-Type: application/javascript');
            $appId = $this->db->select('value')->where('type', 'facebook_app_id')->get('sitesetting')->row();
            $appKey = $this->db->select('value')->where('type', 'google_api_key')->get('sitesetting')->row();
            $clientId = $this->db->select('value')->where('type', 'google_client_id')->get('sitesetting')->row();
            $this->twiggy->set('appId', $appId->value );
            $this->twiggy->set('appkey', $appKey->value );
            $this->twiggy->set('clientId', $clientId->value );
            $this->twiggy->template('social-ids')->display();
	}
}
