<?php

ini_set('memory_limit', '256M');
ini_set('max_execution_time', 300);

class Order extends CI_Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->load->library('cart');
        $this->load->model('Order_Model', 'O_Model');
        $this->load->model('Sitesettings_model', 'SS_Model');
        $this->load->model('Register_Model', 'R_Model');
        $this->load->model('Category_Model', 'C_Model');
        $this->load->model('Users_Model', 'U_Model');
        $this->load->model('Cms_Model', "CM_Model");
        $this->load->model('Manage_text_Model', 'T_Model');
        $this->load->model('State_Model', 'S_Model');
        $this->load->model('Metatags_Model', 'MT_Model');
        $this->load->model('SystemEmail_Model', 'SE_Model');
        $this->load->model('Coupon_model', 'voucher_code');
        $this->load->library('email');
        $this->load->helper('cookie');
        $this->load->helper('cookie_encode');
    }

//    public function index()
//    {
//       if(!$this->phpsession->get('ciAdmId')){   redirect($this->config->item('base_url').'admin/login');   }
//
//       $data['order'] = $this->O_Model->getOrderHistory();
//       $data['menutab'] = 'dashboard';
//       $data['menuitem'] = 'order_history';
//       $this->load->view($this->config->item('base_template_dir').'/admin/order/order_history',$data);
//    }

    function update_payment_status()
    {
        if (!$this->phpsession->get('ciAdmId')) {
            redirect($this->config->item('base_url') . 'admin/login');
        }
        if ($this->input->post('ord_id') > 0) {
            $edit_id = $this->input->post('ord_id');
            $data    = array('payment_status' => $this->input->post('payment_status'));
            $this->O_Model->update_operations($data, 'update', $edit_id);
            $this->phpsession->save('success_msg', 'Payment Status Updated Successfully');
            echo -1;
        }
    }

    function checkLogin()
    {
        if ($this->input->post('check', TRUE)) {
            if ($this->phpsession->get('tmUserId') == '') {
                echo -1;
            }
            else {
                echo 1;
            }
        }
    }

    function checkLoginAndPoints()
    {
        if ($this->input->post('points', TRUE)) {
            if ($this->phpsession->get('tmUserId') == '') {
                echo -1;
            }
            else { //echo $this->phpsession->get('cart_product_points');
                if ($this->phpsession->get('cart_product_points')) {
                    $points = $this->phpsession->get('cart_product_points') + $this->input->post('points', TRUE);
                }
                else {
                    $points = $this->input->post('points', TRUE);
                }

                $userid = $this->phpsession->get('tmUserId');

                $res = $this->O_Model->CheckUserPoints($userid, $points); //echo $this->db->last_query();
                if ($res) {
                    echo -2;
                }
                else {
                    echo -3;
                }
            }
        }
    }

//end checkLoginAndPoints

    function _getMinOrderDeliveryFee()
    {
        $cart_total = 0;
        foreach ($this->cart->contents() as $items) {
            if ($items['options']['loyalty'] != 'lp') {
                $cart_total += $items['subtotal'];
            }
        }

        $fee = $this->C_Model->getMinimumOrderFee();

        $order_less_delivery_fee = 0;
        if (!empty($fee) && !empty($fee->min_order_amt) && !empty($fee->order_less)) {
            if ($cart_total < $fee->min_order_amt) {
                //$cart_total = $cart_total + $fee->order_less;
                $order_less_delivery_fee = $fee->order_less;
            }
        }
        return $order_less_delivery_fee;
    }

//_getMinOrderDeliveryFee
    //if order price is less than define total for paypal then min paypal fee is added
    function _getMinOrderPaypalFee()
    {
        $cart_total = 0;
        foreach ($this->cart->contents() as $items) {
            if ($items['options']['loyalty'] != 'lp') {
                $cart_total += $items['subtotal'];
            }
        }

        $fee = $this->C_Model->getMinimumOrderFee();

        $order_less_paypal_fee = 0;
        if (!empty($fee) && !empty($fee->paypal) && !empty($fee->order_less)) {
            if ($cart_total < $fee->paypal) {
                //$cart_total = $cart_total + $fee->order_less;
                $order_less_paypal_fee = $fee->order_less;
            }
        }
        return $order_less_paypal_fee;
    }

//_getMinOrderPaypalFee
    //if order price is less than define total for credit card online then min order fee is added
    function _getMinOrderCreditCardFee()
    {
        $cart_total = 0;
        foreach ($this->cart->contents() as $items) {
            if ($items['options']['loyalty'] != 'lp') {
                $cart_total += $items['subtotal'];
            }
        }

        $fee = $this->C_Model->getMinimumOrderFee();

        $order_less_credit_card_fee = 0;
        if (!empty($fee) && !empty($fee->cc) && !empty($fee->order_less)) {
            if ($cart_total < $fee->cc) {
                $order_less_credit_card_fee = $fee->order_less;
            }
        }
        return $order_less_credit_card_fee;
    }

//_getMinOrderCreditCardFee

    function _getPayMethodFee($data)
    {
        $res = $this->C_Model->getMinimumOrderFee();
        if (!empty($res)) {

            $cart_total = 0;
            if ($this->cart->contents()) {
                foreach ($this->cart->contents() as $items) {
                    if ($items['options']['loyalty'] != 'lp') {
                        $cart_total += $items['subtotal'];
                    }
                }
            }
//echo $data;
            if ($data == 'Credit Card Online') {
                if ($res->ccamt_flag == 'A') {
                    return ($res->ccamt == 0) ? 0 : $res->ccamt;
                }
                elseif ($res->ccamt_flag == 'P') {
                    return ($res->ccamt == 0) ? 0 : $cart_total / 100 * $res->ccamt;
                }
            }
            elseif ($data == 'Paypal') {
                if ($res->palamt_flag == 'A') {
                    return ($res->palamt == 0) ? 0 : $res->palamt;
                }
                elseif ($res->palamt_flag == 'P') {
                    return ($res->palamt == 0) ? 0 : $cart_total / 100 * $res->palamt;
                }
            }
        }
        else {
            return 0;
        }
    }

//getPaymentFee
    //calling  this function from checkout.js

    function placeOrder()
    {

        if ($this->input->post('order_data')) {

            $order_data   = $this->input->post('order_data', TRUE);
            var_dump($order_data );exit();
            //error_log('order_data: '. var_export($order_data, true));
            $this->load->model('OrderSession_Model', 'OSess');
            $os           = $this->OSess->retrieve($order_data['sessionId']);
            //var_dump($os);
            //print_r($order_data);   //exit;
            $order_option = $os->order_option;

            if (is_null($order_option) || $order_option == '') {
                $order_option                    = $order_data['order_option'];
            }
            $delivery_fee                    = 0;
            $delivery_fee_desc               = '';
            $minimum_order_delivery_fee      = 0;
            $minimum_order_delivery_fee_desc = '';
            $minimum_order_pickup_fee        = '';
            $minimum_order_pickup_fee_desc   = '';
            $public_holiday_fee_desc         = '';

            //calculating payment amount
            $cart_total     = 0;
            $loyalty_points = 0;
            foreach ($this->cart->contents() as $items) {
                if ($items['options']['loyalty'] != 'lp') {
                    $cart_total += $items['subtotal'];
                }

                if ($items['options']['loyalty'] == 'lp') {
                    $loyalty_points += $items['options']['product_points'];
                }
            }

            /* Getting public holiday fee if applicable */
            $public_holiday_fee = $this->C_Model->getPublicHolidayFee();
            if (!$public_holiday_fee) {
                $public_holiday_fee = 0.00;
            }
            else {
                $public_holiday_fee      = number_format(($cart_total / 100) * $public_holiday_fee, 2); //($cart_total/100)*$public_holiday_fee;
                $public_holiday_fee_desc = '<div class="mar ovfl-hidden">
                  <div class="fl"><b>Public Holiday Fee </b>:</div>
                  <div class="fr">$' . $public_holiday_fee/* number_format(($cart_total/100)*$public_holiday_fee, 2) */ . '</div>
                  </div>   ';
            }

            /* End  Getting public holiday fee if applicable */

            if ($order_option == 'D') {
                $delivery_fee      = $this->O_Model->getDeliveryFee();
                $delivery_fee_desc = '<div class="mar ovfl-hidden">
                  <div class="fl"><b>Delivery Fee </b>:</div>
                  <div class="fr">$' . number_format($delivery_fee, 2) . '</div>
                  </div>   ';

                $minimum_order_delivery_fee = $this->_getMinOrderDeliveryFee();
                if (!empty($minimum_order_delivery_fee)) {
                    $minimum_order_delivery_fee_desc = '<div class="mar ovfl-hidden">
                  <div class="fl"><b>Low Order Delivery Fee </b>:</div>
                  <div class="fr">$' . number_format($minimum_order_delivery_fee, 2) . '</div>
                  </div>   ';
                }
            }

            $payment_method = $os->payment;
            $later_order    = $order_data['later_order'];
            $order_date     = $order_data['order_date'];
            if (!$order_date) {
                $order_date = $os->order_date;
            }
            if ($later_order == 'later') {
                //VV $order_date = date('Y-m-d H:i:s', strtotime($os->order_date));
                $order_date = $order_data['order_date']; //VV the above line didn't work
                //error_log('order_date is ' . $order_data['order_date']);
            }
            else {
                $order_date = date('Y-m-d H:i:s');
            }

            //Checking if first order
            $user_id = $os->tmUserId;
            $user_id = intval($user_id);
            if ( 0 == $user_id ) {
                $user_id = $this->phpsession->get('tmUserId');
            }

            $usersInfo = $this->R_Model->getCustomerDetail((int) $user_id);

           $is_first_order = $usersInfo->is_first_order; //VV commented out
            //if ($is_first_order == 'Y') {
                //$this->O_Model->updateFirstOrder($this->phpsession->get('tmUserId'));
            //}
            //Checking if first order
            // print_r($order_data);
            //getting voucher and calculating discount if applicable
            //$voucher = (!empty($order_data['voucher'])) ? $order_data['voucher'] : '';

            $voucher_cookie = $os->tastycode;
            $voucher_code   = decode_voucher_cookie($voucher_cookie);
            $voucher        = $this->voucher_code->getCouponById((int) $voucher_code);

            $voucher_desc = '';
            $coupon_type  = '';
            $discount     = 0.00;
            //$voucher_code = '';
            if (is_object($voucher) && $voucher->coupontype == 'firstorder') {
                $coupon_type = 'firstorder';

                if ($is_first_order == 'Y') {//if applicable for first order
                    $discount_per = $voucher->discountper;
                    $discount     = $cart_total / 100 * $discount_per;
                    $cart_total   = $cart_total - $discount;
                    $voucher_desc = '<div class="mar ovfl-hidden">
                  <div class="fl"><i>First order discount </i>: <b>' . $discount_per . '%</b></div>
                  </div>   ';
                }
            }
            elseif (is_object($voucher) && ($voucher->coupontype == 'voucher' || $voucher->coupontype == 'discount' || $voucher->coupontype == 'freeproduct') && !empty($voucher_code)) {

                //$voucher_code = $order_data['voucher_code'];

                /**      code modified on 31 mar 2012  * */
                $VoucherDiscount = $this->O_Model->checkValidVoucher($voucher_code);
                error_log('discount voucher' . var_export($VoucherDiscount, true));
                if ($VoucherDiscount == 'invalid' || $VoucherDiscount == 'old' || $VoucherDiscount == 'expired') {
                    // Invalid voucher code
                    $coupon_type  = $VoucherDiscount;
		    $voucher_desc = '<div class="mar ovfl-hidden"><div class="fl"><b>Voucher Code:</b></div><div class="fr"> ' . $VoucherDiscount . '</div></div>';

                }//if
                else {

                    if ($VoucherDiscount->coupontype == 'discount') {
                        error_log('discount voucher' . var_export($VoucherDiscount, true));
                        $coupon_type  = 'discount';
                        $discount_per = $VoucherDiscount->discountper;
                        $discount     = $cart_total / 100 * $discount_per;
                        $cart_total   = $cart_total - $discount;
                        $voucher_desc = '<div class="mar ovfl-hidden">
                     <div class="fl"><b>Voucher Code</b>:</div>
                     <div class="fr"> ' . $VoucherDiscount->couponcode . '</div>
                     </div>
                     <div class="mar ovfl-hidden">
                     <div class="fl"><b>Discount </b>:</div>
                     <div class="fr"> ' . $discount_per . ' %</div>
                     </div>
                 <div class="mar ovfl-hidden">
                 <div>' . str_replace(array('\r', '\n'), '', htmlspecialchars_decode($VoucherDiscount->coupondescription, ENT_NOQUOTES)) . '</div>
                 </div>';
                    }
                    else if ($VoucherDiscount->coupontype == 'freeproduct') {
                        $coupon_type  = 'freeproduct';
                        //$freeProduct = $this->O_Model->getFreeProduct($voucher_code);
                        $voucher_desc = '<div class="mar ovfl-hidden">
                  <div class="fl"><b>Voucher Code</b>:</div>
                  <div class="fr"> ' . $VoucherDiscount->couponcode . '</div>
                  </div>
                  <div class="mar ovfl-hidden">
                  <div>' . str_replace(array('\r', '\n'), '', htmlspecialchars_decode($VoucherDiscount->coupondescription, ENT_NOQUOTES)) . '</div>
                  </div>';
                    }//
                }//else
            }
            elseif (is_object($voucher) && $voucher->coupontype == 'allorders') {
                $coupon_type  = 'allorders';
                $discount_per = $voucher->discountper;
                $discount     = $cart_total / 100 * $discount_per;
                $cart_total   = $cart_total - $discount;
                $voucher_desc = '<div class="mar ovfl-hidden">
                  <div class="fl"><i>Online order discount </i>: <b>' . $discount_per . '%</b></div>
                  </div>   ';
            }
            //VV START VOUCHER NOT BEING "0" when not entered *****NEED TO TEST****
           // elseif ($voucher_code ==0) {
           //    $coupon_type = '';
           // }
            //VV STOP VOUCHER NOT BEING "0" when not entered

            else {
                $coupon_type  = 'invalid';
                $discount_per = 0;
                $discount     = 0;
                $voucher_desc = '<div class="mar ovfl-hidden">
              <div class="fl"><b>Voucher Code:</b></div>
               <div class="fr"> ' . $os->discountName . '</div>
              </div>';
                $voucher_code = $os->discountName;
            }

            //end getting voucher and calculating discount if applicable
            $payment_method_fee            = 0;
            $payment_method_desc           = '';
            $minimum_order_credit_card_fee = 0;
            $minimum_order_paypal_fee      = 0;

            $minimum_order_credit_card_fee_desc = '';
            $minimum_order_paypal_fee_desc      = '';

            // echo $payment_method = $order_data['payment_method'];
            if ($payment_method == 'Paypal' || $payment_method == 'Credit Card Online' || $payment_method == 'Credit Card Over Phone') {
                $res = $this->C_Model->getMinimumOrderFee();

                if ($payment_method == 'Credit Card Online' || $payment_method == 'Credit Card Over Phone') {
                    $payment_method_fee = $this->_getPayMethodFee('Credit Card Online');

                    $payment_method_desc = '<div class="mar ovfl-hidden">
                  <div class="fl"><b>Credit Card fee </b></div>
                  <div class="fr"> $' . number_format($payment_method_fee, 2) . '</div>
                  </div>   ';

                    /* $minimum_order_credit_card_fee = $this->_getMinOrderCreditCardFee();
                      if(!empty($minimum_order_credit_card_fee))
                      {
                      $minimum_order_credit_card_fee_desc = '<div class="mar ovfl-hidden">
                      <div class="fl"><b>Min Order Credit Card Fee </b>:</div>
                      <div class="fr">$'.$minimum_order_credit_card_fee.'</div>
                      </div>   ';
                      } */
                }
                elseif ($payment_method == 'Paypal') {
                    //$payment_method_fee = $res->palamt;
                    $payment_method_fee  = $this->_getPayMethodFee('Paypal');
                    $payment_method_desc = '<div class="mar ovfl-hidden">
                  <div class="fl"><b>Paypal fee </b></div>
                  <div class="fr"> $' . number_format($payment_method_fee, 2) . '</div>
                  </div>   ';

                    /* $minimum_order_paypal_fee = $this->_getMinOrderPaypalFee();
                      if(!empty($minimum_order_paypal_fee))
                      {
                      $minimum_order_paypal_fee_desc = '<div class="mar ovfl-hidden">
                      <div class="fl"><b>Min Order Paypal Fee </b>:</div>
                      <div class="fr">$'.$minimum_order_paypal_fee.'</div>
                      </div>   ';
                      }
                     */
                }
            }

            $payment_amount = $cart_total + $delivery_fee + $payment_method_fee + $minimum_order_delivery_fee + $minimum_order_paypal_fee + $minimum_order_credit_card_fee + $public_holiday_fee;
            //end calculating payment amount

            /* Getting the order points for each order if enable */
            $sitesettings  = $this->SS_Model->getSiteSettingsDetails();
            // print_r($sitesettings);
            $points_enable = $sitesettings[12];
            if ($points_enable == 'enable' && $sitesettings[17] == 'enable') {
                $order_points = $sitesettings[11];
            }
            else {
                $order_points = 0;
            }
            /* End Getting the order points for each order if enable */
            if (isset($order_data['order_comments'])) {
                $order_comment = $order_data['order_comments'];
                if (!empty($order_comment)) {
                    $order_comment = '<div class="mar ovfl-hidden">
                  <div><b>ORDER COMMENTS: </b>: <i>' . trim($order_comment) . '</i></div>
                  </div>';
                }
            }
            else {
                $order_comment = '';
            }

           

            $order_description                 = $this->_getOrderDescription() . $order_comment . $voucher_desc . $public_holiday_fee_desc . $delivery_fee_desc . $minimum_order_delivery_fee_desc . $minimum_order_paypal_fee_desc . $minimum_order_credit_card_fee_desc . $payment_method_desc; //getting order description
            $product_detail                    = $this->_getProductDetails();

            if (
                    ( is_string($voucher_code) && '0' == $voucher_code )  ||
                    ( is_numeric($voucher_code) && 0 == $voucher_code )
               )
            {
                $voucher_code = '';
                $coupon_type = ''; //VV
            }


            $order_table_data                  = array(
                'userid'                    => $user_id,
                //VV
                'real_id'                   => $data['order_number'] = $this->O_Model->getOrderNumber(),
                //VV
                'payment_method'            => $payment_method,
                'payment_amount'            => $payment_amount,
                'order_option'              => $order_option,
                'order_description'         => $order_description,
                'order_comment'             => $order_comment,
                'points_earned'             => $order_points, // ($loyalty_points==0)?
                'points_used'               => $loyalty_points,
                'coupon_type'               => $coupon_type,
                'voucher_code'              => $voucher_code,
                'discount'                  => $discount,
                'delivery_fee'              => $delivery_fee,
                'min_order_delivery_fee'    => $minimum_order_delivery_fee,
                'min_order_paypal_fee'      => $minimum_order_paypal_fee,
                'min_order_credit_card_fee' => $minimum_order_credit_card_fee,
                'public_holiday_fee'        => $public_holiday_fee,
                'order_date'                => $order_date,
                'order_placement_date'      => date('Y-m-d H:i:s')
            );
//error_log('payment: ' . var_export($order_table_data, true));
            // print_r($order_table_data);         die;
            ($loyalty_points == 0) ? $order_table_data['points_earned'] = $order_points
                        : $order_table_data['points_earned'] = 0;

            if ($payment_method == 'Paypal' || $payment_method == 'Credit Card Online') {
                $this->_saveToSession($order_table_data);
                //$this->_sendToPaypal();
                $success = false;
                if ($payment_method == 'Credit Card Online') {
                    $success = $this->creditCardPayment(array(
                        'amount'     => $payment_amount,
                        'cardNumber' => trim($order_data['card_number']),
                        'cvv'        => $order_data['cvv'],
                        'cardType'   => strtoupper($order_data['type']),
                        'expiry'     => $order_data['expiry'],
                        'userID'     => $order_table_data['userid'],
                    ));
                    error_log('cc payment success' . var_export($success, true));
                    if ($success[0] === true) {
                        $this->_updateUserPoints($order_points, $loyalty_points);
                        $this->OSess->remove($order_data['sessionId']);
                        $this->paymentSuccessful($order_table_data, $success[1]);
                    }
                    else {
                        $this->paymentUnsuccessful($success);
                    }
                }

                if ($payment_method == 'Paypal') {
                    error_log('payment: paypal');
                    //echo json_encode(array('result' => 'send'));
                    echo 'send';
                }

                //print_r($order_table_data);
            }
            else {
                if ($sitesettings[17] == 'enable') {
                    $this->_updateUserPoints($order_points, $loyalty_points);
                }
                $this->OSess->remove($order_data['sessionId']);
                $this->paymentSuccessful($order_table_data);
            }
        }
       
    }

    private function creditCardPayment(array $post_data)
    {
        $settings = $this->SS_Model->getSiteSettingsDetails();
        // build the reques

        $param_str          = array();
        $fparams            = array();
        $fparams['METHOD']  = urlencode('DoDirectPayment');
        $fparams['VERSION'] = urlencode('78');

        foreach ($fparams as $key => $value) {
            $param_str[] = "{$key}={$value}";
        }

        $params              = array();
        $params['USER']      = urlencode($settings[25]);
        $params['PWD']       = urlencode($settings[26]);
        $params['SIGNATURE'] = urlencode($settings[27]);

        $params['PAYMENTACTION']  = urlencode('Sale');
        $params['IPADDRESS']      = urlencode($_SERVER['REMOTE_ADDR']);
        // VV $params['AMT']            = urlencode($post_data['amount']);
        $params['AMT']            = urlencode(round($post_data['amount'],2)); //VV must be rounded to .00
        $params['ACCT']           = urlencode($post_data['cardNumber']);
        $params['CREDITCARDTYPE'] = urlencode(ucfirst($post_data['cardType']));
        $params['CVV2']           = urlencode($post_data['cvv']);
        $params['EXPDATE']        = urlencode($post_data['expiry']);
        $params['COUNTRYCODE']    = urlencode('AU');
        $params['CURRENCYCODE']   = urlencode('AUD');

        $user = $this->U_Model->getUserById((int) $post_data['userID']);
        $this->load->model('suburb_model', 'suburb');

        $suburb = $this->suburb->getSuburbById($user->suburb);
        $state  = $this->S_Model->getStatebyId($user->state);


        $params['FIRSTNAME'] = $user->first_name;
        $params['LASTNAME']  = $user->last_name;
        $params['STREET']    = $user->address;
        $params['CITY']      = $suburb->suburb_name; //suburb lookup
        $params['STATE']     = $state->state_name; //state lookup

        //VV ZIP CODE START
        //VV $params['ZIP']       = $user->zipcode; //get zip code //VV ORIG
        $params['ZIP']       = trim($suburb->suburb_name);
        $params['ZIP']       = substr($params['ZIP'], -4); //derive zip code from suburb (last 4 digits)
       // error_log('zip code is '. $params['ZIP']);
        //VV ZIP CODE STOP

        foreach ($params as $key => $value) {
            $param_str[] = "{$key}={$value}";
        }

        try {
            $response = $this->processResponse($this->makeRequest($settings[24], join('&', $param_str)));
        }
        catch (Exception $e) {
            error_log($e->getMessage());
        }

        error_log('cc response: ' . var_export($response, true));

        if ("SUCCESS" == strtoupper($response["ACK"]) || "SUCCESSWITHWARNING" == strtoupper($response["ACK"])) {
            return array(true, $response);
        }
        return array(false, $response);
        // process curl request here
    }

    private function processResponse($response)
    {
        if (!$response)
            throw new Exception('no response returned');

        $respArray = explode('&', $response);

        $parsedResponse = array();
        foreach ($respArray as $part) {
            $t = explode('=', $part);
            if (sizeof($t) > 1) {
                $parsedResponse[$t[0]] = $t[1];
            }
        }

        if (0 == sizeof($parsedResponse) || !array_key_exists('ACK', $parsedResponse)) {
            throw new Exception('No response');
        }

        return $parsedResponse;
    }

    private function makeRequest($api_url, $data)
    {
        $curl = curl_init();

        curl_setopt($curl, CURLOPT_URL, $api_url);
        curl_setopt($curl, CURLOPT_VERBOSE, 1);

        // Turn off the server and peer verification (TrustManager Concept).
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);

        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_TIMEOUT, 100);

        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);

        $response = curl_exec($curl);
        if (!$response) {
            throw new Exception("failed: " . curl_error($curl) . '(' . curl_errno($curl) . ')');
        }
        return $response;
    }

    private function paymentUnsuccessful($data)
    {
        echo json_encode($data);
        error_log('payment unsuccessful');
    }

    private function paymentSuccessful($order_table_data, $cc_response = null)
    {
        $order_id = $this->O_Model->addShoppingOrder($order_table_data);

        $shopping_cart_data = $this->_getShoppingCartData($order_id);

        //print_r($shopping_cart_data);

        $order_payment_data = array('order_id'       => $order_id,
            'user_id'        => $this->phpsession->get('tmUserId'),
            'transaction_id' => (!is_null($cc_response)) ? $cc_response['TRANSACTIONID']
                    : '',
            'payment_status' => (!is_null($cc_response)) ? 'Paid' : 'Pending',
            'payment_date'   => (!is_null($cc_response)) ? standard_date() : '');

        $this->O_Model->addOrderPayment($order_payment_data);

        $res = $this->O_Model->addShoppingCartData($shopping_cart_data);
        if ($res) {
            //echo  'not';
            //$user_id = $this->phpsession->get('tmUserId');
            //$o_tdata = $this->phpsession->get('order_'.$user_id);
            //print_r($o_tdata);

            $this->_createPdfData($order_id);
            $this->_sendPdfMail();

            if (!is_null($cc_response)) {


                $siteDomainBack = $this->config->item('back_url') . 'order/payment/success';

                //header("Location: order/payment/success");
                header("Location:{$siteDomainBack}", 302);
                exit;
                //$this->payment('success');
            }
        }
    }

