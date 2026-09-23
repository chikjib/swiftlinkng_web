<template>
  <div v-if="isloading">
    <vcl-table :row="5" :column="8"></vcl-table>
  </div>

  <div v-else class="col-xl-12 col-lg-12">
      <h4 class="card-title">All Transactions</h4>
      <div v-if="errorflag" class="alert alert-danger" role="alert">
        {{ errorflag }}
      </div>
      <div class="row">
        <div class="col-md-9 mb-2">
          <form @submit.prevent="getResults" class="form-inline">
            <div class="col-md-2">
              <select class="form-control" v-model="field">
                <option value="">Search By</option>
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
                <option value="">Select Status</option>
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
        <table class="table table-bordered swift-admin-scroll-table swift-compact-table swift-admin-transactions-table swift-all-transactions-table">
          <thead class="thead-light">
            <tr>
              <th class="swift-col-reference">ID</th>
              <th class="swift-col-user">User</th>

              <th class="swift-col-type">Type</th>
              <th class="swift-col-description">Description</th>

              <th class="swift-col-amount">Amount</th>
              <th class="swift-col-phone">Phone Number</th>

              <th class="swift-col-iuc-meter">IUC/Meter</th>
              <th class="swift-col-balance">Bal</th>
              <th class="swift-col-prev-balance">Prev Bal</th>
              <th class="swift-col-response">Response</th>

              <th class="swift-col-status">Status</th>
              <th class="swift-col-created-at">Created At</th>
              <th class="swift-col-action">Action</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="transaction in transactions.data" :key="transaction.id">
              <td class="swift-col-reference">
                <div class="box swift-reference-cell">
                  {{ transaction.ref }}
                </div>
              </td>
              <td v-if="transaction.user" class="swift-col-user">
                {{
                  (transaction.user.firstname || "") +
                  " " +
                  (transaction.user.email || "") +
                  " " +
                  (transaction.user.phone || "")
                }}
              </td>
              <td v-else class="swift-col-user">User not available</td>
              <td class="swift-col-type">{{ transaction.category ? transaction.category.title : "-" }}</td>
              <td class="swift-col-description">
                <div v-if="!transaction.description || transaction.description.length < 40">
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
              <td class="swift-col-amount">&#8358; {{ formatNumber(transaction.subtotal) }}</td>
              <td class="swift-col-phone">
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

              <td class="swift-col-iuc-meter">{{ transaction.iuc }}{{ transaction.meter }}</td>
              <td class="swift-col-balance">&#8358; {{ formatNumber(transaction.bal) }}</td>
              <td class="swift-col-prev-balance">&#8358; {{ formatNumber(transaction.prev_bal) }}</td>

              <td class="swift-col-response">
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

              <td class="swift-col-status">
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
              <td class="swift-col-created-at">{{ transaction.created_at }}</td>

              <td class="swift-col-action">
                <a
                  class="btn btn-info"
                  href="#"
                  v-if="transaction.category && (transaction.category.title === 'Airtime' || transaction.category.title === 'Data')"
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
      transactions: { data: [] },
      keyword: "",
      field: "",
      status: "",
      searching: false,
      isloading: false,
      showMoreDes: false,
      showMorePhone: false,
      form: {},
      errorflag: "",
    };
  },

  mounted() {
    this.keyword = localStorage.getItem("allTransactions.keyword") || "";
    this.field = localStorage.getItem("allTransactions.field") || "";
    this.status = localStorage.getItem("allTransactions.status") || "";

    if ((this.keyword && this.field) || this.status !== "") {
      this.getResults();
    } else {
      this.list();
    }
  },

  watch: {
    keyword(newKeyword) {
      this.storeFilter("allTransactions.keyword", newKeyword);
    },
    field(newField) {
      this.storeFilter("allTransactions.field", newField);
    },
    status(newStatus) {
      this.storeFilter("allTransactions.status", newStatus);
    },
  },
  methods: {
    storeFilter(key, value) {
      if (value === "" || value == null) localStorage.removeItem(key);
      else localStorage.setItem(key, value);
    },
    requestErrorMessage(error) {
      return error?.response?.data?.message || "Transactions could not be loaded. Please try again.";
    },
    list(page) {
      if (typeof page === "undefined") {
        page = 1;
      }


      var limited = this.limit == null ? 0 : this.limit;
      this.isloading = true;
      this.errorflag = "";

      axios
        .get(`/api/orders?page=${page}&limit=${limited}`, {
          headers: {
            Authorization: `Bearer ${token}`,
            "Content-Type": "application/json",
          },
        })
        .then(({ data }) => {
          this.transactions = data;
        })
        .catch((error) => {
          this.errorflag = this.requestErrorMessage(error);
          this.$toasted.show(this.errorflag);
        })
        .finally(() => {
          this.isloading = false;
        });
    },

    resetList(page){
      if (typeof page === "undefined") {
        page = 1;
      }

      this.keyword = "";
      this.field = "";
      this.status = "";
      localStorage.removeItem("keyword");
      localStorage.removeItem("field");
      localStorage.removeItem("allTransactions.keyword");
      localStorage.removeItem("allTransactions.field");
      localStorage.removeItem("allTransactions.status");
      this.searching = false;
      this.list(page);
    },

    getResults(page) {
      if (typeof page === "undefined") {
        page = 1;
      }
      if (!this.keyword && this.status === "") {
        this.searching = false;
        this.list(page);
        return;
      }

      if (this.keyword && !this.field) {
        this.errorflag = "Select a field to search by.";
        return;
      }

      this.isloading = true;
      this.searching = true;
      this.errorflag = "";

      axios
        .get(`/api/orders`, {
            params: {
              search: this.keyword,
              field: this.field,
              status: this.status,
              page,
            },
            headers: {
              Authorization: `Bearer ${token}`,
              "Content-Type": "application/json",
            },
          })
        .then((response) => {
          this.transactions = response.data;
        })
        .catch((error) => {
          this.errorflag = this.requestErrorMessage(error);
          this.$toasted.show(this.errorflag);
        })
        .finally(() => {
          this.isloading = false;
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
