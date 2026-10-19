<template>
  <main class="sl-service internet-admin">
    <header class="sl-header"><div><h1>Internet Settings</h1><p>Manage packages and prices for the website and app.</p></div></header>
    <p v-if="message" class="sl-alert" :class="{ 'is-error': failed }" role="status">{{ message }}</p>
    <nav class="provider-tabs" aria-label="Internet providers">
      <button v-for="item in definitions" :key="item.key" type="button" :disabled="busy" :aria-pressed="selected === item.key" :class="{ selected: selected === item.key }" @click="chooseProvider(item.key)">
        <img :src="`/template/images/services/internet_${item.key}.svg`" :alt="item.name" /><strong>{{ item.name }}</strong><small>{{ item.route }}</small>
      </button>
    </nav>
    <p v-if="loading" role="status">Loading Internet settings…</p>
    <template v-else-if="settings">
      <section class="sl-card">
        <h2>{{ definition.name }}</h2>
        <form @submit.prevent="saveProvider">
          <label class="sl-toggle"><input type="checkbox" v-model="settings.enabled" :disabled="busy" /> Enable this provider</label>
          <div class="pricing-fields">
            <label class="sl-field">Price multiplier<input v-model="settings.multiplier" class="sl-input" type="number" min="1" max="10" step="0.0001" placeholder="Keep existing pricing" :disabled="busy" /></label>
            <label class="sl-field">Additional fee (₦)<input v-model="settings.fixed_fee" class="sl-input" type="number" min="0" max="1000000" step="0.01" required :disabled="busy" /></label>
          </div>
          <p class="sl-muted">With a multiplier: provider cost × multiplier + fee. Leave it blank to retain existing prices and add only the fee. A package price override is the final selling price. Charges round up to whole naira.</p>
          <p v-if="selected === 'fibrex'" class="sl-muted">Existing AutoSync packages and customer-tier prices are retained when the multiplier is blank. If a package has no cost price, its existing tier price is the multiplier's starting amount. Sync reads the existing AutoSync configuration.</p>
          <p v-if="selected === 'spectranet' || selected === 'nitel'" class="sl-muted">The Sochitel account and currency conversion are configured in <a href="/dashboard/international-admin">International Settings</a>. These Internet pricing controls work independently of the international route switch.</p>
          <p v-if="selected === 'smile'" class="sl-muted">Uses the existing VTpass API credentials configured on the server.</p>
          <button class="sl-btn" :disabled="busy">Save provider settings</button>
        </form>
      </section>
      <section class="sl-card">
        <div class="admin-actions"><button type="button" class="sl-btn" :disabled="busy" @click="run('sync')">{{ busyAction === 'sync' ? 'Syncing…' : 'Sync packages' }}</button><button type="button" class="sl-btn ghost" :disabled="busy" @click="run('test')">{{ busyAction === 'test' ? 'Testing…' : 'Test connection' }}</button></div>
        <p class="sl-muted">Last sync: {{ date(settings.synced_at) }}</p>
        <p v-if="!settings.synced_at" class="sl-muted">Before the first sync, available provider packages use existing pricing. After syncing, only synced and enabled packages are offered. New provider packages appear after the next sync.</p>
        <p v-if="settings.tested_at" :class="{ 'test-failed': settings.test_status === 'failed' }">Last test: {{ date(settings.tested_at) }} — {{ settings.test_message }}</p>
        <p class="sl-muted">Connection tests make read-only requests. No purchase is submitted.</p>
      </section>
      <section class="sl-card">
        <div class="package-heading"><h2>Packages <small>({{ packages.length }})</small></h2><input v-model.trim="search" class="sl-input" aria-label="Search packages" placeholder="Search packages" @input="page = 1" /></div>
        <p v-if="!packages.length" class="sl-muted">No synced packages yet. Click Sync packages to load them.</p>
        <div v-else class="package-table"><table><thead><tr><th>Package</th><th>Available</th><th>Cost</th><th>Price override</th><th>Selling price</th><th>Action</th></tr></thead><tbody>
          <tr v-for="item in visiblePackages" :key="item.id">
            <td><strong>{{ item.name }}</strong><small v-if="!item.available" class="test-failed">No longer in the provider catalogue</small><a v-if="item.source_subcategory_id" :href="`/dashboard/edit-category/${item.source_subcategory_id}`">Original AutoSync configuration</a></td>
            <td><label><input v-model="item.enabled" type="checkbox" :disabled="busy" :aria-label="`Enable ${item.name}`" /> Enabled</label></td>
            <td>{{ money(item.cost) }}</td>
            <td><input v-model="item.price_override" type="number" min="1" max="100000000" step="0.01" class="sl-input price-input" placeholder="Use provider pricing" :disabled="busy" :aria-label="`Price override for ${item.name}`" /></td>
            <td><strong>{{ item.selling_price === null ? 'Price needs review' : money(item.selling_price) }}</strong><small v-if="selected === 'fibrex'">Retail tier preview</small></td>
            <td><button type="button" class="sl-btn ghost" :disabled="busy" @click="savePackage(item)">Save package</button></td>
          </tr>
        </tbody></table></div>
        <p v-if="packages.length && !filteredPackages.length">No packages match your search.</p>
        <div v-if="filteredPackages.length > pageSize" class="pagination-controls"><button type="button" class="sl-btn ghost" :disabled="page <= 1 || busy" @click="page--">Previous</button><span>Page {{ page }} of {{ pageCount }}</span><button type="button" class="sl-btn ghost" :disabled="page >= pageCount || busy" @click="page++">Next</button></div>
        <p class="sl-muted">Switches and overrides survive package syncs. Unavailable packages cannot be purchased. Pending purchases keep their original price and provider.</p>
      </section>
    </template>
  </main>
