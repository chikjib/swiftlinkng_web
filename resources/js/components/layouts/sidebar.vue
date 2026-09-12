<template>
 
  <!-- Sidebar -->
  <ul
    class="navbar-nav sidebar sidebar-light accordion swift-sidebar"
    :class="[
      isAdmin ? 'swift-sidebar--admin' : 'swift-sidebar--customer',
      {
        toggled: isOpen && !isAdmin,
        'swift-sidebar-is-open': isOpen,
      },
    ]"
    id="accordionSidebar"
    tabindex="-1"
    aria-label="Main navigation"
  >
    <li v-if="isOpen" class="swift-sidebar-close-item">
      <button
        type="button"
        class="swift-sidebar-close"
        aria-label="Close navigation menu"
        @click="$emit('close-sidebar')"
      >
        <i class="fas fa-times" aria-hidden="true"></i>
      </button>
    </li>
    <a
      class="sidebar-brand d-flex align-items-center justify-content-center"
      href="/dashboard"
    >
      <div class="sidebar-brand-icon">
        <img :src="'/frontend/images/swiftlogo.png'" />
      </div>
      <div class="sidebar-brand-text mx-2">Swiftlinkng</div>
    </a>
    <div v-if="!isAdmin" class="swift-sidebar-profile">
      <span><i class="fas fa-user"></i></span>
      <div>
        <strong>{{ displayName }}</strong>
        <small>Swiftlinkng account</small>
      </div>
    </div>
    <hr class="sidebar-divider my-0" />
    <li class="nav-item active">
      <a href="/dashboard" class="nav-link">
        <i class="fas fa-fw fa-tachometer-alt"></i>

        <span class="menu-title">Dashboard</span>
      </a>
    </li>
    <hr class="sidebar-divider" />
    <div class="sidebar-heading">Data & Airtime</div>

    <li class="nav-item">
      <a href="/dashboard/airtime" class="nav-link">
        <i class="fas fa-fw fa-mobile"></i>

        <span class="menu-title">Airtime</span>
      </a>
    </li>

    <li class="nav-item">
      <a href="/dashboard/talkmore" class="nav-link">
        <i class="fas fa-fw fa-comments"></i>

        <span class="menu-title">Talk More Airtime</span>
      </a>
    </li>

    <li class="nav-item">
      <a href="/dashboard/data" class="nav-link">
        <i class="fas fa-fw fa-wifi"></i>

        <span class="menu-title">Data</span>
      </a>
    </li>

    <hr class="sidebar-divider" />
    <div class="sidebar-heading">Utilities</div>
    <li class="nav-item">
      <a href="/dashboard/cable" class="nav-link">
        <i class="fas fa-fw fa-laptop"></i>

        <span class="menu-title">Cable Bill</span>
      </a>
    </li>

    <li class="nav-item">
      <a href="/dashboard/electricity" class="nav-link">
        <i class="fas fa-fw fa-burn"></i>

        <span class="menu-title">Electricity</span>
      </a>
    </li>

    <li class="nav-item">
      <a href="/dashboard/exams" class="nav-link">
        <i class="fas fa-fw fa-book"></i>
        <span class="menu-title">Exam Pin</span>
      </a>
    </li>

    <li class="nav-item">
      <a href="/dashboard/airtime2cash" class="nav-link">
        <i class="fas fa-fw fa-money-bill"></i>
        <span class="menu-title">Airtime to Cash</span>
      </a>
    </li>

    <li class="nav-item">
      <a href="/dashboard/sms-disclaimer" class="nav-link">
        <i class="fas fa-fw fa-envelope"></i>

        <span class="menu-title">SMS</span>
      </a>
    </li>

    <hr class="sidebar-divider" />
    <li class="nav-item" :class="{ active: isBulkDataRoute }">
      <button
        type="button"
        class="nav-link bulk-data-toggle"
        :class="{ collapsed: !bulkDataOpen }"
        :aria-expanded="bulkDataOpen ? 'true' : 'false'"
        aria-controls="bulkDataMenu"
        @click="bulkDataOpen = !bulkDataOpen"
      >
        <i class="fas fa-fw fa-database"></i>
        <span class="menu-title">Bulk Data</span>
        <i class="fas fa-chevron-down bulk-data-chevron" aria-hidden="true"></i>
      </button>

      <div v-show="bulkDataOpen" id="bulkDataMenu" class="bulk-data-menu">
        <a href="/dashboard/buy-bulk-data" class="bulk-data-link">
          <i class="fas fa-fw fa-shopping-cart"></i>
          <span>Buy Bulk Data</span>
        </a>
        <a href="/dashboard/vend-bulk-data" class="bulk-data-link">
          <i class="fas fa-fw fa-wifi"></i>
          <span>Vend from Bulk Data</span>
        </a>
        <a href="/dashboard/bulk-data-transactions" class="bulk-data-link">
          <i class="fas fa-fw fa-receipt"></i>
          <span>Bulk Data Transactions</span>
        </a>
        <a href="/dashboard/general-bucket-balances" class="bulk-data-link">
          <i class="fas fa-fw fa-wallet"></i>
          <span>View Bucket Data Balance</span>
        </a>
        <a href="/dashboard/bucket-data-pricing-list" class="bulk-data-link">
          <i class="fas fa-fw fa-tags"></i>
          <span>Bucket Data Pricing List</span>
        </a>
        <a href="/dashboard/user-bucket-sales-analysis" class="bulk-data-link">
          <i class="fas fa-fw fa-chart-line"></i>
          <span>Bucket Sales Analysis</span>
        </a>
      </div>
    </li>


    <hr class="sidebar-divider" />
    <div class="sidebar-heading">Account & Rewards</div>
    <li class="nav-item">
      <a href="/dashboard/profile" class="nav-link">
        <i class="fas fa-fw fa-user-check"></i>
        <span class="menu-title">Profile & Email Verification</span>
      </a>
    </li>
    <li class="nav-item">
      <a href="/dashboard/upgradeaccount" class="nav-link">
        <i class="fas fa-fw fa-mobile"></i>

        <span class="menu-title">Upgrade Account</span>
      </a>
    </li>
    <li class="nav-item">
      <a href="/dashboard/withdraw" class="nav-link">
        <i class="fas fa-fw fa-money-bill"></i>

        <span class="menu-title">Withdraw</span>
      </a>
    </li>

    <li class="nav-item">
      <a href="/dashboard/referrals" class="nav-link">
        <i class="fas fa-fw fa-gift"></i>
        <span class="menu-title">
          Referral Program <span class="badge badge-danger ml-2">New</span>
        </span>
      </a> 
    </li>
    <li class="nav-item">
      <a href="/dashboard/user_sales_analysis" class="nav-link">
        <i class="fas fa-fw fa-th"></i>

        <span class="menu-title"
          >Sales Analysis<span class="badge badge-danger">New</span></span
        >
      </a>
    </li>

    <li class="nav-item">
      <a href="/dashboard/transactions" class="nav-link">
        <i class="fas fa-fw fa-th"></i>

        <span class="menu-title">Transactions</span>
      </a>
    </li>

    <li class="nav-item">
      <a href="https://documenter.getpostman.com/view/20422990/2s93Y5PKhG" target="_blank" class="nav-link">
        <i class="fas fa-fw fa-edit"></i>

        <span class="menu-title">API Documentation<span class="badge badge-danger">New</span></span>
      </a>
    </li>
    

    <div v-if="this.auth.user.role == 1">
      <hr class="sidebar-divider" />
      <div class="sidebar-heading">Admin</div>

      <li class="nav-item">
        <a href="/dashboard/admin/referrals" class="nav-link">
          <i class="fas fa-fw fa-users"></i>
          <span class="menu-title">Referral Program</span>
        </a>
      </li>

      <!-- <li class="nav-item">
        <a href="/dashboard/referral-earnings-audit" class="nav-link">
          <i class="fas fa-fw fa-chart-pie"></i>
          <span class="menu-title">Referral Earnings Audit</span>
        </a>
      </li> -->

      <li class="nav-item">
        <a href="/dashboard/admin/whatsapp-transactions" class="nav-link">
          <i class="fab fa-fw fa-whatsapp"></i>
          <span class="menu-title">WhatsApp Transactions</span>
        </a>
      </li>

      <li class="nav-item">
        <a href="/dashboard/sales_analysis" class="nav-link">
          <i class="fas fa-fw fa-th"></i>

          <span class="menu-title">Sales Analysis</span>
        </a>
      </li>
      <li class="nav-item">
        <a href="/dashboard/accounting-analysis" class="nav-link">
          <i class="fas fa-fw fa-th"></i>

          <span class="menu-title">Sales Accounting Analysis</span>
        </a>
      </li>

      <li class="nav-item">
        <a href="/dashboard/users" class="nav-link">
          <i class="fas fa-fw fa-money-bill"></i>

          <span class="menu-title">Users</span>
        </a>
      </li>

      <li class="nav-item">
        <a href="/dashboard/products" class="nav-link">
          <i class="fas fa-fw fa-th"></i>

          <span class="menu-title">Products</span>
        </a>
      </li>

      <li class="nav-item">
        <a href="/dashboard/alltransactions" class="nav-link">
          <i class="fas fa-fw fa-th"></i>

          <span class="menu-title">All Transactions</span>
        </a>
      </li>

      <li class="nav-item">
        <a
          href="/dashboard/allunconfirmedtransactions"
          class="nav-link"
        >
          <i class="fas fa-fw fa-th"></i>

          <span class="menu-title">Unconfirmed Transact</span>
        </a>
      </li>
            <li class="nav-item">
        <a href="/dashboard/bucket-sales-analysis" class="nav-link">
          <i class="fas fa-fw fa-th"></i>

          <span class="menu-title">Bucket Sales Analysis</span>
        </a>
      </li>


      <li class="nav-item">
        <a href="/dashboard/buckets" class="nav-link">
          <i class="fas fa-fw fa-th"></i>

          <span class="menu-title">Buckets</span>
        </a>
      </li>
      <li class="nav-item">
        <a href="/dashboard/all-bucket-transactions" class="nav-link">
          <i class="fas fa-fw fa-th"></i>

          <span class="menu-title">All Bucket Transactions</span>
        </a>
      </li>
      <li class="nav-item">
        <a href="/dashboard/all-unconfirmed-bucket-transactions" class="nav-link">
          <i class="fas fa-fw fa-th"></i>

          <span class="menu-title">Unconfirmed Bucket Trans.</span>
        </a>
      </li>
      <li class="nav-item">
        <a href="/dashboard/admin-general-bucket-balances" class="nav-link">
          <i class="fas fa-fw fa-th"></i>

          <span class="menu-title">General Bucket Balances</span>
        </a>
      </li>

      <li class="nav-item">
        <a href="/dashboard/slides" class="nav-link">
          <i class="fas fa-fw fa-th"></i>

          <span class="menu-title">Slides</span>
        </a>
      </li>

      <li class="nav-item">
        <a href="/dashboard/prov_repush" class="nav-link">
          <i class="fas fa-fw fa-th"></i>

          <span class="menu-title">Providus Repush</span>
        </a>
      </li>

       <li class="nav-item">
        <a href="/dashboard/mtn-logins" class="nav-link">
          <i class="fas fa-fw fa-th"></i>

          <span class="menu-title">Mtn Logins</span>
        </a>
      </li>

      <li class="nav-item">
        <a href="/dashboard/settings" class="nav-link">
          <i class="fas fa-fw fa-th"></i>

          <span class="menu-title">Settings</span>
        </a>
      </li>
    </div>

    <hr class="sidebar-divider" />
    <div class="version" id="version-ruangadmin"></div>
  </ul>
  <!-- Sidebar -->
