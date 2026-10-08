<template>
  <div v-if="isloading">
    <vcl-twitch></vcl-twitch>
  </div>
  <div v-else class="row mb-3">
    <div class="col-md-12">
      <div v-if="successflag" class="payment-response payment-response--success" role="alert">{{ successflag }}</div>
      <div v-if="pendingflag" class="payment-response payment-response--pending" role="alert">{{ pendingflag }}</div>
      <div v-if="errorflag" class="payment-response payment-response--error" role="alert">{{ errorflag }}</div>
      <button v-if="errorflag || pendingflag" class="btn btn-primary" @click="verifyPayment">Check payment again</button>
    </div>
  </div>
</template>

<script>
import axios from "axios";
import { VclTwitch } from "vue-content-loading";

export default {
  components: { VclTwitch },
  data() {
    return { isloading: false, successflag: "", pendingflag: "", errorflag: "" };
  },
  mounted() {
    this.verifyPayment();
  },
  methods: {
    async verifyPayment() {
      this.successflag = "";
      this.pendingflag = "";
      this.errorflag = "";
      const query = this.$route.query;
      const referenceType = Object.prototype.hasOwnProperty.call(query, "paymentReference")
        ? "paymentReference" : "transactionReference";
      const reference = query[referenceType] ?? localStorage.getItem("transactionReference");
      if (typeof reference !== "string" || !reference.trim()) {
        this.errorflag = "No payment reference was provided. Open the callback link from your payment checkout.";
        return;
      }
      this.isloading = true;
      try {
        const { data } = await axios.get(`/api/verify-payment/${encodeURIComponent(reference)}`, {
          params: { reference_type: referenceType },
          headers: { Authorization: `Bearer ${localStorage.getItem("token")}` },
        });
        const result = data.data;
        const status = result?.responseBody?.paymentStatus;
        if (result?.requestSuccessful !== true || !status) {
          throw new Error(result?.responseMessage || "Unable to verify this payment. Please try again.");
        }
        if (status === "PAID") {
          this.successflag = "Payment successful";
        } else {
          this.pendingflag = `Payment status: ${status}`;
        }
      } catch (error) {
        this.errorflag = error.response?.data?.message || error.message || "Unable to verify this payment. Please try again.";
      } finally {
        this.isloading = false;
      }
    },
  },
};
</script>

<style scoped>
.payment-response {
  padding: 16px 20px;
  margin-bottom: 16px;
  border: 1px solid;
  border-radius: 8px;
  font-weight: 600;
  line-height: 1.6;
  overflow-wrap: anywhere;
}

.payment-response.payment-response--success {
  color: #14532d;
  background: #dcfce7;
  border-color: #86efac;
}

.payment-response.payment-response--pending {
  color: #713f12;
  background: #fef3c7;
  border-color: #fcd34d;
}

.payment-response.payment-response--error {
  color: #7f1d1d;
  background: #fee2e2;
  border-color: #fca5a5;
}
</style>
