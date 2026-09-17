<?php
if(!defined('ABSPATH')) exit;
function lmh_theme_setup(){add_theme_support('title-tag');add_theme_support('post-thumbnails');add_theme_support('custom-logo');register_nav_menus(['primary'=>'Primary Menu']);}
add_action('after_setup_theme','lmh_theme_setup');
function lmh_theme_assets(){wp_enqueue_style('lmh-style',get_stylesheet_uri(),[],wp_get_theme()->get('Version'));wp_enqueue_script('lmh-site',get_template_directory_uri().'/assets/js/site.js',[],wp_get_theme()->get('Version'),true);wp_localize_script('lmh-site','LMH_THEME',['home'=>home_url('/'),'api'=>rest_url('lmh/v1/'),'nonce'=>wp_create_nonce('wp_rest'),'loggedIn'=>is_user_logged_in()]);}
add_action('wp_enqueue_scripts','lmh_theme_assets');