//end placeOrder

    function _updateUserPoints($order_points = 0, $loyalty_points = 0)
    {

        //  echo $order_points;
        //adding order point for per order
        if ($loyalty_points == 0) {
            $this->O_Model->updateUserOrderPoints($this->phpsession->get('tmUserId'), '+' . $order_points);
        }
        //subtracting order points use for loyalty program
        $this->O_Model->updateUserOrderPoints($this->phpsession->get('tmUserId'), '-' . $loyalty_points);
        return true;
    }

    function _updateUserLoyaltyPoints()
    {
        $sitesettings = $this->SS_Model->getSiteSettingsDetails();
        if ($sitesettings[17] == 'enable') {
            $user_id          = $this->phpsession->get('tmUserId');
            $order_table_data = $this->phpsession->get('order_' . $user_id); // print_r($order_table_data);
            $order_points     = $order_table_data['points_earned'];
            $loyalty_points   = $order_table_data['points_used'];
            ($order_points) ? $op               = $order_points : $op               = 0;
            ($loyalty_points) ? $lp               = $loyalty_points : $lp               = 0;
            //echo $loyalty_points;die;
            $this->_updateUserPoints($op, $lp);
        }
        return true;
    }

    function sendToPaypal()
    {
        $this->load->library('Paypal');
        $site_details = $this->SS_Model->getSiteSettingsDetails();
        // Specify your paypal email
        //$this->paypal->addField('business','r.mukesh@agiletechnosys.com');
        $this->paypal->addField('business', $site_details[6]);
        // $this->paypal->addField('business', 'r.muke_1243416712_per@agiletechnosys.com');
        // Specify the currency
        $this->paypal->addField('currency_code', 'AUD');

        //$this->paypal->addField('return', site_url('/order/payment/success'));
        $this->paypal->addField('return', $this->config->item('back_url') . 'order/payment/success');
        //$this->paypal->addField('return', site_url('/'));
        $this->paypal->addField('cancel_url', site_url('/order/payment/fail'));
        // Specify the url where paypal will send the IPN
        $this->paypal->addField('notify_url', site_url('/payment.php')); //  site_url('/order/payment/action/success'));
        //$this->paypal->addField('notify_url',site_url('/order/payment/action/success'));
        //$this->paypal->addField('notify_url',site_url('/order/payment/action/success'));
        //adding cart items
        if ($this->cart->contents()) {
            $i = 1;
            foreach ($this->cart->contents() as $items) {
                if ($items['options']['loyalty'] != 'lp') {//if product having non zero price i.e not from loyalty product
                    $this->paypal->multi_items('true');
                    if ($items['options']['product_type'] == 'half_half') {
                        $this->paypal->addField('item_name_' . $i, 'Half & Half Pizza');
                    }
                    else {
                        $this->paypal->addField('item_name_' . $i, ucwords($items['name']));
                    }


                    $this->paypal->addField('item_number_' . $i, $i);
                    $this->paypal->addField('amount_' . $i, $items['price']);
                    $this->paypal->addField('quantity_' . $i, $items['qty']);
                    $i++;
                }
            }
            $user_id          = $this->phpsession->get('tmUserId');
            //$order_table_data = array('payment_method',);
            $order_table_data = $this->phpsession->get('order_' . $user_id);

            if (!empty($order_table_data['discount'])) {
                $this->paypal->addField('discount_amount_1', $order_table_data['discount']);
            }
            //$this->paypal->addField('tax_cart', $order_table_data['delivery_fee']);
        }

        $payment_method_fee = 0;

        $res = $this->C_Model->getMinimumOrderFee();

        $payment_method_fee = $this->_getPayMethodFee('Paypal');

        $total_extra_charges = $payment_method_fee + @$order_table_data['delivery_fee'] + @$order_table_data['min_order_delivery_fee'] + @$order_table_data['min_order_paypal_fee'] + @$order_table_data['min_order_credit_card_fee'] + @$order_table_data['public_holiday_fee'];

        $this->paypal->addField('handling_cart', $total_extra_charges);

        // Specify any custom value
        // $this->paypal->addField('custom', 'muri-khao');
        // Enable test mode if needed
        $this->paypal->enableTestMode();

        // Let's start the train!
        error_log('paypal: ' . var_export($this->paypal, true));
        $this->paypal->submitPayment();

        //echo 'hello';
    }

//end _sendToPaypal

    function payment($action = null)
    {//echo "hi"; die;
        /* 	if($this->input->post('txn_id')!='')
          { */

        $uname = get_cookie('tmEmail');
        $upass = get_cookie('tmPassword');

        if ( '' != $uname && '' != $upass && !$this->phpsession->get('tmUserId') )
        {
            $usersInfo = array();
            $usersInfo = $this->R_Model->checkLogin($uname, md5($upass));
            if ( $usersInfo != FALSE && $usersInfo->status != 'inactive' )
            {
                $this->phpsession->save('tmusrLgn', TRUE);
                $this->phpsession->save('tmUserId', $usersInfo->userid);
                $this->phpsession->save('tmFirstName', $usersInfo->first_name);
                $this->phpsession->save('tmLastName', $usersInfo->last_name);
                $this->phpsession->save('tmEmail', $usersInfo->email);
                $this->phpsession->save('tmPassword', $usersInfo->password);
                $this->phpsession->save('tmAddress_one', $usersInfo->address);
                $this->phpsession->save('tmUserName', $usersInfo->email);
                $this->phpsession->save('session_demo_username', $usersInfo->email);
            }
        }

        $this->load->library('Paypal'); //echo $this->phpsession->get('tmUserId');die;
        if ($this->phpsession->get('tmUserId')) {
            // use to show Paersonal information

            $usersInfo = $this->R_Model->getCustomerDetail($this->phpsession->get('tmUserId'));
            $userid    = $this->phpsession->get('tmUserId');

            if (!empty($usersInfo) && !empty($usersInfo->is_first_order)) {
                $data['is_first_order']        = $usersInfo->is_first_order;
                //stored personal information
                $data['personal_info']['info'] = $this->R_Model->getInfo($userid);
                //  $data['personal_info']['sub']=$data['suburb'];
            }

            // use to show Paersonal information
            if (is_null($action))
                $action = $this->uri->segment(4);

            $data['payment_status'] = $action;

            /**   chk whether this is user's first order to give points to the referring user  * */
            $data['is_first_order'] = 'Y';
            // echo $this->phpsession->get('tmUserId'); die;
            //checking if first order when customer is login
            if ($this->phpsession->get('tmUserId')) {
                $usersInfo = $this->R_Model->getCustomerDetailsOfreferrer($this->phpsession->get('tmUserId')); //echo $this->db->last_query();
                //    echo "<pre>"; print_r($usersInfo);

                $userid = $this->phpsession->get('tmUserId');
                if (!empty($usersInfo)) {// && !empty($usersInfo->is_first_order)) //echo "in"; die;
                    //$data['is_first_order'] = $usersInfo->is_first_order;
                    if ($usersInfo['is_first_order'] == 'Y') {//echo "in3"; die;
                        /**    getting referrer person data    * */

                        $rfrrow = $this->db->select('value')->get_where('tbl_ref_friend', array('type' => 'ref_person_inform'))->row();
                        if (!empty($rfrrow)) {
                            $referrer_person_mail_content = $rfrrow->value;
                        }
                        $rfrrow_points                = $this->db->select('value')->get_where('tbl_ref_friend', array('type' => 'referring_point'))->row();
                        if (!empty($rfrrow_points)) {
                            $referrer_person_points = $rfrrow_points->value;
                        }
                        $this->R_Model->_addReferrerPoints($usersInfo['referrer_userid'], $referrer_person_points, $usersInfo['referred_userid'], '');  // update referrer points as usr placing order

                        /**   sending mail to referrer   * */
                        $admin_email = $this->R_Model->getAdminEmails();
                        $site        = $this->SE_Model->getSiteTitle(); //site title

                        $this->email->from($admin_email->value, $site->value);
                        $this->email->to($usersInfo['referrer_email']);
                        //$this->email->cc('p.sudhakar@agiletechnosys.com');
                        $subject = 'Refer Friend';
                        $this->email->subject($subject);

                        // $message = str_replace("[[first_name]]", $reffereInfo->first_name, str_replace('\n','',htmlspecialchars_decode($referrer_person_mail_content)));

                        $message = str_replace("[[first_name]]", $usersInfo['referrer_fname'], html_entity_decode($referrer_person_mail_content));

                        $message = str_replace("[[last_name]]", $usersInfo['referrer_lname'], $message);
                        $message = str_replace("[[points]]", $referrer_person_points, $message);
                        $message = str_replace("[[referred_person]]", $usersInfo['referred_fname'] . ' ' . $usersInfo['referred_lname'], $message);

                        $emailPath      = $this->config->item('base_abs_path') . "templates/" . $this->config->item('base_template_dir');
                        $email_template = file_get_contents($emailPath . '/email/email.html');

                        $email_template = str_replace("[[EMAIL_HEADING]]", $subject, $email_template);
                        $email_template = str_replace("[[EMAIL_CONTENT]]", nl2br(utf8_encode($message)), $email_template);
                        $email_template = str_replace("[[SITEROOT]]", $this->config->item('base_url2'), $email_template);
                        $email_template = str_replace("[[LOGO]]", $this->config->item('base_url2') . "templates/" . $this->config->item('base_template_dir'), $email_template);
                        //   print_r($email_template);
                        // echo $this->email->print_debugger();die;
                        $this->email->message(htmlspecialchars_decode(($email_template)));

                        if (!$this->email->send()) {
                            // Generate error
                            echo $this->email->print_debugger();
                        }
                        else {
                            unset($email_template);
                            $this->email->clear(TRUE);
                        }
                        /* done sending mail to reffereer */
                        //echo $this->email->print_debugger();
                    }
                }
            }//if
//echo "in2"; die;

            $this->O_Model->updateFirstOrder($this->phpsession->get('tmUserId'));

            //display thank you page
            $row                 = $this->CM_Model->getCmsById(1);
            //--title heading of page
            $data['title']       = $row->title;
            //--description/content of page
            $data['description'] = $row->description;
            $data['objMinOrder'] = $this->C_Model->getMinimumOrderFee();

            switch ($action) {
                case 'notify':
                    $this->load->view($this->config->item('base_template_dir') . '/front/payment/payment', $data);
                    break;

                case 'success':
                    $this->_updateUserLoyaltyPoints();
                    $this->_addOrderDetails();

                    $meta_tags = $this->MT_Model->getMetaById(4);
                    if ($meta_tags) {
                        $data['meta_title']       = $meta_tags->title;
                        $data['meta_description'] = $meta_tags->description;
                        $data['meta_keyword']     = $meta_tags->keywords;
                    }
                    $data['suburbs']          = $this->R_Model->getSuburb();

                    $data['state']         = $this->S_Model->getStateComboByCountryId();
                    $data['min_order_fee'] = $this->C_Model->getMinimumOrderFee();

                    /* $this->config->set_item('base_url', "https://203.98.91.56/tastypizza/");
                      $this->config->set_item('base_css_dir', $this->config->item('base_url') . "templates/default/css/");
                      $this->config->set_item('base_images_dir', $this->config->item('base_url') . "templates/default/images/");
                      $this->config->set_item('base_js_dir', $this->config->item('base_url') . "templates/default/js/"); */

                    $this->load->view($this->config->item('base_template_dir') . '/front/payment/payment', $data);
                    break;

                case 'fail':  //display order failed page
                    $rows                     = $this->CM_Model->getCmsById(2);
                    //--title heading of page
                    $data['page_title']       = $rows->title;
                    //--description/content of page
                    $data['page_description'] = $rows->description;

                    $meta_tags = $this->MT_Model->getMetaById(5);
                    if ($meta_tags) {
                        $data['meta_title']       = $meta_tags->title;
                        $data['meta_description'] = $meta_tags->description;
                        $data['meta_keyword']     = $meta_tags->keywords;
                    }

                    $this->load->view($this->config->item('base_template_dir') . '/front/payment/order_failed', $data);
                    break;

                case 'notpay':$this->load->view($this->config->item('base_template_dir') . '/front/payment/payment', $data);
                    break;
            }
        }
        /* 	}//if
          else
          redirect('/'); */
    }

