<script setup lang="ts">
import PolycartTypes from '../components/PolycartTypes.vue'

/**
 * `polycart.types`: every registered cart type, from Polycart's API, inside
 * the extensions other packages registered for the screen
 * (`atrium.screens.show`).
 */
const { t } = useI18n()

useHead({ title: () => t('polycart.types.title') })
</script>

<template>
  <UDashboardPanel>
    <template #header>
      <UDashboardNavbar :title="t('polycart.types.title')" />
    </template>
    <template #body>
      <p class="mb-4 text-sm text-muted">{{ t('polycart.types.description') }}</p>
      <AtriumIntegrationScreen screen-key="polycart.types">
        <PolycartTypes />
      </AtriumIntegrationScreen>
    </template>
  </UDashboardPanel>
</template>
