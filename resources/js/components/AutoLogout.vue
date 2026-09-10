<template>
  <!-- No UI needed, this just runs in the background -->
  <div></div>
</template>

<script>
export default {
  name: "AutoLogout",

  data() {
    return {
      events: ["click", "mousemove", "mousedown", "scroll", "keypress", "load"],
      logoutTimer: null,
      warningTimer: null,
      warningZone: false,
    };
  },

  mounted() {
    this.events.forEach(event => {
      window.addEventListener(event, this.resetTimer);
    });
    this.setTimers();
  },

  unmounted() {
    this.events.forEach(event => {
      window.removeEventListener(event, this.resetTimer);
    });
    this.clearTimers();
  },

  methods: {
    /**
     * Set 20-minute logout timer
     */
    setTimers() {
      const rememberToken = window.localStorage.getItem("remember_token");

      // Fixed at 20 minutes
      const sessionLifetime = 20; // minutes

      if (!rememberToken || rememberToken === "undefined") {
        //console.log("No remember_token → 20 min timeout");
        this.logoutTimer = setTimeout(this.logoutUser, sessionLifetime * 60 * 1000);

        // Warning at 19 minutes (1 min before logout)
        this.warningTimer = setTimeout(
          this.warningMessage,
          (sessionLifetime * 60 - 60) * 1000
        );
      } else {
       // console.log("Remember_token exists → still 20 min timeout");
        this.logoutTimer = setTimeout(this.logoutUser, sessionLifetime * 60 * 1000);
      }

      this.warningZone = false;
    },

    /**
     * Show warning before logout
     */
    warningMessage() {
      this.warningZone = true;
     // console.log("⚠️ Warning: Session will expire in 1 minute");
      // You can show a modal/toast here
    },

    /**
     * Log the user out
     */
    logoutUser() {
      this.axios
        .post(`/api/logout`)
        .then(() => {
          if (this.auth && this.auth.logout) {
            this.auth.logout(); // reset app-level auth state if available
          }

          // Clear storage
          window.localStorage.removeItem("token");
          window.localStorage.removeItem("remember_token");
          window.localStorage.removeItem("user");

          // Redirect
          window.location.href = "/login";
        })
        .catch(error => {
          console.error("Logout failed:", error);
        });
    },

    /**
     * Reset timers when user interacts
     */
    resetTimer() {
      this.clearTimers();
      this.setTimers();
    },

    /**
     * Clear all timers
     */
    clearTimers() {
      clearTimeout(this.logoutTimer);
      clearTimeout(this.warningTimer);
    }
  }
};
</script>
