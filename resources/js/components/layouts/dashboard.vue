<template>
  <div
    id="page-top"
    :class="[
      'swift-dashboard-shell',
      isAdminRoute ? 'swift-admin-shell' : 'swift-customer-shell',
      isDashboardRoute ? 'swift-home-route' : '',
      sidebarToggled ? 'sidebar-toggled' : '',
    ]"
  >
    <div id="wrapper" class="swift-app-shell">
      <Sidebar :is-admin="isAdminRoute" :is-open="sidebarToggled"></Sidebar>
      <button
        v-if="sidebarToggled"
        type="button"
        class="swift-sidebar-backdrop"
        aria-label="Close navigation menu"
        @click="sidebarToggled = false"
      ></button>

      <div id="content-wrapper" class="d-flex flex-column swift-content-wrapper">
        <div id="content">
          <Header
            :is-admin="isAdminRoute"
            :is-dashboard="isDashboardRoute"
            :sidebar-toggled="sidebarToggled"
            @toggle-sidebar="toggleSidebar"
          ></Header>

          <!-- Container Fluid-->
          <div class="container-fluid swift-page-content" id="container-wrapper">
            <div
              v-if="isAdminRoute"
              class="d-sm-flex align-items-center justify-content-between mb-4"
            >
              <h1 class="h3 mb-0 text-gray-800">
                {{ this.$route.meta.title }}
              </h1>
              <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="/dashboard">Home</a></li>
                <li class="breadcrumb-item active" aria-current="page">
                  {{ this.$route.meta.title }}
                </li>
              </ol>
            </div>

            <router-view></router-view>
          </div>
          <!---Container Fluid-->
        </div>
        <Footer></Footer>
      </div>
    </div>

    <nav v-if="!isAdminRoute" class="swift-bottom-navigation" aria-label="Primary navigation">
      <a href="/dashboard" :class="{ 'is-active': $route.path === '/dashboard' }">
        <i class="fas fa-home"></i>
        <span>Home</span>
      </a>
      <a href="/dashboard/transactions" :class="{ 'is-active': $route.path === '/dashboard/transactions' }">
        <i class="fas fa-receipt"></i>
        <span>Transactions</span>
      </a>
      <a href="https://whatsapp.com/channel/0029VbAqSujJ93wMUMjxBL3d" target="_blank" rel="noopener">
        <i class="far fa-comment-dots"></i>
        <span>Chat Us</span>
      </a>
      <a href="/dashboard/referrals" :class="{ 'is-active': $route.path.startsWith('/dashboard/referrals') }">
        <i class="fas fa-users"></i>
        <span>Refer &amp; Earn</span>
      </a>
      <a href="/dashboard/profile" :class="{ 'is-active': $route.path === '/dashboard/profile' }">
        <i class="far fa-user"></i>
        <span>Profile</span>
      </a>
    </nav>

  </div>
</template>



<script>
import Header from "./header.vue";
import Footer from "./footer.vue";
import Sidebar from "./sidebar.vue";

export default {
  components: {
    Header,
    Footer,
    Sidebar,
  },
  data() {
    return {
      events: ["click", "mousemove", "mousedown", "scroll", "keypress", "load"],
      // warningTimer: null,
      logoutTimer: null,
      sidebarToggled: false,
      // warningZone: false,
    };
  },
  computed: {
    isAdminRoute() {
      return this.$route.matched.some((record) => record.meta.requiresAdmin);
    },
    isDashboardRoute() {
      return this.$route.name === 'dashboard';
    },
  },
  watch: {
    "$route.fullPath"() {
      this.syncCustomerTheme();
      if (this.isAdminRoute || window.innerWidth < 768) this.sidebarToggled = false;
    },
  },
  created() {
    this.syncCustomerTheme();
  },
  mounted() {
    document.documentElement.classList.add('swift-dashboard-page-scroll');
    this.events.forEach(function (event) {
      window.addEventListener(event, this.resetTimer);
    }, this);
    this.setTimers();
  },
  unmounted() {
    document.documentElement.classList.remove('swift-dashboard-page-scroll');
    this.events.forEach(function (event) {
      window.removeEventListener(event, this.resetTimer);
    }, this);
    this.resetTimer();
  },

  //   created() {
  //     this.autoLogout();
  //   },

  methods: {
    toggleSidebar() {
      this.sidebarToggled = !this.sidebarToggled;
    },
    syncCustomerTheme() {
      const theme = document.getElementById('swiftlink-customer-theme')
        || document.querySelector('link[rel="stylesheet"][href*="/build/assets/swiftlink-"]');
      if (theme && !theme.id) theme.id = 'swiftlink-customer-theme';
      if (theme) theme.disabled = this.isAdminRoute;
    },
    setTimers() {
      var remember_token = window.localStorage.getItem("remember_token");
      //   console.log(remember_token);
      // console.log(remember_token == "null");
      // this.warningTimer = setTimeout(this.warningMessage, process.env.MIX_SESSION_LIFETIME -1 * 60 * 1000); // 14 minutes - 14 * 60 * 1000

      if (remember_token != "null") {
        console.log("it is here");
        this.logoutTimer = setTimeout(
          this.logout,
          (Number(import.meta.env.MIX_SESSION_LIFETIME) || 15) * 60 * 1000
        ); // 15 minutes - 15 * 60 * 1000
      } else {
        console.log("it is here 2");
        this.logoutTimer = setTimeout(this.logout, 400 * 60 * 1000); // 15 minutes - 15 * 60 * 1000
      }
    },
    resetTimer() {
    //   clearTimeout(this.warningTimer);
      clearTimeout(this.logoutTimer);
      this.setTimers();
    },
    // autoLogout() {
    // //   var token = window.localStorage.getItem("token");
    // //   if (token == null) {
    // //     window.location.href = "/login";
    // //   }

    // },
    logout() {
      this.axios
        .post(`/api/logout`)
        .then(({ data }) => {
          this.auth.logout(); //reset local storage
          // this.$router.push("/login");
          window.location.href = "/login";
        })
        .catch((error) => {
          console.log(error);
        });
    },

    onidle() {
      this.logout();
    },
    onremind(time) {
      // alert seconds remaining to 00:00
      alert("You will be logged out in " + time + " seconds");
    },
  },
};
</script>

