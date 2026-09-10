<template>
  <div>
    <modal :show="show" @close="close">
      <div class="card">
        <div class="card-body">
          <h4 class="card-title">Fund Wallet</h4>
          <form @submit.prevent="addfund" class="forms-sample">
            <div class="form-group">
              <label for="amount">Amount</label>
              <input
                type="number"
                class="form-control"
                v-model="amount"
                id="amount"
                placeholder="Amount"
              />
            </div>
            <button type="submit" class="btn btn-danger me-2">Proceed</button>
          </form>
        </div>
      </div>
    </modal>
  </div>
</template>

<script>
const token = window.localStorage.getItem("token");
import Modal from "../modal.vue";
export default {
  props: ["show", "users"],
  components: { Modal },
  data() {
    return {
      user: {},
    };
  },

  methods: {
    close: function () {
      this.$emit("close");
    },

    addUser() {
      axios
        .post(`/api/fund`, this.user, {
          headers: {
            Authorization: `Bearer ${token}`,
            "Content-Type": "application/json",
          },
        })
        .then((response) => {
          console.log(response.data);
          this.close();
          //  this.users.push(this.user);
          this.$toasted.show(response.data.message);
          //this.$router.push({ name: "users" });
        })
        .catch((error) => {
          console.log(error);
          this.$toasted.show(error);
        });
    },

    refreshlist() {
      if (typeof page === "undefined") {
        page = 1;
      }
      axios
        .get(`/api/users?page=${page}`, {
          headers: {
            Authorization: `Bearer ${token}`,
            "Content-Type": "application/json",
          },
        })
        .then((response) => {
          console.log(response.data);
          this.users = response.data;
        });
    },
  },
};
</script>