<?php
/**
 * Plugin Name: Hello World
 * Description: This is our first plugin, which creates an information widget on the admin dashboard as well as an admin notice.
 * Author: Komoliddin
 * Version: 1.0
 * Author URI: https://onlinewebtutorblog.com
 * Plugin URI: https://example.com/hello-world
 */

// Hook into the WordPress admin notices action
add_action("admin_notices", "hw_show_error_message");

function hw_show_success_message() {
    echo '<div class="notice notice-success is-dismissible"><p><strong>Hello, I am a success message!</strong></p></div>';
}


function hw_show_error_message() {
    echo '<div class="notice notice-error is-dismissible"><p><strong>Hello, I am a error message!</strong></p></div>';
}


// Admin dashboard widget

add_action("wp_dashboard_setup" , "hw_create_dashboard_widget");

function hw_create_dashboard_widget(){
    wp_add_dashboard_widget("hw_hello_world" , "New Widget", "hw_custom_admin_widget");
}

function hw_custom_admin_widget(){
    echo "This is custo hello world custom admin widget";
};