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
            <form @submit.prevent="updatePin" class="forms-sample" v-if="this.$route.params.title=='WAEC'">
              <!-- <div class="form-group">
                <label for="file">Upload Image</label>
                <input class="form-control" type="file" v-on:change="onChange" />
              </div> -->
              <div class="form-group">
                <label for="pin_no">Pin No.</label>
                <input
                  type="text"
                  class="form-control"
                  v-model="pin.pin_no"
                  id="pin_no"
                  placeholder="Pin no"
                />
              </div>
              <div class="form-group">
                <label for="serial_no">Serial No.</label>
                <input
                  type="text"
                  class="form-control"
                  v-model="pin.serial_no"
                  id="serial_no"
                  placeholder="Serial No"
                />
              </div>
              <div class="form-group">
                <label for="variation_code">Variation Code</label>
                <input
                  type="text"
                  class="form-control"
                  v-model="pin.variation_code"
                  id="variation_code"
                  placeholder="Variation Code"
                />
              </div>


              <div class="form-group">
                <label for="used">Used</label>
                <select class="form-control" v-model="pin.used">
                  <option value="1">Used</option>
                  <option value="0">Unused</option>
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
            <form @submit.prevent="updatePin" class="forms-sample" v-if="this.$route.params.title=='NECO'">
              <!-- <div class="form-group">
                <label for="file">Upload Image</label>
                <input class="form-control" type="file" v-on:change="onChange" />
              </div> -->
              <div class="form-group">
                <label for="pin_no">Pin No.</label>
                <input
                  type="text"
                  class="form-control"
                  v-model="pin.pin_no"
                  id="pin_no"
                  placeholder="Pin no"
                />
              </div>

              <div class="form-group">
                <label for="variation_code">Variation Code</label>
                <input
                  type="text"
                  class="form-control"
                  v-model="pin.variation_code"
                  id="variation_code"
                  placeholder="Variation Code"
                />
              </div>

              <div class="form-group">
                <label for="used">Used</label>
                <select class="form-control" v-model="pin.used">
                  <option value="1">Used</option>
                  <option value="0">Unused</option>
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
            <form @submit.prevent="updatePin" class="forms-sample" v-else-if="this.$route.params.title=='JAMB'">
              <!-- <div class="form-group">
                <label for="file">Upload Image</label>
                <input class="form-control" type="file" v-on:change="onChange" />
              </div> -->
              <div class="form-group">
                <label for="pin_no">Pin No.</label>
                <input
                  type="text"
                  class="form-control"
                  v-model="pin.pin_no"
                  id="pin_no"
                  placeholder="Pin no"
                />
              </div>
              <div class="form-group">
                <label for="variation_code">Variation Code</label>
                <input
                  type="text"
                  class="form-control"
                  v-model="pin.variation_code"
                  id="variation_code"
                  placeholder="Variation Code"
                />
              </div>

              <div class="form-group">
                <label for="used">Used</label>
                <select class="form-control" v-model="pin.used">
                  <option value="1">Used</option>
                  <option value="0">Unused</option>
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
        pin: {},
        loading: false,
        successflag: "",
        errorflag: "",
      };
    },

    created() {
      axios
        .get(`/api/pins?id=${this.$route.params.id}&title=${this.$route.params.title}`, {
          headers: {
            Authorization: `Bearer ${token}`,
            "Content-Type": "application/json",
          },
        })
        .then((response) => {
          console.log(response.data.data);

          this.pin = response.data.data[0];
        });
    },

    methods: {

      updatePin() {
        this.loading = true;
        if(this.$route.params.title == "WAEC"){
            var formData = {
                "title": this.$route.params.title,
                "pin_no": this.pin.pin_no,
                "serial_no": this.pin.serial_no,
                "variation_code": this.pin.variation_code
            }
        }else  if(this.$route.params.title == "NECO"){
            var formData = {
                "title": this.$route.params.title,
                "pin_no": this.pin.pin_no,
                "variation_code": this.pin.variation_code
            }
        }else if(this.$route.params.title == "JAMB"){
            var formData = {
                "title": this.$route.params.title,
                "pin_no": this.pin.pin_no,
                "variation_code": this.pin.variation_code
            }
        }
        console.log(formData);
        axios
          .put(`/api/update-pin/${this.$route.params.id}`, formData, {
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
