<template>
  <!-- Login Content -->
  <div>
    <!--Sign In Start -->
    <section class="sign-in">
      <div class="container">
        <div v-if="$route.query.result" class="sign-in__top text-success">
          {{ $route.query.result }}
        </div>
        <div class="row">
          <div class="col-xl-6 col-lg-6 mx-auto">
            <div class="sign-in__single swift-auth-card">
              <span class="swift-auth-eyebrow">ACCOUNT RECOVERY</span>
              <h3 class="sign-in__title">Reset your password</h3>
              <p class="swift-auth-subtitle">Enter your email and we’ll send a secure verification code.</p>
              <form
                method="POST"
                action="javascript:void(0)"
                class="sign-in__form"
              >
                <div v-if="errorflag != ''" class="text-danger">
                  {{ errorflag }}
                </div>
                <div v-if="successflag != ''" class="text-success">
                  {{ successflag }}
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
                  <input
                    type="submit"
                    :disabled="processing"
                    @click="submitQuery()"
                    class="thm-btn sign-in__form-btn"
                    value="SEND RESET CODE"
                  />

                  <div v-if="errorflag != null" class="text-danger">
                    {{ errorflag }}
                  </div>
                </div>
                <div v-if="errorflag != ''" class="text-danger">
                  {{ errorflag }}
                </div>

                <div class="sign-in__form-btn-box">
                  <div
                    class="my-2 d-flex justify-content-between align-items-center"
                  >
                    <a href="/login" class="font-weight-bold small"
                      >Back to sign in</a
                    >
                  </div>
                </div>
              </form>
            </div>
          </div>
        </div>
      </div>
    </section>
    <!--Sign In End-->
    <!-- page-body-wrapper ends -->
  </div>
</template>

<script>
export default {
  data() {
    return {
      user: {
        email: "",
      },
      processing: false,
      errorflag: "",
      successflag: "",
    };
  },

  methods: {
    submitQuery() {
      this.processing = true;

      if (this.user.email == "") {
        this.processing = false;
        this.errorflag = "Email cannot be empty";
        return;
      }

      this.axios
        .post(`/api/auth/password/email`, this.user)
        .then(({ data }) => {
          this.processing = false;
          this.$toasted.show(data.data.message);
          this.successflag = data.data.message;
          window.localStorage.setItem("email", this.user.email);

          this.$router.push("/verifytoken");
        })
        .catch((error) => {
          this.processing = false;
          if (error.response.status != 200) {
            this.errorflag = error.response.data.message;
          }
          this.$toasted.show(error.response.data.message);
          console.log(error);
        });
    },
  },
};
</script>
