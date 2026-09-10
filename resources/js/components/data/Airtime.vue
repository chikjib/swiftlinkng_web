<template>
  <main class="swift-service-page">
    <h1 class="swift-service-heading"><button class="swift-back" type="button" @click="$router.back()"><i class="fas fa-arrow-left"></i></button>Airtime Topup</h1>
    <div v-if="successflag" class="alert alert-success swift-alert">{{ successflag }}</div>
    <div v-if="errorflag" class="alert alert-danger swift-alert">{{ errorflag }}</div>

    <form class="swift-form-stack" @submit.prevent="Process('wallet')">
      <section class="swift-service-card">
        <div class="swift-provider-grid">
          <label v-for="provider in subcategories.data" :key="provider.id" class="swift-provider-option" :class="{ 'is-selected': form.subcategory_id === provider.id }">
            <input class="sr-only" type="radio" name="airtime-provider" v-model="form.subcategory_id" :value="provider.id" :disabled="loading" @change="showPlans" />
            <img :src="'/template/images/services/' + provider.subcat_image" :alt="provider.title" />
            <span>{{ provider.title }}</span>
          </label>
        </div>
      </section>

      <div class="swift-inline-fields">
        <div class="swift-form-group">
          <label for="airtime-phone">Phone Number</label>
          <div class="swift-field-wrap"><i class="fas fa-phone-alt"></i><input id="airtime-phone" v-model="form.phonenumber" required inputmode="numeric" class="form-control" placeholder="0801 234 5678" @input="removeSpaces" /></div>
        </div>
        <div class="swift-form-group">
          <label for="airtime-amount">Amount</label>
          <div class="swift-field-wrap"><i class="far fa-credit-card"></i><input id="airtime-amount" v-model="amount" required min="100" type="number" class="form-control" placeholder="Enter amount" /></div>
        </div>
      </div>
      <input type="hidden" v-model="form.amount" />
      <div class="swift-check-row"><span>Is it a ported number?</span><input type="checkbox" v-model="form.ported" value="yes" aria-label="Ported number" /></div>
      <section v-if="form.subcategory_id" class="swift-service-card swift-price-note"><span>Amount to pay</span><strong>₦{{ form.discount || 0 }}</strong></section>
      <section v-if="description2" class="swift-service-card swift-help-card"><i class="far fa-lightbulb mr-2"></i>{{ description2 }}</section>
      <button class="swift-primary-action" type="submit" :disabled="loading || !form.subcategory_id">{{ loading ? 'Processing…' : 'Buy Now' }}</button>
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
      bankcharge: "",
      plan_id: null,
      phonenumber: null,
      description2: "",
      amount: 0,
      newamount: 0,
      discount: 0,
      loading: false,
      successflag: "",
      errorflag: "",
      scriptLoaded: null,
    };
  },

  mounted() {
    this.loadplan();
    // this.requery("813df6a0a2");
  },

  watch: {
    amount(after, before) {
      this.showActualAmount();
    },
  },
  created() {
    this.scriptLoaded = new Promise((resolve) => {
      this.loadScript(() => {
        resolve();
      });
    });
  },
  methods: {
    removeSpaces(event) {
        // Remove all spaces and non-digit characters
      const cleaned = event.target.value.replace(/\D/g, '');
      this.form.phonenumber = cleaned;
    },
    // requery(ref){
    //     axios
    //       .get(`/api/requery/${ref}`, {
    //         headers: {
    //           Authorization: `Bearer ${token}`,
    //           "Content-Type": "application/json",
    //         },
    //       })
    //       .then((response) => {
    //         console.log(response.data);
    //         this.loading = false;

    //         this.successflag = response.data.message;
    //         this.$toasted.show(response.data.message);
    //       });
    // },
    loadScript(callback) {
      const script = document.createElement("script");
      // script.src = "https://sdk.monnify.com/plugin/monnify.js";
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

    buyairtime() {
      this.loading = true;
      if (this.form.amount < 100) {
        this.errorflag = "Amount cannot be less than 100 Naira";
        this.$toasted.show("Amount cannot be less than 100 Naira");
      } else {
        axios
          .post(`/api/purchase/airtime`, this.form, {
            headers: {
              Authorization: `Bearer ${token}`,
              "Content-Type": "application/json",
            },
          })
          .then((response) => {
            //console.log(response.data);
            this.loading = false;

            this.successflag = response.data.message;
            this.$toasted.show(response.data.message);
          })
          .catch((error) => {
            // console.log(error);
            this.loading = false;

            this.errorflag = error.response.data.message;
            this.$toasted.show(error.response.data.message);
          });
      }
    },

    makePayment() {
      if (this.form.amount < 100) {
        this.errorflag = "Amount cannot be less than 100 Naira";
        this.$toasted.show("Amount cannot be less than 100 Naira");
      } else {
        this.loading = true;

        this.form.user_id = this.user.id;
        this.form.newamount = this.newamount;
        axios
          .post(`/api/payment/initiateOrder`, this.form, {
            headers: {
              Authorization: `Bearer ${token}`,
              "Content-Type": "application/json",
            },
          })
          .then((response) => {
            // console.log(response.data.data);
            var ref = response.data.data;

            this.form.user_id = this.user.id;
            this.form.ref = ref;
            // var token = this.token;
            // var succ = this.successflag;
            // var err = this.errorflag;
            // var ld = this.loading;

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
                //   paymentDescription: "Buy Airtime",
                //   isTestMode: true,
                //   onComplete: async (response) => {
                //     if (response.status == "SUCCESS") {
                //       console.log(
                //         "got to payment " + response.paymentReference
                //       );

                //       this.successflag =
                //         "Transaction will be processed shortly";

                //       // await this.confirmPayment(response.paymentReference);

                //       // await axios
                //       //   .get(
                //       //     `/api/payment/confirmorder/${response.paymentReference}`,
                //       //     {
                //       //       headers: {
                //       //         Authorization: `Bearer ${window.localStorage.getItem(
                //       //           "token"
                //       //         )}`,
                //       //         "Content-Type": "application/json",
                //       //       },
                //       //     }
                //       //   )
                //       //   .then((response2) => {
                //       //     // console.log("got here 2");
                //       //     console.log(response2.data);
                //       //     //succ = response.data.message;
                //       //     // this.$toasted.show(response.data.message);

                //       //     alert(response2.data.message);
                //       //     //window.location.href = "/dashboard";
                //       //   })
                //       //   .catch((error) => {
                //       //     // ld = false;
                //       //     console.log("got here 3");
                //       //     // err = error.response.data.message;
                //       //     // this.$toasted.show(error.response.data.message);

                //       //     alert(error.response.data.message);
                //       //   });
                //     }
                //   },
                //   onClose: (data) => {
                //     // // console.log(data);
                //     // if (data.status == "SUCCESS") {
                //     //   // console.log(data.status);
                //     //   console.log("payment success");

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
                  description: "Buy Airtime",
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
      }
    },

    Process(action) {
      if (action === "wallet") {
        this.buyairtime();
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

    async confirmPayment(ref) {
      this.loading = true;

      this.form.user_id = this.user.id;
      this.form.ref = this.ref;
      console.log("got here");

      await axios
        .get(`/api/payment/confirmorder/${ref}`, {
          headers: {
            Authorization: `Bearer ${token}`,
            "Content-Type": "application/json",
          },
        })
        .then((response) => {
          this.loading = false;
          // console.log("got here 2");
          console.log(response.data);
          this.successflag = response.data.message;
          //window.location.href = "/dashboard";
        })
        .catch((error) => {
          this.loading = false;
          // console.log("got here 3");
          this.errorflag = error.response.data.message;
        });
    },
    onClose: function (data) {
      // Perform other operations upon close
      this.loading = false;

      console.log("Monnify Payment closed", data);
      //window.location.href = "/dashboard";
    },

    loadplan() {
      axios
        .get(`/api/subcategory?category_id=2`, {
          headers: {
            Authorization: `Bearer ${token}`,
            "Content-Type": "application/json",
          },
        })
        .then((response) => {
          // console.log(response.data);
          this.subcategories = response.data;
        });
    },

    showPlans() {
      this.loading = true;
      this.phonenumber = "";
      this.amount = 0;
      var c = this.subcategories.data.filter(
        (sub) => sub.id === this.form.subcategory_id
      );

      var content = JSON.parse(c[0].products);

      this.form.discount = this.getUserLevel(content) + "% Discount";
      this.discount = this.getUserLevel(content);

      this.description2 = c[0].description2;
      this.loading = false;
    },

    showActualAmount() {
      this.form.amount = this.amount;

      this.form.discount = this.amount - this.amount * (this.discount / 100);

      this.newamount =
        parseInt(this.amount) + (1.6 / 100) * parseInt(this.amount);

      this.bankcharge = "Pay with Bank Charge: ";
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
