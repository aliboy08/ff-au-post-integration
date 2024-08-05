<?php
/**
 * Plugin Name: FF AU Post Integration
 * Plugin URI: https://www.fivebyfive.com.au/
 * Description: Australia Post API Integration
 * Version: 2.0
 * Author: Five by Five
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */


if ( ! defined( 'ABSPATH' ) ) die();

add_action('init', function(){
    if( !class_exists('WooCommerce') ) return;
    include_once 'class-ff-au-post.php';
    new FF_AU_Post();
});

add_action('admin_menu', function(){
    if( !class_exists('WooCommerce') ) return;
    add_submenu_page( 'fivebyfive', 'Australia Post', 'Australia Post', 'manage_options', 'ff_au_post', function(){
        include_once 'admin-settings.php';
    });
});