//end payment
//adding order data after notify
    function _addOrderDetails()
    { //print_r($_GET);die;
        $user_id = $this->phpsession->get('tmUserId');

        $order_table_data = $this->phpsession->get('order_' . $user_id);
       
        if (!empty($order_table_data)) {
            $arr = serialize($_POST);
            $this->O_Model->insertPaypalTxnData($arr);

            $order_id = $this->O_Model->addShoppingOrder($order_table_data);

            $transaction_id = 0;
            $payment_status = 'pending';
            $payment_date   = date('Y-m-d H:i:s');

            if ($this->input->post('txn_id') != '') {
                $transaction_id = $this->input->post('txn_id');
            }

            if ($this->input->post('payment_status') != '') {
                $payment_status = $this->input->post('payment_status');
            }

            if ($this->input->post('payment_date') != '') {
                $payment_date       = $this->input->post('payment_date');
            }
            //  echo $order_id;
            $order_payment_data = array('order_id'       => $order_id,
                'user_id'        => $user_id,
                'transaction_id' => $transaction_id,
                'payment_status' => $payment_status,
                'payment_date'   => $payment_date);

            //  echo "in payment()->";   print_r($order_payment_data);
            $order_payment_id = $this->O_Model->addOrderPayment($order_payment_data);

            //die;
            //echo $order_table_data['points_earned']
            //adding order point for per order
            //     $this->O_Model->updateUserOrderPoints($user_id,$order_table_data['points_earned']);
            //subtracting order points use for loyalty program
            //  $this->O_Model->updateUserOrderPoints($user_id,'-'.$order_table_data['points_used']);

            $shopping_cart_data = $this->_getShoppingCartData($order_id);

            //print_r($shopping_cart_data);
            $res = $this->O_Model->addShoppingCartData($shopping_cart_data);

            $this->_createPdfData($order_id);
            $this->_sendPdfMail();
            $this->phpsession->clear('order_' . $user_id);
            // echo $order_id;
            return true;
        }
    }

//_addOrderDetails

    function _saveToSession($order_table_data)
    {
        $user_id = $this->phpsession->get('tmUserId');
        $this->phpsession->save('order_' . $user_id, $order_table_data);
        return true;
    }

//end _saveToSession

    function _createPdfData($order_id)
    {
        // qr class
        require_once($this->config->item('base_abs_path_common') . 'application/libraries/phpqrcode/phpqrcode.php');

        $delivery_fee    = 0;
        $credit_card_fee = 0;

        /* Getting the order number set by admin */
        $data['order_number'] = $this->O_Model->getOrderNumber();

        /* updating order number set by admin */
        //VV moved from the bottom. so the same number is not picked up by a following order while processign this one
        $this->O_Model->updateOrderNumber();

        /* user detail */
        $userid              = $this->phpsession->get('tmUserId');
        $usersInfo           = $this->R_Model->getCustomerDetail($userid);
        $data['cust_detail'] = '<b>' . $usersInfo->first_name . ' ' . $usersInfo->last_name . '<br/>%company%' . $usersInfo->address . '<br/>' . $usersInfo->suburb_name . '<br/>Mobile #: ' . $usersInfo->mobile . '</b>';
        $data['cust_name']   = $usersInfo->first_name . ' ' . $usersInfo->last_name; //VV
        $data['cust_detail_head'] = $usersInfo->first_name . ' ' . $usersInfo->last_name . ', %company%' . $usersInfo->address . ', ' . $usersInfo->suburb_name . ', Mobile #: ' . $usersInfo->mobile;

        if ($usersInfo->company_name) {
            $data['cust_detail']      = str_replace('%company%', "{$usersInfo->company_name}<br/>", $data['cust_detail']);
            $data['cust_detail_head'] = str_replace('%company%', "{$usersInfo->company_name}, ", $data['cust_detail_head']);
        }
        else {
            $data['cust_detail']      = str_replace('%company%', "", $data['cust_detail']);
            $data['cust_detail_head'] = str_replace('%company%', "", $data['cust_detail_head']);
        }

        $data['cust_mobile']      = $usersInfo->mobile;
        //$data['customer_address'] = $usersInfo->address;
        // create qr code
        $data['cust_address']     = $usersInfo->address . ', ' . $usersInfo->suburb_name . ', ' . $usersInfo->zipcode;
        $data['p_cust_address']     = $usersInfo->address . '\n' . $usersInfo->suburb_name; //VV for gprs printer
        //VV $data['google_maps_url']  = 'http://maps.google.co.uk/maps?q=' . urlencode($data['cust_address']);
        $url = 'http://maps.google.com.au/maps?q=' . urlencode($data['cust_address']); // VV
        $data['google_maps_url']  = $this->shortUrl($url); //VV - short URL
        $data['qrcode_image_url'] = $this->config->item('base_upload_dir') . 'qrcode/' . $order_id . '.png';
        QRcode::png($data['google_maps_url'], $this->config->item('base_abs_path') . 'uploads/qrcode/' . $order_id . '.png', QR_ECLEVEL_L, 3, 0); //3 is size
        //VV QRcode::png($data['google_maps_url'], $this->config->item('qr_upload_dir') . $order_id . '.png', QR_ECLEVEL_L, 2, 0);

        /* customer address */

        $suburb       = explode(' ', strrev($usersInfo->suburb_name), 2);
        $site_details = $this->SS_Model->getSiteSettingsDetails();

        if (!empty($suburb[1])) {
            $cust_addr = $usersInfo->address . ', ' . strrev($suburb[1]) . ', ' . strrev($suburb[0]) . ', ' . $usersInfo->code . '.';//VV . $site_details[14];
        }
        else {
            $cust_addr = $usersInfo->address . ', ' . strrev($suburb[0]) . ', ' . $usersInfo->code . '.';//VV . $site_details[14];
        }

        $data['customer_address'] = $cust_addr;
        //350 5th Ave, New York, NY, 10118
        /* end customer address */

        /* Getting Map and Driving instruction flag */
        $data['map_flag']                 = $site_details[15];
        $data['driving_instruction_flag'] = $site_details[16];
        $data['qr_code']                  = $site_details[23];

        /* end Getting Map and Driving instruction flag */

        /* Shop address */

        $data['shop_address'] = $site_details[13];
        $data['orderid']      = $order_id;
        $data['user_id']      = $userid;

        /* customer pdf text */
        $pdftext = $this->T_Model->getUserTextDetails();
        if (!empty($pdftext)) {
            $data['email_forward']  = $pdftext[10];
            $data['email_appendix'] = $pdftext[11];
        }

        /* end customer pdf text */

        /* Getting site title */

        $data['site_title']      = $site_details[2];
        $data['restaurant_name'] = $site_details[21];

        $res = $this->O_Model->getOrderByOrderId($order_id);

        // print_r($res);
        /* Getting order details */
        $data['ordered_date']   = date('d/m/y, H:i', strtotime($res->order_placement_date));
        $data['order_get_date'] = date('d/m/y, H:i', strtotime($res->order_date));
        $data['p_order_get_date'] =date('D d/m H:i', strtotime($res->order_date)); //VV GPRS Printer
        if (strtotime($res->order_placement_date) == strtotime($res->order_date)) {
            $data['order_get_date'] = 'ASAP';
            $data['p_order_get_date'] = 'ASAP'; //VV GPRS Printer
        }

        $data['order_date_desc'] = '';

        $data['order_option'] = $res->order_option;
        $data['discount']     = $res->discount;
        $data['coupon_type']  = $res->coupon_type;

        //echo $res->voucher_code;
        if (!empty($res->voucher_code)) {
            $data['voucher_code'] = $res->voucher_code;
        }

        $data['delivery_fee']           = $res->delivery_fee;
        $data['min_order_delivery_fee'] = $res->min_order_delivery_fee;

        $data['min_order_paypal_fee']      = $res->min_order_paypal_fee;
        $data['min_order_credit_card_fee'] = $res->min_order_credit_card_fee;
        $data['public_holiday_fee']        = $res->public_holiday_fee;

        $data['all_extra_fees']            =$data['delivery_fee'] +$data['min_order_delivery_fee']+$data['min_order_paypal_fee']+$data['min_order_credit_card_fee']+$data['public_holiday_fee']; //VV for GPRS PRINTER
        $data['delivery_fee_desc']           = '';
        $data['min_order_delivery_fee_desc'] = '';

        $data['min_order_paypal_fee_desc']      = '';
        $data['min_order_credit_card_fee_desc'] = '';
        $data['public_holiday_fee_desc']        = '';

        $data['delivery_addr_desc'] = '';

        /* holiday fee desc if present */

        if (!empty($data['public_holiday_fee']) && $data['public_holiday_fee'] != 0) {
            $data['public_holiday_fee_desc'] = '
            <tr>
               <td width="300" align="left" valign="top">&nbsp;</td>
               <td width="150" height="20" align="left" valign="top">Public Holiday fee:</td>
               <td width="100" align="right" valign="top">$' . $data['public_holiday_fee'] . '</td>
            </tr>';
        }

        /* order option */
        if ($data['order_option'] == 'D') {
            $data['order_option'] = 'Home Delivery';

            $data['order_date_desc'] = '<tr>
               <td height="20" align="left" valign="top" width="750" style="text-align:right">
                  <strong>Delivery Time: ' . $data['order_get_date'] . ' </strong>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
               </td>
            </tr>';

            if (!empty($data['delivery_fee']) && $data['delivery_fee'] != 0) {
                $data['delivery_fee_desc']  = '
                  <tr>
                     <td width="300" align="left" valign="top">&nbsp;</td>
                     <td width="150" height="20" align="left" valign="top">Delivery fee:</td>
                     <td width="100" align="right" valign="top">$' . $data['delivery_fee'] . '</td>
                  </tr>';
                $data['delivery_addr_desc'] = '<tr>
                                             <td width="750" height="20" align="left" valign="middle" style="">Delivery Address:</td>
                                          </tr>
                                          <tr>
                                             <td width="750" align="left" valign="top">' . $data['cust_detail'] . '
                                             </td>
                                          </tr>';
            //VV "} at wrong place - see bellow"
             }
                if (!empty($data['min_order_delivery_fee']) && $data['min_order_delivery_fee'] != 0) {
                    $data['min_order_delivery_fee_desc'] = '
                  <tr>
                     <td width="300" align="left" valign="top">&nbsp;</td>
                     <td width="150" height="20" align="left" valign="top">Low Order Amount fee:</td>
                     <td width="100" align="right" valign="top">$' . $data['min_order_delivery_fee'] . '</td>
                  </tr>';
                }
          //VV }
        }
        elseif ($data['order_option'] == 'P') {
            $data['order_option']    = 'Pickup';
            $data['order_date_desc'] = '<tr>
               <td height="20" align="left" valign="top" width="750" style="text-align:right">
                  <strong>Pickup Time: ' . $data['order_get_date'] . ' </strong>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
               </td>
            </tr>';
        }

        /* payment method */
        $payment_method         = $res->payment_method;
        $data['payment_method'] = $payment_method;

        if ($payment_method == 'Credit Card Over Phone') {
            //VV need to fix $data['payment_method'] = $payment_method . " - please call customer to take their card number";
            $data['payment_method'] = "Credit Card on Delivery";

        }

        $credit_card_fee          = 0;
        $data['credit_card_desc'] = '';
        if ($payment_method == 'Paypal' OR $payment_method == 'Credit Card Online' OR $payment_method == 'Credit Card Over Phone') {

            $data['paid_or_not'] = 'PAID';

            if ($payment_method == 'Credit Card Over Phone') {$data['paid_or_not'] = 'NOT PAID';} // VV setting CC over phone as NOT PAID

            $res1                = $this->C_Model->getMinimumOrderFee();

            if ($payment_method == 'Credit Card Online' OR $payment_method == 'Credit Card Over Phone') {
                //$credit_card_fee = $res->ccamt;
                $credit_card_fee          = number_format($this->_getPayMethodFee('Credit Card Online'), 2);
                $data['all_extra_fees']   = $data['all_extra_fees'] + $credit_card_fee; //VV for gprs printer
                $data['credit_card_desc'] = '
               <tr>
                  <td width="300" align="left" valign="top">&nbsp;</td>
                  <td width="150" height="20" align="left" valign="top">Credit Card fee:</td>
                  <td width="100" align="right" valign="top">$' . $credit_card_fee . '</td>
               </tr>';
                /* if(!empty($data['min_order_credit_card_fee']) && $data['min_order_credit_card_fee'] != 0)
                  {
                  $data['min_order_credit_card_fee_desc'] = '
                  <tr>
                  <td width="300" align="left" valign="top">&nbsp;</td>
                  <td width="150" height="20" align="left" valign="top">Min Order Credit Card fee:</td>
                  <td width="100" align="right" valign="top">$'.$data['min_order_credit_card_fee'].'</td>
                  </tr>';
                  } */
            }
            elseif ($payment_method == 'Paypal') {
                //$credit_card_fee = $res->palamt;
                $credit_card_fee          = $this->_getPayMethodFee('Paypal');
                $data['all_extra_fees'] =   $data['all_extra_fees'] + $credit_card_fee; //VV for gprs printer
                $data['credit_card_desc'] = '
               <tr>
                  <td width="300" align="left" valign="top">&nbsp;</td>
                  <td width="150" height="20" align="left" valign="top">Paypal fee:</td>
                  <td width="100" align="right" valign="top">$' . number_format($credit_card_fee, 2) . '</td>
               </tr>';
                /*    if(!empty($data['min_order_paypal_fee']) && $data['min_order_paypal_fee'] != 0)
                  {
                  $data['min_order_paypal_fee_desc'] = '
                  <tr>
                  <td width="300" align="left" valign="top">&nbsp;</td>
                  <td width="150" height="20" align="left" valign="top">Min Order Paypal fee:</td>
                  <td width="100" align="right" valign="top">$'.$data['min_order_paypal_fee'].'</td>
                  </tr>';
                  } */
            }
        }
        else {
            $data['paid_or_not'] = 'NOT PAID';
        }

        /* discount if any */

        $data['discount_desc'] = '';
        $discount              = 0;
        $data['free_product']  = '&nbsp;';
        $vdisc                 = $this->O_Model->getCouponDiscDescription($res->voucher_code);
        $vdata                 = $this->voucher_code->getCouponById($res->voucher_code);


        //if (!is_null($vdata)) { //VV failed when a number was entered as an unknown coupon
        if (is_object($vdata)) { //VV
            //error_log('VDATA IS ' . print_r($vdata));
            $data['coupon_type']  = $vdata->coupontype;
            $data['voucher_code'] = $vdata->couponcode;

        }

        // echo $this->db->last_query();
        // print_r($vdisc);die;
        //if($vdisc) $vdisc->coupondescription;
        /* if(!empty($data['discount']) && !empty($data['coupon_type']) && $data['discount']!= 0)
          {
          $discount = $data['discount'];
          $data['discount_desc'] = '
          <tr>
          <td width="300" align="left" valign="top">&nbsp;</td>
          <td width="150" height="20" align="left" valign="top">'.ucfirst($data['coupon_type']).':</td>
          <td width="100" align="right" valign="top">-$'.$data['discount'].'</td>
          </tr>';
          $data['free_product'] = 'Voucher Code: <b>'.$res->voucher_code.'</b>'.str_replace(array('\n','\r'),'', htmlspecialchars_decode($vdisc, ENT_NOQUOTES));
          }
          if(!empty($data['voucher_code']) &&  $data['coupon_type'] == 'freeproduct')
          {
          $free_product = $this->O_Model->getFreeProductDescription($data['voucher_code']);
          if($free_product)
          {
          $data['free_product'] = 'Voucher Code: <b>'.$data['voucher_code'].'</b>'.str_replace(array('\r','\n'),'',htmlspecialchars_decode($free_product,ENT_NOQUOTES));
          }
          else
          {
          //$data['free_product'] = 'Voucher Code: <b>'.$data['voucher_code'].'</b> Invalid voucher<br /><br />';
          $data['free_product'] = 'Voucher Code: <b>'.$data['voucher_code'].'</b><br />';
          }
          } */
        $data['p_discount']=''; //VV for gprs printer
        error_log('coupon type: ' . var_export($data, true));
        if ($data['coupon_type'] == 'firstorder') {
            $discount              = $data['discount'];
            $data['discount_desc'] = '
            <tr>
               <td width="300" align="left" valign="top">&nbsp;</td>
               <td width="150" height="20" align="left" valign="top">' . ucfirst($data['coupon_type']) . ':</td>
               <td width="100" align="right" valign="top">-$' . $data['discount'] . '</td>
            </tr>';
            $vdiscrptn             = str_replace(array('\n', '\r'), '', htmlspecialchars_decode($vdisc, ENT_NOQUOTES));
            //$data['free_product'] = 'Voucher Code: <b>'.$res->voucher_code.'</b> '.$vdiscrptn;//.trim($vdiscrptn, '<p></p>');
             $data['p_discount'] = 'First Order Discount:  -$'.$data['discount']; //VV for gprs printer
        }//
        if ($data['coupon_type'] == 'allorders') {
            $discount              = $data['discount'];
            $data['discount_desc'] = '
            <tr>
               <td width="300" align="left" valign="top">&nbsp;</td>
               <td width="150" height="20" align="left" valign="top">Online order discount:</td>
               <td width="100" align="right" valign="top">-$' . $data['discount'] . '</td>
            </tr>';
            $vdiscrptn             = str_replace(array('\n', '\r'), '', htmlspecialchars_decode($vdisc, ENT_NOQUOTES));
            //$data['free_product'] = 'Voucher Code: <b>'.$res->voucher_code.'</b> '.$vdiscrptn;//.trim($vdiscrptn, '<p></p>');
            $data['p_discount'] = 'Online Order Discount:  -$'.$data['discount']; //VV for gprs printer
        }//

         $VoucherDiscount = $this->O_Model->checkValidVoucher2($res->voucher_code);  //VV
         error_log('VoucherDiscountXX is ' . var_export($VoucherDiscount, true));
        if (!empty($data['voucher_code']) && $data['coupon_type'] == 'discount' && $VoucherDiscount != 'old') { //&& $VoucherDiscount != 'old' VV added && $VoucherDiscount != 'old' to remove "Voucher..." from pdf when old is used
            $discount              = $data['discount'];
            $data['discount_desc'] = '
            <tr>
               <td width="300" align="left" valign="top">&nbsp;</td>
               <td width="150" height="20" align="left" valign="top">' . ucfirst($data['coupon_type']) . ':</td>
               <td width="100" align="right" valign="top">-$' . $data['discount'] . '</td>
            </tr>';
            $vdiscrptn             = str_replace(array('\n', '\r'), '', htmlspecialchars_decode($vdisc, ENT_NOQUOTES));
            $data['free_product']  = 'Voucher Code: <b>' . $vdata->couponcode . '</b> ' . $vdiscrptn; //.trim($vdiscrptn, '<p></p>');
            $data['p_discount'] = strtoupper($vdata->couponcode) .'-'. $vdiscrptn.'  -$'.$data['discount']; //VV for gprs printer

        }//

        if (!empty($data['voucher_code']) && $data['coupon_type'] == 'freeproduct' && $VoucherDiscount != 'old') { //&& $VoucherDiscount != 'old' VV added && $VoucherDiscount != 'old' to remove "Voucher..." from pdf when old is used
            $free_product = $this->O_Model->getFreeProductDescription($data['voucher_code']);
            if ($free_product) {
                $vdiscrptn            = str_replace(array('\r', '\n'), '', htmlspecialchars_decode($free_product, ENT_NOQUOTES));
                $data['free_product'] = 'Voucher Code:<b>'.$data['voucher_code'] . '</b> ' . $vdiscrptn; //.trim($vdiscrptn, '<p></p>');
                $data['p_discount'] = strtoupper($data['voucher_code']).'-'. $vdiscrptn; //VV for gprs printer
            }
        }//
        if ($data['coupon_type'] == 'invalid') {// || $data['coupon_type'] == '')
            if (isset($data['voucher_code']) && (bool) $data['voucher_code'] !== false) {
                $data['free_product'] = '<b>Voucher Code: </b>' . $data['voucher_code'] . '<br />';
                $data['p_discount'] = strtoupper($data['voucher_code']); //VV for gprs printer
            }
        }//
        //calculating payment amount
        $cart_total           = 0;
        $loyalty_points       = 0;
        foreach ($this->cart->contents() as $items) {
            if ($items['options']['loyalty'] != 'lp') {
                $cart_total += $items['subtotal'];
            }

            if ($items['options']['loyalty'] == 'lp') {
                $loyalty_points += $items['options']['product_points'];
            }
        }
        //end calculating payment amount
        $data['order_comment'] = (!empty($res->order_comment)) ? strip_tags($res->order_comment, "<b>,<i>,<strong>,<em>")
                : '&nbsp;';
        $data['total_amount']  = $res->payment_amount; // $cart_total + $credit_card_fee + $data['delivery_fee'] - $discount + $data['min_order_delivery_fee'] + $data['min_order_paypal_fee'] + $data['min_order_credit_card_fee'] + $data['public_holiday_fee'];

        /* Product Item description */
        $data['items_desc'] = ' <tr>
         <td align="left" valign="top" width="750">
            <table width="550" border="0" cellspacing="0" cellpadding="0" style="font:normal 14px Arial">
               <tr>
                  <td width="750" align="left" valign="top">
                     <table width="550" border="0" cellspacing="0" cellpadding="0">
                        <tr>
                        <td width="425" height="20" align="left" valign="top" >Item(s) ordered:</td>
                        <td align="left" valign="top" width="125">&nbsp;</td>
                        </tr>
                     </table>
                  </td>
               </tr>' . $this->_getItemsDescription();

        $data['items_desc_customer'] = ' <tr>
         <td align="left" valign="top" width="750">
            <table width="550" border="0" cellspacing="0" cellpadding="0" style="font:normal 14px Arial">
               <tr>
                  <td width="750" align="left" valign="top">
                     <table width="550" border="0" cellspacing="0" cellpadding="0">
                        <tr>
                           <td width="425" height="20" align="left" valign="top" >Item(s)ordered:</td>
                           <td align="left" valign="top" width="125">&nbsp;</td>
                        </tr>
                     </table>
                  </td>
               </tr>' . $this->_getItemsDescriptionCustomer();
        /* End Product Item description */

        $data['txt_file_item_desc'] = $this->_getTextFileItemsDescription();
        $data['p_txt_file_item_desc'] = $this->p_getTextFileItemsDescription(); //for gprs printer
       // print_r($data);//die;
        $this->gprs_printer($data); //record data to dbs for gprs printer
        $this->load->view($this->config->item('base_template_dir') . '/front/order_history/pdf_order', $data);
//die;
        /* adding rest pdf file name to mast_order table */
        $rest_pdf_file              = $this->phpsession->get('restaurant_pdf_' . $this->phpsession->get('tmUserId'));
        $this->O_Model->addPdfToOrder($order_id, $rest_pdf_file);

        /* updating order number set by admin */
        //VV moved to top righ after a new number is assigned so the same number is not picked up by a following order while processign this one
        //$this->O_Model->updateOrderNumber();
    }

