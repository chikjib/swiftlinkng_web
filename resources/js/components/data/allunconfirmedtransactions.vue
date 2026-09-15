<template>
  <div v-if="isloading">
    <vcl-table :row="5" :column="8"></vcl-table>
  </div>

  <div v-else class="col-xl-12 col-lg-12">
      <h4 class="card-title">Unconfirmed Transactions</h4>
      <div class="row">
        <div class="col-md-9 mb-2">
          <form @submit.prevent="getResults" class="form-inline">
            <div class="col-md-3">
              <select class="form-control" v-model="field">
                <option value="">Search By</option>
                <option value="phone">phone</option>

                <option value="description">Description</option>
                <option value="ref">ref</option>
                <option value="iuc">iuc</option>
                <option value="meter">meter</option>
                <option value="firstname">firstname</option>
                <option value="email">email</option>
              </select>
            </div>
            <div class="col-md-5">
              <input
                type="text"
                class="form-control"
                v-model="keyword"
                placeholder="Enter Keyword"
              />
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
      <br />
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
      <div class="swift-transaction-actions mb-2">
        <button @click="updateStatus('confirm')" :disabled="selectedTransactions.length === 0 || processing" class="btn btn-success">{{ processing ? "Processing..." : "Confirm Selected" }}</button>
        <button @click="updateStatus('reverse')" :disabled="selectedTransactions.length === 0 || processing" class="btn btn-danger">{{ processing ? "Processing..." : "Reverse Selected"}}</button>
      </div>
      <div class="table-responsive swift-admin-scroll-table-wrap">
        <table class="table table-bordered swift-admin-scroll-table swift-compact-table swift-admin-transactions-table swift-unconfirmed-transactions-table" id="datatable">
          <thead class="thead-light">
            <tr>
              <th class="swift-col-select"><input type="checkbox" @change="toggleSelectAll" v-model="selectAll"></th>
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
                <td class="swift-col-select"><input type="checkbox" v-model="selectedTransactions" :value="transaction.id"/></td>

              <td class="swift-col-reference">
                <div class="box swift-reference-cell">
                  {{ transaction.ref }}
                </div>
              </td>
              <td v-if="transaction.user != null" class="swift-col-user">
                {{
                  (transaction.user.firstname || "") +
                  " " +
                  (transaction.user.email || "") +
                  " " +
                  (transaction.user.phone || "")
                }}
              </td>
              <td v-else class="swift-col-user">
                User not available in the users table
              </td>
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
                    class="btn btn-info"
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
                  v-else-if="transaction.status == '4'"
                  >Failed</label
                >

                <label
                  class="badge badge-danger"
                  v-else-if="transaction.status == '3'"
                  >cancelled</label
                >

                <label class="badge badge-warning" v-else>Pending</label>
              </td>
              <td class="swift-col-created-at">{{ transaction.created_at }}</td>

              <td class="swift-col-action">
                <div class="btn-group" role="group">
                  <a
                    class="btn btn-danger"
                    href="#"
                    @click.stop="confirmTransaction(transaction.id, 'confirm')"
                    >Confirm</a
                  >
                  <a
                    class="btn btn-warning"
                    href="#"
                    @click.stop="
                      confirmTransaction(transaction.id, 'cancelled')
                    "
                    >Cancel</a
                  >

                  <a
                    class="btn btn-info"
                    href="#"
                    @click.stop="confirmTransaction(transaction.id, 'reverse')"
                    >Reverse</a
                  >
                  <a
                  v-if="transaction.status == 0 && transaction.category && transaction.category.id == 4"
                  class="btn btn-danger"
                  href="#"
                  @click.stop="
                    requeryTransaction(transaction.id)
                  "
                  >Requery</a
                >
              <a
                  v-if="transaction.status == 0 && transaction.category && transaction.category.id == 3"
                  class="btn btn-danger"
                  href="#"
                  @click.stop="
                    requeryTransaction(transaction.id)
                  "
                  >Requery</a
                >
                <a
                  v-if="transaction.status == 0 && transaction.category && transaction.category.id == 1"
                  class="btn btn-info"
                  href="#"
                  @click.stop="
                    reprocessTransaction(transaction.id)
                  "
                  >Reprocess</a
                >
                </div>
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
import axios from 'axios';
// import "bootstrap/dist/css/bootstrap.min.css";
// import "datatables.net-dt/js/dataTables.dataTables";
// import "datatables.net-dt/css/jquery.dataTables.min.css";
// import $ from "jquery";

