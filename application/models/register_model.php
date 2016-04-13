<?php

class Register_Model extends CI_Model {

    public function __construct() {
        parent::__construct();
        $this->load->database();
    }

//end of construct

    /*
      This function inserts registration detail into the table
     */

    public function doRegistration() {
        //VV need to strip address field. caused pdf generation to fail. all others are alhabeta only fields enforced by js. 
        //orig$regData = array('address' => $this->input->post('street', TRUE),
        //VV $regData = array('address' => filter_var($this->input->post('street', TRUE), FILTER_SANITIZE_STRING), //SOMEHOW WORKS BUT NOT 100%
        
            $regData = array('address' => strip_tags($this->input->post('street', TRUE),"/"),  //OK BUT NOT PERFECT <street> becomes empty also everything behing "<" dissapears 
            'state' => $this->input->post('state', TRUE),
            'suburb' => $this->input->post('suburb', TRUE),
            'first_name' => $this->input->post('firstname', TRUE),
            'last_name' => $this->input->post('lastname', TRUE),
            'email' => $this->input->post('email', TRUE),
            // 'username' => $this->input->post('username',TRUE),
            'password' => md5($this->input->post('password', TRUE)),
            'base_password' => base64_encode($this->input->post('password', TRUE)),
            'usertypeid' => 2,
            'company_name' => ($this->input->post('company_name', TRUE)) ? $this->input->post('company_name', TRUE) : '',
            'mobile' => $this->input->post('mobile', TRUE),
            'verify_code' => $this->input->post('verify_code', TRUE),
            //   'comment' => $this->input->post('comment',TRUE),
            'signup_date' => date("Y-m-d H:i:s")
        );


        // insert new record
        if (is_array($regData)) {
            if ($this->phpsession->get('tmusrLgn')) { //update
                $user_id = $this->phpsession->get('tmUserId');
                $regData = array_filter($regData);
                if ($regData['password'] == md5(false)) {
                    unset($regData['password']);
                    unset($regData['base_password']);
                }
                
                $this->db->query($this->db->update_string("users", $regData, array('userid' => $user_id)));
                return $user_id;
            } else {//add new user
                $this->db->query($this->db->insert_string("users", $regData));
                $user_id = $this->db->insert_id();
                /* Use for refer friend */
                if ($user_id > 0 && $this->phpsession->get('ref_mobile_number') != '') {
                    $ref_mobile_number = $this->phpsession->get('ref_mobile_number');
                    $this->_referFriend($ref_mobile_number, $user_id);
                }
                return $user_id;
            }
        }
    }

//end of doRegistration

    public function Refer_FriendRegistration() {

        $ref_regData = array(
            'address' => $this->input->post('address', TRUE),
            'state' => $this->input->post('state', TRUE),
            'suburb' => $this->input->post('suburb', TRUE),
            'first_name' => $this->input->post('firstname', TRUE),
            'last_name' => $this->input->post('lastname', TRUE),
            'email' => $this->input->post('email', TRUE),
            'password' => md5($this->input->post('password', TRUE)),
            'base_password' => base64_encode($this->input->post('password', TRUE)),
            'usertypeid' => 2,
            'company_name' => ($this->input->post('company_name', TRUE)) ? $this->input->post('company_name', TRUE) : '',
            'mobile' => $this->input->post('refer_mobile', TRUE),
            'verify_code' => $this->input->post('verify_code', TRUE),
            'signup_date' => date("Y-m-d H:i:s")
        );

        if (is_array($ref_regData)) {
            $this->db->query($this->db->insert_string("users", $ref_regData));
            $user_id = $this->db->insert_id();

            if ($user_id > 0 && $this->phpsession->get('ref_mobile_number') != '') {
                $ref_mobile_number = $this->phpsession->get('ref_mobile_number');
                $this->_referFriend($ref_mobile_number, $user_id);                 // refer friend
            }
            return $user_id;
        }
    }

//end of doRegistration

