<script setup lang="ts">
import type { AtriumDetailsItem } from '@refactor-circus/atrium'
import type { TableColumn } from '@refactor-circus/atrium/ui'
import PolycartCartStatus from './PolycartCartStatus.vue'
import {
  POLYCART_VISIBILITIES,
  polycartBasename,
  polycartCartPath,
  polycartCartTitle,
  polycartData,
  polycartExpired,
  polycartLoosely,
  polycartMoney,
  polycartNextStatuses,
  polycartOwner,
  usePolycart,
  usePolycartAction,
  type PolycartCart,
  type PolycartClient,
  type PolycartCursorPage,
  type PolycartLine,
  type PolycartType,
  type PolycartUpdateCart,
  type PolycartVisibility
} from './polycart'

/**
 * The body of `polycart.cart`, ported from v1's `ui/carts/show`: one cart
 * (`carts.show`, with its lines and members), its type's rules (`types.index`)
 * and its nested carts (`carts.index?parent=`), with every v1 action on its
 * v2 route:
 *
 * - update the label and meta (`carts.update`),
 * - delete (`carts.destroy`) and clear (`carts.clear`), each after a confirmation,
 * - move to a next status (`carts.transition`) and convert (`carts.convert`),
 * - set who may see it (`carts.visibility`),
 * - update a line's quantity (`carts.lines.update`) and remove a line
 *   (`carts.lines.destroy`),
 * - share with a member (`carts.members.store`) and unshare (`carts.members.destroy`).
 *
 * Each change reloads the cart. A 422 shows its field errors, or the cart
 * type's refusal; a 403 shows the no-access notice, as Polycart's policies
 * refuse what v1 hid.
 */
const props = defineProps<{ cartId: string }>()

const { t, locale } = useI18n()
const router = useRouter()
const toast = useToast()
const client = usePolycart()
const action = usePolycartAction()

const cart = shallowRef<PolycartCart | null>(null)
const types = shallowRef<PolycartType[] | null>(null)
const children = shallowRef<PolycartCart[]>([])
/** The first load has answered. */
const loaded = ref(false)
const failed = ref(false)
const missing = ref(false)
const refused = ref(false)
const notice = ref<string | null>(null)

const transitionTo = ref('')
const convertTo = ref('')
const keepOriginal = ref(true)
const visibility = ref<PolycartVisibility>('private')
const quantities = reactive<Record<string, number>>({})
const editedLine = ref<string | null>(null)
const share = reactive({ member_type: '', member_id: '', role: '' })
const details = reactive({ label: '', meta: '{}' })
const metaError = ref<string | null>(null)

const dates = computed(() => new Intl.DateTimeFormat(locale.value, { dateStyle: 'medium', timeStyle: 'short' }))

/** The loaded cart as a list of one, for the template (none until it loads). */
const shownCart = computed(() => cart.value === null ? [] : [cart.value])
const cartType = computed(() => types.value?.find(type => type.key === cart.value?.type) ?? null)
/** The types are known and this cart's is not among them: v1 refused every change then. */
const unknownType = computed(() => types.value !== null && cart.value !== null && cartType.value === null)
const nextStatuses = computed(() => polycartNextStatuses(cartType.value, cart.value?.status ?? null))
const conversions = computed(() => cartType.value?.converts_to ?? [])
const visibilityItems = computed(() => POLYCART_VISIBILITIES.map(option => ({ value: option, label: t(`polycart.visibility.${option}`) })))

const lineColumns = computed<TableColumn<PolycartLine>[]>(() => [
  { id: 'item', header: t('polycart.cart.item') },
  { id: 'unit_price', header: t('polycart.cart.unit_price'), meta: { class: { th: 'text-right', td: 'text-right' } } },
  { id: 'quantity', header: t('polycart.cart.quantity') },
  { id: 'total', header: t('polycart.cart.total'), meta: { class: { th: 'text-right', td: 'text-right' } } },
  { id: 'remove', header: () => h('span', { class: 'sr-only' }, t('polycart.cart.remove')), meta: { class: { td: 'text-right' } } }
])

