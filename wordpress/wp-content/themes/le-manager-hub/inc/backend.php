<?php
if (!defined('ABSPATH')) exit;

/**
 * Phase 1 backend foundation: member roles, profile ownership,
 * verification, booking workflow and admin visibility.
 */
final class LMH_Backend {
  const BOOKING_STATUSES = ['new','reviewing','accepted','declined','confirmed','completed','cancelled'];
  const MEMBER_LEVELS = [
    1=>'fan',2=>'connector',3=>'insider',4=>'supporter',
    5=>'ambassador',6=>'elite_ambassador',7=>'vip'
  ];

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
    add_action('admin_post_lmh_create_qr_campaign',[__CLASS__,'admin_create_qr_campaign']);
    add_action('admin_post_lmh_qr_campaign_status',[__CLASS__,'admin_qr_campaign_status']);
    add_action('admin_post_lmh_delete_qr_campaign',[__CLASS__,'admin_delete_qr_campaign']);
    add_action('admin_post_lmh_qr_image',[__CLASS__,'admin_qr_image']);
    add_action('admin_post_lmh_member_level',[__CLASS__,'admin_member_level']);
    add_action('admin_post_lmh_save_level_rules',[__CLASS__,'admin_save_level_rules']);
    add_action('admin_post_lmh_save_level_benefits',[__CLASS__,'admin_save_level_benefits']);
    add_action('admin_post_lmh_save_partner',[__CLASS__,'admin_save_partner']);
    add_action('admin_post_lmh_partner_status',[__CLASS__,'admin_partner_status']);
    add_action('admin_post_lmh_delete_partner',[__CLASS__,'admin_delete_partner']);
    add_action('admin_post_lmh_redeem_partner_offer',[__CLASS__,'admin_redeem_partner_offer']);
    add_action('admin_post_lmh_card_status',[__CLASS__,'admin_card_status']);
    add_action('template_redirect',[__CLASS__,'qr_routes']);
    add_action('init',[__CLASS__,'register_qr_campaign']);
  }

  public static function register_qr_campaign() {
    register_post_type('lmh_qr_campaign',[
      'labels'=>['name'=>'QR Campaigns','singular_name'=>'QR Campaign'],
      'public'=>false,'show_ui'=>true,'show_in_menu'=>false,'supports'=>['title'],
      'capability_type'=>'post','map_meta_cap'=>true
    ]);
  }

  private static function unique_campaign_code() {
    for($i=0;$i<30;$i++){
      $code=strtoupper(wp_generate_password(8,false,false));
      $q=new WP_Query(['post_type'=>'lmh_qr_campaign','post_status'=>'any','posts_per_page'=>1,'fields'=>'ids','meta_key'=>'_lmh_qr_code','meta_value'=>$code]);
      if(!$q->have_posts()) return $code;
    }
    return strtoupper(wp_generate_password(12,false,false));
  }

  public static function campaign_url($campaign_id) {
    $code=(string)get_post_meta($campaign_id,'_lmh_qr_code',true);
    return $code?add_query_arg('lmh_campaign',$code,home_url('/')):'';
  }

  public static function admin_create_qr_campaign() {
    if(!current_user_can('manage_options')) wp_die('Not allowed.');
    check_admin_referer('lmh_create_qr_campaign');
    $label=sanitize_text_field($_POST['label']??'');
    $artist=absint($_POST['artist_id']??0);
    $referrer=absint($_POST['referrer_user_id']??0);
    if(!$label) $label='QR Campaign '.current_time('Y-m-d H:i');
    if($artist && (get_post_type($artist)!=='lmh_artist'||get_post_status($artist)!=='publish')) $artist=0;
    if($referrer && !get_user_by('id',$referrer)) $referrer=0;
    $id=wp_insert_post(['post_type'=>'lmh_qr_campaign','post_status'=>'publish','post_title'=>$label],true);
    if(!is_wp_error($id)){
      update_post_meta($id,'_lmh_qr_code',self::unique_campaign_code());
      update_post_meta($id,'_lmh_qr_artist_id',$artist);
      update_post_meta($id,'_lmh_qr_referrer_user_id',$referrer);
      update_post_meta($id,'_lmh_qr_scans',0);
      update_post_meta($id,'_lmh_qr_joins',0);
      update_post_meta($id,'_lmh_qr_status','active');
    }
    wp_safe_redirect(admin_url('admin.php?page=lmh-control#smart-qr'));exit;
  }

  public static function admin_qr_campaign_status() {
    if(!current_user_can('manage_options')) wp_die('Not allowed.');$id=absint($_POST['campaign_id']??0);check_admin_referer('lmh_qr_campaign_status_'.$id);
    if(get_post_type($id)!=='lmh_qr_campaign') wp_die('Campaign not found.');
    update_post_meta($id,'_lmh_qr_status',($_POST['status']??'')==='active'?'active':'inactive');
    wp_safe_redirect(admin_url('admin.php?page=lmh-control#smart-qr'));exit;
  }
  public static function admin_delete_qr_campaign() {
    if(!current_user_can('manage_options')) wp_die('Not allowed.');$id=absint($_POST['campaign_id']??0);check_admin_referer('lmh_delete_qr_campaign_'.$id);
    if(get_post_type($id)!=='lmh_qr_campaign') wp_die('Campaign not found.');wp_trash_post($id);
    wp_safe_redirect(admin_url('admin.php?page=lmh-control#smart-qr'));exit;
  }

  public static function qr_image_url($campaign_id) {
    return wp_nonce_url(admin_url('admin-post.php?action=lmh_qr_image&campaign_id='.absint($campaign_id)),'lmh_qr_image_'.absint($campaign_id));
  }

  public static function admin_qr_image() {
    if(!current_user_can('manage_options')) wp_die('Not allowed.');
    $id=absint($_GET['campaign_id']??0);
    check_admin_referer('lmh_qr_image_'.$id);
    if(get_post_type($id)!=='lmh_qr_campaign') wp_die('Campaign not found.');
    $url=self::campaign_url($id);if(!$url) wp_die('Campaign URL missing.');
    $code=(string)get_post_meta($id,'_lmh_qr_code',true);$title=get_the_title($id);
    nocache_headers();header('Content-Type: text/html; charset=UTF-8');
    echo '<!doctype html><html><head><meta charset="utf-8"><title>'.esc_html($title).' — Smart QR</title><meta name="viewport" content="width=device-width,initial-scale=1"><style>body{margin:0;background:#111;font-family:Arial,sans-serif}.card{box-sizing:border-box;width:900px;min-height:1100px;margin:30px auto;padding:54px;background:#fff;color:#111;text-align:center;border-radius:28px}.brand{font-size:42px;font-weight:900;letter-spacing:2px}.sub{font-size:20px;letter-spacing:3px;margin-top:8px}.title{font-size:30px;font-weight:800;margin:58px 0 26px}.qr{display:inline-block;padding:24px;background:#fff;border:3px solid #111;border-radius:22px}.code{font-size:18px;font-weight:800;letter-spacing:2px;margin:22px}.url{font-size:14px;overflow-wrap:anywhere;margin:28px auto;max-width:720px}.cta{font-size:20px;font-weight:900;letter-spacing:2px;margin-top:38px}.actions{margin:22px;text-align:center}.actions button{padding:12px 20px;border:0;border-radius:999px;font-weight:800;cursor:pointer}@media(max-width:950px){.card{width:calc(100% - 24px);min-height:auto;margin:12px;padding:28px}.brand{font-size:30px}.qr canvas,.qr img{max-width:100%;height:auto!important}}@media print{body{background:#fff}.actions{display:none}.card{margin:0;border-radius:0}}</style></head><body><div class="actions"><button onclick="window.print()">PRINT / SAVE PDF</button></div><main class="card"><div class="brand">LE MANAGER</div><div class="sub">WORLD MUSIC INDUSTRY</div><div class="title">'.esc_html($title).'</div><div id="lmh-campaign-qr" class="qr" data-qr="'.esc_attr($url).'"></div><div class="code">CAMPAIGN '.esc_html($code).'</div><div class="url">'.esc_html($url).'</div><div class="cta">SCAN • JOIN • CONNECT</div></main><script src="'.esc_url(get_template_directory_uri().'/assets/js/vendor/qrcode.min.js').'"></script><script>document.addEventListener("DOMContentLoaded",function(){var e=document.getElementById("lmh-campaign-qr");if(e&&window.QRCode)new QRCode(e,{text:e.dataset.qr,width:580,height:580,colorDark:"#000000",colorLight:"#ffffff",correctLevel:QRCode.CorrectLevel.H});});</script></body></html>';
    exit;
  }

  private static function campaign_by_code($code) {
    $q=new WP_Query(['post_type'=>'lmh_qr_campaign','post_status'=>'publish','posts_per_page'=>1,'meta_key'=>'_lmh_qr_code','meta_value'=>sanitize_text_field($code)]);
    return $q->have_posts()?$q->posts[0]:null;
  }

  public static function record_campaign_join($campaign_id,$user_id) {
    if(!$campaign_id||get_post_type($campaign_id)!=='lmh_qr_campaign') return;
    update_post_meta($campaign_id,'_lmh_qr_joins',(int)get_post_meta($campaign_id,'_lmh_qr_joins',true)+1);
    update_user_meta($user_id,'_lmh_join_campaign_id',$campaign_id);
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

  public static function member_level_number($user_id) {
    $level=sanitize_key(get_user_meta($user_id,'_lmh_member_level',true)?:'fan');
    $n=array_search($level,self::MEMBER_LEVELS,true);
    return $n?(int)$n:1;
  }

  public static function set_member_level($user_id,$level,$source='admin') {
    $level=is_numeric($level)?(int)$level:sanitize_key($level);
    if(is_int($level)) $slug=self::MEMBER_LEVELS[$level]??'';
    else {$slug=$level;$level=array_search($slug,self::MEMBER_LEVELS,true);}
    if(!$slug || !$level) return false;
    $old=sanitize_key(get_user_meta($user_id,'_lmh_member_level',true)?:'fan');
    if($old===$slug) return true;
    update_user_meta($user_id,'_lmh_member_level',$slug);
    update_user_meta($user_id,'_lmh_member_level_number',(int)$level);
    update_user_meta($user_id,'_lmh_member_level_updated_at',current_time('mysql'));
    $history=get_user_meta($user_id,'_lmh_member_level_history',true);
    if(!is_array($history)) $history=[];
    $history[]=['from'=>$old,'to'=>$slug,'at'=>current_time('mysql'),'source'=>sanitize_key($source)];
    if(count($history)>50) $history=array_slice($history,-50);
    update_user_meta($user_id,'_lmh_member_level_history',$history);
    return true;
  }

  public static function referral_stats($user_id) {
    $direct=get_users(['fields'=>'ids','meta_key'=>'_lmh_referrer_user_id','meta_value'=>absint($user_id),'number'=>-1]);
    return ['direct_count'=>count($direct),'direct_user_ids'=>array_map('intval',$direct)];
  }

  public static function referral_history($user_id,$limit=50) {
    $ids=get_users(['fields'=>'ids','meta_key'=>'_lmh_referrer_user_id','meta_value'=>absint($user_id),'number'=>max(1,min(100,absint($limit))),'orderby'=>'registered','order'=>'DESC']);
    $items=[];
    foreach($ids as $id){
      $u=get_user_by('id',$id);if(!$u)continue;$m=self::member_identity($id);
      $items[]=['user_id'=>$id,'name'=>$u->display_name,'lmid'=>$m['id'],'level'=>$m['level'],'level_number'=>$m['level_number'],'joined'=>get_user_meta($id,'_lmh_join_at',true)?:$u->user_registered,'source'=>sanitize_key(get_user_meta($id,'_lmh_join_source',true)?:'referral')];
    }
    return $items;
  }

  public static function referral_tree($user_id,$max_depth=7,$depth=1,$seen=[]) {
    $user_id=absint($user_id);$max_depth=max(1,min(7,absint($max_depth)));
    if(!$user_id || $depth>$max_depth || in_array($user_id,$seen,true)) return [];
    $seen[]=$user_id;
    $children=get_users(['fields'=>'ids','meta_key'=>'_lmh_referrer_user_id','meta_value'=>$user_id,'number'=>-1,'orderby'=>'registered','order'=>'ASC']);
    $nodes=[];
    foreach($children as $cid){
      $u=get_user_by('id',$cid);if(!$u)continue;
      $m=self::member_identity($cid);
      $nodes[]=['user_id'=>$cid,'name'=>$u->display_name,'lmid'=>$m['id'],'level'=>$m['level'],'level_number'=>$m['level_number'],'joined'=>$u->user_registered,'children'=>self::referral_tree($cid,$max_depth,$depth+1,$seen)];
    }
    return $nodes;
  }

  public static function network_stats($user_id,$max_depth=7) {
    $tree=self::referral_tree($user_id,$max_depth);$total=0;$by_depth=[];
    $walk=function($nodes,$depth) use (&$walk,&$total,&$by_depth){foreach($nodes as $n){$total++;$by_depth[$depth]=($by_depth[$depth]??0)+1;if(!empty($n['children']))$walk($n['children'],$depth+1);}};
    $walk($tree,1);
    return ['total_network'=>$total,'by_depth'=>$by_depth,'tree'=>$tree];
  }

  private static function render_network_nodes($nodes,$depth=1) {
    if(!$nodes) return '<em>No referrals yet.</em>';
    $html='<ul style="margin:8px 0 8px 22px">';
    foreach($nodes as $n){$html.='<li style="margin:8px 0"><strong>'.esc_html($n['name']).'</strong> <code>'.esc_html($n['lmid']).'</code> <span>Level '.esc_html($n['level_number']).' — '.esc_html(ucwords(str_replace('_',' ',$n['level']))).'</span>';if(!empty($n['children']))$html.=self::render_network_nodes($n['children'],$depth+1);$html.='</li>';}
    return $html.'</ul>';
  }

  public static function level_rules() {
    $saved=get_option('lmh_member_level_rules',[]);
    $rules=[];
    foreach(self::MEMBER_LEVELS as $n=>$slug){
      $rules[$n]=['referrals'=>0,'events'=>0,'engagement'=>0];
      if(isset($saved[$n])&&is_array($saved[$n])) foreach($rules[$n] as $k=>$v)$rules[$n][$k]=max(0,absint($saved[$n][$k]??0));
    }
    return $rules;
  }

  public static function partners() {
    $saved=get_option('lmh_partner_network',[]);return is_array($saved)?$saved:[];
  }
  public static function partner_redemption_stats($partner_id) {
    $partner_id=sanitize_key($partner_id);$log=get_option('lmh_partner_redemptions',[]);$count=0;$members=[];
    foreach((array)$log as $row)if(($row['partner_id']??'')===$partner_id){$count++;if(!empty($row['user_id']))$members[(int)$row['user_id']]=1;}
    return ['redemptions'=>$count,'unique_members'=>count($members)];
  }
  public static function admin_redeem_partner_offer() {
    if(!is_user_logged_in()) auth_redirect();$uid=get_current_user_id();$id=sanitize_key($_POST['partner_id']??'');check_admin_referer('lmh_redeem_partner_'.$id);
    $partners=self::partners();$pt=$partners[$id]??null;$level=self::member_level_number($uid);
    if(!$pt||($pt['status']??'')!=='active'||!in_array($level,array_map('intval',(array)($pt['levels']??[])),true))wp_die('This partner offer is not available for your membership level.');
    $log=get_option('lmh_partner_redemptions',[]);if(!is_array($log))$log=[];
    $today=current_time('Y-m-d');$duplicate=false;foreach($log as $row)if(($row['partner_id']??'')===$id&&(int)($row['user_id']??0)===$uid&&($row['day']??'')===$today){$duplicate=true;break;}
    if(!$duplicate){$log[]=['partner_id'=>$id,'user_id'=>$uid,'level'=>$level,'day'=>$today,'at'=>current_time('mysql')];if(count($log)>5000)$log=array_slice($log,-5000);update_option('lmh_partner_redemptions',$log,false);}
    wp_safe_redirect(add_query_arg('lmh_redeemed',$duplicate?'already':'success',lmh_url('account').'#partner-marketplace'));exit;
  }

  public static function admin_partner_status() {
    if(!current_user_can('manage_options')) wp_die('Not allowed.');$id=sanitize_key($_POST['partner_id']??'');check_admin_referer('lmh_partner_status_'.$id);
    $partners=self::partners();if(isset($partners[$id])){$partners[$id]['status']=($_POST['status']??'')==='active'?'active':'inactive';update_option('lmh_partner_network',$partners,false);}
    wp_safe_redirect(admin_url('admin.php?page=lmh-control#partner-network'));exit;
  }
  public static function admin_delete_partner() {
    if(!current_user_can('manage_options')) wp_die('Not allowed.');$id=sanitize_key($_POST['partner_id']??'');check_admin_referer('lmh_delete_partner_'.$id);
    $partners=self::partners();if(isset($partners[$id])){unset($partners[$id]);update_option('lmh_partner_network',$partners,false);}
    wp_safe_redirect(admin_url('admin.php?page=lmh-control#partner-network'));exit;
  }

  public static function admin_save_partner() {
    if(!current_user_can('manage_options')) wp_die('Not allowed.');check_admin_referer('lmh_save_partner');
    $name=sanitize_text_field($_POST['name']??'');if(!$name) wp_safe_redirect(admin_url('admin.php?page=lmh-control#partner-network'));
    $partners=self::partners();$id=sanitize_key($_POST['partner_id']??'');if(!$id)$id='partner_'.wp_generate_password(10,false,false);
    $partners[$id]=['name'=>$name,'category'=>sanitize_text_field($_POST['category']??''),'location'=>sanitize_text_field($_POST['location']??''),'website'=>esc_url_raw($_POST['website']??''),'offer'=>sanitize_textarea_field($_POST['offer']??''),'levels'=>array_values(array_filter(array_map('absint',(array)($_POST['levels']??[])),fn($n)=>$n>=1&&$n<=7)),'status'=>in_array(($_POST['status']??''),['active','inactive'],true)?$_POST['status']:'active'];
    update_option('lmh_partner_network',$partners,false);wp_safe_redirect(admin_url('admin.php?page=lmh-control#partner-network'));exit;
  }

  public static function level_benefits() {
    $saved=get_option('lmh_member_level_benefits',[]);$out=[];
    foreach(self::MEMBER_LEVELS as $n=>$slug){$row=is_array($saved[$n]??null)?$saved[$n]:[];$out[$n]=['title'=>sanitize_text_field($row['title']??ucwords(str_replace('_',' ',$slug))),'benefits'=>sanitize_textarea_field($row['benefits']??''),'discount'=>sanitize_text_field($row['discount']??''),'rewards'=>sanitize_textarea_field($row['rewards']??'')];}
    return $out;
  }
  public static function member_benefits($user_id) {$level=self::member_level_number($user_id);$all=self::level_benefits();return ['level'=>$level,'current'=>$all[$level],'all'=>$all];}

  public static function member_activity($user_id) {
    $r=self::referral_stats($user_id);
    return ['referrals'=>$r['direct_count'],'events'=>max(0,(int)get_user_meta($user_id,'_lmh_events_attended',true)),'engagement'=>max(0,(int)get_user_meta($user_id,'_lmh_engagement_points',true))];
  }

  public static function qualification_progress($user_id) {
    $current=self::member_level_number($user_id);$next=$current<7?$current+1:7;$rules=self::level_rules();$activity=self::member_activity($user_id);$req=$rules[$next];
    $configured=array_sum($req)>0;$met=true;$parts=[];
    foreach($req as $k=>$needed){$have=$activity[$k]??0;$ok=$needed===0||$have>=$needed;if(!$ok)$met=false;$parts[$k]=['have'=>$have,'needed'=>$needed,'met'=>$ok];}
    return ['current'=>$current,'next'=>$next,'next_slug'=>self::MEMBER_LEVELS[$next],'configured'=>$configured,'qualified'=>$current===7?true:($configured&&$met),'requirements'=>$parts];
  }

  public static function card_eligibility($user_id) {
    $level=self::member_level_number($user_id);
    $eligible=$level>=5;
    $status=sanitize_key(get_user_meta($user_id,'_lmh_physical_card_status',true)?:($eligible?'eligible':'not_eligible'));
    return ['eligible'=>$eligible,'status'=>$status,'minimum_level'=>5];
  }

  public static function member_growth_tools($user_id) {
    $level=self::member_level_number($user_id);
    return [
      'enabled'=>true,
      'recruitment_url'=>self::recruitment_url($user_id),
      'smart_qr_url'=>self::member_smart_qr_url($user_id),
      'qr_image_url'=>self::member_qr_image_url($user_id),
      'qr_scans'=>max(0,(int)get_user_meta($user_id,'_lmh_member_qr_scans',true)),
      'qr_joins'=>max(0,(int)get_user_meta($user_id,'_lmh_member_qr_joins',true)),
      'qr_conversion_rate'=>($scans=max(0,(int)get_user_meta($user_id,'_lmh_member_qr_scans',true)))?round((max(0,(int)get_user_meta($user_id,'_lmh_member_qr_joins',true))/$scans)*100,1):0,
      'card'=>self::card_eligibility($user_id),
      'network'=>self::network_stats($user_id,7),
      'referrals'=>self::referral_stats($user_id),
      'referral_history'=>self::referral_history($user_id,50)
    ];
  }

  public static function ambassador_tools($user_id) {
    $tools=self::member_growth_tools($user_id);
    $tools['enabled']=self::member_level_number($user_id)>=5;
    return $tools;
  }

  public static function ensure_member_identity_on_login($login,$user) {
    self::assign_member_identity($user->ID);
  }

  public static function member_identity($user_id) {
    self::assign_member_identity($user_id);
    return [
      'id'=>(string)get_user_meta($user_id,'_lmh_member_id',true),
      'level'=>sanitize_key(get_user_meta($user_id,'_lmh_member_level',true)?:'fan'),
      'level_number'=>self::member_level_number($user_id),
      'status'=>sanitize_key(get_user_meta($user_id,'_lmh_member_status',true)?:'active'),
      'since'=>(string)get_user_meta($user_id,'_lmh_member_since',true),
      'card_status'=>sanitize_key(get_user_meta($user_id,'_lmh_card_status',true)?:'digital'),
    ];
  }

  private static function qr_token($user_id) {
    $token=(string)get_user_meta($user_id,'_lmh_qr_token',true);
    if(!$token){$token=wp_generate_password(40,false,false);update_user_meta($user_id,'_lmh_qr_token',$token);}
    return $token;
  }

  private static function member_referral_code($user_id) {
    $code=(string)get_user_meta($user_id,'_lmh_referral_code',true);
    if(!$code){
      for($i=0;$i<30;$i++){
        $candidate=strtolower(wp_generate_password(16,false,false));
        $users=get_users(['fields'=>'ids','number'=>1,'meta_key'=>'_lmh_referral_code','meta_value'=>$candidate]);
        if(!$users){$code=$candidate;break;}
      }
      if(!$code)$code=strtolower(wp_generate_password(24,false,false));
      update_user_meta($user_id,'_lmh_referral_code',$code);
    }
    return $code;
  }

  private static function member_by_referral_code($code) {
    $users=get_users(['number'=>1,'meta_key'=>'_lmh_referral_code','meta_value'=>sanitize_text_field($code)]);
    return $users?$users[0]:null;
  }

  public static function member_smart_qr_url($user_id) {
    return add_query_arg('lmh_member_qr',self::member_referral_code($user_id),home_url('/'));
  }

  public static function member_qr_image_url($user_id,$size=320) {
    // Kept as an internal compatibility helper. QR rendering is now performed
    // locally by the theme's bundled QRCode.js; no member QR data is sent to a third party.
    return self::member_smart_qr_url($user_id);
  }

  public static function verification_url($user_id) {
    return add_query_arg(['lmh_verify'=>$user_id,'token'=>self::qr_token($user_id)],home_url('/'));
  }

  public static function recruitment_url($user_id,$artist_id=0) {
    $args=['lmh_member_qr'=>self::member_referral_code($user_id)];
    if($artist_id && get_post_type($artist_id)==='lmh_artist') $args['artist']=absint($artist_id);
    return add_query_arg($args,home_url('/'));
  }

  public static function qr_routes() {
    if(isset($_GET['lmh_member_qr'])){
      $code=sanitize_text_field(wp_unslash($_GET['lmh_member_qr']));
      $referrer=self::member_by_referral_code($code);
      if(!$referrer){status_header(404);return;}
      $cookie='lmh_member_qr_seen_'.$referrer->ID;
      if(empty($_COOKIE[$cookie])){
        update_user_meta($referrer->ID,'_lmh_member_qr_scans',(int)get_user_meta($referrer->ID,'_lmh_member_qr_scans',true)+1);
        setcookie($cookie,'1',time()+DAY_IN_SECONDS,COOKIEPATH?:'/',COOKIE_DOMAIN,is_ssl(),true);
      }
      $m=self::member_identity($referrer->ID);
      if(!empty($m['id']))setcookie('lmh_ref',$m['id'],time()+30*DAY_IN_SECONDS,COOKIEPATH?:'/',COOKIE_DOMAIN,is_ssl(),true);
      setcookie('lmh_referral_code',$code,time()+30*DAY_IN_SECONDS,COOKIEPATH?:'/',COOKIE_DOMAIN,is_ssl(),true);
      $artist=absint($_GET['artist']??0);
      if($artist && get_post_type($artist)==='lmh_artist' && get_post_status($artist)==='publish')setcookie('lmh_join_artist',(string)$artist,time()+30*DAY_IN_SECONDS,COOKIEPATH?:'/',COOKIE_DOMAIN,is_ssl(),true);
      $join=lmh_url('join');if($artist)$join=add_query_arg('artist',$artist,$join);
      wp_safe_redirect($join);exit;
    }
    if(isset($_GET['lmh_campaign'])){
      $campaign=self::campaign_by_code(sanitize_text_field(wp_unslash($_GET['lmh_campaign'])));if($campaign && get_post_meta($campaign->ID,'_lmh_qr_status',true)!=='active'){$campaign=null;}
      if(!$campaign || get_post_meta($campaign->ID,'_lmh_qr_status',true)==='inactive'){status_header(404);return;}
      $cookie='lmh_campaign_seen_'.$campaign->ID;
      if(empty($_COOKIE[$cookie])){
        update_post_meta($campaign->ID,'_lmh_qr_scans',(int)get_post_meta($campaign->ID,'_lmh_qr_scans',true)+1);
        $daily=get_post_meta($campaign->ID,'_lmh_qr_daily_scans',true);if(!is_array($daily))$daily=[];$day=current_time('Y-m-d');$daily[$day]=(int)($daily[$day]??0)+1;
        if(count($daily)>90){ksort($daily);$daily=array_slice($daily,-90,null,true);}update_post_meta($campaign->ID,'_lmh_qr_daily_scans',$daily);
        setcookie($cookie,'1',time()+DAY_IN_SECONDS,COOKIEPATH?:'/',COOKIE_DOMAIN,is_ssl(),true);
      }
      setcookie('lmh_campaign_id',(string)$campaign->ID,time()+30*DAY_IN_SECONDS,COOKIEPATH?:'/',COOKIE_DOMAIN,is_ssl(),true);
      $artist=(int)get_post_meta($campaign->ID,'_lmh_qr_artist_id',true);
      $referrer=(int)get_post_meta($campaign->ID,'_lmh_qr_referrer_user_id',true);
      if($referrer){$m=self::member_identity($referrer);if(!empty($m['id']))setcookie('lmh_ref',$m['id'],time()+30*DAY_IN_SECONDS,COOKIEPATH?:'/',COOKIE_DOMAIN,is_ssl(),true);}
      if($artist)setcookie('lmh_join_artist',(string)$artist,time()+30*DAY_IN_SECONDS,COOKIEPATH?:'/',COOKIE_DOMAIN,is_ssl(),true);
      $join=lmh_url('join');if($artist)$join=add_query_arg('artist',$artist,$join);
      wp_safe_redirect($join);exit;
    }
    if(isset($_GET['lmh_verify'])){
      $uid=absint($_GET['lmh_verify']);$token=sanitize_text_field(wp_unslash($_GET['token']??''));$stored=(string)get_user_meta($uid,'_lmh_qr_token',true);
      status_header(200);nocache_headers();get_header();
      echo '<main class="lmh-verify-page"><div class="lmh-verify-shell"><div class="lmh-verify-brand"><span>LE MANAGER</span><small>WORLD MUSIC INDUSTRY</small></div>';
      if($uid&&$stored&&hash_equals($stored,$token)){
        $u=get_user_by('id',$uid);$m=self::member_identity($uid);$mb=self::member_benefits($uid);$active=$m['status']==='active';$level=strtoupper(str_replace('_',' ',$m['level']));$lmid=substr($m['id'],0,3).' '.substr($m['id'],3,3).' '.substr($m['id'],6,3);
        echo '<section class="lmh-verify-pass '.($active?'is-active':'is-inactive').'"><div class="lmh-verify-icon" aria-hidden="true">'.($active?'✓':'!').'</div><div class="lmh-verify-eyebrow">SECURE MEMBER VERIFICATION</div><h1>'.($active?'Membership Verified':'Membership Not Active').'</h1><p class="lmh-verify-lead">'.($active?'This is a valid Le Manager membership credential.':'The credential is valid, but this membership is not currently active.').'</p><div class="lmh-verify-member"><div><span>MEMBER</span><strong>'.esc_html($u?$u->display_name:'Le Manager Member').'</strong></div><div><span>LMID</span><strong>'.esc_html($lmid).'</strong></div><div><span>MEMBERSHIP LEVEL</span><strong>Level '.esc_html($m['level_number']).' · '.esc_html($level).'</strong></div></div><div class="lmh-verify-benefit"><span>PARTNER BENEFIT ELIGIBILITY</span>'.($active&&$mb['current']['discount']?'<strong>'.esc_html($mb['current']['discount']).'</strong><p>Apply the configured Le Manager member benefit according to your partner terms.</p>':'<strong>No discount configured</strong><p>No level-specific partner discount is currently available for this membership.</p>').'</div><div class="lmh-verify-security"><span>✓ Secure QR credential</span><span>✓ Membership identity confirmed</span><span>LMID is not a payment card or password</span></div></section>';
      }else{
        echo '<section class="lmh-verify-pass is-invalid"><div class="lmh-verify-icon" aria-hidden="true">×</div><div class="lmh-verify-eyebrow">SECURE MEMBER VERIFICATION</div><h1>Unable to Verify</h1><p class="lmh-verify-lead">This credential is invalid or can no longer be verified. Do not apply a Le Manager member benefit from this screen.</p><div class="lmh-verify-security"><span>Credential not verified</span><span>Scan the member’s current Le Manager QR again</span></div></section>';
      }
      echo '<p class="lmh-verify-foot">Official Le Manager membership verification · Secure member identity</p></div></main>';get_footer();exit;
    }
    if(isset($_GET['lmh_join'])){
      $ref=preg_replace('/\D/','',(string)($_GET['ref']??''));
      $artist=absint($_GET['artist']??0);
      if(strlen($ref)===9) setcookie('lmh_ref',$ref,time()+30*DAY_IN_SECONDS,COOKIEPATH?:'/',COOKIE_DOMAIN,is_ssl(),true);
      if($artist && get_post_type($artist)==='lmh_artist' && get_post_status($artist)==='publish') setcookie('lmh_join_artist',(string)$artist,time()+30*DAY_IN_SECONDS,COOKIEPATH?:'/',COOKIE_DOMAIN,is_ssl(),true);
      $join=lmh_url('join');
      if($artist) $join=add_query_arg('artist',$artist,$join);
      wp_safe_redirect($join);exit;
    }
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
    $campaigns=new WP_Query(['post_type'=>'lmh_qr_campaign','post_status'=>'publish','posts_per_page'=>25,'orderby'=>'date','order'=>'DESC']);
    $qr_totals=['campaigns'=>0,'scans'=>0,'week'=>0,'joins'=>0];foreach($campaigns->posts as $cq){$qr_totals['campaigns']++;$qr_totals['scans']+=(int)get_post_meta($cq->ID,'_lmh_qr_scans',true);$qr_totals['joins']+=(int)get_post_meta($cq->ID,'_lmh_qr_joins',true);$daily=(array)get_post_meta($cq->ID,'_lmh_qr_daily_scans',true);$cut=wp_date('Y-m-d',current_time('timestamp')-6*DAY_IN_SECONDS);foreach($daily as $d=>$v)if($d>=$cut)$qr_totals['week']+=(int)$v;}$qr_rate=$qr_totals['scans']?round(($qr_totals['joins']/$qr_totals['scans'])*100,1):0;
    echo '<div id="smart-qr" style="margin:28px 0"><h2>Smart QR Campaigns</h2><div style="display:grid;grid-template-columns:repeat(5,minmax(120px,1fr));gap:10px;margin:14px 0 18px"><div class="card"><h3>'.esc_html($qr_totals['campaigns']).'</h3><p>Campaigns</p></div><div class="card"><h3>'.esc_html($qr_totals['scans']).'</h3><p>Total Scans</p></div><div class="card"><h3>'.esc_html($qr_totals['week']).'</h3><p>Last 7 Days</p></div><div class="card"><h3>'.esc_html($qr_totals['joins']).'</h3><p>Successful Joins</p></div><div class="card"><h3>'.esc_html($qr_rate).'%</h3><p>Conversion</p></div></div><form method="post" action="'.esc_url(admin_url('admin-post.php')).'" style="background:#fff;border:1px solid #dcdcde;padding:16px;margin-bottom:16px"><input type="hidden" name="action" value="lmh_create_qr_campaign">'.wp_nonce_field('lmh_create_qr_campaign','_wpnonce',true,false).'<p><input name="label" class="regular-text" placeholder="Campaign name" required> <input name="artist_id" type="number" min="0" placeholder="Artist post ID"> <input name="referrer_user_id" type="number" min="0" placeholder="Member user ID"> <button class="button button-primary">Create Smart QR Campaign</button></p></form>';
    if($campaigns->have_posts()){echo '<table class="widefat striped"><thead><tr><th>Campaign</th><th>Code</th><th>Artist</th><th>Scans</th><th>7 Days</th><th>Joins</th><th>Conversion</th><th>URL / Print</th><th>Status / Actions</th></tr></thead><tbody>';foreach($campaigns->posts as $q){$sc=(int)get_post_meta($q->ID,'_lmh_qr_scans',true);$jo=(int)get_post_meta($q->ID,'_lmh_qr_joins',true);$aid=(int)get_post_meta($q->ID,'_lmh_qr_artist_id',true);$rate=$sc?round(($jo/$sc)*100,1):0;echo '<tr><td>'.esc_html(get_the_title($q)).'</td><td><code>'.esc_html(get_post_meta($q->ID,'_lmh_qr_code',true)).'</code></td><td>'.esc_html($aid?get_the_title($aid):'General').'</td><td>'.esc_html($sc).'</td><td>'.esc_html(array_sum(array_filter((array)get_post_meta($q->ID,'_lmh_qr_daily_scans',true),fn($v,$d)=>$d>=wp_date('Y-m-d',current_time('timestamp')-6*DAY_IN_SECONDS),ARRAY_FILTER_USE_BOTH))).'</td><td>'.esc_html($jo).'</td><td>'.esc_html($rate).'%</td><td><input class="large-text" readonly value="'.esc_attr(self::campaign_url($q->ID)).'"><br><a class="button" style="margin-top:6px" href="'.esc_url(self::qr_image_url($q->ID)).'">OPEN / PRINT QR CARD</a></td><td><form method="post" action="'.esc_url(admin_url('admin-post.php')).'" style="display:inline-flex;gap:5px"><input type="hidden" name="action" value="lmh_qr_campaign_status"><input type="hidden" name="campaign_id" value="'.esc_attr($q->ID).'">'.wp_nonce_field('lmh_qr_campaign_status_'.$q->ID,'_wpnonce',true,false).'<select name="status"><option value="active" '.selected(get_post_meta($q->ID,'_lmh_qr_status',true),'active',false).'>Active</option><option value="inactive" '.selected(get_post_meta($q->ID,'_lmh_qr_status',true),'inactive',false).'>Inactive</option></select><button class="button">Save</button></form><form method="post" action="'.esc_url(admin_url('admin-post.php')).'" style="display:inline-block;margin-left:5px" onsubmit="return confirm(\'Move this campaign to Trash?\')"><input type="hidden" name="action" value="lmh_delete_qr_campaign"><input type="hidden" name="campaign_id" value="'.esc_attr($q->ID).'">'.wp_nonce_field('lmh_delete_qr_campaign_'.$q->ID,'_wpnonce',true,false).'<button class="button button-link-delete">Trash</button></form></td></tr>';}echo '</tbody></table>';}else echo '<p>No Smart QR campaigns yet.</p>';echo '</div>';

    $crm_users=get_users(['number'=>50,'orderby'=>'registered','order'=>'DESC','meta_key'=>'_lmh_join_campaign_id']);
    echo '<div id="fan-crm" style="margin:28px 0"><h2>Fan CRM — Recent QR Members</h2><p>Members attributed to Smart QR campaigns. Contact information is visible only to administrators.</p>';
    if($crm_users){echo '<table class="widefat striped"><thead><tr><th>Member</th><th>LMID</th><th>Email</th><th>Campaign</th><th>Artist</th><th>Referrer</th><th>Joined</th></tr></thead><tbody>';foreach($crm_users as $cu){$cid=(int)get_user_meta($cu->ID,'_lmh_join_campaign_id',true);$aid=(int)get_user_meta($cu->ID,'_lmh_join_artist_id',true);$rid=(int)get_user_meta($cu->ID,'_lmh_referrer_user_id',true);$ru=$rid?get_user_by('id',$rid):false;$lmid=(string)get_user_meta($cu->ID,'_lmh_member_id',true);echo '<tr><td><strong>'.esc_html($cu->display_name).'</strong></td><td>'.esc_html($lmid).'</td><td>'.esc_html($cu->user_email).'</td><td>'.esc_html($cid?get_the_title($cid):'—').'</td><td>'.esc_html($aid?get_the_title($aid):'—').'</td><td>'.esc_html($ru?$ru->display_name:'—').'</td><td>'.esc_html(mysql2date('M j, Y',$cu->user_registered)).'</td></tr>';}echo '</tbody></table>';}else echo '<p>No QR-attributed members yet.</p>';echo '</div>';
    $members=get_users(['number'=>100,'orderby'=>'registered','order'=>'DESC','meta_key'=>'_lmh_member_id']);
    echo '<div id="membership-levels" style="margin:28px 0"><h2>7-Level Membership Engine</h2><p>Fan → Connector → Insider → Supporter → Ambassador → Elite Ambassador → VIP. LMID stays permanent when a member advances.</p>';
    if($members){echo '<table class="widefat striped"><thead><tr><th>Member</th><th>LMID</th><th>Current Level</th><th>Direct Referrals</th><th>Referrer</th><th>Manage Level</th></tr></thead><tbody>';foreach($members as $mu){$mi=self::member_identity($mu->ID);$rs=self::referral_stats($mu->ID);$rid=(int)get_user_meta($mu->ID,'_lmh_referrer_user_id',true);$ru=$rid?get_user_by('id',$rid):false;echo '<tr><td><strong>'.esc_html($mu->display_name).'</strong></td><td>'.esc_html($mi['id']).'</td><td>Level '.esc_html($mi['level_number']).' — '.esc_html(ucwords(str_replace('_',' ',$mi['level']))).'</td><td>'.esc_html($rs['direct_count']).'</td><td>'.esc_html($ru?$ru->display_name:'—').'</td><td><form method="post" action="'.esc_url(admin_url('admin-post.php')).'"><input type="hidden" name="action" value="lmh_member_level"><input type="hidden" name="user_id" value="'.esc_attr($mu->ID).'">'.wp_nonce_field('lmh_member_level_'.$mu->ID,'_wpnonce',true,false).'<select name="level">';foreach(self::MEMBER_LEVELS as $ln=>$ls)echo '<option value="'.esc_attr($ln).'" '.selected($mi['level_number'],$ln,false).'>'.esc_html($ln.' — '.ucwords(str_replace('_',' ',$ls))).'</option>';echo '</select> <button class="button">Update</button></form></td></tr>';}echo '</tbody></table>';}else echo '<p>No LMID members yet.</p>';echo '</div>';
    $roots=get_users(['number'=>50,'orderby'=>'registered','order'=>'ASC','meta_key'=>'_lmh_member_id']);
    $root_ids=[];foreach($roots as $rmu){$rid=(int)get_user_meta($rmu->ID,'_lmh_referrer_user_id',true);if(!$rid)$root_ids[]=$rmu->ID;}
    echo '<div id="each-one-bring-one" style="margin:28px 0"><h2>Each One Bring One — Network Tree</h2><p>Shows verified referral relationships through seven generations. This is attribution and community-growth tracking; membership advancement remains separately managed.</p>';
    if($root_ids){foreach($root_ids as $root_id){$root=get_user_by('id',$root_id);if(!$root)continue;$mi=self::member_identity($root_id);$ns=self::network_stats($root_id,7);echo '<details style="background:#fff;border:1px solid #dcdcde;padding:14px;margin:10px 0"><summary style="cursor:pointer"><strong>'.esc_html($root->display_name).'</strong> — LMID '.esc_html($mi['id']).' — Level '.esc_html($mi['level_number']).' — Network '.esc_html($ns['total_network']).'</summary>'.self::render_network_nodes($ns['tree']).'</details>';}}else echo '<p>No referral network relationships yet.</p>';echo '</div>';
    $ambassadors=get_users(['number'=>100,'orderby'=>'registered','order'=>'DESC','meta_query'=>[['key'=>'_lmh_member_level','value'=>['ambassador','elite_ambassador','vip'],'compare'=>'IN']]]);
    echo '<div id="ambassadors" style="margin:28px 0"><h2>Ambassador & Physical Card Center</h2><p>Levels 5–7 receive Ambassador tools and become eligible for the Le Manager physical membership card.</p>';
    if($ambassadors){echo '<table class="widefat striped"><thead><tr><th>Member</th><th>LMID</th><th>Level</th><th>Direct Referrals</th><th>Network</th><th>Card Status</th><th>Manage Card</th></tr></thead><tbody>';foreach($ambassadors as $au){$mi=self::member_identity($au->ID);$at=self::ambassador_tools($au->ID);echo '<tr><td><strong>'.esc_html($au->display_name).'</strong></td><td>'.esc_html($mi['id']).'</td><td>'.esc_html($mi['level_number'].' — '.ucwords(str_replace('_',' ',$mi['level']))).'</td><td>'.esc_html($at['referrals']['direct_count']).'</td><td>'.esc_html($at['network']['total_network']).'</td><td><strong>'.esc_html(ucwords(str_replace('_',' ',$at['card']['status']))).'</strong></td><td><form method="post" action="'.esc_url(admin_url('admin-post.php')).'"><input type="hidden" name="action" value="lmh_card_status"><input type="hidden" name="user_id" value="'.esc_attr($au->ID).'">'.wp_nonce_field('lmh_card_status_'.$au->ID,'_wpnonce',true,false).'<select name="status">';foreach(['eligible','requested','approved','production','active','suspended','lost','replaced','expired','revoked'] as $cs)echo '<option value="'.esc_attr($cs).'" '.selected($at['card']['status'],$cs,false).'>'.esc_html(ucwords(str_replace('_',' ',$cs))).'</option>';echo '</select> <button class="button">Update</button></form></td></tr>';}echo '</tbody></table>';}else echo '<p>No Level 5–7 members yet.</p>';echo '</div>';
    $partners=self::partners();
    echo '<div id="partner-network" style="margin:28px 0"><h2>Partner Network</h2><p>Register participating businesses and define member offers by membership level.</p><form method="post" action="'.esc_url(admin_url('admin-post.php')).'" style="background:#fff;border:1px solid #dcdcde;padding:18px;margin-bottom:18px"><input type="hidden" name="action" value="lmh_save_partner">'.wp_nonce_field('lmh_save_partner','_wpnonce',true,false).'<p><input required name="name" placeholder="Partner / Business Name" class="regular-text"> <input name="category" placeholder="Category"> <input name="location" placeholder="Location"></p><p><input name="website" type="url" placeholder="Website" class="regular-text"> <select name="status"><option value="active">Active</option><option value="inactive">Inactive</option></select></p><p><textarea name="offer" rows="3" style="width:100%" placeholder="Member offer / discount"></textarea></p><p><strong>Eligible Levels:</strong> ';foreach(self::MEMBER_LEVELS as $ln=>$ls)echo '<label style="margin-right:12px"><input type="checkbox" name="levels[]" value="'.esc_attr($ln).'"> L'.esc_html($ln).'</label>';echo '</p><button class="button button-primary">Add Partner</button></form>';
    if($partners){echo '<table class="widefat striped"><thead><tr><th>Partner</th><th>Category</th><th>Location</th><th>Offer</th><th>Levels</th><th>Usage</th><th>Status / Actions</th></tr></thead><tbody>';foreach($partners as $pt){echo '<tr><td><strong>'.esc_html($pt['name']).'</strong></td><td>'.esc_html($pt['category']).'</td><td>'.esc_html($pt['location']).'</td><td>'.nl2br(esc_html($pt['offer'])).'</td><td>'.esc_html(implode(', ',array_map(fn($n)=>'L'.$n,$pt['levels']))).'</td><td>'.esc_html(self::partner_redemption_stats(array_search($pt,$partners,true))['redemptions']).' uses<br><small>'.esc_html(self::partner_redemption_stats(array_search($pt,$partners,true))['unique_members']).' members</small></td><td><form method="post" action="'.esc_url(admin_url('admin-post.php')).'" style="display:inline-flex;gap:6px;align-items:center"><input type="hidden" name="action" value="lmh_partner_status"><input type="hidden" name="partner_id" value="'.esc_attr(array_search($pt,$partners,true)).'">'.wp_nonce_field('lmh_partner_status_'.array_search($pt,$partners,true),'_wpnonce',true,false).'<select name="status"><option value="active" '.selected($pt['status'],'active',false).'>Active</option><option value="inactive" '.selected($pt['status'],'inactive',false).'>Inactive</option></select><button class="button">Save</button></form><form method="post" action="'.esc_url(admin_url('admin-post.php')).'" style="display:inline-block;margin-left:6px" onsubmit="return confirm(\'Delete this partner?\')"><input type="hidden" name="action" value="lmh_delete_partner"><input type="hidden" name="partner_id" value="'.esc_attr(array_search($pt,$partners,true)).'">'.wp_nonce_field('lmh_delete_partner_'.array_search($pt,$partners,true),'_wpnonce',true,false).'<button class="button button-link-delete">Delete</button></form></td></tr>';}echo '</tbody></table>';}else echo '<p>No partners have been added yet.</p>';echo '</div>';
    $level_benefits=self::level_benefits();
    echo '<div id="level-benefits" style="margin:28px 0"><h2>7-Level Benefits & Rewards</h2><p>Configure benefits, discounts and rewards for every membership level. Smart QR and referral tracking remain available to all levels.</p><form method="post" action="'.esc_url(admin_url('admin-post.php')).'"><input type="hidden" name="action" value="lmh_save_level_benefits">'.wp_nonce_field('lmh_save_level_benefits','_wpnonce',true,false).'<div style="overflow:auto"><table class="widefat striped"><thead><tr><th>Level</th><th>Title</th><th>Benefits</th><th>Discount</th><th>Rewards / Recognition</th></tr></thead><tbody>';foreach(self::MEMBER_LEVELS as $ln=>$ls){$b=$level_benefits[$ln];echo '<tr><td><strong>'.esc_html($ln.' — '.ucwords(str_replace('_',' ',$ls))).'</strong></td><td><input name="title['.esc_attr($ln).']" value="'.esc_attr($b['title']).'"></td><td><textarea name="benefits['.esc_attr($ln).']" rows="3">'.esc_textarea($b['benefits']).'</textarea></td><td><input name="discount['.esc_attr($ln).']" value="'.esc_attr($b['discount']).'"></td><td><textarea name="rewards['.esc_attr($ln).']" rows="3">'.esc_textarea($b['rewards']).'</textarea></td></tr>';}echo '</tbody></table></div><p><button class="button button-primary">Save Benefits & Rewards</button></p></form></div>';
    $level_rules=self::level_rules();
    echo '<div id="qualification-rules" style="margin:28px 0"><h2>Membership Qualification Rules</h2><p>Set the minimum verified activity required for each level. Zero means that metric is not required. Rules do not auto-promote members; they identify who qualifies for review so Le Manager keeps control of advancement.</p><form method="post" action="'.esc_url(admin_url('admin-post.php')).'"><input type="hidden" name="action" value="lmh_save_level_rules">'.wp_nonce_field('lmh_save_level_rules','_wpnonce',true,false).'<table class="widefat striped"><thead><tr><th>Level</th><th>Verified Direct Referrals</th><th>Events Attended</th><th>Engagement Points</th></tr></thead><tbody>';foreach(self::MEMBER_LEVELS as $ln=>$ls){echo '<tr><td><strong>'.esc_html($ln.' — '.ucwords(str_replace('_',' ',$ls))).'</strong></td><td><input type="number" min="0" name="referrals['.esc_attr($ln).']" value="'.esc_attr($level_rules[$ln]['referrals']).'"></td><td><input type="number" min="0" name="events['.esc_attr($ln).']" value="'.esc_attr($level_rules[$ln]['events']).'"></td><td><input type="number" min="0" name="engagement['.esc_attr($ln).']" value="'.esc_attr($level_rules[$ln]['engagement']).'"></td></tr>';}echo '</tbody></table><p><button class="button button-primary">Save Qualification Rules</button></p></form></div>';
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

  public static function admin_card_status() {
    if(!current_user_can('manage_options')) wp_die('Not allowed.');
    $uid=absint($_POST['user_id']??0);check_admin_referer('lmh_card_status_'.$uid);
    $allowed=['eligible','requested','approved','production','active','suspended','lost','replaced','expired','revoked'];
    $status=sanitize_key($_POST['status']??'eligible');
    if($uid && get_user_by('id',$uid) && in_array($status,$allowed,true)){
      update_user_meta($uid,'_lmh_physical_card_status',$status);
      update_user_meta($uid,'_lmh_physical_card_updated_at',current_time('mysql'));
    }
    wp_safe_redirect(admin_url('admin.php?page=lmh-control#ambassadors'));exit;
  }

  public static function admin_save_level_benefits() {
    if(!current_user_can('manage_options')) wp_die('Not allowed.');check_admin_referer('lmh_save_level_benefits');$benefits=[];
    foreach(self::MEMBER_LEVELS as $n=>$slug)$benefits[$n]=['title'=>sanitize_text_field($_POST['title'][$n]??ucwords(str_replace('_',' ',$slug))),'benefits'=>sanitize_textarea_field($_POST['benefits'][$n]??''),'discount'=>sanitize_text_field($_POST['discount'][$n]??''),'rewards'=>sanitize_textarea_field($_POST['rewards'][$n]??'')];
    update_option('lmh_member_level_benefits',$benefits,false);wp_safe_redirect(admin_url('admin.php?page=lmh-control#level-benefits'));exit;
  }

  public static function admin_save_level_rules() {
    if(!current_user_can('manage_options')) wp_die('Not allowed.');
    check_admin_referer('lmh_save_level_rules');
    $rules=[];
    foreach(self::MEMBER_LEVELS as $n=>$slug){
      $rules[$n]=[
        'referrals'=>max(0,absint($_POST['referrals'][$n]??0)),
        'events'=>max(0,absint($_POST['events'][$n]??0)),
        'engagement'=>max(0,absint($_POST['engagement'][$n]??0))
      ];
    }
    update_option('lmh_member_level_rules',$rules,false);
    wp_safe_redirect(admin_url('admin.php?page=lmh-control#qualification-rules'));exit;
  }

  public static function admin_member_level() {
    if(!current_user_can('manage_options')) wp_die('Not allowed.');
    $uid=absint($_POST['user_id']??0);
    check_admin_referer('lmh_member_level_'.$uid);
    $level=absint($_POST['level']??1);
    if($uid && get_user_by('id',$uid) && isset(self::MEMBER_LEVELS[$level])) self::set_member_level($uid,$level,'admin');
    wp_safe_redirect(admin_url('admin.php?page=lmh-control#membership-levels'));exit;
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