    public function selectmobileDetails() {
        $mobile_number = $this->phpsession->get('ref_mobile_number');

        $row = $this->db->select('mobile')->get_where('users', array('mobile' => $mobile_number))->num_rows();

        return ($row > 0) ? "false" : "true";
    }

    public function get_ref_person_greeting() {
        $mobile_number = $this->phpsession->get('ref_mobile_number');
        if (!empty($mobile_number) && strlen($mobile_number) == 10) {
            $row = $this->db->select('userid')->get_where('users', array('mobile' => $mobile_number, 'usertypeid !=' => 1))->row();
            if (!empty($row)) {
                $referrer_id = $row->userid;
            }
            if (!empty($referrer_id)) {
                $reffereInfo = $this->getCustomerDetail($referrer_id);

                $rfdrow = $this->db->select('value')->get_where('tbl_ref_friend', array('type' => 'ref_person_greeting'))->row();

                if (!empty($rfdrow)) {
                    $refered_person_mail_content = $rfdrow->value;
                }

                $rfdrow_points = $this->db->select('value')->get_where('tbl_ref_friend', array('type' => 'referred_point'))->row();
                if (!empty($rfdrow_points)) {
                    $refered_person_points = $rfdrow_points->value;
                }

                $message = str_replace("[[first_name]]", '', $refered_person_mail_content);
                $message = str_replace("[[last_name]]", '', $message);
                $message = str_replace("[[points]]", $refered_person_points, $message);
                $message = str_replace("[[referring_person]]", $reffereInfo->first_name . ' ' . $reffereInfo->last_name, $message);
                $show_message = str_replace('\n', '', htmlspecialchars_decode($message));
// print_r($show_message);die('halted here');
                return ($show_message);
            }
        }
    }

    /**   refer friend old code starts * */
//    private function _referFriend($ref_mobile_number,$user_id)
//    {
//       /* getting the refferer peson userid using his mobile number*/
//       $row = $this->db->select('userid')->get_where('users',array('mobile'=>$ref_mobile_number,'usertypeid !='=>1))->row();
//       if(!empty($row))
//       {
//          $referrer_id= $row->userid;
//       }
// 
//       /* getting the refferer point and reffered point */
//       if(!empty($user_id) && !empty($referrer_id)) 
//       {
//          $rfdrow = $this->db->select('value')->get_where('tbl_ref_friend',array('type'=>'referred_point'))->row();   
//          $rfrrow = $this->db->select('value')->get_where('tbl_ref_friend',array('type'=>'referring_point'))->row();  
//          if(!empty($rfdrow) && ! empty($rfrrow))   
//          {
//             $referred_point= $rfdrow->value;
//             $referring_point= $rfrrow->value;
//             if($this->phpsession->get('http_ref'))
//             {
//                $http_referrer = $this->phpsession->get('http_ref');
//             }  
//             else
//             {
//                $http_referrer = '';
//             }
//             $refer_table_data  = array( 'referrer_id' => $referrer_id,
//                               'refered_id' => $user_id,
//                               'refered_point' =>$referred_point,
//                               'referrer_point' => $referring_point,
//                               'refferrer_url'=> $http_referrer,
//                               'refer_date'=> date("Y-m-d H:i:s")  
//                               );
// 
//             /* Inserting data to refer table*/
//             if(is_array($refer_table_data))
//             {
//                $this->db->query($this->db->insert_string("refer_friend",$refer_table_data));
//                $refer_id=$this->db->insert_id();      
// 
//                if($refer_id > 0 )
//                {
//                   /* after inserting data sending mail to both person*/
//                   $this->_refer_sendmail($refer_id,$referrer_id,$user_id);
//                   /*Clearing mobile number from session*/
//                   $this->phpsession->clear('ref_mobile_number');
//                   $this->phpsession->clear('http_ref');
//                }
//             }
//          }           
//       }
//       return true;
//    }// end _referFriend
    /**   refer friend old code ends * */

