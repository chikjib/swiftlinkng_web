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
              <td>
                {{ transactions.ref }}
                <button v-if="isElectricity" class="swift-copy-button" type="button" title="Copy transaction ID" @click="copyValue(transactions.ref, 'Transaction ID')">
                  <i class="far fa-copy"></i><span>Copy</span>
                </button>
              </td>
            </tr>
            <tr>
              <th>Description</th>
              <td>{{ transactions.plan }}</td>
            </tr>
            <tr v-if="transactions.iuc != null">
              <th>Card No</th>
              <td>{{ transactions.iuc }}</td>
            </tr>
            <tr v-if="transactions.meter != null">
              <th>Meter No</th>
              <td>{{ transactions.meter }}</td>
            </tr>
            <tr v-if="isElectricity && electricityToken">
              <th>Token</th>
              <td>
                {{ electricityToken }}
                <button class="swift-copy-button" type="button" title="Copy electricity token" @click="copyValue(electricityToken, 'Electricity token')">
                  <i class="far fa-copy"></i><span>Copy</span>
                </button>
              </td>
            </tr>
            <tr>
              <th>Phone</th>
              <td>{{ transactions.phone }}</td>
            </tr>
            <tr>
              <th>Amount</th>
              <td>&#8358;{{ transactions.amount }}</td>
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

  computed: {
    isElectricity() {
      const description = `${this.transactions.plan || ''} ${this.transactions.description || ''}`.toLowerCase();
      return this.transactions.meter != null || description.includes('electric');
    },
    electricityToken() {
      if (this.transactions.token) return String(this.transactions.token).trim();
      const description = String(this.transactions.description || '');
      const separator = description.indexOf(':');
      return separator >= 0 ? description.slice(separator + 1).trim() : '';
    },
  },

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
    async copyValue(value, label) {
      const text = String(value || '').trim();
      if (!text) return;

      try {
        await navigator.clipboard.writeText(text);
      } catch (_) {
        const input = document.createElement('textarea');
        input.value = text;
        input.setAttribute('readonly', '');
        input.style.position = 'fixed';
        input.style.opacity = '0';
        document.body.appendChild(input);
        input.select();
        document.execCommand('copy');
        input.remove();
      }

      this.$toasted.show(`${label} copied`);
    },
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
        .get(`/api/orders/${this.$route.params.id}`, {
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

.swift-copy-button {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  margin-left: 8px;
  padding: 4px 8px;
  border: 1px solid #b40008;
  border-radius: 8px;
  background: #fff;
  color: #b40008;
  font-size: 12px;
  font-weight: 700;
  cursor: pointer;
}
</style>
