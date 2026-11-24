<script setup lang="ts">
import { polycartData, polycartStatusColor, usePolycart, type PolycartType } from './polycart'

/**
 * The body of `polycart.types`, ported from v1's `ui/types/index`: every
 * registered cart type (`types.index`) with its statuses, conversions,
 * parents, rules and lifetime, each linking to its carts.
 */
const { t } = useI18n()
const client = usePolycart()

const types = shallowRef<PolycartType[]>([])
const loaded = ref(false)
const failed = ref(false)
const forbidden = ref(false)

async function load(): Promise<void> {
  if (!client) {
    // The route middleware shows the integration as unavailable instead.
    loaded.value = true
    return
  }

  try {
    types.value = polycartData<{ data: PolycartType[] }>(await client.GET('/types')).data
    failed.value = false
  } catch (error) {
    forbidden.value = atriumStatus(error) === 403
    failed.value = !forbidden.value
  } finally {
    loaded.value = true
  }
}

function cartsOf(type: string) {
  return { path: '/polycart/carts', query: { type } }
}

void load()
</script>

<template>
  <div class="flex flex-col gap-4" data-testid="polycart-types">
    <AtriumNoAccess v-if="forbidden" />

    <USkeleton v-else-if="!loaded" class="h-32" />

    <div v-else-if="failed" class="flex items-center justify-between gap-3 rounded-md border border-default p-4 text-sm" role="alert" data-testid="polycart-load-error">
      <span>{{ t('polycart.types.load_failed') }}</span>
      <UButton size="sm" variant="outline" icon="i-lucide-refresh-cw" :label="t('polycart.retry')" data-testid="polycart-retry" @click="load()" />
    </div>

    <div v-else class="overflow-x-auto rounded-md border border-default">
      <table class="w-full text-left text-sm" data-testid="polycart-types-table">
        <thead class="bg-elevated text-muted">
          <tr>
            <th class="px-3 py-2 font-medium">{{ t('polycart.types.type') }}</th>
            <th class="px-3 py-2 font-medium">{{ t('polycart.types.statuses') }}</th>
            <th class="px-3 py-2 font-medium">{{ t('polycart.types.converts_to') }}</th>
            <th class="px-3 py-2 font-medium">{{ t('polycart.types.parents') }}</th>
            <th class="px-3 py-2 font-medium">{{ t('polycart.types.rules') }}</th>
            <th class="px-3 py-2 font-medium">{{ t('polycart.types.lifetime') }}</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-default">
          <tr v-for="type in types" :key="type.key" data-testid="polycart-type">
            <td class="px-3 py-2">
              <RouterLink :to="cartsOf(type.key)" class="font-medium underline-offset-2 hover:underline" data-testid="polycart-type-link">{{ type.key }}</RouterLink>
            </td>
            <td class="px-3 py-2">
              <div v-if="type.statuses.length > 0" class="flex flex-wrap items-center gap-2">
                <UBadge
                  v-for="status in type.statuses"
                  :key="status"
                  :color="polycartStatusColor(status)"
                  variant="subtle"
                  :label="status"
                  :data-status="status"
                  data-testid="polycart-type-status"
                />
              </div>
              <span v-else class="text-muted">—</span>
            </td>
            <td class="px-3 py-2">{{ type.converts_to.join(', ') || '—' }}</td>
            <td class="px-3 py-2">
              <template v-if="type.parents === null">{{ t('polycart.types.any') }}</template>
              <template v-else-if="type.parents.length === 0">{{ t('polycart.types.root_only') }}</template>
              <template v-else>{{ type.parents.join(', ') }}</template>
            </td>
            <td class="px-3 py-2">
              <div class="flex flex-wrap gap-1">
                <UBadge v-if="type.requires_price" color="warning" variant="subtle" :label="t('polycart.types.requires_price')" />
                <UBadge v-if="type.requires_parent" color="primary" variant="subtle" :label="t('polycart.types.requires_parent')" />
                <UBadge v-if="!type.merges_lines" color="neutral" variant="subtle" :label="t('polycart.types.separate_lines')" />
              </div>
            </td>
            <td class="px-3 py-2">{{ type.lifetime ?? t('polycart.never') }}</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>