    /**   refer friend new code starts here * */
    private function _referFriend($ref_mobile_number, $user_id) {
        /* getting the refferer peson userid using his mobile number */
        $row = $this->db->select('userid')->get_where('users', array('mobile' => $ref_mobile_number, 'usertypeid !=' => 1))->row();
        if (!empty($row)) {
            $referrer_id = $row->userid;
        }

        /* getting the refferer point and reffered point */
        if (!empty($user_id) && !empty($referrer_id)) {
            $rfdrow = $this->db->select('value')->get_where('tbl_ref_friend', array('type' => 'referred_point'))->row(); //referred_point-usr referred by others
            $rfrrow = $this->db->select('value')->get_where('tbl_ref_friend', array('type' => 'referring_point'))->row(); //referring_point-usr refering 2 others
            if (!empty($rfdrow) && !empty($rfrrow)) {
                $referred_point = $rfdrow->value;
                $referring_point = $rfrrow->value;
                if ($this->phpsession->get('http_ref')) {
                    $http_referrer = $this->phpsession->get('http_ref');
                } else {
                    $http_referrer = '';
                }
                $refer_table_data = array('referrer_id' => $referrer_id,
                    'refered_id' => $user_id,
                    'refered_point' => $referred_point,
                    'referrer_point' => 0,
                    //'referrer_point' => $referring_point,   // referrd usr will get points only after referred usr place 1st order
                    'refferrer_url' => $http_referrer,
                    'refer_date' => date("Y-m-d H:i:s")
                );

                /* Inserting data to refer table */
                if (is_array($refer_table_data)) {
                    $this->db->query($this->db->insert_string("refer_friend", $refer_table_data));
                    $refer_id = $this->db->insert_id();

                    if ($refer_id > 0) {
                        /* after inserting data sending mail to both person */
                        /**   call below funt _refer_sendmail() only after referred usr place 1st order   * */
                        // 	$this->_refer_sendmail($refer_id,$referrer_id,$user_id);
                        $this->__refer_sendmail($refer_id, $referrer_id, $user_id);

                        /* Clearing mobile number from session */
                        $this->phpsession->clear('ref_mobile_number');
                        $this->phpsession->clear('http_ref');
                    }
                }
            }
        }
        return true;
    }

// end _referFriend
    /**   refer friend new code ends here * */

