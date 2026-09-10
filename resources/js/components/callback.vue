<template>
  <div v-if="isloading">
    <vcl-twitch></vcl-twitch>
  </div>

  <div v-else class="row mb-3">
    <div class="col-md-12">
        <div
      v-if="successflag != ''"
      class="alert alert-success alert-dismissible"
      role="alert"
    >{{ successflag }}</div>
        <div
      v-if="pendingflag != ''"
      class="alert alert-warning alert-dismissible"
      role="alert"
    > {{ pendingflag }}</div>
      <div class="row mb-3"></div>
    </div>

  </div>
</template>

<script>
import { VclTwitch } from "vue-content-loading";

const transaction_reference = localStorage.getItem("transactionReference");
const token = window.localStorage.getItem("token");

export default {
    components: {
        VclTwitch,
    },
  data() {
    return {
      isloading: false,
      successflag: "",
      pendingflag: ""
    };
  },
  mounted() {
    this.verifyPayment();
  },
  methods: {
    verifyPayment() {
      this.isloading = true;
      //   console.log(this.form);
      axios
        .get(`/api/verify-payment/${transaction_reference}`, {
          headers: {
            Authorization: `Bearer ${token}`,
            "Content-Type": "application/json",
          },
        })
        .then((response) => {
           // console.log(response);
            // let resp = response.data.data;
          if(response.data.data.responseBody.paymentStatus === "PAID"){
            this.successflag = "Payment successful"
          }else{
            this.pendingflag = `Payment ${response.data.data.responseBody.paymentStatus}`;
          }

          this.isloading = false;

        })
        .catch((error) => {
          this.isloading = false;
          this.errorflag = error.response.data.message;
          this.$toasted.show(error.response.data.message);
          // return null;
        });
    },
  },
};
</script>
