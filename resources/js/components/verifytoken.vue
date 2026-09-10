<template>
  <!-- Login Content -->
  <div>
    <!--Sign In Start -->
    <section class="sign-in">
      <div class="container">
        <div class="row">
          <div class="col-xl-6 col-lg-6 mx-auto">
            <div class="sign-in__single swift-auth-card">
              <span class="swift-auth-eyebrow">SECURITY CHECK</span>
              <h3 class="sign-in__title">Enter verification code</h3>
              <p class="swift-auth-subtitle">Use the code sent to your registered email address.</p>
              <form
                method="POST"
                action="javascript:void(0)"
                class="sign-in__form"
              >
                <div v-if="successflag != ''" class="text-success">
                  {{ successflag }}
                </div>
                <div class="sign-in__form-input-box">
                  <label for="verification-code">Verification code</label>
                  <input
                    id="verification-code"
                    type="text"
                    class="form-control form-control-lg"
                    v-model="user.token"
                    required
                    autofocus
                    placeholder="Enter Code"
                  />
                </div>

                <div class="sign-in__form-btn-box">
                  <div class="mt-3">
                    <input
                      type="submit"
                      :disabled="processing"
                      @click="submitQuery()"
                      class="thm-btn sign-in__form-btn"
                      value="VERIFY CODE"
                    />
                  </div>
                  <div v-if="errorflag != null" class="text-danger">
                    {{ errorflag }}
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
        token: "",
      },
      processing: false,
      errorflag: "",
      successflag: "",
    };
  },

  methods: {
    submitQuery() {
      this.processing = true;

      if (this.user.token == "") {
        this.processing = false;
        this.errorflag = "Token cannot be empty";
        return;
      }

      this.axios
        .post(`/api/auth/token/verify`, this.user)
        .then(({ data }) => {
          this.processing = false;
          this.successflag = data.data.message;
          window.localStorage.setItem("code", this.user.token);
          this.$toasted.show(data.data.message);

          this.$router.push("/reset");
        })
        .catch((error) => {
          this.processing = false;
          if (error.response.status != 200) {
            this.errorflag = error.response.data.message;
          }
          this.$toasted.show(error.response.data.message);
          //console.log(error);
        });
    },
  },
};
</script>
