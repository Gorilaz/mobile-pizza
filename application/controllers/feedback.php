<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Feedback extends CI_Controller {

	public function index()
	{
		$name 		= $this->input->post('name');
		$message	=	$this->input->post('message');

		$siteSetting 	= $this->session->userdata('siteSetting');
        $email_from 	=  $siteSetting->EMAIL_FROM; //NAME OF THE REST.
        $site_email 	=  $siteSetting->SITE_EMAIL; //REST. EMAIL
        $admin_email 	=  $siteSetting->confirm_email_to;
		$subject 		= "Feedback From Restaurant's App: " . $siteSetting->restaurant_name;
		$comment 		= "$name wrote: $message";
		$headers 		= "From: $email_from" . "\r\n" .
		    "Reply-To: $site_email" . "\r\n" .
		    'X-Mailer: PHP/' . phpversion();

		//send email
		mail($admin_email, $subject, $comment, $headers);

	}
}

/* End of file welcome.php */
/* Location: ./application/controllers/welcome.php */