//_createPdfData
    //VV GPRS PRINTER
    function gprs_printer($data)
    {
        $p_rest_id='#RestID*';
        $p_delivery_or_pickup=strtoupper($data['order_option']).'*';
        $p_order_number=$data['order_number'].'*';
        //$p_items='|1|Sliced beef sliced very very long title |7.10|>SIZE:Large(+10.00)>NO:Capsicum>EXTRA:Basil(+1.50)>Comments:Extra crispy please and easy on cheese;|2|Pork|5.50|*';
        $p_items=substr($data['p_txt_file_item_desc'], 0, -1); //remove last ";"
        $p_items=$p_items.'*';
        $p_discount=$data['p_discount'].';';
        $p_total_amount='$'.$data['total_amount'].';;';
        if($p_delivery_or_pickup=='HOME DELIVERY*') {$p_cust_name=$data['cust_name'].'\n'.$data['p_cust_address'].';';} else {$p_cust_name=$data['cust_name'].';';$p_delivery_or_pickup='IN-STORE PICKUP*';}
        $p_asap_or_later=$data['p_order_get_date'].';';
        $p_deliver_and_other_fees='$'.number_format($data['all_extra_fees'], 2) .';';
        $p_paid_or_not=$data['paid_or_not'];
        $p_payment_method=$data['payment_method'].';';
        $p_cust_mobile=$data['cust_mobile'].'*';
        $p_order_comment=strip_tags($data['order_comment']);
        $p_order_comment=trim(str_replace('ORDER COMMENTS: :','',$p_order_comment));
        $p_order_comment=str_replace('&nbsp;','',$p_order_comment).'*';
        if ($p_order_comment=='*') $p_order_comment=='';
        $p_order_received_at='ORDER RECEIVED: '. date("H:i m-d").'*';
        $p_part_order='#'; //add "PART1/2 if neccessary - TO DO LATER"
        $p_printer_data=$p_rest_id.$p_delivery_or_pickup.$p_order_number.$p_items.$p_discount.$p_total_amount.$p_cust_name.$p_asap_or_later.$p_deliver_and_other_fees.$p_paid_or_not.' - '.$p_payment_method.$p_cust_mobile.$p_order_comment.$p_order_received_at.$p_part_order;
        $p_printer_data=strip_tags($p_printer_data);
        $p_printer_data=trim(preg_replace('/\s+/', ' ', $p_printer_data)); //remove line breaks

        $sitesettings  = $this->SS_Model->getSiteSettingsDetails();
        $sent_by_gsm_printer= $sitesettings[49];
        if($sent_by_gsm_printer=='Y') {$p_status='to_be_printed';} else {$p_status='no_gprs_print';}
        $this->O_Model->recordPrinterData($data['order_number'],$p_printer_data, $p_status);

        $text_file_path = $this->config->item('base_abs_path').'uploads/printer_files/'.$data['order_number'].'_'.urlencode($data['restaurant_name']).'.txt';
        if ( ! write_file($text_file_path, $p_printer_data))
        {
            //echo 'Unable to write the file'; die;
        }
    } //VV end function gprs printer

    private function _sendPdfMail()
    {
        //  return;
        $userid    = $this->phpsession->get('tmUserId');
        $usersInfo = $this->R_Model->getCustomerDetail($userid);

        //VV $admin_email          = $this->SE_Model->getAdminEmails();
        //VV $site                 = $this->SE_Model->getSiteTitle(); //site title
        //getting site details
        $site_details         = $this->SE_Model->getSiteDetails(); //site title
        //getting confirmation mail id
        $confirmation_emailid = $site_details[18];
        $send_by_email = $site_details[29]; //VV
        $send_by_fax = $site_details[30]; //VV
        //$send_by_sms = $site_details[31]; //VV
        $admin_email = $site_details[5]; //VV
        $site = $site_details[2]; //VV

        //$this->load->library('email');

        /**   sending EMAIL to customer * */
        if (1==1) { //VV change to emailorder='yes';
        //VV$this->email->from($admin_email->value, $site->value);
        $this->email->from($admin_email, $site);

        $this->email->to($usersInfo->email);

        if ($send_by_email=='Y') {$this->email->bcc($confirmation_emailid);} //VV confirmation email = restaurant's email at this moment - need to change it
        // VV $this->email->cc($confirmation_emailid);

        $subject               = 'Thank You for your Order';
        $this->email->subject($subject);
        $customer_mail_content = $this->SE_Model->getEmailById(30);

        $message = str_replace("[[first_name]]", $usersInfo->first_name, str_replace('\n', '', htmlspecialchars_decode($customer_mail_content->message)));
        $message = str_replace("[[last_name]]", $usersInfo->last_name, $message);

        $cust_order_data = $this->phpsession->get('customer_pdf_' . $this->phpsession->get('tmUserId'));
        $message         = str_replace("[[order]]", $cust_order_data, $message);
        /* refer friend link */
        //$message = str_replace("[[link]]",'<a href ='.site_url('refer/friend/'.$usersInfo->mobile).'>Refer Friend </a>', $message);
        $message         = str_replace("[[link]]", '<a href =' . site_url($usersInfo->mobile) . '>Refer Friend </a>', $message);

        $emailPath      = $this->config->item('base_abs_path') . "templates/" . $this->config->item('base_template_dir');
//    $email_template =  file_get_contents($emailPath.'/email/email.html');
        $email_template = file_get_contents($emailPath . '/email/customer_order_mail.html');
        // $email_template = str_replace('\n', '', $email_template);
        //$email_template = str_replace('<br>','', $email_template);

        $email_template = str_replace("[[EMAIL_HEADING]]", $subject, $email_template);
        $email_template = str_replace("[[EMAIL_CONTENT]]", (utf8_encode($message)), $email_template);
        $email_template = str_replace("[[SITEROOT]]", $this->config->item('base_url2'), $email_template);
        $email_template = str_replace("[[LOGO]]", $this->config->item('base_url2') . "templates/" . $this->config->item('base_template_dir'), $email_template);
        $this->email->message(htmlspecialchars_decode(($email_template)));

        // echo $email_template; die;

        @$this->email->send();

        /** done sending mail to customer * */
        }
        /* Sending Confirmation SMS to restaurant */ //VV
        $this->_sendConfirmationSMS($usersInfo->mobile);

        /* sending mail to restuarant */

        /* Getting restaurant mail address */
        $restaurant_email = $this->O_Model->getRestuarantEmail();
        /* Getting restaurant mail address */

        if ($restaurant_email && $send_by_fax=='Y') {//if restuarant email is set //VV
            /* Getting FAX details */
            $fax = $this->SE_Model->getFaxDetails();

            /* Sending Fax to restaurant with pdf order */
            $this->email->from($fax['sending_address']);
            $this->email->to($fax['fax_number'] . '@' . $fax['domain_name']);
            //$this->email->cc('test.testemail1@gmail.com');
            $this->email->subject($fax['subject']);
            $body           = $fax['body'];
            $email_template = nl2br(utf8_encode($body));
            $this->email->message(htmlspecialchars_decode(($email_template)));

            $rest_pdf_file       = $this->phpsession->get('restaurant_pdf_' . $this->phpsession->get('tmUserId'));
            $rest_order_pdf_path = $this->config->item('base_abs_path') . 'uploads/restaurant_order_pdf/' . $rest_pdf_file;
            //$rest_order_pdf_path = $this->config->item('pdf_upload_dir') . $rest_pdf_file;

            $this->email->attach($rest_order_pdf_path);
            @$this->email->send();
            /* End Sending Fax */
        }

        /* done sending mail to restuarant */
        $this->_clearAll();
        return true;
    }

//_sendPdfMail

    private function _clearAll()
    {
        /* clearing file name */
        $this->phpsession->clear('customer_pdf_' . $this->phpsession->get('tmUserId'));
        $this->phpsession->clear('restaurant_pdf_' . $this->phpsession->get('tmUserId'));
        /* clearing order table data */
        $user_id = $this->phpsession->get('tmUserId');
        $this->phpsession->clear('order_' . $user_id);
        $this->phpsession->clear('order_number_' . $user_id);
        delete_cookie('order_option');
        delete_cookie('order_date');
        delete_cookie('tastycode');
        delete_cookie('payment');
        delete_cookie('deliveryType');

        /* destroying cart */
        $this->cart->destroy();
    }

// end  _clearAll

    function _sendConfirmationSMS($cust_mobile)
    {

        $sms = $this->SE_Model->getSMSDetails();
        $site_details         = $this->SE_Model->getSiteDetails();
        $send_by_sms = $site_details[31]; //VV
        $restaurant_name = $site_details[21]; //VV
        $order_number = $this->phpsession->get('order_number_' . $this->phpsession->get('tmUserId')); //VV
        if (!empty($sms) && $sms['sms_confirmation'] == 'enable') {
           //VV $order_number = $this->phpsession->get('order_number_' . $this->phpsession->get('tmUserId'));
            $this->email->from($sms['sending_address']);
            $this->email->to($sms['mob_number'] . '@' . $sms['domain_name']);
            //VV$this->email->cc('test.testemail1@gmail.com');
            $this->email->subject($sms['subject']);
            $sms_content = $sms['confirmation_text'];
            $message     = str_replace("[[order_no]]", $order_number, str_replace('\n', '', htmlspecialchars_decode($sms_content)));
            $message     = str_replace("[[customer_number]]", $cust_mobile, $message);
            $email_template = utf8_encode($message);
            $this->email->message(htmlspecialchars_decode(($email_template)));
            $this->email->send();
            //VV return true;
        }
        //VV SENDING ORDER BY SMS (EMAIL) START
        if (!empty($sms) && $send_by_sms=='Y'){ //SENDING TXT ORDER BY EMAIL (SMS)
            $this->email->from($sms['sending_address']);
            $this->email->to($sms['mob_number'] . '@' . $sms['domain_name']);
            //VV$this->email->cc('test.testemail1@gmail.com');
            $this->email->subject($sms['subject']);
            $rest_order_txt = $this->config->item('base_abs_path') . 'uploads/text_version_order/' . $order_number . '_'.$restaurant_name.'.txt';
            $handle = fopen($rest_order_txt, "r");
            $sms_contents = "New ".fread($handle, filesize($rest_order_txt));
           // $sms_content =readfile($rest_order_txt);
            //$message = htmlspecialchars_decode($sms_contents);
            $message = strip_tags($sms_contents);
            $email_template = utf8_encode($message);
            $this->email->message(htmlspecialchars_decode(($email_template)));
            $this->email->send();

        }
        //VV SENDING ORDER BY SMS (EMAIL) STOP
        return true;
    }

//_sendConfirmationSMS
    //getting the products details for non loyalty product use to send to paypal
    function _getProductDetails()
    {
        $product = array();
        if ($this->cart->contents()) {
            foreach ($this->cart->contents() as $items) {
                if ($items['options']['loyalty'] != 'lp') {

                    $product[] = array('product_name' => ucwords($items['name']),
                        'quantity'     => $items['qty'],
                        'price'        => $items['price']);
                }
            }
        }

        return $product;
    }

