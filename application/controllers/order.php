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
        foreach ($cart as $c){

            $html .= '<div class="mar ovfl-hidden">
                            <div class="fl"><strong>' . $c['name'] . '</strong></div>
                        </div>

                        <div class="mar ovfl-hidden">
                            <div class="fl">* Qty :'. $c['qty'] .'</div>
                            <div class="fr"></div>
                        </div>';

            foreach($c['options'] as $option){

                 $html .= '<div class="mar smlTxt ovfl-hidden">
                     <div class="mar ovfl-hidden" style="margin-bottom:0px;">
                          <div class="fl">'.$option['name'].'</div>
                     </div>
                 </div>
                 <div class="mar smlTxt ovfl-hidden"></div>
                 ';
            }
        }

        $check = $this->session->userdata('checkout');
        if( !isset( $check['delivery'] ) ) {
            // Back to menu when Hardware back button press
            redirect(base_url().'menu');
        }

        /** Counpon */
        if(isset( $check['couponDiscount'])){
            $html .= '<div class="mar ovfl-hidden">
                            <div class="fl">Coupon: ' . $check['couponName'] . ': '. $check['couponDiscount'] .'%</div>
                        </div>';
        } elseif(isset( $check['couponName'])) {
            $html .= '<div class="mar ovfl-hidden">
                            <div class="fl">Coupon:' . $check['couponName'] . '</div>
                        </div>';
        }


        /** Comments */
        $html .= '<div class="mar ovfl-hidden">
                            <div class="fl">Order Commnets:'. $check['comment'] .'</div>
                        </div>';
        /** end Description */
        //TODO: coupon discount, comment
        $user = $this->session->userdata('logged');


        /** new total */
        $total = $this->cart->total();
        $newTotal = $total;
        if(isset($check['couponDiscount']) && is_numeric($check['couponDiscount'])){
            $discount = number_format(($total/100)*(int)$check['couponDiscount'], 1, '.', '');
            /** total - coupon discount */
            $newTotal = $total - $discount;
        } else {
            $discount = '';
        }

        /*
         * Calculate total points used in order
         */
        $points_used = 0;
        foreach($cart as $item) {

            if(isset($item['points'])) {
                $points_used+=$item['points'];
            }
        }
        $check['loyalityPointsUsed'] = $points_used;

        /** total + suburb delivery fee */
        if( isset($check['delivery']) && $check['delivery'] == 'D' && isset($user['suburb'])) {
            $delivery_fee = $this->order_model->getDeliveryFee($user['suburb']);
            if($delivery_fee){
                $newTotal += $delivery_fee;
            }
        } else {
            $delivery_fee = 0;
        }

        /** verify if holliday fee */
        $holidayFee = $this->session->userdata('holiday_fee');
        if($holidayFee){
            $holidayPrice = number_format((($total/100)*$holidayFee), 2, '.', '');
            $newTotal += $holidayPrice;
        }
        /** end holliday fee */

        /** credit-card, paypal fee */
        $surchargeOrder = $this->session->userdata('surchange');
        if(!empty($surchargeOrder)){
            $newTotal += $surchargeOrder['value'];
        }
        /** end */


        /** low order */
        $lowOrder = $this->session->userdata('low_order');
        $newTotal += $lowOrder;
        /**end*/
        /** checkout options */
