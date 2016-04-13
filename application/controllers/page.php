<?php
/**
 * Created by PhpStorm.
 * User: GabrielCol
 * Date: 11/9/13
 * Time: 3:42 PM
 * @property Security_model $security_model
 */

class Page extends WMDS_Controller {


    function __construct()
    {
        parent::__construct();
        $this->load->model('general');
        $this->load->model('products_model');
        $this->load->model('order_model');
    }

    public function myaccount() {
        $this->twiggy->set('page', array(
            'title'  => 'My Account',
            'role'   => 'page',
            'theme'  => 'a',
            'backButton'=> true,
        ));

        /** User mobile */
        $this->load->library('session');
        $user = $this->session->userdata('logged');
        if(isset($user['mobile'])){
            $this->twiggy->set('mobile', $user['mobile']);
        }

        $this->twiggy->template('page/myaccount')->display();
    }

    /**
     * Home Page, show splash screen and display
     * if shop is open or close
     */
    public function index($referal = null)

    {

        if($referal){
            $logged = $this->session->userdata('logged');
            if(!$logged){
                $this->load->model('security_model');
                $user = $this->security_model->checkMobileNumber($referal);

                if($user){
                    $this->twiggy->set('user', $user);
                    $this->load->helper('cookie');
                    $cookie = array(
                        'name'   => 'referal',
                        'value'  => '3',
                        'expire' => '99500'
                    );

                    $this->input->set_cookie($cookie);

                    $this->twiggy->display('page/referal');
                } else {
                    redirect(base_url().'404_override');
                }
            }
            $this->twiggy->set('referal', $referal);
        } else {
            $this->twiggy->set('page', array(
                'title'  => 'Welcome',
                'role'   => 'page',
                'id'     => 'page-home'
            ));

            $pageContent = $this->general->getSiteText(array('top_h1', 'top_h2', 'top_h3', 'top_h4'));

            $this->twiggy->set('pageContent', $pageContent);

            $this->twiggy->set('internalPage', false);

            $this->twiggy->template('page/home')->display();
        }

    }

