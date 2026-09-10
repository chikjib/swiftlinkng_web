<template>
  <div>
    <div class="row">
      <div class="col-xl-6 col-lg-6">
        <div class="card mb-6">
          <div class="card-body">
            <h4 class="card-title">Current Purchase Mode: <strong>{{ users.purchase_mode}}</strong></h4>
            <hr />
            <div>
              <p>
                <input
                  type="radio"
                  id="bvn"
                  name="purchase_mode"
                  value="wallet"
                  v-model="picked"
                  @click="changeMode('wallet')"
                />
                Wallet
              </p>

          </div>

          <div>
            <p>
              <input
                type="radio"
                id="bvn"
                name="purchase_mode"
                value="bucket"
                v-model="picked"
                @click="changeMode('bucket')"
              />
              Bucket
            </p>

        </div>
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
      users: {},
      picked: "",
      userdata: this.auth.user,
      form: {
        purchase_mode: ""
      }

    };
  },

  mounted() {
    this.getusers();
    console.log(this.picked)
  },

  methods: {
   changeMode(mode) {
    this.loading = true;
      this.form.purchase_mode = mode;
      console.log(this.form)
      if (confirm("Do you really want to change your purchase mode to " + mode + "?")) {
        axios
          .put(`/api/update-mode/user/${this.userdata.id}`, this.form, {
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
          this.picked = this.users.purchase_mode;
        });
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
.m {
  margin-top: 20px;
  margin-bottom: 20px;
}
</style>
