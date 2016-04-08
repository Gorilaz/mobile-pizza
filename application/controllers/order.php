<?php
/**
 * @property Order_model $order_model
 */
class Order extends WMDS_Controller{

    function __construct(){
        parent::__construct();

        $this->load->library('cart');
        $this->load->model('order_model');
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

            if($back_url == 'yes'){
                $session_id = $this->session->userdata('session_id');
                redirect($this->config->item('mobile_website').'page/order-success/'.$session_id);
            } else {
                redirect(base_url().'page/order-success');
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

        if($checkout['payment'] == 1){
            $paid = 'NOT PAID';
        } else {
            $paid = 'PAID';
        }
        if($checkout['delivery'] == 'D'){

            $delivery = 'Home Delivery / '.$paid;
        } else {
            $delivery = 'In-store Pickup / '.$paid;
        }


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

        return $name;


    }


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
}