<template>
  <main class="sl-service">
    <header class="sl-header"><div><h1>Fund Betting Wallet</h1><p>Verify your account and fund it securely.</p></div><i class="fas fa-futbol fa-2x" aria-hidden="true"></i></header>
    <p v-if="error" class="sl-alert" role="alert">{{ error }}</p>
    <p v-if="loading" role="status">Loading betting services…</p>
    <div class="sl-columns" v-else>
      <section class="sl-card">
        <div v-if="attempt" class="sl-alert"><p>Your last payment needs confirmation. Continue using the same payment reference.</p><button class="sl-btn" :disabled="busy" @click="fund">Continue Last Payment</button></div>
        <template v-if="saved.length"><div class="sl-row sl-between"><h2>Saved Betting Accounts</h2><button class="sl-btn ghost" @click="allSaved=true" :disabled="locked">See All</button></div><div class="sl-row"><button v-for="account in saved.slice(0,3)" :key="account.id" class="sl-btn ghost" :disabled="locked" @click="useSaved(account)">{{ account.biller_name }}<small>{{ mask(account.recharge_account) }}</small></button></div></template>
        <h2 class="mt-4">Select Betting Platform</h2>
        <div class="sl-platforms"><button v-for="b in billers" :key="b.biller_id" class="sl-platform" :class="{active:biller?.biller_id===b.biller_id}" :aria-pressed="biller?.biller_id===b.biller_id" :disabled="locked" @click="select(b)"><img v-if="safeUrl(b.biller_icon)" :src="b.biller_icon" alt="" @error="$event.target.style.display='none'"><span>{{ b.biller_name }}</span></button></div>
        <p v-if="!billers.length" class="sl-muted">Betting is currently unavailable.</p>
        <label v-if="items.length>1" class="sl-field">Funding option<select v-model="itemId" class="sl-input" :disabled="locked" @change="name=''; applyAmount()"><option value="" disabled>Select option</option><option v-for="i in items" :key="i.item_id" :value="i.item_id">{{ i.item_name }}</option></select></label>
        <label for="bet-account" class="sl-field">Betting Account ID / Phone Number</label><div class="sl-row"><input id="bet-account" v-model.trim="account" class="sl-input sl-grow" maxlength="15" :disabled="locked" @input="name=''" placeholder="Enter account ID"><button class="sl-btn" :disabled="locked || !itemId || !account" @click="verify">Verify</button></div>
        <div v-if="name" class="sl-success"><strong>✓ Account Verified</strong><div>Account Name: {{ name }}</div><div>Platform: {{ biller.biller_name }}</div><div>Account ID: {{ account }}</div></div>
        <label class="sl-toggle"><input type="checkbox" v-model="save" :disabled="locked"><span><strong>Save as Self Beneficiary (Optional)</strong><br><small class="sl-muted">Save for faster funding next time.</small></span></label>
        <label for="bet-amount" class="sl-field">Amount to Fund (₦)</label><input id="bet-amount" v-model="amount" type="number" min="1" step="1" class="sl-input" :disabled="locked || item?.is_fixed_amount" placeholder="5000">
        <button class="sl-btn sl-full mt-4" :disabled="locked || !name || pending" @click="fund">{{ busy ? 'Please wait…' : 'Fund Betting Wallet' }}</button><p v-if="pending" class="sl-muted mt-3">A payment is pending. Check it in Recent Funding.</p>
      </section>
      <aside class="sl-card"><h2>Recent Funding</h2><p v-if="!history.length" class="sl-muted">Your betting payments will appear here.</p><button v-for="row in history" :key="row.id" class="sl-btn ghost sl-full mb-3" :disabled="busy" @click="receipt=row"><span>{{ row.biller_name }}<small class="d-block">{{ money(row.amount) }} · {{ row.status }}</small></span><i class="fas fa-chevron-right"></i></button></aside>
    </div>
    <div v-if="receipt" class="sl-modal-backdrop" @click.self="closeReceipt"><section class="sl-modal sl-result" role="dialog" aria-modal="true" aria-labelledby="bet-result-title"><div class="sl-result-icon" :class="receipt.status">{{ receipt.status==='success' ? '✓' : ['failed','cancelled'].includes(receipt.status) ? '×' : '◷' }}</div><h2 id="bet-result-title">{{ receipt.status==='success' ? 'Funding Successful' : ['failed','cancelled'].includes(receipt.status) ? 'Funding Unsuccessful' : 'Funding Pending' }}</h2><div class="sl-price">{{ money(receipt.amount) }}</div><p>{{ receipt.status==='success' ? `Credited to your ${receipt.biller_name} account.` : ['failed','cancelled'].includes(receipt.status) ? 'Your Swiftlink wallet has been refunded.' : 'We are confirming your payment. Please do not submit another payment.' }}</p><dl class="sl-details"><div v-for="(value,label) in receiptDetails" :key="label"><dt>{{ label }}</dt><dd>{{ value }}</dd></div></dl><button v-if="['pending','processing'].includes(receipt.status)" class="sl-btn sl-full mb-2" :disabled="busy" @click="check">Check Status</button><button class="sl-btn secondary sl-full mb-2" @click="share">Share Receipt</button><button class="sl-btn sl-full" @click="closeReceipt">Done</button></section></div>
    <div v-if="allSaved" class="sl-modal-backdrop" @click.self="allSaved=false"><section class="sl-modal" role="dialog" aria-modal="true" aria-label="Saved betting accounts"><div class="sl-row sl-between"><h2>Saved Accounts</h2><button class="sl-btn ghost" @click="allSaved=false">Close</button></div><div v-for="a in saved" :key="a.id" class="sl-row mt-3"><button class="sl-btn ghost sl-grow" @click="allSaved=false; useSaved(a)">{{ a.biller_name }} · {{ a.recharge_account }}</button><button class="sl-btn secondary" @click="remove(a)" :disabled="busy">Remove</button></div></section></div>
  </main>
