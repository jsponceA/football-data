<script setup lang="ts">
import { computed, reactive, ref } from 'vue'
import type { SubmitEventPromise } from 'vuetify'
import { deleteMyPhoto, updateProfile, uploadMyPhoto } from '@/api/auth'
import FormBlock from '@/components/FormBlock.vue'
import FormField from '@/components/FormField.vue'
import PageHeader from '@/components/PageHeader.vue'
import PhotoUpload from '@/components/PhotoUpload.vue'
import { useFormSubmit } from '@/composables/useFormSubmit'
import { useNotify } from '@/composables/useNotify'
import { usePageTitle } from '@/composables/usePageTitle'
import { useAuthStore } from '@/stores/auth'
import { errorMessage } from '@/utils/format'
import { emailFormat, minLength, required } from '@/utils/rules'

const auth = useAuthStore()
const { notify } = useNotify()
const { loading: saving, error, fieldErrors, handleSubmit } = useFormSubmit()

usePageTitle(() => 'Mi perfil')

const MIN_PASSWORD = 8
const form = reactive({ name: '', email: '', current: '', password: '', confirm: '' })
const photo = ref<File | null>(null)
const photoRemoved = ref(false)
const showPasswords = ref(false)

const minPassword = minLength(MIN_PASSWORD)
const passwordLongEnough = computed(() => form.password.length >= MIN_PASSWORD)
const sameAsPassword = (value: string) => value === form.password || 'Las contraseñas no coinciden.'
// la actual solo se pide cuando se quiere poner una contraseña nueva
const currentWhenChanging = (value: string) => !form.password || !!value || 'Escribe tu contraseña actual.'

function fill() {
  form.name = auth.user?.name ?? ''
  form.email = auth.user?.email ?? ''
  form.current = ''
  form.password = ''
  form.confirm = ''
  photo.value = null
  photoRemoved.value = false
}

fill()

async function submit(event: SubmitEventPromise) {
  const saved = await handleSubmit(event, async () => {
    const user = await updateProfile({
      name: form.name,
      email: form.email,
      password: form.password || null,
      current_password: form.password ? form.current : null,
    })
    auth.setUser(user)
  })

  if (!saved) return

  // los datos ya están guardados: si la foto falla solo se avisa y se puede reintentar
  const photoError = await syncPhoto()
  fill()

  if (photoError) {
    notify(`Datos guardados, pero la foto no se pudo actualizar: ${photoError}`, 'error')
  } else {
    notify('Perfil actualizado.')
  }
}

async function syncPhoto() {
  try {
    if (photo.value) {
      auth.setUser(await uploadMyPhoto(photo.value))
    } else if (photoRemoved.value) {
      auth.setUser(await deleteMyPhoto())
    }
    return ''
  } catch (e) {
    return errorMessage(e)
  }
}
</script>

<template>
  <PageHeader :crumbs="[{ title: 'Mi perfil' }]">
    <v-btn
      type="submit"
      form="profile-form"
      color="primary"
      variant="flat"
      prepend-icon="mdi-content-save-outline"
      :loading="saving"
    >
      Guardar cambios
    </v-btn>
  </PageHeader>

  <v-card border flat class="pa-5">
    <v-form id="profile-form" validate-on="submit lazy" @submit.prevent="submit">
      <v-alert v-if="error" type="error" variant="tonal" density="compact" class="mb-4">{{ error }}</v-alert>

      <FormBlock title="Información personal" icon="mdi-card-account-details-outline">
        <v-row>
          <v-col cols="12" md="auto">
            <FormField label="Foto de perfil">
              <PhotoUpload v-model:file="photo" v-model:removed="photoRemoved" :url="auth.user?.photo_url" />
            </FormField>
          </v-col>
          <v-col cols="12" md>
            <v-row>
              <v-col cols="12" md="6">
                <FormField label="Nombre" required>
                  <v-text-field
                    v-model="form.name"
                    placeholder="Nombre completo"
                    variant="outlined"
                    density="compact"
                    prepend-inner-icon="mdi-account-outline"
                    :rules="[required]"
                    :error-messages="fieldErrors.name"
                    hide-details="auto"
                  />
                </FormField>
              </v-col>
              <v-col cols="12" md="6">
                <FormField label="Correo electrónico" required>
                  <v-text-field
                    v-model="form.email"
                    type="email"
                    placeholder="correo@ejemplo.com"
                    variant="outlined"
                    density="compact"
                    prepend-inner-icon="mdi-email-outline"
                    :rules="[required, emailFormat]"
                    :error-messages="fieldErrors.email"
                    hide-details="auto"
                  />
                </FormField>
              </v-col>
            </v-row>
          </v-col>
        </v-row>
      </FormBlock>

      <FormBlock title="Cambiar contraseña" icon="mdi-lock-outline">
        <div class="text-body-2 text-medium-emphasis mb-3">Déjalo vacío si no quieres cambiarla.</div>
        <v-row>
          <v-col cols="12" md="4">
            <FormField label="Contraseña actual">
              <v-text-field
                v-model="form.current"
                autocomplete="current-password"
                placeholder="Tu contraseña actual"
                variant="outlined"
                density="compact"
                prepend-inner-icon="mdi-lock-outline"
                :type="showPasswords ? 'text' : 'password'"
                :append-inner-icon="showPasswords ? 'mdi-eye-off' : 'mdi-eye'"
                :rules="[currentWhenChanging]"
                :error-messages="fieldErrors.current_password"
                hide-details="auto"
                @click:append-inner="showPasswords = !showPasswords"
              />
            </FormField>
          </v-col>
          <v-col cols="12" md="4">
            <FormField label="Contraseña nueva">
              <v-text-field
                v-model="form.password"
                autocomplete="new-password"
                placeholder="Mínimo 8 caracteres"
                variant="outlined"
                density="compact"
                prepend-inner-icon="mdi-lock-plus-outline"
                :type="showPasswords ? 'text' : 'password'"
                :rules="[minPassword]"
                :error-messages="fieldErrors.password"
                hide-details="auto"
              />
            </FormField>
          </v-col>
          <v-col cols="12" md="4">
            <FormField label="Confirmar contraseña nueva">
              <v-text-field
                v-model="form.confirm"
                autocomplete="new-password"
                placeholder="Repite la contraseña nueva"
                variant="outlined"
                density="compact"
                prepend-inner-icon="mdi-lock-check-outline"
                :type="showPasswords ? 'text' : 'password'"
                :rules="[sameAsPassword]"
                hide-details="auto"
              />
            </FormField>
          </v-col>
        </v-row>
        <div class="pw-rules pw-rules--inline mt-2">
          <div class="pw-rules__item" :class="{ 'pw-rules__item--ok': passwordLongEnough }">
            <v-icon :icon="passwordLongEnough ? 'mdi-check-circle' : 'mdi-circle-outline'" size="16" />
            Al menos {{ MIN_PASSWORD }} caracteres
          </div>
        </div>
      </FormBlock>

      <FormBlock title="Mi acceso" icon="mdi-shield-key-outline">
        <div class="text-body-2 text-medium-emphasis mb-2">
          Los roles los asigna un administrador; no se pueden cambiar desde aquí.
        </div>
        <v-chip v-for="role in auth.user?.roles" :key="role" size="small" class="mr-1">{{ role }}</v-chip>
      </FormBlock>
    </v-form>
  </v-card>
</template>
