<template>
  <div class="row">

    <div class="col-xl-6 col-lg-6 ">
      <div class="card mb-4">
        <div
          class="card-header py-3 d-flex flex-row align-items-center"
        >
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th scope="col" class="panel panel-default">RANGE</th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <tr
                v-for="(bucket, index) in JSON.parse(
                  buckets.data[0].price_per_gb
                )"
                :key="index"
              >
                <td class="px-6 py-3 whitespace-nowrap">
                  <div class="text-center">
                    <div class="font-medium text-gray-900">
                      {{ bucket.lower_limit }}GB - {{ bucket.upper_limit }}GB
                    </div>

                    <table class="mt-2 bucket-pricing">
                      <tbody>
                        <tr
                          v-for="(bucket_self, self_index) in buckets.data"
                          :key="self_index"
                        >
                          <td>{{ bucket_self.title }}</td>
                          <td>
                            ₦{{ formatPrice(getLowerRangePrice(
                                bucket_self.price_per_gb,
                                index
                              )) }}
                            - ₦{{ formatPrice(getUpperRangePrice(
                                bucket_self.price_per_gb,
                                index
                              )) }}
                          </td>
                          <td>
                            [₦{{
                              getPriceRange(bucket_self.price_per_gb, index)
                            }}/GB]
                          </td>
                        </tr>
                      </tbody>
                    </table>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</template>

  <script>
const token = window.localStorage.getItem("token");

export default {
  // props: ["user"],
  data() {
    return {
      user: this.auth.user,
      buckets: {},
      bucket_id: "",
      total_cost: 0,
      price_ranges: [],
      loading: false,
      form: {
        price: 0,
        data_size: "",
      },
      successflag: "",
      errorflag: "",
    };
  },

  mounted() {
    this.load_bucket();
  },

  methods: {
    getLowerRangePrice(data, index) {
      const formatted_data = JSON.parse(data);
      const m = formatted_data[index];
      return m !== undefined ? m.price * m.lower_limit : null;
    },

    getUpperRangePrice(data, index) {
      const formatted_data = JSON.parse(data);
      const m = formatted_data[index];
      return m !== undefined ? m.price * m.upper_limit : null;
    },
    getPriceRange(data, index) {
      const formatted_data = JSON.parse(data);
      const m = formatted_data[index];
      return m !== undefined ? m.price : null;
    },
    bucket_purchase() {
      console.log(this.form);
      this.loading = true;
      axios
        .post(`/api/purchase/bucket`, this.form, {
          headers: {
            Authorization: `Bearer ${token}`,
            "Content-Type": "application/json",
          },
        })
        .then((response) => {
          console.log(response.data);
          this.form.amount == "";
          this.successflag = response.data.message;
          this.loading = false;
          this.$toasted.show(response.data.message);
        })
        .catch((error) => {
          this.loading = false;
          this.errorflag = error.response.data.message;
          this.$toasted.show(error.response.data.message);
        });
    },

    load_bucket() {
      axios
        .get(`/api/buckets`, {
          headers: {
            Authorization: `Bearer ${token}`,
            "Content-Type": "application/json",
          },
        })
        .then((response) => {
          console.log(response.data);
          this.buckets = response.data;
        });
    },

    showPrice() {
      this.loading = true;
      this.form.data_size = null;
      this.total_cost = 0;
      var c = this.buckets.data.filter((sub) => sub.id === this.form.bucket_id);
      var price = JSON.parse(c[0].price_per_gb);

      this.loading = false;

      this.price_ranges = price;
      console.log(this.price_ranges);
    },

    showAmount() {
      const match = this.price_ranges.find(
        (range) =>
          this.form.data_size >= range.lower_limit &&
          this.form.data_size <= range.upper_limit
      );
      console.log(match);

      this.form.price = match ? match.price : null;

      this.total_cost = this.form.data_size * this.form.price;
    },
  },
};
</script>

  <style scoped>
img {
  width: 9vw;
  height: 9vw;
  padding: 5px;
}

input[type="radio"] {
  display: none;
}

img:hover {
  opacity: 0.6;
  cursor: pointer;
}

img:active {
  opacity: 0.4;
  cursor: pointer;
}

input[type="radio"]:checked + label > img {
  border: 2px solid #6777ef;
}

ul {
  list-style-type: none;
  margin: 0;
  padding: 0;
  overflow: hidden;
}

li {
  float: left;
  border: 2px solid #ccc;
  margin: 5px;
  border-radius: 10px;
}

li a {
  display: block;
  color: white;
  text-align: center;
  padding: 16px;
  text-decoration: none;
}

.centered {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
}

.bucket-cost {
  margin-bottom: 20px;
}

.bucket-cost-item {
  padding: 10px;
  font-size: 15px;
  font-weight: bold;
}

.bucket-section {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
  justify-content: space-between;
}
.bucket-wallet-content {
  flex: 1 1 calc(25% - 10px);
  box-sizing: border-box;
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
  border: 1px solid red;
  border-radius: 6px;
  padding: 5px;
  margin-top: 10px;
  background: #f0eeee;
  font-size: 14px;
}
.bucket-wallet-title {
  font-weight: bold;
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
  text-align: center;
  margin-bottom: 10px;
}

.bucket-pricing {
  font-size: 14px;
}

table {
  border-collapse: collapse;
  border-spacing: 10px;
  border: 0 none;
}
/* fix padding of TD to suit your needs */
table td {
  border: 0 none;
  padding: 5px;
  text-align: left;
  font-weight: bold;
}

@media screen and (min-width: 768px) {
  .bucket-section {
    flex: 1 1 calc(50% - 10px);
  }
  .bucket-wallet-content {
    padding: 15px;
    font-size: 15px;
  }
}
</style>
