<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
/**
 * Created by PhpStorm.
 * User: anhvnit
 * Date: 6/10/17
 * Time: 22:11
 */
class Openpos_Front{
    private $settings_api;
    private $_core;
    private $_session;
    private $_enable_hpos;
    public function __construct($OPENPOS_SETTING,$OPENPOS_CORE)
    {
        #header('Access-Control-Allow-Origin: *');
      
        
        $this->settings_api = $OPENPOS_SETTING;
        $this->_core = $OPENPOS_CORE;
        
        add_action('plugins_loaded', array($this,'plugins_loaded'));
        add_action( 'wp_ajax_nopriv_openpos', array($this,'getApi') );
        add_action( 'wp_ajax_openpos', array($this,'getApi') );

        add_action( 'wp_ajax_nopriv_op_customer_table_order', array($this,'customer_table_order') );
        add_action( 'wp_ajax_op_customer_table_order', array($this,'customer_table_order') );

        // by pass 
        add_filter('woocommerce_prevent_admin_access',function($result){
            if(isset($_REQUEST['action']) && $_REQUEST['action'] == 'openpos')
            {
                return false;
            }
           return $result; 
        });
    }
    function plugins_loaded()
    {
        $this->_enable_hpos = $this->_core->enable_hpos();
    }
    public function setSession($op_session)
    {
        $this->_session = $op_session;
    }
    

    public function initScripts(){
        global $op_in_pos_screen;
        global $op_in_bill_screen;
        global $op_in_kitchen_screen;
        global $op_in_customer_screen;
        if($op_in_pos_screen)
        {
            add_action( 'init', array($this,'registerScripts') ,10 );
        }
        if($op_in_bill_screen)
        {
            add_action( 'init', array($this,'registerBillScripts') ,10 );
        }
        if($op_in_kitchen_screen)
        {
            add_action( 'init', array($this,'registerKitchenScripts') ,10 );
        }
        if($op_in_customer_screen)
        {
            add_action( 'init', array($this,'registerCustomerScripts') ,10 );
        }
        
        add_filter('script_loader_tag',  array($this,'add_async_attribute'), 10, 2);
        add_filter('openpos_pos_header_js',function($handes){
            $handes[] = 'openpos.pos.head';
            return $handes;
        },10,1);
    }

