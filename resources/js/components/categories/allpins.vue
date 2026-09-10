<template>
  <div>
    <div class="row">
      <div class="col-lg-12">
        <div class="card">
          <div class="card-body">
            <h4 class="card-title">{{ this.$route.params.title }} Pins</h4>
            <div class="row">
              <div class="col-md-4"></div>
              <div class="col-md-4"></div>
            </div>
            <hr />
            <div class="col-md-12">
              <h5>Add Pin</h5>
              <br />
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
              <form
                @submit.prevent="addPin"
                class="forms-sample"
                v-if="this.$route.params.title == 'WAEC'"
              >
                <!-- <div class="form-group">
                    <label for="file">Upload Image</label>
                    <input class="form-control" type="file" v-on:change="onChange" />
                  </div> -->
                <div class="form-group">
                  <label for="title">Pin No</label>
                  <input
                    type="text"
                    class="form-control"
                    v-model="pin_no"
                    id="Pin No"
                    placeholder="Pin No"
                  />
                </div>

                <div class="form-group">
                  <label for="serial_no">Serial No</label>
                  <input
                    type="text"
                    class="form-control"
                    v-model="serial_no"
                    placeholder="Serial No"
                  />
                </div>

                <div class="form-group">
                  <label for="variation_code">Variation Code</label>
                  <input
                    type="text"
                    class="form-control"
                    v-model="variation_code"
                    placeholder="variation_code"
                  />
                </div>

                <button
                  :disabled="loading"
                  type="submit"
                  class="btn btn-danger me-2"
                >
                  Submit
                </button>
              </form>
              <form
                @submit.prevent="addPin"
                class="forms-sample"
                v-if="this.$route.params.title == 'NECO'"
              >
                <!-- <div class="form-group">
                    <label for="file">Upload Image</label>
                    <input class="form-control" type="file" v-on:change="onChange" />
                  </div> -->
                <div class="form-group">
                  <label for="title">Pin No</label>
                  <input
                    type="text"
                    class="form-control"
                    v-model="pin_no"
                    id="Pin No"
                    placeholder="Pin No"
                  />
                </div>
                <div class="form-group">
                    <label for="variation_code">Variation Code</label>
                    <input
                      type="text"
                      class="form-control"
                      v-model="variation_code"
                      placeholder="variation_code"
                    />
                  </div>

                <button
                  :disabled="loading"
                  type="submit"
                  class="btn btn-danger me-2"
                >
                  Submit
                </button>
              </form>
              <form
                @submit.prevent="addPin"
                class="forms-sample"
                v-else-if="this.$route.params.title == 'JAMB'"
              >
                <!-- <div class="form-group">
                    <label for="file">Upload Image</label>
                    <input class="form-control" type="file" v-on:change="onChange" />
                  </div> -->
                <div class="form-group">
                  <label for="title">Pin No</label>
                  <input
                    type="text"
                    class="form-control"
                    v-model="pin_no"
                    id="Pin No"
                    placeholder="Pin No"
                  />
                </div>
                <div class="form-group">
                    <label for="variation_code">Variation Code</label>
                    <input
                      type="text"
                      class="form-control"
                      v-model="variation_code"
                      placeholder="variation_code"
                    />
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

            <div class="table-responsive pt-3">
              <table class="table table-bordered">
                <thead>
                  <tr v-if="this.$route.params.title == 'WAEC'">
                    <th>ID</th>
                    <th>Pin No</th>
                    <th>Serial No</th>
                    <th>Variation Code</th>
                    <th>Used</th>
                    <th>Created At</th>
                    <th>Actions</th>
                  </tr>
                  <tr v-if="this.$route.params.title == 'NECO'">
                    <th>ID</th>
                    <th>Pin No</th>
                    <th>Variation Code</th>
                    <th>Used</th>
                    <th>Created At</th>
                    <th>Actions</th>
                  </tr>
                  <tr v-else-if="this.$route.params.title == 'JAMB'">
                    <th>ID</th>
                    <th>Pin No</th>
                    <th>Variation Code</th>
                    <th>Used</th>
                    <th>Created At</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody v-if="this.$route.params.title == 'WAEC'">
                  <tr v-for="pin in pins.data" :key="pin.id">
                    <td>
                      {{ pin.id }}
                    </td>
                    <td>
                      {{ pin.pin_no }}
                    </td>
                    <td>{{ pin.serial_no }}</td>
                    <td>{{ pin.variation_code }}</td>
                    <td>
                      <label class="badge badge-success" v-if="pin.used == '0'"
                        >No</label
                      >

                      <label class="badge badge-danger" v-else>Yes</label>
                    </td>
                    <td>{{ pin.created_at }}</td>
                    <td>
                      <div class="btn-group" role="group">
                        <a :href="'/dashboard/edit-pins/' + pin.id + '/' + encodeURIComponent($route.params.title || '')" class="btn btn-danger">Edit</a>

                        <button
                          class="btn btn-danger"
                          @click="deletePin(pin.id)"
                        >
                          Delete
                        </button>
                      </div>
                    </td>
                  </tr>
                </tbody>
                <tbody v-if="this.$route.params.title == 'NECO'">
                  <tr v-for="pin in pins.data" :key="pin.id">
                    <td>
                      {{ pin.id }}
                    </td>
                    <td>
                      {{ pin.pin_no }}
                    </td>
                    <td>{{ pin.variation_code }}</td>
                    <td>
                      <label class="badge badge-success" v-if="pin.used == '0'"
                        >No</label
                      >

                      <label class="badge badge-danger" v-else>Yes</label>
                    </td>
                    <td>{{ pin.created_at }}</td>
                    <td>
                      <div class="btn-group" role="group">
                        <a :href="'/dashboard/edit-pins/' + pin.id + '/' + encodeURIComponent($route.params.title || '')" class="btn btn-danger">Edit</a>

                        <button
                          class="btn btn-danger"
                          @click="deletePin(pin.id)"
                        >
                          Delete
                        </button>
                      </div>
                    </td>
                  </tr>
                </tbody>
                <tbody v-else-if="this.$route.params.title == 'JAMB'">
                  <tr v-for="pin in pins.data" :key="pin.id">
                    <td>
                      {{ pin.id }}
                    </td>
                    <td>
                      {{ pin.pin_no }}
                    </td>
                    <td>{{ pin.variation_code }}</td>
                    <td>
                      <label class="badge badge-success" v-if="pin.used == '0'"
                        >No</label
                      >

                      <label class="badge badge-danger" v-else>Yes</label>
                    </td>
                    <td>{{ pin.created_at }}</td>
                    <td>
                      <div class="btn-group" role="group">
                        <a :href="'/dashboard/edit-pins/' + pin.id + '/' + encodeURIComponent($route.params.title || '')" class="btn btn-danger">Edit</a>

                        <button
                          class="btn btn-danger"
                          @click="deletePin(pin.id)"
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
              :data="pins"
              @pagination-change-page="list"
            ></pagination>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

  <script>
  const token = window.localStorage.getItem("token");

  export default {
    watch: {
      keyword(after, before) {
        this.getResults();
      },
    },
    data() {
      return {
        pins: {},
        pin_no: null,
        serial_no: null,
        variation_code: null,
        keyword: null,
        loading: false,
        successflag: "",
        errorflag: "",
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
          .get(`/api/pins?title=${this.$route.params.title}&page=${page}`, {
            headers: {
              Authorization: `Bearer ${token}`,
              "Content-Type": "application/json",
            },
          })
          .then(({ data }) => {
            console.log(data)
            this.pins = data;
          })
          .catch(({ response }) => {
            this.$toasted.show(error.response.data.data);
          });
      },

      addPin(){
        if(this.$route.params.title == "WAEC"){
            var formData = {
                "title": this.$route.params.title,
                "pin_no": this.pin_no,
                "serial_no": this.serial_no,
                "variation_code": this.variation_code
            }
        }else if(this.$route.params.title == "NECO"){
            var formData = {
                "title": this.$route.params.title,
                "pin_no": this.pin_no,
                "variation_code": this.variation_code
            }
        }else if(this.$route.params.title == "JAMB"){
            var formData = {
                "title": this.$route.params.title,
                "pin_no": this.pin_no,
                "variation_code": this.variation_code
            }
        }
        console.log(formData)
        axios
            .post(`/api/add-pins`, formData, {
              headers: {
                Authorization: `Bearer ${token}`,
                "Content-Type": "application/json",
              },
            })
            .then((response) => {
                console.log(response);
              this.$toasted.show(response.data.message);
              this.$router.go(this.$router.currentRoute)
            })
            .catch(({ response }) => {
                console.log(response)
            this.$toasted.show(response.data.message);
          });
      },

      deletePin(id) {
        if (confirm("Do you really want to delete?")) {
          axios
            .get(`/api/delete-pins/${id}/${this.$route.params.title}`, {
              headers: {
                Authorization: `Bearer ${token}`,
                "Content-Type": "application/json",
              },
            })
            .then((response) => {
              let i = this.pins.data.map((item) => item.id).indexOf(id); // find index of object
              this.pins.data.splice(i, 1);
            });
        }
      },

      getResults(page) {
        if (typeof page === "undefined") {
          page = 1;
        }

        axios
          .get(`/api/subcategory?search=${this.keyword}&page=${page}`, {
            headers: {
              Authorization: `Bearer ${token}`,
              "Content-Type": "application/json",
            },
          })
          .then((response) => {
            console.log(response.data);
            this.categories = response.data;

            // this.isloading = false;
          });
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
