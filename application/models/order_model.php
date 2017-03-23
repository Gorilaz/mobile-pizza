<?php

class Order_model extends CI_Model{

    /**
     * Get active payment methods
     * @return mixed
     */
    public function getPaymentMethods() {
        return $this->db->get_where('tbl_payment', array('pay_status'=> 'Active'))->result();
    }

    /**
     * Get Delivery Fee
     * @param $suburbId
     * @return mixed
     */
    public function getDeliveryFee($suburbId){
        $delivery = $this->db->
            select('delivery_fee')->
            where('id', $suburbId)->
            where('status', 'active')->
            get('tbl_suburb')->
            row();
        if( $delivery )
        {
            return $delivery->delivery_fee;
        }
        return 0;
    }

    /**
     * Save Order
     * @param $checkout
     * @param $total
     * @param $delivery_fee
     * @param $userId
     * @param $html
     */
    public function saveOrder($checkout, $total, $discount, $fees, $userId, $html, $status){

        /** real_id (order no) */
        $real = $this->db->get('tbl_order_number')->row();
        $real_id = $real->order_number;
        $this->db->update('tbl_order_number',array('order_number' => ($real_id + $real->increment)));
        /** end */

        $current_timestamp = date('Y-m-d H:i:s');

        if( trim($checkout['when']) === 'Later' )
        {
            $time = date('H:i:s', strtotime($checkout['time']));

            if( empty($checkout['date']) )
            {
                $checkout['date'] = date('Y-m-d');
            }

            $str_date = $checkout['date'] . ' ' . $time;

            $placement_date = date('Y-m-d H:i:s', strtotime($str_date));
        }
        else
        {
            $placement_date = null;
        }

        /* Add points earned if is enabled */
        $settings = $this->session->userdata('siteSetting');
        $pointsEarned = 0;
        if($settings->loyatly_program == 'enable') {
            $pointsEarned = $settings->ORDERPOINTS;
        }

        if($checkout['payment'] == 1){
            $checkout['payment'] = 'Cash On Delivery';
        } elseif($checkout['payment'] == 2) {
            $checkout['payment'] = 'Paypal';
        } else {
            $checkout['payment'] = 'Credit Card';
        }

        //TODO: coupon discount type
        $order = array(
            'userid'                => $userId,
            'real_id'               => $real_id,
            'payment_method'        => $checkout['payment'],
            'payment_amount'        => $total,
            'order_option'          => $checkout['delivery'],
            'order_description'     => $html,
            'order_comment'         => $checkout['comment'],
            'points_earned'         => $pointsEarned,
            'points_used'           => $checkout['loyalityPointsUsed'],
            'coupon_type'           => '',
            'voucher_code'          => (isset($checkout['couponName']))?$checkout['couponName']:'',
            'discount'              => $discount,
            'order_date'            => $placement_date,
            'order_placement_date'  => $current_timestamp,
            'from_mobile'           => 1
        );

        if( isset($fees['delivery']) )
        {
            $order['delivery_fee'] = $fees['delivery'];
        }

        if( isset($fees['holiday']) )
        {
            $order['public_holiday_fee'] = $fees['holiday'];
        }

        if( isset($fees['cc']) )
        {
            $order['min_order_credit_card_fee'] = $fees['cc'];
        }

        if( isset($fees['pp']) )
        {
            $order['min_order_paypal_fee'] = $fees['pp'];
        }

        if($status == 'save'){
            $this->db->insert('mast_order', $order);
            $order_id = $this->db->insert_id();

            /*
             * Update user points in case he used them
             */
            $this->updateUserPointsBasedOnOrder($order);


        } else {
           $order = json_encode($order);
           $this->db->insert('order_pending', array('order' => $order));
           $order_id = $this->db->insert_id();
        }


        return $order_id;

    }

    /**
     * @param $order_id
     * @param $name
     */
    public function updatePdf($order_id, $name)
    {
        $this->db->where('order_id', $order_id)->update('mast_order', array('rest_pdf_file' => $name));
    }

