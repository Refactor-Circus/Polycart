<script setup lang="ts">
import { polycartCartPath, polycartCartTitle } from './polycart'
import { usePolycartStats } from './usePolycartStats'

/**
 * The `polycart.recent-carts` widget, ported from v1's
 * `ui/widgets/recent-carts`: the five carts changed most recently
 * (`stats.show` `recent`), each linking to its page. PHP sends it `data: {}`;
 * it shares the stats request with `polycart.carts-by-type`.
 */
defineProps<{ data?: Record<string, unknown>, settings?: Record<string, unknown> }>()

const { t, locale } = useI18n()
const { data: stats, status, forbidden } = usePolycartStats()

const dates = computed(() => new Intl.DateTimeFormat(locale.value, { dateStyle: 'medium', timeStyle: 'short' }))

function updated(at: string | null): string {
  return at === null ? '' : dates.value.format(new Date(at))
}
</script>

<template>
  <div class="flex flex-col gap-3" data-testid="polycart-recent-carts">
    <p class="text-sm font-medium">{{ t('polycart.widgets.recent_carts') }}</p>

    <AtriumNoAccess v-if="forbidden" compact />
    <AtriumErrorCard v-else-if="status === 'error'" :message="t('atrium.widget.failed')" />
    <USkeleton v-else-if="stats === null" class="h-10" />

    <p v-else-if="stats.recent.length === 0" class="text-sm text-muted" data-testid="polycart-no-recent-carts">
      {{ t('polycart.widgets.no_carts') }}
    </p>

    <ul v-else class="flex flex-col gap-2">
      <li
        v-for="cart in stats.recent"
        :key="cart.id"
        class="flex items-center justify-between gap-2 text-sm"
        data-testid="polycart-recent-cart"
      >
        <span class="flex min-w-0 items-center gap-2">
          <UBadge color="primary" variant="subtle" :label="cart.type" />
          <RouterLink :to="polycartCartPath(cart.id)" class="truncate underline-offset-2 hover:underline">{{ polycartCartTitle(cart) }}</RouterLink>
        </span>
        <span class="shrink-0 text-muted">{{ t('polycart.widgets.line_count', cart.lines_count ?? 0) }} · {{ updated(cart.updated_at) }}</span>
      </li>
    </ul>
  </div>
</template>
