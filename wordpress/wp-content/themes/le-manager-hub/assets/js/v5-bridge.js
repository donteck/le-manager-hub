/* Backend routing only. No changes to the approved V5 text, images or styles. */
document.addEventListener('DOMContentLoaded',()=>{
 const cfg=window.LMH_V5;
 const go=url=>{location.href=url;};
 const directory=(type='lmh_professional',search='',extra={})=>{const url=new URL(cfg.directory);url.searchParams.set('type',type);if(search)url.searchParams.set('search',search);Object.entries(extra).forEach(([k,v])=>{if(v)url.searchParams.set(k,v);});return url.href;};
 const join=kind=>{if(cfg.loggedIn)return cfg.account;const url=new URL(cfg.join);url.searchParams.set('kind',kind);return url.href;};
 const routes={
  'LOGIN':cfg.login,'JOIN':join('fan'),'JOIN FREE':join('professional'),'FOR BUSINESS':join('company'),
  'LIST YOUR SERVICES':join('professional'),'EXPLORE BOOKINGS':cfg.directory,'BOOKINGS':cfg.account,
  'ARTISTS':cfg.archives.artist,'VIEW ALL ARTISTS →':cfg.archives.artist,'VIEW ALL PROS →':cfg.archives.professional,
  'COMPANIES':cfg.archives.company,'GENRES':cfg.directory,'COUNTRIES':cfg.directory,
  'VIEW OPPORTUNITY':cfg.archives.opportunity,'VIEW ALL SHOWS →':cfg.archives.tv,
  'FULL SCHEDULE →':cfg.archives.radio,'ALL EPISODES →':cfg.archives.podcast
 };
 document.addEventListener('click',e=>{
  const a=e.target.closest('a');if(!a)return;
  const text=a.textContent.trim().toUpperCase(),href=a.getAttribute('href');
  let url=routes[(a.dataset.action||'').toUpperCase()];
  if(href==='#join')url=join('professional');
  if(href==='#join-fan')url=join('fan');
  if(text==='VIEW ALL OPPORTUNITIES →')url=cfg.archives.opportunity;
  if(text==='VIEW ALL EVENTS →')url=cfg.archives.event;
  if(a.closest('.radio-actions')&&text.includes('LISTEN LIVE'))url=cfg.archives.radio;
  if(a.matches('.media-nav-card.podcast'))url=cfg.home+'#podcast';
  if(!url&&!cfg.isHome&&href&&href.startsWith('#')&&href!=='#')url=cfg.home+href;
  if(url){e.preventDefault();e.stopImmediatePropagation();go(url);}
 },true);
 const search=document.querySelector('.searchbar');if(search){
  const run=e=>{e.preventDefault();e.stopImmediatePropagation();const selects=search.querySelectorAll('select'),kind=selects[0].value;const professions={Managers:'Artist Managers',Producers:'Producers',Promoters:'Promoters'};go(directory(kind==='Artists'?'lmh_artist':'lmh_professional',search.querySelector('input').value,{profession:professions[kind]||'',country:selects[1].selectedIndex?selects[1].value:'',genre:selects[2].selectedIndex?selects[2].value:''}));};
  search.querySelector('button').addEventListener('click',run,true);
  search.querySelector('input').addEventListener('keydown',e=>{if(e.key==='Enter')run(e);});
 }
 function connect(selector,getUrl){document.querySelectorAll(selector).forEach(el=>{el.setAttribute('role','link');el.setAttribute('tabindex','0');const open=e=>{if(e.target.closest('a,button'))return;go(getUrl(el));};el.addEventListener('click',open);el.addEventListener('keydown',e=>{if(e.key==='Enter'&&e.target===el){e.preventDefault();go(getUrl(el));}});});}
 connect('.directory-item',el=>{const label=el.textContent.replace('→','').trim();return label==='Artists'?directory('lmh_artist'):directory('lmh_professional','',{profession:label});});
 connect('.card.artist',el=>directory('lmh_artist',el.querySelector('h3').textContent));
 connect('.prof',el=>directory('lmh_professional',el.querySelector('h3').textContent.replace('✓','').trim()));
 connect('.video-card',()=>cfg.archives.tv);connect('.podcast-card',()=>cfg.archives.podcast);connect('.show',()=>cfg.archives.radio);
 connect('.op',()=>cfg.archives.opportunity);
 connect('.media-mini',el=>el.textContent.includes('Podcast')?cfg.archives.podcast:el.textContent.includes('Radio')?cfg.archives.radio:cfg.archives.tv);
 connect('.quick',el=>{const label=el.querySelector('h3').textContent;return label.includes('Events')?cfg.archives.event:label.includes('Opportunities')?cfg.archives.opportunity:label.includes('TV')?cfg.archives.tv:cfg.directory;});
 connect('.fan-card',()=>cfg.account+'#squad');
 connect('.event',()=>cfg.archives.event);
 connect('.genre',el=>directory('lmh_artist','',{genre:el.textContent.trim()}));
 connect('.region',el=>directory('lmh_artist','',{country:el.textContent.trim()}));
});
