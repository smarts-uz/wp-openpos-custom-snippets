<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
/**
 * Created by PhpStorm.
 * User: anhvnit
 * Date: 3/12/18
 * Time: 10:10
 */

use Carbon\Carbon;
use Automattic\WooCommerce\Utilities\OrderUtil;
class Openpos_Core
{
    private $settings_api;
    public $plugin_info;
    public $_filesystem;
    public $_enable_hpos;
    
    public function __construct()
    {
        global $OPENPOS_SETTING;
        // check license
        
        //check requirement ....
        $this->settings_api = new OP_Settings();

        if(!class_exists('WP_Filesystem_Direct'))
        {
            require_once(ABSPATH . 'wp-admin/includes/class-wp-filesystem-base.php');
            require_once(ABSPATH . 'wp-admin/includes/class-wp-filesystem-direct.php');
        }
        $this->_filesystem = new WP_Filesystem_Direct(false);
    }

    public function init(){
        add_action( 'woocommerce_reduce_order_stock', array($this,'update_product_qty'), 10, 1 );
        
        add_filter( 'wp_mail_content_type', array($this,'op_set_html_mail_content_type'),10,1 );

        add_action('plugins_loaded', array($this,'plugins_loaded'));

        
        

    }
    public function getDefaultProductPostStatus(){
        $post_status = apply_filters( 'op_default_woocommerce_product_post_status', array('publish') );
        return $post_status;
    }
    public function getPosPostType(){
        $post_types = array('product','product_variation');
        return apply_filters('op_pos_post_types',$post_types);
    }
    public function getPosProductTypes(){
        $allow_types = array('simple','variation');
        return apply_filters('op_allow_product_type',$allow_types);
    }
    public function getPosLayBuyOrderStatus(){
        $status = 'pending';
        return apply_filters('op_laybuy_order_status',$status);
    }
    public function getIgnoreDeductStockOrderStatus(){
        $status = array('pending');
        return apply_filters('op_ignore_deduct_order_status',$status);
    }
    public function update_product_qty($order){
        


        foreach ( $order->get_items() as $item ) {
            $update = 0;
            if (!$item->is_type('line_item')) {
                continue;
            }

            $product = $item->get_product();

            if ( $product && $product->managing_stock() ) {

                $update = $product->get_id();
            }
            if($update)
            {
               
                $this->addProductChange($update);
                
            }
        }

    }

    public function getEndWorkOffsetTime(){
        
        return  apply_filters('op_report_end_work_day_time',0); // in minutes
    }
    public function getPluginInfo(){
        $this->plugin_info = get_plugin_data(OPENPOS_DIR.'woocommerce-openpos.php');
        return $this->plugin_info;
    }
    public function getProducts($args = array(),$display_variable = false)
    {

        $tax_args = array(
            'taxonomy'               => 'product_type',
            'orderby'                => 'name',
            'order'                  => 'ASC',
            'hide_empty'             => false,
        );
        $the_query = new WP_Term_Query($tax_args);
        $terms = $the_query->get_terms();
        $ignore_product_types = array();
        $accept_product_types = $this->getPosProductTypes();
        if(!$accept_product_types)
        {
            $accept_product_types = array();
        }
        if($display_variable)
        {
            $accept_product_types[] = 'variable';
        }
        foreach($terms as $t)
        {
            if(!in_array($t->slug,$accept_product_types))
            {
                
                $ignore_product_types[] = $t->slug;
            }
        }
        $args['post_type'] = $this->getPosPostType();
        $post_status = $this->getDefaultProductPostStatus();
        $args['post_status'] = $post_status;
        $args['suppress_filters'] = false;
        
        $defaults = array(
            'numberposts' => 5,
            'category' => 0, 
            'orderby' => 'date',
            'order' => 'DESC', 
            'include' => array(),
            'exclude' => array(), 
            'meta_key' => '',
            'meta_value' =>'', 
            'post_type' => 'product',
            'suppress_filters' => true
        );
        $r = wp_parse_args( $args, $defaults );
        if ( empty( $r['post_status'] ) )
            $r['post_status'] = ( 'attachment' == $r['post_type'] ) ? 'inherit' : $post_status;
        if ( ! empty($r['numberposts']) && empty($r['posts_per_page']) )
            $r['posts_per_page'] = $r['numberposts'];
        if ( ! empty($r['category']) )
            $r['cat'] = $r['category'];
        if ( ! empty($r['include']) ) {
            $incposts = wp_parse_id_list( $r['include'] );
            $r['posts_per_page'] = count($incposts);  // only the number of posts included
            $r['post__in'] = $incposts;
        } elseif ( ! empty($r['exclude']) )
            $r['post__not_in'] = wp_parse_id_list( $r['exclude'] );

        $r['ignore_sticky_posts'] = false;


        $r['tax_query'] = array(
            array(
                'taxonomy' => 'product_type',
                'field'    => 'slug',
                'terms'    =>  $ignore_product_types,
                'operator' => 'NOT IN',
            ),
        );
       
        if((isset($args['s']) && $args['s'] != '') || $display_variable )
        {
           
            // old logic
            if(isset($args['s']) && $args['s'] != '' )
            {
                $tmp_keywords = explode(',',$r['s']);
                unset($r['s']);
                $r['search_prod_title'] = isset($args['s']) ? trim($args['s']) : '';
                $meta_args = array('relation' => 'OR');
                foreach($tmp_keywords as $keyword)
                {
                    $meta_args[] = array(
                        'key'     => '_sku',
                        'value'   =>  trim($keyword) ,
                        'compare' => 'LIKE'
                    );
                    $meta_args[] = array(
                        'key'     => '_op_barcode',
                        'value'   =>  trim($keyword) ,
                        'compare' => 'LIKE'
                    );
                }
                $r['meta_query'] = $meta_args;
            }

            $query_args = apply_filters('op_get_products_s_args',$r);
           
            $get_posts = new WP_Query($query_args);
           
            $total_page = $get_posts->max_num_pages;
            return array('total'=>$get_posts->found_posts,'posts' => $get_posts->get_posts(),'total_page' => $total_page);
        }else{
            
            $args = array(
                'posts_per_page'   => $r['posts_per_page'],
                'post_type'        => $this->getPosPostType(),
                'post_status'   => $this->getDefaultProductPostStatus(),
                'tax_query' => array(
                    array(
                        'taxonomy' => 'product_type',
                        'field'    => 'slug',
                        'terms'    =>  $ignore_product_types,
                        'operator' => 'NOT IN',
                    ),
                ),
                'orderby' => $r['orderby'],
                'order' => $r['order'], 
                
                
            );
            

            if ( ! empty($r['include']) ) {
                $incposts = wp_parse_id_list( $r['include'] );
                $args['posts_per_page'] = count($incposts);  // only the number of posts included
                $args['post__in'] = $incposts;
            }

            $total = 0;
            $total_product_pages = 0;
            $posts = array();    
            
            if(isset($args['numberposts']) && $args['numberposts'] == -1 )
            {
                $args['posts_per_page'] =  -1;
               
            }else{
                if(isset($r['current_page']))
                {
                    $args['paged'] = $r['current_page'];
                }
            }
            $query_args = apply_filters('op_get_products_args',$args);
            
            $the_query = new WP_Query( $query_args );
            $posts = $the_query->get_posts();
            $total = $the_query->found_posts;
            if($args['posts_per_page'] > 0)
            {
                $total_product_pages = $the_query->max_num_pages;
            }
            return array( 'total'=>$total,'posts' => $posts,'total_page' => $total_product_pages);
        }
    }
    public function getAllVariableProducts()
    {
        $args = array(
            'posts_per_page'   => -1,
            'post_type'        => array('product_variation'),
            'post_status'      => 'publish'
        );
        $posts_array = get_posts($args);
        $result = array();
        foreach($posts_array as $post)
        {
            $parent_id =  $post->post_parent;
            if($parent_id)
            {
                $result[] = $parent_id;
            }
        }
        $arr = array_unique($result);
        $result = array_values($arr);
        return $result;
    }