</template>
<script>
import axios from 'axios';
import Auth from '../../Auth.js';
import './service-ui.css';
export default {
  name:'BettingWallet',
  data(){return {billers:[],items:[],saved:[],history:[],biller:null,itemId:'',account:'',name:'',amount:'',save:false,loading:true,busy:false,error:'',receipt:null,allSaved:false,attempt:null};},
  computed:{key(){return `betting_attempt_${Auth.user?.id}`;},item(){return this.items.find(i=>i.item_id===this.itemId);},locked(){return this.busy||!!this.attempt;},pending(){return this.history.some(r=>['pending','processing'].includes(r.status));},receiptDetails(){const r=this.receipt;return {'Platform':r.biller_name,'Account Name':r.account_name,'Account ID':r.recharge_account,'Transaction ID':r.reference,'Date & Time':r.created_at,'Status':r.status};}},
  mounted(){try{this.attempt=JSON.parse(localStorage.getItem(this.key)||'null');}catch{this.error='Last payment details could not be restored. Check Recent Funding.';}this.load();},
  methods:{
    money(n){return Number(n).toLocaleString('en-NG',{style:'currency',currency:'NGN'});}, mask(s){return s.length>5?`${s.slice(0,2)}••••${s.slice(-3)}`:s;},safeUrl(url){return typeof url==='string'&&url.startsWith('https://');},message(e){return e.response?.data?.message||'Unable to connect. Please try again.';},
    async load(){this.loading=true;await Promise.all(['billers','accounts','fundings'].map(async (path,index)=>{try{const {data}=await axios.get(`/api/betting/${path}`);this[['billers','saved','history'][index]]=data.data;}catch(e){this.error=this.message(e);}}));this.loading=false;},
    async select(b){if(this.locked)return;this.biller=b;this.name='';this.items=[];this.itemId='';this.busy=true;try{this.items=(await axios.get(`/api/betting/billers/${encodeURIComponent(b.biller_id)}/items`)).data.data;if(this.items.length===1){this.itemId=this.items[0].item_id;this.applyAmount();}}catch(e){this.error=this.message(e);}finally{this.busy=false;}},
    applyAmount(){if(this.item?.is_fixed_amount)this.amount=this.item.amount;}, identity(){return {biller_id:this.biller.biller_id,item_id:this.itemId,recharge_account:this.account};},
    async verify(){this.busy=true;this.name='';this.error='';try{this.name=(await axios.post('/api/betting/verify',this.identity())).data.data.account_name;}catch(e){this.error=this.message(e);}finally{this.busy=false;}},
    async useSaved(a){const b=this.billers.find(b=>b.biller_id===a.biller_id);if(!b){this.error='This saved platform is unavailable.';return;}await this.select(b);this.account=a.recharge_account;if(this.itemId)await this.verify();},
    async remove(a){this.busy=true;try{await axios.delete(`/api/betting/accounts/${a.id}`);this.saved=this.saved.filter(s=>s.id!==a.id);}catch(e){this.error=this.message(e);}finally{this.busy=false;}},
    async fund(){if(this.busy)return;this.error='';if(!this.attempt){const n=Number(this.amount);if(!this.name||!Number.isInteger(n)||n<1){this.error='Verify your account and enter a whole-naira amount.';return;}for(const s of [this.biller,this.item]){if((s.min_amount!=null&&n<s.min_amount)||(s.max_amount!=null&&n>s.max_amount)){this.error='Amount is outside this platform’s limits.';return;}}}
      this.busy=true;try{if(!this.attempt){if(this.save){await axios.post('/api/betting/accounts',this.identity());this.saved=(await axios.get('/api/betting/accounts')).data.data;}const bytes=crypto.getRandomValues(new Uint8Array(14));this.attempt={...this.identity(),amount:Number(this.amount),reference:'SLB'+Array.from(bytes,b=>b.toString(16).padStart(2,'0')).join('')};localStorage.setItem(this.key,JSON.stringify(this.attempt));}
      const {data}=await axios.post('/api/betting/fundings',this.attempt);if(data.data?.reference!==this.attempt.reference)throw new Error('Unconfirmed response');this.receipt=data.data;localStorage.removeItem(this.key);this.attempt=null;
      }catch(e){if(axios.isCancel(e))return;this.error=this.message(e);if([401,403,422,428,429].includes(e.response?.status)){localStorage.removeItem(this.key);this.attempt=null;}}finally{this.busy=false;}
    },
    async check(){this.busy=true;try{this.receipt=(await axios.get(`/api/betting/fundings/${encodeURIComponent(this.receipt.reference)}`)).data.data;}catch(e){this.error=this.message(e);}finally{this.busy=false;}},
    async share(){const text=`Swiftlink Betting Receipt\nAmount: ${this.money(this.receipt.amount)}\n`+Object.entries(this.receiptDetails).map(([k,v])=>`${k}: ${v}`).join('\n');try{if(navigator.share)await navigator.share({text});else{await navigator.clipboard.writeText(text);this.error='Receipt copied.';}}catch{}},
    closeReceipt(){this.receipt=null;this.load();},
  },
};
</script>
