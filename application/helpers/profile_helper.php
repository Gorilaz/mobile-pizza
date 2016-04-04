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
    function saveProfile( $template )
    {
        die('saveProfile');
        $obj =& get_instance();
    } // saveProfile
}