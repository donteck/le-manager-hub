<?php
/**
 * Embedded Core: Le Manager Hub
 * Description: Core backend for Le Manager Hub: profiles, fans, companies, events, opportunities, bookings, media and REST API.
 * Version: 0.1.0
 * Author: Le Manager Hub
 */
if (!defined('ABSPATH')) exit;

final class LMH_Theme_Core {
  const NS = 'lmh/v1';

  public static function init() {
    add_action('init', [__CLASS__, 'register_content']);
    add_action('init', [__CLASS__, 'register_roles']);
    add_action('rest_api_init', [__CLASS__, 'routes']);
    add_action('wp_enqueue_scripts', [__CLASS__, 'frontend_config']);
    add_action('save_post', [__CLASS__, 'protect_booking_owner'], 10, 3);
  }

  public static function activate() {
    self::register_roles();
    self::register_content();
    flush_rewrite_rules();
  }

  public static function deactivate() { flush_rewrite_rules(); }

  public static function register_roles() {
    add_role('lmh_fan', 'LM Fan', ['read'=>true]);
    add_role('lmh_professional', 'LM Professional', ['read'=>true,'upload_files'=>true]);
    add_role('lmh_company', 'LM Company', ['read'=>true,'upload_files'=>true]);
  }

  private static function cpt($type,$singular,$plural,$public=true,$rest=true) {
    register_post_type($type, [
      'labels'=>['name'=>$plural,'singular_name'=>$singular,'add_new_item'=>"Add $singular",'edit_item'=>"Edit $singular"],
      'public'=>$public,'show_ui'=>true,'show_in_rest'=>$rest,'has_archive'=>$public,
      'rewrite'=>$public ? ['slug'=>str_replace('lmh_','',$type)] : false,
      'supports'=>['title','editor','thumbnail','author','excerpt','custom-fields'],
      'capabilities'=>$type==='lmh_booking' ? ['edit_posts'=>'manage_options','edit_others_posts'=>'manage_options','publish_posts'=>'manage_options','read_private_posts'=>'manage_options','delete_posts'=>'manage_options','create_posts'=>'manage_options'] : [],
      'map_meta_cap'=>true,'menu_icon'=>'dashicons-groups'
    ]);
  }

  public static function register_content() {
    self::cpt('lmh_artist','Artist','Artists');
    self::cpt('lmh_professional','Professional','Professionals');
    self::cpt('lmh_company','Company','Companies');
    self::cpt('lmh_event','Event','Events');
    self::cpt('lmh_opportunity','Opportunity','Opportunities');
    self::cpt('lmh_booking','Booking','Bookings',false,false);
    self::cpt('lmh_tv','TV Program','TV');
    self::cpt('lmh_radio','Radio Program','Radio');
    self::cpt('lmh_podcast','Podcast Episode','Podcasts');

    $taxes = [
      'lmh_profession'=>['Profession',['lmh_professional','lmh_artist']],
      'lmh_genre'=>['Genre',['lmh_artist','lmh_event','lmh_tv','lmh_radio','lmh_podcast']],
      'lmh_country'=>['Country',['lmh_artist','lmh_professional','lmh_company','lmh_event','lmh_opportunity']],
      'lmh_event_type'=>['Event Type',['lmh_event']],
      'lmh_opportunity_type'=>['Opportunity Type',['lmh_opportunity']]
    ];
    foreach($taxes as $slug=>$cfg) register_taxonomy($slug,$cfg[1],[
      'label'=>$cfg[0],'public'=>true,'show_in_rest'=>true,'hierarchical'=>true,
      'rewrite'=>['slug'=>str_replace('lmh_','',$slug)]
    ]);

    $meta = [
      'lmh_artist'=>['city','website','spotify','apple_music','youtube','instagram','tiktok','facebook','x','booking_email','phone','services','languages','availability','achievements','portfolio_url','cover_image_url','verified_level'],
      'lmh_professional'=>['city','company','website','instagram','tiktok','facebook','x','booking_email','phone','services','languages','availability','achievements','portfolio_url','cover_image_url','verified_level'],
      'lmh_company'=>['city','website','email','phone','instagram','tiktok','facebook','x','services','languages','availability','achievements','portfolio_url','cover_image_url','verified_level'],
      'lmh_event'=>['start_datetime','end_datetime','venue','city','ticket_url','age_requirement'],
      'lmh_opportunity'=>['deadline','city','compensation','application_url'],
      'lmh_booking'=>['requester_id','talent_id','event_id','event_date','status','budget','message'],
      'lmh_tv'=>['video_url','air_datetime'],
      'lmh_radio'=>['stream_url','air_datetime'],
      'lmh_podcast'=>['audio_url','episode_number','published_datetime']
    ];
    foreach($meta as $post_type=>$keys) foreach($keys as $key) {
      register_post_meta($post_type, '_lmh_'.$key, [
        'type'=>'string','single'=>true,'show_in_rest'=>true,
        'sanitize_callback'=>'sanitize_text_field',
        'auth_callback'=>function($allowed,$key,$id){ return current_user_can('edit_post',$id); }
      ]);
    }
  }

