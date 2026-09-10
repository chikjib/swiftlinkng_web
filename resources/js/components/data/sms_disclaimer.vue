<template>
  <div class="col-xl-12 col-lg-8 mx-auto">
    <div class="card shadow-sm disclaimer-card mb-4">
      <div class="card-header bg-white py-3">
        <h4 class="mb-1 text-danger font-weight-bold">
          <i class="fas fa-exclamation-triangle mr-2"></i>
          Bulk SMS Disclaimer
        </h4>
        <small class="text-muted">
          Please read the following information carefully before proceeding.
        </small>
      </div>

      <div class="card-body">

        <div class="notice-box danger">
          <h5>
            <i class="fas fa-ban mr-2"></i>
            Prohibited Content
          </h5>

          <p class="mb-0">
            Do <strong>not</strong> use our platform to send fraudulent,
            threatening, prank, impersonating, illegal, abusive, deceptive,
            or otherwise inappropriate messages.
          </p>

          <p class="mb-0 mt-2">
            Any violation may result in the immediate suspension of your account
            and may be reported to the appropriate authorities where necessary.
          </p>
        </div>

        <div class="notice-box info">
          <h5>
            <i class="fas fa-vial mr-2"></i>
            Test Before Bulk Sending
          </h5>

          <p class="mb-0">
            We strongly recommend sending your message to a
            <strong>small number of recipients first</strong>
            to verify delivery and formatting before sending it to your entire
            contact list.
          </p>
        </div>

        <div class="notice-box warning">
          <h5>
            <i class="fas fa-broadcast-tower mr-2"></i>
            Message Delivery
          </h5>

          <p class="mb-0">
            Message delivery is ultimately controlled by mobile network
            operators. They operate independent filtering systems and may
            block certain messages without prior notice.
          </p>

          <p class="mb-0 mt-2">
            While we continuously monitor delivery trends and customer
            feedback, we cannot override or influence network operator
            filtering decisions.
          </p>
        </div>

        <div class="notice-box refund">
          <h5>
            <i class="fas fa-money-bill-wave mr-2"></i>
            No Refund Policy
          </h5>

          <p class="mb-0">
            SMS units that have already been billed to us by network operators
            <strong>cannot be refunded</strong>, even where delivery to the
            recipient was unsuccessful.
          </p>
        </div>

        <div class="alert alert-light border mt-4 mb-4">
          <strong>Acknowledgement</strong>

          <p class="mb-0 mt-2">
            By clicking
            <strong>"I Understand & Continue"</strong>,
            you confirm that you have read, understood, and agreed to the
            above terms and accept responsibility for the messages you send.
          </p>
        </div>

        <div class="text-right">
          <button
            class="btn btn-danger px-4"
            @click="continueToBulkSMS"
          >
            <i class="fas fa-check-circle mr-2"></i>
            I Understand & Continue
          </button>
        </div>

      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: "BulkSMSDisclaimer",

  methods: {
    continueToBulkSMS() {
      this.$router.push("/dashboard/sms");
      // Change "/bulksms" to your actual route if different.
    },
  },
};
</script>

<style scoped>
.disclaimer-card {
  border: none;
  border-radius: 12px;
}

.card-header {
  border-bottom: 1px solid #ececec;
}

.notice-box {
  border-left: 5px solid;
  border-radius: 8px;
  padding: 18px;
  margin-bottom: 20px;
}

.notice-box h5 {
  font-weight: 600;
  margin-bottom: 10px;
}

.notice-box p {
  color: #555;
  line-height: 1.7;
}

.notice-box.danger {
  background: #fff5f5;
  border-color: #dc3545;
}

.notice-box.info {
  background: #f5f9ff;
  border-color: #007bff;
}

.notice-box.warning {
  background: #fff9ec;
  border-color: #ffc107;
}

.notice-box.refund {
  background: #f7f7f7;
  border-color: #6c757d;
}

.btn-danger {
  border-radius: 8px;
  padding: 10px 28px;
  font-weight: 600;
}

.alert-light {
  border-radius: 8px;
}

@media (max-width: 768px) {
  .card-body {
    padding: 20px;
  }

  .btn-danger {
    width: 100%;
  }
}
</style>
