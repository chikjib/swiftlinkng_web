<template>
    <div class="col-xl-9 col-lg-8">
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
            <form action="#" @submit.prevent="" class="forms-sample">
              <div class="bucket-section">
                   <div
                class="bucket-wallet-content"
                v-for="bucket in bucket_balances.data"
                :key="bucket.id"
              >
                  <div class="bucket-wallet-title">{{ bucket.title }}</div>
                  <div class="bucket-wallet-balance">
                    {{ bucket.bucket_balance }}<span>GB</span>
                  </div>
              </div>
              </div>

  <hr/>
              <div class="row">
                <div class="col-md-6">
                  <div class="form-group">
                    <label for="bucket">Select Data</label>
                    <select
                      name="bucket_id"
                      v-model="form.bucket_id"
                      v-on:change="showPlans"
                      class="form-control"
                    >
                      <option>Select Data</option>
                      <option
                        v-for="bucket in buckets.data"
                        :key="bucket.id"
                        v-bind:value="bucket.id"
                      >
                        {{ bucket.title }}
                      </option>
                    </select>
                  </div>
                </div>

                <div class="col-md-6">
                    <div class="form-group">
                      <label for="category">Select Plan</label>
                      <select
                        name="subcategory_id"
                        v-model="form.plan_id"
                        v-on:change="showAmount"
                        form-control-sm
                        class="form-control"
                      >
                        <option>Select Plan</option>
                        <option
                          v-for="plan in plans"
                          v-bind:value="plan.plan"
                          v-on:change="showAmount"
                          :key="plan.plan"
                        >
                          {{ plan.plan }}
                        </option>
                      </select>
                    </div>
                  </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="phone">Phone Number</label>
                            <input
                              type="number"
                              class="form-control"
                              v-model="form.phonenumber"
                              id="phone"
                              required
                              placeholder="phone"
                            />
                          </div>
                    </div>


                    <div class="col-md-12">
                        <input type="checkbox" v-model="form.ported" value="yes" />

                        <label for="phone">Is Ported Number?</label>
                    </div>


                <div class="col-md-12 bucket-cost">
                  <fieldset class="form-control bucket-cost-item">
                    Total Cost: {{ formatNumber(total_cost) }} GB
                  </fieldset>
                </div>

                <div class="col-md-12">
                  <button
                    :disabled="loading"
                    type="submit"
                    @click="bucket_purchase"
                    class="btn btn-danger me-2"
                  >
                    {{ loading ? "Processing.." : "Process" }}
                  </button>
                </div>
              </div>
            </form>
            <a href="/dashboard/bulk-data-transactions" class="nav-link">
              <i class="fas fa-fw fa-th"></i>

              <span class="menu-title">View Transactions</span>
            </a>
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
        buckets: {},
        bucket_balances: {},
        plans: {},
        total_cost: 0,

        loading: false,
        form: {
          price: 0,
          bucket_id: "",
          data_size: 0,
          plan_id: "",
          phonenumber: ""
        },
        successflag: "",
        errorflag: "",
      };
    },

    mounted() {
      this.load_bucket();
      this.load_bucket_balance();
    },

    methods: {
      bucket_purchase() {
        this.form.data_cost = this.total_cost;
        console.log(this.form);
        this.loading = true;
        axios
          .post(`/api/purchase/bucket/data`, this.form, {
            headers: {
              Authorization: `Bearer ${token}`,
              "Content-Type": "application/json",
            },
          })
          .then((response) => {
            console.log(response.data);
            this.form.amount == "";
            this.successflag = response.data.message;
            this.loading = false;
            this.$toasted.show(response.data.message);
          })
          .catch((error) => {
            this.loading = false;
            this.errorflag = error.response.data.message;
            this.$toasted.show(error.response.data.message);
          });
      },

      load_bucket() {
        axios
          .get(`/api/buckets`, {
            headers: {
              Authorization: `Bearer ${token}`,
              "Content-Type": "application/json",
            },
          })
          .then((response) => {
            console.log(response.data);
            this.buckets = response.data;
          });
      },
      load_bucket_balance() {
        axios
          .get(`/api/buckets/balance`, {
            headers: {
              Authorization: `Bearer ${token}`,
              "Content-Type": "application/json",
            },
          })
          .then((response) => {
            console.log(response.data);
            this.bucket_balances = response.data;
          });
      },

      showPlans() {

      this.loading = true;
      this.phonenumber = null;
    //   this.amount = null;
      var c = this.buckets.data.filter(
        (sub) => sub.id === this.form.bucket_id
      );

    //   console.log(c[0].products);

    //   this.networkImage = c[0].subcat_image;
      var content = JSON.parse(c[0].products);


      this.description2 = c[0].description2;

      this.loading = false;

      this.plans = content;
    },

      showAmount() {
        var c = this.plans.filter(
        (sub) => sub.plan === this.form.plan_id
      );
      this.form.data_size = c[0].data_size;
    //   console.log(c)
        this.total_cost = this.form.data_size / 1000;
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

  .bucket-cost {
    margin-bottom: 20px;
    margin-top: 20px;
    text-align: center;
  }

  .bucket-cost-item {
    padding: 10px;
    font-size: 15px;
    font-weight: bold;
  }

  .bucket-section {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    justify-content: space-between;
  }
  .bucket-wallet-content {
    flex: 1 1 calc(25% - 10px);
    box-sizing: border-box;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    border: 1px solid red;
    border-radius: 6px;
    padding: 5px;
    margin-top: 10px;
    background: #f0eeee;
    font-size: 14px;
  }
  .bucket-wallet-title {
    font-weight: bold;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    text-align: center;
    margin-bottom: 10px;
  }


  @media screen and (min-width: 768px) {
      .bucket-section {
          flex: 1 1 calc(50% - 10px);
      }
      .bucket-wallet-content {
          padding: 15px;
          font-size: 15px;
      }
  }
  </style>