  public static function routes() {
    register_rest_route(self::NS,'/register',[
      'methods'=>'POST','callback'=>[__CLASS__,'register_user'],'permission_callback'=>'__return_true'
    ]);
    register_rest_route(self::NS,'/me',[
      'methods'=>'GET','callback'=>[__CLASS__,'me'],'permission_callback'=>function(){return is_user_logged_in();}
    ]);
    register_rest_route(self::NS,'/directory',[
      'methods'=>'GET','callback'=>[__CLASS__,'directory'],'permission_callback'=>'__return_true'
    ]);
    register_rest_route(self::NS,'/bookings',[
      'methods'=>'POST','callback'=>[__CLASS__,'create_booking'],'permission_callback'=>function(){return is_user_logged_in();}
    ]);
    register_rest_route(self::NS,'/bookings/mine',[
      'methods'=>'GET','callback'=>[__CLASS__,'my_bookings'],'permission_callback'=>function(){return is_user_logged_in();}
    ]);
    register_rest_route(self::NS,'/follow/(?P<artist_id>\d+)',[
      'methods'=>'POST','callback'=>[__CLASS__,'follow_artist'],'permission_callback'=>function(){return is_user_logged_in();}
    ]);
  }

  public static function register_user(WP_REST_Request $r) {
    if (!get_option('users_can_register')) return new WP_Error('registration_closed','Registration is currently closed.',['status'=>403]);
    $email = sanitize_email($r['email']);
    $password = (string)$r['password'];
    $name = sanitize_text_field($r['name']);
    $kind = sanitize_key($r['account_type']);
    if (!is_email($email)) return new WP_Error('invalid_email','Enter a valid email.',['status'=>400]);
    if (strlen($password)<10) return new WP_Error('weak_password','Password must be at least 10 characters.',['status'=>400]);
    if (email_exists($email)) return new WP_Error('exists','An account already exists for this email.',['status'=>409]);
    $role = ['fan'=>'lmh_fan','professional'=>'lmh_professional','company'=>'lmh_company'][$kind] ?? 'lmh_fan';
    $base = sanitize_user(strtok($email,'@'),true) ?: 'member';
    $username=$base; $i=1; while(username_exists($username)) $username=$base.$i++;
    $id = wp_create_user($username,$password,$email);
    if (is_wp_error($id)) return $id;
    wp_update_user(['ID'=>$id,'display_name'=>$name ?: $username,'role'=>$role]);
    return new WP_REST_Response(['success'=>true,'user_id'=>$id,'role'=>$role],201);
  }

  public static function me() {
    $u=wp_get_current_user();
    return ['id'=>$u->ID,'name'=>$u->display_name,'email'=>$u->user_email,'roles'=>$u->roles,
      'following'=>array_values(array_filter(array_map('intval',(array)get_user_meta($u->ID,'lmh_following',true))))];
  }

  public static function directory(WP_REST_Request $r) {
    $type = sanitize_key($r->get_param('type') ?: 'lmh_professional');
    $allowed=['lmh_artist','lmh_professional','lmh_company'];
    if(!in_array($type,$allowed,true)) $type='lmh_professional';
    $tax_query=['relation'=>'AND'];
    foreach(['country'=>'lmh_country','genre'=>'lmh_genre','profession'=>'lmh_profession'] as $param=>$taxonomy){
      $term=sanitize_text_field($r->get_param($param)??'');
      if($term!=='')$tax_query[]=['taxonomy'=>$taxonomy,'field'=>'name','terms'=>[$term]];
    }
    $q = new WP_Query([
      'post_type'=>$type,'post_status'=>'publish','posts_per_page'=>min(50,max(1,(int)($r['per_page']?:20))),
      'paged'=>max(1,(int)($r['page']?:1)),'s'=>sanitize_text_field($r['search']),
      'tax_query'=>count($tax_query)>1?$tax_query:[]
    ]);
    $items=array_map(function($p){return [
      'id'=>$p->ID,'name'=>get_the_title($p),'url'=>get_permalink($p),
      'excerpt'=>get_the_excerpt($p),'image'=>get_the_post_thumbnail_url($p,'medium'),
      'verified'=>get_post_meta($p->ID,'_lmh_verified_level',true)
    ];},$q->posts);
    return ['items'=>$items,'total'=>(int)$q->found_posts,'pages'=>(int)$q->max_num_pages];
  }

