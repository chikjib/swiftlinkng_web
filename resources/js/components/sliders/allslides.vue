<template>
  <div>
    <div class="row">
      <div class="col-lg-12">
        <div class="card">
          <div class="card-body">
            <h4 class="card-title">All Slides</h4>
            <div class="col-md-4">
              <button
                type="button"
                href="#"
                @click.stop="toggleModal('addcategory')"
                class="btn btn-danger btn-rounded btn-fw"
              >
                Add Slide
              </button>
            </div>

            <add-slide-modal
              :show="showModal('addcategory')"
              @close="toggleModal('addcategory')"
            />
            <div class="table-responsive pt-3">
              <table class="table table-bordered">
                <thead>
                  <tr>
                    <th>ID</th>
                    <th>Image</th>

                    <th>Description</th>
                    <th>Status</th>

                    <th>Created At</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="product in categories.data" :key="product.id">
                    <td>{{ product.id }}</td>

                    <td>
                      <img
                        class="img-sm rounded-10"
                        height="40px"
                        width="100px"
                        :src="
                          product.cat_image != null
                            ? '/storage/category/' + product.cat_image
                            : '/template/img/noimage.png'
                        "
                        alt="profile"
                      />
                    </td>

                    <td>{{ product.description }}</td>

                    <td>
                      <label
                        class="badge badge-success"
                        v-if="product.status == true"
                        >Active</label
                      >

                      <label class="badge badge-danger" v-else>InActive</label>
                    </td>
                    <td>{{ product.created_at }}</td>
                    <td>
                      <div class="btn-group" role="group">
                        <a
                          class="btn btn-danger"
                          href="#"
                          @click.stop="toggleModal(product.id)"
                          >Edit</a
                        >

                        <edit-slides-modal
                          :show="showModal(product.id)"
                          :slides="product"
                          @close="toggleModal(product.id)"
                        />

                        <button
                          class="btn btn-danger"
                          @click="deleteCategory(product.id)"
                        >
                          Delete
                        </button>
                      </div>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <pagination
              align="center"
              :data="categories"
              @pagination-change-page="list"
            ></pagination>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
 
<script>
import AddSlideModal from "./AddSlideModal.vue";
import EditSlidesModal from "./EditSlidesModal.vue";

//
const token = window.localStorage.getItem("token");

export default {
  components: {
    AddSlideModal,
    EditSlidesModal,
  },

  data() {
    return {
      // product: {},
      categories: {},
      category_id: 0,

      activeModal: 0,
    };
  },

  mounted() {
    this.list();
  },
  methods: {
    list(page) {
      if (typeof page === "undefined") {
        page = 1;
      }
      axios
        .get(`/api/admin/slides?page=${page}`, {
          headers: {
            Authorization: `Bearer ${token}`,
            "Content-Type": "application/json",
          },
        })
        .then(({ data }) => {
          this.categories = data;
        })
        .catch(({ response }) => {
          this.$toasted.show(error.response.data.data);
        });
    },

    deleteCategory(id) {
      if (confirm("Do you really want to delete?")) {
        axios
          .delete(`/api/admin/slides/${id}`, {
            headers: {
              Authorization: `Bearer ${token}`,
              "Content-Type": "application/json",
            },
          })
          .then((response) => {
            let i = this.categories.data.map((item) => item.id).indexOf(id); // find index of object
            this.categories.data.splice(i, 1);

            this.$toasted.show(response.data.message);
          });
      }
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
  },
};
</script>

<style scoped>
.pagination {
  margin-bottom: 0;
  margin-top: 5;
}
</style>