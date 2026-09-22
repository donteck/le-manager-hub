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
    add_action('user_register',[__CLASS__,'assign_member_identity'],20);
    add_action('wp_login',[__CLASS__,'ensure_member_identity_on_login'],20,2);
    add_action('add_meta_boxes',[__CLASS__,'profile_box']);
    add_action('save_post',[__CLASS__,'save_profile'],20,2);
    add_action('rest_api_init',[__CLASS__,'routes']);
    add_filter('manage_lmh_booking_posts_columns',[__CLASS__,'booking_columns']);
    add_action('manage_lmh_booking_posts_custom_column',[__CLASS__,'booking_column'],10,2);
    add_action('admin_menu',[__CLASS__,'admin_menu']);
    add_action('admin_post_lmh_review_profile',[__CLASS__,'review_profile']);
    add_action('admin_post_lmh_booking_status',[__CLASS__,'admin_booking_status']);
  }

  private static function generate_lmid() {
    global $wpdb;
    for($i=0;$i<50;$i++){
      $id=(string)random_int(100000000,999999999);
      $exists=$wpdb->get_var($wpdb->prepare("SELECT user_id FROM {$wpdb->usermeta} WHERE meta_key='_lmh_member_id' AND meta_value=%s LIMIT 1",$id));
      if(!$exists) return $id;
    }
    return '';
  }

  public static function assign_member_identity($user_id) {
    if(get_user_meta($user_id,'_lmh_member_id',true)) return;
    $id=self::generate_lmid();
    if(!$id) return;
    update_user_meta($user_id,'_lmh_member_id',$id);
    update_user_meta($user_id,'_lmh_member_level','fan');
    update_user_meta($user_id,'_lmh_member_status','active');
    update_user_meta($user_id,'_lmh_member_since',current_time('Y-m-d'));
    update_user_meta($user_id,'_lmh_card_status','digital');
  }

  public static function ensure_member_identity_on_login($login,$user) {
    self::assign_member_identity($user->ID);
  }

  public static function member_identity($user_id) {
    self::assign_member_identity($user_id);
    return [
      'id'=>(string)get_user_meta($user_id,'_lmh_member_id',true),
      'level'=>sanitize_key(get_user_meta($user_id,'_lmh_member_level',true)?:'fan'),
      'status'=>sanitize_key(get_user_meta($user_id,'_lmh_member_status',true)?:'active'),
      'since'=>(string)get_user_meta($user_id,'_lmh_member_since',true),
      'card_status'=>sanitize_key(get_user_meta($user_id,'_lmh_card_status',true)?:'digital'),
    ];
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
      'phone'=>sanitize_text_field($r->get_param('phone')),
      'languages'=>sanitize_text_field($r->get_param('languages')),
      'availability'=>sanitize_text_field($r->get_param('availability')),
      'achievements'=>sanitize_textarea_field($r->get_param('achievements')),
      'portfolio_url'=>esc_url_raw($r->get_param('portfolio_url')),
      'cover_image_url'=>esc_url_raw($r->get_param('cover_image_url')),
      'instagram'=>esc_url_raw($r->get_param('instagram')),
      'tiktok'=>esc_url_raw($r->get_param('tiktok')),
      'facebook'=>esc_url_raw($r->get_param('facebook')),
      'x'=>esc_url_raw($r->get_param('x')),
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
    foreach(['city','website','services','booking_email','phone','languages','availability','achievements','portfolio_url','cover_image_url','instagram','tiktok','facebook','x'] as $key) if($data[$key]!=='') update_post_meta($id,'_lmh_'.$key,$data[$key]);
    return new WP_REST_Response(['success'=>true,'id'=>$id,'status'=>'pending','verification'=>'pending'],201);
  }

  public static function update_profile(WP_REST_Request $r) {
    $id=absint($r['id']);
    if(!in_array(get_post_type($id),['lmh_artist','lmh_professional','lmh_company'],true)) return new WP_Error('not_profile','Profile not found.',['status'=>404]);
    $data=self::profile_payload($r);
    $post=[]; if($data['name']!=='')$post['post_title']=$data['name']; if($r->has_param('bio'))$post['post_content']=$data['bio'];
    if($post){$post['ID']=$id;$saved=wp_update_post($post,true);if(is_wp_error($saved))return $saved;}
    foreach(['city','website','services','booking_email','phone','languages','availability','achievements','portfolio_url','cover_image_url','instagram','tiktok','facebook','x'] as $key) if($r->has_param($key)) update_post_meta($id,'_lmh_'.$key,$data[$key]);
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


  public static function admin_menu() {
    add_menu_page('Le Manager Control Center','Le Manager','manage_options','lmh-control',[__CLASS__,'control_center'],'dashicons-networking',3);
  }

  public static function control_center() {
    if(!current_user_can('manage_options')) return;
    $pending=new WP_Query(['post_type'=>['lmh_artist','lmh_professional','lmh_company'],'post_status'=>['pending','draft'],'posts_per_page'=>50,'orderby'=>'date','order'=>'DESC']);
    $bookings=new WP_Query(['post_type'=>'lmh_booking','post_status'=>'private','posts_per_page'=>25,'orderby'=>'date','order'=>'DESC']);
    $counts=[
      'artists'=>(int)(wp_count_posts('lmh_artist')->publish??0),
      'professionals'=>(int)(wp_count_posts('lmh_professional')->publish??0),
      'companies'=>(int)(wp_count_posts('lmh_company')->publish??0),
      'pending'=>(int)$pending->found_posts,
      'bookings'=>(int)$bookings->found_posts
    ];
    echo '<div class="wrap"><h1>Le Manager Control Center</h1><p>Manage the professional network, verification and booking workflow.</p>';
    echo '<div style="display:flex;gap:12px;flex-wrap:wrap;margin:20px 0">';
    foreach($counts as $label=>$count) echo '<div style="background:#fff;border:1px solid #dcdcde;padding:16px 22px;min-width:130px"><strong style="font-size:24px">'.esc_html($count).'</strong><br>'.esc_html(ucwords($label)).'</div>';
    echo '</div><h2>Profiles Awaiting Review</h2>';
    if(!$pending->have_posts()) echo '<p>No profiles are awaiting review.</p>';
    else {echo '<table class="widefat striped"><thead><tr><th>Profile</th><th>Type</th><th>Owner</th><th>Submitted</th><th>Action</th></tr></thead><tbody>';
      foreach($pending->posts as $p){$owner=(int)get_post_meta($p->ID,'_lmh_owner_user_id',true);$u=$owner?get_user_by('id',$owner):false;
        echo '<tr><td><strong>'.esc_html(get_the_title($p)).'</strong></td><td>'.esc_html(ucwords(str_replace(['lmh_','_'],['',' '],$p->post_type))).'</td><td>'.esc_html($u?$u->display_name:'Unassigned').'</td><td>'.esc_html(get_the_date('', $p)).'</td><td>';
        foreach(['approve'=>'Approve','verify'=>'Approve + Verify','decline'=>'Decline'] as $action=>$label){$url=wp_nonce_url(admin_url('admin-post.php?action=lmh_review_profile&profile_id='.$p->ID.'&decision='.$action),'lmh_review_'.$p->ID);echo '<a class="button" style="margin-right:5px" href="'.esc_url($url).'">'.esc_html($label).'</a>';}
        echo '</td></tr>';
      } echo '</tbody></table>';
    }
    echo '<h2 style="margin-top:30px">Recent Booking Requests</h2>';
    if(!$bookings->have_posts()) echo '<p>No booking requests yet.</p>';
    else {echo '<table class="widefat striped"><thead><tr><th>Booking</th><th>Requester</th><th>Talent</th><th>Event Date</th><th>Status</th><th>Update</th></tr></thead><tbody>';
      foreach($bookings->posts as $b){$requester=(int)get_post_meta($b->ID,'_lmh_requester_id',true);$ru=$requester?get_user_by('id',$requester):false;$talent=(int)get_post_meta($b->ID,'_lmh_talent_id',true);$status=get_post_meta($b->ID,'_lmh_status',true)?:'new';
        echo '<tr><td>#'.esc_html($b->ID).'</td><td>'.esc_html($ru?$ru->display_name:'Unknown').'</td><td>'.esc_html($talent?get_the_title($talent):'—').'</td><td>'.esc_html(get_post_meta($b->ID,'_lmh_event_date',true)).'</td><td><strong>'.esc_html(ucwords($status)).'</strong></td><td><form method="post" action="'.esc_url(admin_url('admin-post.php')).'"><input type="hidden" name="action" value="lmh_booking_status"><input type="hidden" name="booking_id" value="'.esc_attr($b->ID).'">'.wp_nonce_field('lmh_booking_'.$b->ID,'_wpnonce',true,false).'<select name="status">';
        foreach(self::BOOKING_STATUSES as $s) echo '<option value="'.esc_attr($s).'" '.selected($status,$s,false).'>'.esc_html(ucwords($s)).'</option>';
        echo '</select> <button class="button">Update</button></form></td></tr>';
      } echo '</tbody></table>';
    }
    echo '</div>';
  }

  public static function review_profile() {
    if(!current_user_can('manage_options')) wp_die('Not allowed.');
    $id=absint($_GET['profile_id']??0); check_admin_referer('lmh_review_'.$id);
    if(!in_array(get_post_type($id),['lmh_artist','lmh_professional','lmh_company'],true)) wp_die('Profile not found.');
    $decision=sanitize_key($_GET['decision']??'');
    if($decision==='approve'){wp_update_post(['ID'=>$id,'post_status'=>'publish']);update_post_meta($id,'_lmh_verified_level','unverified');}
    elseif($decision==='verify'){wp_update_post(['ID'=>$id,'post_status'=>'publish']);update_post_meta($id,'_lmh_verified_level','verified');}
    elseif($decision==='decline'){wp_update_post(['ID'=>$id,'post_status'=>'draft']);update_post_meta($id,'_lmh_verified_level','unverified');}
    wp_safe_redirect(admin_url('admin.php?page=lmh-control')); exit;
  }

  public static function admin_booking_status() {
    if(!current_user_can('manage_options')) wp_die('Not allowed.');
    $id=absint($_POST['booking_id']??0); check_admin_referer('lmh_booking_'.$id);
    $status=sanitize_key($_POST['status']??'');
    if(get_post_type($id)==='lmh_booking' && in_array($status,self::BOOKING_STATUSES,true)){update_post_meta($id,'_lmh_status',$status);update_post_meta($id,'_lmh_status_updated_at',current_time('mysql'));update_post_meta($id,'_lmh_status_updated_by',get_current_user_id());}
    wp_safe_redirect(admin_url('admin.php?page=lmh-control')); exit;
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
