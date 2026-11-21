<script setup lang="ts">
import { computed, reactive, watch } from 'vue'
import type { SubmitEventPromise } from 'vuetify'
import { createRole, updateRole } from '@/api/admin'
import { useFormSubmit } from '@/composables/useFormSubmit'
import { useNotify } from '@/composables/useNotify'
import type { Permission, Role } from '@/types/api'
import { describePermission } from '@/utils/permissions'
import { required } from '@/utils/rules'

const props = defineProps<{ role: Role | null; permissions: Permission[] }>()
const open = defineModel<boolean>({ required: true })
const emit = defineEmits<{ saved: [] }>()

const { notify } = useNotify()
const { loading: saving, error, fieldErrors, handleSubmit, reset } = useFormSubmit()

const form = reactive({ name: '', permissions: [] as string[] })

watch(open, (value) => {
  if (!value) return
  form.name = props.role?.name ?? ''
  form.permissions = [...(props.role?.permissions ?? [])]
  reset()
})

// se agrupan por sección para que no sea una lista larga de checkboxes sueltos
const groups = computed(() => {
  const map = new Map<string, { label: string; items: { name: string; action: string }[] }>()
  for (const permission of props.permissions) {
    const { subject, action } = describePermission(permission.name)
    if (!map.has(subject)) map.set(subject, { label: subject, items: [] })
    map.get(subject)!.items.push({ name: permission.name, action })
  }
  return [...map.values()]
})

async function submit(event: SubmitEventPromise) {
  const payload = { name: form.name.trim(), permissions: form.permissions }

  const saved = await handleSubmit(event, async () => {
    if (props.role) {
      await updateRole(props.role.id, payload)
    } else {
      await createRole(payload)
    }
  })

  if (saved) {
    notify(props.role ? 'Rol actualizado.' : 'Rol creado.')
    open.value = false
    emit('saved')
  }
}
</script>

<template>
  <v-dialog v-model="open" max-width="520" :persistent="saving" scrollable>
    <v-card :title="role ? 'Editar rol' : 'Nuevo rol'">
      <v-form validate-on="submit lazy" class="d-flex flex-column" style="min-height: 0" @submit.prevent="submit">
        <v-card-text>
          <v-alert v-if="error" type="error" variant="tonal" density="compact" class="mb-4">
            {{ error }}
          </v-alert>

          <v-text-field
            v-model="form.name"
            label="Nombre del rol"
            :rules="[required]"
            :error-messages="fieldErrors.name"
            class="mb-2"
          />

          <div class="text-subtitle-2 mb-1">Permisos</div>
          <div v-for="group in groups" :key="group.label" class="mb-2">
            <div class="text-body-2 text-medium-emphasis">{{ group.label }}</div>
            <v-checkbox
              v-for="item in group.items"
              :key="item.name"
              v-model="form.permissions"
              :value="item.name"
              :label="item.action"
              density="compact"
              hide-details
            />
          </div>
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
