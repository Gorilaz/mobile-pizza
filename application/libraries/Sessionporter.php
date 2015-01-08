<?php
/**
 * Created by PhpStorm.
 * User: GabrielCol
 * Date: 12/5/13
 * Time: 6:48 PM
 */

class Sessionporter {
    private $CI;

    /**
     * Constructor
     */

    public function __construct()
    {
        $this->CI =& get_instance();
    }

    public function portSession($session_id) {
        $this->CI->load->database();

        $query      = $this->CI->db->get_where('mobile_sessions', array('session_id' => $session_id));
        $session    = $query->row();

        $this->CI->session->set_userdata(unserialize($session->user_data));

        // Run the update query
        //$this->CI->db->where('session_id', $this->userdata['session_id']);
        //$this->CI->db->update($this->sess_table_name, array('last_activity' => $this->userdata['last_activity'], 'user_data' => $custom_userdata));

    }
} 