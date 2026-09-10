<template>
  <main class="swift-service-page">
    <h1 class="swift-service-heading"><button class="swift-back" type="button" @click="$router.back()"><i class="fas fa-arrow-left"></i></button>Manual Funding</h1>
    <div v-if="successflag" class="alert alert-success swift-alert">{{ successflag }}</div><div v-if="errorflag" class="alert alert-danger swift-alert">{{ errorflag }}</div>
    <section class="swift-service-card swift-help-card swift-manual-guide">
      <h2>How to fund manually</h2>
      <p><strong>Minimum funding is ₦4,000.</strong></p>
      <ol>
        <li>Pay into <strong>0762974174 · PluginLinkNg · GTBank</strong>.</li>
        <li>Fill the form with the payment details.</li>
        <li>Click the submit button.</li>
        <li>Submit only once to avoid delays or duplicate funding requests.</li>
      </ol>
    </section>
    <form class="swift-form-stack" @submit.prevent="submitManual">
      <div class="swift-form-group"><label for="payment-name">Payment Name</label><div class="swift-field-wrap"><i class="fas fa-user"></i><input id="payment-name" v-model="form.paymentname" required class="form-control" placeholder="Name used for payment" /></div></div>
      <div class="swift-form-group"><label for="manual-bank">Bank Account Paid Into</label><div class="swift-field-wrap"><i class="fas fa-university"></i><select id="manual-bank" v-model="form.bank" required class="form-control"><option :value="undefined">Select bank account</option><option v-for="plan in plans" :key="plan.account" :value="plan.account">{{ plan.bank }} • {{ plan.account }}</option></select></div></div>
      <div class="swift-inline-fields"><div class="swift-form-group"><label for="manual-amount">Amount</label><div class="swift-field-wrap"><i class="far fa-credit-card"></i><input id="manual-amount" v-model="form.amount" required min="1" type="number" class="form-control" placeholder="Enter amount" /></div></div><div class="swift-form-group"><label for="payment-type">Payment Type</label><div class="swift-field-wrap"><i class="fas fa-exchange-alt"></i><input id="payment-type" v-model="form.type" required class="form-control" placeholder="USSD, transfer or POS" /></div></div></div>
      <button class="swift-primary-action" type="submit">Submit Funding Request</button>
    </form>
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
      amount: 0,
      loading: false,
      successflag: "",
      errorflag: "",
      page: 1,
      message: "",
      description2: "",
    };
  },

  mounted() {
    this.loadplan();
  },

  methods: {
    submitManual() {
      axios
        .post(`/api/payment/manual/initiate`, this.form, {
          headers: {
            Authorization: `Bearer ${token}`,
            "Content-Type": "application/json",
          },
        })
        .then((response) => {
          //console.log(response.data);
          this.successflag = response.data.message;
          this.$toasted.show(response.data.message);
        })
        .catch((error) => {
          // console.log(error);
          this.errorflag = error.response.data.message;
          this.$toasted.show(error.response.data.message);
        });
    },

    loadplan() {
      axios
        .get(`/api/subcategory?category_id=7`, {
          headers: {
            Authorization: `Bearer ${token}`,
            "Content-Type": "application/json",
          },
        })
        .then((response) => {
          this.subcategories = response.data;

          this.showPlans();
        });
    },

    showPlans() {
      this.loading = true;

      var c = this.subcategories.data.filter((sub) => sub.title === "Manual");

      var content = JSON.parse(c[0].products);
      this.description2 = c[0].description2;

      console.log(content);

      this.plans = content;

      this.loading = false;
    },
  },
};
</script>

<style scoped>
img {
  width: 10vw;
  height: 10vw;
  padding: 10px;
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
