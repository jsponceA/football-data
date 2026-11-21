<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { getRoles, getUsers } from '@/api/admin'
import DataState from '@/components/DataState.vue'
import PageHeader from '@/components/PageHeader.vue'
import PaginationBar from '@/components/PaginationBar.vue'
import UserFormDialog from '@/components/UserFormDialog.vue'
import { usePaginatedList } from '@/composables/usePaginatedList'
import type { ManagedUser } from '@/types/api'
import { formatDate } from '@/utils/format'

const { items, meta, page, search, loading, error, load } = usePaginatedList(getUsers)

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
  <PageHeader title="Usuarios" subtitle="Cuentas que pueden entrar al sistema">
    <v-text-field
      v-model="search"
      label="Buscar usuario"
      prepend-inner-icon="mdi-magnify"
      density="compact"
      variant="outlined"
      hide-details
      clearable
      style="min-width: 240px"
    />
    <v-btn color="primary" prepend-icon="mdi-plus" @click="openForm(null)">Nuevo</v-btn>
  </PageHeader>

  <DataState
    :loading="loading"
    :error="error"
    :empty="items.length === 0"
    :empty-title="search ? 'Sin coincidencias' : 'No hay usuarios'"
    empty-icon="mdi-account-off-outline"
    @retry="load"
  >
    <v-card>
      <v-progress-linear :active="loading" indeterminate />
      <div style="overflow-x: auto">
        <v-table>
          <thead>
            <tr>
              <th>Nombre</th>
              <th>Correo</th>
              <th>Roles</th>
              <th>Estado</th>
              <th>Creado</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="user in items" :key="user.id">
              <td>{{ user.name }}</td>
              <td>{{ user.email }}</td>
              <td>
                <v-chip v-for="role in user.roles" :key="role" size="small" class="mr-1">{{ role }}</v-chip>
              </td>
              <td>
                <v-chip size="small" :color="user.is_active ? 'success' : undefined">
                  {{ user.is_active ? 'Activo' : 'Inactivo' }}
                </v-chip>
              </td>
              <td>{{ formatDate(user.created_at) }}</td>
              <td class="text-right">
                <v-btn
                  icon="mdi-pencil"
                  variant="text"
                  size="small"
                  :aria-label="`Editar ${user.name}`"
                  @click="openForm(user)"
                />
              </td>
            </tr>
          </tbody>
        </v-table>
      </div>
    </v-card>
    <PaginationBar v-model="page" :meta="meta" />
  </DataState>

  <UserFormDialog v-model="dialog" :user="editing" :roles="roleNames" @saved="load" />
</template>