    public function getBarcode($productId)
    {
        global $OPENPOS_SETTING;
        $this->settings_api = $OPENPOS_SETTING;
        $barcode_field = $this->settings_api->get_option('barcode_meta_key','openpos_label');
        if(!$barcode_field)
        {
            $barcode_field = '_op_barcode';
        }
        $barcode =  ''.$productId;

        if($barcode_field != 'post_id')
        {
            $barcode = get_post_meta($productId,$barcode_field,true);
            if($barcode)
            {
                $barcode =  ''.$barcode;
                $barcode = strtolower($barcode);
                return $barcode;
            }
            $format = '00000000000';
            $id_leng = strlen($productId);
            if($id_leng < 11)
            {
                $format = substr($format,0,(11 - $id_leng));
                $barcode = $format.$productId;
            }else{
                $barcode = ''.$productId;
            }
        }

        return  apply_filters('op_product_barcode',$barcode,$productId);
    }
    public function getProductIdByBarcode($barcode,$accept_product_id = true){
        global $OPENPOS_SETTING;
        $this->settings_api = $OPENPOS_SETTING;
        $barcode_field = $this->settings_api->get_option('barcode_meta_key','openpos_label');
        if(!$barcode_field)
        {
            $barcode_field = '_op_barcode';
        }
        $id = (int)$barcode;
        
        $product_id = 0;

        if($id  && $barcode_field == 'post_id')
        {
            $post = get_post($id);
            if(  $post &&  $post->post_type == 'product' || $post->post_type == 'product_variation')
            {
                $product_id = $post->ID;
            }

        }
        if(!$product_id)
        {
            $args = array(
                'post_type' => 'product',
                'meta_query' => array(
                    array(
                        'key' => $barcode_field,
                        'value' => $barcode,
                        'compare' => '=',
                    )
                )
            );
            if($id)
            {
                $args['post__not_in'] = array($id);
            }
            
            $query = new WP_Query($args);
            $posts = $query->get_posts();
            if(!empty($posts))
            {
                
                $post = end($posts);
                $product_id = $post->ID;
            }
        }
        
        if(!$product_id)
        {
            $args = array(
                'post_type' => 'product_variation',
                'meta_query' => array(
                    array(
                        'key' => $barcode_field,
                        'value' => $barcode,
                        'compare' => '=',
                    )
                )
            );
            if($id)
            {
                $args['post__not_in'] = array($id);
            }
            
            $query = new WP_Query($args);
            $posts = $query->get_posts();
            if(!empty($posts))
            {
                $post = end($posts);
                $product_id = $post->ID;
            }
        }
        if($id  && !$product_id && $accept_product_id)
        {
            $post = get_post($id);
            if($post)
            {
                if(  $post->post_type == 'product' || $post->post_type == 'product_variation')
                {
                    $product_id = $post->ID;
                }
            }
            
        }
        return $product_id;
    }
    public function getProductPrice($product,$format = false)
    {
        return $product->get_price();
    }
    public function getTransactions(){
        
    }


    public function getPosRegisterOrderByDate($register_id,$from,$to,$config_status = array()){
        if(empty($config_status))
        {
            $_config_status = $this->settings_api->get_option('pos_order_status','openpos_general');
            $config_status = array('wc-completed');
            if($_config_status)
            {
                $config_status = array($_config_status);
            }
            $config_status = apply_filters('op_order_report_status',$config_status);
        }
        if($this->enable_hpos())
        {
            $from = get_gmt_from_date($from);
            $to = get_gmt_from_date($to);
        }
        $post_type = 'shop_order';
        $args = array(
            'post_type' => $post_type,
            'date_query' => array(
                array(
                    'after'     => $from,//'January 1st, 2018',
                    'before'    => $to,
                    'inclusive' => true,
                ),
            ),
            'post_status'      => $config_status,
            'posts_per_page' => -1,
            'meta_query' => array(
                array(
                    'key' => '_op_order_source',
                    'value' => 'openpos',
                    'compare' => '=',
                ),
                array(
                    'key' => '_pos_order_cashdrawer',
                    'value' => $register_id,
                    'compare' => '=',
                )
            )
        );
        if($this->enable_hpos())
        {
            $args['order'] = 'DESC';
            $args['orderby'] = 'date_created';
        }
        $final_args = apply_filters('get_pos_register_order_by_date_args',$args,$post_type,$config_status );

        if($this->enable_hpos())
        {
            $final_args['_query_src'] = 'op_order_query';
            $data_store = WC_Data_Store::load( 'order' );
            $orders = $data_store->query( $final_args );
            return $orders;
        }else{
            $query = new WP_Query( $final_args );
            return $query->get_posts();
        }

    }
    public function getPosWarehouseOrderByDate($warehouse_id,$from,$to,$config_status = array()){
        if(empty($config_status))
        {
            $_config_status = $this->settings_api->get_option('pos_order_status','openpos_general');
            $config_status = array('wc-completed');
            if($_config_status)
            {
                $config_status = array($_config_status);
            }
            $config_status = apply_filters('op_order_report_status',$config_status);
        }
        
        if($this->enable_hpos())
        {
            $from = get_gmt_from_date($from);
            $to = get_gmt_from_date($to);
        }
        $post_type = 'shop_order';
        $args = array(
            'post_type' => $post_type,
            'date_query' => array(
                array(
                    'after'     => $from,//'January 1st, 2018',
                    'before'    => $to,
                    'inclusive' => true,
                ),
            ),
            'post_status'      => $config_status,
            'posts_per_page' => -1,
            'meta_query' => array(
                array(
                    'key' => '_op_order_source',
                    'value' => 'openpos',
                    'compare' => '=',
                ),
                array(
                    'key' => '_pos_order_warehouse',
                    'value' => $warehouse_id,
                    'compare' => '=',
                )
            )
        );
        if($this->enable_hpos())
        {
            $args['order'] = 'DESC';
            $args['orderby'] = 'date_created';
        }
        $final_args = apply_filters('get_pos_warehouse_order_by_date_args',$args,$post_type,$config_status );
        if($this->enable_hpos())
        {
            $final_args['_query_src'] = 'op_order_query';
            $data_store = WC_Data_Store::load( 'order' );
            $orders = $data_store->query( $final_args );
            return $orders;
        }else{
            $query = new WP_Query( $final_args );
            return $query->get_posts();
        }
    }
    public function getPosOrderByDate($from,$to, $config_status = array())
    {
        if(empty($config_status))
        {
            $_config_status = $this->settings_api->get_option('pos_order_status','openpos_general');
            $config_status = array('wc-completed');
            if($_config_status)
            {
                $config_status = array($_config_status);
            }
            
            
            $config_status = apply_filters('op_order_report_status',$config_status);
        }
        if($this->enable_hpos())
        {
            $from = get_gmt_from_date($from);
            $to = get_gmt_from_date($to);
        }
        
        $post_type = 'shop_order';
        $args = array(
            'post_type' => $post_type,
            'date_query' => array(
                array(
                    'after'     => $from,//'January 1st, 2018',
                    'before'    => $to,
                    'inclusive' => true,
                ),
            ),
            'post_status'      => $config_status,
            'posts_per_page' => -1,
            'meta_query' => array(
                array(
                    'key' => '_op_order_source',
                    'value' => 'openpos',
                    'compare' => '=',
                )
            )
        );
        if($this->enable_hpos())
        {
            $args['order'] = 'DESC';
            $args['orderby'] = 'date_created';
        }
        

        $final_args = apply_filters('get_pos_order_by_date_args',$args,$post_type,$config_status );
        if($this->enable_hpos())
        {
            $final_args['_query_src'] = 'op_order_query';
            $data_store = WC_Data_Store::load( 'order' );
            $orders = $data_store->query( $final_args );
            
            return $orders;
        }else{
            $query = new WP_Query( $final_args );
            return $query->get_posts();
        }
       
    }

