<template>
  <main class="swift-service-page">
    <div v-if="isloading" class="swift-transaction-skeleton" aria-label="Loading transactions">
      <span v-for="index in 5" :key="index"></span>
    </div>
    <template v-else>
      <div v-if="errorMessage" class="alert alert-danger alert-dismissible" role="alert">
        <button type="button" class="close" aria-label="Close" @click="errorMessage = ''"><span aria-hidden="true">&times;</span></button>
        {{ errorMessage }}
      </div>
      <header class="swift-transaction-toolbar">
        <h1 class="swift-service-heading"><button class="swift-back" type="button" @click="$router.back()"><i class="fas fa-arrow-left"></i></button>{{ limit == 5 ? 'Recent Transactions' : 'Transaction History' }}</h1>
        <div class="swift-transaction-search-row">
          <form class="swift-search" @submit.prevent="getResults"><i class="fas fa-search"></i><input v-model.trim="keyword" class="form-control" placeholder="Search transactions" aria-label="Search transactions" /></form>
          <button type="button" class="swift-transaction-filter-button" :class="{ 'is-active': status !== '' }" aria-haspopup="dialog" aria-label="Filter transactions by status" @click="filterOpen = true"><i class="fas fa-filter"></i><span v-if="status !== ''" aria-hidden="true"></span></button>
        </div>
      </header>
      <div v-if="status !== ''" class="swift-active-filter"><span>{{ statusLabel(status) }}</span><button type="button" @click="chooseStatus('')">Clear</button></div>
      <div v-if="!visibleTransactions.length" class="swift-service-card text-center py-5"><i class="fas fa-receipt fa-2x text-danger mb-3"></i><h5>No transactions found</h5><p class="mb-0 text-muted">Your matching transactions will appear here.</p></div>
      <section v-for="group in groupedTransactions" :key="group.date">
        <h2 class="swift-transaction-date">{{ group.label }}</h2>
        <div class="swift-transaction-list">
          <article v-for="transaction in group.items" :key="transaction.id" class="swift-transaction-item" role="button" tabindex="0" @click="openTransaction(transaction)" @keydown.enter="openTransaction(transaction)">
            <span class="swift-transaction-icon"><i :class="transactionIcon(transaction)"></i></span>
            <span class="swift-transaction-copy"><strong>{{ transactionTitle(transaction) }}</strong><span>{{ transaction.phone || transaction.iuc || transaction.meter || transaction.ref }}</span><span><i class="far fa-clock mr-1"></i>{{ formatTime(transaction.created_at) }}</span></span>
            <span class="swift-transaction-value"><strong :style="transaction.status == '2' ? 'color:#169447' : ''">{{ transaction.status == '2' ? '+' : '-' }} ₦{{ formatNumber(transaction.subtotal) }}</strong><span class="swift-status" :class="statusClass(transaction.status)">{{ statusLabel(transaction.status) }}</span></span>
            <i class="fas fa-chevron-right"></i>
          </article>
        </div>
      </section>
      <nav v-if="lastPage > 1" class="swift-pagination" aria-label="Transaction pages">
        <button type="button" :disabled="currentPage <= 1 || isloading" @click="changePage(currentPage - 1)"><i class="fas fa-chevron-left"></i> Previous</button>
        <span>Page {{ currentPage }} of {{ lastPage }}</span>
        <button type="button" :disabled="currentPage >= lastPage || isloading" @click="changePage(currentPage + 1)">Next <i class="fas fa-chevron-right"></i></button>
      </nav>

      <div v-if="filterOpen" class="swift-sheet-backdrop" role="presentation" @click.self="filterOpen = false">
        <section class="swift-bottom-sheet" role="dialog" aria-modal="true" aria-labelledby="transaction-filter-title">
          <div class="swift-sheet-handle" aria-hidden="true"></div>
          <header class="swift-sheet-header"><h2 id="transaction-filter-title">Filter by status</h2><button type="button" aria-label="Close" @click="filterOpen = false">&times;</button></header>
          <div class="swift-status-filter-list">
            <button v-for="option in statusOptions" :key="option.value" type="button" @click="chooseStatus(option.value)"><i :class="status === option.value ? 'fas fa-dot-circle' : 'far fa-circle'"></i><span>{{ option.label }}</span></button>
          </div>
        </section>
      </div>
    </template>
  </main>
</template>

<script>
const token = window.localStorage.getItem("token");