    function _refer_sendmail($refer_id, $referrer_id, $user_id) {
        $this->load->model('SystemEmail_Model', 'SE_Model');
        $admin_email = $this->getAdminEmails();
        $site = $this->SE_Model->getSiteTitle(); //site title

        /* getting referred person data */
        $usersInfo = $this->getCustomerDetail($user_id);
        $rfdrow = $this->db->select('value')->get_where('tbl_ref_friend', array('type' => 'ref_person_greeting'))->row();
        if (!empty($rfdrow)) {
            $refered_person_mail_content = $rfdrow->value;
        }

        $rfdrow_points = $this->db->select('value')->get_where('tbl_ref_friend', array('type' => 'referred_point'))->row();
        if (!empty($rfdrow_points)) {
            $refered_person_points = $rfdrow_points->value;
        }

        /* getting referrer person data */

        $reffereInfo = $this->getCustomerDetail($referrer_id);
        $rfrrow = $this->db->select('value')->get_where('tbl_ref_friend', array('type' => 'ref_person_inform'))->row();
        if (!empty($rfrrow)) {
            $referrer_person_mail_content = $rfrrow->value;
        }
        $rfrrow_points = $this->db->select('value')->get_where('tbl_ref_friend', array('type' => 'referring_point'))->row();
        if (!empty($rfrrow_points)) {
            $referrer_person_points = $rfrrow_points->value;
        }

        /**   adding refer points to both account      * */
        $this->_addReferPoints($referrer_id, $referrer_person_points, $user_id, $refered_person_points);

        /* end adding refer points to both account */

        /* sending mail to referred person */

        $this->email->from($admin_email->value, $site->value);
        $this->email->to($usersInfo->email);
        //$this->email->cc('p.sudhakar@agiletechnosys.com');
        $subject = 'Refer Friend';
        $this->email->subject($subject);


        //$message = str_replace("[[first_name]]", $usersInfo->first_name, str_replace('\n','',htmlspecialchars_decode($refered_person_mail_content)));
        $message = str_replace("[[first_name]]", $usersInfo->first_name, html_entity_decode($refered_person_mail_content));
        $message = str_replace("[[last_name]]", $usersInfo->last_name, $message);
        $message = str_replace("[[points]]", $refered_person_points, $message);
        $message = str_replace("[[referring_person]]", $reffereInfo->first_name . ' ' . $reffereInfo->last_name, $message);


        $emailPath = $this->config->item('base_abs_path') . "templates/" . $this->config->item('base_template_dir');
        $email_template = file_get_contents($emailPath . '/email/email.html');

        $email_template = str_replace("[[EMAIL_HEADING]]", $subject, $email_template);
        $email_template = str_replace("[[EMAIL_CONTENT]]", nl2br(utf8_encode($message)), $email_template);
        $email_template = str_replace("[[SITEROOT]]", $this->config->item('base_url'), $email_template);
        $email_template = str_replace("[[LOGO]]", $this->config->item('base_url') . "templates/" . $this->config->item('base_template_dir'), $email_template);
        //print_r($email_template); exit;
        $this->email->message(htmlspecialchars_decode(($email_template)));


        if (!$this->email->send()) {
            // Generate error
            echo $this->email->print_debugger();
        } else {
            unset($message);
            unset($email_template);
            $this->email->clear();
        }

        /** done sending mail to reffered person  * */
        /**   sending mail to referrer   * */
        $this->email->from($admin_email->value, $site->value);
        $this->email->to($reffereInfo->email);
        //$this->email->cc('p.sudhakar@agiletechnosys.com');
        $subject = 'Refer Friend';
        $this->email->subject($subject);

        //	$message = str_replace("[[first_name]]", $reffereInfo->first_name, str_replace('\n','',htmlspecialchars_decode($referrer_person_mail_content)));

        $message = str_replace("[[first_name]]", $reffereInfo->first_name, html_entity_decode($referrer_person_mail_content));

        $message = str_replace("[[last_name]]", $reffereInfo->last_name, $message);
        $message = str_replace("[[points]]", $referrer_person_points, $message);
        $message = str_replace("[[referred_person]]", $usersInfo->first_name . ' ' . $usersInfo->last_name, $message);

        $emailPath = $this->config->item('base_abs_path') . "templates/" . $this->config->item('base_template_dir');
        $email_template = file_get_contents($emailPath . '/email/email.html');

        $email_template = str_replace("[[EMAIL_HEADING]]", $subject, $email_template);
        $email_template = str_replace("[[EMAIL_CONTENT]]", nl2br(utf8_encode($message)), $email_template);
        $email_template = str_replace("[[SITEROOT]]", $this->config->item('base_url'), $email_template);
        $email_template = str_replace("[[LOGO]]", $this->config->item('base_url') . "templates/" . $this->config->item('base_template_dir'), $email_template);
        //print_r($email_template); exit;
        $this->email->message(htmlspecialchars_decode(($email_template)));

        if (!$this->email->send()) {
            // Generate error
            echo $this->email->print_debugger();
        } else {
            unset($email_template);
            $this->email->clear(TRUE);
        }

        /* done sending mail to reffereer */

        return true;
    }

//_refer_sendmail

    public function _addReferPoints($referrer_id, $referrer_person_points, $user_id, $refered_person_points) {
        $this->db->query("UPDATE `users` SET `order_points` = order_points + $referrer_person_points WHERE `userid` = $referrer_id");
        $this->db->query("UPDATE `users` SET `order_points` = order_points + $refered_person_points WHERE `userid` = $user_id");
        return true;
    }

//_addReferPoints

