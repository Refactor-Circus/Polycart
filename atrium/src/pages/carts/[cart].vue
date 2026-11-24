<script setup lang="ts">
import PolycartCart from '../../components/PolycartCart.vue'

/**
 * `polycart.cart`: one cart, from Polycart's API, inside the extensions other
 * packages registered for the screen (`atrium.screens.show`, with the cart's
 * id as its record).
 */
const { t } = useI18n()
const route = useRoute()

const cartId = computed(() => String(route.params.cart ?? ''))

useHead({ title: () => t('polycart.cart.heading') })
</script>

<template>
  <UDashboardPanel>
    <template #header>
      <UDashboardNavbar :title="t('polycart.cart.heading')">
        <template #leading>
          <UButton to="/polycart/carts" color="neutral" variant="ghost" icon="i-lucide-arrow-left" :aria-label="t('polycart.cart.all_carts')" />
        </template>
      </UDashboardNavbar>
    </template>
    <template #body>
      <AtriumIntegrationScreen screen-key="polycart.cart" :record="cartId">
        <PolycartCart :key="cartId" :cart-id="cartId" />
      </AtriumIntegrationScreen>
    </template>
  </UDashboardPanel>
</template>
