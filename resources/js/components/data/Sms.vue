<template>
  <main class="swift-service-page">
    <h1 class="swift-service-heading"><button class="swift-back" type="button" @click="$router.back()"><i class="fas fa-arrow-left"></i></button>Bulk SMS</h1>
    <div v-if="successflag" class="alert alert-success swift-alert">{{ successflag }}</div><div v-if="errorflag" class="alert alert-danger swift-alert">{{ errorflag }}</div>
    <form class="swift-form-stack" @submit.prevent="sendsms">
      <div class="swift-form-group"><label for="sender-id">Sender ID</label><div class="swift-field-wrap"><i class="fas fa-signature"></i><input id="sender-id" v-model="form.senderID" maxlength="13" required class="form-control" placeholder="Your sender name" /></div></div>
      <div class="swift-form-group"><label for="sms-numbers">Recipients</label><textarea id="sms-numbers" v-model="form.phone" required class="form-control" rows="4" placeholder="Enter phone numbers separated by commas"></textarea></div>
      <div class="swift-form-group"><label for="sms-message">Message</label><textarea id="sms-message" v-model="message" required class="form-control" rows="5" placeholder="Type your message"></textarea><div class="d-flex justify-content-between mt-2"><span>Page {{ page }}</span><strong>{{ message.length }} / {{ page * 160 }}</strong></div></div>
      <section class="swift-service-card swift-price-note"><span>SMS rate</span><strong>₦{{ form.amount || 0 }} / page</strong></section>
      <button class="swift-primary-action" type="submit" :disabled="isloading">{{ isloading ? 'Sending…' : 'Send SMS' }}</button>
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
      isloading: false,
    };
  },

  mounted() {
    this.loadplan();
  },

  watch: {
    message(after, before) {
      this.page = Math.ceil(this.message.length / 160);
    },
  },

  methods: {
    sendsms() {
      this.form.message = this.message;
      this.isloading = !this.isloading;

      axios
        .post(`/api/send/sms`, this.form, {
          headers: {
            Authorization: `Bearer ${token}`,
            "Content-Type": "application/json",
          },
        })
        .then((response) => {
        //   console.log(response.data);
          this.successflag = response.data.message;
          this.$toasted.show(response.data.message);
          this.isloading = false;
        //   this.$router.go(this.$router.currentRoute)
        })
        .catch((error) => {
        //   console.log(error);
          this.errorflag = error.response.data.message;
          this.$toasted.show(error.response.data.message);
          this.isloading = false;
        //   this.$router.go(this.$router.currentRoute)
        });
    },

    loadplan() {
      axios
        .get(`/api/subcategory?category_id=11`, {
          headers: {
            Authorization: `Bearer ${token}`,
            "Content-Type": "application/json",
          },
        })
        .then((response) => {
          // console.log(response.data);
          this.subcategories = response.data;

          this.showPlans();
        });
    },

    showPlans() {
      this.loading = true;

      var c = this.subcategories.data.filter((sub) => sub.title === "BULKSMS");

      var content = JSON.parse(c[0].products);
      console.log(content);

      this.form.amount = this.getUserLevel(content);
      console.log(this.form.amount);
      this.loading = false;
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
