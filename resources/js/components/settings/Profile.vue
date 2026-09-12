<template>
    <div>
        <div v-if="users.id" class="email-verification-card" :class="users.email_verified ? 'is-verified' : 'is-unverified'">
          <div class="verification-icon">
            <i :class="users.email_verified ? 'fas fa-check-circle' : 'fas fa-envelope-open-text'"></i>
          </div>
          <div class="verification-copy">
            <h4>{{ users.email_verified ? 'Email Verified' : 'Email Verification Required' }}</h4>
            <p v-if="users.email_verified">
              {{ users.email }} has been verified. Your account is eligible for fixed referral rewards.
            </p>
            <p v-else>
              {{ users.email }} is not verified. Open the link sent to this address to qualify for fixed referral rewards.
            </p>
          </div>
          <button
            v-if="!users.email_verified"
            type="button"
            class="btn btn-danger"
            :disabled="sendingVerification"
            @click="resendVerification"
          >
            {{ sendingVerification ? "Sending..." : "Resend Verification Email" }}
          </button>
        </div>
        <div class="row">
            <div class="col-xl-6 col-lg-6">
                 <div class="card mb-6">
             <div class="card-body">
                <h4 class="card-title">Profile Info</h4>
                <div class="table-responsive">
                  <table class="table table-bordered">
                    <tbody>
                    <tr>
                      <td>Name</td>
                      <td>{{ users.firstname + "  " + users.lastname }}</td>
                    </tr>
                    <tr>
                      <td>Wallet</td>
                      <td>&#8358; {{ users.wallet }}</td>
                    </tr>
                    <tr>
                      <td>Email</td>
                      <td>{{ users.email }}</td>
                    </tr>

                    <tr>
                      <td>Email Status</td>
                      <td
                        class="profile-email-status"
                        :class="users.email_verified ? 'is-verified' : 'is-unverified'"
                      >
                        <span v-if="users.email_verified" class="badge badge-success">
                          Verified
                        </span>
                        <div v-else>
                          <span class="badge badge-warning">Not verified</span>
                          <small class="d-block mt-2">
                            Verify your email to qualify for referral rewards.
                          </small>
                          <button
                            type="button"
                            class="btn btn-sm btn-outline-danger mt-2"
                            :disabled="sendingVerification"
                            @click="resendVerification"
                          >
                            {{ sendingVerification ? "Sending..." : "Resend verification email" }}
                          </button>
                        </div>
                      </td>
                    </tr>

                    <tr>
                      <td>Phone</td>
                      <td>{{ users.phone }}</td>
                    </tr>

                    <tr>
                      <td>Join Date</td>
                      <td>{{ users.created_at }}</td>
                    </tr>

                    <tr>
                      <td>Referral Link</td>
                      <td>
                        <input
                          type="text"
                          v-model="reflink"
                          class="form-control"
                          readonly
                        />
                      </td>
                    </tr>

                    <!-- <tr>
                    <td>API KEY</td>
                    <td v-if="users.userToken == null">
                      <a
                        class="btn btn-danger"
                        @click.stop="generateApiKey()"
                        href="#"
                        >Generate Key</a
                      >
                    </td>
                    <td v-else>
                      {{ users.userToken }}
                    </td>
                  </tr> -->
                    </tbody>
                  </table>
                </div>
             </div>
            </div>
            </div>

            <div class="col-xl-6 col-lg-6">
            <div class="card mb-6">
                <div class="card-body">
                    <h4 class="card-title">Form of Identification</h4>
                    <hr />
                    <div v-if="users.bvn ==null">
           <p>
                      <input
                        type="radio"
                        id="bvn"
                        name="id-select"
                        value="bvn"
                        v-model="picked"
                      />
                      Bvn Number
                    </p>
                    <div v-if="picked == 'bvn'" class="bvn_style">
                      <div
                        class="notification alert alert-success"
                        v-if="bvn_success_flag"
                      >
                        <span>{{ bvn_success_flag }}</span>
                      </div>
                      <div
                        class="notification alert alert-danger"
                        v-if="bvn_errors.length"
                      >
                        <span v-for="bvn_error in bvn_errors" :key="bvn_error.key">{{
                          bvn_error
                        }}</span>
                      </div>

                      <form @submit.prevent="submitBvn">
                        <div class="form-group">
                          <label>BVN number</label>
                          <input
                            type="number"
                            name="bvn"
                            id="bvn"
                            v-model="bvn"
                            class="form-control"
                          />
                          </div>
                          <div class="form-group">
                          <label>Full Name (as it appears on your bvn)</label>
                          <input
                            type="text"
                            name="name"
                            id="name"
                            v-model="bvn_name"
                            class="form-control"
                          />

                        </div>
                        <div class="form-group">
                          <button v-if="isLoading" disabled id="bvn" class="btn btn-danger">
                            Processing...
                          </button>
                          <button v-else type="submit" id="bvn" class="btn btn-danger">
                            Submit
                          </button>
                        </div>

                      </form>
                    </div>
                    </div>
                    <div v-else>
                      <p class="text-success">BVN Verified!</p>
                    </div>

                    <div v-if="users.nin == null">
                      <p>
                          <input
                            type="radio"
                            id="nin"
                            name="id-select"
                            value="nin"
                            v-model="picked"
                          />
                          NIN Number
                        </p>
                        <div v-if="picked == 'nin'" class="nin_style">
                          <div
                            class="notification alert alert-success"
                            v-if="nin_success_flag"
                          >
                            <span>{{ nin_success_flag }}</span>
                          </div>
                          <div
                            class="notification alert alert-danger"
                            v-if="nin_errors.length"
                          >
                            <span v-for="nin_error in nin_errors" v-bind:key="nin_error.key">{{
                              nin_error
                            }}</span>
                          </div>

                          <form @submit.prevent="submitNin">
                            <div class="form-group">
                              <label>NIN number</label>
                              <input
                                type="number"
                                name="nin"
                                id="nin"
                                v-model="nin"
                                class="form-control"
                              />
                            </div>
                            <div class="form-group">
                              <button v-if="isLoading" disabled id="bvn" class="btn btn-danger">
                                Processing...
                              </button>
                              <button v-else type="submit" id="bvn" class="btn btn-danger">
                                Submit
                              </button>
                            </div>
                          </form>
                        </div>
                    </div>
                    <div v-else>
                      <p class="text-success">Nin Verified!</p>
                    </div>



                  </div>
                </div>

            </div>

          </div>

          <div class="row m">
            <div class="col-xl-12 col-lg-12">
                <div class="card mb-12">

                    <div class="card-body">
                        <h4 class="card-title">Webhook Url</h4>
                        <form @submit.prevent="updateWebhook" class="forms-sample">
                            <div class="form-group">
                            <div class="col-sm-12">
                                <input
                                required
                                type="text"
                                v-model="users.webhook_url"
                                class="form-control"
                                placeholder="Your Webhook Url"
                                />
                            </div>
                            </div>

                            <button type="submit" class="btn btn-danger me-2">
                            Submit
                            </button>
                        </form>
                    </div>


                </div>
            </div>
        </div>

          <div class="row mt-4">
            <div class="col-xl-12 col-lg-12">
              <div class="card transaction-pin-card">
                <div class="card-body">
                  <div class="transaction-pin-heading">
                    <div class="transaction-pin-icon"><i class="fas fa-shield-alt"></i></div>
                    <div>
                      <h4 class="card-title mb-1">Transaction PIN</h4>
                      <p class="mb-0 text-muted">
                        {{ hasTransactionPin ? "Change your 4-digit purchase PIN." : "Create a 4-digit PIN to secure purchases and withdrawals." }}
                      </p>
                    </div>
                    <span class="badge" :class="hasTransactionPin ? 'badge-success' : 'badge-warning'">
                      {{ hasTransactionPin ? "Active" : "Not set" }}
                    </span>
                  </div>

                  <form class="transaction-pin-form" @submit.prevent="saveTransactionPin">
                    <div v-if="hasTransactionPin" class="form-group">
                      <label>Current transaction PIN</label>
                      <input v-model="transactionPin.current_pin" required maxlength="4" inputmode="numeric" pattern="[0-9]{4}" type="password" class="form-control" placeholder="••••" autocomplete="off" />
                    </div>
                    <div class="form-group">
                      <label>{{ hasTransactionPin ? "New transaction PIN" : "Create transaction PIN" }}</label>
                      <input v-model="transactionPin.pin" required maxlength="4" inputmode="numeric" pattern="[0-9]{4}" type="password" class="form-control" placeholder="••••" autocomplete="new-password" />
                    </div>
                    <div class="form-group">
                      <label>Confirm transaction PIN</label>
                      <input v-model="transactionPin.pin_confirmation" required maxlength="4" inputmode="numeric" pattern="[0-9]{4}" type="password" class="form-control" placeholder="••••" autocomplete="new-password" />
                    </div>
                    <button type="submit" class="btn btn-danger" :disabled="transactionPinLoading">
                      {{ transactionPinLoading ? "Saving..." : (hasTransactionPin ? "Change Transaction PIN" : "Create Transaction PIN") }}
                    </button>
                  </form>

                  <div v-if="hasTransactionPin" class="transaction-pin-reset">
                    <button type="button" class="btn btn-link" @click="pinResetMode = !pinResetMode">
                      <i class="fas fa-key mr-1"></i>
                      {{ pinResetMode ? "Cancel PIN reset" : "Forgot your transaction PIN?" }}
                    </button>
                    <form v-if="pinResetMode" class="transaction-pin-form mt-3" @submit.prevent="resetTransactionPin">
                      <div class="form-group">
                        <label>Account password</label>
                        <input v-model="transactionPinReset.password" required type="password" class="form-control" placeholder="Enter your login password" autocomplete="current-password" />
                        <small class="form-text text-muted">Your account password confirms that this reset belongs to you.</small>
                      </div>
                      <div class="form-group">
                        <label>New transaction PIN</label>
                        <input v-model="transactionPinReset.pin" required maxlength="4" inputmode="numeric" pattern="[0-9]{4}" type="password" class="form-control" placeholder="••••" autocomplete="new-password" />
                      </div>
                      <div class="form-group">
                        <label>Confirm new transaction PIN</label>
                        <input v-model="transactionPinReset.pin_confirmation" required maxlength="4" inputmode="numeric" pattern="[0-9]{4}" type="password" class="form-control" placeholder="••••" autocomplete="new-password" />
                      </div>
                      <button type="submit" class="btn btn-danger" :disabled="transactionPinResetLoading">
                        {{ transactionPinResetLoading ? "Resetting…" : "Reset Transaction PIN" }}
                      </button>
                    </form>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <br/>

          <div class="row">
            <div class="col-xl-6 col-lg-6">
                <div class="card mb-6">
                <div class="card-body">
                    <h4 class="card-title">Change Password</h4>

                    <form @submit.prevent="changePassword" class="forms-sample">
                      <div class="form-group">
                        <div class="col-sm-12">
                          <input
                            required
                            type="password"
                            v-model="form.old_password"
                            class="form-control"
                            placeholder="Old Password"
                          />
                        </div>
                      </div>
                      <div class="form-group">
                        <div class="col-sm-12">
                          <input
                            required
                            type="password"
                            v-model="form.new_password"
                            class="form-control"
                            placeholder="New Password"
                          />
                        </div>
                      </div>
                      <div class="form-group">
                        <div class="col-sm-12">
                          <input
                            required
                            type="password"
                            v-model="form.confirm_password"
                            class="form-control"
                            placeholder="Confirm Password"
                          />
                        </div>
                      </div>
                      <button type="submit" class="btn btn-danger me-2">
                        Submit
                      </button>
                    </form>

              </div>
                </div>
            </div>


            <div class="col-xl-6 col-lg-6">
                <div class="card mb-6">
                    <div class="card-body">

              <h4 v-if="users.bank_name != null" class="card-title">
                Update Profile
              </h4>

              <h4 v-else class="card-title">Update Profile</h4>


                    <form @submit.prevent="addBank" class="forms-sample">
                      <div class="form-group">
                        <div class="col-sm-12">
                          <input
                            required
                            type="text"
                            v-model="users.phone"
                            class="form-control"
                            placeholder="Change Phone"
                          />
                        </div>
                      </div>

                      <div class="form-group">
                        <div class="col-sm-12">
                          <input
                            required
                            type="text"
                            v-model="users.bank_name"
                            class="form-control"
                            placeholder="Bank Name"
                          />
                        </div>
                      </div>
                      <div class="form-group">
                        <div class="col-sm-12">
                          <input
                            required
                            type="text"
                            v-model="users.account_number"
                            class="form-control"
                            placeholder="Account Number"
                          />
                        </div>
                      </div>

                      <button type="submit" class="btn btn-danger me-2">
                          Submit
                        </button>
                    </form>

              </div>
            </div>
            </div>

          </div>

    </div>