//_getProductDetails

    private function _getTextFileItemsDescription()
    {

        $order = '';
        if ($this->cart->contents()) {
            foreach ($this->cart->contents() as $items) {
                if ($items['options']['product_type'] == 'single') {

                    $remove          = '';
                    $extra           = '';
                    $comment         = '';
                    $variation_group = '';

                    if (!empty($items['options']['variation_group'])) {
                        $var_group = unserialize($items['options']['variation_group']);
                        foreach ($var_group as $key => $val) {

                            $variation_group .= ucwords(strtoupper($val['variation_group'])) . ': ' . ucwords($val['variation_name']) . '+' . number_format($val['variation_price'], 2) . ',';
                        }
                    }
                    if (!empty($items['options']['comment'])) {
                        $comment = 'Comment: ' . $items['options']['comment'];
                    }
                    if (!empty($items['options']['extra'])) {
                        $extra_ing = unserialize($items['options']['extra']);
                        foreach ($extra_ing as $val) {

                            $extra .= 'EXTRA: ' . ucwords($val->ingredient_name) . '+' . number_format($val->price, 2);
                        }
                    }

                    if (!empty($items['options']['current'])) {
                        $current_ing = unserialize($items['options']['current']);
                        foreach ($current_ing as $val) {
                            $remove .= 'NO: ' . ucwords($val->ingredient_name) . ',';
                        }
                    }

                    $order .= $items['qty'] . 'x' . ucwords($items['name']) . '(' . $variation_group . $remove . $extra . $comment . '),';
                }
                elseif ($items['options']['product_type'] == 'half_half') {
                    $comment = '';
                    if (!empty($items['options']['comment'])) {
                        $comment       = 'Comment: ' . $items['options']['comment'];
                    }
                    $first_product = unserialize($items['options']['first_product']);

                    $first_product_variation_group = '';
                    if (!empty($first_product['variation_group'])) {
                        $var_group = unserialize($first_product['variation_group']);
                        foreach ($var_group as $key => $val) {

                            $first_product_variation_group .= ucwords(strtoupper($val['variation_group'])) . ': ' . ucwords($val['variation_name']) . '+' . number_format($val['variation_price'] / 2, 2) . ',';
                        }
                    }
                    $first_product_current_options = '';
                    if (!empty($first_product['current'])) {
                        $current_ing = unserialize($first_product['current']);
                        foreach ($current_ing as $val) {
                            $first_product_current_options .= 'NO: ' . ucwords($val->ingredient_name) . ',';
                        }
                    }
                    $first_product_extra_options = '';
                    if (!empty($first_product['extra'])) {
                        $extra_ing = unserialize($first_product['extra']);
                        foreach ($extra_ing as $val) {
                            $first_product_extra_options .= 'EXTRA: ' . ucwords($val->ingredient_name) . '+' . number_format($val->price / 2, 2);
                        }
                    }

                    $second_product                 = unserialize($items['options']['second_product']);
                    $second_product_variation_group = '';
                    if (!empty($second_product['variation_group'])) {
                        $var_group = unserialize($second_product['variation_group']);
                        foreach ($var_group as $key => $val) {

                            $second_product_variation_group .= ucwords(strtoupper($val['variation_group'])) . ': ' . ucwords($val['variation_name']) . '+' . number_format($val['variation_price'] / 2, 2) . ',';
                        }
                    }
                    $second_product_current_options = '';
                    if (!empty($second_product['current'])) {
                        $current_ing = unserialize($second_product['current']);
                        foreach ($current_ing as $val) {
                            $second_product_current_options .= 'NO: ' . ucwords($val->ingredient_name) . ',';
                        }
                    }
                    $second_product_extra_options = '';
                    if (!empty($second_product['extra'])) {
                        $extra_ing = unserialize($second_product['extra']);
                        foreach ($extra_ing as $val) {
                            $second_product_extra_options .= 'EXTRA: ' . ucwords($val->ingredient_name) . '+' . number_format($val->price / 2, 2);
                        }
                    }

                    $order .= $items['qty'] . 'x' . ucwords($items['name']) . '(First Half ' . ucwords($first_product['product_name']) . '
                             (' . $first_product_variation_group . $first_product_current_options . $first_product_extra_options . '),
                             Second Half ' . ucwords($second_product['product_name']) . '(' . $second_product_variation_group . $second_product_current_options . $second_product_extra_options . ')'
                        . $comment . '),';
                }//end elseif
            }
        }

        return $order;
    }

//end _getTextFileItemsDescription

//_getProductDetails

 //VV for GPRS printer - almost identical to getTextFileItemsDescription()
    private function p_getTextFileItemsDescription()
    {

        $order = '';
        if ($this->cart->contents()) {
            foreach ($this->cart->contents() as $items) {
                if ($items['options']['product_type'] == 'single') {

                    $remove          = '';
                    $extra           = '';
                    $comment         = '';
                    $variation_group = '';

                    if (!empty($items['options']['variation_group'])) {
                        $var_group = unserialize($items['options']['variation_group']);
                        foreach ($var_group as $key => $val) {


                            if (ucwords(strtoupper($val['variation_group']))=="SIZE" || number_format($val['variation_price'], 2)=='0.00'){
                              $variation_group .= '>'.ucwords(strtoupper($val['variation_group'])) . ': ' . ucwords($val['variation_name']);
                            }
                            else
                            {
                              $variation_group .= '>'.ucwords(strtoupper($val['variation_group'])) . ': ' . ucwords($val['variation_name']) . '(+' . number_format($val['variation_price'], 2) . ')';
                            }



                           // $variation_group .= '>'.ucwords(strtoupper($val['variation_group'])) . ':' . ucwords($val['variation_name']) . '(+' . number_format($val['variation_price'], 2) . ')';

                        }
                    }
                    if (!empty($items['options']['comment'])) {
                        $comment = '>COMMENTS: ' . $items['options']['comment'];
                    }
                    if (!empty($items['options']['extra'])) {
                        $extra_ing = unserialize($items['options']['extra']);
                        foreach ($extra_ing as $val) {

                            $extra .= '>EXTRA: ' . ucwords($val->ingredient_name) . '(+' . number_format($val->price, 2).')';
                        }
                    }

                    if (!empty($items['options']['current'])) {
                        $current_ing = unserialize($items['options']['current']);
                        foreach ($current_ing as $val) {
                            $remove .= '>NO: ' . ucwords($val->ingredient_name) . ',';
                        }
                    }

                    $item_price=number_format($items['qty']*$items['price'],2);
                    $order .= '|'.$items['qty'] . '|' . strtoupper($items['name']) .'|'.$item_price.'|'.$variation_group . $remove . $extra . $comment.';';

                    //VV OK $order .= '|'.$items['qty'] . '|' . ucwords($items['name']) .'|'.number_format($items['price'],2).'|'.$variation_group . $remove . $extra . $comment.';';
                    //orig$order .= '|'.$items['qty'] . '|' . ucwords($items['name']) . '(' . $variation_group . $remove . $extra . $comment . '),';

                }
                elseif ($items['options']['product_type'] == 'half_half') {
                    $comment = '';
                    if (!empty($items['options']['comment'])) {
                        $comment       = '\nCOMMENTS: ' . $items['options']['comment'];
                    }
                    $first_product = unserialize($items['options']['first_product']);
                    //error_log('ITEMS DATA IS '. var_dump($items));
                    //   error_log('ITEMS DATA IS: ' . print_r($items));

                    //number_format($first_product['default_price'] / 2, 2)

                    $first_product_variation_group = '';
                    if (!empty($first_product['variation_group'])) {
                        $var_group = unserialize($first_product['variation_group']);
                        foreach ($var_group as $key => $val) {

                            if(ucwords(strtoupper($val['variation_group']))=='SIZE' || number_format($val['variation_price'] / 2, 2) =='0.00') {
                             $first_product_variation_group .= '>'. ucwords(strtoupper($val['variation_group'])) . ': ' . ucwords($val['variation_name']);

                            }
                            else {
                              $first_product_variation_group .= '>'. ucwords(strtoupper($val['variation_group'])) . ': ' . ucwords($val['variation_name']) . '(+' . number_format($val['variation_price'] / 2, 2) . ')';

                            }
                        }
                    }
                    $first_product_current_options = '';
                    if (!empty($first_product['current'])) {
                        $current_ing = unserialize($first_product['current']);
                        foreach ($current_ing as $val) {
                            $first_product_current_options .= '>NO: ' . ucwords($val->ingredient_name);
                        }
                    }
                    $first_product_extra_options = '';
                    if (!empty($first_product['extra'])) {
                        $extra_ing = unserialize($first_product['extra']);
                        foreach ($extra_ing as $val) {
                            $first_product_extra_options .= '>EXTRA: ' . ucwords($val->ingredient_name) . '(+' . number_format($val->price / 2, 2).')';

                        }
                    }

                    $second_product                 = unserialize($items['options']['second_product']);
                    $second_product_variation_group = '';
                    if (!empty($second_product['variation_group'])) {
                        $var_group = unserialize($second_product['variation_group']);
                        foreach ($var_group as $key => $val) {

                            if(ucwords(strtoupper($val['variation_group']))=='SIZE') {
                             $second_product_variation_group .= '>'. ucwords(strtoupper($val['variation_group'])) . ': ' . ucwords($val['variation_name']);
                            }
                            else {
                              $second_product_variation_group .= '>'. ucwords(strtoupper($val['variation_group'])) . ': ' . ucwords($val['variation_name']) . '(+' . number_format($val['variation_price'] / 2, 2) . ')';
                            }
                        }
                    }
                    $second_product_current_options = '';
                    if (!empty($second_product['current'])) {
                        $current_ing = unserialize($second_product['current']);
                        foreach ($current_ing as $val) {
                            $second_product_current_options .= '>NO: ' . ucwords($val->ingredient_name);
                        }
                    }
                    $second_product_extra_options = '';
                    if (!empty($second_product['extra'])) {
                        $extra_ing = unserialize($second_product['extra']);
                        foreach ($extra_ing as $val) {
                            $second_product_extra_options .= '>EXTRA: ' . ucwords($val->ingredient_name) . '(+' . number_format($val->price / 2, 2).')';
                        }
                    }

                    $order .= '|'.$items['qty'] . '|HALF & HALF PIZZA' . '||'. '\n1st Half: ' . strtoupper($first_product['product_name']) . $first_product_variation_group . $first_product_current_options . $first_product_extra_options . '\n2nd Half: ' . strtoupper($second_product['product_name']) .  $second_product_variation_group . $second_product_current_options .  $second_product_extra_options .$comment.';';

                   // $order .= '|'.$items['qty'] . '|'. ucwords($items['name']) . '(First Half ' . ucwords($first_product['product_name']) . '
                   //          (' . $first_product_variation_group . $first_product_current_options . $first_product_extra_options . '),
                   //          Second Half ' . ucwords($second_product['product_name']) . '(' . $second_product_variation_group . $second_product_current_options . $second_product_extra_options . ')'
                   //     . $comment . '),';
                }//end elseif
            }
        }

        return $order;
    }

//VV for printer end _getTextFileItemsDescription

    function _getOrderDescription()
    {

        $order = '';
        if ($this->cart->contents()) {
            foreach ($this->cart->contents() as $items) {
                if ($items['options']['product_type'] == 'single') {

                    $remove          = '';
                    $extra           = '';
                    $comment         = '';
                    $variation_group = '';

                    if (!empty($items['options']['variation_group'])) {
                        $var_group = unserialize($items['options']['variation_group']);
                        foreach ($var_group as $key => $val) {

                            $variation_group .= '<div class="mar ovfl-hidden" style="margin-bottom:0px;">
                                       <div class="fl">* ' . ucwords(strtoupper($val['variation_group'])) . ':  ' . ucwords($val['variation_name']) . '</div>
                                       </div>';
                        }
                    }
                    if (!empty($items['options']['comment'])) {
                        $comment = '- Comment: ' . $items['options']['comment'] . '<br />';
                    }
                    if (!empty($items['options']['extra'])) {
                        $extra_ing = unserialize($items['options']['extra']);
                        foreach ($extra_ing as $val) {

                            $extra .= '<div class="mar ovfl-hidden" style="margin-bottom:0px;">
                                       <div class="fl">+ EXTRA:  ' . ucwords($val->ingredient_name) . '</div>
                                       </div>';
                        }
                    }

                    if (!empty($items['options']['current'])) {
                        $current_ing = unserialize($items['options']['current']);
                        foreach ($current_ing as $val) {
                            $remove .= '<div class="mar ovfl-hidden" style="margin-bottom:0px;">
                                       <div class="fl"> - NO:  ' . ucwords($val->ingredient_name) . '</div>
                                       <div class="fr"></div>
                                       </div>';
                        }
                    }

                    $order .= '
                  <div class="mar ovfl-hidden">
                  <div class="fl"><strong>' . ucwords($items['name']) . '</strong></div>
                  </div>
                  <div class="mar ovfl-hidden">
                  <div class="fl">* Qty :' . $items['qty'] . '</div>
                  <div class="fr"></div>
                  </div>
                  <div class="mar smlTxt ovfl-hidden">
                  ' . $variation_group . '
                  </div>
                  <div class="mar smlTxt ovfl-hidden">
                  ' . $remove . '
                  ' . $extra . '
                  ' . $comment . '
                  </div>';
                }
                elseif ($items['options']['product_type'] == 'half_half') {
                    $comment = '';
                    if (!empty($items['options']['comment'])) {
                        $comment       = '- Comment: ' . $items['options']['comment'] . '<br />';
                    }
                    $first_product = unserialize($items['options']['first_product']);

                    $first_product_variation_group = '';
                    if (!empty($first_product['variation_group'])) {
                        $var_group = unserialize($first_product['variation_group']);
                        foreach ($var_group as $key => $val) {

                            $first_product_variation_group .= '<div class="mar ovfl-hidden" style="margin-bottom:0px;">
                                          <div class="fl">* ' . ucwords(strtoupper($val['variation_group'])) . ':  ' . ucwords($val['variation_name']) . '</div>
                                             </div>';
                        }
                    }
                    $first_product_current_options = '';
                    if (!empty($first_product['current'])) {
                        $current_ing = unserialize($first_product['current']);
                        foreach ($current_ing as $val) {
                            $first_product_current_options .= '<div class="mar ovfl-hidden" style="margin-bottom:0px;">
                                          <div class="fl"> - NO:  ' . ucwords($val->ingredient_name) . '</div>
                                          <div class="fr"></div>
                                          </div>';
                        }
                    }
                    $first_product_extra_options = '';
                    if (!empty($first_product['extra'])) {
                        $extra_ing = unserialize($first_product['extra']);
                        foreach ($extra_ing as $val) {

                            $first_product_extra_options .= '<div class="mar ovfl-hidden" style="margin-bottom:0px;">
                                          <div class="fl">+ EXTRA:  ' . ucwords($val->ingredient_name) . '</div>
                                                                              </div>';
                        }
                    }

                    $second_product                 = unserialize($items['options']['second_product']);
                    $second_product_variation_group = '';
                    if (!empty($second_product['variation_group'])) {
                        $var_group = unserialize($second_product['variation_group']);
                        foreach ($var_group as $key => $val) {

                            $second_product_variation_group .= '<div class="mar ovfl-hidden" style="margin-bottom:0px;">
                                          <div class="fl">* ' . ucwords(strtoupper($val['variation_group'])) . ':  ' . ucwords($val['variation_name']) . '</div>

                                          </div>';
                        }
                    }
                    $second_product_current_options = '';
                    if (!empty($second_product['current'])) {
                        $current_ing = unserialize($second_product['current']);
                        foreach ($current_ing as $val) {
                            $second_product_current_options .= '<div class="mar ovfl-hidden" style="margin-bottom:0px;">
                                          <div class="fl"> - NO:  ' . ucwords($val->ingredient_name) . '</div>
                                          <div class="fr"></div>
                                          </div>';
                        }
                    }
                    $second_product_extra_options = '';
                    if (!empty($second_product['extra'])) {
                        $extra_ing = unserialize($second_product['extra']);
                        foreach ($extra_ing as $val) {

                            $second_product_extra_options .= '<div class="mar ovfl-hidden" style="margin-bottom:0px;">
                                          <div class="fl">+ EXTRA:  ' . ucwords($val->ingredient_name) . '</div>

                                          </div>';
                        }
                    }

                    $order .=
                        '<div class="mar ovfl-hidden">
                        <div class="fl"><strong>Half & Half Pizza</strong></div>
                        </div>

                        <div class="mar ovfl-hidden">
                        <div class="fl">* Qty :' . $items['qty'] . '</div>
                        <div class="fr"></div>
                        </div>

                        <div class="mar ovfl-hidden">
                        <div class="fl"><span  style="color:#CC3333;font-style:italic;">First Half</span> ' . ucwords($first_product['product_name']) . '</div>
                        </div>


                     <div class="mar smlTxt ovfl-hidden">
                     ' . $first_product_variation_group . '
                     </div>
                     <div class="mar smlTxt ovfl-hidden">
                     ' . $first_product_current_options . '
                     ' . $first_product_extra_options . '
                     </div>

                     <div class="mar ovfl-hidden">
                     <div class="fl"><span  style="color:#CC3333;font-style:italic;">Second Half</span>
                     ' . ucwords($second_product['product_name']) . '</div>
                     </div>

                     <div class="mar smlTxt ovfl-hidden">
                     ' . $second_product_variation_group . '
                     </div>
                     <div class="mar smlTxt ovfl-hidden">
                     ' . $second_product_current_options . '
                     ' . $second_product_extra_options . '
                        </div>

                     <div class="mar smlTxt ovfl-hidden">
                     ' . $comment . '
                     </div>';
                }//end elseif
            }
        }

        return $order;
    }

