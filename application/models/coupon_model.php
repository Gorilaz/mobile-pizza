<?php

class Coupon_Model extends CI_Model {

    public function __construct() {
        parent::__construct();
        $this->load->database();
    }

//end Country_Model
    #-----------------------------------------
    // Country oprations from admin site
    #-----------------------------------------

    public function couponOperations($data, $action = '', $edit_id = 0) {

        switch ($action) {
            case 'addnew':
                // insert new record

                if (is_array($data)) {
                    if($data['social_loker'] != 0 ){
                        $this->resetSocialLocker();
                    }
                    $this->db->query($this->db->insert_string("tbl_coupon", $data));
                    return $this->db->insert_id();
                }
                break;
            case 'update':

                // update existing record
                if (is_array($data)) {
                    if($data['social_loker'] != 0 ){
                        $this->resetSocialLocker();
                    }
                    $this->db->query($this->db->update_string("tbl_coupon", $data, array('id' => $edit_id)));
                    return 1;
                }
                break;
            case 'delete':
                //$this->db->query("delete from mast_countryname where id in (".$data['id'].")");

                $array = explode(",", $data['coupon_id']);

                $this->db->where_in('id', $array);
//
                $this->db->delete('tbl_coupon');

                return "Coupon(s) deleted successfully.";
                break;
            case 'active':
                $this->db->query("update tbl_coupon set status='active' where id in (" . $data['coupon_id'] . ")");
                return "Coupon(s) actived successfully.";
                break;
            case 'inactive':
                $this->db->query("update tbl_coupon set status='inactive' where id in (" . $data['coupon_id'] . ")");
                return "Coupon(s) inactived successfully.";
                break;
        }
    }

    public function resetSocialLocker(){
        $this->db->where('social_loker', 1)->update('tbl_coupon', array('social_loker' => 0, 'status' => 'inactive'));
    }



//end function testimonialOperations
    #-----------------------------------------
    // Count all Country records
    #-----------------------------------------

    public function countCouponRecords($search = '-', $orderby = '') {
        if ($search != "-") {
            //return $this->db->query("select count(country_id) as rowcount from mast_country where country like '%".$search."%' order by ".$orderby."")->row()->rowcount;

            $this->db->select('count(id) as rowcount');

            $this->db->like('couponcode', $search);

            $this->db->order_by($orderby);

            $res = $this->db->get('tbl_coupon');

            return $res->row()->rowcount;
        } else {
            //	return $this->db->query("select count(country_id) as rowcount from mast_country order by ".$orderby."")->row()->rowcount;

            $this->db->select('count(id) as rowcount');

            $this->db->order_by($orderby);

            $res = $this->db->get('tbl_coupon');

            return $res->row()->rowcount;
        }
    }

//end countCountryRecords
    #-----------------------------------------
    // Get Country records list
    #-----------------------------------------

    public function getCouponRecords($num = 0, $offset = 0, $search = '', $orderby = '') {
        if ($offset > 0) {
            if ($search != "-") {
                //return $this->db->query("SELECT * from mast_country where country like '%".$search."%' order by ".$orderby." limit ".$num .",".$offset);

                $this->db->like('couponcode', $search);

                $this->db->order_by($orderby);

                return $this->db->get('tbl_coupon', $offset, $num);
            } else {
                //	return $this->db->query("SELECT * from mast_country where 1 order by ".$orderby." limit ".$num .",".$offset);

                $this->db->order_by($orderby);

                return $this->db->get('tbl_coupon', $offset, $num);
            }
        } else {
            //return $this->db->query("SELECT * from mast_country where 1 order by ".$orderby." asc");

            $this->db->order_by($orderby);

            return $this->db->get('tbl_coupon');
        }
    }

//end getCountryRecords
    #-----------------------------------------
    // get Country record by country_id
    #-----------------------------------------

    public function getCouponById($coupon_id = 0)
    {
        if( !empty($coupon_id) )
        {
            $query = $this->db->get_where('tbl_coupon', array('couponcode' => $coupon_id));

            $row = array();

            if( $query->num_rows() > 0 )
            {
                $row = $query->row();
            }

            return $row;
        }
    }

    public function getCouponByType($type = null) {
        if (is_null($type)) {
            return;
        }

        $this->db->select('id');
        $this->db->where(array('coupontype' => $type));
        $query = $this->db->get('tbl_coupon');

        $result = $query->result();

        if (empty($result)) {
            $result[] = new stdClass();
            $result[0]->discountper = 0;
            $result[0]->id = 0;
            $result[0]->coupontype = $type;
        }

        return $result;
    }

    public function productCupon($productId){

        $hasCoupon = $this->db->select('has_coupon')->where('product_id', $productId)->get('tbl_product')->row();
        if($hasCoupon->has_coupon == 1){
            return 1;
        } else {
            return 0;
        }

    }

    public function getCoupon($voucher){
        $dateNow = date('Y-m-d');
        $coupon = $this->db->select('discountper, coupondescription')->
            where('couponcode', $voucher)->
            where('expirydate >', $dateNow)->
            where('status', 'active')->
            get('tbl_coupon')->row();

        if(isset($coupon->discountper) && !empty($coupon->discountper)){
            return $coupon;
        } else {
            return false;
        }

    }

    public function getCouponByCode($code = null) {
        if (is_null($code) || !$code) {
            return null;
        }
        //var_dump($code);
        //$this->db->where('expirydate > CURDATE()', null, false);
        $this->db->where('couponcode', $code);
        //$this->db->where('status', 'active');
        $query = $this->db->get('tbl_coupon');
        //var_dump($query);
        $result = $query->result();
//        error_log('getCookie - result: ' . var_export($result, true));
//        error_log('getCookie - result: ' . var_export($result[0], true));
//        error_log('getCookie - result: ' . var_export((isset($result) && !empty($result[0])), true));
        return (isset($result) && !empty($result[0])) ? $result[0] : '';
    }

//end getCountryById

    public function checkDuplicateCoupon($couponcode, $action, $editid) {
        //$this->load->database();
        if ($action == 'addnew') {
            $query = $this->db->query("select couponcode from tbl_coupon where couponcode='$couponcode'");
        } else {

            $query = $this->db->query("select couponcode from tbl_coupon where couponcode='$couponcode' and id != " . $editid);
        }
        //echo $this->db->last_query();
        return ($query->num_rows() > 0) ? "false" : "true";
    }

    public function getCouponIdByName($name) {
        $this->db->select('id');
        $query = $this->db->get_where('tbl_coupon', array('couponcode' => $name));

        return array_pop($query->result());
    }

//    public function couponOperations(){
//        $dateNow = date('Y-m-d');
//        $is_social_loker = $this->db->select('id')->where('expirydate >', $dateNow)->
//            where('social_loker', 1)->
//            where('status', 'active')->
//            get('tbl_coupon');
//        if($is_social_loker->num_rows() > 0){
//            $social_loker_id = $is_social_loker->row();
//            return $social_loker_id->id;
//        } else {
//            return 0;
//        }
//    }

    /**
     * Get text for Not available discounts
     * @return mixed
     */
    public function getNoDiscountText(){
        $text = $this->db->select('value')->where('type', 'text_no_discount')->get('sitesetting')->row();
        return $text->value;
    }

}

//end class Country_Model




?>