    /**
     * receive the ipn from paypal
     * save order
     * @param $item_number
     */
    public function saveOrderFromTemp($item_number){

        $order = $this->db->select('order')->where('id', $item_number)->get('order_pending')->row();
        $order = json_decode($order->order);

        $this->db->insert('mast_order', $order);
        $order_id = $this->db->insert_id();

        /*
         * Update user points in case he used them
         */
        $this->updateUserPointsBasedOnOrder($order);
        return $order_id;
    }


    /**
     * save order after paypal
     * @param $orderId
     */
    public function savePaypalOrder($orderId){

        $order = $this->db->select('order')->where('id', $orderId)->get('order_pending')->row();

        $order = json_decode($order->order);

        $this->db->insert('mast_order', $order);

        /*
        * Update user points in case he used them
        */
        $this->updateUserPointsBasedOnOrder($order);

    }

    private function updateUserPointsBasedOnOrder($order) {
        if($order['points_used'] > 0 || $order['points_earned'] > 0) {

            $user = $this->db->get_where('users',array('userid' => $order['userid']))->row();
            $points = $user->order_points - $order['points_used'];

            if($order['points_earned'] > 0) {
                $points+= $order['points_earned'];
            }

            $this->db->where('userid', $user->userid);
            $this->db->update('users', array('order_points' => $points));
        }
    }

    /**
     * Save Order Again
     * @param $order_again
     * @param $order_id
     * @param $userId
     */
    public function save_order_again($order_again, $order_id, $userId) {

        $order_again_data = array(
            'userid'        => $userId,
            'cart_content'  => json_encode($order_again),
            'order_id'      => $order_id
        );

        $this->db->insert('order_again', $order_again_data);
    }

    /**
     * Get Order
     * @param $orderId
     * @return mixed
     */
    public function getOrder($orderId){

        $order = $this->db->where('order_id', $orderId)->get('mast_order')->row_array();
        return $order;
    }

    /**
     * Get Suburb Name
     * @param $suburbId
     * @return mixed
     */
    public function getSubUrb($suburbId){

        $subUrb = $this->db->select('suburb_name')->
            where('id', $suburbId)->
            where('status', 'active')->
            get('tbl_suburb')->row();

        if($subUrb){
            return $subUrb->suburb_name;
        } else {
            return false;
        }
    }


    /**
     * Get Suburb Fee
     * @param $suburbId
     * @return bool
     */
    public function getSubUrbFee($suburbId){
        $subUrb = $this->db->select('delivery_fee')->
            where('id', $suburbId)->
            where('status', 'active')->
            get('tbl_suburb')->row();
        if($subUrb){
            return $subUrb->delivery_fee;
        } else {
            return false;
        }

    }
    /**
     * Get Shop Address
     */
    public function getShopAddress(){

        $shopAddress = $this->db->select('value')->where('type', 'shop_address')->get('sitesetting')->row();
        return $shopAddress->value;

    }


    /**
     * order text from admin
     * @return mixed
     */
    public function getAdminText(){

        $text = $this->db->select('value')->
            where('type', 'order_text')->
            get('sitesetting')->
            row();
        return $text->value;

    }


    /**
     * get user orders
     * @param $userId : user id
     * @param $offset
     * @param $limit
     * @return mixed
     */
    public function getYourOrders($userId, $offset, $limit){
        $orders = $this->db
                       ->select('order_id, order_description, payment_amount, points_earned, points_used, UNIX_TIMESTAMP(order_placement_date) as order_placement_date')
                       ->where('userid', $userId)
                       ->order_by('order_placement_date', 'desc')
                       ->get('mast_order', $limit, $offset)
                       ->result();

        return $orders;

    }


    /**
     * @param $userId
     */
    public function countYourOrders($userId){

        $count = $this->db->where('userid', $userId)->count_all_results('mast_order');

        return $count;
    }

    /**
     *
     */
    public function getSiteTitle(){
       $title = $this->db->select('value')->where('type', 'SITETITLE')->get('sitesetting')->row();
        return $title->value;
    }

    public function getMinimumOrder(){

        $holidayFee = $this->db->select('min_order_amt')->get('tbl_order_surcharge')->row();

        return $holidayFee->min_order_amt;
    }


    /**
     * Get min order
     * @return mixed
     */
    public function getMinOrder(){
        return $this->db->get('tbl_order_surcharge')->row();
    }

