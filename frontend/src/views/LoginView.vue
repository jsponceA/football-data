<script setup lang="ts">
import { reactive, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import type { SubmitEventPromise } from 'vuetify'
import ThemeToggle from '@/components/ThemeToggle.vue'
import { useFormSubmit } from '@/composables/useFormSubmit'
import { useAuthStore } from '@/stores/auth'
import { emailFormat, required } from '@/utils/rules'
import logo from '@/assets/img/logo.webp'

const auth = useAuthStore()
const route = useRoute()
const router = useRouter()

const form = reactive({ email: '', password: '' })
const showPassword = ref(false)
const { loading, error, fieldErrors, handleSubmit } = useFormSubmit()

function submit(event: SubmitEventPromise) {
  return handleSubmit(event, async () => {
    await auth.login(form.email, form.password)
    const redirect = route.query.redirect
    // solo rutas internas, para que nadie arme un enlace que nos saque del sitio
    router.push(typeof redirect === 'string' && redirect.startsWith('/') ? redirect : { name: 'home' })
  })
}
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
            <img :src="logo" alt="Football Data" width="104" height="104" />
          </div>
          <h2 class="text-h4 font-weight-bold mb-1">Bienvenido</h2>
          <p class="text-body-1 text-medium-emphasis mb-8">Ingresa con tu cuenta para continuar.</p>

          <v-alert v-if="error" type="error" variant="tonal" class="mb-6" density="comfortable">
            {{ error }}
          </v-alert>

          <v-form validate-on="submit lazy" @submit.prevent="submit">
            <v-text-field
              v-model="form.email"
              label="Correo"
              type="email"
              variant="outlined"
              rounded="lg"
              autocomplete="username"
              prepend-inner-icon="mdi-email-outline"
              :rules="[required, emailFormat]"
              :error-messages="fieldErrors.email"
            />
            <v-text-field
              v-model="form.password"
              label="Contraseña"
              variant="outlined"
              rounded="lg"
              autocomplete="current-password"
              prepend-inner-icon="mdi-lock-outline"
              :type="showPassword ? 'text' : 'password'"
              :append-inner-icon="showPassword ? 'mdi-eye-off' : 'mdi-eye'"
              :rules="[required]"
              :error-messages="fieldErrors.password"
              @click:append-inner="showPassword = !showPassword"
            />
            <v-btn
              type="submit"
              color="primary"
              block
              size="x-large"
              rounded="lg"
              class="text-none font-weight-bold mt-2"
              append-icon="mdi-arrow-right"
              :loading="loading"
            >
              Iniciar sesión
            </v-btn>
          </v-form>
        </v-card>
      </div>
    </section>
  </v-main>
</template>
