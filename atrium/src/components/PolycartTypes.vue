<script setup lang="ts">
import type { TableColumn } from '@nuxt/ui'
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

const columns = computed<TableColumn<PolycartType>[]>(() => [
  { id: 'type', header: t('polycart.types.type') },
  { id: 'statuses', header: t('polycart.types.statuses') },
  { id: 'converts_to', header: t('polycart.types.converts_to') },
  { id: 'parents', header: t('polycart.types.parents') },
  { id: 'rules', header: t('polycart.types.rules') },
  { id: 'lifetime', header: t('polycart.types.lifetime') }
])

function cartsOf(type: string) {
  return { path: '/polycart/carts', query: { type } }
}

void load()
</script>

<template>
  <div class="flex flex-col gap-4" data-testid="polycart-types">
    <AtriumNoAccess v-if="forbidden" />

    <USkeleton v-else-if="!loaded" class="h-32" />

    <AtriumState v-else-if="failed" kind="error" :title="t('polycart.types.load_failed')" data-testid="polycart-load-error">
      <UButton size="sm" variant="outline" icon="i-lucide-refresh-cw" :label="t('polycart.retry')" data-testid="polycart-retry" @click="load()" />
    </AtriumState>

    <UTable v-else :data="types" :columns="columns" class="rounded-md border border-default" data-testid="polycart-types-table">
      <template #type-cell="{ row }">
        <ULink :to="cartsOf(row.original.key)" class="font-medium underline-offset-2 hover:underline" data-testid="polycart-type-link">{{ row.original.key }}</ULink>
      </template>
      <template #statuses-cell="{ row }">
        <div v-if="row.original.statuses.length > 0" class="flex flex-wrap items-center gap-2">
          <UBadge
            v-for="status in row.original.statuses"
            :key="status"
            :color="polycartStatusColor(status)"
            variant="subtle"
            :label="status"
            :data-status="status"
            data-testid="polycart-type-status"
          />
        </div>
        <span v-else class="text-muted">—</span>
      </template>
      <template #converts_to-cell="{ row }">
        {{ row.original.converts_to.join(', ') || '—' }}
      </template>
      <template #parents-cell="{ row }">
        <template v-if="row.original.parents === null">{{ t('polycart.types.any') }}</template>
        <template v-else-if="row.original.parents.length === 0">{{ t('polycart.types.root_only') }}</template>
        <template v-else>{{ row.original.parents.join(', ') }}</template>
      </template>
      <template #rules-cell="{ row }">
        <div class="flex flex-wrap gap-1">
          <UBadge v-if="row.original.requires_price" color="warning" variant="subtle" :label="t('polycart.types.requires_price')" />
          <UBadge v-if="row.original.requires_parent" color="primary" variant="subtle" :label="t('polycart.types.requires_parent')" />
          <UBadge v-if="!row.original.merges_lines" color="neutral" variant="subtle" :label="t('polycart.types.separate_lines')" />
        </div>
      </template>
      <template #lifetime-cell="{ row }">
        {{ row.original.lifetime ?? t('polycart.never') }}
      </template>
    </UTable>
  </div>
</template>
