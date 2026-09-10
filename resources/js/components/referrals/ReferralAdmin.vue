<template>
  <div class="admin-page">
    <div class="admin-header">
      <div><h2>Referral Program Management</h2><p>Configure rewards and monitor every referral.</p></div>
      <button class="btn btn-outline-danger" @click="load"><i class="fas fa-sync-alt"></i> Refresh</button>
    </div>
    <div v-if="error" class="alert alert-danger">{{ error }}</div>

    <div class="summary-grid">
      <article><span>Total referred</span><strong>{{ summary.total_referred || 0 }}</strong></article>
      <article><span>Active users</span><strong>{{ summary.active_users || 0 }}</strong></article>
      <article><span>Inactive users</span><strong>{{ summary.inactive_users || 0 }}</strong></article>
      <article><span>Total bonus balances</span><strong>₦{{ money(summary.total_bonus_balances) }}</strong></article>
    </div>

    <section class="panel">
      <div class="panel-title"><h3>Program configuration</h3><button class="btn btn-danger" :disabled="saving" @click="saveSettings">{{ saving ? 'Saving…' : 'Save Settings' }}</button></div>
      <div class="settings-grid">
        <label>Program start date<input v-model="settings.program_starts_at" type="datetime-local" class="form-control" /><small>Changing this date changes which referrals qualify for fixed rewards.</small></label>
        <label>First transaction target (₦)<input v-model.number="settings['welcome.minimum_spend']" type="number" class="form-control" /></label>
        <label>Referrer first reward (₦)<input v-model.number="settings['welcome.referrer_reward']" type="number" class="form-control" /></label>
        <label>Friend first reward (₦)<input v-model.number="settings['welcome.friend_reward']" type="number" class="form-control" /></label>
        <label>Active-user spend (₦)<input v-model.number="settings['active.minimum_spend']" type="number" class="form-control" /></label>
        <label>Active-user reward (₦)<input v-model.number="settings['active.referrer_reward']" type="number" class="form-control" /></label>
      </div>
      <h4 class="subheading">Reward tiers</h4>
      <div class="tier-settings">
        <div v-for="tier in tierFields" :key="tier.key" class="tier-setting">
          <strong>{{ tier.label }}</strong>
          <label>Active users<input v-model.number="settings[tier.key + '.users']" type="number" class="form-control" /></label>
          <label>Reward (₦)<input v-model.number="settings[tier.key + '.reward']" type="number" class="form-control" /></label>
        </div>
      </div>
      <div class="settings-grid lower-settings">
        <label>Qualifying categories<input v-model="settings.qualifying_categories" class="form-control" /><small>Comma-separated category titles.</small></label>
        <label>Data category title<input v-model="settings.data_category" class="form-control" /></label>
        <label>Commission subcategory ID<input v-model.number="settings['data_commission.subcategory_id']" type="number" class="form-control" /></label>
        <label>Reward-history subcategory ID<input v-model.number="settings['order_history.subcategory_id']" type="number" class="form-control" /></label>
      </div>
    </section>

    <section class="panel">
      <div class="panel-title"><h3>Referred users</h3></div>
      <div class="filters">
        <input v-model="filters.search" class="form-control" placeholder="Search user, phone or referrer…" @keyup.enter="load(1)" />
        <select v-model="filters.status" class="form-control" @change="load(1)"><option value="">All statuses</option><option value="active">Active</option><option value="inactive">Inactive</option></select>
        <button class="btn btn-danger" @click="load(1)">Search</button>
      </div>
      <div class="table-responsive">
        <table class="table table-hover">
          <thead><tr><th>Referred user</th><th>Referrer</th><th>Registered</th><th>Spend</th><th>Status</th><th>Welcome reward</th></tr></thead>
          <tbody>
            <tr v-for="item in referrals.data" :key="item.id">
              <td><strong>{{ item.firstname }} {{ item.lastname }}</strong><small>{{ item.email }} · {{ item.phone }}</small></td>
              <td>{{ item.referrer_firstname }} {{ item.referrer_lastname }}<small>{{ item.referrer_email }}</small></td>
              <td>{{ date(item.created_at) }}</td><td>₦{{ money(item.qualifying_spend) }}</td>
              <td><span class="status" :class="item.became_active_at ? 'active' : 'inactive'">{{ item.became_active_at ? 'Active' : 'Inactive' }}</span></td>
              <td>{{ item.welcome_rewarded_at ? date(item.welcome_rewarded_at) : 'Not reached' }}</td>
            </tr>
            <tr v-if="!loading && (!referrals.data || !referrals.data.length)"><td colspan="6" class="text-center">No referrals found.</td></tr>
          </tbody>
        </table>
      </div>
      <pagination v-if="referrals.data" :data="referrals" :limit="5" @pagination-change-page="load"></pagination>
    </section>

    <section class="panel">
      <div class="panel-title"><h3>Recent referral rewards</h3></div>
      <div class="table-responsive">
        <table class="table"><thead><tr><th>Beneficiary</th><th>Type</th><th>Amount</th><th>Balance after</th><th>Date</th></tr></thead>
          <tbody><tr v-for="reward in rewards" :key="reward.id"><td>{{ beneficiaryName(reward) }}<small>{{ reward.beneficiary ? reward.beneficiary.email : '' }}</small></td><td>{{ humanize(reward.type) }}</td><td>₦{{ money(reward.amount) }}</td><td>₦{{ money(reward.balance_after) }}</td><td>{{ date(reward.created_at) }}</td></tr>
          <tr v-if="!rewards.length"><td colspan="5" class="text-center">No rewards recorded yet.</td></tr></tbody>
        </table>
      </div>
    </section>
  </div>
