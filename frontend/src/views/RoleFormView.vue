<script setup lang="ts">
import { computed, reactive, ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import type { SubmitEventPromise } from 'vuetify'
import { createRole, getPermissions, getRole, updateRole } from '@/api/admin'
import DataState from '@/components/DataState.vue'
import EmptyState from '@/components/EmptyState.vue'
import FormBlock from '@/components/FormBlock.vue'
import FormField from '@/components/FormField.vue'
import PageHeader from '@/components/PageHeader.vue'
import { useFormSubmit } from '@/composables/useFormSubmit'
import { useNotify } from '@/composables/useNotify'
import { usePageTitle } from '@/composables/usePageTitle'
import { useRequest } from '@/composables/useRequest'
import type { Permission } from '@/types/api'
import { describePermission, permissionActions } from '@/utils/permissions'
import { required } from '@/utils/rules'

// sin id es un rol nuevo; con id se edita el existente
const props = defineProps<{ id?: number }>()
const isEdit = props.id !== undefined

const router = useRouter()
const { notify } = useNotify()
const { loading: saving, error, fieldErrors, handleSubmit } = useFormSubmit()

const permissions = ref<Permission[]>([])
const formRef = ref<{ resetValidation: () => void } | null>(null)

const { data: role, loading, error: loadError, load } = useRequest(async () => {
  const [existing, all] = await Promise.all([isEdit ? getRole(props.id!) : null, getPermissions()])
  permissions.value = all
  return existing
})

const form = reactive({ name: '', permissions: [] as string[] })

function fill() {
  form.name = role.value?.name ?? ''
  form.permissions = [...(role.value?.permissions ?? [])]
}

watch(role, fill)

function reset() {
  fill()
  formRef.value?.resetValidation()
}

usePageTitle(() => (isEdit ? 'Editar rol' : 'Nuevo rol'))

// una fila por sección y una columna por acción; si la sección no tiene esa acción la celda queda vacía
const rows = computed(() => {
  const map = new Map<string, { key: string; label: string; icon: string; names: Record<string, string> }>()
  for (const permission of permissions.value) {
    const { key, verb, subject, icon } = describePermission(permission.name)
    if (!map.has(key)) map.set(key, { key, label: subject, icon, names: {} })
    map.get(key)!.names[verb] = permission.name
  }
  return [...map.values()]
})

const allSelected = computed(() => permissions.value.length > 0 && form.permissions.length === permissions.value.length)

function toggleAll() {
  form.permissions = allSelected.value ? [] : permissions.value.map((p) => p.name)
}

async function submit(event: SubmitEventPromise) {
  const payload = { name: form.name.trim(), permissions: form.permissions }

  const saved = await handleSubmit(event, async () => {
    if (isEdit) {
      await updateRole(props.id!, payload)
    } else {
      await createRole(payload)
    }
  })

  if (saved) {
    notify(isEdit ? 'Rol actualizado.' : 'Rol creado.')
    router.push({ name: 'roles' })
  }
}
</script>

<template>
  <PageHeader
    :crumbs="[{ title: 'Roles y permisos', to: { name: 'roles' } }, { title: isEdit ? 'Editar rol' : 'Nuevo rol' }]"
  >
    <template v-if="!loading && !loadError && !role?.protected">
      <v-btn variant="outlined" prepend-icon="mdi-eraser" :disabled="saving" @click="reset">Limpiar</v-btn>
      <v-btn
        variant="outlined"
        color="error"
        prepend-icon="mdi-close"
        :disabled="saving"
        :to="{ name: 'roles' }"
      >
        Cancelar
      </v-btn>
      <v-btn
        type="submit"
        form="role-form"
        color="primary"
        variant="flat"
        prepend-icon="mdi-content-save-outline"
        :loading="saving"
      >
        {{ isEdit ? 'Guardar cambios' : 'Guardar rol' }}
      </v-btn>
    </template>
  </PageHeader>

  <DataState :loading="loading" :error="loadError" :empty="loading" skeleton="form" @retry="load">
    <!-- el rol admin no se puede tocar: el backend lo rechaza, así que ni se ofrece el formulario -->
    <EmptyState
      v-if="role?.protected"
      icon="mdi-lock-outline"
      title="Rol protegido"
      text="Este rol es parte del sistema y no se puede modificar."
    >
      <v-btn :to="{ name: 'roles' }" color="primary" variant="tonal">Volver a roles</v-btn>
    </EmptyState>

    <v-card v-else border flat class="pa-5">
      <v-form id="role-form" ref="formRef" validate-on="submit lazy" @submit.prevent="submit">
        <v-alert v-if="error" type="error" variant="tonal" density="compact" class="mb-4">{{ error }}</v-alert>

        <FormBlock title="Información del rol" icon="mdi-shield-account-outline">
          <v-row>
            <v-col cols="12" md="4">
              <FormField label="Nombre del rol" required>
                <v-text-field
                  v-model="form.name"
                  placeholder="Por ejemplo: analista"
                  variant="outlined"
                  density="compact"
                  prepend-inner-icon="mdi-tag-outline"
                  :rules="[required]"
                  :error-messages="fieldErrors.name"
                  hide-details="auto"
                />
              </FormField>
            </v-col>
          </v-row>
        </FormBlock>

        <FormBlock title="Permisos" icon="mdi-key-outline">
          <template #append>
            <span class="text-caption text-medium-emphasis">
              {{ form.permissions.length }} de {{ permissions.length }} marcados
            </span>
            <v-btn variant="text" size="small" @click="toggleAll">
              {{ allSelected ? 'Quitar todos' : 'Marcar todos' }}
            </v-btn>
          </template>

          <v-table density="compact" class="perm-table border rounded">
            <thead>
              <tr>
                <th>Sección</th>
                <th v-for="action in permissionActions" :key="action.verb" class="perm-cell">
                  {{ action.label }}
                  <div class="text-caption font-weight-regular text-medium-emphasis">{{ action.hint }}</div>
                </th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="row in rows" :key="row.key">
                <td>
                  <v-icon :icon="row.icon" size="18" color="primary" class="mr-2" />
                  {{ row.label }}
                </td>
                <td v-for="action in permissionActions" :key="action.verb" class="perm-cell">
                  <v-checkbox-btn
                    v-if="row.names[action.verb]"
                    v-model="form.permissions"
                    :value="row.names[action.verb]"
                    :aria-label="`${row.label}: ${action.label}`"
                    color="primary"
                    density="comfortable"
                  />
                  <span v-else class="text-disabled">—</span>
                </td>
              </tr>
            </tbody>
          </v-table>
          <div v-if="fieldErrors.permissions" class="text-error text-caption mt-2">
            {{ fieldErrors.permissions[0] }}
          </div>
        </FormBlock>
      </v-form>
    </v-card>
  </DataState>
</template>
