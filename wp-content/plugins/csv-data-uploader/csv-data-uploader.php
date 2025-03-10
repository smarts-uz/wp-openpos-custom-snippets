<?php
/**
 * Plugin Name: CSV Data Uploader
 * Description: This plugin will uploads CSV data to DB table
 * Author: Komoliddin
 * Version: 1.0
 * Author URI: https://onlinewebtutorblog.com
 * Plugin URI: https://example.com/shortcodes
 */

define('CDU_PLUGIN_DIR_PATH', plugin_dir_path(__FILE__));

add_shortcode('csv-data-uploader', 'cdu_display_uploader_form');
 
 function cdu_display_uploader_form() {
     // Start PHP buffer
     ob_start();
 
     include_once CDU_PLUGIN_DIR_PATH . 'template/cdu_form.php'; // Put all contents into buffer
 
     // Read buffer
     $template = ob_get_contents();
 
     // Clean buffer
     ob_end_clean();
 
     return $template;    
 }
 
 // Db Table on plugin activation
register_activation_hook(__FILE__, 'cdu_create_table');
 
 function cdu_create_table() {
     global $wpdb;
     $table_prefix = $wpdb->prefix;
     $table_name = $table_prefix . 'students_data'; // "wp_" oldin qo'shiladi
     $table_collate = $wpdb->get_charset_collate();
 
     $sql = "CREATE TABLE  $table_name (
         `id` int NOT NULL AUTO_INCREMENT,
         `name` varchar(255) NOT NULL,
         `email` varchar(255) NOT NULL,
         `age` int NOT NULL,
         `phone` varchar(20) NOT NULL,
         `photo` varchar(255) NOT NULL,
         PRIMARY KEY (`id`)
        ) ENGINE=InnoDB $table_collate;";
 
     require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
     dbDelta($sql);
 
     // Log any errors
     if ($wpdb->last_error) {
         error_log('Database error: ' . $wpdb->last_error);
     }
 }
 
 define('WP_DEBUG', true);
 define('WP_DEBUG_LOG', true);
 