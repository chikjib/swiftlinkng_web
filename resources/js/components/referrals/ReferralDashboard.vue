<template>
  <div class="referral-page">
    <div class="hero-card">
      <div class="hero-copy">
        <span class="eyebrow">SWIFTLINKNG REFERRAL PROGRAM</span>
        <h1>Share Swiftlinkng.<br />Earn real rewards.</h1>
        <p>Invite friends and keep earning as they stay active.</p>
      </div>
      <i class="fas fa-gift hero-icon"></i>
    </div>

    <div class="page-heading">
      <div>
        <h2>Your Referral Dashboard</h2>
        <p>Track every invite, reward and active user.</p>
      </div>
      <button class="btn-refresh" :disabled="loading || referralsLoading" @click="refreshDashboard">
        <i class="fas fa-sync-alt" :class="{ 'fa-spin': loading }"></i> Refresh
      </button>
    </div>

    <div v-if="error" class="alert alert-danger">{{ error }}</div>

    <section class="white-card balance-card">
      <div>
        <span class="card-label"><i class="fas fa-wallet"></i> Referral bonus balance</span>
        <strong class="balance">₦{{ money(dashboard.commission) }}</strong>
      </div>
      <div class="button-row">
        <button class="primary-btn" :disabled="Number(dashboard.commission || 0) <= 0" @click="openTransfer">
          <i class="fas fa-exchange-alt"></i> Transfer
        </button>
        <button class="outline-btn" @click="showLearnMore = true">
          <i class="fas fa-info-circle"></i> Learn More
        </button>
      </div>
    </section>

    <section class="white-card">
      <h3><i class="fas fa-route"></i> How it works</h3>
      <div class="steps">
        <div v-for="(step, index) in steps" :key="step" class="step">
          <span>{{ index + 1 }}</span>
          <p>{{ step }}</p>
        </div>
      </div>
    </section>

    <div class="referral-values">
      <section class="white-card value-card">
        <span class="card-label">Referral link</span>
        <div><i class="fas fa-link"></i><code>{{ dashboard.referral_link }}</code>
          <button @click="copy(dashboard.referral_link, 'Referral link')"><i class="fas fa-copy"></i></button>
        </div>
      </section>
      <section class="white-card value-card">
        <span class="card-label">Referral code</span>
        <div><i class="fas fa-qrcode"></i><code>{{ dashboard.referral_code }}</code>
          <button @click="copy(dashboard.referral_code, 'Referral code')"><i class="fas fa-copy"></i></button>
        </div>
      </section>
    </div>

    <section class="white-card share-advert-card">
      <h3><i class="fas fa-bullhorn"></i> Copy and share this advert</h3>
      <pre>{{ referralAdvert }}</pre>
      <button class="primary-btn" @click="copy(referralAdvert, 'Referral advert')">
        <i class="fas fa-copy"></i> Copy Advert
      </button>
      <div class="repeated-link">
        <span class="card-label">Referral link</span>
        <code>{{ dashboard.referral_link }}</code>
        <button class="outline-btn" @click="copy(dashboard.referral_link, 'Referral link')">
          <i class="fas fa-link"></i> Copy Link
        </button>
      </div>
    </section>

    <section class="journey-section">
      <h3><i class="fas fa-rocket"></i> Your Reward Journey</h3>
      <p>Watch your active referral progress and unlock new cash rewards.</p>
      <div class="progress-note">
        You have referred <strong>{{ dashboard.total_referred || 0 }}</strong> users, with
        <strong>{{ dashboard.active_users || 0 }}</strong> active so far.<br />
        {{ nextTierMessage }}
      </div>
      <div class="tier-grid">
        <article v-for="tier in tiers" :key="tier.name" class="tier-card"
          :class="{ unlocked: activeUsers >= tier.users }">
          <i :class="tier.icon" :style="{ color: tier.color }"></i>
          <h4 :style="{ color: tier.color }">{{ tier.name }}</h4>
          <strong>₦{{ wholeMoney(tier.reward) }}</strong>
          <small>{{ activeUsers >= tier.users ? 'Unlocked' : 'Invite ' + tier.users + ' active friends' }}</small>
        </article>
      </div>
    </section>

    <section class="overview-section">
      <h3><i class="fas fa-chart-line"></i> Referral Overview</h3>
      <div class="overview-grid">
        <article><span>Total Referred</span><strong>{{ dashboard.total_referred || 0 }}</strong></article>
        <article><span>Active Users</span><strong>{{ dashboard.active_users || 0 }}</strong></article>
        <article><span>Inactive Users</span><strong>{{ dashboard.inactive_users || 0 }}</strong></article>
        <article><span>Verified Emails</span><strong>{{ dashboard.verified_referrals || 0 }}</strong></article>
        <article><span>Awaiting Verification</span><strong>{{ dashboard.unverified_referrals || 0 }}</strong></article>
        <article><span>All-time Earnings</span><strong>₦{{ money(dashboard.all_time_earnings) }}</strong></article>
      </div>
    </section>

    <section class="white-card referral-list-card">
      <div class="list-heading">
        <div>
          <h3><i class="fas fa-users"></i> Your Referrals</h3>
          <p>See who joined through your link and whether their email has been verified.</p>
        </div>
        <select v-model="verificationFilter" class="form-control verification-filter" @change="loadReferrals(1)">
          <option value="">All referrals</option>
          <option value="verified">Verified emails</option>
          <option value="unverified">Not verified</option>
        </select>
      </div>

      <div v-if="referralsError" class="alert alert-danger">{{ referralsError }}</div>
      <div v-if="referralsLoading" class="list-state">Loading your referrals…</div>
      <div v-else class="table-responsive">
        <table class="table referral-table">
          <thead>
            <tr><th>Name</th><th>Email</th><th>Email Status</th><th>Activity</th><th>Joined</th></tr>
          </thead>
          <tbody>
            <tr v-for="(referral, index) in referrals.data" :key="index">
              <td><strong>{{ referral.name }}</strong></td>
              <td>{{ referral.email }}</td>
              <td>
                <span class="verification-badge" :class="referral.email_verified ? 'verified' : 'unverified'">
                  <i :class="referral.email_verified ? 'fas fa-check-circle' : 'fas fa-clock'"></i>
                  {{ referral.email_verified ? 'Verified' : 'Not verified' }}
                </span>
              </td>
              <td>{{ referral.active ? 'Active' : 'Inactive' }}</td>
              <td>{{ referral.joined_at || '—' }}</td>
            </tr>
            <tr v-if="!referrals.data || !referrals.data.length">
              <td colspan="5" class="list-state">No referrals found for this filter.</td>
            </tr>
          </tbody>
        </table>
      </div>
      <pagination
        v-if="referrals.data && referrals.last_page > 1"
        :data="referrals"
        :limit="4"
        @pagination-change-page="loadReferrals"
      ></pagination>
    </section>

    <div v-if="showTransfer" class="modal-backdrop-custom" @click.self="showTransfer = false">
      <div class="dialog-card">
        <button class="dialog-close" @click="showTransfer = false">×</button>
        <h3>Transfer bonus to wallet</h3>
        <p>Available balance: <strong>₦{{ money(dashboard.commission) }}</strong></p>
        <label>Amount</label>
        <input v-model.number="transferAmount" type="number" min="0.01" step="0.01" class="form-control" />
        <button class="primary-btn full" :disabled="transferring" @click="transferBonus">
          {{ transferring ? 'Processing…' : 'Transfer to Wallet' }}
        </button>
      </div>
    </div>

    <div v-if="showLearnMore" class="modal-backdrop-custom" @click.self="showLearnMore = false">
      <div class="dialog-card learn-card">
        <button class="dialog-close" @click="showLearnMore = false">×</button>
        <h2>About the Swiftlinkng Program</h2>
        <p><strong>Earn more by sharing Swiftlinkng!</strong> Each friend you invite using your referral link helps you
          build real rewards as they stay active on the platform.</p>
        <h4>🔑 How it works</h4>
        <ol>
          <li>You earn ₦{{ wholeMoney(setting('welcome.referrer_reward', 50)) }} when your referral completes their
            first ₦{{ wholeMoney(setting('welcome.minimum_spend', 500)) }} transaction.</li>
          <li>You earn a small commission on every data purchase your downline makes.</li>
          <li>You unlock a one-time ₦{{ wholeMoney(setting('active.referrer_reward', 400)) }} bonus when an active user
            crosses the required threshold.</li>
        </ol>
        <h4>🎯 Reward tiers</h4>
        <p>Refer 5, 10 or 20 active users to unlock ₦2,000, ₦8,000 and ₦32,000 in bonuses.</p>
        <h4>🎁 Extra benefits</h4>
        <p>Your referred users also enjoy cashbacks, loyalty gifts and bonus perks as they buy more data.</p>
        <h4>🚀 Quick summary</h4>
        <p>Share your link → They complete their first ₦500 transaction → You both earn ₦50 → They become active users →
          You keep earning.</p>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'ReferralDashboard',
  data() {
    return {
      dashboard: {}, loading: false, error: '', showTransfer: false,
      referrals: { data: [] }, referralsLoading: false, referralsError: '', verificationFilter: '',
      showLearnMore: false, transferAmount: '', transferring: false,
      steps: [
        'Share your referral link',
        'They complete their first ₦500 transaction',
        'You both earn a ₦50 bonus',
        'Earn 0.2% Lifetime Commission on Every Successful Data Purchase by Your Downliners.',
        'They stay active and you keep earning',
      ],
    };
  },
  computed: {
    activeUsers() { return Number(this.dashboard.active_users || 0); },
    referralAdvert() {
      return `I use Swiftlinkng for affordable Data, Airtime & Bill Payments.\n\nThey're currently giving new users a ₦50 welcome bonus after email verification and their first ₦500 transaction. 🎁\n\nRegister with my invite link 👇\n${this.dashboard.referral_link || ''}`;
    },
    tiers() {
      return [
        { name: 'Diamond', users: Number(this.setting('diamond.users', 5)), reward: this.setting('diamond.reward', 2000), icon: 'far fa-gem', color: '#2776c9' },
        { name: 'Silver', users: Number(this.setting('silver.users', 10)), reward: this.setting('silver.reward', 8000), icon: 'fas fa-medal', color: '#737982' },
        { name: 'Gold', users: Number(this.setting('gold.users', 20)), reward: this.setting('gold.reward', 32000), icon: 'fas fa-trophy', color: '#b98208' },
      ];
    },
    nextTierMessage() {
      const next = this.tiers.find(tier => this.activeUsers < tier.users);
      return next ? `${next.users - this.activeUsers} more active user(s) to reach ${next.name}.` : 'You have reached the Gold tier!';
    },
  },
  mounted() { this.refreshDashboard(); },
  methods: {
    setting(key, fallback) { const settings = this.dashboard.program_settings || {}; return settings[key] !== undefined ? settings[key] : fallback; },
    money(value) { return Number(value || 0).toLocaleString('en-NG', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); },
    wholeMoney(value) { return Number(value || 0).toLocaleString('en-NG', { maximumFractionDigits: 0 }); },
    loadDashboard() {
      this.loading = true; this.error = '';
      axios.get('/api/referrals/dashboard').then(response => { this.dashboard = response.data.data; })
        .catch(error => { this.error = (error.response && error.response.data.message) || 'Unable to load referral dashboard.'; })
        .finally(() => { this.loading = false; });
    },
    loadReferrals(page) {
      this.referralsLoading = true; this.referralsError = '';
      axios.get('/api/referrals/downline', {
        params: { page: page || 1, verification: this.verificationFilter || undefined },
      }).then(response => { this.referrals = response.data.data; })
        .catch(error => { this.referralsError = (error.response && error.response.data.message) || 'Unable to load your referrals.'; })
        .finally(() => { this.referralsLoading = false; });
    },
    refreshDashboard() { this.loadDashboard(); this.loadReferrals(1); },
    openTransfer() { this.transferAmount = Number(this.dashboard.commission || 0).toFixed(2); this.showTransfer = true; },
    transferBonus() {
      if (!this.transferAmount || Number(this.transferAmount) <= 0) return;
      this.transferring = true;
      axios.post('/api/user/bonus/transfer', { amount: this.transferAmount, channel: 'Web' })
        .then(response => { this.$toasted.show(response.data.message); this.showTransfer = false; this.loadDashboard(); })
        .catch(error => { this.$toasted.show((error.response && error.response.data.message) || 'Transfer failed.'); })
        .finally(() => { this.transferring = false; });
    },
    copy(value, label) {
      if (!value) return;
      navigator.clipboard.writeText(String(value)).then(() => this.$toasted.show(`${label} copied.`));
    },
  },
};
</script>