    public function getPosRegisterTransactionsByDate($register_id,$from,$to,$source_types = array(),$payment_code = ''){
        $post_type = 'op_transaction';

        $meta_query  = array();
        if(!empty($source_types))
        {
            $meta_query = array(
                'relation' => 'AND',
                'source_type_clause' => array(
                    'key' => '_source_type',
                    'value'   => $source_types,
                    'compare' => 'IN',
                ),
                    
            );
        }
        if($payment_code)
        {
            if(empty($meta_query))
            {
                $meta_query['relation'] = 'AND';
            }
            $meta_query['_payment_code'] = array(
                'key' => '_payment_code',
                'value' => $payment_code,
                'compare' => '=',
            );
        }

        $args = array(
            'post_type' => $post_type,
            'date_query' => array(
                array(
                    'after'     => $from,
                    'before'    => $to,
                    'inclusive' => true,
                ),
            ),
            'post_status'      => 'any',
            'posts_per_page' => -1,
            'meta_query' => array(
                array(
                    'key' => '_pos_transaction_cashdrawer',
                    'value' => $register_id,
                    'compare' => '=',
                )
            )
        );
        if(!empty($meta_query))
        {
            $args['meta_query'] = $meta_query;
        }
        $final_args = apply_filters('get_pos_register_transactions_by_date_args',$args,$post_type );
        $query = new WP_Query( $final_args );
       
        return $query->get_posts();
    }
    public function getPosWarehouseTransactionsByDate($warehouse_id,$from,$to,$source_types = array(),$payment_code = ''){
        $post_type = 'op_transaction';

        $meta_query  = array();
        if(!empty($source_types))
        {
            $meta_query = array(
                'relation' => 'AND',
                'source_type_clause' => array(
                    'key' => '_source_type',
                    'value'   => $source_types,
                    'compare' => 'IN',
                ),
                    
            );
        }
        if($payment_code)
        {
            if(empty($meta_query))
            {
                $meta_query['relation'] = 'AND';
            }
            $meta_query['_payment_code'] = array(
                'key' => '_payment_code',
                'value' => $payment_code,
                'compare' => '=',
            );
        }

        $args = array(
            'post_type' => $post_type,
            'date_query' => array(
                array(
                    'after'     => $from,
                    'before'    => $to,
                    'inclusive' => true,
                ),
            ),
            'post_status'      => 'any',
            'posts_per_page' => -1,
            'meta_query' => array(
                array(
                    'key' => '_pos_transaction_warehouse',
                    'value' => $warehouse_id,
                    'compare' => '=',
                )
            )
        );
        if(!empty($meta_query))
        {
            $args['meta_query'] = $meta_query;
        }
        $final_args = apply_filters('get_pos_warehouse_transactions_by_date_args',$args,$post_type );
        $query = new WP_Query( $final_args );
        return $query->get_posts();
    }
    public function getPosTransactionsByDate($from,$to,$source_types = array(),$payment_code = '')
    {
        $post_type = 'op_transaction';

        $meta_query  = array();
        if(!empty($source_types))
        {
            $meta_query = array(
                'relation' => 'AND',
                'source_type_clause' => array(
                    'key' => '_source_type',
                    'value'   => $source_types,
                    'compare' => 'IN',
                ),
                    
            );
        }
        if($payment_code)
        {
            if(empty($meta_query))
            {
                $meta_query['relation'] = 'AND';
            }
            $meta_query['_payment_code'] = array(
                'key' => '_payment_code',
                'value' => $payment_code,
                'compare' => '=',
            );
        }

        $args = array(
            'post_type' => $post_type,
            'date_query' => array(
                array(
                    'after'     => $from,
                    'before'    => $to,
                    'inclusive' => true,
                ),
            ),
            'post_status'      => 'any',
            'posts_per_page' => -1
        );
        if(!empty($meta_query))
        {
            $args['meta_query'] = $meta_query;
        }
        $final_args = apply_filters('get_pos_transactions_by_date_args',$args,$post_type );
        $query = new WP_Query( $final_args );
        return $query->get_posts();
    }
    public function getDebtByDate($from,$to,$config_status = array(),$warehouse_id = -1,$register_id = 0){
        if(empty($config_status))
        {
            
            $config_status = array('wc-pending');
            $config_status = apply_filters('op_order_debt_status',$config_status);
        }

        $meta_query_args = [
           
            array(
                'key' => '_op_remain_paid',
                'value' => 0,
                'compare' => '>',
                'type' => 'DECIMAL(10,3)'
            )
        ];
        if($warehouse_id >= 0)
        {
            $meta_query_args[] = array(
                'key' => '_pos_order_warehouse',
                'value' => $warehouse_id,
                'compare' => '=',
            );
        }
        if($register_id > 0)
        {
            $meta_query_args[] = array(
                'key' => '_pos_order_cashdrawer',
                'value' => $register_id,
                'compare' => '=',
            );
        }

        $post_type = 'shop_order';

        $args = array(
            'post_type' => $post_type,
            'date_query' => array(
                array(
                    'after'     => $from,//'January 1st, 2018',
                    'before'    => $to,
                    'inclusive' => true,
                ),
            ),
            'post_status'      => $config_status,
            'posts_per_page' => -1,
            'meta_query' => $meta_query_args
        );
       
        $args['order'] = 'DESC';
        $args['orderby'] = 'date_created';
        
        $final_args = apply_filters('get_pos_get_debt_by_date_args',$args );
        if($this->enable_hpos())
        {
            $final_args['_query_src'] = 'op_order_query';
            $data_store = WC_Data_Store::load( 'order' );
            $orders = $data_store->query( $final_args );
            return $orders;
        }else{
            
            $query = new WP_Query( $final_args );
            return $query->get_posts();
        }
    }


    public function convertToUtc($ranges,$time_offset_mins){
        $start = $ranges['start'];
        $end = $ranges['end'];
        $start = new Carbon($start,"UTC");
        $start->addMinutes($time_offset_mins);

        $end = new Carbon($end,"UTC");
        $end->addMinutes($time_offset_mins);

        $ranges['start'] = $start->toDateTimeString();
        $ranges['end'] = $end->toDateTimeString();
        if(isset($ranges['ranges']))
        {
            foreach($ranges['ranges'] as $i => $range){

                $start = $range['from'];
                $end = $range['to'];
                $start = new Carbon($start,"UTC");
                $start->addMinutes($time_offset_mins);

                $end = new Carbon($end,"UTC");
                $end->addMinutes($time_offset_mins);

                $ranges['ranges'][$i]['from'] = $start->toDateTimeString();
                $ranges['ranges'][$i]['to'] = $end->toDateTimeString();
            }
        }
        return $ranges;
    }


