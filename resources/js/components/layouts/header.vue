
<template>
  <div>
        <!-- TopBar -->
    <nav
      class="navbar navbar-expand navbar-light bg-navbar topbar mb-4 static-top swift-topbar"
      :class="isAdmin ? 'swift-topbar--admin' : 'swift-topbar--customer'"
    >
      <div class="swift-topbar-leading">
        <button
          v-if="isAdmin || isDashboard"
          id="sidebarToggleTop"
          type="button"
          class="btn btn-link rounded-circle swift-menu-trigger"
          :aria-label="sidebarToggled ? 'Hide navigation menu' : 'Show navigation menu'"
          :aria-expanded="sidebarToggled ? 'true' : 'false'"
          @click="$emit('toggle-sidebar')"
        >
          <i class="fa fa-bars"></i>
        </button>
        <button
          v-else
          type="button"
          class="btn btn-link rounded-circle swift-menu-trigger swift-page-back"
          aria-label="Go back"
          @click="$router.back()"
        >
          <i class="fas fa-arrow-left"></i>
        </button>

        <div
          class="swift-app-downloads"
          :class="{ 'swift-admin-app-downloads': isAdmin }"
          aria-label="Download the Swiftlinkng mobile app"
        >
          <a class="swift-store-link swift-store-link--google" target="_blank" rel="noopener" href="https://play.google.com/store/apps/details?id=com.swiftlink.swiftlink" aria-label="Download on Google Play">
            <img class="swift-store-badge" src="/template/images/services/google.png" width="100" height="29" alt="Get it on Google Play" />
            <img class="swift-store-icon" src="/template/vendor/fontawesome-free/svgs/brands/google-play.svg" alt="" aria-hidden="true" />
          </a>
          <a class="swift-store-link swift-store-link--apple" target="_blank" rel="noopener" href="https://apps.apple.com/ng/app/swiftlinkng/id6480324011" aria-label="Download on the App Store">
            <img class="swift-store-badge" src="/template/images/services/ios.png" width="100" height="28" alt="Download on the App Store" />
            <img class="swift-store-icon" src="/template/vendor/fontawesome-free/svgs/brands/apple.svg" alt="" aria-hidden="true" />
          </a>
        </div>
      </div>

      <a v-if="!isAdmin && isDashboard" href="/dashboard" class="swift-topbar-brand" aria-label="Swiftlinkng dashboard">
        <img src="/frontend/images/swiftlogo.png" alt="Swiftlinkng" />
        <strong>Swiftlinkng</strong>
      </a>
      <strong v-else-if="!isAdmin" class="swift-topbar-title">{{ pageTitle }}</strong>

      <ul class="navbar-nav ml-auto">
        <li v-if="!isAdmin" class="nav-item swift-notification-item">
          <a href="/dashboard/update-details" class="swift-notification-button" aria-label="View updates for you">
            <i class="far fa-bell"></i>
            <span aria-hidden="true"></span>
          </a>
        </li>
        <li class="nav-item dropdown no-arrow">
          <a
            class="nav-link dropdown-toggle"
            href="#"
            id="userDropdown"
            role="button"
            data-toggle="dropdown"
            aria-haspopup="true"
            aria-expanded="false"
          >
            <img
              class="img-profile rounded-circle"
              :src="'/template/img/boy.png'"
              style="max-width: 60px"
            />
            <span class="ml-2 d-none d-lg-inline text-white small">{{ user.firstname || user.lastname }}</span>
          </a>
          <div
            class="dropdown-menu dropdown-menu-right shadow animated--grow-in"
            aria-labelledby="userDropdown"
          >
            <a class="dropdown-item" href="/dashboard/profile">
              <i class="fas fa-user fa-sm fa-fw mr-2 text-gray-400"></i>
              Profile
            </a>

            <div class="dropdown-divider"></div>
            <a
              class="dropdown-item"
              href="javascript:void(0);"
              data-toggle="modal"
              data-target="#logoutModal"
            >
              <i class="fas fa-sign-out-alt fa-sm fa-fw mr-2 text-gray-400"></i>
              Logout
            </a>
          </div>
        </li>
      </ul>
    </nav>
    <!-- Topbar -->

    <!-- Modal Logout -->
    <div
      class="modal fade"
      id="logoutModal"
      tabindex="-1"
      role="dialog"
      aria-labelledby="exampleModalLabelLogout"
      aria-hidden="true"
    >
      <div class="modal-dialog" role="document">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="exampleModalLabelLogout">Ohh No!</h5>
            <button
              type="button"
              class="close"
              data-dismiss="modal"
              aria-label="Close"
            >
              <span aria-hidden="true">&times;</span>
            </button>
          </div>
          <div class="modal-body">
            <p>Are you sure you want to logout?</p>
          </div>
          <div class="modal-footer">
            <button
              type="button"
              class="btn btn-outline-primary"
              data-dismiss="modal"
            >
              Cancel
            </button>

            <a
              class="btn btn-danger"
              href="javascript:void(0)"
              @click="logout()"
            >
              Logout</a
            >
          </div>
        </div>
      </div>
    </div>
  </div>
</template>



<script>
export default {
  emits: ['toggle-sidebar'],
  props: {
    isAdmin: {
      type: Boolean,
      default: false,
    },
    sidebarToggled: {
      type: Boolean,
      default: false,
    },
    isDashboard: {
      type: Boolean,
      default: false,
    },
  },
  computed: {
    pageTitle() {
      return this.$route.meta.title || 'Swiftlinkng';
    },
  },
  data() {
    return {
      user: this.auth.user,
    };
  },

  methods: {
    logout() {
      this.axios
        .post(`/api/logout`)
        .then(({ data }) => {
          this.auth.logout(); //reset local storage
        window.location.href = "/login";
        })
        .catch((error) => {
          console.log(error);
        });
    },
  },
};
</script>

<style scoped>
.swift-topbar .swift-app-downloads img.swift-store-badge {
  display: block !important;
  flex: 0 0 100px;
  width: 100px !important;
  min-width: 100px !important;
  max-width: none !important;
  height: auto !important;
  object-fit: contain;
  opacity: 1 !important;
  visibility: visible !important;
}

.swift-admin-app-downloads {
  display: flex !important;
  min-width: 216px;
  align-items: center;
  gap: 8px;
  opacity: 1 !important;
  visibility: visible !important;
}

.swift-admin-app-downloads > a {
  display: inline-flex !important;
  width: 104px;
  min-width: 104px;
  height: 38px;
  align-items: center;
  justify-content: center;
  padding: 4px 2px;
  overflow: visible;
  background-color: #fff !important;
  border: 1px solid #e3e6f0;
  border-radius: 9px;
  opacity: 1 !important;
  visibility: visible !important;
}

.swift-admin-app-downloads .swift-store-badge {
  position: relative;
  z-index: 1;
}

.swift-admin-app-downloads .swift-store-icon {
  display: none !important;
}

@media (max-width: 767.98px) {
  .swift-admin-app-downloads {
    min-width: 76px;
    gap: 4px;
  }

  .swift-admin-app-downloads > a {
    width: 36px;
    min-width: 36px;
    height: 36px;
    padding: 7px;
  }

  .swift-topbar .swift-app-downloads img.swift-store-badge {
    display: none !important;
  }

  .swift-admin-app-downloads .swift-store-icon {
    display: block !important;
    width: 20px;
    height: 20px;
    opacity: 1 !important;
    visibility: visible !important;
  }
}
</style>
