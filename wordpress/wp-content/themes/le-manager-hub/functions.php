<?php
if(!defined('ABSPATH')) exit;
// Disable legacy Core callbacks if that plugin is still active. Data stays intact.
if (class_exists('LMH_Core')) {
 foreach (['init'=>['register_content','register_roles'],'rest_api_init'=>['routes'],'wp_enqueue_scripts'=>['frontend_config'],'save_post'=>['protect_booking_owner']] as $hook=>$methods) {
  foreach ($methods as $method) remove_action($hook,['LMH_Core',$method],10);
 }
}
require_once get_template_directory().'/inc/core.php';
require_once get_template_directory().'/inc/hub.php';
require_once get_template_directory().'/inc/backend.php';
add_action('after_switch_theme',function(){LMH_Theme_Core::activate();});
function lmh_theme_setup(){add_theme_support('post-thumbnails');add_theme_support('custom-logo');register_nav_menus(['primary'=>'Primary Menu']);}
add_action('after_setup_theme','lmh_theme_setup');
function lmh_theme_assets(){
 $home=is_front_page() && !get_query_var('lmh_view');
 if(!$home){$theme_ver=wp_get_theme()->get('Version');$css_ver=@filemtime(get_template_directory().'/assets/v5.css')?:$theme_ver;$js_ver=@filemtime(get_template_directory().'/assets/js/site.js')?:$theme_ver;wp_enqueue_style('lmh-style',get_stylesheet_uri(),[],$theme_ver);wp_enqueue_style('lmh-v5',get_template_directory_uri().'/assets/v5.css',['lmh-style'],$css_ver);wp_enqueue_script('lmh-site',get_template_directory_uri().'/assets/js/site.js',[],$js_ver,true);}
 wp_enqueue_script('lmh-v5-bridge',get_template_directory_uri().'/assets/js/v5-bridge.js',[],'1.1.0',true);
 $config=['home'=>home_url('/'),'api'=>rest_url('lmh/v1/'),'nonce'=>wp_create_nonce('wp_rest'),'login'=>wp_login_url(lmh_url('account')),'loggedIn'=>is_user_logged_in(),'account'=>lmh_url('account'),'join'=>lmh_url('join'),'directory'=>lmh_url('directory'),'isHome'=>$home,'archives'=>[]];
 foreach(['artist','professional','company','event','opportunity','tv','radio','podcast'] as $type)$config['archives'][$type]=get_post_type_archive_link('lmh_'.$type);
 wp_localize_script('lmh-v5-bridge','LMH_V5',$config);
 if(!$home)wp_localize_script('lmh-site','LMH_THEME',$config);
}
add_action('wp_enqueue_scripts','lmh_theme_assets');

// Keep WordPress default styling from changing the approved homepage.
add_action('wp_enqueue_scripts',function(){if(is_front_page()&&!get_query_var('lmh_view')){foreach(['wp-block-library','wp-block-library-theme','global-styles','classic-theme-styles'] as $handle)wp_dequeue_style($handle);}},100);
add_action('wp_head',function(){if(!is_front_page()||get_query_var('lmh_view'))echo '<title>'.esc_html(wp_get_document_title()).'</title>';},1);
