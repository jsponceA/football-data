<script setup lang="ts">
import { reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import type { SubmitEventPromise } from 'vuetify'
import AuthShell from '@/components/AuthShell.vue'
import TurnstileWidget from '@/components/TurnstileWidget.vue'
import { useCaptcha } from '@/composables/useCaptcha'
import { useFormSubmit } from '@/composables/useFormSubmit'
import { useNotify } from '@/composables/useNotify'
import { useAuthStore } from '@/stores/auth'
import { emailFormat, minLength, required } from '@/utils/rules'

const auth = useAuthStore()
const router = useRouter()
const { notify } = useNotify()

const form = reactive({ name: '', email: '', password: '', confirm: '' })
const showPassword = ref(false)
const { token: captcha, widget, require: requireCaptcha, renew: renewCaptcha } = useCaptcha()
const { loading, error, fieldErrors, handleSubmit } = useFormSubmit()

const minPassword = minLength(8)
const sameAsPassword = (value: string) => value === form.password || 'Las contraseñas no coinciden.'

async function submit(event: SubmitEventPromise) {
  let sent = false
  const ok = await handleSubmit(event, async () => {
    requireCaptcha()
    sent = true
    await auth.register(form.name, form.email, form.password, form.confirm, captcha.value)
    notify('Cuenta creada. ¡Bienvenido!')
    router.push({ name: 'home' })
  })
  // el token ya se gastó en la petición, haya salido bien o no; si no llegó a salir, sigue valiendo
  if (!ok && sent) renewCaptcha()
}
</script>

<template>
  <AuthShell title="Crea tu cuenta" subtitle="Regístrate para consultar ligas, equipos y jugadores." compact>
    <v-alert v-if="error" type="error" variant="tonal" class="mb-6" density="comfortable">
      {{ error }}
    </v-alert>

    <v-form validate-on="submit lazy" @submit.prevent="submit">
      <v-text-field
        v-model="form.name"
        label="Nombre"
        variant="outlined"
        rounded="lg"
        autocomplete="name"
        prepend-inner-icon="mdi-account-outline"
        :rules="[required]"
        :error-messages="fieldErrors.name"
      />
      <v-text-field
        v-model="form.email"
        label="Correo"
        type="email"
        variant="outlined"
        rounded="lg"
        autocomplete="email"
        prepend-inner-icon="mdi-email-outline"
        :rules="[required, emailFormat]"
        :error-messages="fieldErrors.email"
      />
      <v-text-field
        v-model="form.password"
        label="Contraseña"
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

      <TurnstileWidget ref="widget" v-model="captcha" />

      <v-btn
        type="submit"
        color="primary"
        block
        size="x-large"
        rounded="lg"
        class="text-none font-weight-bold mt-2"
        :loading="loading"
      >
        Crear cuenta
      </v-btn>
    </v-form>

    <p class="text-body-2 text-center text-medium-emphasis mt-6">
      ¿Ya tienes cuenta?
      <router-link :to="{ name: 'login' }" class="text-primary font-weight-medium">Inicia sesión</router-link>
    </p>
  </AuthShell>
</template>
