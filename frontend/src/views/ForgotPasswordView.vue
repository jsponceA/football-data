<script setup lang="ts">
import { ref } from 'vue'
import type { SubmitEventPromise } from 'vuetify'
import { forgotPassword } from '@/api/auth'
import AuthShell from '@/components/AuthShell.vue'
import { useFormSubmit } from '@/composables/useFormSubmit'
import { emailFormat, required } from '@/utils/rules'

const email = ref('')
// el backend responde lo mismo exista o no el correo; ese texto es el que se muestra
const sent = ref('')
const { loading, error, fieldErrors, handleSubmit } = useFormSubmit()

function submit(event: SubmitEventPromise) {
  return handleSubmit(event, async () => {
    sent.value = await forgotPassword(email.value)
  })
}
</script>

<template>
  <AuthShell
    title="Recupera tu contraseña"
    :subtitle="sent ? '' : 'Escribe tu correo y te enviaremos un enlace para crear una nueva.'"
    compact
  >
    <template v-if="sent">
      <v-alert type="success" variant="tonal" icon="mdi-email-check-outline" class="mb-6">
        {{ sent }}
      </v-alert>
      <p class="text-body-2 text-medium-emphasis mb-6">
        Revisa también la carpeta de spam. El enlace vence en 60 minutos.
      </p>
      <v-btn :to="{ name: 'login' }" color="primary" block size="large" rounded="lg" class="text-none">
        Volver a iniciar sesión
      </v-btn>
    </template>

    <template v-else>
      <v-alert v-if="error" type="error" variant="tonal" class="mb-6" density="comfortable">
        {{ error }}
      </v-alert>

      <v-form validate-on="submit lazy" @submit.prevent="submit">
        <v-text-field
          v-model="email"
          label="Correo"
          type="email"
          variant="outlined"
          rounded="lg"
          autocomplete="email"
          prepend-inner-icon="mdi-email-outline"
          :rules="[required, emailFormat]"
          :error-messages="fieldErrors.email"
        />
        <v-btn
          type="submit"
          color="primary"
          block
          size="x-large"
          rounded="lg"
          class="text-none font-weight-bold"
          :loading="loading"
        >
          Enviar enlace
        </v-btn>
      </v-form>

      <p class="text-body-2 text-center mt-6">
        <router-link :to="{ name: 'login' }" class="text-primary font-weight-medium">Volver a iniciar sesión</router-link>
      </p>
    </template>
  </AuthShell>
</template>
