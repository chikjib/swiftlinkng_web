<template>
  <main class="swift-service-page">
    <h1 class="swift-service-heading"><button class="swift-back" type="button" @click="$router.back()"><i class="fas fa-arrow-left"></i></button>{{ serviceTitle }}</h1>
    <div v-if="successflag" class="alert alert-success swift-alert">{{ successflag }}</div>
    <div v-if="errorflag" class="alert alert-danger swift-alert">{{ errorflag }}</div>

    <form class="swift-form-stack" @submit.prevent="Process('wallet')">
      <section class="swift-service-card">
        <div class="swift-provider-grid">
          <button v-for="network in networkOptions" :key="network.key" class="swift-provider-option" :class="{ 'is-selected': selectedNetwork === network.key }" type="button" @click="selectNetwork(network.key)">
            <img :src="network.image" :alt="network.label" />
            <span>{{ network.label }}</span>
          </button>
        </div>
      </section>

      <div class="swift-form-group">
        <label>{{ categoryPrompt }}</label>
        <button type="button" class="swift-app-picker" :class="{ 'has-value': selectedCategory }" :disabled="!selectedNetwork || !filteredCategories.length" aria-haspopup="dialog" @click="activePicker = 'category'">
          <span class="swift-app-picker-icon"><i class="fas fa-layer-group"></i></span>
          <strong>{{ selectedCategory ? categoryLabel(selectedCategory.title) : categoryPrompt }}</strong>
          <i class="fas fa-chevron-down"></i>
        </button>
      </div>
      <div v-if="form.subcategory_id" class="swift-form-group">
        <label>{{ planPrompt }}</label>
        <button type="button" class="swift-app-picker" :class="{ 'has-value': selectedPlan }" :disabled="!plans.length" aria-haspopup="dialog" @click="activePicker = 'plan'">
          <span class="swift-app-picker-icon"><i class="fas fa-chart-pie"></i></span>
          <strong>{{ selectedPlan ? selectedPlan.plan : planPrompt }}</strong>
          <em v-if="selectedPlan">₦{{ getUserLevel(selectedPlan) }}</em>
          <i v-else class="fas fa-chevron-down"></i>
        </button>
      </div>
      <div class="swift-inline-fields">
        <div class="swift-form-group"><label for="data-phone">Phone Number</label><div class="swift-field-wrap"><i class="fas fa-phone-alt"></i><input id="data-phone" v-model="form.phonenumber" required inputmode="numeric" class="form-control" placeholder="0801 234 5678" @input="removeSpaces" /></div></div>
        <div class="swift-form-group"><label for="data-amount">Amount</label><div class="swift-field-wrap"><i class="far fa-credit-card"></i><input id="data-amount" v-model="form.amount" readonly class="form-control" placeholder="Amount" /></div></div>
      </div>
      <div class="swift-check-row"><span>Is it a ported number?</span><input type="checkbox" v-model="form.ported" value="yes" aria-label="Ported number" /></div>
      <section v-if="form.subcategory_id && description2" class="swift-service-card swift-help-card"><strong>Note:</strong> {{ description2 }}</section>
      <button class="swift-primary-action" type="submit" :disabled="loading || !form.plan_id">{{ loading ? 'Processing…' : 'Buy Now' }}</button>
    </form>

    <div v-if="activePicker === 'category'" class="swift-sheet-backdrop" role="presentation" @click.self="activePicker = ''">
      <section class="swift-bottom-sheet swift-data-category-sheet" role="dialog" aria-modal="true" aria-labelledby="data-category-sheet-title">
        <div class="swift-sheet-handle" aria-hidden="true"></div>
        <header class="swift-sheet-header"><h2 id="data-category-sheet-title">{{ categoryPrompt }}</h2><button type="button" aria-label="Close" @click="activePicker = ''">&times;</button></header>
        <div class="swift-app-sheet-list">
          <button v-for="category in filteredCategories" :key="category.id" type="button" :class="{ 'is-selected': form.subcategory_id === category.id }" @click="chooseCategory(category)">
            <span class="swift-sheet-list-icon"><i :class="categoryIcon(category.title)"></i></span>
            <strong>{{ categoryLabel(category.title) }}</strong>
            <small>{{ categoryPlanCount(category) }} plans</small>
            <i class="fas fa-chevron-right"></i>
          </button>
        </div>
      </section>
    </div>

    <div v-if="activePicker === 'plan'" class="swift-sheet-backdrop" role="presentation" @click.self="activePicker = ''">
      <section class="swift-bottom-sheet swift-data-plan-sheet" role="dialog" aria-modal="true" aria-labelledby="data-plan-sheet-title">
        <div class="swift-sheet-handle" aria-hidden="true"></div>
        <header class="swift-sheet-header swift-data-plan-header">
          <span class="swift-network-avatar" :class="`is-${selectedNetwork}`">{{ selectedNetwork.slice(0, 1).toUpperCase() }}</span>
          <span><h2 id="data-plan-sheet-title">{{ selectedNetworkLabel }} {{ selectedCategory ? categoryLabel(selectedCategory.title) : 'Plans' }}</h2><small>{{ plans.length }} available plans</small></span>
          <button type="button" aria-label="Close" @click="activePicker = ''">&times;</button>
        </header>
        <div class="swift-app-sheet-list swift-plan-options">
          <button v-for="plan in plans" :key="plan.plan" type="button" :class="{ 'is-selected': form.plan_id === plan.plan }" @click="choosePlan(plan)">
            <strong>{{ plan.plan }}</strong>
            <em>₦{{ getUserLevel(plan) }}</em>
          </button>
        </div>
      </section>
    </div>
  </main>
