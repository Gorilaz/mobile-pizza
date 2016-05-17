<?php
/**
*@property Products_model $products_model
*/

class checkout extends WMDS_Controller {

    function __construct()
    {
        parent::__construct();

        $this->load->model('general');
    }


    /**
     * Checkout page
     */
    public function index() {

        $this->load->model('products_model');
//        $cart = $this->cart->contents();
//        $productsIds = array();
//        foreach($cart as $c){
//            $products = explode('_', $c['id']);
//            foreach($products as $p){
//                $productsIds[] = $p;
//            }
//        }
//        $productsHasDiscount = $this->products_model->productsHasDiscount($productsIds);

        
        /** verify if holliday fee */
        $holiday = $this->products_model->getPublicHoliday();

        if( !empty($holiday) )
        {
            $arr_hol = explode(',', $holiday);

            $dateNow = date('d/m/Y');

            foreach( $arr_hol as $holiday )
            {
                if( $holiday == $dateNow )
                {
                    $holidayFee = $this->products_model->getHolidayFee();

                    $totalCart = $this->cart->total();

                    $holidayPrice = number_format( ( ($totalCart / 100) * $holidayFee), 2, '.', '' );

                    $this->twiggy->set('holidayFee', array(
                        'discount'    => $holidayFee,
                        'price'  => $holidayPrice
                    ));

                    $this->session->set_userdata('holiday_fee', $holidayFee);
                }
            }
        }
        /** end holliday fee */

        /** order text from admin */
        $this->load->model('order_model');
        $text = $this->order_model->getAdminText();
        $this->twiggy->set('text', $text);

        /**
         * Payment Methods &&
         * Payment min amount/taxes
         */
        $paymentMethods = $this->order_model->getPaymentMethods();
        $paymentRules   = $this->order_model->getMinOrder();

        /**
         * Send cart data to view
         */
        $items = $this->getProductIdsWithCoupon();

        if( in_array('1', $items) )
        {
            /** verify if products has discount */
            $this->twiggy->set('haveCoupon', 'havecoupon');

            $logged = $this->session->userdata('logged');

            /**
             * Coupons
             */
            if( $logged )
            {
                $coupons = $this->products_model->getCoupons($logged['userid']);
            }
            else
            {
                $coupons = $this->products_model->getCoupons();
            }

            /* $hasSocialLocker = $this->products_model->getSocialLocker();

            if( $hasSocialLocker )
            {
                $this->twiggy->set('socialLoker', $hasSocialLocker->couponcode);
            } */

            $this->twiggy->set('coupons', $coupons);
            /** end Coupons */
        }
        
        $datesForOrder = $this->general->shopSchedule();

        $cartContents = $this->cart->contents();

        foreach( $cartContents as $key => $cart_item )
        {
            if( $cart_item['product_type'] === 'half' )
            {
                $ids_parts = explode('_', $cart_item['id']);

                if( is_array($ids_parts) && 
                    isset($ids_parts[0]) && 
                    isset($ids_parts[1]) )
                {
                    $first_half_id = $ids_parts[0];
                    $second_half_id = $ids_parts[1];

                    $cartContents[$key]['first_half'] = $this->products_model->getProductById($first_half_id);
                    $cartContents[$key]['second_half'] = $this->products_model->getProductById($second_half_id);
                }
            }
        }

        $this->twiggy->set(array(
                'productsWithCoupon'          => $items,
                'cart'      => $cartContents,
                'itemsNo'   => $this->cart->total_items(),
                'total'     => $this->cart->total(),
                'paymentMethods' => $paymentMethods,
                'rules'     => json_encode($paymentRules),
                'schedule'  => array('forTwig' => $datesForOrder['forTwig'], 'forJquery' => json_encode($datesForOrder['forJquery'])), 
                'time_is_over' => json_encode($datesForOrder['time_is_over']), 
                'start_time' => $datesForOrder['start_time']
            )
        );

        $this->twiggy->set('page', array(
            'title'  => 'Checkout',
            'role'   => 'page',
            'theme'  => 'a',
            'id'     => 'page-checkout'
        ));

        $this->twiggy->template('checkout/order-review')->display();
    }