<style scoped>
.referral-page {
  /* max-width: 1100px; */
  margin: 0 auto;
  padding: 22px;
  color: #20242c;
  font-size: 14px
}

.hero-card {
  position: relative;
  overflow: hidden;
  min-height: 190px;
  padding: 30px;
  border-radius: 24px;
  background: linear-gradient(135deg, #e51c23, #a80c14);
  color: #fff;
  box-shadow: 0 14px 30px rgba(229, 28, 35, .2)
}

.hero-copy {
  position: relative;
  z-index: 2
}

.eyebrow {
  display: inline-block;
  padding: 7px 12px;
  border-radius: 20px;
  background: rgba(255, 255, 255, .16);
  font-size: 11px;
  font-weight: 800;
  letter-spacing: .7px
}

.hero-card h1 {
  font-size: 28px;
  font-weight: 800;
  margin: 20px 0 10px
}

.hero-card p {
  color: #ffe9ea
}

.hero-icon {
  position: absolute;
  right: 25px;
  bottom: -20px;
  font-size: 135px;
  color: rgba(255, 255, 255, .13)
}

.page-heading {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin: 28px 0 14px
}

.page-heading h2 {
  font-size: 23px;
  font-weight: 800;
  margin: 0
}

.page-heading p {
  color: #747983;
  font-size: 13px;
  margin: 5px 0
}

.btn-refresh {
  border: 0;
  background: #fff;
  padding: 10px 14px;
  border-radius: 10px;
  color: #e51c23;
  box-shadow: 0 4px 14px rgba(0, 0, 0, .07)
}

.white-card {
  background: #fff;
  border: 1px solid #ebedef;
  border-radius: 18px;
  padding: 22px;
  margin-bottom: 16px;
  box-shadow: 0 6px 18px rgba(0, 0, 0, .04)
}

.white-card h3,
.journey-section h3,
.overview-section h3 {
  font-size: 18px;
  font-weight: 800
}

.white-card h3 i,
.journey-section h3 i,
.overview-section h3 i {
  color: #e51c23;
  margin-right: 8px
}

.balance-card {
  display: flex;
  justify-content: space-between;
  align-items: flex-end
}

.card-label {
  display: block;
  text-transform: uppercase;
  color: #777c85;
  font-size: 12px;
  font-weight: 800;
  letter-spacing: .6px
}

.card-label i {
  color: #e51c23;
  margin-right: 7px
}

.balance {
  display: block;
  font-size: 30px;
  margin-top: 12px
}

.button-row {
  display: flex;
  gap: 10px
}

.primary-btn,
.outline-btn {
  border-radius: 11px;
  padding: 10px 16px;
  font-size: 13px;
  font-weight: 700
}

.primary-btn {
  border: 1px solid #e51c23;
  background: #e51c23;
  color: #fff
}

.primary-btn:disabled {
  opacity: .45
}

.outline-btn {
  border: 1px solid #e51c23;
  background: #fff;
  color: #e51c23
}

.steps {
  display: flex;
  flex-direction: column;
  gap: 0;
  margin-top: 12px
}

.step {
  position: relative;
  display: flex;
  gap: 12px;
  align-items: flex-start;
  min-height: 48px;
  padding: 5px 0
}

.step:not(:last-child)::after {
  content: '';
  position: absolute;
  left: 14px;
  top: 36px;
  width: 1px;
  height: 20px;
  background: #ffc9cc
}

.step span {
  display: grid;
  place-items: center;
  min-width: 29px;
  height: 29px;
  border-radius: 50%;
  background: #ffeaeb;
  color: #e51c23;
  font-size: 12px;
  font-weight: 800
}

.step p {
  margin: 4px 0 0;
  font-size: 14px;
  line-height: 1.45
}

.referral-values {
  display: grid;
  grid-template-columns: 2fr 1fr;
  gap: 14px
}

.value-card>div {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-top: 10px
}

.value-card>div>i {
  color: #e51c23
}

.value-card code {
  flex: 1;
  overflow-wrap: anywhere;
  color: #20242c
}

.value-card button {
  border: 0;
  background: #ffeaeb;
  color: #e51c23;
  border-radius: 8px;
  padding: 8px 10px
}

.journey-section,
.overview-section {
  margin-top: 30px
}

.journey-section>p {
  color: #6e747e
}

.progress-note {
  text-align: center;
  background: #fff1f2;
  border-radius: 14px;
  padding: 16px;
  line-height: 1.6;
  margin: 15px 0
}

.tier-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 13px
}

