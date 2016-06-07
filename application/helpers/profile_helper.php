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
    function saveProfile($user)
    {
        $temp = time();

        $obj =& get_instance();

        if( isset($user['password']) && 
            isset($user['conf_password']) && 
            ( $user['password'] === $user['conf_password'] ) )
        {
            unset($user['conf_password']);

            $user['password'] = md5($user['password']);
        }

        $obj->load->library('session');

        if( $obj->session->userdata('backToLogin') )
        {
            $user['password'] = md5($temp);
        }

        if( isset($user['password']) && 
            !isset($user['base_password']) )
        {
            $user['base_password'] = base64_encode($user['password']);
        }

        if( empty($user['password']) )
        {
            unset($user['password']);
            unset($user['base_password']);
        }

        if( empty($user['usertypeid']) )
        {
            $user['usertypeid'] = '2';
        }

        if( empty($user['status']) )
        {
            $user['status'] = 'active';
        }

        if( empty($user['delete']) )
        {
            $user['delete'] = 0;
        }

        if( empty($user['signup_date']) )
        {
            $user['signup_date'] = date('Y-m-d H:i:s', time());
        }

        $userLogged = $obj->session->userdata('logged');

        $mobileToCheck = false;

        if( empty($userLogged['mobile']) )
        {
            if( empty($user['mobile']) === false )
            {
                $mobileToCheck = true;
            }
        }
        else
        {
            if( $user['mobile'] !== (string) $userLogged['mobile'] )
            {
                $mobileToCheck = true;
            }
        }

        if( $mobileToCheck )
        {
            $sms_code = (string) $obj->session->userdata('sms_code');

            if( empty($user['mobile_code']) )
            {
                return array('status' => 'error', 'message' => 'Verification code should not be empty.');
            }

            if( $user['mobile_code'] !== $sms_code )
            {
                return array('status' => 'error', 'message' => 'Verification code not valid.');
            }
        }

        unset($user['mobile_code']);

        $obj->load->model('security_model');

        if( empty($userLogged['userid']) )
        {
            $obj->load->helper('cookie');

            $points = get_cookie('referal');

            delete_cookie('referal');

            if( $points )
            {
                $user['order_points'] = $points;
            }

            $newUser = $obj->security_model->save($user, 'no_id');
        }
        else
        {
            $newUser = $obj->security_model->save($user, $userLogged['userid']);
        }

        $obj->session->set_userdata('logged', $newUser);

        return array('status' => 'success');
    } // saveProfile
}