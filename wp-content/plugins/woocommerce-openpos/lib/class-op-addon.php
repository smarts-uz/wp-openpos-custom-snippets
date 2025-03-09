<?php
//install_plugins_tabs
if(!class_exists('OP_Addon'))
{
    class OP_Addon{
        public function __construct()
        {
            add_filter('install_plugins_tabs',array($this,'install_plugins_tabs'),10,1);
            add_action( 'admin_print_styles-plugin-install.php', array( $this, 'add_plugins_page_styles' ) );
            add_action( 'install_plugins_table_api_args_op_addons', array( $this, 'install_plugins_table' ) );
            add_action( 'install_plugins_op_addons', array( $this, 'plugins_api' ),110 ,3);
        }
        public function install_plugins_tabs($tabs){
            $tabs['op_addons']   = _x( 'OpenPOS Addons', 'openpos' );
            return $tabs;
        }
        public function add_plugins_page_styles() {
            ?>
            <style>
                .plugin-install-op_addons > a::after {
                    content: "";
                    display: inline-block;
                    background-image: url("data:image/svg+xml,%3Csvg width='16' height='16' viewBox='0 0 16 16' fill='none' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath fill-rule='evenodd' clip-rule='evenodd' d='M8.33321 3H12.9999V7.66667H11.9999V4.70711L8.02009 8.68689L7.31299 7.97978L11.2928 4H8.33321V3Z' fill='%23646970'/%3E%3Cpath d='M6.33333 4.1665H4.33333C3.8731 4.1665 3.5 4.5396 3.5 4.99984V11.6665C3.5 12.1267 3.8731 12.4998 4.33333 12.4998H11C11.4602 12.4998 11.8333 12.1267 11.8333 11.6665V9.6665' stroke='%23646970'/%3E%3C/svg%3E%0A");
                    width: 16px;
                    height: 16px;
                    background-repeat: no-repeat;
                    vertical-align: text-top;
                    margin-left: 2px;
                }
                .plugin-install-op_addons:hover > a::after {
                    background-image: url("data:image/svg+xml,%3Csvg width='16' height='16' viewBox='0 0 16 16' fill='none' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath fill-rule='evenodd' clip-rule='evenodd' d='M8.33321 3H12.9999V7.66667H11.9999V4.70711L8.02009 8.68689L7.31299 7.97978L11.2928 4H8.33321V3Z' fill='%23135E96'/%3E%3Cpath d='M6.33333 4.1665H4.33333C3.8731 4.1665 3.5 4.5396 3.5 4.99984V11.6665C3.5 12.1267 3.8731 12.4998 4.33333 12.4998H11C11.4602 12.4998 11.8333 12.1267 11.8333 11.6665V9.6665' stroke='%23135E96'/%3E%3C/svg%3E%0A");
                }
            </style>
            <?php
        }
        public function install_plugins_table(){
           // echo '<p>xx</p>';
        }
        public function plugins_api( ){
            if ( ! isset( $_GET['tab'] ) || 'op_addons' !== $_GET['tab'] ) {
                return;
            }
            $woo_url = 'https://wpos.app/openpos-addons/';
            wp_redirect( $woo_url );
		    exit;
        }
    }
}