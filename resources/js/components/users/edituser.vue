<template>
  <div class="row swift-admin-edit-user">
    <div class="col-xl-8 col-lg-7">
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
            <h4 class="card-title">Edit User</h4>
            <form @submit.prevent="updatePost" class="forms-sample">
              <div class="form-group">
                <label for="firstname">Firstname</label>
                <input
                  type="text"
                  class="form-control"
                  v-model="user.firstname"
                  id="firstname"
                  placeholder="Firstname"
                />
              </div>
              <div class="form-group">
                <label for="firstname">Lastname</label>
                <input
                  type="text"
                  class="form-control"
                  v-model="user.lastname"
                  id="lastname"
                  placeholder="Lastname"
                />
              </div>
              <div class="form-group">
                <label for="email">Email address</label>
                <input
                  type="email"
                  class="form-control"
                  id="email"
                  v-model="user.email"
                  placeholder="Email"
                />
              </div>

              <div class="form-group">
                <label for="phone">Phone</label>
                <input
                  type="text"
                  class="form-control"
                  id="phone"
                  v-model="user.phone"
                />
              </div>

              <div class="form-group">
                <label for="reserved_acct">Reserved Account</label>
                <input
                  type="text"
                  readonly
                  class="form-control"
                  v-model="reserved_accts"
                />
              </div>

              <div class="form-group">
                <label for="phone">MTN SME WALLET</label>
                <input
                  type="text"
                  class="form-control"
                  id="phone"
                  v-model="user.mtn_sme_wallet"
                />
              </div>
              <div class="form-group">
                <label for="Price">MTN SME WALLET Status</label>
                <select class="form-control" v-model="user.mtn_sme_wallet_status">
                  <option value="1">Approve</option>
                  <option value="0">Disapprove</option>
                </select>
              </div>
              <div class="form-group">
                <label for="phone">AIRTEL EDS WALLET</label>
                <input
                  type="text"
                  class="form-control"
                  id="phone"
                  v-model="user.airtel_eds_wallet"
                />
              </div>
              <div class="form-group">
                <label for="Price">AIRTEL EDS WALLET Status</label>
                <select class="form-control" v-model="user.airtel_eds_wallet_status">
                  <option value="1">Approve</option>
                  <option value="0">Disapprove</option>
                </select>
              </div>
              <div class="form-group">
                <label for="phone">GLO CG WALLET</label>
                <input
                  type="text"
                  class="form-control"
                  id="phone"
                  v-model="user.glo_cg_wallet"
                />
              </div>
              <div class="form-group">
                <label for="Price">GLO CG WALLET Status</label>
                <select class="form-control" v-model="user.glo_cg_wallet_status">
                  <option value="1">Approve</option>
                  <option value="0">Disapprove</option>
                </select>
              </div>
              <div class="form-group">
                <label for="phone">9MOBILE CG WALLET</label>
                <input
                  type="text"
                  class="form-control"
                  id="phone"
                  v-model="user.nmobile_cg_wallet"
                />
              </div>
              <div class="form-group">
                <label for="Price">9MOBILE CG Status</label>
                <select class="form-control" v-model="user.nmobile_cg_wallet_status">
                  <option value="1">Approve</option>
                  <option value="0">Disapprove</option>
                </select>
              </div>
              <div class="form-group">
                <label for="phone">MTN SMART WALLET</label>
                <input
                  type="text"
                  class="form-control"
                  id="phone"
                  v-model="user.mtn_smart_wallet"
                />
              </div>
              <div class="form-group">
                <label for="Price">MTN SMART WALLET Status</label>
                <select class="form-control" v-model="user.mtn_smart_wallet_status">
                  <option value="1">Approve</option>
                  <option value="0">Disapprove</option>
                </select>
              </div>
              <div class="form-group">
                <label for="phone">AIRTEL AWOOF WALLET</label>
                <input
                  type="text"
                  class="form-control"
                  id="phone"
                  v-model="user.airtel_awoof_wallet"
                />
              </div>
              <div class="form-group">
                <label for="Price">AIRTEL AWOOF Status</label>
                <select class="form-control" v-model="user.airtel_awoof_wallet_status">
                  <option value="1">Approve</option>
                  <option value="0">Disapprove</option>
                </select>
              </div>
              <div class="form-group">
                <label for="phone">GLO AWOOF WALLET</label>
                <input
                  type="text"
                  class="form-control"
                  id="phone"
                  v-model="user.glo_awoof_wallet"
                />
              </div>
              <div class="form-group">
                <label for="Price">GLO AWOOF Status</label>
                <select class="form-control" v-model="user.glo_awoof_wallet_status">
                  <option value="1">Approve</option>
                  <option value="0">Disapprove</option>
                </select>
              </div>


              <button type="submit" class="btn btn-danger me-2">Submit</button>
              <br/>
              <br/>
              <p v-if="user.bvn !=null">
                <b>BVN Verification: </b> <span class="text-success">Verified</span>
              </p>
              <p v-else>
                <b>BVN Verification: </b> <span class="text-danger">Not yet verified</span>
              </p>
              <p v-if="user.nin !=null">
                <b>NIN Verification: </b> <span class="text-success">Verified</span>
              </p>
              <p v-else>
                <b>NIN Verification: </b> <span class="text-danger">Not yet verified</span>
              </p>
            </form>
          </div>
        </div>
      </div>
    </div>

    <div class="col-xl-4 col-lg-5">
      <div class="card mb-4">
        <div
          class="card-header py-3 d-flex flex-row align-items-center justify-content-between"
        >
          <div class="col-md-12">
            <h4 class="card-title">Wallet</h4>
            <form
              method="post"
              @submit.prevent="getButtonName($event)"
              class="forms-sample"
            >
              <div class="form-group">
                <label for="wallet">Current Balance</label>
                <input
                  type="text"
                  class="form-control"
                  v-model="user.wallet"
                  readonly
                  id="wallet"
                  placeholder="wallet"
                />
              </div>

              <div class="form-group">
                <label for="wallet">Amount</label>
                <input
                  type="text"
                  class="form-control"
                  name="amount"
                  v-model="formbalance.amount"
                  placeholder="Amount"
                />
              </div>

              <button
                type="submit"
                name="credit"
                @click="credit($event)"
                value="credit"
                class="btn btn-danger me-2"
              >
                Credit
              </button>
              <button
                type="submit"
                name="debit"
                @click="credit($event)"
                value="debit"
                class="btn btn-danger me-2"
              >
                Debit
              </button>
            </form>
          </div>
        </div>
      </div>

      <div class="card mb-4">
        <div
          class="card-header py-3 d-flex flex-row align-items-center justify-content-between"
        >
          <div class="col-md-12">
            <h4 class="card-title">Bonus Wallet</h4>
            <p class="text-muted">
              Credit or debit this user's referral bonus balance.
            </p>

            <div class="form-group">
              <label for="commission-wallet">Current Bonus Balance</label>
              <input
                id="commission-wallet"
                type="text"
                class="form-control"
                :value="'₦ ' + formatAmount(user.commission)"
                readonly
              />
            </div>

            <div class="form-group">
              <label for="commission-amount">Amount</label>
              <input
                id="commission-amount"
                type="number"
                min="0.01"
                step="0.01"
                class="form-control"
                v-model.number="formcommission.amount"
                placeholder="Enter amount"
              />
            </div>

            <button
              type="button"
              class="btn btn-danger me-2"
              :disabled="commissionLoading"
              @click="adjustCommission('credit')"
            >
              {{ commissionLoading ? "Processing..." : "Credit" }}
            </button>
            <button
              type="button"
              class="btn btn-danger me-2"
              :disabled="commissionLoading"
              @click="adjustCommission('debit')"
            >
              {{ commissionLoading ? "Processing..." : "Debit" }}
            </button>
          </div>
        </div>
      </div>

      <div class="card mb-4">
        <div
          class="card-header py-3 d-flex flex-row align-items-center justify-content-between"
        >
          <div class="col-md-12">
            <h4 class="card-title">Bucket Wallet</h4>
            <form
              method="post"
              @submit.prevent="getButtonName($event)"
              class="forms-sample"
            >
              <div class="form-group">
                <label for="wallet">Select Wallet</label>
                <select
                      name="bucket_id"
                      v-model="formbalance.bucket_id"
                      class="form-control"
                    >
                      <option disabled selected="">Select Wallet</option>
                      <option
                        v-for="buckets in buckets.data"
                        :key="buckets.id"
                        v-bind:value="buckets.id"
                      >
                        {{ buckets.title }}
                      </option>
                    </select>
              </div>

              <div class="form-group">
                <label for="wallet">Data Size</label>
                <input
                  type="text"
                  class="form-control"
                  name="data_size"
                  v-model="formbalance.data_size"
                  placeholder="Data Size"
                />
              </div>

              <button
                type="submit"
                name="credit_bucket"
                @click="credit($event)"
                value="credit_bucket"
                class="btn btn-danger me-2"
              >
                Credit
              </button>
              <button
                type="submit"
                name="debit_bucket"
                @click="credit($event)"
                value="debit_bucket"
                class="btn btn-danger me-2"
              >
                Debit
              </button>
            </form>
          </div>
        </div>
      </div>

      <div class="card mb-4">
        <div
          class="card-header py-3 d-flex flex-row align-items-center justify-content-between"
        >
          <div class="col-md-12">
            <h4 class="card-title">Upgrade User</h4>
            <form @submit.prevent="upgradeLevel" class="forms-sample">
              <p class="text-info">
                Note: 0:Normal, 1: Agent, 2: Whatsapp, 3: API
              </p>
              <div class="form-group">
                <label for="level">Current Level</label>
                <input
                  type="text"
                  class="form-control"
                  v-model="user.userlevel"
                />
              </div>

              <button type="submit" class="btn btn-danger me-2">Upgrade</button>
            </form>
          </div>
        </div>
      </div>
    </div>

  </div>