</template>

<script>
import axios from 'axios';
import './service-ui.css';
const definitions = [
  { key: 'fibrex', name: 'Fibre X', route: 'AutoSync' },
  { key: 'smile', name: 'Smile', route: 'VTpass' },
  { key: 'spectranet', name: 'Spectranet', route: 'Sochitel' },
  { key: 'nitel', name: 'Nitel', route: 'Sochitel' },
];
export default {
  data: () => ({ definitions, selected: 'fibrex', providers: [], packages: [], loading: true, busy: false, busyAction: '', message: '', failed: false, search: '', page: 1, pageSize: 20 }),
  computed: {
    definition() { return definitions.find(item => item.key === this.selected); },
    settings() { return this.providers.find(item => item.provider === this.selected); },
    filteredPackages() { const value = this.search.toLowerCase(); return this.packages.filter(item => item.name.toLowerCase().includes(value)); },
    visiblePackages() { return this.filteredPackages.slice((this.page - 1) * this.pageSize, this.page * this.pageSize); },
    pageCount() { return Math.max(1, Math.ceil(this.filteredPackages.length / this.pageSize)); },
  },
  mounted() { this.initialise(); },
  methods: {
    config() { return { headers: { Authorization: `Bearer ${localStorage.getItem('token')}`, Accept: 'application/json' } }; },
    endpoint(suffix = '') { return `/api/admin/internet/providers/${this.selected}${suffix}`; },
    nullableNumber(value) { return value === '' || value === null || value === undefined ? null : Number(value); },
    date(value) { return value ? new Date(String(value).replace(' ', 'T')).toLocaleString() : 'Never'; },
    money(value) { return new Intl.NumberFormat('en-NG', { style: 'currency', currency: 'NGN', maximumFractionDigits: 2 }).format(Number(value)); },
    setProviders(rows) { this.providers = rows.map(item => ({ ...item, enabled: Boolean(item.enabled) })); },
    error(error) { this.failed = true; this.message = error.response?.data?.message || 'Unable to complete this action. Please retry.'; },
    async loadPackages() { const provider = this.selected; const { data } = await axios.get(this.endpoint('/packages'), this.config()); if (provider === this.selected) this.packages = data.data; },
    async initialise() { this.loading = true; try { const { data } = await axios.get('/api/admin/internet/providers', this.config()); this.setProviders(data.data); await this.loadPackages(); } catch (error) { this.error(error); } finally { this.loading = false; } },
    async chooseProvider(provider) { if (this.busy) return; this.selected = provider; this.search = ''; this.page = 1; this.message = ''; this.packages = []; this.loading = true; try { await this.loadPackages(); } catch (error) { this.error(error); } finally { this.loading = false; } },
    async saveProvider() {
      this.busy = true; this.message = '';
      try { const { data } = await axios.put(this.endpoint(), { enabled: this.settings.enabled, multiplier: this.nullableNumber(this.settings.multiplier), fixed_fee: Number(this.settings.fixed_fee) }, this.config()); this.setProviders(data.data); await this.loadPackages(); this.failed = false; this.message = 'Provider settings saved for both web and app.'; }
      catch (error) { this.error(error); } finally { this.busy = false; }
    },
    async savePackage(item) {
      this.busy = true; this.message = '';
      try { const { data } = await axios.put(this.endpoint(`/packages/${item.id}`), { enabled: item.enabled, price_override: this.nullableNumber(item.price_override) }, this.config()); this.packages = data.data; this.failed = false; this.message = 'Package settings saved for both web and app.'; }
      catch (error) { this.error(error); } finally { this.busy = false; }
    },
    async run(action) {
      this.busy = true; this.busyAction = action; this.message = '';
      try { const { data } = await axios.post(this.endpoint(`/${action}`), {}, this.config()); this.failed = false; this.message = data.message; }
      catch (error) { this.error(error); }
      finally { try { const { data } = await axios.get('/api/admin/internet/providers', this.config()); this.setProviders(data.data); if (action === 'sync') await this.loadPackages(); } catch (error) { this.error(error); } this.busy = false; this.busyAction = ''; }
    },
  },
};
</script>

