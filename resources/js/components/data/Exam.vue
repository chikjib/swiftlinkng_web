<template>
  <main class="swift-service-page swift-exam-page">
    <h1 class="swift-service-heading">
      <button class="swift-back" type="button" @click="$router.back()"><i class="fas fa-arrow-left"></i></button>
      Exam PIN
    </h1>
    <div v-if="successflag" class="alert alert-success swift-alert">{{ successflag }}</div>
    <div v-if="errorflag" class="alert alert-danger swift-alert">{{ errorflag }}</div>

    <form class="swift-form-stack" @submit.prevent="subscribe">
      <div class="swift-form-group">
        <label>Service Exam Provider</label>
        <div class="swift-exam-provider-grid" role="list">
          <button v-for="provider in providerOptions" :key="provider.title" type="button" :class="{ 'is-selected': subcategory_id === provider.title }" @click="selectProvider(provider)">
            <span class="swift-exam-provider-icon">
              <img v-if="provider.subcat_image" :src="providerLogo(provider)" :alt="provider.title" />
              <i v-else class="fas fa-graduation-cap" aria-hidden="true"></i>
            </span>
            <strong>{{ provider.title }}</strong>
          </button>
        </div>
      </div>

      <div class="swift-form-group">
        <label>Exam Type</label>
        <button type="button" class="swift-app-picker" :class="{ 'has-value': form.variation_code }" :disabled="!subcategory_id || loading" aria-haspopup="dialog" @click="examTypeSheetOpen = true">
          <span class="swift-app-picker-icon"><i class="fas fa-book-open"></i></span>
          <strong>{{ examTypeLabel }}</strong>
          <i class="fas fa-chevron-down"></i>
        </button>
      </div>

      <div v-if="subcategory_id === 'JAMB'" class="swift-form-group">
        <label for="jamb-profile-id">JAMB Profile ID</label>
        <div class="swift-field-wrap"><i class="fas fa-id-card"></i><input id="jamb-profile-id" v-model="form.cardno" class="form-control" inputmode="numeric" placeholder="Enter JAMB profile ID" @keyup="verifyCard" /></div>
      </div>
      <div class="swift-form-group">
        <label for="exam-phone">Phone Number</label>
        <div class="swift-field-wrap"><i class="fas fa-phone-alt"></i><input id="exam-phone" v-model="form.phonenumber" type="tel" inputmode="numeric" required class="form-control" placeholder="0801 234 5678" /></div>
      </div>
      <div class="swift-form-group">
        <label for="exam-amount">Amount</label>
        <div class="swift-field-wrap"><i class="far fa-credit-card"></i><input id="exam-amount" v-model="form.amount" type="number" readonly class="form-control" /></div>
      </div>
      <button class="swift-primary-action" type="submit" :disabled="loading || !form.variation_code">{{ loading ? 'Processing…' : 'Process' }}</button>
    </form>

    <div v-if="examTypeSheetOpen" class="swift-sheet-backdrop" role="presentation" @click.self="examTypeSheetOpen = false">
      <section class="swift-bottom-sheet" role="dialog" aria-modal="true" aria-labelledby="exam-type-title">
        <div class="swift-sheet-handle" aria-hidden="true"></div>
        <header class="swift-sheet-header"><h2 id="exam-type-title">Select Exam Type</h2><button type="button" aria-label="Close" @click="examTypeSheetOpen = false">&times;</button></header>
        <div class="swift-app-sheet-list">
          <button v-for="item in examTypeOptions" :key="item.variation_code" type="button" :class="{ 'is-selected': form.variation_code === item.variation_code }" @click="chooseExamType(item)">
            <span class="swift-sheet-list-icon"><i class="fas fa-book-open"></i></span><strong>{{ item.name }}</strong><i :class="form.variation_code === item.variation_code ? 'fas fa-check-circle' : 'fas fa-chevron-right'"></i>
          </button>
        </div>
      </section>
    </div>
  </main>
</template>

<script>
const token = window.localStorage.getItem('token');

