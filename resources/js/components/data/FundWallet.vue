<template>
  <main class="swift-service-page">
    <h1 class="swift-service-heading"><button class="swift-back" type="button" @click="$router.back()"><i class="fas fa-arrow-left"></i></button>Fund Wallet</h1>
    <section class="swift-fund-hero"><span class="swift-transaction-icon"><i class="fas fa-wallet"></i></span><div><h2>Fund your wallet</h2><p>Choose a secure funding method</p></div><i class="fas fa-shield-alt ml-auto"></i></section>
    <div v-if="errorflag" class="alert alert-danger swift-alert">{{ errorflag }}</div>

    <div class="swift-funding-methods">
      <button class="swift-funding-card" type="button" @click="activeMethod = activeMethod === 'card' ? '' : 'card'"><span class="swift-transaction-icon"><i class="far fa-credit-card"></i></span><span><strong>ATM Card</strong><em>💳 Instant Funding</em><small>Enter amount, review charge &amp; pay securely.</small></span><i class="fas fa-chevron-right"></i></button>
      <form v-if="activeMethod === 'card'" class="swift-service-card swift-form-stack" @submit.prevent="makePayment"><div class="swift-form-group"><label for="fund-amount">Amount</label><div class="swift-field-wrap"><i class="far fa-credit-card"></i><input id="fund-amount" v-model.number="form.amount" required min="100" type="number" class="form-control" placeholder="Enter amount" /></div></div><button class="swift-primary-action" :disabled="loading">{{ loading ? 'Opening secure checkout…' : 'Continue' }}</button></form>

      <button class="swift-funding-card" type="button" @click="activeMethod = activeMethod === 'personal' ? '' : 'personal'"><span class="swift-transaction-icon"><i class="fas fa-university"></i></span><span><strong>My Personal Account <b class="swift-recommended">Recommended</b></strong><em>⚡ Instant Funding</em><small>OPay • PalmPay • Moniepoint — fund anytime.</small></span><i class="fas fa-chevron-right"></i></button>
      <section v-if="activeMethod === 'personal'" class="swift-personal-accounts">
        <header class="swift-personal-intro">
          <strong>Fund Your Wallet Instantly</strong>
          <p>Create a personal bank account, save it and transfer to it anytime. Your wallet is credited automatically.</p>
        </header>
        <article v-for="account in personalAccounts" :key="account.key" class="swift-service-card">
          <div class="swift-personal-account-row">
            <div class="swift-personal-account-copy">
              <strong>{{ account.label }} <b v-if="account.recommended" class="swift-recommended">Recommended</b></strong>
              <span class="swift-account-charge">{{ account.charge }}</span>
              <small>{{ account.example }}</small>
              <small>{{ account.verification }}</small>
              <div v-if="account.data" class="swift-account-details">
                <span>{{ account.data.accountName }}</span>
                <button class="swift-copy-number" type="button" @click="copyAccount(account.data.accountNumber)">{{ account.data.accountNumber }} <i class="far fa-copy"></i></button>
              </div>
              <small v-else class="swift-empty-account">No account created yet.</small>
            </div>
            <button v-if="!account.data" class="swift-create-account" type="button" :disabled="creatingBank === account.key" @click="createAccount(account.key)">{{ creatingBank === account.key ? 'Creating…' : `Create ${account.label} account` }}</button>
          </div>
        </article>
        <template v-if="existingAccounts.length">
          <header class="swift-existing-intro">
            <strong>Your existing accounts</strong>
            <p>These older accounts remain available for customers who already created them.</p>
          </header>
          <article v-for="account in existingAccounts" :key="account.key" class="swift-service-card">
            <div class="swift-personal-account-copy">
              <strong>{{ account.label }}</strong>
              <span>{{ account.data.accountName }}</span>
              <button class="swift-copy-number" type="button" @click="copyAccount(account.data.accountNumber)">{{ account.data.accountNumber }} <i class="far fa-copy"></i></button>
            </div>
          </article>
        </template>
      </section>

      <button class="swift-funding-card" type="button" @click="activeMethod = activeMethod === 'onetime' ? '' : 'onetime'"><span class="swift-transaction-icon"><i class="fas fa-stopwatch"></i></span><span><strong>One-Time Bank Account</strong><em>⚡ Instant Funding</em><small>One payment only • Account expires after 30 mins.</small></span><i class="fas fa-chevron-right"></i></button>
      <form v-if="activeMethod === 'onetime' && !temporaryAccount" class="swift-service-card swift-form-stack" @submit.prevent="generateOneTimeAccount"><div class="swift-form-group"><label for="one-time-amount">Amount</label><div class="swift-field-wrap"><i class="far fa-credit-card"></i><input id="one-time-amount" v-model.number="oneTimeAmount" required min="100" max="15000" type="number" class="form-control" placeholder="₦100 – ₦15,000" /></div></div><button class="swift-primary-action" :disabled="loading">{{ loading ? 'Generating…' : 'Generate Account' }}</button></form>
      <section v-if="temporaryAccount" class="swift-service-card swift-one-time-result"><small>Transfer exactly ₦{{ formatNumber(oneTimeAmount) }} once</small><strong>{{ temporaryAccount.bankName }}</strong><button type="button" class="swift-copy-number" @click="copyAccount(temporaryAccount.accountNumber)">{{ temporaryAccount.accountNumber }} <i class="far fa-copy"></i></button><span>{{ temporaryAccount.accountName }}</span><em>Expires after 30 mins • One payment only</em></section>

      <a href="/dashboard/manual" class="swift-funding-card"><span class="swift-router-content"><span class="swift-transaction-icon"><i class="fas fa-paper-plane"></i></span><span><strong>Manual Funding</strong><em class="text-warning">Not Instant</em><small>Send a transfer for manual wallet credit.</small></span><i class="fas fa-chevron-right"></i></span></a>
    </div>
  </main>
