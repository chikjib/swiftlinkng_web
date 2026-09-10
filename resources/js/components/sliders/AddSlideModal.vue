<template>
  <div>
    <modal :show="show" @close="close">
      <div class="card">
        <div class="card-body">
          <h4 class="card-title">Add Slider</h4>
          <form
            @submit.prevent="uploadPhoto"
            enctype="multipart/form-data"
            class="forms-sample"
          >
            <div class="form-group">
              <label for="file">Upload Image</label>
              <input class="form-control" type="file" v-on:change="onChange" />
            </div>

            <div class="form-group">
              <label for="Description">Description</label>
              <input
                type="text"
                class="form-control"
                v-model="product.description"
                id="description"
                placeholder="Description"
              />
            </div>

            <div class="form-group">
              <label for="Price">Status</label>
              <select class="form-control" v-model="product.status">
                <option value="1">Approve</option>
                <option value="0">Disapprove</option>
              </select>
            </div>

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
  props: ["show"],
  components: { Modal },
  data() {
    return {
      product: {},
      loading: false,

      category_id: null,
      subcategory_id: null,
      categories: [],
      subcategories: [],
    };
  },

  methods: {
    close: function () {
      this.$emit("close");
    },

    onChange(e) {
      this.product.cat_image = e.target.files[0];
    },
    uploadPhoto() {
      // e.preventDefault();
      // let existingObj = this;
      const config = {
        headers: {
          Authorization: `Bearer ${token}`,
          "Content-Type": "multipart/form-data",
        },
      };
      let data = new FormData();
      data.append("cat_image", this.product.cat_image);
      data.append("description", this.product.description);

      // console.log(this.upload.user_image);
      axios
        .post(`/api/admin/slides/uploadimage`, data, config)
        .then(function (res) {
          console.log(res.data.message);
          //this.uploadPhoto();
          //this.$router.go();
          window.location.reload();
        })
        .catch(function (err) {
          this.$toasted.show(err.response.data.data);
        });
    },

    addCategory() {
      this.loading = true;

      axios
        .post(`/api/admin/slides`, this.product, {
          headers: {
            Authorization: `Bearer ${token}`,
            "Content-Type": "application/json",
          },
        })
        .then((response) => {
          // console.log(response.data);
          this.loading = false;

          this.close();
          //location.reload;
          this.uploadPhoto();

          this.$toasted.show(response.data.message);
          //this.$router.push({ name: "users" });
        })
        .catch((error) => {
          console.log(error);
          this.loading = false;

          this.$toasted.show(error.response.data.data);
        });
    },
  },
};
</script>