.tier-card {
  text-align: center;
  background: #fff;
  border: 1px solid #e7e8eb;
  border-radius: 16px;
  padding: 20px
}

.tier-card.unlocked {
  border-color: #e51c23
}

.tier-card>i {
  font-size: 27px
}

.tier-card h4 {
  font-weight: 800;
  margin: 10px 0 5px
}

.tier-card strong {
  display: block;
  font-size: 18px
}

.tier-card small {
  display: block;
  color: #747983;
  margin-top: 6px
}

.overview-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 13px
}

.overview-grid article {
  background: #fff;
  border: 1px solid #ebedef;
  border-radius: 15px;
  padding: 18px;
  text-align: center
}

.overview-grid span {
  display: block;
  color: #777c85;
  font-size: 12px
}

.overview-grid strong {
  display: block;
  font-size: 19px;
  margin-top: 8px
}

.referral-list-card {
  margin-top: 30px
}

.share-advert-card {
  margin-top: 18px
}

.share-advert-card pre {
  margin: 16px 0;
  padding: 18px;
  border: 1px solid #eceff2;
  border-radius: 14px;
  background: #fafafa;
  color: #252a32;
  font-family: inherit;
  font-size: 15px;
  line-height: 1.65;
  white-space: pre-wrap
}

.repeated-link {
  display: flex;
  align-items: center;
  gap: 12px;
  flex-wrap: wrap;
  margin-top: 20px;
  padding-top: 18px;
  border-top: 1px solid #eceff2
}

