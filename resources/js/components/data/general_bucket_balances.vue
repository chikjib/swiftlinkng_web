<template>
    <div class="row">
      <div class="col-xl-12 col-lg-12">
        <div class="card mb-4">
          <div
            class="card-header py-3 d-flex flex-row align-items-center justify-content-between"
          >
            <div class="col-md-12">
                <div class="bucket-section">
                  <div
                    class="bucket-wallet-content"
                    v-for="bucket in buckets.data"
                    :key="bucket.id"
                  >
                    <div class="bucket-wallet-title">{{ bucket.title }}</div>
                    <div class="bucket-wallet-balance">
                      {{ bucket.bucket_balance }}<span>GB</span>
                    </div>
                    <a
                    :href="`/dashboard/bucket-transaction/${bucket.id}`"
                    class="nav-link"
                  >
                    <i class="fas fa-fw fa-th"></i>

                    <span class="menu-title">View Transactions</span>
                  </a>
                  </div>
                </div>

            </div>
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

      load_bucket() {
        axios
          .get(`/api/buckets/balance`, {
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
    margin-bottom: 10px;
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
    font-size: 12px;
  }

  table {
    border-collapse: collapse;
    border-spacing: 0;
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
