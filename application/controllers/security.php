<?php
/**
 * @property Security_model $security_model
 */
class Security extends WMDS_Controller {

    public function __construct(){

        parent::__construct();

        $this->load->model('security_model');
        $this->load->library('session');

    }

    /*******  Login   *********/

    public function login_page(){

        $this->twiggy->set('page', array(
            'title'  => 'Login',
            'role'   => 'page',
            'theme'  => 'a',
            'id'     => 'security-login',
            'backButton'=> true,
        ));


        $this->twiggy->display('account/login');
    }
    
    function googleplus_login()
    {
        $post = $this->input->post();
        
        var_dump($post);
        
    }

    /**
     * Verify if logged (ajax)
     */
    public function login(){

        $user = $this->input->post();

        if($user['user'] && $user['pass'] ){

            $is_login  = $this->security_model->login($user);
            if($is_login != 'no_user'){

                $this->session->set_userdata('logged', $is_login);
                echo json_encode(array(
                    'login' => 'true'
                ));
            } else {
                echo json_encode(array(
                    'login' => 'false'
                ));
            }


        } else {
            echo json_encode(array(
                'login' => 'required fields'
            ));
        }
    }

    /**********  Reset Password  ********************************/

    /**
     * Reset password page
     */
    public function reset(){

        $this->twiggy->set('page', array(
            'title'  => 'Recover Password',
            'role'   => 'page',
            'theme'  => 'a',
            'id'     => 'page-recover',
            'backButton'=> true,
        ));

       $this->twiggy->display('account/reset');


    }

    /**
     * Check If Email Is Valid
     */
    public function checkValidEmail(){
        $email = $this->input->post('email');

        $code = md5(uniqid(rand(), true));
        $valid = $this->security_model->checkValidEmail($email, $code);
        if($valid){

            $this->load->library('email');

            $this->email->from('office@pizza.com', 'Pizza');
            $this->email->to($email);

            $this->email->subject('Recovery Password');
            $this->email->message('Hello ' . $email . '! To Recovery Your Password - please visit:'.base_url().'/change-password/'. $code );

            $this->email->send();


            echo json_encode('valid');

        } else {
            echo json_encode('invalid');
        }
    }

    public function changePassword($code){


        $this->twiggy->set('page', array(
            'title'  => 'Recover Password',
            'role'   => 'page',
            'theme'  => 'a',
            'id'     => 'page-change'
        ));

        $this->twiggy->set('code', $code);

        $this->twiggy->display('account/change_pass');
    }

    public function savePassword(){

        $code = $this->input->post('code');
        $password = $this->input->post('pass');

        $this->security_model->updatePassword($code, $password);

    }
    /************************  Edit / Register  *******************/


    public function edit(){

        $logged = $this->session->userdata('logged');
//        print_r($logged);die;

        $this->twiggy->set('logged', $logged);

        $this->load->model('general');
        $suburbs = $this->general->getSub();

        $this->twiggy->set('static',array(
            'suburb' => $suburbs,
        ));

        $sms = $this->security_model->smsSettings();
        $this->twiggy->set('sms', $sms['sms_verification']);

        $this->twiggy->set('page', array(
            'title'  => 'Edit Profile',
            'role'   => 'page',
            'theme'  => 'a',
            'backButton'=> true,
        ));

        $this->twiggy->set('page', array(
            'title'  => 'Edit Profile',
            'role'   => 'page',
            'theme'  => 'a',
            'id'     => 'page-edit',
            'backButton'=> true,
        ));

        $this->twiggy->display('account/edit');
    }

    /**
     * Insert / Update  user
     */
    public function save(){

        $user = $this->input->post();


        unset($user['paypal']);
        if(isset($user['conf_password']) && isset($user['password']))
        {
            unset($user['conf_password']);
            $user['password'] = md5($user['password']);
        }

        $userLogged = $this->session->userdata('logged');

        $this->load->model('security_model');

        if($userLogged['userid']){

            $newUser = $this->security_model->save($user, $userLogged['userid']);

        } else {

            $this->load->helper('cookie');
            $points = get_cookie('referal');
            delete_cookie('referal');

            if($points){
                $user['order_points'] = $points;
            }

            $newUser = $this->security_model->save($user, 'no_id');
        }

        $this->session->set_userdata('logged', $newUser);


    }

