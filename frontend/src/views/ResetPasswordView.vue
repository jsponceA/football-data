<script setup lang="ts">
import { computed, reactive, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import type { SubmitEventPromise } from 'vuetify'
import { resetPassword } from '@/api/auth'
import AuthShell from '@/components/AuthShell.vue'
import { useFormSubmit } from '@/composables/useFormSubmit'
import { useNotify } from '@/composables/useNotify'
import { minLength, required } from '@/utils/rules'

const route = useRoute()
const router = useRouter()
const { notify } = useNotify()

// el correo trae el token y el correo en el enlace; sin ellos no hay nada que restablecer
const token = computed(() => (typeof route.query.token === 'string' ? route.query.token : ''))
const email = computed(() => (typeof route.query.email === 'string' ? route.query.email : ''))
const validLink = computed(() => token.value !== '' && email.value !== '')

const form = reactive({ password: '', confirm: '' })
const showPassword = ref(false)
const { loading, error, fieldErrors, handleSubmit } = useFormSubmit()

const minPassword = minLength(8)
const sameAsPassword = (value: string) => value === form.password || 'Las contraseñas no coinciden.'

function submit(event: SubmitEventPromise) {
  return handleSubmit(event, async () => {
    await resetPassword({
      token: token.value,
      email: email.value,
      password: form.password,
      password_confirmation: form.confirm,
    })
    notify('Tu contraseña se actualizó. Ya puedes iniciar sesión.')
    router.push({ name: 'login' })
  })
}
</script>

<template>
  <AuthShell
    title="Nueva contraseña"
    :subtitle="validLink ? `Elige una contraseña nueva para ${email}.` : ''"
    compact
  >
    <template v-if="!validLink">
      <v-alert type="warning" variant="tonal" class="mb-6">
        Este enlace no es válido. Pide uno nuevo para restablecer tu contraseña.
      </v-alert>
      <v-btn :to="{ name: 'forgot-password' }" color="primary" block size="large" rounded="lg" class="text-none">
        Pedir un enlace nuevo
      </v-btn>
    </template>

    <template v-else>
      <v-alert v-if="error" type="error" variant="tonal" class="mb-6" density="comfortable">
        {{ error }}
        <template v-if="fieldErrors.email">
          <router-link :to="{ name: 'forgot-password' }" class="d-block mt-1 text-body-2">
            Pedir un enlace nuevo
          </router-link>
        </template>
      </v-alert>

      <v-form validate-on="submit lazy" @submit.prevent="submit">
        <v-text-field
          v-model="form.password"
          label="Contraseña nueva"
          variant="outlined"
          rounded="lg"
          autocomplete="new-password"
          prepend-inner-icon="mdi-lock-outline"
          hint="Mínimo 8 caracteres."
          :type="showPassword ? 'text' : 'password'"
          :append-inner-icon="showPassword ? 'mdi-eye-off' : 'mdi-eye'"
          :rules="[required, minPassword]"
          :error-messages="fieldErrors.password"
          @click:append-inner="showPassword = !showPassword"
        />
        <v-text-field
          v-model="form.confirm"
          label="Confirmar contraseña"
          variant="outlined"
          rounded="lg"
          autocomplete="new-password"
          prepend-inner-icon="mdi-lock-check-outline"
          :type="showPassword ? 'text' : 'password'"
          :rules="[required, sameAsPassword]"
        />
        <v-btn
          type="submit"
          color="primary"
          block
          size="x-large"
          rounded="lg"
          class="text-none font-weight-bold mt-2"
          :loading="loading"
        >
          Cambiar contraseña
        </v-btn>
      </v-form>
    </template>
  </AuthShell>
</template>
