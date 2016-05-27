<?php

class Payment extends WMDS_Controller{

    function __construct(){
        parent::__construct();

        $this->load->helper('url');

        // Load PayPal library
        $this->config->load('paypal');

        $config = array(
            'Sandbox' => $this->config->item('Sandbox'), 			// Sandbox / testing mode option.
            'APIUsername' => $this->config->item('APIUsername'), 	// PayPal API username of the API caller
            'APIPassword' => $this->config->item('APIPassword'), 	// PayPal API password of the API caller
            'APISignature' => $this->config->item('APISignature'), 	// PayPal API signature of the API caller
            'APISubject' => '', 									// PayPal API subject (email address of 3rd party user that has granted API permission for your app)
            'APIVersion' => $this->config->item('APIVersion')		// API version you'd like to use for your call.  You can set a default version in the class and leave this blank if you want.
        );

        // Show Errors
        if($config['Sandbox'])
        {
            error_reporting(E_ALL);
            ini_set('display_errors', '1');
        }

        $this->load->library('Paypal_pro', $config);
    }

    public function credit_card(){

        if($this->input->post()){

        }
        $this->twiggy->display('payment/credit-card');
    }


    function Do_direct_payment()
    {

        $this->load->library('session');
//        $this->load->model('menu_model');
        $this->load->library('cart');
//            print_r($this->input->post());die;

        $credit = $this->input->post();


        $cardNumber = '';
        foreach($credit['card_number'] as $c){
            $cardNumber .= $c;
        }


        $user = $this->session->userdata('logged');
//        $card = $this->input->post();

        $expirationDate = $credit['expiration']['month'].$credit['expiration']['year'];

//        $city = $this->menu_model->getCityName($user['city']);
//        $state = $this->menu_model->getStateName($user['state']);

        $DPFields = array(
            'paymentaction'     => 'Sale', 						// How you want to obtain payment.  Authorization indidicates the payment is a basic auth subject to settlement with Auth & Capture.  Sale indicates that this is a final sale for which you are requesting payment.  Default is Sale.
            'ipaddress'         => $_SERVER['REMOTE_ADDR'], 							// Required.  IP address of the payer's browser.
            'returnfmfdetails'  => '1' 					// Flag to determine whether you want the results returned by FMF.  1 or 0.  Default is 0.
        );

        $CCDetails = array(
            'creditcardtype'    => $credit['credit_card'], 				                 	// Required. Type of credit card.  Visa, MasterCard, Discover, Amex, Maestro, Solo.  If Maestro or Solo, the currency code must be GBP.  In addition, either start date or issue number must be specified.
            'acct'              => $cardNumber, 								// Required.  Credit card number.  No spaces or punctuation.
            'expdate'           => $expirationDate, 							// Required.  Credit card expiration date.  Format is MMYYYY
            'cvv2'              => $credit['security'], 								// Requirements determined by your PayPal account settings.  Security digits for credit card.
//            'startdate'         => $credit['issued'], 							// Month and year that Maestro or Solo card was issued.  MMYYYY
//            'issuenumber'       => $credit['issue_number']							// Issue number of Maestro or Solo card.  Two numeric digits max.
        );

        if($credit['credit_card'] == 'Maestro' || $credit['credit_card'] == 'Solo'){
            $CCDetails['startdate']   = $credit['issued'];
            $CCDetails['issuenumber'] = $credit['issue_number'];

        }


        $PayerInfo = array(
            'email'       => $user['email'], 								// Email address of payer.
            'payerid'     => '', 							// Unique PayPal customer ID for payer.
            'payerstatus' => '', 						// Status of payer.  Values are verified or unverified
            'business'    => ''							// Payer's business name.
        );

        $PayerName = array(
//            'salutation' => 'Mr.', 						// Payer's salutation.  20 char max.
            'firstname' => $credit['card_holder'], 							// Payer's first name.  25 char max.
//            'middlename' => '', 						// Payer's middle name.  25 char max.
//            'lastname'  => $credit['last_name'] 							// Payer's last name.  25 char max.
//            'suffix' => ''								// Payer's suffix.  12 char max.
        );

        $BillingAddress = array(
            'street'   => $user['address'], 						// Required.  First street address.
//            'street2' => '', 						// Second street address.
//            'city' => $city, 							// Required.  Name of City.
//            'state' => $state, 							// Required. Name of State or Province.
//            'countrycode' => 'US', 					// Required.  Country code.
            'zip'      => $user['zipcode'], 							// Required.  Postal code of payer.
            'phonenum' => $user['mobile']						// Phone Number of payer.  20 char max.
        );
//
//        $ShippingAddress = array(
//            'shiptoname' => 'Tester Testerson', 					// Required if shipping is included.  Person's name associated with this address.  32 char max.
//            'shiptostreet' => '123 Test Ave.', 					// Required if shipping is included.  First street address.  100 char max.
//            'shiptostreet2' => '', 					// Second street address.  100 char max.
//            'shiptocity' => 'Kansas City', 					// Required if shipping is included.  Name of city.  40 char max.
//            'shiptostate' => 'MO', 					// Required if shipping is included.  Name of state or province.  40 char max.
//            'shiptozip' => '64111', 						// Required if shipping is included.  Postal code of shipping address.  20 char max.
//            'shiptocountry' => 'US', 					// Required if shipping is included.  Country code of shipping address.  2 char max.
//            'shiptophonenum' => '555-555-5555'					// Phone number for shipping address.  20 char max.
//        );

        $PaymentDetails = array(
            'amt'          =>  $credit['total'], 							// Required.  Total amount of order, including shipping, handling, and tax.
            'currencycode' => 'USD', 					// Required.  Three-letter currency code.  Default is USD.
//            'itemamt' => $credit['total'], 						// Required if you include itemized cart details. (L_AMTn, etc.)  Subtotal of items not including S&H, or tax.
//            'shippingamt' => '', 					// Total shipping costs for the order.  If you specify shippingamt, you must also specify itemamt.
//            'shipdiscamt' => '', 					// Shipping discount for the order, specified as a negative number.
//            'handlingamt' => '', 					// Total handling costs for the order.  If you specify handlingamt, you must also specify itemamt.
//            'taxamt' => '', 						// Required if you specify itemized cart tax details. Sum of tax for all items on the order.  Total sales tax.
            'desc'         => 'Pizza Web Order', 							// Description of the order the customer is purchasing.  127 char max.
//            'custom' => '', 						// Free-form field for your own use.  256 char max.
//            'invnum' => '', 						// Your own invoice or tracking number
            'notifyurl'    => base_url() . 'paypal-result'						// URL for receiving Instant Payment Notifications.  This overrides what your profile is set to use.
        );


//        $OrderItems = array();
//        $Item	 = array(
//            'l_name' => 'Test Widget 123', 						// Item Name.  127 char max.
//            'l_desc' => 'The best test widget on the planet!', 						// Item description.  127 char max.
//            'l_amt' => '95.00', 							// Cost of individual item.
//            'l_number' => '123', 						// Item Number.  127 char max.
//            'l_qty' => '1', 							// Item quantity.  Must be any positive integer.
//            'l_taxamt' => '', 						// Item's sales tax amount.
//            'l_ebayitemnumber' => '', 				// eBay auction number of item.
//            'l_ebayitemauctiontxnid' => '', 		// eBay transaction ID of purchased item.
//            'l_ebayitemorderid' => '' 				// eBay order ID for the item.
//        );
//        array_push($OrderItems, $Item);
//
//        $Secure3D = array(
//            'authstatus3d' => '',
//            'mpivendor3ds' => '',
//            'cavv' => '',
//            'eci3ds' => '',
//            'xid' => ''
//        );

        $PayPalRequestData = array(
            'DPFields' => $DPFields,
            'CCDetails' => $CCDetails,
            'PayerInfo' => $PayerInfo,
            'PayerName' => $PayerName,
            'BillingAddress' => $BillingAddress,
//            'ShippingAddress' => $ShippingAddress,
            'PaymentDetails' => $PaymentDetails
//            'OrderItems' => $OrderItems
//            'Secure3D' => $Secure3D
        );

        $PayPalResult = $this->paypal_pro->DoDirectPayment($PayPalRequestData);

        if( empty($PayPalResult['ACK']) )
        {
            echo json_encode(array(
                'error' => true, 
                'message' => array(
                    'Internal Server Error'
                )
            ));

            return;
        }

        if( $this->paypal_pro->APICallSuccessful($PayPalResult['ACK']) )
        {
            echo json_encode(array('error' => false));
        }
        else
        {
            $errors = array('Errors' => $PayPalResult['ERRORS']);

            $message = array();

            foreach( $errors as $e )
            {
                foreach( $e as $m )
                {
                    $message[] = $m['L_LONGMESSAGE'];
                }
            }

            echo json_encode(array(
                'error' => true, 
                'message' => $message
            ));
        }
    }
}