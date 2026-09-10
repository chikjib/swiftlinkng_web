<template>
    <div>
        <div class="row">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">All Products</h4>
                        <div class="row">
                            <div class="col-md-4"></div>
                            <div class="col-md-4"></div>
                            <div class="col-md-4">
                                <input type="text" class="form-control" v-model="keyword"
                                    placeholder="Enter Keyword to Search" />
                            </div>
                        </div>
                        <br />
                        <div class="row">
                            <div class="col-md-4"></div>

                            <div class="col-md-4">
                                <form @submit.prevent="toggleAll">

                                    <input type="text" v-model.trim="product_title" class="form-control"
                                        placeholder="Enter product title">

                                    <br>

                                    <button type="submit" class="btn btn-warning w-100"
                                        :disabled="!product_title || loading">

                                        <span v-if="loading">
                                            Processing...
                                        </span>

                                        <span v-else>
                                            Toggle All {{ product_title || '' }} Services
                                        </span>

                                    </button>

                                </form>
                            </div>

                            <div class="col-md-4"></div>
                        </div>



                        <div class="table-responsive pt-3">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>Category</th>
                                        <th>ID</th>
                                        <th>Title</th>

                                        <th>Image</th>
                                        <th>Description</th>
                                        <th>Status</th>
                                        <th>Created At</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="product in categories.data" :key="product.id">
                                        <td>
                                            {{ product.category.title }}
                                        </td>
                                        <td>{{ product.id }}</td>
                                        <td>
                                            {{ product.title }}
                                        </td>

                                        <td>
                                            <img class="img-sm rounded-10" height="40" width="40" :src="product.subcat_image != null
                                                ? '/template/images/services/' +
                                                product.subcat_image
                                                : '/template/images/services/error.svg'
                                                " alt="profile" />
                                        </td>

                                        <td>{{ product.description }}</td>

                                        <td>
                                            <label class="badge badge-success"
                                                v-if="product.status == '1'">Active</label>

                                            <label class="badge badge-danger" v-else>InActive</label>
                                        </td>
                                        <td>{{ product.created_at }}</td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <a
                                                    v-if="product.title == 'WAEC' || product.title == 'JAMB' || product.title == 'NECO'"
                                                    :href="'/dashboard/pins/' + encodeURIComponent(product.title) + '/' + product.id" class="btn btn-danger">Pins
                                                </a>


                                                <button
                                                    :class="product.status == 1 ? 'btn btn-secondary' : 'btn btn-success'"
                                                    @click="toggleActive(product)">
                                                    {{ product.status == 1 ? 'Deactivate' : 'Activate' }}
                                                </button>

                                                <a :href="'/dashboard/edit-category/' + product.id" class="btn btn-danger">Edit</a>

                                                <button class="btn btn-danger" @click="deleteSubcategory(product.id)">
                                                    Delete
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <pagination align="center" :data="categories" @pagination-change-page="list"></pagination>
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
            product_title: "",
            categories: {},
            keyword: null,
            category_id: 0,
            loading: false
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
                .get(`/api/subcategory?page=${page}`, {
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

        toggleActive(product) {
            axios.get(`/api/toggle-active-package/${product.id}`, {
                headers: {
                    Authorization: `Bearer ${token}`,
                    "Content-Type": "application/json",
                },
            })
                .then(res => {
                    // toggle UI immediately (no reload)
                    // product.status = product.status == 1 ? 0 : 1;
                    location.reload();
                })
                .catch(err => {
                    console.error(err);
                });
        },

        async toggleAll() {

            if (!this.product_title) {
                return;
            }

            const confirmAction = confirm(
                `Are you sure you want to toggle all ${this.product_title} services?`
            );

            if (!confirmAction) {
                return;
            }

            this.loading = true;

            try {

                const response = await axios.get(
                    `/api/toggle-all-packages/${this.product_title}`
                );

                console.log(response.data);

                alert(response.data.message);
                location.reload();

            } catch (error) {

                console.log(error);

                alert('Something went wrong');

            } finally {

                this.loading = false;
            }
        },

        deleteSubcategory(id) {
            if (confirm("Do you really want to delete?")) {
                axios
                    .delete(`/api/subcategory/${id}`, {
                        headers: {
                            Authorization: `Bearer ${token}`,
                            "Content-Type": "application/json",
                        },
                    })
                    .then((response) => {
                        let i = this.subcategories.data.map((item) => item.id).indexOf(id); // find index of object
                        this.subcategories.data.splice(i, 1);
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