const token = window.localStorage.getItem("token");

export default {
  props: ["limit"],
  components: { VclTable },

  data() {
    return {
      transactions: { data: [] },
      keyword: "",
      field: "",
      searching: false,
      isloading: false,
      errorflag: "",
      form: {},
      selectedTransactions: [],
      selectAll: false,
      processing: false,

      showMoreDes: false,
      showMorePhone: false,
    };
  },

  mounted() {
    this.list();
    if(localStorage.keyword) this.keyword = localStorage.keyword;
    if(localStorage.keyword) this.field = localStorage.field;
  },

  watch: {
    // keyword(after, before) {
    //   this.getResults();
    // },
    keyword(newKeyword){
      localStorage.keyword = newKeyword;
    },
    field(newField){
      localStorage.field = newField;
    }
  },

  methods: {
    persist(){
      localStorage.keyword = this.keyword;
      localStorage.field = this.field;

    },

    async updateStatus(new_status){

        if(this.selectedTransactions.length === 0)
        return;
        try {
            this.processing = true;
            await axios.post("/api/orders/update-status", {
                transaction_ids: this.selectedTransactions,
                status: new_status,
            })
            .then((response) => {
            this.processing = false;

            this.$toasted.show(response.data.message);
            window.location.reload();
          });

            this.selectedTransactions = [];
            this.selectAll = false;

        } catch(error){
            console.error("Error updating status: ", error)
        }
    },
    toggleSelectAll(event){
        this.selectedTransactions = event.target.checked ? this.transactions.data.map((t) => t.id) : [];
    },

    confirmTransaction: function (id, value) {
      this.loading = true;
    //   console.log(value);
      this.form.value = value;
      this.form.id = id;
      if (confirm("Do you really want to " + value + "?")) {
        axios
          .put(`/api/update/order/${id}`, this.form, {
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

    requeryTransaction: function (id) {
      this.loading = true;

        axios
          .get(`/api/requery/a-order/${id}`, {
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

    },

    reprocessTransaction: function (id) {
      this.loading = true;

        axios
          .get(`/api/reprocess/data/${id}`, {
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

    },

    resetList(page){
      if (typeof page === "undefined") {
        page = 1;
      }

      var status = 0;
      localStorage.removeItem("keyword");
      localStorage.removeItem("field");
      axios
        .get(`/api/orders?page=${page}&status=${status}`, {
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
    list(page) {
      if (typeof page === "undefined") {
        page = 1;
      }

      var status = 0;
      var section = 1;

      this.isloading = true;
      if(localStorage.keyword != null && localStorage.field != null){
        this.keyword = localStorage.keyword;
        this.field = localStorage.field;

        this.reloadResults(this.keyword,this.field);


      }else{

      axios
        .get(`/api/orders?page=${page}&status=${status}&section=${section}`, {
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
        })
        .catch(({ response }) => {
          this.$toasted.show(error.response.data.data);
          this.isloading = false;
        });
      }
    },

    reloadResults(page,keyword,field) {
      if (typeof page === "undefined") {
        page = 1;
      }
      this.isloading = true;
      this.searching = true;
      // this.isloading = true;
      axios
        .get(
          `/api/orders?search_status=${this.keyword}&field=${this.field}&page=${page}&status=0`,
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

    // requeryTransaction: function (id) {
    //   this.loading = true;
    //
    //     axios
    //       .get(`/api/requery/order/${id}`, {
    //         headers: {
    //           Authorization: `Bearer ${token}`,
    //           "Content-Type": "application/json",
    //         },
    //       })
    //       .then((response) => {
    //         this.loading = false;
    //
    //         this.$toasted.show(response.data.message);
    //         window.location.reload();
    //       })
    //       .catch((error) => {
    //         this.loading = false;
    //         this.errorflag = error.response.data.message;
    //         this.$toasted.show(error.response.data.message);
    //       });
    //
    // },


    getResults(page) {
      if (typeof page === "undefined") {
        page = 1;
      }
      this.isloading = true;
      this.searching = true;
      // this.isloading = true;
      axios
        .get(
          `/api/orders?search_status=${this.keyword}&field=${this.field}&page=${page}&status=0`,
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
  },
};
</script>

<style scoped>
.pagination {
  margin-bottom: 0;
  margin-top: 5px;
}
.box {
  overflow-wrap: anywhere;
  word-break: break-word;
}

.swift-transaction-actions {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}
</style>
