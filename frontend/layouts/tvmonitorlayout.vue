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
  auth: true,
  middleware: [],// hard bypass,

  created() {


    if (!this.$auth.user) {
      {
        this.logout();
        return "";
      }
    }
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