//end _getOrderDescription
//return array of shopping cart data

    function _getShoppingCartData($order_id)
    {
        $shopping_cart_data = array();
        if ($this->cart->contents()) {
            foreach ($this->cart->contents() as $items) {

                if ($items['options']['product_type'] == 'single') {
                    $product_flag = 'S';
                    $product_id   = $items['id'];

                    $variation_group_id = '';
                    if (!empty($items['options']['variation_group'])) {
                        $var_group    = unserialize($items['options']['variation_group']);
                        $variation_id = array();
                        foreach ($var_group as $key => $val) {
                            $variation_id[]     = $key;
                        }
                        $variation_group_id = implode(',', $variation_id);
                        unset($variation_id);
                    }

                    $current_ing_id = '';
                    if (!empty($items['options']['current'])) {
                        $current_ing = unserialize($items['options']['current']);
                        $temp_id     = array();
                        foreach ($current_ing as $val) {
                            $temp_id[]      = $val->ingredient_id;
                        }
                        $current_ing_id = implode(',', $temp_id);
                        unset($temp_id);
                    }

                    $extra_ing_id = '';
                    if (!empty($items['options']['extra'])) {
                        $extra_ing = unserialize($items['options']['extra']);
                        $temp_id   = array();
                        foreach ($extra_ing as $val) {
                            $temp_id[]    = $val->ingredient_id;
                        }
                        $extra_ing_id = implode(',', $temp_id);
                        unset($temp_id);
                    }

                    $quantity = $items['qty'];
                    $comment  = '';
                    if (!empty($items['options']['comment'])) {
                        $comment = $items['options']['comment'];
                    }

                    $shopping_cart_data[] = array('order_id'              => $order_id,
                        'product_flag'          => $product_flag,
                        'product_id'            => $product_id,
                        'variation_id'          => $variation_group_id,
                        'extra_ingredient_id'   => $extra_ing_id,
                        'default_ingredient_id' => $current_ing_id,
                        'quantity'              => $quantity,
                        'comment'               => $comment,
                        'half_pizza_group_id'   => '');
                }
                elseif ($items['options']['product_type'] == 'half_half') {
                    $product_flag = 'H';
                    $quantity     = $items['qty'];
                    $comment      = '';
                    if (!empty($items['options']['comment'])) {
                        $comment = $items['options']['comment'];
                    }

                    $half_pizza_group_id = $items['id'];

                    $first_product            = unserialize($items['options']['first_product']);
                    $first_product_id         = $first_product['product_id'];
                    $first_variation_group_id = '';

                    if (!empty($first_product['variation_group'])) {
                        $first_var_group = unserialize($first_product['variation_group']);
                        $temp_id         = array();
                        foreach ($first_var_group as $key => $val) {
                            $temp_id[]                = $key;
                        }
                        $first_variation_group_id = implode(',', $temp_id);
                        unset($temp_id);
                    }

                    $first_current_ing_id = '';
                    if (!empty($first_product['current'])) {
                        $first_current_ing = unserialize($first_product['current']);
                        $temp_id           = array();
                        foreach ($first_current_ing as $val) {
                            $temp_id[]            = $val->ingredient_id;
                        }
                        $first_current_ing_id = implode(',', $temp_id);
                        unset($temp_id);
                    }

                    $first_extra_ing_id = '';
                    if (!empty($first_product['extra'])) {
                        $first_extra_ing = unserialize($first_product['extra']);
                        $temp_id         = array();
                        foreach ($first_extra_ing as $val) {
                            $temp_id[]          = $val->ingredient_id;
                        }
                        $first_extra_ing_id = implode(',', $temp_id);
                        unset($temp_id);
                    }


                    $shopping_cart_data[]      = array('order_id'              => $order_id,
                        'product_flag'          => $product_flag,
                        'product_id'            => $first_product_id,
                        'variation_id'          => $first_variation_group_id,
                        'extra_ingredient_id'   => $first_extra_ing_id,
                        'default_ingredient_id' => $first_current_ing_id,
                        'quantity'              => $quantity,
                        'comment'               => $comment,
                        'half_pizza_group_id'   => $half_pizza_group_id);
                    //second product starts here
                    $second_product            = unserialize($items['options']['second_product']);
                    $second_product_id         = $second_product['product_id'];
                    $second_variation_group_id = '';

                    if (!empty($second_product['variation_group'])) {
                        $second_var_group = unserialize($second_product['variation_group']);
                        $temp_id          = array();
                        foreach ($second_var_group as $key => $val) {
                            $temp_id[]                 = $key;
                        }
                        $second_variation_group_id = implode(',', $temp_id);
                        unset($temp_id);
                    }

                    $second_current_ing_id = '';
                    if (!empty($second_product['current'])) {
                        $second_current_ing = unserialize($second_product['current']);
                        $temp_id            = array();
                        foreach ($second_current_ing as $val) {
                            $temp_id[]             = $val->ingredient_id;
                        }
                        $second_current_ing_id = implode(',', $temp_id);
                        unset($temp_id);
                    }

                    $second_extra_ing_id = '';
                    if (!empty($second_product['extra'])) {
                        $second_extra_ing = unserialize($second_product['extra']);
                        $temp_id          = array();
                        foreach ($second_extra_ing as $val) {
                            $temp_id[]           = $val->ingredient_id;
                        }
                        $second_extra_ing_id = implode(',', $temp_id);
                        unset($temp_id);
                    }


                    $shopping_cart_data[] = array('order_id'              => $order_id,
                        'product_flag'          => $product_flag,
                        'product_id'            => $second_product_id,
                        'variation_id'          => $second_variation_group_id,
                        'extra_ingredient_id'   => $second_extra_ing_id,
                        'default_ingredient_id' => $second_current_ing_id,
                        'quantity'              => $quantity,
                        'comment'               => $comment,
                        'half_pizza_group_id'   => $half_pizza_group_id);
                }
            }//end foreach

            return $shopping_cart_data;
        }//end if
    }

//end of function _getShoppingCartData

    function getUserPoints()
    {
        if ($this->input->post('op') && $this->phpsession->get('tmUserId')) {
            echo $this->O_Model->getUserPoints($this->phpsession->get('tmUserId'));
        }
    }

//end getUserPoints
    //called from order_history.js
    function getUserDetails()
    {
        if ($this->input->post('chk', true)) {
            $userid    = $this->phpsession->get('tmUserId');
            $usersInfo = $this->R_Model->getCustomerDetail($userid);
            //print_r($usersInfo);

            $user_details['cust_name']    = $usersInfo->first_name . ' ' . $usersInfo->last_name;
            $user_details['cust_points']  = $usersInfo->order_points;
            $user_details['cust_address'] = '<h2>' . $usersInfo->first_name . ' ' . $usersInfo->last_name . '<br/>
                                        ' . $usersInfo->suburb_name . '<br/>Mobile: ' . $usersInfo->mobile . '</h2>';

            $sitesettings                  = $this->SS_Model->getMangeTextDetails();
            $user_details['order_content'] = str_replace('\n', '<br>', html_entity_decode($sitesettings[8]));
            echo json_encode($user_details);
            //$user_details['rder_points'] = $userInfo->order_points;
//       echo 'hdf';
   
        }
    }

//end getUserDetails
    //called from order_history.js
    function getOrderHistory()
    {
        $userid        = $this->phpsession->get('tmUserId');
        //$usersInfo=$this->R_Model->getCustomerDetail($userid);
        $data['order'] = $this->O_Model->getCustomerOrderHistory($userid); // print_r($data['order']->result());
        $this->load->view($this->config->item('base_template_dir') . '/front/order_history/order_history_table', $data);
    }

