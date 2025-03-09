<?php
/**
 * Plugin Name: Shortcode Plugin
 * Description: This is second plugin of course
 * Author: Komoliddin
 * Version: 1.0
 * Author URI: https://onlinewebtutorblog.com
 * Plugin URI: https://example.com/shortcodes
 */

add_shortcode("message", "sp_show_static_message");

function sp_show_static_message(){

    return "Hello I am a simple  shortcode message";
}


add_shortcode("student", "sp_handle_student_data");

function sp_handle_student_data($attributes){

    $attributes = shortcode_atts(array(
        "name" => "Default Student",
        "email" => "Default Email"
    ),$attributes, "student");

    return "<h3>Student Data: Name - ".$attributes['name'].", Email - ".$attributes['email']."</h3>";
}

