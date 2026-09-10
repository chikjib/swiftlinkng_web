<template>
    <div>
        <AutoLogout />
        <router-view />
    </div>

</template>

<script>
import Auth from "./Auth.js";
import AutoLogout from "./components/AutoLogout.vue";

export default {
    components: {
        AutoLogout
    },
    data() {
        return {
            loggedUser: this.auth.user,
        };
    },
    mounted() {
        // console.log(this.auth.user);
    },
    methods: {
        logout() {
            this.axios
                .post("/api/logout")
                .then(({ data }) => {
                    Auth.logout(); //reset local storage
                    this.$router.push("/login");
                })
                .catch((error) => {
                    console.log(error);
                });
        },
    },
};
</script>
