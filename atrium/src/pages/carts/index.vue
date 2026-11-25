<script setup lang="ts">
import PolycartCarts from '../../components/PolycartCarts.vue'

/**
 * `polycart.carts`: the carts the user can access, from Polycart's API,
 * inside the extensions other packages registered for the screen
 * (`atrium.screens.show`).
 */
const { t } = useI18n()

useHead({ title: () => t('polycart.carts.title') })
</script>

<template>
  <UDashboardPanel>
    <template #header>
      <UDashboardNavbar :title="t('polycart.carts.title')" />
    </template>
    <template #body>
      <AtriumIntegrationScreen screen-key="polycart.carts">
        <PolycartCarts />
      </AtriumIntegrationScreen>
    </template>
  </UDashboardPanel>
</template>
