<?php
if (!defined('ABSPATH')) exit;

/**
 * Phase 1 backend foundation: member roles, profile ownership,
 * verification, booking workflow and admin visibility.
 */
final class LMH_Backend {
  const BOOKING_STATUSES = ['new','reviewing','accepted','declined','confirmed','completed','cancelled'];

  public static function init() {
    add_action('init',[__CLASS__,'roles'],20);
    add_action('add_meta_boxes',[__CLASS__,'profile_box']);
    add_action('save_post',[__CLASS__,'save_profile'],20,2);
    add_action('rest_api_init',[__CLASS__,'routes']);
    add_filter('manage_lmh_booking_posts_columns',[__CLASS__,'booking_columns']);
    add_action('manage_lmh_booking_posts_custom_column',[__CLASS__,'booking_column'],10,2);
  }

  public static function roles() {
    $roles=[
      'lmh_artist'=>['LM Artist',['read'=>true,'upload_files'=>true]],
      'lmh_manager'=>['LM Manager',['read'=>true,'upload_files'=>true]],
      'lmh_promoter'=>['LM Promoter',['read'=>true,'upload_files'=>true]],
      'lmh_label'=>['LM Label / Company',['read'=>true,'upload_files'=>true]],
    ];
    foreach($roles as $slug=>$cfg) if(!get_role($slug)) add_role($slug,$cfg[0],$cfg[1]);
  }

  public static function profile_box() {
    foreach(['lmh_artist','lmh_professional','lmh_company'] as $type)
      add_meta_box('lmh-profile-admin','Profile Ownership & Verification',[__CLASS__,'render_profile_box'],$type,'side','high');
  }

  public static function render_profile_box($post) {
    if(!current_user_can('manage_options')) { echo '<p>Managed by Le Manager administrators.</p>'; return; }
    wp_nonce_field('lmh_profile_admin','lmh_profile_admin_nonce');
    $owner=(int)get_post_meta($post->ID,'_lmh_owner_user_id',true);
    $verified=get_post_meta($post->ID,'_lmh_verified_level',true);
    echo '<p><label><strong>Owner User ID</strong></label><input class="widefat" type="number" min="0" name="lmh_owner_user_id" value="'.esc_attr($owner).'"></p>';
    echo '<p><label><strong>Verification</strong></label><select class="widefat" name="lmh_verified_level">';
    foreach(['unverified'=>'Unverified','pending'=>'Pending Review','verified'=>'Verified','featured'=>'Verified + Featured'] as $value=>$label)
      echo '<option value="'.esc_attr($value).'" '.selected($verified?:'unverified',$value,false).'>'.esc_html($label).'</option>';
    echo '</select></p>';
  }

