<template>
  <div>
    <modal :show="show" @close="close">
      <div class="card">
        <div class="card-body">
          <h4 class="card-title">Withdraw</h4>
          <form @submit.prevent="addProduct" class="forms-sample">
            <div class="form-group">
              <label for="Amount">Amount</label>
              <input
                type="number"
                class="form-control"
                v-model="withdrawal.amount"
                placeholder="Amount"
              />
            </div>

            <input type="hidden" name="user_id" v-model="withdrawal.user_id" />

            <button type="submit" class="btn btn-danger me-2">Submit</button>
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
  props: ["show"],
  components: { Modal },
  data() {
    return {
      withdrawal: {},
      user: this.auth.user,
    };
  },

  methods: {
    close: function () {
      this.$emit("close");
    },

    addWithdrawal() {
      this.withdrawal.user_id = this.user.id;

      if (user.wallet < this.withdrawal.amount) {
        this.close();
        this.$toasted.show("You cannot withdraw more than you have");
      } else if (this.withdrawal.amount <= 0) {
        this.close();
        this.$toasted.show("Invalid Amount requested");
      } else {
        axios
          .post(`/api/withdrawals`, this.withdrawal, {
            headers: {
              Authorization: `Bearer ${token}`,
              "Content-Type": "application/json",
            },
          })
          .then((response) => {
            console.log(response.data);
            this.close();
            this.$toasted.show(response.data.message);
            window.location.reload();

            //this.$router.push({ name: "users" });
          })
          .catch((error) => {
            console.log(error);
            this.$toasted.show(error);
          });
      }
    },
  },
};
</script>