<style>
html.swift-dashboard-page-scroll {
  overflow-y: scroll;
}

.swift-dashboard-shell,
.swift-dashboard-shell .swift-app-shell {
  width: 100%;
  min-width: 0;
  overflow-x: hidden;
}

.swift-dashboard-shell .swift-app-shell {
  display: flex;
}

.swift-dashboard-shell .swift-sidebar {
  position: relative;
  top: auto;
  align-self: stretch;
  box-sizing: border-box;
  flex: 0 0 var(--swift-sidebar-width, 260px);
  min-height: 100vh;
  min-height: 100dvh;
  height: auto;
  max-height: none;
  overflow: visible !important;
  padding-bottom: 24px;
}

.swift-dashboard-shell .swift-content-wrapper {
  flex: 1 1 auto;
  width: auto !important;
  min-width: 0;
  margin-left: 0 !important;
  transform: none !important;
}

@media (min-width: 768px) {
  .swift-dashboard-shell.sidebar-toggled .swift-sidebar {
    flex-basis: 0;
    width: 0 !important;
    min-width: 0 !important;
    margin-left: 0 !important;
    overflow: hidden !important;
    transform: translateX(-100%) !important;
  }

  .swift-dashboard-shell.sidebar-toggled .swift-content-wrapper {
    width: auto !important;
    margin-left: 0 !important;
  }
}

@media (max-width: 767.98px) {
  .swift-dashboard-shell .swift-app-shell {
    display: block;
  }

  .swift-dashboard-shell .swift-sidebar {
    position: fixed;
    inset: 0 auto 0 0;
    z-index: 1080;
    width: min(84vw, 310px) !important;
    min-width: min(84vw, 310px) !important;
    min-height: 100vh !important;
    min-height: 100dvh !important;
    height: 100vh !important;
    height: 100dvh !important;
    max-height: 100vh;
    max-height: 100dvh;
    overflow-x: hidden !important;
    overflow-y: scroll !important;
    overscroll-behavior: contain;
    scrollbar-color: #b7bac2 transparent;
    scrollbar-gutter: stable;
    scrollbar-width: thin;
    transform: translateX(-105%) !important;
  }

  .swift-dashboard-shell .swift-sidebar::-webkit-scrollbar { width: 7px; }
  .swift-dashboard-shell .swift-sidebar::-webkit-scrollbar-track { background: transparent; }
  .swift-dashboard-shell .swift-sidebar::-webkit-scrollbar-thumb { background: #b7bac2; border-radius: 999px; }

  .swift-dashboard-shell .swift-sidebar.toggled {
    margin-left: 0 !important;
    transform: translateX(0) !important;
    overflow-x: hidden !important;
    overflow-y: scroll !important;
  }

  .swift-dashboard-shell .swift-content-wrapper {
    width: 100% !important;
    margin-left: 0 !important;
    transform: none !important;
  }
}

/* Admin navigation is an overlay at every viewport size. It stays out of the
   page flow so opening it never pushes or clips the dashboard. */
.swift-dashboard-shell.swift-admin-shell .swift-sidebar--admin {
  position: fixed;
  inset: 0 auto 0 0;
  z-index: 1080;
  display: flex;
  width: min(86vw, var(--swift-sidebar-width, 260px)) !important;
  min-width: min(86vw, var(--swift-sidebar-width, 260px)) !important;
  height: 100vh !important;
  height: 100dvh !important;
  min-height: 100vh !important;
  min-height: 100dvh !important;
  max-height: 100dvh !important;
  flex-basis: auto !important;
  align-content: flex-start;
  overflow-x: hidden !important;
  overflow-y: auto !important;
  overscroll-behavior: contain;
  transform: translateX(-105%) !important;
}

.swift-dashboard-shell.swift-admin-shell.sidebar-toggled .swift-sidebar--admin {
  margin-left: 0 !important;
  overflow-x: hidden !important;
  overflow-y: auto !important;
  transform: translateX(0) !important;
}

.swift-admin-shell .swift-sidebar-backdrop {
  position: fixed;
  inset: 0;
  z-index: 1075;
  display: block;
  width: 100%;
  height: 100%;
  padding: 0;
  background: rgba(10, 11, 14, .58);
  border: 0;
}

.sidebar .nav-item .router-link-exact-active {
  background: #fff;
  position: relative;
  font-weight: bold;
  font-size: 13px;
}

/*
  Enter and leave animations can use different
  durations and timing functions.
*/
.slide-fade-enter-active {
  transition: all 0.3s ease-out;
}

.slide-fade-leave-active {
  transition: all 0.8s cubic-bezier(1, 0.5, 0.8, 1);
}

.slide-fade-enter-from,
.slide-fade-leave-to {
  transform: translateX(20px);
  opacity: 0;
}
</style>