    public function getReportRanges($duration,$start = false,$end = false,$time_offset_mins = 0,$limit_time = true,$_range_type = ''){

        $current_from = Carbon::now();

        $current_to = Carbon::now();
        $timezone = wc_timezone_string();
        if($timezone)
        {
            $current_from = Carbon::now($timezone);

            $current_to = Carbon::now($timezone);
        }
        $day_in_week = $current_from->dayOfWeek;
        $day_in_month = $current_from->day - 1;
        $month_in_year = $current_from->month - 1;
        $quarter = 1;
        if($month_in_year > 3)
        {
            $quarter = 2;
        }
        if($month_in_year > 6)
        {
            $quarter = 3;
        }
        if($month_in_year > 9)
        {
            $quarter = 4;
        }

        $tmp_diff_mins = $this->getEndWorkOffsetTime();

        

        switch ($duration)
        {
            case 'yesterday':
                $start = $current_from->subDays(1);
                $end = $current_to->subDays(1);
                break;
            case 'today':
                $start = $current_from;
                $end = $current_to;
                break;
            case 'last_7_days':
                $start = $current_from->subDays(7);
                $end = $current_to;
                break;
            case 'last_30_days':
                $start = $current_from->subDays(30);
                $end = $current_to;
                break;
            case 'this_week':
                $start = $current_from->subDays($day_in_week);
                $end = $current_to;
                break;
            case 'this_month':
                $start = $current_from->subDays($day_in_month);
                $end = $current_to;
                break;
            case 'this_quarter':
                $start_month = ($quarter - 1 ) * 3 + 1;
                $current_from->month = $start_month;
                $current_from->day = 1;
                $start = $current_from;
                $end = $current_to;
                break;
            case 'this_year':
                $start = $current_from->subMonths($month_in_year)->subDays($day_in_month);
                $end = $current_to;
                break;
            default :
                $start_timed = new \DateTime($start);
                $end_time = new \DateTime($end);
                $start_day = $start_timed->format('j');
                $start_month = $start_timed->format('n');
                $start_year = $start_timed->format('Y');
                $start = Carbon::create($start_year, $start_month, $start_day, 0);
                $end_day = $end_time->format('j');
                $end_month = $end_time->format('n');
                $end_year = $end_time->format('Y');
                $end = Carbon::create($end_year, $end_month, $end_day, 0);
                break;
        }
        $start->hour = 0;
        $start->minute = 0;
        $start->second = 0;
        $result['start'] = $start->toDateTimeString();

        $end->hour = 23;
        $end->minute = 59;
        $end->second = 59;
        $end->addMinutes($tmp_diff_mins);
        $result['end'] = $end->toDateTimeString();
        //total summaries

        //chart

       

        $diff_day = $start->diffInDays($end);
        $diff_month = $start->diffInMonths($end);
        $diff_hour = $start->diffInHours($end);
        if($limit_time)
        {
            if( $diff_month > 12 || $diff_day < 0)
            {
                throw new Exception(__('Time Range is invalid. Maximum is 365 days','openpos'));
            }
        }
        
        if(!$_range_type){
            $range_type = 'hour';
            if($diff_month < 2)
            {
                if($diff_day > 0)
                {
                    $range_type = 'day';
                }
            }else{
                $range_type = 'month';
            }
        }else{
            $range_type = $_range_type;
        }
       
        $ranges = array();
        $result['range_type'] = $diff_hour;
        switch ($range_type)
        {
            case 'month':
                for($i = 1;$i<= ($diff_month +1 ); $i++)
                {
                    $tmp_start_from = Carbon::create($start->year, $start->month, $start->day, $start->hour);
                    $tmp_start_to = Carbon::create($start->year, $start->month, $start->day, $start->hour);
                    $tmp_start_from->addMonths($i - 1);
                    $tmp_start_to->addMonths($i);

                    
                    $tmp_start_to->addMinutes($tmp_diff_mins);

                    $ranges[] = [
                        'from' => $tmp_start_from->toDateTimeString(),
                        'to' => $tmp_start_to->toDateTimeString(),
                        'label' => $tmp_start_from->format('m-Y')
                    ];
                }
                break;
            case 'day':
                for($i = 1;$i<= ($diff_day +1 ); $i++)
                {
                    $tmp_start_from = Carbon::create($start->year, $start->month, $start->day, $start->hour);
                    $tmp_start_to = Carbon::create($start->year, $start->month, $start->day, $start->hour);
                    $tmp_start_from->addDays($i - 1);
                    $tmp_start_to->addDays($i);

                    
                    $tmp_start_to->addMinutes($tmp_diff_mins);

                    $ranges[] = [
                        'from' => $tmp_start_from->toDateTimeString(),
                        'to' => $tmp_start_to->toDateTimeString(),
                        'label' => $tmp_start_from->format('d-m-Y')
                    ];
                }
                break;
            default:
                for($i = 1;$i<= ($diff_hour +1 ); $i++)
                {
                    $tmp_start_from = Carbon::create($start->year, $start->month, $start->day, $start->hour);
                    $tmp_start_to = Carbon::create($start->year, $start->month, $start->day, $start->hour);
                    $tmp_start_from->addHours($i - 1);
                    $tmp_start_to->addHours($i);

                    
                    $tmp_start_to->addMinutes($tmp_diff_mins);

                    $ranges[] = [
                        'from' => $tmp_start_from->toDateTimeString(),
                        'to' => $tmp_start_to->toDateTimeString(),
                        'label' => $tmp_start_from->format('h:i A')
                    ];
                }
                break;
        }
        $result['ranges'] = $ranges;
        return $result;
    }


