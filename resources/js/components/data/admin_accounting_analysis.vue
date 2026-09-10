<template>
    <div v-if="isloading">
        <vcl-table :row="5" :column="8"></vcl-table>
    </div>

    <div v-else class="col-xl-12 col-lg-12">
        <h3 class="mb-4">Sales Accounting Analysis</h3>
        <div class="row">
            <div class="col-md-12 mb-2">
                <form @submit.prevent="getResults" class="form-horizontal">
                    <div class="col-md-12">
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="status">Select Status</label>
                                    <select class="form-control" v-model="form.status">
                                        <option value="">Status</option>
                                        <option value="1">Confirmed</option>
                                        <option value="3">Cancelled</option>
                                        <option value="4">Failed</option>
                                        <option value="2">Reversed</option>
                                        <option value="0">Pending</option>
                                    </select>
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="provider">Data Type</label>
                                    <select name="subcategory_id" v-model="form.subcategory_id" class="form-control">
                                        <option value="">Select Type</option>
                                        <option v-for="subcategories in subcategories.data" :key="subcategories.id"
                                            v-bind:value="subcategories.id">
                                            {{ subcategories.title }} -
                                            {{ subcategories.category.title }}
                                        </option>
                                    </select>
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="status">API Route</label>
                                    <select class="form-control" v-model="form.api_route">
                                        <option value="">Select API Route</option>
                                        <option>MTNDIRECT</option>
                                        <option>SMEPLUG</option>
                                        <option>AIRTELEDUSITE</option>
                                        <option>AIRTIMENIGERIA</option>
                                        <option>VTPASS</option>
                                        <option>RINGO</option>
                                        <option>AUTOPILOT</option>
                                        <option>SIMSERVER</option>
                                        <option>SUBARENA</option>
                                        <option>TBCHPORTAL</option>
                                        <option>OGADAM</option>
                                        <option>GONGOZ</option>
                                        <option>AYINLAK</option>
                                        <option>JONET</option>
                                        <option>GONGOZ</option>
                                        <option>AUTOPILOTAWUF</option>
                                        <option>AUTOSYNCAWUF</option>
                                        <option>TBCHPORTAL</option>
                                        <option>TOPUPACCESS</option>
                                        <option>ZOEPORTAL</option>
                                        <option>KORAPORTAL</option>
                                        <option>ROYALPORTAL</option>
                                        <option>SUBPORTAL</option>
                                        <option>DATAMALL</option>
                                        <option>GTECHPORTAL</option>
                                        <option>WISPER</option>
                                        <option>INTEGRITYPORTAL</option>
                                        <option>VTUPLUGPORTAL</option>
                                        <option>NAIJASUBPORTAL</option>
                                        <option>COOLSUBPORTAL</option>
                                        <option>AMAKASUBPORTAL</option>
                                        <option>ZOEDATAPORTAL</option>
                                    </select>
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="category"> Start Date</label>
                                    <input type="datetime-local" v-model="form.start_date" class="form-control" />
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="category"> End Date</label>
                                    <input type="datetime-local" v-model="form.end_date" class="form-control" />
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <button type="submit" class="btn btn-danger">Load</button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-bordered" v-if="transactions && transactions.length">
                <thead class="thead-light">
                    <tr>
                        <th>Total Count</th>
                        <th v-if="hasDataSize">Total Amount (*by Data Size)</th>
                        <th v-if="hasCostPrice">Total Cost Price</th>
                        <th v-if="hasSumAmount">Total Amount Bought</th>
                        <th v-if="hasAmountPaid">Total Amount Users Paid</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="transaction in transactions" :key="transaction.id">
                        <td>{{ formatNumber(transaction.countPlan) }}</td>
                        <td v-if="transaction.dataSize !== null && transaction.dataSize !== undefined">
                            ₦{{ formatNumber(transaction.dataSize) }}
                        </td>
                        <td v-if="transaction.costPrice !== null && transaction.costPrice !== undefined">
                            ₦{{ formatNumber(transaction.costPrice) }}
                        </td>

                        <td v-if="transaction.sumAmount !== null && transaction.sumAmount !== undefined">₦{{ formatNumber(transaction.sumAmount) }}</td>

                        <td v-if="transaction.amountPaid !== null && transaction.amountPaid !== undefined">
                            ₦{{ formatNumber(transaction.amountPaid) }}
                        </td>
                    </tr>
                </tbody>
            </table>

            <!-- Optional fallback if there’s no data -->
            <div v-else class="text-center p-3">
                No data available
            </div>
        </div>

        <br />
        <br />
        <br />
        <!-- Load user wallet -->
        <button class="btn btn-danger" @click="show_wallet">Show Total Users' wallet</button>
        <div v-if="total_wallet_show" style="margin-top: 30px;">
            <h4>Total User's Wallet balance : <strong>&#8358;{{ formatNumber(total_wallet['data']) }}</strong></h4>
        </div>

        <button class="btn btn-dark ml-md-2 mt-3 mt-md-0" :disabled="earnings_loading" @click="show_earnings">
            {{ earnings_loading ? 'Loading Bonuses...' : "Show Users' Total Bonus Balances" }}
        </button>
        <div id="referral-earnings-audit" v-if="earnings_show" class="card earnings-audit-card mt-4">
            <div class="card-body">
                <h4>Referral Earnings Summary</h4>
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <small>Total users' bonus balances</small>
                        <strong class="d-block">&#8358;{{ formatNumber(earnings.total_bonus_balances) }}</strong>
                    </div>
                    <div class="col-md-4 mb-3">
                        <small>Historical reward credits (audit)</small>
                        <strong class="d-block">&#8358;{{ formatNumber(earnings.all_time_earnings) }}</strong>
                    </div>
                    <div class="col-md-4 mb-3">
                        <small>Rewarded users / reward entries</small>
                        <strong class="d-block">{{ formatNumber(earnings.rewarded_users) }} / {{ formatNumber(earnings.reward_count) }}</strong>
                    </div>
                </div>
                <p class="text-muted mb-3">
                    Total bonus balances are calculated directly from users' commission balances. Historical reward
                    credits remain separate because transferred bonuses are no longer part of the commission balance.
                </p>
                <div class="table-responsive" v-if="earnings.breakdown && earnings.breakdown.length">
                    <table class="table table-sm table-bordered mb-0">
                        <thead><tr><th>Reward Type</th><th>Entries</th><th>Total</th></tr></thead>
                        <tbody>
                            <tr v-for="item in earnings.breakdown" :key="item.type">
                                <td>{{ rewardLabel(item.type) }}</td>
                                <td>{{ formatNumber(item.reward_count) }}</td>
                                <td>&#8358;{{ formatNumber(item.total_amount) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import { VclTable } from "vue-content-loading";