    public function checkUniqueEmail(){

        $email = urldecode($this->input->get('email'));

        $this->load->library('session');
        $logged = $this->session->userdata('logged');
        if($logged){
            $email_user = $logged['email'];
            if(!empty($email_user) && $email == $email_user){
                echo 'true';
            }
        } else {
            $is_unique = $this->security_model->checkUniqueEmail($email);
            if($is_unique){
                echo 'false';
            } else {
                echo 'true';
            }
        }
    }

    public function checkUniqueMobile(){

        $mobile = urldecode($this->input->get('mobile'));

        $this->load->library('session');
        $logged = $this->session->userdata('logged');
        if($logged){
            $mobile_user = $logged['mobile'];
            if(!empty($mobile_user) && $mobile == $mobile_user){
                echo 'true';
            }
        } else {
            $is_unique = $this->security_model->checkUniqueMobile($mobile);
            if($is_unique){
                echo 'true';
            } else {
                echo 'false';
            }
        }
    }

    public function logout($payment = null){

        $this->session->unset_userdata('logged');
        $this->session->unset_userdata('checkout');
        $this->session->unset_userdata('low_order');

        if($payment){
            redirect(base_url().'payment');
        } else {

            redirect(base_url());
        }

    }

    /********  Facebook  *******/
    public function facebook_channel() {

        $cache_expire = 60*60*24*365;
        header("Pragma: public");
        header("Cache-Control: max-age=".$cache_expire);
        header('Expires: ' . gmdate('D, d M Y H:i:s', time()+$cache_expire) . ' GMT');
        echo '<script src="' .base_url().'assets/js/account/all.js"></script>';

    }

    public function facebook_login() {
        $fb = $this->input->post();
        // TODO: add ajax verification
       
        $email = md5($fb['name']);
        if( isset($fb['email']) )
        {
            if( !empty($fb['email']) )
            {
                $email = $fb['email'];
            }
        }       
        $user = $this->db->get_where('users', array('email' => $email));
        if ($user->num_rows() > 0) {
            $user = $user->row_array();
            $this->session->set_userdata('logged', $user);
        } else {
            $insert = array();
            $insert['address'] = '';
            if( isset($fb['location']) )
            {
                if( !empty($fb['first_name']) )
                {
                    $insert['address'] = $fb['location']['name'];                   
                }
            }

            $p = time();
            $insert['first_name'] = '';
            if( isset($fb['first_name']) )
            {
                if( !empty($fb['first_name']) )
                {
                    $insert['first_name'] = $fb['first_name'];                   
                }
            }
            $insert['last_name'] = '';
            if( isset($fb['first_name']) )
            {
                if( !empty($fb['last_name']) )
                {
                    $insert['last_name'] = $fb['last_name'];
                }
            }

            $insert['email'] = $email;
            $insert['password'] = md5($p);
            $insert['base_password'] = base64_encode($p);
            $insert['usertypeid'] = '2';
            $insert['status'] = 'active';
            $insert['delete'] = 0;
            $this->load->helper('cookie');
            $points = get_cookie('referal');
            delete_cookie('referal');
            if( !empty($points) )
            {
                $insert['order_points'] = $points;
            }
            $this->db->insert('users', $insert);
            $insert['userid'] = $this->db->insert_id();
            $this->session->set_userdata('logged', $insert);

        }

    }

    /**
     * Referal Link
     * @param $mobileNumber
     */
    public function referalLink($mobileNumber){

        $logged = $this->session->userdata('logged');
        if($logged){
            redirect(base_url());
        }

        $user = $this->security_model->checkMobileNumber($mobileNumber);

        if($user){

        } else {
            redirect(base_url().'40awdad4');
        }
    }
}