</template>



<script>
export default {
  emits: ['close-sidebar'],
  props: {
    isAdmin: {
      type: Boolean,
      default: false,
    },
    isOpen: {
      type: Boolean,
      default: false,
    },
  },
  data() {
    return {
      bulkDataOpen: false,
    };
  },
  computed: {
    displayName() {
      const user = this.auth && this.auth.user ? this.auth.user : {};
      return [user.firstname, user.lastname].filter(Boolean).join(" ") || "My account";
    },
    isBulkDataRoute() {
      return [
        "/dashboard/buy-bulk-data",
        "/dashboard/vend-bulk-data",
        "/dashboard/bulk-data-transactions",
        "/dashboard/general-bucket-balances",
        "/dashboard/bucket-data-pricing-list",
        "/dashboard/user-bucket-sales-analysis",
      ].includes(this.$route.path);
    },
  },
  watch: {
    "$route.path"() {
      if (this.isBulkDataRoute) {
        this.bulkDataOpen = true;
      }
    },
  },
  mounted() {
    this.bulkDataOpen = this.isBulkDataRoute;
  },
  methods: {
    logout() {
      this.axios
        .post(`${this.$appUrl}/api/logout`)
        .then(({ data }) => {
          this.auth.logout(); //reset local storage
          this.$router.push("/login");
        })
        .catch((error) => {
          console.log(error);
        });
    },
  },
};
</script>

