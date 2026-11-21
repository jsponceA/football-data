<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { deleteRole, getPermissions, getRoles } from '@/api/admin'
import DataState from '@/components/DataState.vue'
import PageHeader from '@/components/PageHeader.vue'
import PaginationBar from '@/components/PaginationBar.vue'
import PerPageSelect from '@/components/PerPageSelect.vue'
import RoleFormDialog from '@/components/RoleFormDialog.vue'
import { usePaginatedList } from '@/composables/usePaginatedList'
import { useNotify } from '@/composables/useNotify'
import type { Permission, Role } from '@/types/api'
import { errorMessage } from '@/utils/format'
import { describePermission } from '@/utils/permissions'

const { items, meta, page, perPage, loading, error, load } = usePaginatedList(getRoles)
const { notify } = useNotify()

const permissions = ref<Permission[]>([])
const dialog = ref(false)
const editing = ref<Role | null>(null)
const toDelete = ref<Role | null>(null)
const deleting = ref(false)

onMounted(async () => {
  try {
    permissions.value = await getPermissions()
  } catch (e) {
    notify(errorMessage(e), 'error')
  }
})

function openForm(role: Role | null) {
  editing.value = role
  dialog.value = true
}

async function confirmDelete() {
  if (!toDelete.value) return
  deleting.value = true
  try {
    await deleteRole(toDelete.value.id)
    notify('Rol eliminado.')
    toDelete.value = null
    // si era el último de la página hay que retroceder, si no queda una página vacía
    if (items.value.length === 1 && page.value > 1) {
      page.value--
    } else {
      load()
    }
  } catch (e) {
    notify(errorMessage(e), 'error')
    toDelete.value = null
  } finally {
    deleting.value = false
  }
}
</script>

<template>
  <PageHeader :crumbs="[{ title: 'Roles y permisos' }]">
    <v-btn color="primary" prepend-icon="mdi-shield-plus-outline" @click="openForm(null)">
      Nuevo rol
    </v-btn>
  </PageHeader>

  <DataState
    :loading="loading"
    :error="error"
    :empty="items.length === 0"
    empty-title="No hay roles"
    empty-icon="mdi-shield-off-outline"
    @retry="load"
  >
    <div class="mb-3">
      <PerPageSelect v-model="perPage" />
    </div>

    <v-card border flat>
      <v-progress-linear :active="loading" indeterminate />
      <div style="overflow-x: auto">
        <v-table class="crud-table">
          <thead>
            <tr>
              <th>Acciones</th>
              <th>Rol</th>
              <th>Permisos</th>
              <th class="text-right">Usuarios</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="role in items" :key="role.id">
              <td>
                <div class="row-actions">
                  <v-btn
                    v-if="!role.protected"
                    icon="mdi-pencil-outline"
                    size="x-small"
                    variant="tonal"
                    color="warning"
                    rounded="lg"
                    :aria-label="`Editar ${role.name}`"
                    @click="openForm(role)"
                  />
                  <v-btn
                    v-if="!role.protected"
                    icon="mdi-delete-outline"
                    size="x-small"
                    variant="tonal"
                    color="error"
                    rounded="lg"
                    :aria-label="`Eliminar ${role.name}`"
                    @click="toDelete = role"
                  />
                  <v-icon v-else icon="mdi-lock-outline" size="small" title="Rol protegido" class="ml-2" />
                </div>
              </td>
              <td class="text-no-wrap">{{ role.name }}</td>
              <td class="py-2">
                <v-chip v-for="name in role.permissions" :key="name" size="small" class="mr-1 mb-1">
                  {{ describePermission(name).subject }}: {{ describePermission(name).action }}
                </v-chip>
              </td>
              <td class="text-right">{{ role.users_count ?? 0 }}</td>
            </tr>
          </tbody>
        </v-table>
      </div>
    </v-card>
    <PaginationBar v-model="page" :meta="meta" />
  </DataState>

  <RoleFormDialog v-model="dialog" :role="editing" :permissions="permissions" @saved="load" />

  <v-dialog :model-value="toDelete !== null" max-width="420" @update:model-value="toDelete = null">
    <v-card title="Eliminar rol">
      <v-card-text>
        ¿Seguro que quieres eliminar el rol <strong>{{ toDelete?.name }}</strong>? Esta acción no se puede deshacer.
      </v-card-text>
      <v-card-actions>
        <v-spacer />
        <v-btn :disabled="deleting" @click="toDelete = null">Cancelar</v-btn>
        <v-btn color="error" variant="flat" :loading="deleting" @click="confirmDelete">Eliminar</v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>