    /**
     * Verify if all products has coupon
     */
    public function productsHaveCoupon(){
        $hasCoupon = false;

        $this->load->model('products_model');

        $cart = $this->cart->contents();

        $product_ids = array();

        foreach( $cart as $product )
        {
            $ids = explode('_', $product['id']);

            foreach( $ids as $id )
            {
                $product_ids[] = $id;
            }
        }

        if( !empty($product_ids) )
        {
            $products = $this->db->select('has_coupon')->where_in('product_id', $product_ids)->get('tbl_product')->result();

            if( !empty($products) )
            {
                foreach( $products as $product )
                {
                    if( !empty($product->has_coupon) )
                    {
                        $hasCoupon = $hasCoupon;

                        break;
                    }
                }
            }
        }

        return $hasCoupon;
    }
    
    /**
     * Detect all products with coupon
     */
    public function getProductIdsWithCoupon()
    {
        $out = array();

        $this->load->model('products_model');

        $cart = $this->cart->contents();

        $product_ids = array();

        foreach( $cart as $product )
        {
            $ids = explode('_', $product['id']);

            foreach( $ids as $id )
            {
                $product_ids[] = $id;
            }
        }

        if( !empty($product_ids) )
        {
            $products = $this->db->select('product_id, has_coupon')->where_in('product_id', $product_ids)->get('tbl_product')->result();

            if( !empty($products) )
            {
                foreach( $products as $product )
                {
                    $out[$product->product_id] = $product->has_coupon;
                }
            }
        }

        return $out;
    }


    /**
     * Get Coupons (ajax)
     */
    public function getCoupons() {
        $this->load->model('general');

        $coupon = $this->input->post('coupon');

        $coupons = $this->general->getCoupons($coupon);

        if( $coupons )
        {
            echo json_encode($coupons);
        }
        else
        {
            echo json_encode('false');
        }
    }

