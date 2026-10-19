<template>
  <main class="swift-service-page swift-internet-page">
    <h1 class="swift-service-heading"><button class="swift-back" type="button" @click="$router.back()" aria-label="Back"><i class="fas fa-arrow-left"></i></button>Buy Internet Plan</h1>
    <p v-if="message" class="alert" :class="failed ? 'alert-danger' : 'alert-success'" role="alert">{{ message }}</p>
    <form class="swift-form-stack" @submit.prevent="buy">
      <section class="swift-service-card"><div class="swift-provider-grid">
        <button v-for="network in networks" :key="network.key" class="swift-provider-option" :class="{'is-selected': selected === network.key}" type="button" :disabled="busy || pending" @click="select(network.key)">
          <img class="internet-brand" :src="`/template/images/services/internet_${network.key}.svg`" :alt="network.label" /><span>{{ network.label }}</span>
        </button>
      </div></section>
      <div class="swift-form-group"><label for="internet-number">Smart Number</label><div class="swift-field-wrap"><i class="fas fa-mobile-alt"></i><input id="internet-number" v-model="number" required inputmode="numeric" pattern="[0-9]{6,20}" maxlength="20" class="form-control" placeholder="0801 234 5678" :disabled="busy || pending" @input="number = number.replace(/\D/g, '')" /></div></div>
      <div class="swift-form-group"><label>Select Package</label><button class="swift-app-picker" type="button" aria-haspopup="dialog" :disabled="loading || busy || pending || !plans.length" @click="picker = true"><span class="swift-app-picker-icon"><i class="fas fa-layer-group"></i></span><strong>{{ plan ? (plan.name || plan.plan) : loading ? 'Loading packages…' : 'Select Package' }}</strong><i class="fas fa-chevron-down"></i></button><small v-if="!loading && !plans.length">Packages are temporarily unavailable. <button type="button" class="btn btn-link p-0" @click="load">Retry</button></small></div>
      <div class="swift-form-group"><label for="internet-amount">Amount</label><div class="swift-field-wrap"><i class="far fa-credit-card"></i><input id="internet-amount" :value="plan ? '₦' + plan.amount.toLocaleString() : ''" readonly class="form-control" placeholder="Amount" /></div></div>
      <button class="swift-primary-action" type="submit" :disabled="busy || loading || pending || !plan">{{ busy ? 'Processing…' : pending ? 'Purchase Pending — Check Transactions' : 'Buy Now' }}</button>
    </form>
    <div v-if="picker" class="swift-sheet-backdrop" role="presentation" @click.self="picker = false" @keydown.esc="picker = false">
      <section class="swift-bottom-sheet swift-data-plan-sheet" role="dialog" aria-modal="true" aria-labelledby="internet-package-title"><div class="swift-sheet-handle"></div><header class="swift-sheet-header"><span><h2 id="internet-package-title">{{ selectedLabel }} Packages</h2><small>{{ plans.length }} available packages</small></span><button type="button" aria-label="Close" @click="picker = false">&times;</button></header><div class="swift-app-sheet-list swift-plan-options"><button v-for="p in plans" :key="p.subcategory_id + p.plan" type="button" @click="choose(p)"><strong>{{ p.name || p.plan }}</strong><em>₦{{ Number(p.amount).toLocaleString() }}</em></button></div></section>
    </div>
  </main>
</template>
<script>
import axios from "axios";
const newKey = () => globalThis.crypto?.randomUUID?.() || `internet-${Date.now()}-${Math.random().toString(36).slice(2)}`;
export default {
  data: () => ({ selected: 'fibrex', number: '', plan: null, catalogue: [], loading: true, busy: false, pending: false, picker: false, message: '', failed: false, requestKey: newKey(), networks: [{key:'fibrex',label:'Fibre X'},{key:'smile',label:'Smile'},{key:'spectranet',label:'Spectranet'},{key:'nitel',label:'Nitel'}] }),
  computed: {
    plans() { return this.catalogue.filter(p => p.network === this.selected).flatMap(p => (p.products || []).map(plan => ({...plan, subcategory_id:p.id}))); },
    selectedLabel() { return this.networks.find(n => n.key === this.selected)?.label || ''; },
  },
  mounted() { this.load(); },
  methods: {
    headers() { return {Authorization:`Bearer ${localStorage.getItem('token')}`,Accept:'application/json'}; },
    select(key) { this.selected=key; this.plan=null; this.message=''; this.requestKey=newKey(); },
    choose(plan) { this.plan=plan; this.picker=false; this.requestKey=newKey(); },
    async load() { this.loading=true; try { const {data}=await axios.get('/api/internet/catalogue',{headers:this.headers()}); this.catalogue=data.data || []; this.plan=null; } catch(e) { this.failed=true; this.message=e.response?.data?.message || 'Unable to load Internet packages. Please retry.'; } finally { this.loading=false; } },
    async buy() {
      if (!this.plan || this.busy || this.pending) return;
      this.busy=true; this.message='';
      try { const {data}=await axios.post('/api/purchase/internet',{subcategory_id:this.plan.subcategory_id,plan_id:this.plan.plan,amount:this.plan.amount,phonenumber:this.number,request_key:this.requestKey,channel:'Web'},{headers:this.headers()}); this.message=data.message; this.failed=false; this.pending=data.status==='pending'; if (!this.pending) { this.plan=null; this.number=''; this.requestKey=newKey(); } }
      catch(e) { this.failed=true; this.message=e.response?.data?.message || 'Unable to confirm the purchase. Check Transactions before retrying.'; if (!e.response || e.response.status >= 500) this.pending=true; }
      finally { this.busy=false; }
    },
  },
};
</script>
<style scoped>
.swift-internet-page .swift-provider-option { min-width:0; padding:16px 3px; }
.swift-internet-page .swift-provider-option > span:last-child { font-size:12px; }
.swift-internet-page .internet-brand { width:82px; height:72px; }
.swift-internet-page .swift-provider-grid { grid-template-columns:repeat(4,minmax(0,1fr)); }
</style>
