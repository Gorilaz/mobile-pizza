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
    
    function googleplus_login()
    {
        $backToLogin = '';
        $flag = false;
        $google = $this->input->post();
        $email = '';
        $error = '';
        if( isset($google['email']) )
        {
            if( !empty($google['email']) )
            {
                $email = $google['email'];
            }
        }
        if( !empty($email) )
        {
            $this->security_model->clearEmptyUsers();
            $user = $this->security_model->getUserByEmail($email);
            if( $user )
            {
                if( 
                    empty($user['address'])
                    || empty($user['suburb']) 
                    || empty($user['mobile'])
                  )
                {
                    $backToLogin = 'requare';
                }
                $this->session->set_userdata('logged', $user);
                $flag = true;
            }
        }
        if( !$flag )
        {
            $firstPointLogin = $this->session->userdata('firstPointLogin');
            if( 'login' != $firstPointLogin )
            {
                //$backToLogin = 'requare';
                $insert = array();
                $insert['email'] = $email;
                $insert['address'] = '';
                $insert['first_name'] = '';
                if( isset($google['first_name']) )
                {
                    if( !empty($google['first_name']) )
                    {
                        $insert['first_name'] = $google['first_name'];
                    }
                }
                $insert['last_name'] = '';
                if( isset($google['last_name']) )
                {
                    if( !empty($google['last_name']) )
                    {
                        $insert['last_name'] = $google['last_name'];
                    }
                }
                $this->session->set_userdata('backToLogin', $backToLogin);
                $this->load->helper('profile');
                saveProfile( $insert );
            } else {
                $error = 'User not found in the database. Make your first order to get registered.';
            }
        }      
        echo json_encode(array(
            'fields' => $backToLogin,
            'error' => $error
        ));
    }


    /**
     * Generate Login page
     */
    public function login_page()
    {
        $userLogged = $this->session->userdata('logged');

        if( empty($userLogged['userid']) )
        {
            $firstPointLogin = $this->session->userdata('firstPointLogin');

            if( empty($firstPointLogin) )
            {
                $this->session->set_userdata('firstPointLogin', 'login');
            }

            $this->twiggy->set('page', array(
                'title' => 'Login', 
                'role' => 'page', 
                'theme' => 'a', 
                'id' => 'security-login', 
                'backButton' => true
            ));

            $this->twiggy->set('pageposition', 'login');

            $out = prepareProfilePage($this->twiggy);

            $out->display('account/login');
        }
        else
        {
            redirect(base_url() . 'my-account');
        }
    } // login_page

    /**
     * Verify if logged (ajax)
     */
    public function login()
    {
        $flag = 'false';
        $user = $this->input->post();
        if( $user['user'] && $user['pass'] )
        {
            $is_login = $this->security_model->login($user);
            if( $is_login != 'no_user' )
            {
                $this->session->set_userdata('logged', $is_login);
                $flag = 'true';
            }
        } else {
            $flag = 'required fields';
        }
        echo json_encode(array(
            'login' => $flag
        ));
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
        $mobile = $this->input->post('mobile');

        $this->load->model('security_model');

        $sms = $this->security_model->smsSettings();

        $code = md5(uniqid(rand(), true));

        if( !!$email )
        {
            $user = $this->security_model->getUserByEmail($email);

            $valid = $this->security_model->checkValidEmail($email, $code);
        }
        else if( !!$mobile )
        {
            $user = $this->security_model->getUserByMobile($mobile);

            $valid = $this->security_model->checkValidMobile($mobile, $code);
        }

        if( $valid )
        {
            if( !!$email )
            {
                $this->load->library('email');

                $this->email->from('office@pizza.com', 'Pizza');
                $this->email->to($email);

                $this->email->subject('Recovery Password');
                $this->email->message('Hello ' . $email . '! To Recovery Your Password - please visit this <a href="' . base_url() . '/change-password/' . $code . '">link</a>');

                $this->email->send();
            }
            else if( !!$mobile )
            {
                if( $this->config->item('sms_service') === 'telerivet' )
                {
                    $sendMessage = $this->Telerivet_Project->sendMessage(array(
                        'content' => 'Hello ' . $mobile . '! To Recovery Your Password - please visit: ' . base_url() . '/change-password/' . $code, 
                        'to_number' => $user['mobile']
                    ));
                }
                else
                {
                    $this->load->library('email');

                    $this->email->from('office@pizza.com', 'Pizza');
                    $this->email->to($mobile . '@' . $sms['domain_name']);

                    $this->email->subject('Recovery Password');
                    $this->email->message('Hello ' . $mobile . '! To Recovery Your Password - please visit: ' . base_url() . '/change-password/' . $code);

                    $this->email->send();
                }
            }

            echo json_encode('valid');
        }
        else
        {
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

    public function savePassword()
    {
        $code = $this->input->post('code');
        $password = $this->input->post('pass');
        $this->security_model->updatePassword($code, $password);
    }

    /************************  Edit / Register  *******************/

    /**
     * Generate edit profile page
     */
    public function edit(){
        $backToLogin = $this->session->userdata('backToLogin');
        $parameters = array(
            'title'  => 'Edit Profile',
            'role'   => 'page',
            'theme'  => 'a',
            'id'     => 'page-edit',
            'backButton' => true,
        );
        if( !empty($backToLogin) && $backToLogin == 'requare' )
        {
            $parameters['backButton'] = false;
            $this->twiggy->set('backToLogin', $backToLogin);
        }
        $this->twiggy->set('page', $parameters);
        $this->twiggy->set('saveBtn', true);
        $out = prepareProfilePage($this->twiggy);
        $out->display('account/edit');
    } // edit

    /**
     * Insert / Update  user
     */
    public function save()
    {
        $user = $this->input->post();

        $this->load->helper('profile');

        unset($user['paypal']);

        if( empty($user['address']) || 
            empty($user['suburb']) || 
            empty($user['mobile']) )
        {
            echo 'error';

            return;
        }

        $saveProfile = saveProfile($user);

        if( $saveProfile['status'] !== 'success' )
        {
            echo $saveProfile['status'] . ': ' . $saveProfile['message'];

            return;
        }

        $this->session->unset_userdata('backToLogin');

        $firstPointLogin = $this->session->userdata('firstPointLogin');

        if( empty($firstPointLogin) )
        {
            echo 'order';

            return;
        }

        $this->session->unset_userdata('firstPointLogin');

        echo $firstPointLogin;
    } // save

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

    /**
     * Ajax method for check exists Mobile
     */
    public function checkUniqueMobile()
    {
        $flag = 'false';
        $mobile = urldecode($this->input->get('mobile'));
        $this->load->library('session');
        $logged = $this->session->userdata('logged');
        if( $logged )
        {
            if( 
                !empty($logged['mobile'])
                && $mobile == $logged['mobile']
            )
            {
                $flag = 'true';
            }
        }
        if( 'false' == $flag )
        {
            if( $this->security_model->checkUniqueMobile($mobile) )
            {
                $flag = 'true';
            }
        }
        echo $flag;
    }

    public function logout($payment = null) {
        $this->session->unset_userdata('logged');
        $this->session->unset_userdata('checkout');
        $this->session->unset_userdata('low_order');
        $this->session->unset_userdata('backToLogin');
        $this->session->unset_userdata('firstPointLogin');

        if( $payment )
        {
            redirect(base_url() . 'payment');
        }
        else
        {
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

    public function facebook_login()
    {
        $backToLogin = '';

        $flag = false;

        $fb = $this->input->post();

        $fbId = '';
        $email = '';

        $error = '';

        if( empty($fb['id']) === false )
        {
            $fbId = $fb['id'];
        }

        if( empty($fb['email']) === false )
        {
            $email = $fb['email'];
        }

        if( empty($email) === false )
        {
            $this->security_model->clearEmptyUsers();

            $user = $this->security_model->getUserByEmail($email);

            if( empty($user) === false )
            {
                if( empty($user['address']) || 
                    empty($user['suburb']) || 
                    empty($user['mobile']) )
                {
                    $backToLogin = 'requare';
                }

                $this->session->set_userdata('logged', $user);

                $flag = true;
            }
        }

        if( empty($flag) )
        {
            $firstPointLogin = $this->session->userdata('firstPointLogin');

            if( 'login' === $firstPointLogin )
            {
                $backToLogin = 'requare';

                $insert = array();

                $insert['email'] = $email;
                $insert['address'] = '';
                $insert['facebook_id'] = $fbId;
                $insert['first_name'] = empty($fb['first_name']) ? '' : $fb['first_name'];
                $insert['last_name'] = empty($fb['last_name']) ? '' : $fb['last_name'];

                $this->session->set_userdata('backToLogin', $backToLogin);

                $this->load->helper('profile');

                saveProfile($insert);
            }
            else
            {
                $error = 'User not found in the database. Make your first order to get registered.';
            }
        }

        echo json_encode(array(
            'fields' => $backToLogin, 
            'error' => $error
        ));
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