</template>

<script>
const token = window.localStorage.getItem("token");
export default {
  data() { return { home: {}, activeMethod: "", form: { amount: "" }, oneTimeAmount: "", temporaryAccount: null, loading: false, creatingBank: "", errorflag: "" }; },
  computed: {
    personalAccounts() {
      return [
        { key: "opay", label: "OPay", recommended: true, charge: "Lowest Charge: 0.4%", example: "Transfer ₦1,000 → ₦996 credited", verification: "BVN or NIN required", data: this.home.opay_reserved_acct },
        { key: "palmpay", label: "PalmPay", charge: "Charge: 0.5%", example: "Transfer ₦1,000 → ₦995 credited", verification: "BVN required", data: this.home.palmpay_reserved_acct },
        { key: "moniepoint", label: "Moniepoint", charge: "Charge: 0.85%", example: "Transfer ₦1,000 → ₦991.50 credited", verification: "BVN or NIN required", data: this.home.moniepoint_reserved_acct },
      ];
    },
    existingAccounts() {
      return [
        { key: "gtbank", label: "GTBank", data: this.home.gtbank_reserved_acct },
        { key: "wema", label: "Wema Bank", data: this.home.wema_reserved_acct },
        { key: "providus", label: "Providus Bank", data: this.home.providus_reserved_acct },
      ].filter((account) => account.data);
    },
  },
  mounted() { this.loadHome(); },
  methods: {
    headers() { return { Authorization: `Bearer ${token}`, "Content-Type": "application/json" }; },
    loadHome() { axios.get("/api/load-home", { headers: this.headers() }).then(({ data }) => { this.home = data.data || {}; }).catch(() => { this.errorflag = "Unable to load your funding accounts."; }); },
    makePayment() {
      this.loading = true; this.errorflag = "";
      axios.post("/api/generate-payment-link", this.form, { headers: this.headers() }).then(({ data }) => {
        const payload = data.data || {}; const body = payload.responseBody || payload.data || payload; const url = body.checkoutUrl || body.paymentUrl;
        if (!url) throw new Error("Payment provider did not return a checkout link.");
        if (body.transactionReference) localStorage.setItem("transactionReference", body.transactionReference);
        window.location.assign(url);
      }).catch((error) => { this.errorflag = error.response?.data?.message || error.message || "Payment initialization failed."; }).finally(() => { this.loading = false; });
    },
    generateOneTimeAccount() {
      this.loading = true; this.errorflag = "";
      axios.post("/api/palmpay-bank-transfer", { amount: this.oneTimeAmount }, { headers: this.headers() }).then(({ data }) => {
        const account = data.data || {}; if (!account.accountNumber && account.checkoutUrl) { window.location.assign(account.checkoutUrl); return; }
        if (!account.accountNumber) throw new Error(data.message || "No account number was returned.");
        this.temporaryAccount = account;
      }).catch((error) => { this.errorflag = error.response?.data?.message || error.message || "Unable to generate the account."; }).finally(() => { this.loading = false; });
    },
    createAccount(bank) {
      this.creatingBank = bank; this.errorflag = "";
      const request = bank === "opay" ? axios.post("/api/opay/wallet", {}, { headers: this.headers() }) : axios.get(`/api/reserve/account/${bank}`, { headers: this.headers() });
      request.then(() => this.loadHome()).catch((error) => { this.errorflag = error.response?.data?.message || "Account creation is unavailable right now."; }).finally(() => { this.creatingBank = ""; });
    },
    copyAccount(number) { if (!number) return; navigator.clipboard.writeText(String(number)); this.$toasted.show("Account number copied"); },
  },
};
</script>
