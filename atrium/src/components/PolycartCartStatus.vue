<script setup lang="ts">
import { polycartStatusColor } from './polycart'

/**
 * A cart's status, coloured by meaning as v1's `cart-status` partial did;
 * an expired cart reads as over, and says so.
 */
const props = defineProps<{ status: string, expired?: boolean }>()

const { t } = useI18n()

const color = computed(() => polycartStatusColor(props.status, props.expired))
const label = computed(() => props.expired ? t('polycart.status_expired', { status: props.status }) : props.status)
</script>

<template>
  <UBadge
    :color="color"
    variant="subtle"
    :label="label"
    :data-status="status"
    :data-color="color"
    data-testid="polycart-cart-status"
  />
</template>
