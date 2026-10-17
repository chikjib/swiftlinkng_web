// Load each screen when it is visited so sign-in does not wait for the dashboard.
const International = () => import('./components/services/International.vue');
const InternationalAdmin = () => import('./components/services/InternationalAdmin.vue');
const BettingWallet = () => import('./components/services/Betting.vue');
const TravelEsim = () => import('./components/services/TravelEsim.vue');
const ServiceAdmin = () => import('./components/services/ServiceAdmin.vue');
import Auth from "./Auth.js";
import { createRouter, createWebHistory } from "vue-router";

import Login from "./components/login.vue";
const Register = () => import("./components/register.vue");
const VerifyEmailNotice = () => import("./components/VerifyEmailNotice.vue");
const forgotpassword = () => import("./components/forgot.vue");
const VerifyToken = () => import("./components/verifytoken.vue");
const Reset = () => import("./components/reset.vue");

const Dashboard = () => import("./components/dashboard.vue");
const UpdateDetails = () => import("./components/updates/UpdateDetails.vue");

const dashboardLayout = () => import("./components/layouts/dashboard.vue");

const data = () => import("./components/data/Buydata.vue");
const bulkdata = () => import("./components/data/BulkData.vue");
const vendbulkdata = () => import("./components/data/VendBulkData.vue");
const bulkdatatransactions = () => import("./components/data/BulkDataTransactions.vue");
const singlebuckettransaction = () => import("./components/data/single_bucket_transactions.vue");
const allbuckettransactions = () => import("./components/data/all_bucket_transactions.vue");
const bucketdatapricing = () => import("./components/data/bucket_data_pricing_list.vue");
const general_bucket_balances = () => import("./components/data/general_bucket_balances.vue");
const purchasemode = () => import("./components/data/purchase_mode.vue");
const allunconfirmedbuckettransactions = () => import("./components/data/all_unconfirmed_bucket_transactions.vue");
const airtime = () => import("./components/data/Airtime.vue");
const upgradeaccount = () => import("./components/data/UpgradeAccount.vue");

const cable = () => import("./components/data/Cable.vue");
const electricity = () => import("./components/data/Electricity.vue");
const airtime2cash = () => import("./components/data/Airtime2Cash.vue");
const verifyotpmtn = () => import("./components/data/verify_otp_mtn.vue");
const exams = () => import("./components/data/Exam.vue");
const transactions = () => import("./components/data/transactions.vue");
const alltransactions = () => import("./components/data/alltransactions.vue");
const allunconfirmedtransactions = () => import("./components/data/allunconfirmedtransactions.vue");
const viewtransaction = () => import("./components/data/transactionsbyuser.vue");
const viewbuckettransaction = () => import("./components/data/buckettransactionsbyuser.vue");
const manual = () => import("./components/data/Manual.vue");
const fundWallet = () => import("./components/data/FundWallet.vue");
const admin_general_bucket_balances = () => import("./components/data/admin_general_bucket_balances.vue");
const commission = () => import("./components/commissions/allcommissions.vue");
const withdraw = () => import("./components/data/Withdraw.vue");

const settings = () => import("./components/settings/Settings.vue");
const mtn_logins = () => import("./components/categories/allmtnlogins.vue");
const users = () => import("./components/users/allusers.vue");
const edituser = () => import("./components/users/edituser.vue");
const receipt = () => import("./components/data/Receipt.vue");
const bucketreceipt = () => import("./components/data/BucketReceipt.vue");

const products = () => import("./components/categories/allcategories.vue");
const adminBucket = () => import("./components/categories/allbuckets.vue");
const editAdminBucket = () => import("./components/categories/editBuckets.vue");
const allpins = () => import("./components/categories/allpins.vue");
const editcategory = () => import("./components/categories/EditCategory.vue");
const editpin = () => import("./components/categories/editpins.vue");
const profile = () => import("./components/settings/Profile.vue");
const BvnVerify = () => import("./components/settings/BvnVerify.vue");
const slides = () => import("./components/sliders/allslides.vue");
const sms = () => import("./components/data/Sms.vue");
const sales_analysis = () => import("./components/data/sales_analysis.vue");
const user_sales_analysis = () => import("./components/data/user_sales_analysis.vue");
const admin_user_sales_analysis = () => import("./components/data/admin_user_sales_analysis.vue");