</template>

<script>
const token = window.localStorage.getItem("token");

export default {
  props: {
    serviceTitle: { type: String, default: "Data Topup" },
    categoryId: { type: Number, default: 1 },
    purchaseEndpoint: { type: String, default: "/api/purchase/data" },
  },
  data() {
    return {
      user: this.auth.user,
      subcategories: {},
      plans: [],
      bankcharge: "",
      category_id: null,
      subcategory_id: null,
      plan_id: null,
      phonenumber: null,
      amount: 0,
      newamount: 0,

      loading: false,
      networkImage: "",
      form: {},
      successflag: "",
      errorflag: "",
      description2: "",
      scriptLoaded: null,
      selectedNetwork: "",
      activePicker: "",
      catalogueRefreshTimer: null,
    };
  },

  computed: {
    networkOptions() {
      const definitions = [
        { key: "mtn", label: "MTN" },
        { key: "airtel", label: "Airtel" },
        { key: "glo", label: "Glo" },
        { key: "9mobile", label: "9mobile" },
      ];
      return definitions.map((item) => {
        const match = (this.subcategories.data || []).find(
          (category) => this.networkKey(category.title, category.subcat_image) === item.key
        );
        return match ? { ...item, image: `/template/images/services/${match.subcat_image || `${item.key}.png`}` } : null;
      }).filter(Boolean);
    },
    categoryPrompt() {
      return this.categoryId === 13 ? 'Select Airtime Category' : 'Select Data Category';
    },
    planPrompt() {
      return this.categoryId === 13 ? 'Select an Airtime plan' : 'Select a data plan';
    },
    filteredCategories() {
      return (this.subcategories.data || []).filter(
        (category) => this.networkKey(category.title, category.subcat_image) === this.selectedNetwork
      );
    },
    selectedCategory() {
      return (this.subcategories.data || []).find((category) => category.id === this.form.subcategory_id) || null;
    },
    selectedPlan() {
      return this.plans.find((plan) => plan.plan === this.form.plan_id) || null;
    },
    selectedNetworkLabel() {
      return ({ mtn: 'MTN', airtel: 'Airtel', glo: 'Glo', '9mobile': '9mobile' })[this.selectedNetwork] || 'Network';
    },
  },

  mounted() {
    this.loadplan();
    window.addEventListener("focus", this.refreshCatalogue);
    document.addEventListener("visibilitychange", this.refreshVisibleCatalogue);
    this.catalogueRefreshTimer = window.setInterval(this.refreshCatalogue, 60000);
  },

  beforeUnmount() {
    window.removeEventListener("focus", this.refreshCatalogue);
    document.removeEventListener("visibilitychange", this.refreshVisibleCatalogue);
    if (this.catalogueRefreshTimer) {
      window.clearInterval(this.catalogueRefreshTimer);
    }
  },

  created() {
    this.scriptLoaded = new Promise((resolve) => {
      this.loadScript(() => {
        resolve();
      });
    });
  },

  methods: {
    networkKey(title, image = "") {
      const value = `${String(title || "")} ${String(image || "")}`.toLowerCase();
      if (value.includes("9mobile") || value.includes("etisalat")) return "9mobile";
      if (value.includes("airtel")) return "airtel";
      if (value.includes("glo")) return "glo";
      if (value.includes("mtn")) return "mtn";
      return "";
    },
    refreshCatalogue() {
      this.loadplan(true);
    },
    refreshVisibleCatalogue() {
      if (document.visibilityState === "visible") this.refreshCatalogue();
    },
    categoryLabel(title) {
      const original = String(title || "").trim();
      const category = original.replace(/^(mtn|airtel|glo|9mobile|etisalat)\b[\s_:/-]*/i, "").trim();
      return category || original;
    },
    selectNetwork(network) {
      this.selectedNetwork = network;
      this.form.subcategory_id = undefined;
      this.form.plan_id = undefined;
      this.form.amount = "";
      this.plans = [];
      this.description2 = "";
    },
    chooseCategory(category) {
      this.form.subcategory_id = category.id;
      this.showPlans();
      this.activePicker = '';
    },
    choosePlan(plan) {
      this.form.plan_id = plan.plan;
      this.showAmount();
      this.activePicker = '';
    },
    categoryPlanCount(category) {
      return this.normalizePlans(category.products).length;
    },
    categoryIcon(title) {
      const value = String(title || '').toLowerCase();
      if (value.includes('sme')) return 'fas fa-users';
      if (value.includes('corporate') || value.includes('cg')) return 'fas fa-building';
      if (value.includes('gift')) return 'fas fa-gift';
      if (value.includes('awoof')) return 'fas fa-bolt';
      return 'fas fa-layer-group';
    },
    loadScript(callback) {
      const script = document.createElement("script");
      script.src = "https://checkout.flutterwave.com/v3.js";
      document.getElementsByTagName("head")[0].appendChild(script);
      if (script.readyState) {
        // IE
        script.onreadystatechange = () => {
          if (
            script.readyState === "loaded" ||
            script.readyState === "complete"
          ) {
            script.onreadystatechange = null;
            callback();
          }
        };
      } else {
        // Others
        script.onload = () => {
          callback();
        };
      }
    },
    removeSpaces(event) {
        // Remove all spaces and non-digit characters
      const cleaned = event.target.value.replace(/\D/g, '');
      this.form.phonenumber = cleaned;
    },

    makePayment() {
      this.loading = true;

      this.form.user_id = this.user.id;
      axios
        .post(`/api/payment/initiateOrder`, this.form, {
          headers: {
            Authorization: `Bearer ${token}`,
            "Content-Type": "application/json",
          },
        })
        .then((response) => {
          console.log(response.data.data);
          var ref = response.data.data;

          this.form.user_id = this.user.id;
          this.form.ref = ref;

          this.scriptLoaded &&
            this.scriptLoaded.then(() => {
              // const monnifyOptions = {
              //   amount: this.newamount,
              //   currency: "NGN",
              //   reference: ref,
              //   // customerFullName:
              //   //   this.user.firstname + " " + this.user.lastname,
              //   customerEmail: this.user.email,
              //   //customerMobileNumber: this.user.phone,
              //   apiKey: process.env.MIX_MONNIFYAPIKEY,
              //   contractCode: process.env.MIX_MONNIFYCONTRACTCODE,
              //   paymentDescription: "Buy Data",
              //   isTestMode: true,
              //   onComplete: (response) => {
              //     if (response.status == "SUCCESS") {
              //       // console.log(response.paymentStatus);
              //       // this.confirmPayment(response.paymentReference);
              //       this.successflag = "Transaction will be processed shortly";
              //     }
              //   },
              //   onClose: (data) => {
              //     //console.log(data);
              //     // if (data.status == "SUCCESS") {
              //     //   // console.log(data.status);
              //     //   this.confirmPayment(data.paymentReference);
              //     // }
              //     this.onClose(data);
              //   },
              //   //metadata: this.metadata,
              //   //paymentMethods: "CARD",
              // };
              // window.MonnifySDK.initialize(monnifyOptions);
              FlutterwaveCheckout({
                public_key: import.meta.env.MIX_RAVE_PUBLICKEY,
                tx_ref: ref,
                amount: this.newamount,
                currency: "NGN",
                payment_options: "banktransfer,card, account, ussd",
                // redirect_url: "https://glaciers.titanic.com/handle-flutterwave-payment",

                customer: {
                  email: this.user.email,
                  phone_number: this.user.phone,
                  name: this.user.firstname + " " + this.user.lastname,
                },
                customizations: {
                  title: "Swiftlink",
                  description: "Buy Data",
                  //logo: "https://www.logolynx.com/images/logolynx/22/2239ca38f5505fbfce7e55bbc0604386.jpeg",
                },
                callback: (payment) => {
                  // Send AJAX verification request to backend
                  this.loading = false;

                  console.log(payment);
                  if(payment.status == "completed"){
                    this.successflag = "Transaction will be processed shortly";
                  }


                  // console.log(verifyTransactionOnBackend(payment.id));
                },

                onclose: (incomplete) => {

                    this.verifyTransaction(incomplete);

                    // this.verifyTransaction(incomplete);

                }

              });

            });
        })
        .catch((error) => {
          this.loading = false;
          this.errorflag = error.response.data.message;
          // return null;
        });
    },

    Process(action) {
      if (action === "wallet") {
        this.buydata();
        return;
      } else {
        this.makePayment();
      }
    },

    verifyTransaction(incomplete){
      this.loading = false;

      if(incomplete === false){
        this.successflag = "Transaction will be processed shortly";
        console.log(this.successflag);
      }else{
        this.errorflag = "Transaction failed!";
        console.log(this.errorflag);
      }

      // Perform other operations upon close
      console.log("Flutterwave Payment closed", incomplete);
    },
    // onClose: function (data) {
    //   this.loading = false;

    //   // Perform other operations upon close
    //   console.log("Monnify Payment closed", data);
    //   //window.location.href = "/dashboard";
    // },

    confirmPayment(ref) {
      this.loading = true;

      this.form.user_id = this.user.id;
      this.form.ref = this.ref;
      console.log("got here");

      axios
        .get(`/api/payment/confirmorder/${ref}`, {
          headers: {
            Authorization: `Bearer ${token}`,
            "Content-Type": "application/json",
          },
        })
        .then((response) => {
          this.loading = false;
          // console.log(response.data.data);
          this.successflag = response.data.message;
          //window.location.href = "/dashboard";
        })
        .catch((error) => {
          this.loading = false;
          this.errorflag = error.response.data.message;
        });
    },

    refreshlist() {
      if (typeof page === "undefined") {
        page = 1;
      }
      axios
        .get(`/api/users?page=${page}`, {
          headers: {
            Authorization: `Bearer ${token}`,
            "Content-Type": "application/json",
          },
        })
        .then((response) => {
          console.log(response.data);
          this.users = response.data;
        });
    },

    buydata() {
      this.loading = true;
      axios
        .post(this.purchaseEndpoint, this.form, {
          headers: {
            Authorization: `Bearer ${token}`,
            "Content-Type": "application/json",
          },
        })
        .then((response) => {
          console.log(response.data);
          this.form.amount == "";
          this.form.phonenumber = "";
          this.successflag = response.data.message;
          this.loading = false;
          this.$toasted.show(response.data.message);
        })
        .catch((error) => {
          this.loading = false;
          this.errorflag = error.response.data.message;
          this.$toasted.show(error.response.data.message);
        });
    },

    loadplan(preserveSelection = false) {
      axios
        .get(`/api/subcategory`, {
          params: {
            category_id: this.categoryId,
            _catalogue_version: Date.now(),
          },
          headers: {
            Authorization: `Bearer ${token}`,
            "Content-Type": "application/json",
            "Cache-Control": "no-cache",
          },
        })
        .then((response) => {
          this.subcategories = response.data;
          if (!preserveSelection) return;

          const categories = this.subcategories.data || [];
          const selectedCategory = categories.find(
            (category) => category.id === this.form.subcategory_id
          );

          if (!selectedCategory) {
            this.form.subcategory_id = undefined;
            this.form.plan_id = undefined;
            this.form.amount = "";
            this.plans = [];
            this.description2 = "";
            if (!this.networkOptions.some((network) => network.key === this.selectedNetwork)) {
              this.selectedNetwork = "";
            }
            return;
          }

          this.applyCategoryPlans(selectedCategory);
          if (!this.plans.some((plan) => plan.plan === this.form.plan_id)) {
            this.form.plan_id = undefined;
            this.form.amount = "";
          }
        });
    },

    showAmount() {
      var c = this.plans.filter((sub) => sub.plan === this.form.plan_id);

      this.form.amount = this.getUserLevel(c[0]);

      if (this.form.amount <= 500) {
        this.newamount = parseInt(this.form.amount) + 10;
      } else if (
        parseInt(this.form.amount) > 500 &&
        parseInt(this.form.amount) <= 1000
      ) {
        this.newamount = parseInt(this.form.amount) + 20;
      } else if (
        parseInt(this.form.amount) > 1000 &&
        parseInt(this.form.amount) <= 2000
      ) {
        this.newamount = parseInt(this.form.amount) + 30;
      } else if (
        parseInt(this.form.amount) > 2000 &&
        parseInt(this.form.amount) <= 4000
      ) {
        this.newamount = parseInt(this.form.amount) + 60;
      } else if (
        parseInt(this.form.amount) > 4000 &&
        parseInt(this.form.amount) <= 6999
      ) {
        this.newamount = parseInt(this.form.amount) + 70;
      } else if (
        parseInt(this.form.amount) > 7000 &&
        parseInt(this.form.amount) <= 10000
      ) {
        this.newamount = parseInt(this.form.amount) + 150;
      } else if (parseInt(this.form.amount) > 10000) {
        this.newamount = parseInt(this.form.amount) + 220;
      }
      this.bankcharge = "Pay with Bank Charge: ";
    },

    showPlans() {
      this.loading = true;
      this.phonenumber = null;
      this.amount = null;
      var c = this.subcategories.data.filter(
        (sub) => sub.id === this.form.subcategory_id
      );
      if (!c.length) {
        this.loading = false;
        this.plans = [];
        return;
      }
      this.applyCategoryPlans(c[0]);

      this.loading = false;
    },

    applyCategoryPlans(category) {
      this.plans = this.normalizePlans(category.products);
      this.networkImage = category.subcat_image;
      this.description2 = category.description2;
    },

    normalizePlans(rawPlans) {
      let value = rawPlans;

      // Older records are JSON strings while some newly created records can
      // arrive double-encoded or wrapped by an object.
      for (let depth = 0; depth < 3 && typeof value === "string"; depth += 1) {
        const trimmed = value.trim();
        if (!trimmed) return [];
        try {
          value = JSON.parse(trimmed);
        } catch (_) {
          return [];
        }
      }

      if (Array.isArray(value)) return value;
      if (!value || typeof value !== "object") return [];
      if (value.plan) return [value];

      const wrappedPlans = value.plans ?? value.products ?? value.data;
      if (wrappedPlans !== undefined) return this.normalizePlans(wrappedPlans);

      // Accept objects saved with numeric plan keys, e.g. {"0": {...}}.
      return Object.values(value).filter(
        (plan) => plan && typeof plan === "object" && plan.plan
      );
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
img {
  width: 9vw;
  height: 9vw;
  padding: 5px;
}

input[type="radio"] {
  display: none;
}

img:hover {
  opacity: 0.6;
  cursor: pointer;
}

img:active {
  opacity: 0.4;
  cursor: pointer;
}

input[type="radio"]:checked + label > img {
  border: 2px solid #6777ef;
}

ul {
  list-style-type: none;
  margin: 0;
  padding: 0;
  overflow: hidden;
}

li {
  float: left;
  border: 2px solid #ccc;
  margin: 5px;
  border-radius: 10px;
}

li a {
  display: block;
  color: white;
  text-align: center;
  padding: 16px;
  text-decoration: none;
}

.centered {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
}
</style>
