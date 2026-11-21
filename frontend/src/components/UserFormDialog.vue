<script setup lang="ts">
import { reactive, ref, watch } from 'vue'
import type { SubmitEventPromise } from 'vuetify'
import { createUser, updateUser, type UserPayload } from '@/api/admin'
import { ApiError } from '@/api/http'
import { useNotify } from '@/composables/useNotify'
import type { ManagedUser } from '@/types/api'

const props = defineProps<{ user: ManagedUser | null; roles: string[] }>()
const open = defineModel<boolean>({ required: true })
const emit = defineEmits<{ saved: [] }>()

const { notify } = useNotify()

const form = reactive({ name: '', email: '', password: '', is_active: true, roles: [] as string[] })
const saving = ref(false)
const error = ref('')
const fieldErrors = ref<Record<string, string[]>>({})
const showPassword = ref(false)

// cada vez que se abre se parte de cero o de los datos del usuario a editar
watch(open, (value) => {
  if (!value) return
  form.name = props.user?.name ?? ''
  form.email = props.user?.email ?? ''
  form.password = ''
  form.is_active = props.user?.is_active ?? true
  form.roles = [...(props.user?.roles ?? [])]
  error.value = ''
  fieldErrors.value = {}
})

const required = (v: unknown) => (Array.isArray(v) ? v.length > 0 : !!v) || 'Este campo es obligatorio.'
const emailFormat = (v: string) => /.+@.+\..+/.test(v) || 'Escribe un correo válido.'
const minLength = (v: string) => !v || v.length >= 8 || 'Mínimo 8 caracteres.'

async function submit(event: SubmitEventPromise) {
  const { valid } = await event
  if (!valid) return

  saving.value = true
  error.value = ''
  fieldErrors.value = {}

  const payload: UserPayload = {
    name: form.name,
    email: form.email,
    password: form.password || null,
    is_active: form.is_active,
    roles: form.roles,
  }

  try {
    if (props.user) {
      await updateUser(props.user.id, payload)
    } else {
      await createUser(payload)
    }
    notify(props.user ? 'Usuario actualizado.' : 'Usuario creado.')
    open.value = false
    emit('saved')
  } catch (e) {
    if (e instanceof ApiError) {
      error.value = e.message
      fieldErrors.value = e.errors
    } else {
      error.value = 'Ocurrió un error inesperado.'
    }
  } finally {
    saving.value = false
  }
}
</script>

<template>
  <v-dialog v-model="open" max-width="520" :persistent="saving">
    <v-card :title="user ? 'Editar usuario' : 'Nuevo usuario'">
      <v-form validate-on="submit lazy" @submit.prevent="submit">
        <v-card-text>
          <v-alert v-if="error" type="error" variant="tonal" density="compact" class="mb-4">
            {{ error }}
          </v-alert>

          <v-text-field v-model="form.name" label="Nombre" :rules="[required]" :error-messages="fieldErrors.name" />
          <v-text-field
            v-model="form.email"
            label="Correo"
            type="email"
            :rules="[required, emailFormat]"
            :error-messages="fieldErrors.email"
          />
          <v-text-field
            v-model="form.password"
            label="Contraseña"
            autocomplete="new-password"
            :type="showPassword ? 'text' : 'password'"
            :append-inner-icon="showPassword ? 'mdi-eye-off' : 'mdi-eye'"
            :hint="user ? 'Déjala vacía para no cambiarla.' : 'Mínimo 8 caracteres.'"
            persistent-hint
            :rules="user ? [minLength] : [required, minLength]"
            :error-messages="fieldErrors.password"
            class="mb-2"
            @click:append-inner="showPassword = !showPassword"
          />
          <v-select
            v-model="form.roles"
            :items="roles"
            label="Roles"
            multiple
            chips
            closable-chips
            :rules="[required]"
            :error-messages="fieldErrors.roles"
          />
          <v-switch v-model="form.is_active" label="Usuario activo" color="primary" hide-details />
        </v-card-text>
        <v-card-actions>
          <v-spacer />
          <v-btn :disabled="saving" @click="open = false">Cancelar</v-btn>
          <v-btn type="submit" color="primary" variant="flat" :loading="saving">Guardar</v-btn>
        </v-card-actions>
      </v-form>
    </v-card>
  </v-dialog>
</template>
