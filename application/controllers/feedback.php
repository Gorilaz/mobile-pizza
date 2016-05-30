<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Feedback extends CI_Controller {

	/**
	 * Index Page for this controller.
	 *
	 * Maps to the following URL
	 * 		http://example.com/index.php/welcome
	 *	- or -
	 * 		http://example.com/index.php/welcome/index
	 *	- or -
	 * Since this controller is set as the default controller in
	 * config/routes.php, it's displayed at http://example.com/
	 *
	 * So any other public methods not prefixed with an underscore will
	 * map to /index.php/welcome/<method_name>
	 * @see http://codeigniter.com/user_guide/general/urls.html
	 */
	public function index()
	{
		$name 		= $_POST["name"];
		$message	= $_POST["message"];
		//$file = fopen("output.txt","w");
		//fwrite($file,"$name");
		//fclose($file);



		$admin_email 	= "feedback@freemaila.info";
		$email 			= "feedback@freemaila.info";
		$subject 		= "feedback from pizza web";
		$comment 		= "$name wrote: $message";
		$headers 		= 'From: webmaster@example.com' . "\r\n" .
		    'Reply-To: webmaster@example.com' . "\r\n" .
		    'X-Mailer: PHP/' . phpversion();

		//send email
		mail($admin_email, $subject, $comment, $headers);

	}
}

/* End of file welcome.php */
/* Location: ./application/controllers/welcome.php */