</template>

<script>
export default {
  name: 'ReferralAdmin',
  data() { return { summary: {}, settings: {}, referrals: { data: [] }, rewards: [], filters: { search: '', status: '' }, loading: false, saving: false, error: '', tierFields: [{ key: 'diamond', label: 'Diamond' }, { key: 'silver', label: 'Silver' }, { key: 'gold', label: 'Gold' }] }; },
  mounted() { this.load(); },
  methods: {
    load(page) { this.loading = true; this.error = ''; axios.get('/api/admin/referrals', { params: { page: page || 1, search: this.filters.search, status: this.filters.status } }).then(response => { const data = response.data.data; this.summary = data.summary; this.settings = this.prepareSettings(data.settings); this.referrals = data.referrals; this.rewards = data.rewards || []; }).catch(error => { this.error = (error.response && error.response.data.message) || 'Unable to load referral administration.'; }).finally(() => { this.loading = false; }); },
    saveSettings() { this.saving = true; axios.put('/api/admin/referrals/settings', this.settings).then(response => { this.settings = response.data.data; this.$toasted.show(response.data.message); }).catch(error => { const data = error.response && error.response.data; this.$toasted.show((data && data.message) || 'Unable to save settings.'); }).finally(() => { this.saving = false; }); },
    money(value) { return Number(value || 0).toLocaleString('en-NG', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); },
    date(value) { return value ? new Date(value).toLocaleString('en-NG') : '—'; },
    humanize(value) { return String(value || '').replace(/_/g, ' ').replace(/\b\w/g, letter => letter.toUpperCase()); },
    beneficiaryName(reward) { return reward.beneficiary ? `${reward.beneficiary.firstname} ${reward.beneficiary.lastname}` : `User #${reward.beneficiary_id}`; },
    prepareSettings(settings) { const values = Object.assign({}, settings); if (values.program_starts_at) { const parsed = new Date(values.program_starts_at); if (!Number.isNaN(parsed.getTime())) { const offset = parsed.getTimezoneOffset() * 60000; values.program_starts_at = new Date(parsed.getTime() - offset).toISOString().slice(0, 16); } } return values; },
  },
};
</script>

<style scoped>
.admin-page{padding:22px}.admin-header,.panel-title{display:flex;align-items:center;justify-content:space-between}.admin-header h2{font-weight:800;margin:0}.admin-header p{color:#747983}.summary-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin:20px 0}.summary-grid article,.panel{background:#fff;border:1px solid #e8eaed;border-radius:15px;padding:20px;box-shadow:0 5px 16px rgba(0,0,0,.04)}.summary-grid span{display:block;color:#737983;font-size:12px;text-transform:uppercase}.summary-grid strong{font-size:26px}.panel{margin-bottom:18px}.panel h3{font-weight:800}.settings-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:14px;margin-top:18px}.settings-grid label,.tier-setting label{font-weight:700;font-size:13px}.settings-grid small{display:block;color:#777}.subheading{margin:24px 0 10px;font-weight:800}.tier-settings{display:grid;grid-template-columns:repeat(3,1fr);gap:14px}.tier-setting{border:1px solid #eceef0;border-radius:12px;padding:15px}.tier-setting>strong{display:block;color:#e51c23;margin-bottom:10px}.lower-settings{grid-template-columns:2fr 1fr 1fr 1fr}.filters{display:grid;grid-template-columns:2fr 1fr auto;gap:10px;margin:15px 0}td small{display:block;color:#80858d}.status{display:inline-block;border-radius:20px;padding:5px 9px;font-size:11px;font-weight:800}.status.active{background:#e6f7ed;color:#167741}.status.inactive{background:#fff1df;color:#9a5b00}@media(max-width:900px){.summary-grid,.settings-grid,.tier-settings,.lower-settings{grid-template-columns:repeat(2,1fr)}}@media(max-width:600px){.admin-page{padding:12px}.summary-grid,.settings-grid,.tier-settings,.lower-settings,.filters{grid-template-columns:1fr}.admin-header{align-items:flex-start;gap:10px}}
</style>
