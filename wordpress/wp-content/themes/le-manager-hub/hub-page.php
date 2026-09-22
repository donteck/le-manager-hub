<?php get_header();$view=get_query_var('lmh_view');?><main id="main" class="lmh-section"><div class="lmh-shell">
<?php if($view==='join'):?><div class="lmh-kicker">Your next connection starts here</div><h1 class="lmh-title">Join Le Manager Hub</h1>
<?php if(is_user_logged_in()):?><p>You are already signed in. <a href="<?php echo esc_url(lmh_url('account'));?>">Go to your account →</a></p><?php elseif(!get_option('users_can_register')):?><p>New member registration is currently closed.</p><a class="lmh-btn" href="<?php echo esc_url(wp_login_url(lmh_url('account')));?>">SIGN IN</a><?php else:?>
<form class="lmh-form" data-api="register"><label>Your name<input name="name" required autocomplete="name" maxlength="100"></label><label>Email<input name="email" type="email" required autocomplete="email"></label><label>Password<input name="password" type="password" minlength="10" required autocomplete="new-password"></label><label>Account type<select name="account_type"><?php foreach(['fan'=>'Fan','professional'=>'Professional','company'=>'Company'] as $kind=>$label):?><option value="<?php echo esc_attr($kind);?>" <?php selected(sanitize_key($_GET['kind']??'fan'),$kind);?>><?php echo esc_html($label);?></option><?php endforeach;?></select></label><button class="lmh-btn">CREATE MY ACCOUNT</button><p role="status" aria-live="polite"></p></form><p>Already a member? <a href="<?php echo esc_url(wp_login_url(lmh_url('account')));?>">Sign in</a></p><?php endif;?>
<?php elseif($view==='directory'):?><div class="lmh-kicker">The network</div><h1 class="lmh-title">Global Directory</h1><form id="lmh-search" class="lmh-search"><label>Search<input name="search" placeholder="Name, talent or service"></label><label>Explore<select name="type"><option value="lmh_artist">Artists</option><option value="lmh_professional">Professionals</option><option value="lmh_company">Companies</option></select></label><label>Country<input name="country" placeholder="All countries"></label><label>Genre<input name="genre" placeholder="All genres"></label><label>Profession<input name="profession" placeholder="All professions"></label><button class="lmh-btn">SEARCH</button></form><p id="directory-status" role="status"></p><div id="directory-results" class="lmh-grid"></div><button id="directory-more" class="lmh-btn" hidden>LOAD MORE</button>
<?php else:$user=wp_get_current_user();?><div class="lmh-kicker">Your community</div><h1 class="lmh-title">Welcome, <?php echo esc_html($user->display_name);?></h1><p><?php echo esc_html('LM '.(get_user_meta($user->ID,'lmh_fan_level',true)?:'Fan'));?> · <a href="<?php echo esc_url(get_edit_profile_url());?>">Edit account</a> · <a href="<?php echo esc_url(wp_logout_url(home_url('/')));?>">Sign out</a></p><section id="squad" class="lmh-squad"><h2>Le Manager Event Squad</h2><p>Stay part of the community that shows up for music.</p><button class="lmh-btn" id="squad-button" data-joined="<?php echo get_user_meta($user->ID,'lmh_event_squad',true)==='joined'?'true':'false';?>"><?php echo get_user_meta($user->ID,'lmh_event_squad',true)==='joined'?'LEAVE EVENT SQUAD':'JOIN EVENT SQUAD';?></button><p id="squad-status" role="status"></p></section><h2>Artists you follow</h2><div class="lmh-grid"><?php $ids=array_filter(array_map('intval',(array)get_user_meta($user->ID,'lmh_following',true)));$shown=0;foreach($ids as $id)if(get_post_status($id)==='publish'&&get_post_type($id)==='lmh_artist'){$shown++;echo '<a class="lmh-card" href="'.esc_url(get_permalink($id)).'">'.esc_html(get_the_title($id)).'</a>';}if(!$shown)echo '<p>Discover an artist and select Follow to add them here.</p>';?></div><p><a href="<?php echo esc_url(lmh_url('directory'));?>">Discover artists →</a></p><section class="lmh-profile-builder">
<h2>Create a Professional Profile</h2>
<p>Create your public Le Manager identity. New profiles are submitted for review before publication.</p>
<form id="lmh-profile-form" class="lmh-form">
<label>Profile type<select name="type"><option value="artist">Artist</option><option value="professional">Industry Professional</option><option value="company">Company / Label</option></select></label>
<label>Professional / Stage Name<input name="name" required maxlength="120"></label>
<label>City<input name="city" maxlength="120"></label>
<label>Website<input name="website" type="url" placeholder="https://"></label>
<label>Booking Email<input name="booking_email" type="email"></label>
<label>Phone<input name="phone" type="tel"></label>
<label>Languages<input name="languages" placeholder="English, French, Haitian Creole…"></label>
<label>Availability<input name="availability" placeholder="Available for bookings, weekends, touring…"></label>
<label>Services<textarea name="services" rows="3" placeholder="Management, production, booking, promotion…"></textarea></label>
<label>Achievements<textarea name="achievements" rows="3" placeholder="Awards, notable projects, milestones…"></textarea></label>
<label>Portfolio URL<input name="portfolio_url" type="url" placeholder="https://"></label>
<label>Cover Image URL<input name="cover_image_url" type="url" placeholder="https://"></label>
<label>Instagram<input name="instagram" type="url" placeholder="https://instagram.com/..."></label>
<label>TikTok<input name="tiktok" type="url" placeholder="https://tiktok.com/@..."></label>
<label>Facebook<input name="facebook" type="url" placeholder="https://facebook.com/..."></label>
<label>X / Twitter<input name="x" type="url" placeholder="https://x.com/..."></label>
<label>Bio<textarea name="bio" rows="6" maxlength="3000"></textarea></label>
<button class="lmh-btn" type="submit">SUBMIT PROFILE FOR REVIEW</button>
<p id="lmh-profile-status" role="status" aria-live="polite"></p>
</form>
</section>
<section class="lmh-dashboard" aria-labelledby="lmh-dashboard-title">
<h2 id="lmh-dashboard-title">My Professional Dashboard</h2>
<div class="lmh-grid">
<article class="lmh-card"><h3>My Profiles</h3><div id="lmh-my-profiles">Loading your profiles…</div></article>
<article class="lmh-card"><h3>Bookings</h3><p>Track requests, event dates and booking status from one place.</p><a class="lmh-text-link" href="#my-bookings">VIEW BOOKINGS →</a></article>
<article class="lmh-card"><h3>FanBase</h3><p>Artists you follow and your Le Manager community activity live here.</p><a class="lmh-text-link" href="<?php echo esc_url(lmh_url('directory'));?>">DISCOVER PEOPLE →</a></article>
</div>
</section>
<h2>My booking requests</h2><div id="my-bookings" aria-live="polite">Loading your requests…</div>
<script>
document.addEventListener('DOMContentLoaded',function(){
 var box=document.getElementById('lmh-my-profiles'); if(!box||!window.LMH_V5)return;
 var form=document.getElementById('lmh-profile-form'), status=document.getElementById('lmh-profile-status');
 if(form) form.addEventListener('submit',function(e){
   e.preventDefault(); status.textContent='Submitting profile…';
   var data={}; new FormData(form).forEach(function(v,k){data[k]=v;});
   fetch(LMH_V5.api+'profiles',{method:'POST',credentials:'same-origin',headers:{'Content-Type':'application/json','X-WP-Nonce':LMH_V5.nonce},body:JSON.stringify(data)})
   .then(function(r){return r.json().then(function(j){if(!r.ok)throw new Error(j.message||'Unable to create profile');return j;});})
   .then(function(){status.textContent='Profile submitted for review.';form.reset();setTimeout(function(){location.reload();},700);})
   .catch(function(err){status.textContent=err.message;});
 });
 fetch(LMH_V5.api+'profiles/mine',{credentials:'same-origin',headers:{'X-WP-Nonce':LMH_V5.nonce}})
 .then(function(r){if(!r.ok)throw new Error('Unable to load profiles');return r.json();})
 .then(function(items){
   if(!items.length){box.innerHTML='<p>No professional profile is connected to this account yet.</p>';return;}
   box.innerHTML=items.map(function(p){
     var label=(p.type||'').replace('lmh_','').replace('_',' ');
     return '<div class="lmh-profile-row"><strong>'+escapeHtml(p.name)+'</strong><br><small>'+escapeHtml(label)+' · '+escapeHtml(p.status)+' · '+escapeHtml(p.verification)+'</small>'+(p.edit_url?' <a href="'+encodeURI(p.edit_url)+'">Manage →</a>':'')+'</div>';
   }).join('');
 }).catch(function(){box.innerHTML='<p>Profiles are temporarily unavailable.</p>';});
 function escapeHtml(s){var d=document.createElement('div');d.textContent=s==null?'':String(s);return d.innerHTML;}
});
</script><?php endif;?></div></main><?php get_footer();?>
