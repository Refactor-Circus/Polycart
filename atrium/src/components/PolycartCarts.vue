<script setup lang="ts">
import type { TableColumn } from '@refactor-circus/atrium/ui'
import { AtriumIntegrationError } from '@refactor-circus/atrium'
import PolycartCartStatus from './PolycartCartStatus.vue'
import {
  polycartCartPath,
  polycartCartTitle,
  polycartData,
  polycartExpired,
  polycartLoosely,
  polycartOwner,
  usePolycart,
  type PolycartCart,
  type PolycartCartsQuery,
  type PolycartCursorPage,
  type PolycartType
} from './polycart'

/**
 * The body of `polycart.carts`, ported from v1's `ui/carts/index`: the carts
 * the user can access (`carts.index`), filtered by v1's type, status, source
 * and search, and paged by cursor as the endpoint pages. The type filter
 * offers the registered types (`types.index`), so it never offers a type the
 * API would refuse.
 *
 * The filters live in the page's query (`?type=order&status=open`), as v1's
 * GET form kept them, so the widgets and the types screen link to a filtered
 * list; `unexpired=1` from such a link is kept until the filters change.
 */
interface Filters {
  type: string
  status: string
  source: string
  search: string
}

const FILTERS = ['type', 'status', 'source', 'search'] as const

const { t, locale } = useI18n()
const route = useRoute()
const router = useRouter()
const client = usePolycart()

function queryString(key: string): string {
  const value = route.query[key]

  return typeof value === 'string' ? value : ''
}

const filters = reactive<Filters>({ type: queryString('type'), status: queryString('status'), source: queryString('source'), search: queryString('search') })
/** The filters the list shows: what is typed applies only on submit. */
const applied = ref<Filters>({ ...filters })
const unexpired = ref(['1', 'true'].includes(queryString('unexpired')))
const cursor = ref<string | null>(null)

const carts = shallowRef<PolycartCart[]>([])
const next = ref<string | null>(null)
const prev = ref<string | null>(null)
const types = shallowRef<string[]>([])
/** The first load has answered. */
const loaded = ref(false)
const failed = ref(false)
const forbidden = ref(false)
const errors = ref<string[]>([])

const dates = computed(() => new Intl.DateTimeFormat(locale.value, { dateStyle: 'medium', timeStyle: 'short' }))

const typeItems = computed(() => types.value.map(type => ({ value: type, label: type })))

const columns = computed<TableColumn<PolycartCart>[]>(() => [
  { id: 'cart', header: t('polycart.carts.cart') },
  { id: 'type', header: t('polycart.carts.type') },
  { id: 'status', header: t('polycart.carts.status') },
  { id: 'owner', header: t('polycart.carts.owner') },
  { id: 'source', header: t('polycart.carts.source') },
  { id: 'lines', header: t('polycart.carts.lines'), meta: { class: { th: 'text-right', td: 'text-right' } } },
  { id: 'updated', header: t('polycart.carts.updated'), meta: { class: { td: 'text-muted' } } },
  { id: 'expires', header: t('polycart.carts.expires') }
])

/** `carts.index`'s query: the applied filters that are set, and the cursor. */
function query(): Record<string, string> {
  const sent: Record<string, string> = {}

  for (const key of FILTERS) {
    if (applied.value[key] !== '') {
      sent[key] = applied.value[key]
    }
  }

  if (unexpired.value) {
    // A Laravel boolean: `1` passes, the string `true` does not.
    sent.unexpired = '1'
  }

  if (cursor.value !== null) {
    sent.cursor = cursor.value
  }

  return sent
}

async function load(): Promise<void> {
  if (!client) {
    // The route middleware shows the integration as unavailable instead.
    loaded.value = true
    return
  }

  try {
    const page = polycartData<PolycartCursorPage<PolycartCart>>(await client.GET('/carts', { params: { query: polycartLoosely<PolycartCartsQuery>(query()) } }))

    carts.value = page.data
    next.value = page.meta.next_cursor
    prev.value = page.meta.prev_cursor
    failed.value = false
    errors.value = []
  } catch (error) {
    const status = atriumStatus(error)
    const body = (error instanceof AtriumIntegrationError ? error.body : null) as { errors?: Record<string, string[]> } | null

    forbidden.value = status === 403
    errors.value = status === 422 ? Object.values(body?.errors ?? {}).flat() : []
    failed.value = status !== 403 && status !== 422
    carts.value = []
  } finally {
    loaded.value = true
  }
}

async function loadTypes(): Promise<void> {
  if (!client) {
    return
  }

  try {
    types.value = polycartData<{ data: PolycartType[] }>(await client.GET('/types')).data.map(type => type.key)
  } catch {
    // The filter then offers no type; the list's own answer says why.
  }
}

async function applyFilters(): Promise<void> {
  applied.value = { ...filters }
  unexpired.value = false
  cursor.value = null

  const kept = Object.fromEntries(Object.entries(route.query).filter(([key]) => !(FILTERS as readonly string[]).includes(key) && key !== 'unexpired'))
  const set = Object.fromEntries(FILTERS.filter(key => applied.value[key] !== '').map(key => [key, applied.value[key]]))

  await router.replace({ query: { ...kept, ...set } })
  await load()
}

