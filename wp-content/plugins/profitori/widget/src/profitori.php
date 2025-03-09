<?php
/**
 * @package profitori
 */
/*
Plugin Name: Profitori
Plugin URI: https://profitori.com/
Description: Profitori: WooCommerce ERP Plugin - Purchase Orders, Stock Management and Profit Margin Tracking
Version: 2.7.8.1
Author: Unity Business Technology Pty Ltd
Text Domain: profitori
WC requires at least: 3.8
WC tested up to: 6.1.1
*/

function prfi_time_ms() {
  return date("H:i:s") . substr((string)microtime(), 0, 8);
}

global $prfiTimestamp;
$prfiTimestamp = date('Y-m-d H:i:s');
global $prfiTimestampMs;
$prfiTimestampMs = prfi_time_ms();

define('PRFI_VERSION', '1.0.0');
define('PRFI__MINIMUM_WP_VERSION', '4.0');
define('PRFI__PLUGIN_DIR', plugin_dir_path( __FILE__ ));

define( 'PRFI_WIDGET_PATH', plugin_dir_path( __FILE__ ) . '/widget' );
define( 'PRFI_ASSET_MANIFEST', PRFI_WIDGET_PATH . '/build/asset-manifest.json' );
define( 'PRFI_INCLUDES', plugin_dir_path( __FILE__ ) . '/includes' );

require_once( PRFI_INCLUDES . '/enqueue.php' );

register_activation_hook( __FILE__, 'prfi_on_activation' );

if ( ! function_exists( 'str_ends_with' ) ) {
    function str_ends_with( $haystack, $needle ) {
        if ( '' === $haystack && '' !== $needle ) {
            return false;
        }
        $len = strlen( $needle );
        return 0 === substr_compare( $haystack, $needle, -$len, $len );
    }
}

add_action( 'wp_loaded', 'prfi_wp_loaded' );

function prfi_exec_sql($sql) {
  global $wpdb;
  return $wpdb->query($sql);
}

function prfi_get_call_stack() {
  ob_start();
      debug_print_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
      $res = ob_get_contents();
  ob_end_clean();
  return $res;
}

function prfi_val($obj, $prop) {
  if ( ! is_array($obj) ) {
    if ( ! isset($obj->{$prop}) ) return null;
    return $obj->{$prop};
  }
  if ( ! isset($obj[$prop]) ) return null;
  return $obj[$prop];
}

function prfi_wp_loaded() {
  $url = prfi_current_page_url();
  if ( strpos($url, '/prfi_attachment/') ) {
    prfi_echo_front_end_attachment_download();
    die;
  }
  if ( strpos($url, '/prfi_pay_invoice/') ) {
    prfi_start_invoice_payment();
    die;
  }
  if ( ($_SERVER['REQUEST_METHOD'] === 'POST') && strpos($url . '/', '/cart/') )
    prfi_maybe_process_imposts_post();
}

function prfi_is_plugin_active($plugin) {
  return in_array( $plugin, (array) get_option( 'active_plugins', array() ) );
}

function prfi_on_activation() {
  prfi_unset_installed_flag(); // So that client will do full harmonize
  prfi_add_shipments_endpoint();
  prfi_add_preorders_endpoint();
  flush_rewrite_rules();
}

function prfi_add_shipments_endpoint() {
  add_rewrite_endpoint( 'shipments', EP_ROOT | EP_PAGES );
}

function prfi_add_preorders_endpoint() {
  add_rewrite_endpoint( 'preorders', EP_ROOT | EP_PAGES );
}

function prfi_unset_installed_flag() {
  $id = prfi_get_configuration_id(); if ( ! $id ) return;
  prfi_set_meta($id, 'stocktend_installed', 'no');
  prfi_set_meta($id, 'stocktend_installed4', 'no');
}

function prfi_sanitize_textarea_field($str) {
  $str = (string) $str;
  $filtered = wp_check_invalid_utf8( $str );
  if ( strpos( $filtered, '<' ) !== false ) {
    //$filtered = wp_pre_kses_less_than( $filtered ); OMITTED as it doesn't handle javascript properly
    // This will strip extra whitespace for us.
    $filtered = wp_strip_all_tags( $filtered, false );
    // Use HTML entities in a special case to make sure no later
    // newline stripping stage could lead to a functional tag.
    $filtered = str_replace( "<\n", "&lt;\n", $filtered );
  }
  $filtered = trim( $filtered );
  $found = false;
  while ( preg_match( '/%[a-f0-9]{2}/i', $filtered, $match ) ) {
    $filtered = str_replace( $match[0], '', $filtered );
    $found    = true;
  }
  if ( $found ) {
    // Strip out the whitespace that may now exist after removing the octets.
    $filtered = trim( preg_replace( '/ +/', ' ', $filtered ) );
  }
  return $filtered;
}

function prfi_get_configuration_id() {
  global $wpdb;
  $query =
    "SELECT " .
      "pm1.post_id " .
    "FROM " .
      "$wpdb->postmeta pm1 " .
    "WHERE " .
      "pm1.meta_key = 'stocktend__datatype' AND " .
      "pm1.meta_value = 'Configuration' AND " .
      "(SELECT post_status FROM $wpdb->posts p WHERE p.id = pm1.post_id) = 'publish' " .
    "LIMIT 1";
  $res = (int)$wpdb->get_var($query);
  return $res;
}

if ( ! function_exists('add_action') ) {
	echo 'Direct calls disallowed';
	exit;
}

function prfi_secure_get($aName) {
  return filter_var($_GET[$aName]);
}

function prfi_secure_post($aName) {
  return filter_var($_POST[$aName]);
}

function prfi_determine_language() {
  global $prfi_lang;
  $prfi_lang = "EN";
  if ( isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) )
    $prfi_lang = strtoupper(substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2));
  $acceptLang = ['ES', 'ZH', 'ID', 'EN']; 
  $prfi_lang = in_array($prfi_lang, $acceptLang) ? $prfi_lang : 'EN';
}

prfi_determine_language();
add_action('admin_menu', 'prfi_add_menu_page');

function prfi_is_premium() {
  $file = plugin_dir_path( __FILE__ ) . "/includes/premium";
  $res = file_exists($file);
  return $res;
}

function prfi_add_menu_page(){
  $imgPath = basename(PRFI__PLUGIN_DIR) . '/images/mono16x16.png';
  add_menu_page('Profitori', 'Profitori', 'manage_woocommerce', 'profitori', 'prfi_Home', plugins_url($imgPath));
  prfi_submenu('Home');
  if ( prfi_is_premium() ) 
    prfi_submenu('Dashboard');
  prfi_submenu('Inventory');
  prfi_submenu('Location Inventory');
  prfi_submenu('Purchase Orders');
  prfi_submenu('Receive Purchases');
  prfi_submenu('Work Orders');
  prfi_submenu('Sales and Invoices');
  prfi_submenu('Fulfillment');
  prfi_submenu('View Profits');
  prfi_submenu('General Ledger');
  prfi_submenu('Stocktake');
  prfi_submenu('Suppliers');
  prfi_submenu('Customers');
  prfi_submenu('Locations');
  prfi_submenu('Reports');
  prfi_submenu('Search');
  prfi_submenu('Settings');
  prfi_submenu('Modify Profitori');
  remove_submenu_page('profitori', 'profitori');
}

function prfi_Home() {
  prfi_submenu_root('Home');
}

function prfi_AssessNeeds() {
  prfi_submenu_root('Assess Needs');
}

function prfi_PurchaseOrders() {
  prfi_submenu_root('Purchase Orders');
}

function prfi_WorkOrders() {
  prfi_submenu_root('Work Orders');
}

function prfi_ModifyProfitori() {
  prfi_submenu_root('Modify Profitori');
}

function prfi_ReceivePurchases() {
  prfi_submenu_root('Receive Purchases');
}

function prfi_Search() {
  prfi_submenu_root('Search');
}

function prfi_Stocktake() {
  prfi_submenu_root('Stocktake');
}

function prfi_Dashboard() {
  prfi_submenu_root('Dashboard');
}

function prfi_Fulfillment() {
  prfi_submenu_root('Fulfillment');
}

function prfi_Locations() {
  prfi_submenu_root('Locations');
}

function prfi_Inventory() {
  prfi_submenu_root('Inventory');
}

function prfi_LocationInventory() {
  prfi_submenu_root('Location Inventory');
}

function prfi_GeneralLedger() {
  prfi_submenu_root('GeneralLedger');
}

function prfi_SalesandInvoices() {
  prfi_submenu_root('Sales and Invoices');
}

function prfi_ViewProfits() {
  prfi_submenu_root('View Profits');
}

function prfi_Suppliers() {
  prfi_submenu_root('Suppliers');
}

function prfi_Customers() {
  prfi_submenu_root('Customers');
}

function prfi_Reports() {
  prfi_submenu_root('Reports');
}

function prfi_Settings() {
  prfi_submenu_root('Settings');
}

function prfi_submenu_root($aCaption) {
  $caption = prfi_translate($aCaption);
  ?>
    <style>
      .lds-ring {
        display: inline-block;
        width: 80px;
        height: 80px;
        position: absolute;
        z-index: 15;
        top: 200px;
        left: 50%;
        margin: -40px 0 0 -40px;
      }
      .lds-ring div {
        box-sizing: border-box;
        display: block;
        position: absolute;
        width: 64px;
        height: 64px;
        margin: 8px;
        border: 8px solid #84BA65;
        border-radius: 50%;
        animation: lds-ring 1.2s cubic-bezier(0.5, 0, 0.5, 1) infinite;
        border-color: #84BA65 transparent transparent transparent;
      }
      .lds-ring div:nth-child(1) {
        animation-delay: -0.45s;
      }
      .lds-ring div:nth-child(2) {
        animation-delay: -0.3s;
      }
      .lds-ring div:nth-child(3) {
        animation-delay: -0.15s;
      }
      @keyframes lds-ring {
        0% {
          transform: rotate(0deg);
        }
        100% {
          transform: rotate(360deg);
        }
      }
    </style>
  <?php
  echo "<div id='stocktend-root' data-defaultcaption='$caption'><div class=\"lds-ring\"><div></div><div></div><div></div><div></div></div></div>";
}

function prfi_get_check_optimized_sql() {
  global $wpdb;
  $sql = "
      SELECT  index_name
      FROM    information_schema.statistics is1
      WHERE   is1.table_name = '$wpdb->postmeta' and 
              is1.table_schema = database() and
              is1.column_name = 'meta_key' and
              is1.seq_in_index = 2 and
              (
                SELECT 
                  count(*) 
                FROM information_schema.statistics is2 
                WHERE   is2.table_name = '$wpdb->postmeta' and
                        is2.table_schema = database() and
                        is2.column_name = 'post_id' and
                        is2.seq_in_index = 1 and
                        is2.index_name = is1.index_name
              ) = 1
    ";
    return $sql;
}

function prfi_do_wp_action($obj) {
  $action = $obj->action;
  $parm = $obj->parm;
  do_action($action, $parm);
}

function prfi_delete_all_old_trashed() {
  global $wpdb;
  $two_days_ago = date('Y-m-d H:i:s', time() - (48 * 3600));
  $sql = $wpdb->prepare("
      DELETE $wpdb->postmeta, $wpdb->posts
      FROM $wpdb->postmeta
      INNER JOIN $wpdb->posts ON post_id = ID
      WHERE   
        post_type = 'stocktend_object' AND
        post_status = 'trash' AND
        post_modified < %s
    ",
    $two_days_ago
  );
  $wpdb->query($sql);
}

function prfi_deoptimize_database() {
  global $wpdb;
  if ( ! prfi_is_database_optimized() ) 
    return;
  $sql = "ALTER TABLE $wpdb->postmeta DROP INDEX prfi_post_id_key";
  $wpdb->query($sql);
}

function prfi_optimize_database() {
  global $wpdb;
  if ( prfi_is_database_optimized() ) 
    return;
  $theQuery = "ALTER TABLE $wpdb->postmeta ADD INDEX prfi_post_id_key (post_id, meta_key(30))";
  $wpdb->query($theQuery);
}

function prfi_is_database_optimized() {
  global $wpdb;
  $sql = prfi_get_check_optimized_sql();
  $rows = $wpdb->get_results($sql); 
  if ( sizeof($rows) === 0 ) 
    return false;
  return true;
}

function prfi_datatype_to_antique_index($datatype, $antiques) {
  $idx = 0;
  foreach ( $antiques as $antique ) {
    if ( $antique['datatype'] === $datatype )
      return $idx;
    $idx++;
  }
  return FALSE;
}

function prfi_create_antique($datatype) {
  global $prfiTimestamp;
  $res = array();
  $res['datatype'] = $datatype;
  $res['timestamp'] = $prfiTimestamp;
  return $res;
}

function prfi_stale_datatype($datatype) {
  prfi_stale_datatype_ex($datatype, false, NULL);
}

function prfi_stale_datatype_ex($datatype, $stale_me, $excision_object_id=null) {
  $include_me = $stale_me;
  $other_trysts = prfi_get_other_trysts($include_me);
  foreach ( $other_trysts as $tryst ) {
    $antiques = array();
    if ( isset($tryst['antiques']) && $tryst['antiques'] ) {
      $antiques = unserialize($tryst['antiques']);
    }
    $changed = FALSE;
    $idx = prfi_datatype_to_antique_index($datatype, $antiques);
    if ( $idx === FALSE ) {
      $antique = prfi_create_antique($datatype);
      array_push($antiques, $antique);
      $idx = sizeof($antiques) - 1;
      $changed = TRUE;
    }
    if ( $excision_object_id ) {
      if ( ! isset($antiques[$idx]['excisions']) )
        $antiques[$idx]['excisions'] = array();
      array_push($antiques[$idx]['excisions'], $excision_object_id);
      $changed = TRUE;
    }
    if ( $changed ) {
      $tryst['antiques'] = serialize($antiques);
      prfi_save_tryst($tryst);
    }
  }
}

function prfi_retrieve_tryst() {
  global $wpdb;
  $sql = $wpdb->prepare("
      SELECT  p.ID,
              p.post_content antiques,
              p.post_modified,
              p.post_title foreman_uuid
      FROM    $wpdb->posts p
      WHERE   p.post_type = 'stocktend_tryst'
              AND p.post_title = %s;
    ",
    prfi_get_foreman_uuid()
  );
  $rows = $wpdb->get_results($sql, ARRAY_A); if ( sizeof($rows) === 0 ) return null;
  return $rows[0];
}

function prfi_get_foreman_uuid() {
  $url = $_SERVER['REQUEST_URI'];
  $res = prfi_url_and_parm_name_to_value($url, "foremanUUID");
  return $res;
}

function prfi_get_seat_id() {
  $url = $_SERVER['REQUEST_URI'];
  $res = prfi_url_and_parm_name_to_value($url, "seat_id");
  return $res;
}

global $prfi_other_trysts;

function prfi_get_other_trysts($include_me) {
  //global $prfi_other_trysts;
  //if ( ! empty($prfi_other_trysts) )
    //return $prfi_other_trysts;
  global $wpdb;
  $exclude = prfi_get_foreman_uuid();
  if ( $include_me )
    $exclude = '';
  $sql = $wpdb->prepare("
      SELECT  p.ID,
              p.post_content antiques,
              p.post_modified,
              p.post_title foreman_uuid
      FROM    $wpdb->posts p
      WHERE   p.post_type = 'stocktend_tryst'
              AND p.post_title <> %s;
    ",
    $exclude
  );
  $rows = $wpdb->get_results($sql, ARRAY_A);
  //$prfi_other_trysts = $rows;
  return $rows;
}

function prfi_create_tryst() {
  global $wpdb;
  $tryst = array();
  $tryst['antiques'] = '';
  $tryst['post_modified'] = date('Y-m-d H:i:s');
  $tryst['foreman_uuid'] = prfi_get_foreman_uuid();
  $tryst['seat_id'] = prfi_get_seat_id();
  $sql = $wpdb->prepare("
      INSERT INTO $wpdb->posts (
        `post_content`, `post_modified`, `post_type`, `post_title`, `post_excerpt`
      ) VALUES (
        %s, %s, %s, %s, %s
      )
    ",
    $tryst['antiques'], $tryst['post_modified'], 'stocktend_tryst', $tryst['foreman_uuid'], $tryst['seat_id']
  );
  $wpdb->query($sql);
  $id = (int)$wpdb->get_var("SELECT LAST_INSERT_ID()");
  $tryst['ID'] = $id;
  return $tryst;
}

function prfi_save_tryst($tryst, $update_post_modified=false) {
  global $wpdb;
  if ( ! isset($tryst['antiques']) )
    $tryst['antiques'] = '';
  if ( $update_post_modified )
    $tryst['post_modified'] = date('Y-m-d H:i:s');
  $sql = $wpdb->prepare("
      UPDATE $wpdb->posts 
      SET 
        `post_content` = %s,
        `post_modified` = %s,
        `post_type` = %s,
        `post_title` = %s
      WHERE   
        post_type = 'stocktend_tryst'
        AND ID = %d
    ",
    $tryst['antiques'], $tryst['post_modified'], 'stocktend_tryst', $tryst['foreman_uuid'], $tryst['ID']
  );
  $wpdb->query($sql);
  return;
}

function prfi_delete_old_trysts() {
  global $wpdb;
  $two_days_ago = date('Y-m-d H:i:s', time() - (48 * 3600));
  $sql = $wpdb->prepare("
      DELETE FROM $wpdb->posts
      WHERE   
        post_type = 'stocktend_tryst'
        AND post_modified < %s
        AND post_title <> %s;
    ",
    $two_days_ago, prfi_get_foreman_uuid()
  );
  $wpdb->query($sql);
  return;
}

function prfi_retrieve_or_create_tryst() {
  $res = prfi_retrieve_tryst();
  if ( ! $res ) {
    $res = prfi_create_tryst();
    prfi_delete_old_trysts();
  }
  return $res;
}

function prfi_url_and_parm_name_to_value($aUrl, $aParmName) {
  $url_components = parse_url($aUrl); 
  if ( ! isset($url_components['query']) ) return null;
  parse_str($url_components['query'], $params); 
  if ( ! isset($params[$aParmName]) ) return null;
  return $params[$aParmName];
}

function prfi_remove_blanks($aStr) {
  return str_replace(' ', '', $aStr);
}

function prfi_submenu($aCaption) {
  $sanCaption = prfi_remove_blanks($aCaption);
  $slug = "profitori_" . $sanCaption;
  $caption = prfi_translate($aCaption);
  add_submenu_page('profitori', $caption, $caption, 'manage_woocommerce', $slug, 'prfi_' . $sanCaption);
}

function prfi_translate($aStr) {
  global $prfi_lang;
  if ( ! $prfi_lang ) return $aStr;
  if ( $prfi_lang === "EN" ) return $aStr;
  $phraseMap = prfi_language_to_phrase_map($prfi_lang); if ( ! $phraseMap ) return $aStr;
  if ( ! isset($phraseMap->{$aStr}) ) return $aStr;
  $res = $phraseMap->{$aStr}; if ( ! $res ) return $aStr;
  return $res;
}

function prfi_language_to_phrase_map($aLang) {
  $file = plugin_dir_path( __FILE__ ) . "/lang/" . $aLang . ".server.json";
  $contents = prfi_file_get_contents($file); if ( ! $contents ) return null;
  $map = json_decode($contents);
  return $map;
}

function prfi_unsanctify($reqStr) {
  return str_replace('o_rde_r', 'order', $reqStr);
}

function prfi_call_api($aReqStr) {
  $httpMethod = prfi_get_http_method();
  $reqStr = prfi_unsanctify($aReqStr);
  $GLOBALS["stApiUrl"] = $reqStr;
  $request = new WP_REST_Request($httpMethod, $reqStr);
  $request->add_header( 'Content-Type', 'application/json' );
  if ( $httpMethod === "POST" )
    $request->set_body(prfi_get_post_body());
  else
    $request->set_body("{}");
  ini_set('memory_limit', '-1'); // Otherwise json_encode can run out of memory
  $obj = rest_do_request($request);
  $jsonStr = json_encode($obj);
  return $jsonStr;
}

function prfi_file_get_contents($name) {
  if ( ! file_exists($name) ) return null;
  return file_get_contents($name);
}

function prfi_get_post_body() {
  return file_get_contents('php://input');
}

function prfi_get_http_method() {
  return $_SERVER['REQUEST_METHOD'];
}

add_action( 'admin_init', 'prfi_on_admin_init', 3 );

function prfi_on_admin_init() {
  prfi_intercept_api_request();
  if ( ! (current_user_can("manage_woocommerce") || current_user_can("administrator")) ) return;
  if ( ! prfi_user_in_settings_security() ) return;
  add_action('woocommerce_product_options_pricing', 'prfi_add_product_costs', 20);
  add_action('woocommerce_variation_options_pricing', 'prfi_add_variation_costs', 21, 3);
}


add_filter('woocommerce_order_item_get_formatted_meta_data', 'prfi_item_meta', 10, 1); // Outside of prfi_on_admin_init because it's needed on store front end
add_filter('woocommerce_product_get_stock_quantity' ,'prfi_get_stock_quantity', 10, 2);
add_filter('woocommerce_product_variation_get_stock_quantity' ,'prfi_get_stock_quantity', 10, 2);
add_filter('woocommerce_product_is_in_stock' ,'prfi_product_is_in_stock', 10, 2);
add_filter('woocommerce_variation_is_in_stock' ,'prfi_product_is_in_stock', 10, 2);
add_filter('woocommerce_available_payment_gateways', 'prfi_available_payment_gateways');
add_action('woocommerce_product_query', 'prfi_woocommerce_product_query');
add_action('woocommerce_shortcode_products_query', 'prfi_woocommerce_shortcode_products_query');
add_action('pre_get_posts', 'prfi_pre_get_posts');

function prfi_pre_get_posts($q) {
prfi_log(1);
  if ( ! $q->is_main_query() ) return;
  if ( ! $q->is_post_type_archive() ) return;
  if ( ! is_admin() && is_shop() ) {
    $hide_disc = prfi_conf_val('hideDisc');
    if ( $hide_disc !== 'Yes' ) return $q;
    $ids = prfi_get_discontinued_product_ids();
    $q->set( 'post__not_in', $ids );
  }
}

function prfi_woocommerce_shortcode_products_query($q){ 
prfi_log(2);
  if ( is_admin() ) return $q;
  $hide_disc = prfi_conf_val('hideDisc');
  if ( $hide_disc !== 'Yes' ) return $q;
  $ids = prfi_get_discontinued_product_ids();
  $q->set( 'post__not_in', $ids );
  return $q;
}

function prfi_woocommerce_product_query($q){ 
prfi_log(3);
  if ( is_admin() ) return;
  $hide_disc = prfi_conf_val('hideDisc');
  if ( $hide_disc !== 'Yes' ) return;
  $ids = prfi_get_discontinued_product_ids();
  $q->set( 'post__not_in', $ids );
}

