<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Gabriel
 * Date: 12/12/13
 * Time: 2:09 AM
 * To change this template use File | Settings | File Templates.
 */

class paypal extends WMDS_Controller {

    /**
     * Home Page, show splash screen and display
     * if shop is open or close
     */
    public function index()
    {

        $paypalFields = $this->session->userdata('paypal');

        $user = $this->session->userdata('logged');

        $this->twiggy->set('user', $user);
        $this->twiggy->set('total', $paypalFields['total']);
        $this->twiggy->set('orderId', $paypalFields['orderId']);

        $this->twiggy->set('page', array(
            'title'  => 'Paypal',
            'role'   => 'page'
        ));

        $this->twiggy->set('internalPage', false);

        $this->twiggy->template('payment/paypal')->display();
    }

    public function cancel(){


        $this->session->unset_userdata('paypal');
        $this->cart->destroy();
        redirect(base_url().'order-failed');
    }

    public function succes(){

        $this->session->unset_userdata('paypal');
        $this->cart->destroy();
        redirect(base_url().'order-success');

    }

    public function ipn(){


        $request = "cmd=_notify-validate";
        foreach ($_POST as $varname => $varvalue){
//            $email .= "$varname: $varvalue\n";
            if(function_exists('get_magic_quotes_gpc') and get_magic_quotes_gpc()){
                $varvalue = urlencode(stripslashes($varvalue));
            }
            else {
                $varvalue = urlencode($varvalue);

            }
            $request .= "&$varname=$varvalue";
        }

        /** get item_number */
        $str = urldecode($request);
        parse_str($str, $output);

        $item_number = $output['item_number'];

        $ch = curl_init();
        curl_setopt($ch,CURLOPT_URL,"https://www.sandbox.paypal.com/cgi-bin/webscr");
//curl_setopt($ch,CURLOPT_URL,"https://www.paypal.com");
        curl_setopt($ch,CURLOPT_POST,true);
        curl_setopt($ch,CURLOPT_POSTFIELDS,$request);
        curl_setopt($ch,CURLOPT_FOLLOWLOCATION,false);
        curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
        $result = curl_exec($ch);
        curl_close($ch);
        switch($result){
            case "VERIFIED":
                $this->load->model('order_model');
               $order_id = $this->order_model->saveOrderFromTemp($item_number);
                /** sms confirmation */
                $this->confirmationSms($order_id);

                // verified payment
                break;
            case "INVALID":
                // invalid/fake payment
                break;
            default:
                // any other case (such as no response, connection timeout...)
        }












//        $a = $this->input->post();
//
//
//        ini_set('log_errors', true);
//        ini_set('error_log', dirname(__FILE__).'/ipn_errors.log');
//
//
//     instantiate the IpnListener class
//        include('ipnlistener.php');
//        $listener = new IpnListener();
//
//        /*
//        When you are testing your IPN script you should be using a PayPal "Sandbox"
//        account: https://developer.paypal.com
//        When you are ready to go live change use_sandbox to false.
//        */
//        $listener->use_sandbox = true;
//
//        /*
//        By default the IpnListener object is going  going to post the data back to PayPal
//        using cURL over a secure SSL connection. This is the recommended way to post
//        the data back, however, some people may have connections problems using this
//        method.
//
//        To post over standard HTTP connection, use:
//
//
//        To post using the fsockopen() function rather than cURL, use:
//        $listener->use_curl = false;
//        */
//
//        $listener->use_curl = true;
//        try {
//            $listener->requirePostMethod();
//            $this->db->insert('ipn', array('ipn' => json_encode($a), 'status' => 'valid'));
//            $verified = $listener->processIpn(json_encode($a));
//
//        } catch (Exception $e) {
//            error_log($e->getMessage());
//            exit(0);
//        }
//
//
//        /*
//        The processIpn() method returned true if the IPN was "VERIFIED" and false if it
//        was "INVALID".
//        */
//        if ($verified) {
//
//            $this->db->insert('ipn', array('ipn' => 'valid', 'status' => 'valid'));
//            mail('YOUR EMAIL ADDRESS', 'Verified IPN', $listener->getTextReport());
//
//        } else {
//
//            $this->db->insert('ipn', array('ipn' => 'invalid', 'status' => 'invalid'));
//            mail('YOUR EMAIL ADDRESS', 'Invalid IPN', $listener->getTextReport());
//        }
//
//
//        $a = $this->input->post();
//        $b = $this->input->get();
//
//        print_r($a);
//        print_r($b);
//        die;
//
//        $paypalFiedls = $this->session->userdata('paypal');
//
//        $this->load->model('order_model');
//
//        $this->order_model->savePaypalOrder($paypalFiedls['order_id']);
//
//        $this->load->library('cart');

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


                $this->email->subject('Order no'.$real_id );

                $this->email->message($content_message);

                $this->email->send();
            }
        }
    }

}