</template>

<script>
const token = window.localStorage.getItem("token");

export default {
  data() {
    return {
      user: {},
      successflag: "",
      errorflag: "",
      form: {},
      formbalance: {},
      formcommission: {
        amount: null,
      },
      commissionLoading: false,
      formlevel: {},
      reserved_accts: [],
      buckets: {}
    };
  },
  mounted() {
    this.load_bucket();
  },
  created() {
    axios
      .get(`/api/admin/allusers/${this.$route.params.id}`, {
        headers: {
          Authorization: `Bearer ${token}`,
          "Content-Type": "application/json",
        },
      })
      .then((response) => {
        console.log(response.data);

        this.user = response.data.data;
        const providus = this.user.providus_reserved_acct != null ? this.user.providus_reserved_acct + "," : "" ;
        const rehoboth = this.user.rehoboth_reserved_acct != null ? this.user.rehoboth_reserved_acct + "," : "";
        const gtbank = this.user.gtbank_reserved_acct !=null ? this.user.gtbank_reserved_acct + "," : "";
        const wema = this.user.wema_reserved_acct != null ? this.user.wema_reserved_acct + "," : "";
        const moniepoint = this.user.moniepoint_reserved_acct !=null ? this.user.moniepoint_reserved_acct + "," : "";
        const palmpay = this.user.palmpay_reserved_acct !=null ? this.user.palmpay_reserved_acct + "," : "";
        this.reserved_accts = providus + rehoboth + gtbank + wema +moniepoint+palmpay;

      });
  },
  methods: {
    formatAmount(value) {
      return Number(value || 0).toLocaleString("en-NG", {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
      });
    },
    getButtonName(event) {
      // console.log(event);
    },
    updatePost() {
      axios
        .put(`/api/admin/allusers/${this.$route.params.id}`, this.user, {
          headers: {
            Authorization: `Bearer ${token}`,
            "Content-Type": "application/json",
          },
        })
        .then((response) => {
          // console.log(response.data);
          // this.$router.push({ name: "users" });

          this.successflag = response.data.message;
          this.$toasted.show(response.data.message);
        })
        .catch((error) => {
          console.log(error.response.data.message);
          this.$toasted.show(error.response.data.message);
        });
    },

    credit(e) {
      this.formbalance.type = e.target.name;
      this.formbalance.user_id = this.user.id;

      console.log(e.target.name);
      axios
        .post(`/api/admin/user/update/wallet`, this.formbalance, {
          headers: {
            Authorization: `Bearer ${token}`,
            "Content-Type": "application/json",
          },
        })
        .then((response) => {
          console.log(response.data);

          this.successflag = response.data.message;
          this.$toasted.show(response.data.message);
        })
        .catch((error) => {
          this.errorflag = error.response.data.message;
          console.log(error.response.data.message);
          this.$toasted.show(error.response.data.message);
        });
    },

    adjustCommission(type) {
      const amount = Number(this.formcommission.amount);

      if (!Number.isFinite(amount) || amount <= 0) {
        this.$toasted.show("Enter a valid commission amount.");
        return;
      }

      const action = type === "credit" ? "credit" : "debit";
      if (!window.confirm(`Are you sure you want to ${action} this bonus wallet?`)) {
        return;
      }

      this.commissionLoading = true;
      axios
        .post(
          `/api/admin/user/update/commission`,
          {
            user_id: this.user.id,
            type,
            amount,
            channel: "Web",
          },
          {
            headers: {
              Authorization: `Bearer ${token}`,
              "Content-Type": "application/json",
            },
          }
        )
        .then((response) => {
          this.user.commission = response.data.data.commission;
          this.formcommission.amount = null;
          this.successflag = response.data.message;
          this.errorflag = "";
          this.$toasted.show(response.data.message);
        })
        .catch((error) => {
          const message =
            error.response && error.response.data && error.response.data.message
              ? error.response.data.message
              : "Bonus wallet adjustment failed.";
          this.errorflag = message;
          this.$toasted.show(message);
        })
        .then(() => {
          this.commissionLoading = false;
        });
    },

    upgradeLevel() {
      this.formlevel.user_id = this.user.id;
      this.formlevel.userlevel = this.user.userlevel;
      axios
        .post(`/api/user/admin-update/level`, this.formlevel, {
          headers: {
            Authorization: `Bearer ${token}`,
            "Content-Type": "application/json",
          },
        })
        .then((response) => {
          console.log(response.data);
          // this.$router.push({ name: "users" });

          this.successflag = response.data.message;
          this.$toasted.show(response.data.message);
        })
        .catch((error) => {
          console.log(error.response.data.message);
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

  },
};
</script>