// The cart and its type load side by side: each choice defaults to the first
// on offer once both are in, and again whenever what is on offer changes.
watch(nextStatuses, (offered) => {
  if (!offered.includes(transitionTo.value)) {
    transitionTo.value = offered[0] ?? ''
  }
}, { immediate: true })

watch(conversions, (offered) => {
  if (!offered.includes(convertTo.value)) {
    convertTo.value = offered[0] ?? ''
  }
}, { immediate: true })

/** Puts a freshly loaded cart's values in the forms. */
function fill(loadedCart: PolycartCart): void {
  visibility.value = loadedCart.visibility ?? 'private'
  details.label = loadedCart.label ?? ''
  details.meta = JSON.stringify(loadedCart.meta ?? {}, null, 2)

  for (const line of loadedCart.lines ?? []) {
    quantities[line.id] = line.quantity
  }
}

async function loadChildren(pc: PolycartClient, parent: string): Promise<PolycartCart[]> {
  try {
    return polycartData<PolycartCursorPage<PolycartCart>>(await pc.GET('/carts', { params: { query: { parent } } })).data
  } catch {
    return []
  }
}

async function loadTypes(pc: PolycartClient): Promise<void> {
  try {
    types.value = polycartData<{ data: PolycartType[] }>(await pc.GET('/types')).data
  } catch {
    // Without its type, the cart offers no move or conversion.
  }
}

async function load(): Promise<void> {
  if (!client) {
    // The route middleware shows the integration as unavailable instead.
    loaded.value = true
    return
  }

  try {
    const shown = polycartData<{ data: PolycartCart }>(await client.GET('/carts/{cart}', { params: { path: { cart: props.cartId } } })).data

    cart.value = shown
    failed.value = false
    fill(shown)
    children.value = (shown.children_count ?? 0) > 0 ? await loadChildren(client, shown.id) : []
  } catch (error) {
    const status = atriumStatus(error)

    refused.value = status === 403
    missing.value = status === 404
    failed.value = status !== 403 && status !== 404
  } finally {
    loaded.value = true
  }
}

/** Runs a change; on success shows `done` and reloads the cart. */
async function change(run: (pc: PolycartClient) => Promise<unknown>, done: string): Promise<boolean> {
  notice.value = null

  if (!client || !await action.run(() => run(client))) {
    return false
  }

  notice.value = done
  await load()

  return true
}

const path = () => ({ path: { cart: props.cartId } })

async function updateDetails(): Promise<void> {
  metaError.value = null

  let meta: unknown

  try {
    meta = details.meta.trim() === '' ? null : JSON.parse(details.meta)
  } catch {
    meta = undefined
  }

  if (meta === undefined || (meta !== null && (typeof meta !== 'object' || Array.isArray(meta)))) {
    metaError.value = t('polycart.cart.meta_invalid')
    return
  }

  await change(async pc => polycartData(await pc.PATCH('/carts/{cart}', {
    params: path(),
    body: { label: details.label === '' ? null : details.label, meta: polycartLoosely<PolycartUpdateCart['meta']>(meta) }
  })), t('polycart.cart.updated'))
}

async function destroy(): Promise<void> {
  if (!client || !window.confirm(t('polycart.cart.delete_confirm'))) {
    return
  }

  notice.value = null

  if (await action.run(async () => polycartData(await client.DELETE('/carts/{cart}', { params: path() })))) {
    toast.add({ title: t('polycart.cart.deleted'), color: 'success' })
    await router.push('/polycart/carts')
  }
}

async function clear(): Promise<void> {
  if (!window.confirm(t('polycart.cart.clear_confirm'))) {
    return
  }

  await change(async pc => polycartData(await pc.POST('/carts/{cart}/clear', { params: path() })), t('polycart.cart.cleared'))
}

