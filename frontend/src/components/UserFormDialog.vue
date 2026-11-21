<script setup lang="ts">
import { reactive, ref, watch } from 'vue'
import type { SubmitEventPromise } from 'vuetify'
import { createUser, updateUser, type UserPayload } from '@/api/admin'
import { useFormSubmit } from '@/composables/useFormSubmit'
import { useNotify } from '@/composables/useNotify'
import type { ManagedUser } from '@/types/api'
import { emailFormat, minLength, required } from '@/utils/rules'

const props = defineProps<{ user: ManagedUser | null; roles: string[] }>()
const open = defineModel<boolean>({ required: true })
const emit = defineEmits<{ saved: [] }>()

const { notify } = useNotify()
const { loading: saving, error, fieldErrors, handleSubmit, reset } = useFormSubmit()

const form = reactive({ name: '', email: '', password: '', is_active: true, roles: [] as string[] })
const showPassword = ref(false)
const minPassword = minLength(8)

// cada vez que se abre se parte de cero o de los datos del usuario a editar
watch(open, (value) => {
  if (!value) return
  form.name = props.user?.name ?? ''
  form.email = props.user?.email ?? ''
  form.password = ''
  form.is_active = props.user?.is_active ?? true
  form.roles = [...(props.user?.roles ?? [])]
  reset()
})

async function submit(event: SubmitEventPromise) {
  const payload: UserPayload = {
    name: form.name,
    email: form.email,
    password: form.password || null,
    is_active: form.is_active,
    roles: form.roles,
  }

  const saved = await handleSubmit(event, async () => {
    if (props.user) {
      await updateUser(props.user.id, payload)
    } else {
      await createUser(payload)
    }
  })

  if (saved) {
    notify(props.user ? 'Usuario actualizado.' : 'Usuario creado.')
    open.value = false
    emit('saved')
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
            :rules="user ? [minPassword] : [required, minPassword]"
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
