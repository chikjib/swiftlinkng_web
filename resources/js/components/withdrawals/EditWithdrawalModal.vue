<template>
  <div>
    <modal :show="show" @close="close">
      <div class="card">
        <div class="card-body">
          <h4 class="card-title">Edit Withdrawal</h4>
          <form @submit.prevent="updateWithdrawal" class="forms-sample">
            <div class="form-group">
              <label for="Price">Status</label>
              <select class="form-control" v-model="withdrawal.status">
                <option value="1">Approve</option>
                <option value="0">Disapprove</option>
              </select>
            </div>

            <button
              :disabled="loading"
              type="submit"
              class="btn btn-primary me-2"
            >
              Submit
            </button>
          </form>
        </div>
      </div>
    </modal>
  </div>
</template>

<script>
import Modal from "../modal.vue";
const token = window.localStorage.getItem("token");

export default {
  props: ["show", "withdrawal"],
  components: { Modal },
  data() {
    return {
      loading: false,
      //      user: this.$store.state.auth.user,
    };
  },

  methods: {
    close: function () {
      this.$emit("close");
    },

    updateWithdrawal() {
      this.loading = true;

      axios
        .put(`/api/withdrawals/${this.withdrawal.id}`, this.withdrawal, {
          headers: {
            Authorization: `Bearer ${token}`,
            "Content-Type": "application/json",
          },
        })
        .then((response) => {
          console.log(response.data);
          this.loading = false;

          this.close();
          this.$toasted.show(response.data.message);
          window.location.reload();
          //this.$router.push({ name: "users" });
        });
    },
  },
};
</script>