async function transition(): Promise<void> {
  await change(async pc => polycartData(await pc.POST('/carts/{cart}/status', {
    params: path(),
    body: { status: transitionTo.value }
  })), t('polycart.cart.status_changed'))
}

async function convert(): Promise<void> {
  if (!client) {
    return
  }

  notice.value = null
  const result: { cart: PolycartCart | null } = { cart: null }

  const converted = await action.run(async () => {
    result.cart = polycartData<{ data: PolycartCart }>(await client.POST('/carts/{cart}/convert', {
      params: path(),
      body: { to: convertTo.value, copy: keepOriginal.value }
    })).data
  })

  if (!converted) {
    return
  }

  if (result.cart !== null && result.cart.id !== props.cartId) {
    toast.add({ title: t('polycart.cart.converted'), color: 'success' })
    await router.push(polycartCartPath(result.cart.id))
    return
  }

  notice.value = t('polycart.cart.converted')
  await load()
}

async function setVisibility(): Promise<void> {
  await change(async pc => polycartData(await pc.PUT('/carts/{cart}/visibility', {
    params: path(),
    body: { visibility: visibility.value }
  })), t('polycart.cart.visibility_changed'))
}

async function updateLine(line: PolycartLine): Promise<void> {
  editedLine.value = line.id

  await change(async pc => polycartData(await pc.PATCH('/carts/{cart}/lines', {
    params: path(),
    body: { lines: [{ id: line.id, quantity: Number(quantities[line.id] ?? line.quantity) }] }
  })), t('polycart.cart.line_updated'))
}

async function removeLine(line: PolycartLine): Promise<void> {
  editedLine.value = line.id

  await change(async pc => polycartData(await pc.DELETE('/carts/{cart}/lines', {
    params: { ...path(), query: { 'lines[]': [line.id] } }
  })), t('polycart.cart.line_removed'))
}

async function shareCart(): Promise<void> {
  const shared = await change(async pc => polycartData(await pc.POST('/carts/{cart}/members', {
    params: path(),
    body: { ...share }
  })), t('polycart.cart.shared'))

  if (shared) {
    Object.assign(share, { member_type: '', member_id: '', role: '' })
  }
}

async function unshare(memberId: string): Promise<void> {
  await change(async pc => polycartData(await pc.DELETE('/carts/{cart}/members/{member}', {
    params: { path: { cart: props.cartId, member: memberId } }
  })), t('polycart.cart.unshared'))
}

function dated(at: string | null): string {
  return at === null ? '' : dates.value.format(new Date(at))
}

/** The cart's facts; the scope and the parent fill their own slots. */
function detailItems(shown: PolycartCart): AtriumDetailsItem[] {
  return [
    { key: 'id', label: t('polycart.cart.cart_id'), value: shown.id, mono: true },
    { key: 'owner', label: t('polycart.cart.owner'), value: polycartOwner(shown) ?? t('polycart.guest') },
    { key: 'source', label: t('polycart.cart.source'), value: shown.source },
    { key: 'expires', label: t('polycart.cart.expires'), value: shown.expires_at ? dated(shown.expires_at) : t('polycart.never') },
    { key: 'updated', label: t('polycart.cart.updated_at'), value: dated(shown.updated_at) },
    { key: 'scope', label: t('polycart.cart.scope') },
    ...(shown.parent_id ? [{ key: 'parent', label: t('polycart.cart.parent') }] : [])
  ]
}

/** What a line buys: `Product #12`, or a custom line's description. */
function item(line: PolycartLine): string {
  if (line.purchasable_type !== null) {
    return `${polycartBasename(line.purchasable_type)} #${line.purchasable_id ?? ''}`
  }

  const description = (line.meta as Record<string, unknown> | null)?.description

  return typeof description === 'string' ? description : ''
}

void Promise.all([client ? loadTypes(client) : null, load()])
</script>

