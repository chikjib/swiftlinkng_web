<template>
  <div v-if="isloading">
    <vcl-table :row="5" :column="8"></vcl-table>
  </div>

  <div v-else class="col-lg-12 grid-margin stretch-card">
    <div class="card">
      <div class="card-body">
        <h4 class="card-title">All Commissions</h4>
        <div class="row">
          <div class="col-md-6">
            <div class="boxsumarry">
              <p class="statistics-title">Total Commission</p>
              <div class="row">
                <div class="col-md-8">
                  <h3 class="rate-percentage">
                    &#8358;
                    {{ formatNumber(totalcommission > 0
                        ? totalcommission
                        : "0.00") }}
                  </h3>
                </div>
                <div class="col-md-4">
                  <i class="mdi mdi-wallet"></i>
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
                <th>Agent</th>
                <th>Product</th>
                <th>Farmer</th>
                <th>Product Cost</th>
                <th>Commission</th>
                <th>Status</th>
                <th>Created At</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="product in commissions.data" :key="product.id">
                <td>{{ product.id }}</td>
                <td>{{ product.user.firstname }}</td>
                <td>{{ product.product.title }}</td>
                <td>{{ product.farmer.firstname }}</td>
                <td>&#8358; {{ product.total }}</td>

                <td>&#8358; {{ product.commission }}</td>
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
          :data="commissions"
          @pagination-change-page="list"
        ></pagination>
      </div>
    </div>
  </div>
</template>
 
<script>
import EditCommissionModal from "./EditCommissionModal.vue";

import { VclTable } from "vue-content-loading";

const token = window.localStorage.getItem("token");

export default {
  components: { EditCommissionModal, VclTable },

  data() {
    return {
      commissions: {},
      keyword: null,
      totalcommission: 0,
      activeModal: 0,
      isloading: false,
    };
  },

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

      var url = `${this.$appUrl}/api/commissions?search=${this.keyword}&page=${page}`;

      axios
        .get(url, {
          headers: {
            Authorization: `Bearer ${token}`,
            "Content-Type": "application/json",
          },
        })
        .then(({ data }) => {
          this.commissions = data;

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
      this.commissions.data.forEach((val) => {
        if (val.status == 1) this.totalcommission += parseFloat(val.commission);
      });
      // console.log(basket_total);
    },

    getResults(page) {
      if (typeof page === "undefined") {
        page = 1;
      }
      var url = `${this.$appUrl}/api/commissions?search=${this.keyword}&page=${page}`;
      this.isloading = true;
      axios
        .get(url, {
          headers: {
            Authorization: `Bearer ${token}`,
            "Content-Type": "application/json",
          },
        })
        .then((response) => {
          console.log(response.data);
          this.commissions = response.data;

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