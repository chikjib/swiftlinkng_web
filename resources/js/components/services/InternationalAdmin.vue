<template>
 <main class="sl-service">
  <h1>International Settings</h1>
  <form v-if="settings" class="sl-card" @submit.prevent="save">
   <label><input type="checkbox" v-model="settings.enabled"> Enable international top-ups</label>
   <label class="sl-field">Top-up route
    <select class="sl-input" v-model="settings.provider"><option value="vtugate">VTUgate</option><option value="sochitel">Sochitel</option></select>
   </label>
   <p>Changing the route applies to new purchases on both the website and app. Pending payments stay with their original provider.</p>
   <label class="sl-field">Price multiplier<input class="sl-input" type="number" step="0.0001" min="1" max="10" v-model="settings.multiplier" required></label>
   <p>Provider cost in NGN × multiplier = customer price. ₦6,000 × 1.03 = ₦6,180.</p>
   <h2>VTUgate</h2>
   <label class="sl-field">API key / international code<input class="sl-input" type="password" v-model="apiKey" autocomplete="new-password" placeholder="Leave blank to keep the saved key"></label>
   <p>{{ settings.api_key_configured?'API key configured':'API key required before enabling VTUgate' }}.</p>
   <h2>Sochitel</h2>
   <p>{{ settings.sochitel_base_url_configured?'API URL configured on server.':'Set SOCHITEL_BASE_URL on the server to the API URL assigned by Sochitel.' }}</p>
   <label class="sl-field">Account user ID<input class="sl-input" v-model="settings.sochitel_user_id" inputmode="numeric" pattern="[0-9]+"></label>
   <label class="sl-field">RSA private key<textarea class="sl-input" v-model="sochitelKey" rows="4" autocomplete="off" spellcheck="false" placeholder="Paste the 4096-bit private key; leave blank to keep the saved key"></textarea></label>
   <p>Upload the matching public certificate in the <a href="https://clientportal.sochitel.com/business/settings/rsakeysconfig.html" target="_blank" rel="noopener noreferrer">Sochitel portal</a>. Both providers’ secrets are saved encrypted and never returned here. {{ settings.sochitel_key_configured?'Sochitel private key saved.':'' }}</p>
   <label class="sl-field">Sochitel account currency<input class="sl-input" v-model="settings.sochitel_currency" pattern="[A-Z]{3}" maxlength="3" required placeholder="NGN"></label>
   <label v-if="settings.sochitel_currency!=='NGN'" class="sl-field">NGN per 1 {{ settings.sochitel_currency }}<input class="sl-input" type="number" min="0.000001" max="1000000" step="0.000001" v-model="settings.sochitel_ngn_rate" required></label>
   <p v-if="settings.sochitel_currency!=='NGN'">Sochitel account cost × this exchange rate × multiplier = amount charged in NGN. Keep this rate current.</p>
   <p>First save the credentials while VTUgate is selected or top-ups are disabled. Then test the saved connection, select Sochitel, and save again to activate it.</p>
   <p>{{ settings.sochitel_verified?'Saved Sochitel credentials verified.':'Sochitel connection needs verification before activation.' }}</p>
   <h3>Sochitel operator logos</h3>
   <p>Sochitel’s catalogue does not include logos. Add an HTTPS image URL against its operator ID to display the logo on both screens.</p>
   <div v-for="(logo,index) in logos" :key="index" class="sl-row mb-2"><input class="sl-input" v-model="logo.id" placeholder="Operator ID" aria-label="Sochitel operator ID" pattern="[0-9]+" required><input class="sl-input" v-model="logo.url" type="url" placeholder="https://…" aria-label="Operator logo URL" required><button class="sl-btn ghost" type="button" @click="logos.splice(index,1)">Remove</button></div>
   <button class="sl-btn ghost" type="button" @click="logos.push({id:'',url:''})">Add Operator Logo</button>
   <h2>Country calling codes</h2><p>Override a prefix using the country’s two-letter ISO code.</p>
   <div v-for="(c,index) in codes" :key="index" class="sl-row mb-2"><input class="sl-input" v-model="c.iso" placeholder="GH" aria-label="ISO country code" pattern="[A-Z]{2}" maxlength="2" required><input class="sl-input" v-model="c.code" placeholder="+233" aria-label="Calling code" pattern="\+[1-9][0-9]{0,3}" required><button class="sl-btn ghost" type="button" @click="codes.splice(index,1)">Remove</button></div>
   <button class="sl-btn ghost" type="button" @click="codes.push({iso:'',code:''})">Add Country Code</button>
   <button class="sl-btn sl-full mt-3" :disabled="busy">Save Settings</button>
  </form>
  <p v-if="message" :class="saved?'sl-success':'sl-alert'" class="mt-3" role="status">{{ message }}</p>
  <section v-if="settings" class="sl-card"><button class="sl-btn" :disabled="busy" @click="testConnection">Test Sochitel Connection</button><p v-if="testMessage" :class="testSuccess?'sl-success':'sl-alert'" class="mt-3" role="status">{{ testMessage }}</p><p>Reads account balance and available products only. It does not buy a top-up. Uses the last saved credentials.</p></section>
  <section class="sl-card"><h2>International Transactions</h2><button class="sl-btn ghost" @click="load">Refresh</button><div class="sl-table-wrap"><table class="sl-table"><thead><tr><th>Reference</th><th>Customer</th><th>Route</th><th>Amount</th><th>Status</th><th>Provider ID</th><th>Action</th></tr></thead><tbody><tr v-for="p in transactions.data" :key="p.id"><td>{{ p.reference }}</td><td>{{ p.user_id }}</td><td>{{ p.provider==='sochitel'?'Sochitel':'VTUgate' }}</td><td>₦{{ (p.amount_kobo/100).toFixed(2) }}</td><td>{{ p.status }}</td><td>{{ p.transaction_id||'Awaiting confirmation' }}</td><td><button v-if="['pending','processing'].includes(p.status)" class="sl-btn ghost" @click="resolving=p;evidence='';outcome='';message='';saved=false">Resolve</button></td></tr></tbody></table></div><div class="sl-row"><button class="sl-btn ghost" :disabled="transactions.current_page<=1" @click="load(transactions.current_page-1)">Previous</button><button class="sl-btn ghost" :disabled="transactions.current_page>=transactions.last_page" @click="load(transactions.current_page+1)">Next</button></div></section>
  <div v-if="resolving" class="sl-modal-backdrop"><form class="sl-modal" @submit.prevent="resolve"><h2>Resolve {{ resolving.reference }}</h2><p>Confirm delivery or refund with {{ resolving.provider==='sochitel'?'Sochitel':'VTUgate' }} first. Record their support reference or evidence.</p><select v-model="outcome" class="sl-input" required><option disabled value="">Select confirmed outcome</option><option value="success">Delivered successfully</option><option value="reversed">Not delivered — refund wallet</option></select><textarea v-model="evidence" class="sl-input mt-3" minlength="15" maxlength="2000" required placeholder="Provider confirmation evidence"></textarea><button class="sl-btn sl-full mt-3" :disabled="busy">Record Outcome</button><p v-if="message" class="sl-alert" role="status">{{ message }}</p><button class="sl-btn ghost sl-full mt-2" type="button" @click="resolving=null">Cancel</button></form></div>
 </main>
