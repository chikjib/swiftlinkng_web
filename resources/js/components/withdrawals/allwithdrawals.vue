<template>
  <div v-if="isloading">
    <vcl-table :row="5" :column="8"></vcl-table>
  </div>

  <div v-else class="col-lg-12 grid-margin stretch-card">
    <div class="card">
      <div class="card-body">
        <h4 class="card-title">All Withdrawals</h4>
        <div class="row">
          <div class="col-md-4">
            <div class="boxsumarry">
              <p class="statistics-title">Wallet Balance</p>
              <div class="row">
                <div class="col-md-6">
                  <h3 class="rate-percentage">
                    &#8358; {{ formatNumber(this.auth.user.wallet) }}
                  </h3>
                </div>

                <br />
              </div>
            </div>
          </div>
          <div class="col-md-4">
            <div class="boxsumarry">
              <p class="statistics-title">Total Withdrawn</p>
              <div class="row">
                <div class="col-md-6">
                  <h3 class="rate-percentage">
                    &#8358; {{ formatNumber(totalWithdrawn) }}
                  </h3>
                </div>

                <br />
              </div>
            </div>
          </div>
        </div>

        <div class="table-responsive pt-3">
          <table class="table table-bordered">
            <thead>
              <tr>
                <th>ID</th>
                <th>User</th>
                <th>Bank</th>

                <th>Amount</th>
                <th>Status</th>
                <th>Created At</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="product in withdrawals.data" :key="product.id">
                <td>{{ product.id }}</td>
                <td>{{ product.user.firstname }}</td>
                <td>
                  {{
                    product.user.bank_name + " " + product.user.account_number
                  }}
                </td>
                <td>&#8358; {{ product.amount }}</td>
                <td>
                  <label
                    class="badge badge-success"
                    v-if="product.status == '1'"
                    >Approved</label
                  >

                  <label class="badge badge-danger" v-else>Pending</label>
                </td>
                <td>{{ product.created_at }}</td>
              </tr>
            </tbody>
          </table>
        </div>

        <pagination
          align="center"
          :data="withdrawals"
          @pagination-change-page="list"
        ></pagination>
      </div>
    </div>
  </div>
</template>
 
<script>
import AddWithdrawalModal from "./AddWithdrawalModal.vue";
import EditWithdrawalModal from "./EditWithdrawalModal.vue";
import { VclTable } from "vue-content-loading";
const token = window.localStorage.getItem("token");

export default {
  components: { AddWithdrawalModal, EditWithdrawalModal, VclTable },
  // components: {
  //   paginate,
  // },
  data() {
    return {
      withdrawals: {},
      keyword: null,

      totalWithdrawn: 0,

      activeModal: 0,
      isloading: false,
    };
  },
  // created() {
  //   console.log("token 2" + token);
  //   axios
  //     .get("/api/products", {
  //       headers: {
  //         Authorization: `Bearer ${token}`,
  //         "Content-Type": "application/json",
  //       },
  //     })
  //     .then((response) => {
  //       console.log(response.data);
  //       this.products = response.data.data;
  //     });
  // },

  mounted() {
    this.list();
  },

  watch: {
    keyword(after, before) {
      this.getResults();
    },
  },
  methods: {
    list(page) {
      if (typeof page === "undefined") {
        page = 1;
      }
      this.isloading = true;
      axios
        .get(`/api/withdrawals?page=${page}`, {
          headers: {
            Authorization: `Bearer ${token}`,
            "Content-Type": "application/json",
          },
        })
        .then(({ data }) => {
          this.withdrawals = data;
          this.sumTotal();
          this.isloading = false;
        })
        .catch(({ response }) => {
          this.$toasted.show(error.response.data.data);
          this.isloading = false;
        });
    },

    sumTotal() {
      // let basket_total = [];
      this.withdrawals.data.forEach((val) => {
        if (val.status == 1) this.totalWithdrawn += parseFloat(val.amount);
      });
      //console.log(basket_total);
    },

    getResults(page) {
      if (typeof page === "undefined") {
        page = 1;
      }
      this.isloading = true;
      axios
        .get(`/api/withdrawals?search=${this.keyword}&page=${page}`, {
          headers: {
            Authorization: `Bearer ${token}`,
            "Content-Type": "application/json",
          },
        })
        .then((response) => {
          console.log(response.data);
          this.withdrawals = response.data;
          this.sumTotal();
          this.isloading = false;
        });
    },

    showModal: function (id) {
      return this.activeModal === id;
    },
    toggleModal: function (id) {
      if (this.activeModal !== 0) {
        this.activeModal = 0;
        return false;
      }
      this.activeModal = id;
    },
  },
};
</script>

<style scoped>
.pagination {
  margin-bottom: 0;
  margin-top: 5;
}
</style>