</template>

<script>
export default {
  data() {
    return {
      users: {},
      picked: "",
      bvn_errors: [],
      bvn_success_flag: "",
      nin_errors: [],
      nin_success_flag: "",
      bvn: "",
      bvn_name: "",
      nin: "",
      isLoading: false,
      sendingVerification: false,
      transactionPinLoading: false,
      transactionPinResetLoading: false,
      pinResetMode: false,
      hasTransactionPin: false,
      transactionPin: {
        current_pin: "",
        pin: "",
        pin_confirmation: "",
      },
      transactionPinReset: {
        password: "",
        pin: "",
        pin_confirmation: "",
      },
      form: {
        old_password: null,
        new_password: null,
        confirm_password: null,
        bank_name: null,
        phone: "",
        account_number: null,
        webhook_url: null,
      },

      upload: {
        user_image: null,
      },

      reflink: this.$appUrl + "/register?ref=" + this.auth.user.id,

      userdata: this.auth.user,

      activeModal: 0,
    };
  },

  mounted() {
    this.getusers();
    this.loadTransactionPinStatus();
    // this.getProd();
    this.get_avatar();
  },

  methods: {
    loadTransactionPinStatus() {
      axios
        .get('/api/transaction-pin/status')
        .then((response) => {
          this.hasTransactionPin = !!(
            response.data &&
            response.data.data &&
            response.data.data.has_transaction_pin
          );
        })
        .catch(() => this.$toasted.show('Unable to load transaction PIN status.'));
    },

    saveTransactionPin() {
      const digits = /^\d{4}$/;
      if (!digits.test(this.transactionPin.pin)) {
        this.$toasted.show('Transaction PIN must contain exactly four digits.');
        return;
      }
      if (this.hasTransactionPin && !digits.test(this.transactionPin.current_pin)) {
        this.$toasted.show('Enter your current four-digit transaction PIN.');
        return;
      }
      if (this.transactionPin.pin !== this.transactionPin.pin_confirmation) {
        this.$toasted.show('The transaction PINs do not match.');
        return;
      }

      this.transactionPinLoading = true;
      const request = this.hasTransactionPin
        ? axios.put('/api/transaction-pin', this.transactionPin)
        : axios.post('/api/transaction-pin', this.transactionPin);
      request
        .then((response) => {
          this.$toasted.show(response.data.message);
          this.hasTransactionPin = true;
          this.transactionPin = { current_pin: '', pin: '', pin_confirmation: '' };
        })
        .catch((error) => {
          const data = error.response && error.response.data;
          const validation = data && data.errors
            ? Object.values(data.errors).flat().join(' ')
            : null;
          this.$toasted.show(validation || (data && data.message) || 'Unable to save transaction PIN.');
        })
        .finally(() => {
          this.transactionPinLoading = false;
        });
    },

    resetTransactionPin() {
      const digits = /^\d{4}$/;
      if (!digits.test(this.transactionPinReset.pin)) {
        this.$toasted.show('Transaction PIN must contain exactly four digits.');
        return;
      }
      if (this.transactionPinReset.pin !== this.transactionPinReset.pin_confirmation) {
        this.$toasted.show('The transaction PINs do not match.');
        return;
      }

      this.transactionPinResetLoading = true;
      axios
        .post('/api/transaction-pin/reset', this.transactionPinReset)
        .then((response) => {
          this.$toasted.show(response.data.message);
          this.transactionPinReset = { password: '', pin: '', pin_confirmation: '' };
          this.pinResetMode = false;
        })
        .catch((error) => {
          const data = error.response && error.response.data;
          const validation = data && data.errors
            ? Object.values(data.errors).flat().join(' ')
            : null;
          this.$toasted.show(validation || (data && data.message) || 'Unable to reset transaction PIN.');
        })
        .finally(() => {
          this.transactionPinResetLoading = false;
        });
    },

    resendVerification() {
      if (!this.users.email || this.sendingVerification) {
        return;
      }

      this.sendingVerification = true;
      axios
        .post('/api/email/verification/resend', { email: this.users.email })
        .then((response) => {
          this.$toasted.show(response.data.message);
        })
        .catch((error) => {
          this.$toasted.show(
            (error.response && error.response.data.message) ||
              'The verification email could not be sent. Please try again.'
          );
        })
        .finally(() => {
          this.sendingVerification = false;
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

    get_avatar() {
      let photo =
        this.users.user_image == null
          ? "/template/images/faces/noimage.png"
          : this.users.user_image;
      return photo;
    },

    getusers() {
      axios
        .get(`/api/users/${this.userdata.id}`, {
          headers: {
            Authorization: `Bearer ${this.auth.token}`,
            "Content-Type": "application/json",
          },
        })
        .then((response) => {
          console.log(response.data.data);
          this.users = response.data.data;
        });
    },

    generateApiKey() {
      axios
        .get(`/api/generate/api/key`, {
          headers: {
            Authorization: `Bearer ${this.auth.token}`,
            "Content-Type": "application/json",
          },
        })
        .then((response) => {
          // console.log(response.data.data);
          // this.form.token = response.data.data.userToken;
          window.location.reload();
        });
    },

    onChange(e) {
      this.upload.user_image = e.target.files[0];
    },
    formSubmit(e) {
      e.preventDefault();
      let existingObj = this;
      const config = {
        headers: {
          Authorization: `Bearer ${this.auth.token}`,
          "Content-Type": "multipart/form-data",
        },
      };
      let data = new FormData();
      data.append("user_image", this.upload.user_image);
      // console.log(this.upload.user_image);
      axios
        .post(`/api/users/uploadimage/${this.userdata.id}`, data, config)
        .then(function (res) {
          // existingObj.success = res.data.success;
          // console.log(res.data.message);
          //this.$toasted.show(res.data.message);
          //this.$router.push({ name: "users" });
          window.location.reload();
        })
        .catch(function (err) {
          //existingObj.output = err;
          this.$toasted.show(error.response.data.data);
        });
    },

    onChange22(e) {
      this.upload.user_image = e.target.files[0];
      axios
        .put(`/api/users/${this.userdata.id}`, this.upload, {
          headers: {
            Authorization: `Bearer ${this.auth.token}`,
            "Content-Type": "application/json",
          },
        })
        .then((response) => {
          console.log(response.data);
          // this.close();
          //  this.users.push(this.user);
          this.$toasted.show(response.data.data.message);
          //this.$router.push({ name: "users" });
          window.location.reload();
        })
        .catch((error) => {
          console.log(error);
          this.$toasted.show(error.response.data.data);
        });
    },

    updateWebhook() {
      this.form.webhook_url = this.users.webhook_url;


      axios
        .put(`/api/update-webhook`, this.form, {
          headers: {
            Authorization: `Bearer ${this.auth.token}`,
            "Content-Type": "application/json",
          },
        })
        .then((response) => {
          console.log(response);
          // this.close();
          //  this.users.push(this.user);
          this.$toasted.show(response.data.data.message);
          alert("Webhook url updated successfully");
          //this.$router.push({ name: "users" });
          window.location.reload();
        })
        .catch((error) => {
          console.log(error);
          this.$toasted.show(error.response.data.data);
        });
    },

    changePassword() {
      axios
        .post(`/api/change-password/`, this.form, {
          headers: {
            Authorization: `Bearer ${this.auth.token}`,
            "Content-Type": "application/json",
          },
        })
        .then((response) => {
          console.log(response.data);
          // this.close();
          //  this.users.push(this.user);
          this.$toasted.show(response.data.data.message);
          //this.$router.push({ name: "users" });
          window.location.reload();
        })
        .catch((error) => {
          console.log(error);
          this.$toasted.show(error.response.data.data);
        });
    },

    addBank() {
      this.form.phone = this.users.phone;
      this.form.bank_name = this.users.bank_name;
      this.form.account_number = this.users.account_number;

      axios
        .put(`/api/users/${this.userdata.id}`, this.form, {
          headers: {
            Authorization: `Bearer ${this.auth.token}`,
            "Content-Type": "application/json",
          },
        })
        .then((response) => {
          // this.close();
          //  this.users.push(this.user);
          this.$toasted.show(response.data.data.message);
          //this.$router.push({ name: "users" });
          window.location.reload();
        })
        .catch((error) => {
          console.log(error);
          this.$toasted.show(error.response.data.data);
        });
    },

    submitBvn() {
      this.bvn_errors = [];
      this.bvn_success_flag = "";
      const new_bvn_dob = "3-Jun-1994";
      if (this.bvn == "") {
        this.bvn_errors.push("BVN Number is required");
      }else if (this.bvn.length < 11 || this.bvn.length > 11 ) {
        this.bvn_errors.push("BVN Number must be exactly 11 digits");
      } else if (this.bvn_name == "") {
        this.bvn_errors.push("Name is required");
      } else {
        this.isLoading = true;
        const formData = {
          bvn: this.bvn,
          name: this.bvn_name,
          dob: new_bvn_dob,
          phone: this.users.phone,
        };

        axios
          .post(`/api/update/profile/bvn/`, formData, {
            headers: {
              Authorization: `Bearer ${this.auth.token}`,
              "Content-Type": "application/json",
            },
          })
          .then((response) => {
            console.log(response.data.data.message);
            // this.close();
            //  this.users.push(this.user);
            this.bvn_success_flag = response.data.message;
            this.$toasted.show(response.data.message);
            this.isLoading = false;
            //this.$router.push({ name: "users" });
            // window.location.reload();
            alert("Bvn verified successfully");

            //this.$router.push({ name: "users" });
            window.location.reload();
          })
          .catch((error) => {
            console.log(error);
            this.bvn_errors = error.response.data.data;
            this.$toasted.show(error.response.data.data);
            this.isLoading = false;
          });

        console.log(formData);
      }
    },
    submitNin() {
      this.nin_errors = [];
      if (this.nin == "") {
        this.nin_errors.push("NIN Number is required");
      } else if (this.nin.length < 11 || this.nin.length > 11) {
        this.nin_errors.push("NIN Number must be exactly 11 digits");
      } else {
        this.isLoading = true;
        const formData = {
          nin: this.nin,
        };
        axios
          .post(`/api/update/profile/nin/`, formData, {
            headers: {
              Authorization: `Bearer ${this.auth.token}`,
              "Content-Type": "application/json",
            },
          })
          .then((response) => {
            console.log(response.data.data.message);
            // this.close();
            //  this.users.push(this.user);
            this.nin_success_flag = response.data.message;
            this.$toasted.show(response.data.message);
            this.isLoading = false;
            //this.$router.push({ name: "users" });
            // window.location.reload();
            alert("Nin verified successfully");

            //this.$router.push({ name: "users" });
            window.location.reload();
          })
          .catch((error) => {
            console.log(error);
            this.nin_errors = error.response.data.data;
            this.$toasted.show(error.response.data.data);
            this.isLoading = false;
          });

        console.log(formData);
      }
    },
  },
};
</script>
<style scoped>
.email-verification-card {
  display: flex;
  align-items: center;
  gap: 18px;
  padding: 20px;
  margin-bottom: 22px;
  border: 1px solid;
  border-radius: 14px;
}

.email-verification-card.is-verified {
  color: #126b39;
  background: #e8f8ee;
  border-color: #b9e7ca;
}

.email-verification-card.is-unverified {
  color: #7b4d00;
  background: #fff5df;
  border-color: #f0d08d;
}

.verification-icon { font-size: 30px; }
.verification-copy { flex: 1; }
.verification-copy h4, .verification-copy p { margin: 0; }
.verification-copy p { margin-top: 5px; }

@media (prefers-color-scheme: dark) {
  .email-verification-card.is-verified {
    color: #075f2a !important;
    background: #dff5e7;
    border-color: #72bc8d;
  }

  .email-verification-card.is-verified .verification-icon,
  .email-verification-card.is-verified .verification-copy,
  .email-verification-card.is-verified h4,
  .email-verification-card.is-verified p,
  .email-verification-card.is-verified i,
  .email-verification-card.is-verified strong,
  .email-verification-card.is-verified a {
    color: #075f2a !important;
  }

  .email-verification-card.is-unverified {
    color: #7f1018 !important;
    background: #ffe5e8;
    border-color: #d77b83;
  }

  .email-verification-card.is-unverified .verification-icon,
  .email-verification-card.is-unverified .verification-copy,
  .email-verification-card.is-unverified h4,
  .email-verification-card.is-unverified p,
  .email-verification-card.is-unverified i,
  .email-verification-card.is-unverified strong,
  .email-verification-card.is-unverified a,
  .email-verification-card.is-unverified button {
    color: #7f1018 !important;
  }

  .email-verification-card.is-unverified button {
    background: #fff5f6 !important;
    border-color: #9f0711 !important;
  }

  .profile-email-status.is-verified,
  .profile-email-status.is-verified .badge {
    color: #075f2a !important;
  }

  .profile-email-status.is-verified .badge {
    background: #dff5e7 !important;
    border: 1px solid #72bc8d;
  }

  .profile-email-status.is-unverified,
  .profile-email-status.is-unverified small,
  .profile-email-status.is-unverified .badge,
  .profile-email-status.is-unverified button {
    color: #7f1018 !important;
  }

  .profile-email-status.is-unverified .badge,
  .profile-email-status.is-unverified button {
    background: #fff5f6 !important;
    border-color: #9f0711 !important;
  }
}

@media (max-width: 700px) {
  .email-verification-card {
    align-items: flex-start;
    flex-direction: column;
  }
}

.bvn_style,
.nin_style {
  padding: 0 20px 20px;
}

.transaction-pin-card {
  border: 1px solid rgba(227, 6, 19, 0.2);
  border-radius: 16px;
  overflow: hidden;
}

.transaction-pin-heading {
  display: flex;
  align-items: center;
  gap: 14px;
  margin-bottom: 20px;
}

.transaction-pin-heading > div:nth-child(2) { flex: 1; }
.transaction-pin-icon {
  width: 48px;
  height: 48px;
  display: grid;
  place-items: center;
  border-radius: 14px;
  color: #e30613;
  background: #ffedef;
  font-size: 21px;
}

.transaction-pin-form {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 14px;
  align-items: end;
  margin-bottom: 22px;
}

.transaction-pin-reset .transaction-pin-form { margin-bottom: 8px; }

.transaction-pin-form .form-group { margin: 0; }
.transaction-pin-form input { letter-spacing: 8px; font-weight: 800; }
.transaction-pin-form button { min-height: 46px; }

@media (max-width: 800px) {
  .transaction-pin-form { grid-template-columns: 1fr; }
  .transaction-pin-heading { align-items: flex-start; flex-wrap: wrap; }
}

label {
  font-weight: bold;
  margin-top:10px;
}
.m {
    margin-top: 20px;
    margin-bottom: 20px;
  }
</style>
