<template>
  <div>
    <modal :show="show" @close="close">
      <div class="card">
        <div class="card-body">
          <h4 class="card-title">Edit Commission</h4>
          <form @submit.prevent="updateCommission" class="forms-sample">
            <div class="form-group">
              <label for="Price">Status</label>
              <select class="form-control" v-model="commission.status">
                <option value="1">Approve</option>
                <option value="0">Disapprove</option>
              </select>
            </div>

            <input type="hidden" class="form-control" v-model="commission.id" />

            <input
              type="hidden"
              class="form-control"
              v-model="commission.user.id"
            />

            <button
              :disabled="loading"
              type="submit"
              class="btn btn-danger me-2"
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
  props: ["show", "commission"],
  components: { Modal },
  data() {
    return {
      loading: false,
      //      user: this.$store.state.auth.user,
    };
  },
  //   created() {
  //     axios
  //       .get(`${this.$appUrl}/api/users/${this.$route.params.id}`, {
  //         headers: {
  //           Authorization: `Bearer ${token}`,
  //           "Content-Type": "application/json",
  //         },
  //       })
  //       .then((response) => {
  //         console.log(response.data);

  //         this.user = response.data.data;
  //       });
  //   },
  methods: {
    close: function () {
      this.$emit("close");
    },

    updateCommission() {
      this.loading = true;

      this.commission.user_id = this.commission.user.id;

      axios
        .put(
          `${this.$appUrl}/api/commissions/${this.commission.id}`,
          this.commission,
          {
            headers: {
              Authorization: `Bearer ${token}`,
              "Content-Type": "application/json",
            },
          }
        )
        .then((response) => {
          this.loading = false;

          console.log(response.data);
          this.close();
          this.$toasted.show(response.data.message);
          window.location.reload();
          //this.$router.push({ name: "users" });
        });
    },
  },
};
</script>