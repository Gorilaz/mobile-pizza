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
            ->set('base_url',$this->config->base_url())
            ->set('current_url',uri_string())
            ->set('author', 'WMD Solution Romania');





        /**
         * Set Site Settings - one time only
         */
        if(!$this->session->userdata('siteSetting')) {
            $this->session->set_userdata(array(
                'siteSetting' => $this->formatSiteSettingsForSession($this->db->get('sitesetting')->result())
            ));
        }

        /**
         * Set Loyality_program
         */
        $loyalityProgram = $this->db->select('value')->where('type', 'loyatly_program')->get('sitesetting')->row();
        $this->twiggy->set('loyalityProgram', $loyalityProgram->value);


        /* Set twig variable */
        $this->twiggy->set('settings', $this->session->userdata('siteSetting'));


        /**
         * Set if store is open or not - Refresh it once at 5min (300s)
         */
        $storeOpen = $this->session->userdata('storeOpen');

        if(!$storeOpen OR ($storeOpen['checkTime']+300 < time())) {
            $this->load->model('general');

            $this->session->set_userdata(array(
                'storeOpen' => array(
                    'isOpen'    => $this->general->isOpenNow(),
                    'checkTime' => time(),
                )
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