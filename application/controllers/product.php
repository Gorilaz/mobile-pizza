<?php
/**
 * Created by PhpStorm.
 * User: GabrielCol
 * Date: 11/11/13
 * Time: 7:10 PM
 */

class product extends WMDS_Controller {

    /**
     * Information about product: Single Page
     * @param $id
     * @param $points
     */
    public function view($id, $points = false) {
        $this->load->model('products_model');
        $this->load->model('order_model');
        $this->load->model('Sitesettings_model', 'SS_Model');
        if( !is_numeric($id) )
        {   //error_log("113123" . time(),1);
            $product = $this->db->select('*, LOWER(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(TRIM(p.product_name), ' . $this->db->escape(':') . ', ' . $this->db->escape('') . '), ' . $this->db->escape(')') . ', ' . $this->db->escape('') . '), ' . $this->db->escape('(') . ', ' . $this->db->escape('') . '), ' . $this->db->escape(',') . ', ' . $this->db->escape('') . '), ' . $this->db->escape('\\') . ', ' . $this->db->escape('') . '), ' . $this->db->escape('\/') . ', ' . $this->db->escape('') . '), ' . $this->db->escape('\"') . ', ' . $this->db->escape('') . '), ' . $this->db->escape('?') . ', ' . $this->db->escape('') . '), ' . $this->db->escape('\'') . ', ' . $this->db->escape('') . '), ' . $this->db->escape('&') . ', ' . $this->db->escape('') . '), ' . $this->db->escape('!') . ', ' . $this->db->escape('') . '), ' . $this->db->escape('.') . ', ' . $this->db->escape('') . '), ' . $this->db->escape(' ') . ', ' . $this->db->escape('-') . '), ' . $this->db->escape('--') . ', ' . $this->db->escape('-') . '), ' . $this->db->escape('--') . ', ' . $this->db->escape('-') . ')) as friendly_url', FALSE)->having('friendly_url', $id)->get('tbl_product as p')->row();
            //error_log("rrqwer" . time(),0);
        }
        else
        {
            $product = $this->products_model->getProductById($id);
        }

        $siteSetting = $this->session->userdata('siteSetting');

        if( !$product )
        {
            $this->load->library('../controllers/page');

            $this->page->staticpage($id, $points);

            die;
        }

        $category = $this->products_model->getCategoryById($product->category_id);

        $og = array(
            'title' => $product->product_name,
            'description' => $product->description,
            'type' => 'product.item',
            'image' => $siteSetting->assests_url . '/templates/' . $siteSetting->TEMPLATEDIR . '/uploads/products/thumb/' . ( empty($product->product_image) ? 'no_prod_image_thumb.png' : $product->product_image ),
            'url' => current_url(),
            'product' => array(
                'brand' => $siteSetting->restaurant_name,
                'category' => $category->category_name,
                'price:amount' => $product->product_price,
                'price:currency' => '$'
            )
        );

        $variations = $this->products_model->getVariationsById($product->product_id);

        $size = false;

        foreach( $variations as $variation )
        {
            $variation_price = (float) $variation->variation_price;

            if( empty($variation_price) )
            {
                $size = $variation->variation_name;

                break;
            }
        }

        if( $size !== false )
        {
            $og['product']['size'] = $size;
        }

        $this->twiggy->set('og', $og);

        /**
         * Calculate left points
         */
        $this->load->library('cart');

        $cart = $this->cart->contents();

        $userdPoints = 0;

        foreach( $cart as $item )
        {
            if( !empty($item['points']) )
            {
                $userdPoints += $item['points'];
            }
        }

        $user = $this->session->userdata('logged');

        $pointsLeft = $user['order_points'] - $userdPoints;

        $this->twiggy->set('pointsLeft', $pointsLeft);

        /**
         * Get and format product images
         */
        $image = false;

        if( !empty($product->product_image) )
        {
            $image['full'] = $product->product_image;

            $image_extension = substr($product->product_image, -4, 4);
            $image_name = substr($product->product_image, 0, -4);

            $image['thumb'] = $image_name . '_thumb' . $image_extension;
        }

        /**
         * Deal with variations
         */

        $variationsGroups = $this->products_model->getProductVariations($product->product_id);

        //$productType = $this->products_model->getProductType($variationsGroups['variations'], isset($variationsGroups['type']) ? $variationsGroups['type'] : '');
        $productType = $this->products_model->getProductType($variationsGroups['variations'], $product->product_type);

        $withPoints = $product->product_points > 0 && $points === 'points';

        $loyalty_description = $this->db->where('type', 'loyalty_description')->get('tbl_manage_text')->row()->value;

        $this->twiggy
            ->set('product',$product)
            ->set('image',  $image)
            ->set('options',$variationsGroups['variations'])
            ->set('halfs',  $variationsGroups['halfs'])
            ->set('withPoints', $withPoints)
            ->set('loyalty_description', $loyalty_description)
            ->set('isLoyalty', ( ( empty($product->product_points) || empty($points) ) ? false : true ))
            ->set($productType);

        $sitesettings = $this->db->select('type, value')->where_in('type', array('restaurant_name', 'restaurant_suburb'))->get('sitesetting')->result();

        if( !empty($sitesettings) )
        {
            foreach( $sitesettings as $sitesetting )
            {
                if( $sitesetting->type === 'restaurant_name' )
                {
                    $restaurant_name = $sitesetting->value;
                }

                if( $sitesetting->type === 'restaurant_suburb' )
                {
                    $restaurant_suburb = $sitesetting->value;
                }
            }
        }

        $restaurant_name = empty($restaurant_name) ? '' : $restaurant_name;
        $restaurant_suburb = empty($restaurant_suburb) ? '' : $restaurant_suburb;

        $this->twiggy->set('page', array(
            'title'  => $product->product_name,
            'keywords' => $product->product_name . ', ' . $restaurant_name . ', ' . $restaurant_suburb,
            'description' => $product->product_name . ' by ' . $restaurant_name . ' - ' . strip_tags($product->description),
            'role'   => 'page',
            'theme'  => 'a',
            'id'     => 'page-product'
        ));

        $this->twiggy->set(array(
                'itemsNo'   => $this->cart->total_items(),
                'total'     => $this->cart->total(),
                'minOrder'  => $this->order_model->getMinimumOrder()
            )
        );

        $this->twiggy->template('product/details')->display();
    }

    /**
     * Used for dynamically getting product ingredients based on variation
     * @param $variationId
     * @return string
     */
    public function ingredients($variationId) {

        if (!$this->input->is_ajax_request()) {
            exit('No direct script access allowed');
        }

        $this->load->model('products_model');

        $ingredients = $this->products_model->getIngredientsByVariation($variationId);

//        print_r($ingredients);

        header('Content-type: application/json');
        echo json_encode($ingredients);
    }

    /**
     * Remove one item from the cart
     * @param $rowid
     */
    public function removeItemFromCart($rowid) {
        if (!$this->input->is_ajax_request()) {
            exit('No direct script access allowed');
        }
        $data = array(
            'rowid' => $rowid,
            'qty'   => 0
        );

        $this->cart->update($data);
    }
}
