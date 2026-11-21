<script setup lang="ts">
import ThemeToggle from '@/components/ThemeToggle.vue'
import logo from '@/assets/img/logo.webp'

// marco común de las pantallas sin sesión (entrar, registrarse, recuperar contraseña): portada a la izquierda y tarjeta a la derecha
withDefaults(defineProps<{ title: string; subtitle?: string; compact?: boolean }>(), {
  subtitle: '',
  compact: false,
})
</script>

<template>
  <v-main class="login">
    <section class="login-hero">
      <div>
        <img :src="logo" alt="" class="login-hero__logo" />
        <h1 class="login-hero__title">Football Data</h1>
        <p class="login-hero__text">
          Consulta las ligas, los equipos y los jugadores de las principales competiciones.
        </p>
      </div>
    </section>

    <section class="login-panel">
      <div class="login-theme">
        <ThemeToggle />
      </div>

      <div class="login-form">
        <v-card class="pa-8" rounded="xl" border flat>
          <div class="login-form__logo">
            <img :src="logo" alt="Football Data" :width="compact ? 72 : 104" :height="compact ? 72 : 104" />
          </div>
          <h2 class="text-h4 font-weight-bold mb-1">{{ title }}</h2>
          <p v-if="subtitle" class="text-body-1 text-medium-emphasis mb-6">{{ subtitle }}</p>

          <slot />
        </v-card>
      </div>
    </section>
  </v-main>
</template>
