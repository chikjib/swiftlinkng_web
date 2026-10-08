<template>
 <main class="swift-service-page esim-app">
  <h1 class="swift-service-heading"><button class="swift-back" type="button" aria-label="Go back" @click="back"><i class="fas fa-arrow-left"></i></button>{{ detail ? 'eSIM Details' : 'Travel eSIM' }}</h1>
  <p v-if="error" class="esim-notice error esim-page-error" role="alert">{{ error }}</p>
  <div class="esim-layout" :class="{'with-guide':!result&&!detail}">
  <div class="esim-content">
   <details v-if="savedPayments.length" class="esim-saved-payments"><summary>Saved payments ({{ savedPayments.length }}) — resume anytime</summary><p>You can resume a saved payment or start a new purchase. Payments already submitted may still complete.</p><div v-for="saved in savedPayments" :key="saved.reference" class="esim-saved-payment"><span>{{ saved.package_name||'eSIM payment' }}<small>{{ saved.cancelled?'Cancelled before payment':saved.reference }}</small></span><button class="esim-outline" :disabled="busy" @click="purchase(saved)">Resume Payment</button></div></details>
   <template v-if="result">
    <section class="esim-result">
     <div class="esim-result-symbol" :class="result.status">{{ result.status==='success'?'✓':result.status==='reversed'?'×':'◷' }}</div>
     <h1>{{ result.status==='success' ? (result.kind==='topup'?'Top Up Successful':'eSIM Ready!') : result.status==='reversed'?'eSIM Purchase Reversed':'Preparing Your eSIM' }}</h1>
     <p>{{ result.status==='success'?'Your eSIM has been purchased successfully.':result.status==='reversed'?`We couldn’t deliver your eSIM. ${money(result.amount_kobo)} has been returned to your wallet.`:'Your payment is pending. Check its status before making another purchase.' }}</p>
     <div v-if="result.status!=='reversed'" class="esim-summary"><span class="esim-flag">{{ flag(resultLocation) }}</span><div><strong>{{ result.package_name }}</strong><small>{{ money(result.amount_kobo) }}</small></div></div>
     <template v-if="result.status==='success' && result.profile_id"><button class="esim-primary swift-primary-action" @click="resultAction('install')">Install eSIM</button><button class="esim-outline" @click="resultAction('qr')">▧ &nbsp; View QR Code</button></template>
     <button v-if="['pending','processing'].includes(result.status)" class="esim-primary swift-primary-action" :disabled="busy" @click="checkResult">{{ busy?'Checking…':'Check Status' }}</button>
     <button v-if="result.status==='reversed'" class="esim-primary swift-primary-action" @click="result=null;tab='buy'">Try Again</button>
     <button class="esim-outline" @click="tab=result.status==='reversed'?'buy':'mine';result=null;loadMine()">{{ result.status==='reversed'?'Back to eSIM':'▣  View in My eSIMs' }}</button>
     <div v-if="result.status==='success'" class="esim-notice info"><strong>ⓘ &nbsp; When does my plan start?</strong><p>Check your plan’s activation terms. Plans activate on installation or when connecting to a supported network.</p></div>
    </section>
   </template>
   <template v-else-if="detail">
    <div class="esim-summary"><span class="esim-flag">{{ flag(profileCode(detail)) }}</span><div><strong>{{ profileName(detail) }}</strong><small>{{ volume(detail.details.totalVolume) }} · {{ detail.details.totalDuration }} Days</small></div><span class="esim-status">{{ statusLabel(detail.details.esimStatus) }}</span></div>
    <div class="esim-usage-box"><span>Data Remaining</span><strong>{{ volume(remaining(detail)) }} / {{ volume(detail.details.totalVolume) }}</strong><div class="esim-progress"><span :style="{width:remainingPercent(detail)+'%'}"></span></div></div>
    <dl class="esim-details"><div v-for="(value,key) in detailRows" :key="key"><dt>⊙ &nbsp; {{ key }}</dt><dd>{{ value }} <button v-if="key==='ICCID'" aria-label="Copy ICCID" @click="copyText(value)">▢</button></dd></div></dl>
    <p v-if="detail.stale" class="esim-notice">Live details unavailable; showing saved details.</p>
    <button v-if="canTopup(detail)" class="esim-primary swift-primary-action" :disabled="busy||!enabled" @click="topup(detail);detail=null">Top Up</button>
    <button class="esim-outline" @click="sheet='install'">▣ &nbsp; Install eSIM</button><button class="esim-outline" @click="sheet='qr'">▧ &nbsp; View QR Code</button>
    <p class="esim-muted">Usage updates may be delayed by 2–3 hours. Last checked: {{ date(detail.synced_at) }}</p>
   </template>
   <template v-else>
    <div v-if="tab==='buy'&&!location&&!topupProfile" class="esim-hero"><h1>Travel <em>eSIM</em></h1><p>Stay connected<br>worldwide.</p></div>
    <div v-if="tab==='mine'||(!location&&!topupProfile)" class="esim-tabs"><button :class="{active:tab==='buy'}" @click="tab='buy'">Buy eSIM</button><button :class="{active:tab==='mine'}" @click="tab='mine';loadMine()">My eSIMs</button></div>
    <template v-if="tab==='buy'">
     <p v-if="!enabled" class="esim-notice">New eSIM purchases and top-ups are currently unavailable. Your existing eSIMs remain accessible.</p>
     <template v-if="!location&&!topupProfile">
      <label class="esim-search"><span>⌕</span><input v-model="search" aria-label="Search country" role="combobox" aria-autocomplete="list" :aria-expanded="!!search.trim()" aria-controls="esim-country-suggestions" placeholder="Search country (e.g. UK, USA, UAE)" @keydown.down.prevent="focusSuggestion" @keydown.esc="search=''"></label>
      <div v-if="search.trim()" id="esim-country-suggestions" class="esim-suggestions" role="listbox" aria-label="Suggested countries"><button v-for="l in filteredLocations" :key="l.code" role="option" :aria-selected="location===l.code" :disabled="!enabled||busy" @click="chooseCountry(l)"><span class="esim-flag">{{ flag(l.code) }}</span><span>{{ l.name }}</span><span>›</span></button><p v-if="!filteredLocations.length" role="status">No countries match “{{ search }}”.</p></div>
      <template v-else><h2>Popular Countries</h2><div class="esim-country-grid"><button v-for="l in displayedLocations" :key="l.code" :disabled="!enabled||busy" @click="chooseCountry(l)"><span class="esim-flag">{{ flag(l.code) }}</span><span>{{ shortName(l) }}</span></button></div><p v-if="!displayedLocations.length" class="esim-muted">{{ loading?'Loading destinations…':'No destinations available.' }}</p></template>
      <button class="esim-country-all" @click="sheet='countries'">◎ &nbsp; View All Countries <span>›</span></button>
      <p class="esim-travel-tip">◎ &nbsp; Keep your main SIM active for calls &amp; SMS while using your travel eSIM for data.</p>
     </template>
     <template v-else>
      <div class="esim-hero destination"><div class="esim-summary"><span class="esim-flag">{{ flag(location||profileCode(topupProfile)) }}</span><div><strong>{{ topupProfile?profileName(topupProfile):destinationName }}</strong><small>{{ topupProfile?'Top Up Your eSIM':`Stay connected in ${destinationName}` }}</small></div></div><button @click="sheet='countries'" :disabled="busy">⇄ &nbsp; Change Country</button></div>
      <button class="esim-country-all" @click="sheet='compatibility'">▯ &nbsp; Check if your phone supports eSIM <span>›</span></button>
      <div v-if="chosen" class="esim-summary"><div><strong>{{ volume(chosen.volume) }}{{ chosen.daily?' / day':'' }} · {{ money(chosen.amount_kobo) }}{{ chosen.daily?' / day':'' }}</strong><small>{{ chosen.duration }} {{ chosen.duration_unit.toLowerCase() }}{{ Number(chosen.duration)!==1?'s':'' }}</small></div></div>
      <button class="esim-primary swift-primary-action" :disabled="busy||!enabled" aria-haspopup="dialog" @click="sheet='plans'">{{ chosen?'Choose or Change Data Plan':'Select a Data Plan' }}</button>
     </template>
    </template>
    <template v-else>
     <p v-if="!profiles.length" class="esim-notice">Your purchased eSIMs will appear here.</p>
     <article v-for="p in profiles" :key="p.id" class="esim-profile"><button class="esim-profile-link" @click="openProfile(p)"><span class="esim-flag">{{ flag(profileCode(p)) }}</span><span><strong>{{ profileName(p) }}</strong><small>{{ volume(p.details.totalVolume) }} · {{ p.details.totalDuration }} Days</small></span><span class="esim-status">{{ statusLabel(p.details.esimStatus) }}</span><span>›</span></button><div class="esim-progress"><span :style="{width:remainingPercent(p)+'%'}"></span></div><div class="esim-profile-foot"><div><p>{{ volume(remaining(p)) }} remaining</p><small>Expires: {{ date(p.details.expiredTime) }}</small></div><button v-if="canTopup(p)" class="esim-topup" :disabled="busy||!enabled" @click="topup(p)">Top Up</button></div></article>
     <details v-if="purchases.length" class="esim-history"><summary>Recent Purchases</summary><button v-for="p in purchases" :key="p.id" class="esim-country-all" @click="result=p">{{ p.package_name }} · {{ p.status }} <span>›</span></button></details>
    </template>
   </template>
  </div>
  <aside v-if="!result&&!detail" class="esim-guide swift-service-card"><h2>Easy Steps to<br>Stay Connected</h2><ol><li v-for="step in ['Select destination','Choose a plan','Enter PIN & Buy','Get your eSIM','Install & Enjoy']" :key="step">{{ step }}</li></ol><p>Check that your phone supports eSIM before purchasing. An internet connection is needed for installation.</p></aside>
  </div>
  <div v-if="selected||sheet||termsOpen" class="esim-overlay" @click.self="closeSheet" @keydown.esc="closeSheet"><section class="esim-sheet" role="dialog" aria-modal="true" aria-label="eSIM options" tabindex="-1"><div class="esim-handle"></div><button class="esim-sheet-close" aria-label="Close" :disabled="busy" @click="closeSheet">×</button>
   <template v-if="termsOpen"><h2>Terms &amp; Conditions</h2><p class="esim-terms">{{ terms }}</p><button class="esim-primary swift-primary-action" @click="termsOpen=false">Back</button></template>
   <template v-else-if="selected"><h2>Confirm your {{ topupProfile?'top up':'eSIM' }}</h2><div class="esim-summary"><span class="esim-flag">{{ flag(location) }}</span><div><strong>{{ selected.name }}</strong><small>{{ volume(selected.volume) }}{{ selected.daily?' per day':'' }} · {{ selected.duration }} {{ selected.duration_unit.toLowerCase() }}</small></div></div><label v-if="selected.daily">Number of days<input class="esim-input" type="number" v-model="days" min="1" max="365" @input="quote=null"></label><p>{{ selected.description }}</p><p class="esim-muted">{{ selected.activation_type===2?'Activates on first network connection.':'Activates on installation.' }}</p><p v-if="selected.fup_policy" class="esim-muted">Fair use: {{ selected.fup_policy }}</p><p v-for="n in selected.networks" :key="n.locationName" class="esim-muted">{{ n.locationName }}: {{ n.operatorList.map(o=>o.operatorName+' '+o.networkType).join(', ') }}</p><label class="esim-checkbox"><input type="checkbox" v-model="compatible"> My device supports eSIM.</label><button class="esim-link" @click="openTerms">Terms &amp; Conditions</button><p v-if="error" class="esim-notice error">{{ error }}</p><button class="esim-primary swift-primary-action" :disabled="busy||!compatible" @click="quote?purchase():getQuote()">{{ busy?'Please wait…':quote?`Enter PIN & Buy · ${money(quote.amount_kobo)}`:'Confirm Price' }}</button></template>
   <template v-else-if="sheet==='plans'">
      <h2>Select a Data Plan</h2><p class="esim-muted">{{ topupProfile?profileName(topupProfile):destinationName }}</p><p v-if="error" class="esim-notice error" role="alert">{{ error }}</p><p v-if="loading" role="status">Loading plans…</p>
      <div class="esim-plans"><button v-for="p in plans" :key="p.package_code" class="esim-plan" :class="{chosen:chosen?.package_code===p.package_code}" :aria-pressed="chosen?.package_code===p.package_code" :disabled="busy||!enabled" @click="chosen=p"><span class="esim-radio">{{ chosen?.package_code===p.package_code?'✓':'' }}</span><span class="esim-plan-label"><strong>{{ volume(p.volume) }}{{ p.daily?' / day':'' }}</strong><small>{{ p.duration }} {{ p.duration_unit.toLowerCase() }}{{ Number(p.duration)!==1?'s':'' }}</small></span><strong class="esim-plan-price">{{ money(p.amount_kobo) }}<small v-if="p.daily"> / day</small></strong></button></div>
      <p v-if="!loading&&!error&&!plans.length" class="esim-muted">No plans are available for this destination right now.</p>
      <button v-if="error&&!loading" class="esim-outline" @click="loadPlans">Retry Loading Plans</button>
      <div class="esim-plan-actions"><button class="esim-primary swift-primary-action" :disabled="!chosen||busy||!enabled" @click="selectPlan(chosen)">{{ topupProfile?'Top Up Now':'Buy eSIM Now' }}</button></div>
   </template>
   <template v-else-if="sheet==='countries'"><h2>Select Destination</h2><label class="esim-search">⌕ <input v-model="search" placeholder="Search country or region" aria-label="Search all countries"></label><button v-for="l in filteredLocations" :key="l.code" class="esim-country-all" :disabled="busy||!enabled" @click="chooseCountry(l)">{{ flag(l.code) }} &nbsp; {{ l.name }} <span>›</span></button></template>
   <template v-else-if="sheet==='compatibility'"><h2>Does your phone support eSIM?</h2><p>Open your phone’s settings and look for “Add eSIM” under Mobile / Cellular or SIM Manager.</p><p>Your device must support eSIM. Availability can vary by model and region; check with your manufacturer if you’re unsure.</p><button class="esim-primary swift-primary-action" @click="sheet=null">Got it</button></template>
   <template v-else-if="detail"><h2>{{ sheet==='qr'?'View QR Code':'Install eSIM' }}</h2><template v-if="sheet==='qr'"><img v-if="safeUrl(detail.details.qrCodeUrl)" class="esim-qr" :src="detail.details.qrCodeUrl" alt="Private eSIM installation QR code"><p v-else>QR code unavailable. Use the manual installation details below.</p><p>Scan from your phone’s Add eSIM settings. Keep this code private.</p></template><a v-else-if="installUrl(detail)" :href="installUrl(detail)" target="_blank" rel="noopener noreferrer" class="esim-primary swift-primary-action">Install on this device</a><p>Settings → Mobile / Cellular → Add eSIM → Enter details manually.</p><label>SM-DP+ Address</label><code>{{ activation(detail)[1]||'Not available yet' }}</code><label>Activation Code</label><code>{{ activation(detail)[2]||'Not available yet' }}</code><button class="esim-outline" @click="copyActivation">Copy Activation Code</button></template>
  </section></div>
 </main>
