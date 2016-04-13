<?php

class Category_Model extends CI_Model
{

    public function __construct()
    {
        parent::__construct();
        $this->load->database();
    }

//end Faq_Model

    public function categoryOperations($data, $action = '', $edit_id = 0)
    {
        switch ($action)
        {
            case 'addnew':
                //	insert new record
                if (is_array($data))
                {
                    $this->db->query($this->db->insert_string("tbl_product_categories", $data));
                    return $this->db->insert_id();
                }
                break;
            case 'update':
                //	update existing record
                if (is_array($data))
                {
                    $this->db->query($this->db->update_string("tbl_product_categories", $data, array('category_id' => $edit_id)));
                    return 1;
                }
                break;
            case 'delete':
                $this->db->query("delete from tbl_product_categories where category_id in (" . $data['category_id'] . ")");
                $this->_deleteCategoryTree($data['category_id']);

                return "Product Category(s) deleted successfully.";
                break;
            case 'active':
                $this->db->query("update tbl_product_categories set category_status='A' where category_id in (" . $data['category_id'] . ")");
                return "Product Category(s) activated successfully.";
                break;
            case 'inactive':
                $this->db->query("update tbl_product_categories set category_status='D' where category_id in (" . $data['category_id'] . ")");
                return "Product Category(s) inactivated successfully.";
                break;
        }
    }

//end categoryOperations

    /* Deleting whole categories,product,variation */

    function _deleteCategoryTree($category_id)
    {
        $this->db->select('product_id');
        $this->db->where_in('category_id', $category_id);
        $_prod_id = $this->db->get('tbl_product')->result();

        if ($_prod_id)
        {
            foreach ($_prod_id as $val)
            {
                $product_id[] = $val->product_id;
            }
            //print_r($product_id); exit;


            /* Deleting product images */

            $cnt = sizeof($product_id);
            for ($i   = 0; $i < $cnt; $i++)
            {
                $res = $this->db->select('product_image')->get_where('tbl_product', array('product_id' => $product_id[$i]))->row();
                if (!empty($res) && !empty($res->product_image))
                {

                    $product_image_file = $this->config->item('base_abs_path') . 'uploads/products/thumb/' . $res->product_image;

                    if (file_exists($product_image_file))
                    {
                        unlink($product_image_file);
                    }
                }
            }
            /* End Deleting product images */

            $product_ids = implode(',', $product_id);

            $this->db->select('category_id');
            $_cat_id = $this->db->get('tbl_product_categories')->result();
            if ($_cat_id)
            {
                foreach ($_cat_id as $val)
                {
                    $prod_cat_id[] = $val->category_id;
                }

                $_cat_ids = implode(',', $prod_cat_id);
                $this->db->query("delete from tbl_product where category_id NOT IN (" . $_cat_ids . ")");
            }


            /* Deleting Products and variations */
            if ($this->db->query("delete from tbl_product where product_id in (" . $product_ids . ")"))//deleting from product table
            {
                $this->db->select('product_id');
                $res_pid = $this->db->get('tbl_product')->result();

                if (!empty($res_pid))
                {
                    foreach ($res_pid as $val)
                    {
                        $pid[] = $val->product_id;
                    }
                    $this->db->where_not_in('product_id', $pid);
                    if ($this->db->delete('tbl_variations'))
                    {
                        $this->db->select('variation_id');
                        $res_vid = $this->db->get('tbl_variations')->result();
                        if (!empty($res_vid))
                        {
                            foreach ($res_vid as $val)
                            {
                                $vid[] = $val->variation_id;
                            }
                            $this->db->where_not_in('variation_id', $vid);
                            $this->db->delete('tbl_variation_ingredients');
                        }
                    }
                }
                else
                {
                    $this->db->truncate('tbl_variations');
                    $this->db->truncate('tbl_variation_ingredients');
                }
            }
        }
        /* End Deleting Products and variations */

        return true;
    }

//end _deleteCategoryTree

    public function countCategoryRecords($search = '-')
    {
        if ($search != "-")
            return $this->db->query("select count(category_id) as rowcount from tbl_product_categories where category_name like '%" . $search . "%'")->row()->rowcount;
        else
            return $this->db->query("select count(category_id) as rowcount from tbl_product_categories")->row()->rowcount;
    }

//end countCategoryRecords

    public function getCategoryRecords($num, $offset, $search = '-')
    {
        if ($search != "-")
        {
            return $this->db->query("SELECT * from tbl_product_categories where category_name like '%" . $search . "%' limit " . $num . "," . $offset);
        }
        else
        {
            return $this->db->query("SELECT * from tbl_product_categories where 1 order by category_id desc limit " . $num . "," . $offset);
        }
    }

//end getCategoryRecords

    public function getCategoryById($category_id = 0)
    {
        if ($category_id > 0)
        {
            $query = $this->db->query("select * from tbl_product_categories where category_id=" . $category_id);
            $row   = array();
            if ($query->num_rows() > 0)
            {
                $row = $query->row();
            }
            return $row;
        }
    }

//end getCategoryById

    public function getCategoryByTitle($category_title = 0)
    {

        $query = $this->db->query("select * from tbl_product_categories where category_title= '$category_title' ");
        $row   = array();
        if ($query->num_rows() > 0)
        {
            $row = $query->row();
        }
        return $row;
    }

//end getCategoryById