    function __refer_sendmail($refer_id, $referrer_id, $user_id) {
        $this->load->model('SystemEmail_Model', 'SE_Model');
        $admin_email = $this->getAdminEmails();
        $site = $this->SE_Model->getSiteTitle(); //site title

        /* getting referred person data */
        $usersInfo = $this->getCustomerDetail($user_id);
        $rfdrow = $this->db->select('value')->get_where('tbl_ref_friend', array('type' => 'ref_person_greeting'))->row();
        if (!empty($rfdrow)) {
            $refered_person_mail_content = $rfdrow->value;
        }

        $rfdrow_points = $this->db->select('value')->get_where('tbl_ref_friend', array('type' => 'referred_point'))->row();
        if (!empty($rfdrow_points)) {
            $refered_person_points = $rfdrow_points->value;
        }

        /* getting referrer person data */

        $reffereInfo = $this->getCustomerDetail($referrer_id);
        $rfrrow = $this->db->select('value')->get_where('tbl_ref_friend', array('type' => 'ref_person_inform'))->row();
        if (!empty($rfrrow)) {
            $referrer_person_mail_content = $rfrrow->value;
        }
        $rfrrow_points = $this->db->select('value')->get_where('tbl_ref_friend', array('type' => 'referring_point'))->row();
        if (!empty($rfrrow_points)) {
            $referrer_person_points = $rfrrow_points->value;
        }

        /**   adding refer points only to referred persons      * */
        //$this->_addReferrerPoints($referrer_id,$referrer_person_points,$user_id,$refered_person_points);
        $this->_addReferredPoints($referrer_id, $referrer_person_points, $user_id, $refered_person_points);

        /* sending mail to referred person */

        $this->email->from($admin_email->value, $site->value);
        $this->email->to($usersInfo->email);
        //$this->email->cc('p.sudhakar@agiletechnosys.com');
        $subject = 'Refer Friend';
        $this->email->subject($subject);

        //$message = str_replace("[[first_name]]", $usersInfo->first_name, str_replace('\n','',htmlspecialchars_decode($refered_person_mail_content)));
        $message = str_replace("[[first_name]]", $usersInfo->first_name, html_entity_decode($refered_person_mail_content));
        $message = str_replace("[[last_name]]", $usersInfo->last_name, $message);
        $message = str_replace("[[points]]", $refered_person_points, $message);
        $message = str_replace("[[referring_person]]", $reffereInfo->first_name . ' ' . $reffereInfo->last_name, $message);

        $emailPath = $this->config->item('base_abs_path') . "templates/" . $this->config->item('base_template_dir');
        $email_template = file_get_contents($emailPath . '/email/email.html');

        $email_template = str_replace("[[EMAIL_HEADING]]", $subject, $email_template);
        $email_template = str_replace("[[EMAIL_CONTENT]]", nl2br(utf8_encode($message)), $email_template);
        $email_template = str_replace("[[SITEROOT]]", $this->config->item('base_url'), $email_template);
        $email_template = str_replace("[[LOGO]]", $this->config->item('base_url') . "templates/" . $this->config->item('base_template_dir'), $email_template);
        // echo $email_template; exit;

        $this->email->message(htmlspecialchars_decode(($email_template)));

        //echo $this->email->print_debugger();die;

        if (!$this->email->send()) {
            // Generate error
            echo $this->email->print_debugger();
        } else {
            unset($message);
            unset($email_template);
            $this->email->clear();
        }
        /** done sending mail to reffered person  * */
        return true;
    }

//__refer_sendmail

    public function _addReferrerPoints($referrer_id, $referrer_person_points, $user_id, $refered_person_points) { // user who is referring others
        $this->db->query("UPDATE `users` SET `order_points` = order_points + $referrer_person_points WHERE `userid` = $referrer_id");
        // echo "UPDATE refer_friend SET referrer_point = $referrer_person_points WHERE refered_id = $user_id and referrer_id = $referrer_id and referrer_point = 0";
        $this->db->query("UPDATE refer_friend SET referrer_point = $referrer_person_points WHERE refered_id = $user_id and referrer_id = $referrer_id and referrer_point = 0");
        return true;
    }

//_addReferPoints