.repeated-link code {
  flex: 1;
  min-width: 220px;
  overflow-wrap: anywhere;
  color: #b70f18
}

.list-heading {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 16px;
  margin-bottom: 18px
}

.list-heading p {
  color: #747983;
  margin: 5px 0 0
}

.verification-filter {
  width: 190px
}

.referral-table th {
  white-space: nowrap;
  color: #656b75;
  font-size: 12px;
  text-transform: uppercase
}

.referral-table td {
  vertical-align: middle;
  white-space: nowrap
}

.verification-badge {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  border-radius: 20px;
  padding: 6px 10px;
  font-size: 11px;
  font-weight: 800
}

.verification-badge.verified {
  background: #e4f7eb;
  color: #13733c
}

.verification-badge.unverified {
  background: #fff1d8;
  color: #935c00
}

.list-state {
  padding: 24px !important;
  text-align: center;
  color: #747983
}

.modal-backdrop-custom {
  position: fixed;
  z-index: 1050;
  inset: 0;
  background: rgba(24, 28, 35, .65);
  display: grid;
  place-items: center;
  padding: 20px
}

.dialog-card {
  position: relative;
  width: min(460px, 100%);
  max-height: 90vh;
  overflow: auto;
  background: #fff;
  border-radius: 18px;
  padding: 28px
}

.learn-card {
  width: min(700px, 100%);
  line-height: 1.6
}

.dialog-close {
  position: absolute;
  right: 14px;
  top: 10px;
  border: 0;
  background: none;
  font-size: 28px
}

.dialog-card label {
  font-weight: 700;
  margin-top: 10px
}

.full {
  width: 100%;
  margin-top: 16px
}

@media(max-width:800px) {

  .overview-grid {
    grid-template-columns: repeat(2, 1fr)
  }

  .referral-values {
    grid-template-columns: 1fr
  }

  .balance-card {
    display: block
  }

  .button-row {
    margin-top: 18px
  }

  .tier-grid {
    grid-template-columns: 1fr
  }

  .list-heading {
    flex-direction: column
  }

  .verification-filter {
    width: 100%
  }

  .hero-icon {
    font-size: 110px
  }

  .hero-card h1 {
    font-size: 25px
  }
}

@media(max-width:480px) {
  .referral-page {
    padding: 12px
  }

  .overview-grid {
    grid-template-columns: 1fr
  }

  .button-row {
    flex-direction: column
  }

  .page-heading {
    align-items: flex-start;
    gap: 10px
  }

  .hero-card {
    min-height: 170px;
    padding: 22px
  }
}
</style>