    public function getAllActiveCategory()
    {
        $this->db->select('tbl_product.category_id');
        $this->db->from('tbl_variations');
        $this->db->join('tbl_product', 'tbl_variations.product_id = tbl_product.product_id');
        $this->db->order_by('category_id', 'ASC');
        $this->db->where('available', 'Y');

        $category = $this->db->get()->result();

        foreach ($category as $val)
        {
            $category_id[] = $val->category_id;
        }
        if ($category_id)
        {
            $this->db->select('category_id,category_name');
            $this->db->where(array('category_status' => 'A'));
            $this->db->where_in('category_id', $category_id);
            $this->db->order_by('category_id', 'ASC');
            $rows             = $this->db->get('tbl_product_categories')->result();
            if ($rows)
            {
                foreach ($rows as $val)
                {
                    $row[$val->category_id] = $val->category_name;
                }
                return $row;
            }
        }
        return false;
    }

    public function getCategoryRecordsByStatus($prod_category_id = 0)
    {        //to get categories in combo for product category selection
        $this->db->where('cat_status', 'Active');
        $this->db->order_by('`id` ASC');
        $query = $this->db->get('mast_prod_categories');
        $row   = array();
        $frmcombo = "";
        if ($query->num_rows() > 0)
        {
            foreach ($query->result() as $combo)
            {
                $frmcombo .="<option value=" . $combo->id . " ";
                if ($prod_category_id > 0)
                {
                    if ($prod_category_id == $combo->id)
                    {
                        $frmcombo .="selected='true'";
                    }
                }
                $frmcombo .=">";
                $frmcombo .=$combo->categoryname . "</option>";
            }
        }
        return $frmcombo;
    }

//end getCategoryRecordsByStatus

    function getFreeProduct()
    {
        $this->db->select();
        $dateRange = "expirydate > CURDATE()";
        $this->db->where($dateRange, NULL, FALSE);
        $this->db->limit(1);
        $res       = $this->db->get_where('tbl_coupon', array('coupontype' => 'freeproduct', 'status'     => 'active'))->row();
        if (!empty($res))
        {
            return $res;
        }//$echo $res->
    }

// end of getFreeProduct

    function getPublicHolidayFee()
    {
        $res = $this->db->select('value')->limit(1)->get_where('sitesetting', array('id' => 12))->row();

        $holiday_dates = explode(',', $res->value);
        $current_date  = date('d/m/Y');

        if (in_array($current_date, $holiday_dates))
        {
            $result = $this->db->select('public_holiday_amt')->limit(1)->get_where('tbl_order_surcharge', array('id' => 1))->row();
            return ($result->public_holiday_amt == 0) ? false : $result->public_holiday_amt;
        }
        else
        {
            return false;
        }
    }

//end getPublicHolidayFee

    function getFirstOrderDiscount()
    {
        $this->db->select();
        $dateRange = "expirydate > CURDATE()";
        $this->db->where($dateRange, NULL, FALSE);
        $this->db->limit(1);
        $res       = $this->db->get_where('tbl_coupon', array('coupontype' => 'firstorder', 'status'     => 'active'))->row();
        if (!empty($res))
        {
            return $res->discountper;
        }
        
        return false;
    }

    function getAllOrderDiscount()
    {
        $this->db->select();
        $dateRange = "expirydate > CURDATE()";
        $this->db->where($dateRange, NULL, FALSE);
        $this->db->limit(1);
        $res       = $this->db->get_where('tbl_coupon', array('coupontype' => 'allorders', 'status'     => 'active'))->row();
        if (!empty($res))
        {
            return $res->discountper;
        }
        
        return false;
    }

    function getMinimumOrderFee()
    {

        $result = $this->db->select()->limit(1)->get_where('tbl_order_surcharge', array('id' => 1))->row();
        if (!empty($result))
        {
            return $result;
        }
        else
        {
            return false;
        }
    }

//end getMinimumOrderFee 

    function getPaymentMethod()
    {
        $result = $this->db->select('*')->order_by('id', 'asc')->get_where('tbl_payment', array('pay_status' => 'Active'))->result();

        if (!empty($result))
        {
            return $result;
        }
        else
        {
            return false;
        }
    }

//end getPaymentMethod

    function getmetatags()
    {
        $result = $this->db->select()->get_where('tbl_meta_tags', array('metaid' => '2'))->row();

        if (!empty($result))
        {
            return $result;
        }
        else
        {
            return false;
        }
    }

//end getPaymentMethod

    function getVoucherDiscount()
    {
        $this->db->select();
        $dateRange = "expirydate >= CURDATE()";
        $this->db->where($dateRange, NULL, FALSE);
        $this->db->limit(1);
        $res       = $this->db->get_where('tbl_coupon', array('coupontype' => 'discount', 'status'     => 'active'))->row();
        if (!empty($res))
        {
            return $res->discountper;
        }//$echo $res->
        return false;
    }

    function getSiteDetails()
    {
        $this->db->order_by('id', 'asc');
        $query = $this->db->get('sitesetting')->result();
        foreach ($query as $ssettings)
        {
            $arrsetting[$ssettings->type] = $ssettings->value;
        }
        return $arrsetting;
    }

//getSiteDetails

    public function getSocialLoker(){
        $dateNow = date('Y-m-d');
        $is_social_loker = $this->db->select('couponcode')->where('expirydate >', $dateNow)->
            where('social_loker', 1)->
            where('status', 'active')->
            get('tbl_coupon');
        if($is_social_loker->num_rows() > 0){
            $social_loker_id = $is_social_loker->row();
            return $social_loker_id->couponcode;
        } else {
            return false;
        }
    }


    public function textSocialLocker(){
        $text = $this->db->select('value')->where('type', 'text_social_locker')->get('sitesetting')->row();

        return $text->value;

    }
}

//class