  public static function create_booking(WP_REST_Request $r) {
    $talent=(int)$r['talent_id']; $date=sanitize_text_field($r['event_date']);
    if (!in_array(get_post_type($talent),['lmh_artist','lmh_professional','lmh_company'],true) || get_post_status($talent)!=='publish') return new WP_Error('missing_talent','Select a published artist, professional or company.',['status'=>400]);
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/',$date) || !strtotime($date) || date('Y-m-d',strtotime($date))!==$date || $date<current_time('Y-m-d')) return new WP_Error('invalid_date','Choose a valid future event date.',['status'=>400]);
    if ((int)$r['event_id'] && (get_post_type((int)$r['event_id'])!=='lmh_event' || get_post_status((int)$r['event_id'])!=='publish')) return new WP_Error('invalid_event','Event not found.',['status'=>400]);
    $id=wp_insert_post(['post_type'=>'lmh_booking','post_status'=>'private',
      'post_title'=>'Booking request '.current_time('Y-m-d H:i:s'),'post_author'=>get_current_user_id()],true);
    if(is_wp_error($id)) return $id;
    update_post_meta($id,'_lmh_requester_id',get_current_user_id());
    update_post_meta($id,'_lmh_talent_id',$talent);
    update_post_meta($id,'_lmh_event_id',(int)$r['event_id']);
    update_post_meta($id,'_lmh_event_date',$date);
    update_post_meta($id,'_lmh_budget',sanitize_text_field($r['budget']));
    update_post_meta($id,'_lmh_message',sanitize_textarea_field($r['message']));
    update_post_meta($id,'_lmh_status','pending');
    return new WP_REST_Response(['success'=>true,'booking_id'=>$id,'status'=>'pending'],201);
  }

  public static function my_bookings() {
    $q=new WP_Query(['post_type'=>'lmh_booking','post_status'=>'private','author'=>get_current_user_id(),'posts_per_page'=>50]);
    return array_map(function($p){return [
      'id'=>$p->ID,'talent_id'=>(int)get_post_meta($p->ID,'_lmh_talent_id',true),
      'event_date'=>get_post_meta($p->ID,'_lmh_event_date',true),
      'budget'=>get_post_meta($p->ID,'_lmh_budget',true),
      'status'=>get_post_meta($p->ID,'_lmh_status',true)
    ];},$q->posts);
  }

  public static function follow_artist(WP_REST_Request $r) {
    $uid=get_current_user_id(); $aid=(int)$r['artist_id'];
    if(get_post_type($aid)!=='lmh_artist' || get_post_status($aid)!=='publish') return new WP_Error('not_artist','Artist not found.',['status'=>404]);
    $following=(array)get_user_meta($uid,'lmh_following',true);
    $following=array_values(array_filter(array_unique(array_map('intval',$following))));
    if(in_array($aid,$following,true)) $following=array_values(array_diff($following,[$aid]));
    else $following[]=$aid;
    update_user_meta($uid,'lmh_following',$following);
    return ['artist_id'=>$aid,'following'=>in_array($aid,$following,true),'following_ids'=>$following];
  }

  public static function protect_booking_owner($post_id,$post,$update) {
    if($post->post_type!=='lmh_booking' || wp_is_post_revision($post_id)) return;
    if(!get_post_meta($post_id,'_lmh_status',true)) update_post_meta($post_id,'_lmh_status','pending');
  }

  public static function frontend_config() {
    wp_register_script('lmh-api-config','',[], '0.1.0', true);
    wp_enqueue_script('lmh-api-config');
    wp_add_inline_script('lmh-api-config','window.LMH='.wp_json_encode([
      'api'=>esc_url_raw(rest_url(self::NS.'/')),'nonce'=>wp_create_nonce('wp_rest'),
      'loggedIn'=>is_user_logged_in()
    ]).';','before');
  }
}
LMH_Theme_Core::init();



