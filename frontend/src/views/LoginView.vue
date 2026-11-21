<script setup lang="ts">
import { reactive, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import type { SubmitEventPromise } from 'vuetify'
import { ApiError } from '@/api/http'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()
const route = useRoute()
const router = useRouter()

const form = reactive({ email: '', password: '' })
const showPassword = ref(false)
const loading = ref(false)
const error = ref('')
const fieldErrors = ref<Record<string, string[]>>({})

const required = (v: string) => !!v || 'Este campo es obligatorio.'
const emailFormat = (v: string) => /.+@.+\..+/.test(v) || 'Escribe un correo válido.'

async function submit(event: SubmitEventPromise) {
  const { valid } = await event
  if (!valid) return

  loading.value = true
  error.value = ''
  fieldErrors.value = {}

  try {
    await auth.login(form.email, form.password)
    const redirect = route.query.redirect
    // solo rutas internas, para que nadie arme un enlace que nos saque del sitio
    router.push(typeof redirect === 'string' && redirect.startsWith('/') ? redirect : { name: 'home' })
  } catch (e) {
    if (e instanceof ApiError) {
      error.value = e.message
      fieldErrors.value = e.errors
    } else {
      error.value = 'Ocurrió un error inesperado.'
    }
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <v-main class="d-flex align-center justify-center">
    <v-card width="100%" max-width="420" class="pa-4 ma-4" elevation="2">
      <v-card-title class="text-h5 d-flex align-center ga-2">
        <v-icon icon="mdi-soccer" color="primary" /> Football Data
      </v-card-title>
      <v-card-subtitle>Inicia sesión para continuar</v-card-subtitle>

      <v-card-text>
        <v-alert v-if="error" type="error" variant="tonal" class="mb-4" density="compact">
          {{ error }}
        </v-alert>

        <v-form validate-on="submit lazy" @submit.prevent="submit">
          <v-text-field
            v-model="form.email"
            label="Correo"
            type="email"
            autocomplete="username"
            prepend-inner-icon="mdi-email-outline"
            :rules="[required, emailFormat]"
            :error-messages="fieldErrors.email"
          />
          <v-text-field
            v-model="form.password"
            label="Contraseña"
            autocomplete="current-password"
            prepend-inner-icon="mdi-lock-outline"
            :type="showPassword ? 'text' : 'password'"
            :append-inner-icon="showPassword ? 'mdi-eye-off' : 'mdi-eye'"
            :rules="[required]"
            :error-messages="fieldErrors.password"
            @click:append-inner="showPassword = !showPassword"
          />
          <v-btn type="submit" color="primary" block size="large" :loading="loading">
            Entrar
          </v-btn>
        </v-form>
      </v-card-text>
    </v-card>
  </v-main>
</template>