<style scoped>
.internet-admin { max-width: 1250px; }
.internet-admin .sl-header h1 { color: #fff !important; }
.provider-tabs { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 12px; margin: 22px 0; }
.provider-tabs button { display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 8px; border: 1px solid #ddd; border-radius: 16px; padding: 16px 8px; background: transparent; color: inherit; }
.provider-tabs button.selected { border-color: #e30613; box-shadow: 0 0 0 1px #e30613; }
.provider-tabs img { width: 80px; height: 58px; object-fit: contain; }
.pricing-fields { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
.admin-actions, .pagination-controls { display: flex; align-items: center; gap: 12px; flex-wrap: wrap; }
.package-heading { display: flex; justify-content: space-between; gap: 20px; align-items: center; margin-bottom: 15px; }
.package-heading > input { max-width: 320px; }
.package-table { overflow-x: auto; }
table { width: 100%; min-width: 800px; border-collapse: collapse; }
th, td { text-align: left; padding: 14px 10px; border-bottom: 1px solid #ddd; vertical-align: middle; }
td small, td a { display: block; margin-top: 5px; font-size: 12px; }
.price-input { min-width: 150px; }
.pagination-controls { justify-content: flex-end; margin: 16px 0; }
.test-failed, .is-error { color: #b52131; }
@media (max-width: 600px) { .provider-tabs { gap: 7px; } .provider-tabs img { width: 54px; } .provider-tabs button { font-size: 12px; } .pricing-fields { grid-template-columns: 1fr; } .package-heading { flex-direction: column; align-items: stretch; } }
@media (prefers-color-scheme: dark) { .provider-tabs button, th, td { border-color: #45454e; } .test-failed, .is-error { color: #ff8790; } }
</style>