    /**
     * Payment page / Auth page
     */
    public function payment()
    {
        $this->load->library('session');
        $this->load->model('general');
        $this->load->model('order_model');

        //$firstPointLogin = $this->session->userdata('firstPointLogin');
        //if( !$firstPointLogin )
        //{
            $this->session->set_userdata('firstPointLogin', 'order');
        //}

        $surcharge = $this->order_model->getMinOrder();

        $total = $this->cart->total();

        if( empty($total) ) {
            // Back to menu when Hardware back button press
            redirect(base_url() . 'menu');
        }

        if( $this->input->post() )
        {
            $post = $this->input->post();

            if($post['orderHash'] != $this->session->userdata('session_id')) {
                $ip = $this->get_client_ip();
                $browser = $_SERVER['HTTP_USER_AGENT'];

                $session = unserialize($this->general->getSession($post['orderHash'], $ip, $browser));

                /** if user was logged */
                if(isset($session['logged']) && !empty($session['logged'])){
                    $this->session->unset_userdata('logged');
                    $this->session->set_userdata('logged', $session['logged']);
                }else {
                    $this->session->unset_userdata('logged');
                }

                /** if session storeOpen */
                if(isset($session['storeOpen']) && !empty($session['storeOpen'])){
                    $this->session->unset_userdata('storeOpen');
                    $this->session->userdata('storeOpen', $session['storeOpen']);
                }

                /** if session siteSetting */
                if(isset($session['siteSetting']) && !empty($session['siteSetting'])){
                    $this->session->unset_userdata('storeOpen');
                    $this->session->userdata('siteSetting', $session['siteSetting']);
                }

                /** if session user_data */
                if(isset($session['user_data']) && !empty($session['user_data'])){
                    $this->session->unset_userdata('user_data');
                    $this->session->userdata('user_data', $session['user_data']);
                }

                /** create cart */
                $oldCart = $session['cart_contents'];
                $this->cartInsert($oldCart);

                /** command is from outher site (yes/no) */

                $backSite = 'yes';
                $this->session->set_userdata('backUrl',$backSite);
            } else {

                /** command is from outher site (yes/no) */
                $backSite = 'no';
                $this->session->set_userdata('backUrl',$backSite);
            }

            if(!isset($post['when']) || $post['when'] == null){
                $post['when'] = 'Later';
            }

            $check = array(
                'payment'   => $post['payment'],
                'delivery'  => $post['delivery'],
                'when'      => $post['when'],
                'comment'   => $post['comment']
            );

            /** save checkout on cart*/
            if(!empty($post['coupon']) && is_numeric($post['coupon'])){
                $coupon                  = $this->general->getCoupon($post['coupon']);
                $check['couponName']     = $coupon['couponcode'];
                $check['couponDiscount'] = $coupon['discountper'];

            } else if(!empty($post['coupon']) && isset($post['outher-coupon'])){
                $check['couponName']     = $post['outher-coupon'];
                $check['couponDiscount'] = '';
            }

            if(!empty($post['date'])){
                $check['date'] = $post['date'];
            }

            if(!empty($post['time'])){
                $check['time'] = $post['time'];
            }

            $this->load->library('cart');
            $this->session->set_userdata('checkout', $check);
            /** end cart */

            $paymentFee = array();
            /** payment type */
            if($post['payment'] == 3){
                if($surcharge->ccamt_flag == 'A'){
                    $paymentFee = array(
                        'name'  => 'Credit Card',
                        'value' => $surcharge->ccamt,
                    );
                } else {
                    $ccFee = number_format(($total/100)* $surcharge->ccamt, 1, '.', '');;
                    $paymentFee = array(
                        'name'  => 'Credit Card',
                        'value' => $ccFee,
                    );
                }
                $pg = 'credit-card';
            } elseif($post['payment'] == 1){
                $paymentFee = '';
                $pg = 'cash';
            } elseif($post['payment'] == 4) {
                if($surcharge->palamt_flag == 'A'){
                    $paymentFee = array(
                        'name'  => 'Pay Pal',
                        'value' => $surcharge->palamt,
                    );
                } else {
                    $paypalFee = number_format(($total/100)* $surcharge->palamt, 1, '.', '');;
                    $paymentFee = array(
                        'name'  => 'Pay Pal',
                        'value' => $paypalFee,
                    );
                }
                $pg = 'paypal';
            }

            /** save in session credit-card or paypal fee */
            $this->session->set_userdata('surchange', $paymentFee);
            /** end */


            $this->session->set_userdata('pg', $pg);
        }
        else
        {
            $pg = $this->session->userdata('pg');
        }

        $this->twiggy->set('pg', $pg);

        $paymentFee2 = $this->session->userdata('surchange');

        if( !empty($paymentFee2) )
        {
            $this->twiggy->set('paymentFee', $paymentFee2);
        }

        /** holiday fee */
        $holiday = $this->session->userdata('holiday_fee');

        if( !empty($holiday) )
        {
            $this->twiggy->set('holidayFee', $holiday);
        }

        /** total */
        $this->twiggy->set('total', $total);

        /** coupon */
        $check = $this->session->userdata('checkout');

        $this->twiggy->set('check', $check);

        $logged = $this->session->userdata('logged');

        if(isset($check['couponName']) && isset($check['couponDiscount']))
        {
            $hasCoupon = true;

            if( ( $check['couponName'] ===  'FIRSTORDER' ) && !empty($logged) )
            {
                $this->load->model('products_model');

                $coupons = $this->products_model->getCoupons($logged['userid']);

                if( !isset($coupons['firstOrder']) || empty($coupons['firstOrder']) )
                {
                    unset($check['couponName']);
                    unset($check['couponDiscount']);

                    $this->twiggy->set('firstOrderDeleted', true);

                    $this->session->set_userdata('checkout', $check);

                    $hasCoupon = false;
                }

                if( $hasCoupon )
                {
                    $this->twiggy->set('coupon', array(
                        'name' => $check['couponName'], 
                        'discount' => $check['couponDiscount']
                    ));
                }
            }
            else
            {
                $this->twiggy->set('coupon', array(
                    'name' => $check['couponName'], 
                    'discount' => $check['couponDiscount']
                ));
            }
        }

        /** delivery fee */
        if( $check['delivery'] === 'P' )
        {
            $this->twiggy->set('hasDeliveryFee', 0);
        }
        else
        {
            $this->twiggy->set('hasDeliveryFee', 1);
        }
        /** verify if is logged */

        if( !empty($logged) )
        {
            /** delivery fee */
            $this->twiggy->set('logged', $logged);

            $suburb = 0;

            if( isset($logged['suburb']) )
            {
                $suburb = $logged['suburb'];
            }

            $this->load->model('order_model');

            $delivery_fee = $this->order_model->getDeliveryFee($suburb);

            $this->twiggy->set('delivery_fee', $delivery_fee);
        }

        $cart_items = $this->cart->contents();

        $items = $this->getProductIdsWithCoupon();

        foreach( $cart_items as $key => $cart_item )
        {
            if( $cart_item['product_type'] === 'half' )
            {
                $ids_parts = explode('_', $cart_item['id']);

                if( is_array($ids_parts) && isset($ids_parts[0]) && isset($ids_parts[1]) )
                {
                    $cart_items[$key]['first_half'] = $this->products_model->getProductById($ids_parts[0]);
                    $cart_items[$key]['second_half'] = $this->products_model->getProductById($ids_parts[1]);
                }
            }
            else
            {
                $cart_items[$key]['coupon'] = $items[$cart_item['id']];
            }
        }

        $this->twiggy->set('cart_items', $cart_items);

        $totalDiscount = 0;

        if( !empty($check['couponDiscount']) )
        {
            foreach( $cart_items as $key => $cart_item )
            {
                if( isset($half) && $half !== false )
                {
                    $half = false;
                }

                if( $cart_item['product_type'] === 'half' )
                {
                    $half = 'first';

                    foreach( $cart_item['options'] as $option )
                    {
                        if( $half != 'second' && 
                            strpos(strtolower($option['name']), 'second half') !== false )
                        {
                            $half = 'second';
                        }

                        if( $half == 'first' && $cart_item['first_half']->has_coupon == 1 )
                        {
                            $totalDiscount += ( ( ( (double) $option['price'] * (integer) $cart_item['qty'] ) / 100 ) * (integer) $check['couponDiscount'] );
                        }

                        if( $half == 'second' && $cart_item['second_half']->has_coupon == 1 )
                        {
                            $totalDiscount += ( ( ( (double) $option['price'] * (integer) $cart_item['qty'] ) / 100 ) * (integer) $check['couponDiscount'] );
                        }
                    }
                }
                else
                {
                    if( $cart_item['coupon'] == 1 )
                    {
                        $totalDiscount += ( ( ( (double) $cart_item['price'] * (integer) $cart_item['qty'] ) / 100 ) * (integer) $check['couponDiscount'] );
                    }
                }
            }
        }

        $totalWithDiscount = $total - $totalDiscount;

        $min_order_amt = (double) $surcharge->min_order_amt;
        $order_less = (double) $surcharge->order_less;

        if( $order_less > 0 )
        {
            if( $min_order_amt > $totalWithDiscount )
            {
                $low_order = $order_less;
            }
            else
            {
                $low_order = 0;
            }
        }
        else
        {
            $low_order = 0;
        }

        $this->twiggy->set('low_order', $low_order);
        $this->session->set_userdata('low_order', $low_order);

        /** end */

        /** have sms verification 
        $this->load->model('security_model');
        $sms = $this->security_model->smsSettings();
        $this->twiggy->set('sms', $sms['sms_verification']);
/*
        $suburbs = $this->general->getSub();
        $this->twiggy->set('static',array(
            'suburb' => $suburbs,
        ));
*/

        $this->twiggy->set('page', array(
            'title' => 'Payment', 
            'role' => 'page', 
            'theme' => 'a', 
            'id' => 'page-payment'
        ));

        $this->twiggy->set('pageposition', 'order');

        $out = prepareProfilePage($this->twiggy);

        $out->template('checkout/order-login')->display();
    }

