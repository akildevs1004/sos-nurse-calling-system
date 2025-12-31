<template></template>
<script>
export default {
  layout: "login",
  async created() {




    this.loading = true;

    try {
      // 1) Clear Nuxt Auth (token + user + storage)
      // This is the most important part.
      await this.$auth.logout();
    } catch (e) { }

    try {
      // 2) Clear your TV auto-login saved credentials
      if (process.client) {
        localStorage.removeItem("saved_email");
        localStorage.removeItem("saved_password");
      }
    } catch (e) { }

    try {
      // 3) Clear any Vuex stored credentials (if you use these mutations)
      try { this.$store.commit("email", null); } catch (e) { }
      try { this.$store.commit("password", null); } catch (e) { }

      // 4) Reset any module states you already use
      try { await this.$store.dispatch("dashboard/resetState"); } catch (e) { }
      try { await this.$store.dispatch("resetState"); } catch (e) { }
    } catch (e) { }

    try {
      // 5) Make sure login page is shown
      // replace prevents back button returning to protected screen
      await this.$router.replace("/login");
    } catch (e) {
      // last resort
      if (process.client) window.location.href = "/login";
    } finally {
      this.loading = false;
    }
















    try {
      this.$store.dispatch("dashboard/resetState");
      this.$store.dispatch("resetState");
      // Clear the token and user data
      await this.$auth.logout();

      // // Manually clear any other user-related data
      await this.$auth.setUser(false); // Clear user data
      // this.$auth.setToken("local", null); // Clear the token
      //console.log("logout page", this.$auth.user);
      // Redirect to login or another route
      //this.$router.push("/login", true);
      this.$router.push("/login");
    } catch (error) {
      //console.error("Error during logout:", error);
    }
    // this.$axios.get(`/logout`).then(({ res }) => {
    //   this.$auth.logout();
    //   this.$router.push(`/login`);
    // });
    // setTimeout(() => {
    //   try {
    //     if (localStorage) {
    //       localStorage.setItem("auth._token.local", false);
    //       localStorage.setItem("auth._token_expiration.local", false);
    //     }
    //   } catch (e) {}
    //   this.$router.push(`/login`);
    //   try {
    //     windows.location.href = "/login";
    //   } catch (e) {}
    // }, 1000);
  },
};
</script>
