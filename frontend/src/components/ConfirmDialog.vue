<script setup lang="ts">
withDefaults(
  defineProps<{
    title: string
    confirmText?: string
    icon?: string
    color?: string
    loading?: boolean
    confirmDisabled?: boolean
  }>(),
  { confirmText: 'Confirmar', icon: 'mdi-alert-outline', color: 'error', loading: false, confirmDisabled: false },
)

const open = defineModel<boolean>({ required: true })
defineEmits<{ confirm: [] }>()
</script>

<template>
  <v-dialog v-model="open" max-width="420" :persistent="loading">
    <v-card class="text-center pa-6" rounded="xl">
      <v-avatar :color="color" variant="tonal" size="64" class="mx-auto mb-4">
        <v-icon :icon="icon" size="32" />
      </v-avatar>
      <h2 class="text-h6 mb-2">{{ title }}</h2>
      <div class="text-body-2 text-medium-emphasis">
        <slot />
      </div>

      <div class="d-flex ga-3 mt-6">
        <v-btn class="flex-1-1" variant="outlined" :disabled="loading" @click="open = false">Cancelar</v-btn>
        <v-btn
          class="flex-1-1"
          variant="flat"
          :color="color"
          :loading="loading"
          :disabled="confirmDisabled"
          @click="$emit('confirm')"
        >
          {{ confirmText }}
        </v-btn>
      </div>
    </v-card>
  </v-dialog>
</template>