function prfi_get_discontinued_product_ids() {
  global $wpdb;
  $db = $wpdb->prefix;
  $sql = $wpdb->prepare("
    SELECT
      (SELECT meta_value from $wpdb->postmeta _product_id 
        WHERE _situation.post_id = _product_id.post_id AND _product_id.meta_key = 'stocktend_product_id' LIMIT 1) product_id
    FROM
      $wpdb->postmeta _situation
      STRAIGHT_JOIN
        $wpdb->posts inventory ON _situation.post_id = inventory.ID AND
          inventory.post_type = 'stocktend_object' AND (inventory.post_status IN ('publish', 'private'))
      LEFT JOIN
        $wpdb->postmeta datatype ON _situation.post_id = datatype.post_id AND
          datatype.meta_key = 'stocktend__datatype'
    WHERE
      _situation.meta_key = 'stocktend_situation' AND
      _situation.meta_value LIKE 'Discontinued%' AND
      datatype.meta_value = 'Inventory'
  ");
  $rows = $wpdb->get_results($sql, ARRAY_A);
  $res = array();
  foreach ( $rows as $row ) {
    $id = $row["product_id"];
    $res[] = $id;
  }
  return $res;
}

function prfi_available_payment_gateways($gateways) {
  $credit_pay_meths = prfi_conf_val('creditPayMeths');
  if ( ! $credit_pay_meths ) return $gateways;
 	$order_id = wc_get_order_id_by_order_key($_GET['key']);
  $is_invoice = true;
  if ( $order_id )
    $is_invoice = prfi_get_meta($order_id, '_prfi_is_dummy') === 'Yes';
  if ( $is_invoice || (! prfi_customer_has_sufficient_credit()) ) 
    $gateways = prfi_remove_credit_gateways($credit_pay_meths, $gateways);
  return $gateways;
}

function prfi_remove_credit_gateways($meths_str, $gateways) {
  $res = array();
  $remove_meths = explode(",", $meths_str);
  foreach ( $gateways as $gateway ) {
    $title = $gateway->title;
    if ( in_array($title, $remove_meths) )
      continue;
    $res[] = $gateway;
  }
  return $res;
}

function prfi_customer_has_credit_facility() {
  $debtor_id = prfi_get_debtor_id(); if ( ! $debtor_id ) return false;
  $balance = (float)get_post_meta($debtor_id, 'stocktend_balance', true);
  $credit_limit = (float)get_post_meta($debtor_id, 'stocktend_creditLimit', true);
  return ($credit_limit > 0) || ($balance != 0);
}

function prfi_customer_has_sufficient_credit() {
  $debtor_id = prfi_get_debtor_id(); if ( ! $debtor_id ) return false;
  $balance = (float)get_post_meta($debtor_id, 'stocktend_balance', true);
  $credit_limit = (float)get_post_meta($debtor_id, 'stocktend_creditLimit', true);
  $total_due = WC()->cart->cart_contents_total;
  return ($balance + $credit_limit) > $total_due;
}

function prfi_product_to_bundle_id($product) {
  global $wpdb;
  $product_id = $product->get_id();
  $sql = $wpdb->prepare("
      SELECT
        bundle.ID
      FROM 
        $wpdb->postmeta bundleProductId
        INNER JOIN $wpdb->posts bundle ON bundleProductId.post_id = bundle.ID and post_status IN ('publish', 'private')
        LEFT JOIN $wpdb->postmeta bundleDatatype ON bundle.ID = bundleDatatype.post_id AND bundleDatatype.meta_key = 'stocktend__datatype' AND
          bundleDatatype.meta_value = 'Bundle'
      WHERE
        bundleProductId.meta_key = 'stocktend_bundleProductId' and bundleProductId.meta_value = %s
      LIMIT 1
    ",
    $product_id
  );
  $res = $wpdb->get_var($sql);
  return (int)$res;
}

function prfi_product_to_where_used($product) {
  global $wpdb;
  $product_id = $product->get_id();
  $sql = "
      SELECT
        component.ID id,
        componentBundle.meta_value bundle_ref,
        componentQuantity.meta_value quantity
      FROM 
        $wpdb->postmeta componentProductId
        INNER JOIN $wpdb->posts component ON componentProductId.post_id = component.ID AND component.post_status IN ('publish', 'private')
        LEFT JOIN $wpdb->postmeta componentBundle ON component.ID = componentBundle.post_id AND componentBundle.meta_key = 'stocktend_bundle'
        LEFT JOIN $wpdb->postmeta componentQuantity ON component.ID = componentQuantity.post_id AND componentQuantity.meta_key = 'stocktend_quantity'
      WHERE
        componentProductId.meta_key = 'stocktend_componentProductId' and componentProductId.meta_value = '$product_id'
    ";
  $comps = $wpdb->get_results($sql, ARRAY_A);
  $res = array();
  foreach ( $comps as $comp ) {
    $ref_str = $comp["bundle_ref"];
    $ref = json_decode($ref_str);
    if ( ! isset($ref->id) ) continue;
    $comp["bundle_id"] = $ref->id;
    $res[] = $comp;
  }
  return $res;
}

function prfi_bundle_id_to_components($bundle_id) {
  global $wpdb;
  $id_phrase = '"id":' . $bundle_id;
  $sql = "
      SELECT
        component.ID,
        componentBundleId.meta_value bundle_ref,
        componentProduct.meta_value product,
        componentQuantity.meta_value quantity
      FROM 
        $wpdb->postmeta componentBundleId
        INNER JOIN $wpdb->posts component ON componentBundleId.post_id = component.ID AND component.post_status IN ('publish', 'private')
        LEFT JOIN $wpdb->postmeta componentProduct ON component.ID = componentProduct.post_id AND componentProduct.meta_key = 'stocktend_product'
        LEFT JOIN $wpdb->postmeta componentQuantity ON component.ID = componentQuantity.post_id AND componentQuantity.meta_key = 'stocktend_quantity'
        LEFT JOIN $wpdb->postmeta datatype ON component.ID = datatype.post_id AND datatype.meta_key = 'stocktend__datatype'
      WHERE
        componentBundleId.meta_key = 'stocktend_bundle' and componentBundleId.meta_value LIKE '%$id_phrase%' AND
        datatype.meta_value = 'Component'
    ";
  $comps = $wpdb->get_results($sql, ARRAY_A);
  $res = array();
  foreach ( $comps as $comp ) {
    $ref_str = $comp["bundle_ref"];
    $ref = json_decode($ref_str);
    if ( ! isset($ref->id) ) continue;
    if ( $ref->id !== $bundle_id ) continue;
    $res[] = $comp;
  }
  return $res;
}

function prfi_component_to_stock_quantity($component) {
  $product = prfi_component_to_product($component); if ( ! $product ) return 0;
  return $product->get_stock_quantity();
}

function prfi_get_stock_quantity($value, $product) {
  global $prfi_need_raw_stock_quantity;
  if ( $prfi_need_raw_stock_quantity )
    return $value;
  $res = $value + prfi_product_to_makeable_qty($product);
  if ( prfi_conf_val('deductPre') !== 'Yes' ) 
    return $res;
  $res = $res - prfi_product_to_quantity_on_firm_preorders($product);
  return $res;
}

function prfi_product_is_in_stock($default, $aProduct) {
  global $product;
  if ( ! $aProduct )
    $aProduct = $product;
  if ( ! $aProduct )
    return $default;
  if ( $aProduct->backorders_allowed() )
    return $default;
  if ( (! prfi_product_is_bundle($aProduct)) && (prfi_conf_val('deductPre') !== 'Yes') )
    return $default;
  $res = $default;
  try {
    $res = $aProduct->get_stock_quantity() > 0;
  } catch(Exception $aException) {
  }
  return $res;
}

function prfi_product_is_bundle($product) {
  $bundle_id = prfi_product_to_bundle_id($product); 
  if ( $bundle_id ) 
    return true;
  return false;
}

function prfi_product_to_quantity_on_firm_preorders($product) {
  $res = prfi_get_meta_numeric($product->get_id(), '_prfi_quantityOnFirmPreorders');
  return $res;
}

function prfi_product_to_makeable_qty($product) {
  $bundle_id = prfi_product_to_bundle_id($product); if ( ! $bundle_id ) return 0;
  $manageManufacturingWithWorkOrders = get_post_meta($bundle_id, 'stocktend_manageManufacturingWithWorkOrders', true);
  if ( $manageManufacturingWithWorkOrders === 'Yes' ) return 0;
  $components = prfi_bundle_id_to_components($bundle_id);
  $max_makeable = 99999;
  foreach ( $components as $component ) {
    $comp_qty = prfi_component_to_stock_quantity($component);
    $qty_per_bundle = $component["quantity"];
    $makeable_qty = $qty_per_bundle == 0 ? 99999 : floor($comp_qty / $qty_per_bundle);
    if ( $makeable_qty < $max_makeable )
      $max_makeable = $makeable_qty;
  }
  if ( $max_makeable === 99999 )
    $max_makeable = 0;
  return $max_makeable;
}

function prfi_item_meta($meta) {
  /* Prevent our meta values from appearing on orders and invoices */
  foreach ( $meta as $arrayKey => $obj ) {
    if ( substr($obj->key, 0, 5) === 'prfi_' ) {
      unset($meta[$arrayKey]);
    } else if ( substr($obj->key, 0, 6) === '_prfi_' ) {
      unset($meta[$arrayKey]);
    }
  }
  return $meta;
}

function prfi_user_in_settings_security() {
  global $wpdb;
  if ( current_user_can('administrator') )
    return true;
  $sql = "
      SELECT  usersWithAccess.meta_value usersWithAccess
      FROM    $wpdb->posts p
              INNER JOIN $wpdb->postmeta pm ON p.id = pm.post_id
              LEFT JOIN $wpdb->postmeta usersWithAccess ON p.id = usersWithAccess.post_id AND usersWithAccess.meta_key = 'stocktend_usersWithAccess'
      WHERE   p.post_type = 'stocktend_object'
              AND p.post_status = 'publish'
              AND pm.meta_key = 'stocktend__datatype'
              AND pm.meta_value = 'Configuration'
    ";
  $rows = $wpdb->get_results($sql); if ( ! $rows ) return true;
  if ( sizeof($rows) === 0 ) return true;
  $row = $rows[0];
  if ( ! isset($row->usersWithAccess) ) return true;
  $usersWithAccess = $row->usersWithAccess;
  if ( empty($usersWithAccess) ) 
    return true;
  $users = explode(",", $usersWithAccess);
  $login = wp_get_current_user()->data->user_login;
  $res = in_array($login, $users, true);
  return $res;
}

function prfi_call_wc_api($aReqStr) {
  $request = new WP_REST_Request( 'GET', $aReqStr );
  $request->add_header( 'Content-Type', 'application/json' );
  $obj = rest_do_request( $request );
  $jsonStr = json_encode($obj);
  return $jsonStr;
}

function prfi_intercept_wc_api_request() {
  if ( ! isset($_GET["req_prfi"]) ) return;
  $apiRequest = prfi_secure_get("req_prfi");
  $apiRequest = "/wc/v3/" . $apiRequest;
  echo prfi_call_wc_api($apiRequest);
  die();
}

function prfi_intercept_api_request() {
  $page = prfi_get_page(); 
  if ( $page === "profitori_wc" )
    prfi_intercept_wc_api_request();
  if ( $page !== "profitori" ) return;
  $apiRequest = prfi_get_api_request(); if ( $apiRequest === null ) return;
  echo prfi_call_api($apiRequest);
  die();
}

function prfi_get_page() {
  if ( isset($_GET["page"]) ) return prfi_secure_get("page");
  if ( isset($_POST["page"]) ) return prfi_secure_post("page");
  return null;
}

function prfi_get_api_request() {
  $url = $_SERVER['REQUEST_URI'];
  $pos = strpos($url, "req_prfi="); 
  if ( ! $pos ) return null;
  $res = substr($url, $pos + 9, 9999);
  return filter_var($res);
}

add_action( 'init', 'prfi_on_init' );

function prfi_on_init() {
  prfi_register_post_types();
  add_rewrite_endpoint( 'shipments', EP_ROOT | EP_PAGES );
  add_rewrite_endpoint( 'preorders', EP_ROOT | EP_PAGES );
  add_action('woocommerce_new_order_item', 'prfi_on_new_order_item', 10, 3);
  add_action('woocommerce_update_order_item', 'prfi_on_update_order_item', 10, 2);
  add_action('woocommerce_before_delete_order_item', 'prfi_on_delete_order_item', 10, 1);
  add_action('woocommerce_reduce_order_stock', 'prfi_on_order_reduced_stock', 10, 1);
  add_action('woocommerce_variation_set_stock', 'prfi_on_qoh_change', 10, 1);
  add_action('woocommerce_product_set_stock', 'prfi_on_qoh_change', 10, 1);
  add_action('woocommerce_product_object_updated_props', 'prfi_on_product_change', 10, 2);
  add_action('woocommerce_update_product', 'prfi_on_product_update', 10, 1);
  add_action('woocommerce_update_product_variation', 'prfi_on_product_update', 10, 1);
  add_action('user_register','prfi_user_register', 10, 1);
  add_action('woocommerce_order_status_changed', 'prfi_woocommerce_order_status_changed', 10, 3);
  add_filter('woocommerce_account_menu_items', 'prfi_woocommerce_account_menu_items');
  add_action('woocommerce_account_shipments_endpoint', 'prfi_woocommerce_account_shipments_endpoint');
  add_action('woocommerce_account_preorders_endpoint', 'prfi_woocommerce_account_preorders_endpoint');
  add_filter('woocommerce_my_account_my_orders_columns', 'prfi_woocommerce_my_account_my_orders_columns');
  add_action('woocommerce_my_account_my_orders_column_order-finance-status', 'prfi_emit_order_finance_status');
  add_action('woocommerce_account_orders_endpoint', 'prfi_woocommerce_account_orders_endpoint');
  add_action('woocommerce_order_note_added', 'prfi_order_note_added', 10, 2 );
  add_action('wp_print_scripts', 'prfi_print_scripts');
  add_action('wp_enqueue_scripts', 'prfi_enqueue_scripts');
  add_action('woocommerce_cart_calculate_fees', 'prfi_cart_calculate_fees', 10, 1);
  add_action('woocommerce_account_content', 'prfi_account_content');
  add_filter('woocommerce_endpoint_order-received_title', 'prfi_order_received_title' );
  add_filter('woocommerce_thankyou_order_received_text', 'prfi_order_received_text', 20, 2 );
  add_filter('woocommerce_pay_order_button_text', 'prfi_pay_order_button_text', 20, 1 );
  add_filter('the_title', 'prfi_the_title', 10, 2);
}

function prfi_the_title($title, $id) {
  if ( is_checkout_pay_page() && (get_the_ID() === $id) ) {
 	  $order_id = wc_get_order_id_by_order_key($_GET['key']);
    $dummy = true;
    if ( $order_id )
      $dummy = prfi_get_meta($order_id, '_prfi_is_dummy') === 'Yes';
    if ( $dummy )
      return __('Pay invoice', 'woocommerce');
  }
  return $title;
}

function prfi_pay_order_button_text($old_text) {
 	$order_id = wc_get_order_id_by_order_key($_GET['key']);
  $dummy = true;
  if ( $order_id )
    $dummy = prfi_get_meta($order_id, '_prfi_is_dummy') === 'Yes';
  if ( ! $dummy )
    return $old_text;
  return __('Pay invoice', 'woocommerce');
}

function prfi_order_received_text($thank_you_title, $order) {
  $dummy = true;
  if ( $order )
    $dummy = prfi_get_meta($order->get_id(), '_prfi_is_dummy') === 'Yes';
  if ( ! $dummy )
    return $thank_you_title;
  return __('Thank you.  Your payment has been received.', 'woocommerce');
}

function prfi_order_received_title($old_title) {
 	$order_id = wc_get_order_id_by_order_key($_GET['key']);
  $dummy = true;
  if ( $order_id )
    $dummy = prfi_get_meta($order_id, '_prfi_is_dummy') === 'Yes';
  if ( ! $dummy )
    return $old_title;
  return __('Payment Received', 'woocommerce');
}

function prfi_account_content() {
  if ( prfi_conf_val('sbp') !== 'Yes' ) return;
  $did_trash = prfi_trash_dummy_orders();
  if ( $did_trash )
    prfi_reload_current_page(); // otherwise orders remain in cache
}

function prfi_trash_dummy_orders($option=null) {
  $res = false;
  if ( $option === 'all_users' ) {
    prfi_trash_all_dummy_orders();
    return;
  }
  $user_id = get_current_user_id(); if ( ! $user_id ) return;
  $orders = wc_get_orders(array('customer_id' => $user_id));
  foreach ( $orders as $order ) {
    $order_id = $order->get_id();
    if ( prfi_get_meta($order_id, '_prfi_is_dummy') === 'Yes' ) {
      $order->delete();
      $res = true;
    }
  }
  return $res;
}

function prfi_trash_all_dummy_orders() {
  $rows = prfi_get_old_dummy_order_rows();
  foreach ( $rows as $row ) {
    $order_id = $row['ID'];
    $order = wc_get_order($order_id); if ( ! $order ) continue;
    if ( prfi_get_meta($order_id, '_prfi_is_dummy') === 'Yes' ) // double check
      $order->delete();
  }
}

function prfi_get_old_dummy_order_rows() {
  global $wpdb;
  $one_day_ago = date('Y-m-d H:i:s', time() - (3600 * 24));
  $sql = $wpdb->prepare("
      SELECT
        _order.ID
      FROM
        $wpdb->postmeta _prfi_is_dummy
      LEFT JOIN
        $wpdb->posts _order ON _prfi_is_dummy.post_id = _order.ID
      WHERE
        _prfi_is_dummy.meta_key = '_prfi_is_dummy' AND _prfi_is_dummy.meta_value = 'Yes' AND
        _order.post_type = 'shop_order' AND (_order.post_status NOT IN ('wc-failed', 'wc-cancelled', 'wc-refunded', 'trash', 'auto-draft')) AND
        _order.post_modified < %s
    ",
    $one_day_ago
  );
  return $wpdb->get_results($sql, ARRAY_A);
}

function prfi_reload_current_page() {
  echo date('Y-m-d H:i:s');
  echo '<script type="text/javascript">location.reload(true);</script>';
  die;
}

function prfi_cart_imposts_dirty() {
  global $woocommerce;
  $items = $woocommerce->cart->get_cart();
  foreach ( $items as $key => $values ) { 
    $last_qty = prfi_get_cart_item_meta($key, '_prfi_impost_qty');
    $last_product_id = prfi_get_cart_item_meta($key, '_prfi_impost_product_id');
    $qty = $values['quantity']; 
    $product_id = $values['data']->get_id(); 
    if ( ($last_qty == $qty) && ($last_product_id == $product_id) ) continue;
    return true;
  }
  return false;
}

function prfi_cart_calculate_fees($cart_object) {
  if ( is_admin() && ! defined( 'DOING_AJAX' ) )
    return;
  $items = $cart_object->get_cart();
  foreach ( $items as $key => $values ) { 
    $impost_id = prfi_get_cart_item_meta($key, '_prfi_impost_id');
    if ( ! $impost_id ) continue;
    $fee_amount = prfi_get_cart_item_meta($key, '_prfi_fee_amount');
    $product = wc_get_product($values['data']->get_id()); if ( ! $product ) continue;
    $description = $product->get_title() . ' - ' . prfi_get_meta($impost_id, 'stocktend_description');
    $cart_object->add_fee($description, $fee_amount, false);
    $qty = $values['quantity']; 
  }
}

function prfi_order_note_added($comment_id, $order) {
  $comment = get_comment($comment_id);
  $text = $comment->comment_content;
  $caption = 'tracking number';
  $pos = strpos(strtolower($text), $caption); if ( ! $pos ) return;
  $tracking_number = '';
  for ( $i = $pos + strlen($caption) + 1; $i < strlen($text); $i++ ) {
    $char = $text[$i];
    if ( $char === ' ' ) break;
    if ( $char === '.' ) break;
    $tracking_number .= $char;
  }
  if ( ! $tracking_number ) return;
  prfi_add_tracking_number_to_order($order, $tracking_number);
}

function prfi_add_tracking_number_to_order($order, $tracking_number) {
  $order_id = $order->get_id();
  $res = get_post_meta($order_id, '_prfi_tracking_numbers', true);
  if ( ! $res )
    $res = '';
  else
    $res .= ',';
  $res .= $tracking_number;
  prfi_set_meta($order_id, '_prfi_tracking_numbers', $res);
  prfi_update_post_modified($order_id);
}

function prfi_enqueue_scripts() {
  if ( wp_doing_ajax() )
    return;
  $slug = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
  if ( $slug !== 'cart' )
    return;
  if ( $_SERVER['REQUEST_METHOD'] === 'POST' )
    return;
  if ( prfi_conf_val('stImp') !== 'Yes' )
    return;
  if ( prfi_cart_imposts_dirty() )
    return; // page will be reloaded after js impost calculation
  wp_register_script('prfi_reload_on_cart_update', '', [], '', true );
  wp_enqueue_script('prfi_reload_on_cart_update');
  wp_add_inline_script('prfi_reload_on_cart_update', 
    '
      jQuery(document.body).on("updated_wc_div", function (event) {
        location.reload();
      });
    '
  );
}

function prfi_print_scripts() { 
  if ( wp_doing_ajax() )
    return;
  $slug = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
  if ( $slug !== 'cart' )
    return;
  if ( $_SERVER['REQUEST_METHOD'] === 'POST' )
    return;
  if ( prfi_conf_val('stImp') !== 'Yes' )
    return;
  if ( ! prfi_cart_imposts_dirty() )
    return;
  $js = prfi_generate_impost_javascript();
  ?>
    <script>
      <?php echo $js; ?>;
    </script>
  <?php
}

function prfi_get_imposts_post_url() {
  return prfi_current_page_url();
}

function prfi_generate_impost_product_fields_js($product_id) {
  global $prfi_impost_scripts;
  if ( ! $prfi_impost_scripts ) return '';
  $values = get_post_meta($product_id);
  $res = '';
  foreach ( $values as $key => $value ) {
    if ( $key === '_weight' )
      $key = 'weight'; 
    if ( ! strpos($prfi_impost_scripts, $key) ) continue;
    $res .= '
      "' . $key . '": "' . addslashes($value[0]) . '", 
    ';
  }
  return $res;
}

function prfi_generate_impost_products_js_statement() {
  global $woocommerce;
  $res = '
    let products = [
  ';
  $items = $woocommerce->cart->get_cart();
  foreach ( $items as $item => $values ) { 
    $product_id = $values['data']->get_id(); 
    $quantity = $values['quantity']; 
    $res .= '
      {
        cartItemKey: "' . $item . '",
        id: ' . $product_id . ',
        __quantity: ' . $quantity . ',
    ';
    $res .= prfi_generate_impost_product_fields_js($product_id);
    $res .= '
      },
    ';
  } 
  $res .= '
    ];
  ';
  return $res;
}

function prfi_get_imposts() {
  global $wpdb;
  $db = $wpdb->prefix;
  $sql = "
      SELECT
        impost.ID,
        impost.post_title,
        impost.post_parent,
        impost.post_modified,
        _script.meta_value script,
        _customerMetaFieldName.meta_value customerMetaFieldName
      FROM
        $wpdb->postmeta _script
      STRAIGHT_JOIN
        $wpdb->posts impost ON _script.post_id = impost.ID AND
          impost.post_type = 'stocktend_object' AND (impost.post_status IN ('publish', 'private'))
      LEFT JOIN
        $wpdb->postmeta datatype ON impost.ID = datatype.post_id AND
          datatype.meta_key = 'stocktend__datatype'
      LEFT JOIN
        $wpdb->postmeta _customerMetaFieldName ON impost.ID = _customerMetaFieldName.post_id AND
          _customerMetaFieldName.meta_key = 'stocktend_customerMetaFieldName'
      WHERE
        _script.meta_key = 'stocktend_script' AND
        datatype.meta_value = 'Impost'
      GROUP BY impost.ID
      ORDER BY impost.ID ASC;
  ";
  $res = $wpdb->get_results($sql, ARRAY_A);
  return $res;
}

function prfi_impost_applies_to_this_customer($impost) {
  $meta_key = prfi_val($impost, 'customerMetaFieldName'); if ( ! $meta_key ) return false;
  $user_id = get_current_user_id(); if ( ! $user_id ) return false;
  $value = get_user_meta($user_id, $meta_key, true);
  return ($value === 'Yes');
}

function prfi_generate_impost_imposts_js_statement() {
  global $prfi_impost_scripts;
  $prfi_impost_scripts = '';
  $res = '
    let imposts = [
  ';
  $imposts = prfi_get_imposts();
  foreach ( $imposts as $impost ) {
    if ( ! prfi_impost_applies_to_this_customer($impost) ) continue;
    $impost_id = prfi_val($impost, 'ID'); if ( ! $impost_id ) continue;
    $script = prfi_val($impost, 'script'); if ( ! $script ) continue;
    $res .= '
      {
        id: ' . $impost_id . ',
        fn: ' . $script . '
      },
    ';
    $prfi_impost_scripts .= $script;
  }
  $res .= '
    ];
  ';
  return $res;
}

function prfi_get_post_object() {
  try {
    $str = file_get_contents('php://input');
    return json_decode($str);
  } catch(Exception $aException) {
    return null;
  }
}

function prfi_set_session_meta($key, $value) {
  $data = (array)WC()->session->get('_prfi_session_data');
  if ( empty( $data ) ) {
    $data = array();
  }
  $data[$key] = $value;
  WC()->session->set('_prfi_session_data', $data);
}

function prfi_get_session_meta($key) {
  $data = (array)WC()->session->get('_prfi_session_data');
  if ( empty( $data ) ) {
    return null;
  }
  return $data[$key];
}

function prfi_set_cart_item_meta($cart_item_key, $key, $value ) {
  $data = (array)WC()->session->get('_prfi_cart_data');
  if ( empty( $data[$cart_item_key] ) ) {
    $data[$cart_item_key] = array();
  }
  $data[$cart_item_key][$key] = $value;
  WC()->session->set('_prfi_cart_data', $data);
}

function prfi_get_cart_item_meta($cart_item_key, $key) {
  $data = (array)WC()->session->get('_prfi_cart_data');
  if ( empty( $data[$cart_item_key] ) ) {
    $data[$cart_item_key] = array();
  }
  return empty($data[$cart_item_key][$key] ) ? null : $data[$cart_item_key][$key];
}

function prfi_impost_id_to_cart_item($id) {
  global $woocommerce;
  $items = $woocommerce->cart->get_cart();
  foreach ( $items as $key => $values ) { 
    $impost_id = prfi_get_cart_item_meta($key, '_prfi_impost_id');
    if ( $impost_id == $id )
      return $key;
  }
}

function prfi_cart_item_to_fee($key, $fees) {
  foreach ( $fees as $fee ) { 
    if ( prfi_val($fee, 'cartItemKey') == $key )
      return $fee;
  }
  return null;
}

function prfi_maybe_process_imposts_post() {
  global $woocommerce;
  $obj = prfi_get_post_object(); if ( ! $obj ) return;
  if ( prfi_val($obj, 'action') !== 'prfi_process_imposts' )
    return;
  $fees = prfi_val($obj, 'fees');
  $items = $woocommerce->cart->get_cart();
  foreach ( $items as $key => $values ) { 
    $fee = prfi_cart_item_to_fee($key, $fees);
    if ( $fee ) {
      $impost_id = prfi_val($fee, 'impostId');
      $fee_amount = prfi_val($fee, 'feeAmount');
      $product_id = $values['data']->get_id(); 
      $quantity = $values['quantity']; 
      prfi_set_cart_item_meta($key, '_prfi_impost_id', $impost_id);
      prfi_set_cart_item_meta($key, '_prfi_fee_amount', $fee_amount);
      prfi_set_cart_item_meta($key, '_prfi_impost_qty', $quantity);
      prfi_set_cart_item_meta($key, '_prfi_impost_product_id', $product_id);
    } else {
      prfi_set_cart_item_meta($key, '_prfi_impost_id', '');
    }
  }
}

function prfi_generate_impost_javascript() {
  $url = prfi_get_imposts_post_url();
  $res = prfi_generate_impost_imposts_js_statement();
  $res .= prfi_generate_impost_products_js_statement();
  $res .= '
    let fees = [];
    let createFee = function(impost, product, feeAmount) {
      let fee = {impostId: impost.id, productId: product.id, feeAmount: feeAmount, cartItemKey: product.cartItemKey};
      return fee;
    };
    let processImpost = function(impost) {
      for ( var i = 0; i < products.length; i++ ) {
        let product = products[i];
        let feeAmount = impost.fn(product, product.__quantity);
        if ( ! feeAmount ) continue;
        let feeItem = createFee(impost, product, feeAmount); 
        if ( ! feeItem ) continue;
          fees.push(feeItem);
      }
    };
    for ( var i = 0; i < imposts.length; i++ ) {
      let impost = imposts[i];
      processImpost(impost);
    };
    if ( fees.length > 0 ) {
      var xhr = new XMLHttpRequest();
      xhr.open("POST", "' . $url . '"); // NOTE: synchronous
      xhr.setRequestHeader("Content-Type", "application/json");
      xhr.onreadystatechange = function() { 
        var status = xhr.status
        if (status === 0 || (status >= 200 && status < 400)) {
          location.reload()
        } else {
          console.log("Error processing imposts: status = " + status)
        }
      }
      xhr.send(JSON.stringify({
        action: "prfi_process_imposts",
        fees: fees
      }));
    }
  ';
  return $res;
}

function prfi_updating_order_directly() {
  global $prfi_updating_order_directly;
  return $prfi_updating_order_directly;
}

function prfi_set_updating_order_directly($val) {
  global $prfi_updating_order_directly;
  $prfi_updating_order_directly = $val;
}

function prfi_woocommerce_account_orders_endpoint() {
  if ( ! prfi_show_finance_info_to_customer() ) return;
  $debtor_id = prfi_get_debtor_id(); if ( ! $debtor_id ) return;
  $balance_caption = __('Balance');
  $balance = get_post_meta($debtor_id, 'stocktend_balance', true);
  $balance = prfi_cook_cost($balance);
  $credit_limit_caption = __('Credit Limit');
  $credit_limit = get_post_meta($debtor_id, 'stocktend_creditLimit', true);
  $credit_limit = prfi_cook_cost($credit_limit);
  ?>
    <div class="prfi_account_orders_finance_info">
      <p>
        <strong>
          <div class="prfi_balance_caption">
            <?php echo $balance_caption; ?>
          </div>
        </strong>
        <div class="prfi_balance">
          <?php echo $balance; ?>
        </div>
        <strong>
          <div class="prfi_credit_limit_caption">
            <?php echo $credit_limit_caption; ?>
          </div>
        </strong>
        <div class="prfi_credit_limit">
          <?php echo $credit_limit; ?>
        </div>
      </p>
    </div>
  <?php
}

function prfi_get_debtor_id() {
  $customer_user = get_current_user_id(); if ( ! $customer_user ) return null;
  return prfi_customer_id_to_debtor_id($customer_user);
}

function prfi_woocommerce_my_account_my_orders_columns($columns) {
  if ( ! prfi_show_finance_info_to_customer() ) return $columns;
  $new_columns = array();
  foreach ( $columns as $key => $name ) {
    $new_columns[ $key ] = $name;
    if ( 'order-status' === $key ) {
      $new_columns['order-finance-status'] = __( 'Finance Status', 'textdomain' );
    }
  }
  return $new_columns;
}

function prfi_emit_order_finance_status($order) {
  echo prfi_order_to_finance_status($order);
}

function prfi_order_to_finance_status($order) {
  $customer_user = $order->get_customer_id(); if ( ! $customer_user ) return null;
  $debtor_id = prfi_customer_id_to_debtor_id($customer_user);
  $balance = prfi_get_meta_numeric($debtor_id, 'stocktend_balance');
  $credit_limit = prfi_get_meta_numeric($debtor_id, 'stocktend_creditLimit');
  $avail = $balance + $credit_limit;
  if ( $avail <= 0 )
    return __( 'Unfinanced', 'textdomain' );
  $value = $order->get_total();
  if ( $avail >= $value )
    return __( 'Financed', 'textdomain' );
  return __( 'Partially Financed', 'textdomain' );
}

function prfi_customer_id_to_debtor_id($customer_id) {
  global $wpdb;
  $db = $wpdb->prefix;
  $sql = $wpdb->prepare("
      SELECT
        debtor.ID id
      FROM
        $wpdb->postmeta _customerId
      LEFT JOIN
        $wpdb->posts debtor ON _customerId.post_id = debtor.ID
      LEFT JOIN 
        $wpdb->postmeta datatype ON debtor.ID = datatype.post_id AND 
          datatype.meta_key = 'stocktend__datatype'
      WHERE
        _customerId.meta_key = 'stocktend_customerId' AND _customerId.meta_value = %s AND
        debtor.post_type = 'stocktend_object' AND (debtor.post_status IN ('publish', 'private')) and
        datatype.meta_value = 'Debtor';
    ",
    $customer_id
  );
  $res = $wpdb->get_results($sql, ARRAY_A);
  if ( count($res) <= 0 )
    return null;
  return $res[0]['id'];
}

function prfi_woocommerce_account_menu_items($items) {
  if ( prfi_show_shipments_to_customer() ) {
    $hide = prfi_hide_shipments_if_no_credit_facility();
    if ( $hide ) {
      if ( prfi_customer_has_credit_facility() ) {
        $hide = false;
      }
    }
    if ( ! $hide )
      $items = prfi_add_shipments_menu($items);
  }
  if ( prfi_show_preorders_to_customer() ) 
    $items = prfi_add_preorders_menu($items);
  return $items;
}

function prfi_add_shipments_menu($items) {
  $idx = array_search('orders', array_keys($items));
  $text = __( 'Shipments', 'woocommerce' );
  $alt = prfi_conf_val('shc');
  if ( $alt )
    $text = __( $alt, 'woocommerce' );;
  if ( $idx < 0 )
    $items['shipments'] = $text;
  else {
    $oldArray = $items;
    $items = array_slice($oldArray, 0, $idx + 1, true) + array('shipments' => $text) + array_slice($oldArray, $idx + 1, NULL, true);
  }
  return $items;
}

function prfi_add_preorders_menu($items) {
  $idx = array_search('orders', array_keys($items));
  $text = __( 'Booked Orders', 'woocommerce' );
  if ( $idx < 0 )
    $items['preorders'] = $text;
  else {
    $oldArray = $items;
    $items = array_slice($oldArray, 0, $idx + 1, true) + array('preorders' => $text) + array_slice($oldArray, $idx + 1, NULL, true);
  }
  return $items;
}

function prfi_woocommerce_account_shipments_endpoint() {
  $url = prfi_current_page_url();
  $shipment_lines = prfi_get_shipment_lines();
  ?>
    <div class="prfiShipments">
      <div class="prfiShipmentsInner">
        <?php
          if ( count($shipment_lines) === 0 ) {
            ?>
              <div class="prfiShipmentsEmptyMessage">
                <?php
                  echo(__('There are no shipments yet.', 'woocommerce'));
                ?>
              </div>
            <?php
          } else {
            prfi_emit_shipment_lines($shipment_lines);
          }
        ?>
      </div>
    </div>
  <?php
}

function prfi_woocommerce_account_preorders_endpoint() {
  $preorder_lines = prfi_get_preorder_lines();
  ?>
    <div class="prfiPreorders">
      <div class="prfiPreordersInner">
        <?php
          if ( count($preorder_lines) === 0 ) {
            ?>
              <div class="prfiPreordersEmptyMessage">
                <?php
                  echo(__('There are no booked orders yet.', 'woocommerce'));
                ?>
              </div>
            <?php
          } else {
            prfi_emit_preorder_lines($preorder_lines);
          }
        ?>
      </div>
    </div>
  <?php
}

function prfi_start_invoice_payment() {
  $url = prfi_current_page_url();
  $url = rtrim($url, "/");
  $invoice_id = substr($url, strrpos($url, '/') + 1);
  $order = prfi_get_or_create_dummy_order_for_invoice_payment($invoice_id);
  $pay_now_url = $order->get_checkout_payment_url();
  header("Location: $pay_now_url");
  die;
}

function prfi_get_or_create_dummy_order_for_invoice_payment($invoice_id, $user_id=null) {;
  if ( $user_id )
    $user = get_user_by('id', $user_id);
  else
    $user = wp_get_current_user(); 
  if ( ! $user ) return;
  $order = prfi_invoice_id_to_dummy_order($invoice_id);
  if ( $order )
    wp_delete_post($order->get_id());
  $amount = prfi_invoice_id_to_amount($invoice_id);
  $invoice_number = prfi_get_meta($invoice_id, 'stocktend_creditNumber');
  $order = wc_create_order();
  $order->set_customer_id($user->ID);
  $order->update_status('pending');
  $item = new WC_Order_Item_Fee();
  $item->set_name('Invoice # ' . $invoice_number);
  $item->set_amount($amount);
  $item->set_tax_class('');
  $item->set_tax_status('none');
  $item->set_total_tax(0);
  $item->set_total($amount);
  $country_code = $order->get_shipping_country();
  $calculate_tax_for = array(
    'country' => $country_code, 
    'state' => '', 
    'postcode' => '', 
    'city' => ''
  );
  $item->calculate_taxes($calculate_tax_for);
  $order->add_item($item);
  $order->calculate_totals();
  $order->save();
  prfi_set_meta($invoice_id, 'dummyOrderId', $order->get_id());
  prfi_set_meta($order->get_id(), '_prfi_is_dummy', 'Yes');
  prfi_set_meta($order->get_id(), '_prfi_invoice_id', $invoice_id);
  return $order;
}

function prfi_invoice_id_to_amount($id) {
  $str = prfi_get_meta($id, 'stocktend_amount');
  if ( ! $str )
    return 0;
  return - floatval($str);
}

function prfi_invoice_id_to_dummy_order($id) {
  $order_id = prfi_get_meta($id, 'dummyOrderId'); if ( ! $order_id ) return null;
  return wc_get_order($order_id);
}

function prfi_echo_front_end_attachment_download() {
  $url = prfi_current_page_url();
  $url = rtrim($url, "/");
  $attachment_id = substr($url, strrpos($url, '/') + 1);
  $attachment = prfi_id_to_attachment($attachment_id); if ( ! $attachment ) die;
  $customer_user = (int)$attachment['customerId'];
  if ( $customer_user !== get_current_user_id() )
    die;
  $original_file_name = $attachment['fileName'];
  $file_name_or_url = $attachment['contents'];
  if ( strpos($file_name_or_url, 'http') === 0 ) {
    $path_and_file = $file_name_or_url;
    header("Location: $path_and_file");
    die;
  }
  $path = prfi_get_attachments_path();
  $file_name = $file_name_or_url;
  $subfolder = prfi_attachment_to_subfolder($attachment);
  if ( $subfolder )
    $path .= '/' . $subfolder;
  $path_and_file = $path . '/' . $file_name;
  $content_type = mime_content_type($path_and_file);
  header('Content-type: ' . $content_type);
  header('Content-Disposition: attachment; filename="' . $original_file_name . '"');
  readfile($path_and_file);
  die;
}

function prfi_attachment_to_subfolder($attachment) {
  if ( prfi_conf_val('useAttachmentSubfolders') !== 'Yes' ) 
    return null;
  $res = '';
  $entityName = $attachment['entityName'];
  if ( $entityName ) {
    $entityName = mb_ereg_replace("([^\w\s\d\-_~,;\[\]\(\).])", '', $entityName);
    $res = $entityName;
  }
  $parentReference = $attachment['parentReference'];
  if ( $parentReference ) {
    if ( $res )
      $res .= '/';
    $res = $res . $parentReference;
  }
  return $res;
}

function prfi_conf_val($name) {
  $id = prfi_get_configuration_id(); if ( ! $id ) return '';
  $res = get_post_meta($id, 'stocktend_' . $name, true);
  return $res;
}

function prfi_get_attachments_path() {
  if ( prfi_conf_val('storeAttachmentsInSecureLocation') !== 'Yes' ) {
    return ''; // NOTE: in this case, attachment.contents is the full url
  }
  return prfi_conf_val('attachmentsPathOnServer');
}

function prfi_get_shipment_lines() {
  global $wpdb;
  $db = $wpdb->prefix;
  $sql = $wpdb->prepare("
      SELECT  
        soShipmentLine.ID,
        soShipmentLine.post_title,
        soShipmentLine.post_parent,
        soShipmentLine.post_modified,
        _orderId.meta_value orderId,
        _shipmentDate.meta_value shipmentDate,
        _descriptionAndSKU.meta_value descriptionAndSKU,
        _quantityShipped.meta_value quantityShipped
      FROM 
        $wpdb->postmeta customerId
      STRAIGHT_JOIN 
        $wpdb->posts soShipmentLine ON customerId.post_id = soShipmentLine.ID AND 
          soShipmentLine.post_type = 'stocktend_object' AND (soShipmentLine.post_status IN ('publish', 'private'))
      LEFT JOIN 
        $wpdb->postmeta datatype ON soShipmentLine.ID = datatype.post_id AND 
          datatype.meta_key = 'stocktend__datatype'
      LEFT JOIN 
        $wpdb->postmeta _orderId ON soShipmentLine.ID = _orderId.post_id AND 
          _orderId.meta_key = 'stocktend_orderId'
      LEFT JOIN 
        $wpdb->postmeta _shipmentDate ON soShipmentLine.ID = _shipmentDate.post_id AND 
          _shipmentDate.meta_key = 'stocktend_shipmentDate'
      LEFT JOIN 
        $wpdb->postmeta _descriptionAndSKU ON soShipmentLine.ID = _descriptionAndSKU.post_id AND 
          _descriptionAndSKU.meta_key = 'stocktend_descriptionAndSKU'
      LEFT JOIN 
        $wpdb->postmeta _quantityShipped ON soShipmentLine.ID = _quantityShipped.post_id AND 
          _quantityShipped.meta_key = 'stocktend_quantityShipped'
      WHERE 
        customerId.meta_value = %s AND
        customerId.meta_key = 'stocktend_customerId' AND
        datatype.meta_value = 'SOShipmentLine'
      ORDER BY _orderId.meta_value, soShipmentLine.ID ASC;
      ",
      get_current_user_id()
  );
  $res = $wpdb->get_results($sql, ARRAY_A);
  $show_billing = (prfi_conf_val('sbp') === 'Yes');
  if ( $show_billing )
    $res = prfi_add_billing_to_shipment_lines($res);
  return $res;
}

function prfi_add_billing_to_shipment_lines($lines) {
  $res = array();
  foreach ( $lines as $line ) {
    $shipment_line_id = prfi_val($line, 'ID');
    $invoiceNumber = null;
    $invoice = prfi_shipment_line_id_to_invoice($shipment_line_id);
    $billing_status = 'Uninvoiced';
    if ( $invoice ) {
      if ( prfi_val($invoice, 'paid') === 'Yes' )
        $billing_status = 'Paid';
      else
        $billing_status = 'Unpaid';
      $line['invoiceAmount'] = - (float)prfi_val($invoice, 'amount');
      $line['invoiceId'] = prfi_val($invoice, 'ID');
      $invoiceNumber = prfi_val($invoice, 'invoiceNumber');
      $line['invoiceNumber'] = $invoiceNumber;
      $line['dueDate'] = prfi_val($invoice, 'dueDate');
    }
    $line['invoiceOrOrderNumber'] = $invoiceNumber ? $invoiceNumber : prfi_val($line, 'orderId');
    $line['billingStatus'] = $billing_status;
    $res[] = $line;
  }
  $sortCol = array_column($res, 'invoiceOrOrderNumber');
  array_multisort($sortCol, SORT_ASC, $res);
  return $res;
}

function prfi_shipment_line_id_to_invoice($id) {
  $shipment_id = prfi_shipment_line_id_to_shipment_id($id); if ( ! $shipment_id ) return null;
  $invoice_id = prfi_shipment_id_to_invoice_id($shipment_id); if ( ! $invoice_id ) return null;
  return prfi_id_to_invoice($invoice_id);
}

function prfi_id_to_invoice($id) {
  global $wpdb;
  $sql = $wpdb->prepare("
      SELECT  
        invoice.ID,
        (SELECT meta_value FROM $wpdb->postmeta pm WHERE pm.post_id = invoice.ID AND pm.meta_key = 'stocktend_creditNumber' LIMIT 1) invoiceNumber,
        (SELECT meta_value FROM $wpdb->postmeta pm WHERE pm.post_id = invoice.ID AND pm.meta_key = 'stocktend_paid' LIMIT 1) paid,
        (SELECT meta_value FROM $wpdb->postmeta pm WHERE pm.post_id = invoice.ID AND pm.meta_key = 'stocktend_amount' LIMIT 1) amount,
        (SELECT meta_value FROM $wpdb->postmeta pm WHERE pm.post_id = invoice.ID AND pm.meta_key = 'stocktend_dueDate' LIMIT 1) dueDate
      FROM
        $wpdb->posts invoice
      WHERE
        invoice.ID = %s AND 
        (invoice.post_status IN ('publish', 'private'))
    ",
    $id
  );
  $rows = $wpdb->get_results($sql, ARRAY_A);
  if ( count($rows) <= 0 )
    return null;
  return $rows[0];
}

function prfi_shipment_id_to_invoice_id($id) {
  $shipment_number = prfi_shipment_id_to_number($id); if ( ! $shipment_number ) return null;
  return prfi_shipment_number_to_invoice_id($shipment_number);
}

function prfi_shipment_number_to_invoice_id($shipment_number) {
  global $wpdb;
  $sql = $wpdb->prepare("
      SELECT
        credit.ID
      FROM 
        $wpdb->postmeta shipmentNumber
      LEFT JOIN
        $wpdb->postmeta datatype ON shipmentNumber.post_id = datatype.post_id AND datatype.meta_key = 'stocktend__datatype'
      LEFT JOIN
        $wpdb->posts credit ON shipmentNumber.post_id = credit.ID
      WHERE
        shipmentNumber.meta_key = 'stocktend_shipmentNumber' AND
        shipmentNumber.meta_value = %s AND
        datatype.meta_value = 'Credit' AND
        (credit.post_status IN ('publish', 'private'))
      LIMIT 1
    ",
    $shipment_number
  );
  return $wpdb->get_var($sql);
}

function prfi_shipment_id_to_number($id) {
  global $wpdb;
  $sql = $wpdb->prepare("
      SELECT  
        (SELECT meta_value FROM $wpdb->postmeta pm WHERE pm.post_id = shipment.ID AND pm.meta_key = 'stocktend_shipmentNumber')
      FROM
        $wpdb->posts shipment
      WHERE
        shipment.ID = %s AND
        (shipment.post_status IN ('publish', 'private'))
      LIMIT 1
    ",
    $id
  );
  return $wpdb->get_var($sql);
}

function prfi_shipment_line_id_to_shipment_id($id) {
  global $wpdb;
  $sql = $wpdb->prepare("
      SELECT  
        shipmentLine.post_parent
      FROM
        $wpdb->posts shipmentLine
      WHERE
        shipmentLine.ID = %s AND
        (shipmentLine.post_status IN ('publish', 'private'))
      LIMIT 1
    ",
    $id
  );
  return $wpdb->get_var($sql);
}

function prfi_get_preorder_lines() {
  global $wpdb;
  $db = $wpdb->prefix;
  $sql = $wpdb->prepare("
      SELECT  
        preorderLine.ID,
        preorderLine.post_title,
        preorderLine.post_parent,
        preorderLine.post_modified,
        _preorderNumber.meta_value preorderNumber,
        _requestedETADate.meta_value requestedETADate,
        _descriptionAndSKU.meta_value descriptionAndSKU,
        _quantity.meta_value quantity
      FROM 
        $wpdb->postmeta customerId
      STRAIGHT_JOIN 
        $wpdb->posts preorderLine ON customerId.post_id = preorderLine.ID AND 
          preorderLine.post_type = 'stocktend_object' AND (preorderLine.post_status IN ('publish', 'private'))
      LEFT JOIN 
        $wpdb->postmeta datatype ON preorderLine.ID = datatype.post_id AND 
          datatype.meta_key = 'stocktend__datatype'
      LEFT JOIN 
        $wpdb->postmeta _preorderNumber ON preorderLine.ID = _preorderNumber.post_id AND 
          _preorderNumber.meta_key = 'stocktend_preorderNumber'
      LEFT JOIN 
        $wpdb->postmeta _requestedETADate ON preorderLine.ID = _requestedETADate.post_id AND 
          _requestedETADate.meta_key = 'stocktend_requestedETADate'
      LEFT JOIN 
        $wpdb->postmeta _descriptionAndSKU ON preorderLine.ID = _descriptionAndSKU.post_id AND 
          _descriptionAndSKU.meta_key = 'stocktend_descriptionAndSKU'
      LEFT JOIN 
        $wpdb->postmeta _quantity ON preorderLine.ID = _quantity.post_id AND 
          _quantity.meta_key = 'stocktend_quantity'
      WHERE 
        customerId.meta_value = %s AND
        customerId.meta_key = 'stocktend_customerId' AND
        datatype.meta_value = 'preorderLine'
      GROUP BY preorderLine.ID
      ORDER BY preorderLine.ID ASC;
      ",
      get_current_user_id()
  );
  $res = $wpdb->get_results($sql, ARRAY_A);
  return $res;
}

function prfi_emit_shipment_lines($shipment_lines) {
  $show_billing = (prfi_conf_val('sbp') === 'Yes');
  if ( $show_billing ) {
    prfi_emit_shipment_lines_with_billing($shipment_lines);
    return;
  }
  ?>
    <table class="prfiShipmentsTable woocommerce-orders-table woocommerce-MyAccount-orders shop_table shop_table_responsive my_account_orders account-orders-table">
      <thead>
        <?php
          prfi_emit_shipment_th('Order#');
          prfi_emit_shipment_th('Shipment Date');
          prfi_emit_shipment_th('Product');
          prfi_emit_shipment_th('Quantity');
          prfi_emit_shipment_th('Attachments');
        ?>
      </thead>
      <tbody>
        <?php
          $lastOrderId = null;
          foreach ( $shipment_lines as $shipment_line ) {
            $orderId = $shipment_line['orderId'];
            ?>
              <tr class="woocommerce-orders-table__row order">
                <?php
                  prfi_emit_shipment_td('orderId', $shipment_line);
                  prfi_emit_shipment_td('shipmentDate', $shipment_line);
                  prfi_emit_shipment_td('descriptionAndSKU', $shipment_line);
                  prfi_emit_shipment_td('quantityShipped', $shipment_line);
                  if ( $orderId === $lastOrderId ) {
                    echo '<td/>';
                  } else {
                    prfi_emit_shipment_attachments_td($shipment_line);
                  }
                ?>
              </tr>
            <?php
            $lastOrderId = $orderId;
          }
        ?>
      </tbody>
    </table>
  <?php
}

function prfi_emit_shipment_lines_with_billing($shipment_lines) {
  ?>
    <table class="prfiShipmentsTable woocommerce-orders-table woocommerce-MyAccount-orders shop_table shop_table_responsive my_account_orders account-orders-table">
      <thead>
        <?php
          prfi_emit_shipment_th('Invoice / Order');
          prfi_emit_shipment_th('Ship Date');
          prfi_emit_shipment_th('Due Date');
          prfi_emit_shipment_th('Product');
          prfi_emit_shipment_th('Qty');
          prfi_emit_shipment_th('Status');
          prfi_emit_shipment_th('Amount');
          prfi_emit_shipment_th('');
        ?>
      </thead>
      <tbody>
        <?php
          $lastInvoiceOrOrderNumber = null;
          foreach ( $shipment_lines as $shipment_line ) {
            $invoiceOrOrderNumber = $shipment_line['invoiceOrOrderNumber'];
            ?>
              <tr class="woocommerce-orders-table__row order">
                <?php
                  prfi_emit_shipment_td('invoiceOrOrderNumber', $shipment_line);
                  prfi_emit_shipment_td('shipmentDate', $shipment_line);
                  prfi_emit_shipment_td('dueDate', $shipment_line);
                  prfi_emit_shipment_td('descriptionAndSKU', $shipment_line);
                  prfi_emit_shipment_td('quantityShipped', $shipment_line);
                  if ( $invoiceOrOrderNumber === $lastInvoiceOrOrderNumber ) {
                    echo '<td/>';
                    echo '<td/>';
                    echo '<td/>';
                  } else {
                    prfi_emit_shipment_td('billingStatus', $shipment_line);
                    prfi_emit_shipment_td('invoiceAmount', $shipment_line, 'currency_amount');
                    prfi_emit_shipment_td_pay_button($shipment_line);
                  }
                ?>
              </tr>
            <?php
            $lastInvoiceOrOrderNumber = $invoiceOrOrderNumber;
          }
        ?>
      </tbody>
    </table>
  <?php
}

function prfi_emit_preorder_lines($preorder_lines) {
  ?>
    <table class="prfiShipmentsTable">
      <thead>
        <?php
          prfi_emit_preorder_th('Booking#');
          prfi_emit_preorder_th('Requested ETA Date');
          prfi_emit_preorder_th('Product');
          prfi_emit_preorder_th('Quantity');
        ?>
      </thead>
      <tbody>
        <?php
          $lastOrderId = null;
          foreach ( $preorder_lines as $preorder_line ) {
            ?>
              <tr>
                <?php
                  prfi_emit_preorder_td('preorderNumber', $preorder_line);
                  prfi_emit_preorder_td('requestedETADate', $preorder_line);
                  prfi_emit_preorder_td('descriptionAndSKU', $preorder_line);
                  prfi_emit_preorder_td('quantity', $preorder_line);
                ?>
              </tr>
            <?php
          }
        ?>
      </tbody>
    </table>
  <?php
}


function prfi_emit_shipment_th($caption) {
  ?>
    <th class="woocommerce-orders-table__header">
      <?php
        echo(__($caption, 'woocommerce'));
      ?>
    </th>
  <?php
}

function prfi_emit_preorder_th($caption) {
  ?>
    <th>
      <?php
        echo(__($caption, 'woocommerce'));
      ?>
    </th>
  <?php
}

function prfi_emit_shipment_td_pay_button($shipment_line) {
  ?>
    <td>
      <?php
        $field = 'invoiceId';
        if ( isset($shipment_line[$field]) && (prfi_val($shipment_line, 'billingStatus') !== 'Paid') ) {
          $invoice_id = $shipment_line[$field];
          $url = prfi_current_page_url();
          $url = prfi_strip_after($url, '/shipments') . '/prfi_pay_invoice/' . $invoice_id;
          ?>
            <button 
              class="prfiPayButton" 
              onclick='
                window.location = "<?php echo $url; ?>"
              '
            >
              <?php
                echo(__('Pay Now', 'woocommerce'));
              ?>
            </button>
          <?php
        }
      ?>
    </td>
  <?php
}

function prfi_emit_shipment_td($field, $shipment_line, $format='') {
  ?>
    <td class="woocommerce-orders-table__cell">
      <?php
        if ( isset($shipment_line[$field]) ) {
          $val = $shipment_line[$field];
          if ( $format === 'currency_amount' ) 
            $val = prfi_add_decimals($val, 2);
          echo $val;
        }
      ?>
    </td>
  <?php
}

function prfi_emit_preorder_td($field, $preorder_line) {
  ?>
    <td>
      <?php
        if ( isset($preorder_line[$field]) ) {
          $val = $preorder_line[$field];
          if ( $val === '1971-01-01' )
            $val = 'Unspecified';
          echo $val;
        }
      ?>
    </td>
  <?php
}

function prfi_emit_shipment_attachments_td($shipment_line) {
  ?>
    <td>
      <?php
        $attachments = prfi_shipment_line_to_attachments($shipment_line);
        foreach ( $attachments as $attachment ) {
          $desc = $attachment['description'];
          if ( ! $desc )
            $desc = $attachment['fileName'];
          $href = prfi_attachment_to_href($attachment);
          ?>
            <a href="<?php echo $href; ?>">
              <?php echo $desc; ?>
            </a>
          <?php
        }
      ?>
    </td>
  <?php
}

function prfi_current_page_url() {
  $url =  "//{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}";
  $escaped_url = htmlspecialchars( $url, ENT_QUOTES, 'UTF-8' );
  return $escaped_url;
}

function prfi_attachment_to_href($attachment) {
  $escaped_url = prfi_current_page_url();
  $res = prfi_strip_after($escaped_url, '/shipments') . '/prfi_attachment/' . $attachment['ID'];
  return $res;
}

function prfi_strip_after($haystack, $needle) {
  $pos = strpos($haystack, $needle);
  if ( $pos < 0 ) 
    return $haystack;
  return substr($haystack, 0, $pos + strlen($needle));
}

function prfi_shipment_line_to_attachments($shipment_line) {
  global $wpdb;
  $shipmentId = $shipment_line['post_parent'];
  $db = $wpdb->prefix;
  $sql = $wpdb->prepare("
      SELECT  
        attachment.ID,
        attachment.post_title,
        attachment.post_parent,
        attachment.post_modified,
        _description.meta_value description,
        _entityName.meta_value entityName,
        __fileName.meta_value fileName,
        _contents.meta_value contents,
        _customerId.meta_value customerId,
        _parentReference.meta_value parentReference
      FROM 
        $wpdb->postmeta attachmentParentId
      STRAIGHT_JOIN
        $wpdb->posts attachment ON attachmentParentId.post_id = attachment.ID AND
          attachment.post_type = 'stocktend_object' AND (attachment.post_status IN ('publish', 'private'))
      LEFT JOIN 
        $wpdb->postmeta datatype ON attachment.ID = datatype.post_id AND 
          datatype.meta_key = 'stocktend__datatype'
      LEFT JOIN 
        $wpdb->postmeta _description ON attachment.ID = _description.post_id AND 
          _description.meta_key = 'stocktend_description'
      LEFT JOIN 
        $wpdb->postmeta _entityName ON attachment.ID = _entityName.post_id AND 
          _entityName.meta_key = 'stocktend_entityName'
      LEFT JOIN 
        $wpdb->postmeta __fileName ON attachment.ID = __fileName.post_id AND 
          __fileName.meta_key = 'stocktend_fileName'
      LEFT JOIN 
        $wpdb->postmeta _contents ON attachment.ID = _contents.post_id AND 
          _contents.meta_key = 'stocktend_contents'
      LEFT JOIN 
        $wpdb->postmeta _customerId ON attachment.ID = _customerId.post_id AND 
          _customerId.meta_key = 'stocktend_customerId'
      LEFT JOIN 
        $wpdb->postmeta _parentReference ON attachment.ID = _parentReference.post_id AND 
          _parentReference.meta_key = 'stocktend_parentReference'
      WHERE 
        attachmentParentId.meta_value = %s AND
        attachmentParentId.meta_key = 'stocktend_theParentId' AND
        datatype.meta_value = 'Attachment'
      GROUP BY attachment.ID
      ORDER BY attachment.ID ASC;
      ",
      $shipmentId
  );
  $res = $wpdb->get_results($sql, ARRAY_A);
  return $res;
}

function prfi_id_to_attachment($attachment_id) {
  global $wpdb;
  $db = $wpdb->prefix;
  $sql = $wpdb->prepare("
      SELECT
        attachment.ID,
        attachment.post_title,
        attachment.post_parent,
        attachment.post_modified,
        _description.meta_value description,
        _entityName.meta_value entityName,
        __fileName.meta_value fileName,
        _contents.meta_value contents,
        _customerId.meta_value customerId,
        _parentReference.meta_value parentReference
      FROM
        $wpdb->posts attachment
      LEFT JOIN
        $wpdb->postmeta _description ON attachment.ID = _description.post_id AND
          _description.meta_key = 'stocktend_description'
      LEFT JOIN
        $wpdb->postmeta _entityName ON attachment.ID = _entityName.post_id AND
          _entityName.meta_key = 'stocktend_entityName'
      LEFT JOIN
        $wpdb->postmeta __fileName ON attachment.ID = __fileName.post_id AND
          __fileName.meta_key = 'stocktend_fileName'
      LEFT JOIN
        $wpdb->postmeta _contents ON attachment.ID = _contents.post_id AND
          _contents.meta_key = 'stocktend_contents'
      LEFT JOIN
        $wpdb->postmeta _customerId ON attachment.ID = _customerId.post_id AND
          _customerId.meta_key = 'stocktend_customerId'
      LEFT JOIN
        $wpdb->postmeta _parentReference ON attachment.ID = _parentReference.post_id AND
          _parentReference.meta_key = 'stocktend_parentReference'
      WHERE
        attachment.id = %s AND
        attachment.post_type = 'stocktend_object' AND (attachment.post_status IN ('publish', 'private'))
      GROUP BY attachment.ID
      ORDER BY attachment.ID ASC;
      ",
      $attachment_id
  );
  $res = $wpdb->get_results($sql, ARRAY_A);
  if ( count($res) <= 0 )
    return null;
  return $res[0];
}


function prfi_hide_shipments_if_no_credit_facility() {
  return prfi_conf_val('hsnc') === 'Yes';
}

function prfi_show_shipments_to_customer() {
  $id = prfi_get_configuration_id(); if ( ! $id ) return false;
  $show = get_post_meta($id, 'stocktend_showShipmentsToCustomer', true);
  return ($show === 'Yes');
}

function prfi_show_preorders_to_customer() {
  $id = prfi_get_configuration_id(); if ( ! $id ) return false;
  $show = get_post_meta($id, 'stocktend_showPre', true);
  return ($show === 'Yes');
}

function prfi_show_finance_info_to_customer() {
  $id = prfi_get_configuration_id(); if ( ! $id ) return false;
  $show = get_post_meta($id, 'stocktend_showFinanceInfoToCustomer', true);
  return ($show === 'Yes');
}

function prfi_user_register($user_id) {
  try {
    prfi_stale_datatype("users");
  } catch(Exception $aException) {
    prfi_log("Exception in prfi_user_register: $aException->getMessage()");
  }
}

function prfi_on_new_order_item($item_id, $item, $order_id) {
  try {
    prfi_stale_datatype("orders");
    prfi_stale_datatype("order_items");
  } catch(Exception $aException) {
    prfi_log("Exception in prfi_on_new_order_item: $aException->getMessage()");
  }
}

function prfi_woocommerce_order_status_changed($order_id, $old_status, $new_status) {
  try {
    prfi_update_post_modified($order_id);
    prfi_stale_datatype("orders");
    prfi_stale_datatype("order_items");
    if ( prfi_get_meta($order_id, '_prfi_is_dummy') === 'Yes' ) 
      prfi_maybe_finish_invoice_payment($order_id, $old_status, $new_status);
    else /* if ( ! prfi_updating_order_directly() ) 23/3/2022 */
      prfi_create_morsel('order', null, null, 0, 0, $order_id);
  } catch(Exception $aException) {
    prfi_log("Exception in prfi_woocommerce_order_status_changed: $aException->getMessage()");
  }
}

function prfi_maybe_finish_invoice_payment($order_id, $old_status, $new_status) {
  $paid = (strpos($old_status, 'pending') !== FALSE) && (strpos($new_status, 'pending') === FALSE);
  if ( ! $paid )
    return;
  $invoice_id = prfi_get_meta($order_id, '_prfi_invoice_id'); 
  $transaction_id = prfi_get_meta($order_id, '_transaction_id'); 
  wp_delete_post($order_id);
  if ( ! $invoice_id ) 
    return;
  prfi_set_meta($invoice_id, 'stocktend_paid', 'Yes');
  prfi_set_meta($invoice_id, 'paymentReference', $transaction_id);
  prfi_update_post_modified($invoice_id);
  prfi_stale_datatype_ex("Credit", true);
  prfi_create_morsel('payment', null, null, 0, 0, null, $invoice_id);
}

function prfi_on_update_order_item($item_id, $args) {
  try {
    if ( ! prfi_updating_order_directly() )
      prfi_create_morsel_from_order_item_id($item_id, false);
    prfi_refresh_order_modified_by_order_item_id($item_id);
    prfi_stale_datatype("orders");
    prfi_stale_datatype("order_items");
  } catch(Exception $aException) {
    prfi_log("Exception in prfi_on_update_order_item: $aException->getMessage()");
  }
}

function prfi_on_delete_order_item($item_id) {
  try {
    prfi_create_morsel_from_order_item_id($item_id, true);
    prfi_refresh_order_modified_by_order_item_id($item_id);
    prfi_stale_datatype("orders");
    prfi_stale_datatype_ex("order_items", TRUE, $item_id);
  } catch(Exception $aException) {
    prfi_log("Exception in prfi_on_delete_order_item: $aException->getMessage()");
  }
}

function prfi_refresh_order_modified_by_order_item_id($item_id) {
  global $wpdb;
  $db = $wpdb->prefix;
  $sql = $wpdb->prepare(
    "SELECT ord.ID FROM ${db}posts ord 
      INNER JOIN ${db}woocommerce_order_items oi ON oi.order_id = ord.id
      WHERE oi.order_item_id = %s",
    $item_id
  );
  $order_id = $wpdb->get_var($sql);
  if ( ! $order_id ) return;
  $now = date('Y-m-d H:i:s');
  $sql = $wpdb->prepare(
    "UPDATE $wpdb->posts SET post_modified = %s WHERE id = %s",
    $now,
    $order_id
  );
  $wpdb->query($sql);
}

function prfi_update_post_modified($id) {
  global $wpdb;
  $post_date = date('Y-m-d H:i:s');
  $sql = $wpdb->prepare("UPDATE $wpdb->posts SET post_modified = %s WHERE ID = %d", $post_date, $id);
  $wpdb->query($sql);
}

function prfi_on_qoh_change($aProduct) {
  try {
    global $prfi_am_updating_product_directly;
    $product = $aProduct;
    prfi_maybe_add_stock_clue(3, $product->get_id(), '_stock', 'na', $product->get_stock_quantity());
    if ( prfi_polylang_installed() ) {
      $product = prfi_product_to_main_language_product($aProduct);
    }
    prfi_update_post_modified($product->get_id());
    $stale_me = ! $prfi_am_updating_product_directly;
    prfi_stale_datatype_ex("products", $stale_me, NULL);
    prfi_stale_product_bundles($product);
    prfi_update_post_modified($product->get_id()); // 18/2/22
    if ( $prfi_am_updating_product_directly && (prfi_get_msg_to_server() !== "simulatingStockChange") ) return;
    prfi_create_morsel_from_product($product);
  } catch(Exception $aException) {
    prfi_log("Exception in prfi_on_qoh_change: $aException->getMessage()");
  }
}

function prfi_product_to_parent_bundle_ids($product, $depth_limit) {
  if ( $depth_limit <= 0 ) return;
  $res = array();
  $direct_components = prfi_product_to_where_used($product);
  foreach ( $direct_components as $direct_component ) {
    $res[] = $direct_component["bundle_id"];
  }
  foreach ( $res as $bundle_id ) {
    $parent_product = prfi_bundle_id_to_product($bundle_id);
    $parent_bundle_ids = prfi_product_to_parent_bundle_ids($parent_product, $depth_limit - 1);
    foreach ( $parent_bundle_ids as $parent_bundle_id ) {
      $res[] = $parent_bundle_id;
    }
  }
  return $res;
}

function prfi_bundle_id_to_product($bundle_id) {
  global $wpdb;
  $sql = $wpdb->prepare("
      SELECT
        bundleProductId.meta_value product_id
      FROM 
        $wpdb->posts bundle
        LEFT JOIN $wpdb->postmeta bundleProductId ON bundle.ID = bundleProductId.post_id AND bundleProductId.meta_key = 'stocktend_bundleProductId'
      WHERE
        bundle.ID = %s
      LIMIT 1
    ",
    $bundle_id
  );
  $product_id = (int)$wpdb->get_var($sql);
  $res = wc_get_product($product_id);
  return $res;
}

function prfi_stale_product_bundles($product) {
  $bundle_ids = array();
  $bundle_id = prfi_product_to_bundle_id($product);
  if ( $bundle_id )
    $bundle_ids[] = $bundle_id;
  $parent_bundle_ids = prfi_product_to_parent_bundle_ids($product, 100);
  foreach ( $parent_bundle_ids as $parent_bundle_id ) {
    $bundle_ids[] = $parent_bundle_id;
  }
  foreach ( $bundle_ids as $bundle_id ) {
    prfi_update_post_modified($bundle_id);
    prfi_stale_datatype_ex("Bundle", true, NULL);
  }
}

function prfi_product_to_main_language_product($product) {
  $sku = $product->get_sku();; if ( ! $sku ) return $product;
  global $wpdb;
  $db = $wpdb->prefix;
  $sql = $wpdb->prepare(
    "
      SELECT
        p.ID ID,
        tt.description polylang_language
      FROM
        $wpdb->postmeta pm
      INNER JOIN
        $wpdb->posts p ON pm.post_id = p.ID AND p.post_type IN ('product', 'product_variation') AND p.post_status IN ('publish', 'private')
      INNER JOIN {$db}term_relationships tr ON p.ID = tr.object_id
      INNER JOIN {$db}term_taxonomy tt ON tr.term_taxonomy_id = tt.term_taxonomy_id and tt.taxonomy = 'language'
      WHERE
        pm.meta_key = '_sku' AND pm.meta_value = %s
    ",
    $sku
  );
  $res = $wpdb->get_results($sql);
  $res = prfi_remove_other_lang_products($res);
  if ( count($res) === 0 )
    return $product;
  $row = $res[0];
  $id = $row->ID;
  $res = wc_get_product($id); if ( ! $res ) return $product;
  return $res;
}

function prfi_polylang_installed() {
  return prfi_is_plugin_active('polylang/polylang.php') || prfi_is_plugin_active('polylang-pro/polylang.php');
}

function prfi_get_main_language_sku_product_id_map() {
  global $wpdb;
  $db = $wpdb->prefix;
  $sql =
    "
      SELECT
        p.ID ID,
        tt.description polylang_language,
        sku.meta_value _sku
      FROM
        $wpdb->postmeta pm
      INNER JOIN
        $wpdb->posts p ON pm.post_id = p.ID AND p.post_type IN ('product', 'product_variation') AND p.post_status IN ('publish', 'private')
      INNER JOIN {$db}term_relationships tr ON p.ID = tr.object_id
      INNER JOIN {$db}term_taxonomy tt ON tr.term_taxonomy_id = tt.term_taxonomy_id and tt.taxonomy = 'language'
      INNER JOIN $wpdb->postmeta sku ON pm.post_id = sku.post_id AND sku.meta_key = '_sku'
    ";
  $rows = $wpdb->get_results($sql);
  $rows = prfi_remove_other_lang_products($rows);
  $res = array();
  foreach ( $rows as $row ) {
    $sku = $row->_sku;
    $product_id = $row->ID;
    $res[$sku] = $product_id;
  }
  return $res;
}

function prfi_remove_other_lang_products($rows) {
  $limit_to_polylang_language = prfi_get_polylang_language_to_limit_to();
  if ( ! $limit_to_polylang_language )
    return $rows;
  $res = array();
  foreach ( $rows as $row ) {
    if ( ! isset($row->polylang_language) ) continue;
    $language = unserialize($row->polylang_language);
    if ( ! isset($language["locale"]) ) continue;
    $locale = $language["locale"];
    if ( $locale !== $limit_to_polylang_language ) continue;
    $res[] = $row;
  }
  return $res;
}

function prfi_get_polylang_language_to_limit_to() {
  global $wpdb;
  if ( ! prfi_polylang_installed() ) return null;
  $sql = "
    SELECT
      option_value
    FROM
      $wpdb->options
    WHERE
      option_name = '_transient_pll_languages_list'
  ";
  $res = $wpdb->get_var($sql); if ( ! $res ) return null;
  $languages = unserialize($res);
  $min_term_group = 9999999;
  $res = null;
  foreach ( $languages as $language ) {
    if ( ! isset($language['term_group']) ) continue;
    if ( ! isset($language['locale']) ) continue;
    $term_group = $language['term_group'];
    if ( $term_group >= $min_term_group ) continue;
    $min_term_group = $term_group;
    $res = $language['locale'];
  }
  return $res;
}

function prfi_on_product_update($aProductId) {
  try {
    global $prfi_am_updating_product_directly;
    $product = wc_get_product($aProductId);
    prfi_maybe_add_stock_clue(12, $aProductId, '_stock', 'na', $product->get_stock_quantity());
    prfi_update_post_modified($product->get_id());
    $stale_me = ! $prfi_am_updating_product_directly;
    prfi_stale_datatype_ex("products", $stale_me, NULL);
  } catch(Exception $aException) {
    prfi_log("Exception in prfi_on_product_update: $aException->getMessage()");
  }
}

function prfi_on_product_change($aProduct, $aUpdatedProps) {
  try {
    global $prfi_am_updating_product_directly;
    prfi_maybe_add_stock_clue(11, $aProduct->get_id(), '_stock', 'na', $aProduct->get_stock_quantity());
    $product = $aProduct;
    if ( prfi_polylang_installed() ) {
      $product = prfi_product_to_main_language_product($aProduct);
    }
    prfi_update_post_modified($product->get_id());
    $stale_me = (! $prfi_am_updating_product_directly) || (prfi_get_msg_to_server() !== "simulatingProductChange");
    prfi_stale_datatype_ex("products", $stale_me, NULL);
    if ( $prfi_am_updating_product_directly ) return;
    foreach ( $aUpdatedProps as $prop ) {
      if ( ($prop === '_stock') || ($prop === 'stock_quantity') )
        prfi_maybe_add_stock_clue(13, $aProduct->get_id(), '_stock', 'na', $aProduct->get_stock_quantity());
      if ( $prop === "low_stock_amount" ) {
        prfi_create_morsel_from_product($product);
      }
      if ( $prop === "regular_price" )  {
        prfi_create_morsel_from_product($product);
      }
    }
  } catch(Exception $aException) {
    prfi_log("Exception in prfi_on_product_change: $aException->getMessage()");
  }
}

function prfi_get_msg_to_server() {
  if ( isset($_GET["msgToServer"]) )
    return $_GET["msgToServer"];
  if ( isset($_POST["msgToServer"]) )
    return $_POST["msgToServer"];
  return null;
}

function prfi_on_order_reduced_stock($aOrder) {
  try {
    foreach ( $aOrder->get_items() as $orderItem ) {
      if ( ! $orderItem->is_type('line_item') ) continue;
      $product = $orderItem->get_product(); if ( ! $product ) continue;
      prfi_maybe_add_stock_clue(4, $product->get_id(), '_stock', 'na', $product->get_stock_quantity(), $orderItem->get_id());
      prfi_update_post_modified($product->get_id());
      if ( prfi_polylang_installed() ) {
        $product = prfi_product_to_main_language_product($product);
        prfi_update_post_modified($product->get_id());
      }
      if ( ! $product->managing_stock() ) continue;
      if ( ! prfi_updating_order_directly() )
        prfi_create_morsel_from_order_item($orderItem, false);
    }
  } catch(Exception $aException) {
    prfi_log("Exception in prfi_on_order_reduced_stock: $aException->getMessage()");
  }
}

function prfi_maybe_make_product_from_components($product, $order_item, $old_qty_ordered, $new_qty_ordered) {
  $bundle_id = prfi_product_to_bundle_id($product); if ( ! $bundle_id ) return 0;
  $manageManufacturingWithWorkOrders = get_post_meta($bundle_id, 'stocktend_manageManufacturingWithWorkOrders', true);
  if ( $manageManufacturingWithWorkOrders === 'Yes' ) return;
  if ( $old_qty_ordered > 0 )
    prfi_undo_preempts($product, $order_item);
  if ( $new_qty_ordered == 0 ) return;
  $stock_qty = prfi_product_to_raw_stock_quantity($product);
  if ( $stock_qty >= 0 ) return; // This is the quantity net of the order quantity.  It's not negative, so all needed stock was already available
  $qty_to_make = - $stock_qty;
  if ( $new_qty_ordered < $qty_to_make )
    $qty_to_make = $new_qty_ordered;
  $qty_made = prfi_make_product_from_components($product, $qty_to_make, $order_item);
}

function prfi_undo_preempts($product, $order_item) {
  $preempts = prfi_order_item_to_preempts($order_item);
  foreach ( $preempts as $preempt ) {
    prfi_undo_preempt($preempt);
  }
}

function prfi_order_item_to_preempts($order_item) {
  global $wpdb;
  $db = $wpdb->prefix;
  $sql = $wpdb->prepare("
    SELECT
      p.ID id,
      order_item_id.meta_value order_item_id,
      product_id.meta_value product_id, 
      quantity.meta_value quantity
    FROM
      $wpdb->postmeta order_item_id
      INNER JOIN $wpdb->posts p ON order_item_id.post_id = p.ID AND p.post_type = 'stocktend_object' AND p.post_status IN ('publish', 'private')
      INNER JOIN $wpdb->postmeta pm ON p.id = pm.post_id AND pm.meta_key = 'stocktend__datatype' AND pm.meta_value = 'Preempt'
      LEFT JOIN $wpdb->postmeta product_id ON p.ID = product_id.post_id AND product_id.meta_key = 'stocktend_product_id'
      LEFT JOIN $wpdb->postmeta quantity ON p.ID = quantity.post_id AND quantity.meta_key = 'stocktend_quantity'
    WHERE   
      order_item_id.meta_key = 'stocktend_order_item_id' AND
      order_item_id.meta_value = %s
    GROUP BY p.ID
    ORDER BY p.ID ASC;",
    $order_item->get_id()
  );
  $wpdb->query($sql);
  $res = $wpdb->last_result;
  return $res;
}

function prfi_undo_preempt($preempt) {
  $product = wc_get_product($preempt->product_id);
  $old_qty = prfi_product_to_raw_stock_quantity($product);
  $new_qty = $old_qty - $preempt->quantity;
  prfi_set_product_stock_quantity($product, $new_qty);
  $product->save();
  prfi_trash_preempt($preempt);
}

function prfi_trash_preempt($preempt) {
  global $wpdb;
  $id = $preempt->id;
  $now = date('Y-m-d H:i:s');
  $sql = $wpdb->prepare(
    "UPDATE $wpdb->posts SET post_status = 'trash', post_modified = %s WHERE id = $id",
    $now
  );
  $wpdb->query($sql);
  prfi_stale_datatype_ex('Preempt', true, NULL);
}

function prfi_make_product_from_components($product, $qty, $order_item) {
  if ( $qty <= 0 ) return;
  $makeable_qty = prfi_product_to_makeable_qty($product);
  $qty_to_make = min($qty, $makeable_qty);
  if ( $qty_to_make == 0 ) return;
  $bundle_id = prfi_product_to_bundle_id($product); if ( ! $bundle_id ) return 0;
  $manageManufacturingWithWorkOrders = get_post_meta($bundle_id, 'stocktend_manageManufacturingWithWorkOrders', true);
  if ( $manageManufacturingWithWorkOrders === 'Yes' ) return 0;
  $components = prfi_bundle_id_to_components($bundle_id);
  foreach ( $components as $component ) {
    $qty_per_bundle = $component["quantity"];
    $component_consume_qty = $qty_per_bundle * $qty_to_make;
    prfi_consume_component($component, $component_consume_qty, $order_item);
  }
  prfi_increase_product_stock_quantity($product, $qty_to_make, $order_item, 'made');
  return $qty_to_make;
}

function prfi_consume_component($component, $qty, $order_item) {
  $product = prfi_component_to_product($component); if ( ! $product ) return;
  $makeable_qty = prfi_product_to_makeable_qty($product);
  $pickable_qty = prfi_product_to_raw_stock_quantity($product);
  $qty_to_pick = $qty;
  $qty_to_make = 0;
  if ( $pickable_qty < $qty_to_pick ) {
    $qty_to_make = $qty_to_pick - $pickable_qty;
    if ( $makeable_qty < $qty_to_make )
      $qty_to_make = $makeable_qty;
    $qty_to_pick = $pickable_qty;
    if ( ($qty_to_pick + $qty_to_make) < $qty )
      $qty_to_pick = $qty - $qty_to_make;
  }
  prfi_decrease_component_stock_quantity($component, $qty_to_pick + $qty_to_make, $order_item); // decrease by full amount, as made ones are added back below
  $product = wc_get_product($product->get_id()); // refresh buffer
  if ( $qty_to_make <= 0 ) return;
  prfi_make_product_from_components($product, $qty_to_make, $order_item);
}

function prfi_decrease_component_stock_quantity($component, $qty, $order_item) {
  $product = prfi_component_to_product($component); if ( ! $product ) return;
  prfi_increase_product_stock_quantity($product, - $qty, $order_item, 'consumed');
}

function prfi_increase_product_stock_quantity($product, $qty, $order_item, $made_or_consumed) {
  prfi_create_preempt($product, $qty, $order_item, $made_or_consumed);
  $old_qty = prfi_product_to_raw_stock_quantity($product);
  $new_qty = $old_qty + $qty;
  prfi_set_product_stock_quantity($product, $new_qty);
}

function prfi_set_product_stock_quantity($product, $qty) {
  global $prfi_need_raw_stock_quantity;
  $prfi_need_raw_stock_quantity = true;
  $product->set_stock_quantity($qty);
  $product->save();
  $prfi_need_raw_stock_quantity = false;
}

function prfi_create_preempt($product, $qty, $order_item, $made_or_consumed) {
  $id = prfi_create_stocktend_object("Preempt");
  prfi_set_meta($id, "stocktend_order_item_id", $order_item->get_id());
  prfi_set_meta($id, "stocktend_product_id", $product->get_id());
  prfi_set_meta($id, "stocktend_quantity", $qty);
  prfi_set_meta($id, "stocktend_madeOrConsumed", $made_or_consumed);
}

function prfi_component_to_product($component) {
  $ref_str = $component["product"];
  $ref = json_decode($ref_str);
  if ( ! isset($ref->id) ) return null;
  $product_id = $ref->id;
  $product = wc_get_product($product_id);
  return $product;
}

function prfi_create_morsel_from_product($aProduct) {
  $product = $aProduct;
  if ( prfi_polylang_installed() ) {
    $product = prfi_product_to_main_language_product($product);
    prfi_update_post_modified($product->get_id());
  }
  $productId = $product->get_id();
  $qty = prfi_product_id_to_raw_stock_quantity($productId);
  prfi_create_morsel('manual', null, $productId, $qty, null);
}

function prfi_product_to_raw_stock_quantity($product) {
  global $prfi_need_raw_stock_quantity;
  $prfi_need_raw_stock_quantity = true;
  $res = $product->get_stock_quantity();
  $prfi_need_raw_stock_quantity = false;
  return $res;
  //return prfi_product_id_to_raw_stock_quantity($product->get_id());
}

function prfi_product_id_to_raw_stock_quantity($product_id) {
  $product = wc_get_product($product_id); if ( ! $product ) return 0;
  return prfi_product_to_raw_stock_quantity($product);
}

function prfi_create_morsel_from_order_item_id($aId, $aDelete) {
  $orderItem = prfi_id_to_order_item($aId); if ( ! $orderItem ) return;
  $orderItem = prfi_id_to_order_item($aId); if ( ! $orderItem ) return;
  return prfi_create_morsel_from_order_item($orderItem, $aDelete);
}

function prfi_create_morsel_from_order_item($aOrderItem, $aDelete) {
  try {
    if ( ! ($aOrderItem instanceof WC_Order_Item_Product) ) return 0;
    $order = $aOrderItem->get_order();
    $product = $aOrderItem->get_product();
    if ( $product === FALSE ) return 0;
    if ( prfi_polylang_installed() ) {
      $product = prfi_product_to_main_language_product($product);
    }
    prfi_update_post_modified($product->get_id());
    $productId = $product->get_id();
    $orderQty = apply_filters('woocommerce_order_item_quantity', $aOrderItem->get_quantity(), $order, $aOrderItem );
    $orderLineAmount = $aOrderItem->get_total();
    $qty = (int)$aOrderItem->get_meta('_reduced_stock', true);
    if ( $aDelete )
      $qty = 0;
    $oldQty = prfi_get_order_item_meta_numeric($aOrderItem, '_prfi_old_quantity');
    $oldLineAmount = prfi_get_order_item_meta_numeric($aOrderItem, '_prfi_old_total');
    $qohChg = (- $qty) + $oldQty;
    $status = $order->get_status();
    $oldStatus = prfi_get_order_item_meta($aOrderItem, '_prfi_old_status');
    $statusChanged = ($status !== $oldStatus);
    if ( ($qohChg == 0) && (! $statusChanged) && (! $aDelete) )
      return $qohChg;
    if ( $orderQty === 0 )
      $lineAmount = 0;
    else
      $lineAmount = ($qty / $orderQty) * $orderLineAmount;
    $lineAmountChg = $lineAmount - $oldLineAmount;
    prfi_create_morsel('sale', $aOrderItem->get_id(), $productId, $qohChg, $lineAmountChg, $order->get_id());
    prfi_set_order_item_meta($aOrderItem, '_prfi_old_quantity', $qty);
    prfi_set_order_item_meta($aOrderItem, '_prfi_old_total', $lineAmount);
    prfi_set_order_item_meta($aOrderItem, '_prfi_old_status', $status);
    prfi_maybe_add_stock_clue(5, $productId, '_stock', 'na', $product->get_stock_quantity(), $aOrderItem->get_id());
    prfi_maybe_make_product_from_components($product, $aOrderItem, $oldQty, $qty);
  } catch(Exception $aException) {
    prfi_log("Exception in prfi_create_morsel_from_order_item: $aException->getMessage()");
  }
}

function prfi_set_order_item_meta($aOrderItem, $aKey, $aValue) {
  global $wpdb;
  $db = $wpdb->prefix;
  $id = $aOrderItem->get_id();
  $query = $wpdb->prepare("SELECT COUNT(*) FROM {$db}woocommerce_order_itemmeta WHERE order_item_id = %d AND meta_key = %s", $id, $aKey);
  $knt = (int)$wpdb->get_var($query);
  if ( $knt === 0 ) {
    $sql = $wpdb->prepare(
      " INSERT INTO {$db}woocommerce_order_itemmeta
          ( `order_item_id`,
            `meta_key`,
            `meta_value`
          )
        VALUES
          (
            %d,
            %s,
            %s
          )
      ",
      $id,
      $aKey,
      $aValue
    );
  } else {
    $sql = $wpdb->prepare(
      " UPDATE {$db}woocommerce_order_itemmeta
        SET `meta_value` = %s
        WHERE order_item_id = %d AND meta_key = %s
      ",
      $aValue,
      $id,
      $aKey
    );
  }
  $wpdb->query($sql);
}

function prfi_get_order_item_meta($aOrderItem, $aKey) {
  global $wpdb;
  $db = $wpdb->prefix;
  $id = $aOrderItem->get_id();
  $query = $wpdb->prepare(
    "SELECT meta_value FROM {$db}woocommerce_order_itemmeta WHERE order_item_id = %d AND meta_key = %s", 
    $id, 
    $aKey
  );
  $value = $wpdb->get_var($query);
  // FOR LEGACY DATA, which didn't have the _ prefix
  if ( ($value === NULL) && (substr($aKey, 0, 1) === '_') )
    $value = prfi_get_order_item_meta($aOrderItem, substr($aKey, 1, 999));
  return $value;
}

function prfi_get_meta($id, $aKey) {
  return get_post_meta($id, $aKey, true);
}

function prfi_get_meta_numeric($id, $aKey) {
  $value = get_post_meta($id, $aKey, true);
  $value = (int)$value;
  if ( ! $value )
    return 0;
  return floatval($value);
}

function prfi_get_order_item_meta_numeric($aOrderItem, $aKey) {
  $value = prfi_get_order_item_meta($aOrderItem, $aKey);
  $value = (int)$value;
  if ( ! $value )
    return 0;
  return floatval($value);
}

function prfi_id_to_order_item($aId) {
  return WC_Order_Factory::get_order_item($aId);
}

function prfi_get_request_id() {
  global $prfi_request_id;
  if ( ! $prfi_request_id )
    $prfi_request_id = strval(rand(10000, 99999));
  return $prfi_request_id;
}

function prfi_get_request_start_time() {
  global $prfiTimestampMs;
  return $prfiTimestampMs;
}

function prfi_get_meta_from_db($id, $key) {
  global $wpdb;
  $db = $wpdb->prefix;
  $query = $wpdb->prepare(
    "SELECT meta_value FROM {$db}postmeta WHERE post_id = %d AND meta_key = %s", 
    $id, 
    $key
  );
  $value = $wpdb->get_var($query);
  return $value;
}

function prfi_get_meta_from_db_numeric($id, $aKey) {
  $value = prfi_get_meta_from_db($id, $aKey, true);
  $value = (int)$value;
  if ( ! $value )
    return 0;
  return floatval($value);
}

function prfi_product_id_to_db_stock($product_id) {
  return prfi_get_meta_from_db_numeric($product_id, '_stock');
}

function prfi_maybe_add_stock_clue($point, $castId, $fieldName, $oldVal, $newVal, $reference='', $datatype='products') {
  $configId = prfi_get_configuration_id(); if ( ! $configId ) return;
  if ( get_post_meta($configId, 'stocktend_logChangesToStockForProblemDiagnosis', true) !== 'Yes' ) return;
  $id = prfi_create_stocktend_object("Clue");
  $now = date('Y-m-d H:i:s');
  if ( $datatype === 'products' )
    $reference .= ' ' . prfi_get_call_stack();
  prfi_set_meta($id, "stocktend_date", date('Y-m-d'));
  prfi_set_meta($id, "stocktend_time", prfi_time_ms()); 
  prfi_set_meta($id, "stocktend_clientOrServer", 'S');
  prfi_set_meta($id, "stocktend_point", $point);
  prfi_set_meta($id, "stocktend_theDatatype", $datatype);
  prfi_set_meta($id, "stocktend_castId", $castId);
  prfi_set_meta($id, "stocktend_fieldName", $fieldName);
  prfi_set_meta($id, "stocktend_newValue", $newVal);
  prfi_set_meta($id, "stocktend_oldValue", $oldVal);
  prfi_set_meta($id, "stocktend_reference", $reference);
  prfi_set_meta($id, "stocktend_requestId", prfi_get_request_id());
  prfi_set_meta($id, "stocktend_requestStartTime", prfi_get_request_start_time());
  prfi_set_meta($id, "stocktend__stock", prfi_product_id_to_db_stock($castId));
  $user_data = wp_get_current_user()->data;
  if ( isset($user_data->user_login) ) 
    prfi_set_meta($id, "stocktend_user", $user_data->user_login);
}

function prfi_create_morsel($aType, $aOrderItemId, $aProductId, $aQuantity, $aAmount, $aOrderId=null, $aId=null) {
  $id = prfi_create_stocktend_object("Morsel");
  prfi_set_meta($id, "stocktend_morsel_type", $aType);
  prfi_set_meta($id, "stocktend_order_item_id", $aOrderItemId);
  prfi_set_meta($id, "stocktend_product_id", $aProductId);
  prfi_set_meta($id, "stocktend_quantity", $aQuantity);
  prfi_set_meta($id, "stocktend_amount", $aAmount);
  prfi_set_meta($id, "stocktend_morsel_date", date('Y-m-d H:i:s'));
  prfi_set_meta($id, "stocktend_order_id", $aOrderId);
  prfi_set_meta($id, "stocktend_the_id", $aId);
  $user_data = wp_get_current_user()->data;
  if ( isset($user_data->user_login) ) 
    prfi_set_meta($id, "stocktend_user_login", $user_data->user_login);
}

function prfi_create_stocktend_object($aDatatype) {
  global $wpdb;
  $post_author = 1;
  $post_date = date('Y-m-d H:i:s');
  $post_date_gmt = $post_date; 
  $post_title = $aDatatype;
  $post_status = "publish";
  $post_name = $post_title;
  $post_modified = $post_date;
  $post_modified_gmt = $post_date_gmt;
  $post_parent = 0;
  $post_type = 'stocktend_object';
  $sql = $wpdb->prepare("
    INSERT INTO $wpdb->posts
      ( `post_author`, 
        `post_date`, 
        `post_date_gmt`, 
        `post_title`, 
        `post_status`, 
        `post_name`, 
        `post_modified`, 
        `post_modified_gmt`, 
        `post_parent`, 
        `post_type`
      )
    VALUES
      ( %d,
        %s,
        %s,
        %s,
        %s,
        %s,
        %s,
        %s,
        %d,
        %s
      )
    ",
    $post_author,
    $post_date,
    $post_date_gmt,
    $post_title,
    $post_status,
    $post_name, 
    $post_modified, 
    $post_modified_gmt, 
    $post_parent, 
    $post_type
  );
  $wpdb->query($sql);
  $id = (int)$wpdb->get_var("SELECT LAST_INSERT_ID()");
  prfi_set_meta($id, "stocktend__datatype", $aDatatype);
  prfi_stale_datatype_ex('Morsel', true, NULL);
  prfi_stale_datatype_ex($aDatatype, true, NULL);
  return $id;
}

function prfi_set_post_modified($id, $date) {
  global $wpdb;
  $wpdb->query("UPDATE $wpdb->posts SET post_modified = '{$date}', post_modified_gmt = '{$date}'  WHERE ID = {$id}");
}

function prfi_set_meta($aId, $aKey, $aValue) {
  global $wpdb;
  $query = $wpdb->prepare("SELECT COUNT(*) FROM $wpdb->postmeta WHERE post_id = %d AND meta_key = %s", $aId, $aKey);
  $knt = (int)$wpdb->get_var($query);
  if ( $knt === 0 ) {
    $sql = $wpdb->prepare(
      " INSERT INTO $wpdb->postmeta
          ( `post_id`,
            `meta_key`,
            `meta_value`
          )
        VALUES
          (
            %d,
            %s,
            %s
          )
      ",
      $aId,
      $aKey,
      $aValue
    );
  } else {
    $sql = $wpdb->prepare(
      " UPDATE $wpdb->postmeta
        SET `meta_value` = %s
        WHERE post_id = %d AND meta_key = %s
      ",
      $aValue,
      $aId,
      $aKey
    );
  }
  $wpdb->query($sql);
}

function prfi_set_user_meta($aId, $aKey, $aValue) {
  global $wpdb;
  $query = $wpdb->prepare("SELECT COUNT(*) FROM $wpdb->usermeta WHERE user_id = %d AND meta_key = %s", $aId, $aKey);
  $knt = (int)$wpdb->get_var($query);
  if ( $knt === 0 ) {
    $sql = $wpdb->prepare(
      " INSERT INTO $wpdb->usermeta
          ( `user_id`,
            `meta_key`,
            `meta_value`
          )
        VALUES
          (
            %d,
            %s,
            %s
          )
      ",
      $aId,
      $aKey,
      $aValue
    );
  } else {
    $sql = $wpdb->prepare(
      " UPDATE $wpdb->usermeta
        SET `meta_value` = %s
        WHERE user_id = %d AND meta_key = %s
      ",
      $aValue,
      $aId,
      $aKey
    );
  }
  $wpdb->query($sql);
}

function prfi_register_post_types() {
  register_post_type('stocktend_object',
    array(
      'labels' => array(
        'name' => __('Profitori Objects'),
        'singular_name' => __('Profitori Object'),
      ),
      'public' => false,
      'show_ui' => false,
      'show_in_nav_menus' => false,
      'show_in_admin_bar' => false,
    )
  );
}

add_action( 'rest_api_init', 'prfi_on_rest_init' );

function prfi_init_cors($value) {
  $origin = get_http_origin();
  header( 'Access-Control-Allow-Origin: ' . esc_url_raw( $origin ) );
  header( 'Access-Control-Allow-Methods: OPTIONS, GET, POST, PUT, PATCH, DELETE' );
  header( 'Access-Control-Allow-Credentials: true' );
  header('Access-Control-Allow-Headers: Authorization,DNT,X-CustomHeader,Keep-Alive,User-Agent,X-Requested-With,If-Modified-Since,Cache-Control,Origin,Content-Type,X-Auth-Token,Content-Range,Range');
  header('Access-Control-Expose-Headers: Authorization,DNT,X-CustomHeader,Keep-Alive,User-Agent,X-Requested-With,If-Modified-Since,Cache-Control,Origin,Content-Type,X-Auth-Token,Content-Range,Range');
  header( 'Vary: Origin' );
  return $value;
}

function prfi_is_api_call() {
  return prfi_url_contains('/wp-json/wc/v3/stocktend_object') ;
}

function prfi_url_contains($str) {
  $url = $_SERVER['REQUEST_URI'];
  return strpos($url, $str) !== FALSE;
}

function prfi_on_rest_init() {
  if ( prfi_is_api_call() ) {
    remove_filter( 'rest_pre_serve_request', 'rest_send_cors_headers' );
    if ( prfi_conf_val('includeCorsHeaders') === 'Yes' )
      add_filter( 'rest_pre_serve_request', 'prfi_init_cors', 15 );
  }
  $captain = new PRFI_Captain("stocktend_object");
  $captain->register_routes();
}

class WC_REST_Prfi_Controller {
  protected $namespace = 'wc/v3';
  protected $rest_base = 'stocktend_object';

  public function get_custom( $data ) {
    if ( is_user_logged_in() ) {
      $apiRequest = prfi_get_api_request(); if ( $apiRequest === null ) return;
      $res = prfi_call_api($apiRequest);
      echo $res;
      die;
    }
    return new WP_Error('unauthorized', __('Authentication failed'), [ 'status' => 401 ]);
  }

  public function register_routes() {
    register_rest_route(
      $this->namespace,
      '/' . $this->rest_base,
      array(
        'methods' => 'GET',
        'callback' => array( $this, 'get_custom' ),
        'permission_callback' => '__return_true'
      )
    );
  }
}

if ( prfi_is_api_call() ) {
  add_filter( 'woocommerce_rest_api_get_rest_namespaces', 'prfi_custom_api' );
}

function prfi_custom_api($controllers) {
  $controllers['wc/v3']['stocktend_object'] = 'WC_REST_Prfi_Controller';
  return $controllers;
}

class PRFI_Captain extends WP_REST_Posts_Controller {

  public function register_routes() {
    register_rest_route("stocktend/v1", "/stocktend_object(.*)", $this->get_routes());
  }

  function get_routes() {
    $res = array(
      $this->get_route("GET", "get_objects"),
      $this->get_route("POST", "update_objects"),
      'schema' => array( $this, 'get_public_item_schema' ),
      'args' => $this->get_route_args()
    );
    return $res;
  }

	function get_route_args() {
		$query_params = parent::get_collection_params();
		$query_params['_datatype'] = array(
			'description' => "Type of data",
			'type' => 'string'
		);
    return $query_params;
  }

  function get_route($aHttpMethod, $aCallbackName) {
    $res = array(
      'methods' => $aHttpMethod,
      'callback' => array( $this, $aCallbackName ),
      'permission_callback' => '__return_true'
    );
    return $res;
  }

  function get_plexus($req) {
    global $wpdb;
    $sql = $this->construct_plexus_query();
    $rows = $wpdb->get_results($sql);
    $objects = $this->plexus_rows_to_objects($rows);
		$response = rest_ensure_response($objects);
    return $response;
  }

  function datatype_to_plexus_field_names($datatype) {
    $res = array();
    $plexquest = $this->datatype_to_plexquest($datatype); if ( ! $plexquest ) return $res;
    $call = $plexquest->call;
    $res = $this->url_to_fields($call);
    return $res;
  }

  function datatype_to_plexquest($datatype) {
    global $prfi_plexquests;
    foreach ( $prfi_plexquests as $plexquest ) {
      if ( $plexquest->datatype === $datatype )
        return $plexquest;
    }
    return null;
  }

  function plexus_rows_to_objects($rows) {
    $res = array();
    $field_names = array();
    $last_datatype = null;
    foreach ($rows as $row) {
      $datatype = $row->_datatype;
      if ( $datatype !== $last_datatype )
        $field_names = $this->datatype_to_plexus_field_names($datatype);
      $obj = $this->row_to_object($row, $datatype, $field_names);
      $obj = $this->prepare_response_for_collection($obj);
      array_push($res, $obj);
    }
    return $res;
  }

  function construct_plexus_query() {
    global $prfi_plexquests;
    $obj = prfi_get_post_object();
    $prfi_plexquests = $obj->plexquests;
    $res = "
      SELECT  
        p.ID,
        p.post_title,
        p.post_parent,
        p.post_modified,
        pm.meta_value _datatype
    ";
    $res .= $this->get_plexus_subselects() . ' ';
    $res .= $this->get_plexus_from_clause() . ' ';
    $res .= $this->get_plexus_where_clause() . ' ';
    return $res;
  }

  function get_plexus_where_clause() {
    global $prfi_plexquests;
    $datatypes = '';
    foreach ( $prfi_plexquests as $plexquest ) {
      if ( $datatypes )
        $datatypes .= ', ';
      $secure_datatype = $this->secure_dynamic_sql($plexquest->datatype);
      $datatypes .= "'$secure_datatype'";
    }
    return "
      WHERE pm.meta_key = 'stocktend__datatype' AND pm.meta_value IN ( $datatypes )
    ";
  }

  function get_plexus_from_clause() {
    global $wpdb;
    return "
      FROM    
        $wpdb->postmeta pm
        STRAIGHT_JOIN $wpdb->posts p ON pm.post_id = p.ID AND p.post_type = 'stocktend_object' AND 
          (p.post_status IN ('publish', 'private'))
    ";
  }

  function get_plexus_subselects() {
    global $prfi_plexquests;
    $res = '';
    $subselects = true;
    $native = false;
    $done_fields = array();
    foreach ( $prfi_plexquests as $plexquest ) {
      $call = $plexquest->call;
      $fields = $this->url_to_fields($call);
      $fields = $this->exclude_fields($fields, $done_fields);
      $res .= $this->field_names_to_cols($fields, $native, $subselects) . ' ';
      $done_fields = array_merge($done_fields, $fields);
    }
    return $res;
  }

  function exclude_fields($fields, $exclude_fields) {
    $res = array();
    if ( ! $fields ) return $res;
    foreach ( $fields as $field ) {
      if ( in_array($field, $exclude_fields) ) continue;
      $res[] = $field;
    }
    return $res;
  }

  function update_objects($aReq) {
    global $wpdb;
    global $prfi_is_save;
    try {
      //tc(501);
      $url = $GLOBALS["stApiUrl"];
      if ( prfi_get_msg_to_server() === "getPlexus" ) {
        return $this->get_plexus($aReq);
      }
      if ( prfi_get_msg_to_server() === "email" ) {
        $this->send_email($aReq);
        return;
      }
      if ( prfi_get_msg_to_server() === "deleteStocktendObjects" ) {
        $this->delete_stocktend_objects();
        return;
      }
      if ( prfi_get_msg_to_server() === "simulatePayment" ) {
        $this->simulate_payment($aReq);
        return;
      }
      $this->tryst = prfi_retrieve_or_create_tryst();
      $wpdb->query('START TRANSACTION');
      //tc(500);
      if ( prfi_be_slow() ) 
        sleep(2);
      $posts = $this->save_request_as_posts($aReq);
      //tc(3);
      $objects = $this->wp_posts_to_objects($posts, $aReq);
      if ( $prfi_is_save )
        $objects = $this->maybe_append_tryst($objects); // 6/3/22
      //tc(4);
		  $response = rest_ensure_response($objects);
      //tc(2);
      $wpdb->query('COMMIT');
      //tcReport();
      return $response;
    } catch(Exception $aException) {
      $wpdb->query('ROLLBACK');
      $msg = $aException->getMessage();
      return new WP_Error('Error', $msg);
    }
  }

  function copy_url_to_temp_file($url) {
    $contents = file_get_contents($url);
    $tempfile = wp_tempnam();
    file_put_contents($tempfile, $contents);
    rename($tempfile, $tempfile . '.pdf');
    return $tempfile . '.pdf';
  }

  function send_email($aReq) {
    $body = filter_var($aReq->get_body());
    $parm_obj = json_decode($body);
    $parms = $parm_obj->parms;
    $subfolder = isset($parms->subfolder) ? $parms->subfolder : null;
    $file_name = $this->attachment_id_to_file_name($parms->attachmentId, $subfolder); if ( ! $file_name ) return;
    $delete_file = false;
    if ( strpos($file_name, 'http') === 0 ) {
      $file_name = $this->copy_url_to_temp_file($file_name);
      $delete_file = true;
    }
    $attachments = array($file_name);
    $mail_res = wp_mail($parms->emailAddress, $parms->subject, $parms->message, '', $attachments);
    if ( $delete_file )
      unlink($file_name);
    $res_obj = array(
      "result" => "sent"
    );
    if ( ! $mail_res )
      $res_obj["result"] = "failed";
    $jsonStr = json_encode($res_obj);
    echo $jsonStr;
    die;
  }

  function delete_stocktend_objects() {
    if ( ! prfi_is_running_tests() ) return;
    global $wpdb;
    prfi_exec_sql("delete from $wpdb->postmeta where meta_key like 'stocktend%';");
    prfi_exec_sql("delete from $wpdb->posts where post_type like 'stocktend%';");
    $res_obj = array(
      "result" => "sent"
    );
    $jsonStr = json_encode($res_obj);
    echo $jsonStr;
    die;
  }

  function simulate_payment($aReq) {
    $body = filter_var($aReq->get_body());
    $parm_obj = json_decode($body);
    $parms = $parm_obj->parms;
    $invoice_id = prfi_val($parms, 'invoiceId');
    $user_id = prfi_val($parms, 'userId');
    $order = prfi_get_or_create_dummy_order_for_invoice_payment($invoice_id, $user_id);
    $order->set_status('processing'); // triggers status change action on dummy order, which in turn triggers our payment finalisation
    $order->save();
    $res_obj = array(
      "result" => "sent"
    );
    $jsonStr = json_encode($res_obj);
    echo $jsonStr;
    die;
  }

  function attachment_id_to_file_name($id, $subfolder) {
    $attachment = prfi_id_to_attachment($id); if ( ! $attachment ) return;
    $original_file_name = $attachment['fileName']; if ( ! $original_file_name ) return;
    $file_name_or_url = $attachment['contents']; 
    if ( strpos($file_name_or_url, 'http') === 0 ) {
      return $file_name_or_url;
    }
    $path = prfi_get_attachments_path();
    $file_name = $file_name_or_url;
    $subfolder = prfi_attachment_to_subfolder($attachment);
    if ( $subfolder )
      $path .= '/' . $subfolder;
    $path_and_file = $path . '/' . $file_name;
    return $path_and_file;
  }

  function maybe_append_tryst($objects) {
    $tryst = prfi_retrieve_tryst();
    if ( ! $tryst ) return $objects;
    if ( empty($tryst['antiques']) ) {
      prfi_save_tryst($tryst, true); // To update post_modified
      return $objects;
    }
    $tryst['antiques'] = unserialize($tryst['antiques']);
    $tryst = $this->prepare_response_for_collection($tryst);
    array_push($objects, $tryst);
    $tryst['antiques'] = '';
    prfi_save_tryst($tryst, true);
    return $objects;
  }

  function trash_post($aId) {
    global $wpdb;
    try {
      if ( (! $aId) || ($aId === 0) ) return;
      $ids = $this->id_to_recursive_child_ids($aId);
      array_push($ids, $aId);
      $idList = join(',', $ids);
      $now = date('Y-m-d H:i:s');
      $sql = $wpdb->prepare(
        "UPDATE $wpdb->posts SET post_status = 'trash', post_modified = %s WHERE id IN ($idList)",
        $now
      ); // Safe, as idList is retrieved from post ids (integers)
      $wpdb->query($sql);
    } catch(Exception $aException) {
      return new WP_Error('Error', $aException->getMessage());
    }
  }

  function id_to_recursive_child_ids($aId) {
    global $wpdb;
    $res = [];
    $sql = $wpdb->prepare("SELECT id FROM $wpdb->posts WHERE post_parent = %s", $aId);
    $rows = $wpdb->get_results($sql);
    foreach ( $rows as $row ) {
      $childId = $row->id;
      array_push($res, $childId);
      $grandchildIds = $this->id_to_recursive_child_ids($childId);
      $res = array_merge($res, $grandchildIds);
    }
    return $res;
  }

  function get_id_requested() {
    $url = $GLOBALS["stApiUrl"];
    $res = basename($url);
    $pos = strpos($res, "&"); 
    if ( $pos ) 
      $res = substr($res, 0, $pos);
    return $res;
  }

  function maybeCreateIntentionalError($aDatatype) {
    if ( ! isset($_GET["spannerDatatype"]) ) return;
    if ( $aDatatype !== $_GET["spannerDatatype"] ) return;
    global $prfiSpannerIndex;
    if ( $_GET["spannerIndex"] != $prfiSpannerIndex ) {
      $prfiSpannerIndex++;
      return;
    }
    throw new Exception("Database error was generated for testing");
  }

  function save_obj_as_post($obj, $first) {
    global $prfi_is_save;
    $res = null;
    if ( isset($obj->__submethod) && ($obj->__submethod === 'do_wp_action') ) {
      prfi_do_wp_action($obj);
      return $res;
    }
    if ( isset($obj->__submethod) && ($obj->__submethod === 'deoptimize_database') ) {
      prfi_deoptimize_database();
      return $res;
    }
    if ( isset($obj->__submethod) && ($obj->__submethod === 'deleteAllOldTrashed') ) {
      prfi_delete_all_old_trashed();
      prfi_trash_dummy_orders('all_users');
      return $res;
    }
    if ( isset($obj->__submethod) && ($obj->__submethod === 'optimize_database') ) {
      prfi_optimize_database();
      return $res;
    }
    $this->maybeCreateIntentionalError($obj->_datatype);
    $prfi_is_save = true;
    if ( $obj->_datatype === "orders" ) {
      $post = $this->save_object_as_order($obj);
      $res = $post;
      return $res;
    }
    if ( $obj->_datatype === "order_items" ) {
      $order_item = $this->save_object_as_order_item($obj);
      if ( $order_item ) 
        $res = $order_item;
      return $res;
    }
    if ( $obj->_datatype === "users" )  {
      $user = $this->save_object_as_user($obj);
      if ( $user )
        $res = $user;
      prfi_stale_datatype($obj->_datatype);
      return $res;
    }
    $post = null;
    if ( isset($obj->_markedForDeletion) && $obj->_markedForDeletion  ) {
      $id = $obj->id;
      $ghost = $this->object_to_ghost($obj, "fortrash");
      if ( $id && ($id > 0) ) {
        $this->trash_post($id);
      }
      $res = $ghost;
      prfi_stale_datatype($obj->_datatype);
      return $res;
    }
    if ( $obj->_datatype === "products" )  {
      $post = $this->save_object_as_product($obj, $first);
    } else {
      $ghost = $this->object_to_ghost($obj, null);
      $post = $this->save_ghost_as_post($ghost, $first);
      if ( $post ) {
        $obj->id = $post->ID; 
      }
      prfi_stale_datatype($obj->_datatype);
    }
    if ( isset($obj->_datatype) && ($obj->_datatype === "Inventory") && $first )
      $this->update_product_meta_from_inventory_obj($obj);
    $res = $post;
    return $res;
  }

  function save_request_as_posts($aReq) {
    global $prfiSpannerIndex;
    global $prfi_is_save;
    $prfiSpannerIndex = 0;
    $res = array();
    //tc(10);
    $objs = $this->request_to_objects($aReq);
    //tc(11);
    $this->objects_being_saved = $objs;
    $first = true;
    $knt = 0;
    while ( true ) {
      $did_something = false;
      $res_idx = -1;
      foreach ($objs as $obj) {
        $res_idx++;
        $dirty = isset($obj->_has_dirty_id) && $obj->_has_dirty_id;
        if ( (! $first) && (! $dirty) )
          continue;
        $did_something = true;
        $res[$res_idx] = $this->save_obj_as_post($obj, $first);
        $obj->_has_dirty_id = false;
      }
      if ( ! $did_something )
        break;
      $first = false;
      $knt++;
      if ( $knt > 50 ) {
        prfi_log('WARNING: save_request_as_posts looped too many times');
        break;
      }
    }
    return $res;
  }

  function update_product_meta_from_inventory_obj($obj) {
    if ( ! isset($obj->product) ) return;
    if ( ! is_object($obj->product) ) return;
    $product_id = $obj->product->id; if ( ! $product_id ) return;
    $product = wc_get_product($product_id); if ( ! $product ) return;
    if ( isset($obj->quantityOnPurchaseOrders) ) {
      prfi_set_meta($product_id, '_prfi_quantityOnPurchaseOrders', $obj->quantityOnPurchaseOrders);
    }
    if ( isset($obj->avgUnitCost) )
      prfi_set_meta($product_id, '_prfi_avgUnitCost', prfi_cook_cost($obj->avgUnitCost));
    if ( isset($obj->avgUnitCostExclTax) )
      prfi_set_meta($product_id, '_prfi_avgUnitCostExclTax', prfi_cook_cost($obj->avgUnitCostExclTax));
    if ( isset($obj->lastPurchaseUnitCostIncTax) )
      prfi_set_meta($product_id, '_prfi_lastPurchaseUnitCostIncTax', prfi_cook_cost($obj->lastPurchaseUnitCostIncTax));
    $quantityOnPurchaseOrders = prfi_get_meta_numeric($product_id, '_prfi_quantityOnPurchaseOrders');
    if ( isset($obj->situation) ) {
      $discontinued_str = 'No';
      if ( $obj->situation === 'Discontinued by Us' )
        $discontinued_str = 'Unknown';
      else if ( $obj->situation === 'Discontinued by Supplier' )
        $discontinued_str = 'Yes';
      prfi_set_meta($product_id, '_prfi_discontinued', $discontinued_str);
    }
    if ( isset($obj->quantityOnPurchaseOrders) )
      $quantityOnPurchaseOrders = $obj->quantityOnPurchaseOrders;
    if ( isset($obj->quantityOnFirmPreorders) ) {
      prfi_set_meta($product_id, '_prfi_quantityOnFirmPreorders', $obj->quantityOnFirmPreorders);
    }
    if ( isset($obj->quantityOnForecastPreorders) )
      prfi_set_meta($product_id, '_prfi_quantityOnForecastPreorders', $obj->quantityOnForecastPreorders);
    $quantityOnHand = $product->get_stock_quantity();
    $oversold = 0;
    if ( $quantityOnHand < 0 )
      $oversold = - $quantityOnHand;
    $value = $quantityOnPurchaseOrders - $oversold;
    prfi_set_meta($product_id, '_prfi_quantityOnPurchaseOrdersLessOversold', $value);
    if ( isset($obj->nextExpectedDeliveryDate) )
      prfi_set_meta($product_id, '_prfi_nextExpectedDeliveryDate', prfi_cook_date($obj->nextExpectedDeliveryDate));
    if ( isset($obj->externalQuantityOnHand) )
      prfi_set_meta($product_id, '_prfi_externalQuantityOnHand', $obj->externalQuantityOnHand);
    if ( isset($obj->externalPrice) )
      prfi_set_meta($product_id, '_prfi_externalPrice', $obj->externalPrice);
    if ( isset($obj->externalPriceFX) )
      prfi_set_meta($product_id, '_prfi_externalPriceFX', $obj->externalPriceFX);
  }

  function request_to_objects($aReq) {
    $body = filter_var($aReq->get_body());
    return json_decode($body);
  }

  function is_permanent_id($aId) {
    return $aId >= 0;
  }

  function object_to_ghost($aObj, $aReason) {
    //tc(60);
		$ghost = new stdClass();
    $id = isset($aObj->id) ? (int)$aObj->id : 0;
    if ( $this->is_permanent_id($id) ) {
      $ghost->ID = $id;
    } else {
      $ghost->ID = null;
      $ghost->temp_id = $id;
    }
    //tc(61);
    if ( isset($aObj->wp_post_title) )
      $ghost->post_title = $aObj->wp_post_title;
    else
      $ghost->post_title = $aObj->_datatype;
    if ( isset($aObj->parentId) )
      $ghost->post_parent = $aObj->parentId;
    //tc(62);
    $ghost->post_modified = date('Y-m-d H:i:s');
    $ghost->post_name = sanitize_title($ghost->post_title);
    $ghost->post_type = "stocktend_object";
    $ghost->post_status = "publish";
    if ( isset($aObj->__cruxFieldNames) )
      $ghost->__cruxFieldNames = $aObj->__cruxFieldNames;
    if ( isset($aObj->__old) )
      $ghost->__old = $aObj->__old;
    if ( (isset($aObj->__fileFieldName)) && ($aReason !== 'fortrash') ) {
      $field_name = $aObj->__fileFieldName;
      $base64 = $aObj->{$field_name};
      $path = isset($aObj->__attachmentsPathOnServer) ? $aObj->__attachmentsPathOnServer : null;
      $contents = base64_decode($base64);
      $aObj->{$field_name} = $this->create_file($contents, $aObj->__fileName, $path);
      $datatype = $aObj->_datatype;
      if ( ($datatype === 'Attachment') && $this->file_name_is_text_or_csv($aObj->__fileName) )
        $aObj->attachmentContents = $contents;
    }
    //tc(64);
    $this->add_object_properties_to_ghost($aObj, $ghost, $aReason);
    //tc(63);
    return $ghost;
  }

  function file_name_is_text_or_csv($file_name) {
    //$extension = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
    $array = explode('.', $file_name);
    $knt = count($array);
    if ( $knt === 0 ) 
      return false;
    $extension = strtolower($array[$knt - 1]);
    $res = ($extension === 'csv') || ($extension === 'txt');
    return $res;
  }

  function mkdir($path) {
    $path = str_replace("\\", "/", $path);
    $path = explode("/", $path);
    $rebuild = '';
    foreach($path AS $p) {
      $rebuild .= "/$p";
      if( ! is_dir($rebuild) ) {
        $oldmask = umask(0);
        mkdir($rebuild, 0777);
        umask($oldmask);
      }
    }
  }

  function create_file($contents, $file_name, $path) {
    if ( $path ) {
      if ( ! is_dir($path) )
        $this->mkdir($path);
      if ( ! is_dir($path) )
        throw new Exception("Unable to create subfolders for $path - an administrator should check server permissions");
    }
    $upload_path = $path ? $path : wp_upload_dir()['path'];
    $file_name = wp_unique_filename($upload_path, $file_name);
    if ( $path ) {
      $pres = file_put_contents($path . '/' . $file_name, $contents);
      if ( $pres === FALSE )
        throw new Exception("Unable to write to $path");
      return $file_name;
    } else {
      $ures = wp_upload_bits($file_name, null, $contents);
      return $ures['url'];
    }
  }

  function permanize_child_ids($aTempId, $aId) {
    foreach ($this->objects_being_saved as $obj) {
      if ( isset($obj->parentId) && ($obj->parentId === $aTempId) ) {
        if ( prfi_val($obj, 'parentId') !== $aId ) {
          $obj->parentId = $aId;
          $obj->_has_dirty_id = true;
        }
        foreach ($obj as $prop => $value) {
          if ( ! is_object($value) ) continue;
          if ( isset($value->kind) && ($value->kind === "parent") && (prfi_val($value, 'id') !== $aId) ) {
            $value->id = $aId;
            $obj->_has_dirty_id = true;
          }
        }
      }
    }
  }

  function permanize_reference_ids($aTempId, $aId) {
    foreach ($this->objects_being_saved as $obj) {
      foreach ($obj as $prop => $value) {
        if ( ! is_object($value) ) {
          if ( ($value === $aTempId) && str_ends_with($prop, '_id') ) {
            $obj->{$prop} = $aId;
            $obj->_has_dirty_id = true;
          }
          continue;
        }
        if ( isset($value->id) && ($value->id === $aTempId) ) {
          $value->id = $aId;
          $obj->_has_dirty_id = true;
        }
      }
    }
  }

  function check_stock_quantity_unchanged($old_db_value, $obj) {
    if ( isset($obj->__old) ) {
      $meta_value = $obj->_stock;
      if ( $meta_value != $old_db_value ) {
        $prop_name = '_stock';
        if ( isset($obj->__old->{$prop_name}) ) {
          $old_client_value = $obj->__old->{$prop_name};
          if ( ($old_db_value != $old_client_value) && (! (($old_db_value === '') && ($old_client_value === '1971-01-01'))) ) {
            $msg = "Updates conflict with another session - please refresh and try again. products #$obj->ID ($prop_name '$old_client_value' => '$meta_value') " .
              "(Other session set it to '$old_db_value')";
            throw new Exception($msg);
          }
        }
      }
    }
  }

  function do_save_object_as_product($aObj, $first) {
    $id = $aObj->id;
    if ( isset($aObj->_markedForDeletion) && $aObj->_markedForDeletion  ) {
      wp_delete_post($id);
      return $id;
    }
    if ( $id < 0 ) {
      $name = $this->unique_name_to_name($aObj->uniqueName);
      $sku = $this->unique_name_to_sku($aObj->uniqueName);
      $prod = new WC_Product();
      $prod->set_name($name);
      $prod->save();
      $id = $prod->get_id();
    } else {
      $prod = wc_get_product($id); prfi_chk($prod);
    }
    $prod->set_manage_stock('yes');
    if ( isset($aObj->_stock) ) {
      $oldVal = prfi_product_id_to_db_stock($prod->get_id());
      if ( $first )
        $this->check_stock_quantity_unchanged($oldVal, $aObj);
      prfi_set_product_stock_quantity($prod, $aObj->_stock);
      prfi_maybe_add_stock_clue(2, $id, '_stock', $oldVal, $aObj->_stock);
    }
    if ( isset($aObj->_low_stock_amount) )
      $prod->set_low_stock_amount($aObj->_low_stock_amount);
    if ( isset($aObj->_regular_price) ) {
      $regularPrice = $aObj->_regular_price;
      if ( ! empty($regularPrice) )
        $prod->set_regular_price($regularPrice);
    }
    if ( isset($aObj->_sku) ) {
      $sku = $aObj->_sku;
      if ( ! empty($sku) )
        $prod->set_sku($sku);
    }
    $this->set_product_wc_fields($prod, $aObj);
    $prod->save();
    prfi_update_post_modified($prod->get_id());
    return $id;
  }

  function set_product_wc_fields($prod, $obj) {
    $this->set_product_wc_metas($prod, $obj);
    $this->set_product_wc_attributes($prod, $obj);
  }

  function set_product_wc_metas($prod, $obj) {
    $realms = isset($obj->_realms) ? $obj->_realms : null; if ( ! $realms ) return;
    foreach ($obj as $prop => $value) {
      $realm = isset($realms->$prop) ? $realms->$prop : null; if ( ! $realm ) continue;
      if ( $realm->name !== 'WC Product' ) continue;
      $key = $realm->wcFieldName;
      prfi_set_meta($prod->get_id(), $key, $value);
    }
  }

  function set_product_wc_attributes($prod, $obj) {
    $realms = isset($obj->_realms) ? $obj->_realms : null; if ( ! $realms ) return;
    foreach ($obj as $prop => $value) {
      $realm = isset($realms->$prop) ? $realms->$prop : null; if ( ! $realm ) continue;
      if ( $realm->name !== 'WC Product Attribute' ) continue;
      $key = $realm->wcFieldName;
      $this->set_wc_product_attribute($prod, $key, $value);
    }
  }

	function set_wc_product_attribute( &$product, $key, $value ) {
	  $attributes = array();
	  $existing_attributes = $product->get_attributes();
  	$is_variation = 0;
		if ( $existing_attributes ) {
			foreach ( $existing_attributes as $existing_attribute ) {
				if ( $existing_attribute->get_name() === $key ) {
					$is_variation = $existing_attribute->get_variation();
					break;
				}
			}
		}
	  $attribute_object = new WC_Product_Attribute();
	  $attribute_object->set_name( $key );
	  $attribute_object->set_options( array($value) );
	  $attribute_object->set_variation( $is_variation );
	  $attributes[] = $attribute_object;
  	$product->set_attributes( $attributes );
	}

  function name_and_sku_to_product($aName, $aSKU) {
    global $wpdb;
    $sql = $wpdb->prepare("
        SELECT  p.ID
        FROM    $wpdb->posts p
                LEFT JOIN $wpdb->postmeta sku ON (p.ID = sku.post_id AND sku.meta_key = '_sku' AND sku.meta_value = %s)
        WHERE   p.post_type IN ('product', 'product_variation')
                AND p.post_title = %s
        LIMIT 1;
      ",
      $aSKU,
      $aName
    );
    $id = (int)$wpdb->get_var($sql);
    $prod = wc_get_product($id); prfi_chk($prod);
    return $prod;
  }

  function untrash_product($aName, $aSKU) {
    global $prfi_is_save;
    $prod = $this->name_and_sku_to_product($aName, $aSKU);
    $prod->set_status('publish');
    $now = date('Y-m-d H:i:s');
    $prod->set_date_modified($now);
    $prod->save();
    prfi_stale_datatype("products", true);
    prfi_create_morsel_from_product($prod);
    prfi_update_post_modified($prod->get_id());
    $prfi_is_save = false;
    return $prod->get_id();
  }

  function save_object_as_product($aObj, $first) {
    global $prfi_am_updating_product_directly;
    if ( isset($aObj->__submethod) && ($aObj->__submethod === 'untrash') ) {
      $id = $this->untrash_product($aObj->name, $aObj->_sku);
    } else {
      $prfi_am_updating_product_directly = true;
      $id = $this->do_save_object_as_product($aObj, $first);
      $prfi_am_updating_product_directly = false;
    }
    $post = get_post($id);
    $uniqueName = stripslashes($post->post_title);
    if ( isset($post->_sku) && $post->_sku )
      $differentiator = $post->_sku;
    else
      $differentiator = '#' . $id;
    $uniqueName .= " ($differentiator)";
    $post->name = $uniqueName;
    $post->uniqueName = $uniqueName;
    $post->post_title = $uniqueName;
    return $post;
  }

  function save_object_as_user($aObj) {
    $user = get_user_by('id', $aObj->id); prfi_chk($user);
    $this->set_user_wc_metas($user, $aObj);
    return $aObj;
  }

  function set_user_wc_metas($user, $obj) {
    $realms = isset($obj->_realms) ? $obj->_realms : null; if ( ! $realms ) return;
    foreach ($obj as $prop => $value) {
      $realm = isset($realms->$prop) ? $realms->$prop : null; if ( ! $realm ) continue;
      if ( $realm->name !== 'Meta' ) continue;
      $key = $realm->wcFieldName;
      prfi_set_user_meta($user->ID, $key, $value);
    }
  }

  function save_object_as_order($aObj) {
    $id = $aObj->id;
    if ( isset($aObj->_markedForDeletion) && $aObj->_markedForDeletion  ) {
      wp_delete_post($id);
      return $aObj;
    }
    $isNew = false;
    $manageInProfitori = isset($aObj->manageInProfitori) && ($aObj->manageInProfitori === 'Yes');
    if ( $id < 0 ) {
      $order = wc_create_order();
      $this->permanize_child_ids($id, $order->get_id());
      $this->permanize_reference_ids($id, $order->get_id());
      $id = $order->get_id();
      $isNew = true;
    } else {
      $order = wc_get_order($id); prfi_chk($order);
      $manageInProfitori = $manageInProfitori || (get_post_meta($id, '_manage_in_profitori', true) === 'Yes');
    }
    prfi_set_updating_order_directly($manageInProfitori);
    if ( $isNew )
      $order->set_date_created($aObj->order_date);
    if ( isset($aObj->status) ) {
      $status = $aObj->status;
      if ( substr($status, 0, 3) === "wc-" )
        $status = substr($status, 3);
      if ( $status !== $order->get_status() ) {
        $order->update_status($status);
        if ( $status === 'completed' ) {
          $order_date = isset($aObj->order_date) ? $aObj->order_date : $order->get_date_created();
          $order->set_date_completed($order_date);
        }
      }
    }
    if ( $isNew || $manageInProfitori ) {
      if ( isset($aObj->_billing_first_name) ) $order->set_billing_first_name($aObj->_billing_first_name);
      if ( isset($aObj->_billing_last_name) ) $order->set_billing_last_name($aObj->_billing_last_name);
      if ( isset($aObj->_billing_company) ) $order->set_billing_company($aObj->_billing_company);
      if ( isset($aObj->_billing_address_1) ) $order->set_billing_address_1($aObj->_billing_address_1);
      if ( isset($aObj->_billing_address_2) ) $order->set_billing_address_2($aObj->_billing_address_2);
      if ( isset($aObj->_billing_city) ) $order->set_billing_city($aObj->_billing_city);
      if ( isset($aObj->_billing_state) ) $order->set_billing_state($aObj->_billing_state);
      if ( isset($aObj->_billing_postcode) ) $order->set_billing_postcode($aObj->_billing_postcode);
      if ( isset($aObj->_billing_country) ) $order->set_billing_country($aObj->_billing_country);
      if ( isset($aObj->_billing_email) ) $order->set_billing_email($aObj->_billing_email);
      if ( isset($aObj->_billing_phone) ) $order->set_billing_phone($aObj->_billing_phone);
      if ( isset($aObj->_shipping_first_name) ) $order->set_shipping_first_name($aObj->_shipping_first_name);
      if ( isset($aObj->_shipping_last_name) ) $order->set_shipping_last_name($aObj->_shipping_last_name);
      if ( isset($aObj->_shipping_company) ) $order->set_shipping_company($aObj->_shipping_company);
      if ( isset($aObj->_shipping_address_1) ) $order->set_shipping_address_1($aObj->_shipping_address_1);
      if ( isset($aObj->_shipping_address_2) ) $order->set_shipping_address_2($aObj->_shipping_address_2);
      if ( isset($aObj->_shipping_city) ) $order->set_shipping_city($aObj->_shipping_city);
      if ( isset($aObj->_shipping_state) ) $order->set_shipping_state($aObj->_shipping_state);
      if ( isset($aObj->_shipping_postcode) ) $order->set_shipping_postcode($aObj->_shipping_postcode);
      if ( isset($aObj->_shipping_country) ) $order->set_shipping_country($aObj->_shipping_country);
      if ( isset($aObj->_customer_user) ) $order->set_customer_id($aObj->_customer_user);
    }
    $this->set_order_wc_fields($order, $aObj);
    $order->save();
    if ( (prfi_get_msg_to_server() === "setModDateToOrder") && $isNew && prfi_is_running_tests() )
      prfi_set_post_modified($id, $aObj->order_date);
    $post = get_post($id);
    $post->order_id = $id;
    prfi_set_updating_order_directly(false);
    return $post;
  }

  function set_order_wc_fields($prod, $obj) {
    $this->set_order_wc_metas($prod, $obj);
  }

  function set_order_wc_metas($order, $obj) {
    $realms = isset($obj->_realms) ? $obj->_realms : null; if ( ! $realms ) return;
    foreach ($obj as $prop => $value) {
      $realm = isset($realms->$prop) ? $realms->$prop : null; if ( ! $realm ) continue;
      if ( $realm->name !== 'Meta' ) continue;
      $key = $realm->wcFieldName;
      prfi_set_meta($order->get_id(), $key, $value);
    }
  }

/*
  function maybe_add_shipping_line($aObj, $order) {
    if ( (! isset($aObj->_order_shipping)) || (! $aObj->_order_shipping) ) return;
    $id = $aObj->id;
    $item = new WC_Order_Item_Shipping();
    $item->set_method_title("Flat rate");
    $item->set_total($aObj->_order_shipping);
    if ( isset($aObj->_order_shipping_tax) ) {
      $item->set_taxes(array('total' => array($aObj->_order_shipping_tax)));
    }
    $order->add_item($item);
    $order->calculate_totals();
  }

  function maybe_add_fees_line($aObj, $order) {
    if ( (! isset($aObj->fees_total)) || (! $aObj->fees_total) ) return;
    $id = $aObj->id;
    $item = new WC_Order_Item_Fee();
    $item->set_name('Fee');
    if ( isset($aObj->fees_tax) && ($aObj->fees_tax !== 0) ) {
      $item->set_total($aObj->fees_total);
      $item->set_total_tax($aObj->fees_tax);
    } else {
      $country_code = $order->get_shipping_country();
      $calculate_tax_for = array(
        'country' => $country_code, 
        'state' => '', 
        'postcode' => '', 
        'city' => ''
      );
      $item->set_amount($aObj->fees_total);
      $item->set_tax_class('');
      $item->set_tax_status('none');
      $item->set_total_tax(0);
      $item->set_total($aObj->fees_total);
      $item->calculate_taxes($calculate_tax_for);
    }
    $order->add_item($item);
    $order->calculate_totals();
  }

  function update_fee_order_item(&$item, $aObj, $order) {
    if ( ! (isset($aObj->_line_total) || isset($aObj->_line_tax)) ) return;
    if ( isset($aObj->_line_tax) && ($aObj->_line_tax !== 0) ) {
      if ( isset($aObj->_line_total) )
        $item->set_total($aObj->_line_total);
      if ( isset($aObj->_line_tax) )
        $item->set_total_tax($aObj->_line_tax);
    } else {
      if ( isset($aObj->_line_total) ) {
        $country_code = $order->get_shipping_country();
        $calculate_tax_for = array(
          'country' => $country_code,
          'state' => '',
          'postcode' => '',
          'city' => ''
        );
        $item->set_amount($aObj->line_total);
        $item->set_tax_class('');
        $item->set_tax_status('none');
        $item->set_total_tax(0);
        $item->set_total($aObj->_line_total);
        $item->calculate_taxes($calculate_tax_for);
      }
    }
  }
*/

  function update_fee_order_item(&$item, $aObj, $order) {
    if ( isset($aObj->_name) )
      $item->set_name($aObj->_name);
    if ( isset($aObj->_line_total) )
      $item->set_total($aObj->_line_total);
    if ( isset($aObj->_line_tax) ) {
      $line_tax = $aObj->_line_tax;
      $item->set_total_tax($line_tax);
      if ( $line_tax === 0 ) {
        $item->set_tax_class('');
        $item->set_tax_status('none');
      }
    }
  }

  function add_fee_item($order, $aObj) {
    $item = new WC_Order_Item_Fee();
    $this->update_fee_order_item($item, $aObj, $order);
    $this->calculate_order_item_taxes($item, $aObj, $order);
    $order->add_item($item);
    $order->save();
    return $item->get_id();
  }

  function update_shipping_order_item(&$item, $aObj, $order) {
    if ( isset($aObj->_name) ) {
      $item->set_name($aObj->_name);
    }
    if ( isset($aObj->_line_total) )
      $item->set_total($aObj->_line_total);
    if ( isset($aObj->method_id) )
      $item->set_method_id($aObj->method_id);
    if ( isset($aObj->instance_id) )
      $item->set_instance_id($aObj->instance_id);
  }

  function calculate_order_item_taxes(&$item, $aObj, $order) {
    $country_code = $order->get_shipping_country();
    $calculate_tax_for = array(
      'country' => $country_code,
      'state' => '',
      'postcode' => '',
      'city' => ''
    );
    $item->calculate_taxes($calculate_tax_for);
  }

  function add_shipping_item($order, $aObj) {
    $item = new WC_Order_Item_Shipping();
    $this->update_shipping_order_item($item, $aObj, $order);
    $this->calculate_order_item_taxes($item, $aObj, $order);
    $order->add_item($item);
    $order->save();
    return $item->get_id();
  }

  function save_object_as_order_item($aObj) {
    $id = $aObj->id;
    if ( isset($aObj->_markedForDeletion) && $aObj->_markedForDeletion  ) {
      try {
        wc_delete_order_item($id);
      } catch(Exception $aException) {
      }
      return $aObj;
    }
    $order_id = $aObj->theOrder->id;
    $order = wc_get_order($order_id); prfi_chk($order);
    $order_modified = get_the_modified_date('Y-m-d H:i:s', $order->get_id());
    $isNew = false;
    $product_id = null;
    $manageInProfitori = get_post_meta($order_id, '_manage_in_profitori', true) === 'Yes';
    prfi_set_updating_order_directly($manageInProfitori);
    try {
      if ( $id < 0 ) {
        $tempId = $id;
        //$total = round($aObj->_line_total * (10 / 11), 2); // Testing only - assume 10% tax
        //$total = $aObj->_line_total - $aObj->_line_tax;
        $total = $aObj->_line_total; // 6/2/22
        $product_id = isset($aObj->_product_id) ? $aObj->_product_id : null;
        if ( ! $product_id )
          $product_id = isset($aObj->_variation_id) ? $aObj->_variation_id : null;
        if ( $product_id ) {
          $product = wc_get_product($product_id);
          $id = $order->add_product(
            $product,
            $aObj->_qty, 
            ['subtotal' => $total, 'total' => $total]
          );
        } else {
          if ( isset($aObj->order_item_type) && ($aObj->order_item_type === 'fee') )
            $id = $this->add_fee_item($order, $aObj);
          else if ( isset($aObj->order_item_type) && ($aObj->order_item_type === 'shipping') )
            $id = $this->add_shipping_item($order, $aObj);
          else
            return $aObj;
          if ( ! $id ) {
            prfi_log("Order item creation failed for following object:");
            prfi_log($aObj);
            throw new Exception("Order item creation failed for order $order_id");
          }
        }
        $this->permanize_child_ids($tempId, $id);
        $this->permanize_reference_ids($tempId, $id);
        $isNew = true;
      }
      if ( (! $isNew) && (! prfi_is_running_tests()) && (! $manageInProfitori) ) return $aObj;
      if ( $id > 0 ) {
        $order_item = prfi_id_to_order_item($id);
        if ( $order_item ) {
          if ( is_a($order_item, 'WC_Order_Item_Product') ) {
            $this->update_product_order_item($order_item, $aObj);
          } else if ( is_a($order_item, 'WC_Order_Item_Fee') ) {
            $this->update_fee_order_item($order_item, $aObj, $order);
            $this->calculate_order_item_taxes($order_item, $aObj, $order);
          } else if ( is_a($order_item, 'WC_Order_Item_Shipping') ) {
            $this->update_shipping_order_item($order_item, $aObj, $order);
            $this->calculate_order_item_taxes($order_item, $aObj, $order);
          }
          $order_item->save();
          $order->save();
        }
      }
      $order->calculate_totals();
      if ( isset($aObj->order_status) ) {
        $status = $aObj->order_status;
        if ( ! empty($status) ) {
          if ( substr($status, 0, 3) === "wc-" )
            $status = substr($status, 3);
          if ( $status === "pending" ) {
            $order->update_status("pending");
            $order->save();
          } else {
            $order_date = $order->get_date_completed();
            $order->update_status("pending"); // so that payment_complete will trigger the right hooks
            $order->save();
            if ( $status === "completed" )
              $order->payment_complete();
            $order->update_status($status);
            if ( $status === "completed" )
              $order->set_date_completed($order_date);
            $order->save();
          }
        }
      }
      if ( (prfi_get_msg_to_server() === "setModDateToOrder") && $isNew && prfi_is_running_tests() )
        prfi_set_post_modified($order->get_id(), $order_modified);
      else
        prfi_refresh_order_modified_by_order_item_id($id);
      prfi_stale_datatype_ex("orders", true, NULL);
      $aObj->id = $id;
    } finally {
      prfi_set_updating_order_directly(false);
    }
    return $aObj;
  }

  function update_product_order_item($order_item, $aObj) {
    if ( isset($aObj->_variation_id) && $aObj->_variation_id ) $order_item->set_variation_id($aObj->_variation_id);
    if ( isset($aObj->_product_id) && $aObj->_product_id ) $order_item->set_product_id($aObj->_product_id);
    if ( isset($aObj->_qty) ) $order_item->set_quantity($aObj->_qty);
    if ( isset($aObj->_line_total) ) $order_item->set_total($aObj->_line_total);
    if ( isset($aObj->_line_tax) ) $order_item->set_total_tax($aObj->_line_tax);
  }

  function save_ghost_as_post($aGhost, $first) {
    $datatype = $aGhost->meta_input["stocktend__datatype"];
    if ( $aGhost->ID === null ) {
      if ( ! $first ) {
        throw new Exception("Unexpected error saving post (" . $datatype . ")");
      }
      if ( isset($aGhost->post_parent) && ($aGhost->post_parent < 0) ) {
        throw new Exception("Tried to save post with temporary parent id (" . $datatype . ")");
      }
      //tc(24);
      $this->refresh_ghost_crux($aGhost);
      $id = $this->insert_post($aGhost); prfi_chk($id);
      //tc(25);
      $this->permanize_child_ids($aGhost->temp_id, $id);
      $this->permanize_reference_ids($aGhost->temp_id, $id);
      $this->check_not_duplicate($aGhost);
    } else {
      //tc(20);
      $id = $this->update_post($aGhost, $first); prfi_chk($id);
      //tc(21);
    }
    //tc(22);
    $post = get_post($id);
    //tc(23);
    return $post;
  }

  function ghost_to_crux($ghost) {
    $res = '';
    if ( ! isset($ghost->__cruxFieldNames) ) return null;
    foreach ( $ghost->__cruxFieldNames as $fieldName ) {
      $val = $ghost->meta_input['stocktend_' . $fieldName];
      if ( $val && ($val[0] === "{") ) {
        $val = json_decode($val);
        $val = $val->id;
      }
      $res .= $val . '-';
    }
    return $res;
  }

  function refresh_ghost_crux($ghost) {
    $crux = $this->ghost_to_crux($ghost); if ( (! $crux) || ($crux === '') ) return;
    $ghost->meta_input["stocktend___crux"] = $crux;
  }

  function check_not_duplicate($ghost) {
    global $wpdb;
    if ( ! isset($ghost->meta_input["stocktend___crux"]) ) return;
    $crux = $ghost->meta_input["stocktend___crux"];
    $datatype = $ghost->meta_input["stocktend__datatype"];
    $sql = $wpdb->prepare(
      "
        SELECT 
          COUNT(*) 
        FROM 
          $wpdb->postmeta pm
          STRAIGHT_JOIN $wpdb->posts p ON pm.post_id = p.ID AND p.post_status = 'publish'
          INNER JOIN $wpdb->postmeta pm2 ON p.id = pm2.post_id AND pm2.meta_key = 'stocktend__datatype' AND pm2.meta_value = %s
        WHERE
          pm.meta_key = 'stocktend___crux' AND
          pm.meta_value = %s AND
          pm.post_id <> %d
      ",
      $datatype,
      $crux,
      $ghost->ID
    );
    $knt = (int)$wpdb->get_var($sql);
    if ( $knt > 0 ) {
      throw new Exception(
        "Data updates in this session conflict with those made by another session. Please refresh your browser and try again. ($datatype $crux)"
      );
    }
  }

  function insert_post($aGhost) {
    global $wpdb;
    $post_author = 1;
    $post_date = $aGhost->post_modified;
    $post_date_gmt = $post_date; 
    $post_title = $aGhost->post_title;
    if ( isset($aGhost->meta_input["stocktend_name"]) )
      $post_title = $aGhost->meta_input["stocktend_name"];
    $post_status = $aGhost->post_status;
    $post_name = $aGhost->post_name;
    $post_modified = $post_date;
    $post_modified_gmt = $post_date_gmt;
    $post_parent = isset($aGhost->post_parent) ? $aGhost->post_parent : 0;
    $post_type = $aGhost->post_type;
    $sql = $wpdb->prepare("
      INSERT INTO $wpdb->posts
        ( `post_author`, 
          `post_date`, 
          `post_date_gmt`, 
          `post_title`, 
          `post_status`, 
          `post_name`, 
          `post_modified`, 
          `post_modified_gmt`, 
          `post_parent`, 
          `post_type`
        )
      VALUES
        ( %d,
          %s,
          %s,
          %s,
          %s,
          %s,
          %s,
          %s,
          %d,
          %s
        )
      ",
      $post_author,
      $post_date,
      $post_date_gmt,
      $post_title,
      $post_status,
      $post_name, 
      $post_modified, 
      $post_modified_gmt, 
      $post_parent, 
      $post_type
    );
    $wpdb->query($sql);
    $id = (int)$wpdb->get_var("SELECT LAST_INSERT_ID()");
    $aGhost->ID = $id;
    $this->insert_post_meta($aGhost);
    return $id;
  }

  function update_post($aGhost, $first) {
    global $wpdb;
    $id = $aGhost->ID;
    $post_title = $aGhost->post_title;
    if ( isset($aGhost->meta_input["stocktend_name"]) )
      $post_title = $aGhost->meta_input["stocktend_name"];
    $post_status = $aGhost->post_status;
    $post_name = $aGhost->post_name;
    $post_modified = $aGhost->post_modified;
    $post_modified_gmt = $post_modified;
    $sql = $wpdb->prepare(
      " UPDATE $wpdb->posts SET
          `post_title` = %s,
          `post_status` = %s,
          `post_name` = %s,
          `post_modified` = %s,
          `post_modified_gmt` = %s
        WHERE id = %s
      ",
      $post_title,
      $post_status,
      $post_name,
      $post_modified,
      $post_modified_gmt,
      $id
    );
    $wpdb->query($sql);
    $this->update_post_meta($aGhost, $first);
    return $id;
  }

  function insert_post_meta($aGhost) {
    global $wpdb;
    while ( true ) {
      $meta_value = current($aGhost->meta_input);
      if ( $meta_value === FALSE ) break;
      $meta_value = $meta_value;
      $meta_key = key($aGhost->meta_input);
      $sql = $wpdb->prepare(
        " INSERT INTO $wpdb->postmeta
            ( `post_id`,
              `meta_key`,
              `meta_value`
            )
          VALUES
            (
              %d,
              %s,
              %s
            )
        ",
        $aGhost->ID,
        $meta_key,
        $meta_value
      );
      $wpdb->query($sql);
      next($aGhost->meta_input);
    }
  }

  function update_post_meta($aGhost, $first) {
    global $wpdb;
    while ( true ) {
      $meta_value = current($aGhost->meta_input);
      if ( $meta_value === FALSE ) break;
      $meta_key = key($aGhost->meta_input);
      $query = $wpdb->prepare("SELECT meta_value FROM $wpdb->postmeta WHERE post_id = %d AND meta_key = %s LIMIT 1", $aGhost->ID, $meta_key);
      $rows = $wpdb->get_results($query);
      $knt = count($rows);
      $old_db_value = NULL;
      if ( $knt === 0 ) {
        $sql = $wpdb->prepare(
          " INSERT INTO $wpdb->postmeta
              ( `post_id`,
                `meta_key`,
                `meta_value`
              )
            VALUES
              (
                %d,
                %s,
                %s
              )
          ",
          $aGhost->ID,
          $meta_key,
          $meta_value
        );
      } else {
        $old_db_value = $rows[0]->meta_value;
        if ( isset($aGhost->__old) ) {
          if ( $meta_value != $old_db_value ) {
            $prop_name = $this->strip_start($meta_key, 'stocktend_');
            if ( isset($aGhost->__old->{$prop_name}) ) {
              $old_client_value = $aGhost->__old->{$prop_name};
              if ( ($old_db_value != $old_client_value) && (! (($old_db_value === '') && ($old_client_value === '1971-01-01'))) ) {
                $datatype = $aGhost->meta_input["stocktend__datatype"];
                $msg = "Updates conflict with another user - please refresh and try again. $datatype #$aGhost->ID ($prop_name '$old_client_value' => '$meta_value') " .
                  "(Other user set it to '$old_db_value')";
                if ( $first )
                  throw new Exception($msg);
              }
            }
          }
        }
        $sql = $wpdb->prepare(
          " UPDATE $wpdb->postmeta
            SET `meta_value` = %s
            WHERE post_id = %d AND meta_key = %s
          ",
          $meta_value,
          $aGhost->ID,
          $meta_key
        );
      }
      $wpdb->query($sql);
      if ( ($meta_key === 'stocktend_quantityOnHand') ) {
        $datatype = $aGhost->meta_input["stocktend__datatype"];
        if ( $datatype === 'Inventory' )
          prfi_maybe_add_stock_clue(10, $aGhost->ID, 'quantityOnHand', $old_db_value, $meta_value, '', 'Inventory');
      }
      next($aGhost->meta_input);
    }
  }

  function add_object_properties_to_ghost($aObj, $aGhost, $aReason) {
    //tc(70);
    foreach ($aObj as $prop => $value) {
      if ( $prop === "id" ) continue;
      if ( $prop === "parentId" ) continue;
      if ( $prop === "post_parent" ) continue; // Added 3/2/2022
      if ( $prop === "post_modified" ) continue;
      if ( $prop === "__cruxFieldNames" ) continue;
      if ( $prop === "__fileFieldName" ) continue;
      if ( $prop === "__fileName" ) continue;
      //tc(71);
      if ( is_object($value) ) {
        //tc(74);
        if ( (! isset($value->id)) || ($value->id <= 0) )
          $value = $this->replace_keyval_with_id($value, $aReason);
        //tc(75);
        if ( isset($value->kind) && ($value->kind === "parent") ) {
          $aGhost->post_parent = (int)($value->id);
        }
        //tc(76);
        $value = json_encode($value, JSON_UNESCAPED_UNICODE);
        $aGhost->meta_input["stocktend_$prop"] = $value;
        //tc(77);
      } else {
        //tc(72);
        $mod_value = $value;
        if ( $prop !== 'attachmentContents' )
          $mod_value = prfi_sanitize_textarea_field($value);
        $aGhost->meta_input["stocktend_$prop"] = $mod_value;
        //tc(73);
      }
    }
  }

  function replace_keyval_with_id($aValue, $aReason) {
    if ( ! isset($aValue->keyname) ) return $aValue;
    if ( ! isset($aValue->keyval) ) return $aValue;
    $keyname = $aValue->keyname;
    $keyval = $aValue->keyval;
    $dt = $aValue->datatype;
    $id = $this->datatype_and_keyval_to_id($dt, $keyname, $keyval);
    if ( $id )
      $aValue->id = $id;
    if ( $aReason !== "fortrash" ) {
      if ( $dt === "products" ) {
        if ( (! isset($aValue->id)) || (! $aValue->id) ) {
          $msg = "Product not found: " . $keyval;
          //throw new Exception($msg); Stops harmonize from working when there are products with SKUs that have changed
        }
      }
    }
    return $aValue;
  }
  
  function replace_id_with_keyval($aValue) {
    if ( ! $aValue ) return $aValue;
    if ( ! is_string($aValue) ) return $aValue;
    if ( strlen($aValue) === 0 ) return $aValue;
    if ( $aValue[0] !== "{" ) return $aValue;
    $aValue = json_decode($aValue);
    if ( ! isset($aValue->keyname) ) return $aValue;
    if ( ! isset($aValue->id) ) return $aValue;
    $id = $aValue->id;
    $dt = $aValue->datatype;
    $keyname = $aValue->keyname;
    $aValue->keyval = $this->datatype_and_id_to_keyval($dt, $keyname, $id);
    return $aValue;
  }

  function datatype_and_id_to_keyval($aDatatype, $aKeyname, $aId) {
    if ( $aDatatype === "products" )
      return $this->id_to_product_unique_name($aId);
    global $wpdb;
    $metaKey1 = "stocktend_$aKeyname";
    $query = $wpdb->prepare(
      "SELECT " .
        "pm1.meta_value " .
      "FROM " .
        "$wpdb->posts posts " .
          "INNER JOIN $wpdb->postmeta pm1 ON posts.id = pm1.post_id " .
          "INNER JOIN $wpdb->postmeta pm2 ON posts.id = pm2.post_id " .
      "WHERE " .
        "posts.id = %s AND " .
        "pm1.meta_key = %s AND " .
        "pm2.meta_key = 'stocktend__datatype' AND " .
        "pm2.meta_value = %s ",
      $aId,
      $metaKey1,
      $aDatatype
    );
    //$res = stripslashes($wpdb->get_var($query));
    $value = $wpdb->get_var($query);
    if ( $value && ($value[0] === "{") ) {
      $json = json_decode($value);
      if ( is_object($json) ) {
        $value = $json;
      } else {
        $value = stripslashes($value);
      }
    } else {
      $value = stripslashes($value);
    }
    return $value;
  }

  function id_to_product_unique_name($aId) {
    $prod = wc_get_product($aId); if ( ! $prod ) return '';
    $res = $prod->get_name();
    $sku = $prod->get_sku();
    if ( $sku )
      $differentiator = $sku;
    else
      $differentiator = '#' . $prod->get_id();
    $res .= " ($differentiator)";
    return $res;
  }

  function datatype_and_keyval_to_id($aDatatype, $aKeyname, $aKeyval) {
    //tc(90);
    global $gLastK2IDatatype;
    global $gLastK2IKeyval;
    global $gLastK2IId;
    $keyval = $aKeyval;
    if ( is_object($keyval) ) {
      if ( isset($keyval->id) )
        return $keyval->id;
      $keyval = $keyval->keyval;
    }
    if ( $aDatatype === "products" ) {
      //tc(91);
      $res =  $this->unique_name_to_product_id($aKeyval);
      //tc(92);
      return $res;
    }
    if ( ($aDatatype === $gLastK2IDatatype) && ($aKeyval === $gLastK2IKeyval) )
      return $gLastK2IId;
    //tc(93);
    global $wpdb;
    $metaKey1 = "stocktend_$aKeyname";
    $query = $wpdb->prepare(
      "SELECT " .
        "pm1.post_id " .
      "FROM " .
        "$wpdb->postmeta pm1 " .
          "INNER JOIN $wpdb->postmeta pm2 ON pm1.post_id = pm2.post_id " .
          "INNER JOIN $wpdb->posts p ON (pm1.post_id = p.id AND p.post_status IN ('publish', 'private')) " .
      "WHERE " .
        "pm1.meta_key = %s AND " .
        "pm1.meta_value = %s AND " .
        "pm2.meta_key = 'stocktend__datatype' AND " .
        "pm2.meta_value = %s ",
      $metaKey1,
      $keyval,
      $aDatatype
    );
    $res = (int)$wpdb->get_var($query);
    //tc(94);
    $gLastK2IDatatype = $aDatatype;
    $gLastK2IKeyval = $aKeyval;
    $gLastK2IId = $res;
    return $res;
  }

  function unique_name_to_product_id($aUniqueName) {
    global $wpdb;
    $id = $this->unique_name_to_id($aUniqueName);
    if ( $id )
      return $id;
    $name = $this->unique_name_to_name($aUniqueName);
    $sku = $this->unique_name_to_sku($aUniqueName);
    if ( ! $sku ) {
      $query = $wpdb->prepare(
        "SELECT " .
          "p.id " .
        "FROM " .
          "$wpdb->posts p " .
        "WHERE " .
          "post_title = %s " .
        $name
      );
      $res = (int)$wpdb->get_var($query);
      return $res;
    }
    $query = $wpdb->prepare(
      "SELECT " .
        "p.id " .
      "FROM " .
        "$wpdb->posts p " .
        "INNER JOIN $wpdb->postmeta m ON p.ID = m.post_id AND m.meta_key = '_sku' " .
      "WHERE " .
        "post_title = %s AND post_status IN ('publish', 'private') AND " .
        "m.meta_value = %s ",
      $name,
      $sku
    );
    $res = (int)$wpdb->get_var($query);
    if ( ! $res ) {
      $query = $wpdb->prepare(
        "SELECT " .
          "m.post_id id " .
        "FROM " .
          "$wpdb->postmeta m " .
        "WHERE " .
          "m.meta_key = '_sku' AND " .
          "m.meta_value = %s " .
          "LIMIT 1 ",
        $sku
      );
      $res = (int)$wpdb->get_var($query);
    }
    return $res;
  }

  function unique_name_to_id($aName) {
    $str = $this->unique_name_to_contents_of_trailing_brackets($aName); if ( ! $str ) return null;
    if ( $str[0] !== "#" ) return null;
    return (int)substr($str, 1);
  }

  function unique_name_to_sku($aName) {
    $str = $this->unique_name_to_contents_of_trailing_brackets($aName); if ( ! $str ) return null;
    if ( $str[0] === "#" ) return null;
    return $str;
  }

  function unique_name_to_contents_of_trailing_brackets($aName) {
    $open = strrpos($aName, "("); if ( $open === FALSE ) return null;
    $close = strrpos($aName, ")"); if ( $close === FALSE ) return null;
    if ( $close < $open ) return null;
    return substr($aName, $open + 1, $close - $open - 1);
  }

  function unique_name_to_name($aName) {
    $open = strrpos($aName, " ("); if ( $open === FALSE ) return null;
    return substr($aName, 0, $open);
  }

  function url_to_datatype($aUrl) {
    return $this->url_and_parm_name_to_value($aUrl, "datatype");
  }

  function url_to_source($aUrl) {
    return $this->url_and_parm_name_to_value($aUrl, "source");
  }

  function url_to_parent_id($aUrl) {
    return $this->url_and_parm_name_to_value($aUrl, "parent_id");
  }

  function url_to_ids($aUrl) {
    $str =  $this->url_and_parm_name_to_value($aUrl, "ids"); if ( ! $str ) return null;
    $res = explode(",", $str);
    return $res;
  }

  function url_to_fields($aUrl) {
    $str =  $this->url_and_parm_name_to_value($aUrl, "fields"); if ( ! $str ) return null;
    $res = explode(",", $str);
    return $res;
  }

  function url_to_id($aUrl) {
    return $this->url_and_parm_name_to_value($aUrl, "id");
  }

  function url_to_prop_name($aUrl) {
    return $this->url_and_parm_name_to_value($aUrl, "propName");
  }

  function url_to_prop_val($aUrl) {
    return $this->url_and_parm_name_to_value($aUrl, "propVal");
  }

  function url_and_parm_name_to_value($aUrl, $aParmName) {
    return prfi_url_and_parm_name_to_value($aUrl, $aParmName);
  }

  function bundle_to_product($bundle) {
    $ref_str = $bundle["product"];
    if ( is_object($ref_str) )
      $ref = $ref_str;
    else
      $ref = json_decode($ref_str);
    if ( ! isset($ref->id) )
      return null;
    $res = wc_get_product($ref->id);
    return $res;
  }

  function decorate_bundle($bundle) {
    $res = $bundle;
    $product = $this->bundle_to_product($bundle); if ( ! $product ) return $res;
    $res['sellableQuantity'] = $product->get_stock_quantity();
    return $res;
  }

  function decorate_bundles($bundles) {
    $res = array();
    foreach ( $bundles as $bundle ) {
      $bundle = $this->decorate_bundle($bundle);
      $res[] = $bundle;
    }
    return $res;
  }

  function get_objects($aReq) {
    //tc(40);
    try {
      if ( prfi_be_slow() ) 
        sleep(2);
      $this->tryst = prfi_retrieve_or_create_tryst();
      //tc(44);
      $url = $GLOBALS["stApiUrl"];
      $source = $this->url_to_source($url);
      if ( $source === "WC" )
        return $this->get_objects_wc($aReq);
      if ( $source === "postImage" ) {
        $ok = $this->echo_post_image($aReq);
        if ( ! $ok )
          $this->echo_empty_image();
          //http_response_code(404);
        die;
      }
      if ( $source === "download" ) {
        $this->echo_download($aReq);
        die;
      }
      if ( $source === "wcStockLevel" ) {
        $this->echo_wc_stock_level($aReq);
        die;
      }
      if ( $source === "search" ) {
        $datatype = 'Found';
        $post_rows = $this->find_objects($aReq);
      } else {
        //tc(45);
        $datatype = $this->url_to_datatype($url);
        if ( $datatype === 'Null' ) 
          $post_rows = array();
        else {
          $fields = $this->url_to_fields($url);
          //tc(41);
          $post_rows = $this->get_post_rows($datatype, $fields);
        }
      }
      //tc(42);
      $objects = $this->rows_to_objects($post_rows, $datatype);
      if ( $datatype === "Bundle" )
        $objects = $this->decorate_bundles($objects);
      $objects = $this->maybe_append_tryst($objects);
		  $response = rest_ensure_response($objects);
      //tc(43);
      return $response;
    } catch(Exception $aException) {
      return new WP_Error('Error', $aException->getMessage());
    } finally {
      //tcReport();
    }
  }

  function echo_empty_image() {
    $name = PRFI__PLUGIN_DIR . '/images/empty.jpg';
    $image = file_get_contents($name);
    echo $image;
  }

  function echo_post_image($req) {
    $url = $GLOBALS["stApiUrl"];
    $id = $this->url_and_parm_name_to_value($url, "id");
    $image_type = $this->url_and_parm_name_to_value($url, "imageType");
    $att_id = get_post_thumbnail_id($id); if ( ! $att_id ) return false;
    $image_urls = wp_get_attachment_image_src($att_id, $image_type);
    if ( $image_urls && (sizeof($image_urls) > 0) ) {
      $image_url = wp_get_attachment_image_src($att_id, $image_type)[0];
      if ( $image_url ) {
        $image = file_get_contents($image_url);
        if ( $image ) {
          echo $image;
          return true;
        }
      }
    }
    return false;
  }

  function echo_wc_stock_level($req) {
    $url = $GLOBALS["stApiUrl"];
    $id = $this->url_and_parm_name_to_value($url, "id");
    $product = wc_get_product($id);
    echo $product->get_stock_quantity();
  }

  function echo_download($req) {
    $url = $GLOBALS["stApiUrl"];
    $id = $this->url_and_parm_name_to_value($url, "castId");
    $field_name = $this->url_and_parm_name_to_value($url, "field");
    $file_name = get_post_meta($id, 'stocktend_' . $field_name, true); if ( ! $file_name ) return;
    $path = $this->get_attachments_path();
    $subfolder = $this->url_and_parm_name_to_value($url, "subfolder");
    if ( $subfolder )
      $path .= '/' . $subfolder;
    $path_and_file = $path . '/' . $file_name;
    $content_type = mime_content_type($path_and_file);
    header('Content-type: ' . $content_type);
    header('Content-Disposition: attachment; filename="' . $file_name . '"');
    readfile($path_and_file);
  }

  function get_attachments_path() {
    $id = prfi_get_configuration_id(); if ( ! $id ) return '';
    $res = get_post_meta($id, 'stocktend_attachmentsPathOnServer', true);
    return $res;
  }

  function secure_dynamic_sql($aStr) {
    return preg_replace('/[^a-zA-Z0-9_]/', '', $aStr);
  }

  function get_modified_after() {
    $url = $GLOBALS["stApiUrl"];
    $res = $this->url_and_parm_name_to_value($url, "modifiedAfter");
    return $res;
  }

  function get_post_rows($aDatatype, $aFieldNames) {
    global $wpdb;
    global $prfi_subselects;
    $subset_condition = $this->get_subset_condition();
    $prfi_subselects = prfi_conf_val('subselects') === 'Yes';
    $db = $wpdb->prefix;
    $cols = $this->field_names_to_cols($aFieldNames); // Note: secured within field_names_to_cols
    $joins = $this->field_names_to_meta_joins($aFieldNames, false); // Note: secured within field_names_to_meta_joins
    $modifiedAfter = $this->get_modified_after();
    $includeDeleted = '';
    if ( $modifiedAfter )
      $includeDeleted = 'includeDeleted';
    else
      $modifiedAfter = '1970-01-01 00:00:01';
    if ( $aDatatype === 'Morsel' ) {
      // Faster for a data set that is often empty
      $sql = $wpdb->prepare("
          SELECT  p.ID,
                  p.post_title,
                  p.post_parent,
                  p.post_modified,
                  IF ( p.post_status IN ('publish', 'private'), '', 'DELETE' ) __incrementalDelete
                  $cols
          FROM    $wpdb->posts p
                  INNER JOIN $wpdb->postmeta pm ON p.id = pm.post_id
                  $joins
          WHERE   p.post_type = 'stocktend_object'
                  AND p.post_modified >= %s
                  AND ((%s = 'includeDeleted') OR (p.post_status IN ('publish', 'private')))
                  AND pm.meta_key = 'stocktend__datatype'
                  AND pm.meta_value = %s
          " . $subset_condition . "
          GROUP BY p.ID
          ORDER BY p.ID ASC;
          ",
        $modifiedAfter,
        $includeDeleted,
        $aDatatype
      );
    } else {
      $sql = $wpdb->prepare("
          SELECT  p.ID,
                  p.post_title,
                  p.post_parent,
                  p.post_modified,
                  IF ( p.post_status IN ('publish', 'private'), '', 'DELETE' ) __incrementalDelete
                  $cols
          FROM    $wpdb->postmeta pm
                  STRAIGHT_JOIN $wpdb->posts p ON pm.post_id = p.ID 
                    AND p.post_type = 'stocktend_object'
                    AND p.post_modified >= %s
                    AND ((%s = 'includeDeleted') OR (p.post_status IN ('publish', 'private')))
                  $joins
          WHERE pm.meta_key = 'stocktend__datatype'
                  AND pm.meta_value = %s
          " . $subset_condition . "
          GROUP BY p.ID
          ORDER BY p.ID ASC;
          ",
        $modifiedAfter,
        $includeDeleted,
        $aDatatype
      );
    }
    //tc(200);
    //$res = $wpdb->get_results($sql);
    $wpdb->query($sql);
    //tc(201);
    $res = $wpdb->last_result;
    //tc(202);
    return $res;
  }

  function get_atumPO_post_rows($aFieldNames) {
    global $wpdb;
    $db = $wpdb->prefix;
    $cols = $this->field_names_to_cols($aFieldNames, true);
    $joins = $this->field_names_to_meta_joins($aFieldNames, true);
    $sql = "
        SELECT  p.ID,
                p.post_title,
                p.post_parent,
                p.post_content,
                p.post_modified
                $cols
        FROM    $wpdb->posts p
                $joins
        WHERE   p.post_type = 'atum_purchase_order'
        ORDER BY p.ID ASC;
      ";
    $res = $wpdb->get_results($sql);
    return $res;
  }

  function get_atumSupplier_post_rows($aFieldNames) {
    global $wpdb;
    $db = $wpdb->prefix;
    $cols = $this->field_names_to_cols($aFieldNames, true);
    $joins = $this->field_names_to_meta_joins($aFieldNames, true);
    $sql = "
        SELECT  p.ID,
                p.post_title,
                p.post_parent,
                p.post_modified
                $cols
        FROM    $wpdb->posts p
                $joins
        WHERE   p.post_type = 'atum_supplier'
                AND p.post_status IN ('publish', 'private')
        GROUP BY p.ID
        ORDER BY p.ID ASC;
      ";
    $res = $wpdb->get_results($sql);
    return $res;
  }

  function get_category_rows($aFieldNames) {
    global $wpdb;
    $db = $wpdb->prefix;
    $sql = "
        SELECT  term.term_id ID,
                'NA' post_title,
                0 post_parent,
                CURDATE() post_modified,
                term.name categoryName
        FROM {$db}terms term
        LEFT JOIN {$db}term_taxonomy tax ON tax.term_id = term.term_id
        WHERE tax.taxonomy = 'product_cat'
      ";
    $res = $wpdb->get_results($sql);
    return $res;
  }

  function get_productCategory_rows($aFieldNames) {
    global $wpdb;
    $db = $wpdb->prefix;
    $sql = "
        SELECT  ((tax.term_taxonomy_id * 1000) + rel.object_id) ID,
                'NA' post_title,
                0 post_parent,
                CURDATE() post_modified,
                term.name categoryName,
                rel.object_id productId,
                term.term_id categoryId
        FROM {$db}terms term
        LEFT JOIN {$db}term_taxonomy tax ON tax.term_id = term.term_id
        LEFT JOIN {$db}term_relationships rel ON rel.term_taxonomy_id = tax.term_taxonomy_id
        WHERE tax.taxonomy = 'product_cat'
      ";
    $res = $wpdb->get_results($sql);
    return $res;
  }

  function get_atumProduct_rows($aFieldNames) {
    global $wpdb;
    $db = $wpdb->prefix;
    $cols = '';
    foreach ( $aFieldNames as $name ) {
      $secureName = $this->secure_dynamic_sql($name);
      $col = ", $secureName";
      $cols = $cols . $col;
    }
    $db = $wpdb->prefix;
    $sql = "
        SELECT  product_id ID,
                'NA' post_title,
                0 post_parent,
                update_date post_modified
                $cols
        FROM {$db}atum_product_data p
        ORDER BY product_id ASC;
      ";
    $res = $wpdb->get_results($sql);
    return $res;
  }

  function field_names_to_user_meta_joins($aNames) {
    global $wpdb;
    $res = '';
    $idx = 0;
    if ( ! $aNames ) return $res;
    foreach ( $aNames as $name ) {
      $secureName = $this->secure_dynamic_sql($name);
      $join = " LEFT JOIN $wpdb->usermeta pm$idx ON (p.ID = pm$idx.user_id AND pm$idx.meta_key = '$secureName') ";
      $res = $res . $join;
      $idx = $idx + 1;
    }
    return $res;
  }

  function field_names_to_meta_joins($aNames, $aNative) {
    global $wpdb;
    global $prfi_subselects;
    $res = '';
    if ( $prfi_subselects )
      return $res;
    $idx = 0;
    $prefix = "stocktend_";
    if ( $aNative ) 
      $prefix = "";
    if ( ! $aNames ) return $res;
    foreach ( $aNames as $name ) {
      $secureName = $this->secure_dynamic_sql($name);
      $join = " LEFT JOIN $wpdb->postmeta pm$idx ON (p.id = pm$idx.post_id AND pm$idx.meta_key = '$prefix$secureName') ";
      $res = $res . $join;
      $idx = $idx + 1;
      if ( $idx >= 58 ) // joins are limited to 61, rest are handled with subselects
        break;
    }
    return $res;
  }

  function field_names_to_cols($aNames, $aNative=false, $force_subselects=false) {
    global $wpdb;
    global $prfi_subselects;
    $subselects = $prfi_subselects || $force_subselects;
    $res = '';
    $idx = 0;
    $prefix = "stocktend_";
    if ( $aNative ) 
      $prefix = "";
    if ( ! $aNames ) return $res;
    foreach ( $aNames as $name ) {
      $secureName = $this->secure_dynamic_sql($name);
      $col = ", pm$idx.meta_value `$secureName`";
      if ( $subselects || ($idx >= 58) ) // joins are limited to 61
        $col = ", (SELECT pm$idx.meta_value FROM $wpdb->postmeta pm$idx WHERE p.id = pm$idx.post_id AND pm$idx.meta_key = '$prefix$secureName' LIMIT 1) `$secureName`";
      $res = $res . $col;
      $idx = $idx + 1;
    }
    return $res;
  }

  function rows_to_objects($aRows, $aDatatype) {
    $res = array();
    foreach ($aRows as $row) {
      $obj = $this->row_to_object($row, $aDatatype);
      $obj = $this->prepare_response_for_collection($obj);
      array_push($res, $obj);
    }
    return $res;
  }

  function row_to_object($aRow, $aDatatype, &$field_names=null) {
    $obj = array();
    foreach ( $aRow as $prop => $value ) {
      if ( ($prop === "ID" ) || ($prop === "post_parent") || ($prop === "post_modified") || ($prop === "post_title") ) continue;
      if ( $field_names && ! in_array($prop, $field_names, true) ) continue;
      if ( $value && ($value[0] === "{") ) {
        $json = json_decode($value);
        if ( is_object($json) ) {
          $value = $json;
        } else {
          $value = stripslashes($value);
        }
      } else {
        $value = stripslashes($value);
        $value = str_replace('&lt;', '<', $value); // '<' is replaced with '&lt;' by sanitize_textarea_field which we use when inserting/updating
      }
      $obj[$prop] = $value;
    }
    if ( isset($aRow->ID) )
      $obj['id'] = (int)$aRow->ID;
    if ( isset($aRow->post_parent) )
      $obj['parentId'] = (int)$aRow->post_parent;
    if ( isset($aRow->post_modified) )
      $obj['post_modified'] = $aRow->post_modified;
    $name = '';
    if ( $aDatatype === "products" ) {
      $name = $this->prod_row_to_unique_name($aRow, $obj);
      $obj['uniqueName'] = $name;
    } else if ( $aDatatype === "atumPO" ) {
      $notes = $aRow->post_content;
      $obj['notes'] = $notes;
    } else {
      if ( isset($aRow->post_title) ) {
        if ( (! isset($obj['name'])) || (! $obj['name']) ) {
          $name = stripslashes($aRow->post_title);
          $obj['name'] = $name;
        }
      }
    }
    $obj['wp_post_title'] = $name;
    $obj['_datatype'] = $aDatatype;
    return $obj;
  }

  function prod_row_to_unique_name($aRow, $aObj) {
    $res = stripslashes($aRow->post_title);
    if ( isset($aObj['_sku']) && $aObj['_sku'] )
      $differentiator = $aObj['_sku'];
    else
      $differentiator = '#' . $aObj['id'];
    $res .= " ($differentiator)";
    return $res;
  }

  function url_to_meta_query_args($aUrl) {
    $res = array();
    $datatype = $this->url_to_datatype($aUrl);
    if ( $datatype ) {
      $res[] = array(
        'key' => 'stocktend__datatype',
        'value' => $datatype,
      );
    }
    $propName = $this->url_to_prop_name($aUrl);
    $propVal = $this->url_to_prop_val($aUrl);
    $compare = "==";
    if ( $propVal[0] === "{" ) {
      $json = json_decode($propVal);
      if ( is_object($json) ) {
        $propVal = $json->value;
        $compare = $json->compare;
      }
    }
    if ( $propName ) {
      $res[] = array(
        'key' => 'stocktend_' . $propName,
        'value' => $propVal,
        'compare' => $compare,
      );
    }
    return $res;
  }

	function find_objects($aReq) {
    global $wpdb;
    $res = array();
    $db = $wpdb->prefix;
    $url = $GLOBALS["stApiUrl"];
    $searchText = $this->url_and_parm_name_to_value($url, "searchText");
    if ( ! $searchText )
      return $res;
    $searchText = $wpdb->esc_like($searchText);
    $sql = 
      "
        SELECT  
          p.ID,
          p.post_title,
          p.post_parent,
          p.post_modified,
          pm.meta_key `rawFieldName`,
          pm.meta_value `rawValue`,
          datatype.meta_value `theDatatype`,
          p.post_type `postType`
        FROM    
          {$db}postmeta pm
        JOIN 
          {$db}posts p ON 
            pm.post_id = p.ID AND
            p.post_type IN ('stocktend_object', 'product', 'product_variation', 'shop_order', 'shop_order_refund') AND
            p.post_status IN ('publish', 'private')
        LEFT JOIN 
          {$db}postmeta datatype ON
            pm.post_id = datatype.post_id AND
            datatype.meta_key = 'stocktend__datatype'
        WHERE   
          pm.meta_value like '%" . $searchText . "%' AND
          pm.meta_key NOT LIKE 'stocktend\_\_%' AND
          ((pm.meta_value NOT LIKE '{%}') OR (pm.meta_value like '%\"keyval\":\"%" . $searchText . "%\"%')) AND
          pm.meta_key NOT IN ('stocktend_entityName','stocktend_documentType') AND
          (
            (p.post_type <> 'stocktend_object') OR
            (datatype.meta_value NOT IN ('Configuration','UserFilter','NextNumber','Facet','Template'))
          )
        GROUP BY p.ID
        ORDER BY p.ID ASC
        LIMIT 20;
      ";  
    $res = $wpdb->get_results($sql);
    return $res;
  }

	function get_objects_wc($aReq) {
    $url = $GLOBALS["stApiUrl"];
    $datatype = $this->url_to_datatype($url);
    if ( $datatype === "products" )
      return $this->get_products_wc($aReq);
    else if ( $datatype === "users" )
      return $this->get_users_wc($aReq);
    else if ( $datatype === "orders" )
      return $this->get_orders_wc($aReq);
    else if ( $datatype === "order_items" )
      return $this->get_order_items_wc($aReq);
    else if ( $datatype === "tax_rates" )
      return $this->get_tax_rates_wc($aReq);
    else if ( $datatype === "shipping_methods" )
      return $this->get_shipping_methods_wc($aReq);
    else if ( $datatype === "metakey" )
      return $this->get_metakeys_wc($aReq);
    else if ( $datatype === "canon" )
      return $this->get_canons_wc($aReq);
    else if ( $datatype === "attribute" )
      return $this->get_attributes_wc($aReq);
    else if ( $datatype === "session" )
      return $this->get_sessions_wc($aReq);
    else if ( $datatype === "atumSupplier" )
      return $this->get_atumSuppliers_wc($aReq);
    else if ( $datatype === "atumProduct" )
      return $this->get_atumProducts_wc($aReq);
    else if ( $datatype === "atumPO" )
      return $this->get_atumPOs_wc($aReq);
    else if ( $datatype === "atumPOLine" )
      return $this->get_atumPOLines_wc($aReq);
    else if ( $datatype === "category" )
      return $this->get_categories_wc($aReq);
    else if ( $datatype === "productCategory" )
      return $this->get_productCategories_wc($aReq);
  }

	function get_products_wc($aReq) {
    $args = array(
      'numberposts' => -1,
      'post_status' => 'published',
    );
    $prod_rows = $this->get_prod_rows(null);
    $objects = $this->prod_rows_to_objects($prod_rows, $aReq);
		$response = rest_ensure_response($objects);
    return $response;
  }

	function get_atumPOs_wc($aReq) {
    $url = $GLOBALS["stApiUrl"];
    $fields = $this->url_to_fields($url);
    $post_rows = $this->get_atumPO_post_rows($fields);
    $objects = $this->rows_to_objects($post_rows, 'atumPO');
		$response = rest_ensure_response($objects);
    return $response;
  }

	function get_atumSuppliers_wc($aReq) {
    $url = $GLOBALS["stApiUrl"];
    $fields = $this->url_to_fields($url);
    $post_rows = $this->get_atumSupplier_post_rows($fields);
    $objects = $this->rows_to_objects($post_rows, 'atumSupplier');
		$response = rest_ensure_response($objects);
    return $response;
  }

	function get_categories_wc($aReq) {
    $url = $GLOBALS["stApiUrl"];
    $fields = $this->url_to_fields($url);
    $post_rows = $this->get_category_rows($fields);
    $objects = $this->rows_to_objects($post_rows, 'category');
		$response = rest_ensure_response($objects);
    return $response;
  }

	function get_productCategories_wc($aReq) {
    $url = $GLOBALS["stApiUrl"];
    $fields = $this->url_to_fields($url);
    $post_rows = $this->get_productCategory_rows($fields);
    $objects = $this->rows_to_objects($post_rows, 'productCategory');
		$response = rest_ensure_response($objects);
    return $response;
  }

	function get_atumProducts_wc($aReq) {
    $url = $GLOBALS["stApiUrl"];
    $fields = $this->url_to_fields($url);
    $post_rows = $this->get_atumProduct_rows($fields);
    $objects = $this->rows_to_objects($post_rows, 'atumProduct');
		$response = rest_ensure_response($objects);
    return $response;
  }

	function get_attributes_wc($aReq) {
    $objects = $this->get_attribute_objects();
		$response = rest_ensure_response($objects);
    return $response;
  }

	function get_metakeys_wc($aReq) {
    $mk_rows = $this->get_metakey_rows();
    $objects = $this->metakey_rows_to_objects($mk_rows, $aReq);
		$response = rest_ensure_response($objects);
    return $response;
  }

	function get_canons_wc($aReq) {
    $rows = $this->get_canon_rows();
    $objects = $this->canon_rows_to_objects($rows, $aReq);
		$response = rest_ensure_response($objects);
    return $response;
  }

	function get_sessions_wc($aReq) {
    $objects = array();
    $sess = array();
    $sess["id"] = 1;
    $user = wp_get_current_user();
    $sess["user"] = isset($user->data->user_login) ? $user->data->user_login : '';
    $sess["userHasAdminRights"] = current_user_can('administrator');
    $sess["decimalSeparator"] = get_option('woocommerce_price_decimal_sep');
    $sess["thousandSeparator"] = get_option('woocommerce_price_thousand_sep');
    $sess["atumIsActive"] = prfi_is_plugin_active('atum-stock-manager-for-woocommerce/atum-stock-manager-for-woocommerce.php');
    $sess["databaseIsOptimized"] = prfi_is_database_optimized();
    $sess["seatsInUseCount"] = prfi_get_seats_in_use_count();
    $sess["wcPricesIncludeTax"] = prfi_get_woocommerce_prices_include_tax();
    array_push($objects, $sess);
		$response = rest_ensure_response($objects);
    return $response;
  }

	function get_users_wc($aReq) {
    $user_rows = $this->get_user_rows();
    $objects = $this->user_rows_to_objects($user_rows, $aReq);
		$response = rest_ensure_response($objects);
    return $response;
  }

	function get_orders_wc($aReq) {
    //tc(300);
    $ord_rows = $this->get_ord_rows();
    //tc(301);
    $objects = $this->ord_rows_to_objects($ord_rows, $aReq);
    //tc(302);
		$response = rest_ensure_response($objects);
    return $response;
  }

	function get_atumPOLines_wc($aReq) {
    $rows = $this->get_atum_order_item_rows(null);
    $objects = $this->atum_order_item_rows_to_objects($rows, $aReq);
		$response = rest_ensure_response($objects);
    return $response;
  }

	function get_tax_rates_wc($aReq) {
    $rows = $this->get_tax_rate_rows(null);
    $objects = $this->tax_rate_rows_to_objects($rows, $aReq);
		$response = rest_ensure_response($objects);
    return $response;
  }

	function get_shipping_methods_wc($aReq) {
    $rows = $this->get_shipping_method_rows(null);
    $objects = $this->shipping_method_rows_to_objects($rows, $aReq);
		$response = rest_ensure_response($objects);
    return $response;
  }

	function get_order_items_wc($aReq) {
    $rows = $this->get_order_item_rows(null);
    $objects = $this->order_item_rows_to_objects($rows, $aReq);
		$response = rest_ensure_response($objects);
    return $response;
  }

  function prod_rows_to_objects($aRows) {
    $res = array();
    foreach ($aRows as $row) {
      $obj = $this->prod_row_to_object($row);
			$obj = $this->prepare_response_for_collection($obj);
			array_push($res, $obj);
    }
    return $res;
  }

  function get_attribute_objects() {
    global $wpdb;
    $res = array();
    $db = $wpdb->prefix;
    $query = "
        SELECT  p.ID,
                pm.meta_value _product_attributes
        FROM    {$db}posts p
                JOIN {$db}postmeta pm ON (p.ID = pm.post_id AND pm.meta_key = '_product_attributes')
        WHERE   p.post_type IN ('product', 'product_variation')
                AND p.post_status IN ('publish', 'private')
    ";
    $rows = $wpdb->get_results($query);
    $allAttrs = array();
    foreach ($rows as $row) {
      $attrs = unserialize($row->_product_attributes);
      foreach ( $attrs as $prop => $attr ) {
        if ( $attr["is_taxonomy"] === "1" ) continue;
        if ( array_search($prop, $allAttrs, true) === FALSE )
          $allAttrs[] = $prop;
      }
    }
    foreach ($allAttrs as $attr) {
      $obj = array();
      $obj["name"] = $attr;
      $res[] = $obj;
    }
    return $res;
  }

  function canon_rows_to_objects($aRows) {
    $res = array();
    foreach ($aRows as $row) {
      $obj = $this->canon_row_to_object($row);
			$obj = $this->prepare_response_for_collection($obj);
			array_push($res, $obj);
    }
    return $res;
  }

  function metakey_rows_to_objects($aRows) {
    $res = array();
    foreach ($aRows as $row) {
      $obj = $this->metakey_row_to_object($row);
			$obj = $this->prepare_response_for_collection($obj);
			array_push($res, $obj);
    }
    return $res;
  }

  function user_rows_to_objects($aRows) {
    $res = array();
    foreach ($aRows as $row) {
      $obj = $this->user_row_to_object($row);
			$obj = $this->prepare_response_for_collection($obj);
			array_push($res, $obj);
    }
    return $res;
  }

  function ord_rows_to_objects($aRows) {
    $res = array();
    foreach ($aRows as $row) {
      $obj = $this->ord_row_to_object($row);
			$obj = $this->prepare_response_for_collection($obj);
			array_push($res, $obj);
    }
    return $res;
  }

  function atum_order_item_rows_to_objects($aRows) {
    $res = array();
    foreach ($aRows as $row) {
      $obj = $this->atum_order_item_row_to_object($row);
			$obj = $this->prepare_response_for_collection($obj);
			array_push($res, $obj);
    }
    return $res;
  }

  function shipping_method_rows_to_objects($aRows) {
    $res = array();
    foreach ($aRows as $row) {
      $obj = $this->shipping_method_row_to_object($row);
			$obj = $this->prepare_response_for_collection($obj);
			array_push($res, $obj);
    }
    return $res;
  }

  function tax_rate_rows_to_objects($aRows) {
    $res = array();
    foreach ($aRows as $row) {
      $obj = $this->tax_rate_row_to_object($row);
			$obj = $this->prepare_response_for_collection($obj);
			array_push($res, $obj);
    }
    return $res;
  }

  function order_item_rows_to_objects($aRows) {
    $res = array();
    foreach ($aRows as $row) {
      $obj = $this->order_item_row_to_object($row);
			$obj = $this->prepare_response_for_collection($obj);
			array_push($res, $obj);
    }
    return $res;
  }

  function prod_row_to_object($aRow) {
    $res = $this->row_to_object($aRow, "products");
    $res = $this->convert_attributes_to_properties($res);
    return $res;
  }

  function atumSupplier_row_to_object($aRow) {
    $res = $this->row_to_object($aRow, "atumSupplier");
    $res = $this->convert_attributes_to_properties($res);
    return $res;
  }

  function convert_attributes_to_properties($aProd) {
    $res = $aProd;
    if ( ! isset($res["_product_attributes"]) ) return $res;
    $pa = $res["_product_attributes"];
    if ( ! $pa ) return $res;
    $attrs = unserialize($pa);
    $url = $GLOBALS["stApiUrl"];
    $fieldNames = $this->url_to_fields($url);
    foreach ( $attrs as $prop => $attrObj ) {
      if ( array_search("WC Product Attribute." . $prop, $fieldNames, true) === FALSE ) continue;
      if ( isset($attrObj["is_taxonomy"]) && ($attrObj["is_taxonomy"] === 1) )
        $res[$prop] = $this->attr_name_to_taxonomy_values($attrObj["name"], $aProd['id']);
      else if ( isset($attrObj["value"]) )
        $res[$prop] = $attrObj["value"];
    }
    unset($res["_product_attributes"]);
    return $res;
  } 

  function attr_name_to_taxonomy_values($name, $id) {
    global $wpdb;
    $db = $wpdb->prefix;
    $sql = $wpdb->prepare(
      "
        SELECT 
          GROUP_CONCAT(term.name SEPARATOR ',') as value 
        FROM 
          {$db}term_relationships rel 
        LEFT JOIN 
          {$db}term_taxonomy tax ON rel.term_taxonomy_id = tax.term_taxonomy_id AND tax.taxonomy = %s
        LEFT JOIN 
          {$db}terms term ON tax.term_id = term.term_id
        WHERE
          rel.object_id = %d
        GROUP BY 
          rel.object_id;
      ",
      $name,
      $id
    );
    $res = $wpdb->get_results($sql);
    if ( count($res) <= 0 )
      return '';
    return $res[0]->value;
  }

  function canon_row_to_object($aRow) {
    $obj = array();
    foreach ( $aRow as $prop => $value ) {
      $value = stripslashes($value);
      $obj[$prop] = $value;
    }
    $name = stripslashes($aRow->name);
    $obj['wp_post_title'] = $name;
    $obj['name'] = $name;
    $obj['_datatype'] = "canon";
    return $obj;
  }

  function metakey_row_to_object($aRow) {
    $obj = array();
    foreach ( $aRow as $prop => $value ) {
      $value = stripslashes($value);
      if ( ($prop === "meta_id" ) ) continue;
      $obj[$prop] = $value;
    }
    $obj['id'] = (int)$aRow->meta_id;
    $name = stripslashes($aRow->meta_key);
    $obj['wp_post_title'] = $name;
    $obj['name'] = $name;
    $obj['_datatype'] = "metakey";
    return $obj;
  }

  function user_row_to_object($aRow) {
    $obj = $this->row_to_object($aRow, "users");
    return $obj;
  }

  function ord_row_to_object($aRow) {
    $obj = $this->row_to_object($aRow, "orders");
    $ts = (int)$obj['_date_completed'];
    if ( $ts === 0 ) {
      $obj['_date_completed'] = '';
    } else
      $obj['_date_completed'] = date('Y-m-d H:i:s', $ts);
    return $obj;
  }

  function shipping_method_row_to_object($aRow) {
    return $this->row_to_object($aRow, "shipping_methods");
  }

  function tax_rate_row_to_object($aRow) {
    return $this->row_to_object($aRow, "tax_rates");
  }

  function order_item_row_to_object($aRow) {
    $obj = $this->row_to_object($aRow, "order_items");
    $ts = (int)$obj['_date_completed'];
    if ( $ts === 0 ) {
      $obj['_date_completed'] = '';
    } else
      $obj['_date_completed'] = date('Y-m-d H:i:s', $ts);
    return $obj;
  }

  function atum_order_item_row_to_object($aRow) {
    return $this->row_to_object($aRow, "atumPOLine");
  }

  function get_product_metakey_fields() {
    $url = $GLOBALS["stApiUrl"];
    $fields = $this->url_to_fields($url);
    $res = array();
    foreach ( $fields as $field ) {
      $parts = explode(".", $field);
      if ( sizeof($parts) < 2 ) continue;
      $realm = $parts[0];
      if ( $realm !== "WC Product" ) continue;
      $res[] = $parts[1];
    }
    return $res;
  }

  function get_user_metakey_fields() {
    $url = $GLOBALS["stApiUrl"];
    $fields = $this->url_to_fields($url);
    $res = array();
    foreach ( $fields as $field ) {
      $parts = explode(".", $field);
      if ( sizeof($parts) < 2 ) continue;
      $realm = $parts[0];
      if ( $realm !== "Meta" ) continue;
      $res[] = $parts[1];
    }
    return $res;
  }

  function get_order_metakey_fields() {
    $url = $GLOBALS["stApiUrl"];
    $fields = $this->url_to_fields($url);
    $res = array();
    foreach ( $fields as $field ) {
      $parts = explode(".", $field);
      if ( sizeof($parts) < 2 ) continue;
      $realm = $parts[0];
      if ( $realm !== "Meta" ) continue;
      $res[] = $parts[1];
    }
    return $res;
  }

  function field_names_to_product_meta_joins($aNames) {
    global $wpdb;
    $res = '';
    $idx = 0;
    foreach ( $aNames as $name ) {
      $secureName = $this->secure_dynamic_sql($name);
      $join = " LEFT JOIN $wpdb->postmeta pm$idx ON (p.id = pm$idx.post_id  AND pm$idx.meta_key = '$secureName') ";
      $res = $res . $join;
      $idx = $idx + 1;
    }
    return $res;
  }

  function get_order_item_polylang_clauses() {
    global $wpdb;
    $res = array("cols" => "", "joins" => "");
    $limit_to_polylang_language = prfi_get_polylang_language_to_limit_to();
    if ( ! $limit_to_polylang_language )
      return $res;
    $db = $wpdb->prefix;
    $res["cols"] = ", tt.description polylang_language, sku.meta_value _sku ";
    $res["joins"] = "
      INNER JOIN {$db}term_relationships tr ON COALESCE(product_id.meta_value, variation_id.meta_value) = tr.object_id
      INNER JOIN {$db}term_taxonomy tt ON tr.term_taxonomy_id = tt.term_taxonomy_id and tt.taxonomy = 'language'
      INNER JOIN {$db}postmeta sku ON COALESCE(product_id.meta_value, variation_id.meta_value) = sku.post_id AND sku.meta_key = '_sku'
    ";
    return $res;
  }

  function get_polylang_clauses($options) {
    global $wpdb;
    $res = array("cols" => "", "joins" => "");
    $ignore_polylang = $options && isset($options["ignore_polylang"]) ? true : false;
    if ( $ignore_polylang )
      return $res;
    $limit_to_polylang_language = prfi_get_polylang_language_to_limit_to();
    if ( ! $limit_to_polylang_language )
      return $res;
    $db = $wpdb->prefix;
    $res["cols"] = ", tt.description polylang_language ";
    $res["joins"] = "
      INNER JOIN {$db}term_relationships tr ON p.ID = tr.object_id
      INNER JOIN {$db}term_taxonomy tt ON tr.term_taxonomy_id = tt.term_taxonomy_id and tt.taxonomy = 'language'
    ";
    return $res;
  }

  function get_prod_rows($options) {
    global $wpdb;
    $polylang_clauses = $this->get_polylang_clauses($options);
    $polylang_cols = $polylang_clauses["cols"];
    $polylang_joins = $polylang_clauses["joins"];
    $fieldNames = $this->get_product_metakey_fields();
    $cols = $this->field_names_to_cols($fieldNames); // Note: secured within field_names_to_cols
    $joins = $this->field_names_to_product_meta_joins($fieldNames); // Note: secured within field_names_to_product_meta_joins
    $db = $wpdb->prefix;
    $modifiedAfter = $this->get_modified_after();
    $includeDeleted = '';
    if ( $modifiedAfter )
      $includeDeleted = 'includeDeleted';
    else
      $modifiedAfter = '1970-01-01 00:00:01';
    $sql = $wpdb->prepare("
        SELECT  p.ID,
                p.post_title,
                p.post_parent,
                p.post_modified,
                CONVERT(pm.meta_value, SIGNED INTEGER) _stock,
                sku.meta_value _sku,
                COALESCE(low_stock_amount.meta_value, parent_low_stock_amount.meta_value) _low_stock_amount,
                regular_price.meta_value _regular_price,
                IF(tax_class.meta_value <> 'parent', tax_class.meta_value, parent_tax_class.meta_value) _tax_class,
                tax_status.meta_value _tax_status,
                pa.meta_value _product_attributes,
                IF ( p.post_status IN ('publish', 'private'), '', 'DELETE' ) __incrementalDelete
                $cols
                $polylang_cols
        FROM    {$db}posts p
                LEFT JOIN {$db}postmeta pm ON (p.ID = pm.post_id AND pm.meta_key = '_stock')
                LEFT JOIN {$db}postmeta sku ON (p.ID = sku.post_id AND sku.meta_key = '_sku')
                LEFT JOIN {$db}postmeta tax_status ON 
                  (p.ID = tax_status.post_id AND tax_status.meta_key = '_tax_status')
                LEFT JOIN {$db}postmeta tax_class ON 
                  (p.ID = tax_class.post_id AND tax_class.meta_key = '_tax_class')
                LEFT JOIN {$db}postmeta low_stock_amount ON 
                  (p.ID = low_stock_amount.post_id AND low_stock_amount.meta_key = '_low_stock_amount')
                LEFT JOIN {$db}postmeta regular_price ON 
                  (p.ID = regular_price.post_id AND regular_price.meta_key = '_regular_price')
                LEFT JOIN {$db}postmeta parent_low_stock_amount ON 
                  (p.post_parent = parent_low_stock_amount.post_id AND parent_low_stock_amount.meta_key = '_low_stock_amount')
                LEFT JOIN {$db}postmeta parent_tax_class ON 
                  (p.post_parent = parent_tax_class.post_id AND parent_tax_class.meta_key = '_tax_class')
                INNER JOIN {$db}postmeta manageStock ON (p.ID = manageStock.post_id AND manageStock.meta_key = '_manage_stock')
                LEFT JOIN {$db}postmeta pa ON (p.ID = pa.post_id AND pa.meta_key = '_product_attributes')
                $joins
                $polylang_joins
        WHERE   p.post_type IN ('product', 'product_variation')
                AND p.post_modified >= %s
                AND ((%s = 'includeDeleted') OR (p.post_status IN ('publish', 'private')))
                AND manageStock.meta_value <> 'no'
        " . $this->get_subset_condition() . "
        GROUP BY p.ID
        ORDER BY p.ID ASC;
        ",
      $modifiedAfter,
      $includeDeleted
    );
    $res = $wpdb->get_results($sql);
    if ( $polylang_cols ) {
      $res = prfi_remove_other_lang_products($res);
      if ( count($res) === 0 )
        $res = $this->get_prod_rows(array("ignore_polylang" => true));
    }
    return $res;
  }

  function get_metakey_rows() {
    global $wpdb;
    $db = $wpdb->prefix;
    $query = "
        SELECT  m.meta_id,
                m.meta_key
        FROM    {$db}postmeta m
                JOIN {$db}posts p ON (p.ID = m.post_id AND p.post_type like 'product%')
        GROUP BY m.meta_key
        ORDER BY m.meta_key ASC;
    ";
    $res = $wpdb->get_results($query);
    return $res;
  }

  function get_canon_rows() {
    $res = array();
    $this->add_canon_rows("specs", $res);
    $this->add_canon_rows("premium/specs", $res);
    return $res;
  }

  function add_canon_rows($path, &$res) {
    $fullPath = PRFI_WIDGET_PATH . "/src/" . $path;
    $files = scandir($fullPath);
    foreach ( $files as $file ) {
      if ( ! strpos($file, '.js') ) continue;
      $obj = new stdClass();
      $obj->path = $path . '/' . $file;
      $obj->blueprint = prfi_file_get_contents($fullPath . '/' . $file);
      $obj->name = $file;
      $res[] = $obj;
    }
  }

  function get_user_rows() {
    global $wpdb;
    $db = $wpdb->prefix;
    $fieldNames = array(
      "billing_first_name",
      "billing_last_name",
      "billing_company",
      "billing_address_1",
      "billing_address_2",
      "billing_city",
      "billing_postcode",
      "billing_country",
      "billing_state",
      "billing_phone",
      "billing_email",
      "shipping_first_name",
      "shipping_last_name",
      "shipping_company",
      "shipping_address_1",
      "shipping_address_2",
      "shipping_city",
      "shipping_postcode",
      "shipping_country",
      "shipping_state",
      "first_name",
      "last_name"
    );
    $fieldNames = array_merge($fieldNames, $this->get_user_metakey_fields());
    $cols = $this->field_names_to_cols($fieldNames, true);
    $joins = $this->field_names_to_user_meta_joins($fieldNames);
    $modifiedAfter = $this->get_modified_after();
    $includeDeleted = '';
    if ( $modifiedAfter )
      $includeDeleted = 'includeDeleted';
    else
      $modifiedAfter = '1970-01-01 00:00:01';
    $oldest_date = $modifiedAfter;
    $sql = $wpdb->prepare("
        SELECT  
          p.ID,
          p.user_login,
          p.user_nicename,
          p.user_email,
          p.user_url,
          p.user_status,
          p.display_name,
          cap.meta_value `wp_capabilities`
          $cols
        FROM    
          {$db}users p
          LEFT JOIN {$db}usermeta cap ON (p.ID = cap.user_id AND cap.meta_key = '{$db}capabilities')
          $joins
        WHERE   
          p.user_registered >= %s
        GROUP BY p.ID
        ORDER BY p.ID ASC;
      ",
      $oldest_date
    );
    $wpdb->query($sql);
    $res = $wpdb->last_result;
    return $res;
  }

  function get_ord_rows() {
    global $wpdb;
    $db = $wpdb->prefix;
    $fieldNames = array(
      "_billing_first_name",
      "_billing_last_name",
      "_billing_company",
      "_billing_address_1",
      "_billing_address_2",
      "_billing_city",
      "_billing_state",
      "_billing_postcode",
      "_billing_country",
      "_billing_email",
      "_billing_phone",
      "_shipping_first_name",
      "_shipping_last_name",
      "_shipping_company",
      "_shipping_address_1",
      "_shipping_address_2",
      "_shipping_city",
      "_shipping_state",
      "_shipping_postcode",
      "_shipping_country",
      "_order_currency",
      "_payment_method_title",
      "_cart_discount",
      "_cart_discount_tax",
      "_order_shipping",
      "_order_shipping_tax",
      "_order_total",
      "_order_tax",
      "_customer_user",
      "_wc_deposits_deposit_amount",
      "_wc_deposits_deposit_paid",
      "_wc_deposits_second_payment",
      "_wc_deposits_second_payment_paid",
      "_date_completed"
    );
    $fieldNames = array_merge($fieldNames, $this->get_order_metakey_fields());
    $cols = $this->field_names_to_cols($fieldNames, true);
    $joins = $this->field_names_to_meta_joins($fieldNames, true);
    $modifiedAfter = $this->get_modified_after();
    $includeDeleted = '';
    if ( $modifiedAfter )
      $includeDeleted = 'includeDeleted';
    else
      $modifiedAfter = '1970-01-01 00:00:01';
    $oldest_date = $modifiedAfter;
    if ( ! $oldest_date ) 
      $oldest_date = $this->get_two_years_ago();
    $sql = $wpdb->prepare("
        SELECT  p.ID,
                p.ID order_id,
                p.post_title,
                p.post_parent,
                p.post_modified,
                p.post_date order_date,
                ( SELECT SUM(CAST(oim.meta_value AS DECIMAL(12,2)))
                  FROM 
                    {$db}woocommerce_order_items AS oi 
                  JOIN
                    {$db}woocommerce_order_itemmeta AS oim ON oi.order_item_id = oim.order_item_id AND oim.meta_key = '_line_total'
                  WHERE oi.order_id = p.ID AND oi.order_item_type = 'fee') AS fees_total,
                ( SELECT SUM(CAST(oim.meta_value AS DECIMAL(12,2)))
                  FROM 
                    {$db}woocommerce_order_items AS oi 
                  JOIN
                    {$db}woocommerce_order_itemmeta AS oim ON oi.order_item_id = oim.order_item_id AND oim.meta_key = '_line_tax'
                  WHERE oi.order_id = p.ID AND oi.order_item_type = 'fee') AS fees_tax,
                p.post_status status,
                IF ( p.post_status IN ('wc-failed', 'wc-cancelled', 'wc-refunded', 'trash', 'auto-draft'), 'DELETE', '' ) __incrementalDelete
                $cols
        FROM    {$db}posts p
                $joins
        WHERE   p.post_type IN ('shop_order', 'shop_order_refund')
                AND p.post_modified >= %s
                AND ((%s = 'includeDeleted') OR (p.post_status NOT IN ('wc-failed', 'wc-cancelled', 'wc-refunded', 'trash', 'auto-draft')))
        " . $this->get_subset_condition() . "
        GROUP BY p.ID
        ORDER BY p.ID ASC;
      ",
      $oldest_date,
      $includeDeleted
    );
    //$res = $wpdb->get_results($sql);
    $wpdb->query($sql);
    $res = $wpdb->last_result;
    return $res;
  }

  function get_two_years_ago() {
    return date('Y-m-d', strtotime('-2 years', time()));
  }

  function get_atum_order_item_rows($aId) {
    global $wpdb;
    $db = $wpdb->prefix;
    $queryPart1 = "
        SELECT  oi.order_item_id ID,
                0 post_parent,
                oi.order_id order_id,
                oi.order_item_name order_item_name,
                oi.order_item_type order_item_type,
                product_id.meta_value _product_id,
                variation_id.meta_value _variation_id,
                qty.meta_value _qty,
                line_total.meta_value _line_total,
                line_tax.meta_value _line_tax,
                cost.meta_value _cost,
                total_tax.meta_value _total_tax,
                p.post_date order_date,
                p.post_status order_status,
                p.post_modified,
                p.post_title
        FROM    {$db}atum_order_items oi
                LEFT JOIN {$db}atum_order_itemmeta product_id ON (oi.order_item_id = product_id.order_item_id AND product_id.meta_key = '_product_id')
                LEFT JOIN {$db}atum_order_itemmeta variation_id ON 
                  (oi.order_item_id = variation_id.order_item_id AND variation_id.meta_key = '_variation_id')
                LEFT JOIN {$db}atum_order_itemmeta qty ON (oi.order_item_id = qty.order_item_id AND qty.meta_key = '_qty')
                LEFT JOIN {$db}atum_order_itemmeta line_total ON (oi.order_item_id = line_total.order_item_id AND line_total.meta_key = '_line_total')
                LEFT JOIN {$db}atum_order_itemmeta line_tax ON (oi.order_item_id = line_tax.order_item_id AND line_tax.meta_key = '_line_tax')
                LEFT JOIN {$db}atum_order_itemmeta cost ON (oi.order_item_id = cost.order_item_id AND cost.meta_key = '_cost')
                LEFT JOIN {$db}atum_order_itemmeta total_tax ON (oi.order_item_id = total_tax.order_item_id AND total_tax.meta_key = '_total_tax')
                LEFT JOIN {$db}posts p ON (oi.order_id = p.ID)
      ";
    $queryPart2 = "
        ORDER BY oi.order_item_id ASC;
    ";
    if ( $aId ) 
      $query = $wpdb->prepare($queryPart1 . " AND oi.order_item_id = %s" . $queryPart2, $aId);
    else
      $query = $queryPart1 . $queryPart2;
    $res = $wpdb->get_results($query);
    return $res;
  }

  function zone_to_location_codes_csv_str($zone) {
    $locations = prfi_val($zone, 'zone_locations');
    $res = '';
		foreach ( $locations as $location ) {
      if ( strlen($res) > 0 )
        $res .= ',';
      $res .= prfi_val($location, 'code');
    }
    return $res;
  }

  function get_shipping_method_rows($aId) {
    $zones = WC_Shipping_Zones::get_zones();
    $rows = array();
		foreach ( $zones as $zone ) {
      $zone_name = prfi_val($zone, 'zone_name');
      $shipping_methods = $zone['shipping_methods'];
		  foreach ( $shipping_methods as $shipping_method ) {
        $method_title = prfi_val($shipping_method, 'method_title');
        $title = prfi_val($shipping_method, 'title');
        $uniqueName = $zone_name . ': ' . $title;
        $location_codes = $this->zone_to_location_codes_csv_str($zone);
        $row = array(
          'uniqueName' => $uniqueName,
          'zone_id' => prfi_val($zone, 'zone_id'),
          'zone_name' => prfi_val($zone, $zone_name), 
          'zone_order' => prfi_val($zone, 'zone_order'),
          'location_codes' => $location_codes,
          'instance_id' => prfi_val($shipping_method, 'instance_id'),
          'shipping_id' => prfi_val($shipping_method, 'id'), 
          'method_title' => $method_title, 
          'title' => prfi_val($shipping_method, 'title'), 
          'tax_status' => prfi_val($shipping_method, 'tax_status'), 
          'cost' => prfi_val($shipping_method, 'cost'),
          'min_amount' => prfi_val($shipping_method, 'min_amount'),
        );
        $rows[] = $row;
      }
    }
    return $rows;
  }

  function get_tax_rate_rows($aId) {
    global $wpdb;
    $db = $wpdb->prefix;
    $query = "
        SELECT  tr.tax_rate_id ID,
                0 post_parent,
                tr.tax_rate_country,
                tr.tax_rate_state,
                tr.tax_rate,
                tr.tax_rate_name,
                tr.tax_rate_priority,
                tr.tax_rate_compound,
                tr.tax_rate_shipping,
                tr.tax_rate_order,
                tr.tax_rate_class,
                NULL post_modified,
                tax_rate_name post_title
        FROM    {$db}woocommerce_tax_rates tr
      ";
    $res = $wpdb->get_results($query);
    return $res;
  }

  function get_subset_condition() {
    $modifiedAfter = $this->get_modified_after();
    if ( $modifiedAfter )
      return ''; // If modifiedAfter is set, we get everything after that date, regardless of the subset specified, so that we can unstale the mold in the client
      // Remember that subsets are a performance tool, and that the client further limits the retrieved data to return the actual subset. So from the server
      // prespective, as long as it returns at least the subset (can be more than the subset), all is well.
    $url = $GLOBALS["stApiUrl"];
    $critStr = $this->url_and_parm_name_to_value($url, "subsetCriteria");
    if ( ! $critStr ) return '';
    $critObj = json_decode($critStr, FALSE);
    $res = ' AND ' . $this->crit_to_condition($critObj);
    return $res;
  }

  function crit_to_condition($aCrit) {
    global $wpdb;
    $res = '';
    $first = true;
    $idx = -1;
    foreach ( $aCrit as $name => $value ) {
      $idx++;
      if ( ! $first )
        $res .= " AND ";
      $first = false;
      if ( $name === 'or' ) {
        $res .= " (" . $this->or_clauses_to_condition($value) . ")";
        continue;
      }
      if ( $name === 'post_modified' ) 
        $useName = 'p.post_modified';
      else if ( ($name === 'order_status') || ($name === 'status') ) 
        $useName = 'p.post_status';
      else {
        $useName = "(SELECT meta_value FROM $wpdb->postmeta cond$idx WHERE p.id = cond$idx.post_id AND cond$idx.meta_key = 'stocktend_$name' LIMIT 1)";
      }
      if ( is_object($value) ) {
        if ( isset($value->compare) ) {
          $compare = $value->compare;
          if ( ! $compare )
            $compare = '==';
          if ( is_array($value->value) ) {
            $arrayStr = implode("','", $value->value);
            $res .= " $useName $compare ('$arrayStr') ";
          } else {
            $res .= " $useName $compare '$value->value'";
          }
        } else {
          $id_phrase = '"id":' . $value->id;
          $res .= " $useName LIKE '%$id_phrase%'";
        }
      } else if ( is_array($value) ) {
        $arrayStr = implode("','", $value);
        $res .= " $useName IN ('$arrayStr') ";
      } else {
        $res .= " $useName = '$value' ";
      }
    }
    return $res;
  }

  function or_clauses_to_condition($aOrClauses) {
    $res = '';
    $first = true;
		foreach ( $aOrClauses as $clause ) {
      if ( ! $first )
        $res .= " OR ";
      $first = false;
      $res .= $this->crit_to_condition($clause);
    }
    return $res;
  }

  function get_order_item_meta_clauses() {
    global $wpdb;
    $db = $wpdb->prefix;
    $res = array("cols" => "", "joins" => "");
    $subselects = prfi_conf_val('subselects');
    if ( $subselects !== 'Yes' ) {
      $res["cols"] = "
        product_id.meta_value _product_id,
        variation_id.meta_value _variation_id,
        qty.meta_value _qty,
        IF(oi.order_item_type = 'shipping', cost.meta_value, line_total.meta_value) _line_total,
        IF(oi.order_item_type = 'shipping', total_tax.meta_value, line_tax.meta_value) _line_tax,
        treated_as_made.meta_value treated_as_made,
        date_completed.meta_value _date_completed,
        method_id.meta_value method_id,
        instance_id.meta_value instance_id,
        refunded_item_id.meta_value _refunded_item_id,
      ";
      $res["joins"] = "
        LEFT JOIN {$db}woocommerce_order_itemmeta product_id ON (oi.order_item_id = product_id.order_item_id AND product_id.meta_key = '_product_id')
        LEFT JOIN {$db}woocommerce_order_itemmeta variation_id ON 
          (oi.order_item_id = variation_id.order_item_id AND variation_id.meta_key = '_variation_id')
        LEFT JOIN {$db}woocommerce_order_itemmeta qty ON (oi.order_item_id = qty.order_item_id AND qty.meta_key = '_qty')
        LEFT JOIN {$db}woocommerce_order_itemmeta line_total ON (oi.order_item_id = line_total.order_item_id AND line_total.meta_key = '_line_total')
        LEFT JOIN {$db}woocommerce_order_itemmeta cost ON (oi.order_item_id = cost.order_item_id AND cost.meta_key = 'cost')
        LEFT JOIN {$db}woocommerce_order_itemmeta total_tax ON (oi.order_item_id = total_tax.order_item_id AND total_tax.meta_key = 'total_tax')
        LEFT JOIN {$db}woocommerce_order_itemmeta line_tax ON (oi.order_item_id = line_tax.order_item_id AND line_tax.meta_key = '_line_tax')
        LEFT JOIN {$db}woocommerce_order_itemmeta refunded_item_id ON 
          (oi.order_item_id = refunded_item_id.order_item_id AND refunded_item_id.meta_key = '_refunded_item_id')
        LEFT JOIN {$db}woocommerce_order_itemmeta treated_as_made ON 
          (oi.order_item_id = treated_as_made.order_item_id AND treated_as_made.meta_key = 'prfi_treated_as_made')
        LEFT JOIN {$db}postmeta date_completed ON (oi.order_id = date_completed.post_id AND  date_completed.meta_key = '_date_completed')
        LEFT JOIN {$db}woocommerce_order_itemmeta method_id ON (oi.order_item_id = method_id.order_item_id AND method_id.meta_key = 'method_id')
        LEFT JOIN {$db}woocommerce_order_itemmeta instance_id ON (oi.order_item_id = instance_id.order_item_id AND instance_id.meta_key = 'instance_id')
      ";
    } else {
      $res["cols"] = "
        (SELECT product_id.meta_value FROM {$db}woocommerce_order_itemmeta product_id 
          WHERE oi.order_item_id = product_id.order_item_id AND product_id.meta_key = '_product_id' LIMIT 1) `_product_id`, 
        (SELECT variation_id.meta_value FROM {$db}woocommerce_order_itemmeta variation_id 
          WHERE oi.order_item_id = variation_id.order_item_id AND variation_id.meta_key = '_variation_id' LIMIT 1) `_variation_id`, 
        (SELECT qty.meta_value FROM {$db}woocommerce_order_itemmeta qty 
          WHERE oi.order_item_id = qty.order_item_id AND qty.meta_key = '_qty' LIMIT 1) `_qty`, 
        (IF(oi.order_item_type <> 'shipping',
          (SELECT line_total.meta_value FROM {$db}woocommerce_order_itemmeta line_total 
            WHERE oi.order_item_id = line_total.order_item_id AND line_total.meta_key = '_line_total' LIMIT 1),
          (SELECT cost.meta_value FROM {$db}woocommerce_order_itemmeta cost 
            WHERE oi.order_item_id = cost.order_item_id AND cost.meta_key = 'cost' LIMIT 1)
        )) `_line_total`, 
        (IF(oi.order_item_type <> 'shipping',
          (SELECT line_tax.meta_value FROM {$db}woocommerce_order_itemmeta line_tax 
            WHERE oi.order_item_id = line_tax.order_item_id AND line_tax.meta_key = '_line_tax' LIMIT 1), 
          (SELECT total_tax.meta_value FROM {$db}woocommerce_order_itemmeta total_tax 
            WHERE oi.order_item_id = total_tax.order_item_id AND total_tax.meta_key = 'total_tax' LIMIT 1)
        )) `_line_tax`, 
        (SELECT refunded_item_id.meta_value FROM {$db}woocommerce_order_itemmeta refunded_item_id 
          WHERE oi.order_item_id = refunded_item_id.order_item_id AND refunded_item_id.meta_key = '_refunded_item_id' LIMIT 1) `_refunded_item_id`, 
        (SELECT treated_as_made.meta_value FROM {$db}woocommerce_order_itemmeta treated_as_made 
          WHERE oi.order_item_id = treated_as_made.order_item_id AND treated_as_made.meta_key = 'prfi_treated_as_made' LIMIT 1) `treated_as_made`, 
        (SELECT date_completed.meta_value FROM {$db}postmeta date_completed 
          WHERE oi.order_id = date_completed.post_id AND date_completed.meta_key = '_date_completed' LIMIT 1) `_date_completed`, 
        (SELECT method_id.meta_value FROM {$db}woocommerce_order_itemmeta method_id 
          WHERE oi.order_item_id = method_id.order_item_id AND method_id.meta_key = 'method_id' LIMIT 1) `method_id`, 
        (SELECT instance_id.meta_value FROM {$db}woocommerce_order_itemmeta instance_id 
          WHERE oi.order_item_id = instance_id.order_item_id AND instance_id.meta_key = 'instance_id' LIMIT 1) `instance_id`, 
      ";
    }
    return $res;
  }

  function get_order_item_rows($aId) {
    global $wpdb;
    $db = $wpdb->prefix;
    $modifiedAfter = NULL;
    $includeDeleted = '';
    $oldest_date = NULL;
    $subsetCondition = '';
    if ( ! $aId ) {
      $modifiedAfter = $this->get_modified_after();
      if ( $modifiedAfter )
        $includeDeleted = 'includeDeleted';
      else
        $modifiedAfter = '1970-01-01 00:00:01';
      $oldest_date = $modifiedAfter;
      if ( ! $oldest_date ) 
        $oldest_date = $this->get_two_years_ago();
      $subsetCondition = $this->get_subset_condition(); // Includes AND
    }
    if ( ! $oldest_date )
      $oldest_date = '1970-01-01 00:00:01';
    $polylang_clauses = $this->get_order_item_polylang_clauses();
    $polylang_cols = $polylang_clauses["cols"];
    $polylang_joins = $polylang_clauses["joins"];
    $meta_clauses = $this->get_order_item_meta_clauses();
    $meta_cols = $meta_clauses["cols"];
    $meta_joins = $meta_clauses["joins"];
    $queryPart1 = "
        SELECT  oi.order_item_id ID,
                CONCAT('{\"datatype\": \"orders\", \"id\": ', oi.order_id, '}') AS theOrder,
                oi.order_id post_parent,
                oi.order_item_type order_item_type,
                $meta_cols
                p.post_date order_date,
                p.post_status order_status,
                p.post_modified,
                p.post_title,
                oi.order_item_name _name,
                IF ( p.post_status IN ('wc-failed', 'wc-cancelled', 'wc-refunded', 'trash', 'auto-draft'), 'DELETE', '' ) __incrementalDelete
                $polylang_cols
        FROM    {$db}woocommerce_order_items oi
                LEFT JOIN {$db}posts p ON (oi.order_id = p.ID)
                $meta_joins
                $polylang_joins
        WHERE   oi.order_item_type IN ('line_item', 'fee', 'shipping') AND
                p.post_modified >= %s AND
                ((%s = 'includeDeleted') OR (p.post_status NOT IN ('wc-failed', 'wc-cancelled', 'wc-refunded', 'trash', 'auto-draft'))) 
      " . $subsetCondition;
    $queryPart2 = "
        GROUP BY oi.order_item_id
        ORDER BY oi.order_item_id ASC;
    ";
    if ( $aId ) {
      $query = $wpdb->prepare(
        $queryPart1 . " AND oi.order_item_id = %s" . $queryPart2, 
        $oldest_date,
        $includeDeleted,
        $aId
      );
    } else {
      $query = $wpdb->prepare(
        $queryPart1 . $queryPart2,
        $oldest_date,
        $includeDeleted
      );
    }
    $res = $wpdb->get_results($query);
    if ( $polylang_cols ) 
      $res = $this->convert_other_lang_products($res);
    return $res;
  }

  function convert_other_lang_products($rows) {
    $map = prfi_get_main_language_sku_product_id_map();
    foreach ( $rows as $row ) {
      $sku = $row->_sku;
      $product_id = $map[$sku]; if ( ! $product_id ) continue;
      if ( ! empty($row->_product_id) )
        $row->_product_id = $product_id;
      if ( ! empty($row->_variation_id) )
        $row->_variation_id = $product_id;
    }
    return $rows;
  }

  function wp_posts_to_objects($aPosts, $aReq) {
		$objs = array();
		foreach ($aPosts as $post) {
      $obj = null;
      if ( $post ) {
        if ( property_exists($post, "post_title") ) {
			    $obj = $this->wp_post_to_object($post, $aReq);
          if ( isset($obj['_datatype']) && ($obj['_datatype'] === 'Bundle') ) {
            $obj = $this->decorate_bundle($obj);
          }
        } else {
          $obj = $this->id_to_order_item_obj($post->id);
        }
      }
      if ( $obj ) {
        if ( isset($obj['_stock']) ) {
          prfi_maybe_add_stock_clue(20, $obj['id'], '_stock', 'na', $obj['_stock']);
        }
			  $obj = $this->prepare_response_for_collection($obj);
      }
			array_push($objs, $obj);
		}
    return $objs;
  }

  function id_to_order_item_obj($aId) {
    $row = $this->id_to_order_item_row($aId); if ( ! $row ) return null;
    return $this->order_item_row_to_object($row, "order_items");
  }

  function id_to_order_item_row($aId) {
    $rows = $this->get_order_item_rows($aId);
    if ( sizeof($rows) === 0 ) return null;
    return $rows[0];
  }

  public function prfi_get_fields_for_response( $request ) {
    $schema     = $this->get_item_schema();
    $properties = isset( $schema['properties'] ) ? $schema['properties'] : array();

    $additional_fields = $this->get_additional_fields();

    foreach ( $additional_fields as $field_name => $field_options ) {
        // For back-compat, include any field with an empty schema
        // because it won't be present in $this->get_item_schema().
        if ( is_null( $field_options['schema'] ) ) {
            $properties[ $field_name ] = $field_options;
        }
    }

    // Exclude fields that specify a different context than the request context.
    $context = $request['context'];
    if ( $context ) {
        foreach ( $properties as $name => $options ) {
            if ( ! empty( $options['context'] ) && ! in_array( $context, $options['context'], true ) ) {
                unset( $properties[ $name ] );
            }
        }
    }

    $fields = array_keys( $properties );

    if ( ! isset( $request['_fields'] ) ) {
        return $fields;
    }
    $requested_fields = wp_parse_list( $request['_fields'] );
    if ( 0 === count( $requested_fields ) ) {
        return $fields;
    }
    // Trim off outside whitespace from the comma delimited list.
    $requested_fields = array_map( 'trim', $requested_fields );
    // Always persist 'id', because it can be needed for add_additional_fields_to_object().
    if ( in_array( 'id', $fields, true ) ) {
        $requested_fields[] = 'id';
    }
    // Return the list of all requested fields which appear in the schema.
    return array_reduce(
        $requested_fields,
        function( $response_fields, $field ) use ( $fields ) {
            if ( in_array( $field, $fields, true ) ) {
                $response_fields[] = $field;
                return $response_fields;
            }
            // Check for nested fields if $field is not a direct match.
            $nested_fields = explode( '.', $field );
            // A nested field is included so long as its top-level property
            // is present in the schema.
            if ( in_array( $nested_fields[0], $fields, true ) ) {
                $response_fields[] = $field;
            }
            return $response_fields;
        },
        array()
    );
  }

  function wp_post_to_object($aPost, $aReq) {
		$GLOBALS['post'] = $aPost;
		setup_postdata($aPost);
		$fields = $this->prfi_get_fields_for_response($aReq);
    $obj = array();
    $obj['id'] = (int)$aPost->ID;
    $obj['parentId'] = (int)$aPost->post_parent;
    $obj['wp_post_title'] = $aPost->post_name;
    $obj['post_modified'] = $aPost->post_modified;
		$metaValues = prfi_get_metadata('post', array($aPost->ID));
    foreach ($metaValues as $key => &$value) {
      if ( $key === "stocktend_id" ) continue;
      $value[0] = maybe_unserialize($value[0]); 
      if ( ! $value[0] ) continue;
      $value[0] = $this->replace_id_with_keyval($value[0]);
      if ( is_string($value[0]) )
        $value[0] = str_replace('&lt;', '<', $value[0]); // '<' is replaced with '&lt;' by sanitize_textarea_field
      $key = $this->strip_start($key, "stocktend_");
      $obj[$key] = $value[0];
    }
    if ( isset($aPost->uniqueName) )
      $obj['uniqueName'] = $aPost->uniqueName;
    if ( isset($aPost->order_id) )
      $obj['order_id'] = $aPost->order_id;
    return $obj;
  }

  function strip_start($str, $start) {
    $startLen = strlen($start);
    $check = substr($str, 0, $startLen);
    if ($check !== $start) return $str;
    return substr($str, $startLen, strlen($str) - $startLen);
  }

}

function prfi_get_metadata( $meta_type, $object_ids ) {
  global $wpdb;
  if ( ! $meta_type || ! $object_ids ) {
    return false;
  }
  $table = _get_meta_table( $meta_type );
  if ( ! $table ) {
    return false;
  }
  $column = sanitize_key( $meta_type . '_id' );
  if ( ! is_array( $object_ids ) ) {
    $object_ids = preg_replace( '|[^0-9,]|', '', $object_ids );
    $object_ids = explode( ',', $object_ids );
  }
  $object_ids = array_map( 'intval', $object_ids );
  $cache          = array();
  $id_list   = implode( ',', $object_ids );
  $id_column = ( 'user' === $meta_type ) ? 'umeta_id' : 'meta_id';
  $meta_list = $wpdb->get_results( "SELECT $column, meta_key, meta_value FROM $table WHERE $column IN ($id_list) ORDER BY $id_column ASC", ARRAY_A );
  if ( ! empty( $meta_list ) ) {
    foreach ( $meta_list as $metarow ) {
      $mpid = (int) $metarow[ $column ];
      $mkey = $metarow['meta_key'];
      $mval = $metarow['meta_value'];
      if ( ! isset( $cache[ $mpid ] ) || ! is_array( $cache[ $mpid ] ) ) {
        $cache[ $mpid ] = array();
      }
      if ( ! isset( $cache[ $mpid ][ $mkey ] ) || ! is_array( $cache[ $mpid ][ $mkey ] ) ) {
        $cache[ $mpid ][ $mkey ] = array();
      }
      $cache[ $mpid ][ $mkey ][] = $mval;
    }
  }
  return $cache[$object_ids[0]];
}

function prfi_add_product_costs() {
  prfi_product_id_to_cost_strs(get_the_ID(), $avg_unit_cost_str, $last_purchase_cost_str);
  prfi_add_product_field($avg_unit_cost_str, 'stAvgUnitCostField', 'Avg unit cost' . ' (' . get_woocommerce_currency_symbol() . ')');
  prfi_add_product_field($last_purchase_cost_str, 'stLastPurchCostField', 'Last purch price inc tax' . ' (' . get_woocommerce_currency_symbol() . ')');
}

function prfi_add_product_field($value_str, $field_id, $field_title) {
  $wrapper_class = 'stProductField';
  ?>
    <p class="form-field <?php echo esc_attr( $wrapper_class ) ?>">
      <label for="<?php echo esc_attr( $field_id ) ?>"><?php echo esc_html( $field_title ) ?></label>
      <span class="prfi-text" id="<?php echo esc_attr($field_id)?>" style="display: block;">
        <?php echo esc_attr($value_str) ?>
      </span>
    </p>
  <?php
}

function prfi_add_variation_costs($loop = NULL, $variation_data = array(), $variation = NULL) {
  prfi_product_id_to_cost_strs($variation->ID, $avg_unit_cost_str, $last_purchase_cost_str);
  prfi_add_variation_field($avg_unit_cost_str, 'stAvgUnitCostField', 'first', 'Avg unit cost' . ' (' . get_woocommerce_currency_symbol() . ')');
  prfi_add_variation_field($last_purchase_cost_str, 'stLastPurchCostField', 'last', 'Last purch price inc tax' . ' (' . get_woocommerce_currency_symbol() . ')');
}

function prfi_add_variation_field($value_str, $field_id, $row_pos, $field_title) {
  $wrapper_class = 'stProductField form-row form-row-' . $row_pos;
  ?>
    <p class="form-field <?php echo esc_attr( $wrapper_class ) ?>">
      <label for="<?php echo esc_attr( $field_id ) ?>"><?php echo esc_html( $field_title ) ?></label>
      <span class="prfi-text" id="<?php echo esc_attr($field_id)?>" style="padding-top: 12px; display: block; width: 100%">
        <?php echo esc_attr($value_str) ?>
      </span>
    </p>
  <?php
}

function prfi_product_id_to_cost_strs($aId, &$avg_unit_cost_str, &$last_purchase_cost_str) {
  global $wpdb;
  $sql = $wpdb->prepare("
      SELECT  
              avgUnitCost.meta_value avgUnitCost,
              lastPurchaseUnitCostIncTax.meta_value lastPurchaseUnitCostIncTax
      FROM    $wpdb->posts inventory
              INNER JOIN $wpdb->postmeta product_id
                ON (inventory.ID = product_id.post_id) AND ('stocktend_product_id' = product_id.meta_key)
              LEFT JOIN $wpdb->postmeta avgUnitCost
                ON (inventory.ID = avgUnitCost.post_id) AND ('stocktend_avgUnitCost' = avgUnitCost.meta_key)
              LEFT JOIN $wpdb->postmeta lastPurchaseUnitCostIncTax
                ON (inventory.ID = lastPurchaseUnitCostIncTax.post_id) AND ('stocktend_lastPurchaseUnitCostIncTax' = lastPurchaseUnitCostIncTax.meta_key)
      WHERE   product_id.meta_value = %s and
              inventory.post_status = 'publish'
    ",
    $aId
  );
  $res = $wpdb->get_results($sql); 
  if ( sizeof($res) === 0 ) {
    $avg_unit_cost_str = '0.00';
    $last_purchase_cost_str = '0.00';
  } else {
    $avg_unit_cost_str = prfi_cook_cost($res[0]->avgUnitCost);
    $last_purchase_cost_str = prfi_cook_cost($res[0]->lastPurchaseUnitCostIncTax);
  }
}

function prfi_cook_cost($aCost) {
  if ( ! is_string($aCost) ) 
    return prfi_cook_cost_numeric($aCost);
  $res = $aCost;
  if ( $res === prfi_unknown_number() )
    return 'Unknown';
  if ( ! $res )
    $res = '0';
  $res = prfi_add_decimals($res, 2);
  $res = wc_format_localized_price($res);
  return $res;
}

function prfi_cook_date($date) {
  $res = $date;
  if ( $res === '1971-01-01' ) {
    return '';
  }
  return $res;
}

function prfi_cook_cost_numeric($aCost) {
  $res = $aCost;
  if ( $res === -99999999999999 )
    return 0;
  if ( ! $res )
    $res = 0;
  return $res;
}

function prfi_unknown_number() {
  return '-99999999999999';
}

function prfi_add_decimals($aStr, $aPlaces) {
  if ( ! is_string($aStr) ) 
    $aStr = (string)$aStr;
  $res = $aStr;
  $pos = strpos($res, '.');
  if ( $pos === FALSE ) {
    $res .= '.';
    $pos = strpos($res, '.');
  }
  $decs = strlen($res) - $pos - 1;
  if ( $decs >= $aPlaces )
    return $res;
  $res = str_pad($res, strlen($res) + $aPlaces - $decs, '0');
  return $res;
}

function prfi_get_woocommerce_prices_include_tax() {
  global $wpdb;
  $db = $wpdb->prefix;
  $query = "
    SELECT
      option_value
    FROM
      ${db}options
    WHERE
      option_name = 'woocommerce_prices_include_tax'
    ";
  $rows = $wpdb->get_results($query, ARRAY_A); 
  if ( sizeof($rows) === 0 ) return 'no';
  $value = $rows[0]['option_value'];
  return $value;
}

function prfi_get_seats_in_use_count() {
  global $wpdb;
  $one_hour_ago = date('Y-m-d H:i:s', time() - 3600);
  $query = $wpdb->prepare("
      SELECT COUNT(*) FROM 
        (
          SELECT 
            p.post_excerpt seat_id
          FROM $wpdb->posts p
          WHERE   
            p.post_type = 'stocktend_tryst' AND 
            p.post_modified > %s
          GROUP BY 
            p.post_excerpt 
        ) seats
    ",
    $one_hour_ago
  );
  $knt = (int)$wpdb->get_var($query);
  return $knt;
}

function prfi_chk($aWPError) {
  if ( ! is_wp_error($aWPError) ) return;
  throw new Exception($aWPError->get_error_message() . " (" . $aWPError->get_error_code() . ")");
}

function prfi_log($aObj, $option=null) {
  $msg = print_r($aObj, TRUE);
  $url = NULL;
  if ( isset($GLOBALS["stApiUrl"]) )
    $url = $GLOBALS["stApiUrl"];
  //error_log($url . "\n", 3, '/tmp/wp-errors.log');
  $testNo = NULL;
/*
  if ( $url )
    $testNo = prfi_url_and_parm_name_to_value($url, "testNo");
  if ( $testNo )
    $msg = 'Test No ' . $testNo . ': ' . $msg;
*/
  if ( $option === 'nonl' )
    $msg = str_replace(array("\r", "\n"), '', $msg);
  error_log($msg . "\n", 3, '/tmp/wp-errors.log');
}

function prfi_be_slow() {
  return false;
}

function prfi_being_accessed_from_outside_wp() {
  return isset($_GET["testClient"]) || isset($_POST["testClient"]);
}

function prfi_is_running_tests() {
  //$url = NULL;
  //if ( isset($GLOBALS["stApiUrl"]) )
    //$url = $GLOBALS["stApiUrl"];
  //$testNo = NULL;
  //if ( $url )
    //$testNo = prfi_url_and_parm_name_to_value($url, "testNo");
  $testNo = isset($_GET["testNo"]) ? $_GET["testNo"] : false;
  $res = $testNo ? true : false;
  return $res;
}









if ( prfi_being_accessed_from_outside_wp() ) {
  function wp_validate_auth_cookie( $cookie = '', $scheme = '' ) {
    return "admin";
  }
}

/*
function tc($aNo) {
  global $gTimes;
  global $gLastTime;
  $secs = microtime(true);
  if ( ! $gLastTime )
    $gLastTime = $secs;
  if ( ! isset($gTimes[$aNo]) ) 
    $gTimes[$aNo] = new STTime($aNo, $secs);
  $t = $gTimes[$aNo];
  $t->total = $t->total + $secs - $gLastTime;
  $gLastTime = $secs;
}

function tcReport() {
  global $gTimes;
  prfi_log("");
  prfi_log("TIMES");
  prfi_log("-----");
  foreach ( $gTimes as $t ) {
    $s = round($t->total,3);
    prfi_log("$t->no\t$s");
  }
  prfi_log("=====");
  prfi_log("");
}

function st_call_stack() {
  ob_start();
      debug_print_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
      $res = ob_get_contents();
  ob_end_clean();
  prfi_log($res);
}

global $gLastK2IDatatype;
global $gLastK2IKeyval;
global $gLastK2IId;

global $gTimes;
global $gLastTime;
$gTimes = array();

class STTime {

  public $no;
  public $total;

  public function __construct($aNo, $aMt) {
    $this->total = 0;
    $this->no = $aNo;
  }

}
*/