    /**
     * Menu page - List all Categories and Products
     */
    public function menu() {

        $pageContent = $this->general->getSiteText('promotion_text');

        $this->twiggy->set('promoText', $pageContent);

        /**
         * Do we have to add an item to cart?
         */
        if($this->input->post()) {
            $productIngredients = array();
            $options = array();

            parse_str($this->input->post('general'), $post);
            parse_str($this->input->post('ingredients'), $ingredients);
            parse_str($this->input->post('ingredients2'), $ingredients2);

//            print_r($post);
//            print_r($ingredients);
//            print_r($ingredients2);die;
            /**
             * Case: Half Pizza Order
             */
            if(isset($post['halfPizza']) && $post['halfPizza'] > 0) {
                $product = $this->products_model->getItemByVariation($post['variation']);
                if($ingredients && count($ingredients) > 0) {
                    $productIngredients = $this->products_model->getIngredientsByVariation($post['variation']);

                }

                $product2 = $this->products_model->getItemByVariation($post['halfPizza']);
                if($ingredients2 && count($ingredients2) > 0) {
                    $productIngredients2 = $this->products_model->getIngredientsByVariation($post['halfPizza']);
                }

                /* Add data to cart */
                $this->cart->insert($this->formatDataForCart(
                    array(
                        'product'       => $product,
                        'ingredients'   => array(
                            'all'           => $productIngredients,
                            'selected'      => (isset($ingredients['ingredient']))?$ingredients['ingredient']:'',
                        ),
                        'product2'       => $product2,
                        'ingredients2'   => array(
                            'all'           => $productIngredients2,
                            'selected'      => (isset($ingredients2['ingredient']))?$ingredients2['ingredient']:'',
                        ),
                        'details'       => $post
                    ),
                    'half'
                ));
            }

            /**
             * Case: Products With Variations
             */
            elseif(isset($post['variation']) && !is_array($post['variation'])) {
                $product = $this->products_model->getItemByVariation($post['variation']);
                if($ingredients && count($ingredients) > 0) {
                    $productIngredients = $this->products_model->getIngredientsByVariation($post['variation']);
                }

//                print_r($ingredients);

                /* Add data to cart */
                $this->cart->insert($this->formatDataForCart(
                    array(
                        'product'       => $product,
                        'ingredients'   => array(
                            'all'           => $productIngredients,
                            'selected'      => (isset($ingredients['ingredient']))?$ingredients['ingredient']:''
                        ),
                        'details'       => $post
                    ),
                    'single'
                ));
            }

            /**
             * Case: Deal Order
             */
            elseif(isset($post['variation']) && is_array($post['variation'])) {
                $product = $this->products_model->getProductById($post['pid']);

                foreach($post['variation'] as $key => $variation) {
                    $options[$key] = $this->products_model->getItemByVariation($variation);
                }

                $this->cart->insert($this->formatDataForCart(
                    array(
                        'product'       => $product,
                        'details'       => $post,
                        'options'       => $options
                    ),
                    'deal'
                ));
            }

            /**
             * Case: Simple Products
             */
            else {
                $product = $this->products_model->getProductById($post['pid']);

                /* Add data to cart */
                $this->cart->insert($this->formatDataForCart(
                    array(
                        'product'       => $product,
                        'details'       => $post
                    ),
                    'single'
                ));
            }


//            print_r($product);
//            print_r($ingredients);
//            print_r($productIngredients);

        }



        /**
         * Clear cart - came in GET from checkout page
         */
        if($this->input->get('action') == 'clear-cart') {
            $this->cart->destroy();
            $this->twiggy->set('notice', 'Your cart is now empty');
        }

        $products_db = $this->products_model->getProductsAndCategories();
        $products = array();

        //print_r($products_db);
        if($products_db) {
            foreach ($products_db as $prod) {
                if (!isset($products[$prod->category_id])) {
                    $products[$prod->category_id]['category_name'] = $prod->category_name;
                    $products[$prod->category_id]['withImage'] = ($prod->page_with_image == 'enable')?true:false;
                } else {
                    $products[$prod->category_id]['items'][] = $prod;
                }
            }
        }

        /* Add loyalty products */
        $loyaltyProducts = $this->products_model->getLoyaltyProducts();
        if($loyaltyProducts) {
            $products['loyalty']['category_name'] = 'FREE';
            $products['loyalty']['items']         = $loyaltyProducts;
        }

        $metas = $this->db->select('title, keywords, description')->where('pagename', 'menu')->get('tbl_meta_tags')->row();

        $this->twiggy->set('page', array(
            'title'  => $metas->title,
            'keywords' => $metas->keywords,
            'description' => $metas->description,
            'role'   => 'page',
            'theme'  => 'a',
            'id'     => 'page-menu'

        ));

        $this->twiggy->set('products', $products);
        /* Set Cart Variables */
        $this->twiggy->set(array(
                'itemsNo'   => $this->cart->total_items(),
                'total'     => $this->cart->total(),
                'minOrder'  => $this->order_model->getMinimumOrder()
            )
        );

        $this->twiggy->template('page/menu')->display();
    }

