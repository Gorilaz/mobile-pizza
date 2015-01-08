<?php

class Security_model extends CI_Model{

    /**
     * Check if is user
     * @param $user
     * @return string
     */
    public function login($user){

        $find_user = $this->db->from('users')
            ->where('password', md5($user['pass']))
            ->where('email', $user['user'])
            ->get();

        if ($find_user->num_rows() > 0) {
            return $find_user->row_array();
        } else {
            return 'no_user';
        }
    }

    /**
     * Save user
     * @param $user
     * @param null $loggedId
     */
    public function save($user, $loggedId = null){

        if($loggedId != 'no_id'){
            $this->db->where('userid', $loggedId)->update('users', $user);

            $new_user = $this->db->where('userid', $loggedId)->get('users')->row_array();
        } else {
            $this->db->insert('users', $user);
            $id = $this->db->insert_id();
            $new_user = $this->db->where('userid', $id)->get('users')->row_array();
        }

        return $new_user;
    }

    /**
     * Check if email is unique
     * @param $email
     */
    public function checkUniqueEmail($email){
        $is_unique = $this->db->where('email', $email)->count_all_results('users');

        if ($is_unique > 0){
            return true;
        } else {
            return false;
        }
    }

    /**
     * Check if mobile is unique
     * @param $mobile
     */
    public function checkUniqueMobile($mobile){

        $is_unique = $this->db->where('mobile', $mobile)->count_all_results('users');

        if ($is_unique > 0){
            return false;
        } else {
            return true;
        }
    }

    /**
     * Check if email is valid
     * @param $email
     * @param $code
     * @return bool
     */
    public function checkValidEmail($email, $code){
        $count = $this->db->where('email', $email)->count_all_results('users');
        if ($count == 1){
            $this->db->where('email', $email)->update('users', array('verify_code' => $code));
            return true;
        } else {
            return false;
        }
    }

    /**
     * Update Password
     * @param $code
     * @param $password
     */
    public function updatePassword($code, $password){

        $this->db->where('verify_code', $code)->update('users', array('password' => md5($password), 'verify_code' => ''));

    }


    /**
     * Sms Settings
     * @return mixed
     */
    public function smsSettings(){
        $this->db->order_by('id','asc');
        $query = $this->db->get('tbl_sms_setting')->result();
        foreach($query as $sms_settings)
        {
            $arrsetting[$sms_settings->type] =$sms_settings->value;
        }
        return $arrsetting;
    }


    /**
     * get email for sms settings
     * @param $id
     * @return mixed
     */
    public function getEmailById($id){

        $email = $this->db->where('emailid', $id)->get('mast_emails')->row();

    return $email;
    }

    /**
     * update user mobile
     * @param $mobile
     * @param $email
     */
    public function changeMobile($mobile, $email){
        $this->db->where('email', $email)->update('users', array('mobile' => $mobile));

        return $this->db->where('email', $email)->get('users')->row_array();
    }

    /**
     * Get User fields
     * @param $userId
     */
    public function getUser($userId){

        $user = $this->db->where('userid', $userId)->get('users')->row_array();
        return $user;
    }

    /**
     * Check if mobile number exists
     * @param $mobileNumber
     */
    public function checkMobileNumber($mobileNumber){

        $user = $this->db->select('first_name, last_name')->where('mobile', $mobileNumber)->get('users')->row_array();

        if($user){
            return $user;
        } else {
            return false;
        }

    }

    /**
     * Get real id from order for sms confirmation
     * @param $order_id
     * @return mixed
     */
    public function getRealId($order_id){
        $real_id = $this->db->select('real_id')->where('order_id', $order_id)->get('mast_order')->row();

        return $real_id->real_id;
    }
}