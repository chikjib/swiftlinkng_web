<template>
  <main class="swift-service-page">
    <h1 class="swift-service-heading"><button class="swift-back" type="button" @click="$router.back()"><i class="fas fa-arrow-left"></i></button>Electricity Bill</h1>
    <div v-if="successflag" class="alert alert-success swift-alert">{{ successflag }}</div><div v-if="errorflag" class="alert alert-danger swift-alert">{{ errorflag }}</div>
    <form class="swift-form-stack" @submit.prevent="buyElectricity">
      <div class="swift-form-group">
        <label>Select Distribution Company</label>
        <button type="button" class="swift-app-picker swift-provider-picker" :class="{ 'has-value': selectedProvider }" aria-haspopup="dialog" @click="providerSheetOpen = true">
          <span class="swift-app-picker-icon swift-provider-picker-logo">
            <img v-if="selectedProvider" :src="providerLogo(selectedProvider)" :alt="selectedProvider.title" />
            <i v-else class="far fa-lightbulb"></i>
          </span>
          <strong>{{ providerLabel }}</strong>
          <i class="fas fa-chevron-down"></i>
        </button>
      </div>
      <div class="swift-form-group"><label>Select Package</label><button type="button" class="swift-app-picker" :class="{ 'has-value': form.type }" aria-haspopup="dialog" @click="packageSheetOpen = true"><span class="swift-app-picker-icon"><i class="fas fa-tachometer-alt"></i></span><strong>{{ packageLabel }}</strong><i class="fas fa-chevron-down"></i></button></div>
      <div class="swift-form-group"><label for="meter-number">Meter Number</label><div class="swift-field-wrap"><i class="fas fa-tachometer-alt"></i><input id="meter-number" v-model="form.cardno" required inputmode="numeric" class="form-control" placeholder="Enter meter number" /></div><button class="btn btn-outline-danger mt-2" type="button" :disabled="verifiying || !subcategory_id" @click="verifyCard">{{ verifiying ? 'Verifying…' : 'Verify Meter Number' }}</button><div v-if="cardowner" class="mt-2 font-weight-bold text-success">{{ cardowner }}</div></div>
      <div class="swift-inline-fields"><div class="swift-form-group"><label for="electricity-phone">Phone Number</label><div class="swift-field-wrap"><i class="fas fa-phone-alt"></i><input id="electricity-phone" v-model="form.phonenumber" required inputmode="numeric" class="form-control" placeholder="0801 234 5678" /></div></div><div class="swift-form-group"><label for="electricity-amount">Amount</label><div class="swift-field-wrap"><i class="far fa-credit-card"></i><input id="electricity-amount" v-model="amount" required min="500" type="number" class="form-control" placeholder="Enter amount" /></div></div></div>
      <input type="hidden" v-model="form.amount" />
      <section v-if="amount" class="swift-service-card swift-price-note"><span>Amount to pay</span><strong>₦{{ form.discount || amount }}</strong></section>
      <button class="swift-primary-action" type="submit" :disabled="!successCode || loading">{{ loading ? 'Processing…' : 'Buy Now' }}</button>
    </form>
    <div v-if="providerSheetOpen" class="swift-sheet-backdrop" role="presentation" @click.self="providerSheetOpen = false">
      <section class="swift-bottom-sheet" role="dialog" aria-modal="true" aria-labelledby="electricity-provider-title">
        <div class="swift-sheet-handle" aria-hidden="true"></div>
        <header class="swift-sheet-header"><h2 id="electricity-provider-title">Select Distribution Company</h2><button type="button" aria-label="Close" @click="providerSheetOpen = false">&times;</button></header>
        <div class="swift-app-sheet-list swift-provider-sheet-list">
          <button v-for="provider in providerOptions" :key="provider.title" type="button" :class="{ 'is-selected': subcategory_id === provider.title }" @click="chooseProvider(provider)">
            <span class="swift-sheet-list-icon swift-provider-list-logo"><img :src="providerLogo(provider)" :alt="provider.title" /></span>
            <strong>{{ provider.title }}</strong>
            <i :class="subcategory_id === provider.title ? 'fas fa-check-circle' : 'fas fa-chevron-right'"></i>
          </button>
        </div>
      </section>
    </div>
    <div v-if="packageSheetOpen" class="swift-sheet-backdrop" role="presentation" @click.self="packageSheetOpen = false">
      <section class="swift-bottom-sheet" role="dialog" aria-modal="true" aria-labelledby="electricity-package-title">
        <div class="swift-sheet-handle" aria-hidden="true"></div>
        <header class="swift-sheet-header"><h2 id="electricity-package-title">Select Package</h2><button type="button" aria-label="Close" @click="packageSheetOpen = false">&times;</button></header>
        <div class="swift-app-sheet-list">
          <button v-for="item in packageOptions" :key="item.value" type="button" :class="{ 'is-selected': form.type === item.value }" @click="choosePackage(item.value)">
            <span class="swift-sheet-list-icon"><i :class="item.icon"></i></span><strong>{{ item.label }}</strong><i :class="form.type === item.value ? 'fas fa-check-circle' : 'fas fa-chevron-right'"></i>
          </button>
        </div>
      </section>
    </div>
  </main>
