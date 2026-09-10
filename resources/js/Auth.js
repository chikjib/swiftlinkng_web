import axios from "axios";

class Auth {
    constructor() {
        this.token = window.localStorage.getItem("token");
        let userData = window.localStorage.getItem("user");
        this.user = userData ? JSON.parse(userData) : null;

        if (this.token) {
            axios.defaults.headers.common["Authorization"] =
                "Bearer " + this.token;
        }
    }
    login(token, user) {
        // alert(JSON.stringify(user));
        // alert(user.remember_token);
        // console.log(user.remember_token);
        this.clearSession();
        localStorage.setItem("token", token);
        localStorage.setItem("remember_token", user.remember_token);
        localStorage.setItem("user", JSON.stringify(user));
        axios.defaults.headers.common["Authorization"] = "Bearer " + token;

        this.token = token;
        this.user = user;
    }
    check() {
        return !!this.token;
    }
    logout() {
        this.clearSession();
        this.user = null;
        this.token = null;
    }
    clearSession() {
        localStorage.removeItem("token");
        localStorage.removeItem("remember_token");
        localStorage.removeItem("user");
        localStorage.removeItem("keyword");
        localStorage.removeItem("field");
        delete axios.defaults.headers.common["Authorization"];
    }
}
export default new Auth();
