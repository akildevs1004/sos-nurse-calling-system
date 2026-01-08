<template>
  <v-app>
    <v-main class="tv-main">
      <nuxt />
    </v-main>
  </v-app>
</template>

<script>
export default {
  name: "TvLayout",
  auth: false,
  middleware: [],// hard bypass,

  created() {
    const hasToken =
      !!this.$auth?.strategy?.token?.get() ||
      !!this.$auth?.$storage?.getUniversal?.("auth._token.local");

    if (this.$auth?.loggedIn || hasToken) {
      // this.autoRedirecting = true;
      // this.$router.replace("/alarm/tvmonitor1");
      // return;
    }
    else {
      alert("Unauthorized Access. Redirecting to Login.");
      this.logout();
      return "";
    }

    // if (!this.$auth.user) {
    //   {
    //     this.logout();
    //     return "";
    //   }
    // }
  }
};


</script>

<style scoped>
html,
body,
#__nuxt,
#__layout {
  height: 100%;
}

.tv-main {
  min-height: 100vh;
  padding: 0;
}

.theme--dark.v-application {
  background: #1a2632 !important;
  color: #FFFFFF;
}
</style>
