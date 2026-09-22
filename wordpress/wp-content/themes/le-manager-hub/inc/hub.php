<?php
if (!defined('ABSPATH')) exit;
function lmh_url($view) { return add_query_arg('lmh_view',$view,home_url('/')); }
add_filter('query_vars',function($vars){$vars[]='lmh_view';return $vars;});
add_filter('template_include',function($file){return in_array(get_query_var('lmh_view'),['account','join','directory'],true) ? get_template_directory().'/hub-page.php' : $file;});
add_action('template_redirect',function(){if(get_query_var('lmh_view')==='account') { nocache_headers(); if(!is_user_logged_in()){wp_safe_redirect(wp_login_url(lmh_url('account')));exit;} }});
function lmh_cards($type,$limit=4){
 $q=new WP_Query(['post_type'=>$type,'post_status'=>'publish','posts_per_page'=>$limit]);
 echo '<div class="lmh-grid">';
 while($q->have_posts()){ $q->the_post(); echo '<article class="lmh-card">'; if(has_post_thumbnail()) echo '<a href="'.esc_url(get_permalink()).'">'.get_the_post_thumbnail(null,'medium_large').'</a>'; echo '<h3><a href="'.esc_url(get_permalink()).'">'.esc_html(get_the_title()).'</a></h3>'; the_excerpt(); echo '<a class="lmh-text-link" href="'.esc_url(get_permalink()).'">Explore →</a></article>'; }
 if(!$q->post_count) echo '<p class="lmh-empty">New additions are coming soon. Check back for the latest from the community.</p>';
 echo '</div>';wp_reset_postdata();
}
add_action('rest_api_init',function(){
 register_rest_route('lmh/v1','/squad',['methods'=>'POST','permission_callback'=>function(){return is_user_logged_in();},'callback'=>function($r){
  $joined=rest_sanitize_boolean($r->get_param('joined'));update_user_meta(get_current_user_id(),'lmh_event_squad',$joined?'joined':'');return ['joined'=>$joined];
 }]);
});
add_action('add_meta_boxes',function(){
 foreach(get_post_types([], 'names') as $type) if(strpos($type,'lmh_')===0) add_meta_box('lmh-details','Le Manager Hub Details','lmh_meta_box',$type,'normal','high');
});
function lmh_meta_box($post){
 wp_nonce_field('lmh_details','lmh_details_nonce');
 foreach(get_registered_meta_keys('post',$post->post_type) as $key=>$schema){
  if(strpos($key,'_lmh_')!==0)continue;
  $label=ucwords(str_replace('_',' ',substr($key,5)));
  echo '<p><label for="'.esc_attr($key).'"><strong>'.esc_html($label).'</strong></label><br><input class="widefat" id="'.esc_attr($key).'" name="lmh_meta['.esc_attr($key).']" value="'.esc_attr(get_post_meta($post->ID,$key,true)).'"></p>';
 }
}
add_action('save_post',function($id){
 if(!isset($_POST['lmh_details_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['lmh_details_nonce'])),'lmh_details') || !current_user_can('edit_post',$id) || wp_is_post_revision($id) || (defined('DOING_AUTOSAVE')&&DOING_AUTOSAVE))return;
 foreach(get_registered_meta_keys('post',get_post_type($id)) as $key=>$schema){if(strpos($key,'_lmh_')===0 && isset($_POST['lmh_meta'][$key]) && is_scalar($_POST['lmh_meta'][$key]))update_post_meta($id,$key,sanitize_textarea_field(wp_unslash($_POST['lmh_meta'][$key])));}
});
add_filter('manage_users_columns',function($cols){$cols['lmh_squad']='Event Squad';return $cols;});
add_filter('manage_users_custom_column',function($value,$column,$id){return $column==='lmh_squad' ? esc_html(get_user_meta($id,'lmh_event_squad',true)) : $value;},10,3);
add_action('show_user_profile','lmh_user_fields');add_action('edit_user_profile','lmh_user_fields');
function lmh_user_fields($user){if(!current_user_can('manage_options'))return; wp_nonce_field('lmh_member','lmh_member_nonce'); ?>
 <h2>Le Manager Community</h2><p><label>Fan level <select name="lmh_fan_level"><?php foreach(['Fan','Insider','Ambassador','VIP'] as $level) echo '<option '.selected(get_user_meta($user->ID,'lmh_fan_level',true)?:'Fan',$level,false).'>'.esc_html($level).'</option>';?></select></label></p>
 <p><label><input type="checkbox" name="lmh_event_squad" value="joined" <?php checked(get_user_meta($user->ID,'lmh_event_squad',true),'joined');?>> Event Squad member</label></p><?php
}
function lmh_save_user($id){if(!current_user_can('manage_options') || !isset($_POST['lmh_member_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['lmh_member_nonce'])),'lmh_member'))return; $level=sanitize_text_field(wp_unslash($_POST['lmh_fan_level']??'Fan'));if(in_array($level,['Fan','Insider','Ambassador','VIP'],true))update_user_meta($id,'lmh_fan_level',$level);update_user_meta($id,'lmh_event_squad',isset($_POST['lmh_event_squad'])?'joined':'');}
add_action('personal_options_update','lmh_save_user');add_action('edit_user_profile_update','lmh_save_user');
add_action('admin_menu',function(){add_theme_page('Le Manager Hub','Le Manager Hub','manage_options','lmh-help',function(){echo '<div class="wrap"><h1>Le Manager Hub</h1><p>Your Core backend is included in this theme. No separate plugin is required.</p><p>Use Artists, Professionals, Companies, Events, Opportunities, TV, Radio and Podcasts to publish content. Featured images and excerpts populate the connected directory and archive pages. The homepage preserves your approved V5 HTML exactly. Complete the Le Manager Hub Details box for links and metadata.</p><p>Bookings are private and managed by administrators. Users can view their own requests in their account. Event Squad membership and fan levels are managed under Users.</p><p>To allow signups, enable “Anyone can register” under Settings → General. The theme never changes this setting automatically.</p><p>Switching themes preserves records but hides Hub features until this theme is reactivated. Deactivate the old Le Manager Hub Core plugin if installed.</p></div>';});});