</template>
<script>
import axios from 'axios';
import './service-ui.css';
export default {
 data(){return {settings:null,codes:[],logos:[],apiKey:'',sochitelKey:'',busy:false,message:'',saved:false,testMessage:'',testSuccess:false,transactions:{data:[]},resolving:null,evidence:'',outcome:''};},
 async mounted(){try{await this.settingsLoad();}catch(e){this.message=this.error(e);}await this.load();},
 methods:{
  error(e){this.saved=false;return e.response?.data?.message||'Request failed.';},
  async settingsLoad(){this.settings=(await axios.get('/api/admin/international/settings')).data.data;this.codes=Object.entries(this.settings.calling_codes).map(([iso,code])=>({iso,code}));this.logos=Object.entries(this.settings.sochitel_logos).map(([id,url])=>({id,url}));},
  async save(){this.busy=true;this.saved=false;this.message='';this.testMessage='';try{
   if(new Set(this.codes.map(c=>c.iso)).size!==this.codes.length)throw new Error('Duplicate country code');
   if(new Set(this.logos.map(c=>c.id)).size!==this.logos.length)throw new Error('Duplicate operator ID');
   this.settings=(await axios.put('/api/admin/international/settings',{enabled:this.settings.enabled,provider:this.settings.provider,multiplier:String(this.settings.multiplier),calling_codes:Object.fromEntries(this.codes.map(c=>[c.iso,c.code])),api_key:this.apiKey||null,sochitel_user_id:this.settings.sochitel_user_id||null,sochitel_private_key:this.sochitelKey||null,sochitel_currency:this.settings.sochitel_currency,sochitel_ngn_rate:String(this.settings.sochitel_ngn_rate),sochitel_logos:Object.fromEntries(this.logos.map(c=>[c.id,c.url]))})).data.data;
   this.apiKey='';this.sochitelKey='';this.message='International settings saved.';this.saved=true;
  }catch(e){this.message=e.message?.startsWith('Duplicate ')?e.message:this.error(e);}finally{this.busy=false;}},
  async testConnection(){this.busy=true;this.testMessage='';this.testSuccess=false;try{const result=(await axios.post('/api/admin/international/sochitel/test')).data.data;this.settings.sochitel_verified=true;this.testMessage=`Connection verified. ${result.products} compatible products. Account currency: ${result.currency}. Balance: ${result.balance??'unavailable'}.`;this.testSuccess=true;}catch(e){this.testMessage=this.error(e);}finally{this.busy=false;}},
  async load(page=1){try{this.transactions=(await axios.get('/api/admin/international/transactions',{params:{page:typeof page==='number'?page:1}})).data.data;}catch(e){this.message=this.error(e);}},
  async resolve(){this.busy=true;try{await axios.post(`/api/admin/international/transactions/${this.resolving.id}/resolve`,{outcome:this.outcome,evidence:this.evidence});this.resolving=null;this.message='Transaction outcome recorded.';this.saved=true;await this.load();}catch(e){this.message=this.error(e);}finally{this.busy=false;}}
 }
};
</script>