    /**
     * Static pages
     */
    public function staticpage($name, $session_id =null) {


        $page = $this->general->getPageByType($name);

//        print_r($page);
        if($name == 'order-success'){

            $this->load->model('security_model');

            if($this->session->userdata('session_id') != $session_id && !empty($session_id)){
                $ip = $this->get_client_ip();
                $browser = $_SERVER['HTTP_USER_AGENT'];

                $session = unserialize($this->general->getSession($session_id, $ip, $browser));
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
            }

            $logged = $this->session->userdata('logged');

            $user = $this->security_model->getUser($logged['userid']);
            $this->session->set_userdata('logged', $user);

            /* reset another site command */
            $this->session->unset_userdata('back_url');
            /* reset checkout */
            $this->session->unset_userdata('checkout');
            /* reset low order */
            $this->session->unset_userdata('low_order');
            /* reset surchange */
            $this->session->unset_userdata('surchange');

            /** reset cart */
            $this->cart->destroy();

            $this->twiggy->set('logged', $logged);
            $this->twiggy->set('userPoints', $logged['order_points']);

            $this->twiggy->set('menuPage', 1);

            $this->twiggy->set('page', array(
                'title'     => $page->title,
                'data'      => $page,
                'backButton'=> false,
                'role'      => 'page',
                'theme'     => 'a'
            ));
        } else {
            $this->twiggy->set('page', array(
                'title'     => $page->title,
                'data'      => $page,
                'backButton'=> true,
                'role'      => 'page',
                'theme'     => 'a'
            ));
        }


        $this->twiggy->template('page/staticPage')->display();

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

    public function orderAgain($orderId = null){

        $shopping = $this->order_model->getShoppingOrder($orderId);

//        print_r($shopping);die;


        $i = 0;
        while ($i < count($shopping)) {

            $variation = explode(',', $shopping[$i]['variation_id']);
            $variationCount = count($variation);


            /** Half */
            if($shopping[$i]['product_flag'] == 'H'){

                $halfFee = $this->order_model->getHalfFee($shopping[$i]['half_pizza_group_id']);

                $post = array(
                    'p-quantity'    => $shopping[$i]['quantity'],
                    'variation'     => $shopping[$i]['variation_id'],
                    'halfPizza'     => $shopping[$i+1]['variation_id'],
                    'half-group-id' => $shopping[$i]['half_pizza_group_id'],
                    'half-fee'      => $halfFee,
                    'isHalf'        => 1,
                    'buyWithPoints' => 0,
                    'hasHalfInput'  => 1,
                    'hasIngredientsInput' => 0,
                    'textarea'      => $shopping[$i]['comment'],
                    'pid'           => $shopping[$i]['product_id']

                );

                $firstIngredinets = array();
                if(!empty($shopping[$i]['extra_ingredient_id'])){
                    $firstExtIng = explode(',',$shopping[$i]['extra_ingredient_id']);
                    $firstIngredinets = array_merge($firstIngredinets, $firstExtIng);
                }
                if(!empty($shopping[$i]['default_ingredient_id'])){
                    $productIngredients = $this->products_model->getIngredientsByVariation($shopping[$i]['variation_id']);

                    $firstDefIng = explode(',',$shopping[$i]['default_ingredient_id']);


                    $def = array();
                    $j = 0;
                    foreach($productIngredients['included'] as $item){

                        if(!in_array($item->ingredient_id, $firstDefIng)){
                            $def[$j] = $item->ingredient_id;
                            $j++;
                        }
                    }

                    $firstIngredinets = array_merge($firstIngredinets, $def);
                }


                $ingredients = array(
                    'ingredient' => $firstIngredinets
                );

                $secondIngredients = array();
                if(!empty($shopping[$i+1]['extra_ingredient_id'])){
                    $secondExtIng = explode(',',$shopping[$i+1]['extra_ingredient_id']);
                    $secondIngredients = array_merge($secondIngredients, $secondExtIng);
                }

                if(!empty($shopping[$i+1]['default_ingredient_id'])){
//
                    $productIngredients = $this->products_model->getIngredientsByVariation($shopping[$i+1]['variation_id']);
                    $secondDefIng = explode(',',$shopping[$i+1]['default_ingredient_id']);
                    $def = array();
                    $j = 0;

                    foreach($productIngredients['included'] as $item){

                        if(!in_array($item->ingredient_id, $secondDefIng)){

                            $def[$j] = $item->ingredient_id;
                            $j++;
                        }
                    }
//
                    $secondIngredients = array_merge($secondIngredients, $def);
                }


                $ingredients2 = array(
                    'ingredient' => $secondIngredients
                );


                /** salt the other half */
                $i++;
            }
            /** Single */
            elseif($variationCount == 1){

                $halfFee = $this->order_model->getHalfFee($shopping[$i]['half_pizza_group_id']);

                $post = array(
                    'p-quantity'    => $shopping[$i]['quantity'],
                    'variation'     => $shopping[$i]['variation_id'],
                    'halfPizza'     => '',
                    'half-group-id' => '',
                    'half-fee'      => '',
                    'isHalf'        => 1,
                    'buyWithPoints' => 0,
                    'hasHalfInput'  => 0,
                    'hasIngredientsInput' => 0,
                    'textarea'      => $shopping[$i]['comment'],
                    'pid'           => $shopping[$i]['product_id']

                );


                $ingredints = array();
                if(!empty($shopping[$i]['extra_ingredient_id'])){
                    $extraIng = explode(',',$shopping[$i]['extra_ingredient_id']);
                    $ingredints = array_merge($ingredints, $extraIng);
                }
                if(!empty($shopping[$i]['default_ingredient_id'])){
                    $defaultIng = explode(',',$shopping[$i]['default_ingredient_id']);
                    $ingredints = array_merge($ingredints, $defaultIng);
                }

                $ingredients = array(
                    'ingredient' => $ingredints
                );

                $ingredients2 = array(
                    'ingredient' => array()
                );

                /** Deal */
            } else {

                $post = array(
                    'p-quantity'    => $shopping[$i]['quantity'],
                    'variation'     => $variation,
                    'buyWithPoints' => 0,
                    'hasHalfInput'  => 0,
                    'hasIngredientsInput' => 0,
                    'textarea'      => $shopping[$i]['comment'],
                    'pid'           => $shopping[$i]['product_id']

                );

                $ingredients = array();
                $ingredients2 = array();

            }
//            print_r($post);
//            print_r($ingredients);
//            print_r($ingredients2);die;
            $this->orderAgainIsertCart($post, $ingredients,$ingredients2);
            $i++;
        }
        redirect(base_url().'checkout');

    }

    /**
     * Parameters received from orderAgain to insert products in cart
     * @param $post
     * @param $ingredients
     * @param $ingredients2
     */
    private function orderAgainIsertCart($post, $ingredients, $ingredients2 ){

//        print_r($post);
//        print_r($ingredients);
//        print_r($ingredients2);die;
        /**
         * Case: Half Pizza Order
         */
        if(isset($post['halfPizza']) && $post['halfPizza'] > 0) {
            $product = $this->products_model->getItemByVariation($post['variation']);
            if($ingredients && count($ingredients) > 0) {
                $productIngredients = $this->products_model->getIngredientsByVariation($post['variation']);

            }

            $product2 = $this->products_model->getItemByVariation($post['halfPizza']);
            if($ingredients2 && count($ingredients2) > 0) {
                $productIngredients2 = $this->products_model->getIngredientsByVariation($post['halfPizza']);
            }

            /* Add data to cart */
            $this->cart->insert($this->formatDataForCart(
                array(
                    'product'       => $product,
                    'ingredients'   => array(
                        'all'           => $productIngredients,
                        'selected'      => (isset($ingredients['ingredient']))?$ingredients['ingredient']:'',
                    ),
                    'product2'       => $product2,
                    'ingredients2'   => array(
                        'all'           => $productIngredients2,
                        'selected'      => (isset($ingredients2['ingredient']))?$ingredients2['ingredient']:'',
                    ),
                    'details'       => $post
                ),
                'half'
            ));
        }

        /**
         * Case: Products With Variations
         */
        elseif(isset($post['variation']) && !is_array($post['variation'])) {
            $product = $this->products_model->getItemByVariation($post['variation']);
            if($ingredients && count($ingredients) > 0) {
                $productIngredients = $this->products_model->getIngredientsByVariation($post['variation']);
            }


            /* Add data to cart */
            $this->cart->insert($this->formatDataForCart(
                array(
                    'product'       => $product,
                    'ingredients'   => array(
                        'all'           => $productIngredients,
                        'selected'      => (isset($ingredients['ingredient']))?$ingredients['ingredient']:''
                    ),
                    'details'       => $post
                ),
                'single'
            ));
        }

        /**
         * Case: Deal Order
         */
        elseif(isset($post['variation']) && is_array($post['variation'])) {
            $product = $this->products_model->getProductById($post['pid']);

            foreach($post['variation'] as $key => $variation) {

                $options[$key] = $this->products_model->getItemByVariation($variation);
            }

            $this->cart->insert($this->formatDataForCart(
                array(
                    'product'       => $product,
                    'details'       => $post,
                    'options'       => $options
                ),
                'deal'
            ));
        }

        /**
         * Case: Simple Products
         */
        else {
            $product = $this->products_model->getProductById($post['pid']);

            /* Add data to cart */
            $this->cart->insert($this->formatDataForCart(
                array(
                    'product'       => $product,
                    'details'       => $post
                ),
                'single'
            ));
        }
    }

    /**
     * Helper - Formats data to insert into the cart object
     */
    private function formatDataForCart($data = array(), $type) {

        $order = array();

        if($data['product']) {
            switch($type) {
                case 'single':
                    $orderDetails = $this->formatPriceAndOptions($data);

                    /* deal with loyalty program - buy with points */
                    if($data['details']['buyWithPoints'] == 1) {
                        $order = array(
                            'id'        => $data['product']->product_id,
                            'name'      => $data['product']->product_name,
                            'price'     => (float)0,
                            'prod_price'=> (float)0,
                            'points'    => $data['product']->product_points*$data['details']['p-quantity'],
                            'prod_points'=>$data['product']->product_points,
                            'qty'       => $data['details']['p-quantity'],
                            'options'   => $orderDetails['options'],
                            'ingredient_ids' => $orderDetails['ingredient_ids'],
                            'instruction'    => $data['details']['textarea'],
                            'half_pizza_group_id' => 0,
                            'variation_id'        => $data['details']['variation'],
                            'product_type'        => 'single'
                        );
                    } else {
                        $order = array(
                            'id'        => $data['product']->product_id,
                            'name'      => $data['product']->product_name,
                            'price'     => $orderDetails['price'],
                            'prod_price'=> $data['product']->product_price,
                            'qty'       => $data['details']['p-quantity'],
                            'options'   => $orderDetails['options'],
                            'ingredient_ids' => $orderDetails['ingredient_ids'],
                            'instruction'    => $data['details']['textarea'],
                            'half_pizza_group_id' => 0,
                            'variation_id'        => $data['details']['variation'],
                            'product_type'        => 'single'
                        );
                    }

                    break;

                case 'half':

                    $orderDetails = $this->formatPriceAndOptions($data);
                    $order = array(
                        'id'        => $data['product']->product_id.'_'.$data['product2']->product_id,
                        'name'      => 'Half and Half Pizza', //TODO: find a way to not hardcode this?
                        'price'     => $orderDetails['price'],
                        'prod_price'=> $data['details']['half-fee'],
                        'qty'       => $data['details']['p-quantity'],
                        'options'   => $orderDetails['options'],
                        'ingredient_ids' => $orderDetails['ingredient_ids'],
                        'instruction'    => $data['details']['textarea'],
                        'half_pizza_group_id' => $data['details']['half-group-id'],
                        'variation_id'        => $data['details']['variation'],
                        'variation_id_half'   => $data['details']['halfPizza'],
                        'product_type'        => 'half'
                    );

                    break;

                case 'deal':

                    $orderDetails = $this->formatPriceAndOptions($data);
                    $order = array(
                        'id'        => $data['product']->product_id,
                        'name'      => $data['product']->product_name,
                        'price'     => $orderDetails['price'],
                        'prod_price'=> $data['product']->product_price,
                        'qty'       => $data['details']['p-quantity'],
                        'options'   => $orderDetails['options'],
                        'instruction'  => $data['details']['textarea'],
                        'variation_id' => $data['details']['variation'],
                        'product_type' => 'deal'
                    );
                    break;
            }
        }

        return $order;
    }


    private function formatPriceAndOptions($data) {

        $index = 0;
        $orderElements = array();
        $ingIDs = array();

        /* Add pizza first in case its a half half order */
        if(isset($data['details']['halfPizza']) && $data['details']['halfPizza'] > 0) {
            $price =$data['product']->product_price/2;


            if(isset($data['product2'])) {
                $halfPrefixText = 'First Half';
            } else {
                $halfPrefixText = 'Second Half';
            }

            $orderElements[$index]['price'] = $data['product']->product_price/2;
            $orderElements[$index]['name']  = $halfPrefixText.': '.$data['product']->product_name;
            $index++;
        } else {
            $price = $data['product']->product_price;
        }

        /* Add variation price if any */
        if(isset($data['product']->variation_price)) {

            $price+=$this->getPrice($data['product']->variation_price,$data['details']);

            $orderElements[$index]['price'] = $this->getPrice($data['product']->variation_price,$data['details']);
            $orderElements[$index]['name']  = $data['product']->title.': '.$data['product']->variation_name;
            $index++;
        }

        /* Add/Subtract ingredients price */
        if(isset($data['ingredients']) && count($data['ingredients']) > 0 && !empty($data['ingredients']['selected'])) {


            /* Surf in all possible ingredients and mark what has been selected/deselected */
            foreach($data['ingredients']['all'] as $type => $items) {

                /*
                 * Included documents - if one included is not selected, we need to subtract the amount
                 */
                if($type == 'included') {

                    foreach($items as $key => $item) {
//                        if($item->status == 'DF' && !in_array($item->ingredient_id,$data['ingredients']['selected'])) {

                        if(!in_array($item->ingredient_id,$data['ingredients']['selected'])) {

//                            $price-=$this->getPrice($item->price,$data['details']);
                            if(!isset($halfPrefixText) || empty($halfPrefixText)){
                                $halfPrefixText = 'Single';
                            }
                            /** ingredient id */
                            $ingIDs[$halfPrefixText]['default'][] = $item->ingredient_id;

                            $orderElements[$index]['price'] = false;
                            $orderElements[$index]['name']  = '-NO: '.$item->ingredient_name;
                            $index++;

                        }
//                        elseif($item->status != 'DF' && in_array($item->ingredient_id,$data['ingredients']['selected'])) {
////                            $price+=$this->getPrice($item->price,$data['details']);
//
//                            $orderElements[$index]['price'] = false;
//                            $orderElements[$index]['name']  = '+WITH: '.$item->ingredient_name;
//                            $index++;
//                        }
                    }
                }
                /*
                 * Extra Ingredients - any selected is a plus to the order
                 */
                else {
                    foreach($items as $extraCategory => $ingredients) {
                        foreach($ingredients as $key => $item) {

                            if(in_array($item->ingredient_id,$data['ingredients']['selected'])) {
                                $price+=$this->getPrice($item->price,$data['details']);

                                if(!isset($halfPrefixText) || empty($halfPrefixText)){
                                    $halfPrefixText = 'Single';
                                }
                                $ingIDs[$halfPrefixText]['extra'][] = $item->ingredient_id;

                                $orderElements[$index]['price'] = $this->getPrice($item->price,$data['details']);
                                $orderElements[$index]['name']  = '+EXTRA: '.$item->ingredient_name;
                                $index++;
                            }
                        }
                    }
                }
            }
        }

        /* If its deal */
        if(isset($data['options'])) {
            foreach($data['options'] as $item) {

                $price+=$item->variation_price;

                $orderElements[$index]['price'] = $item->variation_price;
                $orderElements[$index]['name']  = $item->title.': '.$item->variation_name;
                $index++;
            }
        }

        /* If it's half order */
        if(isset($data['product2'])) {
            /**
             * Calling self function recursively to get info for the 2nd pizza
             */
            $halfOptions = $this->formatPriceAndOptions(
                array(
                    'product' => $data['product2'],
                    'ingredients' => $data['ingredients2'],
                    'details'   => $data['details']
                )
            );

            return array(
                'price'     => $price + $halfOptions['price'] + $data['details']['half-fee'],
                'options'   => array_merge($orderElements,$halfOptions['options']),
                'ingredient_ids' =>  array_merge($ingIDs,$halfOptions['ingredient_ids'])
            );
        } else {
            return array(
                'price'     => $price,
                'options'   => $orderElements,
                'ingredient_ids' => $ingIDs
            );
        }
    }

    private function getPrice($price, $details) {
        if(isset($details['halfPizza']) && $details['isHalf'] > 0) {
            return $price/2;
        }
        return $price;
    }
}