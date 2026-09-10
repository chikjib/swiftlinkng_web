<template>
  <div class="col-xl-8 col-lg-7">
    <div class="card mb-4">
      <div
        class="card-header py-3 d-flex flex-row align-items-center justify-content-between"
      >
        <div class="col-md-12">
          <div
            v-if="successflag != ''"
            class="alert alert-success alert-dismissible"
            role="alert"
          >
            <button
              type="button"
              class="close"
              data-dismiss="alert"
              aria-label="Close"
            >
              <span aria-hidden="true">&times;</span>
            </button>
            {{ successflag }}
          </div>

          <div
            v-if="success_message != ''"
            class="alert alert-success alert-dismissible"
            role="alert"
          >
            <button
              type="button"
              class="close"
              data-dismiss="alert"
              aria-label="Close"
            >
              <span aria-hidden="true">&times;</span>
            </button>
            {{ success_message }}
          </div>

          <div
            v-if="errorflag != ''"
            class="alert alert-danger alert-dismissible"
            role="alert"
          >
            <button
              type="button"
              class="close"
              data-dismiss="alert"
              aria-label="Close"
            >
              <span aria-hidden="true">&times;</span>
            </button>
            {{ errorflag }}
          </div>
          <div
            v-if="error_message != ''"
            class="alert alert-danger alert-dismissible"
            role="alert"
          >
            <button
              type="button"
              class="close"
              data-dismiss="alert"
              aria-label="Close"
            >
              <span aria-hidden="true">&times;</span>
            </button>
            {{ error_message }}
          </div>

          <form @submit.prevent="buyairtime" class="forms-sample">
            <div class="row">
              <img
                v-if="networkImage != ''"
                :src="'/template/images/services/' + networkImage"
              />
            </div>

            <div v-if="description != ''" class="col-md-12">
              <p v-html="description"></p>
            </div>

            <div class="row">
              <div class="col-md-6">
                <div class="form-group">
                  <label for="category">Select Provider</label>
                  <select
                    name="subcategory_id"
                    v-model="subcategory_id"
                    v-on:change="showPlans"
                    form-control-sm
                    class="form-control"
                  >
                    <option>Select Provider</option>
                    <option
                      v-for="subcategories in subcategories.data"
                      :key="subcategories.id"
                      v-bind:value="subcategories.id"
                    >
                      {{ subcategories.title }}
                    </option>
                  </select>
                </div>
              </div>

              <div class="col-md-6" v-if="subcategory_id === 17">
                <div class="form-group">
                  <label for="phone"
                    >Phone Number you are transferring from</label
                  >
                  <input
                    type="text"
                    class="form-control"
                    v-model="form.tphone"
                    required
                    id="phone"
                    placeholder="phone"
                  />
                </div>
              </div>
              <div class="col-md-6" v-else>
                <div class="form-group">
                  <label for="phone">Phone Number you transferred from</label>
                  <input
                    type="text"
                    class="form-control"
                    v-model="form.tphone"
                    required
                    id="phone"
                    placeholder="phone"
                  />
                </div>
              </div>

              <div class="col-md-6" v-if="subcategory_id === 17">
                <div class="form-group">
                  <label for="amount">Amount you want to transfer</label>
                  <input
                    type="text"
                    class="form-control"
                    required
                    v-model="tamount"
                  />
                </div>
              </div>
              <div class="col-md-6" v-else>
                <div class="form-group">
                  <label for="amount">Amount Transferred</label>
                  <input
                    type="text"
                    class="form-control"
                    required
                    v-model="tamount"
                  />
                </div>
              </div>

              <input type="hidden" v-model="form.tamount" />

              <div class="col-md-6">
                <div class="form-group">
                  <label for="amount">Amount To Receive</label>
                  <input
                    type="text"
                    class="form-control"
                    readonly
                    v-model="form.amount"
                  />
                </div>
              </div>

              <div class="col-md-6">
                <div class="form-group">
                  <label for="amount">Our Percentage</label>
                  <input
                    type="number"
                    class="form-control"
                    readonly
                    v-model="form.percentage"
                  />
                </div>
              </div>
              <div class="col-md-6" v-if="subcategory_id === 17">
                <div class="form-group position-relative">
                  <label for="share-pin">Your Mtn Airtime Share Pin</label>
                  <input
                    :type="showPin ? 'number' : 'password'"
                    id="password"
                    placeholder="Enter your Mtn Airtime Share Pin"
                    v-model="form.pin"
                    class="form-control"
                  />
                  <span
                    class="position-absolute"
                    @click="showPin = !showPin"
                    style="top: 42px; right: 15px; cursor: pointer"
                  >
                    <i :class="showPin ? 'fa fa-eye-slash' : 'fa fa-eye'"></i>
                  </span>
                </div>
              </div>
            </div>
            <button type="submit" class="btn btn-danger me-2">
              {{ loading ? "Processing..." : "Proceed" }}
            </button>
          </form>
        </div>
      </div>
    </div>
  </div>
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
      tamount: 0,
      discount: 0,
      loading: false,
      networkImage: "",
      description: "",
      successflag: "",
      errorflag: "",
      showPin: false,
    };
  },

  mounted() {
    this.loadplan();
  },
  computed: {
    success_message() {
      if (this.$route.params.status === "0000") {
        return this.$route.params.message || "";
      } else {
        return "";
      }
    },
    error_message() {
      if (this.$route.params.status === "404") {
        return this.$route.params.message || "";
      } else {
        return "";
      }
    },
  },

  watch: {
    tamount(after, before) {
      this.showActualAmount();
    },
  },

  methods: {
    toggleShow() {
      const input = document.getElementById("password");
      const icon = document.getElementById("toggleIcon");

      if (input.type === "password") {
        input.type = "text";
        icon.classList.remove("fa-eye");
        icon.classList.add("fa-eye-slash");
      } else {
        input.type = "password";
        icon.classList.remove("fa-eye-slash");
        icon.classList.add("fa-eye");
      }
    },
    buyairtime() {
      this.form.subcategory_id = this.subcategory_id;
      //   console.log(this.form);
      this.loading = true;

      if (this.subcategory_id === 17) {
        axios
          .post(`/api/initiate-a2cash`, this.form, {
            headers: {
              Authorization: `Bearer ${token}`,
              "Content-Type": "application/json",
            },
          })
          .then((response) => {
            this.loading = false;
            //   console.log(response.data);
            this.successflag = response.data.message;
            // this.$toasted.show(response.data.message);
            //  console.log(this.form);
            if (response.data.success) {
              this.$router.push({
                name: "verifyotpmtn",
                params: { postData: this.form },
              });
            }
          })
          .catch((error) => {
            // console.log(error);
            this.loading = false;
            this.errorflag = error.response.data.message;
            this.$toasted.show(error.response.data.message);
          });
      } else {
        this.loading = true;
        axios
          .post(`/api/purchase/a2cash`, this.form, {
            headers: {
              Authorization: `Bearer ${token}`,
              "Content-Type": "application/json",
            },
          })
          .then((response) => {
            this.loading = false;
            //console.log(response.data);
            this.successflag = response.data.message;
            this.$toasted.show(response.data.message);
          })
          .catch((error) => {
            // console.log(error);
            this.loading = false;
            this.errorflag = error.response.data.message;
            this.$toasted.show(error.response.data.message);
          });
      }
    },

    loadplan() {
      axios
        .get(`/api/subcategory?category_id=5`, {
          headers: {
            Authorization: `Bearer ${token}`,
            "Content-Type": "application/json",
          },
        })
        .then((response) => {
          //   console.log(response.data);
          this.subcategories = response.data;
        });
    },

    showPlans() {
      //   this.loading = true;
      this.phonenumber = null;
      this.amount = null;
      var c = this.subcategories.data.filter(
        (sub) => sub.id === this.subcategory_id
      );
      this.networkImage = c[0].subcat_image;
      this.description = c[0].description;

      var content = JSON.parse(c[0].products);

      this.form.percentage = this.getUserLevel(content);
      // axios
      //   .get(`/api/subcategory?subcategory_id=${this.subcategory_id}`, {
      //     headers: {
      //       Authorization: `Bearer ${token}`,
      //       "Content-Type": "application/json",
      //     },
      //   })
      //   .then((response) => {
      //     console.log(response.data);
      //     this.loading = false;
      //     this.plans = response.data;
      //   });
    },

    showActualAmount() {
      this.form.tamount = this.tamount;

      this.form.amount =
        this.tamount - this.tamount * (this.form.percentage / 100);
    },

    showAmount() {
      this.form.amount = "";

      this.form.amount =
        this.form.tamount - this.form.tamount * (this.form.percentage / 100);
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
  width: 9vw;
  height: 9vw;
  padding: 5px;
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
#code {
  font-size: 8pt;
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

.show-pin {
  position: absolute;
  top: 0;
  bottom: 0;
  align-self: center;
  padding-top: 15px;
}
</style>
