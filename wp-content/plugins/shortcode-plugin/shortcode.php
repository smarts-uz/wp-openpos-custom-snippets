<?php
/**
 * Plugin Name: Shortcode Plugin
 * Description: This is second plugin of course
 * Author: Komoliddin
 * Version: 1.0
 * Author URI: https://onlinewebtutorblog.com
 * Plugin URI: https://example.com/shortcodes
 */


 // Basic shortcode
add_shortcode("message", "sp_show_static_message");

function sp_show_static_message(){

    return "Hello I am a simple  shortcode message";
}


// Shortcode with params
add_shortcode("student", "sp_handle_student_data");

function sp_handle_student_data($attributes){

    $attributes = shortcode_atts(array(
        "name" => "Default Student",
        "email" => "Default Email"
    ),$attributes, "student");

    return "<h3>Student Data: Name - ".$attributes['name'].", Email - ".$attributes['email']."</h3>";
}

// Shortcode with DB operation

add_shortcode("list-posts", "sp_handle_list_posts_wp_query_class");

function sp_handle_list_posts(){

    global $wpdb;

    $table_prefix = $wpdb->prefix; //wp_
    $table_name = $table_prefix . "posts"; // wp_posts


    //Get post whose post_type = post and post_status = publish

    $posts = $wpdb->get_results(
        "SELECT post_title from {$table_name} WHERE post_type = 'post' AND post_status = 'publish'"
    );

    if(count($posts) > 0){

        $outputHTML = "<ul>";


        foreach($posts as $post){
            $outputHTML .= '<li>'.$post->post_title.'</li>';
        }

        $outputHTML .= "</ul>";

        return $outputHTML;
    }


    return 'No Post Found';
}

// [list-posts number="10"]

function sp_handle_list_posts_wp_query_class($attributes){
    $attributes = shortcode_atts(array(
        "number" => 6
    ), $attributes, "list-posts");

    $query = new WP_Query(array(
        "posts_per_page" => $attributes['number'],
        "post_status" => "publish"
    ));

    if($query-> have_posts()){

        $outputHTML = '<ul>';

        while($query->have_posts()){
            $query->the_post();
            $outputHTML .= '<li class="my_class"><a href="'.get_the_permalink().'">'.get_the_title().'</a></li>'; // Hello World
            // $outputHTML .= '<li class="my_class">'.get_the_title().'</li>';
        }

        $outputHTML .= '</ul>';

        return $outputHTML;
    }

    return "No post found";
}