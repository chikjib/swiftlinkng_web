<template>
  <div class="col-xl-12 col-lg-12">
      <div class="row">
        <div class="card mb-4 col-md-6">
            <br/>
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
                  type="text"
                  name="bvn"
                  id="bvn"
                  v-model="bvn"
                  @keydown="validateInput"
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
                <button
                  v-if="isLoading"
                  disabled
                  id="bvn"
                  class="btn btn-danger"
                >
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
                  type="text"
                  name="nin"
                  id="nin"
                  @keydown="validateInput"
                  v-model="nin"
                  class="form-control"
                />
              </div>
              <div class="form-group">
                <button
                  v-if="isLoading"
                  disabled
                  id="nin"
                  class="btn btn-danger"
                >
                  Processing...
                </button>
                <button v-else type="submit" id="nin" class="btn btn-danger">
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
</template>

  <script>
const token = window.localStorage.getItem("token");
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

      upload: {
        user_image: null,
      },

      userdata: this.auth.user,

      activeModal: 0,
    };
  },

  mounted() {
    this.getusers();
    // this.getProd();
  },

  methods: {
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

    getusers() {
      axios
        .get(`/api/users/${this.userdata.id}`, {
          headers: {
            Authorization: `Bearer ${token}`,
            "Content-Type": "application/json",
          },
        })
        .then((response) => {
          console.log(response.data.data);
          this.users = response.data.data;
        });
    },

    validateInput(event) {
      if (
        event.shiftKey &&
        ((event.keyCode >= 48 && event.keyCode <= 57) ||
          (event.keyCode >= 186 && event.keyCode <= 222))
      ) {
        // Ensure that it is a number and stop the Special chars
        event.preventDefault();
      } else if (
        (event.shiftKey || event.ctrlKey) &&
        event.keyCode > 34 &&
        event.keyCode < 40
      ) {
        // let it happen, don't do anything
      } else {
        // Allow only backspace , delete, numbers
        if (
          event.keyCode == 9 ||
          event.keyCode == 46 ||
          event.keyCode == 8 ||
          event.keyCode == 39 ||
          event.keyCode == 37 ||
          (event.keyCode >= 48 && event.keyCode <= 57)
        ) {
          // let it happen, don't do anything
        } else {
          // Ensure that it is a number and stop the key press
          event.preventDefault();
        }
      }
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
              Authorization: `Bearer ${token}`,
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
              Authorization: `Bearer ${token}`,
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
.bvn_style,
.nin_style {
  padding: 0 20px 20px;
}

label {
  font-weight: bold;
  margin-top: 10px;
}
</style>
