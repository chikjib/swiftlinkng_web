<template>
  <div id="printMe" class="col-xl-4 col-lg-4 mx-auto">
    <div class="card mb-4">
      <div
        class="card-header py-3 d-flex flex-row align-items-center justify-content-between"
      >
        <div class="col-md-12">
          <table class="table">
            <tbody>
            <tr>
              <td colspan="2">
                <img :src="'/frontend/images/swiftlogo.png'" />
              </td>
            </tr>
            <tr>
              <td colspan="2">Swiftlinkng Receipt</td>
            </tr>
            <tr>
              <th>Ref</th>
              <td>{{ transactions.ref }}</td>
            </tr>
            <tr>
              <th>Description</th>
              <td>{{ transactions.plan }}</td>
            </tr>

            <tr v-if="transactions.phone !== null">
              <th>Phone</th>
              <td>{{ transactions.phone }}</td>
            </tr>
            <tr>
              <th>Amount</th>
              <td>{{ formatNumber(transactions.amount) }}</td>
            </tr>
            <tr>
              <th>Status</th>

              <td>
                <label
                  class="badge badge-success"
                  v-if="transactions.status == '1'"
                  >Confirmed</label
                >

                <label
                  class="badge badge-info"
                  v-else-if="transactions.status == '2'"
                  >Reversed</label
                >

                <label
                  class="badge badge-danger"
                  v-else-if="transactions.status == '3'"
                  >Cancelled</label
                >
                <label
                  class="badge badge-danger"
                  v-else-if="transactions.status == '4'"
                  >Failed</label
                >

                <label class="badge badge-warning" v-else>Pending</label>
              </td>
            </tr>
            <tr>
              <th>Date</th>
              <td>{{ transactions.created_at }}</td>
            </tr>
            </tbody>
          </table>
          <button class="btn btn-danger" @click="print">Print</button>
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
      transactions: {},
    };
  },

  mounted() {
    this.list();
  },
  methods: {
    print() {
      // Pass the element id here
      this.$htmlToPaper("printMe");
    },
    list(page) {
      if (typeof page === "undefined") {
        page = 1;
      }

      this.isloading = true;
      axios
        .get(`/api/bucket-orders/${this.$route.params.id}`, {
          headers: {
            Authorization: `Bearer ${token}`,
            "Content-Type": "application/json",
          },
        })
        .then(({ data }) => {
          this.transactions = data.data;

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
