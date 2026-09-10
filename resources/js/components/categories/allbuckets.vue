<template>
  <div>
    <div class="row">
      <div class="col-lg-12">
        <div class="card">
          <div class="card-body">
            <h4 class="card-title">All Buckets</h4>
            <div class="row">
              <div class="col-md-4"></div>
              <div class="col-md-4"></div>
              <!-- <div class="col-md-4">
                  <input
                    type="text"
                    class="form-control"
                    v-model="keyword"
                    placeholder="Enter Keyword to Search"
                  />
                </div> -->
            </div>

            <div class="table-responsive pt-3">
              <table class="table table-bordered">
                <thead>
                  <tr>
                    <th>ID</th>
                    <th>Title</th>
                    <th>Category</th>
                    <th>Status</th>
                    <th>Bucket Purchase Status</th>
                    <th>Created At</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="bucket in buckets.data" :key="bucket.id">
                    <td>{{ bucket.id }}</td>
                    <td>
                      {{ bucket.title }}
                    </td>

                    <td>
                      {{ bucket.category.title }}
                    </td>
                    <td>
                      <label
                        class="badge badge-success"
                        v-if="bucket.status == '1'"
                        >Active</label
                      >

                      <label class="badge badge-danger" v-else>InActive</label>
                    </td>
                    <td>
                      <label
                        class="badge badge-success"
                        v-if="bucket.is_purchase === 1"
                        >On</label
                      >

                      <label class="badge badge-danger" v-else>Off</label>
                    </td>
                    <td>{{ bucket.created_at }}</td>
                    <td>
                      <div class="btn-group" role="group">
                        <a :href="'/dashboard/buckets/' + bucket.id" class="btn btn-danger">Edit</a>

                        <button
                          v-if="bucket.is_purchase == 1"
                          class="btn btn-danger"
                          @click="switchOffBucketPurchase(bucket.id)"
                        >
                          Switch Off Bucket Purchase
                        </button>

                        <button
                          v-else
                          class="btn btn-success"
                          @click="switchOffBucketPurchase(bucket.id)"
                        >
                          Switch On Bucket Purchase
                        </button>

                        <button
                          class="btn btn-danger"
                          @click="deleteBucket(bucket.id)"
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
              :data="buckets"
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
      buckets: {},
      keyword: null,
      category_id: 0,
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
        .get(`/api/get-all-buckets`, {
          headers: {
            Authorization: `Bearer ${token}`,
            "Content-Type": "application/json",
          },
        })
        .then(({ data }) => {
          console.log(data);
          this.buckets = data;
        })
        .catch(({ response }) => {
          this.$toasted.show(error.response.data.data);
        });
    },

    switchOffBucketPurchase(id) {
      if (confirm("Do you really want to switch on/off bucket purchase?")) {
        axios
          .get(`/api/switch-off-bucket-purchase/${id}`, {
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
    deleteBucket(id) {
      if (confirm("Do you really want to delete?")) {
        axios
          .get(`/api/delete-bucket/${id}`, {
            headers: {
              Authorization: `Bearer ${token}`,
              "Content-Type": "application/json",
            },
          })
          .then((response) => {
            let i = this.buckets.data.map((item) => item.id).indexOf(id); // find index of object
            this.buckets.data.splice(i, 1);
          });
      }
    },

    getResults(page) {
      if (typeof page === "undefined") {
        page = 1;
      }

      axios
        .get(`/api/buckets?search=${this.keyword}&page=${page}`, {
          headers: {
            Authorization: `Bearer ${token}`,
            "Content-Type": "application/json",
          },
        })
        .then((response) => {
          console.log(response.data);
          this.buckets = response.data;

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
