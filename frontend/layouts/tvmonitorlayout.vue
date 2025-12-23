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
  middleware: [] // hard bypass
};
</script>

<style>
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
</style>
