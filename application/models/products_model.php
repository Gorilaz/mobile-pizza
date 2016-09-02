<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Products_model extends CI_Model{

    /**
     * Get all Products and Categories
     * @return mixed
     */
    public function getProductsAndCategories(){
        $products = $this->db->select('c.category_id, c.category_name, c.page_with_image, p.*, LOWER(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(TRIM(p.product_name), ' . $this->db->escape('/') . ', ' . $this->db->escape('') . '), ' . $this->db->escape(':') . ', ' . $this->db->escape('') . '), ' . $this->db->escape(')') . ', ' . $this->db->escape('') . '), ' . $this->db->escape('(') . ', ' . $this->db->escape('') . '), ' . $this->db->escape(',') . ', ' . $this->db->escape('') . '), ' . $this->db->escape('\\') . ', ' . $this->db->escape('') . '), ' . $this->db->escape('\/') . ', ' . $this->db->escape('') . '), ' . $this->db->escape('\"') . ', ' . $this->db->escape('') . '), ' . $this->db->escape('?') . ', ' . $this->db->escape('') . '), ' . $this->db->escape('\'') . ', ' . $this->db->escape('') . '), ' . $this->db->escape('&') . ', ' . $this->db->escape('') . '), ' . $this->db->escape('!') . ', ' . $this->db->escape('') . '), ' . $this->db->escape('.') . ', ' . $this->db->escape('') . '), ' . $this->db->escape(' ') . ', ' . $this->db->escape('-') . '), ' . $this->db->escape('--') . ', ' . $this->db->escape('-') . '), ' . $this->db->escape('--') . ', ' . $this->db->escape('-') . ')) AS friendly_url', FALSE)->
            join('tbl_product as p', 'p.category_id = c.category_id')->
            where('p.product_status','A')->
            where('c.category_status','A')->
            order_by('c.category_id')->
            get('tbl_product_categories as c')->result();

        return $products;

        // $products = $this->db->query('SELECT `c`.`category_id`, `c`.`category_name`, `c`.`page_with_image`, REPLACE(`p`.)');
    }

    /**
     * Get loyalty products
     * @return mixed
     */
    public function getLoyaltyProducts() {
        $products = $this->db->
            select('p.*, LOWER(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(TRIM(p.product_name), ' . $this->db->escape('/') . ', ' . $this->db->escape('') . '), ' . $this->db->escape(':') . ', ' . $this->db->escape('') . '), ' . $this->db->escape(')') . ', ' . $this->db->escape('') . '), ' . $this->db->escape('(') . ', ' . $this->db->escape('') . '), ' . $this->db->escape(',') . ', ' . $this->db->escape('') . '), ' . $this->db->escape('\\') . ', ' . $this->db->escape('') . '), ' . $this->db->escape('\/') . ', ' . $this->db->escape('') . '), ' . $this->db->escape('\"') . ', ' . $this->db->escape('') . '), ' . $this->db->escape('?') . ', ' . $this->db->escape('') . '), ' . $this->db->escape('\'') . ', ' . $this->db->escape('') . '), ' . $this->db->escape('&') . ', ' . $this->db->escape('') . '), ' . $this->db->escape('!') . ', ' . $this->db->escape('') . '), ' . $this->db->escape('.') . ', ' . $this->db->escape('') . '), ' . $this->db->escape(' ') . ', ' . $this->db->escape('-') . '), ' . $this->db->escape('--') . ', ' . $this->db->escape('-') . '), ' . $this->db->escape('--') . ', ' . $this->db->escape('-') . ')) AS friendly_url', FALSE)->
            where('p.product_points > 0')->
            where('p.product_status','A')->
            join('tbl_ref_friend as r', 'r.value = p.product_id', 'left')->
            get('tbl_product as p')->

            result();

        return $products;
    }

    /**
     * Get product Row
     * @param $id
     * @return mixed
     */
    public function getProductById($id) {
        return $this->db
            ->get_where('tbl_product', array('product_id' => $id))
            ->row();
    }

    public function getCategoryById($id)
    {
        return $this->db
            ->get_where('tbl_product_categories', array('category_id' => $id))
            ->row();
    }

    public function getVariationsById($id)
    {
        return $this->db->get_where('tbl_variations', array('product_id' => $id, 'available' => 'Y'))->result();
    }

    /**
     * Get product types (available in veriation_group table)
     * Returns Sizes, Deals, grouped by array key
     *
     * Variation with price 0 is the default one!
     *
     * @param $id
     * @return mixed
     */
    public function getProductVariations($id) {

        $result     = array(
            'halfs'         => false,
            'variations'    => false
        );
        $variations =  $this->db
            ->select('variation_group.*, tbl_variations.*, half_pizza_group.*')
            ->where('product_id',$id)
            ->where('tbl_variations.available','Y')
            ->join('tbl_variations','variation_group.ID = tbl_variations.variation_group_id','INNER')
            ->join('half_pizza_group','tbl_variations.half_pizza_group_id = half_pizza_group.ID','LEFT')
            ->order_by("variation_name", "asc")
            ->get('variation_group')
            ->result();

        if($variations) {
            $result['halfs'] = $this->getMatchedPizzaForHalf($variations, $id);

            foreach($variations as $variation) {
                if( isset($variation->type) )
                {
                    $result['type'] = $variation->type;
                }

                $result['variations'][$variation->title][] = $variation;
            }
        }
        return $result;
    }

    /**
     * @param $variations
     * @param $product_id
     * @return bool|string
     */
    public function getMatchedPizzaForHalf($variations, $product_id) {
        $result = false;

        if( !empty($variations) )
        {
            $half_pizza_group_ids = array();

            foreach( $variations as $variation )
            {
                if( !empty($variation->half_pizza_group_id) )
                {
                    $half_pizza_group_ids[] = $variation->half_pizza_group_id;
                }
            }
        }

        if( !empty($half_pizza_group_ids) )
        {
            $halfs = $this->db
                          ->select('tbl_product.product_id, tbl_product.product_name, tbl_product.description, tbl_product.product_image, tbl_product.product_price, tbl_variations.variation_price, tbl_variations.variation_id, tbl_variations.half_pizza_group_id')
                          ->where_in('tbl_variations.half_pizza_group_id', $half_pizza_group_ids)
                          ->where('tbl_variations.available', 'Y')
                          ->where('tbl_variations.product_id <> ' . $product_id)
                          ->join('tbl_product', 'tbl_product.product_id = tbl_variations.product_id', 'INNER')
                          ->get('tbl_variations')
                          ->result();

            if( !empty($halfs) )
            {
                $result = array();

                foreach( $halfs as $item )
                {
                    $item->description = strip_tags($item->description);

                    $result[$item->half_pizza_group_id][$item->variation_id] = $item;
                }
            }
        }

        if( $result )
        {
            return json_encode($result);
        }

        return false;
    }

    /**
     * Get ingredients based on variation     *
     * Groups ingredients
     *
     * Used on single view and checkout
     *
     * @param $variationId
     * @param $ingredientsIds
     * @return mixed
     */
    public function getIngredientsByVariation($variationId, $ingredientsIds = false) {

        $result         = false;

        $this->db
            ->select('tbl_ingredients.ingredient_id, status, price, ingredient_name, name')
            ->where('variation_id',$variationId);
        if($ingredientsIds) {
            $this->db->where_in('tbl_ingredients.ingredient_id', $ingredientsIds);
        }
        $this->db
            ->join('tbl_ingredients','tbl_variation_ingredients.ingredient_id = tbl_ingredients.ingredient_id','INNER')
            ->join('extra_ing_groups','extra_ing_groups.id = tbl_ingredients.group_id','LEFT')
            ->where_in('tbl_variation_ingredients.status', array('OP','DF'))
            ->order_by('ingredient_name','ASC')
            ;

        $ingredients    = $this->db->get('tbl_variation_ingredients')->result();
        if($ingredients) {

            foreach($ingredients as $item) {

                if($item->status == 'DF') {
                    $result['included'][] = $item;
                } else {
                    if($item->name == '') {
                        $result['extra']['Other'][] = $item;
                    } else {
                        // in case it has a group
                        $result['extra'][$item->name][] = $item;
                    }
                }

                /**
                 * In case ingredient don't have a group, check to see if it's an extra ingredient
                 * or an normal ingredient
                 */
//                if($item->name == '') {
//                    if(in_array($item->status,array('NA','DF'))) {
//                        $result['included'][] = $item;
//                    } else {
//                        $result['extra']['Other'][] = $item;
//                    }
//                }
            }
        }
        return $result;
    }


    /**
     * Get type of the product based on the variations returned
     * Returns array that is directly passed to the view
     *
     * - isSingle: pizza & pastas (has variations like size, sauce)
     * - isMultiple: deals (selectors for multiple products)
     * - isSimple: drinks, etc (no variations like size, sauce etc.)
     * - hasHalf: for pizza Only, if allows half pizza
     *
     * @param $variationGroups
     * @return array
     */

    public function getProductType($variationGroups, $product_type) {

        $return = array(
            'isSingle'      => false,
            'isMultiple'    => false,
            'isSimple'      => false,
            'hasHalf'       => false,
        );

        if($product_type === 'isSimple'){
            $return['isSimple'] = true;
        }

        if($product_type === 'isMultiple'){
            $return['isMultiple'] = true;
        }

        if($product_type === 'isSingle' || !$product_type){ //if product_type not set, display "Add/Modify Ingredients" on a product Page
            $return['isSingle'] = true;
        }

        if($variationGroups && is_array($variationGroups)) {
            $variations = array_keys($variationGroups);

            /**
             * Check if allows half pizza
             */
            if(isset($variationGroups['Size'])) {
                foreach($variationGroups['Size'] as $vars) {
                    if(isset($vars->half_pizza_group_status) && $vars->half_pizza_group_status == "A") {
                        $return['hasHalf'] = true;
                    }
                }
            }
        }

        return $return;
    }

    /**
     * Get product and product details based on variation.
     * Used on Checkout
     *
     * @param $variationId
     * @return mixed
     * @TODO: optimize to get only required columns
     */
    public function getItemByVariation($variationId) {

        $variation =  $this->db
            ->select('tbl_product.*, tbl_variations.*, variation_group.*')
            ->where('variation_id',$variationId)
            ->where('tbl_variations.available','Y')
            ->join('tbl_product','tbl_variations.product_id = tbl_product.product_id','INNER')
            ->join('variation_group','tbl_variations.variation_group_id = variation_group.ID','INNER')
            ->get('tbl_variations')
            ->row();
//        die( $this->db->last_query());
        if($variation) {
            return $variation;
        }
        return false;
    }


    /**
     * Get coupons
     * @param null $userId
     */
    public function getCoupons($userId = null) {
        $coupons = array();

        $now = date('Y-m-d');

        $need_coupon_for_first_order = false;

        if( $userId )
        {
            $hasOrder = $this->hasOrder($userId);

            if( !$hasOrder )
            {
                $need_coupon_for_first_order = true;
            }
        }
        else
        {
            $need_coupon_for_first_order = true;
        }

        $this->db
             ->where('status', 'active')
             ->where('expirydate >=', $now);

        if( $need_coupon_for_first_order )
        {
            $this->db
                 ->where_in('coupontype', array('firstorder', 'allorders'));
        }
        else
        {
            $this->db
                 ->where('coupontype', 'allorders');
        }

        $this->db
             ->order_by('discountper','desc');

        $all_coupons = $this->db
                        ->get('tbl_coupon')
                        ->result();

        if( !empty($all_coupons) )
        {
            foreach( $all_coupons as $coupon )
            {
                if( $need_coupon_for_first_order &&
                    $coupon->coupontype === 'firstorder' )
                {
                    $coupons['firstOrder'] = $coupon;
                }

                if( $coupon->coupontype === 'allorders' )
                {
                    $coupons['allOrder'] = $coupon;
                }

                if( ( $need_coupon_for_first_order &&
                        isset($coupons['firstOrder']) &&
                        isset($coupons['allOrder']) ) ||
                    ( !$need_coupon_for_first_order &&
                        isset($coupons['allOrder']) ) )
                {
                    break;
                }
            }
        }

        return $coupons;
    }

    /**
     * Verify if has order
     * @param $userId
     * @return bool
     */
    public function hasOrder($userId) {
        $has_order = $this->db->where('userid', $userId)->count_all_results('mast_order');

        return ( $has_order > 0 );
    }

    /**
     * get social locker
     * @return mixed
     */
    public function getSocialLocker(){

        $socialLocker = $this->db->select('couponcode')->where('social_loker', 1)->limit(1)->get('tbl_coupon')->row();

        return $socialLocker;


    }

    /**
     * get public holliday
     */
    public function getPublicHoliday(){

        $holiday = $this->db->select('value')->where('type', 'public_holiday')->get('sitesetting')->row();

        return $holiday->value;
    }

    /**
     * get holiday fee
     */
    public function getHolidayFee(){

       $holidayFee = $this->db->select('public_holiday_amt')->get('tbl_order_surcharge')->row();

       return $holidayFee->public_holiday_amt;
    }
}