import Datepicker from "vuejs-datepicker";

const token = window.localStorage.getItem("token");

export default {
    props: ["limit"],
    components: { VclTable, Datepicker },

    data() {
        return {
            transactions: {},
            keyword: null,
            field: "Search By",
            form: {
                status: "",
                start_date: new Date().toISOString().slice(0, 16),
                end_date: new Date().toISOString().slice(0, 16),
            },
            subcategories: {},

            searching: false,
            isloading: false,

            category_id: null,
            subcategory_id: null,
            plan_id: null,

            showMoreDes: false,
            showMorePhone: false,
            total_wallet: 0,
            total_wallet_show: false,
            earnings: {},
            earnings_show: false,
            earnings_loading: false,
        };
    },

    mounted() {
        this.loadplan();
        if (this.$route.name === "referral_earnings_audit") {
            this.show_earnings(true);
        }
    },
    computed: {
        hasDataSize() {
            return (
                this.transactions &&
                Array.isArray(this.transactions) &&
                this.transactions.some(t => t.dataSize != null) // checks both null & undefined
            );
        },

        hasCostPrice() {
            return (
                this.transactions &&
                Array.isArray(this.transactions) &&
                this.transactions.some(t => t.costPrice != null)
            );
        },

        hasSumAmount() {
            return (
                this.transactions &&
                Array.isArray(this.transactions) &&
                this.transactions.some(t => t.sumAmount != null) // checks both null & undefined
            );
        },

        hasAmountPaid() {
            return (
                this.transactions &&
                Array.isArray(this.transactions) &&
                this.transactions.some(t => t.amountPaid != null) // checks both null & undefined
            );
        },

    },

    // watch: {
    //   keyword(after, before) {
    //     this.getResults();
    //   },
    // },
    methods: {
        loadplan() {
            axios
                .get(`/api/accounting`, {
                    headers: {
                        Authorization: `Bearer ${token}`,
                        "Content-Type": "application/json",
                    },
                })
                .then((response) => {
                    console.log(response.data);
                    this.subcategories = response.data;
                });
        },

        getResults(page) {
            if (typeof page === "undefined") {
                page = 1;
            }

            this.isloading = true;
            this.searching = true;
            this.errorMessage = "";
            this.transactions = null; // reset old data

            axios
                .post(`/api/accounting/reports`, this.form, {
                    headers: {
                        Authorization: `Bearer ${token}`,
                        "Content-Type": "application/json",
                    },
                })
                .then((response) => {
                    console.log(response.data);

                    // ✅ Laravel sendResponse() case
                    if (response.data.success === true) {
                        this.transactions = response.data.data.map(t => ({
                            countPlan: t.countPlan ?? 0,
                            dataSize: t.dataSize ?? null,
                            costPrice: t.costPrice ?? null,
                            amountPaid: t.amountPaid ?? null,
                            sumAmount: t.sumAmount ?? null
                        })); // your report data
                        this.errorMessage = "";
                    }
                    // ✅ Laravel sendError() case
                    else if (response.data.success === false) {
                        this.errorMessage =
                            response.data.message || "No data found for the selected filters.";
                        this.transactions = { data: [] }; // ensure table doesn’t crash
                    }
                })
                .catch((error) => {
                    console.error("API Error:", error);

                    // ✅ Network/server errors
                    if (error.response && error.response.data) {
                        this.errorMessage =
                            error.response.data.message ||
                            "An error occurred while fetching reports.";
                    } else {
                        this.errorMessage = "Unable to connect to server. Please try again.";
                    }

                    this.transactions = { data: [] };
                })
                .finally(() => {
                    this.isloading = false;
                    this.searching = false;
                });
        },

        show_wallet() {
            axios
                .get(`/api/total-wallet`, {
                    headers: {
                        Authorization: `Bearer ${token}`,
                        "Content-Type": "application/json",
                    },
                })
                .then((response) => {
                    console.log(response.data);
                    this.total_wallet = response.data;
                    this.total_wallet_show = true;
                });
        },

        show_earnings(scrollToAudit = false) {
            const shouldScroll = scrollToAudit === true;
            this.earnings_loading = true;
            axios
                .get(`/api/admin/referrals/earnings-summary`, {
                    headers: {
                        Authorization: `Bearer ${token}`,
                        "Content-Type": "application/json",
                    },
                })
                .then((response) => {
                    this.earnings = response.data.data || {};
                    this.earnings_show = true;
                    if (shouldScroll) {
                        this.$nextTick(() => {
                            const audit = document.getElementById("referral-earnings-audit");
                            if (audit) audit.scrollIntoView({ behavior: "smooth", block: "start" });
                        });
                    }
                })
                .catch((error) => {
                    const message = error.response && error.response.data
                        ? error.response.data.message
                        : "Unable to load referral earnings audit.";
                    this.$toasted.show(message);
                })
                .finally(() => {
                    this.earnings_loading = false;
                });
        },

        rewardLabel(type) {
            return String(type || "")
                .replace(/_/g, " ")
                .replace(/\b\w/g, letter => letter.toUpperCase());
        }
    },
};
</script>

<style scoped>
.pagination {
    margin-bottom: 0;
    margin-top: 5;
}

.box {
    inline-size: 5px;
    overflow-wrap: break-word;
}

.earnings-audit-card {
    border: 0;
    border-left: 5px solid #d20b27;
    box-shadow: 0 8px 24px rgba(26, 16, 18, 0.08);
}

.earnings-audit-card small {
    color: #6c757d;
}

.earnings-audit-card strong {
    color: #282124;
    font-size: 1.25rem;
}
</style>
