<template>
    <div>
      <div class="row">
        <div class="col-lg-12">
          <div class="card">
            <div class="card-body">
              <h4 class="card-title">All Mtn Logins</h4>
              <div class="row">
                <div class="col-md-4">
                    <a href="/mtn-direct-app-login" class="btn btn-success"><span class="fa fa-plus"></span> Add Number</a>
                  </div>
              </div>

              <div class="table-responsive pt-3">
                <table class="table table-bordered">
                  <thead>
                    <tr>
                      <th>ID</th>
                      <th>Phone Number</th>
                      <th>Status</th>
                      <th>Created At</th>
                      <th>Actions</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="mtn_login in mtn_logins.data" :key="mtn_login.id">
                      <td>{{ mtn_login.id }}</td>
                      <td>
                        {{ mtn_login.phone_number }} (&#8358;{{ formatNumber(mtn_login.balance) }})
                      </td>


                      <td>
                        <label
                          class="badge badge-success"
                          v-if="mtn_login.status == '1'"
                          >Active</label
                        >

                        <label class="badge badge-danger" v-else>InActive</label>
                      </td>

                      <td>{{ mtn_login.created_at }}</td>
                      <td>
                        <div class="btn-group" role="group">

                          <button
                            v-if="mtn_login.status == 1"
                            class="btn btn-danger"
                            @click="activate(mtn_login.id)"
                          >
                            Deactivate
                          </button>

                          <button
                            v-else
                            class="btn btn-success"
                            @click="activate(mtn_login.id)"
                          >
                            Activate
                          </button>

                          <button
                            class="btn btn-danger"
                            @click="deleteNumber(mtn_login.id)"
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
                :data="mtn_logins"
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
        // product: {},
        mtn_logins: {},
        keyword: null,
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
          .get(`/api/load-logins`, {
            headers: {
              Authorization: `Bearer ${token}`,
              "Content-Type": "application/json",
            },
          })
          .then(({ data }) => {
            console.log(data);
            this.mtn_logins = data;
          })
          .catch(({ error }) => {
            console.log(error);
            this.$toasted.show(error.response.data.data);
          });
      },

      activate(id) {
        if (confirm("Do you really want to activate/deactive this number?")) {
          axios
            .get(`/api/activate-number/${id}`, {
              headers: {
                Authorization: `Bearer ${token}`,
                "Content-Type": "application/json",
              },
            })
            .then((response) => {
              this.loading = false;

              this.$toasted.show(response.data.message);
              window.location.reload();
            });
        }
      },
      deleteNumber(id) {
        if (confirm("Do you really want to delete?")) {
          axios
            .get(`/api/delete-number/${id}`, {
              headers: {
                Authorization: `Bearer ${token}`,
                "Content-Type": "application/json",
              },
            })
            .then((response) => {
              let i = this.mtn_logins.data.map((item) => item.id).indexOf(id); // find index of object
              this.mtn_logins.data.splice(i, 1);
            });
        }
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
