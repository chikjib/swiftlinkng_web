/**
 * First we will load all of this project's JavaScript dependencies which
 * includes Vue and other libraries. It is a great starting point when
 * building robust, powerful web applications using Vue and Laravel.
 */
import "./bootstrap";
import Toasted from "vue-toasted";

import { createApp, configureCompat } from "vue";
import LaravelPagination from "./components/LaravelPagination.vue";
import moment from "moment";
//import Flutterwave from "vue-flutterwave";

//import LaravelPermissionToVueJS from "laravel-permission-to-vuejs";

import axios from "axios";
import Auth from "./Auth.js";
import { installTransactionSecurity } from "./transactionSecurity";

import VueHtmlToPaper from "vue-html-to-paper";
import numeral from "numeral";

configureCompat({
    MODE: 2,
    // Vue Router 3 installs this hook globally to unregister route instances.
    // Keep the compatibility behavior, but do not warn for every component
    // unmounted while the application completes its Vue 3 migration.
    OPTIONS_DESTROYED: "suppress-warning",
});

installTransactionSecurity(axios);

const options = {
    name: "_blank",
    specs: ["fullscreen=yes", "titlebar=yes", "scrollbars=yes"],
    styles: [
        "https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css",
        "https://unpkg.com/kidlat-css/css/kidlat.css",
    ],
};

axios.interceptors.response.use(
  response => response, // if response is fine, return it
  error => {
    if (error.response && error.response.status === 401) {
      console.warn("⚠️ Session expired or unauthorized. Logging out...");

      Auth.logout();

    //   // optional: also request logout API if you want server cleanup
    //   axios.post("/api/logout").catch(() => {});

      // redirect to login page
      window.location.href = "/login";
    }

    return Promise.reject(error);
  }
);

import App from "./app.vue";
import router from "./routes";

const app = createApp(App);

// These small plugins only need Vue's component registry and global-property
// bag. Supplying that surface keeps their existing APIs available without
// booting the application through Vue 2's removed global constructor API.
const legacyPluginTarget = {
    component: (...args) => app.component(...args),
    prototype: app.config.globalProperties,
};

Toasted.install(legacyPluginTarget, { duration: 1000 });
VueHtmlToPaper.install(legacyPluginTarget, options);
app.config.globalProperties.axios = axios;
app.config.globalProperties.auth = Auth;
app.config.globalProperties.$token = import.meta.env.MIX_VUE_APP_TOKEN;
app.config.globalProperties.$appUrl = import.meta.env.MIX_APP_URL || window.location.origin;

app.component("pagination", LaravelPagination);

app.mixin({
    methods: {
        formatDateTime(value) {
            return value ? moment(String(value)).format("DD/MM/YYYY hh:mm") : "";
        },
        formatDate(value) {
            return value ? moment(String(value)).format("DD/MM/YYYY") : "";
        },
        formatNumber(value) {
            return numeral(value).format("0,0.00");
        },
        formatPrice(value) {
            return numeral(value).format("0,0");
        },
    },
});

// axios.interceptors.request.use((config) => {
//     config.baseURL = "http://127.0.0.1:8000";
//     config.withCredentials = false;
//     // config.headers.common["Access-Control-Allow-Origin"] =
//     //     "https://127.0.0.1:8000";

//     return config;
// });

app.use(router);
app.mount("#app");
