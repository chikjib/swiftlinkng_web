<template>
  <section class="verification-page">
    <div class="verification-card">
      <div class="mail-icon" aria-hidden="true">
        <i class="fas fa-envelope-open-text"></i>
      </div>

      <span class="eyebrow">REGISTRATION SUCCESSFUL</span>
      <h1>Verify your email to unlock rewards</h1>
      <p class="lead">
        We sent a verification link<span v-if="maskedEmail"> to <strong>{{ maskedEmail }}</strong></span>.
        Open the message and click the link to verify your email address.
      </p>

      <div class="reward-note">
        <i class="fas fa-gift"></i>
        <div>
          <strong>Verification is required for referral rewards</strong>
          <p>
            You can skip verification and continue using Swiftlink, but you will not receive the ₦50 welcome bonus
            or other fixed referral rewards until your email is verified.
          </p>
        </div>
      </div>

      <div v-if="message" class="alert" :class="messageType === 'success' ? 'alert-success' : 'alert-danger'">
        {{ message }}
      </div>

      <div class="verification-actions">
        <a href="/login" class="primary-action">Continue to login</a>
        <button v-if="email" type="button" class="secondary-action" :disabled="sending" @click="resend">
          {{ sending ? "Sending..." : "Resend verification email" }}
        </button>
      </div>

      <p class="small-note">Check your spam or promotions folder if the message is not in your inbox.</p>
    </div>
  </section>
</template>

<script>
const EMAIL_KEY = "swiftlink_pending_verification_email";

export default {
  name: "VerifyEmailNotice",

  data() {
    return {
      email: window.sessionStorage.getItem(EMAIL_KEY) || "",
      sending: false,
      message: "",
      messageType: "success",
    };
  },

  computed: {
    maskedEmail() {
      if (!this.email || this.email.indexOf("@") === -1) return "";
      const parts = this.email.split("@");
      const local = parts[0];
      const visible = local.slice(0, Math.min(2, local.length));
      return `${visible}${"*".repeat(Math.max(3, local.length - visible.length))}@${parts[1]}`;
    },
  },

  methods: {
    resend() {
      this.sending = true;
      this.message = "";
      axios
        .post("/api/email/verification/resend", { email: this.email })
        .then(() => {
          this.messageType = "success";
          this.message = "A fresh verification link has been sent. Please check your inbox.";
        })
        .catch((error) => {
          this.messageType = "error";
          this.message = error.response && error.response.data && error.response.data.message
            ? error.response.data.message
            : "The verification email could not be sent. Please try again later.";
        })
        .finally(() => {
          this.sending = false;
        });
    },
  },
};
</script>

<style scoped>
.verification-page {
  min-height: calc(100vh - 90px);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: clamp(42px, 8vw, 90px) 18px;
  background: radial-gradient(circle at top, #fff2f3 0, #f8f8fa 58%);
}

.verification-card {
  width: 100%;
  max-width: 620px;
  padding: clamp(28px, 5vw, 44px);
  border: 1px solid #f3dadd;
  border-radius: 26px;
  background: #fff;
  text-align: center;
  box-shadow: 0 24px 65px rgba(79, 13, 24, 0.12);
}

.mail-icon {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 82px;
  height: 82px;
  margin-bottom: 18px;
  border-radius: 50%;
  color: #fff;
  background: linear-gradient(135deg, #d20b27, #ff5369);
  font-size: clamp(27px, 5vw, 34px);
  box-shadow: 0 12px 30px rgba(210, 11, 39, 0.27);
}

.eyebrow {
  display: block;
  color: #d20b27;
  font-size: 12px;
  font-weight: 800;
  letter-spacing: 0.14em;
}

h1 {
  margin: 9px 0 14px;
  color: #241b1d;
  font-size: 34px;
  font-weight: 850;
}

.lead {
  color: #6c5d60;
  font-size: 17px;
  line-height: 1.7;
}

.reward-note {
  display: flex;
  gap: 16px;
  margin: 28px 0;
  padding: 20px;
  border-radius: 15px;
  color: #553e10;
  background: #fff8df;
  text-align: left;
}

.reward-note > i {
  color: #d49100;
  font-size: 25px;
}

.reward-note p {
  margin: 5px 0 0;
  line-height: 1.55;
}

.verification-actions {
  display: flex;
  flex-wrap: wrap;
  justify-content: center;
  gap: 12px;
  margin-top: 24px;
}

.primary-action,
.secondary-action {
  min-width: 210px;
  padding: 13px 20px;
  border: 2px solid #d20b27;
  border-radius: 10px;
  font-weight: 750;
  text-decoration: none;
  cursor: pointer;
}

.primary-action {
  color: #fff;
  background: #d20b27;
}

.secondary-action {
  color: #d20b27;
  background: #fff;
}

.secondary-action:disabled { cursor: wait; opacity: 0.65; }
.small-note { margin: 23px 0 0; color: #87777a; font-size: 13px; }

@media (max-width: 576px) {
  .verification-page { align-items: flex-start; padding-top: 34px; }
  .verification-card { padding: 30px 20px; border-radius: 22px; }
  .primary-action, .secondary-action { width: 100%; }
}

@media (prefers-color-scheme: dark) {
  .verification-page { background: radial-gradient(circle at top, #311317 0, #101214 58%); }
  .verification-card { color: #fff; background: #1b1e22; border-color: #3b2a2d; }
  h1 { color: #fff; }
  .lead, .small-note { color: #c7c2c3; }
  .reward-note { color: #ffe7aa; background: #302817; }
  .secondary-action { color: #fff; background: #24282d; }
}
</style>