    public function registerScripts(){
        global $op_in_pos_screen;
        if($op_in_pos_screen)
        {
            $info = $this->_core->getPluginInfo();
            $custom_css = $this->settings_api->get_option('pos_custom_css','openpos_pos');
            $stock_manager = $this->settings_api->get_option('pos_stock_manage','openpos_general');
            $logo = $this->settings_api->get_option('openpos_logo','openpos_pos');
    
            $custom_css = html_entity_decode($custom_css);
            
            $payment_methods = $this->settings_api->get_option('payment_methods','openpos_payment');
    
    
            
            wp_enqueue_style('openpos.material.icon.offline', OPENPOS_URL.'/assets/css/material-icons.css','',$info['Version']);
            
            wp_enqueue_style('openpos.material.icon','https://fonts.googleapis.com/icon?family=Material+Icons',array('openpos.material.icon.offline'),$info['Version']);
    
    
            wp_enqueue_style( 'openpos.styles.font', OPENPOS_URL.'/pos/font.css','',$info['Version']);
            wp_enqueue_style( 'openpos.front', OPENPOS_URL.'/pos/pos.css',array('openpos.styles.font'),$info['Version']);
            wp_enqueue_style( 'openpos.styles', OPENPOS_URL.'/pos/styles.css','',$info['Version']);
            if($logo)
            {
                $custom_css .= ".top-pos-logo-desktop{overflow: hidden;}";
                $custom_css .= ".top-pos-logo-desktop{ text-indent: -999px; background: url(".esc_url($logo).") no-repeat; background-size: cover }";
                $custom_css .= ".top-pos-logo-mobile a{ text-indent: -999px; background: url(".esc_url($logo).") no-repeat; display: inline-block; width: 30px; height: 30px; background-size: cover; }";
            }
            if($stock_manager == 'no')
            {
                // $custom_css .= ".suggest-product-image .product-qty{display: none;}";
                // $custom_css .= ".product-item-detail .product-qty{display: none;}";
               
            }
            $custom_css_after = apply_filters('pos_front_custom_css',$custom_css );
            
    
            if($custom_css_after)
            {
                wp_add_inline_style( 'openpos.front', $custom_css_after);
            }
            $depend_script = array();
            if(isset($payment_methods['stripe']))
            {
                $stripe_public_key = $this->settings_api->get_option('stripe_public_key','openpos_payment');
                if($stripe_public_key)
                {
                    wp_enqueue_script('openpos.pos.stripe', 'https://js.stripe.com/v3/','',$info['Version']);
                    wp_add_inline_script('openpos.pos.stripe',"
                      var stripe = Stripe('".esc_textarea($stripe_public_key)."'); 
                      
                    ");
                    $depend_script[] = 'openpos.pos.stripe';
                }
            }
            $ga_account = apply_filters('op_google_ga_acc','G-L61T3JE413');// change this to your system account
            if($ga_account)
            {
                wp_register_script('openpos.pos.ga', 'https://www.googletagmanager.com/gtag/js?id='.$ga_account,$depend_script);
                wp_add_inline_script('openpos.pos.ga',"
                 window.dataLayer = window.dataLayer || [];
                  function gtag(){dataLayer.push(arguments);}
                  gtag('js', new Date());
                  gtag('config', '".$ga_account."',{
                      'linker': {
                        'accept_incoming': true
                      },
                      'domain': '".esc_url(OPENPOS_URL)."',
                      'page_referrer': '".esc_url(OPENPOS_URL)."',
                      'content_group': '".esc_url(OPENPOS_URL)."'
                  });
                ");
            }
            wp_enqueue_script('openpos.pos.main',  OPENPOS_URL.'/assets/js/front/openpos.js','',$info['Version']);
            wp_enqueue_script('openpos.pos.head',  OPENPOS_URL.'/assets/js/front/openpos_header.js','',$info['Version']);
            $pos_pwa_enable = $this->settings_api->get_option('pos_pwa_enable','openpos_general');
           // pos_pwa_enable
           if($pos_pwa_enable != 'no'){
                wp_add_inline_script('openpos.pos.main',"
                    if ('serviceWorker' in navigator) {
                        console.log('register service worker');
                        navigator.serviceWorker.register('./service-worker.js?v=".esc_attr($info['Version'])."').then(function(registration) {
                            console.log('ServiceWorker registration successful with scope:',  registration.scope);
                        }).catch(function(error) {
                            console.log('ServiceWorker registration failed:', error);
                        });
                    }
                ");
           }
           $openpos_login_mode = $this->settings_api->get_option('openpos_login_mode','openpos_pos');
           if($openpos_login_mode == 'pin')
           {
               
                wp_add_inline_script('openpos.pos.head',"
                    var global = global || window;
                    global.pos_setting = {login_mode : 'pin'};
                    console.log(global);
                ",'before');
           }
           
                
        }
        
        
    }

    function add_async_attribute($tag, $handle) {
        // add script handles to the array below
        $scripts_to_async = array('openpos.pos.ga');

        foreach($scripts_to_async as $async_script) {
            if ($async_script === $handle) {
                return str_replace(' src', ' async="async" src', $tag);
            }
        }
        return $tag;
    }

    public function registerBillScripts(){
        $info = $this->_core->getPluginInfo();

        wp_enqueue_style('openpos.bill.bootstrap', OPENPOS_URL.'/assets/css/bootstrap.css');
        wp_enqueue_style('openpos.bill.style',OPENPOS_URL.'/assets/css/bill.css',array('openpos.bill.bootstrap'),$info['Version']);


        wp_enqueue_script('openpos.bill.nosleep', OPENPOS_URL.'/assets/js/NoSleep.min.js','',$info['Version']);
        wp_enqueue_script('openpos.bill.screenfull', OPENPOS_URL.'/assets/js/screenfull.min.js','',$info['Version']);
        wp_enqueue_script('openpos.bill.bootstrap', OPENPOS_URL.'/assets/js/bootstrap.js','',$info['Version']);
        wp_enqueue_script('openpos.bill.accounting', OPENPOS_URL.'/assets/js/accounting.min.js','',$info['Version']);
        wp_enqueue_script('openpos.bill.ejs', OPENPOS_URL.'/assets/js/ejs.js','',$info['Version']);
        wp_register_script('openpos.bill.script',OPENPOS_URL.'/assets/js/bill.js',array('jquery','openpos.bill.ejs','openpos.bill.accounting','openpos.bill.nosleep','openpos.bill.screenfull'),$info['Version']);

    }

    public function registerKitchenScripts(){
        $info = $this->_core->getPluginInfo();

        wp_enqueue_style('openpos.kitchen.bootstrap', OPENPOS_URL.'/assets/css/bootstrap.css');
        wp_enqueue_style('openpos.kitchen.style',OPENPOS_URL.'/assets/css/kitchen.css',array('openpos.kitchen.bootstrap'),$info['Version']);


        wp_enqueue_script('openpos.kitchen.nosleep', OPENPOS_URL.'/assets/js/NoSleep.min.js','',$info['Version']);
        wp_enqueue_script('openpos.kitchen.screenfull', OPENPOS_URL.'/assets/js/screenfull.min.js','',$info['Version']);
        wp_enqueue_script('openpos.kitchen.bootstrap', OPENPOS_URL.'/assets/js/bootstrap.js','',$info['Version']);
        wp_enqueue_script('openpos.kitchen.ejs', OPENPOS_URL.'/assets/js/ejs.js','',$info['Version']);
        wp_enqueue_script('openpos.kitchen.timeago', OPENPOS_URL.'/assets/js/jquery.timeago.js','',$info['Version']);
        wp_register_script('openpos.kitchen.script',OPENPOS_URL.'/assets/js/kitchen.js',array('jquery','openpos.kitchen.ejs','openpos.kitchen.nosleep','openpos.kitchen.screenfull','openpos.kitchen.timeago','openpos.kitchen.bootstrap'),$info['Version']);
    }
    public function registerCustomerScripts(){
        $info = $this->_core->getPluginInfo();

        wp_enqueue_style('openpos.customer.bootstrap', OPENPOS_URL.'/assets/bootstrap-v5.0.1/css/bootstrap.min.css');
        wp_enqueue_style('openpos.customer.style',OPENPOS_URL.'/assets/css/customer.css',array('openpos.customer.bootstrap'),$info['Version']);

        wp_enqueue_script('openpos.customer.ejs', OPENPOS_URL.'/assets/js/ejs.js','',$info['Version']);

        wp_enqueue_script('openpos.customer.bootstrap', OPENPOS_URL.'/assets/bootstrap-v5.0.1/js/bootstrap.min.js','',$info['Version']);
        wp_enqueue_script('openpos.customer.accounting', OPENPOS_URL.'/assets/js/accounting.min.js','',$info['Version']);
        wp_register_script('openpos.customer.script',OPENPOS_URL.'/assets/js/customer.js',array('jquery','openpos.customer.ejs','openpos.customer.accounting'),$info['Version']);
    }

    public function getApi(){
        //secure implement
        global $op_session_data;
        global $op_woo_order;
        ob_start();

        $result = array(
            'status' => 0,
            'message' => '',
            'data' => array(
                'framework'=>'woocommerce',
                'woo_version'=> $this->_woo_version_number(),
                'version'=> $this->_op_version_number(),
                'params' => $_REQUEST
            )
        );
        $api_action = isset($_REQUEST['pos_action']) ? esc_attr($_REQUEST['pos_action']) : '';
        $validate = false;
        $allow_hpos = $this->_core->enable_hpos();
        $warehouse_id = 0;

        if($api_action != 'app_view')
        {
            header('Content-Type: application/json');
        }else{
            header('Content-Type: text/html');
        }

        if($api_action == 'login' || $api_action == 'logout')
        {
            $validate = true;
        }else{
            $session_id = trim($_REQUEST['session']);
            if($session_id )
            {
                if($this->_session->validate($session_id))
                {
                    $session_data = $this->_getSessionData();
                    $op_session_data = $session_data;
                    $warehouse_id = isset($session_data['login_warehouse_id']) ? $session_data['login_warehouse_id'] : 0;

                    $validate = true;
                }else{
                    $validate = false;
                    $result['status'] = -1;
                }
            }
        }
        if($validate )
        {
            do_action('op_before_api_return',$api_action);
            
            switch ($api_action)
            {
                case 'login':
                    $user_name =  isset($_REQUEST['username']) ? sanitize_text_field($_REQUEST['username']) : '';
                    $password =  isset($_REQUEST['password']) ? stripslashes($_REQUEST['password']) : '';
                    $login_mode =  isset($_REQUEST['login_mode']) ? stripslashes($_REQUEST['login_mode']) : 'default';
                    if($login = $this->login($user_name,$password,$login_mode))
                    {
                        $result = $login;
                    }
                    break;
                case 'logout':
                    if($logout = $this->logout())
                    {
                        $result = $logout;
                    }
                    break;
                case 'login_cashdrawer':
                    $result = $this->login_cashdrawer();
                    break;
                case 'update_qty_products':
                    $result = $this->getUpdateProducts();
                    break;
                case 'products':
                    $result = $this->getProducts();
                    break;
                case 'stock_over_view':
                    $result = $this->getStockOverView();
                    break;
                case 'orders':
                    //get online order --pending
                    break;
                case 'new-order':
                    $result = $this->add_order(false);
                    break;
                case 'pending-order':
                    $result = $this->pending_payment_order();
                    break;
                case 'payment-cc-order':
                    $result = $this->payment_cc_order();
                    break;
                case 'payment-order':
                    $result = $this->payment_order();
                    break;
                case 'get-order-note':
                    $result = $this->get_order_note();
                    break;
                case 'save-order-note':
                    $result = $this->save_order_note();
                    break;
                case 'update-order':
                    $result = $this->update_order();
                    break;
                case 'customers':
                    $result = $this->search_customer();
                    break;
                case 'get-customer-field':
                    $result = $this->get_customer_field();
                    break;
                case 'search-customer-by':
                    $result = $this->search_customer_by();
                    break;
                case 'get-customer-orders':
                    $result = $this->get_customer_orders();
                    break;
                case 'update-customer':
                    $result = $this->update_customer();
                    break;
                case 'new-customer':
                    $result = $this->add_customer();
                    break;
                case 'new-transaction':
                    $result = $this->add_transaction();
                    break;
                case 'transactions':
                    //pending - get online transactions
                    break;
                case 'check-coupon':
                    $result = $this->check_coupon();
                    break;
                case 'refund-order':
                    $result = $this->refund_order();
                    break;
                case 'close-order':
                    $result = $this->close_order();
                    break;
                case 'check-order':
                    $result = $this->check_order();
                    break;
                case 'latest-order':
                    $result = $this->latest_order();
                    break;
                case 'search-order':
                    $result = $this->search_order();
                    break;
                case 'pickup-order':
                    $result = $this->pickup_order();
                    break;
                case 'get-carts':
                    $result = $this->get_draft_orders();
                    break;
                case 'load-cart':
                    $result = $this->load_draft_order();
                    break;
                case 'draft-order':
                    $result = $this->draft_order();
                    break;
                case 'delete-cart':
                    $result = $this->delete_cart();
                    break;
                case 'logon':
                    $result = $this->logon();
                    break;
                case 'get-shipping-method':
                    $result = $this->get_shipping_method();
                    break;
                case 'get-cart-discount':
                    $result = $this->get_cart_discount();
                    break;
                case 'get-shipping-cost':
                    $result = $this->get_shipping_cost();
                    break;
                case 'get_order_number':
                    if($allow_hpos)
                    {
                        $result = $op_woo_order->hpos_get_order_number();
                    }else{
                        $result = $this->get_order_number();
                    }
                    break;
                case 'get_cart_number':
                    $result = $this->get_cart_number();
                    break;
                case 'upload-desk':
                    $result = $this->upload_desk();
                    break;
                case 'pull-desk':
                    $result = $this->pull_desk();
                    break;
                case 'pull-desks':
                    $result = $this->pull_desks();
                    break;
                case 'remove-takeaway-desk':
                    $result = $this->remove_desk();
                    break;
                case 'get-takeaway-list':
                    $result = $this->get_takeaway_list();
                    break;
                case 'send_message': //pending..
                    $result =$this->send_message();
                    break;
                case 'pull_messages': //pending..
                    $result =$this->pull_messages();
                    break;
                case 'delete_messages': //pending..
                    $result =$this->delete_messages();
                    break;
                case 'check':
                    $result = $this->update_state();
                    break;
                case 'add_custom_product':
                    $result = $this->add_custom_product();
                    break;
                case 'get_app_list':
                    $result = $this->get_app_list();
                    break;
                case 'app_view':
                    $this->app_view();
                    exit;
                    break;
                case 'scan_product':
                    $result = $this->scan_product();
                    break;
                case 'search_product':
                    $result = $this->search_product();
                    break;
                case 'upload_file':
                    $result = $this->upload_file();
                    break;
                case 'send-receipt':
                    $result = $this->send_receipt();
                    break;
                case 'get-order-transactions':
                    $result = $this->get_order_transactions();
                    break;
                case 'get-order':
                    $result = $this->get_order();
                    break;
                case 'add-order-transaction':
                    $result = $this->add_order_transaction();
                    break;
                case 'session-login':
                    $result = $this->login_with_session();
                    break;

            }
        }
        
        $result['database_version'] = get_option('_openpos_product_version_'.$warehouse_id,0);
        if($this->settings_api->get_option('pos_auto_sync','openpos_pos') == 'no')
        {
            $result['database_version'] = -1;
        }
        

        do_action('op_after_api_return',$api_action,$result);
        

        $final_result = apply_filters('op_api_result',$result,$api_action);
        
        $api_result = json_encode($final_result);
        if(!$api_result)
        {
            $erro_num = json_last_error();
            switch($erro_num)
            {
                case 0:
                    echo 'JSON_ERROR_NONE';
                    break;
                case 1:
                    echo 'JSON_ERROR_DEPTH';
                    break;
                case 2:
                    echo 'JSON_ERROR_STATE_MISMATCH';
                    break;
                case 3:
                    echo 'JSON_ERROR_CTRL_CHAR';
                    break;
                case 4:
                    echo 'JSON_ERROR_SYNTAX';
                    break;
                case 5:
                    echo 'JSON_ERROR_UTF8';
                    break;
            }
        }
        echo $api_result;
        exit;
    }

    private function _getSessionData($sid = ''){
        if($sid)
        {
            $session_id = $sid;
        }else{
            $session_id = isset($_REQUEST['session']) ? trim($_REQUEST['session']) : '';
        }
        
        if($session_id && $this->_session->validate($session_id))
        {
            return $this->_session->data($session_id);
        }
        return array();
    }
    public function getUpdateProducts()
    {
        global $op_woo;
        $session_data = $this->_getSessionData();
        $login_warehouse_id = isset($session_data['login_warehouse_id']) ? $session_data['login_warehouse_id'] : 0;
        $local_db_version = isset($_REQUEST['local_db_version']) ? $_REQUEST['local_db_version'] : 0;
        $database_version = get_option('_openpos_product_version_'.$login_warehouse_id,0);
        if($local_db_version > 0)
        {
            $product_changed_data = $op_woo->getProductChanged($local_db_version,$login_warehouse_id);

            $product_ids = array();
            foreach($product_changed_data['data'] as $product_id => $qty)
            {
                $product_ids[] = $product_id;
            }

            $data = array('total_page' => 0,'page' => 0,'version' => $product_changed_data['current_version']);

            $data['product'] = array();
            $data['delete_product'] = array();
            $session_data = $this->_getSessionData();
            $login_cashdrawer_id = isset($session_data['login_cashdrawer_id']) ?  $session_data['login_cashdrawer_id'] : 0;
            
            $show_out_of_stock_setting = $this->settings_api->get_option('pos_display_outofstock','openpos_pos');
            $show_out_of_stock = true;
            if($show_out_of_stock_setting != 'yes')
            {
                //$show_out_of_stock = false;
            }

            $allow_status = $this->_core->getDefaultProductPostStatus();
            foreach($product_ids as $product_id)
            {
                $_product = get_post($product_id);
                $status = get_post_status( $product_id );
                if(!in_array($status,$allow_status))
                {
                    $data['delete_product'][] = $product_id;
                    continue;
                }
                $warehouse_id = 0;
                if($login_cashdrawer_id > 0)
                {
                    $warehouse_id = $session_data['login_warehouse_id'];

                }

                $product_data = $op_woo->get_product_formatted_data($_product,$warehouse_id);

                if(!$product_data)
                {
                    $data['delete_product'][] = $product_id;
                    continue;
                }
                if(empty($product_data))
                {
                    $data['delete_product'][] = $product_id;
                    continue;
                }
                if(!$show_out_of_stock)
                {
                    if( $product_data['manage_stock'] &&  is_numeric($product_data['qty']) && $product_data['qty'] <= 0)
                    {
                        $data['delete_product'][] = $product_id;
                        continue;
                    }
                }

                $data['product'][] = $product_data;

            }
            $version = $product_changed_data['current_version'];
           
            if(empty($data['product']) &&  $version == 0)
            {
                $version = $database_version;
                
            }

            return array(
                'product' => $data['product'],
                'version' => $version
            );
        }else{
            return array(
                'product' => array(),
                'version' => $database_version
            );
        }
    }
    public function getProductPerPage(){
        return apply_filters('op_load_product_per_page',50);
    }
    public function getTotalPageProduct(){
        $rowCount = $this->getProductPerPage();
        $args = array(
            'posts_per_page'   => $rowCount,
            'offset'           => 0,
            'category'         => '',
            'category_name'    => '',
            'post_type'        => $this->_core->getPosPostType(),
            'post_status'      => $this->_core->getDefaultProductPostStatus(),
            'suppress_filters' => false
        );
        $args = apply_filters('op_load_product_args',$args);
        $products = $this->_core->getProducts($args,true);
        return ceil($products['total'] / $rowCount) + 1;
    }
    public function getTotalProduct(){
        $args = array(
            'posts_per_page'   => 1,
            'offset'           => 0,
            'category'         => '',
            'category_name'    => '',
            'post_type'        => $this->_core->getPosPostType(),
            'post_status'      => $this->_core->getDefaultProductPostStatus(),
            'suppress_filters' => false
        );
        $args = apply_filters('op_load_product_args',$args);
        $products = $this->_core->getProducts($args,true);
        return $products['total'];
    }
    public function getProducts($show_out_of_stock = false)
    {
        
        global $op_warehouse;
        global $op_woo;
        global $session_data;
        $session_data = $this->_getSessionData();
        $page = isset($_REQUEST['page']) ? (int)$_REQUEST['page'] : 1;
        $rowCount = $this->getProductPerPage();
        $current = $page;
        $offet = ($current -1) * $rowCount;
        $sortBy = 'title';
        $order = 'ASC';

        $args = array(
            'posts_per_page'   => $rowCount,
            'offset'           => $offet,
            'current_page'           => $current,
            'category'         => '',
            'category_name'    => '',
            'orderby'          => $sortBy,
            'order'            => $order,
            'post_type'        => $this->_core->getPosPostType(),
            'post_status'      => $this->_core->getDefaultProductPostStatus(),
            'suppress_filters' => false
        );
        $args = apply_filters('op_load_product_args',$args);
        $products = $this->_core->getProducts($args,true);
        if(isset($session_data['total_product_page']))
        {
            $total_page = $session_data['total_product_page'];
        }else{

            if(isset($products['total_page']))
            {
                $total_page = $products['total_page'];
            }else{
                $total_page = $this->getTotalPageProduct();
            }
        }
        
        
        $data = array('total_page' => $total_page, 'page' => $current);

        $data['product'] = array();
        
        $login_cashdrawer_id = isset($session_data['login_cashdrawer_id']) ?  $session_data['login_cashdrawer_id'] : 0;
        $login_warehouse_id = isset($session_data['login_warehouse_id']) ? $session_data['login_warehouse_id'] : 0;
        $show_out_of_stock_setting = $this->settings_api->get_option('pos_display_outofstock','openpos_pos');
        if($show_out_of_stock_setting == 'yes')
        {
            $show_out_of_stock = true;
        }
        $warehouse_id = $login_warehouse_id;
       
        
        foreach($products['posts'] as $_product)
        {

            if(is_a($_product, 'WP_Post'))
            {
                $product_id = $_product->ID;
            }else{
                $product_id = $_product->get_id();
            }
            
            $product = wc_get_product($product_id );
            
            $product_data = $op_woo->get_product_formatted_data($_product,$warehouse_id);
            
            $allow = $this->_allowProduct($product_data,$warehouse_id);
            
            if(!$allow)
            {
                continue;
            }
           
            if(!$show_out_of_stock)
            {
                if( $product_data['manage_stock'] &&  is_numeric($product_data['qty']) && $product_data['qty'] <= 0)
                {
                    $product_data['display'] = false;
                    if(!empty($data['product']))
                    {
                        continue;
                    }

                }
                if($warehouse_id == 0)
                {
                    if($product->get_type() == 'variable' && $product_data['stock_status'] == 'outofstock')
                    {
                        continue;
                    }
                    if( !$product_data['manage_stock'] &&  $product_data['stock_status'] == 'outofstock' )
                    {
                        continue;
                    }
                }
                
            }
           
            $data['product'][] = $product_data;
            
        }
        $result = array(
            'product' => $data['product'],
            'total_page' => $data['total_page'],
            'current_page' => $data['page']
        );
        
        return $result;

    }
    public function getStockOverView(){
        global $op_warehouse;
        global $op_woo;
        $params = $_POST;
        $result = array(
            'status' => 0,
            'message' => 'Unknown',
            'data' => array()
        );
        try{
            $barcode =  isset($params['barcode']) ? $params['barcode'] : 0;
            if(!$barcode)
            {

                throw new Exception(__('Please enter barcode to search','openpos'));
            }
            $product_id = $this->_core->getProductIdByBarcode($barcode);
            $warehouses = $op_warehouse->warehouses();
            if($product_id)
            {

                $total_with_online = 0;
                $total_no_online = 0;
                $product = wc_get_product($product_id);
                $product_data = array(
                    'name' => $product->get_name()
                );
                $stock_data = array();
                foreach($warehouses as $w)
                {
                    if($w['status'] == 'draft')
                    {
                        continue;
                    }
                    $qty = $op_warehouse->get_qty($w['id'],$product_id);
                    $total_with_online += $qty;
                    if($w['id'])
                    {
                        $total_no_online += $qty;
                    }
                    $stock_data[]  = array( 'warehouse' => $w['name'] , 'qty' => $qty );
                }
                $product_data['stock_overview'] = $stock_data;
                $result['data'][] = $product_data;

            }else{
                $posts = $op_woo->searchProductsByTerm($barcode);
                foreach($posts as $post)
                {
                    $product_id = $post->ID;
                    $total_with_online = 0;
                    $total_no_online = 0;
                    $product = wc_get_product($product_id);
                    if(!$product)
                    {
                        continue;
                    }
                    if($product->get_type() == 'variable')
                    {
                        continue;
                    }
                    $product_data = array(
                        'name' => $product->get_name()
                    );
                    $stock_data = array();
                    foreach($warehouses as $w)
                    {
                        if($w['status'] == 'draft')
                        {
                            continue;
                        }
                        $qty = $op_warehouse->get_qty($w['id'],$product_id);
                        $total_with_online += $qty;
                        if($w['id'])
                        {
                            $total_no_online += $qty;
                        }
                        $stock_data[]  = array( 'warehouse' => $w['name'] , 'qty' => $qty );
                    }
                    $product_data['stock_overview'] = $stock_data;
                    $result['data'][] = $product_data;
                }
            }
            if(empty($result['data']))
            {
                $result['status'] = 0;
                $result['message'] = __('No product found. Please check your barcode !','openpos');
            }else{
                $result['status'] = 1;
            }

        }catch (Exception $e)
        {
            $result['status'] = 0;
            $result['message'] = $e->getMessage();
        }
        return $result;
    }
    public function getSetting($cashdrawer_id = 0){
        global $op_woo;
        $setting_sections = $this->settings_api->get_sections();
        $setting = array();
        $ignore = array(
            'stripe_public_key',
            'stripe_secret_key'
        );
    
        foreach($setting_sections as $section)
        {
            $options = $this->settings_api->get_options($section['id']);
            foreach($options as $field => $value)
            {
                $option = $field;
                if(in_array($option,$ignore))
                {
                    continue;
                }
                switch ($option)
                {
                    case 'shipping_methods':
                        $setting_methods = $value;
                        $shipping_methods =   $op_woo->get_setting_shipping_methods();// WC()->shipping()->get_shipping_methods();
                        $shipping_result = array();
                        if(!is_array($setting_methods))
                        {
                            $setting_methods = array();
                        }
                        foreach ($setting_methods as $shipping_method_code)
                        {
                            foreach($shipping_methods as $shipping_method)
                            {
                                $instance_id = $shipping_method->instance_id ? $shipping_method->instance_id : 0;

                                $code = $shipping_method->id.':'.$instance_id;
                               
                                if($code == $shipping_method_code)
                                {
                                    $title = $shipping_method->title;
                                    if(!$title)
                                    {
                                        $title = $shipping_method->method_title;
                                    }
                                    if(!$title)
                                    {
                                        $title = $code;
                                    }
                                    $taxes = array();
                                    $cost = isset($shipping_method->cost) ? $shipping_method->cost : 0;
                                    
                                    $tmp = array(
                                        'code' => $code,
                                        'title' => $title,
                                        'cost' => $cost,
                                        'cost_online' => 'yes',
                                        'inclusive_tax' => 'yes',
                                        'tax_details' => $taxes
                                    );
                                    $shipping_result[] = apply_filters('op_setting_shipping_method_data',$tmp);
                                }
                            }
                        }
                        $shipping_methods =  apply_filters('op_shipping_methods',$shipping_result);
                        $setting[$option] = $shipping_methods;
                        break;
                    case 'payment_methods':
                        $payment_gateways = WC()->payment_gateways->payment_gateways();
                        $addition_payment_gateways = $this->_core->additionPaymentMethods();
                        $payment_gateways = array_merge($payment_gateways,$addition_payment_gateways);
                        $payment_options = $value;
                        foreach ($payment_gateways as $code => $p)
                        {
                            if($p)
                            {
                                if(isset( $payment_options[$code]))
                                {
                                    if(!is_object($p))
                                    {
                                        $title = $p;
                                        $payment_options[$code] = $title;
                                    }else{
                                        $title = $p->title;
                                        $payment_options[$code] = $title;
                                    }

                                }
                            }
                        }
                        $setting[$option] = $payment_options;
                        break;
                    default:
                        $setting[$option] = $value;
                        if($option == 'receipt_template_header' || $option == 'receipt_template_footer')
                        {
                            $setting[$option] = balanceTags($setting[$option],true);
                        }
                        break;
                }
            }
           
        }
        $setting['pos_allow_online_payment'] = $this->_core->allow_online_payment(); // yes or no

        $setting['openpos_tables'] = array();

        return $setting;
    }

    public function getCashierRegisterList(){
        
    }
    public function getCashierList($cashdrawer_id = 0){

        $roles =  array(); //array('administrator','shop_manager');
        $final_roles = apply_filters('op_allow_user_roles',$roles);
        if(!$final_roles)
        {
            $final_roles = array();
        }
        $rows = array();
        
        if($cashdrawer_id > 0)
        {
            $sellers = get_post_meta($cashdrawer_id,'_op_cashiers',true);
            
            foreach($sellers as $user_id)
            {
                $user = get_userdata( $user_id );
                if($user)
                {
                    $user_roles = $user->roles;
                    $user_data = (array)$user->data;

                    if(isset($user_data['user_pass']))
                    {
                        unset($user_data['user_pass']);
                    }

                    $user_data['id'] = (int)$user_data['ID'];
                    $user_data['name'] = $user_data['display_name'] ? $user_data['display_name'] : $user_data['user_nicename'];

                    $user_data['avatar'] = get_avatar_url($user_id);
                    $allow = false;
                    $result = array_intersect($final_roles,$user_roles);
                    if(empty($final_roles)  || !empty($result))
                    {
                        $allow = true;
                    }
                    if($allow)
                    {
                        $rows[] = $user_data;
                    }
                    
                  
                }
            }
        }else{
            $args = array(
                'count_total' => true,
                'number'   => -1,
                'role__in' => $final_roles,
                'fields' => array('ID', 'display_name','user_email','user_login','user_status'),
                'meta_query' => array(
                    array(
                        'key'     => '_op_allow_pos',
                        'value'   => 1,
                        'compare' => '='
                    )
                )
            );
            if(!empty($final_roles))
            {
                $args['role__in'] = $final_roles;
            }
    
            $users = get_users( $args);
            foreach($users as $user)
            {
                $tmp = (array)$user;
    
                $tmp_test['id'] = (int)$tmp['ID'];
                $tmp_test['name'] = $tmp['display_name'];
    
                $rows[] = $tmp_test;
            }
        }
        
        return $rows;
    }

    public function login($user_name,$password,$login_mode = 'default'){
        $result = array('status' => 0, 'message' => '','data' => array());
        try{
            $creds = array(
                'user_login'    => $user_name,
                'user_password' => $password,
                'remember'      => false
            );
            
            if($login_mode == 'pin')
            {
                global $op_woo;
                if( !$password)
                {
                    throw new Exception(__('PIN can not empty.','openpos' ));
                }
                $unique_session = apply_filters('op_login_pin_unique_session',false);
                $user = $op_woo->pin_authenticate($password,$unique_session);
            }else{
                if(!$user_name || !$password)
                {
                    throw new Exception(__('User Name and Password can not empty.','openpos' ));
                }
                
                $user = wp_authenticate($creds['user_login'], $creds['user_password']);
            }
            do_action( 'openpos_before_login',$creds );
            if ( is_wp_error( $user ) ) {
                $result['message'] =  $user->get_error_message();
            }else{
                $id = $user->ID;
                
                $setting = $this->getSetting();
               
                $setting =  apply_filters('op_setting_data',$setting,$user);
                $sale_person = array();
                $pos_balance = get_option('_pos_cash_balance',0);
                $cash = array();
                $drawers = $this->getAllowCashdrawers($id);
                $allow_pos = get_user_meta($id,'_op_allow_pos',true);
                if(!$allow_pos)
                {
                    throw new Exception(__('You have no permission to access POS. Please contact with admin to resolve it.','openpos' ));
                }

                if(!$drawers || empty($drawers))
                {
                    throw new Exception(__('You have no grant access to any Register POS. Please contact with admin to assign your account to POS Register.','openpos' ));
                }
               
                $payment_methods = $this->_core->formatPaymentMethods($setting['payment_methods']);

               
                $price_included_tax = true;
                if(wc_tax_enabled())
                {
                    $price_included_tax = wc_prices_include_tax();
                }
                $user_data = $user->data;
                $prefix = sanitize_title($user_data->user_login);
                $session_id = $this->_session->generate_session_id($prefix);
                $_setting = apply_filters('op_login:setting',$setting,$user_data);
                $format_setting = $this->_formatSetting($_setting);

                foreach ($payment_methods as $_payment_method)
                {
                    if($_payment_method['type'] != 'offline')
                    {
                        $format_setting['pos_allow_online_payment'] = 'yes';
                    }
                }

                if(isset($format_setting['pos_money']))
                {
                    $cash = $format_setting['pos_money'];
                }

                $ip = $this->_core->getClientIp();
                $cashier_name = $user_data->display_name;
                if(!$cashier_name)
                {
                    $cashier_name = $user_data->user_nicename;
                }
                
                $avatar = rtrim(OPENPOS_URL,'/').'/assets/images/default_avatar.png';

                $avatar_args = get_avatar_data( $id);
                if($avatar_args && isset($avatar_args['url']))
                {
                    $avatar = $avatar_args['url'];
                }
               

                $user_login_data = array(
                    'user_id' => $id ,
                    'ip' => $ip,
                    'session' => $session_id ,
                    'username' =>  $user_data->user_login ,
                    'name' =>  $cashier_name,
                    'email' =>  $user_data->user_email ,
                    'role' =>  $user->roles ,
                    'phone' => '',
                    'logged_time' => current_time('Y-m-d H:i:s',true), // gmt date
                    'setting' => apply_filters('op_formatted_setting_data',$format_setting,$user,$payment_methods),
                    'session' => $session_id,
                    'sale_persons' => $sale_person,
                    'payment_methods' => $payment_methods,
                    'cash_drawer_balance' => $pos_balance,
                    'balance' => $pos_balance,
                    'cashes' => $cash,
                    'cash_drawers' => $drawers,
                    'price_included_tax' => $price_included_tax,
                    'avatar' => $avatar,
                    'location' => isset($_REQUEST['location']) ? $_REQUEST['location'] : ''
                );

                $result['data']= apply_filters('op_login_data',$user_login_data,$user);

                $this->_session->save($session_id,$result['data'] );
                $result['status'] = 1;
            }

            do_action( 'openpos_after_login',$creds,$result );
        }catch (Exception $e)
        {
            $result['status'] = 0;
            $result['message'] = $e->getMessage();
        }

        return $result;
    }

    public function logout(){
        global $op_woo_order;
        global $op_report;
        $result['status'] = 1;
        $result['data'] = array();

        $session_id = trim($_REQUEST['session']);
        $current_order_number = isset($_REQUEST['current_order_number']) ? json_decode(stripslashes($_REQUEST['current_order_number']),true) : array();
        
        $op_woo_order->reset_order_number($current_order_number);
        $z_report_str = isset($_REQUEST['z_report']) ? $_REQUEST['z_report'] : '';
        $z_report_data = isset($_REQUEST['z_report']) ? json_decode(stripslashes($z_report_str),true): array();
        $session_data = $this->_getSessionData();
        if(!empty($z_report_data))
        {
            
            if(!empty($session_data))
            {
                unset($session_data['setting']);
                unset($session_data['categories']);
                unset($session_data['cashes']);
                unset($session_data['payment_methods']);
                unset($session_data['sale_persons']);
                $z_report_data['session_data'] = $session_data;
                $id = $op_report->add_z_report($z_report_data,$z_report_str);
                if(!$id)
                {
                    $result['status'] = 0;
                }else{
                    $result['data']['zid'] = $id;
                }
                
            }

            
        }
        if($result['status']  == 1)
        {
            do_action( 'openpos_logout',$session_id, $session_data  );
            $this->_session->clean($session_id);
        }
        
        
        return apply_filters('op_logout_data',$result,$session_data);
    }

    public function logon(){
        $result = array('status' => 0, 'message' => '','data' => array());
        try{
            $session_id = trim($_REQUEST['session']);
           
            $session_data = $this->_getSessionData();
            $logon_mode =  isset($_REQUEST['logon_mode']) ? stripslashes($_REQUEST['logon_mode']) : 'default';
            $username = $session_data['username'];
            $user_id = $session_data['user_id'];
            if($logon_mode == 'pin')
            {
                $password =  isset($_REQUEST['password']) ? trim($_REQUEST['password']) : '';
               
                global $op_woo;
                if( !$password)
                {
                    throw new Exception(__('PIN can not empty.','openpos' ));
                }
                $user = $op_woo->pin_authenticate($password,false);
                if ( is_wp_error($user) ) {
                    
                    throw new Exception($user->get_error_message());
                }
                $sale_persons = isset($session_data['sale_persons']) ? $session_data['sale_persons'] : array();
                $allow_user = array($user_id);
                foreach($sale_persons as $staff)
                {
                    $allow_user[] = $staff['ID'];
                }
                
                if(in_array($user->ID , $allow_user))
                {
                    $result['data'] = array('logon_user_id' => $user->ID,'session_data' =>$session_data);
                    $result['status'] = 1;
                }else{
                    throw new Exception(__('Your PIN is incorrect. Please try again.','openpos' ));
                }

                //start verify pin
            }else{
                $password =  isset($_REQUEST['password']) ? sanitize_text_field($_REQUEST['password']) : '';
                if(!$password)
                {
                    throw new Exception(__('Please enter password','openpos' ));
                }
                
                $user = wp_authenticate($username, $password);
                if ( is_wp_error($user) ) {
                    throw new Exception(__('Your password is incorrect. Please try again.','openpos' ));
                }
                $result['data'] = array('logon_user_id' => $user->ID,'session_data' =>$session_data);
                $result['status'] = 1;
            }
        }catch (Exception $e){
            $result['status'] = 0;
            $result['message'] = $e->getMessage();
        }
        return $result;
    }

    protected function _formatSetting($setting)
    {
        global $op_woo;
        $setting['pos_money'] = $this->_formatCash($setting['pos_money']);
        $setting['pos_custom_cart_discount_amount'] = $this->_core->_formatDiscountAmount($setting['pos_custom_cart_discount_amount']);
        $setting['pos_custom_item_discount_amount'] = $this->_core->_formatDiscountAmount($setting['pos_custom_item_discount_amount']);

        if(isset($setting['pos_allow_tip']) && $setting['pos_allow_tip'] == 'yes' && isset($setting['pos_tip_amount']))
        {
            $setting['pos_tip_amount'] = $this->_core->_formatTipAmount($setting['pos_tip_amount']);
        }

        if(isset($setting['pos_tax_rate_id']))
        {
            $setting['pos_tax_details'] = $this->getTaxDetails($setting['pos_tax_class'],$setting['pos_tax_rate_id']);
        }

        if($setting['pos_tax_class'] == 'op_productax' && isset($setting['pos_discount_tax_rate']))
        {
            $setting['pos_discount_tax'] = $this->getTaxDetails($setting['pos_discount_tax_class'],$setting['pos_discount_tax_rate']);
        }

        if($setting['pos_tax_class'] == 'op_notax')
        {
            $setting['pos_tax_details'] = $this->getTaxDetails('',0);
        }else{
            if ( 'yes' === get_option( 'woocommerce_tax_round_at_subtotal' ) ) {
                $setting['pos_tax_on_item_total'] = 'yes';
            } 
        }

        


        $setting['openpos_customer_addition_fields'] = $op_woo->getCustomerAdditionFields();
        
        $setting['pos_incl_tax_mode'] = ( $setting['pos_tax_class'] != 'op_notax'  && 'yes' === get_option( 'woocommerce_prices_include_tax' ) )  ? 'yes' : 'no'; //check
        $setting['pos_prices_include_tax'] = ( 'yes' === get_option( 'woocommerce_prices_include_tax' ) )  ? 'yes' : 'no';

        if(!isset($setting['pos_tax_included_discount']))
        {
            $setting['pos_tax_included_discount'] = 'no';
        }

        $setting['pos_tax_included_discount'] = ( $setting['pos_tax_class'] != 'op_productax'  || 'yes' === get_option( 'woocommerce_prices_include_tax' ) )  ? 'yes' : $setting['pos_tax_included_discount'];
        
        if($setting['pos_incl_tax_mode'] == 'yes')
        {
            $setting['pos_item_incl_tax_mode'] = 'yes';

        }
        if($setting['pos_sequential_number_enable'] == 'no')
        {
            if(isset($setting['pos_sequential_number']))
            {
                unset($setting['pos_sequential_number']);
            }
            if(isset($setting['pos_sequential_number_prefix']))
            {
                unset($setting['pos_sequential_number_prefix']);
            }

        }

        if($setting['pos_tax_class'] == '')
        {
            $setting['pos_tax_class'] = 'standard';
        }
        if($setting['allow_exchange'] == 'alway')
        {
            $setting['allow_exchange'] = 'yes';
        }

        if($setting['pos_tax_class'] == 'op_notax' )
        {
            $setting['price_included_tax'] = false; 
        }
        if($setting['pos_tax_class'] == 'op_notax' || $setting['pos_tax_class'] == 'op_productax' )
        {
          
           $setting_pos_tax_details = $this->getTaxDetails('',0);
           $setting_pos_tax_details['rate'] = 0;
           $setting['pos_tax_details'] = $setting_pos_tax_details;
           
        }

        

        //remove none use

        if($setting['barcode_label_template'] )
        {
            unset($setting['barcode_label_template']);
        }
        if($setting['pos_custom_css'] )
        {
            unset($setting['pos_custom_css']);
        }
       

        if(isset($setting['pos_product_grid']) && !empty($setting['pos_product_grid'])){
           
            $pos_product_grid_column = 4;
            $pos_product_grid_row = 4;
            if(isset($setting['pos_product_grid']['col'])){
                $col = (int)$setting['pos_product_grid']['col'];
                if($col > 0)
                {
                    $pos_product_grid_column = $col;
                }
            }
            if(isset($setting['pos_product_grid']['row'])){
                $row = (int)$setting['pos_product_grid']['row'];
                if($row > 0)
                {
                    $pos_product_grid_row = $row;
                }
            }
            $setting['pos_product_grid_column'] = $pos_product_grid_column;
            $setting['pos_product_grid_row'] = $pos_product_grid_row;

            $setting['pos_product_grid_column_sm'] = $pos_product_grid_column;
            $setting['pos_product_grid_row_sm'] = $pos_product_grid_row;

            $setting['pos_product_grid_column_xs'] = $pos_product_grid_column;
            $setting['pos_product_grid_row_xs'] = $pos_product_grid_row; 

            unset($setting['pos_product_grid']);
        }
        if(isset($setting['pos_category_grid']) && !empty($setting['pos_category_grid'])){

            $pos_cat_grid_column = 2;
            $pos_cat_grid_row = 4;


            if(isset($setting['pos_category_grid']['col'])){
                $col = (int)$setting['pos_category_grid']['col'];
                if($col > 0)
                {
                    $pos_cat_grid_column = $col;
                }
            }
            if(isset($setting['pos_category_grid']['row'])){
                $row = (int)$setting['pos_category_grid']['row'];
                if($row > 0)
                {
                    $pos_cat_grid_row = $row;
                }
            }

            $setting['pos_cat_grid_column'] = $pos_cat_grid_column;
            $setting['pos_cat_grid_row'] = $pos_cat_grid_row;

            $setting['pos_cat_grid_column_sm'] = $pos_cat_grid_column;
            $setting['pos_cat_grid_row_sm'] = $pos_cat_grid_row;

            $setting['pos_cat_grid_column_xs'] = $pos_cat_grid_column;
            $setting['pos_cat_grid_row_xs'] = $pos_cat_grid_row;

            unset($setting['pos_category_grid']);
        }

        return $setting;
    }

    function getTaxDetails($tax_class,$rate_id = 0){
        global $op_woo;
        $result = array('rate'=> 0,'compound' => '0','rate_id' => $rate_id,'shipping' => 'no','label' => 'Tax');
        if($tax_class != 'op_productax' && $tax_class != 'op_notax')
        {
            $tax_rate = 0;

            if(wc_tax_enabled() )
            {

                    $tax_rates = WC_Tax::get_rates( $tax_class );
                    $rates = $op_woo->getTaxRates($tax_class);
                    if(!empty($tax_rates))
                    {
                        $rate = end($tax_rates);
                        $result = $rate;
                    }
                    if($rate_id && isset($rates[$rate_id]))
                    {
                        $result = $rates[$rate_id];
                    }

                    $result['code'] = $tax_class ? $tax_class.'_'.$rate_id : 'standard_'.$rate_id;
                    $result['rate_id'] = $rate_id;
            }

        }

        return $result;
    }
    function _formatCash($cash_str){
        $result = array();
        if($cash_str)
        {
            $tmp_cashes = explode('|',$cash_str);
            foreach($tmp_cashes as $c_str)
            {
                $c_str = trim($c_str);
                if(is_numeric($c_str))
                {
                    $money_value = (float)$c_str;
                    $tmp_money = array(
                        'name' => html_entity_decode(strip_tags($money_value)),
                        'value' => $money_value
                    );
                    $result[] = $tmp_money;
                }
            }
        }


        return $result;
    }
    
    function _woo_version_number() {
        // If get_plugins() isn't available, require it

        $plugin_folder = get_plugins( '/' . 'woocommerce' );
        $plugin_file = 'woocommerce.php';

        // If the plugin version number is set, return it
        if ( isset( $plugin_folder[$plugin_file]['Version'] ) ) {
            return $plugin_folder[$plugin_file]['Version'];

        } else {
            // Otherwise return null
            return NULL;
        }
    }
    function _op_version_number() {
        // If get_plugins() isn't available, require it

        $plugin_folder = get_plugins( '/' . 'woocommerce-openpos' );
        $plugin_file = 'woocommerce-openpos.php';

        // If the plugin version number is set, return it
        if ( isset( $plugin_folder[$plugin_file]['Version'] ) ) {
            return $plugin_folder[$plugin_file]['Version'];

        } else {
            // Otherwise return null
            return NULL;
        }
    }
    function add_transaction($transaction_data = array()){
        global $op_register;
        global $op_warehouse;
        global $op_transaction;
        $result = array('status' => 0, 'message' => '','data' => array());
        try{
            $session_data = $this->_getSessionData();
            if(empty($transaction_data))
            {
                $transaction = json_decode(stripslashes($_REQUEST['transaction']),true);
                $transaction_id = isset($_REQUEST['id']) ? $_REQUEST['id'] : '';
            }else{
                $transaction = $transaction_data;
                $transaction_id =  $transaction_data['id'];
                
            }
            
           
            $currency = isset($transaction['currency']) ? $transaction['currency'] : null;
            $in_amount = isset($transaction['in_amount']) ? floatval($transaction['in_amount']) : 0;
            $out_amount = isset($transaction['out_amount']) ? floatval($transaction['out_amount']) : 0;
            $store_id = isset($transaction['store_id']) ? intval($transaction['store_id']) : 0;
            $ref = isset($transaction['ref']) ? $transaction['ref'] : date('d-m-Y H:i:s');
            $payment_code = isset($transaction['payment_code']) ? $transaction['payment_code'] : 'cash';
            $payment_name = isset($transaction['payment_name']) ? $transaction['payment_name'] : 'Cash';
            $payment_ref = isset($transaction['payment_ref']) ? $transaction['payment_ref'] : '';
            $created_at = isset($transaction['created_at']) ? $transaction['created_at'] : '';
            $created_at_utc = isset($transaction['created_at_utc']) ? $transaction['created_at_utc'] : '';
            $created_at_time = isset($transaction['created_at_time']) ? $transaction['created_at_time'] : current_time( 'timestamp', true );
            $created_by_id = isset($transaction['created_by_id']) ? $transaction['created_by_id'] : 0;

            $user_id = isset($session_data['user_id']) ? $session_data['user_id'] : '';
           
            $source_type = isset($transaction['source_type']) ? $transaction['source_type'] : '';
            $source = isset($transaction['source']) ? $transaction['source'] : '';
            $source_data = isset($transaction['source_data']) ? $transaction['source_data'] : array();
            $_get_session_id = isset($_REQUEST['session']) ? trim($_REQUEST['session']) : '';
            $session_id = isset($transaction['session']) ? $transaction['session'] : $_get_session_id ;

            $cashdrawer_id = isset($session_data['login_cashdrawer_id']) ? $session_data['login_cashdrawer_id'] : 0;
            $warehouse_id = isset($session_data['login_warehouse_id']) ? $session_data['login_warehouse_id'] : 0;

            $currency_rate = 1;
            if($currency != null)
            {
                if(isset($currency['rate']))
                {
                    $currency_rate = 1* $currency['rate'];
                }
            }
           


            if($transaction_id)
            {
                $post_type = 'op_transaction';
                //start check transaction exist
                $args = array(
                    'post_type' => $post_type,
                    'meta_query' => array(
                        array(
                            'key' => '_transaction_id',
                            'value' => $transaction_id,
                            'compare' => '=',
                        )
                    )
                );
                $query = new WP_Query($args);
                $transactions = $query->get_posts();
                $id = 0;
                $is_new = false;
                if(empty($transactions))
                {
                    $transaction_data = array(
                        'id' => $transaction_id,
                        'session' => $session_id,
                        'source_data' => $source_data,
                        'source' => $source,
                        'source_type' => $source_type,
                        'user_id' => $user_id,
                        'created_by_id' => $created_by_id,
                        'created_at' => $created_at,
                        'created_at_utc' => $created_at_utc,
                        'created_at_time' => $created_at_time,
                        'payment_ref' => $payment_ref,
                        'payment_name' => $payment_name,
                        'payment_code' => $payment_code,
                        'ref' => $ref,
                        'store_id' => $store_id,
                        'out_amount' => $out_amount,
                        'in_amount' => $in_amount,
                        'currency' => $currency,
                        'login_cashdrawer_id' => $cashdrawer_id,
                        'login_warehouse_id' => $warehouse_id,
                        'transaction_data' => $transaction,
                    );
                    $id = $op_transaction->add($transaction_data);
                    $is_new = true;

                }else{
                    $transaction = end($transactions);
                    $id = $transaction->ID;
                   
                }
                //end
                if($id)
                {
                    //add cash drawer balance
                    $is_added_balance = get_post_meta($id,'_add_balance_amount',true);
                    if( $is_new || !$is_added_balance )
                    {
                        if($payment_code == 'cash')
                        {
                            $balance = ($in_amount - $out_amount) / $currency_rate;

                            $op_register->addCashBalance($cashdrawer_id,$balance);
    
                            add_post_meta($id,'_add_balance_amount',$balance);
                        }
                    }
                    
                    $result['status'] = 1;
                    $result['data'] = $id;
                    if($is_new)
                    {
                        do_action('op_add_transaction_after',$id,$session_data,$transaction_data);
                    }
                    
                }

            }

        }catch (Exception $e)
        {
            $result['status'] = 0;
            $result['message'] = $e->getMessage();
        }
        return $result;

    }

    function search_customer_query(){
        global $wpdb;
        $term = isset($_REQUEST['term']) ? $_REQUEST['term'] : '';
        $result = array('status' => 0, 'message' => '','data' => array());
        if($term)
        {
            $term_query = esc_attr( trim($term) );
            $sql = $wpdb->prepare("SELECT * FROM `{$wpdb->users}` as cuser LEFT JOIN `{$wpdb->usermeta}` AS user_meta ON  cuser.ID = user_meta.user_id  WHERE (cuser.user_login LIKE '%s' OR cuser.user_nicename LIKE '%s' OR cuser.user_email LIKE '%s') AND user_meta.meta_key = 'wp_capabilities' AND  user_meta.meta_value LIKE '%customer%' LIMIT 0,5",'%'.$term_query.'%','%'.$term_query.'%','%'.$term_query.'%');
            
            $users_found = $wpdb->get_results( $sql );
            $customers = array();
            $result['status'] = 1;
            foreach($users_found as $user)
            {

                $customer_data = $this->_get_customer_data($user->ID);
                if($customer_data != null)
                {
                    $customer_id = $customer_data['id'];
                    if($customer_id && $customer_id != null )
                    {
                        $customers[] = $customer_data;
                    }
                }
                
            }
            $result['data'] = $customers;
        }
        return $result;
    }
    function search_customer_name_query($full_name){
        $term = $full_name;
        $result = array('status' => 0, 'message' => '','data' => array());
        if($term)
        {

            $name = trim($full_name);
            $tmp = explode(' ',$name);
            $firstname = $tmp[0];
            $lastname = trim(substr($name,(strlen($firstname))));
            if($firstname && $lastname)
            {
                $users = new WP_User_Query(
                    array(
                        'meta_query' => array(
                            'relation' => 'AND',
                            array(
                                'key' => 'first_name',
                                'value' => $firstname,
                                'compare' => 'LIKE'
                            ),
                            array(
                                'key' => 'last_name',
                                'value' => $lastname,
                                'compare' => 'LIKE'
                            )
                        )
                    )
                );
                $users_found =  $users->get_results();

                $result['data'] = $users_found;
            }

        }
        return $result;
    }
    function search_customer_email_query($email){
        global $wpdb;
        $term = $email;
        $result = array('status' => 0, 'message' => '','data' => array());
        if($term)
        {

            $sql = $wpdb->prepare("SELECT * FROM `{$wpdb->users}` as cuser  WHERE cuser.user_email LIKE '%s'  LIMIT 0,1",'%'.esc_attr( trim($term) ).'%' );

            $users_found = $wpdb->get_results( $sql );

            $result['data'] = $users_found;
        }
        return $result;
    }

    function search_customer(){
        global $wpdb;
        global $op_woo;
        $term = isset($_REQUEST['term']) ? trim($_REQUEST['term']) : '';
        $is_browse_latest = isset($_REQUEST['term']) ? false : true;
        $result = array('status' => 0, 'message' => '','data' => array());
        $roles = $op_woo->getAllUserRoles();
        if($term || $is_browse_latest)
        {
            $customers = array();
            if($is_browse_latest)
            {
                
                $customers = $this->get_last_customer(10);
            }else{
                $term = trim($term);
                if(is_email($term))
                {
                    $tmp_phone_search_result = $this->search_customer_by('email',array('email' => $term),true);
                    if($tmp_phone_search_result['status'] == 1)
                    {
                        $customers = $tmp_phone_search_result['data'];
                    }
                }else{
                    $tmp_phone_search_result = $this->search_customer_by('phone',array('phone' => $term),true);
                    if($tmp_phone_search_result['status'] == 1)
                    {
                        $customers = $tmp_phone_search_result['data'];
                    }
                }
                
                
            }
            
           
            if(count($customers)  < 1 && $term)
            {
                $users_found  = array();
                $users_per_page = apply_filters('op_search_customer_result_per_page',5,$term);
                $args = array(
                    'number'  => $users_per_page,
                    'offset'  => 0,
                    'search'  => $term ,
                    'fields'  => 'all',
                );

                if (function_exists('wp_is_large_network') && wp_is_large_network( 'users' ) ) {
                    $args['search'] = ltrim( $args['search'], '*' );
                } elseif ( '' !== $args['search'] ) {
                    $args['search'] = trim( $args['search'], '*' );
                    $args['search'] = '*' . $args['search'] . '*';
                }

                $args = apply_filters('op_search_customer_args',$args,$term);
                $users = new WP_User_Query($args );
                
                if(method_exists($wpdb,'remove_placeholder_escape'))
                {
                    $sql = $wpdb->remove_placeholder_escape($users->request);
                    $users_found = $wpdb->get_results($sql);
                }else{
                    $users_found = $users->get_results();
                }
                $users_found_name = array();
                if(count($users_found) < $users_per_page)
                {
                    $limit = $users_per_page - count($users_found);

                    $users_found_name = $this->_core->search_customer_name($term, $limit);
                    
                }
                foreach($users_found as $user)
                {
                    $user_id = $user->ID;
                    $customer_data = $this->_get_customer_data($user_id);
                    if($customer_data != null)
                    {
                        $customers[$user_id] = $customer_data;
                    }
                    
                }
                foreach($users_found_name as $user)
                {
                    $user_id = $user->ID;
                    $customer_data = $this->_get_customer_data($user_id);
                    if($customer_data != null)
                    {
                        $customer_id = $customer_data['id'];
                        if($customer_id && $customer_id != null)
                        {
                            $customers[$user_id] = $customer_data;
                        }
                    }
                    
                    
                }
            }
            $result['status'] = 1;
            $customers = apply_filters('op_search_customer_result',$customers,$term,$this);   
            $result_customers = array();
            if(is_array($customers) && !empty($customers))
            {
                $result_customers = array_values($customers);
            }
            
            if(count($result_customers) == 1 )
            {
                $result_customers[0]['auto_add'] = 'yes';
            }
            $result['data'] = $result_customers;
            if(empty($customers))
            {
                $result['status'] = 0;
                if($term)
                {
                    $result['message'] = sprintf(__('No customer with search keyword: %s','openpos'),$term);
                }else{
                    $result['message'] = sprintf(__('No customers','openpos'),$term);
                }
                
            }
        }
        return $result;
    }
    function get_last_customer($count = 10)
    {
        global $wpdb;
        $sql = $wpdb->prepare("SELECT * FROM  `{$wpdb->users}` AS user ORDER BY user_registered DESC  LIMIT 0,%d",$count);
        $users_found_query = $wpdb->get_results( $sql );
        $customers = array();
        
        foreach($users_found_query as $u)
        {
            $_user = get_user_by('id', $u->ID);
            if($_user)
            {
                
                $customer_data = $this->_get_customer_data($u->ID);
                if($customer_data != null)
                {
                    $customers[] = $customer_data;
                }
                
            }
            
        }
        return $customers;
    }
    function search_customer_by($by = '',$search_data = array(),$multi = false ){
        global $op_woo;
        global $wpdb;
        $result = array('status' => 0, 'message' => '','data' => array());
        try{
            if(!$by)
            {
                $search_data = isset($_REQUEST['by_data']) ?  json_decode(stripslashes($_REQUEST['by_data']),true) : array();
                $by = isset($_REQUEST['by']) ? esc_attr($_REQUEST['by']) : '';
            }
            
            $term = '';
            
            if($by && isset($search_data[$by]))
            {
                $term = trim($search_data[$by]);
            }
            $roles = $op_woo->getAllUserRoles();
            if($term)
            {
                $users_found = array();
                if($by == 'id'){
                   
                    if( $customer = get_user_by('id', $term))
                    {
                        $users_found[] = $customer;
                    }
                    
                    
                    
                }else{
                    if($by == 'email')
                    {
                        $args = array(
                            'search'         => '*'.esc_attr( trim($term) ).'*',
                            'search_columns' => array(
                                'user_email'
                            ),
                            'number' => 5
                        );
                        $args = apply_filters('op_search_customer_by_email_args',$args);
                        $users = new WP_User_Query( $args );
                        $users_found = $users->get_results();
                    }else{
                        

                        $sql = $wpdb->prepare("SELECT * FROM  `{$wpdb->usermeta}` AS user_meta  WHERE  user_meta.meta_key = 'billing_phone' AND  user_meta.meta_value LIKE '%s' LIMIT 0,5",'%'.esc_sql($term).'%');
                        
                        
                        $users_found_query = $wpdb->get_results( $sql );

                        foreach($users_found_query as $u)
                        {
                            $_user = get_user_by('id', $u->user_id);
                            if($_user)
                            {
                                $users_found[] = $_user;
                            }
                            
                        }
    
                        
                    }
    
                   
                    $users_found = apply_filters('op_search_customer_by_'.$by.'_result',$users_found,$term,$this);   
                    
                    if(count($users_found) > 1 && !$multi)
                    {
                        throw new Exception(__('There are multi user with same term','openpos'));
                    }
                }
                $customers = array();
                if(count($users_found) == 0 )
                {
                    throw new Exception(sprintf(__('No customer found with %s : "%s"','openpos'),$by,$term));
                }
                
                if(count($users_found) == 1 || $multi)
                {

                    
                    foreach($users_found as $user)
                    {
                        $user_roles = $user->roles;
                    
                        if(!empty(array_intersect($roles,$user_roles)))
                        {

                            $customer_data = $this->_get_customer_data($user->ID);
                            if($customer_data != null)
                            {
                                $customers[] = $customer_data;
                            }
                            
                        }
                    }
                    
                    if(!empty($customers))
                    {
                        $result['status'] = 1;
                        if(!$multi)
                        {
                            $result['data'] = end($customers);
                        }else{
                            $result['data'] = $customers;
                        }
                        
                    }

                }
            }else{
                throw new Exception(sprintf(__('Please enter any keyword for "%s" to search customer','openpos'),$by));
            }
        }catch (Exception $e)
        {
            $result['status'] = 0;
            $result['message'] = $e->getMessage();
        }

        return $result;
    }

    function get_customer_field(){
        $result = array('status' => 0, 'message' => '','data' => array());
        try{
            $by_data = json_decode(stripslashes($_REQUEST['by_data']),true);
            $country = $by_data['country'];
            $data = array();
            if($country )
            {
                $countries_obj   = new WC_Countries();
                $states = $countries_obj->get_states($country);
                if(!$states || empty($states))
                {
                    $data['state'] = array(
                        'type' => 'text',
                        'default' => ''
                    );
                }else{
                    $state_options = array();
                    foreach($states as $key => $state)
                    {
                        $state_options[] = ['value' => $key,'label' => $state];
                    }
                    $data['state'] = array(
                        'type' => 'select',
                        'default' => '',
                        'options' => $state_options
                    );
                }

            }
            $result['data'] = apply_filters('op_get_customer_field',$data);
            $result['status'] = 1;
        }catch (Exception $e)
        {
            $result['status'] = 0;
            $result['message'] = $e->getMessage();
        }

        return $result;
    }

    function add_customer(){
        global $op_woo;
        $request = $_REQUEST;

        $customer_data_json = stripslashes($request['customer']);
        $customer_request_data = apply_filters('op_new_customer_request',json_decode($customer_data_json,true));
        
        $name = isset($customer_request_data['name']) ? $customer_request_data['name'] : '';
        $create_user = isset($customer_request_data['create_customer']) ? $customer_request_data['create_customer'] : 1;
        
        $result = array('status' => 0, 'message' => '','data' => array());
        if(!$create_user)
        {
            if($name)
            {
                $name = trim($name);
                $tmp = explode(' ',$name);
                $firstname = $tmp[0];
                $lastname = trim(substr($name,(strlen($firstname))));
            }else{
                $firstname = isset($customer_request_data['firstname']) ? $customer_request_data['firstname'] : '';
                $lastname = isset($customer_request_data['lastname']) ? $customer_request_data['lastname'] : '';
            }
            $email = isset($customer_request_data['email']) &&  $customer_request_data['email'] != 'null' ? $customer_request_data['email'] : '';
            $phone = isset($customer_request_data['phone']) &&  $customer_request_data['phone'] != 'null'  ? $customer_request_data['phone'] : '';
            $address = isset($customer_request_data['address']) &&  $customer_request_data['address'] != 'null'  ? $customer_request_data['address'] : '';
            $company = isset($customer_request_data['company']) &&  $customer_request_data['company'] != 'null'  ? $customer_request_data['company'] : '';
            $customer_data = array(
                'id' => 0,
                'name' => $name,
                'firstname' =>$firstname,
                'lastname' => $lastname,
                'company' => $company,	
                'address' => $address,
                'phone' => $phone,
                'email' => $email,
                'billing_address' =>array(),
                'point' => 0,
                'discount' => 0,
                'badge' => '',
                'shipping_address' => array()
            );
            
            $tmp = apply_filters('op_guest_customer_data',$customer_data);
            $result['status'] = 1;
            $result['data'] = $tmp;
            return $result;
        }
        
        $session_data = $this->_getSessionData();
        $reponse_customer_data = $op_woo->_add_customer($customer_request_data,$session_data);
       
        if($reponse_customer_data['status'] > 0)
        {
            try{
                
                $id = $reponse_customer_data['data'];
                if($id)
                {
                    do_action('op_add_customer_after',$id,$session_data,$customer_request_data);
                    $customer_data = $this->_get_customer_data($id);
                    $tmp = apply_filters('op_new_customer_data',$customer_data);
                    $result['status'] = 1;
                    $result['data'] = $tmp;
                    
                    
                }
                
            }catch (Exception $e)
            {
                $result['status'] = 0;
                $result['message'] = $e->getMessage();
            }

        }else{
            $result['status'] = 0;
            $result['message'] = $reponse_customer_data['message'];
        }
        return $result;
    }
    
    public function _get_customer_data($customer_id){
        global $op_woo;
        return $op_woo->formatCustomer($customer_id);
    }
    function update_customer(){
        $result = array('status' => 0, 'message' => '','data' => array());
        try{
            $request = $_REQUEST;
            $customer_data_json = stripslashes($request['customer']);
            $customer_request_data = apply_filters('op_update_customer_request',json_decode($customer_data_json,true));;

            $customer_id = isset($customer_request_data['id']) ? (1 * $customer_request_data['id']) : 0; 
            if(!$customer_id)
            {
                throw new Exception(__('Customer do not exist','openpos'));
            }
            $name = isset($customer_request_data['name']) ? esc_textarea($customer_request_data['name']) : '';
            $address = (isset($customer_request_data['address']) && $customer_request_data['address']!=null) ? esc_textarea($customer_request_data['address']) : '';
            $phone = isset($customer_request_data['phone']) && $customer_request_data['phone'] != null ? esc_textarea($customer_request_data['phone']) : '';
            $address_2 = isset($customer_request_data['address_2']) && $customer_request_data['address_2'] != null ? esc_textarea($customer_request_data['address_2']):'';
            $state = isset($customer_request_data['state']) && $customer_request_data['state'] != null ? esc_textarea($customer_request_data['state']):'';
            $city = isset($customer_request_data['city']) && $customer_request_data['city'] != null ? esc_textarea($customer_request_data['city']):'';
            $country = isset($customer_request_data['country']) && $customer_request_data['country'] != null ? esc_textarea($customer_request_data['country']):'';
            $postcode = isset($customer_request_data['postcode']) && $customer_request_data['postcode'] != null ? esc_textarea($customer_request_data['postcode']):'';
            $customer = new WC_Customer($customer_id);
            $session_data = $this->_getSessionData();
            
            if($customer->get_email())
            {
                $firstname = isset($customer_request_data['firstname']) ? esc_textarea($customer_request_data['firstname']) : '';
                $lastname = isset($customer_request_data['lastname']) ? esc_textarea($customer_request_data['lastname']) : '';

                if($name)
                {
                    $name = trim($name);
                    $tmp = explode(' ',$name);
                    $firstname = trim($tmp[0]);
                    $lastname = trim(substr($name,(strlen($firstname))));
                }

          
                $customer->set_billing_address($address);
                $customer->set_billing_phone($phone);
                $customer->set_display_name($name);
                $customer->set_first_name(wc_clean($firstname));
                $customer->set_last_name(wc_clean($lastname));

                $customer->set_billing_first_name($firstname);
                $customer->set_billing_last_name($lastname);

                if($address_2 )
                {
                    $customer->set_billing_address_2($address_2);
                }
                if($state)
                {
                    $customer->set_billing_state($state);
                }
                if($city)
                {
                    $customer->set_billing_city($city);
                }
                if($postcode)
                {
                    $customer->set_billing_postcode($postcode);
                }

                if($country)
                {
                    $customer->set_billing_country($country);
                }

                if($name)
                {
                    $customer->update_meta_data('_op_full_name',$name);
                }

                $customer->save_data();

                $user_obj = get_userdata( $customer_id);
                clean_user_cache( $user_obj );
                
                do_action('op_update_customer_after',$customer_id,$session_data,$customer_request_data);
                $result['status'] = 1;
                $result['data'] = $this->_get_customer_data($customer_id);
            }

        }catch (Exception $e)
        {
            $result['status'] = 0;
            $result['message'] = $e->getMessage();
        }
        return $result;
    }
    function get_customer_orders(){
        global $op_woo;
        $result = array('status' => 0, 'message' => '','data' => array());
        try{
            $customer_id = (int)$_REQUEST['customer_id'];
            $current_page = isset($_REQUEST['page']) ? (int)$_REQUEST['page'] : 1;
            if(!$customer_id)
            {
                throw new Exception(__('Customer do not exist','openpos'));
            }
            $customer = new WC_Customer($customer_id);
            if(!$customer)
            {
                throw new Exception(__('Customer do not exist','openpos'));
            }
            $total_order_count = $customer->get_order_count();
            $per_page = 10;

            $total_page = ceil($total_order_count / $per_page);

            $data['status'] = 1;
            $data['total_page'] = $total_page;

            $data['orders'] = array();
            $offset = ($current_page -1) * $per_page;
           

            if($this->_enable_hpos)
            {
                $post_type = 'shop_order';
               
                $args = array(
                    'numberposts' => $per_page,
                    'post_type' => $post_type,
                    'post_status' => 'any',
                    'customer_id' => $customer_id,
                    'offset'           => $offset,
                    'orderby'          => 'date',
                    'order'            => 'DESC',
                );
                $args['_query_src'] = 'op_order_query';
                $data_store = WC_Data_Store::load( 'order' );
                $customer_orders = $data_store->query( $args );
               
            }else{
                $query_params = array(
                    'numberposts' => $per_page,
                    'meta_key'    => '_customer_user',
                    'meta_value'  => $customer_id,
                    'post_type'   => wc_get_order_types( 'view-orders' ),
                    'post_status' => array_keys( wc_get_order_statuses() ),
                    'offset'           => $offset,
                    'customer' => $customer_id,
                    'orderby'          => 'date',
                    'order'            => 'DESC',
                ) ;
                $customer_orders = get_posts( $query_params );
            }
            

            foreach($customer_orders as $customer_order)
            {
                if($customer_order instanceof WC_Order )
                {
                    $order_id = $customer_order->get_id();
                }else{
                    $order_id = $customer_order->ID;
                }
                
                $formatted_order = $op_woo->formatWooOrder($order_id);
                if(!empty($formatted_order))
                {
                    $data['orders'][] =  $formatted_order;
                }
            }

            $result['data'] = $data;
        }catch (Exception $e)
        {
            $result['status'] = 0;
            $result['message'] = $e->getMessage();
        }
        return $result;
    }
    function update_order(){
        $result = array('status' => 0, 'message' => '','data' => array());
        try{
            global $op_exchange;
            global $op_woo;
            global $op_woo_order;
            $session_data = $this->_getSessionData();
            $order_post_data = json_decode(stripslashes($_REQUEST['order']),true);
            $is_refund = false;
            $is_exchange = false;
            $login_warehouse_id = isset($session_data['login_warehouse_id']) ? $session_data['login_warehouse_id'] : 0;

            $order_id = $order_post_data['order_id'];
            $order_number = isset($order_post_data['order_number']) ? $order_post_data['order_number'] : 0;

            $order = wc_get_order($order_id);
            
            if($order_number && !$order )
            {
                $tmp_order_id = $op_woo_order->get_order_id_from_number($order_number);
                if($tmp_order_id)
                {
                    $order_id = $tmp_order_id;
                    $order = wc_get_order($order_id);
                }
            }
            if(!$order){
                $tmp_time_test = date('Y',round($order_id/1000));
                $today_year = date('Y');
                $is_local = (!$order_number  || ($today_year - $tmp_time_test) < 10) ? true : false;
                if(  $is_local && isset($order_post_data['id']))
                {
                    $_id = $order_post_data['id'];
                    $tmp_order_id = $op_woo_order->get_order_id_from_local_id($_id);
                  
                    if($tmp_order_id)
                    {
                        $order_id = $tmp_order_id;
                        $order = wc_get_order($order_id);
                    }
                }
                
            }

            if(isset($order_post_data['refunds']) && !empty($order_post_data['refunds'])){
                $is_refund = true;
            }
            if(isset($order_post_data['exchanges']) && !empty($order_post_data['exchanges'])){
                $is_exchange = true;
            }
            if((!$is_exchange && !$is_refund) || !$order_id)
            {
                $order_result = $this->add_order();
            }else{
                $order_result['status'] = 0;
                $order_result['data'] = array();
                $_order = wc_get_order($order_id);
                if($_order)
                {
                    $order_result['status'] = 1;
                    $order_result['data'] = $op_woo->formatWooOrder($order_id);
                    $_order->update_meta_data( '_op_order', $order_post_data );
                    $_order->save();
                }
                
            }
            if($order_result['status'] == 1)
            {
                global $pos_order_id;
                $order_data = $order_result['data'];
                do_action('op_update_order_before',$order_data,$order_post_data);
                $order_id = $order_data['order_id'];
                $order_number = isset($order_data['order_number']) ? $order_data['order_number'] : 0;
                $order = wc_get_order($order_id);
                $pos_order_id = $order_id;
                if($order)
                {
                    if(isset($order_post_data['refunds']) && !empty($order_post_data['refunds']))
                    {
                        $order_refunds = $order->get_refunds();
                        $post_refunds = $order_post_data['refunds'];
    
                        $order_items = $order->get_items();
                        $order_fee_items = $order->get_items('fee');
                        
                        $warehouse_id = $order->get_meta('_op_sale_by_store_id');

                        foreach($post_refunds as $_refund)
                        {
    
                            $refund_reason = isset($_refund['reason']) ? $_refund['reason'] : '';
                            $refund_restock = isset($_refund['restock']) ? $_refund['restock'] : 'yes';
                            $allow_add = true;
                            foreach($order_refunds as $order_refund)
                            {
                                $order_refund_id = $order_refund->get_id();
                                $local_id = get_post_meta($order_refund_id,'_op_local_id',true);
                                if($local_id == $_refund['id'])
                                {
                                    $allow_add = false;
                                }
                            }
                            if($allow_add)
                            {
                                $line_items = array();
                                
                                foreach($_refund['items'] as $refund_item)
                                {
                                    $item_local_id = $refund_item['id'];
                                    $tax_details = $refund_item['tax_details'];
                                    $item_type = $refund_item['item_type'] ? $refund_item['item_type'] : '';
                                    $check_order_id = wc_get_order_id_by_order_item_id($item_local_id);
                                    $item_id = $item_local_id;
                                    if($check_order_id != $order_id)
                                    {
                                        if($item_type == 'fee')
                                        {
                                            foreach($order_fee_items as $order_item)
                                            {
                                                $order_local_item_id = $order_item->get_meta('_op_local_id');
                                                if($order_local_item_id == $item_local_id)
                                                {
                                                    $item_id = $order_item->get_id();
                                                }
                                            }
                                        }else{
                                            foreach($order_items as $order_item)
                                            {
                                                $order_local_item_id = $order_item->get_meta('_op_local_id');
                                                if($order_local_item_id == $item_local_id)
                                                {
                                                    $item_id = $order_item->get_id();
                                                }
                                            }
                                        }
                                    }
                                    $refund_tax = array();
                                    foreach($tax_details as $k => $v)
                                    {
                                        $rate_id = $v['rate_id'];
                                        $refund_tax[$rate_id] = $v['total'];
                                    }
                                   
                                    $line_items[ $item_id ] = array(
                                        'qty'          => 1 * $refund_item['qty'],
                                        'refund_total' => 1 * $refund_item['refund_total'],
                                        'refund_tax'   => $refund_tax,
                                    );
                                }
                                $refund_amount = $_refund['refund_total'];
    
                                if($refund_amount > 0)
                                {
                                    global $op_refund_restock;
                                    $op_refund_restock = $refund_restock == 'no' ? 'no' : 'yes';
                                    $restock_items = $refund_restock == 'no' ? false : true;
    
                                    if($login_warehouse_id > 0)
                                    {
                                        $restock_items = false;
                                    }
     
                                    $refund_arg = apply_filters('update_order:refund_arg',array(
                                        'amount'         => $refund_amount,
                                        'reason'         => $refund_reason,
                                        'order_id'       => $order_id,
                                        'line_items'     => $line_items,
                                        'refund_payment' => false,
                                        'restock_items'  => $restock_items,
                                    ));
                                   
                                    $refund = wc_create_refund($refund_arg);
    
                                    if( $refund instanceof WP_Error)
                                    {
                                        throw new Exception($refund->get_error_message());
                                    }else{
                                        
                                        $refund->update_meta_data('_op_local_id',$_refund['id']);
                                        $refund->save();
                                    }
                                }
                            }
                        }
                    }
                    //exchange
                    if(isset($order_post_data['exchanges']) && !empty($order_post_data['exchanges']) && false) // disable exchange old
                    {
                        $pos_exchange_partial_refund = $this->settings_api->get_option('pos_exchange_partial_refund','openpos_general');
                        
                       
                        
                        $order_refunds = $order->get_refunds();
                        $post_exchanges = $order_post_data['exchanges'];
    
                        $order_items = $order->get_items();
                        $warehouse_id = $order->get_meta('_op_sale_by_store_id');
                        
                        foreach($post_exchanges as $_exchange)
                        {
                            $refund_reason = isset($_exchange['reason']) ? $_exchange['reason'] : '';
                            $allow_add = true;
                            foreach($order_refunds as $order_refund)
                            {
                                $order_refund_id = $order_refund->get_id();
                                $local_id = get_post_meta($order_refund_id,'_op_local_id',true);
                                if($local_id == $_exchange['id'])
                                {
                                    $allow_add = false;
                                }
                            }
                            if($allow_add)
                            {
                                $op_exchange->save($order_id,$_exchange,$session_data);
                                $line_items = array();
                                foreach($_exchange['return_items'] as $refund_item)
                                {
                                    $item_local_id = $refund_item['id'];
                                    $check_order_id = wc_get_order_id_by_order_item_id($item_local_id);
                                    if($check_order_id == $order_id)
                                    {
                                        $item_id = $item_local_id;
                                    }else{
                                        foreach($order_items as $order_item)
                                        {
                                            $order_local_item_id = $order_item->get_meta('_op_local_id');
                                            if($order_local_item_id == $item_local_id)
                                            {
                                                $item_id = $order_item->get_id();
                                            }
                                        }
                                    }
    
    
                                    $line_items[ $item_id ] = array(
                                        'qty'          => 1 * $refund_item['qty'],
                                        'refund_total' => 1 * $refund_item['refund_total'],
                                        'refund_tax'   => array(),
                                    );
                                }
    
                                if($_exchange['fee_amount'] > 0)
                                {
                                    $fee_item = new WC_Order_Item_Fee();
                                    $fee_item->set_name(__('Exchange Fee','openpos'));
                                    $fee_item->set_total($_exchange['fee_amount']);
                                    $fee_item->set_amount($_exchange['fee_amount']);
                                    $order->add_item($fee_item);
                                }
                                $addition_total = $_exchange['addition_total'] - $_exchange['fee_amount'];
                                if( $addition_total  > 0)
                                {
                                    $fee_item = new WC_Order_Item_Fee();
                                    $fee_item->set_name(__('Addition total for exchange items','openpos'));
                                    $fee_item->set_total($addition_total);
                                    $fee_item->set_amount($addition_total);
                                    $order->add_item($fee_item);
                                }
                                $order->calculate_totals(false);
                                $order->save();
                            }
                        }
    
    
                    }
                }
                
                do_action('op_update_order_after',$order_data,$order_post_data);
                return $order_result;
            }else{
                return $order_result;
            }
        }catch (Exception $e)
        {
            $result['status'] = 0;
            $result['message'] = $e->getMessage();
        }
        return $result;
    }
    function add_order($is_clear = false){
        global $op_register;
        global $op_warehouse;
        global $_op_warehouse_id;
        global $op_woo;
        global $op_woo_order;
        global $op_receipt;
        $result = array('status' => 0, 'message' => '','data' => array());
        $use_hpos = $this->_core->enable_hpos();
        $setting_tax_class =  apply_filters('add_order:pos_tax_class', $this->settings_api->get_option('pos_tax_class','openpos_general') );
        $setting_tax_rate_id = apply_filters('add_order:pos_tax_rate_id', $this->settings_api->get_option('pos_tax_rate_id','openpos_general'));
        

        $is_product_tax = false;
        try{
            $session_data = $this->_getSessionData();
            $session_setting = isset($session_data['setting']) ? $session_data['setting'] : array();
            $login_cashdrawer_id = isset($session_data['login_cashdrawer_id']) ? $session_data['login_cashdrawer_id'] : 0;
            $login_warehouse_id = isset($session_data['login_warehouse_id']) ? $session_data['login_warehouse_id'] : 0;
            $_op_warehouse_id = $login_warehouse_id;
            $order_data_json = stripslashes($_REQUEST['order']);
            $order_data = json_decode($order_data_json,true);
            
            if(!$login_cashdrawer_id)
            {
                if(isset($_REQUEST['cashdrawer_id']))
                {
                    $login_cashdrawer_id = (int)$_REQUEST['cashdrawer_id'];
                }
            }
            $order_source = isset($_REQUEST['source']) ? $_REQUEST['source'] : 'sync';

            $order_parse_data = apply_filters('op_new_order_data',$order_data,$session_data);

            $has_shipping = false;
            if(isset($order_parse_data['add_shipping']) && $order_parse_data['add_shipping'] == true)
            {
                $has_shipping = true;
            }
            $order_number = isset($order_parse_data['order_number']) ? $order_parse_data['order_number'] : 0;
            $new_order_number = $order_number;
            $order_id = isset($order_parse_data['order_id']) ? $order_parse_data['order_id'] : 0;
            $order_local_id = isset($order_parse_data['id']) ? $order_parse_data['id'] : '';
            $order_number_format = isset($order_parse_data['order_number_format']) ? $order_parse_data['order_number_format'] : '';
            $order_number_details = isset($order_parse_data['order_number_details']) ? $order_parse_data['order_number_details'] : array();
            if(isset($order_number_details['order_id']) && $order_number_details['order_id'] &&  $order_id != $order_number_details['order_id']  )
            {
                $order_id = $order_number_details['order_id'];
            }

            do_action('op_add_order_data_before',$order_parse_data,$session_data);

            $items = isset($order_parse_data['items']) ? $order_parse_data['items'] : array();
            $fee_items = isset($order_parse_data['fee_items']) ? $order_parse_data['fee_items'] : array();
            if(empty($items))
            {
                throw new Exception('Item not found.');
            }
            $customer_id = 0;
            
            $customer = isset($order_parse_data['customer']) ? $order_parse_data['customer'] : array();
            $customer_email = isset($customer['email']) ? $customer['email'] : '';
            if(!empty($customer) && isset($customer['id']))
            {
                $customer_id = $customer['id'];
                
                if($customer_id == 0 || !$customer_id)
                {
                    if($customer_email && $customer_email != null)
                    {
                        $customer_user = get_user_by('email',$customer_email);
                        if($customer_user)
                        {
                            $customer_id = $customer_user->get('ID');
                        }
                    }
                    if($customer_id == 0 && isset($customer['create_customer']) && $customer['create_customer'] == 1)
                    {
                        $tmp_create_customer = $op_woo->_add_customer($customer,$session_data);
                        $customer_id =  $tmp_create_customer['data'];
                        //create new customer
                    }
                }
            }
            if(isset($customer['addition_data']) && is_array($customer['addition_data']))
            {
                foreach($customer['addition_data'] as $addition_data_key => $addition_data_value)
                {
                    $customer[$addition_data_key] = $addition_data_value;
                }
            }
            $default_country = $op_woo->getDefaultContry();

            $tip = isset($order_parse_data['tip']) ? $order_parse_data['tip'] : array();
            $source = isset($order_parse_data['source']) ? $order_parse_data['source'] : '';
            $source_type = isset($order_parse_data['source_type']) ? $order_parse_data['source_type'] : '';

            $_get_session_id = isset($_REQUEST['session']) ? trim($_REQUEST['session']) : '';
            $session_id = isset($order_parse_data['session']) ? $order_parse_data['session'] : $_get_session_id ;


            $sub_total = isset($order_parse_data['sub_total']) ? floatval($order_parse_data['sub_total']) : 0;
            $tax_amount = isset($order_parse_data['tax_amount']) ? floatval($order_parse_data['tax_amount']) : 0;
            $tax_details = isset($order_parse_data['tax_details']) ? $order_parse_data['tax_details'] : array();
            $fee_tax_details = isset($order_parse_data['fee_tax_details']) ? $order_parse_data['fee_tax_details'] : array();
            $discount_amount = isset($order_parse_data['discount_amount']) ? floatval($order_parse_data['discount_amount']) : 0;
            $discount_type = isset($order_parse_data['discount_code']) ? floatval($order_parse_data['discount_code']) : 0;

            $discount_excl_tax = isset($order_parse_data['discount_excl_tax']) ? floatval($order_parse_data['discount_excl_tax']) : 0;
            $discount_final_amount = isset($order_parse_data['discount_final_amount']) ? floatval($order_parse_data['discount_final_amount']) : 0;
            $discount_tax_amount = isset($order_parse_data['discount_tax_amount']) ? floatval($order_parse_data['discount_tax_amount']) : 0;

            

            $discount_tax_details = isset($order_parse_data['discount_tax_details']) ? $order_parse_data['discount_tax_details'] : array();
            $transactions = isset($order_parse_data['transactions']) ? $order_parse_data['transactions'] : array();
                 
            $final_discount_amount = isset($order_parse_data['final_discount_amount']) ? floatval($order_parse_data['final_discount_amount']) : 0;
            $final_items_discount_amount = 0;
            $final_items_discount_tax = 0;

            $grand_total = isset($order_parse_data['grand_total']) ? floatval($order_parse_data['grand_total']) : 0;

            $point_paid = isset($order_parse_data['point_paid']) ? $order_parse_data['point_paid'] : 0;
            $point_discount = isset($order_parse_data['point_discount']) ? $order_parse_data['point_discount'] : array();

            $discount_code = isset($order_parse_data['discount_code']) ? $order_parse_data['discount_code'] : '';
            $discount_code_amount = isset($order_parse_data['discount_code_amount']) ? floatval($order_parse_data['discount_code_amount']) : 0;
            $discount_code_tax_amount = isset($order_parse_data['discount_code_tax_amount']) ? floatval($order_parse_data['discount_code_tax_amount']) : 0;
            $discount_code_excl_tax = isset($order_parse_data['discount_code_excl_tax']) ? floatval($order_parse_data['discount_code_excl_tax']) : ( $discount_code_amount - $discount_code_tax_amount);
            $discount_codes = isset($order_parse_data['discount_codes']) ? $order_parse_data['discount_codes'] : array();

            $payment_method = isset($order_parse_data['payment_method']) ? $order_parse_data['payment_method'] : array();
            $shipping_information = isset($order_parse_data['shipping_information']) ? $order_parse_data['shipping_information'] : array();
            
            $sale_person_id = isset($order_parse_data['sale_person']) ? intval($order_parse_data['sale_person']) : 0;
            $sale_person_name = isset($order_parse_data['sale_person_name']) ? $order_parse_data['sale_person_name'] : '';
            

            $created_at = isset($order_parse_data['created_at']) ? $order_parse_data['created_at'] : current_time( 'timestamp', true );
            $created_at_time = isset($order_parse_data['created_at_time']) ? $order_parse_data['created_at_time'] : time() * 1000;

            $store_id = isset($order_parse_data['store_id']) ? intval($order_parse_data['store_id']) : 0;
            $is_online_payment = ($order_parse_data['online_payment'] == 'true') ? true : false;
            $order_state = isset($order_parse_data['state']) ? $order_parse_data['state'] : 'completed';
            $email_receipt = isset($order_parse_data['email_receipt']) ? $order_parse_data['email_receipt'] : 'no';
            $shipping_tax = isset($order_parse_data['shipping_tax']) ? $order_parse_data['shipping_tax'] : 0;
            $shipping_rate_id = isset($order_parse_data['shipping_rate_id']) ? $order_parse_data['shipping_rate_id'] : '';

            $tmp_setting_order_status = $this->settings_api->get_option('pos_order_status','openpos_general');
            $setting_order_status =  apply_filters('op_new_order_status',$tmp_setting_order_status,$order_parse_data);
            if($order_state == 'pending_payment')
            {
                $is_online_payment = true;
            }

            $point_discount = isset($order_parse_data['point_discount']) ? $order_parse_data['point_discount'] : array();
            $shipping_cost = isset($order_parse_data['shipping_cost']) ? $order_parse_data['shipping_cost'] : 0;
            $shipping_first_name  = '';
            $shipping_last_name = '';

            $customer_firstname = isset($customer['firstname']) ? $customer['firstname'] : '';
            $customer_lastname = isset($customer['lastname']) ? $customer['lastname'] : '';
            $customer_name = isset($customer['name']) ? $customer['name'] : '';

            //total paid
            $customer_total_paid = isset($order_parse_data['customer_total_paid']) ? $order_parse_data['customer_total_paid'] : 0;
            $total_paid = isset($order_parse_data['total_paid']) ? $order_parse_data['total_paid'] : 0; //amount should pay
            $allow_laybuy = isset($order_parse_data['allow_laybuy']) ? $order_parse_data['allow_laybuy'] : 'no';
            if(!$total_paid)
            {
                $total_paid = $grand_total;
            }
            if($customer_total_paid == $total_paid)
            {
                $allow_laybuy = 'no';
            }

            if(!$customer_firstname && !$customer_lastname && $customer_name)
            {
                $name = trim($customer_name);
                $tmp = explode(' ',$name);
                if(count($tmp) > 0)
                {
                    $customer_firstname = $tmp[0];
                    $customer_lastname = substr($name,strlen($customer_firstname));
                }
            }


            if(isset($shipping_information['name']) && $shipping_information['name'])
            {
                $name = trim($shipping_information['name']);
                $tmp = explode(' ',$name);
                if(count($tmp) > 0)
                {
                    $shipping_first_name = $tmp[0];
                    $shipping_last_name = trim(substr($name,strlen($shipping_first_name)));
                }
            }

            $cashier_id = $session_data['user_id'];
            $note = isset($order_parse_data['note']) ? $order_parse_data['note'] : '';
            $orders = array();
            if($order_id)
            {
                
                if($order_id == $order_local_id)
                {
                    $_tmp_order = wc_get_order($order_id);
                    if($_tmp_order){
                        $orders[] = $_tmp_order;
                    }
                }
                if(empty($orders))
                {
                    $post_type = 'shop_order';
                    //start check order exist
                    $args = array(
                        'post_type' => $post_type,
                        'post_status' => 'any',
                        'meta_query' => array(
                            array(
                                'key' => '_pos_order_id',
                                'value' => $order_id,
                                'compare' => '=',
                            )
                        )
                    );
                    if($use_hpos)
                    {
                        $args['_query_src'] = 'op_order_query';
                        $data_store = WC_Data_Store::load( 'order' );
                        $orders = $data_store->query( $args );
                        // hpos here
                    }else{
                        $query = new WP_Query($args);
                        $orders = $query->get_posts();
                    }
                }
                
                
            }
            
            
            if(empty($orders))
            {
                $post_type = 'shop_order';
                //start check order exist
                $args = array(
                    'post_type' => $post_type,
                    'post_status' => 'any',
                    'meta_query' => array(
                        array(
                            'key' => '_op_local_id',
                            'value' => $order_local_id,
                            'compare' => '=',
                        )
                    )
                );
                if($use_hpos)
                {
                    $args['_query_src'] = 'op_order_query';
                    $data_store = WC_Data_Store::load( 'order' );
                    $orders = $data_store->query( $args );
                }else{
                    $query = new WP_Query($args);
                    $orders = $query->get_posts();
                }
                
            }
            
            
            $continue_order_status = $this->settings_api->get_option('pos_continue_checkout_order_status','openpos_general');
            foreach($orders as $o)
            {
                $is_abandoned = false;
                if ( $o instanceof WC_Order )
                {
                    $post_status = $o->get_status();
                    $from_sesison = $o->get_meta('_op_pos_session');
                    $from_source = $o->get_meta('_op_order_source');
                    if($from_sesison && $from_source != 'openpos' )
                    {
                        $is_abandoned = true;
                    }
                }else{
                    $post_status = $o->post_status;
                    $from_sesison = get_post_meta($o->ID,'_op_pos_session',true);
                    $from_source = get_post_meta($o->ID,'_op_order_source',true);
                    if($from_sesison && $from_source != 'openpos' )
                    {
                        $is_abandoned = true;
                    }
                }
                if(is_array($continue_order_status) && !empty($continue_order_status)){
                    if(strpos($post_status,'wc-') === false)
                    {
                       $post_status = 'wc-'.$post_status;
                    }
                    if(in_array($post_status , $continue_order_status))
                    {
                        $is_clear = true; 
                    }
                }
                if($is_abandoned)
                {
                    $is_clear = true; 
                }

            }

           
            $orders = apply_filters('op_check_order_data',$orders,$order_parse_data,$session_data);
            
            
            
            if(empty($orders) || $is_clear )
            {
                $arg = array(
                    'status'        => null,
                    'customer_id'   => $customer_id,
                    'customer_note' => $note
                );

                
                if($order_number > 0)
                {
                    $order_post = false;
                    if($order_id > 0)
                    {
                        if($use_hpos)
                        {
                            $order_post = wc_get_order($order_id);
                        }else{
                            $order_post = get_post($order_id);
                        }
                        
                    }
                    
                    if(!$order_post)
                    {
                        $pos_use_offline_order_number = (isset($session_setting['pos_use_offline_order_number']) && $session_setting['pos_use_offline_order_number'] == 'yes') ? true : false;
                        if(!$pos_use_offline_order_number && $order_source == 'direct')
                        {
                            $is_update_order_number = apply_filters('op_order_data_order_is_update_order_number',true,$order_post,$order_parse_data);
                            if($use_hpos)
                            {
                                $next_order_json = $op_woo_order->hpos_get_order_number($is_update_order_number );
                            }else{
                                $next_order_json = $this->get_order_number($is_update_order_number );
                            }
                            
                            $next_order = isset($next_order_json) ? $next_order_json['data'] : array();
                            
                            $order_id = $next_order['order_id'];
                            $order_post = apply_filters('op_order_data_order_post_obj',get_post($order_id),$order_parse_data);

                            $new_order_number = apply_filters('op_order_data_order_post_order_number',$next_order['order_number'],$order_post,$order_parse_data);
                            $order_number_format = apply_filters('op_order_data_order_post_order_number_format',$next_order['order_number_formatted'],$order_post,$order_parse_data);
                            
                           
                        }else{
                            $_order_post = $op_woo_order->reGenerateDraftOrder($order_id,$new_order_number,$order_number_format); // hpos implemented
                            $order_post = apply_filters('op_order_data_order_post_obj',$_order_post,$order_parse_data);
                        }
                        
                    }
                    if($order_post)
                    {
                        if ( $order_post instanceof WC_Order )
                        {
                            $order_id = $order_post->get_id();
                            $order_post->update_status('wc-pending');
                            //$order_post->save();
                        }else{
                            $order_id = $order_post->ID;
                            $hidden_order = array(
                                'ID'           => $order_id,
                                'post_status'   => 'wc-pending'
                            );
                            wp_update_post( $hidden_order );
                        }
                        
                    }    
                }
                if($order_id)
                {
                    $arg['order_id'] = $order_id;
                }
                $order_meta = array();
                $order_meta[] = new WC_Meta_Data( array(
                    'key'   => 'sale_person_name',
                    'value' => $sale_person_name,
                ) );
                $order_meta[] = new WC_Meta_Data( array(
                    'key'   => 'pos_created_at',
                    'value' => $created_at,
                ) );
                $order_meta[] = new WC_Meta_Data( array(
                    'key'   => 'pos_created_at_time',
                    'value' => $created_at_time
                ) );

                

                $arg['cart_hash'] = md5($order_data_json);
                $arg['created_via'] = 'openpos';

                $order = wc_create_order($arg);
                
                if ( is_wp_error( $order ) ) {
                    $logger = wc_get_logger();
                    $context = array( 'source' => 'woocommerce-openpos-failure' );
                    $request_data = json_encode($_REQUEST);
                    $logger->debug( $request_data, $context );
                    $logger->debug(  print_r($order->get_error_messages(),true), $context );
                }
                
                $tmp_items = $order->get_items();    

                if(!empty($tmp_items) || $is_clear)
                {
                    $order = $op_woo_order->remove_order_items($order,true);
                    
                }

                do_action('op_add_order_before',$order,$order_parse_data,$session_data);

                $order->set_order_key( wc_generate_order_key() );

                $created_time_unix = round($created_at_time / 1000);
                $current_time_unix = time();
                if($created_time_unix > $current_time_unix)
                {
                    $created_time_unix = $current_time_unix;
                }
                $date_time = new WC_DateTime();
                $date_time->setTimestamp($created_time_unix);
                $order->set_date_created($date_time);
                //$order->set_date_created(current_time( 'timestamp', true ));

                $order->set_customer_note($note);
                if($order_id)
                {
                    if($use_hpos)
                    {
                        $order_meta[] = new WC_Meta_Data( array(
                            'key'   => '_pos_order_id',
                            'value' => $order_id,
                        ) );
                    }else{
                        update_post_meta($order->get_id(),'_pos_order_id',$order_id);
                    }
                    
                }
                //product list
                $total_items = count($items);
                $total_item_index = 0;
                $remain_cart_discount_amount = $final_discount_amount;
                
                foreach($items as $_itm)
                {
                    $_item = apply_filters('op_order_item_data_before',$_itm,$order_parse_data);
                    $item_seller_id = isset($_item['seller_id']) ? $_item['seller_id'] : $sale_person_id;
                    $item_local_id = isset($_item['id']) ? $_item['id'] : 0;
                    $disable_qty_change = isset($_item['disable_qty_change']) ? $_item['disable_qty_change'] : false;
                    $item = new WC_Order_Item_Product();
                    if($disable_qty_change && $item_local_id)
                    {
                        $_tmp_item_order_id = wc_get_order_id_by_order_item_id($item_local_id);
                        if($_tmp_item_order_id == $order_id)
                        {
                            $item->set_id($item_local_id);
                        }
                    }
                    
                    $item_options = isset($_item['options']) ? $_item['options'] : array();
                    $item_bundles = isset($_item['bundles']) ? $_item['bundles'] : array();
                    $item_variations = isset($_item['variations']) ? $_item['variations'] : array();

                    $item_note = (isset($_item['note']) && $_item['note'] != null && strlen($_item['note']) > 0 )  ? $_item['note'] : '';
                    $item_sub_name = (isset($_item['sub_name']) && $_item['sub_name'] != null && strlen($_item['sub_name']) > 0 )  ? $_item['sub_name'] : '';


                    if(isset($_item['final_discount_amount_incl_tax']))
                    {
                        $final_items_discount_tax +=  ($_item['final_discount_amount_incl_tax'] - $_item['final_discount_amount']);
                        $final_items_discount_amount += $_item['final_discount_amount'];
                    }

                    do_action('op_add_order_item_meta',$item,$_item);
                    $v_product_id = $_item['product_id'];
                   
                    if(isset($_item['product_id']) && $_item['product_id'])
                    {
                        $product_id = $_item['product_id'];
                        $post = get_post($product_id);
                        if(!$post)
                        {
                            $v_product_id = 0;
                        }
                        if($post && $post->post_type == 'product_variation')
                        {
                            $product_id = 0;
                            if( $post->post_parent)
                            {
                                $product_id = $post->post_parent;
                                $item->set_variation_id($_item['product_id']);

                                $variation_product = wc_get_product($_item['product_id']);

                                $_item_variations = $item_variations;

                                if(isset($_item_variations['options']) && !empty($_item_variations['options']))
                                {
                                    $_item_variation_options = $_item_variations['options'];

                                    foreach($_item_variation_options as $vcode => $v_val)
                                    {
                                        $v_name = str_replace( 'attribute_', '', $vcode );
                                        $label = isset($v_val['value_label']) ? $v_val['value_label'] : '';
                                        if($label)
                                        {
                                            $item->add_meta_data($v_name,$label);
                                        }
                                    }
                                }else{
                                    $v_attributes = $variation_product->get_variation_attributes();
                                    if($v_attributes && is_array($v_attributes))
                                    {
                                        foreach($v_attributes as $vcode => $v_val)
                                        {
                                            $v_name = str_replace( 'attribute_', '', $vcode );
                                            $item->add_meta_data($v_name,$v_val);
                                        }
                                    }
                                }


                            }
                        }

                        if($post && $product_id)
                        {
                            $item->set_product_id($product_id);

                        }


                    }
                    $item->set_name($_item['name']);
                    $item->set_quantity($_item['qty']);

                    $item_product = false;
                    if(isset($_item['product']))
                    {
                        $item_product = $_item['product'];
                    }

                    $final_price = $_item['final_price'];
                    $final_price_incl_tax = $_item['final_price_incl_tax'];
                    $item_total_tax = $_item['total_tax'];



                    //new
                    $_item_discount_amount = $_item['discount_amount'];

                    $_item_final_discount_amount = $_item['final_discount_amount'];


                    
                    

                    //$item->set_total_tax($item_total_tax);
                    $item_tax_amount = $final_price_incl_tax - $final_price;
                    
                    $item->set_props(
                        array(
                            'price' => $final_price,
                            'custom_price' => $final_price,
                            'discount_amount' => $_item_final_discount_amount,
                            'final_discount_amount' => $_item_final_discount_amount,
                            'discount_type' => $_item['discount_type'],
                            'total_tax' => $item_total_tax,
                            'tax_amount' => $item_tax_amount,
                        )
                    );

                    if($v_product_id)
                    {
                        //set current cost price
                        $current_post_price = $op_woo->get_cost_price($v_product_id);
                        if($current_post_price !== false)
                        {
                            $item->add_meta_data( '_op_cost_price', $current_post_price);
                        }
                    }
                    if($item_sub_name)
                    {
                        $item->add_meta_data( 'op_item_details', $item_sub_name);
                    }

                    $item->add_meta_data( '_op_local_id', $_item['id']);

                    $item->add_meta_data( '_op_seller_id', $item_seller_id);

                    if(!empty($item_options))
                    {
                        $item->add_meta_data( '_op_item_options', $item_options);
                    }
                    if(!empty($item_bundles))
                    {
                        $item->add_meta_data( '_op_item_bundles', $item_bundles);
                    }
                    if(!empty($item_variations))
                    {
                        $item->add_meta_data( '_op_item_variations', $item_variations);
                    }

                    

                    foreach($item_options as $op)
                    {
                        $meta_key = $op['title'];
                        $meta_value = implode(',',$op['value_id']);
                        if($op['cost'])
                        {
                            $meta_value .= ' ('.wc_price($op['cost']).')';
                        }

                        $item->add_meta_data($meta_key , $meta_value);
                    }
                    if($item_note)
                    {
                        $item->add_meta_data('note' , $item_note);
                    }

                    $item_sub_total = $_item['qty'] * $_item['final_price'];

                    $item->set_total_tax($item_total_tax);

                    $item_total_before_discount = $_item['final_price'] * (1 * $_item['qty']);
                    $item_total_tax_before_discount = ($_item['final_price_incl_tax'] - $_item['final_price']) * (1 * $_item['qty']);
                    $item_total = $_item['total'];

                    //coupon
                    
                    if($discount_code && isset($discount_codes[$discount_code]) && isset($discount_codes[$discount_code]['applied_items']) && isset($discount_codes[$discount_code]['applied_items'][$item_local_id]))
                    {
                        $item_discount_code_amount = 1 * $discount_codes[$discount_code]['applied_items'][$item_local_id];
                        if($item_discount_code_amount)
                        {
                            $item_total -= $item_discount_code_amount;
                        }
                    }

                    $item->set_subtotal($item_total_before_discount);
                    //$item->set_subtotal_tax($item_total_tax_before_discount);

                    $item->set_total($item_total);

                    if(isset($_item['subtotal']))
                    {
                        $item->set_subtotal($_item['subtotal']);
                    }

                    $item->add_meta_data( '_op_item_data_details', $_item);
                    // item tax
                    $item->save();
                    $item_taxes = array();
                    if(isset($_item['tax_details']) && !empty($_item['tax_details']))
                    {
                        foreach($_item['tax_details'] as $item_tax_detail)
                        {
                            $item_tax_class = '';
                            $tax_class_code = $item_tax_detail['code'];
                            $tmp_code = explode('_',$tax_class_code);
                            if(count($tmp_code) == 2)
                            {
                                $tmp_tax_class = $tmp_code[0];
                                if($tmp_tax_class != 'standard')
                                {
                                    $item_tax_class = $tmp_tax_class;
                                }
                            }
                           
                            $item->set_total_tax($item_tax_detail['total']);
                            

                            $item->set_tax_class($item_tax_class);
                            
                            if(isset($item_tax_detail['rate_id']) && $item_tax_detail['rate_id'] != '' && $item_tax_detail['rate_id'] >= 0 )
                            {
                                $item_taxes['total'][$item_tax_detail['rate_id']] = $item_tax_detail['total'];
                                $item_taxes['subtotal'][$item_tax_detail['rate_id']] = $item_tax_detail['total'];//$item_total_tax_before_discount;
                            }
                            
                          

                        }
                    }
                   
                    if(!empty($item_taxes))
                    {
                        $item->set_taxes($item_taxes);
                    }

                    //end item tax
                    $final_item = apply_filters('op_order_item_data',$item,$_item,$order);
                    
                    $order->add_item($final_item);
                    do_action('op_add_order_item_after',$order,$item,$_item,$session_data);
                    $total_item_index++;
                }
                //fee
                if(!empty($fee_items))
                {
                    foreach($fee_items as $_fee_item)
                    {

                        $fee_amount = 1 * $_fee_item['total'];
                        $fee_item = new WC_Order_Item_Fee();
                        $fee_item->set_name($_fee_item['name']);
                        $fee_item->set_total($fee_amount);
                        $fee_item->set_amount($fee_amount);

                        $item_taxes = array();
                        if(isset($_fee_item['tax_details']) && !empty($_fee_item['tax_details']))
                        {
                            foreach($_fee_item['tax_details'] as $item_tax_detail)
                            {
                                $item_tax_class = '';
                                $tax_class_code = $item_tax_detail['code'];
                                $tmp_code = explode('_',$tax_class_code);
                                if(count($tmp_code) == 2)
                                {
                                    $tmp_tax_class = $tmp_code[0];
                                    if($tmp_tax_class != 'standard')
                                    {
                                        $item_tax_class = $tmp_tax_class;
                                    }
                                }
                            
                                $fee_item->set_total_tax($item_tax_detail['total']);
                                

                                $fee_item->set_tax_class($item_tax_class);
                                
                                if(isset($item_tax_detail['rate_id']) && $item_tax_detail['rate_id'] != '' && $item_tax_detail['rate_id'] >= 0 )
                                {
                                    $item_taxes['total'][$item_tax_detail['rate_id']] = $item_tax_detail['total'];
                                    $item_taxes['subtotal'][$item_tax_detail['rate_id']] = $item_tax_detail['total'];
                                }
                            

                            }
                        }
                        if(!empty($item_taxes))
                        {
                            $fee_item->set_taxes($item_taxes);
                        }

                        
                        #$fee_item->set_total_tax(0);
                        
                        $fee_item->add_meta_data( '_op_local_id',$_fee_item['id']);
                        $fee_item->add_meta_data( '_pos_item_type','cart_fee');
                        $fee_item->add_meta_data( '_op_item_data_details', $_fee_item);
                        
                        $order->add_item($fee_item);
                    }
                }
                //coupon
                $coupons = array();
                if($discount_code)
                {
                    foreach($discount_codes as $_discount_code)
                    {
                        $_discount_code_total = $_discount_code['total'];
                        $_discount_code_tax = $_discount_code['tax'];
                        $coupon_item = new WC_Order_Item_Coupon();
                        $coupon_item->set_code($_discount_code['code']);
                        $coupon_item->set_discount($_discount_code_total);
                        $coupon_item->set_discount_tax($_discount_code_tax);
                        $order->add_item($coupon_item);
                    }
                   

                    $final_items_discount_amount += $discount_code_excl_tax;
                    $final_items_discount_tax += $discount_code_tax_amount;
                    /*
                    $coupon = array(
                        'code' => $discount_code,
                        'amount' => $discount_code_excl_tax,
                        'tax_amount' => $discount_code_tax_amount,
                        'applied' => array()
                    );
                    $coupons[] = $coupon;
                    foreach($coupons as $c)
                    {
                        $coupon_code = $c['code'];
                        $coupon_object = new WC_Coupon();
                        $coupon_object->set_virtual( true );
                        $coupon_object->set_code( $coupon_code );
                        $coupon_object->set_discount_type( 'fixed_cart' );
                        $coupon_object->set_amount($c['amount']);
                        //$coupon_object->set_tax($c['amount']);
                        $order->apply_coupon($coupon_object);
                    }*/
                    // $discounts = new WC_Discounts();
                    // $discount_items = array($coupon_item);

                    // foreach ( $discount_items as $coupon_item ) {
                    //     $coupon_code = $coupon_item->get_code();
                    

                    //     // If we do not have a coupon ID (was it virtual? has it been deleted?) we must create a temporary coupon using what data we have stored during checkout.
                    //     $coupon_object = new WC_Coupon();
                    //     $coupon_object->set_props( (array) $coupon_item->get_meta( 'coupon_data', true ) );
                    //     $coupon_object->set_code( $coupon_code );
                    //     $coupon_object->set_virtual( true );

                    //     // If there is no coupon amount (maybe dynamic?), set it to the given **discount** amount so the coupon's same value is applied.
                    //     if ( ! $coupon_object->get_amount() ) {
                    //         $coupon_object->set_amount( $coupon_item->get_discount() + $coupon_item->get_discount_tax() );
                    //         $coupon_object->set_discount_type( 'fixed_cart' );
                    //     }
                    //     if ( $coupon_object ) {
                    //         $discounts->apply_coupon( $coupon_object, false );
                    //     }
                    // }
                    // $order->set_item_discount_amounts($discounts);
                    
                    do_action('op_add_order_coupon_after',$order,$order_parse_data,$discount_code,$discount_code_amount);

                }
                
                //cart discount item as fee


                if($discount_final_amount > 0)
                {
                    $cart_discount_item = new WC_Order_Item_Fee();

                    $cart_discount_item->set_name(__('POS Cart Discount','openpos'));
                    $cart_discount_item->set_amount(0 - $discount_excl_tax);
                    $cart_discount_item_taxes_total = array();
                    $cart_discount_item_taxes_subtotal = array();
                    foreach($discount_tax_details as $discount_tax_detail)
                    {
                        $item_tax_class = '';
                        $tax_class_code = $discount_tax_detail['code'];
                        $tmp_code = explode('_',$tax_class_code);
                        if(count($tmp_code) == 2)
                        {
                            $tmp_tax_class = $tmp_code[0];
                            if($tmp_tax_class != 'standard')
                            {
                                $item_tax_class = $tmp_tax_class;
                            }
                        }

                        $cart_discount_item->set_total_tax( 0 - $discount_tax_detail['total']);
                        $cart_discount_item->set_tax_class($item_tax_class);

                        $cart_discount_item_taxes_total[$discount_tax_detail['rate_id']] = (0 - $discount_tax_detail['total']);
                        $cart_discount_item_taxes_subtotal[$discount_tax_detail['rate_id']] = (0 - $discount_tax_detail['total']);

                    }
                    $cart_discount_item->set_taxes(
                        array(
                            'total'    => $cart_discount_item_taxes_total,
                            'subtotal' => $cart_discount_item_taxes_subtotal,
                        )
                    );
                    $cart_discount_item->set_total(0 - $discount_excl_tax);
                    $cart_discount_item->add_meta_data('_pos_item_type','cart_discount');
                   
                    $order->add_item($cart_discount_item);
                }
                //end cart discount item

                

                if($final_items_discount_amount)
                {
                    $order->set_discount_total(1 * $final_items_discount_amount);
                    $order->set_discount_tax(1 * $final_items_discount_tax);
                }else{
                    $order->set_discount_total(0);
                    $order->set_discount_tax(0);
                }
                //billing information
                if($customer_firstname)
                {
                    $order->set_billing_first_name($customer_firstname);
                }
                if($customer_lastname)
                {
                    $order->set_billing_last_name($customer_lastname);
                }
                if(isset($customer['company']) && $customer['company'])
                {
                    $order->set_billing_company($customer['company']);
                }
                if(isset($customer['address']) && $customer['address'])
                {
                    $order->set_billing_address_1($customer['address']);
                }
                if(isset($customer['email']) && $customer['email'])
                {
                    $order->set_billing_email($customer['email']);
                }
                if(isset($customer['phone']) && $customer['phone'])
                {
                    $order->set_billing_phone($customer['phone']);
                }

                if(isset($customer['address_2']) && $customer['address_2'] != null)
                {
                    $order->set_billing_address_2($customer['address_2']);
                }
                if(isset($customer['state']) && $customer['state'] != null)
                {
                    $order->set_billing_state($customer['state']);
                }
                if(isset($customer['city']) && $customer['city'] != null)
                {
                    $order->set_billing_city($customer['city']);
                }
                if(isset($customer['postcode']) && $customer['postcode'] != null)
                {
                    $order->set_billing_postcode($customer['postcode']);
                }
                // country
                $billing_country = '';
                if(isset($customer['country']) && $customer['country'] != null)
                {
                    $billing_country = $customer['country'];

                }
                if(!$billing_country)
                {
                    $billing_country = $default_country;
                }
                if($billing_country)
                {
                    $order->set_billing_country($billing_country);
                }
                //shipping


                if($has_shipping)
                {
                    $order->set_shipping_first_name($shipping_first_name);
                    $order->set_shipping_last_name($shipping_last_name);


                    if(isset($shipping_information['address']))
                    {
                        $order->set_shipping_address_1($shipping_information['address']);
                    }

                    if(isset($shipping_information['company']) && $shipping_information['company'])
                    {
                        $order->set_shipping_company($shipping_information['company']);
                    }

                    if(isset($shipping_information['address_2']))
                    {
                        $order->set_shipping_address_2($shipping_information['address_2']);
                    }
                    if(isset($shipping_information['city']))
                    {
                        $order->set_shipping_city($shipping_information['city']);
                    }
                    // default contry
                    $shipping_country = '';
                    if(isset($shipping_information['country']) && $shipping_information['country'] != null)
                    {
                        $shipping_country = $shipping_information['country'];

                    }
                    if(!$shipping_country)
                    {
                        $shipping_country = $default_country;
                    }
                    if($shipping_country)
                    {
                        $order->set_shipping_country($shipping_country);
                    }
                    //end default country

                    if(isset($shipping_information['state']))
                    {
                        $order->set_shipping_state($shipping_information['state']);
                    }
                    if(isset($shipping_information['postcode']))
                    {
                        $order->set_shipping_postcode($shipping_information['postcode']);
                    }

                    if(isset($shipping_information['phone']))
                    {
                        $order_meta[] = new WC_Meta_Data( array(
                            'key'   => 'shipping_phone',
                            'value' => $shipping_information['phone'],
                        ) );
                        if($use_hpos)
                        {
                            $order_meta[] = new WC_Meta_Data( array(
                                'key'   => '_pos_shipping_phone',
                                'value' => $shipping_information['phone'],
                            ) );  
                        }else{
                            update_post_meta($order->get_id(),'_pos_shipping_phone',$shipping_information['phone']);
                        }  
                        

                        $order->set_shipping_phone($shipping_information['phone']);
                    }
                    //shipping item
                    $shipping_item = new WC_Order_Item_Shipping();
                    $shipping_note = isset($shipping_information['note']) ? $shipping_information['note'] : '';
                    if(isset($shipping_information['shipping_method']) && $shipping_information['shipping_method'])
                    {
                        $shipping_method_details = isset($shipping_information['shipping_method_details']) ? $shipping_information['shipping_method_details'] : array();
                        $shipping_tax_details = isset($shipping_information['tax_details']) ? $shipping_information['tax_details'] : array();
                        if(!empty($shipping_method_details) && isset($shipping_method_details['code']))
                        {
                            $title = $shipping_method_details['label'];
                            $code = $shipping_method_details['code'];
                            $tmp = explode(':',$code);
                            if(count($tmp) == 2){
                                $tmp_code = $tmp[0];
                                $tmp_instance_id = $tmp[1];
                                $shipping_item->set_method_id($tmp_code);
                                $shipping_item->set_instance_id($tmp_instance_id);
                            }else{

                                $shipping_item->set_method_id($code);
                            }
                            $shipping_item->set_method_title($title);
                        }else{
                            $order_shipping = $op_woo->get_shipping_method_by_code($shipping_information['shipping_method']);
                            $title = $order_shipping['title'];
                            $code = $order_shipping['code'];

                            $shipping_item->set_method_title($title);

                            $shipping_item->set_method_id($code);
                        }
                        $shipping_item->set_total($shipping_cost - $shipping_tax);
                        if(!empty($shipping_tax_details))
                        {
                            
                            $shipping_taxes_total = array();
                            foreach($shipping_tax_details as $shipping_tax_data)
                            {
                                
                                $shipping_taxes_total[$shipping_tax_data['rate_id']] = $shipping_tax_data['total'];
                            }
                            if(!empty($shipping_taxes_total))
                            {
                                $shipping_taxes = array(
                                    'total' => $shipping_taxes_total
                                );
                                $shipping_item->set_taxes($shipping_taxes);
                            }
                            
                        }
                        $order->add_item($shipping_item);
                    }else{
                        $shipping_item->set_method_title(__('POS Customer Pickup','openpos'));
                        $shipping_item->set_total($shipping_cost - $shipping_tax);
                        $shipping_item->set_method_id('openpos');
                        $order->add_item($shipping_item);
                    }

                    if($shipping_note)
                    {
                        $order->set_customer_note($shipping_note);
                    }
                }else{
                    $use_store_address =  apply_filters('op_order_use_store_shipping_address',true,$order_parse_data);
                    if($use_store_address)
                    {
                        $store_address = $op_warehouse->getStorePickupAddress($login_warehouse_id);
                        $order->set_shipping_first_name($shipping_first_name);
                        $order->set_shipping_last_name($shipping_last_name);
    
    
                        if(isset($store_address['address_1']))
                        {
                            $order->set_shipping_address_1($store_address['address_1']);
                        }
    
                        if(isset($store_address['address_2']))
                        {
                            $order->set_shipping_address_2($store_address['address_2']);
                        }
                        if(isset($store_address['city']))
                        {
                            $order->set_shipping_city($store_address['city']);
                        }
    
                        if(isset($store_address['state']))
                        {
                            $order->set_shipping_state($store_address['state']);
                        }
                        if(isset($store_address['postcode']))
                        {
                            $order->set_shipping_postcode($store_address['postcode']);
                        }
                        if(isset($store_address['country']))
                        {
                            $order->set_shipping_country($store_address['country']);
                        }
                    }
                    

                }
                
                $order->set_shipping_total($shipping_cost - $shipping_tax);
                $order->set_shipping_tax($shipping_tax);

                //$grand_total += $shipping_tax;
                //tax
                //$tax_amount -= $discount_tax_amount + $discount_code_tax_amount;

                foreach($fee_tax_details as $fee_tax)
                {
                    $tax_amount += $fee_tax['total'];
                    $has_tax = false;
                    foreach($tax_details as $index => $t)
                    {
                        if($fee_tax['code'] == $t['code'])
                        {
                            $tax_details[$index]['total'] += $fee_tax['total'];
                            $has_tax = true;
                        }
                    }
                    if(!$has_tax)
                    {
                        $tax_details[] = $fee_tax;
                    }
                }
                
                if($tax_amount >= 0 && !$is_product_tax)
                {

                    if(empty($tax_details))
                    {
                        $tax_item = new WC_Order_Item_Tax();
                        $label =  __('Tax on POS','openpos');

                        $tax_rates = $op_woo->getTaxRates($setting_tax_class);
                        if($setting_tax_rate_id && isset($tax_rates[$setting_tax_rate_id]))
                        {
                            $setting_tax_rate = $tax_rates[$setting_tax_rate_id];
                            if(isset($setting_tax_rate['label']))
                            {
                                $label = $setting_tax_rate['label'];
                            }
                        }
                        $tax_item->set_label($label);
                        $tax_item->set_name( $label);
                        $tax_item->set_tax_total($tax_amount);
                        if($setting_tax_rate_id)
                        {
                            $tax_item->set_rate_id($setting_tax_rate_id);
                        }
                        if($tax_amount > 0)
                        {
                            $order->add_item($tax_item);
                        }

                    }else{
                        
                        foreach($tax_details as $tax_detail)
                        {
                            $tax_item = new WC_Order_Item_Tax();
                            $label = $tax_detail['label'];
                            $setting_tax_rate_id = $tax_detail['rate_id'];
                           
                            
                            if($setting_tax_class == 'op_productax' || $setting_tax_class == 'op_notax' )
                            {
                                $tax_item->set_tax_total($tax_detail['total']);
                            }else{
                                $tax_item->set_tax_total($tax_amount);
                            }
                            if($setting_tax_rate_id)
                            {
                                $tax_item->set_rate_id($setting_tax_rate_id);
                                $tax_item->set_rate($setting_tax_rate_id);
                                $tax_item->set_rate_code($tax_detail['code']);
                            }
                            $tax_item->set_compound(false);
                            $tax_item->set_shipping_tax_total(0);

                            $tax_item->set_label($label);
                            $tax_item->set_name(strtoupper( $label.'-'.$setting_tax_rate_id));

                            $order->add_item($tax_item);

                        }
                    }
                }
                $order->set_cart_tax($tax_amount);
                // payment method

                $payment_method_code = 'pos_payment';
                $payment_method_title = __('Pay On POS','openpos');

                if(count($payment_method) > 1)
                {

                    $payment_method_code = 'pos_multi';
                    $payment_method_title = __('Multi Methods','openpos');
                    $sub_str = array();
                    foreach($payment_method as $p)
                    {
                        $paid = wc_price($p['paid']);
                        $sub_str[] = implode(': ',array($p['name'],strip_tags($paid)));
                    }
                    if(!empty($sub_str))
                    {
                        $payment_method_title .= '( '.implode(' & ',$sub_str).' ) ';
                    }
                }else{
                    $method = end($payment_method);
                    if(is_array($method) && $method['code'])
                    {
                        $payment_method_code = $method['code'];
                        $payment_method_title = $method['name'];
                    }
                }
                if(!$is_online_payment)
                {
                    $order->set_payment_method($payment_method_code);
                    $order->set_payment_method_title($payment_method_title);
                    

                }

                // order total

                if(isset($order_parse_data['addition_information']))
                {
                    update_post_meta($order->get_id(),'_op_order_addition_information',$order_parse_data['addition_information']);
                }   
                
                if($use_hpos)
                {
                    $order_meta[] = new WC_Meta_Data( array(
                        'key'   => '_op_order_total_paid',
                        'value' => $total_paid,
                    ) );
                    $order_meta[] = new WC_Meta_Data( array(
                        'key'   => '_op_order',
                        'value' => $order_parse_data,
                    ) );
                    $order_meta[] = new WC_Meta_Data( array(
                        'key'   => '_op_order_source',
                        'value' => 'openpos',
                    ) );
                    $order_meta[] = new WC_Meta_Data( array(
                        'key'   => '_wc_order_attribution_source_type',
                        'value' => 'typein',
                    ) );
                    
                    
                    
                    $order_meta[] = new WC_Meta_Data( array(
                        'key'   => '_op_local_id',
                        'value' => $order_local_id,
                    ) );
                    $order_meta[] = new WC_Meta_Data( array(
                        'key'   => '_op_sale_by_person_id',
                        'value' => $sale_person_id,
                    ) );
                    $order_meta[] = new WC_Meta_Data( array(
                        'key'   => '_op_order_number',
                        'value' => $new_order_number,
                    ) );
                    $order_meta[] = new WC_Meta_Data( array(
                        'key'   => '_op_wc_custom_order_number_formatted',
                        'value' => $order_number_format,
                    ) );
                    $order_meta[] = new WC_Meta_Data( array(
                        'key'   => '_op_point_discount',
                        'value' => $point_discount,
                    ) );
                    $order_meta[] = new WC_Meta_Data( array(
                        'key'   => '_op_sale_by_cashier_id',
                        'value' => $cashier_id,
                    ) );

                    $warehouse_meta_key = $op_warehouse->get_order_meta_key();
                    $cashdrawer_meta_key = $op_register->get_order_meta_key();

                    $order_meta[] = new WC_Meta_Data( array(
                        'key'   => '_op_sale_by_store_id',
                        'value' => $store_id,
                    ) );
                    $order_meta[] = new WC_Meta_Data( array(
                        'key'   => '_op_email_receipt',
                        'value' => $email_receipt,
                    ) );
                    $order_meta[] = new WC_Meta_Data( array(
                        'key'   => $warehouse_meta_key,
                        'value' => $login_warehouse_id,
                    ) );
                    $order_meta[] = new WC_Meta_Data( array(
                        'key'   => $cashdrawer_meta_key,
                        'value' => $login_cashdrawer_id,
                    ) );

                    
                    if(!$is_online_payment) {
                        $order_meta[] = new WC_Meta_Data( array(
                            'key'   => '_op_payment_methods',
                            'value' => $payment_method,
                        ) );
                        
                    }
                    
                    if($session_id )
                    {
                        $order_meta[] = new WC_Meta_Data( array(
                            'key'   => '_op_session_id',
                            'value' => $session_id,
                        ) );
                        
                    }
                    if($order_number_format)
                    {
                        $order_number_format = trim($order_number_format,'#');
                        $order_number_format = trim( $order_number_format);

                        $order_meta[] = new WC_Meta_Data( array(
                            'key'   => '_op_order_number_format',
                            'value' => $order_number_format,
                        ) );
                    
                    }
                    $order_meta[] = new WC_Meta_Data( array(
                        'key'   => '_op_source_type',
                        'value' => $source_type,
                    ) );
                    $order_meta[] = new WC_Meta_Data( array(
                        'key'   => '_op_source',
                        'value' => $source,
                    ) );
                    
                }else{
                    update_post_meta($order->get_id(),'_op_order_total_paid',$total_paid);
                    update_post_meta($order->get_id(),'_op_order',$order_parse_data);
                    update_post_meta($order->get_id(),'_op_order_source','openpos');
                    update_post_meta($order->get_id(),'_op_local_id',$order_local_id);
                    update_post_meta($order->get_id(),'_op_sale_by_person_id',$sale_person_id);
                    update_post_meta($order->get_id(),'_op_order_number',$new_order_number);
                    update_post_meta($order->get_id(),'_op_wc_custom_order_number_formatted',$order_number_format);
                    if(!$is_online_payment) {
                        update_post_meta($order->get_id(), '_op_payment_methods', $payment_method);
                    }
                    update_post_meta($order->get_id(), '_op_point_discount', $point_discount);
    
                    update_post_meta($order->get_id(),'_op_sale_by_cashier_id',$cashier_id);
    
    
                    $warehouse_meta_key = $op_warehouse->get_order_meta_key();
                    $cashdrawer_meta_key = $op_register->get_order_meta_key();
    
                    update_post_meta($order->get_id(),$warehouse_meta_key,$login_warehouse_id);
                    update_post_meta($order->get_id(),$cashdrawer_meta_key,$login_cashdrawer_id);
                    update_post_meta($order->get_id(),'_op_sale_by_store_id',$store_id);
                    update_post_meta($order->get_id(),'_op_email_receipt',$email_receipt);
                    update_post_meta($order->get_id(),'_op_source_type',$source_type);
                    update_post_meta($order->get_id(),'_op_source',$source);
    
    
                    
                    if($session_id )
                    {
                        update_post_meta($order->get_id(),'_op_session_id',$session_id);
                    }
                    if($order_number_format)
                    {
                        $order_number_format = trim($order_number_format,'#');
                        $order_number_format = trim( $order_number_format);
                        update_post_meta($order->get_id(),'_op_order_number_format',$order_number_format);
                       
                    }
                }

                if($use_hpos)
                {
                    foreach ( $order_meta as $meta ) {
                       
                        $order->update_meta_data( $meta->key, $meta->value );
                    }
                }else{
                    $order->set_meta_data($order_meta);
                }
                
                $order->set_total( $grand_total );
                if($note)
                {
                    $order->add_order_note($note);
                }

                do_action('op_add_order_before_change_payment',$order,$order_parse_data);

                if($allow_laybuy == 'yes')
                {
                    $laybuy_order_status = $this->_core->getPosLayBuyOrderStatus();
                   
                    $order->set_status($laybuy_order_status , __('Create via OpenPos', 'openpos'));
                    
                    if($use_hpos)
                    {
                        $order->update_meta_data('_op_allow_laybuy',$allow_laybuy);
                    }else{
                        update_post_meta($order->get_id(),'_op_allow_laybuy',$allow_laybuy);
                    }
                    if($customer_total_paid < $total_paid)
                    {
                        $op_remain_amount = ($total_paid - $customer_total_paid);
                        $op_register->addDebitBalance($login_cashdrawer_id, $op_remain_amount);
                        if($use_hpos)
                        {
                            $order->update_meta_data('_op_remain_paid',$op_remain_amount);
                        }else{
                            update_post_meta($order->get_id(),'_op_remain_paid',$op_remain_amount); //remain amount
                        }
                    }
                }else{
                    if(!$is_online_payment) {
                        $order->save();
                        $order->payment_complete();
                       
                        $order->set_status($setting_order_status, __('Done via OpenPos', 'openpos'));
                    }
                }

                if($tip && isset($tip['total']) && $tip['total'])
                {
                   
                    if($use_hpos)
                    {
                        $order->update_meta_data('_op_tip',$tip);
                    }else{
                        update_post_meta($order->get_id(),'_op_tip',$tip);
                    }
                }

                if($order_number)
                {
                    if($use_hpos)
                    {
                        
                        $order->update_meta_data('_op_wc_custom_order_number',$order_number);
                    }else{
                        update_post_meta($order->get_id(),'_op_wc_custom_order_number',$order_number);
                    }
                }

                $order->save();
                

                if(!empty($transactions))
                {
                    foreach($transactions as $transaction){

                        $_transaction = apply_filters('op_order_transaction_data',$transaction,$order,$order_parse_data);
                        $this->add_transaction($_transaction);
                    }
                }

                
                $arg = array(
                    'ID' => $order->get_id(),
                    'post_author' => $cashier_id,
                );
                if($source_type == "order_exchange" && $source){
                    global $op_exchange;
                    $paren_order_number = isset($source['order_number']) ? $source['order_number'] : 0;
                    
                    if($paren_order_number)
                    {
                        if($use_hpos){
                            $order->update_meta_data('_op_source_order_number',$paren_order_number);
                            $order->save();
                        }else{
                            update_post_meta($order->get_id(),'_op_source_order_number',$paren_order_number);
                        }
                    }
                    $parent_order_id = $op_woo_order->get_order_id_from_number($paren_order_number);
                    if($parent_order_id)
                    {
                        $arg['post_parent'] = $parent_order_id;

                        $op_exchange->saveNewExchange($parent_order_id,$order,$session_data);
                    }
                }
                //take look when use hpos
                if(!$use_hpos)
                {
                    wp_update_post( $arg );
                }
                
                do_action('op_add_order_after',$order,$order_parse_data);
                
                $result['data'] = $op_woo->formatWooOrder($order->get_id());
                
            }else{

                $post_order = end($orders);
                $result['data'] = $op_woo->formatWooOrder($post_order->ID);
            }
            

            $result['status'] = 1;
            //shop_order

            if($source_type == "hold" && $source){
                $cart_id = isset($source['order_id']) ? $source['order_id'] : 0;
                $op_woo_order->remove_draft_cart($cart_id);
            }
            //add send email
            $allow_send_op_email_receipt = apply_filters('op_allow_send_op_email_receipt',$email_receipt);
            if($allow_send_op_email_receipt == 'yes')
            {
                $email_result = $op_receipt->send_receipt($customer_email,$order_parse_data,$login_cashdrawer_id);
                if($email_result['status'] == 0)
                {
                    $result['message'].= $email_result['message'] ;
                }
            }

            if($customer_id && $has_shipping)
            {
                // update shipping information
                $op_woo->_update_customer_shipping($customer_id,$shipping_information);
            }

            do_action('op_add_order_final_after',$result['data']);

        }catch (Exception $e)
        {
            $result['status'] = 0;
            $result['message'] = $e->getMessage();
        }
        
        return $result;
    }

    function get_order_note(){
        $result = array('status' => 0, 'message' => '','data' => array(
            'notes' => array(),
            'allow_status' => array(),
            'order_status' => ''
        ));
        try{
            global $op_woo_order;
            
            $order_number_old = intval($_REQUEST['order_number']);
            $order_id_old = intval($_REQUEST['order_id']);
            $order_local_id_old = intval($_REQUEST['local_order_id']);
            $order_number = $op_woo_order->get_order_id_from_number($order_number_old);
           
            $order = wc_get_order($order_number);
            if(!$order)
            {
                $order = wc_get_order($order_number_old);
            }
            if(!$order)
            {
                $order_number = $op_woo_order->get_order_id_from_local_id($order_number_old);
                $order = wc_get_order($order_number);
            }

            if(!$order)
            {
                $order_number = $op_woo_order->get_order_id_from_local_id($order_local_id_old);
                $order = wc_get_order($order_number);
            }
            if(!$order)
            {
                $order_number = $op_woo_order->get_order_id_from_local_id($order_id_old);
                $order = wc_get_order($order_number);
            }

            if($order)
            {
                $notes = $op_woo_order->getOrderNotes($order->get_id());
                $result['data']['notes'] = $notes;
                $result['data']['order_status'] = $order->get_status();
               

                $order_note_allow_status = array();

                //$order_note_allow_status[] = array('code' => 'completed','label' => 'Completed');
                
                $result['data']['allow_status'] =   $tmp = apply_filters('op_order_note_allow_status',$order_note_allow_status,$order,$this);
                
                $result['status'] = 1;
            }else{
                throw new Exception(__('Order not found.','openpos'));
            }
           


        }catch (Exception $e)
        {
            $result['status'] = 0;
            $result['message'] = $e->getMessage();
        }
        return $result;
    }
    function save_order_note(){
        $result = array('status' => 0, 'message' => '','data' => array());
        try{
            global $op_woo_order;
            
            $order_number_old = intval($_REQUEST['order_number']);
            $order_id_old = intval($_REQUEST['order_id']);
            $order_local_id_old = intval($_REQUEST['local_order_id']);
            $order_status = isset($_REQUEST['order_status']) ? $_REQUEST['order_status'] : '';
            $order_number = $op_woo_order->get_order_id_from_number($order_number_old);
           
            $order = wc_get_order($order_number);
            if(!$order)
            {
                $order = wc_get_order($order_number_old);
            }
            if(!$order)
            {
                $order_number = $op_woo_order->get_order_id_from_local_id($order_number_old);
                $order = wc_get_order($order_number);
            }

            if(!$order)
            {
                $order_number = $op_woo_order->get_order_id_from_local_id($order_local_id_old);
                $order = wc_get_order($order_number);
            }
            if(!$order)
            {
                $order_number = $op_woo_order->get_order_id_from_local_id($order_id_old);
                $order = wc_get_order($order_number);
            }
            if($order)
            {
                $order_note = esc_textarea($_REQUEST['note']);
                if(!$order_status)
                {
                    $op_woo_order->addOrderNote($order->get_id(),$order_note);
                }else{
                    $op_woo_order->addOrderStatusNote($order->get_id(),$order_note,$order_status);
                }
                
                $result['status'] = 1;
                do_action('op_save_order_note_after',$order_note,$order_status,$order);
            }else{
                throw new Exception(__('Order not found.','openpos'));
            }


            
            
        }catch (Exception $e)
        {
            $result['status'] = 0;
            $result['message'] = $e->getMessage();
        }
        return $result;
    }

    function payment_order(){
        $result = array('status' => 0, 'message' => 'Unknown message','data' => array());
        try{
            global $op_woo;
            $order_result = $this->add_order();

            if($order_result['status'] == 1)
            {

                $order = wc_get_order($order_result['data']['order_id']);

                $order_parse_data = json_decode(stripslashes($_REQUEST['order']),true);

                $tmp_setting_order_status = $this->settings_api->get_option('pos_order_status','openpos_general');
                $setting_order_status =  apply_filters('op_new_payment_order_status',$tmp_setting_order_status,$order_parse_data);


                $payment_data = json_decode(stripslashes($_REQUEST['payment']),true);
                $amount = (float)$_REQUEST['amount'];
                if($amount > 0)
                {
                    $payment_method = isset($order_parse_data['payment_method']) ? $order_parse_data['payment_method'] : array();
                    $_method = array();
                    if(isset($payment_data['id']) && $payment_data['id']) // stripe payment
                    {
                        $source = $payment_data['id'];

                        
                        $payment_result = $op_woo->stripe_charge($amount * 100,$source);
                        if($payment_result['paid'] && $payment_result['status'] == 'succeeded')
                        {
                            $result['status'] = 1;
                            //update back payment of order
                            $_method = array(
                                'code' => 'stripe',
                                'name' => 'Credit Card (Stripe)',
                                'paid' => round($payment_result['amount'] / 100,2),
                                'ref' => $payment_result['id'],
                                'return' => 0,
                                'paid_point' => 0
                            );
                        }

                    }else{
                        $tmp_payment_method = array(
                            'code' => isset($payment_data['code']) ? $payment_data['code'] : '',
                            'name' => isset($payment_data['name']) ? $payment_data['name'] : '',
                            'paid' => $amount,
                            'ref' => '',
                            'return' => 0,
                            'paid_point' => 0
                        );
                       
                        if($tmp_payment_method['code'])
                        {
                            $_method = $tmp_payment_method;
                        }
                       
                    }
                    if(!empty($_method))
                    {
                        $payment_method[] = $_method;
                    }
                    $payment_method =  apply_filters('op_payment_order_payment_method',$payment_method,$order_parse_data,$amount,$payment_data);
                    $result =  apply_filters('op_payment_order_result',$result,$order_parse_data,$amount,$payment_data,$payment_method);
                    
                    if($result['status'] == 1 && !empty($payment_method))
                    {
                        // payment method
                        $payment_method_code = 'pos_payment';
                        $payment_method_title = __('Pay On POS','openpos');

                        if(count($payment_method) > 1)
                        {
                            $payment_method_code = 'pos_multi';
                            $payment_method_title = __('Multi Methods','openpos');
                        }else{
                            $method = end($payment_method);
                            if($method['code'])
                            {
                                $payment_method_code = $method['code'];
                                $payment_method_title = $method['name'];
                            }
                        }
                        $order->set_payment_method($payment_method_code);
                        $order->set_payment_method_title($payment_method_title);
                        // order total
                        $result['data']['payment_method'] = $payment_method;
                        if($this->_enable_hpos)
                        {
                            $order->update_meta_data('_op_payment_methods',$payment_method);
                        }else{
                            update_post_meta($order->get_id(), '_op_payment_methods', $payment_method);
                        }
                        
                        $order->payment_complete();
                        $order->set_status($setting_order_status, __('Done via OpenPos', 'openpos'));
                        $order->save();
                    }
                }
                $result['data']['order']  = $order_result['data'];
                $result['data']['payment']  = $payment_data;

                do_action('op_completed_payment_order_after',$result);
            }
        }catch (Exception $e)
        {
            $result['status'] = 0;
            $result['message'] = $e->getMessage();
        }
        $result = apply_filters('payment_order_result',$result);
        return $result;
    }

    function pending_payment_order(){
        $result = array('status' => 0, 'message' => '','data' => array());
        try{
            global $op_woo;

            $payment_parse_data = json_decode(stripslashes($_REQUEST['payment']),true);

            $order_result = $this->add_order(true);

            if($order_result['status'] == 1)
            {

                $order = wc_get_order($order_result['data']['order_id']);
                $payment_data = apply_filters('op_pending_payment_method_data',$payment_parse_data,$order_result);
                if(!empty($payment_data))
                {
                    if($this->_enable_hpos)
                    {
                        $order->update_meta_data('pos_payment',$payment_data);
                    }else{
                        add_post_meta($order_result['data']['order_id'],'pos_payment',$payment_data);
                    }
                    
                }

                do_action('op_pending_payment_order',$order,$payment_data);
                /*
                $order->set_status('on-hold');
                $order->set_payment_method($payment_parse_data['code']);
                $order->set_payment_method_title($payment_parse_data['name']);
                $order->save();
                */

                
                $result['status'] = 1;
                $checkout_url = $order->get_checkout_payment_url();
                $image_url = $this->_core->generateQRcode($checkout_url,100,100);
                $guide_html = '<div class="checkout-container">';
                $guide_html .= '<p style="text-align: center" id="payment-qr-image"><img  src="'.$image_url.'" /></p>';
                $guide_html .= '<p  style="text-align: center">Please checkout with scan QrCode or <a target="_blank" href="'.esc_url($checkout_url).'">click here</a> to continue checkout</p>';
                $guide_html .= '</div>';
                $result['data']['checkout_guide']  = apply_filters('op_order_checkout_guide_data',$guide_html,$order,$payment_data);
                $result['data']['order']  = $order_result['data'];

            }
        }catch (Exception $e)
        {
            $result['status'] = 0;
            $result['message'] = $e->getMessage();
        }
        $result = apply_filters('pending_payment_order_result',$result);
        return $result;
    }

    function payment_cc_order(){
        $result = array('status' => 0, 'message' => '','data' => array());
        try{
            $payment_parse_data = json_decode(stripslashes($_REQUEST['payment']),true);
            $payment_code = isset($payment_parse_data['code']) ? esc_attr($payment_parse_data['code']) : '';
            if($payment_code)
            {
                $result = apply_filters('op_payment_cc_order_'.$payment_code,$result);
            }

        }catch (Exception $e)
        {
            $result['status'] = 0;
            $result['message'] = $e->getMessage();
        }
        return $result;
    }

    function check_coupon(){
        $result = array('status' => 0, 'message' => '','data' => array());
        try{
            $request = apply_filters('op_check_coupon_request',$_REQUEST);
            if(class_exists('OP_Discounts'))
            {
                $wc_discount = new OP_Discounts();
                $code = trim($request['code']);
                $coupon = new WC_Coupon($code);
                $cart_data = json_decode(stripslashes($request['cart']),true);
                $applied_coupons = isset($request['applied_codes']) ? json_decode(stripslashes($request['applied_codes']),true) : array();

                if(!empty($applied_coupons)){
                    
                    foreach($applied_coupons as $_a)
                    {
                        $a_coupon = new WC_Coupon($_a['code']);
                        if($a_coupon->get_individual_use())
                        {
                            throw new Exception(sprintf(__('Coupon %s is invidual use','openpos'),$_a['code']));
                        }
                    }
                    
                    
                }

                $items = array();
                $_pf = new WC_Product_Factory();

                

                $grand_total = 0;
                if(!$grand_total)
                {
                    $grand_total = $cart_data['tax_amount'] + $cart_data['sub_total'] - $cart_data['discount_amount'] + $cart_data['shipping_cost'];
                }


                $item_discount_ids = array(); 
                $is_after_tax = false;
                if($this->settings_api->get_option('pos_cart_discount','openpos_general') == 'after_tax')
                {
                    $is_after_tax = true;
                }
                
                $discount_is_after_tax = apply_filters('op_check_coupon_request',$is_after_tax);
                foreach ( $cart_data['items'] as $key => $cart_item ) {
                    $item                = new stdClass();
                    $item->key           = $key;
                    $item->object        = $cart_item;
                    $item->product       = $_pf->get_product($cart_item['product']['id']);
                    $item->quantity      = $cart_item['qty'];
                    if($discount_is_after_tax)
                    {
                        $item->price         = wc_add_number_precision_deep( $cart_item['total_incl_tax']  );
                    }else{
                        $item->price         = wc_add_number_precision_deep( $cart_item['total']  );
                    }
                    
                    $items[ $key ] = $item;
                }

                $wc_discount->set_items($items);
                $valid = $wc_discount->is_coupon_valid($coupon,$cart_data);
                
                $after_items = $wc_discount->get_items();
                foreach($after_items as $_i)
                {
                    $item_obj = $_i->object;
                    $item_discount_ids[$_i->key] =  $item_obj['id'];
                }
                
                if($valid === true)
                {
                    
                    $result['valid'] = $valid;

                    
                    
                    $discount_type = $coupon->get_discount_type();

                    $amount = $wc_discount->apply_coupon($coupon);
                    $amount = wc_round_discount($amount/pow(10 , wc_get_price_decimals()),wc_get_price_decimals());



                    if($amount > $grand_total)
                    {
                        $amount = $grand_total;
                    }

                    if($amount < 0)
                    {
                        $msg = __('Coupon code has been expired','openpos');
                        throw new Exception($msg );
                    }
                    $code = $coupon->get_code();
                    $discount_amount_type = 'fixed';
                    if($discount_type == 'percent'){
                        $discount_amount_type = 'percent';
                    }
                    if($discount_type == 'fixed_product'){
                        $discount_amount_type = 'fixed_product';
                    }
                    $result['amount'] = $amount;
                    $result['data']['code'] = $code;
                    $result['data']['base_amount'] = $coupon->get_amount();
                    $result['data']['amount'] = $amount; // amount calculate base on cart
                    $result['data']['tax_amount'] = 0;
                    $result['data']['applied_items'] = array();
                    $result['data']['discount_type'] = $discount_type;
                    $result['data']['discount_amount'] = $coupon->get_amount();
                    $result['data']['description'] = $coupon->get_description();
                    $result['data']['discount_amount_type'] = $discount_amount_type;

                    $applied_items = $wc_discount->get_discounts();
                    $has_zero = false;
                    foreach($applied_items[$code] as $k => $_amount)
                    {
                        $id = $item_discount_ids[$k];
                        if($id)
                        {
                            $result['data']['applied_items'][$id] = 1 * $_amount;
                            if($_amount == 0)
                            {
                                $has_zero = true;
                            }
                        }
                        
                    }
                    if($has_zero)
                    {
                        $result['data']['discount_type'] = 'fixed';
                        $result['data']['discount_amount_type'] = 'fixed';
                        $result['data']['discount_amount'] = $amount;
                        $string_amnt = wc_price($amount);
                    }else{
                        $string_amnt = wc_price($coupon->get_amount());

                        if($discount_type == 'percent')
                        {
                            $string_amnt = number_format($coupon->get_amount()).'%';
                        }
                    }
                   
                    $result['message'] = sprintf(__("<b>%s</b> discount value: <b>%s</b>", 'openpos'), $code,$string_amnt);
                    $result['status'] = 1;

                }else{
                    $msg = $valid->get_error_message();

                    throw new Exception($msg );

                }
                do_action('op_check_coupon_after',$result);
            }

        }catch (Exception $e)
        {
            $result['status'] = 0;
            $result['message'] = $e->getMessage();
        }
        return  apply_filters('op_check_coupon_data',$result,$request);

    }
    function refund_order(){
        global $op_woo;
        global $op_woo_order;
        $result = array('status' => 0, 'message' => '','data' => array());
        try{
            $order_data = json_decode(stripslashes($_REQUEST['order']),true);
            $refund_amount = trim($_REQUEST['refund_amount']);
            $refund_reason = trim($_REQUEST['refund_reason']);
            $refund_qty = trim($_REQUEST['refund_qty']);
            $refund_type = isset($_REQUEST['refund_type']) ? trim($_REQUEST['refund_type']) : '';
            $session_data = $this->_getSessionData();
            $local_order_id = $order_data['id'];
            $system_order_id = isset($order_data['system_order_id']) ? $order_data['system_order_id'] : 0;
            if(!$system_order_id)
            {
                $order_id = $op_woo_order->get_order_id_from_local_id($local_order_id);
            }else{
                $order_id = $system_order_id;
            }
           

            if($order_id)
            {
                do_action('op_refund_order_before',$order_id);
                $order = wc_get_order( $order_id );
                $max_refund = wc_format_decimal( $order->get_total() - $order->get_total_refunded() );
                if($refund_amount > $max_refund)
                {
                    throw new Exception( __('Maximum refund amount ', 'openpos').$max_refund);
                }
                $refund_data = array(
                    'amount'     => $refund_amount,
                    'reason'     => $refund_reason,
                    'order_id'   => $order_id,
                    'line_items' => array(),
                    'restock_items' => $refund_qty
                );
                if($refund_type == 'stripe')
                {
                    if($this->_enable_hpos)
                    {
                        $_op_payment_methods = $order->get_meta('_op_payment_methods');
                    }else{
                        $_op_payment_methods = get_post_meta($order_id,'_op_payment_methods',true);
                    }
                    
                    $ref = '';
                    foreach($_op_payment_methods as $m)
                    {
                        if($m['code'] == 'stripe')
                        {
                            $ref = $m['ref'];
                        }
                    }
                    if($ref)
                    {
                        $stripe_refund = $op_woo->stripe_refund($ref);

                        if(isset($stripe_refund['status']) && $stripe_refund['status'] == 'succeeded')
                        {
                            $refund = wc_create_refund(
                                $refund_data
                            );
                            if($refund)
                            {
                                $refund->set_refunded_by($session_data['user_id']);
                                $refund->save();
                            }
                            $result['status'] = 1;
                        }

                    }

                }else{
                    $refund = wc_create_refund(
                        $refund_data
                    );
                    if($refund)
                    {
                        $refund->set_refunded_by($session_data['user_id']);
                        $refund->save();
                    }
                    $result['status'] = 1;
                }


                do_action('op_refund_order_after',$refund_data);
               
                $result = apply_filters('op_refund_order_data',$result,$refund_data);

            }else{
                throw new Exception( __('Order is not found', 'openpos'));
            }


        }catch (Exception $e)
        {
            $result['status'] = 0;
            $result['message'] = $e->getMessage();
        }
        return $result;
    }

    public function close_order(){
        global $op_woo;
        global $op_woo_order;
        global $_op_warehouse_id;
        $result = array('status' => 0, 'message' => '','data' => array());
        try{

            $session_data = $this->_getSessionData();

            $login_warehouse_id = isset($session_data['login_warehouse_id']) ? $session_data['login_warehouse_id'] : 0;
            $_op_warehouse_id = $login_warehouse_id;

            $order_data = json_decode(stripslashes($_REQUEST['order']),true);
            $order_number = $order_data['order_number'];
            if($order_number)
            {
                $order_number = $op_woo_order->get_order_id_from_number($order_number);
            }
            
            if((int)$order_number > 0)
            {
                $order = wc_get_order($order_number);
                $orders = array();
                if($order )
                {
                    $orders[] = $order;
                }

                if(count($orders) > 0)
                {

                    $_order = end($orders);
                    $formatted_order = $op_woo->formatWooOrder($_order->get_id());
                    $result['data'] = $formatted_order;
                    $payment_status = $formatted_order['payment_status'];
                    $status = '';
                    if($payment_status != 'paid')
                    {
                        $pos_order =  $order;
                        
                        
                        if($this->_enable_hpos)
                        {
                            $_order->update_meta_data('_op_order_close_by',$session_data['username']);
                        }else{
                            update_post_meta($_order->get_id(),'_op_order_close_by',$session_data['username']);
                        }
                        $status = apply_filters('op_woocommerce_cancelled_order_status','cancelled',$pos_order);
                        if($status == 'cancelled')
                        {
                            //$pos_order->close_order();
                        }
                        $pos_order->update_status($status ,__('Closed from POS','openpos'));

                        $result['status'] = 1;
                    }else{
                        $result['message'] = __('You can not close a order has been paid! Please complete order by click Check Payment button.', 'openpos');

                    }
                    do_action( 'op_woocommerce_cancelled_order', $_order->get_id(), $status );
                    
                }else{
                    throw new Exception( __('Order is not found', 'openpos'));
                }

                //$query = new WP_Query($args);


            }else{
                throw new Exception(__('Order is not found', 'openpos'));

            }
        }catch (Exception $e)
        {
            $result['status'] = 0;
            $result['message'] = $e->getMessage();
        }
        return $result;
    }
    public function check_order(){
        $result = array('status' => 0, 'message' => '','data' => array());
        global $op_woo_order;
        try{
            global $op_woo;
            $order_number = esc_textarea($_REQUEST['order_number']);
            if($order_number)
            {
                $order_number = $op_woo_order->get_order_id_from_number($order_number);
            }
            if((int)$order_number > 0)
            {

                $post_type = 'shop_order';
                $order = wc_get_order($order_number);
                $orders = array();
                if($order )
                {
                    $orders[] = $order;
                }

                if(count($orders) > 0)
                {

                    $_order = end($orders);
                    $formatted_order = $op_woo->formatWooOrder($_order->get_id());
                    $result['data'] = $formatted_order;
                    $payment_status = $formatted_order['payment_status'];
                    $result['message'] = __('Payment Status : ','openpos').$payment_status;
                    $result['status'] = 1;
                }else{
                    throw new Exception( __('Order is not found','openpos') );
                }

                //$query = new WP_Query($args);


            }else{
                throw new Exception(__('Order number too short','openpos'));

            }
        }catch (Exception $e)
        {
            $result['status'] = 0;
            $result['message'] = $e->getMessage();
        }
        return $result;
    }
    public function latest_order(){
        global $op_woo;
        $result = array('status' => 0, 'message' => '','data' => array('orders'=> array(),'total_page' => 0));
        try{
                $page = isset($_REQUEST['page']) ? (int)$_REQUEST['page'] : 1;
                $list_type = isset($_REQUEST['list_type']) ? $_REQUEST['list_type'] : 'latest';
                $post_type = 'shop_order';
                $today = getdate();
                $per_page = 15;
                $per_page = apply_filters('op_latest_order_per_page',$per_page);
                $post_statuses =  array(
                    'wc-processing',
                    'wc-pending',
                    'wc-completed',
                    'wc-refunded',
                    'wc-on-hold',
                );
                if($list_type == 'latest')
                {
                    $args = array(
                        'post_type' => $post_type,
                        'post_status' =>  $post_statuses,
                        'posts_per_page' => $per_page,
                        'paged' => $page
                    );
                }else{
                    $session_data = $this->_getSessionData();
                    $time = time();
                    if(isset($session_data['logged_time']) && $session_data['logged_time'])
                    {
                        $time = strtotime($session_data['logged_time']);
                    }
                    $wc_date = new WC_DateTime();
                    if ( get_option( 'timezone_string' ) ) {
                        $wc_date->setTimezone( new DateTimeZone( wc_timezone_string() ) );
                    } else {
                        $wc_date->set_utc_offset( wc_timezone_offset() );
                    }
                    $wc_date->setTimestamp($time);
                    $date_string = $wc_date->date("Y-m-d 00:00:00");
                    
                    $args = array(
                        'date_query' => array(
                            'after'  =>  $date_string
                        ),
                        'post_type' => $post_type,
                        'post_status' =>  $post_statuses,
                        'posts_per_page' => $per_page,
                        'paged' => $page
                    );
                }
                
                $args['order'] = 'DESC';
                $args['orderby'] = 'ID';

                $args = apply_filters('op_latest_order_query_args',$args);

                if($this->_enable_hpos)
                {
                    $args['_query_src'] = 'op_order_query';
                    $data_store = WC_Data_Store::load( 'order' );
                    $orders = $data_store->query( $args );
                    $total_order_numbs = 0;
                    
                    
                    
                    if($list_type == 'latest')
                    {
                        foreach($post_statuses as $status)
                        {
                            $total_order_numbs += $data_store->get_order_count($status);
                        }
                        $total_page  = ceil($total_order_numbs / $per_page);
                        if( ($total_page  * $per_page) < $total_order_numbs)
                        {
                            $total_page += 1;
                        }
                        $result['data']['total_page']  =   $total_page;
                    }
                     $orders = apply_filters('op_latest_orders_result',$orders,$list_type,null);

                }else{
                    $query = new WP_Query($args);
                    $orders = $query->get_posts();
                    if($list_type == 'latest')
                    {
                        $result['data']['total_page']  = $query->max_num_pages;
                    }
                    $orders = apply_filters('op_latest_orders_result',$orders,$list_type,$query);
                }
                


                
                
                if(count($orders) > 0)
                {
                    foreach($orders as $_order)
                    {

                        if($_order)
                        {
                            if($_order instanceof WC_Order )
                            {
                                $formatted_order = $op_woo->formatWooOrder($_order->get_id());
                             }else{
                                $formatted_order = $op_woo->formatWooOrder($_order->ID);
                             }
                            
                            if(!$formatted_order || empty($formatted_order))
                            {
                                continue;
                            }
                            $payment_status = $formatted_order['payment_status'];
                            $result['data']['orders'][] = $formatted_order;
                        }
                       
                    }
                    $result['status'] = 1;
                }else{
                    throw new Exception(__('No order found','openpos'));
                }

        }catch (Exception $e)
        {
            $result['status'] = 0;
            $result['message'] = $e->getMessage();
        }
        return $result;

    }
    public function search_order(){
        global $op_woo;
        global $op_woo_order;
        $result = array('status' => 0, 'message' => '','data' => array());
        try{
            $term = esc_textarea($_REQUEST['term']);
            
            if(strlen($term) > 1)
            {
                $term = trim($term);
                $post_type = 'shop_order';
                $term_id = $op_woo_order->get_order_id_from_number($term);
                $orders = array();
                $order = wc_get_order($term);
                if($order )
                {
                    $orders[] = $order;
                }
                $order = wc_get_order($term_id);
                if($order )
                {
                    $orders[] = $order;
                }
                if(!$order || !$term_id)
                {
                    $term_id = $op_woo_order->get_order_id_from_order_number_format($term);
                   
                    if($term_id)
                    {
                        $order = wc_get_order($term_id);
                        if($order )
                        {
                            $orders[] = $order;
                            
                        }
                    }
                    
                }
                
                $args = array(
                    'post_type' => $post_type,
                    'post_status' => 'any',
                    'meta_query' => array(
                        'relation' => 'OR',
                        array(
                            'key' => '_billing_email',
                            'value' => $term,
                            'compare' => 'like',
                        ),
                        array(
                            'key' => '_billing_phone',
                            'value' => $term,
                            'compare' => 'like',
                        ),
                        array(
                            'key' => '_billing_address_index',
                            'value' => $term,
                            'compare' => 'like',
                        ),


                    ),
                    'posts_per_page' => 10,
                    'orderby'   => array(
                        'date' =>'DESC',

                    )
                );
                if($this->_enable_hpos)
                {
                    $args = array(
						'type' => $post_type,
						'limit' => 10,
						'paginate' => 1,
						'page' => 1,
                    	'post_status' => 'any',
						'orderby' => 'date',
						'order' => 'DESC',
                        's' => $term,
                        'search_filter' => 'all'
					);
                }
                $args = apply_filters('op_search_order_args',$args);
                if($this->_enable_hpos)
                {
                    //$args['_query_src'] = 'op_order_query';
                    //$data_store = WC_Data_Store::load( 'order' );
                    //$_orders = $data_store->query( $args );
                   
                    
                    $_orders_result = wc_get_orders( $args );
                    $_orders = $_orders_result->orders;
                    foreach($_orders as $_order)
                    {
                        if($_order)
                        {
                            $orders[] = $_order; 
                        }
                    }
                    //$orders = array_merge($orders,$_orders);
                    
                }else{
                    $query = new WP_Query($args);
                    $_orders = $query->get_posts();
                    $orders = array_merge($orders,$_orders);
                }
                if(count($orders) > 0)
                {
                    $result_orders = array();
                    foreach($orders as $_order)
                    {
                        $order_id = 0;
                        if($_order instanceof WC_Order )
                        {
                            $order_id = $_order->get_id();
                            $formatted_order = $op_woo->formatWooOrder($_order->get_id());
                        }else{
                            
                            $order_id = $_order->ID;
                            
                            $formatted_order = $op_woo->formatWooOrder($_order->ID);
                        }
                        
                        if(!$formatted_order || empty($formatted_order))
                        {
                            continue;
                        }
                        $result_orders[$order_id] = $formatted_order;
                        
                    }
                    $result['data'] = array_values($result_orders);
                    $result['status'] = 1;
                }else{
                    throw new Exception(__('Order is not found', 'openpos'));
                }

                //$query = new WP_Query($args);


            }else{
                throw new Exception( __('Order number too short','openpos') );

            }
        }catch (Exception $e)
        {
            $result['status'] = 0;
            $result['message'] = $e->getMessage();
        }
        return $result;
    }

    public function pickup_order(){
        $result = array('status' => 0, 'message' => '','data' => array());
        try{
            $order_data = json_decode(stripslashes($_REQUEST['order']),true);
            $pickup_note = esc_textarea($_REQUEST['pickup_note']);
            $session_data = $this->_getSessionData();

            if($order_data['allow_pickup'])
            {
                $order_id = $order_data['system_order_id'];
                $order = wc_get_order($order_id);
                if($order)
                {
                    if(!$pickup_note)
                    {
                        $pickup_note = 'Pickup ';
                    }
                    $pickup_note.= ' By '.$session_data['username'];
                    $order->update_status('wc-completed',$pickup_note);
                    if($this->_enable_hpos)
                    {
                        $order->update_meta_data('_op_order_pickup_by',$session_data['username']);
                    }else{
                        update_post_meta($order->get_id(),'_op_order_pickup_by',$session_data['username']);
                    }
                    
                    $result['status'] = 1;
                    $result['data'] = $order->get_data();
                }else{
                    throw new Exception(__('Order is not found','openpos'));
                }
            }else{
                throw new Exception(__('Order do not allow pickup from store','openpos'));
            }

        }catch (Exception $e)
        {
            $result['status'] = 0;
            $result['message'] = $e->getMessage();
        }
        return $result;
    }

    public function draft_order(){

        global $_op_warehouse_id;
        global $op_warehouse;
        global $op_woo_order;

        $result = array('status' => 0, 'message' => '','data' => array());

        try{
            $session_data = $this->_getSessionData();
            $login_warehouse_id = isset($session_data['login_warehouse_id']) ? $session_data['login_warehouse_id'] : 0;
            $_op_warehouse_id = $login_warehouse_id;
            $order_data = json_decode(stripslashes($_REQUEST['order']),true);

            $order_parse_data = apply_filters('op_new_order_data',$order_data,$session_data);


            $order_number = isset($order_parse_data['order_number']) ? $order_parse_data['order_number'] : 0;
            $order_id = isset($order_parse_data['order_id']) ? $order_parse_data['order_id'] : 0;
            if(!$order_id && !$this->_enable_hpos)
            {
                $order_id = $op_woo_order->get_order_id_from_number($order_number);
            }

            do_action('op_add_draft_order_data_before',$order_parse_data,$session_data);

            $items = isset($order_parse_data['items']) ? $order_parse_data['items'] : array();
            if(empty($items))
            {
                throw new Exception('Item not found.');
            }

            
            $order = get_post($order_id);
            if($order)
            {
               
                do_action('op_add_draft_order_before',$order,$order_data,$session_data);
                $warehouse_meta_key = $op_warehouse->get_order_meta_key();
                
                update_post_meta($order->ID,'_op_cart_data',$order_data);
                update_post_meta($order->ID,$warehouse_meta_key,$login_warehouse_id);
                
                

                do_action('op_add_draft_order_after',$order,$order_data);
                $result['data'] = array('id' => $order->ID);
                $result['status'] = 1;
            }else{

                throw new  Exception(__('Cart Not Found', 'openpos'));
            }

            //shop_order
        }catch (Exception $e)
        {
            $result['status'] = 0;
            $result['message'] = $e->getMessage();
        }
        return $result;
    }

    public function get_draft_orders(){

        global $op_warehouse;
        $cart_type = 'openpos';
        $result = array('status' => 0, 'message' => '','data' => array(),'cart_type' => $cart_type);
        try{
            $session_data = $this->_getSessionData();
            $warehouse_meta_key = $op_warehouse->get_order_meta_key();
            $login_warehouse_id = isset($session_data['login_warehouse_id']) ? $session_data['login_warehouse_id'] : 0;
            $post_type = 'shop_order';

            $today = getdate();
            
            $args = array(
                'date_query' => array(
                    array(
                        'year'  => $today['year'],
                        'month' => $today['mon'],
                        'day'   => $today['mday'],
                    ),
                ),
                'post_type' => $post_type,
                'post_status' => 'auto-draft',
                'meta_query' => array(
                    array(
                        'key' => $warehouse_meta_key,
                        'value' => $login_warehouse_id,
                        'compare' => '=',
                    )
                ),
                'posts_per_page' => -1
            );
            $args = apply_filters('op_draft_orders_query_args',$args);

            
            $query = new WP_Query($args);
            $orders = $query->get_posts();
            
           
            
            $carts = array();
            if(count($orders) > 0)
            {
                foreach($orders as $_order)
                {
                    $order_number = $_order->ID;
                    $cart_data = get_post_meta($order_number,'_op_cart_data');
                    if($cart_data && is_array($cart_data) && !empty($cart_data))
                    {
                        $cart= end($cart_data);
                        $cart['allow_delete'] = 'yes';
                        $carts[] = $cart;
                    }else{
                        continue;
                    }
                }

                $result['data'] = $carts;
                $result['status'] = 1;
            }else{
                throw new Exception(__('No cart found','openpos'));
            }

            /*

            if(is_numeric($order_number))
            {
                //cashier cart
                $order = wc_get_order((int)$order_number);
                if(!$order)
                {
                    throw new Exception('Cart Not found');
                }
                $cart_data = get_post_meta($order->get_id(),'_op_cart_data');
                if($cart_data && is_array($cart_data) && !empty($cart_data))
                {
                    $result['data'] = $cart_data[0];
                    $result['status'] = 1;
                }
            }
            */


        }catch (Exception $e)
        {
            $result['status'] = 0;
            $result['message'] = $e->getMessage();
        }
        return $result;
    }
    public function load_draft_order(){
        global $op_woo_cart;
        global $op_woo_order;
        $cart_type = 'openpos';
        $result = array('status' => 0, 'message' => '','data' => array(),'cart_type' => $cart_type);
        try{
            $order_number = isset($_REQUEST['order_number']) ? trim($_REQUEST['order_number'],'#') : 0;
            if(!$order_number)
            {
                throw new Exception( __('Cart Not found','openpos') );
            }else{
                if(!$this->_enable_hpos)
                {
                    $order_number = $op_woo_order->get_order_id_from_number($order_number);
                }
            }
           
            if(is_numeric($order_number))
            {
                //cashier cart
                $order = get_post((int)$order_number);
                if(!$order)
                {
                    throw new Exception( __('Cart Not found','openpos') );
                }
                $cart_data = get_post_meta($order->ID,'_op_cart_data');
                if($cart_data && is_array($cart_data) && !empty($cart_data))
                {
                    $result['data'] = $cart_data[0];
                    $result['status'] = 1;
                }
            }else{
                // online cart
                $cart_type = 'website';
                $result['cart_type'] = $cart_type;
                $cart_data = $op_woo_cart->getCartBySessionId($order_number);
                if(!$cart_data || !is_array($cart_data) || empty($cart_data))
                {
                    throw new Exception( __('Cart Not found','openpos')   );
                }else{
                    $result['data'] = $cart_data;
                    $result['status'] = 1;
                }
            }


        }catch (Exception $e)
        {
            $result['status'] = 0;
            $result['message'] = $e->getMessage();
        }
        return $result;
    }
    public function delete_cart(){
        global $op_woo_cart;
        global $op_woo_order;
      
        $result = array('status' => 0, 'message' => '','data' => array());
        try{
            $cart_data = json_decode(stripslashes($_REQUEST['cart']),true);
            $order_number = isset($cart_data['order_number']) ? trim($cart_data['order_number'],'#') : 0;
            if(!$order_number)
            {
                throw new Exception('Cart Not found');
            }else{
                if(!$this->_enable_hpos)
                {
                    $order_number = $op_woo_order->get_order_id_from_number($order_number);
                }
            }
            //'post_status' => 'auto-draft',
            if(is_numeric($order_number) && get_post_status($order_number) == 'auto-draft')
            {
                do_action('delete_cart_before',$order_number,$cart_data);
                wp_trash_post( $order_number );
            }
            $result['status'] = 1;

        }catch (Exception $e)
        {
            $result['status'] = 0;
            $result['message'] = $e->getMessage();
        }
        return $result;
    }
    public function upload_desk(){
        global $op_table;
        $result = array('status' => 0, 'message' => 'Unknown message','data' => array());
       
        try{
            $tables = array();
            if(isset($_REQUEST['tables']))
            {
                $tables = json_decode(stripslashes($_REQUEST['tables']),true);
            }
            $is_force = isset($_REQUEST['fore_update']) && $_REQUEST['fore_update'] == 'yes' ? true : false;
            
            $session_data = $this->_getSessionData();
            $_tables = array();
            $_old_tables = array();
            if($session_data)
            {
                $warehouse_id = $session_data['login_warehouse_id'];
                   
                //save to table data
                foreach($tables as $table_id => $table)
                {
                    

                    $desk_type = $op_table->getDeskType($table);

                    //old data
                    if(strpos($table_id,'desk') !== false )
                    {
                        $_table_id = str_replace('desk-','',$table_id);
                        $_old_tables[$table_id] = $op_table->bill_screen_data($_table_id);
                    }
                    if(strpos($table_id,'takeaway') !== false )
                    {
                        $_table_id = str_replace('takeaway-','',$table_id);
                        $_old_tables[$table_id] = $op_table->bill_screen_data($_table_id,'takeaway');
                    }
                    //end old data

                    if($desk_type == 'guest_takeaway')
                    {
                        $table_id = 'takeaway-'.$table['desk']['id'];
                        if($op_table->isDeletedGuestTakeay($table_id,$warehouse_id))
                        {
                            
                            throw new Exception(__('Your order has been deleted. Please scan order QRcode and try again.','openpos'));
                        }
                    }
                    
                    if(strpos($table_id,'takeaway') !== false )
                    {
                        $op_table->removed_deleted_markup($warehouse_id,$table_id);
                    }

                    $_tables[$table_id] = $table;
                }
            }
            
            $result['data'] = $op_table->update_bill_screen($_tables,$is_force);
            
            do_action('op_upload_desk_after',$_tables,$op_table,$tables,$_old_tables);

            $result['status'] = 1;
        }catch (Exception $e)
        {
            $result['status'] = 0;
            $result['message'] = $e->getMessage();
        }
        return $result;
    }
    public function pull_desk(){
        global $op_table;
        $result = array('status' => 0, 'message' => '','data' => array());
        try{
            $desk_id = isset($_REQUEST['desk_id']) ? trim($_REQUEST['desk_id'],'#') : 0;
            if(!$desk_id)
            {
                throw new Exception('Desk Not found');
            }
            if(strpos($desk_id,'takeaway-') === 0)
            {
                $desk_data = $op_table->bill_screen_data($desk_id);
                $result_data = $desk_data;
            }else{
                
                $desk_data = $op_table->bill_screen_data($desk_id);
                $items = isset($desk_data['items']) ? $desk_data['items'] : array();
                $version = isset($desk_data['ver']) ? $desk_data['ver'] : 0;
                $system_ver = isset($desk_data['system_ver']) ? $desk_data['system_ver'] : 0;
                $start_time = isset($desk_data['start_time']) ? $desk_data['start_time'] : 0;
                $seller = isset($desk_data['seller']) ? $desk_data['seller'] : null;
                $fee_item = isset($desk_data['fee_item']) ? $desk_data['fee_item'] : null;
                $parent = isset($desk_data['parent']) ? $desk_data['parent'] : 0;
                $child_desks = isset($desk_data['child_desks']) ? $desk_data['child_desks'] : [];
                if(!isset($seller['id']))
                {
                    $seller = null;
                }
                $note = isset($desk_data['note']) ? $desk_data['note'] : '';
                $customer =  isset($desk_data['customer']) ? $desk_data['customer'] : null;
                
                $result_data = array(
                    'items' => $items,
                    'version'  => $version,
                    'system_ver'  => $system_ver,
                    'start_time'  => $start_time,
                    'parent'  => $parent,
                    'child_desks'  => $child_desks,
                    'seller' => $seller,
                    'note' => $note,
                    'customer' => $customer,
                    'fee_item' => $fee_item,
                );
                
            }
            
            $result['data'] = apply_filters('op_pull_desk_data',$result_data,$desk_data);
            $result['status'] = 1;
        }catch (Exception $e)
        {
            $result['status'] = 0;
            $result['message'] = $e->getMessage();
        }
        return $result;
    }
    public function pull_desks(){
        global $op_table;
        $result = array('status' => 0, 'message' => '','data' => array());
        try{
            //$desk_id = isset($_REQUEST['desk_id']) ? trim($_REQUEST['desk_id'],'#') : 0;
            $desk_ids =  isset($_REQUEST['desk_ids']) ?  json_decode(stripslashes($_REQUEST['desk_ids']),true) : array();
            if(empty($desk_ids))
            {
                throw new Exception('Desk Not found');
            }
            foreach($desk_ids as $desk_id)
            {
                $desk_data = $op_table->bill_screen_data($desk_id);
                $items = isset($desk_data['items']) ? $desk_data['items'] : array();
                $version = isset($desk_data['ver']) ? $desk_data['ver'] : 0;
                $sys_version = isset($desk_data['system_ver']) ? $desk_data['system_ver'] : 0;
                $start_time = isset($desk_data['start_time']) ? $desk_data['start_time'] : 0;
                $parent = isset($desk_data['parent']) ? $desk_data['parent'] : 0;
                $child_desks = isset($desk_data['child_desks']) ? $desk_data['child_desks'] : [];
                $seller = isset($desk_data['seller']) ? $desk_data['seller'] : null;
                $fee_item = isset($desk_data['fee_item']) ? $desk_data['fee_item'] : null;
                if(!isset($seller['id']))
                {
                    $seller = null;
                }
                $note = isset($desk_data['note']) ? $desk_data['note'] : '';
                $customer =  isset($desk_data['customer']) ? $desk_data['customer'] : null;
                
                $result_data = array(
                    'items' => $items,
                    'version'  => $version,
                    'system_ver'  => $sys_version,
                    'start_time'  => $start_time,
                    'parent'  => $parent,
                    'child_desks'  => $child_desks,
                    'seller' => $seller,
                    'note' => $note,
                    'customer' => $customer,
                    'fee_item' => $fee_item,
                );
                $result['data'][$desk_id] = apply_filters('op_pull_desk_data',$result_data,$desk_data);
            }
            
            $result['status'] = 1;
        }catch (Exception $e)
        {
            $result['status'] = 0;
            $result['message'] = $e->getMessage();
        }
        return $result;
    }

    public function remove_desk(){ //remove takeaway
        global $op_table;
        $result = array('status' => 0, 'message' => '','data' => array());
        try{
            $desk_id = isset($_REQUEST['desk_number']) ? trim($_REQUEST['desk_number'],'#') : 0;
            $force = isset($_REQUEST['force_remove']) && $_REQUEST['force_remove'] == 'yes' ? true : false;
            if(!$desk_id)
            {
                throw new Exception( __('Cart Not found','openpos')  );
            }

            $allow = apply_filters('op_allow_remove_takeaway',true,$desk_id);
            if($allow)
            {
                $op_table->removeJsonTable($desk_id,$force);
                $result['status'] = 1;
            }else{
                throw new Exception( __('You do not allow remove this','openpos')  );
            }
           
        }catch (Exception $e)
        {
            $result['status'] = 0;
            $result['message'] = $e->getMessage();
        }
        return $result;
    }
    public function get_takeaway_list(){
        global $op_table;
        $result = array('status' => 0, 'message' => '','data' => array());
        $session_data = $this->_getSessionData();
        if($session_data)
        {
            $desk_ids = isset($_REQUEST['desk_ids']) ? json_decode(stripslashes($_REQUEST['desk_ids'])) : array();
            if(!is_array($desk_ids))
            {
                $desk_ids = array();
            }
            $warehouse_id = $session_data['login_warehouse_id'];
            $list = $op_table->takeawayJsonTables($warehouse_id,$desk_ids);
            $result['status'] = 1;
            $result['data'] = $list;
        }
       
        return $result;
    }

    public function allowRefundOrder($order_id){
        global $op_woo;
        return $op_woo->allowRefundOrder($order_id);
    }
    public function allowPickup($order_id){
        global $op_woo;
        return $op_woo->allowPickup($order_id);
    }

    public function get_shipping_method(){
        global $op_woo_cart;
        $result = array('status' => 0, 'message' => '','data' => array());
        try{
            $session_data = $this->_getSessionData();
            $by_data = json_decode(stripslashes($_REQUEST['by_data']),true);

            $cart = json_decode(stripslashes($_REQUEST['cart']),true);


            $result['status'] = 1;
            $result['data'] = $op_woo_cart->getShippingMethod($by_data,$cart);

            do_action('op_get_online_shipping_method',$result,$session_data);
            $result = apply_filters('op_get_online_shipping_method_response',$result,$session_data);
        }catch (Exception $e)
        {
            $result['status'] = 0;
            $result['message'] = $e->getMessage();
        }
        return $result;
    }
    public function get_shipping_cost(){
        global $op_woo_cart;
        $result = array('status' => 0, 'message' => '','data' => array());
        try{
            $session_data = $this->_getSessionData();
            $by_data = json_decode(stripslashes($_REQUEST['by_data']),true);

            $cart = json_decode(stripslashes($_REQUEST['cart']),true);

            $method = $by_data['shipping_method'] ? $by_data['shipping_method'] :'';
            $calc_shipping_cost = 0;
            $result['data']['calc_shipping_cost'] = $calc_shipping_cost;
            $result['data']['calc_shipping_tax'] = 0;
            $result['data']['calc_shipping_rate_id'] = '';
            if($method)
            {
                $result['status'] = 1;
                $cost = $op_woo_cart->getShippingCost($by_data,$cart);
                if(!empty($cost))
                {
                    $result['data']['calc_shipping_cost'] = $cost['cost'];
                    $result['data']['calc_shipping_tax'] = $cost['tax'];
                    $result['data']['calc_shipping_rate_id'] = $cost['rate_id'];
                }
            }
            do_action('op_get_online_shipping_cost',$result,$session_data);
            $result = apply_filters('op_get_online_shipping_cost_response',$result,$session_data);
        }catch (Exception $e)
        {
            $result['status'] = 0;
            $result['message'] = $e->getMessage();
        }
        return $result;
    }

    public function get_cart_discount(){
        global $op_woo_cart;
        $result = array('status' => 0, 'message' => '','data' => array());
        try{
            $session_data = $this->_getSessionData();

            $cart = json_decode(stripslashes($_REQUEST['cart']),true);

            $calc_shipping_cost = 0;
            $result['data']['discount_amount'] = $calc_shipping_cost;
            $result['data']['discount_type'] = 'fixed'; // fixed , percent

            $result['status'] = 1;
            $cost = $op_woo_cart->getCartDiscount($cart);
            if(!empty($cost))
            {
                $result['data']['discount_amount'] = $cost['discount_amount'];
                $result['data']['discount_type'] = $cost['discount_type'];
            }

            do_action('op_get_online_discount',$result,$session_data);
            $result = apply_filters('op_get_online_shipping_cost_response',$result,$session_data);
        }catch (Exception $e)
        {
            $result['status'] = 0;
            $result['message'] = $e->getMessage();
        }
        return $result;
    }

    public function get_order_number($update_number = true){
        global $op_woo_order;
        $result = array('status' => 0, 'message' => '','data' => array());
        try{
            
            $session_data = $this->_getSessionData();

            // lock order number
            $post_type = 'shop_order';
            $arg = array(
                'post_type' => $post_type,
                'post_status'   => 'auto-draft'
            );
            $next_order_id = wp_insert_post( $arg );
            $next_order_number = $next_order_id;
            update_post_meta($next_order_id,'_op_pos_session',$session_data['session']);
            if($update_number)
            {
                $next_order_number = $op_woo_order->update_order_number($next_order_id);
            }
            

            if(!$next_order_number)
            {
                $next_order_number = $next_order_id;
            }
            $setting = isset($session_data['setting']) ? $session_data['setting'] : array();
            $pos_sequential_number_prefix = isset($setting['pos_sequential_number_prefix']) ? ''.$setting['pos_sequential_number_prefix'] : '';

            $order_number_info = array(
                'order_id' => $next_order_id,
                'order_number' => $next_order_number,
                'order_number_formatted' => $op_woo_order->formatOrderNumber($next_order_number,$pos_sequential_number_prefix)
            );
            $result['data'] = apply_filters('op_get_next_order_number_info',$order_number_info);

        }catch (Exception $e)
        {
            $result['status'] = 0;
            $result['message'] = $e->getMessage();
        }
        return $result;
    }
    public function get_cart_number(){
        global $op_woo_order;
        $result = array('status' => 0, 'message' => '','data' => array());
        try{

            $cart_data = json_decode(stripslashes($_REQUEST['cart']),true);
            $cart_number = isset($cart_data['cart_number']) ? $cart_data['cart_number'] : array();


            $session_data = $this->_getSessionData();

            // lock order number
            $post_type = 'shop_order';
            $arg = array(
                'post_type' => $post_type,
                'post_status'   => 'auto-draft'
            );

            if(isset($cart_number['order_id']) && $cart_number['order_id'] )
            {
                $post = get_post($cart_number['order_id']);
                if($post && $post->post_status == 'auto-draft')
                {
                    $arg['ID'] = $post->ID;
                }
            }
           
            $next_order_id = wp_insert_post( $arg );
            update_post_meta($next_order_id,'_op_pos_session',$session_data['session']);
            $next_order_number = $next_order_id;

            if(!$next_order_number)
            {
                $next_order_number = $next_order_id;
            }
            $order_number_info = array(
                'order_id' => $next_order_id,
                'order_number' => $next_order_number,
                'order_number_formatted' => '#'.$next_order_number
            );
            $result['data'] = apply_filters('op_get_next_cart_number_info',$order_number_info);
        }catch (Exception $e)
        {
            $result['status'] = 0;
            $result['message'] = $e->getMessage();
        }
        return $result;
    }
    public function login_cashdrawer(){
        global $op_register;
        global $op_woo;
        global $op_table;
        $result = array('status' => 0, 'message' => 'Unknown message','data' => array());
        $session_id = trim($_REQUEST['session']);
        try{

            $session_data = $this->_session->data($session_id);
            if(empty($session_data))
            {
                throw  new Exception(__('Your login session has been clean. Please try login again','openpos'));
            }

            $client_time_offset = isset($_REQUEST['client_time_offset']) ? $_REQUEST['client_time_offset'] : 0;
            $cashdrawer_id = (int)$_REQUEST['cashdrawer_id'];
            $cash_drawers = $session_data['cash_drawers'];
            $check = false;
            foreach($cash_drawers as $c)
            {
                if($c['id'] == $cashdrawer_id)
                {
                    $check = true;
                }
            }
            if($check)
            {
                $register = $op_register->get($cashdrawer_id);
                $warehouse_id = isset($register['warehouse']) ? $register['warehouse'] : 0;
                $pos_balance = $op_register->cash_balance($cashdrawer_id);

                $session_data['client_time_offset'] = $client_time_offset;
                $session_data['cash_drawer_balance'] = $pos_balance;
                $session_data['balance'] = $pos_balance;
                $session_data['login_cashdrawer_id'] = $cashdrawer_id;
                $session_data['login_cashdrawer_mode'] = isset($register['register_mode']) ? $register['register_mode'] : 'cashier' ;
                $session_data['login_warehouse_id'] = $warehouse_id;
                $session_data['default_display'] =  ( $this->settings_api->get_option('openpos_type','openpos_pos') == 'grocery'  && $this->settings_api->get_option('dashboard_display','openpos_pos') =='table' ) ? 'product': $this->settings_api->get_option('dashboard_display','openpos_pos');
                $session_data['categories'] = $op_woo->get_pos_categories($cashdrawer_id);
                $session_data['currency_decimal'] = wc_get_price_decimals() ;

                $session_data['time_frequency'] = $this->settings_api->get_option('time_frequency','openpos_pos') ? (int)$this->settings_api->get_option('time_frequency','openpos_pos') : 3000 ;
                $session_data['product_sync'] = true;
                $session_data['date_format'] = $this->_core->convert_to_js_date_format(get_option( 'date_format' ));
                $session_data['time_format'] = $this->_core->convert_to_js_date_format(get_option( 'time_format' ));


                if($this->settings_api->get_option('pos_auto_sync','openpos_pos') == 'no')
                {
                    $session_data['product_sync'] =false;
                }


                $setting = $session_data['setting'];
                $user_id = $session_data['user_id'];
                $incl_tax_mode = $op_woo->inclTaxMode() == 'yes' ? true : false;
                $setting = $this->_core->formatReceiptSetting($setting,$incl_tax_mode);

                if(isset($setting['pos_sequential_number_prefix']) && $setting['pos_sequential_number_prefix'] )
                {
                    $pos_sequential_number_prefix = $setting['pos_sequential_number_prefix'];
                    $pos_sequential_number_prefix = str_replace('[outlet_id]',$warehouse_id,$pos_sequential_number_prefix);
                    $pos_sequential_number_prefix = str_replace('[register_id]',$cashdrawer_id,$pos_sequential_number_prefix);
                    $pos_sequential_number_prefix = str_replace('[cashier_id]',$user_id,$pos_sequential_number_prefix);
                    $pos_sequential_number_prefix = str_replace('[year]',date('Y'),$pos_sequential_number_prefix);
                    $pos_sequential_number_prefix = str_replace('[month]',date('m'),$pos_sequential_number_prefix);
                    $pos_sequential_number_prefix = str_replace('[day]',date('d'),$pos_sequential_number_prefix);
                   
                    $setting['pos_sequential_number_prefix'] = $pos_sequential_number_prefix;
                }
                
                if($setting['openpos_type'] == 'restaurant')
                {
                    $setting['openpos_tables'] = $op_table->tables($warehouse_id,true);
                    
                     // desk multi pay
                     $setting['pos_desk_multi_pay'] = 'yes';
                     $setting['pos_auto_dish_send'] = 'no';
                     $session_data['takeaway_number'] = $op_table->getTakeawayNumber($cashdrawer_id,$warehouse_id);
                }
                if($setting['pos_default_checkout_mode'] == 'single_mutli_times'){
                    $setting['pos_default_checkout_mode'] = 'single';
                    $setting['pos_single_multi_payment'] = 'yes';
                }

                if($setting['pos_default_checkout_mode'] == 'single_mutli_times' ||  $setting['pos_default_checkout_mode'] == 'single'){
                    $session_data['allow_receipt'] = 'no';
                }
                $pos_available_taxes =  $op_woo->getAvailableTaxes($warehouse_id);
                $setting['pos_available_taxes'] =  $pos_available_taxes;

                // fee tax
                if(isset($setting['pos_fee_tax_class']) && $setting['pos_fee_tax_class'] != 'op_notax')
                {
                    $fee_tax_class = $setting['pos_fee_tax_class'];
                    $setting['pos_fee_tax'] = array();
                    if($setting['pos_fee_tax_class'] === ''){
                        $fee_tax_class = 'standard';
                    }
                    foreach($pos_available_taxes as $tax_class)
                    {
                        $tax_class_code = $tax_class['tax_class'] ? $tax_class['tax_class'] : '';
                        if($fee_tax_class == $tax_class_code)
                        {
                            $setting['pos_fee_tax'] = $tax_class;
                        }
                    }
                    
                }
                

                //start role
                $roles = array();
                
                switch($session_data['login_cashdrawer_mode'])
                {
                    case 'seller':
                    case 'waiter':
                        $roles = array(
                            'do_checkout' => 'no',
                            'transactions' => 'no',
                        );
                        break;
                    case 'customer':
                        $roles = array(
                            'orders' => 'no',
                            'customers' => 'no',
                            'transactions' => 'no',
                            'report' => 'no',
                            'tables' => 'no',
                            'takeaway' => 'no',
                            'switch_seller' => 'no',
                        );
                        $setting['pos_default_checkout_mode'] = 'multi';
                        $setting['pos_disable_item_discount'] = 'yes';
                        $setting['pos_disable_cart_discount'] = 'yes';
                        $setting['pos_cart_buttons'] = array(
                            'cart-note',
                            // 'shipping',
                            // 'pickup',
                            // 'cart-discount',
                            // 'custom-item',
                            // 'custom-tax',
                            // 'seller'
                        );
                        break;
                }
                // do_remove_ready_dish : allow remove dish once it ready
                // do_clear_desk : clear desk
                // do_remove_dish : remove dish out of desk
                // do_mark_item_done : mark item done on desk
                $session_data['role'] = $roles;
                //end role
                $default_currency  = array(
                    'decimal' => wc_get_price_decimals(),
                    'decimal_separator' => wc_get_price_decimal_separator(),
                    'thousand_separator' => wc_get_price_thousand_separator(),
                    'code' => get_woocommerce_currency(), 
                    'symbol' => get_woocommerce_currency_symbol(), 
                    'rate' => 1
                );
                $setting['currency'] = $default_currency;
                $availble_currencies[$default_currency['code']] = $default_currency;

                $setting['currencies'] = $availble_currencies;
                //cart button
                $pos_cart_buttons = ['cart-note','shipping','pickup','cart-discount','coupon','custom-item','custom-tax','seller','fee'];
                if($setting['pos_tax_class'] == 'op_productax')
                {
                    $pos_cart_buttons = array_diff( $pos_cart_buttons, ['custom-tax'] ) ;
                }
                if(isset($setting['pos_allow_custom_item'] ) && $setting['pos_allow_custom_item'] == 'no')
                {
                    $pos_cart_buttons = array_diff( $pos_cart_buttons, ['custom-item'] ) ;
                }
                if(!isset($setting['pos_allow_custom_note']) || $setting['pos_allow_custom_note'] != 'yes')
                {
                    $pos_cart_buttons = array_diff( $pos_cart_buttons, ['cart-note'] ) ;
                }
                
            
                $setting['pos_cart_buttons'] = array_values($pos_cart_buttons);
                //end
                
                $setting['shipping_methods'] = $op_woo->getStoreShippingMethods($warehouse_id,$setting);
                if(isset($setting['pos_enable_weight_barcode']) && $setting['pos_enable_weight_barcode'] == 'yes')
                {
                    $setting['pos_weight_barcode_prefix'] = '20';
                }

                $session_data['setting'] = $setting;

                if(!isset($session_data['setting']['pos_categories']) || !$session_data['setting']['pos_categories'])
                {
                    $session_data['setting']['pos_categories'] = array();
                }

                $sale_persons = $this->getCashierList($cashdrawer_id);
                
                $session_data['sale_persons'] = $sale_persons;

                
                $session_data['logged_time'] = $this->_core->convertToShopTime($session_data['logged_time']);

                //$session_data['total_product_page'] = $this->getTotalPageProduct();

                $session_data = apply_filters('op_cashdrawer_login_session_data',$session_data);

                $this->_session->clean($session_id);
                $this->_session->save($session_id,$session_data);

                $session_response_data = $session_data; //$this->_session->data($session_id);

                $result['data'] = apply_filters('op_get_login_cashdrawer_data',$session_response_data);
                $result['status'] = 1;
            }else{
                $this->_session->clean($session_id);
                $result['message'] = __('Your have no grant to any register','openpos');
            }
        }catch (Exception $e)
        {
            $this->_session->clean($session_id);
            $result['status'] = 0;
            $result['message'] = $e->getMessage();
        }
        return $result;
    }
    public function login_with_session(){
        
        $result = array('status' => 0, 'message' => 'Unknown message','data' => array());
        $session_id = trim($_REQUEST['session']);
        try{
            $session_data = $this->_session->data($session_id);
            if(empty($session_data))
            {
                throw  new Exception(__('Your login session has been clean. Please try login again','openpos'));
            }
            $check = true;
            if($check)
            {
                $session_response_data = $session_data; 
                $result['data'] = apply_filters('op_get_login_with_session_cashdrawer_data',$session_response_data);
                $result['status'] = 1;
            }else{
                $this->_session->clean($session_id);
                $result['message'] = __('Your have no grant to any register','openpos');
            }
        }catch (Exception $e)
        {
            $this->_session->clean($session_id);
            $result['status'] = 0;
            $result['message'] = $e->getMessage();
        }
        return $result;
    }
    public function getAllowCashdrawers($user_id){
        global $op_register;
        global $op_warehouse;
        $result = array();

        $registers = $op_register->registers();
       
        foreach($registers as $register)
        {
            $warehouse_id = $register['warehouse'];
            
            if($register['status'] == 'publish')
            {
                $warehouse = $op_warehouse->get($warehouse_id);
                $state = '';
                $country = '';
                if($warehouse['country'])
                {
                    if(strpos($warehouse['country'],':') !== false)
                    {
                        $tmp = explode(':',$warehouse['country']);
                        $state = end($tmp);
                        $country = $tmp[0];
                    }
                }
                $warehouse_address_data = array(
                    'address_1'  => $warehouse['address'],
                    'address_2'  => $warehouse['address_2'],
                    'city'       => $warehouse['city'],
                    'state'      => $state,
                    'postcode'   => $warehouse['postal_code'],
                    'country'    => $country,
                );
                
                $cashiers = $register['cashiers'];
                if(in_array($user_id,$cashiers))
                {
                    if(!empty($warehouse))
                    {
                        $result[] = array(
                            'id' => $register['id'],
                            'name' => $register['name'],
                            'outlet_id' => $warehouse['id'],
                            'outlet_name' => $warehouse['name'],
                            'address' => WC()->countries->get_formatted_address( $warehouse_address_data,',' )
                        );
                    }
                    
                }
            }
        }
        
        return $result;

    }
    public function update_state(){
        global $op_register;
        global $op_table;
        global $op_woo;

        $result = array('status' => 0, 'message' => 'Unknown message','data' => array());
        $session_id = trim($_REQUEST['session']);
        try{
            $last_check = isset($_REQUEST['last_check']) ? trim($_REQUEST['last_check']) : 0; // in miliseconds
            $session_data = $this->_session->data($session_id);
            
            if($last_check == 0 && isset($session_data['logged_time']))
            {
                $last_check = strtotime($session_data['logged_time']) * 1000;
            }
            $client_time_offset = isset($session_data['client_time_offset']) ? $session_data['client_time_offset'] : 0;
            $last_check_utc = $last_check ;//+ $client_time_offset * 60 * 1000;

            $cart = json_decode(stripslashes($_REQUEST['cart']),true);
            //save to bill screen data
           
            if( !empty($cart) ){
                $op_register->update_bill_screen($session_data,$cart);
            }
            
            $tables_version = array();
            $ready_dish = array();
            $deleted_takeaway = array();
            $desk_message = '';
            if($this->settings_api->get_option('openpos_type','openpos_pos') == 'restaurant' )
            {
                $tables = array();
                if(isset($_REQUEST['tables']))
                {
                    $_tables = json_decode(stripslashes($_REQUEST['tables']),true);
                    foreach($_tables as $table_id => $table)
                    {
                        $source_type = isset($table['source_type']) ? $table['source_type'] : '';
                        if($source_type == 'order_takeaway')
                        {
                            //$tables[$table_id] = $table;
                        }
                    }
                }

                //save to table data
                //disable auto background save item on table
                if(!empty($tables))
                {
                    $op_table->update_bill_screen($tables,true);
                }
                

                $warehouse_id = isset($session_data['login_warehouse_id']) ? $session_data['login_warehouse_id'] : 0;
                
                $update_data = $op_table->get_all_update_data($warehouse_id,$last_check,$last_check_utc);

                $tables_version = isset($update_data['tables_version']) ? $update_data['tables_version'] : array();

                $ready_dish = isset($update_data['ready_dish']) ? $update_data['ready_dish'] : array();

                $tables_desk_messages = $update_data['desk_message'];
                $deleted_takeaway = isset($update_data['deleted_takeaway']) ? $update_data['deleted_takeaway'] : array();

                if(!empty($tables_desk_messages))
                {
                    $desk_message = sprintf(__( 'There are new message from tables: %s', 'openpos' ),implode(',',$tables_desk_messages));
                    
                }
                // $tables_version = $op_table->tables_version($warehouse_id);
                // $ready_dish = $op_table->ready_dishes($warehouse_id);
            }

            $result['data']['deleted_takeaway'] = $deleted_takeaway;
            $result['data']['tables'] = $tables_version;
            $result['data']['ready_dish'] = $ready_dish;
            $notifications = $op_woo->getNotifications($last_check,$session_data);

            $notifications['desk_message'] = $desk_message;
            $result['data']['notifications'] = $notifications;


            $result['status'] = 1;
        }catch (Exception $e)
        {
            $result['status'] = 0;
            $result['message'] = $e->getMessage();
        }
        return $result;
    }
    public function get_app_list(){

        $result['status'] = 1;
        $result['data'] = array();
        $classes = get_declared_classes();
        foreach($classes as $klass) {
            $reflect = new ReflectionClass($klass);

            if($reflect->implementsInterface('OP_App'))
            {
               $tmp_class =  new $klass();
               $app_key = $tmp_class->get_key();
               if($app_key)
               {
                   $tmp = array(
                       'key' => $app_key,
                       'name' => $tmp_class->get_name(),
                       'thumb' => $tmp_class->get_thumb(),
                       'object'   => $klass
                   );
                   $result['data'][] = $tmp;
               }

            }
        }

        return $result;
    }
    public function app_view(){
        $app_key = isset($_REQUEST['app']) ?  esc_attr($_REQUEST['app']) : '';
        $session = $this->_getSessionData();
        $apps = $this->get_app_list();

        foreach($apps['data'] as $app)
        {
            if($app['key'] == $app_key)
            {
                $obj = $app['object'];
                $app_obj = new $obj;
                $app_obj->set_session($session);
                $app_obj->render();
                exit;
            }

        }
    }
    public function add_custom_product(){
        global $op_warehouse;
        global $op_woo;
        $result = array('status' => 0, 'message' => '','data' => array());
        try{
            $session_data = $this->_getSessionData();
            $warehouse_id = isset($session_data['login_warehouse_id']) ? $session_data['login_warehouse_id'] : 0;
            $product_data = json_decode(stripslashes($_REQUEST['product']),true);
            $barcode = isset($product_data['barcode']) ? trim($product_data['barcode']) : '';
            $name = isset($product_data['name']) ? trim($product_data['name']) : '';
            $qty = isset($product_data['qty']) ? trim($product_data['qty']) : 0;
            $price = isset($product_data['price']) ? 1 * $product_data['price'] : 0;
            $tax_amount = isset($product_data['tax_amount']) ? 1 * $product_data['tax_amount'] : 0;
            $entered_price = isset($product_data['entered_price']) ? 1 * $product_data['entered_price'] : 0;
            $description = isset($product_data['description']) ?  $product_data['description'] : '';
            $final_price = $price + $tax_amount;
            if($entered_price)
            {
                $final_price = $entered_price;
            }

            if(!$barcode)
            {
                throw new Exception(__('Please enter product barcode','openpos'));
            }
            $product_id = $this->_core->getProductIdByBarcode($barcode);
            if(!$product_id)
            {
                $objProduct = new WC_Product();
                $objProduct->set_price($final_price);
                $objProduct->set_regular_price($final_price);
                $objProduct->set_name($name);
                $objProduct->set_description($description);
                $objProduct->set_stock_quantity(0);
                $objProduct->set_sku($barcode);
                $product_id = $objProduct->save();
                $op_warehouse->set_qty($warehouse_id,$product_id,$qty);
                $barcode_field = $this->settings_api->get_option('barcode_meta_key','openpos_label');
                update_post_meta($product_id,$barcode_field,$barcode);
                $status = 'pending';
                $post = array( 'ID' => $product_id, 'post_status' => $status );
                wp_update_post($post);
            }

            do_action( 'openpos_after_add_custom_product',$product_id,$product_data );

            $product_post = get_post($product_id);
            $data = $op_woo->get_product_formatted_data($product_post,$warehouse_id);
            $result['data'] = $data;
            $result['status'] = 1;
            $result = apply_filters('op_get_custom_item_data',$result,$session_data,$product_data);

        }catch (Exception $e)
        {
            $result['status'] = 0;
            $result['message'] = $e->getMessage();
        }
        return $result;
    }
    public function search_product(){
        global $op_warehouse;
        global $op_woo;
        $result = array('status' => 0, 'message' => '','data' => array());
        try{
            $session_data = $this->_getSessionData();
            $warehouse_id = isset($session_data['login_warehouse_id']) ? $session_data['login_warehouse_id'] : 0;
            $term = isset($_REQUEST['term']) ? $_REQUEST['term'] : '' ;
            $term = trim($term);
            $products = array();
            if($term)
            {
                
                $product_id_by_barcode = $this->_core->getProductIdByBarcode($term,false);

                
                if($product_id_by_barcode)
                {
                    $product_post = get_post($product_id_by_barcode);
                    $tmp_product = $op_woo->get_product_formatted_data($product_post,$warehouse_id,false,true);
                    if($tmp_product)
                    {
                        $products[$product_id_by_barcode] = $tmp_product;
                    }
                }
                if(empty($products)){
                    $data_store = new OP_WC_Product_Data_Store_CPT();//WC_Data_Store::load( 'product' );
                    $search_result_total = $this->settings_api->get_option('search_result_total','openpos_pos');
                    $result_number = apply_filters('op_get_online_search_total_result',$search_result_total);

                    $include_variable = apply_filters('search_product_include_variable',false);
                    $ids        = $data_store->search_products( $term, '', $include_variable , false, $result_number );
                   
                    foreach ( $ids as $product_id ) {
                        // if(function_exists('icl_object_id'))
                        // {
                        //     $post_type = get_post_type( $product_id ) ;
                        //     $product_id = icl_object_id( $product_id, $post_type, false, ICL_LANGUAGE_CODE );
                            
                        // }
                        if($product_id)
                        {
                                $product_post = get_post($product_id);
                                if($product_post)
                                {
                                    $tmp_product = $op_woo->get_product_formatted_data($product_post,$warehouse_id,false,true);

                                    $allow = $this->_allowProduct($tmp_product,$warehouse_id);

                                    if($allow)
                                    {
                                        $products[$product_id] = $tmp_product;
                                    }
                                    
                                }
                        }
                    }
                }
                
                if(!empty($products))
                {
                    $products = array_values($products);
                }
                $result['data']['term'] = $term;
                $result['data']['products'] = $products;
                $result['status'] = 1;
                $result = apply_filters('op_get_search_product_result_data',$result,$session_data);
            }



        }catch (Exception $e)
        {
            $result['status'] = 0;
            $result['message'] = $e->getMessage();
        }
        return $result;
    }
    private function _allowProduct($product_formatted,$warehouse_id = -1){
        global $op_warehouse;
        $allow = true;
        if(!$product_formatted)
        {
            return false;
        }
        if(empty($product_formatted))
        {
            return false;
        }
        $product_id = $product_formatted['id'];
        if(!$op_warehouse->is_instore($warehouse_id,$product_id))
        {
            $allow = false;;
        }
        return $allow;

    }

    public function scan_product(){
        global $op_warehouse;
        global $op_woo;
        $result = array('status' => 0, 'message' => '','data' => array());
        try{
            $session_data = $this->_getSessionData();
            $warehouse_id = isset($session_data['login_warehouse_id']) ? $session_data['login_warehouse_id'] : 0;
            $term = isset($_REQUEST['term']) ? $_REQUEST['term'] : '' ;
            $term = trim($term);
            $products = array();
            if($term)
            {
                $product_id_by_barcode = $this->_core->getProductIdByBarcode($term);
                if($product_id_by_barcode)
                {
                    $product_post = get_post($product_id_by_barcode);

                    $tmp_product = $op_woo->get_product_formatted_data($product_post,$warehouse_id,false,true);
                    $allow = $this->_allowProduct($tmp_product,$warehouse_id);
                    if($allow)
                    {
                        $products[] = $tmp_product;
                    }
                }
                if(!empty($products))
                {
                    $result['data'] = end($products);
                    $result['status'] = 1;
                }else{
                    $result['status'] = 0;
                    $result['message'] = sprintf(__('Have no product with barcode "%s". Please check again!','openpos'),$term);
                }
                
                
                $result = apply_filters('op_get_search_product_result_data',$result,$session_data);
            }



        }catch (Exception $e)
        {
            $result['status'] = 0;
            $result['message'] = $e->getMessage();
        }
        return $result;

    }

    public function upload_file(){
        global $op_warehouse;
        global $op_woo;
        $result = array('status' => 0, 'message' => '','data' => array());
        try{
            $session_data = $this->_getSessionData();
            $warehouse_id = isset($session_data['login_warehouse_id']) ? $session_data['login_warehouse_id'] : 0;
            $file_data = isset($_REQUEST['file_data']) ? json_decode(stripslashes($_REQUEST['file_data']),true) : array();

            $file_name = isset($_REQUEST['filename'])  ? $_REQUEST['filename']  : '';
            $file_type = isset($_REQUEST['filetype'])  ? $_REQUEST['filetype']  : '';
            $file_base_64 = isset($_REQUEST['value'])  ? $_REQUEST['value']  : '';
            $file_name = strtolower(sanitize_file_name($file_name));
           
            if($file_base_64 && $this->_core->allow_upload(array('type'=>$file_type,'name' => $file_name)))
            {
                $_base_path =  WP_CONTENT_DIR.'/uploads/openpos/tmp';
               
                $_filesystem = $this->_core->_filesystem;
                $file_mode = 0755;
                if(!file_exists($_base_path))
                {
                    $_filesystem->mkdir($_base_path,$file_mode);
                }

                $_filesystem->put_contents(
                    $_base_path.'/'.$file_name,
                    base64_decode($file_base_64),
                    $file_mode
                );
                
                $result['data'] = array(
                    'file_id' => time(),
                    'temp_file' => $file_name,
                    'file' => $file_name,
                    'url' => WP_CONTENT_URL.'/uploads/openpos/tmp/'.$file_name,
                    'thumb_url' => WP_CONTENT_URL.'/uploads/openpos/tmp/'.$file_name,
                );
                $result['status'] = 1;
                $result = apply_filters('op_upload_file_result_data',$result,$session_data);
            }else{
                throw new Exception('File not valid. Please try again.');
            }

        }catch (Exception $e)
        {
            $result['status'] = 0;
            $result['message'] = $e->getMessage();
        }
        return $result;
    }

    public function send_receipt(){
        global $op_warehouse;
        global $op_woo;
        global $is_openpos_email;
        global $op_receipt;
        $result = array('status' => 0, 'message' => '','data' => array());
        try{
            $is_openpos_email = true;
            $session_data = $this->_getSessionData();
            $order_data = json_decode(stripslashes($_REQUEST['order']),true);
            $send_to = $_REQUEST['to'];
            $register_id = isset($_REQUEST['cashdrawer_id']) ? $_REQUEST['cashdrawer_id'] : 0;
            if(!$register_id)
            {
                $register_id = isset($session_data['login_cashdrawer_id']) ? $session_data['login_cashdrawer_id'] : 0;
            }
            if(is_email($send_to))
            {
                $result = $op_receipt->send_receipt($send_to,$order_data,$register_id,'manual');
            }else{
                $result['message'] = __('Your email address is incorrect. Please check again!','openpos');
            }

        }catch (Exception $e)
        {
            $result['status'] = 0;
            $result['message'] = $e->getMessage();
        }
        return $result;
    }
    public function get_order(){
        global $op_woo;
        global $op_woo_order;
        global $is_openpos_email;
        $result = array('status' => 0, 'message' => '','data' => array());
        try{
            $is_openpos_email = true;
            $session_data = $this->_getSessionData();
            $order_number = isset($_REQUEST['order_number']) ? $_REQUEST['order_number'] : 0;
            $order_id = isset($_REQUEST['order_id']) ? $_REQUEST['order_id'] : 0;
            $order = false;
            if(!$order_id)
            {
                $order_id = $op_woo_order->get_order_id_from_number($order_number);
            }
            $order = wc_get_order($order_id);
            if($order)
            {

                $result['data'] = $op_woo->formatWooOrder($order_id);
                $result['status'] = 1;
            }else{
                throw new Exception(__('Order not found.','openpos'));
            }
            
        }catch (Exception $e)
        {
            $result['status'] = 0;
            $result['message'] = $e->getMessage();
        }
        return $result;
    }
    public function get_order_transactions(){
      
        global $op_woo;
        global $op_woo_order;
        global $is_openpos_email;
        $result = array('status' => 0, 'message' => '','data' => array());
        try{
            $is_openpos_email = true;
            $session_data = $this->_getSessionData();
            

            $order_number_old = intval($_REQUEST['order_number']);
            $order_id_old = intval($_REQUEST['order_id']);
            $order_local_id_old = intval($_REQUEST['local_order_id']);
            
            $order = false;
            if($order_id_old)
            {
                $order = wc_get_order($order_id_old);
                $order_number = $op_woo_order->get_order_id_from_local_id($order_id_old);
                $order = wc_get_order($order_number);
            }
            if(!$order)
            {
                $order_id = $op_woo_order->get_order_id_from_number($order_number_old);
                $order = wc_get_order($order_id);
            }
            
            if(!$order)
            {
                $order = wc_get_order($order_number_old);
            }
            if(!$order)
            {
                $order_number = $op_woo_order->get_order_id_from_local_id($order_number_old);
                $order = wc_get_order($order_number);
            }

            if(!$order)
            {
                $order_number = $op_woo_order->get_order_id_from_local_id($order_local_id_old);
                $order = wc_get_order($order_number);
            }
           
            if($order)
            {
                
                $order_formatted = $op_woo->formatWooOrder($order);
              
                $_order_total_paid = isset($order_formatted['total_paid']) ? $order_formatted['total_paid'] : $order->get_total();

               
                $transactions = array();
                if($order_formatted['status'] == 'refunded')
                {
                    $transactions = $op_woo->getOrderTransactions($order->get_id(),array( 'order','refund_order'));
                }else{
                    $transactions = $op_woo->getOrderTransactions($order->get_id(),array( 'order','refund_order'));
                }
                
                $total_paid = 0;
                foreach($transactions as $transaction)
                {
                    $result['data']['transactions'][] = array(
                        'in_amount' => $transaction['in_amount'],
                        'out_amount' => $transaction['out_amount'],
                        'created_at' => $transaction['created_at'],
                        'ref' => $transaction['ref'],
                        'payment_name' => $transaction['payment_name'],
                        'payment_code' => $transaction['payment_code'],
                        'payment_ref' => $transaction['payment_ref'],
                        'created_by' => $transaction['created_by'],
                    );
                    $total_paid += ( 1* $transaction['in_amount'] - 1 * $transaction['out_amount']);
                }

                $_order_customer_paid_amount = isset($order_formatted['customer_total_paid']) ? 1 * $order_formatted['customer_total_paid'] : $total_paid;

                if($order_formatted['status'] == 'refunded')
                {
                    $result['data']['customer_total_paid'] = 0;
                    $result['data']['total_paid'] = 0;
                    $result['data']['total_remain'] = 0;
                }else{
                    $result['data']['total_paid'] = $total_paid;
                    $result['data']['total_remain'] =  ($_order_total_paid - $_order_customer_paid_amount);//$total - $total_paid;
                }
                
                
               
                $result['status'] = 1;
            }else{
                throw new Exception(__('Order not found.','openpos'));
            }




            
        }catch (Exception $e)
        {
            $result['status'] = 0;
            $result['message'] = $e->getMessage();
        }
        return $result;
    }

    public function add_order_transaction(){
        global $op_woo_order;
        global $op_woo;
        global $op_register;
        global $is_openpos_email;
        $result = array('status' => 0, 'message' => 'Unknown message','data' => array());
        try{
            $transaction = json_decode(stripslashes($_REQUEST['transaction']),true);
            
            $order_number_old = intval($_REQUEST['order_number']);
            $order_id_old = intval($_REQUEST['order_id']);
            $order_local_id_old = intval($_REQUEST['local_order_id']);
            $in_amount = isset($transaction['in_amount']) ? 1*$transaction['in_amount'] : 0;
            $order_id = $op_woo_order->get_order_id_from_number($order_number_old);
            if(!$order_id)
            {
                $order_number = $op_woo_order->get_order_id_from_local_id($order_id_old);
            }
            if(!$order_id)
            {
                $order_number = $op_woo_order->get_order_id_from_local_id($order_local_id_old);
            }
            
            if(!$order_id)
            {
                   
                throw new Exception(__('Order not found','openpos'));
            }
            if($in_amount)
            {
                $order = $op_woo->formatWooOrder($order_id);
              
                $total_paid = $order['total_paid'];
                $customer_paid_amount = 1 * $order['customer_total_paid'];
                
                $remain_amount = ($total_paid - $customer_paid_amount);
                if($remain_amount >= $in_amount  )
                {
                     $tmp_result = $this->add_transaction($transaction);
                     
                     $transaction_id = $tmp_result['data'];
                     $customer_total_paid_amount = $customer_paid_amount + 1*$in_amount;
                     $result['status'] = 1;
                     $result['data'] = array(
                         'transaction_id' => $transaction_id,
                         'customer_total_paid' => $customer_total_paid_amount
                     );

                     //update debit
                     $cashdrawer_meta_key = $op_register->get_order_meta_key();

                     $register_id = get_post_meta($order_id,$cashdrawer_meta_key,true);
                     if($register_id !== false)
                     {
                         $op_register->addDebitBalance($register_id,(0 - (1*$in_amount) ));
                         $op_remain_amount = $order['total_paid'] - $customer_total_paid_amount;
                         if($op_remain_amount < 0)
                         {
                            $op_remain_amount = 0;
                         }
                         update_post_meta($order_id,'_op_remain_paid',$op_remain_amount); //remain amount
                     }
               
                      //check full payment 
                     $_order = wc_get_order( $order_id );
                     
                     if($customer_total_paid_amount >= $order['total_paid'])
                     {
                        $tmp_setting_order_status = $this->settings_api->get_option('pos_order_status','openpos_general');
                        $setting_order_status =  apply_filters('op_order_status_full_pay',$tmp_setting_order_status,$order);
                        
                        $_order->payment_complete();
                        $_order->set_status($setting_order_status, __('Full Payment via OpenPos', 'openpos'));
                        $_order->save();
                     }else{
                        $note = wp_sprintf(__('Paid amount %s  via %s','openpos'),wc_price($in_amount),$transaction['payment_name']); 
                        $op_woo_order->addOrderNote($order_id,$note);
                     }

                }else{
                    throw new Exception(wp_sprintf(__('Amount not match with remain amount: %s','openpos'),$remain_amount));
                }
               
            }else{
                throw new Exception(__('Amount value is incorrect','openpos'));
            }
           
            
        }catch (Exception $e)
        {
            $result['status'] = 0;
            $result['message'] = $e->getMessage();
        }
        return $result;
    }
    public function customer_table_order(){
        
        $result = array(
            'status' => 0,
            'data' => array(),
            'message' => __('Unknow message','openpos')
        );
        global $op_register;
        global $op_table;
        global $op_woo;
        global $op_warehouse;
        try{
            $key = isset($_REQUEST['key']) ? esc_attr($_REQUEST['key']) : '';
            $table = $op_table->getTableByKey($key);

            $order_type = 'table';
            if(!$table || $table == null){
                $table = $op_warehouse->getTakeawayByKey($key);
                
                $order_type = 'takeaway';
            }
            
            if(!$table || $table == null)
            {
                throw new Exception(__('Table do not exist or Your QRcode has been expired','openpos'));
            }

            if($table['status'] != "publish")
            {
                throw new Exception(__('Table not publish yet!','openpos'));
            }

            
            
            $register_id = isset($table['register_id']) ? $table['register_id'] : 0;

            $register = $op_register->get($register_id);

            if(!$register || $register == null)
            {
                throw new Exception(__('Register do not exist','openpos'));
            }
            $warehouse_id = $register['warehouse'];
            $ip = $this->_core->getClientIp();
            $session_id = $this->_session->generate_session_id();
            $session_id = 'cust-'.$session_id;
            $user_name = 'Guest';
            $cashier_name = 'Guest';
            $email = 'guest@mail.com';
            $avatar = '';
            $payment_methods = array();
            $setting = $this->getSetting();
            
            $drawers = array();
            $drawers[] = $register ;
            $price_included_tax = true;
            if(wc_tax_enabled())
            {
                $price_included_tax = wc_prices_include_tax();
            }
            $setting['currency'] = array(
                'decimal' => wc_get_price_decimals(),
                'decimal_separator' => wc_get_price_decimal_separator(),
                'thousand_separator' => wc_get_price_thousand_separator(),
            ) ;
            $setting['pos_default_checkout_mode'] = 'single';
            $setting['pos_alway_product_option_popup'] = 'yes';
            $setting['pos_search_product_online'] = 'yes';
            $setting['pos_default_open_cash'] = 'no';

            //$setting['pos_table_default_view'] = 'no';
            $redirect_url = '';
            
            if($order_type == "takeaway")
            {
                $table_id = time();
                $table['id'] = $table_id;
                $guest_takeaway_number = date_i18n('dHis');
                $guest_takeway_name = sprintf(__('Guest Takeway: %s','openpos'),$guest_takeaway_number);//'dsfds';
                $setting['openpos_tables'] = array();
                /*
                $setting['openpos_tables'][] = array(
                    'id' => $table_id ,
                    'name' => $guest_takeway_name,
                    'warehouse' => $warehouse_id,
                    'warehouse_id' => $warehouse_id,
                    'position' => 0,
                    'type' => 'guest_takeaway',
                    'cost' => 0,
                    'cost_type' => '',
                    'status' => 1
                );*/
                //create new takeaway order
                $takeaway_data = array(
                    'id' => $table_id,
                    'order_number' => $table_id,
                    'label' => $guest_takeway_name,
                    'desk' => array(
                        "id" =>  $table_id,
                        "name" =>  $guest_takeway_name,
                        "type" =>  "takeaway",
                        "warehouse_id" => $warehouse_id
                    ),
                    'child_desks' => array(),
                    'ver' => 10,
                    'parent' => 0,
                    'online_ver' => 10,
                    'system_ver' => 10,
                    'collection' => 1,
                    'cost' => 0,
                    'type' => 'takeaway',
                    'start_time' => 0,
                    'seller' => array(),
                    'customer' => array(),
                    'shipping' => null,
                    'seat' => 0,
                    'total_qty' => 0,
                    'serverd_qty' => 0,
                    'sub_total_incl_tax' => 0,
                    'created_at_time' => time()*1000,
                    'items' => array(),
                    'fee_item' => null,
                    'note' => '',
                    'source' => '',
                    'source_type' => 'guest_takeaway',
                    'source_details' => array(),
                    'state' => '',
                    'tag' => '',
                    'dining' => '',
                    'messages' => '',
                    'session' => $session_id,
                );
               
                $op_table->update_bill_screen(['takeaway-'.$table_id => $takeaway_data],true);
                $redirect_url = '/to-go/'.$table_id;
                //end
            }else{
                $openpos_tables = $op_table->tables($warehouse_id,true);
                $setting['openpos_tables'] = array();
                foreach($openpos_tables as $t)
                {
                    if($t['id'] == $table['id'])
                    {
                        $setting['openpos_tables'][] = $t;
                    }
                }
                $redirect_url = null;
                
            }


            $setting['custom_ring_messages'] = array(__('I want to pay!','openpos'),__('I need a help!','openpos'));
           
            //reset big variable
            $setting['receipt_template_footer'] = '';
            $setting['barcode_label_template'] = '';
            $setting['receipt_template_header'] = '';
            $setting['receipt_css'] = '';
            $setting['pos_custom_css'] = '';
            $setting['payment_methods'] = [];
            $setting['shipping_methods'] = [];
            
            $session_data = array(
                'user_id' => 0 ,
                'ip' => $ip,
                'session' => $session_id ,
                'username' =>  $user_name ,
                'name' =>  $cashier_name,
                'email' =>  $email ,
                'role' =>  [] ,
                'phone' => '',
                'logged_time' => current_time('d-m-Y H:i:s',true),
                'setting' => $setting,
                'session' => $session_id,
                'sale_persons' => array(),
                'payment_methods' => $payment_methods,
                'cash_drawer_balance' => 0,
                'balance' => 0,
                'cashes' => array(),
                'cash_drawers' => $drawers,
                'price_included_tax' => $price_included_tax,
                'avatar' => $avatar,
                'location' => '',
                'lock_table' => $redirect_url
            );
            
            $session_data = apply_filters('op_cashdrawer_login_session_data',$session_data);
            $session_data['login_cashdrawer_id'] = $register_id;
            
            $session_data['login_warehouse_id'] = $warehouse_id;
            $session_data['login_table_id'] = $table['id'];
            $session_data['default_display'] =  'board';
            $session_data['categories'] = $op_woo->get_pos_categories($register_id);
            $session_data['currency_decimal'] = wc_get_price_decimals() ;

            $session_data['time_frequency'] = $this->settings_api->get_option('time_frequency','openpos_pos') ? (int)$this->settings_api->get_option('time_frequency','openpos_pos') : 3000 ;
            $session_data['product_sync'] = true;
           
            
            $session_data['login_cashdrawer_mode'] = 'customer' ;

            $session_data = apply_filters('op_get_guest_login_session_data',$session_data);
            
            $this->_session->save($session_id,$session_data);

            //$result['data']['url']= 'http://localhost:4200/login/customer-login#'.$session_id;
            $result['data']['url']= $this->_core->get_pos_url('/#/login/customer-login#'.$session_id);
            $result['status'] = 1;
            $result['data']['currency'] = array(
                'currency_decimal' => wc_get_price_decimals(),
                'currency_decimal_separator' => wc_get_price_decimal_separator(),
                'currency_thousand_separator' => wc_get_price_thousand_separator(),
            );
            $result['data']['table'] = $table;
            $table_data = $op_table->bill_screen_data($table['id']);
            $result['data']['dishes'] = isset($table_data['items']) ? $table_data['items'] : array();

            $result = apply_filters('op_get_guest_login',$result,$session_data);
            
        }catch (Exception $e)
        {
            $result['status'] = 0;
            $result['message'] = $e->getMessage();
        }
        echo json_encode($result);
        exit;
    }
    //communicate between customer and waiter
    public function send_message(){
        global $op_table;
        $result = array(
            'status' => 0,
            'data' => array(),
            'message' =>''
        );
        $session = isset($_REQUEST['session']) ? $_REQUEST['session'] : '';
        $time_stamp = isset($_REQUEST['time_stamp']) ? $_REQUEST['time_stamp'] : 0;
        $client_time_offset = isset($_REQUEST['client_time_offset']) ? $_REQUEST['client_time_offset'] : 0;
        $session_data = $this->_getSessionData();
        
        if(!empty($session_data))
        {
            $data_request = isset($_REQUEST['data_request']) ?   json_decode(stripslashes($_REQUEST['data_request']),true) : array();
            if(isset($data_request['messages']) && !empty($data_request['messages']))
            {
                $desk = $data_request['desk'];
                if($desk && isset($desk['id']))
                {
                    $messages = array();
                    foreach($data_request['messages'] as $time => $m)
                    {
                        $utc_time = $time_stamp ;//+ ($client_time_offset * 60 * 1000);
                        $messages[$utc_time] = $m;
                    }
                    if($op_table->addMessage($desk['id'],$data_request['messages']))
                    {
                        $result['status'] = 1;
                        $result['message'] = __('Your request has been sent to our waiter. Please wait.','openpos');
                    }else{
                        $result['message'] = __('All by waiter is busy. Pls try later','openpos');
                    }
                }
            }
        }
        return $result;
    }
    public function pull_messages(){
        global $op_table;

        $result = array(
            'status' => 0,
            'data' => array(),
            'message' => __('Unknow message','openpos')
        );
        $session = isset($_REQUEST['session']) ? $_REQUEST['session'] : '';
        $session_data = $this->_getSessionData();
        if(!empty($session_data))
        {
            $time_stamp = isset($_REQUEST['time_stamp']) ? $_REQUEST['time_stamp'] : 0;
            $client_time_offset = isset($_REQUEST['client_time_offset']) ? $_REQUEST['client_time_offset'] : 0;
            $warehouse_id = isset($session_data['login_warehouse_id']) ? $session_data['login_warehouse_id'] : -1;
            if($warehouse_id >= 0)
            {
                $tables = $op_table->tables($warehouse_id);
                $messages = array();
                foreach($tables as $table)
                {
                    $table_id = $table['id'];
                    $_messages = $op_table->getMessages($table_id);
                    foreach($_messages as $k => $v)
                    {
                        $messages[$k] = $v;
                    }
                }
                krsort($messages);
                
                if(!empty($messages))
                {
                    $result['status'] = 1;
                    $result['data'] = $messages;
                }
            }
           
        }
        
        return $result;
    }
    public function delete_messages(){
        global $op_table;
        $result = array(
            'status' => 0,
            'data' => array(),
            'message' => __('Unknow message','openpos')
        );
        $session = isset($_REQUEST['session']) ? $_REQUEST['session'] : '';
        $session_data = $this->_getSessionData();
        if(!empty($session_data))
        {
            $time_stamp = isset($_REQUEST['time_stamp']) ? $_REQUEST['time_stamp'] : 0;
            $client_time_offset = isset($_REQUEST['client_time_offset']) ? $_REQUEST['client_time_offset'] : 0;
            $warehouse_id = isset($session_data['login_warehouse_id']) ? $session_data['login_warehouse_id'] : -1;
            if($warehouse_id >= 0)
            {
                $tables = $op_table->tables($warehouse_id);
                foreach($tables as $table)
                {
                    $table_id = $table['id'];
                    if($op_table->clearMessages($table_id))
                    {
                        $result['status'] = 1;
                        $result['message'] = __('Deleted.','openpos');
                    }
                }
                
            }
            
        }
        
        return $result;
    }

    


}