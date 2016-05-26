<?php
/**
 * @property Order_model $order_model
 */
class Order extends WMDS_Controller{

    function __construct(){
        parent::__construct();

        $this->load->library('cart');
        $this->load->model('order_model');
        $this->load->model('Sitesettings_model', 'SS_Model');
        $this->load->model('Register_Model', 'R_Model');
        $this->load->model('Category_Model', 'C_Model');
        $this->load->model('Coupon_model', 'voucher_code');
    }

    /**
     * Save order in database
     */
    public function save_order($payment = null){
        $cart = $this->cart->contents();

        /** start Description */
        $html = '';

        foreach( $cart as $c )
        {
            $html .= '<div class="mar ovfl-hidden">
                            <div class="fl"><strong>' . $c['name'] . '</strong></div>
                        </div>

                        <div class="mar ovfl-hidden">
                            <div class="fl">* Qty :' . $c['qty'] . '</div>
                            <div class="fr"></div>
                        </div>';

            foreach( $c['options'] as $option )
            {
                 $html .= '<div class="mar smlTxt ovfl-hidden">
                     <div class="mar ovfl-hidden" style="margin-bottom:0px;">
                          <div class="fl">' . $option['name'] . '</div>
                     </div>
                 </div>
                 <div class="mar smlTxt ovfl-hidden"></div>';
            }
        }

        $check = $this->session->userdata('checkout');

        if( !isset($check['delivery']) )
        {
            // Back to menu when Hardware back button press
            redirect(base_url() . 'menu');
        }

        /** Counpon */
        if( isset($check['couponDiscount']) )
        {
            $html .= '<div class="mar ovfl-hidden">
                            <div class="fl">Coupon: ' . $check['couponName'] . ': ' . $check['couponDiscount'] . '%</div>
                        </div>';
        }
        else if( isset($check['couponName']) )
        {
            $html .= '<div class="mar ovfl-hidden">
                            <div class="fl">Coupon:' . $check['couponName'] . '</div>
                        </div>';
        }

        /** Comments */
        $html .= '<div class="mar ovfl-hidden">
                            <div class="fl">Order Commnets:' . $check['comment'] . '</div>
                        </div>';
        /** end Description */

        // TODO: coupon discount, comment
        $user = $this->session->userdata('logged');

        $cart_items = $this->cart->contents();

        $productIdsWithCoupon = array();

        $product_ids = array();

        foreach( $cart_items as $cart_item )
        {
            $ids = explode('_', $cart_item['id']);

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
                    $productIdsWithCoupon[$product->product_id] = $product->has_coupon;
                }
            }
        }

        $this->load->model('products_model');

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
                $cart_items[$key]['coupon'] = $productIdsWithCoupon[$cart_item['id']];
            }
        }

        $surcharge = $this->order_model->getMinOrder();

        $total = $this->cart->total();

        $newTotal = $total;

        $min_order_amt = (double) $surcharge->min_order_amt;
        $order_less = (double) $surcharge->order_less;

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
                        if( $half !== 'second' && 
                            strpos(strtolower($option['name']), 'second half') !== false )
                        {
                            $half = 'second';
                        }

                        if( $half === 'first' && $cart_item['first_half']->has_coupon == 1 )
                        {
                            $totalDiscount += ( ( ( (double) $option['price'] * (integer) $cart_item['qty'] ) / 100 ) * (integer) $check['couponDiscount'] );
                        }

                        if( $half === 'second' && $cart_item['second_half']->has_coupon == 1 )
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

        $discount = number_format($totalDiscount, 2, '.', '');

        $newTotal -= $totalDiscount;

        $fees = 0;

        if( $order_less > 0 )
        {
            if( $min_order_amt > $newTotal )
            {
                $newTotal += $order_less;

                $fees += $order_less;
            }
        }

        /** total + suburb delivery fee */
        if( isset($check['delivery']) && 
            $check['delivery'] === 'D' && 
            isset($user['suburb']) )
        {
            $delivery_fee = $this->order_model->getDeliveryFee($user['suburb']);

            if( !empty($delivery_fee) )
            {
                $newTotal += (double) $delivery_fee;

                $fees += (double) $delivery_fee;
            }
        }

        /** verify if holliday fee */
        $holidayFee = $this->session->userdata('holiday_fee');

        if( !empty($holidayFee) )
        {
            $holidayPrice = ( ( $total / 100 ) * (double) $holidayFee );

            $newTotal += $holidayPrice;

            $fees += $holidayPrice;
        }
        /** end holliday fee */

        /** credit-card, paypal fee */
        $surchargeOrder = $this->session->userdata('surchange');

        if( !empty($surchargeOrder) )
        {
            $newTotal += (double) $surchargeOrder['value'];

            $fees += (double) $surchargeOrder['value'];
        }
        /** end */

        $fees = number_format($fees, 2, '.', '');

        /*
         * Calculate total points used in order
         */
        $points_used = 0;

        foreach( $cart as $item )
        {
            if( isset($item['points']) )
            {
                $points_used += $item['points'];
            }
        }

        $check['loyalityPointsUsed'] = $points_used;

        if( $payment == 'paypal' )
        {
            $order_id = $this->order_model->saveOrder($check, $newTotal, $discount, $fees, $user['userid'], $html, 'pending');

            $paypalFields = array(
                'total'   => $newTotal,
                'orderId' => $order_id
            );

            $this->session->set_userdata('paypal', $paypalFields);

            redirect(base_url().'paypal');
        } else {
            $order_id    = $this->order_model->saveOrder($check, $newTotal, $discount, $fees, $user['userid'], $html, 'save');

            /** sms confirmation */
            $this->confirmationSms($order_id);

            /** save shopping cart*/
            $this->order_model->saveShoppingCart($cart, $order_id);
            /** end save shopping cart */

            $order_again = $this->remove_cart_rowid($cart);

            $this->order_model->save_order_again($order_again, $order_id, $user['userid']);

            $name = $this->order_pdf($order_id);

            $this->order_model->updatePdf($order_id, $name);

            /** conmmand is from outher site*/
            $back_url = $this->session->userdata('backUrl');

            if( $back_url === 'yes' )
            {
                $session_id = $this->session->userdata('session_id');

                redirect(base_url() . 'order-success/' . $session_id);
            }
            else
            {
                redirect(base_url() . 'order-success');
            }
        }
    }

    /**
     * SMS Confirmation
     * @param $order_id
     */
    private function confirmationSms($order_id)
    {
        $this->load->model('security_model');

        $sms = $this->security_model->smsSettings();

        if( $sms['sms_confirmation'] === 'enable' )
        {
            $real_id = $this->security_model->getRealId($order_id);

            $content_message = str_replace("[[order_no]]", $real_id, $sms['confirmation_text']);
            $content_message = str_replace("[[customer_number]]", $sms['mob_number'], $content_message);

            if( $this->config->item('sms_service') === 'telerivet' )
            {
                $content_message = strip_tags(str_replace("<br />", "\n", $content_message));

                $this->Telerivet_Project->sendMessage(array(
                    'content' => $content_message, 
                    'to_number' => $sms['mob_number']
                ));
            }
            else
            {
                $this->load->library('email');
                $from = $sms['sending_address'];

                $to = $sms['mob_number'] . '@' . $sms['domain_name'];

                $this->email->from($from);
                $this->email->to($to);
                $content_message = str_replace("[[order_no]]", $real_id, $sms['confirmation_text']);
                $content_message = str_replace("[[customer_number]]", $sms['mob_number'], $content_message);

                $this->email->subject('Order no'.$real_id );

                $this->email->message($content_message);

                $this->email->send();
            }
        }
    }

    /**
     * Remove Cart Rows Id
     * @param $items cart
     * @return array
     */
    private function remove_cart_rowid($items) {
        $out = array();
        foreach ($items as $id=>$item) {
            unset($items[$id]['rowid']);
            $out[] = $items[$id];
        }
        return $out;
    }



    /**********  PDF  *************/
    /**
     * Save order pdf
     * @param $orderId
     */
    public function order_pdf($orderId){
        require_once(FCPATH."application/helpers/dompdf/dompdf_config.inc.php");
        require_once(FCPATH. 'application/libraries/phpqrcode/phpqrcode.php');

        $user = $this->session->userdata('logged');
        $subUrb = $this->order_model->getSubUrb($user['suburb']);
        $order = $this->order_model->getOrder($orderId);
        $checkout = $this->session->userdata('checkout');

        if( $checkout['payment'] == 1 )
        {
            $paid = 'NOT PAID';
        }
        else
        {
            $paid = 'PAID';
        }

        if( $checkout['delivery'] == 'D' )
        {
            $delivery = 'Home Delivery / '.$paid;
        }
        else
        {
            $delivery = 'In-store Pickup / '.$paid;
        }

        $data = array();

        $data['order_option'] = $order['order_option'];

        if( $data['order_option'] == 'D' )
        {
            $data['order_option'] = 'Home Delivery';
        }
        else if( $data['order_option'] == 'P' )
        {
            $data['order_option'] = 'Pickup';
        }

        $data['order_number'] = $this->order_model->getOrderNumber();

        $data['p_txt_file_item_desc'] = $this->p_getTextFileItemsDescription(); //for gprs printer

        $data['discount'] = $order['discount'];
        $data['p_discount'] = ''; //VV for gprs printer

        $vdisc = $this->order_model->getCouponDiscDescription($order['voucher_code']);
        $vdata = $this->voucher_code->getCouponById($order['voucher_code']);

        if( is_object($vdata) )
        {
            $data['coupon_type'] = $vdata->coupontype;
            $data['voucher_code'] = $vdata->couponcode;
        }

        if( !empty($data['coupon_type']) && $data['coupon_type'] == 'firstorder' )
        {
            $data['p_discount'] = 'First Order Discount:  -$' . $data['discount']; //VV for gprs printer
        }

        if( !empty($data['coupon_type']) && $data['coupon_type'] == 'allorders' )
        {
            $data['p_discount'] = 'Online Order Discount:  -$' . $data['discount']; //VV for gprs printer
        }

        $VoucherDiscount = $this->order_model->checkValidVoucher2($order['voucher_code']);  //VV

        if( !empty($data['voucher_code']) && !empty($data['coupon_type']) && 
            $data['coupon_type'] == 'discount' && $VoucherDiscount != 'old' )
        {
            $vdiscrptn = str_replace(array('\n', '\r'), '', htmlspecialchars_decode($vdisc, ENT_NOQUOTES));
            $data['p_discount'] = strtoupper($data['voucher_code']) . '-' . $vdiscrptn . '  -$' . $data['discount']; //VV for gprs printer
        }

        if( !empty($data['voucher_code']) && !empty($data['coupon_type']) && 
            $data['coupon_type'] == 'freeproduct' && $VoucherDiscount != 'old' )
        {
            $free_product = $this->order_model->getFreeProductDescription($data['voucher_code']);

            if( $free_product )
            {
                $vdiscrptn = str_replace(array('\r', '\n'), '', htmlspecialchars_decode($free_product, ENT_NOQUOTES));

                $data['p_discount'] = strtoupper($data['voucher_code']) . '-' . $vdiscrptn; //VV for gprs printer
            }
        }

        if( !empty($data['voucher_code']) && !empty($data['coupon_type']) && 
            $data['coupon_type'] == 'invalid' && (bool) $data['voucher_code'] !== false )
        {
            $data['p_discount'] = strtoupper($data['voucher_code']); //VV for gprs printer
        }

        $data['total_amount']  = $order['payment_amount']; // $cart_total + $credit_card_fee + $data['delivery_fee'] - $discount + $data['min_order_delivery_fee'] + $data['min_order_paypal_fee'] + $data['min_order_credit_card_fee'] + $data['public_holiday_fee'];

        // user detail
        $usersInfo = $this->session->userdata('logged');

        $data['cust_name'] = $usersInfo['first_name'] . ' ' . $usersInfo['last_name']; // VV
        $data['p_cust_address'] = $usersInfo['address'] . '\n' . $subUrb; // VV for gprs printer

        $data['p_order_get_date'] = date('D d/m H:i', strtotime($order['order_date'])); // VV GPRS Printer

        if( strtotime($order['order_placement_date']) == strtotime($order['order_date']) || empty($order['order_date']) )
        {
            $data['p_order_get_date'] = 'ASAP'; //VV GPRS Printer
        }

        $data['delivery_fee'] = $order['delivery_fee'];
        $data['min_order_delivery_fee'] = $order['min_order_delivery_fee'];

        $data['min_order_paypal_fee'] = $order['min_order_paypal_fee'];
        $data['min_order_credit_card_fee'] = $order['min_order_credit_card_fee'];
        $data['public_holiday_fee'] = $order['public_holiday_fee'];

        $data['all_extra_fees'] = $data['delivery_fee'] + $data['min_order_delivery_fee'] + $data['min_order_paypal_fee'] + $data['min_order_credit_card_fee'] + $data['public_holiday_fee']; //VV for GPRS PRINTER

        $payment_method = $order['payment_method'];
        $data['payment_method'] = $payment_method;

        if( $payment_method == 'Credit Card Over Phone' )
        {
            $data['payment_method'] = 'Credit Card on Delivery';
        }

        $credit_card_fee = 0;

        if( $payment_method == 'Paypal' OR $payment_method == 'Credit Card Online' OR $payment_method == 'Credit Card Over Phone' )
        {
            $data['paid_or_not'] = 'PAID';

            if( $payment_method == 'Credit Card Over Phone' )
            {
                $data['paid_or_not'] = 'NOT PAID'; // VV setting CC over phone as NOT PAID
            }

            if( $payment_method == 'Credit Card Online' OR $payment_method == 'Credit Card Over Phone' )
            {
                $credit_card_fee = number_format($this->_getPayMethodFee('Credit Card Online'), 2);
                $data['all_extra_fees'] = $data['all_extra_fees'] + $credit_card_fee; //VV for gprs printer
            }
            else if( $payment_method == 'Paypal' )
            {
                $credit_card_fee = $this->_getPayMethodFee('Paypal');
                $data['all_extra_fees'] = $data['all_extra_fees'] + $credit_card_fee; //VV for gprs printer
            }
        }
        else
        {
            $data['paid_or_not'] = 'NOT PAID';
        }

        $data['cust_mobile'] = $usersInfo['mobile'];

        $data['order_comment'] = (!empty($order['order_comment']) ? strip_tags($order['order_comment'], '<b>,<i>,<strong>,<em>') : '&nbsp;');

        $data['restaurant_name'] = $this->db->select('value')->where('type', 'restaurant_name')->get('sitesetting')->row()->value;

        if(empty($order['order_date']))
        {
            $when = 'Delivery Time: ASAP';
        }
        else
        {
            $order_date =  DateTime::createFromFormat('Y-m-d H:i:s', $order['order_date'])->format('d-m-Y H:i');

            $when = 'Delivery Time: '.$order_date;
        }

        $datePlacementOrder = DateTime::createFromFormat('Y-m-d H:i:s', $order['order_placement_date'])->format('d-m-Y H:i');

        /** QRCODE */

        $searchAddress = $user['address'] . ',' . $subUrb . ',' . $user['zipcode'];
        $url = 'http://maps.google.com.au/maps?q=' . urlencode($searchAddress); // VV
        $googleUrl = $this->shortUrl($url);
        $qrUrl = FCPATH . 'templates/qrcode/' . $order['order_id'] . '.png';
        QRcode::png($googleUrl, $qrUrl, QR_ECLEVEL_L, 3, 0); //3 is size

        /** END QRCODE */

        //TODO: printer_files

        $sitetitle = $this->order_model->getSiteTitle();
        /** MAP */
        $shopAddress = $this->order_model->getShopAddress();
        include_once(APPPATH.'helpers/create_map.php');

        $map = $new_img;
        $directions = $tbl;
        /** END MAP*/

        $space = '<tr >
                        <td style="height:30px;" colspan="2"> </td>
                     </tr>';


        $html = '<table style="width: 700px;">
                <thead>
                </thead>
                <tbody>[[INTRO]]';

        /** header 1 */
        $html .='<tr>
                    <td colspan="2"><b>Order no.'. $order['real_id'] .', '.$sitetitle.'</b>, Date Ordered: ' . $datePlacementOrder . '</td>
                </tr>
                <tr>
                    <td colspan="2">' . $user['first_name'] . ', ' . $user['last_name'] . ', ' . $user['company_name'] . ', ' . $user['address'] . ', ' . $subUrb . ', Mobile #:' . $user['mobile'] . '</td>
                </tr>';

        $html .= $space;

        /** header 2 */
        $html .='<tr>
                    <td></td>
                    <td><b>'. $delivery . '</b></td>
                </tr>
                <tr>
                    <td></td>
                    <td style="border-bottom: 1px solid #000000;"><b>Payment Details: '. $data['payment_method'] . '</b></td>
                </tr>
                <tr>
                    <td></td>
                    <td><b>'. $when .'</b></td>
                </tr>';

        $html .= $space;

        /** order */

        $html .= '<tr>
                    <td colspan="2">Item(s) ordered:</td>
                </tr>';
        $cart = $this->cart->contents();
        $total = $this->cart->total();

        foreach($cart as $c){
            $html .='<tr>
                    <td colspan="2"><b>'. $c['qty'] .' x '. $c['name'] .'</b></td>
                    </tr>';
            foreach($c['options'] as $option){
                $html .='<tr>
                            <td >'. $option['name'].'</td>';
                if(!empty($option['price'])){
                    $html .= '<td >+$'. $option['price'] .'</td>';
                } else {
                    $html .= '<td ></td>';
                }

                $html .= '</tr>';
            }

            if(!empty($c['instruction'])){
                $html .='<tr>
                        <td ><b>Instruction: "</b>' . $c['instruction'] .'"</td>
                        <td></td>
                     </tr>';
            }

//            $html .= $spance;

            if($c['subtotal'] == 0 && isset($c['prod_points'])){
                $html .= '<tr>
                        <td></td>
                        <td><b>Point(s): ' . $c['prod_points'] .'</b></td>
                     </tr>';
            } else {
                $html .= '<tr>
                        <td></td>
                        <td><b>$' . $c['subtotal'] .'</b></td>
                     </tr>';
            }


            $html .='<tr>
                        <td colspan="2" style="border-top: 1px dashed #000000;"></td>
                     </tr>';
        }

        $newTotal = $total;
        if(!empty( $checkout['couponName']) && !empty($checkout['couponDiscount'])){

            $discountPrice = number_format((($total/100)*$checkout['couponDiscount']), 2, '.', '');
            $newTotal = $total - $discountPrice;

            $html .= '<tr>
                        <td><b>Coupon: ' . $checkout['couponName'] . ' ('. $checkout['couponDiscount'] .'%)</b></td>
                        <td><b>-$'. $discountPrice .'</b></td>
                     </tr>';

        } else if(!empty( $checkout['couponName'])) {

            $html .= '<tr>
                        <td><b>Coupon:</b>' . $checkout['couponName'] . '</td>
                        <td></td>
                     </tr>';
        }

        $holidayDiscount = $this->session->userdata('holiday_fee');
        if($holidayDiscount){
            $holidayPrice = number_format((($total/100)*$holidayDiscount), 2, '.', '');
            $newTotal = $newTotal + $holidayPrice;

            $html .= '<tr>
                        <td><b>Public Holiday Fee (' . $holidayDiscount . '%)</b></td>
                        <td><b>+$'. $holidayPrice .'</b></td>
                     </tr>';
        }

        $paymentFee = $this->session->userdata('surchange');
        if(!empty($paymentFee)){
            $newTotal += $paymentFee['value'];

            $html .= '<tr>
                        <td><b>'. $paymentFee['name']  .'</b></td>
                        <td><b>+$'. $paymentFee['value'] .'</b></td>
                     </tr>';
        }


        $subUrbFee = $this->order_model->getSubUrbFee($user['suburb']);
        if($subUrbFee){
            $newTotal = $newTotal + $subUrbFee;
            $html .= '<tr>
                        <td><b>Delyvery fee</b></td>
                        <td><b>+$'. $subUrbFee .'</b></td>
                     </tr>';
        }
        /** low order */
        $lowOrder = $this->session->userdata('low_order');
        if($lowOrder > 0){
            $newTotal += $lowOrder;
            $html .= '<tr>
                        <td><b>Minimum order fee</b></td>
                        <td><b>+$'. $lowOrder .'</b></td>
                     </tr>';
        }

        /**end*/

        $newTotal = number_format($newTotal, 2, '.', '');
        $html .= '<tr>
                    <td><b>Total</b></td>
                    <td><b>$' . $newTotal .'</b></td>
                 </tr>';

        $html .= $space;
        if(isset($checkout['comment']) && !empty($checkout['comment'])){
            $html .= '<tr style="top:20;width:100%;">
                        <td><b>ORDER COMMENTS:</b>'. $checkout['comment'] .'</td>
                        <td></td>
                     </tr>';
        }


        $html .= $space;

        $html_email = $html;

        $html .= '<tr >
                    <td colspan="2" rowsan="3" style="border: 1px solid #cccccc;width: 500px;">
                        <table>
                            <tbody>
                                <tr>
                                    <td style="width:245px" margin-right:5px;>
                                        <table>
                                            <tbody>
                                                <tr>
                                                    <td>' . $user['first_name'] . ', ' . $user['last_name'] . '</td>
                                                </tr>
                                                <tr>
                                                    <td>' . $user['company_name'] . '</td>
                                                </tr>
                                                <tr>
                                                    <td>' . $user['address'] . '</td>
                                                </tr>
                                                <tr>
                                                    <td>' . $subUrb . '</td>
                                                </tr>
                                                <tr>
                                                    <td>' . $user['mobile'] . '</td>
                                                </tr>
                                                 <tr>
                                                    <td><img src="' . $qrUrl . '"/></td>
                                                </tr>

                                            </tbody>
                                        </table>
                                    </td>

                                    <td style="width:350px;"><img src="' . $map . '" /> </td>
                                </tr>
                            </tbody>
                        </table>
                    </td>
                 </tr>';

        $html .='<tr>
                    <td colspan="2">
                        ' . $directions . '
                    <td>
                </tr>';

        $html_email .= '<tr >
                    <td colspan="2" rowsan="3">
                        <table>
                            <tbody>
                                <tr>
                                    <td>
                                        <table>
                                            <tbody>
                                                <tr>
                                                    <td><b>' . $user['first_name'] . ', ' . $user['last_name'] . '</b></td>
                                                </tr>
                                                <tr>
                                                    <td><b>' . $user['company_name'] . '</b></td>
                                                </tr>
                                                <tr>
                                                    <td><b>' . $user['address'] . '</b></td>
                                                </tr>
                                                <tr>
                                                    <td><b>' . $subUrb . '</b></td>
                                                </tr>
                                                <tr>
                                                    <td><b>' . $user['mobile'] . '</b></td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </td>
                </tr>
                <tr>
                    <td style="height:30px;" colspan="2"> </td>
                </tr>
                <tr>
                    <td>
                        <p>
                            <strong>Jailhouse Rock Pizza &amp; Pasta Restaurant</strong>
                        </p>
                        <p>
                            <strong>
                                <a href="http://mobile.bluestarpizza.com.au/">mobile.bluestarpizza.com.au</a>
                            </strong>
                        </p>
                    </td>
                </tr>';

        $html .='';


        //TODO: order comments

        $html .='</tbody>
               </table>';

        $html = str_replace('[[INTRO]]', '', $html);

        $dompdf = new DOMPDF();
        $dompdf->load_html($html);
        $dompdf->render();
        $output = $dompdf->output();
        $pdf_path = FCPATH.'templates/pdf/';
        $name = 'DEMOTEST_'.$order['real_id'].'.pdf';
        $filename= $pdf_path.$name;
        file_put_contents($filename, $output);

        $this->_sendPdfMail($html_email, $order['real_id']);

        $this->gprs_printer($data);

        return $name;


    }

    function _sendPdfMail($html, $order_id)
    {
        $logged = $this->session->userdata('logged');

        $siteSetting = $this->session->userdata('siteSetting');

        $email_template = file_get_contents($this->config->item('base_abs_path') . 'templates/' . $siteSetting->TEMPLATEDIR . '/email/customer_order_mail.html');

        $subject = 'Thank You for your Order';

        $html = str_replace('[[INTRO]]', '
                <tr>
                    <td colspan="2"><b>Subject: ' . $subject . ' (no.' . $order_id . ')</b></td>
                </tr>
                <tr>
                    <td style="height:30px;" colspan="2"> </td>
                </tr>
                <tr>
                    <td colspan="2"><b>Thank you very much for yoru order. Please allow about ' . $siteSetting->delivery_time . ' minutes for delivery. If you have any questions you can contact us at 09599 0333</b></td>
                </tr>
                <tr>
                    <td style="height:30px;" colspan="2"> </td>
                </tr>', $html);

        $email_template = str_replace('[[LOGO]]', $siteSetting->desktop_url . 'templates/' . $siteSetting->TEMPLATEDIR . '/templates/default/images/smal-circular-logo.png', $email_template);
        $email_template = str_replace('[[EMAIL_HEADING]]', $subject, $email_template);
        $email_template = str_replace('[[EMAIL_CONTENT]]', $html, $email_template);

        $this->load->library('email');

        $this->email->initialize(array('mailtype' => 'html'));

        $this->email->subject($subject);

        $this->email->from($siteSetting->FROM_EMAIL, $siteSetting->SITETITLE);
        $this->email->to($logged['email']);

        if( $siteSetting->order_by_email === 'Y' )
        {
            $this->email->bcc($siteSetting->confirm_email_to);
        }

        $this->email->message($email_template);

        $send = $this->email->send();
    }

    //VV GPRS PRINTER
    function gprs_printer($data)
    {
        $p_rest_id = '#RestID*';

        $p_delivery_or_pickup = strtoupper($data['order_option']) . '*'; // home delivery / pickup

        $p_order_number = $data['order_number'] . '*'; // number of order

        $p_items = substr($data['p_txt_file_item_desc'], 0, -1) . '*'; // description of the content of the order (products, extras)

        $p_discount = $data['p_discount'] . ';'; // description of the discount

        $p_total_amount = '$' . $data['total_amount'] . ';;'; // total order amount

        if( $p_delivery_or_pickup === 'HOME DELIVERY*' )
        {
            $p_cust_name = $data['cust_name'] . '\n' . $data['p_cust_address'] . ';';
        }
        else
        {
            $p_cust_name = $data['cust_name'] . ';';

            $p_delivery_or_pickup = 'IN-STORE PICKUP*';
        }

        $p_asap_or_later = $data['p_order_get_date'] . ';';

        $p_deliver_and_other_fees = '$' . number_format($data['all_extra_fees'], 2) . ';';

        $p_paid_or_not = $data['paid_or_not'];

        $p_payment_method = $data['payment_method'] . ';';

        $p_cust_mobile = $data['cust_mobile'] . '*';

        $p_order_comment = strip_tags($data['order_comment']);

        $p_order_comment = trim(str_replace('ORDER COMMENTS: :', '', $p_order_comment));

        $p_order_comment = str_replace('&nbsp;', '', $p_order_comment) . '*';

        if( $p_order_comment === '*' )
        {
            $p_order_comment = '';
        }

        $p_order_received_at = 'ORDER RECEIVED: ' . date('H:i m-d') . '*';

        $p_part_order = '#'; //add "PART1/2 if neccessary - TO DO LATER"

        $p_printer_data = $p_rest_id . $p_delivery_or_pickup . $p_order_number . $p_items . $p_discount . $p_total_amount . $p_cust_name . $p_asap_or_later . $p_deliver_and_other_fees . $p_paid_or_not . ' - ' . $p_payment_method . $p_cust_mobile . $p_order_comment . $p_order_received_at . $p_part_order;

        $p_printer_data = strip_tags($p_printer_data);

        $p_printer_data = trim(preg_replace('/\s+/', ' ', $p_printer_data)); //remove line breaks

        $sitesetting = $this->db->select('value')->where('type', 'order_by_gsm_printer')->get('sitesetting')->row();

        if( $sitesetting )
        {
            $sent_by_gsm_printer = $sitesetting->value;
        }
        else
        {
            $sent_by_gsm_printer = '0';
        }

        if( empty($sent_by_gsm_printer) )
        {
            $p_status='no_gprs_print';
        }
        else
        {
            $p_status = 'to_be_printed';
        }

        $this->order_model->recordPrinterData($data['order_number'],$p_printer_data, $p_status);

        $text_file_path = FCPATH.'templates/printer_files/'.$data['order_number'].'_'.urlencode($data['restaurant_name']).'.txt';

        if ( file_put_contents($text_file_path, $p_printer_data) === false )
        {
            // echo 'Unable to write the file'; die;
        }
    } //VV end function gprs printer

    /**
     * Short url
     * @param $url
     * @return mixed
     */
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


    /**
     * User Orders
     */
    public function yourOrders()
    {
        $logged = $this->session->userdata('logged');

        if( !empty($logged) )
        {
            $offset = 0;
            $limit = 5;

            $orders = $this->order_model->getYourOrders($logged['userid'], $offset, $limit);
// echo '<pre>'; var_dump($orders); echo '</pre>'; die;
            if( $orders )
            {
                $this->twiggy->set('orders', $orders);

                $total = $this->order_model->countYourOrders($logged['userid']);

                if( $total )
                {
                    $this->twiggy->set('total', $total);
                }

                $this->twiggy->set('count', $limit);
            }
        }

        $this->twiggy->set('page', array(
            'title' => 'Your Orders', 
            'role' => 'page', 
            'theme' => 'a', 
            'id' => 'your-orders', 
            'backButton' => true
        ));

        $this->twiggy->display('your_orders/yourOrders');
    }

    /**
     *  get orders (ajax)
     */
    public function getAjaxOrders()
    {
        $logged = $this->session->userdata('logged');

        $offset = $this->input->post('count');
        $pageType = $this->input->post('page');

        $limit = 5;

        if( $pageType == 'next' )
        {
            $orders = $this->order_model->getYourOrders($logged['userid'], $offset, $limit);
        }
        else if( $pageType == 'preview' )
        {
            if( $offset == 5 )
            {
                $offset = $offset - 5;
            }
            else
            {
                $offset = $offset - 10;
            }

            $orders = $this->order_model->getYourOrders($logged['userid'], $offset, $limit);
        }

        foreach( $orders as $key => $order )
        {
            $orders[$key]->order_placement_date = date('Y, F jS g:i a', (integer) $order->order_placement_date);
        }

        $count = $offset + $limit;

        echo json_encode(array(
            'orders' => $orders, 
            'count' => $count
        ));
    }

 //VV for GPRS printer - almost identical to getTextFileItemsDescription()
    private function p_getTextFileItemsDescription()
    {
        $order = '';

        if( $this->cart->contents() )
        {
            foreach( $this->cart->contents() as $items )
            {
                if( !empty($items['product_type']) && $items['product_type'] == 'single' )
                {
                    $variation_group = '';
                    $current = '';
                    $extra = '';

                    $comment = '';

                    foreach( $items['options'] as $option )
                    {
                        if( strpos(strtolower($option['name']), 'size:') !== false )
                        {
                            $option_name_parts = explode(':', $option['name']);

                            $name = isset($option_name_parts[0]) ? trim($option_name_parts[0]) : '';
                            $value = isset($option_name_parts[1]) ? trim($option_name_parts[1]) : '';

                            if( !empty($name) && 
                                !empty($value) )
                            {
                                $variation_group .= '>' . strtoupper($name) . ': ' . strtoupper($value);
                            }
                        }

                        if( strpos(strtolower($option['name']), '-no:') !== false )
                        {
                            $option_name_parts = explode(':', $option['name']);

                            $name = isset($option_name_parts[0]) ? trim($option_name_parts[0]) : '';
                            $value = isset($option_name_parts[1]) ? trim($option_name_parts[1]) : '';

                            if( !empty($name) && 
                                !empty($value) )
                            {
                                $current .= '>NO: ' . ucwords($value);
                            }
                        }

                        if( strpos(strtolower($option['name']), '+extra:') !== false )
                        {
                            $option_name_parts = explode(':', $option['name']);

                            $name = isset($option_name_parts[0]) ? trim($option_name_parts[0]) : '';
                            $value = isset($option_name_parts[1]) ? trim($option_name_parts[1]) : '';

                            $price = isset($option['price']) ? trim($option['price']) : '';

                            if( !empty($name) && 
                                !empty($value) )
                            {
                                $extra .= '>EXTRA: ' . ucwords($value) . ( empty($price) ? '' : '(+' . $price . ')' );
                            }
                        }
                    }

                    if( !empty($items['instruction']) )
                    {
                        $comment = '\nCOMMENTS: ' . $items['instruction'];
                    }

                    $item_price = number_format((integer) $items['qty'] * (float) $items['price'], 2);

                    $order .= '|' . $items['qty'] . '|' . strtoupper($items['name']) . '|' . $item_price . '|' . $variation_group . $current . $extra . $comment . ';';
                }
                else if( !empty($items['product_type']) && $items['product_type'] === 'half' )
                {
                    $half = false;

                    $first_product_variation_group = '';
                    $first_product_current_options = '';
                    $first_product_extra_options = '';

                    $second_product_variation_group = '';
                    $second_product_current_options = '';
                    $second_product_extra_options = '';

                    $comment = '';

                    foreach( $items['options'] as $option )
                    {
                        if( $half === false && 
                            strpos(strtolower($option['name']), 'first half') !== false )
                        {
                            $half = 'first';

                            $option_name_parts = explode(':', $option['name']);

                            $first_pizza_name = isset($option_name_parts[1]) ? strtoupper(trim($option_name_parts[1])) : '';
                        }

                        if( $half === 'first' && 
                            strpos(strtolower($option['name']), 'second half') !== false )
                        {
                            $half = 'second';

                            $option_name_parts = explode(':', $option['name']);

                            $second_pizza_name = isset($option_name_parts[1]) ? strtoupper(trim($option_name_parts[1])) : '';
                        }

                        if( strpos(strtolower($option['name']), 'size:') !== false )
                        {
                            $option_name_parts = explode(':', $option['name']);

                            $name = isset($option_name_parts[0]) ? trim($option_name_parts[0]) : '';
                            $value = isset($option_name_parts[1]) ? trim($option_name_parts[1]) : '';

                            if( !empty($name) && 
                                !empty($value) )
                            {
                                $product_variation_group = '>' . strtoupper($name) . ': ' . strtoupper($value);

                                if( $half === 'first' )
                                {
                                    $first_product_variation_group .= $product_variation_group;
                                }

                                if( $half === 'second' )
                                {
                                    $second_product_variation_group .= $product_variation_group;
                                }
                            }
                        }

                        if( strpos(strtolower($option['name']), '-no:') !== false )
                        {
                            $option_name_parts = explode(':', $option['name']);

                            $name = isset($option_name_parts[0]) ? trim($option_name_parts[0]) : '';
                            $value = isset($option_name_parts[1]) ? trim($option_name_parts[1]) : '';

                            if( !empty($name) && 
                                !empty($value) )
                            {
                                $product_current_options = '>NO: ' . ucwords($value);

                                if( $half === 'first' )
                                {
                                    $first_product_current_options .= $product_current_options;
                                }

                                if( $half === 'second' )
                                {
                                    $second_product_current_options .= $product_current_options;
                                }
                            }
                        }

                        if( strpos(strtolower($option['name']), '+extra:') !== false )
                        {
                            $option_name_parts = explode(':', $option['name']);

                            $name = isset($option_name_parts[0]) ? trim($option_name_parts[0]) : '';
                            $value = isset($option_name_parts[1]) ? trim($option_name_parts[1]) : '';

                            $price = isset($option['price']) ? trim($option['price']) : '';

                            if( !empty($name) && 
                                !empty($value) )
                            {
                                $product_extra_options = '>EXTRA: ' . ucwords($value) . ( empty($price) ? '' : '(+' . $price . ')' );

                                if( $half === 'first' )
                                {
                                    $first_product_extra_options .= $product_extra_options;
                                }

                                if( $half === 'second' )
                                {
                                    $second_product_extra_options .= $product_extra_options;
                                }
                            }
                        }
                    }

                    if( !empty($items['instruction']) )
                    {
                        $comment = '\nCOMMENTS: ' . $items['instruction'];
                    }

                    $item_price = number_format((integer) $items['qty'] * (float) $items['price'], 2);

                    $order .= '|' . $items['qty'] . '|HALF & HALF PIZZA' . '|' . $item_price . '|' . '\n1st Half: ' . $first_pizza_name . $first_product_variation_group . $first_product_current_options . $first_product_extra_options . '\n2nd Half: ' . $second_pizza_name .  $second_product_variation_group . $second_product_current_options .  $second_product_extra_options . $comment . ';';
                }
            }
        }

        return $order;
    }

//VV for printer end _getTextFileItemsDescription

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
}