    /**
     * Save Shopping Cart
     * @param $cart
     * @param $order_id
     */
    public function saveShoppingCart($cart, $order_id){

        foreach($cart as $item){
            /** Half */
            if($item['product_type'] == 'half'){
                $ids = explode('_',$item['id']);

                /** First Half */

                if(isset($item['ingredient_ids']['First Half']['extra'])){
                    $firstExtIng = implode(',', $item['ingredient_ids']['First Half']['extra']);
                } else {
                    $firstExtIng = '';
                }

                if(isset($item['ingredient_ids']['First Half']['default'])){
                    $firstDefIng = implode(',', $item['ingredient_ids']['First Half']['default']);
                } else {
                    $firstDefIng = '';
                }


                $firstProduct = array(
                    'order_id' => $order_id,
                    'product_flag' => 'H',
                    'product_id'   => $ids[0],
                    'variation_id' => $item['variation_id'],
                    'extra_ingredient_id' => $firstExtIng,
                    'default_ingredient_id' => $firstDefIng,
                    'quantity'              => $item['qty'],
                    'comment'               => $item['instruction'],
                    'half_pizza_group_id'   => $item['half_pizza_group_id']

                );

                $this->db->insert('tbl_shopping_cart', $firstProduct);
                /** end First Half */

                /** Second Half */
                if(isset($item['ingredient_ids']['Second Half']['extra'])){
                    $secondExtIng = implode(',', $item['ingredient_ids']['Second Half']['extra']);
                } else {
                    $secondExtIng = '';
                }

                if(isset($item['ingredient_ids']['Second Half']['default'])){
                    $secondDefIng = implode(',', $item['ingredient_ids']['Second Half']['default']);
                } else {
                    $secondDefIng = '';
                }


                $secondProduct = array(
                    'order_id'              => $order_id,
                    'product_flag'          => 'H',
                    'product_id'            => $ids[1],
                    'variation_id'          => $item['variation_id_half'],
                    'extra_ingredient_id'   => $secondExtIng,
                    'default_ingredient_id' => $secondDefIng,
                    'quantity'              => $item['qty'],
                    'comment'               => $item['instruction'],
                    'half_pizza_group_id'   => $item['half_pizza_group_id']

                );

                $this->db->insert('tbl_shopping_cart', $secondProduct);
                /** end Second Half */

            /** Single */
            } elseif($item['product_type'] == 'single'){

                if(isset($item['ingredient_ids']['Single']['extra'])){
                    $extIng = implode(',', $item['ingredient_ids']['Single']['extra']);
                } else {
                    $extIng = '';
                }

                if(isset($item['ingredient_ids']['Single']['default'])){
                    $defIng = implode(',', $item['ingredient_ids']['Single']['default']);
                } else {
                    $defIng = '';
                }

                $product = array(
                    'order_id'              => $order_id,
                    'product_flag'          => 'S',
                    'product_id'            => $item['id'],
                    'extra_ingredient_id'   => $extIng,
                    'default_ingredient_id' => $defIng,
                    'quantity'              => $item['qty'],
                    'comment'               => $item['instruction'],
                    'half_pizza_group_id'   => $item['half_pizza_group_id']

                );

                if( isset($item['variation_id']) )
                {
                    $product['variation_id'] = $item['variation_id'];
                }
                $this->db->insert('tbl_shopping_cart', $product);
               



            } else {
                $variation_id = implode(',', $item['variation_id']);
                $product = array(
                    'order_id'              => $order_id,
                    'product_flag'          => 'S',
                    'product_id'            => $item['id'],
                    'variation_id'          => $variation_id,
                    'extra_ingredient_id'   => '',
                    'default_ingredient_id' => '',
                    'quantity'              => $item['qty'],
                    'comment'               => $item['instruction'],
                    'half_pizza_group_id'   => ''

                );
                $this->db->insert('tbl_shopping_cart', $product);
            }



        }

    }
    
       /**
     * Send Order data to API
     * @param $order_id
     */
    