//end getOrderHistory

    public function getReferFriendDetail()
    {

        $userid = $this->phpsession->get('tmUserId');

        $data['refer_friend'] = $this->O_Model->getReferFriendHistory($userid);
        $this->load->view($this->config->item('base_template_dir') . '/front/order_history/refer_friend_table', $data);
    }

    public function _getItemsDescription()
    {

        // print_r($this->cart->contents()) ;
        if (!$this->cart->contents()) {
            echo 'You don\'t have any items yet.<hr  style="color:#A92724;" />';
        }
        else {
            $order = '';
            foreach ($this->cart->contents() as $items) {
                if ($items['options']['product_type'] == 'single') {
                    //if product is from loyalty program
                    if ($items['options']['loyalty'] == 'lp') {
                        $remove          = '';
                        $extra           = '';
                        $comment         = '';
                        $variation_group = '';

                        if (!empty($items['options']['variation_group'])) {

                            $var_group = unserialize($items['options']['variation_group']);
                            foreach ($var_group as $key => $val) {
                                $variation_group .= '<tr>
                                    <td width="75" align="left" valign="top">&nbsp;</td>
                                    <td width="350" align="left" valign="top">' . ucwords($val['variation_group']) . ': ' . ucwords($val['variation_name']) . '</td>
                                    <td width="125" align="right" valign="top"></td>
                                 </tr>';
                            }
                        }
                        if (!empty($items['options']['comment'])) {
                            $comment = 'Note: ' . $items['options']['comment'];
                        }
                        if (!empty($items['options']['extra'])) {
                            $extra_ing = unserialize($items['options']['extra']);
                            foreach ($extra_ing as $val) {
                                $extra .= '<tr>
                                    <td align="left" valign="top">&nbsp;</td>
                                    <td width="350" height="20" align="left" valign="top">EXTRA ' . ucwords($val->ingredient_name) . '</td>
                                    <td width="125" align="right" valign="top"></td>
                                 </tr>';
                            }
                        }

                        if (!empty($items['options']['current'])) {
                            $current_ing = unserialize($items['options']['current']);
                            foreach ($current_ing as $val) {
                                $remove .= '<tr>
                                       <td align="left" valign="top">&nbsp;</td>
                                       <td width="350" height="20" align="left" valign="top">NO ' . ucwords($val->ingredient_name) . '</td>
                                       <td width="125" align="right" valign="top">&nbsp;</td>
                                       </tr>';
                            }
                        }
                        //<span class="change_cart_product" style="cursor:pointer;">Change</span><input type="hidden" value='.$items['rowid'].'  />
                        $order .= '<tr>
                                 <td width="550" align="left" valign="top">
                                 <table width="550" border="0" cellspacing="0" cellpadding="0" style="border-bottom:dotted 1px #000">
                                    <tr>
                                       <td width="750" align="left" valign="top">
                                          <table width="550" border="0" cellspacing="0" cellpadding="0">
                                             <tr>
                                                <td width="350" height="20" align="left" valign="top"><strong>' . $items['qty'] . 'x ' . ucwords($items['name']) . '(Loyalty Product)</strong></td>
                                                <td width="75" align="left" valign="top">&nbsp;</td>
                                                <td align="right" valign="top" width="125"></td>
                                             </tr>
                                          </table>
                                       </td>
                                    </tr>

                                    <tr>
                                       <td align="left" valign="top">
                                       <table width="550" border="0" cellspacing="0" cellpadding="0">
                                          ' . $variation_group . '
                                          ' . $remove . '
                                          ' . $extra . '
                                       </table>
                                       </td>
                                    </tr>

                                    <tr>
                                       <td align="left" valign="top">
                                       <table width="550" border="0" cellspacing="0" cellpadding="0">
                                          <tr>
                                             <td width="425" height="20" align="left" valign="middle">' . $comment . '</td>
                                             <td align="right" valign="middle" width="125" style="border-top:dotted 1px #000"><strong>Points ' . $items['options']['product_points'] . '</strong></td>
                                          </tr>
                                       </table>
                                       </td>
                                    </tr>

                                 </table>

                                 </td>
                                 </tr>
                                 ';
                    }
                    else {
                        $remove          = '';
                        $extra           = '';
                        $comment         = '';
                        $variation_group = '';

                        if (!empty($items['options']['variation_group'])) {
                            $var_group = unserialize($items['options']['variation_group']);
                            foreach ($var_group as $key => $val) {

                                $variation_group .= '<tr>
                                             <td width="75" align="left" valign="top">&nbsp;</td>
                                             <td width="350" align="left" valign="top">' . ucwords($val['variation_group']) . ': ' . ucwords($val['variation_name']) . '</td>
                                             <td width="125" align="right" valign="top">$' . number_format($val['variation_price'], 2) . '</td>
                                          </tr>';
                            }
                        }
                        if (!empty($items['options']['comment'])) {
                            $comment = 'Note: ' . $items['options']['comment'];
                        }
                        if (!empty($items['options']['extra'])) {
                            $extra_ing = unserialize($items['options']['extra']);
                            if (!empty($extra_ing) && is_array($extra_ing)) {
                                foreach ($extra_ing as $val) {

                                    $extra .= '<tr>
                                             <td align="left" valign="top">&nbsp;</td>
                                             <td width="350" height="20" align="left" valign="top">EXTRA ' . ucwords($val->ingredient_name) . '</td>
                                             <td width="125" align="right" valign="top">+$' . number_format($val->price, 2) . '</td>
                                          </tr>';
                                }
                            }
                        }

                        if (!empty($items['options']['current'])) {
                            $current_ing = unserialize($items['options']['current']);
                            foreach ($current_ing as $val) {
                                $remove .= '<tr>
                                       <td align="left" valign="top">&nbsp;</td>
                                       <td width="350" height="20" align="left" valign="top">NO ' . ucwords($val->ingredient_name) . '</td>
                                       <td width="125" align="right" valign="top">&nbsp;</td>
                                       </tr>';
                            }
                        }
                        //<span class="change_cart_product" style="cursor:pointer;">Change</span><input type="hidden" value='.$items['rowid'].'  />
                        $order .= ' <tr>
                                 <td width="550" align="left" valign="top">
                                 <table width="550" border="0" cellspacing="0" cellpadding="0" style="border-bottom:dotted 1px #000">
                                    <tr>
                                       <td width="750" align="left" valign="top">
                                          <table width="550" border="0" cellspacing="0" cellpadding="0">
                                             <tr>
                                                <td width="350" height="20" align="left" valign="top"><strong>' . $items['qty'] . 'x ' . ucwords($items['name']) . '</strong></td>
                                                <td width="75" align="left" valign="top">&nbsp;</td>
                                                <td align="right" valign="top" width="125">$' . number_format($items['options']['default_price'], 2) . '</td>
                                             </tr>
                                          </table>
                                       </td>
                                    </tr>

                                    <tr>
                                       <td align="left" valign="top">
                                       <table width="550" border="0" cellspacing="0" cellpadding="0">
                                          ' . $variation_group . '
                                          ' . $remove . '
                                          ' . $extra . '
                                       </table>
                                       </td>
                                    </tr>

                                    <tr>
                                       <td align="left" valign="top">
                                       <table width="550" border="0" cellspacing="0" cellpadding="0">
                                          <tr>
                                             <td width="350" height="20" align="left" valign="middle">' . $comment . '</td>
                                             <td width="75" align="left" valign="top">&nbsp;</td>
                                             <td align="right" valign="middle" width="125" style="border-top:dotted 1px #000"><strong>$' . number_format($items['subtotal'], 2) . '</strong></td>
                                          </tr>
                                       </table>
                                       </td>
                                    </tr>

                                 </table>

                                 </td>
                                 </tr>

                                 ';
                    }
                }
                elseif ($items['options']['product_type'] == 'half_half') {
                    if ($items['options']['loyalty'] == 'lp') {
                        $comment = '';
                        if (!empty($items['options']['comment'])) {
                            $comment       = 'Note: ' . $items['options']['comment'];
                        }
                        $first_product = unserialize($items['options']['first_product']);

                        $first_product_variation_group = '';
                        if (!empty($first_product['variation_group'])) {
                            $var_group = unserialize($first_product['variation_group']);
                            foreach ($var_group as $key => $val) {
                                $first_product_variation_group .= '<tr>
                                 <td width="75" align="left" valign="top">&nbsp;</td>
                                 <td width="350" align="left" valign="top">' . ucwords($val['variation_group']) . ': ' . ucwords($val['variation_name']) . '</td>
                                 <td width="125" align="right" valign="top"></td>
                                 </tr>';
                            }
                        }
                        $first_product_current_options = '';
                        if (!empty($first_product['current'])) {
                            $current_ing = unserialize($first_product['current']);
                            foreach ($current_ing as $val) {
                                $first_product_current_options .= '<tr>
                                 <td align="left" valign="top">&nbsp;</td>
                                 <td width="350" height="20" align="left" valign="top">NO ' . ucwords($val->ingredient_name) . '</td>
                                 <td width="125" align="right" valign="top">&nbsp;</td>
                                 </tr>';
                            }
                        }
                        $first_product_extra_options = '';
                        if (!empty($first_product['extra'])) {
                            $extra_ing = unserialize($first_product['extra']);
                            foreach ($extra_ing as $val) {
                                $first_product_extra_options .= '<tr>
                                 <td align="left" valign="top">&nbsp;</td>
                                 <td width="350" height="20" align="left" valign="top">EXTRA ' . ucwords($val->ingredient_name) . '</td>
                                 <td width="125" align="right" valign="top"></td>
                              </tr>';
                            }
                        }

                        $second_product                 = unserialize($items['options']['second_product']);
                        $second_product_variation_group = '';
                        if (!empty($second_product['variation_group'])) {
                            $var_group = unserialize($second_product['variation_group']);
                            foreach ($var_group as $key => $val) {
                                $second_product_variation_group .= '<tr>
                              <td width="75" align="left" valign="top">&nbsp;</td>
                              <td width="350" align="left" valign="top">' . ucwords($val['variation_group']) . ': ' . ucwords($val['variation_name']) . '</td>
                              <td width="125" align="right" valign="top"></td>
                              </tr>';
                            }
                        }
                        $second_product_current_options = '';
                        if (!empty($second_product['current'])) {
                            $current_ing = unserialize($second_product['current']);
                            foreach ($current_ing as $val) {
                                $second_product_current_options .= '<tr>
                                       <td align="left" valign="top">&nbsp;</td>
                                       <td width="350" height="20" align="left" valign="top">NO ' . ucwords($val->ingredient_name) . '</td>
                                       <td width="125" align="right" valign="top">&nbsp;</td>
                                       </tr>';
                            }
                        }
                        $second_product_extra_options = '';
                        if (!empty($second_product['extra'])) {
                            $extra_ing = unserialize($second_product['extra']);
                            foreach ($extra_ing as $val) {
                                $second_product_extra_options .= ' <tr>
                           <td align="left" valign="top">&nbsp;</td>
                           <td width="350" height="20" align="left" valign="top">EXTRA ' . ucwords($val->ingredient_name) . '</td>
                           <td width="125" align="right" valign="top"></td>
                        </tr>';
                            }
                        }

                        $order .=
                            '<tr>
                  <td align="left" valign="top" width="550">
                  <table width="550" border="0" cellspacing="0" cellpadding="0" style="border-bottom:dotted 1px #000">
                     <tr>
                        <td width="750" align="left" valign="top">
                           <table width="550" border="0" cellspacing="0" cellpadding="0">
                              <tr>
                                 <td width="350" height="20" align="left" valign="top"><strong>' . $items['qty'] . 'x Half & Half Pizza (Loyalty Product)</strong></td>
                                 <td width="75" align="left" valign="top">&nbsp;</td>
                                 <td align="right" valign="top" width="125"></td>
                              </tr>
                           </table>
                        </td>
                     </tr>
                     <tr>
                        <td align="left" valign="top">
                           <table width="550" border="0" cellspacing="0" cellpadding="0">
                              <tr>
                                 <td width="750" align="left" valign="top">
                                    <table width="550" border="0" cellspacing="0" cellpadding="0">
                                       <tr>
                                          <td width="350" height="20" align="left" valign="top"><strong>First Half ' . ucwords($first_product['product_name']) . '</strong></td>
                                             <td width="75" align="left" valign="top">&nbsp;</td>
                                          <td align="right" valign="top" width="125"></td>
                                       </tr>
                                    </table>
                                 </td>
                              </tr>
                              <tr>
                                 <td align="left" valign="top">
                                    <table width="550" border="0" cellspacing="0" cellpadding="0">
                                       ' . $first_product_variation_group . '
                                       ' . $first_product_current_options . '
                                       ' . $first_product_extra_options . '
                                    </table>
                                 </td>
                              </tr>
                           </table>
                           <table width="550" border="0" cellspacing="0" cellpadding="0">
                              <tr>
                                 <td width="750" align="left" valign="top">
                                    <table width="550" border="0" cellspacing="0" cellpadding="0">
                                       <tr>
                                          <td width="350" height="20" align="left" valign="top"><strong>Second Half ' . ucwords($second_product['product_name']) . '</strong></td>
                                          <td width="75" align="left" valign="top">&nbsp;</td>
                                          <td align="right" valign="top" width="125"></td>
                                       </tr>
                                    </table>
                                 </td>
                              </tr>
                              <tr>
                                 <td align="left" valign="top">
                                    <table width="550" border="0" cellspacing="0" cellpadding="0">
                                       ' . $second_product_variation_group . '
                                       ' . $second_product_current_options . '
                                       ' . $second_product_extra_options . '
                                    </table>
                                 </td>
                              </tr>
                           </table>
                        </td>
                        </tr>
                           <tr>
                              <td align="left" valign="top">
                                 <table width="550" border="0" cellspacing="0" cellpadding="0">
                                    <tr>
                                       <td width="350" height="20" align="left" valign="middle">' . $comment . '</td>
                                       <td width="75" align="left" valign="top">&nbsp;</td>
                                       <td align="right" valign="middle" width="125" style="border-top:dotted 1px #000"><strong>Points ' . $items['options']['product_points'] . '</strong></td>
                                    </tr>
                                 </table>
                              </td>
                           </tr>
                     </table>
                  </td>
               </tr>
               ';
                    }
                    else {
                        $comment = '';
                        if (!empty($items['options']['comment'])) {
                            $comment       = 'Note: ' . $items['options']['comment'];
                        }
                        $first_product = unserialize($items['options']['first_product']);

                        $first_product_variation_group = '';
                        if (!empty($first_product['variation_group'])) {
                            $var_group = unserialize($first_product['variation_group']);
                            foreach ($var_group as $key => $val) {

                                $first_product_variation_group .= '<tr>
                                 <td width="75" align="left" valign="top">&nbsp;</td>
                                 <td width="350" align="left" valign="top">' . ucwords($val['variation_group']) . ': ' . ucwords($val['variation_name']) . '</td>
                                 <td width="125" align="right" valign="top">$' . number_format(($val['variation_price'] / 2), 2) . '</td>
                                 </tr>';
                            }
                        }
                        $first_product_current_options = '';
                        if (!empty($first_product['current'])) {
                            $current_ing = unserialize($first_product['current']);
                            foreach ($current_ing as $val) {
                                $first_product_current_options .= '<tr>
                                 <td align="left" valign="top">&nbsp;</td>
                                 <td width="350" height="20" align="left" valign="top">NO ' . ucwords($val->ingredient_name) . '</td>
                                 <td width="125" align="right" valign="top">&nbsp;</td>
                                 </tr>';
                            }
                        }
                        $first_product_extra_options = '';
                        if (!empty($first_product['extra'])) {
                            $extra_ing = unserialize($first_product['extra']);
                            foreach ($extra_ing as $val) {
                                $first_product_extra_options .= '<tr>
                                 <td align="left" valign="top">&nbsp;</td>
                                 <td width="350" height="20" align="left" valign="top">EXTRA ' . ucwords($val->ingredient_name) . '</td>
                                 <td width="125" align="right" valign="top">+$' . number_format(($val->price / 2), 2) . '</td>
                              </tr>';
                            }
                        }

                        $second_product                 = unserialize($items['options']['second_product']);
                        $second_product_variation_group = '';
                        if (!empty($second_product['variation_group'])) {
                            $var_group = unserialize($second_product['variation_group']);
                            foreach ($var_group as $key => $val) {
                                $second_product_variation_group .= '<tr>
                                 <td width="75" align="left" valign="top">&nbsp;</td>
                                 <td width="350" align="left" valign="top">' . ucwords($val['variation_group']) . ': ' . ucwords($val['variation_name']) . '</td>
                                 <td width="125" align="right" valign="top">$' . number_format($val['variation_price'] / 2, 2) . '</td>
                                 </tr>';
                            }
                        }
                        $second_product_current_options = '';
                        if (!empty($second_product['current'])) {
                            $current_ing = unserialize($second_product['current']);
                            foreach ($current_ing as $val) {
                                $second_product_current_options .= '<tr>
                                    <td align="left" valign="top">&nbsp;</td>
                                    <td width="350" height="20" align="left" valign="top">NO ' . ucwords($val->ingredient_name) . '</td>
                                    <td width="125" align="right" valign="top">&nbsp;</td>
                                    </tr>';
                            }
                        }
                        $second_product_extra_options = '';
                        if (!empty($second_product['extra'])) {
                            $extra_ing = unserialize($second_product['extra']);
                            foreach ($extra_ing as $val) {
                                $second_product_extra_options .= ' <tr>
                                 <td align="left" valign="top">&nbsp;</td>
                                 <td width="350" height="20" align="left" valign="top">EXTRA ' . ucwords($val->ingredient_name) . '</td>
                                 <td width="125" align="right" valign="top">+$' . number_format($val->price / 2, 2) . '</td>
                              </tr>';
                            }
                        }

                        $order .=
                            '<tr>
                  <td align="left" valign="top" width="550">
                  <table width="550" border="0" cellspacing="0" cellpadding="0" style="border-bottom:dotted 1px #000">
                     <tr>
                        <td width="750" align="left" valign="top">
                           <table width="550" border="0" cellspacing="0" cellpadding="0">
                              <tr>
                                 <td width="350" height="20" align="left" valign="top"><strong>' . $items['qty'] . 'x Half & Half Pizza</strong></td>
                                 <td width="75" align="left" valign="top">&nbsp;</td>
                                 <td align="right" valign="top" width="125">$' . $items['options']['half_pizza_fee'] . '</td>
                              </tr>
                           </table>
                        </td>
                     </tr>
                     <tr>
                        <td align="left" valign="top">
                           <table width="550" border="0" cellspacing="0" cellpadding="0">
                              <tr>
                                 <td width="750" align="left" valign="top">
                                    <table width="550" border="0" cellspacing="0" cellpadding="0">
                                       <tr>
                                          <td width="350" height="20" align="left" valign="top"><strong>First Half ' . ucwords($first_product['product_name']) . '</strong></td>
                                          <td width="75" align="left" valign="top">&nbsp;</td>
                                          <td align="right" valign="top" width="125">$' . number_format($first_product['default_price'] / 2, 2) . '</td>
                                       </tr>
                                    </table>
                                 </td>
                              </tr>
                              <tr>
                                 <td align="left" valign="top">
                                    <table width="550" border="0" cellspacing="0" cellpadding="0">
                                       ' . $first_product_variation_group . '
                                       ' . $first_product_current_options . '
                                       ' . $first_product_extra_options . '
                                    </table>
                                 </td>
                              </tr>
                           </table>
                           <table width="550" border="0" cellspacing="0" cellpadding="0">
                              <tr>
                                 <td width="750" align="left" valign="top">
                                    <table width="550" border="0" cellspacing="0" cellpadding="0">
                                       <tr>
                                          <td width="350" height="20" align="left" valign="top"><strong>Second Half ' . ucwords($second_product['product_name']) . '</strong></td>
                                          <td width="75" align="left" valign="top">&nbsp;</td>
                                          <td align="right" valign="top" width="125">$' . number_format($second_product['default_price'] / 2, 2) . '</td>
                                       </tr>
                                    </table>
                                 </td>
                              </tr>
                              <tr>
                                 <td align="left" valign="top">
                                    <table width="550" border="0" cellspacing="0" cellpadding="0">
                                       ' . $second_product_variation_group . '
                                       ' . $second_product_current_options . '
                                       ' . $second_product_extra_options . '
                                    </table>
                                 </td>
                              </tr>
                           </table>
                        </td>
                        </tr>
                           <tr>
                              <td align="left" valign="top">
                                 <table width="550" border="0" cellspacing="0" cellpadding="0">
                                    <tr>
                                       <td width="350" height="20" align="left" valign="middle">' . $comment . '</td>
                                       <td width="75" align="left" valign="top">&nbsp;</td>
                                       <td align="right" valign="middle" width="125" style="border-top:dotted 1px #000"><strong>$' . number_format($items['subtotal'], 2) . '</strong></td>
                                    </tr>
                                 </table>
                              </td>
                           </tr>
                     </table>
                  </td>
               </tr>
               ';
                    }
                }
            }
            return $order;
        }
    }

    /* description for customer pdf */

    public function _getItemsDescriptionCustomer()
    {

        // print_r($this->cart->contents()) ;
        if (!$this->cart->contents()) {
            echo 'You don\'t have any items yet.<hr  style="color:#A92724;" />';
        }
        else {
            $order = '';
            foreach ($this->cart->contents() as $items) {
                if ($items['options']['product_type'] == 'single') {

                    //if product is from loyalty program
                    if ($items['options']['loyalty'] == 'lp') {
                        $remove          = '';
                        $extra           = '';
                        $comment         = '';
                        $variation_group = '';

                        if (!empty($items['options']['variation_group'])) {

                            $var_group = unserialize($items['options']['variation_group']);
                            foreach ($var_group as $key => $val) {
                                $variation_group .= '<tr>
                                    <td width="75" align="left" valign="top">&nbsp;</td>
                                    <td width="350" align="left" valign="top">' . ucwords($val['variation_group']) . ': ' . ucwords($val['variation_name']) . '</td>
                                    <td width="125" align="right" valign="top"></td>
                                 </tr>';
                            }
                        }
                        if (!empty($items['options']['comment'])) {
                            $comment = 'Note : ' . $items['options']['comment'];
                        }
                        if (!empty($items['options']['extra'])) {
                            $extra_ing = unserialize($items['options']['extra']);
                            foreach ($extra_ing as $val) {
                                $extra .= '<tr>
                                    <td align="left" valign="top">&nbsp;</td>
                                    <td width="350" height="20" align="left" valign="top">EXTRA ' . ucwords($val->ingredient_name) . '</td>
                                    <td width="125" align="right" valign="top"></td>
                                 </tr>';
                            }
                        }

                        if (!empty($items['options']['current'])) {
                            $current_ing = unserialize($items['options']['current']);
                            foreach ($current_ing as $val) {
                                $remove .= '<tr>
                                       <td align="left" valign="top">&nbsp;</td>
                                       <td width="350" height="20" align="left" valign="top">NO ' . ucwords($val->ingredient_name) . '</td>
                                       <td width="125" align="right" valign="top">&nbsp;</td>
                                       </tr>';
                            }
                        }
                        //<span class="change_cart_product" style="cursor:pointer;">Change</span><input type="hidden" value='.$items['rowid'].'  />
                        $order .= '<tr>
                                 <td width="550" align="left" valign="top">
                                 <table width="550" border="0" cellspacing="0" cellpadding="0" style="border-bottom:dotted 1px #000">
                                    <tr>
                                       <td  align="left" valign="top">
                                          <table width="550" border="0" cellspacing="0" cellpadding="0">
                                             <tr>
                                                <td width="425" height="20" align="left" valign="top"><strong>' . $items['qty'] . 'x ' . ucwords($items['name']) . '(Loyalty Product)</strong></td>
                                                <td align="right" valign="top" width="125"></td>
                                             </tr>
                                          </table>
                                       </td>
                                    </tr>

                                    <tr>
                                       <td align="left" valign="top">
                                       <table width="550" border="0" cellspacing="0" cellpadding="0">
                                          ' . $variation_group . '
                                          ' . $remove . '
                                          ' . $extra . '
                                       </table>
                                       </td>
                                    </tr>

                                    <tr>
                                       <td align="left" valign="top">
                                       <table width="550" border="0" cellspacing="0" cellpadding="0">
                                          <tr>
                                             <td width="425" height="20" align="left" valign="middle">' . $comment . '</td>
                                             <td align="right" valign="middle" width="125" style="border-top:dotted 1px #000"><strong>Points ' . $items['options']['product_points'] . '</strong></td>
                                          </tr>
                                       </table>
                                       </td>
                                    </tr>

                                 </table>

                                 </td>
                                 </tr>
                                 ';
                    }
                    else {
                        $remove          = '';
                        $extra           = '';
                        $comment         = '';
                        $variation_group = '';

                        if (!empty($items['options']['variation_group'])) {
                            $var_group = unserialize($items['options']['variation_group']);
                            foreach ($var_group as $key => $val) {

                                $variation_group .= '<tr>
                                             <td width="75" align="left" valign="top">&nbsp;</td>
                                             <td width="350" align="left" valign="top">' . ucwords($val['variation_group']) . ': ' . ucwords($val['variation_name']) . '</td>
                                             <td width="125" align="right" valign="top">$' . number_format($val['variation_price'], 2) . '</td>
                                          </tr>';
                            }
                        }
                        if (!empty($items['options']['comment'])) {
                            $comment = 'Note : ' . $items['options']['comment'];
                        }
                        if (!empty($items['options']['extra'])) {
                            $extra_ing = unserialize($items['options']['extra']);
                            if (!empty($extra_ing) && is_array($extra_ing)) {
                                foreach ($extra_ing as $val) {

                                    $extra .= '<tr>
                                             <td align="left" valign="top">&nbsp;</td>
                                             <td width="350" height="20" align="left" valign="top">EXTRA ' . ucwords($val->ingredient_name) . '</td>
                                             <td width="125" align="right" valign="top">+$' . number_format($val->price, 2) . '</td>
                                          </tr>';
                                }
                            }
                        }

                        if (!empty($items['options']['current'])) {
                            $current_ing = unserialize($items['options']['current']);
                            foreach ($current_ing as $val) {
                                $remove .= '<tr>
                                       <td align="left" valign="top">&nbsp;</td>
                                       <td width="350" height="20" align="left" valign="top">NO ' . ucwords($val->ingredient_name) . '</td>
                                       <td width="125" align="right" valign="top">&nbsp;</td>
                                       </tr>';
                            }
                        }
                        //<span class="change_cart_product" style="cursor:pointer;">Change</span><input type="hidden" value='.$items['rowid'].'  />
                        $order .= ' <tr>
                                 <td width="550" align="left" valign="top">
                                 <table width="550" border="0" cellspacing="0" cellpadding="0" style="border-bottom:dotted 1px #000">
                                    <tr>
                                       <td  align="left" valign="top">
                                          <table width="550" border="0" cellspacing="0" cellpadding="0">
                                             <tr>
                                                <td width="425" height="20" align="left" valign="top"><strong>' . $items['qty'] . 'x ' . ucwords($items['name']) . '</strong></td>
                                                <td align="right" valign="top" width="125">$' . number_format($items['options']['default_price'], 2) . '</td>
                                             </tr>
                                          </table>
                                       </td>
                                    </tr>

                                    <tr>
                                       <td align="left" valign="top">
                                       <table width="550" border="0" cellspacing="0" cellpadding="0">
                                          ' . $variation_group . '
                                          ' . $remove . '
                                          ' . $extra . '
                                       </table>
                                       </td>
                                    </tr>

                                    <tr>
                                       <td align="left" valign="top">
                                       <table width="550" border="0" cellspacing="0" cellpadding="0">
                                          <tr>
                                             <td width="425" height="20" align="left" valign="middle">' . $comment . '</td>
                                             <td align="right" valign="middle" width="125" style="border-top:dotted 1px #000"><strong>$' . number_format($items['subtotal'], 2) . '</strong></td>
                                          </tr>
                                       </table>
                                       </td>
                                    </tr>

                                 </table>

                                 </td>
                                 </tr>

                                 ';
                    }
                }
                elseif ($items['options']['product_type'] == 'half_half') {
                    if ($items['options']['loyalty'] == 'lp') {
                        $comment = '';
                        if (!empty($items['options']['comment'])) {
                            $comment       = 'Note : ' . $items['options']['comment'];
                        }
                        $first_product = unserialize($items['options']['first_product']);

                        $first_product_variation_group = '';
                        if (!empty($first_product['variation_group'])) {
                            $var_group = unserialize($first_product['variation_group']);
                            foreach ($var_group as $key => $val) {
                                $first_product_variation_group .= '<tr>
                                 <td width="75" align="left" valign="top">&nbsp;</td>
                                 <td width="350" align="left" valign="top">' . ucwords($val['variation_group']) . ': ' . ucwords($val['variation_name']) . '</td>
                                 <td width="125" align="right" valign="top"></td>
                                 </tr>';
                            }
                        }
                        $first_product_current_options = '';
                        if (!empty($first_product['current'])) {
                            $current_ing = unserialize($first_product['current']);
                            foreach ($current_ing as $val) {
                                $first_product_current_options .= '<tr>
                                 <td align="left" valign="top">&nbsp;</td>
                                 <td width="350" height="20" align="left" valign="top">NO ' . ucwords($val->ingredient_name) . '</td>
                                 <td width="125" align="right" valign="top">&nbsp;</td>
                                 </tr>';
                            }
                        }
                        $first_product_extra_options = '';
                        if (!empty($first_product['extra'])) {
                            $extra_ing = unserialize($first_product['extra']);
                            foreach ($extra_ing as $val) {
                                $first_product_extra_options .= '<tr>
                                 <td align="left" valign="top">&nbsp;</td>
                                 <td width="350" height="20" align="left" valign="top">EXTRA ' . ucwords($val->ingredient_name) . '</td>
                                 <td width="125" align="right" valign="top"></td>
                              </tr>';
                            }
                        }

                        $second_product                 = unserialize($items['options']['second_product']);
                        $second_product_variation_group = '';
                        if (!empty($second_product['variation_group'])) {
                            $var_group = unserialize($second_product['variation_group']);
                            foreach ($var_group as $key => $val) {
                                $second_product_variation_group .= '<tr>
                              <td width="75" align="left" valign="top">&nbsp;</td>
                              <td width="350" align="left" valign="top">' . ucwords($val['variation_group']) . ': ' . ucwords($val['variation_name']) . '</td>
                              <td width="125" align="right" valign="top"></td>
                              </tr>';
                            }
                        }
                        $second_product_current_options = '';
                        if (!empty($second_product['current'])) {
                            $current_ing = unserialize($second_product['current']);
                            foreach ($current_ing as $val) {
                                $second_product_current_options .= '<tr>
                                       <td align="left" valign="top">&nbsp;</td>
                                       <td width="350" height="20" align="left" valign="top">NO ' . ucwords($val->ingredient_name) . '</td>
                                       <td width="125" align="right" valign="top">&nbsp;</td>
                                       </tr>';
                            }
                        }
                        $second_product_extra_options = '';
                        if (!empty($second_product['extra'])) {
                            $extra_ing = unserialize($second_product['extra']);
                            foreach ($extra_ing as $val) {
                                $second_product_extra_options .= ' <tr>
                           <td align="left" valign="top">&nbsp;</td>
                           <td width="350" height="20" align="left" valign="top">EXTRA ' . ucwords($val->ingredient_name) . '</td>
                           <td width="125" align="right" valign="top"></td>
                        </tr>';
                            }
                        }

                        $order .=
                            '<tr>
                  <td align="left" valign="top" width="550">
                  <table width="550" border="0" cellspacing="0" cellpadding="0" style="border-bottom:dotted 1px #000">
                     <tr>
                        <td align="left" valign="top">
                           <table width="550" border="0" cellspacing="0" cellpadding="0">
                              <tr>
                                 <td width="425" height="20" align="left" valign="top"><strong>' . $items['qty'] . 'x Half & Half Pizza (Loyalty Product)</strong></td>
                                 <td align="right" valign="top" width="125"></td>
                              </tr>
                           </table>
                        </td>
                     </tr>
                     <tr>
                        <td align="left" valign="top">
                           <table width="550" border="0" cellspacing="0" cellpadding="0">
                              <tr>
                                 <td  align="left" valign="top">
                                    <table width="550" border="0" cellspacing="0" cellpadding="0">
                                       <tr>
                                          <td width="425" height="20" align="left" valign="top"><strong>First Half ' . ucwords($first_product['product_name']) . '</strong></td>
                                          <td align="right" valign="top" width="125"></td>
                                       </tr>
                                    </table>
                                 </td>
                              </tr>
                              <tr>
                                 <td align="left" valign="top">
                                    <table width="550" border="0" cellspacing="0" cellpadding="0">
                                       ' . $first_product_variation_group . '
                                       ' . $first_product_current_options . '
                                       ' . $first_product_extra_options . '
                                    </table>
                                 </td>
                              </tr>
                           </table>
                           <table width="550" border="0" cellspacing="0" cellpadding="0">
                              <tr>
                                 <td  align="left" valign="top">
                                    <table width="550" border="0" cellspacing="0" cellpadding="0">
                                       <tr>
                                          <td width="425" height="20" align="left" valign="top"><strong>Second Half ' . ucwords($second_product['product_name']) . '</strong></td>
                                          <td align="right" valign="top" width="125"></td>
                                       </tr>
                                    </table>
                                 </td>
                              </tr>
                              <tr>
                                 <td align="left" valign="top">
                                    <table width="550" border="0" cellspacing="0" cellpadding="0">
                                       ' . $second_product_variation_group . '
                                       ' . $second_product_current_options . '
                                       ' . $second_product_extra_options . '
                                    </table>
                                 </td>
                              </tr>
                           </table>
                        </td>
                        </tr>
                           <tr>
                              <td align="left" valign="top">
                                 <table width="550" border="0" cellspacing="0" cellpadding="0">
                                    <tr>
                                       <td width="425" height="20" align="left" valign="middle">' . $comment . '</td>
                                       <td align="right" valign="middle" width="125" style="border-top:dotted 1px #000"><strong>Points ' . $items['options']['product_points'] . '</strong></td>
                                    </tr>
                                 </table>
                              </td>
                           </tr>
                     </table>
                  </td>
               </tr>
               ';
                    }
                    else {
                        $comment = '';
                        if (!empty($items['options']['comment'])) {
                            $comment       = 'Note : ' . $items['options']['comment'];
                        }
                        $first_product = unserialize($items['options']['first_product']);

                        $first_product_variation_group = '';
                        if (!empty($first_product['variation_group'])) {
                            $var_group = unserialize($first_product['variation_group']);
                            foreach ($var_group as $key => $val) {

                                $first_product_variation_group .= '<tr>
                                 <td width="75" align="left" valign="top">&nbsp;</td>
                                 <td width="350" align="left" valign="top">' . ucwords($val['variation_group']) . ': ' . ucwords($val['variation_name']) . '</td>
                                 <td width="125" align="right" valign="top">$' . number_format($val['variation_price'] / 2, 2) . '</td>
                                 </tr>';
                            }
                        }
                        $first_product_current_options = '';
                        if (!empty($first_product['current'])) {
                            $current_ing = unserialize($first_product['current']);
                            foreach ($current_ing as $val) {
                                $first_product_current_options .= '<tr>
                                 <td align="left" valign="top">&nbsp;</td>
                                 <td width="350" height="20" align="left" valign="top">NO ' . ucwords($val->ingredient_name) . '</td>
                                 <td width="125" align="right" valign="top">&nbsp;</td>
                                 </tr>';
                            }
                        }
                        $first_product_extra_options = '';
                        if (!empty($first_product['extra'])) {
                            $extra_ing = unserialize($first_product['extra']);
                            foreach ($extra_ing as $val) {
                                $first_product_extra_options .= '<tr>
                                 <td align="left" valign="top">&nbsp;</td>
                                 <td width="350" height="20" align="left" valign="top">EXTRA ' . ucwords($val->ingredient_name) . '</td>
                                 <td width="125" align="right" valign="top">+$' . number_format($val->price / 2, 2) . '</td>
                              </tr>';
                            }
                        }

                        $second_product                 = unserialize($items['options']['second_product']);
                        $second_product_variation_group = '';
                        if (!empty($second_product['variation_group'])) {
                            $var_group = unserialize($second_product['variation_group']);
                            foreach ($var_group as $key => $val) {
                                $second_product_variation_group .= '<tr>
                                 <td width="75" align="left" valign="top">&nbsp;</td>
                                 <td width="350" align="left" valign="top">' . ucwords($val['variation_group']) . ': ' . ucwords($val['variation_name']) . '</td>
                                 <td width="125" align="right" valign="top">$' . number_format($val['variation_price'] / 2, 2) . '</td>
                                 </tr>';
                            }
                        }
                        $second_product_current_options = '';
                        if (!empty($second_product['current'])) {
                            $current_ing = unserialize($second_product['current']);
                            foreach ($current_ing as $val) {
                                $second_product_current_options .= '<tr>
                                    <td align="left" valign="top">&nbsp;</td>
                                    <td width="350" height="20" align="left" valign="top">NO ' . ucwords($val->ingredient_name) . '</td>
                                    <td width="125" align="right" valign="top">&nbsp;</td>
                                    </tr>';
                            }
                        }
                        $second_product_extra_options = '';
                        if (!empty($second_product['extra'])) {
                            $extra_ing = unserialize($second_product['extra']);
                            foreach ($extra_ing as $val) {
                                $second_product_extra_options .= ' <tr>
                                 <td align="left" valign="top">&nbsp;</td>
                                 <td width="350" height="20" align="left" valign="top">EXTRA ' . ucwords($val->ingredient_name) . '</td>
                                 <td width="125" align="right" valign="top">+$' . number_format($val->price / 2, 2) . '</td>
                              </tr>';
                            }
                        }

                        $order .=
                            '<tr>
                  <td align="left" valign="top" width="550">
                  <table width="550" border="0" cellspacing="0" cellpadding="0" style="border-bottom:dotted 1px #000">
                     <tr>
                        <td align="left" valign="top">
                           <table width="550" border="0" cellspacing="0" cellpadding="0">
                              <tr>
                                 <td width="425" height="20" align="left" valign="top"><strong>' . $items['qty'] . 'x Half & Half Pizza</strong></td>
                                 <td align="right" valign="top" width="125">$' . $items['options']['half_pizza_fee'] . '</td>
                              </tr>
                           </table>
                        </td>
                     </tr>
                     <tr>
                        <td align="left" valign="top">
                           <table width="550" border="0" cellspacing="0" cellpadding="0">
                              <tr>
                                 <td  align="left" valign="top">
                                    <table width="550" border="0" cellspacing="0" cellpadding="0">
                                       <tr>
                                          <td width="425" height="20" align="left" valign="top"><strong>First Half ' . ucwords($first_product['product_name']) . '</strong></td>
                                          <td align="right" valign="top" width="125">$' . number_format($first_product['default_price'] / 2, 2) . '</td>
                                       </tr>
                                    </table>
                                 </td>
                              </tr>
                              <tr>
                                 <td align="left" valign="top">
                                    <table width="550" border="0" cellspacing="0" cellpadding="0">
                                       ' . $first_product_variation_group . '
                                       ' . $first_product_current_options . '
                                       ' . $first_product_extra_options . '
                                    </table>
                                 </td>
                              </tr>
                           </table>
                           <table width="550" border="0" cellspacing="0" cellpadding="0">
                              <tr>
                                 <td  align="left" valign="top">
                                    <table width="550" border="0" cellspacing="0" cellpadding="0">
                                       <tr>
                                          <td width="425" height="20" align="left" valign="top"><strong>Second Half ' . ucwords($second_product['product_name']) . '</strong></td>
                                          <td align="right" valign="top" width="125">$' . number_format($second_product['default_price'] / 2, 2) . '</td>
                                       </tr>
                                    </table>
                                 </td>
                              </tr>
                              <tr>
                                 <td align="left" valign="top">
                                    <table width="550" border="0" cellspacing="0" cellpadding="0">
                                       ' . $second_product_variation_group . '
                                       ' . $second_product_current_options . '
                                       ' . $second_product_extra_options . '
                                    </table>
                                 </td>
                              </tr>
                           </table>
                        </td>
                        </tr>
                           <tr>
                              <td align="left" valign="top">
                                 <table width="550" border="0" cellspacing="0" cellpadding="0">
                                    <tr>
                                       <td width="425" height="20" align="left" valign="middle">' . $comment . '</td>
                                       <td align="right" valign="middle" width="125" style="border-top:dotted 1px #000"><strong>$' . number_format($items['subtotal'], 2) . '</strong></td>
                                    </tr>
                                 </table>
                              </td>
                           </tr>
                     </table>
                  </td>
               </tr>
               ';
                    }
                }
            }
            return $order;
        }
    }

    /* end description for customer pdf */

    function _getVoucherDiscount($voucher_code)
    {
        return $this->O_Model->getVoucherDiscount($voucher_code);
    }

