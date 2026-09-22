<footer>
<div class="container">
<div class="footer-grid">
<div class="footer-logo">
<div class="brandmark"><span class="l">L</span><span class="m" style="color:#fff">M</span></div>
<div class="brandname"><span style="color:#d80a16">LE</span> MANAGER</div>
<div class="brandtag">TALENT • BRAND • OPPORTUNITIES</div>
<p style="max-width:360px">The global music industry hub connecting artists, music-industry professionals, companies, media, brands, fans and opportunities worldwide.</p>
</div>
<div class="footer"><h4>DISCOVER</h4><a class="lm-action" data-action="Artists" href="#">Artists</a><a href="#professionals">Professionals</a><a class="lm-action" data-action="Companies" href="#">Companies</a><a class="lm-action" data-action="Genres" href="#">Genres</a><a class="lm-action" data-action="Countries" href="#">Countries</a></div>
<div class="footer"><h4>NETWORK</h4><a href="#directory">Directory</a><a href="#opportunities">Opportunities</a><a class="lm-action" data-action="Bookings" href="#">Bookings</a><a href="#events">Events</a><a href="#fans">Fans</a><a href="#verification">Verification</a></div>
<div class="footer"><h4>MEDIA</h4><a href="#tv">Le Manager TV</a><a href="#radio">Le Manager Radio</a><a href="#podcast">Le Manager Podcast</a><a href="#news">Industry News</a><a href="#music">Music</a></div>
<div class="footer"><h4>ACCOUNT</h4><a class="lm-action" data-action="Join" href="#">Join</a><a class="lm-action" data-action="Login" href="#">Login</a><a class="lm-action" data-action="Membership" href="#">Membership</a><a class="lm-action" data-action="Advertise" href="#">Advertise</a><a class="lm-action" data-action="Contact" href="#">Contact</a></div>
</div>
<div class="footer-bottom">
<span>© 2026 Le Manager. All rights reserved.</span>
<span>Privacy • Terms • Community Guidelines • Verification Policy</span>
</div>
</div>
</footer>

<div aria-hidden="true" class="lm-modal" id="lm-modal">
<div aria-labelledby="lm-modal-title" aria-modal="true" class="lm-modal-card" role="dialog">
<button aria-label="Close" class="lm-modal-close" type="button">×</button>
<div class="kicker">LE MANAGER HUB</div>
<h3 id="lm-modal-title">Coming Soon</h3>
<p id="lm-modal-copy">This feature is being prepared for the full Le Manager Hub platform.</p>
<button class="btn primary lm-modal-close-action" type="button">CONTINUE EXPLORING</button>
</div>
</div>

<script>
(function(){
  const modal=document.getElementById('lm-modal');
  const title=document.getElementById('lm-modal-title');
  const copy=document.getElementById('lm-modal-copy');

  function openModal(label){
    title.textContent=label || 'Le Manager Hub';
    copy.textContent='This button is active. The full '+(label || 'feature')+' workflow will connect to the WordPress account, booking, media, or membership system when the backend is installed.';
    modal.classList.add('open');
    modal.setAttribute('aria-hidden','false');
  }
  function closeModal(){
    modal.classList.remove('open');
    modal.setAttribute('aria-hidden','true');
  }

  document.querySelectorAll('.lm-action').forEach(el=>{
    el.addEventListener('click',function(e){
      e.preventDefault();
      openModal(this.dataset.action || this.textContent.trim());
    });
  });
  document.querySelectorAll('.lm-modal-close,.lm-modal-close-action').forEach(el=>el.addEventListener('click',closeModal));
  modal.addEventListener('click',e=>{if(e.target===modal) closeModal();});
  document.addEventListener('keydown',e=>{if(e.key==='Escape') closeModal();});

  // Search button scrolls to directory and visually acknowledges the action.
  document.querySelectorAll('button').forEach(btn=>{
    if(/SEARCH/i.test(btn.textContent)){
      btn.addEventListener('click',function(){
        const directory=document.getElementById('directory');
        if(directory) directory.scrollIntoView({behavior:'smooth'});
      });
    }
  });
})();
</script>
<?php wp_footer(); ?></body>
</html>
