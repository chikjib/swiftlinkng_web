<template>
  <div v-if="isloading">
    <vcl-table :row="5" :column="8"></vcl-table>
  </div>

  <div v-else class="col-xl-12 col-lg-12">
    <div class="row">
      <div class="col-md-12 mb-2">
        <form @submit.prevent="getResults" class="form-horizontal">
          <div class="col-md-12">
            <div class="row">
              <div class="col-md-3">
                <div class="form-group">
                  <label for="status">Select Status</label>
                  <select class="form-control" v-model="form.status">
                    <option>Status</option>
                    <option value="1">Confirmed</option>
                    <option value="3">Cancelled</option>
                    <option value="4">Failed</option>
                    <option value="2">Reversed</option>
                    <option value="0">Pending</option>
                  </select>
                </div>
              </div>
              <div class="col-md-3">
                <div class="form-group">
                  <label for="status">Select User Level</label>
                  <select class="form-control" v-model="form.userlevel">
                    <option>User Level</option>
                    <option value="0">Normal</option>
                    <option value="1">Agent</option>
                    <option value="2">Whatsapp</option>
                    <option value="3">API</option>
                  </select>
                </div>
              </div>
              <div class="col-md-3">
                <div class="form-group">
                  <label for="provider">Select Category</label>
                  <select
                    name="subcategory_id"
                    v-model="form.subcategory_id"
                    class="form-control"
                  >
                    <option>Select Provider</option>
                    <option
                      v-for="subcategories in subcategories.data"
                      :key="subcategories.id"
                      v-bind:value="subcategories.id"
                    >
                      {{ subcategories.title }} -
                      {{ subcategories.category.title }}
                    </option>
                  </select>
                </div>
              </div>

              <div class="col-md-3">
                <div class="form-group">
                  <label for="category"> Start Date</label>
                  <input type="datetime-local" v-model="form.start_date" class="form-control" />
                </div>
              </div>
              <div class="col-md-3">
                <div class="form-group">
                  <label for="category"> End Date</label>
                  <input type="datetime-local" v-model="form.end_date" class="form-control" />
                </div>
              </div>
            </div>
            <div class="col-md-3">
              <div class="form-group">
                <button type="submit" class="btn btn-danger">Load</button>
              </div>
            </div>
          </div>
        </form>
      </div>
    </div>
    <div class="table-responsive">
      <table class="table table-bordered">
        <thead class="thead-light">
            <tr>
                <th>Total Count</th>
                <th>Total Data Size (MB)</th>
                <th>Amount</th>
              </tr>
        </thead>
        <tbody>
            <tr v-for="transaction in transactions.data" :key="transaction.id">
                <td>{{ formatNumber(transaction.countPlan) }}</td>
                <td>{{ formatNumber(transaction.countDataSize ?  transaction.countDataSize : transaction.sumAmount) }}</td>
                <td>&#8358; {{ formatNumber(transaction.sumtotal) }}</td>
              </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script>
import { VclTable } from "vue-content-loading";
import Datepicker from "vuejs-datepicker";

const token = window.localStorage.getItem("token");

export default {
  props: ["limit"],
  components: { VclTable, Datepicker },

  data() {
    return {
      transactions: {},
      keyword: null,
      field: "Search By",
      form: {
        status: "",
        start_date: new Date().toISOString().slice(0, 16),
        end_date: new Date().toISOString().slice(0, 16),
      },
      subcategories: {},

      searching: false,
      isloading: false,

      category_id: null,
      subcategory_id: null,
      plan_id: null,

      showMoreDes: false,
      showMorePhone: false,
    };
  },

  mounted() {
    this.loadplan();
  },

  // watch: {
  //   keyword(after, before) {
  //     this.getResults();
  //   },
  // },
  methods: {
    loadplan() {
      axios
        .get(`/api/subcategory?all=1`, {
          headers: {
            Authorization: `Bearer ${token}`,
            "Content-Type": "application/json",
          },
        })
        .then((response) => {
          console.log(response.data);
          this.subcategories = response.data;
        });
    },

    getResults(page) {
      if (typeof page === "undefined") {
        page = 1;
      }
      this.isloading = true;
      this.searching = true;
      console.log(this.form);
      axios
        .post(`/api/order/reports`, this.form, {
          headers: {
            Authorization: `Bearer ${token}`,
            "Content-Type": "application/json",
          },
        })
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