//_getVoucherDiscount
    //

   function checkvalue()
    {
        //$total = str_replace("$", '', strip_tags($this->input->post('total_payment')));
        //$discount = -1 * (int)str_replace("$", '', strip_tags($this->input->post('discount')));

        $discountType = $this->input->post('discountType');
//var_dump('checkvalue: ' . $discountType);
        $voucher_id   = $this->voucher_code->getCouponByCode($discountType);

        if (is_object($voucher_id)) {
            $new_hash = encode_voucher_cookie($voucher_id->id);
        }
        else {
            $new_hash   = $discountType;
        }
        //echo strip_tags($total);
        $paymethod  = $this->input->post('paymethod');
        $cart_total = 0;
        foreach ($this->cart->contents() as $items) {
            if ($items['options']['loyalty'] != 'lp') {
                $cart_total += $items['subtotal'];
            }
        }
        $total     = $cart_total;
        /**   low order chk * */
        $fee       = $this->C_Model->getMinimumOrderFee(); //print_r($fee);
        $minamt    = $fee->min_order_amt;
        $low_order = $fee->order_less;

        $valid   = $ccvalid = $ppvalid = 0;

        if ($low_order > 0) { //echo 'in low';
            if ($total < $minamt) {
                $valid = 1;
            }
            else {
                $valid  = 0;
            }//if
            $result = array('minamount'     => $minamt, 'success'       => $valid, 'pmethod'       => 'delivery', 'discount'      => $new_hash, 'low_order_fee' => $low_order);
        }
        else {
            $valid  = 0;
            $result = array('minamount'     => $minamt, 'success'       => $valid, 'pmethod'       => 'delivery', 'discount'      => $new_hash, 'low_order_fee' => $low_order);
        }
//echo $valid;
        if ($valid == 0) /**   chk for cc n pp   * */ {
            /**  Low order for CC * */
            if ($paymethod == 'Credit Card Over Phone' || $paymethod == 'Credit Card Online') {  //echo 'in cc';
                if (!empty($fee) && !empty($fee->cc)) {
                    if ($total < $fee->cc)
                        $ccvalid = 1;
                    else
                        $ccvalid = 0;
                }
                $result  = array('minamount'     => $fee->cc, 'cc'            => $ccvalid, 'pmethod'       => $paymethod, 'discount'      => $new_hash, 'low_order_fee' => $low_order);
            }//if

            /**  Low order for paypal * */
            //   $paymethod = $this->input->post('paymethod');
            if ($paymethod == 'Paypal') {// echo 'in pp';
                if (!empty($fee) && !empty($fee->paypal)) {
                    if ($total < $fee->paypal)
                        $ppvalid = 1;
                    else
                        $ppvalid = 0;
                }
                $result  = array('minamount'     => $fee->paypal, 'pp'            => $ppvalid, 'pmethod'       => $paymethod, 'discount'      => $new_hash, 'low_order_fee' => $low_order);
            }//if
        }
        // $result = array('success'=>$valid, 'minamount'=>$minamt, 'cc' => $ccvalid, 'ccminamt' => $fee->cc, 'pp' => $ppvalid, 'ppminamt' => $fee->paypal);
        echo json_encode($result);
    }

//_checkminOrderamount

    function checkminvalue()
    {
        ini_set("display_errors", 1);
        error_reporting(E_ALL);
        //echo strip_tags($total);
        $paymethod = $this->input->post('paymethod');

        $fee        = $this->C_Model->getMinimumOrderFee(); //print_r($fee);
        $cart_total = 0;
        $result     = array();
        foreach ($this->cart->contents() as $items) {
            if ($items['options']['loyalty'] != 'lp') {
                $cart_total += $items['subtotal'];
            }
        }
        $total = $cart_total;

        /**  Low order for CC * */
        if ($paymethod == 'Credit Card Over Phone' || $paymethod == 'Credit Card Online') {  //echo 'in cc';
            if (!empty($fee) && !empty($fee->cc)) {
                if ($total < $fee->cc)
                    $ccvalid = 1;
                else
                    $ccvalid = 0;
            }
            $result  = array('minamount' => $fee->cc, 'cc'        => $ccvalid, 'pmethod'   => $paymethod);
        }//if

        /**  Low order for paypal * */
        //   $paymethod = $this->input->post('paymethod');
        if ($paymethod == 'Paypal') {// echo 'in pp';
            if (!empty($fee) && !empty($fee->paypal)) {
                if ($total < $fee->paypal)
                    $ppvalid = 1;
                else
                    $ppvalid = 0;
            }
            $result  = array('minamount' => $fee->paypal, 'pp'        => $ppvalid, 'pmethod'   => $paymethod);
        }//if

        /**   low order chk * */
        if ($paymethod == 'Cash On Delivery') {
            $fee       = $this->C_Model->getMinimumOrderFee(); //print_r($fee);
            $minamt    = $fee->min_order_amt;
            $low_order = $fee->order_less;

            $valid   = $ccvalid = $ppvalid = 0;

            //if ($low_order == 0) { //echo 'in low';
//                if ($total < $minamt) {
//                    $valid = 1;
//                } else {
//                    $valid = 0;
//                }//if
//                $result = array('minamount' => $minamt, 'success' => $valid, 'pmethod' => 'delivery');
//            } else {
//                $valid = 0;
            $result = array('minamount' => $minamt, 'success'   => $valid, 'pmethod'   => 'delivery');
            //    }
        }//if
        // $result = array('success'=>$valid, 'minamount'=>$minamt, 'cc' => $ccvalid, 'ccminamt' => $fee->cc, 'pp' => $ppvalid, 'ppminamt' => $fee->paypal);
        echo json_encode($result);
    }

    /**
     * Stores the posted user session, either by inserting
     * or updating the content
     *
     * @param   sessionId   int|null    If required a sessionId
     *
     * @return  string                  The insert id if available, or
     *                                  boolean to show success/failure
     */
    public function storeOrderSession()
    {
        $this->load->model('OrderSession_Model', 'OSess');

        $storable = $this->_buildStorableSessionData();

        $result = $this->OSess->store($storable);

        echo (string) $result;
    }

    /**
     * Retrieves a previously stored session
     *
     * @param   sessionId   int     The required session id
     *
     * @return  string              The stored data json_coded or empty object
     *
     */
    public function retreiveOrderSession($sessionId = null)
    {

        $this->load->model('OrderSession_Model', 'OSess');

        $data = $this->OSess->retrieve($sessionId);

        $this->OSess->remove($sessionId);

        echo $data;
    }

    private function _buildStorableSessionData()
    {

        $data                 = array();
        $data['tastycode']    = $this->input->post('tastycode');
        $data['payment']      = $this->input->post('payment');
        $data['deliveryType'] = $this->input->post('deliveryType');
        $data['discountName'] = $this->input->post('discountName');
        $data['order_option'] = $this->input->post('order_option');
        $data['order_date']   = $this->input->post('order_date');
        $data['tmUserId']     = $this->phpsession->get('tmUserId');

        return $data;
    }

    function placeCookie($parameter = null)
    {
        $tempArray = explode(';', urldecode($parameter));
        foreach ($tempArray as $value) {
            $cookieParameters = explode('=', $value);
            if ( isset($cookieParameters[1]) )
            {
                set_cookie($cookieParameters[0], $cookieParameters[1], 86400);
                echo 'var vr_' . md5( $cookieParameters[0] ) . ' = "' . md5( $cookieParameters[1] ) . '";';
            }
        }
    }

    function removeCookie($parameter = null)
    {
        $tempArray = explode(';', urldecode($parameter));
        foreach ($tempArray as $value) {
            $cookieParameters = explode('=', $value);
            if ( isset($cookieParameters[1]) )
            {
                set_cookie($cookieParameters[0], $cookieParameters[1], 'none');
                echo 'var vr_' . md5( $cookieParameters[0] ) . ' = "' . md5( $cookieParameters[1] ) . '";';
            }
        }
        $this->phpsession->clear('tmUserId');
        $this->phpsession->clear('auto_login_flag');
    }
    //VV START SHORTURL FUNCTION
    public function shortUrl($url) {

        $signature ='11fd2de2c6'; //secret API key
        $format = 'json';               // output format: 'json', 'xml' or 'simple'
        $api_url = 'http://ktu.com.au/yourls-api.php';
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $api_url);
        curl_setopt($ch, CURLOPT_HEADER, 0);            // No header in the result
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // Return, do not echo result
        curl_setopt($ch, CURLOPT_POST, 1);              // This is a POST request
        curl_setopt($ch, CURLOPT_POSTFIELDS, array(     // Data to POST
                'url'      => $url,
                'format'   => $format,
                'action'   => 'shorturl',
                'signature' => $signature
            ));
        // Fetch and return content
        $data = curl_exec($ch);
        curl_close($ch);
        $obj = json_decode($data);
        return $obj->{'shorturl'};
    }
    //VV STOP SHORTURL FUNCTION
}

//end of class