    /**
     * Get IP
     * @return string
     */
    private function  get_client_ip() {
        $ipaddress = '';
        if (getenv('HTTP_CLIENT_IP'))
            $ipaddress = getenv('HTTP_CLIENT_IP');
        else if(getenv('HTTP_X_FORWARDED_FOR'))
            $ipaddress = getenv('HTTP_X_FORWARDED_FOR'&quot);
        else if(getenv('HTTP_X_FORWARDED'))
            $ipaddress = getenv('HTTP_X_FORWARDED');
        else if(getenv('HTTP_FORWARDED_FOR'))
            $ipaddress = getenv('HTTP_FORWARDED_FOR');
        else if(getenv('HTTP_FORWARDED'))
            $ipaddress = getenv('HTTP_FORWARDED');
        else if(getenv('REMOTE_ADDR'))
            $ipaddress = getenv('REMOTE_ADDR');
        else
            $ipaddress = 'UNKNOWN';

        return $ipaddress;
    }

    /**
     * Create new cart
     * @param $oldCart
     */
    private function cartInsert($oldCart){

        /** reset cart */
        $this->cart->destroy();

        /** insert products */
        foreach($oldCart as $item){
            $this->cart->insert($item);
        }
    }

    /**
     * SMS Mobile
     */
    public function smsMobile(){
        $this->load->model('security_model');
        $post = $this->input->post();

        $sms = $this->security_model->smsSettings();
        $mail_content = $this->security_model->getEmailById(3);

        // $code = rand(1000, 9999);
        $code = 1111;

        $message = $mail_content->message;

        $message = str_replace("[[email]]", $post['email'], $message);
        $message = str_replace("[[code]]", $code, $message);
        $message = str_replace("[[firstname]]", $post['fname'], $message);
        $message = str_replace("[[lastname]]", $post['lname'], $message);

        $content_message = str_replace("[sitename]", base_url(), $message);

        if( $this->config->item('sms_service') === 'telerivet' )
        {
            $sms_template = strip_tags(str_replace('<br />', "\n", $content_message));

            $this->Telerivet_Project->sendMessage(array(
                'content' => $sms_template, 
                'to_number' => $this->input->post('mobile')
            ));
        }
        else
        {
            $this->load->library('email');

            $from = $sms['sending_address'];
            $from_name = 'admin_tastypizza';

            $to = $this->input->post('mobile') . '@' . $sms['domain_name'];

            $this->email->from($from, $from_name);
            $this->email->to($to);

            $this->email->subject($sms['subject']);

            $email_template = str_replace('<br />', "\n", nl2br(utf8_encode($content_message)));

            $this->email->message($email_template);

            $this->email->send();
        }

        $this->session->set_userdata('sms_code', $code);
    }


