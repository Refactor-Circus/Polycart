<script setup lang="ts">
import { usePolycartStats } from './usePolycartStats'

/**
 * The `polycart.carts-by-type` widget, ported from v1's
 * `ui/widgets/carts-by-type`: how many unexpired carts each registered type
 * holds (`stats.show` `by_type`), each linking to those carts. PHP sends it
 * `data: {}`; it shares the stats request with `polycart.recent-carts`.
 */
defineProps<{ data?: Record<string, unknown>, settings?: Record<string, unknown> }>()

const { t } = useI18n()
const { data: stats, status, forbidden } = usePolycartStats()

function cartsOf(type: string) {
  return { path: '/polycart/carts', query: { type, unexpired: '1' } }
}
</script>

<template>
  <div class="flex flex-col gap-3" data-testid="polycart-carts-by-type">
    <p class="text-sm font-medium">{{ t('polycart.widgets.carts_by_type') }}</p>

    <AtriumNoAccess v-if="forbidden" compact />
    <AtriumErrorCard v-else-if="status === 'error'" :message="t('atrium.widget.failed')" />
    <USkeleton v-else-if="stats === null" class="h-10" />

    <div v-else class="flex flex-wrap gap-2">
      <RouterLink
        v-for="entry in stats.by_type"
        :key="entry.type"
        :to="cartsOf(entry.type)"
        class="flex items-center gap-2 rounded-md border border-default px-3 py-2 transition hover:bg-elevated"
        data-testid="polycart-type-count"
      >
        <UBadge color="primary" variant="subtle" :label="entry.type" />
        <span class="text-sm font-semibold tabular-nums">{{ entry.count }}</span>
      </RouterLink>
    </div>
  </div>
</template>
