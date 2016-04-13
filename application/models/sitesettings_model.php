<?php

class Sitesettings_model extends CI_Model {

    function __construct() {
        parent::__construct();
        $this->load->database();
    }

    function getSiteSettingsDetails() {
        $this->db->order_by('id', 'asc');
        $query = $this->db->get('sitesetting')->result();
        foreach ($query as $ssettings) {
            $arrsetting[] = $ssettings->value;
        }
        return $arrsetting;
    }

//getUserDetails

    function getMangeTextDetails() {
        $this->db->order_by('text_id', 'asc');
        $query = $this->db->get('tbl_manage_text')->result();
        foreach ($query as $ssettings) {
            $arrsetting[] = $ssettings->value;
        }
        return $arrsetting;
    }

//getUserDetails

    public function getTextByType($type) {
        if (!is_string($type)) {
            return;
        }

        $query = $this->db->get_where('tbl_manage_text', array('type' => $type));
        $result = $query->result();

        return $result[0]->value;
    }

    function siteSettingsUpdate($data, $editid) {

        if (is_array($data)) {
            $this->db->query($this->db->update_string("sitesetting", $data, "id = " . $editid . ""));
            return 1;
        }
    }

//end publisherhomepage_operations

    public function getsiteStatus() {
        $query = $admin_email = $this->db->query("SELECT * from sitesetting where type='sitemode'");
        $row = array();
        return $row = $query->row();
    }

    public function getsiteofflinecontent() {
        $query = $admin_email = $this->db->query("SELECT * from sitesetting where type='offlinecontent'");
        $row = array();
//         print_r($row);exit;
        return $row = $query->row();
    }
    
    public function getCheckoutUrl() {
        $this->db->select('value');
        $query = $this->db->get_where('sitesetting', array('type' => 'pay_url'));
        return array_pop($query->result());
    }

    function rest_setUpdate($data, $editid) {
        if (is_array($data)) {
            $this->db->query($this->db->update_string("tbl_rest_setting", $data, "id = " . $editid . ""));
            return 1;
        }
    }

    function sms_setUpdate($data, $editid) {
        if (is_array($data)) {

            $this->db->query($this->db->update_string("tbl_sms_setting", $data, "id = " . $editid . ""));
            return 1;
        }
    }

    function getrest_SetDetails() {
        $query = $this->db->get('tbl_rest_setting')->result();
        foreach ($query as $rsettings) {
            $arrsetting[] = $rsettings->value;
        }
        return $arrsetting;
    }

//getUserDetails

    function getsms_SetDetails() {
        $this->db->order_by('id', 'asc');
        $query = $this->db->get('tbl_sms_setting')->result();
        foreach ($query as $rsettings) {
            $arrsetting[] = $rsettings->value;
        }
        return $arrsetting;
    }

//getUserDetails

    function updateOrderNumber() {
        $this->db->set(array('order_number' => $this->input->post('order_number'), 'increment' => $this->input->post('increment_by')));
        $this->db->update('tbl_order_number');
    }

    function getOrderNumber() {
        $res = $this->db->select('order_number,increment')->get('tbl_order_number')->row();
        if (!empty($res)) {
            return $res;
        }
        return false;
    }

}

//end class