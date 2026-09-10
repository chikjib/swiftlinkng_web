<template>
  <main class="swift-service-page">
    <h1 class="swift-service-heading"><button class="swift-back" type="button" @click="$router.back()"><i class="fas fa-arrow-left"></i></button>Withdraw</h1>
    <div v-if="successflag" class="alert alert-success swift-alert">{{ successflag }}</div><div v-if="errorflag" class="alert alert-danger swift-alert">{{ errorflag }}</div>
    <section class="swift-service-card"><div class="d-flex align-items-center"><span class="swift-transaction-icon mr-3"><i class="fas fa-university"></i></span><div><strong>Withdraw to bank account</strong><div class="text-muted">Funds are sent to your saved bank details.</div></div></div></section>
    <form class="swift-form-stack" @submit.prevent="withdraw">
      <div class="swift-form-group"><label for="withdraw-amount">Amount</label><div class="swift-field-wrap"><i class="far fa-credit-card"></i><input id="withdraw-amount" v-model="form.amount" required min="1" type="number" class="form-control" placeholder="Enter amount" /></div></div>
      <section class="swift-service-card swift-help-card"><i class="fas fa-info-circle mr-2"></i>Confirm your bank details in Profile before requesting a withdrawal.</section>
      <button class="swift-primary-action" type="submit" :disabled="loading">{{ loading ? 'Processing…' : 'Withdraw Now' }}</button>
    </form>
  </main>
</template>

<script>
const token = window.localStorage.getItem("token");

export default {
  // props: ["user"],
  data() {
    return {
      form: {},
      amount: 0,
      loading: false,
      successflag: "",
      errorflag: "",
    };
  },

  methods: {
    withdraw() {
      if (this.form.amount <= 0) {
        this.errorflag = "Invalid amount specified";

      } else {
        axios
          .post(`/api/fund/withdraw`, this.form, {
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
      }
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
