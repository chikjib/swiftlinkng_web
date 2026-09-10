<template>
  <div class="col-md-12">
    <div class="card swift-admin-update-card mb-4">
      <div class="card-body">
        <div class="swift-admin-update-heading">
          <div class="swift-admin-update-icon">
            <i class="fas fa-bell"></i>
          </div>
          <div>
            <h4 class="card-title card-title-dash mb-1">App Dashboard Update</h4>
            <p class="text-muted mb-0">Edit the “Updates for You” card shown on the Swiftlink app dashboard.</p>
          </div>
        </div>

        <form class="swift-admin-update-form mt-4" @submit.prevent="saveDashboardUpdate">
          <div class="form-group">
            <label for="app-update-eyebrow">Small heading</label>
            <input id="app-update-eyebrow" v-model.trim="dashboardUpdate.eyebrow" maxlength="40" required class="form-control" />
          </div>
          <div class="form-group">
            <label for="app-update-title">Update title</label>
            <input id="app-update-title" v-model.trim="dashboardUpdate.title" maxlength="120" required class="form-control" />
          </div>
          <div class="form-group swift-admin-update-message">
            <label for="app-update-message">Short description</label>
            <textarea id="app-update-message" v-model.trim="dashboardUpdate.message" maxlength="240" rows="3" class="form-control"></textarea>
          </div>
          <div class="form-group swift-admin-update-details">
            <label for="app-update-details">Full update details</label>
            <textarea id="app-update-details" v-model.trim="dashboardUpdate.details" maxlength="5000" rows="7" class="form-control" placeholder="Write the complete information users should see after selecting View Details."></textarea>
            <small class="form-text text-muted">This appears on the dedicated “Updates for You” page in the website and mobile app.</small>
          </div>
          <div class="form-group swift-admin-update-link">
            <label for="app-update-link">Optional link</label>
            <input id="app-update-link" v-model.trim="dashboardUpdate.link" type="url" placeholder="https://…" class="form-control" />
          </div>
          <label class="swift-toggle-field">
            <input v-model="dashboardUpdate.enabled" type="checkbox" />
            <span>Show this update in the app</span>
          </label>
          <button type="submit" class="btn btn-danger" :disabled="dashboardUpdateSaving">
            {{ dashboardUpdateSaving ? "Saving…" : "Save App Update" }}
          </button>
        </form>
      </div>
    </div>

    <div class="card">
      <div class="card-body">
        <div class="col-lg-12">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
              <h4 class="card-title card-title-dash">Admin Settings</h4>
            </div>
          </div>
          <div class="mt-3">
            <div class="table-responsive">
              <table class="table table-bordered">
                <thead>
                  <tr>
                    <th>ID</th>
                    <th>Key</th>
                    <th>Value</th>
                    <th>Created At</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="setting in settings.data" :key="setting.id">
                    <td>{{ setting.id }}</td>
                    <td>{{ setting.key }}</td>
                    <td>{{ setting.value }}</td>

                    <td>{{ setting.created_at }}</td>
                    <td>
                      <div class="btn-group" role="group">
                        <a
                          class="btn btn-danger"
                          href="#"
                          @click.stop="toggleModal(setting.id)"
                          >Edit</a
                        >

                        <edit-settings-modal
                          :show="showModal(setting.id)"
                          :setting="setting"
                          @close="toggleModal(setting.id)"
                        />
                        <button
                          class="btn btn-danger"
                          @click="deleteSetting(setting.id)"
                        >
                          Delete
                        </button>
                      </div>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
 
<script>
import EditSettingsModal from "./EditSettingsModal.vue";
//import farmersprofile from "./farmersprofile.vue";

const token = window.localStorage.getItem("token");

export default {
  components: { EditSettingsModal },
  data() {
    return {
      settings: {},

      keyword: null,

      activeModal: 0,
      dashboardUpdateSaving: false,
      dashboardUpdate: {
        eyebrow: "Updates for You",
        title: "OPay wallet funding is now available!",
        message: "Enjoy instant funding with low charges.",
        details: "OPay wallet funding is now available on Swiftlinkng. Open Fund Wallet, choose My Personal Account and transfer to your OPay account for fast, secure wallet funding.",
        link: "",
        enabled: true,
      },
    };
  },

  mounted() {
    //this.getusers();
    this.getProd();
    this.loadDashboardUpdate();
  },

  watch: {
    keyword(after, before) {
      // this.getResults();
    },
  },

  methods: {
    loadDashboardUpdate() {
      axios
        .get('/api/app/dashboard-update')
        .then((response) => {
          if (response.data && response.data.data) {
            this.dashboardUpdate = response.data.data;
          }
        })
        .catch(() => this.$toasted.show('Unable to load the app dashboard update.'));
    },

    saveDashboardUpdate() {
      this.dashboardUpdateSaving = true;
      axios
        .put('/api/admin/app-dashboard-update', this.dashboardUpdate, {
          headers: {
            Authorization: `Bearer ${token}`,
            'Content-Type': 'application/json',
          },
        })
        .then((response) => {
          if (response.data && response.data.data) {
            this.dashboardUpdate = response.data.data;
          }
          this.$toasted.show('App dashboard update saved successfully.');
        })
        .catch((error) => {
          const data = error.response && error.response.data;
          const validation = data && data.errors
            ? Object.values(data.errors).flat().join(' ')
            : null;
          this.$toasted.show(validation || (data && data.message) || 'Unable to save the app dashboard update.');
        })
        .finally(() => {
          this.dashboardUpdateSaving = false;
        });
    },

    showModal: function (id) {
      return this.activeModal === id;
    },
    toggleModal: function (id) {
      if (this.activeModal !== 0) {
        this.activeModal = 0;
        return false;
      }
      this.activeModal = id;
    },

    getProd() {
      axios
        .get(`/api/admin/settings`, {
          headers: {
            Authorization: `Bearer ${token}`,
            "Content-Type": "application/json",
          },
        })
        .then((response) => {
          console.log(response.data);
          // this.users = response.data.data;
          this.settings = response.data;
        });
    },

    deleteSetting(id) {
      if (confirm("Do you really want to delete?")) {
        axios
          .delete(`/api/admin/settings/${id}`, {
            headers: {
              Authorization: `Bearer ${token}`,
              "Content-Type": "application/json",
            },
          })
          .then((response) => {
            let i = this.settings.data.map((item) => item.id).indexOf(id); // find index of object
            this.settings.data.splice(i, 1);
          });
      }
    },
  },
};
</script>
