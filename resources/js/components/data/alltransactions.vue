<template>
  <div v-if="isloading">
    <vcl-table :row="5" :column="8"></vcl-table>
  </div>

  <div v-else class="row">
    <div class="col-lg-12">
      <h4 class="card-title">All Transactions</h4>
      <div class="row">
        <div class="col-md-2"></div>
        <div class="col-md-9 mb-2">
          <form @submit.prevent="getResults" class="form-inline">
            <div class="col-md-2">
              <select class="form-control" v-model="field">
                <option>Search By</option>
                <option value="phone">phone</option>

                <option value="description">Description</option>
                <option value="type">Type</option>
                <option value="ref">ref</option>
                <option value="iuc">iuc</option>
                <option value="meter">meter</option>
                <option value="firstname">firstname</option>
                <option value="response">response</option>
                <option value="created_at">created at</option>
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

              <button type="button" @click="resetList" class="btn btn-warning">
                Reset
              </button>
            </div>
          </form>
        </div>
      </div>
      <div class="table-responsive swift-admin-scroll-table-wrap">
        <table class="table table-bordered swift-admin-scroll-table swift-compact-table swift-admin-transactions-table">
          <thead class="thead-light">
            <tr>
              <th>ID</th>
              <th>User</th>

              <th>Type</th>
              <th>Description</th>

              <th>Amount</th>
              <th>Phone</th>

              <th>IUC/Meter</th>
              <th></th>
              <th>Bal</th>
              <th>Prev Bal</th>
              <th>Response</th>

              <th>Status</th>
              <th>Created At</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="transaction in transactions.data" :key="transaction.id">
              <td>
                <div class="box swift-reference-cell">
                  {{ transaction.ref }}
                </div>
              </td>
              <td>
                {{
                  transaction.user.firstname +
                  " " +
                  transaction.user.email +
                  " " +
                  transaction.user.phone
                }}
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
              <td>&#8358; {{ formatNumber(transaction.subtotal) }}</td>
              <td>
                <div v-if="transaction.phone == null">
                  {{ transaction.phone }}
                </div>
                <div v-else-if="transaction.phone.length < 13">
                  {{ transaction.phone }}
                </div>
                <div v-else>
                  <div v-if="showMorePhone === false">
                    {{ transaction.phone.substring(0, 13) + ".." }}
                  </div>
                  <div v-else>
                    {{ transaction.phone }}
                  </div>
                  <button
                    @click="showMorePhone = !showMorePhone"
                    class="btn btn-danger"
                  >
                    <i v-if="showMorePhone === false" class="fas fa-eye"></i>
                    <i v-else class="fas fa-eye-slash"></i>
                  </button>
                </div>
              </td>

              <td>{{ transaction.iuc }}{{ transaction.meter }}</td>
              <td></td>
              <td>&#8358; {{ formatNumber(transaction.bal) }}</td>
              <td>&#8358; {{ formatNumber(transaction.prev_bal) }}</td>

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
                  class="btn btn-info"
                  href="#"
                  v-if="transaction.category.title === 'Airtime' || transaction.category.title === 'Data'"
                  @click.stop="confirmTransaction(transaction.id, 'reverse')"
                  >Reverse</a
                >
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
      >
      </pagination>
    </div>
  </div>
</template>

<script>
import { VclTable } from "vue-content-loading";
import axios from "axios";

const token = window.localStorage.getItem("token");

export default {
  props: ["limit"],
  components: { VclTable },

  data() {
    return {
      transactions: {},
      keyword: null,
      field: "Search By",
      status: "Select Status",
      searching: false,
      isloading: false,
      showMoreDes: false,
      showMorePhone: false,
      form: {},
    };
  },

  mounted() {
    this.list();
    if (localStorage.keyword) this.keyword = localStorage.keyword;
    if (localStorage.keyword) this.field = localStorage.field;
  },

  watch: {
    keyword(newKeyword) {
      localStorage.keyword = newKeyword;
    },
    field(newField) {
      localStorage.field = newField;
    },
  },
  methods: {
    persist() {
      localStorage.keyword = this.keyword;
      localStorage.field = this.field;
    },
    list(page) {
      if (typeof page === "undefined") {
        page = 1;
      }


      var limited = this.limit == null ? 0 : this.limit;
      this.isloading = true;

      if(localStorage.keyword != null && localStorage.field != null){
        this.keyword = localStorage.keyword;
        this.field = localStorage.field;

        this.getResults(page);


      } else {
        axios
        .get(`/api/orders?page=${page}&limit=${limited}`, {
          headers: {
            Authorization: `Bearer ${token}`,
            "Content-Type": "application/json",
          },
        })
        .then(({ data }) => {
          console.log(data);
          this.transactions = data;
          this.isloading = false;
        })
        .catch(({ response }) => {
          this.$toasted.show(error.response.data.data);
          this.isloading = false;
        });
      }


    },

    resetList(page){
      if (typeof page === "undefined") {
        page = 1;
      }

      var limited = this.limit == null ? 0 : this.limit;
      this.isloading = true;

      var status = 0;
      localStorage.removeItem("keyword");
      localStorage.removeItem("field");
      axios
        .get(`/api/orders?page=${page}&limit=${limited}`, {
          headers: {
            Authorization: `Bearer ${token}`,
            "Content-Type": "application/json",
          },
        })
        .then(({ data }) => {
          this.transactions = data;
          // $("#datatable").DataTable();
        //   console.log(data);

          this.isloading = false;
          window.location.reload();
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
          `/api/orders?search=${this.keyword}&field=${this.field}&status=${this.status}&page=${page}`,
          {
            headers: {
              Authorization: `Bearer ${token}`,
              "Content-Type": "application/json",
            },
          }
        )
        .then((response) => {
          console.log(response.data);
          this.isloading = false;

          this.transactions = response.data;
          // this.isloading = false;
        });
    },

    confirmTransaction: function (id, value) {
      this.loading = true;
    //   console.log(value);
      this.form.value = value;
      this.form.id = id;
      if (confirm("Do you really want to " + value + "?")) {
        axios
          .put(`/api/update/order/confirmed/${id}`, this.form, {
            headers: {
              Authorization: `Bearer ${token}`,
              "Content-Type": "application/json",
            },
          })
          .then((response) => {
            this.loading = false;

            this.$toasted.show(response.data.message);
            window.location.reload();
          })
          .catch((error) => {
            this.loading = false;
            this.errorflag = error.response.data.message;
            this.$toasted.show(error.response.data.message);
          });
      }
    },
  },
};
</script>

<style scoped>
.pagination {
  margin-bottom: 0;
  margin-top: 5px;
}

.box {
  inline-size: 5px;
  overflow-wrap: break-word;
}
</style>