//        $order_id = $this->order_model->saveOrder($checkout, $total, $discount, $delivery_fee, $user['userid'], $html);

        if($payment == 'paypal'){
            $order_id = $this->order_model->saveOrder($check, $newTotal, $discount, $delivery_fee, $user['userid'], $html, 'pending');


            $paypalFields = array(
                'total'   => $newTotal,
                'orderId' => $order_id
            );

            $this->session->set_userdata('paypal', $paypalFields);

            redirect(base_url().'paypal');
        } else {
//            print_r($cart);die;
            $order_id    = $this->order_model->saveOrder($check, $newTotal, $discount, $delivery_fee, $user['userid'], $html, 'save');

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
    private function confirmationSms($order_id){
        $this->load->model('security_model');
        $sms = $this->security_model->smsSettings();

        if($sms['sms_confirmation'] == 'enable'){

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

//        include_once('create_map.php');

        $user = $this->session->userdata('logged');
        $subUrb = $this->order_model->getSubUrb($user['suburb']);
//        print_r($user);die;
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
            $data['coupon_type'] = $vdata['coupontype'];
            $data['voucher_code'] = $vdata['couponcode'];
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

        $data['cust_name'] = $usersInfo['first_name'] . ' ' . $usersInfo['last_name']; //VV
        $data['p_cust_address'] = $usersInfo['address'] . '\n' . $subUrb; //VV for gprs printer

        $data['p_order_get_date'] = date('D d/m H:i', strtotime($order['order_date'])); //VV GPRS Printer

        if( strtotime($order['order_placement_date']) == strtotime($order['order_date']) )
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

//        print_r($order);
//        print_r($checkout);die;
        if(empty($order['order_date'])){
            $when = 'Delivery Time: ASAP';
        } else {
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
                <tbody>';
        /** header 1 */
        $html .='<tr>
                    <td colspan="2"><b>Order no.'. $order['real_id'] .', '.$sitetitle.'</b>, Date Ordered:' . $datePlacementOrder . '</td>
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
                    <td style="border-bottom: 1px solid #000000;"><b>Payment Details:'. $checkout['payment'] . '</b></td>
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

        $html .='';


        //TODO: order comments

        $html .='</tbody>
               </table>';

        $dompdf = new DOMPDF();
        $dompdf->load_html($html);
        $dompdf->render();
        $output = $dompdf->output();
        $pdf_path = FCPATH.'templates/pdf/';
        $name = 'DEMOTEST_'.$order['real_id'].'.pdf';
        $filename= $pdf_path.$name;
//        print_pre($filename);die;
        file_put_contents($filename, $output);

        $this->gprs_printer($data);

        return $name;


    }

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
        $this->order_model->recordPrinterData($data['order_number'],$p_printer_data, $p_status);

        $text_file_path = FCPATH.'templates/printer_files/'.$data['order_number'].'_'.urlencode($data['restaurant_name']).'.txt';

        if ( file_put_contents($text_file_path, $p_printer_data) === false )
        {
            //echo 'Unable to write the file'; die;
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
    public function yourOrders(){

        $logged = $this->session->userdata('logged');
        if(!empty($logged)){

            $offset = 0;
            $limit = 5;

            $orders = $this->order_model->getYourOrders($logged['userid'], $offset, $limit);
            if($orders){


                $this->twiggy->set('orders', $orders);

                $total = $this->order_model->countYourOrders($logged['userid']);
                if($total){

                    $this->twiggy->set('total', $total);

                }
                $this->twiggy->set('count', $limit);
            }

        }

        $this->twiggy->set('page', array(
            'title'  => 'Your Orders',
            'role'   => 'page',
            'theme'  => 'a',
            'id'     => 'your-orders',
            'backButton'=> true,
        ));

        $this->twiggy->display('your_orders/yourOrders');
    }

    /**
     *  get orders (ajax)
     */
    public function getAjaxOrders(){

        $logged = $this->session->userdata('logged');


        $offset = $this->input->post('count');
        $pageType = $this->input->post('page');

        $limit = 5;

        if($pageType == 'next'){

            $orders = $this->order_model->getYourOrders($logged['userid'], $offset, $limit);



        } elseif($pageType == 'preview') {
            if($offset == 5){
                $offset = $offset - 5;
            } else{
                $offset = $offset - 10;
            }

            $orders = $this->order_model->getYourOrders($logged['userid'], $offset, $limit);
//            $count = $offset;
        }
        $count = $offset + $limit;
        echo json_encode(array(
            'orders'     => $orders,
            'count'      => $count
        ));

    }

 //VV for GPRS printer - almost identical to getTextFileItemsDescription()
    private function p_getTextFileItemsDescription()
    {

        $order = '';
        if ($this->cart->contents()) {
            foreach ($this->cart->contents() as $items) {
                if ( !empty($items['options']['product_type']) && $items['options']['product_type'] == 'single' ) {

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
                elseif ( !empty($items['options']['product_type']) && $items['options']['product_type'] == 'half_half' ) {
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