  public static function save_profile($post_id,$post) {
    if(!in_array($post->post_type,['lmh_artist','lmh_professional','lmh_company'],true)) return;
    if(!isset($_POST['lmh_profile_admin_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['lmh_profile_admin_nonce'])),'lmh_profile_admin')) return;
    if(!current_user_can('manage_options')) return;
    $owner=absint($_POST['lmh_owner_user_id']??0);
    if($owner && !get_user_by('id',$owner)) $owner=0;
    update_post_meta($post_id,'_lmh_owner_user_id',$owner);
    $level=sanitize_key($_POST['lmh_verified_level']??'unverified');
    if(!in_array($level,['unverified','pending','verified','featured'],true)) $level='unverified';
    update_post_meta($post_id,'_lmh_verified_level',$level);
  }

  public static function routes() {
    register_rest_route('lmh/v1','/bookings/(?P<id>\d+)/status',[
      'methods'=>'POST',
      'callback'=>[__CLASS__,'booking_status'],
      'permission_callback'=>function(){ return current_user_can('manage_options'); }
    ]);
    register_rest_route('lmh/v1','/profiles',[
      'methods'=>'POST','callback'=>[__CLASS__,'create_profile'],
      'permission_callback'=>function(){ return is_user_logged_in(); }
    ]);
    register_rest_route('lmh/v1','/profiles/(?P<id>\\d+)',[
      'methods'=>'POST','callback'=>[__CLASS__,'update_profile'],
      'permission_callback'=>function($r){ return self::can_manage_profile(absint($r['id'])); }
    ]);
    register_rest_route('lmh/v1','/profiles/mine',[
      'methods'=>'GET',
      'callback'=>[__CLASS__,'my_profiles'],
      'permission_callback'=>function(){ return is_user_logged_in(); }
    ]);
  }


  public static function can_manage_profile($id) {
    if(current_user_can('manage_options')) return true;
    return $id && (int)get_post_meta($id,'_lmh_owner_user_id',true)===get_current_user_id();
  }

  private static function profile_type($kind) {
    return ['artist'=>'lmh_artist','professional'=>'lmh_professional','company'=>'lmh_company'][sanitize_key($kind)] ?? '';
  }

  private static function profile_payload(WP_REST_Request $r) {
    return [
      'name'=>sanitize_text_field($r->get_param('name')),
      'bio'=>wp_kses_post((string)$r->get_param('bio')),
      'city'=>sanitize_text_field($r->get_param('city')),
      'website'=>esc_url_raw($r->get_param('website')),
      'services'=>sanitize_textarea_field($r->get_param('services')),
      'booking_email'=>sanitize_email($r->get_param('booking_email')),
    ];
  }

  public static function create_profile(WP_REST_Request $r) {
    $type=self::profile_type($r->get_param('type'));
    $data=self::profile_payload($r);
    if(!$type) return new WP_Error('invalid_type','Choose Artist, Professional or Company.',['status'=>400]);
    if($data['name']==='') return new WP_Error('missing_name','Profile name is required.',['status'=>400]);
    $id=wp_insert_post(['post_type'=>$type,'post_status'=>'pending','post_title'=>$data['name'],'post_content'=>$data['bio'],'post_author'=>get_current_user_id()],true);
    if(is_wp_error($id)) return $id;
    update_post_meta($id,'_lmh_owner_user_id',get_current_user_id());
    update_post_meta($id,'_lmh_verified_level','pending');
    foreach(['city','website','services','booking_email'] as $key) if($data[$key]!=='') update_post_meta($id,'_lmh_'.$key,$data[$key]);
    return new WP_REST_Response(['success'=>true,'id'=>$id,'status'=>'pending','verification'=>'pending'],201);
  }

  public static function update_profile(WP_REST_Request $r) {
    $id=absint($r['id']);
    if(!in_array(get_post_type($id),['lmh_artist','lmh_professional','lmh_company'],true)) return new WP_Error('not_profile','Profile not found.',['status'=>404]);
    $data=self::profile_payload($r);
    $post=[]; if($data['name']!=='')$post['post_title']=$data['name']; if($r->has_param('bio'))$post['post_content']=$data['bio'];
    if($post){$post['ID']=$id;$saved=wp_update_post($post,true);if(is_wp_error($saved))return $saved;}
    foreach(['city','website','services','booking_email'] as $key) if($r->has_param($key)) update_post_meta($id,'_lmh_'.$key,$data[$key]);
    if(!current_user_can('manage_options') && get_post_status($id)==='publish') update_post_meta($id,'_lmh_profile_updated_at',current_time('mysql'));
    return ['success'=>true,'id'=>$id,'status'=>get_post_status($id),'verification'=>get_post_meta($id,'_lmh_verified_level',true)?:'unverified'];
  }

  public static function booking_status(WP_REST_Request $r) {
    $id=absint($r['id']);
    if(get_post_type($id)!=='lmh_booking') return new WP_Error('not_booking','Booking not found.',['status'=>404]);
    $status=sanitize_key($r->get_param('status'));
    if(!in_array($status,self::BOOKING_STATUSES,true)) return new WP_Error('invalid_status','Invalid booking status.',['status'=>400]);
    update_post_meta($id,'_lmh_status',$status);
    update_post_meta($id,'_lmh_status_updated_at',current_time('mysql'));
    update_post_meta($id,'_lmh_status_updated_by',get_current_user_id());
    return ['success'=>true,'booking_id'=>$id,'status'=>$status];
  }

  public static function my_profiles() {
    $uid=get_current_user_id();
    $q=new WP_Query([
      'post_type'=>['lmh_artist','lmh_professional','lmh_company'],
      'post_status'=>['publish','pending','draft','private'],
      'posts_per_page'=>50,
      'meta_key'=>'_lmh_owner_user_id','meta_value'=>$uid
    ]);
    return array_map(function($p){return [
      'id'=>$p->ID,'type'=>$p->post_type,'name'=>get_the_title($p),
      'status'=>$p->post_status,'verification'=>get_post_meta($p->ID,'_lmh_verified_level',true)?:'unverified',
      'edit_url'=>current_user_can('edit_post',$p->ID)?get_edit_post_link($p->ID,'raw'):null
    ];},$q->posts);
  }

  public static function booking_columns($cols) {
    $cols['lmh_status']='Status'; $cols['lmh_date']='Event Date'; $cols['lmh_talent']='Talent';
    return $cols;
  }
  public static function booking_column($column,$id) {
    if($column==='lmh_status') echo esc_html(ucwords(str_replace('_',' ',get_post_meta($id,'_lmh_status',true)?:'new')));
    if($column==='lmh_date') echo esc_html(get_post_meta($id,'_lmh_event_date',true));
    if($column==='lmh_talent'){ $talent=(int)get_post_meta($id,'_lmh_talent_id',true); echo $talent?esc_html(get_the_title($talent)):'—'; }
  }
}
LMH_Backend::init();
