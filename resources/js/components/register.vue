<template>
  <div>
    <!--Sign In Start -->
    <section class="sign-in">
      <div class="container">
        <div v-if="successflag" class="sign-in__top text-success">
          {{ successflag }}
        </div>
        <div class="row">
          <div class="col-xl-6 col-lg-6 mx-auto">
            <div class="sign-in__single swift-auth-card swift-auth-card--wide">
              <span class="swift-auth-eyebrow">CREATE YOUR WALLET</span>
              <h3 class="sign-in__title">Join Swiftlinkng</h3>
              <p class="swift-auth-subtitle">One account for airtime, data, bills and instant wallet funding.</p>
              <form
                method="POST"
                class="sign-in__form"
                @submit.prevent="register"
              >
                <div class="sign-in__form-input-box">
                  <label for="firstname">First name</label>
                  <input
                    type="text"
                    id="firstname"
                    class="form-control form-control-lg"
                    v-model="user.firstname"
                    name="name"
                    required
                    autocomplete="firstname"
                    autofocus
                    placeholder="First Name"
                  />
                </div>
                <div class="sign-in__form-input-box">
                  <label for="name">Last name</label>
                  <input
                    type="text"
                    id="name"
                    class="form-control form-control-lg"
                    v-model="user.lastname"
                    name="lastname"
                    required
                    autocomplete="lastname"
                    autofocus
                    placeholder="Last Name"
                  />
                </div>
                <div class="sign-in__form-input-box">
                  <label for="email">Email address</label>
                  <input
                    type="email"
                    id="email"
                    class="form-control form-control-lg"
                    v-model="user.email"
                    name="email"
                    required
                    autocomplete="email"
                    autofocus
                    placeholder="Email Address"
                  />
                </div>
                <div class="sign-in__form-input-box">
                  <label for="phone">Phone number</label>
                  <input
                    type="text"
                    id="phone"
                    class="form-control form-control-lg"
                    v-model="user.phone"
                    name="phone"
                    required
                    autocomplete="phone"
                    autofocus
                    placeholder="Phone Number"
                  />
                </div>
                <div class="sign-in__form-input-box">
                  <label for="register-password">Password</label>
                  <input
                    id="register-password"
                    type="password"
                    class="form-control form-control-lg"
                    v-model="user.password"
                    required
                    placeholder="Password"
                  />
                </div>

                <div class="sign-in__form-input-box">
                  <label for="register-password-confirmation">Confirm password</label>
                  <input
                    id="register-password-confirmation"
                    type="password"
                    class="form-control form-control-lg"
                    v-model="user.c_password"
                    required
                    placeholder="Confirm Password"
                  />
                </div>
                <div class="sign-in__form-input-box">
                  <label>How did you hear about us?</label>
                  <select v-model="user.hear_about_us" class="form-control">
                    <option value="" selected disabled>
                      How did you hear about us?
                    </option>
                    <option value="Facebook">Facebook</option>
                    <option value="Instagram">Instagram</option>
                    <option value="Twitter">Twitter</option>
                    <option value="Google">Google</option>
                    <option value="From a friend">From a friend</option>
                  </select>
                </div>

                <div class="sign-in__form-input-box">
                  <label for="referral_code">
                    Referral Code <small class="text-muted">(Optional)</small>
                  </label>
                  <input
                    type="text"
                    id="referral_code"
                    class="form-control form-control-lg"
                    v-model.trim="user.referral_code"
                    name="referral_code"
                    autocomplete="off"
                    inputmode="numeric"
                    placeholder="Enter referral code"
                  />
                  <small v-if="referralFromLink" class="text-success">
                    Referral code applied from your invite link.
                  </small>
                </div>

                <div class="sign-in__form-input-box">
                  <p>
                    By clicking the registration button, you agree to our
                    <a href="/terms"> Terms and Conditions </a> and
                    <a href="/privacy"> Privacy Policy </a>
                  </p>
                </div>

                <div class="sign-in__form-btn-box">
                  <div class="sign-in__form-forgot-password">
                    <div class="mt-3">
                      <button
                        type="submit"
                        :disabled="processing"
                        class="thm-btn sign-in__form-btn"
                      >
                        {{ processing ? "PLEASE WAIT..." : "SIGN UP" }}
                      </button>
                    </div>
                    <div v-if="errorflag != ''" class="alert alert-danger swift-auth-alert">
                      {{ errorflag }}
                    </div>

                    <div class="sign-in__form-forgot-password">
                      <a href="/login" class="font-weight-bold small"
                        >Already registered? Sign in</a
                      >
                    </div>
                  </div>
                </div>
              </form>
            </div>
          </div>
        </div>
      </div>
    </section>
    <!--Sign In End-->
  </div>
</template>

<script>
export default {
  data() {
    return {
      user: {
        firstname: "",
        lastname: "",
        email: "",
        password: "",
        c_password: "",
        phone: "",
        //role: 0,
        hear_about_us: "Facebook",
        referral_code: "",
      },
      referralFromLink: false,
      processing: false,
      errorflag: "",
      successflag: "",
    };
  },

  mounted() {
    this.applyReferralFromLink();
  },

  watch: {
    "$route.query": {
      handler() {
        this.applyReferralFromLink();
      },
      deep: true,
    },
  },

  methods: {
    applyReferralFromLink() {
      const browserQuery = new URLSearchParams(window.location.search);
      let referral =
        this.$route.query.ref ||
        this.$route.query.referral_code ||
        browserQuery.get("ref") ||
        browserQuery.get("referral_code") ||
        "";

      if (Array.isArray(referral)) {
        referral = referral[0] || "";
      }

      referral = String(referral).trim();
      if (referral !== "") {
        this.user.referral_code = referral;
        this.referralFromLink = true;
      }
    },

    register() {
      this.processing = true;
      this.axios
        .post(`/api/register`, this.user)
        .then(({ data }) => {
          this.processing = false;
          const registeredEmail = this.user.email;

          this.successflag =
            "Registration successful. Verify your email to qualify for referral rewards.";
          this.$toasted.show(
            "Registration successful. Verify your email to qualify for referral rewards."
          );

          window.sessionStorage.setItem(
            "swiftlink_pending_verification_email",
            registeredEmail
          );

          this.user.firstname = "";
          this.user.lastname = "";
          this.user.email = "";
          this.user.phone = "";
          this.user.password = "";
          this.user.c_password = "";
          this.user.referral_code = "";

          this.$router.push({ name: "VerifyEmailNotice" });
        })
        .catch((error) => {
          this.processing = false;
          const message =
            error.response && error.response.data
              ? error.response.data.message
              : "Registration could not be completed. Please try again.";
          this.errorflag = message;
          this.$toasted.show(message);
        });
    },
  },
};
</script>