export default {
  props: ["limit"],
  data() {
    return {
      transactions: { data: [] }, keyword: "", field: "description", status: "", searching: false,
      isloading: false, loading: false, errorMessage: "", filterOpen: false,
      statusOptions: [
        { value: '', label: 'All transactions' },
        { value: '1', label: 'Successful' },
        { value: '0', label: 'Pending' },
        { value: '2', label: 'Reversed' },
        { value: '3', label: 'Cancelled' },
        { value: '4', label: 'Failed' },
      ],
    };
  },
  computed: {
    visibleTransactions() {
      const term = this.keyword.toLowerCase();
      return (this.transactions.data || []).filter((item) => {
        const text = [item.description, item.phone, item.iuc, item.meter, item.ref, item.subtotal, item.category && item.category.title].filter(Boolean).join(" ").toLowerCase();
        return !term || text.includes(term);
      });
    },
    groupedTransactions() {
      const groups = [];
      this.visibleTransactions.forEach((item) => {
        const date = this.parseDate(item.created_at);
        const key = Number.isNaN(date.getTime()) ? "unknown" : date.toISOString().slice(0, 10);
        let group = groups.find((entry) => entry.date === key);
        if (!group) { group = { date: key, label: this.formatDate(item.created_at), items: [] }; groups.push(group); }
        group.items.push(item);
      });
      return groups;
    },
    currentPage() { return Number(this.transactions.current_page || this.transactions.meta?.current_page || 1); },
    lastPage() { return Number(this.transactions.last_page || this.transactions.meta?.last_page || 1); },
  },
  mounted() { this.list(); },
  methods: {
    chooseStatus(status) {
      this.status = status;
      this.filterOpen = false;
      status === '' ? this.list() : this.getResults();
    },
    changePage(page) { if (page < 1 || page > this.lastPage) return; this.searching ? this.getResults(page) : this.list(page); },
    list(page = 1) {
      this.isloading = true; this.searching = false; this.errorMessage = "";
      axios.get(`/api/orders?page=${page}&user=${this.auth.user.id}&stats=1`, { headers: { Authorization: `Bearer ${token}`, "Content-Type": "application/json" } })
        .then(({ data }) => { this.assignTransactions(data); this.isloading = false; })
        .catch((error) => { this.errorMessage = error.response?.data?.message || error.response?.data?.data || "Unable to load transactions. Please try again."; this.$toasted.show(this.errorMessage); this.isloading = false; });
    },
    getResults(page = 1) {
      if (!this.status) return this.list(page);
      this.isloading = true; this.searching = true; this.errorMessage = "";
      axios.get(`/api/orders?search_user=${encodeURIComponent(this.keyword)}&field=${this.field}&status=${this.status}&user_id=${this.auth.user.id}&page=${page}`, { headers: { Authorization: `Bearer ${token}`, "Content-Type": "application/json" } })
        .then(({ data }) => { this.assignTransactions(data); this.isloading = false; })
        .catch((error) => { this.errorMessage = error.response?.data?.message || "Unable to filter transactions. Please try again."; this.$toasted.show(this.errorMessage); this.isloading = false; });
    },
    assignTransactions(payload) {
      this.transactions = payload && payload.data && payload.data.data && Array.isArray(payload.data.data)
        ? payload.data
        : (payload || { data: [] });
      if (!Array.isArray(this.transactions.data)) this.transactions.data = [];
    },
    parseDate(value) {
      const direct = new Date(value);
      if (!Number.isNaN(direct.getTime())) return direct;
      const match = String(value || '').match(/^(\d{2})\/(\d{2})\/(\d{4})(?:\s+(\d{2}):(\d{2}):(\d{2}))?$/);
      return match ? new Date(Number(match[3]), Number(match[2]) - 1, Number(match[1]), Number(match[4] || 0), Number(match[5] || 0), Number(match[6] || 0)) : new Date(NaN);
    },
    openTransaction(transaction) { if (transaction.status == '1') this.$router.push({ name: 'receipt', params: { id: transaction.id } }); },
    transactionTitle(transaction) { return transaction.description || (transaction.category && transaction.category.title) || "Transaction"; },
    transactionIcon(transaction) {
      const value = `${transaction.description || ''} ${(transaction.category && transaction.category.title) || ''}`.toLowerCase();
      if (value.includes('data')) return 'fas fa-wifi'; if (value.includes('airtime')) return 'fas fa-phone-alt'; if (value.includes('electric')) return 'far fa-lightbulb'; if (value.includes('cable') || value.includes('dstv') || value.includes('gotv')) return 'fas fa-tv'; if (value.includes('sms')) return 'fas fa-comment-alt'; if (value.includes('fund')) return 'fas fa-wallet'; return 'fas fa-receipt';
    },
    statusLabel(status) { return ({ '1': 'Successful', '2': 'Reversed', '3': 'Cancelled', '4': 'Failed' })[String(status)] || 'Pending'; },
    statusClass(status) { return status == '0' ? 'is-pending' : (status == '1' || status == '2') ? '' : 'is-failed'; },
    formatDate(value) { const date = this.parseDate(value); if (Number.isNaN(date.getTime())) return 'Transactions'; const today = new Date(); const yesterday = new Date(); yesterday.setDate(today.getDate() - 1); const same = (a,b) => a.toDateString() === b.toDateString(); const prefix = same(date,today) ? 'Today' : same(date,yesterday) ? 'Yesterday' : ''; const formatted = date.toLocaleDateString('en-NG', { month:'short', day:'numeric', year:'numeric' }); return prefix ? `${prefix} — ${formatted}` : formatted; },
    formatTime(value) { const date = this.parseDate(value); return Number.isNaN(date.getTime()) ? '' : date.toLocaleTimeString('en-NG', { hour:'2-digit', minute:'2-digit', hour12:true }); },
  },
};
</script>

<style scoped>
.swift-transaction-skeleton { display:grid; gap:10px; }
.swift-transaction-skeleton span { display:block; height:82px; border-radius:17px; background:linear-gradient(90deg,#eeeeF1 25%,#f8f8fa 50%,#eeeeF1 75%); background-size:200% 100%; animation:swift-shimmer 1.2s infinite; }
.swift-pagination { display:flex; align-items:center; justify-content:space-between; gap:10px; margin:20px 0 0; }
.swift-pagination button { min-height:42px; padding:8px 14px; border:1px solid #dedee3; border-radius:12px; background:#fff; color:#d30613; font-weight:800; }
.swift-pagination button:disabled { opacity:.4; }
.swift-pagination span { font-size:.85rem; font-weight:750; }
@keyframes swift-shimmer { to { background-position:-200% 0; } }
@media (prefers-color-scheme:dark) { .swift-transaction-skeleton span { background:linear-gradient(90deg,#23252b 25%,#30333a 50%,#23252b 75%); background-size:200% 100%; } .swift-pagination button { background:#1c1e23; border-color:#3b3e46; } }
</style>
