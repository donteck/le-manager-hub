/* Le Manager Hub frontend API helper */
window.LMHApi = {
  async request(path, options = {}) {
    const headers = Object.assign({'Content-Type':'application/json'}, options.headers || {});
    if (window.LMH && LMH.nonce) headers['X-WP-Nonce'] = LMH.nonce;
    const response = await fetch((window.LMH?.api || '/wp-json/lmh/v1/') + path, {...options, headers});
    const data = await response.json();
    if (!response.ok) throw new Error(data.message || 'Le Manager Hub request failed');
    return data;
  },
  register(payload){ return this.request('register',{method:'POST',body:JSON.stringify(payload)}); },
  me(){ return this.request('me'); },
  directory(params={}){ return this.request('directory?'+new URLSearchParams(params)); },
  booking(payload){ return this.request('bookings',{method:'POST',body:JSON.stringify(payload)}); },
  myBookings(){ return this.request('bookings/mine'); },
  followArtist(id){ return this.request('follow/'+id,{method:'POST'}); }
};
