<script setup lang="ts">
import { computed, reactive, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import type { SubmitEventPromise } from 'vuetify'
import AuthShell from '@/components/AuthShell.vue'
import { useFormSubmit } from '@/composables/useFormSubmit'
import { useAuthStore } from '@/stores/auth'
import { emailFormat, required } from '@/utils/rules'

const auth = useAuthStore()
const route = useRoute()
const router = useRouter()

// el router manda aquí con ?expired=1 cuando el servidor ya no acepta la sesión
const sessionExpired = computed(() => route.query.expired === '1')

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
  <AuthShell title="Bienvenido" subtitle="Ingresa con tu cuenta para continuar.">
    <v-alert v-if="error" type="error" variant="tonal" class="mb-6" density="comfortable">
      {{ error }}
    </v-alert>
    <v-alert
      v-else-if="sessionExpired"
      type="warning"
      variant="tonal"
      icon="mdi-clock-alert-outline"
      class="mb-6"
      density="comfortable"
    >
      Tu sesión venció. Inicia sesión de nuevo para continuar.
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

      <div class="text-right mb-4">
        <router-link :to="{ name: 'forgot-password' }" class="text-body-2 text-primary">
          ¿Olvidaste tu contraseña?
        </router-link>
      </div>

      <v-btn
        type="submit"
        color="primary"
        block
        size="x-large"
        rounded="lg"
        class="text-none font-weight-bold"
        append-icon="mdi-arrow-right"
        :loading="loading"
      >
        Iniciar sesión
      </v-btn>
    </v-form>

    <p class="text-body-2 text-center text-medium-emphasis mt-6">
      ¿No tienes cuenta?
      <router-link :to="{ name: 'register' }" class="text-primary font-weight-medium">Regístrate</router-link>
    </p>
  </AuthShell>
</template>
