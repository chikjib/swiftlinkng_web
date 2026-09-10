<template>
    <div class="col-xl-12 col-lg-12">
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
            <h4 class="card-title">All Users</h4>

            <div class="row">
              <div class="col-md-4"></div>
              <div class="col-md-4"></div>
              <div class="col-md-4">
                <input
                  type="text"
                  class="form-control"
                  v-model="keyword"
                  placeholder="Enter Keyword to Search"
                />
              </div>
            </div>

            <div class="table-responsive pt-3 swift-admin-scroll-table-wrap">
              <table class="table table-bordered swift-admin-scroll-table">
                <thead>
                  <tr>
                    <th>ID</th>
                    <th>Firstname</th>
                    <th>Lastname</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Wallet</th>
                    <th>Bonus</th>
                    <th>Role</th>
                    <th>Ref From</th>
                    <th>Created At</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="user in users.data" :key="user.id">
                    <td>{{ user.id }}</td>
                    <td>
                      {{ user.firstname }}
                    </td>
                    <td>{{ user.lastname }}</td>
                    <td>{{ user.email }}</td>
                    <td>{{ user.phone }}</td>
                    <td>&#8358; {{ formatNumber(user.wallet) }}</td>
                    <td>&#8358; {{ formatNumber(user.commission) }}</td>
                    <td>
                      <label class="badge badge-info" v-if="user.role == '1'"
                        >Admin</label
                      >

                      <label class="badge badge-danger" v-else>User</label>
                    </td>
                    <td>{{ user.hear_about_us }}</td>


                    <td>{{ user.created_at }}</td>
                    <td>
                      <div class="btn-group" role="group">
                        <a :href="'/dashboard/edit-user/' + user.id" class="btn btn-danger">Edit</a>

                        <a :href="'/dashboard/view-transaction/' + user.id" class="btn btn-info">Transactions</a>

                        <a :href="'/dashboard/view-bucket-transaction/' + user.id" class="btn btn-info">Bucket Transactions</a>

                        <a :href="'/dashboard/admin_user_sales_analysis/' + user.id" class="btn btn-success">Sales Analysis</a>

                        <a :href="'/dashboard/admin-user-bucket-sales-analysis/' + user.id" class="btn btn-success">Bucket Sales Analysis</a>

                        <button
                          class="btn btn-danger"
                          @click="deleteUser(user.id)"
                        >
                          Delete
                        </button>
                      </div>
                    </td>
                  </tr>
                </tbody>
              </table>

              <pagination
                align="center"
                :data="users"
                :limit="5"
                @pagination-change-page="getusers"
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
    data() {
      return {
        users: {},
        keyword: null,
        successflag: "",
        errorflag: "",
      };
    },

    mounted() {
      this.getusers();
    },

    watch: {
      keyword(after, before) {
        this.getResults();
      },
    },
    methods: {
      getResults(page) {
        if (typeof page === "undefined") {
          page = 1;
        }
        axios
          .get(`/api/admin/allusers?search=${this.keyword}&page=${page}`, {
            headers: {
              Authorization: `Bearer ${token}`,
              "Content-Type": "application/json",
            },
          })
          .then((response) => {
            this.users = response.data;

          });
      },

      getusers(page) {
        if (typeof page === "undefined") {
          page = 1;
        }
        axios
          .get(`/api/admin/allusers?page=${page}`, {
            headers: {
              Authorization: `Bearer ${token}`,
              "Content-Type": "application/json",
            },
          })
          .then((response) => {
            this.users = response.data;
            console.log(this.users);
          });
      },


      deleteUser(id) {
        if (confirm("Do you really want to delete?")) {
          axios
            .delete(`/api/admin/allusers/${id}`, {
              headers: {
                Authorization: `Bearer ${token}`,
                "Content-Type": "application/json",
              },
            })
            .then((response) => {
              let i = this.users.data.map((item) => item.id).indexOf(id); // find index of object
              this.users.data.splice(i, 1);
            });
        }
      },
    },
  };
  </script>