</template>
<script>
import axios from 'axios';
import Auth from '../../Auth.js';
import './esim-ui.css';
export default {
 name:'TravelEsim',
 data(){return {chosen:null,sheet:null,tab:'buy',locations:[],location:'',search:'',plans:[],profiles:[],purchases:[],enabled:false,loading:false,busy:false,error:'',selected:null,days:1,compatible:false,quote:null,result:null,detail:null,showQr:false,topupProfile:null,attempt:null,savedPayments:[],termsOpen:false,terms:''};},
 computed:{wallet(){return this.money(Number(Auth.user?.wallet||0)*100);},destinationName(){return this.locations.find(l=>l.code===this.location)?.name||this.location;},displayedLocations(){if(this.search)return this.filteredLocations;const codes=['GB','US','CA','AE','SA','TR','FR','DE','ZA'];return codes.map(c=>this.locations.find(l=>l.code===c)).filter(Boolean);},resultLocation(){return this.location||this.profileCode(this.profiles.find(p=>p.id===this.result?.profile_id));},detailRows(){const d=this.detail.details;return {Validity:`${d.totalDuration} Days`,Expires:this.date(d.expiredTime),ICCID:d.iccid,Network:(this.detail.networks||[]).flatMap(n=>n.operatorList||[]).map(o=>o.operatorName).join(', ')||'See plan coverage',APN:d.apn||'Automatic'};},key(){return `esim_attempt_${Auth.user?.id}`;},filteredLocations(){return this.locations.filter(l=>(`${l.name} ${l.code} ${({'GB':'UK','US':'USA','AE':'UAE'})[l.code]||''}`).toLowerCase().includes(this.search.trim().toLowerCase()));}},
 async mounted(){try{const stored=JSON.parse(localStorage.getItem(this.key)||'null');this.savedPayments=Array.isArray(stored)?stored:stored?[stored]:[];const s=(await axios.get('/api/services/status')).data.data;this.enabled=s.esim_enabled;if(this.enabled)this.locations=(await axios.get('/api/esim/locations')).data.data;}catch(e){this.error=this.message(e);}await this.loadMine();},
 methods:{
  focusSuggestion(){this.$el.querySelector('#esim-country-suggestions button')?.focus();},
  back(){if(this.result){this.result=null;}else if(this.detail){this.detail=null;}else if(this.location||this.topupProfile){this.location='';this.topupProfile=null;this.plans=[];this.chosen=null;}else{this.$router.push('/dashboard');}},
  closeSheet(){if(this.busy)return;if(this.termsOpen){this.termsOpen=false;return;}this.sheet=null;this.selected=null;},
  shortName(l){return ({GB:'UK',US:'USA',AE:'UAE'})[l.code]||l.name;},
  profileCode(p){return p?.details?.packageList?.[0]?.locationCode||'';},
  async chooseCountry(l){this.location=l.code;this.topupProfile=null;this.sheet='plans';this.search='';await this.loadPlans();},
  async resultAction(action){await this.openResultProfile();if(this.detail)this.sheet=action;},
  async copyText(text){try{await navigator.clipboard.writeText(text);}catch{this.error='Select and copy the value manually.';}},

  message(e){return e.response?.data?.message||'Unable to connect. Please try again.';},money(k){return (Number(k)/100).toLocaleString('en-NG',{style:'currency',currency:'NGN'});},volume(b){return (Number(b||0)/1073741824).toLocaleString('en-NG',{maximumFractionDigits:2})+' GB';},date(d){return d?new Date(d).toLocaleDateString('en-GB',{day:'2-digit',month:'short',year:'numeric'}):'Not activated';},flag(c){return /^[A-Z]{2}$/.test(c||'')?Array.from(c,x=>String.fromCodePoint(127397+x.charCodeAt(0))).join(''):'🌍';},profileName(p){const code=this.profileCode(p);return this.locations.find(l=>l.code===code)?.name||p.details.packageList?.[0]?.packageName||'Travel eSIM';},remaining(p){return Math.max(0,Number(p.details.totalVolume||0)-Number(p.details.orderUsage||0));},remainingPercent(p){return Math.min(100,100*this.remaining(p)/Math.max(1,Number(p.details.totalVolume||0)));},statusLabel(s){return {GOT_RESOURCE:'Ready to install',IN_USE:'Active',USED_UP:'Data used',USED_EXPIRED:'Expired',UNUSED_EXPIRED:'Expired',CANCEL:'Cancelled',SUSPENDED:'Suspended'}[s]||s;},canTopup(p){return [2,3].includes(Number(p.details.supportTopUpType))&&!['USED_EXPIRED','UNUSED_EXPIRED','CANCEL','REVOKE'].includes(p.details.esimStatus);},safeUrl(s){try{return new URL(s).protocol==='https:';}catch{return false;}},activation(p){return String(p.details.ac||'').split('$');},installUrl(p){const url=p.details.shortUrl;try{const u=new URL(url);return u.protocol==='https:'&&u.hostname==='p.qrsim.net'?u.href:'';}catch{return '';}},
  async openTerms(){this.termsOpen=true;this.terms='Loading terms…';try{this.terms=(await axios.get('/api/esim/terms')).data.data.content||'Terms are not yet published. Please contact support.';}catch{this.terms='Unable to load terms. Please try again before purchasing.';}},
  async loadMine(){try{const [p,h]=await Promise.all([axios.get('/api/esim/profiles'),axios.get('/api/esim/purchases')]);this.profiles=p.data.data;this.purchases=h.data.data;}catch(e){this.error=this.message(e);}},
  async loadPlans(){this.plans=[];this.chosen=null;if(!this.location&&!this.topupProfile)return;this.loading=true;this.error='';try{this.plans=(await axios.get('/api/esim/packages',{params:this.topupProfile?{profile_id:this.topupProfile.id}:{location:this.location}})).data.data;this.plans.sort((a,b)=>Number(a.amount_kobo)-Number(b.amount_kobo));this.chosen=this.plans[0]||null;}catch(e){this.error=this.message(e);}finally{this.loading=false;}},
  selectPlan(p){this.sheet=null;this.selected=p;this.quote=null;this.days=1;this.compatible=false;this.error='';},
  async getQuote(){this.busy=true;try{const data={package_code:this.selected.package_code,location:this.location};if(this.selected.daily)data.period_num=Number(this.days);if(this.topupProfile)data.profile_id=this.topupProfile.id;this.quote=(await axios.post('/api/esim/quotes',data)).data.data;}catch(e){this.error=this.message(e);}finally{this.busy=false;}},
  savePayments(){localStorage.setItem(this.key,JSON.stringify(this.savedPayments));},
  rememberPayment(payment){const i=this.savedPayments.findIndex(p=>p.reference===payment.reference);if(i<0)this.savedPayments.push(payment);else this.savedPayments.splice(i,1,payment);this.savePayments();},
  forgetPayment(reference){this.savedPayments=this.savedPayments.filter(p=>p.reference!==reference);this.savePayments();},
  async purchase(saved=null){
    if(this.busy)return;
    this.busy=true;this.error='';
    let payment=saved;
    try{
      if(!payment){
        if(!this.quote)throw new Error('Confirm a price before purchasing.');
        payment=this.savedPayments.find(p=>p.quote_id===this.quote.id);
        if(!payment){const bytes=crypto.getRandomValues(new Uint8Array(14));payment={quote_id:this.quote.id,reference:'SLE'+Array.from(bytes,b=>b.toString(16).padStart(2,'0')).join(''),package_name:this.selected?.name||this.quote.package_name,expires_at:this.quote.expires_at,plan:this.selected,location:this.location,profile:this.topupProfile?{id:this.topupProfile.id,details:{packageList:this.topupProfile.details.packageList}}:null,days:this.days};}
      }
      if(payment.cancelled && payment.expires_at && new Date(payment.expires_at)<=new Date() && payment.plan){
        this.location=payment.location;this.topupProfile=payment.profile;this.selectPlan(payment.plan);this.days=payment.days||1;this.tab='buy';this.error='The saved price has expired. Confirm a current price to resume this plan.';return;
      }
      payment.cancelled=false;this.attempt=payment;this.rememberPayment(payment);
      const r=(await axios.post('/api/esim/purchases',{quote_id:payment.quote_id,reference:payment.reference})).data.data;
      if(!r||!['pending','processing','success','reversed'].includes(r.status))throw new Error('Unconfirmed purchase');
      this.result=r;this.selected=null;this.quote=null;this.forgetPayment(payment.reference);this.attempt=null;await this.loadMine();
    }catch(e){
      if(axios.isCancel(e)){payment.cancelled=true;this.rememberPayment(payment);this.selected=null;this.quote=null;this.attempt=null;}
      else{this.error=this.message(e);if([401,403,422,428,429].includes(e.response?.status)){if(payment)this.forgetPayment(payment.reference);this.attempt=null;this.quote=null;}}
    }finally{this.busy=false;}
  },
  async checkResult(){this.busy=true;try{this.result=(await axios.get(`/api/esim/purchases/${encodeURIComponent(this.result.reference)}`)).data.data;await this.loadMine();}catch(e){this.error=this.message(e);}finally{this.busy=false;}},
  async openProfile(p){this.detail=p;this.showQr=false;try{this.detail=(await axios.get(`/api/esim/profiles/${p.id}`)).data.data;}catch(e){this.error=this.message(e);}},
  async openResultProfile(){const id=this.result.profile_id;this.result=null;await this.loadMine();const p=this.profiles.find(p=>p.id===id);if(p)await this.openProfile(p);},
  async topup(p){this.topupProfile=p;this.tab='buy';this.sheet='plans';await this.loadPlans();},
  async copyActivation(){try{await navigator.clipboard.writeText(this.detail.details.ac);}catch{this.error='Select and copy the manual installation details.';}},
 },
};
</script>
