<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');


if ( !function_exists('prepareProfilePage') )
{
    /**
     * Prepare profile form
     * @param object $template - Twigg template
     * @return object
     */
    function prepareProfilePage( $template )
    {
        $obj =& get_instance();
        $obj->load->library('session');
        $obj->load->model('general');
        $logged = $obj->session->userdata('logged');

        $obj->load->model('security_model');
        $sms = $obj->security_model->smsSettings();
        $template->set('sms', $sms['sms_verification']);

        $suburb = 0;
        $suburbs = $obj->general->getSub();
        $template->set('static',array(
            'suburb' => $suburbs,
        ));
        if(!empty($logged)){
            $template->set('logged', $logged);
            if( isset($logged['suburb']) )
            {
                $suburb = $logged['suburb'];
            }
        } else {
            $template->set('logged', 0);
            $text = $obj->general->getRegisterText();
            $template->set('regText', $text);
        }
        return $template;
    } // prepareProfilePage
}

if ( !function_exists('saveProfile') )
{
    function saveProfile( $user )
    {
        $temp = time();
        unset($user['mobile_code']);
        $obj =& get_instance();
        if(
                isset($user['conf_password']) 
                && isset($user['password']) 
                && ( $user['conf_password'] === $user['password'] )
          )
        {
            unset($user['conf_password']);
            $user['password'] = md5($user['password']);
        }
        if( $obj->session->userdata('backToLogin') )
        {
            $user['password'] = md5($temp);
        }
        if( !isset($user['base_password']) && isset($user['password']) )
        {
            $user['base_password'] = base64_encode($user['password']);
        }
        if( !isset($user['usertypeid']) )
        {
            $user['usertypeid'] = '2';
        }
        if( !isset($user['status']) )
        {
            $user['status'] = 'active';
        }
        if( !isset($user['delete']) )
        {
            $user['delete'] = 0;
        }
        if( !isset($user['signup_date']) )
        {
            $user['signup_date'] = date('Y-m-d H:i:s', time());
        }
        $userLogged = $obj->session->userdata('logged');
        $obj->load->model('security_model');
        if( $userLogged['userid'])
        {
            $newUser = $obj->security_model->save( $user, $userLogged['userid'] );
        } else {
            $obj->load->helper('cookie');
            $points = get_cookie('referal');
            delete_cookie('referal');
            if($points)
            {
                $user['order_points'] = $points;
            }
            $newUser = $obj->security_model->save($user, 'no_id');
        }
        $obj->session->set_userdata('logged', $newUser);
        return true;
    } // saveProfile
}