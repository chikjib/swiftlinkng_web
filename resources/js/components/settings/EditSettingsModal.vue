<template>
  <div>
    <modal :show="show" @close="close">
      <div class="card">
        <div class="card-body">
          <h4 class="card-title">Edit Setting</h4>
          <form @submit.prevent="updateSettings" class="forms-sample">
            <div class="form-group">
              <label for="value">Value</label>
              <textarea rows="10" cols="30" v-model="setting.value"></textarea>
            </div>

            <input type="hidden" v-model="setting.id" id="user_id" />

            <button type="submit" class="btn btn-danger me-2">Submit</button>
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
  props: ["show", "setting"],
  components: { Modal },
  data() {
    return {
      //      user: this.$store.state.auth.user,
    };
  },

  methods: {
    close: function () {
      this.$emit("close");
    },

    updateSettings() {
      axios
        .put(`/api/admin/settings/${this.setting.id}`, this.setting, {
          headers: {
            Authorization: `Bearer ${token}`,
            "Content-Type": "application/json",
          },
        })
        .then((response) => {
          //console.log(response.data);
          this.close();
          this.$toasted.show(response.data.message);
          window.location.reload();
          //this.$router.push({ name: "users" });
        });
    },
  },
};
</script>