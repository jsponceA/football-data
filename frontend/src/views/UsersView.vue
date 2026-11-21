<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { getRoles, getUsers } from '@/api/admin'
import CrudTable from '@/components/CrudTable.vue'
import DataState from '@/components/DataState.vue'
import FilterBar from '@/components/FilterBar.vue'
import FilterField from '@/components/FilterField.vue'
import PageHeader from '@/components/PageHeader.vue'
import PaginationBar from '@/components/PaginationBar.vue'
import PerPageSelect from '@/components/PerPageSelect.vue'
import UserFormDialog from '@/components/UserFormDialog.vue'
import { usePaginatedList } from '@/composables/usePaginatedList'
import type { ManagedUser } from '@/types/api'
import { formatDateTime } from '@/utils/format'

const { items, meta, page, perPage, filters, filtered, loading, error, load, apply, clear } =
  usePaginatedList(getUsers, { search: '', role: null as string | null })

const roleNames = ref<string[]>([])
const dialog = ref(false)
const editing = ref<ManagedUser | null>(null)

onMounted(async () => {
  try {
    roleNames.value = (await getRoles({ per_page: 100 })).data.map((r) => r.name)
  } catch {
    // sin roles el formulario no se puede usar, pero la lista sí; el error real saldrá al guardar
  }
})

function openForm(user: ManagedUser | null) {
  editing.value = user
  dialog.value = true
}
</script>

<template>
  <PageHeader :crumbs="[{ title: 'Usuarios' }]">
    <v-btn color="primary" prepend-icon="mdi-account-plus-outline" @click="openForm(null)">
      Nuevo usuario
    </v-btn>
  </PageHeader>

  <FilterBar @apply="apply" @clear="clear">
    <FilterField label="Buscar" wide>
      <v-text-field
        v-model="filters.search"
        placeholder="Nombre o correo…"
        prepend-inner-icon="mdi-magnify"
        variant="outlined"
        density="compact"
        hide-details
      />
    </FilterField>
    <FilterField label="Rol">
      <v-select
        v-model="filters.role"
        :items="roleNames"
        placeholder="Todos los roles"
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
    :empty-title="filtered ? 'Sin coincidencias' : 'No hay usuarios'"
    :empty-text="filtered ? 'Prueba con otros filtros.' : ''"
    empty-icon="mdi-account-off-outline"
    @retry="load"
  >
    <div class="mb-3">
      <PerPageSelect v-model="perPage" />
    </div>

    <CrudTable :loading="loading">
      <thead>
        <tr>
          <th>Acciones</th>
          <th>Usuario</th>
          <th>Correo</th>
          <th>Roles</th>
          <th>Estado</th>
          <th>Creado</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="user in items" :key="user.id">
          <td>
            <div class="row-actions">
              <v-btn
                icon="mdi-pencil-outline"
                size="x-small"
                variant="tonal"
                color="warning"
                rounded="lg"
                :aria-label="`Editar ${user.name}`"
                @click="openForm(user)"
              />
            </div>
          </td>
          <td class="text-no-wrap">
            <v-avatar size="32" color="primary" variant="tonal" class="mr-2">
              {{ user.name.charAt(0).toUpperCase() }}
            </v-avatar>
            {{ user.name }}
          </td>
          <td>{{ user.email }}</td>
          <td>
            <v-chip v-for="role in user.roles" :key="role" size="small" class="mr-1">{{ role }}</v-chip>
          </td>
          <td>
            <v-chip size="small" :color="user.is_active ? 'success' : undefined">
              {{ user.is_active ? 'Activo' : 'Inactivo' }}
            </v-chip>
          </td>
          <td class="text-no-wrap">{{ formatDateTime(user.created_at) }}</td>
        </tr>
      </tbody>
    </CrudTable>
    <PaginationBar v-model="page" :meta="meta" />
  </DataState>

  <UserFormDialog v-model="dialog" :user="editing" :roles="roleNames" @saved="load" />
</template>
