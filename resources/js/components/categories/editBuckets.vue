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
            <form @submit.prevent="updateBucket" class="forms-sample">
              <!-- <div class="form-group">
                <label for="file">Upload Image</label>
                <input class="form-control" type="file" v-on:change="onChange" />
              </div> -->
              <div class="form-group">
                <label for="title">Title</label>
                <input
                  type="text"
                  class="form-control"
                  v-model="bucket.title"
                  id="title"
                  placeholder="Title"
                />
              </div>

              <div class="form-group">
                <label for="category">Category</label>
                <input
                  type="text"
                  class="form-control"
                  v-model="bucket.category.title"
                  readonly
                />
              </div>

              <div class="form-group">
                <label for="Description">Description</label>
                <input
                  type="text"
                  class="form-control"
                  v-model="bucket.description"
                  id="description"
                  placeholder="Description"
                />
              </div>

              <div class="form-group">
                <label for="Note">Note</label>
                <input
                  type="text"
                  class="form-control"
                  v-model="bucket.description2"
                  id="Note"
                  placeholder="Note"
                />
              </div>

              <div class="form-group">
                <label for="Pins">Pins/Code</label>
                <p class="text-warning">
                  Please Edit Exactly in the format shown below or keep a copy of
                  this incase of error
                </p>
                <input
                  type="text"
                  class="form-control"
                  v-model="bucket.pins"
                  id="pins"
                  placeholder="Pins"
                />
              </div>

              <div class="form-group">
                <label for="telegram">Telegram ID</label>
                <input
                  type="text"
                  class="form-control"
                  v-model="bucket.telegram"
                  id="telegram"
                  placeholder="telegram id"
                />
              </div>
              <div class="form-group">
                <label for="products">Price Per GB</label>
                <p class="text-warning">
                  Please Edit Exactly in the format shown below or keep a copy of
                  this incase of error
                </p>
                <textarea
                  rows="5"
                  cols="10"
                  class="form-control"
                  v-model="bucket.price_per_gb"
                ></textarea>
              </div>


              <div class="form-group">
                <label for="products">Products</label>
                <p class="text-warning">
                  Please Edit Exactly in the format shown below or keep a copy of
                  this incase of error
                </p>
                <textarea
                  rows="5"
                  cols="10"
                  class="form-control"
                  v-model="bucket.products"
                ></textarea>
              </div>

              <div class="form-group">
                <label for="Price">Status</label>
                <select class="form-control" v-model="bucket.status">
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
      </div>
    </div>
  </template>

  <script>
  const token = window.localStorage.getItem("token");

  export default {
    data() {
      return {
        bucket: {},
        // form: {},
        loading: false,
        successflag: "",
        errorflag: "",
      };
    },

    created() {
      axios
        .get(`/api/bucket/${this.$route.params.id}`, {
          headers: {
            Authorization: `Bearer ${token}`,
            "Content-Type": "application/json",
          },
        })
        .then((response) => {
          console.log(response.data);

          this.bucket = response.data.data;
        });
    },

    methods: {


      updateBucket() {
        this.loading = true;

        console.log(this.bucket);

        // this.form.category_id = this.bucket.category.id;
        // this.form.subcategory_id = this.bucket.subcategory_id;
        // this.form.status = this.bucket.status;

        axios
          .put(`/api/update-bucket/${this.bucket.id}`, this.bucket, {
            headers: {
              Authorization: `Bearer ${token}`,
              "Content-Type": "application/json",
            },
          })
          .then((response) => {
            // console.log(response.data);
            this.loading = false;

            location.reload;
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
