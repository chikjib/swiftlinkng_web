import Auth from "./Auth.js";
import { createRouter, createWebHistory } from "vue-router";

import Login from "./components/login.vue";
import Register from "./components/register.vue";
import VerifyEmailNotice from "./components/VerifyEmailNotice.vue";
import forgotpassword from "./components/forgot.vue";
import VerifyToken from "./components/verifytoken.vue";
import Reset from "./components/reset.vue";

import Dashboard from "./components/dashboard.vue";
import UpdateDetails from "./components/updates/UpdateDetails.vue";

import dashboardLayout from "./components/layouts/dashboard.vue";

import data from "./components/data/Buydata.vue";
import bulkdata from "./components/data/BulkData.vue";
import vendbulkdata from "./components/data/VendBulkData.vue";
import bulkdatatransactions from "./components/data/BulkDataTransactions.vue";
import singlebuckettransaction from "./components/data/single_bucket_transactions.vue";
import allbuckettransactions from "./components/data/all_bucket_transactions.vue";
import bucketdatapricing from "./components/data/bucket_data_pricing_list.vue";
import general_bucket_balances from "./components/data/general_bucket_balances.vue";
import purchasemode from "./components/data/purchase_mode.vue";
import allunconfirmedbuckettransactions from "./components/data/all_unconfirmed_bucket_transactions.vue";
import airtime from "./components/data/Airtime.vue";
import upgradeaccount from "./components/data/UpgradeAccount.vue";

import cable from "./components/data/Cable.vue";
import electricity from "./components/data/Electricity.vue";
import airtime2cash from "./components/data/Airtime2Cash.vue";
import verifyotpmtn from "./components/data/verify_otp_mtn.vue";
import exams from "./components/data/Exam.vue";
import transactions from "./components/data/transactions.vue";
import alltransactions from "./components/data/alltransactions.vue";
import allunconfirmedtransactions from "./components/data/allunconfirmedtransactions.vue";
import viewtransaction from "./components/data/transactionsbyuser.vue";
import viewbuckettransaction from "./components/data/buckettransactionsbyuser.vue";
import manual from "./components/data/Manual.vue";
import fundWallet from "./components/data/FundWallet.vue";
import admin_general_bucket_balances from "./components/data/admin_general_bucket_balances.vue";
import commission from "./components/commissions/allcommissions.vue";
import withdraw from "./components/data/Withdraw.vue";

import settings from "./components/settings/Settings.vue";
import mtn_logins from "./components/categories/allmtnlogins.vue";
import users from "./components/users/allusers.vue";
import edituser from "./components/users/edituser.vue";
import receipt from "./components/data/Receipt.vue";
import bucketreceipt from "./components/data/BucketReceipt.vue";

import products from "./components/categories/allcategories.vue";
import adminBucket from "./components/categories/allbuckets.vue";
import editAdminBucket from "./components/categories/editBuckets.vue";
import allpins from "./components/categories/allpins.vue";
import editcategory from "./components/categories/EditCategory.vue";
import editpin from "./components/categories/editpins.vue";
import profile from "./components/settings/Profile.vue";
import BvnVerify from "./components/settings/BvnVerify.vue";
import slides from "./components/sliders/allslides.vue";
import sms from "./components/data/Sms.vue";
import sales_analysis from "./components/data/sales_analysis.vue";
import user_sales_analysis from "./components/data/user_sales_analysis.vue";
import admin_user_sales_analysis from "./components/data/admin_user_sales_analysis.vue";

import bucket_sales_analysis from "./components/data/bucket_sales_analysis.vue";
import user_bucket_sales_analysis from "./components/data/user_bucket_sales_analysis.vue";
import admin_user_bucket_sales_analysis from "./components/data/admin_user_bucket_sales_analysis.vue";

import repush from "./components/data/Repush.vue";
import referralDashboard from "./components/referrals/ReferralDashboard.vue";
import referralAdmin from "./components/referrals/ReferralAdmin.vue";
import whatsAppTransactionsAdmin from "./components/whatsapp/WhatsAppTransactionsAdmin.vue";

import callback from "./components/callback.vue";
import Admin_accounting_analysis from "./components/data/admin_accounting_analysis.vue";
import Sms_disclaimer from "./components/data/sms_disclaimer.vue";

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
                component: Sms_disclaimer,
                meta: {
                    requiresAuth: true,
                    title: "Bulk SMS Disclaimer",
                },
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