    /**
     * Ajax method for send SMS verification code
     */
    public function verifyMobile()
    {
        $this->load->model('security_model');
        $sms = $this->security_model->smsSettings();
        $post = $this->input->post();
        $mail_content = $this->security_model->getEmailById(3); // 3 - id for verify email template
        $code = rand(1000, 9999);

        /* TODO: Placeholder for check */
        $code = 1111;
        /* -- */

        $user = $this->session->userdata('logged');
        $user['new_mobile'] = $post['mobile'];
        $this->session->set_userdata('logged', $user);

        $message = $mail_content->message;

        $message = str_replace("[[email]]", $post['email'], $message);
        $message = str_replace("[[code]]", $code, $message);
        $message = str_replace("[[firstname]]", $post['fname'], $message);
        $message = str_replace("[[lastname]]", $post['lname'], $message);

        $content_message = str_replace("[sitename]", base_url(), $message);

        if( $this->config->item('sms_service') === 'telerivet' )
        {
            $sms_template = strip_tags(str_replace('<br />', "\n", $content_message));

            $this->Telerivet_Project->sendMessage(array(
                'content' => $sms_template, 
                'to_number' => $post['mobile']
            ));
        }
        else
        {
            $this->load->library('email');

            $from = $sms['sending_address'];
            $from_name = 'admin_tastypizza';

            $to = $post['mobile'] . '@' . $sms['domain_name'];
            $this->email->from($from, $from_name);
            $this->email->to($to);
            $this->email->subject($sms['subject']);

            $email_template = str_replace('<br />', "\n", nl2br(utf8_encode($content_message)));
            $this->email->message($email_template);
            $this->email->send();
        }

        /*  */
        $this->session->set_userdata('sms_code', $code);
    }

    /**
     * Ajax method verify sms code
     */
    public function verifyCode(){
        $this->load->model('security_model');
        $code      = $this->input->post('code');
        $sess_code = $this->session->userdata('sms_code');
        $user = $this->session->userdata('logged');
        if($code == $sess_code && isset($user['new_mobile']))
        {
            $user = $this->security_model->changeMobile($user['new_mobile'], $user['email']);
            $this->session->set_userdata('logged', $user);
            $this->session->unset_userdata('sms_code');
            echo json_encode(array(
                'valid' => true
            ));
        } else {
            echo json_encode(array(
                'valid' => false
            ));
        }
    }



}