async function resetFilters(): Promise<void> {
  Object.assign(filters, { type: '', status: '', source: '', search: '' })
  await applyFilters()
}

async function page(target: string | null): Promise<void> {
  cursor.value = target
  await load()
}

function dated(at: string | null): string {
  return at === null ? '' : dates.value.format(new Date(at))
}

void Promise.all([loadTypes(), load()])
</script>

<template>
  <div class="flex flex-col gap-4" data-testid="polycart-carts">
    <AtriumNoAccess v-if="forbidden" />

    <template v-else>
      <UForm :state="filters" class="grid items-start gap-3 sm:grid-cols-2 lg:grid-cols-5" data-testid="polycart-filters" @submit="applyFilters">
        <UFormField :label="t('polycart.carts.type')" name="type">
          <AtriumSelect v-model="filters.type" :items="typeItems" :blank="t('polycart.carts.all_types')" class="w-full" data-testid="polycart-filter-type" />
        </UFormField>
        <UFormField :label="t('polycart.carts.status')" name="status">
          <UInput v-model="filters.status" class="w-full" data-testid="polycart-filter-status" />
        </UFormField>
        <UFormField :label="t('polycart.carts.source')" name="source">
          <UInput v-model="filters.source" class="w-full" data-testid="polycart-filter-source" />
        </UFormField>
        <UFormField :label="t('polycart.carts.search')" name="search">
          <UInput v-model="filters.search" class="w-full" data-testid="polycart-filter-search" />
        </UFormField>
        <div class="mt-field-label flex gap-2">
          <UButton type="submit" variant="outline" icon="i-lucide-funnel" :label="t('polycart.carts.filter')" data-testid="polycart-filter" />
          <UButton color="neutral" variant="ghost" icon="i-lucide-x" :label="t('polycart.carts.reset')" data-testid="polycart-reset" @click="resetFilters" />
        </div>
      </UForm>

      <UAlert v-if="errors.length > 0" color="error" variant="subtle" icon="i-lucide-circle-alert" data-testid="polycart-filter-errors">
        <template #description>
          <ul>
            <li v-for="message in errors" :key="message">{{ message }}</li>
          </ul>
        </template>
      </UAlert>

      <USkeleton v-if="!loaded" class="h-32" />

      <AtriumState v-else-if="failed" kind="error" :title="t('polycart.carts.load_failed')" data-testid="polycart-load-error">
        <UButton size="sm" variant="outline" icon="i-lucide-refresh-cw" :label="t('polycart.retry')" data-testid="polycart-retry" @click="load()" />
      </AtriumState>

      <UTable v-else :data="carts" :columns="columns" class="rounded-md border border-default" data-testid="polycart-carts-table">
        <template #empty>
          <UEmpty icon="i-lucide-shopping-cart" :title="t('polycart.carts.no_carts')" variant="naked" data-testid="polycart-no-carts" />
        </template>
        <template #cart-cell="{ row }">
          <ULink :to="polycartCartPath(row.original.id)" class="font-medium underline-offset-2 hover:underline" data-testid="polycart-cart-link">
            {{ polycartCartTitle(row.original) }}
          </ULink>
        </template>
        <template #type-cell="{ row }">
          <UBadge color="primary" variant="subtle" :label="row.original.type" />
        </template>
        <template #status-cell="{ row }">
          <PolycartCartStatus v-if="row.original.status" :status="row.original.status" :expired="polycartExpired(row.original)" />
          <span v-else class="text-muted">—</span>
        </template>
        <template #owner-cell="{ row }">
          {{ polycartOwner(row.original) ?? t('polycart.guest') }}
        </template>
        <template #source-cell="{ row }">
          <UBadge color="neutral" variant="subtle" :label="row.original.source ?? '—'" />
        </template>
        <template #lines-cell="{ row }">
          {{ row.original.lines_count ?? '' }}
        </template>
        <template #updated-cell="{ row }">
          {{ dated(row.original.updated_at) }}
        </template>
        <template #expires-cell="{ row }">
          <span v-if="row.original.expires_at" :class="polycartExpired(row.original) ? 'text-warning' : 'text-muted'">{{ dated(row.original.expires_at) }}</span>
          <span v-else class="text-muted">{{ t('polycart.never') }}</span>
        </template>
      </UTable>

      <div v-if="loaded && !failed && (next !== null || prev !== null)" class="flex items-center justify-between gap-3 text-sm">
        <UButton
          size="sm"
          color="neutral"
          variant="outline"
          icon="i-lucide-chevron-left"
          :label="t('polycart.carts.previous')"
          :disabled="prev === null"
          data-testid="polycart-prev"
          @click="page(prev)"
        />
        <UButton
          size="sm"
          color="neutral"
          variant="outline"
          trailing-icon="i-lucide-chevron-right"
          :label="t('polycart.carts.next')"
          :disabled="next === null"
          data-testid="polycart-next"
          @click="page(next)"
        />
      </div>
    </template>
  </div>
</template>