    function getClientIp(){
        if ( isset( $_SERVER['HTTP_X_REAL_IP'] ) ) { // WPCS: input var ok, CSRF ok.
            return sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_REAL_IP'] ) );  // WPCS: input var ok, CSRF ok.
        } elseif ( isset( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) { // WPCS: input var ok, CSRF ok.
            // Proxy servers can send through this header like this: X-Forwarded-For: client1, proxy1, proxy2
            // Make sure we always only send through the first IP in the list which should always be the client IP.
            return (string) rest_is_ip_address( trim( current( preg_split( '/,/', sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) ) ) ) ); // WPCS: input var ok, CSRF ok.
        } elseif ( isset( $_SERVER['REMOTE_ADDR'] ) ) { // @codingStandardsIgnoreLine
            return sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ); // @codingStandardsIgnoreLine
        }
        return '0.0.0.0';
    }

    function  render_order_date_column($order_object) {
        if(!$order_object)
        {
            return '&ndash;';
        }
        $order_timestamp = $order_object->get_date_created() ? $order_object->get_date_created()->getTimestamp() : '';

        if ( ! $order_timestamp ) {
            return '&ndash;';

        }

        // Check if the order was created within the last 24 hours, and not in the future.
        if ( $order_timestamp > strtotime( '-1 day', current_time( 'timestamp', true ) ) && $order_timestamp <= current_time( 'timestamp', true ) ) {
            $show_date = sprintf(
            /* translators: %s: human-readable time difference */
                _x( '%s ago', '%s = human-readable time difference', 'openpos' ),
                human_time_diff( $order_object->get_date_created()->getTimestamp(), current_time( 'timestamp', true ) )
            );
        } else {
            $show_date = $order_object->get_date_created()->date_i18n( apply_filters( 'woocommerce_admin_order_date_format', __( 'M j, Y', 'openpos' ) ) );
        }
        return sprintf(
            '<time datetime="%1$s" title="%2$s">%3$s</time>',
            esc_attr( $order_object->get_date_created()->date( 'c' ) ),
            esc_html( $order_object->get_date_created()->date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ) ) ),
            esc_html( $show_date )
        );
    }

    function render_ago_date_by_time_stamp($date_string){
        $WC_DateTime = wc_string_to_datetime( $date_string );
        $order_timestamp = $WC_DateTime->getTimestamp() ;


        // Check if the order was created within the last 24 hours, and not in the future.
        if ( $order_timestamp > strtotime( '-1 day', current_time( 'timestamp', true ) ) && $order_timestamp <= current_time( 'timestamp', true ) ) {
            $show_date = sprintf(
            /* translators: %s: human-readable time difference */
                _x( '%s ago', '%s = human-readable time difference', 'openpos' ),
                human_time_diff( $order_timestamp, current_time( 'timestamp', true ) )
            );
        } else {
            $show_date = $WC_DateTime->date_i18n( apply_filters( 'woocommerce_admin_order_date_format', __( 'M j, Y', 'openpos' ) ) );
        }
        return sprintf(
            '<time datetime="%1$s" class="session-ago" title="%2$s">%3$s</time>',
            esc_attr( $WC_DateTime->date( 'c' ) ),
            esc_html( $WC_DateTime->date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ) ) ),
            esc_html( $show_date )
        );
    }
    function formatTimeStamp($timestamp){
        $datetime = new WC_DateTime( "@{$timestamp}", new DateTimeZone( 'UTC' ) );
        // Set local timezone or offset.
        if ( get_option( 'timezone_string' ) ) {
            $datetime->setTimezone( new DateTimeZone( wc_timezone_string() ) );
        } else {
            $datetime->set_utc_offset( wc_timezone_offset() );
        }
        return $datetime;
    }

    function additionPaymentMethodDetails(){
        $payment_options = array();
        $payment_options['chip_pin'] = array(
           'code' => 'chip_pin',
           'admin_title' => __('Chip & PIN','openpos'),
           'frontend_title' => __('Chip & PIN','openpos'),
            'description' => ''
        );

        // $payment_options['stripe'] = array(
        //     'code' => 'stripe',
        //     'admin_title' => __('Credit Card ( Stripe ) - Online Payment for POS only','openpos'),
        //     'frontend_title' => __('Credit Card','openpos'),
        //     'description' => ''
        // );
        return  apply_filters('op_addition_payment_methods',$payment_options);
    }

    function additionPaymentMethods(){
        $payment_options = array();
        foreach($this->additionPaymentMethodDetails() as $code => $m)
        {
            $payment_options[$code] = $m['admin_title'];
        }

        return $payment_options;
    }

    function allow_online_payment(){ // yes or no
        global $OPENPOS_SETTING;
        $payment_methods = $OPENPOS_SETTING->get_option('payment_methods','openpos_payment');

        if(isset($payment_methods['stripe']))
        {
            $stripe_public_key = $OPENPOS_SETTING->get_option('stripe_public_key','openpos_payment');
            $stripe_secret_key = $OPENPOS_SETTING->get_option('stripe_secret_key','openpos_payment');
            if($stripe_public_key && $stripe_secret_key)
            {
                return 'yes';
            }

        }
        return 'no';
    }

    function formatPaymentMethods($methods){
        $payment_methods = array();
        $addition_payments = $this->additionPaymentMethodDetails();
        $payment_gateways   = WC_Payment_Gateways::instance()->payment_gateways();
        if($methods && !empty($methods))
        {
            foreach($methods as $code => $title)
            {
                $type = 'offline';
                $online_checkout_type = 'external';
                if(isset($addition_payments[$code]))
                {
                    $title = $addition_payments[$code]['frontend_title'];
                }
                $allow_partial_payment = true;
                
                if($code == 'stripe'){
                    $type = 'online';
                    $online_checkout_type = 'inline';
                }
                // Get an instance of the WC_Payment_Gateways object
                $description = '';

                if(isset($payment_gateways[$code]))
                {
                    $payment_gateway    = $payment_gateways[$code];
                    if(isset($payment_gateway->instructions))
                    {
                        $description = $payment_gateway->instructions;
                    }
                }
                $allow_refund = 'no'; // no, yes, offline , online
                if($code == 'chip_pin')
                {
                    $description = __('Click Generate to get a reference order number. Then process the payment using your chip & PIN device.','openpos');
                    $allow_refund = 'offline';
                }
                $payment_method_data = array(
                    'code' => $code,
                    'name' => $title,
                    'type' => $type,
                    'hasRef' => true,
                    'partial' => $allow_partial_payment,
                    'description' => $description,
                    'online_type' => $online_checkout_type,
                    'allow_refund' => $allow_refund,
                );
                $payment_methods[] = apply_filters('op_login_format_payment_data',$payment_method_data,$methods);
            }
        }
        return apply_filters('op_pos_payment_method_list',$payment_methods);
    }
    function getAllLanguage(){
        $langs = array(
            "ab",
            "aa",
            "af",
            "ak",
            "sq",
            "am",
            "ar",
            "an",
            "hy",
            "as",
            "av",
            "ae",
            "ay",
            "az",
            "bm",
            "ba",
            "eu",
            "be",
            "bn",
            "bh",
            "bi",
            "bs",
            "br",
            "bg",
            "my",
            "ca",
            "ch",
            "ce",
            "ny",
            "zh",
            "zh-Hans",
            "zh-Hant",
            "cv",
            "kw",
            "co",
            "cr",
            "hr",
            "cs",
            "da",
            "dv",
            "nl",
            "dz",
            "en",
            "eo",
            "et",
            "ee",
            "fo",
            "fj",
            "fi",
            "fr",
            "ff",
            "gl",
            "gd",
            "gv",
            "ka",
            "de",
            "el",
            "kl",
            "gn",
            "gu",
            "ht",
            "ha",
            "he",
            "hz",
            "hi",
            "ho",
            "hu",
            "is",
            "io",
            "ig",
            "id",
            "in",
            "ia",
            "ie",
            "iu",
            "ik",
            "ga",
            "it",
            "ja",
            "jv",
            "kl",
            "kn",
            "kr",
            "ks",
            "kk",
            "km",
            "ki",
            "rw",
            "rn",
            "ky",
            "kv",
            "kg",
            "ko",
            "ku",
            "kj",
            "lo",
            "la",
            "lv",
            "li",
            "ln",
            "lt",
            "lu",
            "lg",
            "lb",
            "gv",
            "mk",
            "mg",
            "ms",
            "ml",
            "mt",
            "mi",
            "mr",
            "mh",
            "mo",
            "mn",
            "na",
            "nv",
            "ng",
            "nd",
            "ne",
            "no",
            "nb",
            "nn",
            "ii",
            "oc",
            "oj",
            "cu",
            "or",
            "om",
            "os",
            "pi",
            "ps",
            "fa",
            "pl",
            "pt",
            "pa",
            "qu",
            "rm",
            "ro",
            "ru",
            "se",
            "sm",
            "sg",
            "sa",
            "sr",
            "sh",
            "st",
            "tn",
            "sn",
            "ii",
            "sd",
            "si",
            "ss",
            "sk",
            "sl",
            "so",
            "nr",
            "es",
            "su",
            "sw",
            "ss",
            "sv",
            "tl",
            "ty",
            "tg",
            "ta",
            "tt",
            "te",
            "th",
            "bo",
            "ti",
            "to",
            "ts",
            "tr",
            "tk",
            "tw",
            "ug",
            "uk",
            "ur",
            "uz",
            "ve",
            "vi",
            "vo",
            "wa",
            "cy",
            "wo",
            "fy",
            "xh",
            "yi",
            "ji",
            "yo",
            "za",
            "zu",
        );
        sort($langs);
        $result = array(
            '_auto' => 'Auto Detect'
        );
        foreach($langs as $lang)
        {
            $result[$lang] = $lang;
        }

        return $result;
    }

    public function formatReceiptSetting($setting,$op_incl_tax_mode= false)
    {
        $payment_methods = '<ul class="payment-methods">';
        $payment_methods .= '<% payment_method.forEach(function(payment){ %>';
        $payment_methods .= '<li><%= payment.name %> : <%= payment.paid %></li>';

        $payment_methods .= '<% if (payment.code == "cash" && payment.return > 0) { %>';
        $payment_methods .= '<li>'.__('Return','openpos').' : <%= payment.return %></li>';
        $payment_methods .= '<% } %>';

        $payment_methods .= '<% }); %>';
        $payment_methods .= '</ul>';
        $rules = array(
            '< %' => '<%',
            '[customer_name]' => '<%= customer.name %>',
            '[customer_phone]' => '<%= customer.phone %>',
            '[customer_firstname]' => '<%= customer.firstname %>',
            '[customer_lastname]' => '<%= customer.lastname %>',
            '[sale_person]' => '<% if(typeof sale_person_name != "undefined") { %> <%= sale_person_name %> <% } %>',
            '[created_at]' => '<%= created_at %>',
            '[order_number]' => '<%= order_number %>',
            '[order_number_format]' => '<%= order_number_format %>',
            '[order_note]' => '<%= note %>',
            '[customer_email]' => '<%= customer.email %>',
            '[payment_method]' => $payment_methods,
        );

        //receipt css

        $file_name = 'receipt_css.txt';
        $theme_file_path = rtrim(get_stylesheet_directory(),'/').'/woocommerce-openpos/'.$file_name;
        if(file_exists($theme_file_path))
        {
            $setting['receipt_css'] = $this->_filesystem->get_contents($theme_file_path);
        }
        // receipt_template_footer

        $file_name = 'receipt_template_footer.txt';
        $theme_file_path = rtrim(get_stylesheet_directory(),'/').'/woocommerce-openpos/'.$file_name;
        if(file_exists($theme_file_path))
        {
            $setting['receipt_template_footer'] = $this->_filesystem->get_contents($theme_file_path);
        }

        if(isset($setting['receipt_template_footer']))
        {
            $setting['receipt_template_footer'] = str_replace(array_keys($rules), array_values($rules), html_entity_decode($setting['receipt_template_footer']));
        }else{
            $setting['receipt_template_footer'] = '';
        }
        //receipt_template_header
        $file_name = 'receipt_template_header.txt';
        $theme_file_path = rtrim(get_stylesheet_directory(),'/').'/woocommerce-openpos/'.$file_name;
        if(file_exists($theme_file_path))
        {
            $setting['receipt_template_header'] = $this->_filesystem->get_contents($theme_file_path);
        }

        if(isset($setting['receipt_template_header']))
        {
            $setting['receipt_template_header'] = str_replace(array_keys($rules), array_values($rules), html_entity_decode($setting['receipt_template_header']));
        }else{
            $setting['receipt_template_header'] = '';
        }


        $file_name = 'receipt_template_body.txt';


        $file_path = rtrim(OPENPOS_DIR,'/').'/default/'.$file_name;

        $theme_file_path = rtrim(get_stylesheet_directory(),'/').'/woocommerce-openpos/'.$file_name;
        if(file_exists($theme_file_path))
        {
            $file_path = $theme_file_path;
        }else{
            if($op_incl_tax_mode)
            {
                $file_name = 'receipt_incl_tax_template_body.txt';
                $file_path = rtrim(OPENPOS_DIR,'/').'/default/'.$file_name;
            }
        }
        $_gift_file_name = 'receipt_gift_template_body.txt';
        $gift_file_path = rtrim(OPENPOS_DIR,'/').'/default/'.$_gift_file_name;

        $gift_theme_file_path = rtrim(get_stylesheet_directory(),'/').'/woocommerce-openpos/'.$_gift_file_name;
        if(file_exists($gift_theme_file_path))
        {
            $gift_file_path = $gift_theme_file_path;
        }
        $setting['receipt_gift_template'] = '';

        if(file_exists($gift_file_path))
        {
            $setting['receipt_gift_template'] = $this->_filesystem->get_contents($gift_file_path);;
        }

        if($this->_filesystem->is_file($file_path))
        {
            $receipt_template = $this->_filesystem->get_contents($file_path);

        }else{
            $receipt_template = '<table>
            <tr class="tabletitle">
                <td class="item"><h2>'.__('Item','openpos' ).'</h2></td>
                <td class="qty"><h2>'.__('Price','openpos' ).'</h2></td>
                <td class="qty"><h2>'.__('Qty','openpos' ).'</h2></td>
                <td class="qty"><h2>'.__('Discount','openpos' ).'</h2></td>
                <td class="total"><h2>'.__('Total','openpos' ).'</h2></td>
            </tr>
            <% items.forEach(function(item){ %>
            <tr class="service">
                <td class="tableitem item-name">
                    <p class="itemtext"><%= item.name %></p>
                    <% if(item.sub_name.length > 0){ %>   
                         
                           <p class="option-item"> <%- item.sub_name  %> </p>
                        
                    <% }; %>
                     
                </td>
                <td class="tableitem item-price"><p class="itemtext"><%= item.final_price %></p></td>
                <td class="tableitem item-qty"><p class="itemtext"><%= item.qty %></p></td>
                <td class="tableitem item-discount"><p class="itemtext"><%= item.final_discount_amount %></p></td>
                <td class="tableitem item-total"><p class="itemtext"><%= item.total %></p></td>
            </tr>
            <% }); %>
            <tr class="tabletitle">
                <td></td>
                <td class="Rate sub-total-title" colspan="3"><h2>'.__('Sub Total','openpos' ).'</h2></td>
                <td class="payment sub-total-amount"><h2><%= sub_total %></h2></td>
            </tr>
            <% if(shipping_cost > 0) {%>
            <tr class="tabletitle">
                <td></td>
                <td class="Rate shipping-title" colspan="3"><h2>'.__('Shipping','openpos' ).'</h2></td>
                <td class="payment shipping-amount"><h2><%= shipping_cost %></h2></td>
            </tr>
            <% } %>
            <tr class="tabletitle">
                <td></td>
                <td class="Rate cart-discount-title" colspan="3"><h2>'.__('Discount','openpos' ).'</h2></td>
                <td class="payment cart-discount-amount"><h2><%= final_discount_amount %></h2></td>
            </tr>
            <tr class="tabletitle">
                <td></td>
                <td class="Rate tax-title" colspan="3"><h2>'.__('Tax','openpos' ).'</h2></td>
                <td class="payment tax-amount"><h2><%= tax_amount %></h2></td>
            </tr>
            <tr class="tabletitle">
                <td></td>
                <td class="Rate grand-total-title" colspan="3"><h2>'.__('Grand Total','openpos' ).'</h2></td>
                <td class="payment grand-total-amount"><h2><%= grand_total %></h2></td>
            </tr>
            </table>';
        }
        if(!isset($setting['receipt_css']))
        {
            $setting['receipt_css'] = '';
        }
        $setting['receipt_css'] = 'html,body{ height: fit-content; }'.$setting['receipt_css'];
        $setting['receipt_template_header'] =  apply_filters('op_receipt_template_header_data',do_shortcode($setting['receipt_template_header']),$setting);
        $setting['receipt_template_footer'] =  apply_filters('op_receipt_template_footer_data',do_shortcode($setting['receipt_template_footer']),$setting);
        $setting['receipt_template'] =  apply_filters('op_receipt_template_data',$receipt_template,$setting);

        
        

        $lang = $this->settings_api->get_option('pos_language','openpos_pos');
        if($lang == 'vi')
        {
            $setting['product_search_fields'] = array('search_keyword');
        }

        return $setting;
    }
    public function addProductChange($product_id,$warehouse_id = 0){
        $time = time();
        update_post_meta($product_id,'_openpos_product_version_'.$warehouse_id,$time);

        $parent_id = wp_get_post_parent_id($product_id);
        if($parent_id)
        {
            update_post_meta($parent_id,'_openpos_product_version_'.$warehouse_id,$time);
        }

        update_option('_openpos_product_version_'.$warehouse_id,$time);
        do_action('op_add_product_change_after',$product_id,$warehouse_id,$time,$this);
    }
    public function formart_draft_order($order_data){
        $result = array();
        if(isset($order_data['items']) && !empty($order_data['items']))
        {
            $items = array();
            foreach($order_data['items'] as $item)
            {
                $product_id = 0;
                $product_barcode = 0;
                if( isset($item['product']) && !empty($item['product']))
                {
                    $product_id = $item['product']['id'];
                    $product_barcode = $item['product']['barcode'];
                    $tmp = array(
                        'product_id' => $product_id,
                        'product_name' => $item['name'],
                        'barcode' => $product_barcode,
                        'qty' => $item['qty'],
                        'sub_name' => $item['sub_name'],
                        'note' => $item['note']
                    );
                    $items[] = $tmp;
                }else{
                    $tmp = array(
                        'product_id' => $product_id,
                        'product_name' => $item['name'],
                        'sub_name' => isset($item['sub_name']) ? $item['sub_name'] : '',
                        'barcode' => $product_barcode,
                        'qty' => $item['qty'],
                        'note' => isset($item['note']) ? $item['note'] : ''
                    );
                    $items[] = $tmp;
                }

            }
            if(!empty($items))
            {
                $result = array(
                    'items' => $items,
                    'customer' => array(),
                    'note' => isset($order_data['note']) ? $order_data['note'] : ''
                );
            }
        }
        return $result;
    }
    public function  convertToShopTime($time_str){
        $timezone = wc_timezone_string();

        if($timezone)
        {
            // $default_time_zone = date_default_timezone_get();
            // $date = Carbon::createFromFormat('d-m-Y H:i:s', $time_str, $default_time_zone);
            // $date->setTimezone($timezone);
            // return $date->toDateTimeString();
            return get_date_from_gmt($time_str,'d-m-Y H:i:s');
        }else{
            return $time_str;
        }
    }

    public function getReceiptFontCss(){
        $font_css = array();
        $font_css['receipt_font'] = OPENPOS_URL.'/pos/font.css';
        return $font_css;
    }
    public function allow_upload($file = array()){
        
         $allow_extensions = array(
            '.jpg',
            '.jpeg',
            '.png',
            '.gif',
            '.pdf',
            '.doc',
            '.docx',
            '.ppt', 
            '.pptx',
            '.csv', 
            '.xls', 
            '.xlsx',
            '.mp3',
            '.m4a',
            '.wav',
            '.mp3', 
            '.m4a' , 
            '.wav',
            '.mp4', 
            '.m4v',
            '.mpg',
            '.wmv',
            '.mov',
            '.avi',
            '.swf',
            '.zip'
         );
         if(isset($file['type']) && isset($file['name']))
         {
            $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
            $ext = strtolower($ext);
            if(in_array('.'.$ext,$allow_extensions))
            {
                return true;
            }
         }
         return false;   
    }
    function search_customer_name($term,$limit = 5){
        global $wpdb;
        $search_string = esc_attr( trim( $term ) );
        $prefix = $wpdb->prefix;
        $sql = "SELECT t1.* FROM `".$prefix ."usermeta` t1  LEFT JOIN `".$prefix ."usermeta` t2 ON ( t1.user_id = t2.user_id AND CONCAT(t1.meta_value,' ',t2.meta_value) like '%s') WHERE ";
        $sql .= " (t1.meta_key = 'first_name' AND t2.meta_key = 'last_name') ";
        $sql .= " OR (t1.meta_key = 'billing_first_name' AND t2.meta_key = 'billing_last_name') ";
        $sql .= " OR (t1.meta_key = 'shipping_first_name' AND t2.meta_key = 'shipping_last_name') ";
        $sql .= " LIMIT 0,".$limit;

        $final_sql = $wpdb->prepare($sql ,'%'.$search_string.'%');
        
        $results = $wpdb->get_results($final_sql);
        if(empty($results))
        {
            $sql = "SELECT t1.* FROM `".$prefix ."usermeta` t1  LEFT JOIN `".$prefix ."usermeta` t2 ON ( t1.user_id = t2.user_id AND CONCAT(t1.meta_value,' ',t2.meta_value) like '%s') WHERE t1.meta_key = 'billing_first_name' AND t2.meta_key = 'billing_last_name' LIMIT 0,".$limit;
            $final_sql = $wpdb->prepare($sql ,'%'.$search_string.'%');
            $results = $wpdb->get_results($final_sql);
        }
        if(empty($results))
        {
            $sql = "SELECT t1.* FROM `".$prefix ."usermeta` t1  LEFT JOIN `".$prefix ."usermeta` t2 ON ( t1.user_id = t2.user_id AND CONCAT(t1.meta_value,' ',t2.meta_value) like '%s') WHERE t1.meta_key = 'shipping_first_name' AND t2.meta_key = 'shipping_last_name' LIMIT 0,".$limit;
            $final_sql = $wpdb->prepare($sql ,'%'.$search_string.'%');
            $results = $wpdb->get_results($final_sql);
        }
        $users = array();
        foreach($results as $u)
        {
            if($u->user_id)
            {
                $users[] = get_user_by('id', $u->user_id);
            }
        }
        $found_total = count($users);
        $use_lookup =  apply_filters('op_customer_search_use_lookup',false);
        if($found_total < $limit && $use_lookup)
        {
            $lookup_users = $this->search_customer_lookup_name($search_string,($limit - $found_total));
            
            foreach($lookup_users as $u)
            {

                if($u->user_id)
                {
                    $users[] = get_user_by('id', $u->user_id);
                }else{
                    if($u->customer_id)
                    {
                        $users[] = get_user_by('id', $u->customer_id);
                    }
                }
            }
        }
        
        return $users;
        
    }
    function search_customer_lookup_name($search_string,$limit = 10){
        global $wpdb;
       
        $customer_lookup_table = $wpdb->prefix . 'wc_customer_lookup';
        $search_string = strtolower($search_string);
        $sql = "SELECT * FROM `".$customer_lookup_table ."` WHERE LOWER(CONCAT(first_name,' ',last_name)) like '%s' LIMIT 0,".$limit;
        $final_sql = $wpdb->prepare($sql ,'%'.$search_string.'%');
        $results = $wpdb->get_results($final_sql);
        return $results;
        
    }
    function op_set_html_mail_content_type($content_type) {
        global $is_openpos_email;
        if($is_openpos_email)
        {
            $content_type = 'text/html';
        }
        return $content_type;
    }
    function get_pos_url($sub_str = ''){
     
        $pos_url = apply_filters('op_pos_url',OPENPOS_URL.'/pos/');
        if($sub_str)
        {
            $pos_url = trim(rtrim($pos_url,'/'));    
           
            $pos_url .= '/'.$sub_str;
        }
        return  $pos_url;
    }
    function get_kitchen_url($wareshouse_id = 0,$base = false){
     
        $pos_url = apply_filters('op_kitchen_url',OPENPOS_URL.'/kitchen/');
       
        if(!$base)
        {
            $pos_url = rtrim($pos_url,'/');
            $site_id = get_current_blog_id();
            if($site_id)
            {
                $pos_url.= '/index.php?site='.$site_id.'&id='.$wareshouse_id;
            }else{
                $pos_url.= '/index.php?id='.$wareshouse_id;
            }
            
        }
        return  $pos_url;
    }
    function get_bill_url($register_id = 0,$base = false){
     
        $pos_url = apply_filters('op_bill_url',OPENPOS_URL.'/bill/');
        
        if(!$base)
        {
            $pos_url = rtrim($pos_url,'/');
            $site_id = get_current_blog_id();
            if($site_id)
            {
                $pos_url.= '/index.php?site='.$site_id.'&id='.$register_id;
            }else{
                $pos_url.= '/index.php?id='.$register_id;
            }
            
        }
        
        return  $pos_url;
    }
    function get_customer_url($params = array()){
        $customer_url = apply_filters('op_customer_url',OPENPOS_URL.'/customer/');
        if(!empty($params))
        {
            $params_str = http_build_query($params);
            $customer_url = rtrim($customer_url,'/');
            $customer_url .= '/index.php?'.$params_str;
        }
        return $customer_url;
    }
    function get_hosting_time($local_time,$client_time_offset = 0){ // in min
        $hosting_offset =  wc_timezone_offset();  // in second
        $client_time_offset = $client_time_offset * 60;  // $client_time_offset in min
        $utc_time =  ceil($local_time / 1000) + ( $client_time_offset  + $hosting_offset);
        return $utc_time;

    }
    function enable_hpos(){
        if(!$this->_enable_hpos)
        {
            $woocommerce_feature_custom_order_tables_enabled = get_option('woocommerce_feature_custom_order_tables_enabled') ;
            $woocommerce_custom_orders_table_enabled = get_option('woocommerce_custom_orders_table_enabled') ;
            if($woocommerce_feature_custom_order_tables_enabled == 'yes' || $woocommerce_custom_orders_table_enabled == 'yes')
            {
                $this->_enable_hpos = true;
            }
        }
        
        return  apply_filters('op_enable_hpos',$this->_enable_hpos);
    }
    function plugins_loaded(){
       
        if ( OrderUtil::custom_orders_table_usage_is_enabled() ) {
           $this->_enable_hpos = true;
        } 
        //language
        $locale = determine_locale();
        unload_textdomain( 'openpos', true );
		load_textdomain( 'openpos', trailingslashit(OPENPOS_DIR).'languages/openpos-' . $locale . '.mo' );
        load_plugin_textdomain( 'openpos', false,  trailingslashit(OPENPOS_DIR).'languages/' );
        //end
    }
    function getTemplatePath($file_name){
        #$templath_path = OPENPOS_DIR.'templates/emails/';
        $templath_dir = 'woocommerce-openpos/';
        $check_dirs = array(
			trailingslashit( get_stylesheet_directory() ) . $templath_dir,
			trailingslashit( get_template_directory() ) . $templath_dir,
			trailingslashit( get_stylesheet_directory() ),
			trailingslashit( get_template_directory() ),
			trailingslashit( OPENPOS_DIR ) . 'templates/',
        );
        foreach ( $check_dirs as $dir ) {
			if ( file_exists( trailingslashit( $dir ) . $file_name ) ) {
				return trailingslashit( $dir ) ;
			}
		}
    }
    function _formatDiscountAmount($discount_str){
        $result = array();
        if($discount_str)
        {
            $tmp_cashes = explode('|',$discount_str);
            foreach($tmp_cashes as $c_str)
            {
                $c_str = trim($c_str);
                $type = 'fixed';
                if(strpos($c_str,'%') > 0)
                {
                    $type = 'percent';
                }
                $number_amount = str_replace('%','',$c_str);
                if(is_numeric($number_amount))
                {
                    $money_value = 1 * (float)$number_amount;
                    $tmp_money = array(
                        'type' => $type,
                        'amount' => $money_value
                    );
                    $result[] = $tmp_money;
                }
            }
        }
        return $result;
    }
    function _formatTipAmount($tip_str){
        $result = array();
        if($tip_str)
        {
            $tmp_cashes = explode('|',$tip_str);
            foreach($tmp_cashes as $c_str)
            {
                $c_str = trim($c_str);
                $type = 'fixed';
                if(strpos($c_str,'%') > 0)
                {
                    $type = 'percent';
                }
                $number_amount = str_replace('%','',$c_str);
                if(is_numeric($number_amount))
                {
                    $money_value = 1 * (float)$number_amount;
                    $tmp_money = array(
                        'label' => $c_str,
                        'type' => $type,
                        'amount' => $money_value
                    );
                    $result[] = $tmp_money;
                }
            }
        }
        return $result;
    }
    function convert_to_js_date_format($format){
		$replace = array(
				'd' => 'dd',
				'D' => 'EEE',
				'j' => 'd',
				'l' => 'EEEE',
				'N' => '',
				'S' => '',
				'w' => '',
				'z' => 'o',
			// Week
				'W' => '',
			// Month
				'F' => 'MMMM',
				'm' => 'MM',
				'M' => 'MMM',
				'n' => 'M',
				't' => '',
			// Year
				'L' => '',
				'o' => '',
				'Y' => 'yyyy',
				'y' => 'yy',
			// Time
				'a' => 'a',
				'A' => 'a',
				'B' => '',
				'g' => 'h',
				'G' => 'H',
				'h' => 'hh',
				'H' => 'HH',
				'i' => 'mm',
				's' => 'ss',
				'u' => 'sss'
        );
        // d/m/Y => dd/MM/yyyy
       
        $arr1 = str_split($format);
        $result = array();
        foreach($arr1 as $v )
        {
            if(isset($replace[$v]))
            {
                $result[] = $replace[$v];
            }else{
                $result[] = $v;
            }
            
        }
        return implode('',$result);
    }
    function generateQRcode($content,$width = 6,$height = 6){
        $barcodeobj = new TCPDF2DBarcode($content, 'QRCODE,H');
        $data = $barcodeobj->getBarcodePngData($width, $height);
        $type = 'png';
        $base64 = 'data:image/' . $type . ';base64,' . base64_encode($data);
        return $base64;
    }
    function generateBarcode($content,$barcode_mode,$width=null,$height=null){
        
        try{
            switch ($barcode_mode)
            {
                case 'code_128':
                    $mode = 'C128';
                    break;
                case 'ean_13':
                    $mode = 'EAN13';
                    break;
                case 'ean_8':
                    $mode = 'EAN8';
                    break;
                case 'code_39':
                    $mode = 'C39';
                    break;
                case 'upc_a':
                    $mode = 'UPCA';
                    break;
                case 'upc_e':
                    $mode = 'UPCE';
                    break;
                default:
                    $mode = 'C128';
            }
    
            $barcodeobj = new TCPDFBarcode($content, $mode);
            if($width == null)
            {
                $width = 100;
            }
            if($height == null)
            {
                $height = 50;
            }
            
    
            $data = $barcodeobj->getBarcodePngData($width, $height);
            $type = 'png';
            $base64 = 'data:image/' . $type . ';base64,' . base64_encode($data);
            return $base64;
        }catch(Exception $e){
            return '';
        }
        
    }
   
    public function sanitize_cost( $value ) {
		$value = is_null( $value ) ? '' : $value;
		$value = wp_kses_post( trim( wp_unslash( $value ) ) );
		$value = str_replace( array( get_woocommerce_currency_symbol(), html_entity_decode( get_woocommerce_currency_symbol() ) ), '', $value );

		$test_value = str_replace( wc_get_price_decimal_separator(), 'D', $value );
		$test_value = str_replace( array( get_woocommerce_currency_symbol(), html_entity_decode( get_woocommerce_currency_symbol() ), wc_get_price_thousand_separator() ), '', $test_value );
        $test_value = str_replace(  'D','.', $test_value );
		if ( $test_value && ! is_numeric( $test_value ) ) {
			return 0;
		}

		return $test_value;
    }
    public function unsanitize_cost( $value ) {
		$value = is_null( $value ) ? '' : $value;
		$value = wp_kses_post( trim( wp_unslash( $value ) ) );
		$value = str_replace( array( get_woocommerce_currency_symbol(), html_entity_decode( get_woocommerce_currency_symbol() ) ), '', $value );

		$test_value = str_replace( '.', 'D', $value );
		$test_value = str_replace( array( get_woocommerce_currency_symbol(), html_entity_decode( get_woocommerce_currency_symbol() ), wc_get_price_thousand_separator() ), '', $test_value );
        $test_value = str_replace(  'D',wc_get_price_decimal_separator(), $test_value );
    
		return $test_value;
	}

    
    

}