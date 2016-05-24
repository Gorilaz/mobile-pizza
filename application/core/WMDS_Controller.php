<?php

/**
 * Created by PhpStorm.
 * User: GabrielCol
 * Date: 11/9/13
 * Time: 3:35 PM
 */

class WMDS_Controller extends CI_Controller {
    function __construct() {
        parent::__construct();

        /** Set Twig Global Variables */
        $this->twiggy
            ->set('base_url', $this->config->base_url())
            ->set('current_url', current_url())
            ->set('author', 'WMD Solution Romania');

        if( $this->config->item('sms_service') === 'telerivet' )
        {
            $telerivet_path = realpath(FCPATH . APPPATH . 'vendor/telerivet-php-client-master/telerivet.php');

            if( $telerivet_path )
            {
                include_once $telerivet_path;

                $this->load->model('security_model');

                $sms_settings = $this->security_model->smsSettings();

                $this->Telerivet_API = new Telerivet_API($sms_settings['telerivet_api_key']);

                $this->Telerivet_Project = $this->Telerivet_API->initProjectById($sms_settings['telerivet_project_id']);
            }
            else
            {
                $this->config->set_item('sms_service', 'email2sms');
            }
        }

        /**
         * Set Site Settings - one time only
         */
        if( !$this->session->userdata('siteSetting') )
        {
            $this->session->set_userdata(array(
                'siteSetting' => $this->formatSiteSettingsForSession($this->db->get('sitesetting')->result())
            ));
        }

        /**
         * Set Loyality_program
         */
        $loyalityProgram = $this->db->select('value')->where('type', 'loyatly_program')->get('sitesetting')->row();

        $this->twiggy->set('loyalityProgram', $loyalityProgram->value);

        /**
         * Set twig variable
         */
        $this->twiggy->set('settings', $this->session->userdata('siteSetting'));

        $this->load->model('general');

        $schedule = $this->general->getScheduleForSchema();

        $this->twiggy->set('schedule_for_schema', $schedule);

        /**
         * Set if store is open or not - Refresh it once at 5min (300s)
         */
        $storeOpen = $this->session->userdata('storeOpen');

        $sitemode = $this->db->select('value')->where('type', 'SITEMODE')->get('sitesetting')->row()->value;

        $this->twiggy->set('sitemode_online', $sitemode === 'online' ? true : false);

        if( $sitemode !== 'online' )
        {
            $offlinecontent = $this->db->select('value')->where('type', 'offlinecontent')->get('sitesetting')->row()->value;

            $this->twiggy->set('offlinecontent', $offlinecontent);
        }

        if( !$storeOpen || ( ( $storeOpen['checkTime'] + 300 ) < time() ) )
        {
            $isOpenNow = $this->general->isOpenNow();

            $storeOpen = array(
                'isOpen' => $isOpenNow, 
                'checkTime' => time()
            );

            if( empty($isOpenNow) )
            {
                $weWillOpen = $this->general->weWillOpen();

                $storeOpen['we_will_open'] = strtotime($weWillOpen);
            }

            $this->session->set_userdata(array(
                'storeOpen' => $storeOpen
            ));
        }

        /* Set twig variable */
        $this->twiggy->set('isopen', $this->session->userdata('storeOpen'));
        $this->twiggy->set('sessionid', $this->session->userdata('session_id'));

        /**
         * Whatever to include the header part or not.
         * Default set to YES, replaced in controllers for Home and other pages that require
         */
        $this->twiggy->set('internalPage', true);

        /**
         * Default Page Properties
         */
        $this->twiggy->set('page', array(
            'role'          => 'page',
            'title'         => '',
            'backButton'    => false,
        ));

        $user = $this->session->userdata('logged');

        if($user){
            $points = 0;
            if( isset($user['order_points']) && !empty($user['order_points']) )
            {
                $points = $user['order_points'];
            }
            $this->twiggy->set('logged', 1 );
            $this->twiggy->set('userPoints', $points );
        } else {
            $this->twiggy->set('logged', 0 );
            $this->twiggy->set('userPoints', 0 );
        }
    }

    protected function formatSiteSettingsForSession($data) {
        $return = new \stdClass();
        if($data) {
            foreach($data as $item) {
                $type   = $item->type;
                $value  = $item->value;
                $return->$type = $value;
            }
        }
        return $return;
    }
}