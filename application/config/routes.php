<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/*
| -------------------------------------------------------------------------
| URI ROUTING
| -------------------------------------------------------------------------
| This file lets you re-map URI requests to specific controller functions.
|
| Typically there is a one-to-one relationship between a URL string
| and its corresponding controller class/method. The segments in a
| URL normally follow this pattern:
|
|	example.com/class/method/id/
|
| In some instances, however, you may want to remap this relationship
| so that a different class/function is called than the one
| corresponding to the URL.
|
| Please see the user guide for complete details:
|
|	http://codeigniter.com/user_guide/general/routing.html
|
| -------------------------------------------------------------------------
| RESERVED ROUTES
| -------------------------------------------------------------------------
|
| There area two reserved routes:
|
|	$route['default_controller'] = 'welcome';
|
| This route indicates which controller class should be loaded if the
| URI contains no data. In the above example, the "welcome" class
| would be loaded.
|
|	$route['404_override'] = 'errors/page_missing';
|
| This route will tell the Router what URI segments to use if those provided
| in the URL cannot be matched to a valid route.
|
*/

/**
 * Set default controller
 */
$route['default_controller']            = 'page';

/**
 * Set routes for page controller
 */
$route['menu']                          = 'page/menu';
$route['my-account']                    = 'page/myaccount';
$route['order-again/(:num)']            = 'page/orderAgain/$1';
$route['404-override']                  = 'page/staticpage/404';
$route['(:num)']                        = 'page/index/$1';

/**
 * Set routes for checkout controller
 */
$route['checkout']                      = 'checkout/index';
$route['payment']                       = 'checkout/payment';
$route['payment/socialLogin']           = 'checkout/payment/socialLogin';
$route['checkout/getCoupons']           = 'checkout/getCoupons';
$route['checkout/verifyMobile']         = 'checkout/verifyMobile';
$route['checkout/verifyCode']           = 'checkout/verifyCode';

/**
 * Set routes for paypal controller
 */
$route['paypal']                        = 'paypal/index';
$route['paypal/(:any)']                 = 'paypal/$1/';
$route['ipn']                           = 'paypal/ipn';
$route['paypal/cancel']                 = 'paypal/cancel';
$route['paypal/succes']                 = 'paypal/succes';


$route['feedback']                 = 'feedback/index';


/**
 * Set routes for payment controller
 */
$route['credit-card']                   = 'payment/credit_card';
$route['payment/Do-direct-payment']     = 'payment/Do_direct_payment';

/**
 * Set routes for order controller
 */
$route['orders']                        = 'order/yourOrders';
$route['order/save-order/(:any)']       = 'order/save_order/$1';
$route['order/getAjaxOrders']           = 'order/getAjaxOrders';
$route['send/order/api']           =      'order/getAjaxOrders';


/**
 * Set routes for security controller
 */
$route['login-page']                    = 'security/login_page';
$route['change-password/(:any)']        = 'security/changePassword/$1';
$route['security/save']                 = 'security/save';
$route['security-edit']                 = 'security/edit';
$route['security/login']                = 'security/login';
$route['security/checkUniqueEmail']     = 'security/checkUniqueEmail';
$route['security/checkUniqueMobile']    = 'security/checkUniqueMobile';
$route['logout']                        = 'security/logout';
$route['logout/(:any)']                 = 'security/logout/$1';
$route['security/checkValidEmail']      = 'security/checkValidEmail';
$route['security/savePassword']         = 'security/savePassword';
$route['reset']                         = 'security/reset';
$route['security/googleplus-login']     = 'security/googleplus_login';
$route['security/facebook-login']       = 'security/facebook_login';


/**
 * Set routes for social controller
 */
$route['social']                        = 'social/index';

/**
 * Set routes for product controller
 */
$route['get/ingredients/(:num)']        = 'product/ingredients/$1';

$route['remove/(:any)']                 = 'product/removeItemFromCart/$1';

$route['product/(:num)']                = 'product/view/$1';
$route['product/(:num)/(:any)']         = 'product/view/$1/$2';

$route['(:any)']                        = 'product/view/$1';
$route['(:any)/(:any)']                 = 'product/view/$1/$2';

/* End of file routes.php */
/* Location: ./application/config/routes.php */