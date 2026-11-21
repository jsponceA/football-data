<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { getRoles, getUsers, setUserActive } from '@/api/admin'
import ConfirmDialog from '@/components/ConfirmDialog.vue'
import CrudTable from '@/components/CrudTable.vue'
import DataState from '@/components/DataState.vue'
import FilterBar from '@/components/FilterBar.vue'
import FilterField from '@/components/FilterField.vue'
import PageHeader from '@/components/PageHeader.vue'
import PaginationBar from '@/components/PaginationBar.vue'
import PerPageSelect from '@/components/PerPageSelect.vue'
import { useNotify } from '@/composables/useNotify'
import { usePaginatedList } from '@/composables/usePaginatedList'
import { useAuthStore } from '@/stores/auth'
import type { ManagedUser } from '@/types/api'
import { errorMessage, formatDateTime } from '@/utils/format'

const { items, meta, page, perPage, filters, filtered, loading, error, load, apply, clear } =
  usePaginatedList(getUsers, { search: '', role: null as string | null })
const { notify } = useNotify()
const auth = useAuthStore()

const toDeactivate = ref<ManagedUser | null>(null)
// id del usuario cuyo estado se está cambiando, para bloquear su botón mientras tanto
const changing = ref<number | null>(null)

async function changeStatus(user: ManagedUser, active: boolean) {
  changing.value = user.id
  try {
    await setUserActive(user.id, active)
    notify(active ? `${user.name} quedó activo.` : `${user.name} quedó desactivado.`)
    toDeactivate.value = null
    load()
  } catch (e) {
    notify(errorMessage(e), 'error')
    toDeactivate.value = null
  } finally {
    changing.value = null
  }
}

// activar no tiene riesgo y va directo; desactivar cierra sus sesiones, así que pide confirmación
function toggle(user: ManagedUser) {
  if (user.is_active) {
    toDeactivate.value = user
  } else {
    changeStatus(user, true)
  }
}

const roleNames = ref<string[]>([])

// los roles solo hacen falta para el filtro; el formulario los pide por su cuenta
onMounted(async () => {
  try {
    roleNames.value = (await getRoles({ per_page: 100 })).data.map((r) => r.name)
  } catch {
    // sin roles el filtro queda vacío, la lista sigue funcionando
  }
})
</script>

<template>
  <PageHeader :crumbs="[{ title: 'Usuarios' }]">
    <v-btn color="primary" prepend-icon="mdi-account-plus-outline" :to="{ name: 'user-new' }">
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
                :to="{ name: 'user-edit', params: { id: user.id } }"
              />
              <v-btn
                :icon="user.is_active ? 'mdi-account-off-outline' : 'mdi-account-check-outline'"
                size="x-small"
                variant="tonal"
                :color="user.is_active ? 'error' : 'success'"
                rounded="lg"
                :loading="changing === user.id"
                :disabled="user.id === auth.user?.id"
                :aria-label="`${user.is_active ? 'Desactivar' : 'Activar'} ${user.name}`"
                :title="user.id === auth.user?.id ? 'No puedes desactivar tu propio usuario' : user.is_active ? 'Desactivar' : 'Activar'"
                @click="toggle(user)"
              />
            </div>
          </td>
          <td class="text-no-wrap">
            <v-avatar size="32" color="primary" variant="tonal" class="mr-2">
              <v-img v-if="user.photo_url" :src="user.photo_url" :alt="user.name" cover />
              <template v-else>{{ user.name.charAt(0).toUpperCase() }}</template>
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

  <ConfirmDialog
    :model-value="toDeactivate !== null"
    title="Desactivar usuario"
    confirm-text="Desactivar"
    icon="mdi-account-off-outline"
    :loading="changing !== null"
    @update:model-value="toDeactivate = null"
    @confirm="toDeactivate && changeStatus(toDeactivate, false)"
  >
    <strong>{{ toDeactivate?.name }}</strong> no podrá iniciar sesión y se cerrarán sus sesiones abiertas.
    Puedes volver a activarlo cuando quieras.
  </ConfirmDialog>
</template>
