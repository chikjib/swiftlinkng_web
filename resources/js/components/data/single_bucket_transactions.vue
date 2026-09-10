<template>
    <div v-if="isloading">
      <vcl-table :row="5" :column="8"></vcl-table>
    </div>

    <div v-else class="col-xl-12 col-lg-12">
      <h4 class="card-title">
        {{ limit == 5 ? "Recent Transaction" : "Transactions" }}
      </h4>
      <div class="row">
        <div class="col-md-6">
          <a href="/dashboard/buy-bulk-data" class="nav-link">
            <i class="fas fa-fw fa-wifi"></i>

            <span class="menu-title">Buy Bulk Data</span>
          </a>
        </div>
        <div class="col-md-9 mb-2">
          <form @submit.prevent="getResults" class="form-inline">
            <div class="col-md-2">
              <select class="form-control" v-model="field">
                <option>Search By</option>
                <option value="phone">phone</option>

                <option value="description">Description</option>
                <option value="ref">ref</option>
                <option value="firstname">firstname</option>
                <option value="email">email</option>
              </select>
            </div>
            <div class="col-md-3">
              <input
                type="text"
                class="form-control"
                v-model="keyword"
                placeholder="Enter Keyword"
              />
            </div>

            <div class="col-md-3">
                <select class="form-control" v-model="status">
                  <option>Select Status</option>
                  <option value="1">Confirmed</option>
                  <option value="0">Pending</option>
                  <option value="2">Reversed</option>
                  <option value="3">Ignored</option>
                  <option value="4">Failed</option>


                </select>
              </div>
            <div class="col-md-4">
              <button type="submit" class="btn btn-danger">Search</button>

              <button type="button" @click="list" class="btn btn-warning">
                Reset
              </button>
            </div>
          </form>
        </div>

      </div>
      <div class="table-responsive">
        <table class="table table-bordered">
          <thead class="thead-light">
            <tr>
              <th>ID</th>
              <th>Type</th>
              <th>Description</th>
              <th>Bucket</th>
              <th>Amount (GB)</th>
              <th>Phone</th>
              <th>Bucket Bal (GB)</th>
              <th>Prev Bucket Bal (GB)</th>
              <th>Response</th>
              <th>Status</th>
              <th>Created At</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(transaction, index) in transactions.data" :key="index">
              <td>
                <div style="width: 60px" class="box">
                  {{ transaction.ref }}
                </div>
              </td>
              <td>{{ transaction.category.title }}</td>

              <td>
                <div v-if="transaction.description.length < 40">
                  {{ transaction.description }}
                </div>
                <div v-else>
                  <div v-if="showMoreDes == false">
                    {{ transaction.description.substring(0, 40) + ".." }}
                  </div>
                  <div v-else>
                    {{ transaction.description }}
                  </div>
                  <button
                    @click="showMoreDes = !showMoreDes"
                    class="btn btn-danger"
                  >
                    <i v-if="showMoreDes == false" class="fas fa-eye"></i>
                    <i v-else class="fas fa-eye-slash"></i>
                  </button>
                </div>
              </td>
              <td>{{ transaction.bucket.title }} Bucket</td>
              <td>{{ formatNumber(transaction.subtotal) }}</td>
              <td>
                <div v-if="transaction.phone == null">
                  {{ transaction.phone }}
                </div>
                <div v-else-if="transaction.phone.length < 13">
                  {{ transaction.phone }}
                </div>
                <div v-else>
                  <div v-if="showMorePhone == false">
                    {{ transaction.phone.substring(0, 13) + ".." }}
                  </div>
                  <div v-else>
                    {{ transaction.phone }}
                  </div>
                  <button
                    @click="showMorePhone = !showMorePhone"
                    class="btn btn-danger"
                  >
                    <i v-if="showMorePhone == false" class="fas fa-eye"></i>
                    <i v-else class="fas fa-eye-slash"></i>
                  </button>
                </div>
              </td>

              <td v-if="transaction.bucket_bal !== 0"> {{ formatNumber(transaction.bucket_bal) }}</td>
              <td v-if="transaction.prev_bucket_bal !== 0"> {{ formatNumber(transaction.prev_bucket_bal) }}</td>

              <td>
                <div v-if="transaction.response == null">
                  {{ transaction.response }}
                </div>
                <div v-else-if="transaction.response.length < 40">
                  {{ transaction.response }}
                </div>
                <div v-else>
                  <div v-if="showMoreDes == false">
                    {{ transaction.response.substring(0, 40) + ".." }}
                  </div>
                  <div v-else>
                    {{ transaction.response }}
                  </div>
                  <button
                    @click="showMoreDes = !showMoreDes"
                    class="btn btn-danger"
                  >
                    <i v-if="showMoreDes == false" class="fas fa-eye"></i>
                    <i v-else class="fas fa-eye-slash"></i>
                  </button>
                </div>
              </td>

              <td>
                <label
                  class="badge badge-success"
                  v-if="transaction.status == '1'"
                  >Confirmed</label
                >

                <label
                  class="badge badge-info"
                  v-else-if="transaction.status == '2'"
                  >Reversed</label
                >

                <label
                  class="badge badge-danger"
                  v-else-if="transaction.status == '3'"
                  >Cancelled</label
                >
                <label
                  class="badge badge-danger"
                  v-else-if="transaction.status == '4'"
                  >Failed</label
                >

                <label class="badge badge-warning" v-else>Pending</label>
              </td>
              <td>{{ transaction.created_at }}</td>
              <td>
                <a
                  v-if="transaction.status == '1'"
                  :href="`/dashboard/bucket-receipt/${transaction.id}`"
                  class="btn btn-danger"
                  >Receipt
                </a>

              </td>
            </tr>

          </tbody>
        </table>
      </div>

      <pagination
        v-if="searching == false"
        align="center"
        :limit="5"
        :data="transactions"
        @pagination-change-page="list"
      ></pagination>

      <pagination
        v-if="searching == true"
        align="center"
        :limit="5"
        :data="transactions"
        @pagination-change-page="getResults"
      ></pagination>
    </div>
  </template>

  <script>
  import { VclTable } from "vue-content-loading";
  const token = window.localStorage.getItem("token");

  export default {
    props: ["limit"],
    components: { VclTable},

    data() {
      return {
        transactions: {},
        keyword: null,
        field: "Search By",
        status: "Select Status",
        searching: false,
        isloading: false,
        form: {},
        showMoreDes: false,
        showMorePhone: false,
      };
    },

    mounted() {
      this.list();
    },

    // watch: {
    //   keyword(after, before) {
    //     this.getResults();
    //   },
    // },
    methods: {
      list(page) {
        if (typeof page === "undefined") {
          page = 1;
        }

        // var limited = this.limit == null ? 0 : this.limit;
        this.isloading = true;
        axios
          .get(`/api/bucket-orders/bucket/${this.$route.params.bucket_id}`, {
            headers: {
              Authorization: `Bearer ${token}`,
              "Content-Type": "application/json",
            },
          })
          .then(({ data }) => {
            console.log(data)
            this.transactions = data;
            this.isloading = false;
          })
          .catch(({ response }) => {
            this.$toasted.show(error.response.data.data);
            this.isloading = false;
          });
      },


      getResults(page) {
        if (typeof page === "undefined") {
          page = 1;
        }
        this.isloading = true;
        this.searching = true;
        axios
          .get(
            `/api/bucket-orders?search_user=${this.keyword}&field=${this.field}&status=${this.status}&user_id=${this.auth.user.id}&page=${page}`,
            {
              headers: {
                Authorization: `Bearer ${token}`,
                "Content-Type": "application/json",
              },
            }
          )
          .then((response) => {
            console.log(response.data);
            this.transactions = response.data;
            this.isloading = false;
          });
      },
    },
  };
  </script>

  <style scoped>
  .pagination {
    margin-bottom: 0;
    margin-top: 5;
  }
  .box {
    inline-size: 5px;
    overflow-wrap: break-word;
  }
  </style>
