<?php
if(!class_exists('OP_Update'))
{
    class OP_Update{
        private $item_id = '';
        private $plugin_key = 'openpos';
        private $plugin_path;
        private $plugin_basename;
        private $purchase_code;
        private $api_url = 'https://wpos.app/openpos-update.php';
        public $plugin_info;
        public function __construct($plugin_path,$item_id)
        {
            $this->plugin_path = $plugin_path;
            $this->item_id = $item_id;
            $this->plugin_basename  = plugin_basename($this->plugin_path);
            $this->purchase_code    = $this->get_purchase_code();
            $this->plugin_info = get_plugin_data(OPENPOS_DIR.'woocommerce-openpos.php');

            add_filter( 'pre_set_site_transient_update_plugins', array($this,'pre_set_site_transient_update_plugins') );
            if (is_multisite()) {
                add_filter('network_admin_plugin_action_links_' . $this->plugin_basename, array($this, 'display_purchase_code_edit_link'));
            }
            else {
                add_filter('plugin_action_links_' . $this->plugin_basename, array($this, 'display_purchase_code_edit_link'));
            }
            add_action('wp_ajax_op_up_pc_change', array($this, 'op_up_pc_change'));
            add_action( 'install_plugins_pre_plugin-information', array($this,'op_plugin_details'));
        }
        public static function init($plugin_path,$item_id){
            new self($plugin_path,$item_id);
        }
        function _nonce_check(){
            $nonce = isset($_REQUEST['op_nonce']) ? $_REQUEST['op_nonce'] : '';
            if ( !$nonce || ! wp_verify_nonce( $nonce, 'op_nonce' ) ) {
                die( __( 'Security check', 'openpos' ) ); 
            } 
    
        }
        public function get_purchase_code(){
            $purchase_code = get_option('_op_purchase_code',false);
            return $purchase_code;
        }
        private function verify_ssl() {
            return (bool) apply_filters( 'op_sl_api_request_verify_ssl', true, $this );
        }
        private function api_request( $_action, $data ) {

            global $wp_version;
            
            if( $this->api_url == trailingslashit ( home_url() ) ) {
                return false; // Don't allow a plugin to ping itself
            }
    
            $api_params = array(
                'op_action'  => $_action,// 'get_version',
                'license'     => ! empty( $data['license'] ) ? $data['license'] : '',
                'item_name'   => isset( $data['item_name'] ) ? $data['item_name'] : false,
                'item_id'     => isset( $data['item_id'] ) ? $data['item_id'] : false,
                'version'     => isset( $data['version'] ) ? $data['version'] : false,
                'php_version' => phpversion(),
                'wp_version'  => $wp_version,
                'slug'        => $data['slug'],
                'author'      => $data['author'],
                'url'         => home_url(),
                'beta'        => ! empty( $data['beta'] ),
            );
    
            $verify_ssl = $this->verify_ssl();
            $request    = wp_remote_post( $this->api_url, array( 'timeout' => 15, 'sslverify' => $verify_ssl, 'body' => $api_params ) );
    
            if ( ! is_wp_error( $request ) ) {
                $request = json_decode( wp_remote_retrieve_body( $request ) );
            }
            
            if ( $request && isset( $request->banners ) ) {
                $request->banners = maybe_unserialize( $request->banners );
            }
    
            if ( $request && isset( $request->icons ) ) {
                $request->icons = maybe_unserialize( $request->icons );
            }
    
            if( ! empty( $request->sections ) ) {
                foreach( $request->sections as $key => $section ) {
                    $request->$key = (array) $section;
                }
            }
           
            return $request;
        }
        function pre_set_site_transient_update_plugins($transient){
            // Query premium/private repo for updates.
            $plugin_data = get_plugin_data(OPENPOS_DIR.'woocommerce-openpos.php');;
            $current_version = $plugin_data['Version'];
            $license = $this->purchase_code;

            // No update is available.
            $item = (object) array(
                'id'            => 'woocommerce-openpos/woocommerce-openpos.php',
                'slug'          => 'woocommerce-openpos',
                'plugin'        => 'woocommerce-openpos/woocommerce-openpos.php',
                'new_version'   => $current_version,
                'url'           => '',
                'package'       => '',
                'icons'         => array(),
                'banners'       => array(),
                'banners_rtl'   => array(),
                'tested'        => '',
                'requires_php'  => '',
                'compatibility' => new stdClass(),
            );

            if($license)
            {
                $update = $this->api_request('check_update',array(
                    'license' => $license,
                    'item_id'            => $this->item_id,
                    'item_name'            => 'openpos',
                    'version'            => $current_version,
                    'slug' => 'openpos',
                    'author' => 'anhvnit@gmail.com',
                    'beta' => false
                ));
                if ( $update && isset($update->new_version) && $update->new_version != null && $current_version != null  && version_compare($update->new_version, $current_version) > 0  ) {
    
                    $transient->response['woocommerce-openpos/woocommerce-openpos.php'] = $update;
                } else {
                    $transient->no_update['woocommerce-openpos/woocommerce-openpos.php'] = $item;
                }
            }else{
                $transient->no_update['woocommerce-openpos/woocommerce-openpos.php'] = $item;
            }
            return $transient;
        }
        public function display_purchase_code_edit_link($links){
            // Format link
            $link = '<a class="op_up_pc_' . $this->plugin_key . '" style="cursor: pointer; ' . (empty($this->purchase_code) ? 'font-weight: 700;' : '') . '">' . __('Purchase Code', 'rightpress-updates') . '</a>';
            $op_nonce = wp_create_nonce( 'op_nonce' );
            // Open script
            $link .= '<script type="text/javascript" style="display: none;">';

            // Javascript handler
            $link .= "
                jQuery(document).ready(function() {

                    // Define current purchase code
                    var current_purchase_code = '$this->purchase_code';

                    // Bind click action
                    jQuery('.op_up_pc_$this->plugin_key').click(function() {
                        var prompt_text = '" . __('Enter your Envato Purchase Code to enable automatic updates.', 'rightpress-updates') . "';
                        op_handle_purchase_code_change(prompt_text, current_purchase_code);
                    });

                    // Handle purchase code change
                    function op_handle_purchase_code_change(prompt_text, purchase_code)
                    {
                        // Display dialog
                        var new_purchase_code = prompt(prompt_text, purchase_code);

                        // Prompt cancelled or purchase code was not changed
                        if (new_purchase_code === null || new_purchase_code === current_purchase_code) {
                            return;
                        }

                        // Send request to server
                        jQuery.post(
                            '" . admin_url('admin-ajax.php') . "',
                            {
                                'action':           'op_up_pc_change',
                                'plugin_key':       '$this->plugin_key',
                                'op_nonce':       '$op_nonce',
                                'purchase_code':    new_purchase_code
                            },
                            function(response) {
                                console.log(response);
                                var status = response.status;
                                if(status == 1)
                                {
                                    alert(response.message);
                                }else{
                                    prompt_text = response.message;
                                    op_handle_purchase_code_change(prompt_text, new_purchase_code);
                                    return;
                                }
                                

                                // Reload page to start fresh
                                window.location.reload();
                            },
                            'json'
                        );
                    }
                });
            ";

            // Close script
            $link .= '</script>';

            // Add to links array
            array_unshift($links, $link);
            return $links;
        }
        public function op_up_pc_change(){
            $result = array(
                'status' => 0,
                'message' => __('Unknown','openpos')
            );
            try{
                $this->_nonce_check();
                $plugin_key = isset($_REQUEST['plugin_key']) ? esc_attr($_REQUEST['plugin_key']) : 'openpos';
                $purchase_code = isset($_REQUEST['purchase_code']) ? esc_attr($_REQUEST['purchase_code']) : '';
                if(!$purchase_code)
                {
                    throw new Exception(__('Please enter purchase code','openpos'));
                }
                
                $license_details = $this->api_request('check_license',array(
                    'license' => $purchase_code,
                    'item_id'            => $this->item_id,
                    'item_name'            => 'openpos',
                    'slug' => $plugin_key,
                    'author' => 'anhvnit@gmail.com',
                    'url'         => home_url(),
                    'beta' => false
                ));
                
                if($license_details != null)
                {
                    
                    if($license_details->message )
                    {
                        $result['message'] = $license_details->message;
                    }
                    if($license_details->status && $license_details->status == 1)
                    {
                        update_option('_op_purchase_code',$purchase_code,false);
                        $result['status'] = 1;
                        $result['message'] = __('Thank you! Your purchase code is valid.','openpos');
                    }
                }
               
                
            }catch(Exception $e){
                $result['status'] = 0;
                $result['message'] = $e->getMessage();
            }
            

            echo json_encode($result);
            exit;
        }
        public function op_plugin_details(){
            global $tab;
			if($tab == 'plugin-information' && $_REQUEST['plugin'] == 'woocommerce-openpos'){
                $banner =  OPENPOS_URL.'/assets/openpos.png';
                $all_data = array(
                    'banners' => array('low' =>$banner),
                    'sections' => array(
                        'description' => 'Openpos - Quick POS system for woocommerce.',
                        'changelog' => '',
                    )
                );
                try{
                    $request = $this->api_request('get_update_log',array(
                        'license' => '',
                        'item_id'            => $this->item_id,
                        'item_name'            => 'openpos',
                        'version'            => '',
                        'slug' => 'openpos',
                        'author' => 'anhvnit@gmail.com',
                        'beta' => false
                    ));
                    
                    if ( ! is_wp_error( $request ) && $request->status == 1 ) {
                       
                        $all_data = json_decode(json_encode($request->data), true);
                    }
                }catch(Exception $e){

                }
                

                $this->create_html_data($all_data);
                wp_die('','',array('response' => 200));
            }
        }
        public function create_html_data($all_data){
            $banner =  OPENPOS_URL.'/assets/openpos.png';
			?>
			<style>
				#TB_window{
					top : 4% !important;
				}
				.op_plugin_banner > img {
					height: 55%;
					width: 100%;
					border: 1px solid;
					border-radius: 7px;
				}
				.op_plugin_description > h4 {
					background-color: rgba(0, 182, 255, 0.67);
					padding: 5px;
					color: #ffffff;
					border-radius: 5px;
				}
				.op_plugin_requirement > h4 {
					background-color: rgba(0, 182, 255, 0.67);
					padding: 5px;
					color: #ffffff;
					border-radius: 5px;
                }
                .wp-die-message,
				#error-page > p {
					display: none;
				}
			</style>
			<div class="op_plugin_details_wrapper">
				<div class="op_plugin_banner">
					<img src="<?php echo isset($all_data['banners']['low']) && $all_data['banners']['low'] ? esc_url($all_data['banners']['low']) : $banner;?>">	 
				</div>
				<div class="op_plugin_description">
					<h4><?php _e('Plugin Description','openpos'); ?></h4>
					<span><?php echo isset($all_data['sections']) ? $all_data['sections']['description']: 'Openpos - Quick POS system for woocommerce.'; ?></span>
				</div>
				<div class="op_plugin_requirement">
					<h4><?php _e('Plugin Change Log','openpos'); ?></h4>
					<pre><?php echo isset($all_data['sections']) ? $all_data['sections']['changelog'] : ''; ?></pre>
				</div> 
			</div>
			<?php
		}
    }
}