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
              <span class="swift-auth-eyebrow">WELCOME BACK</span>
              <h3 class="sign-in__title">Sign in to Swiftlinkng</h3>
              <p class="swift-auth-subtitle">Pay bills, fund your wallet and manage everything securely.</p>
              <form
                class="sign-in__form"
                @submit.prevent="login"
              >
                <div class="sign-in__form-input-box">
                  <label for="exampleInputEmail">Email address</label>
                  <input
                    type="email"
                    class="form-control"
                    v-model="user.email"
                    required
                    id="exampleInputEmail"
                    aria-describedby="emailHelp"
                    placeholder="Email Address"
                  />
                </div>
                <div class="sign-in__form-input-box">
                  <label for="login-password">Password</label>
                  <div class="col-md-12">
                    <div class="row">
                      <div class="col-md-10 col-lg-10 col-xl-10">
                        <input
                          v-if="showPassword"
                          type="text"
                          class="form-control"
                          v-model="user.password"
                          id="login-password"
                          placeholder="Password"
                        />
                        <input
                          v-else
                          type="password"
                          class="form-control"
                          v-model="user.password"
                          id="login-password"
                          placeholder="Password"
                        />
                      </div>

                      <div class="col-md-2 col-lg-2 col-xl-2">
                        <button type="button" class="btn col-md-12" @click="toggleShow" aria-label="Show or hide password">
                          <i
                            class="fas"
                            :class="{
                              'fa-eye-slash': showPassword,
                              'fa-eye': !showPassword,
                            }"
                          ></i>
                        </button>
                      </div>
                    </div>
                  </div>
                </div>
                <label class="sign-in__form-input-box swift-auth-check" for="checkbox">
                  <input
                    type="checkbox"
                    v-model="user.remember_me"
                    id="checkbox"
                    true-value="true"
                    false-value="false"
                    aria-describedby="emailHelp"
                  />
                  <span>Remember me</span>
                </label>

                <div v-if="errorflag != ''" class="alert alert-danger swift-auth-alert">
                  {{ errorflag }}
                </div>

                <div class="sign-in__form-btn-box">
                  <button
                    type="submit"
                    :disabled="processing"
                    class="thm-btn sign-in__form-btn"
                  >
                    {{ processing ? "SIGNING IN…" : "SIGN IN" }}
                  </button>
                  <div class="sign-in__form-forgot-password">
                    <a href="/forgotpassword" class="font-weight-bold small"
                      >Forgot password?</a
                    >
                  </div>

                  <div class="sign-in__form-forgot-password">
                    <a class="font-weight-bold small" href="/register"
                      >New to Swiftlinkng? Create an account</a
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
  </div>
  <!-- Login Content -->
</template>

<script>
import Auth from "../Auth.js";

export default {
  data() {
    return {
      user: {
        email: "",
        password: "",
        remember_me: false,
      },
      processing: false,
      errorflag: "",
      showPassword: false,
    };
  },

  computed: {
    buttonLabel() {
      return this.showPassword ? "Hide" : "Show";
    },
  },

  methods: {
    toggleShow() {
      this.showPassword = !this.showPassword;
    },
    login() {
      if (this.processing) return;

      this.processing = true;
      this.errorflag = "";

      if (this.user.email == "") {
        this.processing = false;
        this.errorflag = "Email cannot be empty";
        return;
      }
      if (this.user.password == "") {
        this.processing = false;
        this.errorflag = "Password cannot be empty";
        return;
      }

      this.axios
        .post(`/api/login`, this.user)
        .then(({ data }) => {
          localStorage.removeItem('token');
          Auth.login(data.data.token, data.data); //set local storage
          this.processing = false;
          window.location.href = "/dashboard";
          // this.$router.go("/dashboard");

        })
        .catch((error) => {
          this.processing = false;
          this.errorflag = error?.response?.data?.message
            || "Unable to sign in right now. Please try again.";
        });
      // });
    },
  },
};
</script>