<template>
  <div class="flex flex-col gap-5" data-testid="polycart-cart">
    <AtriumNoAccess v-if="refused" />

    <USkeleton v-else-if="!loaded" class="h-48" />

    <AtriumState v-else-if="missing" icon="i-lucide-search-x" :title="t('polycart.cart.not_found')" data-testid="polycart-not-found">
      <UButton size="sm" variant="outline" icon="i-lucide-arrow-left" to="/polycart/carts" :label="t('polycart.cart.all_carts')" />
    </AtriumState>

    <AtriumState v-else-if="failed || cart === null" kind="error" :title="t('polycart.cart.load_failed')" data-testid="polycart-load-error">
      <UButton size="sm" variant="outline" icon="i-lucide-refresh-cw" :label="t('polycart.retry')" data-testid="polycart-retry" @click="load()" />
    </AtriumState>

    <!-- The loaded cart, as a loop variable so it stays non-null in every slot. -->
    <template v-else>
      <template v-for="shown in shownCart" :key="shown.id">
        <AtriumPageHeader :title="polycartCartTitle(shown)">
          <template #title>
            <span class="truncate" data-testid="polycart-cart-title">{{ polycartCartTitle(shown) }}</span>
          </template>
          <template #description>
            <span class="flex flex-wrap items-center gap-2">
              <UBadge color="primary" variant="subtle" :label="shown.type" />
              <PolycartCartStatus v-if="shown.status" :status="shown.status" :expired="polycartExpired(shown)" />
            </span>
          </template>
          <UButton
            size="sm"
            color="neutral"
            variant="outline"
            icon="i-lucide-archive-x"
            :label="t('polycart.cart.clear')"
            :loading="action.busy.value"
            data-testid="polycart-clear"
            @click="clear"
          />
          <UButton
            size="sm"
            color="error"
            variant="outline"
            icon="i-lucide-trash"
            :label="t('polycart.cart.delete')"
            :loading="action.busy.value"
            data-testid="polycart-delete"
            @click="destroy"
          />
        </AtriumPageHeader>

        <UAlert v-if="notice" color="success" variant="subtle" icon="i-lucide-check" :title="notice" data-testid="polycart-notice" />
        <AtriumNoAccess v-if="action.forbidden.value" compact :description="t('polycart.cart.forbidden')" />
        <UAlert v-if="action.message.value" color="error" variant="subtle" icon="i-lucide-circle-alert" :title="action.message.value" data-testid="polycart-error" />
        <UAlert v-if="unknownType" color="warning" variant="subtle" icon="i-lucide-triangle-alert" :title="t('polycart.cart.unknown_type', { type: shown.type })" data-testid="polycart-unknown-type" />

        <div class="grid gap-4 sm:grid-cols-3">
          <AtriumStat :label="t('polycart.cart.subtotal')" :value="polycartMoney(shown.subtotal)" data-testid="polycart-subtotal" />
          <AtriumStat :label="t('polycart.cart.quantity')" :value="shown.quantity ?? 0" tone="neutral" data-testid="polycart-quantity" />
          <AtriumStat :label="t('polycart.cart.lines')" :value="shown.lines?.length ?? shown.lines_count ?? 0" tone="neutral" data-testid="polycart-lines-count" />
        </div>

        <UCard>
          <AtriumDetails :items="detailItems(shown)" data-testid="polycart-details">
            <template #scope>
              <UBadge v-if="shown.scope_type" color="neutral" variant="subtle" :label="`${polycartBasename(shown.scope_type)} #${shown.scope_id ?? ''}`" />
              <span v-else class="text-muted">{{ t('polycart.cart.unscoped') }}</span>
            </template>
            <template #parent>
              <ULink :to="polycartCartPath(shown.parent_id ?? '')" class="atrium-mono underline-offset-2 hover:underline" data-testid="polycart-parent-link">{{ shown.parent_id }}</ULink>
            </template>
          </AtriumDetails>
        </UCard>

        <UCard v-if="!unknownType && (nextStatuses.length > 0 || conversions.length > 0)" data-testid="polycart-lifecycle">
          <template #header>
            <p class="font-medium">{{ t('polycart.cart.lifecycle') }}</p>
          </template>
          <div class="flex flex-wrap items-end gap-4">
            <UForm v-if="nextStatuses.length > 0" :state="{ status: transitionTo }" class="flex items-end gap-2" data-testid="polycart-transition-form" @submit="transition">
              <UFormField :label="t('polycart.cart.move_to')" name="status" :error="action.error('status')">
                <USelect v-model="transitionTo" :items="nextStatuses" class="w-48" data-testid="polycart-transition-status" />
              </UFormField>
              <UButton type="submit" icon="i-lucide-arrow-right" :label="t('polycart.cart.move')" :loading="action.busy.value" data-testid="polycart-transition" />
            </UForm>

            <UForm v-if="conversions.length > 0" :state="{ to: convertTo, copy: keepOriginal }" class="flex flex-wrap items-end gap-2" data-testid="polycart-convert-form" @submit="convert">
              <UFormField :label="t('polycart.cart.convert_to')" name="to" :error="action.error('to')">
                <USelect v-model="convertTo" :items="conversions" class="w-48" data-testid="polycart-convert-to" />
              </UFormField>
              <UCheckbox v-model="keepOriginal" :label="t('polycart.cart.keep_original')" class="pb-1.5" data-testid="polycart-convert-copy" />
              <UButton type="submit" icon="i-lucide-arrow-right-left" :label="t('polycart.cart.convert')" :loading="action.busy.value" data-testid="polycart-convert" />
            </UForm>
          </div>
        </UCard>

        <UCard>
          <template #header>
            <p class="font-medium">{{ t('polycart.cart.lines') }}</p>
          </template>

          <UTable :data="shown.lines ?? []" :columns="lineColumns" data-testid="polycart-lines">
            <template #empty>
              <UEmpty icon="i-lucide-package-open" :title="t('polycart.cart.no_lines')" size="xs" variant="naked" data-testid="polycart-no-lines" />
            </template>
            <template #item-cell="{ row }">
              <span v-if="row.original.purchasable_type === null" class="flex items-center gap-2">
                <UBadge color="neutral" variant="subtle" :label="t('polycart.cart.custom')" />
                <span>{{ item(row.original) }}</span>
              </span>
              <span v-else class="font-mono">{{ item(row.original) }}</span>
            </template>
            <template #unit_price-cell="{ row }">
              {{ polycartMoney(row.original.unit_price) }}
            </template>
            <template #quantity-cell="{ row }">
              <UForm :state="quantities" class="flex items-center gap-2" data-testid="polycart-line-form" @submit="updateLine(row.original)">
                <UInputNumber
                  v-model="quantities[row.original.id]"
                  :min="0"
                  size="sm"
                  class="w-28"
                  :aria-label="t('polycart.cart.quantity')"
                  data-testid="polycart-line-quantity"
                />
                <UButton type="submit" size="xs" variant="ghost" icon="i-lucide-check" :label="t('polycart.cart.update')" data-testid="polycart-line-update" />
              </UForm>
              <p v-if="editedLine === row.original.id && action.error('lines')" class="mt-1 text-xs text-error">{{ action.error('lines') }}</p>
            </template>
            <template #total-cell="{ row }">
              {{ polycartMoney(row.original.total) }}
            </template>
            <template #remove-cell="{ row }">
              <UButton size="xs" color="neutral" variant="ghost" icon="i-lucide-trash" :label="t('polycart.cart.remove')" data-testid="polycart-line-remove" @click="removeLine(row.original)" />
            </template>
          </UTable>

          <p v-if="(shown.lines ?? []).length > 0 && shown.fully_priced === false" class="mt-3 text-sm text-warning" data-testid="polycart-unpriced">{{ t('polycart.cart.unpriced_lines') }}</p>
        </UCard>

        <UCard>
          <template #header>
            <p class="font-medium">{{ t('polycart.cart.members') }}</p>
          </template>

          <div class="flex flex-col gap-4">
            <UForm :state="{ visibility }" class="flex items-end gap-2" data-testid="polycart-visibility-form" @submit="setVisibility">
              <UFormField :label="t('polycart.cart.visible_to')" name="visibility" :error="action.error('visibility')">
                <USelect v-model="visibility" :items="visibilityItems" class="w-72" data-testid="polycart-visibility" />
              </UFormField>
              <UButton type="submit" variant="outline" icon="i-lucide-check" :label="t('polycart.cart.save')" data-testid="polycart-set-visibility" />
            </UForm>

            <p v-if="(shown.members ?? []).length === 0" class="text-sm text-muted">{{ t('polycart.cart.no_members') }}</p>

            <ul v-else class="flex flex-col gap-2">
              <li v-for="member in shown.members" :key="member.id" class="flex items-center justify-between gap-2 text-sm" data-testid="polycart-member">
                <span class="flex items-center gap-2">
                  <span class="font-mono">{{ polycartBasename(member.member_type) }} #{{ member.member_id }}</span>
                  <UBadge color="primary" variant="subtle" :label="member.role" />
                </span>
                <UButton size="xs" color="neutral" variant="ghost" icon="i-lucide-user-minus" :label="t('polycart.cart.remove')" data-testid="polycart-member-remove" @click="unshare(member.id)" />
              </li>
            </ul>

            <UForm v-if="shown.scope_type !== null" :state="share" class="flex flex-wrap items-end gap-2" data-testid="polycart-share-form" @submit="shareCart">
              <UFormField :label="t('polycart.cart.member_type')" name="member_type" :hint="t('polycart.cart.member_type_hint')" :error="action.error('member_type')">
                <UInput v-model="share.member_type" class="w-40" data-testid="polycart-share-type" />
              </UFormField>
              <UFormField :label="t('polycart.cart.member_id')" name="member_id" :error="action.error('member_id')">
                <UInput v-model="share.member_id" class="w-32" data-testid="polycart-share-id" />
              </UFormField>
              <UFormField :label="t('polycart.cart.role')" name="role" :error="action.error('role')">
                <UInput v-model="share.role" class="w-40" data-testid="polycart-share-role" />
              </UFormField>
              <UButton type="submit" icon="i-lucide-user-plus" :label="t('polycart.cart.share')" :loading="action.busy.value" data-testid="polycart-share" />
            </UForm>
          </div>
        </UCard>

        <UCard v-if="children.length > 0">
          <template #header>
            <p class="font-medium">{{ t('polycart.cart.children') }}</p>
          </template>
          <ul class="flex flex-col gap-2">
            <li v-for="child in children" :key="child.id" class="flex items-center gap-2 text-sm" data-testid="polycart-child">
              <UBadge color="primary" variant="subtle" :label="child.type" />
              <ULink :to="polycartCartPath(child.id)" class="underline-offset-2 hover:underline">{{ polycartCartTitle(child) }}</ULink>
            </li>
          </ul>
        </UCard>

        <UCard data-testid="polycart-details">
          <template #header>
            <p class="font-medium">{{ t('polycart.cart.details') }}</p>
          </template>
          <UForm :state="details" class="flex flex-col gap-3" data-testid="polycart-details-form" @submit="updateDetails">
            <UFormField :label="t('polycart.cart.label')" name="label" :error="action.error('label')">
              <UInput v-model="details.label" class="w-full" data-testid="polycart-details-label" />
            </UFormField>
            <UFormField :label="t('polycart.cart.meta')" name="meta" :error="metaError ?? action.error('meta')">
              <UTextarea v-model="details.meta" :rows="6" class="w-full font-mono" data-testid="polycart-details-meta" />
            </UFormField>
            <div>
              <UButton type="submit" icon="i-lucide-check" :label="t('polycart.cart.save')" :loading="action.busy.value" data-testid="polycart-update" />
            </div>
          </UForm>
        </UCard>
      </template>
    </template>
  </div>
</template>