    public function _addReferredPoints($referrer_id, $referrer_person_points, $user_id, $refered_person_points) { // user who is referred by other
        $this->db->query("UPDATE `users` SET `order_points` = order_points + $refered_person_points WHERE `userid` = $user_id");
        return true;
    }

//_addReferPoints

    public function getAdminEmails() {
        $query = $admin_email = $this->db->query("SELECT * from sitesetting where type='SITE_EMAIL'");
        $row = array();
        return $row = $query->row();
    }

//end function getAdminEmails

    public function getSiteTitle() {
        $query = $admin_email = $this->db->query("SELECT * from sitesetting where type='SITETITLE'");
        $row = array();
        return $row = $query->row();
    }

//end function getAdminEmails

    /*
      This function use to check duplicate email
     */

    function checkDuplicateEmail($email) {
        if ($this->phpsession->get('tmusrLgn')) {
            $user_id = $this->phpsession->get('tmUserId');
            $row = $this->db->get_where('users', array('email' => $email, 'userid !=' => $user_id))->num_rows();
        } else {
            $row = $this->db->get_where('users', array('email' => $email))->num_rows();
        }
        return ($row > 0) ? "false" : "true";
    }

// end of checkDuplicateEmail()

    /*
      This function use to check duplicate mobile
     */

    function checkDuplicateMobile($mobile) {
        $row = $this->db->get_where('users', array('mobile' => $mobile))->num_rows();
        return ($row > 0) ? "false" : "true";
    }

// end of checkDuplicateMobile()

    function checkDuplicateMobileOnCheckout($mobile) {
        if ($this->phpsession->get('tmusrLgn')) {
            $user_id = $this->phpsession->get('tmUserId');
            $row = $this->db->get_where('users', array('mobile' => $mobile, 'userid !=' => $user_id))->num_rows();
        } else {
            $row = $this->db->get_where('users', array('mobile' => $mobile))->num_rows();
        }
        return ($row > 0) ? "false" : "true";
    }

// end of checkDuplicateUsername()

    function updateActivationCode($userid, $code) {
        $data = array('activation_code' => $code);
        if (!empty($code) && !empty($userid)) {
            $result = $this->db->query($this->db->update_string("tbl_registration", $data, array('registration_id' => $userid)));
            if ($result != "") {
                return 1;
            }
        }
    }

    #-----------------------------------------
    // Get user activation code by id
    #-----------------------------------------

    public function getUserActivationCode($act_code) {
        $query = $this->db->query("SELECT * from tbl_registration where activation_code='$act_code'");

        if ($query) {
            $row = array();
            if ($query->num_rows() > 0) {
                $row = $query->row();
                return $row;
            } else {
                return FALSE;
            }
        } else {
            return FALSE;
        }
    }

//end getUserActivationCode
    ##-------------------------------------------------
    // Verified user account
    ##-------------------------------------------------

    public function verifyUserAccount($uid) {
        $dt = date("Y-m-d h:m:s");
        $row = $this->db->query("update users set is_verified='yes',verified_date='$dt' where userid='$uid'");
        // $personid = $row['verify_person_account'];
    }

    /* check user login */

    public function checkLogin($uname, $pass) {
        //$this->load->database();
        $query = $this->db->query("select * from users where email ='$uname'  AND password = '$pass'  AND usertypeid <> 1");
        if ($query->num_rows() > 0) {
            return $query->row();
        } else {
            return FALSE;
        }
    }

    public function updateUsersSignupDate($uid) {
        $this->db->query("update users set signup_date = '" . date("Y-m-d H:i:s") . "' where userid='$uid'");
        return 1;
    }

    public function getSuburb($id = 0) {
        $query = $this->db->query("SELECT * from tbl_suburb");
        $frmcombo = '';
        foreach ($query->result() as $suburb) {
            $frmcombo .="<option value=" . $suburb->id . " ";
            if ($id > 0) {
                if ($id == $suburb->id) {
                    $frmcombo .="selected='true'";
                }
            }
            $frmcombo .=">";

            $frmcombo .=$suburb->suburb_name . " , +$" . $suburb->delivery_fee . "</option>";
        }//foreach
        return $frmcombo;
    }

//end getSuburb

