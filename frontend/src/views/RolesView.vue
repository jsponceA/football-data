<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { deleteRole, getPermissions, getRoles } from '@/api/admin'
import CrudTable from '@/components/CrudTable.vue'
import ConfirmDialog from '@/components/ConfirmDialog.vue'
import DataState from '@/components/DataState.vue'
import FilterBar from '@/components/FilterBar.vue'
import FilterField from '@/components/FilterField.vue'
import PageHeader from '@/components/PageHeader.vue'
import PaginationBar from '@/components/PaginationBar.vue'
import PerPageSelect from '@/components/PerPageSelect.vue'
import { usePaginatedList } from '@/composables/usePaginatedList'
import { useNotify } from '@/composables/useNotify'
import type { Role } from '@/types/api'
import { errorMessage } from '@/utils/format'
import { describePermission } from '@/utils/permissions'

const { items, meta, page, perPage, filters, filtered, loading, error, load, apply, clear } = usePaginatedList(
  getRoles,
  { search: '', permission: null as string | null, users: null as string | null },
)
const { notify } = useNotify()

const usageOptions = [
  { title: 'Con usuarios', value: 'with' },
  { title: 'Sin usuarios', value: 'without' },
]

// el filtro de permiso ofrece los mismos nombres que se ven en la tabla
const permissionOptions = ref<{ title: string; value: string }[]>([])

onMounted(async () => {
  try {
    permissionOptions.value = (await getPermissions()).map((p) => ({
      title: `${describePermission(p.name).subject}: ${describePermission(p.name).action}`,
      value: p.name,
    }))
  } catch {
    // sin permisos el filtro queda vacío, la lista sigue funcionando
  }
})

const toDelete = ref<Role | null>(null)
const deleting = ref(false)

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
    <v-btn color="primary" prepend-icon="mdi-shield-plus-outline" :to="{ name: 'role-new' }">
      Nuevo rol
    </v-btn>
  </PageHeader>

  <FilterBar @apply="apply" @clear="clear">
    <FilterField label="Buscar" wide>
      <v-text-field
        v-model="filters.search"
        placeholder="Nombre del rol…"
        prepend-inner-icon="mdi-magnify"
        variant="outlined"
        density="compact"
        hide-details
      />
    </FilterField>
    <FilterField label="Permiso" wide>
      <v-select
        v-model="filters.permission"
        :items="permissionOptions"
        placeholder="Cualquier permiso"
        variant="outlined"
        density="compact"
        hide-details
        clearable
      />
    </FilterField>
    <FilterField label="Usuarios">
      <v-select
        v-model="filters.users"
        :items="usageOptions"
        placeholder="Todos"
        variant="outlined"
        density="compact"
        hide-details
        clearable
      />
    </FilterField>
  </FilterBar>

  <DataState
    :loading="loading"
    :error="error"
    :empty="items.length === 0"
    :empty-title="filtered ? 'Sin coincidencias' : 'No hay roles'"
    :empty-text="filtered ? 'Prueba con otros filtros.' : ''"
    empty-icon="mdi-shield-off-outline"
    @retry="load"
  >
    <div class="mb-3">
      <PerPageSelect v-model="perPage" />
    </div>

    <CrudTable :loading="loading">
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
                :to="{ name: 'role-edit', params: { id: role.id } }"
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
    </CrudTable>
    <PaginationBar v-model="page" :meta="meta" />
  </DataState>

  <ConfirmDialog
    :model-value="toDelete !== null"
    title="Eliminar rol"
    confirm-text="Eliminar"
    icon="mdi-delete-outline"
    :loading="deleting"
    :confirm-disabled="(toDelete?.users_count ?? 0) > 0"
    @update:model-value="toDelete = null"
    @confirm="confirmDelete"
  >
    <template v-if="(toDelete?.users_count ?? 0) > 0">
      El rol <strong>{{ toDelete?.name }}</strong> lo usan {{ toDelete?.users_count }} usuario(s).
      Cambia su rol antes de eliminarlo.
    </template>
    <template v-else>
      Vas a eliminar el rol <strong>{{ toDelete?.name }}</strong>. Esta acción no se puede deshacer.
    </template>
  </ConfirmDialog>
</template>
