<template>
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
          <form @submit.prevent="verifyotp" class="forms-sample">
            <div class="row">
              <div class="col-md-6">
                <div class="form-group">
                  <label for="phone">Enter OTP</label>
                  <input
                    type="text"
                    class="form-control"
                    v-model="form.otp"
                    required
                    id="otp"
                    placeholder="Enter otp"
                  />
                </div>
              </div>
            </div>
            <button type="submit" class="btn btn-danger me-2">{{ loading ? "Processing..." : "Proceed"}}</button>
          </form>
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
      form: {},
      loading: false,
      successflag: "",
      errorflag: "",
    };
  },

  mounted() {
    window.addEventListener("beforeunload", this.preventUnload);
  },
  beforeUnmount() {
    window.removeEventListener("beforeunload", this.preventUnload);
  },
  created() {
    this.form = this.$route.params.postData;
  },

  methods: {
    preventUnload(event) {
      event.preventDefault();
      event.returnValue = ""; // Required for Chrome
    },
    verifyotp() {
        this.loading = true
      axios
        .post(`/api/purchase/a2cash`, this.form, {
          headers: {
            Authorization: `Bearer ${token}`,
            "Content-Type": "application/json",
          },
        })
        .then((response) => {
          //console.log(response.data);
          this.loading = false;
          this.successflag = response.data.message;
          this.$toasted.show(response.data.message);
          if(response.data.message === "Wrong phone number or verification code."){
            this.$router.push({ name: 'airtime2cash', params: { message: response.data.message, status: "404" } });
          }else if(response.data.success){
            this.$router.push({ name: 'airtime2cash', params: { message: response.data.message, status: "0000" } });
          }

        })
        .catch((error) => {
          // console.log(error);
          this.loading= false;
          this.errorflag = error.response.data.message;
          this.$toasted.show(error.response.data.message);
          this.$router.push({ name: 'airtime2cash', params: { message: error.response.data.message, status: "404" } });
        });
    },
  },
};
</script>

  <style scoped>
img {
  width: 9vw;
  height: 9vw;
  padding: 5px;
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
#code {
  font-size: 8pt;
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
