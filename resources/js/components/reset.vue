<template>
  <div>
    <!--Sign In Start -->
    <section class="sign-in">
      <div class="container">
        <div class="row">
          <div class="col-xl-6 col-lg-6 mx-auto">
            <div class="sign-in__single swift-auth-card">
              <span class="swift-auth-eyebrow">ALMOST DONE</span>
              <h3 class="sign-in__title">Choose a new password</h3>
              <p class="swift-auth-subtitle">Use a strong password you haven’t used before.</p>
              <form
                method="POST"
                action="javascript:void(0)"
                class="sign-in__form"
              >
                <div class="sign-in__form-input-box">
                  <label for="password">New password</label>
                  <input
                    type="password"
                    id="password"
                    class="form-control form-control-lg"
                    v-model="user.password"
                    name="password"
                    required
                    autocomplete="current-password"
                    placeholder="Password"
                  />
                </div>
                <div class="sign-in__form-input-box">
                  <label for="password_confirmation">Confirm new password</label>
                  <input
                    type="password"
                    id="password_confirmation"
                    class="form-control form-control-lg"
                    v-model="user.password_confirmation"
                    name="password_confirmation"
                    required
                    autocomplete="confirm-password"
                    placeholder="Confirm Password"
                  />
                </div>
                <div v-if="errorflag != ''" class="text-danger">
                  {{ errorflag }}
                </div>

                <div class="sign-in__form-btn-box">
                  <div class="mt-3">
                    <input
                      type="submit"
                      :disabled="processing"
                      @click="submitQuery()"
                      class="thm-btn sign-in__form-btn"
                      value="RESET PASSWORD"
                    />
                  </div>
                  <div v-if="errorflag != ''" class="text-danger">
                    {{ errorflag }}
                  </div>
                  <div v-if="successflag != ''" class="text-success">
                    {{ successflag }}
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
        password: "",
        password_confirmation: "",
        email: "",
        code: "",
      },
      processing: false,
      errorflag: "",
      successflag: "",
    };
  },

  methods: {
    submitQuery() {
      this.processing = true;
      this.user.email = window.localStorage.getItem("email");
      this.user.code = window.localStorage.getItem("code");
      this.axios
        .post(`/api/auth/password/reset`, this.user)
        .then(({ data }) => {
          this.processing = false;
          this.$toasted.show(data.data.message);

          window.localStorage.removeItem("email");
          window.localStorage.removeItem("code");
          this.$router.push("/login");
        })
        .catch((error) => {
          // this.processing = false;
          // this.$toasted.show(error.response.data.data);
          // console.log(error);
          this.processing = false;
          if (error.response.status != 200) {
            this.errorflag = error.response.data.message;
          }
          this.$toasted.show(error.response.data.message);
        });
    },
  },
};
</script>
