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
          <!-- New User Card Example -->
          <div class="col-xl-10 col-md-10 mb-4 mx-auto">
            <div class="row no-gutters align-items-center">
              <div class="col mr-2">
                <div class="text-xs font-weight-bold text-uppercase mb-1">
                  User Level
                </div>
                <div class="h5 mb-0 mr-3 font-weight-bold text-gray-800">
                  {{ translateLevel(home.userlevel) }}
                </div>
                <div class="mt-2 mb-0 text-muted text-xs"></div>
              </div>
            </div>

            <form @submit.prevent="upgradeLevel" class="forms-sample">
              <div class="modal-body">
                <div class="form-group">
                  <select
                    v-model="form.levelName"
                    v-on:change="showAmount"
                    class="form-control"
                  >
                    <option>Select Level</option>
                    <option
                      v-for="level in levelPackage"
                      v-bind:value="level.title"
                      :key="level.title"
                    >
                      {{ level.title + " &#8358;" + level.amount }}
                    </option>
                  </select>
                  <div v-if="form.benefit != ''" class="form-group">
                    <div class="text-info">{{ form.benefit }}</div>
                  </div>

                  <input type="hidden" v-model="form.levelamount" />
                </div>
              </div>
              <div class="row">
                <button type="submit" class="btn btn-danger">Proceed</button>
              </div>
            </form>
          </div>
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
      home: {},
      category_id: null,
      subcategory_id: null,
      plan_id: null,
      phonenumber: null,
      description2: "",
      amount: 0,
      discount: 0,
      loading: false,
      successflag: "",
      errorflag: "",
    };
  },

  mounted() {
    this.list();
  },

  watch: {
    amount(after, before) {
      this.showActualAmount();
    },
  },

  methods: {
    list() {
      var url = `/api/load-home`;
      this.isloading = true;

      axios
        .get(url, {
          headers: {
            Authorization: `Bearer ${token}`,
            "Content-Type": "application/json",
          },
        })
        .then(({ data }) => {
          this.home = data.data;
          this.levelPackage = JSON.parse(data.data.levelPackage);
          this.isloading = false;
        })
        .catch(({ response }) => {
          this.$toasted.show(error.response.data.data);
          this.isloading = false;
        });
    },

    translateLevel(userlevel) {
      if (userlevel == 0) {
        return "Normal";
      } else if (userlevel == 1) {
        return "Agent";
      } else if (userlevel == 2) {
        return "Whatsapp";
      }
      if (userlevel == 3) {
        return "API";
      }
    },

    upgradeLevel() {
      this.form.user_id = this.user.id;
      // this.form.userlevel = this.user.userlevel;

      // console.log(this.form.userlevel);

      axios
        .post(`/api/user/update/level`, this.form, {
          headers: {
            Authorization: `Bearer ${token}`,
            "Content-Type": "application/json",
          },
        })
        .then((response) => {
          console.log(response.data);
          // this.$router.push({ name: "users" });

          this.successflag =
            response.data.message + ", You need to logout and Login to apply";
          this.$toasted.show(response.data.message);
          this.logout();

          // window.location.href = "/dashboard";
        })
        .catch((error) => {
          console.log(error.response.data.message);
          this.$toasted.show(error.response.data.message);
        });
    },

    buyairtime() {
      axios
        .post(`/api/purchase/airtime`, this.form, {
          headers: {
            Authorization: `Bearer ${token}`,
            "Content-Type": "application/json",
          },
        })
        .then((response) => {
          //console.log(response.data);
          this.successflag = response.data.message;
          this.$toasted.show(response.data.message);
        })
        .catch((error) => {
          // console.log(error);
          this.errorflag = error.response.data.message;
          this.$toasted.show(error.response.data.message);
        });
    },

    loadplan() {
      axios
        .get(`/api/subcategory?category_id=2`, {
          headers: {
            Authorization: `Bearer ${token}`,
            "Content-Type": "application/json",
          },
        })
        .then((response) => {
          // console.log(response.data);
          this.subcategories = response.data;
        });
    },

    showPlans() {
      this.loading = true;
      this.phonenumber = "";
      this.amount = 0;
      var c = this.subcategories.data.filter(
        (sub) => sub.id === this.form.subcategory_id
      );

      var content = JSON.parse(c[0].products);

      this.form.discount = this.getUserLevel(content) + "% Discount";
      this.discount = this.getUserLevel(content);

      this.description2 = c[0].description2;
      this.loading = false;
    },

    showAmount() {
      var c = this.levelPackage.filter(
        (sub) => sub.title === this.form.levelName
      );
      console.log(c[0].amount);
      this.form.levelamount = c[0].amount;
      this.form.benefit = c[0].benefit;

      function indexWhere(array, conditionFn) {
        const item = array.find(conditionFn);
        return array.indexOf(item);
      }

      const index = indexWhere(
        this.levelPackage,
        (item) => item.title === this.form.levelName
      );
      this.form.userlevel = index;
    },

    showActualAmount() {
      this.form.amount = this.amount;

      this.form.discount = this.amount - this.amount * (this.discount / 100);
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
  width: 10vw;
  height: 10vw;
  padding: 10px;
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
</style>