</template>

<script>
const token = window.localStorage.getItem("token");

export default {
  // props: ["user"],

  data() {
    return {
      user: this.auth.user,
      subcategories: {},
      plans: {},
      form: {},

      category_id: null,
      subcategory_id: null,
      plan_id: null,
      phonenumber: null,
      amount: null,
      discount: 0,
      loading: false,
      networkImage: "",
      cardowner: "",
      successCode: false,
      verifiying: false,
      descriptiondata: "",

      successflag: "",
      errorflag: "",
      packageSheetOpen: false,
      providerSheetOpen: false,
      packageOptions: [
        { value: 'prepaid', label: 'Prepaid', icon: 'fas fa-bolt' },
        { value: 'postpaid', label: 'Postpaid', icon: 'fas fa-file-invoice' },
      ],
    };
  },

  computed: {
    providerOptions() {
      return Array.isArray(this.subcategories.data) ? this.subcategories.data : [];
    },
    selectedProvider() {
      return this.providerOptions.find((provider) => provider.title === this.subcategory_id) || null;
    },
    providerLabel() {
      return this.selectedProvider ? this.selectedProvider.title : 'Select provider';
    },
    packageLabel() {
      const selected = this.packageOptions.find((item) => item.value === this.form.type);
      return selected ? selected.label : 'Select Package';
    },
  },

  mounted() {
    this.loadplan();
  },

  watch: {
    amount(after, before) {
      this.showActualAmount();
    },
  },

  methods: {
    providerLogo(provider) {
      if (provider && provider.subcat_image) {
        return `/template/images/services/${provider.subcat_image}`;
      }
      const title = (provider?.title || '').toLowerCase();
      if (title.includes('ikeja') || title.includes('ikedc')) return '/template/images/services/ikeja.jpeg';
      if (title.includes('eko') || title.includes('ekedc')) return '/template/images/services/eko.jpeg';
      if (title.includes('ibadan') || title.includes('ibedc')) return '/template/images/services/ibedc.png';
      if (title.includes('abuja') || title.includes('aedc')) return '/template/images/services/aedc.jpeg';
      if (title.includes('kaduna') || title.includes('kaedco')) return '/template/images/services/kaedco.jpeg';
      if (title.includes('kano') || title.includes('kedco')) return '/template/images/services/kedco.jpeg';
      if (title.includes('jos') || title.includes('jed')) return '/template/images/services/jed.png';
      if (title.includes('port harcourt') || title.includes('phed')) return '/template/images/services/phed.jpeg';
      return '/template/images/services/error.svg';
    },
    chooseProvider(provider) {
      this.subcategory_id = provider.title;
      this.networkImage = provider.subcat_image || '';
      this.providerSheetOpen = false;
      this.successCode = false;
      this.cardowner = '';
      this.showPlans();
    },
    choosePackage(value) {
      this.form.type = value;
      this.packageSheetOpen = false;
      this.successCode = false;
      this.cardowner = '';
    },
    buyElectricity() {
      this.loading = true;

      this.form.variation_code = this.form.type;
      this.form.serviceID = this.subcategory_id;
      var plan1 = this.subcategory_id.toString().split("-");
      var plan2 = plan1[1].trim().replace(" ", "-");
      this.form.plan = plan2.toString().toLowerCase();

      if (this.amount < 500 || this.form.amount < 500) {
        this.errorflag = "Amount cannot be less than 500";
        this.$toasted.show("Amount cannot be less than 500");
        this.loading = false;
      } else {
        axios
          .post(`/api/purchase/electricity`, this.form, {
            headers: {
              Authorization: `Bearer ${token}`,
              "Content-Type": "application/json",
            },
          })
          .then((response) => {
            this.successflag = response.data.message;
            this.loading = false;
            this.form.amount = "";
            this.form.cardno = "";
            this.form.phonenumber = "";
            this.$toasted.show(response.data.message);
          })
          .catch((error) => {
            this.loading = false;
            this.errorflag = error.response.data.message;
            this.$toasted.show(error.response.data.message);
          });
      }
    },

    loadplan() {
      axios
        .get(`/api/subcategory?category_id=4`, {
          headers: {
            Authorization: `Bearer ${token}`,
            "Content-Type": "application/json",
          },
        })
        .then((response) => {
          console.log(response.data);
          this.subcategories = response.data;
        });
    },

    showPlans() {
      this.loading = true;
      this.phonenumber = null;
      //this.amount = 0;
      var c = this.subcategories.data.filter(
        (sub) => sub.title === this.subcategory_id
      );
      if (!c.length) {
        this.loading = false;
        return;
      }
      this.networkImage = c[0].subcat_image;

      var content = JSON.parse(c[0].products);

      this.form.discount = this.getUserLevel(content) + "% Discount";
      this.discount = this.getUserLevel(content);
      this.descriptiondata = c[0].description;

      this.loading = false;
    },

    verifyCard() {
      this.successCode = false;
      this.verifiying = true;

      if (this.subcategory_id == "") {
        return this.$toasted.show("No provider has veen selected");
      }
      //this.form.plan = this.subcategory_id;

      var plan1 = this.subcategory_id.toString().split("-");

      // console.log(plan1[1]);

      var plan2 = plan1[1].trim().replace(" ", "-");

      this.form.plan = plan2.toString().toLowerCase();
      this.form.description = this.descriptiondata;

      this.form.serviceID = this.subcategory_id;

      //console.log(this.form.plan);

      axios
        .post(`/api/verify/card`, this.form, {
          headers: {
            Authorization: `Bearer ${token}`,
            "Content-Type": "application/json",
          },
        })
        .then((response) => {
          //  this.successCodee=
          console.log("res" + response);
          if (response.data.data.content.Customer_Name != null) {
            this.cardowner =
              "Customer:" + response.data.data.content.Customer_Name;
            this.successCode = true;
            this.verifiying = false;
          } else {
            this.cardowner = "Meter Number Info not found";
            this.successCode = false;
            this.verifiying = false;
          }
        })
        .catch((error) => {
          console.log(error.response);
          this.verifiying = false;
          this.errorflag = error.response.data.message;

          this.$toasted.show(error);
        });
    },

    showActualAmount() {
      this.form.amount = this.amount;

      this.form.discount = this.amount - this.amount * (this.discount / 100);
    },

    getUserLevel(content) {
      if (this.user.userlevel == 0) {
        return content.amount1;
      } else if (this.user.userlevel == 1) {
        return content.amount2;
      } else if (this.user.userlevel == 2) {
        return content.amount3;
      } else if (this.user.userlevel == 3) {
        return content.amount4;
      }
    },
  },
};
</script>

<style scoped>
.swift-provider-picker-logo,
.swift-provider-list-logo {
  overflow: hidden;
  background: #fff;
}

.swift-provider-picker-logo img,
.swift-provider-list-logo img {
  display: block;
  width: 32px;
  height: 32px;
  padding: 0;
  object-fit: contain;
  border-radius: 50%;
}

.swift-provider-list-logo {
  width: 44px;
  height: 44px;
}

.swift-provider-list-logo img {
  width: 38px;
  height: 38px;
}
</style>
