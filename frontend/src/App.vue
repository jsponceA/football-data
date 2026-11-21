<script setup lang="ts">
import { notification } from '@/composables/useNotify'
</script>

<template>
  <v-app>
    <router-view />
    <v-snackbar v-model="notification.show" :color="notification.color" :timeout="4000">
      {{ notification.text }}
    </v-snackbar>
  </v-app>
</template>