const bucket_sales_analysis = () => import("./components/data/bucket_sales_analysis.vue");
const user_bucket_sales_analysis = () => import("./components/data/user_bucket_sales_analysis.vue");
const admin_user_bucket_sales_analysis = () => import("./components/data/admin_user_bucket_sales_analysis.vue");

const MonnifyRepush = () => import("./components/data/MonnifyRepush.vue");
const repush = () => import("./components/data/Repush.vue");
const referralDashboard = () => import("./components/referrals/ReferralDashboard.vue");
const referralAdmin = () => import("./components/referrals/ReferralAdmin.vue");
const whatsAppTransactionsAdmin = () => import("./components/whatsapp/WhatsAppTransactionsAdmin.vue");

const callback = () => import("./components/callback.vue");
const Admin_accounting_analysis = () => import("./components/data/admin_accounting_analysis.vue");

const routes = [
    {
        path: "/",
        component: Login,
        name: "Home",
    },
    {
        path: "/login",
        component: Login,
        name: "Login",
    },
    {
        path: "/register",
        component: Register,
        name: "Register",
    },
    {
        path: "/verify-email",
        component: VerifyEmailNotice,
        name: "VerifyEmailNotice",
    },

    {
        path: "/forgotpassword",
        component: forgotpassword,
        name: "forgotpassword",
    },

    {
        path: "/reset",
        component: Reset,
        name: "Reset",
    },

    {
        path: "/verifytoken",
        component: VerifyToken,
        name: "VerifyToken",
    },

    {
        path: "",
        component: dashboardLayout,
        children: [
            {path:'/dashboard/international',name:'international',component:International,meta:{requiresAuth:true,title:'International'}},
            {path:'/dashboard/international-admin',name:'international-admin',component:InternationalAdmin,meta:{requiresAdmin:true,title:'International Admin'}},
            {path:'/dashboard/betting',name:'betting',component:BettingWallet,meta:{requiresAuth:true,title:'Fund Betting Wallet'}},
            {path:'/dashboard/esim',name:'esim',component:TravelEsim,meta:{requiresAuth:true,title:'Travel eSIM'}},
            {path:'/dashboard/service-admin',name:'service-admin',component:ServiceAdmin,meta:{requiresAdmin:true,title:'Betting & Travel eSIM Admin'}},
            {
                path: "/dashboard",
                name: "dashboard",
                component: Dashboard,
                meta: {
                    requiresAuth: true,
                    title: "Dashboard",
                },
            },

            {
                path: "/dashboard/data",
                name: "data",
                component: data,
                meta: {
                    requiresAuth: true,
                    title: "Data purchase",
                },
            },
            {
                path: "/dashboard/talkmore",
                name: "talkmore",
                component: data,
                props: {
                    serviceTitle: "Talk More Airtime",
                    categoryId: 13,
                    purchaseEndpoint: "/api/purchase/talkmore",
                },
                meta: {
                    requiresAuth: true,
                    title: "Talk More Airtime",
                },
            },
            {
                path: "/dashboard/update-details",
                name: "update-details",
                component: UpdateDetails,
                meta: {
                    requiresAuth: true,
                    title: "Updates for You",
                },
            },
            {
                path: "/dashboard/airtime",
                name: "airtime",
                component: airtime,
                meta: {
                    requiresAuth: true,
                    title: "Airtime Purchase",
                },
            },

            {
                path: "/dashboard/cable",
                name: "cable",
                component: cable,
                meta: {
                    requiresAuth: true,
                    title: "Cable Bills",
                },
            },

            {
                path: "/dashboard/electricity",
                name: "electricity",
                component: electricity,
                meta: {
                    requiresAuth: true,
                    title: "Electricity Bill",
                },
            },

            {
                path: "/dashboard/airtime2cash",
                name: "airtime2cash",
                component: airtime2cash,
                meta: {
                    requiresAuth: true,
                    title: "Airtime to Cash",
                },
            },

            {
                path: "/dashboard/verify-otp",
                name: "verifyotpmtn",
                component: verifyotpmtn,
                meta: {
                    requiresAuth: true,
                    title: "Airtime to Cash",
                },
            },

            {
                path: "/dashboard/exams",
                name: "exams",
                component: exams,
                meta: {
                    requiresAuth: true,
                    title: "Exam Pin",
                },
            },
            {
                path: "/dashboard/sms-disclaimer",
                name: "sms-disclaimer",
                redirect: "/dashboard/sms",
            },

            {
                path: "/dashboard/sms",
                name: "sms",
                component: sms,
                meta: {
                    requiresAuth: true,
                    title: "Bulk SMS",
                },
            },

            {
                path: "/dashboard/commission",
                name: "commission",
                component: commission,
                meta: {
                    requiresAuth: true,
                    title: "Commission Earned",
                },
            },

            {
                path: "/dashboard/withdraw",
                name: "withdraw",
                component: withdraw,
                meta: {
                    requiresAuth: true,
                    title: "Withdraw",
                },
            },

            {
                path: "/dashboard/transactions",
                name: "transactions",
                component: transactions,
                meta: {
                    requiresAuth: true,
                    title: "All transactions",
                },
            },

            {
                path: "/dashboard/fund",
                name: "fund-wallet",
                component: fundWallet,
                meta: {
                    requiresAuth: true,
                    title: "Fund Wallet",
                },
            },

            {
                path: "/dashboard/manual",
                name: "Manual",
                component: manual,
                meta: {
                    requiresAuth: true,
                    title: "Manual Funding",
                },
            },

            {
                path: "/dashboard/alltransactions",
                name: "alltransactions",
                component: alltransactions,
                meta: {
                    requiresAdmin: true,
                    title: "All transactions",
                },
            },

            {
                path: "/dashboard/allunconfirmedtransactions",
                name: "allunconfirmedtransactions",
                component: allunconfirmedtransactions,
                meta: {
                    requiresAdmin: true,
                    title: "All Unconfirmed transactions",
                },
            },

            {
                path: "/dashboard/settings",
                name: "settings",
                component: settings,
                meta: {
                    requiresAdmin: true,
                    title: "Settings",
                },
            },

            {
                path: "/dashboard/products",
                name: "products",
                component: products,
                meta: {
                    requiresAdmin: true,
                    title: "Products",
                },
            },
            {
                path: "/dashboard/pins/:title/:id",
                name: "pins",
                component: allpins,
                meta: {
                    requiresAdmin: true,
                    title: "Pins",
                },
            },

            {
                path: "/dashboard/users",
                name: "users",
                component: users,
                meta: {
                    requiresAdmin: true,
                    title: "users",
                },
            },

            {
                path: "/dashboard/edit-user/:id",
                name: "edit-user",
                component: edituser,
                meta: {
                    requiresAdmin: true,
                    title: "Edit User",
                },
            },

            {
                path: "/dashboard/edit-category/:id",
                name: "edit-category",
                component: editcategory,
                meta: {
                    requiresAdmin: true,
                    title: "Edit Category",
                },
            },
            {
                path: "/dashboard/edit-pins/:id/:title",
                name: "edit-pin",
                component: editpin,
                meta: {
                    requiresAdmin: true,
                    title: "Edit Pin",
                },
            },

            {
                path: "/dashboard/view-transaction/:id",
                name: "view-transaction",
                component: viewtransaction,
                meta: {
                    requiresAdmin: true,
                    title: "View Transactions",
                },
            },

            {
                path: "/dashboard/view-bucket-transaction/:id",
                name: "view-bucket-transaction",
                component: viewbuckettransaction,
                meta: {
                    requiresAdmin: true,
                    title: "View Transactions",
                },
            },

            {
                path: "/dashboard/profile",
                name: "profile",
                component: profile,
                meta: {
                    requiresAuth: true,
                    title: "Profile",
                },
            },
            {
                path: "/dashboard/identity-verification",
                name: "Identity Verification",
                component: BvnVerify,
                meta: {
                    requiresAuth: true,
                    title: "Identity Verification",
                },
            },

            {
                path: "/dashboard/slides",
                name: "slides",
                component: slides,
                meta: {
                    requiresAdmin: true,
                    title: "Slides",
                },
            },

            {
                path: "/dashboard/upgradeaccount",
                name: "upgrade",
                component: upgradeaccount,
                meta: {
                    requiresAuth: true,
                    title: "Upgrade Account",
                },
            },

            {
                path: "/dashboard/receipt/:id",
                name: "receipt",
                component: receipt,
                meta: {
                    requiresAuth: true,
                    title: "Receipt",
                },
            },
            {
                path: "/dashboard/callback",
                name: "Notification",
                component: callback,
                meta: {
                    requiresAuth: true,
                    title: "Payment Notification"
                }
            },
            {
                path: "/dashboard/bucket-receipt/:id",
                name: "bucket-receipt",
                component: bucketreceipt,
                meta: {
                    requiresAuth: true,
                    title: "Receipt",
                },
            },
            // admin
            {
                path: "/dashboard/sales_analysis",
                name: "sales_analysis",
                component: sales_analysis,
                meta: {
                    requiresAdmin: true,
                    title: "Sales Analysis",
                },
            },
            {
                path: "/dashboard/accounting-analysis",
                name: "accounting_analysis",
                component: Admin_accounting_analysis,
                meta: {
                    requiresAdmin: true,
                    title: "Accounting Analysis",
                },
            },
            {
                path: "/dashboard/referral-earnings-audit",
                name: "referral_earnings_audit",
                component: Admin_accounting_analysis,
                meta: {
                    requiresAdmin: true,
                    title: "Sales Accounting Analysis",
                },
            },
            //admin
            {
                path: "/dashboard/bucket-sales-analysis",
                name: "admin_bucket_sales_analysis",
                component: bucket_sales_analysis,
                meta: {
                    requiresAdmin: true,
                    title: "Bucket Sales Analysis",
                },
            },
            //user
            {
                path: "/dashboard/user_sales_analysis",
                name: "user-sales-analysis",
                component: user_sales_analysis,
                meta: {
                    requiresAuth: true,
                    title: "User Sales Analysis",
                },
            },
            //admin
            {
                path: "/dashboard/admin_user_sales_analysis/:id",
                name: "admin-user-sales-analysis",
                component: admin_user_sales_analysis,
                meta: {
                    requiresAdmin: true,
                    title: "User Sales Analysis",
                },
            },
            //user
            {
                path: "/dashboard/user-bucket-sales-analysis",
                name: "bucket-user-sales-analysis",
                component: user_bucket_sales_analysis,
                meta: {
                    requiresAuth: true,
                    title: "Bucket Sales Analysis",
                },
            },

            //admin
            {
                path: "/dashboard/admin-user-bucket-sales-analysis/:id",
                name: "admin-bucket-user-sales-analysis",
                component: admin_user_bucket_sales_analysis,
                meta: {
                    requiresAdmin: true,
                    title: "Bucket Sales Analysis",
                },
            },
            {
                path: "/dashboard/buckets",
                name: "bucket",
                component: adminBucket,
                meta: {
                    requiresAdmin: true,
                    title: "Bucket",
                },
            },

            {
                path: "/dashboard/buckets/:id",
                name: "edit-bucket",
                component: editAdminBucket,
                meta: {
                    requiresAdmin: true,
                    title: "Bucket",
                },
            },


            {
                path: "/dashboard/general-bucket-balances",
                name: "bucket-balances",
                component: general_bucket_balances,
                meta: {
                    requiresAuth: true,
                    title: "Bucket Balances",
                },
            },
            {
                path: "/dashboard/purchase-mode",
                name: "purchase-mode",
                component: purchasemode,
                meta: {
                    requiresAuth: true,
                    title: "Purchase Mode",
                },
            },

            {
                path: "/dashboard/buy-bulk-data",
                name: "bulk-data",
                component: bulkdata,
                meta: {
                    requiresAuth: true,
                    title: "Bulk Data",
                },
            },

            {
                path: "/dashboard/vend-bulk-data",
                name: "vend-bulk-data",
                component: vendbulkdata,
                meta: {
                    requiresAuth: true,
                    title: "Bulk Data",
                },
            },

            {
                path: "/dashboard/bulk-data-transactions",
                name: "bulk-data-transactions",
                component: bulkdatatransactions,
                meta: {
                    requiresAuth: true,
                    title: "All Bulk Data transactions",
                },
            },

            {
                path: "/dashboard/bucket-transaction/:bucket_id",
                name: "single-bucket-transaction",
                component: singlebuckettransaction,
                meta: {
                    requiresAuth: true,
                    title: "Bucket transactions",
                },
            },
            {
                path: "/dashboard/bucket-data-pricing-list",
                name: "bucket-data-pricing-list",
                component: bucketdatapricing,
                meta: {
                    requiresAuth: true,
                    title: "Bucket Data Pricing",
                },
            },

            {
                path: "/dashboard/all-bucket-transactions",
                name: "all bucket transactions",
                component: allbuckettransactions,
                meta: {
                    requiresAdmin: true,
                    title: "All Bulk Data transactions",
                },
            },
            {
                path: "/dashboard/admin-general-bucket-balances",
                name: "all bucket balances",
                component: admin_general_bucket_balances,
                meta: {
                    requiresAdmin: true,
                    title: "Admin Bucket Balances",
                },
            },

            {
                path: "/dashboard/all-unconfirmed-bucket-transactions",
                name: "all unconfirmed bucket transactions",
                component: allunconfirmedbuckettransactions,
                meta: {
                    requiresAdmin: true,
                    title: "All Bulk Data transactions",
                },
            },



            {
                path: "/dashboard/referrals",
                name: "referrals",
                component: referralDashboard,
                meta: {
                    requiresAuth: true,
                    title: "Refer & Earn",
                },
            },

            {
                path: "/dashboard/admin/referrals",
                name: "admin-referrals",
                component: referralAdmin,
                meta: {
                    requiresAdmin: true,
                    title: "Referral Program Management",
                },
            },

            {
                path: "/dashboard/admin/whatsapp-transactions",
                name: "admin-whatsapp-transactions",
                component: whatsAppTransactionsAdmin,
                meta: {
                    requiresAdmin: true,
                    title: "WhatsApp Bot Transactions",
                },
            },

            {
                path: "/dashboard/monnify_repush",
                name: "monnify-repush",
                component: MonnifyRepush,
                meta: { requiresAdmin: true, title: "Monnify Repush" },
            },
            {
                path: "/dashboard/prov_repush",
                name: "repush",
                component: repush,
                meta: {
                    requiresAdmin: true,
                    title: "Providus Repush",
                },
            },
            {
                path: "/dashboard/mtn-logins",
                name: "mtnlogins",
                component: mtn_logins,
                meta: {
                    requiresAdmin: true,
                    title: "Mtn Logins",
                },
            },
        ],
    },
];

const router = createRouter({
    history: createWebHistory(),
    routes: routes,
    scrollBehavior() {
        return { left: 0, top: 0 };
    },
});

router.beforeEach((to) => {
    const title = to.meta.title == null ? to.name : to.meta.title;
    document.title = `${title} - ${import.meta.env.MIX_APP_NAME || 'Swiftlinkng'}`;

    if (to.matched.some((record) => record.meta.requiresAuth)) {
        if (Auth.check()) {
            return true;
        }
        Auth.logout();
        return "/login";
    }

    if (to.matched.some((record) => record.meta.requiresAdmin)) {
        const role = Auth.user?.role;
        if (Auth.check() && Number(role) === 1) {
            return true;
        }
        Auth.logout();
        return "/login";
    }

    return true;
});

export default router;