    public function getAllSuburb() {
        //$this->db->select('id,suburb_name');
        return $this->db->get('tbl_suburb')->result();
    }

//end getSuburb

    public function getInfo($uid) {
        $query = $this->db->query("select * from users where userid = '$uid'  ");

        if ($query->num_rows() > 0) {
            return $query->row();
        } else {
            return FALSE;
        }
    }

    /* This function adds mobile verification code */

    public function addCode($code, $uid) {
        $this->db->query("update users set verify_code = '$code' where userid = '$uid'");
        return 1;
    }

    public function addMob() {

        $regData = array('mobile' => $this->input->post('mobile', TRUE));
        // insert new record
        if (is_array($regData)) {
            //$this->db->query($this->db->insert_string("tbl_registration",$regData));
            $this->db->query($this->db->update_string("users", $regData));
            return $this->db->insert_id();
        }
    }

//end of doRegistration

    function getCustomerDetail($cid) {
        $this->db->reconnect(); //muse connect after pdf

        $this->db->join('tbl_suburb', 'users.suburb = tbl_suburb.id');
        $this->db->join('mast_state', 'users.state = mast_state.id');
        $res = $this->db->get_where('users', array('userid' => $cid))->row();
        if ($res) {
            return $res;
        } else {
            return false;
        }
    }

    function getCustomerDetailsOfreferrer($userid) {
        /* $this->db->select('u1.userid as referrer_userid, u1.first_name as referrer_fname, u1.last_name as referrer_lname, u2.userid as referred_userid, u2.first_name as referred_fname, u2.last_name as referred_lname');
          $this->db->from('users u1, users u2'); // u1 for referrer, u2 for referred usr
          //  $this->db->join('users u1', "rf.referrer_id = u1.userid");

          $this->db->get_where('refer_friend rf', array('rf.refered_id' => $userid, 'rf.referrer_point' => 0, "rf.referrer_id" => "u1.userid", "rf.referrer_id" => "u2.userid"))->row(); */
        $q = "select u1.userid as referrer_userid, u1.first_name as referrer_fname, u1.last_name as referrer_lname, u1.email as referrer_email,
                  u2.userid as referred_userid, u2.first_name as referred_fname, u2.last_name as referred_lname, u2.is_first_order, u2.email as referred_email
         from users u1, users u2, refer_friend rf
         where rf.refered_id = $userid and rf.referrer_point= 0 and rf.referrer_id=u1.userid and rf.refered_id=u2.userid";
        $r = $this->db->query($q);
        return $r->row_array();
    }

//getCustomerDetailsOfreferrer

    function getRegisterDetail($cid) {
        $this->db->join('tbl_suburb', 'users.suburb = tbl_suburb.id');
        $res = $this->db->get_where('users', array('userid' => $cid))->row();
        if ($res) {
            return $res;
        } else {
            return false;
        }
    }

    function checkDuplicateMobile_PerInfo($mobile) {
        $user_id = $this->phpsession->get('tmUserId');
        $row = $this->db->get_where('users', array('mobile' => $mobile, 'userid !=' => $user_id))->num_rows();

        return ($row > 0) ? "false" : "true";
    }

// end of checkDuplicateMobile()

    function checkpassword($password) {
        $user_id = $this->phpsession->get('tmUserId');
        $pass = base64_encode($password);
        $row = $this->db->get_where('users', array('base_password' => $pass, 'userid =' => $user_id))->num_rows();

        return ($row > 0) ? "true" : "false";
    }

//end cheackpassword

    function getUserInfo($userid) {
// 		  $row = $this->db->get_where('users',array('userid='=>$userid))->row();
        $query = $this->db->query("select mobile from users where userid = '$userid'  ");
        return $query->result();
    }

//end cheackpassword	
}

// end of class