    public function sendOrderToAPI($order_id) {
        
     //   $order = $this->order_model->getOrder($orderId);
     
    $ord = $this->db->select('*')->where('order_id', $order_id)->get('tbl_shopping_cart')->result();
    
    $zakaz = json_encode($ord);
    
    $product = $this->db->select('*')->where('product_id', $ord[0]->product_id)->get('tbl_product')->result();
    
     //   $halfFee = $this->db->select('half_pizza_group_fee')->where('id', $half_pizza_group_id)->get('half_pizza_group')->row();

     //  $product = json_encode($product);
 var_dump($product);exit();
  //  die($zakaz);
 
   $obj = new stdObject();
   
   }

    /**
     * Get order products for order again
     * @param $orderId
     */
    public function getShoppingOrder($orderId){
        $shoping = $this->db->where('order_id', $orderId)->get('tbl_shopping_cart')->result_array();

        return $shoping;
    }

    /**
     * Get half fee
     * @param $half_pizza_group_id
     * @return mixed
     */
    public function getHalfFee($half_pizza_group_id)
    {

        $halfFee = $this->db->select('half_pizza_group_fee')->where('id', $half_pizza_group_id)->get('half_pizza_group')->row();
        return $halfFee->half_pizza_group_fee;
    }

    /**
     *
     */
    public function getOrderNumber()
    {
        $row = $this->db->select('(order_number) AS order_number')->get('tbl_order_number')->row();

        if( !empty($row) )
        {
            return $row->order_number;
        }

        return 1;
    }

//checkValidVoucher

    //VV almost the same like checkValidVoucher() above except the last condition $row>1 (instead fo $row>0), This functin is called form order.php after the data has been recarded to the mast_ord so need to increase
    // the row number to filter out just recorded order
    public function checkValidVoucher2($code) {
        $now = date('Y-m-d');
        $this->db->select('*');   // $this->db->select('discountper');
        //   $this->db->where('expirydate >=', $now);
        $this->db->where('status', 'active');
        if (is_integer((int) $code))
            $this->db->where('id', $code);
        else
            $this->db->where('couponcode', $code);
        $res = $this->db->get('tbl_coupon');
        if ($res->num_rows() > 0) {
             $user=$this->phpsession->get('tmUserId');
             $user=intval($user);
             $code=trim($code);
             $row = $this->db->query("SELECT real_id FROM `mast_order` WHERE voucher_code = '$code' and userid = '$user'  and (coupon_type='discount' or coupon_type='freeproduct' or coupon_type='old')"); //
             $row=$row->num_rows();
            $d = $res->row();
            if ($d->expirydate < $now)
                return 'expired';
            if ($row>1) {
                return 'old';  //existing
            } else {
                return $res->row();
            }
        } else {
            $item = new stdClass();
            $item->id = 0;
            $item->couponcode = $code;
            $item->coupondescription = '';
            $item->coupontype = $code;
            $item->discountper = 0;
            $item->status = 'active';

            return $item;  //// non existing
        }
    }

//getFreeProductDescription

    public function getFreeProductDescription($code) {
        $this->db->reconnect();
        $now = date('Y-m-d');
        $this->db->select('coupondescription');
        $res = $this->db->get_where('tbl_coupon', array('coupontype' => 'freeproduct', 'expirydate >=' => $now, 'status' => 'active', 'couponcode' => trim($code)))->row();
        if ($res) {
            return $res->coupondescription;
        } else {
            return false;
        }
    }

//getCouponDiscDescription

    public function getCouponDiscDescription($code) {
        $this->db->reconnect();
        $now = date('Y-m-d');
        $this->db->select('coupondescription');
        $res = $this->db->get_where('tbl_coupon', array('expirydate >=' => $now, 'status' => 'active', 'id' => trim($code)))->row();
        if ($res) {
            return $res->coupondescription;
        } else {
            return false;
        }
    }

//VV GPRS PRINTER models start

    public function recordPrinterData($real_id,$printer_data,$p_status) {
        $data=array('real_id' =>$real_id,
                    'printer_data' =>$printer_data,
                    'status' => $p_status,
                    'customer_informed'=> false,
                    'restaurant_answer'=>'',
                    'deliver_in'=>'',
                    'why_rejected'=>'',
                    'restaurant_answered_at'=>'',
                    );
        $this->db->query($this->db->insert_string("gprs_printer", $data));
    }
}