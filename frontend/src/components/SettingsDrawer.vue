<script setup lang="ts">
import { useAppearance } from '@/composables/useAppearance'
import { palettes, type PaletteKey } from '@/plugins/palettes'
import { useUiStore, type ThemeMode } from '@/stores/ui'

const open = defineModel<boolean>({ required: true })

const ui = useUiStore()
const { isDark, setMode, setPalette, reset } = useAppearance()

const modes: { value: ThemeMode; label: string; icon: string }[] = [
  { value: 'light', label: 'Claro', icon: 'mdi-white-balance-sunny' },
  { value: 'dark', label: 'Oscuro', icon: 'mdi-weather-night' },
  { value: 'system', label: 'Sistema', icon: 'mdi-monitor' },
]

const keys = Object.keys(palettes) as PaletteKey[]
</script>

<template>
  <v-navigation-drawer v-model="open" location="right" temporary width="340">
    <div class="d-flex align-center px-4 py-3">
      <h2 class="text-h6">Personalización</h2>
      <v-spacer />
      <v-btn icon="mdi-close" variant="text" aria-label="Cerrar" @click="open = false" />
    </div>
    <v-divider />

    <div class="pa-4">
      <h3 class="text-subtitle-1 font-weight-medium">Tema</h3>
      <p class="text-body-2 text-medium-emphasis mb-2">
        Elige el modo claro, el oscuro o el que use tu sistema.
      </p>
      <v-radio-group :model-value="ui.theme" hide-details @update:model-value="(mode) => mode && setMode(mode)">
        <v-radio v-for="mode in modes" :key="mode.value" :value="mode.value" color="primary">
          <template #label>
            <v-icon :icon="mode.icon" class="mr-2" />
            {{ mode.label }}
          </template>
        </v-radio>
      </v-radio-group>
    </div>
    <v-divider />

    <div class="pa-4">
      <h3 class="text-subtitle-1 font-weight-medium">Color principal</h3>
      <p class="text-body-2 text-medium-emphasis mb-3">Se aplica a botones, enlaces y selecciones.</p>
      <div class="d-flex flex-wrap ga-3">
        <v-btn
          v-for="key in keys"
          :key="key"
          icon
          size="small"
          variant="flat"
          :color="isDark ? palettes[key].dark : palettes[key].light"
          :aria-label="`Color ${palettes[key].label}`"
          :title="palettes[key].label"
          @click="setPalette(key)"
        >
          <v-icon v-if="ui.palette === key" icon="mdi-check" />
        </v-btn>
      </div>
    </div>
    <v-divider />

    <div class="pa-4">
      <v-switch v-model="ui.rail" color="primary" hide-details label="Menú lateral compacto" />
      <p class="text-body-2 text-medium-emphasis">
        Muestra solo los iconos. También lo cambias con el botón de menú de la barra.
      </p>
    </div>
    <v-divider />

    <div class="pa-4">
      <v-btn block variant="tonal" prepend-icon="mdi-restore" @click="reset">
        Restablecer valores
      </v-btn>
    </div>
  </v-navigation-drawer>
</template>