export default {
  data() {
    return {
      user: this.auth.user,
      subcategories: {},
      bouquet: {},
      form: {},
      subcategory_id: null,
      loading: false,
      successflag: '',
      errorflag: '',
      cardowner: '',
      successCode: false,
      examTypeSheetOpen: false,
    };
  },
  computed: {
    providerOptions() {
      return Array.isArray(this.subcategories.data) ? this.subcategories.data : [];
    },
    examTypeOptions() {
      if (Array.isArray(this.bouquet.data)) return this.bouquet.data;
      return Array.isArray(this.bouquet) ? this.bouquet : [];
    },
    examTypeLabel() {
      const selected = this.examTypeOptions.find((item) => item.variation_code === this.form.variation_code);
      return selected ? selected.name : 'Select Type';
    },
  },
  mounted() {
    this.loadplan();
  },
  methods: {
    providerLogo(provider) {
      return `/template/images/services/${provider.subcat_image}`;
    },
    selectProvider(provider) {
      this.subcategory_id = provider.title;
      this.form.variation_code = null;
      this.bouquet = {};
      this.showPlans();
    },
    chooseExamType(item) {
      this.form.variation_code = item.variation_code;
      this.examTypeSheetOpen = false;
    },
    subscribe() {
      this.loading = true;
      this.errorflag = '';
      this.form.plan = this.subcategory_id;
      axios.post('/api/purchase/exam', this.form, {
        headers: { Authorization: `Bearer ${token}`, 'Content-Type': 'application/json' },
      }).then((response) => {
        this.successflag = response.data.message;
        this.loading = false;
        this.form.amount = '';
        this.form.cardno = '';
        this.form.phonenumber = '';
        this.$toasted.show(response.data.message);
      }).catch((error) => {
        this.loading = false;
        this.errorflag = error.response?.data?.message || 'Unable to process the exam PIN purchase.';
        this.$toasted.show(this.errorflag);
      });
    },
    loadplan() {
      axios.get('/api/subcategory?category_id=6', {
        headers: { Authorization: `Bearer ${token}`, 'Content-Type': 'application/json' },
      }).then((response) => {
        this.subcategories = response.data;
      });
    },
    showPlans() {
      this.loading = true;
      const provider = this.providerOptions.find((item) => item.title === this.subcategory_id);
      if (!provider) {
        this.loading = false;
        return;
      }
      const content = typeof provider.products === 'string' ? JSON.parse(provider.products) : provider.products;
      this.form.amount = this.getUserLevel(content || {});
      axios.get(`/api/fetch/bouquet?plan=${encodeURIComponent(this.subcategory_id)}`, {
        headers: { Authorization: `Bearer ${token}`, 'Content-Type': 'application/json' },
      }).then((response) => {
        this.bouquet = response.data;
        this.loading = false;
      }).catch((error) => {
        this.loading = false;
        this.errorflag = error.response?.data?.message || 'Unable to load exam types.';
      });
    },
    verifyCard() {
      this.successCode = false;
      if (!this.subcategory_id) return;
      this.form.plan = this.subcategory_id;
      axios.post('/api/verify/card', this.form, {
        headers: { Authorization: `Bearer ${token}`, 'Content-Type': 'application/json' },
      }).then((response) => {
        if (response.data.data.content.error == null) {
          this.cardowner = `Customer: ${response.data.data.content.Customer_Name}`;
          this.successCode = true;
        } else {
          this.cardowner = 'Card Owner Info not found';
        }
      }).catch(() => {
        this.cardowner = 'Card Owner Info not found';
      });
    },
    getUserLevel(content) {
      if (this.user.userlevel == 0) return content.amount1;
      if (this.user.userlevel == 1) return content.amount2;
      if (this.user.userlevel == 2) return content.amount3;
      if (this.user.userlevel == 3) return content.amount4;
      return content.amount1;
    },
  },
};
</script>

<style scoped>
.swift-exam-provider-grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 8px; }
.swift-exam-provider-grid > button { min-width: 0; min-height: 96px; padding: 9px 6px; color: var(--swift-ink, #17171c); background: var(--swift-surface, #fff); border: 1px solid var(--swift-border, #dedee3); border-radius: 13px; cursor: pointer; }
.swift-exam-provider-grid > button.is-selected { color: #e30613; background: #ffecee; border-color: #e30613; }
.swift-exam-provider-grid strong { display: block; overflow: hidden; font-size: 12px; font-weight: 800; text-overflow: ellipsis; white-space: nowrap; }
.swift-exam-provider-icon { display: grid; width: 42px; height: 42px; margin: 0 auto 7px; place-items: center; color: #1976d2; background: #eef6ff; border-radius: 50%; font-size: 21px; }
.is-selected .swift-exam-provider-icon { color: #e30613; background: #fff; }
.swift-exam-provider-icon img { width: 34px; height: 34px; padding: 0; object-fit: contain; border-radius: 50%; }
@media (max-width: 430px) {
  .swift-exam-provider-grid > button { min-height: 88px; }
  .swift-exam-provider-icon { width: 38px; height: 38px; }
  .swift-exam-provider-grid strong { font-size: 11px; }
}
</style>