<style scoped>
.swift-sidebar-close-item {
  display: none;
}

@media (max-width: 767.98px) {
  .swift-sidebar-close-item {
    position: sticky;
    top: 0;
    z-index: 3;
    display: flex;
    flex: 0 0 auto;
    justify-content: flex-end;
    min-height: 48px;
    padding: 6px 10px 0;
    pointer-events: none;
  }

  .swift-sidebar-close {
    display: inline-grid;
    width: 40px;
    height: 40px;
    padding: 0;
    place-items: center;
    color: #fff;
    font-size: 20px;
    background: #9f071d;
    border: 2px solid #fff;
    border-radius: 50%;
    box-shadow: 0 3px 12px rgba(0, 0, 0, .3);
    pointer-events: auto;
    -webkit-tap-highlight-color: transparent;
    touch-action: manipulation;
  }
}

.bulk-data-toggle {
  align-items: center;
  background: transparent;
  border: 0;
  cursor: pointer;
  display: flex;
  text-align: left;
  width: 100%;
}

.bulk-data-toggle .menu-title {
  flex: 1;
}

.bulk-data-chevron {
  font-size: 11px;
  margin-left: 8px;
  transition: transform 0.2s ease;
}

.bulk-data-toggle[aria-expanded="true"] .bulk-data-chevron {
  transform: rotate(180deg);
}

.bulk-data-menu {
  background: rgba(255, 255, 255, 0.65);
  border-radius: 8px;
  margin: 0 12px 8px;
  padding: 6px 0;
}

.bulk-data-link {
  align-items: center;
  color: #5f646b;
  display: flex;
  font-size: 13px;
  gap: 7px;
  padding: 9px 12px;
  text-decoration: none;
}

.bulk-data-link:hover,
.bulk-data-link.router-link-active {
  background: #fbeaec;
  color: #d20b2d;
  text-decoration: none;
}
</style>
