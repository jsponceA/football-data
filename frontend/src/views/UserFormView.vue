<script setup lang="ts">
import { computed, reactive, ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import type { SubmitEventPromise } from 'vuetify'
import {
  createUser,
  deleteUserPhoto,
  getRoles,
  getUser,
  updateUser,
  uploadUserPhoto,
  type UserPayload,
} from '@/api/admin'
import DataState from '@/components/DataState.vue'
import FormBlock from '@/components/FormBlock.vue'
import FormField from '@/components/FormField.vue'
import PageHeader from '@/components/PageHeader.vue'
import PhotoUpload from '@/components/PhotoUpload.vue'
import { useFormSubmit } from '@/composables/useFormSubmit'
import { useNotify } from '@/composables/useNotify'
import { usePageTitle } from '@/composables/usePageTitle'
import { useRequest } from '@/composables/useRequest'
import { useAuthStore } from '@/stores/auth'
import type { Role } from '@/types/api'
import { errorMessage } from '@/utils/format'
import { emailFormat, minLength, required } from '@/utils/rules'

// sin id es un usuario nuevo; con id se edita el existente
const props = defineProps<{ id?: number }>()
const isEdit = props.id !== undefined

const router = useRouter()
const auth = useAuthStore()
const { notify } = useNotify()
const { loading: saving, error, fieldErrors, handleSubmit } = useFormSubmit()

const roles = ref<Role[]>([])
const formRef = ref<{ resetValidation: () => void } | null>(null)

// los roles disponibles se piden siempre; el usuario solo al editar
const { data: user, loading, error: loadError, load } = useRequest(async () => {
  const [existing, list] = await Promise.all([isEdit ? getUser(props.id!) : null, getRoles({ per_page: 100 })])
  roles.value = list.data
  return existing
})

const MIN_PASSWORD = 8
const form = reactive({ name: '', email: '', password: '', confirm: '', is_active: true, roles: [] as string[] })
// la foto se sube en una petición aparte, después de guardar los datos
const photo = ref<File | null>(null)
const photoRemoved = ref(false)
const showPassword = ref(false)
const minPassword = minLength(MIN_PASSWORD)
const passwordLongEnough = computed(() => form.password.length >= MIN_PASSWORD)
const sameAsPassword = (value: string) => value === form.password || 'Las contraseñas no coinciden.'

function fill() {
  form.name = user.value?.name ?? ''
  form.email = user.value?.email ?? ''
  form.is_active = user.value?.is_active ?? true
  form.roles = [...(user.value?.roles ?? [])]
  form.password = ''
  form.confirm = ''
  photo.value = null
  photoRemoved.value = false
}

watch(user, fill)

// "Limpiar" vuelve al estado inicial: vacío al crear, los datos guardados al editar
function reset() {
  fill()
  formRef.value?.resetValidation()
}

usePageTitle(() => (isEdit ? 'Editar usuario' : 'Nuevo usuario'))

async function submit(event: SubmitEventPromise) {
  const payload: UserPayload = {
    name: form.name,
    email: form.email,
    password: form.password || null,
    is_active: form.is_active,
    roles: form.roles,
  }

  let userId = props.id ?? 0

  const saved = await handleSubmit(event, async () => {
    const result = isEdit ? await updateUser(props.id!, payload) : await createUser(payload)
    userId = result.id
  })

  if (!saved) return

  // los datos ya quedaron guardados: si la foto falla no se repite el alta, solo se avisa
  const photoError = await syncPhoto(userId)

  // si se cambió la foto propia, el menú superior debe verla sin recargar
  if (auth.user?.id === userId) await auth.refresh()

  if (photoError) {
    notify(`Usuario guardado, pero la foto no se pudo actualizar: ${photoError}`, 'error')
  } else {
    notify(isEdit ? 'Usuario actualizado.' : 'Usuario creado.')
  }
  router.push({ name: 'users' })
}

async function syncPhoto(userId: number) {
  try {
    if (photo.value) {
      await uploadUserPhoto(userId, photo.value)
    } else if (photoRemoved.value) {
      await deleteUserPhoto(userId)
    }
    return ''
  } catch (e) {
    return errorMessage(e)
  }
}
</script>

<template>
  <PageHeader
    :crumbs="[{ title: 'Usuarios', to: { name: 'users' } }, { title: isEdit ? 'Editar usuario' : 'Nuevo usuario' }]"
  >
    <template v-if="!loading && !loadError">
      <v-btn variant="outlined" prepend-icon="mdi-eraser" :disabled="saving" @click="reset">Limpiar</v-btn>
      <v-btn
        variant="outlined"
        color="error"
        prepend-icon="mdi-close"
        :disabled="saving"
        :to="{ name: 'users' }"
      >
        Cancelar
      </v-btn>
      <v-btn
        type="submit"
        form="user-form"
        color="primary"
        variant="flat"
        prepend-icon="mdi-content-save-outline"
        :loading="saving"
      >
        {{ isEdit ? 'Guardar cambios' : 'Guardar usuario' }}
      </v-btn>
    </template>
  </PageHeader>

  <DataState :loading="loading" :error="loadError" :empty="loading" skeleton="form" @retry="load">
    <v-card border flat class="pa-5">
      <v-form id="user-form" ref="formRef" validate-on="submit lazy" @submit.prevent="submit">
        <v-alert v-if="error" type="error" variant="tonal" density="compact" class="mb-4">{{ error }}</v-alert>

        <FormBlock title="Información personal" icon="mdi-card-account-details-outline">
          <v-row>
            <v-col cols="12" md="auto">
              <FormField label="Foto de perfil">
                <PhotoUpload v-model:file="photo" v-model:removed="photoRemoved" :url="user?.photo_url" />
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

        <FormBlock title="Contraseña" icon="mdi-lock-outline">
          <v-row>
            <v-col cols="12" md="4">
              <FormField label="Contraseña" :required="!isEdit">
                <v-text-field
                  v-model="form.password"
                  autocomplete="new-password"
                  :placeholder="isEdit ? 'Déjala vacía para no cambiarla' : 'Mínimo 8 caracteres'"
                  variant="outlined"
                  density="compact"
                  prepend-inner-icon="mdi-lock-outline"
                  :type="showPassword ? 'text' : 'password'"
                  :append-inner-icon="showPassword ? 'mdi-eye-off' : 'mdi-eye'"
                  :rules="isEdit ? [minPassword] : [required, minPassword]"
                  :error-messages="fieldErrors.password"
                  hide-details="auto"
                  @click:append-inner="showPassword = !showPassword"
                />
              </FormField>
            </v-col>
            <v-col cols="12" md="4">
              <FormField label="Confirmar contraseña" :required="!isEdit">
                <v-text-field
                  v-model="form.confirm"
                  autocomplete="new-password"
                  placeholder="Repite la contraseña"
                  variant="outlined"
                  density="compact"
                  prepend-inner-icon="mdi-lock-check-outline"
                  :type="showPassword ? 'text' : 'password'"
                  :rules="[sameAsPassword]"
                  hide-details="auto"
                />
              </FormField>
            </v-col>
            <v-col cols="12" md="4">
              <div class="pw-rules">
                <div class="pw-rules__item" :class="{ 'pw-rules__item--ok': passwordLongEnough }">
                  <v-icon :icon="passwordLongEnough ? 'mdi-check-circle' : 'mdi-circle-outline'" size="16" />
                  Al menos {{ MIN_PASSWORD }} caracteres
                </div>
              </div>
            </v-col>
          </v-row>
        </FormBlock>

        <FormBlock title="Rol y acceso" icon="mdi-shield-key-outline">
          <v-row>
            <v-col cols="12" md="8">
              <FormField label="Roles" required>
                <v-select
                  v-model="form.roles"
                  :items="roles"
                  item-title="name"
                  item-value="name"
                  multiple
                  chips
                  closable-chips
                  placeholder="Seleccionar roles…"
                  variant="outlined"
                  density="compact"
                  prepend-inner-icon="mdi-shield-outline"
                  :rules="[required]"
                  :error-messages="fieldErrors.roles"
                  hide-details="auto"
                />
              </FormField>
            </v-col>
            <v-col cols="12" md="4">
              <FormField label="Estado">
                <v-switch
                  v-model="form.is_active"
                  color="primary"
                  density="compact"
                  hide-details
                  :label="form.is_active ? 'Activo: puede iniciar sesión' : 'Inactivo: no puede iniciar sesión'"
                />
              </FormField>
            </v-col>
          </v-row>
        </FormBlock>
      </v-form>
    </v-card>
  </DataState>
</template>
