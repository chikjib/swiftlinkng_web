<template>
  <main class="swift-service-page">
    <h1 class="swift-service-heading"><button class="swift-back" type="button" @click="$router.back()"><i class="fas fa-arrow-left"></i></button>Cable TV</h1>
    <div v-if="successflag" class="alert alert-success swift-alert">{{ successflag }}</div><div v-if="errorflag" class="alert alert-danger swift-alert">{{ errorflag }}</div>
    <form class="swift-form-stack" @submit.prevent="subscribe">
      <section class="swift-service-card"><div class="swift-provider-grid"><label v-for="provider in subcategories.data" :key="provider.id" class="swift-provider-option" :class="{ 'is-selected': subcategory_id === provider.title }"><input class="sr-only" type="radio" name="cable-provider" v-model="subcategory_id" :value="provider.title" :disabled="loading" @change="showPlans" /><img :src="'/template/images/services/' + provider.subcat_image" :alt="provider.title" /><span>{{ provider.title }}</span></label></div></section>
      <div v-if="title !== 'SHOWMAX'" class="swift-form-group"><label for="cable-card">Smart Card Number</label><div class="swift-field-wrap"><i class="far fa-credit-card"></i><input id="cable-card" v-model="form.cardno" class="form-control" placeholder="Enter smart card number" /></div><button class="btn btn-outline-danger mt-2" type="button" :disabled="verifiying || !subcategory_id" @click="verifyCard">{{ verifiying ? 'Verifying…' : 'Verify Smart Card' }}</button><div v-if="cardowner" class="mt-2 font-weight-bold text-success">{{ cardowner }}</div></div>
      <div class="swift-form-group"><label for="cable-bouquet">Choose Bouquet</label><div class="swift-field-wrap"><i class="fas fa-tv"></i><select id="cable-bouquet" v-model="form.variation_code" class="form-control" :disabled="!subcategory_id" @change="showAmount"><option :value="undefined">Select bouquet</option><option v-for="bouq in bouquet.data" :key="bouq.variation_code" :value="bouq.variation_code">{{ bouq.name }}</option></select></div></div>
      <div class="swift-inline-fields"><div class="swift-form-group"><label for="cable-phone">Phone Number</label><div class="swift-field-wrap"><i class="fas fa-phone-alt"></i><input id="cable-phone" v-model="form.phonenumber" required inputmode="numeric" class="form-control" placeholder="0801 234 5678" /></div></div><div class="swift-form-group"><label for="cable-amount">Amount</label><div class="swift-field-wrap"><i class="far fa-credit-card"></i><input id="cable-amount" v-model="amount" readonly class="form-control" placeholder="Amount" /></div></div></div>
      <input type="hidden" v-model="form.amount" />
      <section v-if="amount" class="swift-service-card swift-price-note"><span>Amount to pay</span><strong>₦{{ form.discount || amount }}</strong></section>
      <button class="swift-primary-action" type="submit" :disabled="!successCode || loading">{{ loading ? 'Processing…' : 'Buy Now' }}</button>
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
      bouquet: {},

      category_id: null,
      subcategory_id: null,
      plan_id: null,
      phonenumber: null,
      amount: null,
      discount: 0,

      loading: false,
      cardowner: "",
      successCode: false,
      verifiying: false,
      successflag: "",
      errorflag: "",
      discription: "",
      title: "",
    };
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
    subscribe() {
      this.loading = true;

      this.form.plan = this.subcategory_id;
      this.form.description = this.description;

      axios
        .post(`/api/purchase/cable`, this.form, {
          headers: {
            Authorization: `Bearer ${token}`,
            "Content-Type": "application/json",
          },
        })
        .then((response) => {
          this.successflag = response.data.message;
          this.loading = false;
          this.form.amount = "";
          this.amount = null;
          this.form.cardno = "";
          this.form.phonenumber = "";
          this.$toasted.show(response.data.message);
        })
        .catch((error) => {
          this.loading = false;
          this.errorflag = error.response.data.message;
          this.$toasted.show(error.response.data.message);
        });
    },

    loadplan() {
      axios
        .get(`/api/subcategory?category_id=3`, {
          headers: {
            Authorization: `Bearer ${token}`,
            "Content-Type": "application/json",
          },
        })
        .then((response) => {
          this.subcategories = response.data;

        });
    },

    showAmount() {
      var c = this.bouquet.data.filter(
        (x) => x.variation_code == this.form.variation_code
      );
      this.amount = c[0].variation_amount;
    },

    showPlans() {
      this.loading = true;
      this.phonenumber = null;
      // this.amount = null;
      var c = this.subcategories.data.filter(
        (sub) => sub.title === this.subcategory_id
      );
      var content = JSON.parse(c[0].products);

      this.form.discount = this.getUserLevel(content) + "% Discount";
      this.discount = this.getUserLevel(content);
      this.description = c[0].description;
      this.title = c[0].title;

      axios
        .get(
          `/api/fetch/bouquet?plan=${this.subcategory_id}&description=${this.description}`,
          {
            headers: {
              Authorization: `Bearer ${token}`,
              "Content-Type": "application/json",
            },
          }
        )
        .then((response) => {
          // console.log(response.data);

          this.bouquet = response.data;
        //   console.log(this.subcategories)
          if(this.title == "SHOWMAX"){
            this.successCode = true
          }

          this.loading = false;
        });
    },

    verifyCard() {
      this.successCode = false;
      this.verifiying = true;

      if (this.subcategory_id == "") {
        return this.$toasted.show("No provider has been selected");
      }
      this.form.plan = this.subcategory_id;
      this.form.description = this.description;

      axios
        .post(`/api/verify/card`, this.form, {
          headers: {
            Authorization: `Bearer ${token}`,
            "Content-Type": "application/json",
          },
        })
        .then((response) => {
          //  this.successCodee=
          console.log(response.data);
          if (response.data.data.content.Customer_Name != null) {
            this.cardowner =
              "Customer:" + response.data.data.content.Customer_Name;
            this.successCode = true;
            this.verifiying = false;
          } else {
            this.cardowner = "Card Owner Info not found";
            this.successCode = false;
            this.verifiying = false;
          }
        })
        .catch((error) => {
          console.log(error);
          this.verifiying = false;

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
