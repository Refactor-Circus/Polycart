<script setup lang="ts">
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

    <div v-else-if="missing" class="atrium-empty items-start text-start" data-testid="polycart-not-found">
      <p>{{ t('polycart.cart.not_found') }}</p>
      <UButton size="sm" variant="outline" icon="i-lucide-arrow-left" to="/polycart/carts" :label="t('polycart.cart.all_carts')" />
    </div>

    <div v-else-if="failed || cart === null" class="flex items-center justify-between gap-3 rounded-md border border-default p-4 text-sm" role="alert" data-testid="polycart-load-error">
      <span>{{ t('polycart.cart.load_failed') }}</span>
      <UButton size="sm" variant="outline" icon="i-lucide-refresh-cw" :label="t('polycart.retry')" data-testid="polycart-retry" @click="load()" />
    </div>

    <!-- The loaded cart, as a loop variable so it stays non-null in every slot. -->
    <template v-else>
      <template v-for="shown in shownCart" :key="shown.id">
        <div class="flex flex-wrap items-center justify-between gap-3">
          <div class="flex min-w-0 items-center gap-2">
            <h2 class="truncate text-lg font-semibold" data-testid="polycart-cart-title">{{ polycartCartTitle(shown) }}</h2>
            <UBadge color="primary" variant="subtle" :label="shown.type" />
            <PolycartCartStatus v-if="shown.status" :status="shown.status" :expired="polycartExpired(shown)" />
          </div>
          <div class="flex gap-2">
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
          </div>
        </div>

        <UAlert v-if="notice" color="success" variant="subtle" icon="i-lucide-check" :title="notice" data-testid="polycart-notice" />
        <AtriumNoAccess v-if="action.forbidden.value" compact :description="t('polycart.cart.forbidden')" />
        <UAlert v-if="action.message.value" color="error" variant="subtle" icon="i-lucide-circle-alert" :title="action.message.value" data-testid="polycart-error" />
        <UAlert v-if="unknownType" color="warning" variant="subtle" icon="i-lucide-triangle-alert" :title="t('polycart.cart.unknown_type', { type: shown.type })" data-testid="polycart-unknown-type" />

        <div class="grid gap-4 sm:grid-cols-3">
          <div class="rounded-md border border-default p-4" data-testid="polycart-subtotal">
            <p class="text-xs text-muted">{{ t('polycart.cart.subtotal') }}</p>
            <p class="text-lg font-semibold tabular-nums">{{ polycartMoney(shown.subtotal) }}</p>
          </div>
          <div class="rounded-md border border-default p-4" data-testid="polycart-quantity">
            <p class="text-xs text-muted">{{ t('polycart.cart.quantity') }}</p>
            <p class="text-lg font-semibold tabular-nums">{{ shown.quantity ?? 0 }}</p>
          </div>
          <div class="rounded-md border border-default p-4" data-testid="polycart-lines-count">
            <p class="text-xs text-muted">{{ t('polycart.cart.lines') }}</p>
            <p class="text-lg font-semibold tabular-nums">{{ shown.lines?.length ?? shown.lines_count ?? 0 }}</p>
          </div>
        </div>

        <UCard>
          <dl class="grid gap-x-6 gap-y-3 text-sm sm:grid-cols-[auto_1fr]">
            <dt class="text-muted">{{ t('polycart.cart.cart_id') }}</dt>
            <dd class="font-mono">{{ shown.id }}</dd>
            <dt class="text-muted">{{ t('polycart.cart.owner') }}</dt>
            <dd>{{ polycartOwner(shown) ?? t('polycart.guest') }}</dd>
            <dt class="text-muted">{{ t('polycart.cart.source') }}</dt>
            <dd>{{ shown.source ?? '—' }}</dd>
            <dt class="text-muted">{{ t('polycart.cart.expires') }}</dt>
            <dd>{{ shown.expires_at ? dated(shown.expires_at) : t('polycart.never') }}</dd>
            <dt class="text-muted">{{ t('polycart.cart.updated_at') }}</dt>
            <dd>{{ dated(shown.updated_at) }}</dd>
            <dt class="text-muted">{{ t('polycart.cart.scope') }}</dt>
            <dd>
              <UBadge v-if="shown.scope_type" color="neutral" variant="subtle" :label="`${polycartBasename(shown.scope_type)} #${shown.scope_id ?? ''}`" />
              <span v-else class="text-muted">{{ t('polycart.cart.unscoped') }}</span>
            </dd>
            <template v-if="shown.parent_id">
              <dt class="text-muted">{{ t('polycart.cart.parent') }}</dt>
              <dd>
                <RouterLink :to="polycartCartPath(shown.parent_id)" class="font-mono underline-offset-2 hover:underline" data-testid="polycart-parent-link">{{ shown.parent_id }}</RouterLink>
              </dd>
            </template>
          </dl>
        </UCard>

        <UCard v-if="!unknownType && (nextStatuses.length > 0 || conversions.length > 0)" data-testid="polycart-lifecycle">
          <template #header>
            <p class="font-medium">{{ t('polycart.cart.lifecycle') }}</p>
          </template>
          <div class="flex flex-wrap items-end gap-4">
            <form v-if="nextStatuses.length > 0" class="flex items-end gap-2" data-testid="polycart-transition-form" @submit.prevent="transition">
              <UFormField :label="t('polycart.cart.move_to')" :error="action.error('status')">
                <select v-model="transitionTo" class="w-48 rounded-md border border-default bg-default px-2.5 py-1.5 text-sm" data-testid="polycart-transition-status">
                  <option v-for="status in nextStatuses" :key="status" :value="status">{{ status }}</option>
                </select>
              </UFormField>
              <UButton type="submit" icon="i-lucide-arrow-right" :label="t('polycart.cart.move')" :loading="action.busy.value" data-testid="polycart-transition" />
            </form>

            <form v-if="conversions.length > 0" class="flex flex-wrap items-end gap-2" data-testid="polycart-convert-form" @submit.prevent="convert">
              <UFormField :label="t('polycart.cart.convert_to')" :error="action.error('to')">
                <select v-model="convertTo" class="w-48 rounded-md border border-default bg-default px-2.5 py-1.5 text-sm" data-testid="polycart-convert-to">
                  <option v-for="to in conversions" :key="to" :value="to">{{ to }}</option>
                </select>
              </UFormField>
              <label class="flex items-center gap-2 pb-1.5 text-sm">
                <input v-model="keepOriginal" type="checkbox" data-testid="polycart-convert-copy">
                {{ t('polycart.cart.keep_original') }}
              </label>
              <UButton type="submit" icon="i-lucide-arrow-right-left" :label="t('polycart.cart.convert')" :loading="action.busy.value" data-testid="polycart-convert" />
            </form>
          </div>
        </UCard>

        <UCard>
          <template #header>
            <p class="font-medium">{{ t('polycart.cart.lines') }}</p>
          </template>

          <p v-if="(shown.lines ?? []).length === 0" class="text-sm text-muted" data-testid="polycart-no-lines">{{ t('polycart.cart.no_lines') }}</p>

          <template v-else>
            <div class="overflow-x-auto">
              <table class="w-full text-left text-sm">
                <thead class="text-muted">
                  <tr>
                    <th class="px-3 py-2 font-medium">{{ t('polycart.cart.item') }}</th>
                    <th class="px-3 py-2 text-right font-medium">{{ t('polycart.cart.unit_price') }}</th>
                    <th class="px-3 py-2 font-medium">{{ t('polycart.cart.quantity') }}</th>
                    <th class="px-3 py-2 text-right font-medium">{{ t('polycart.cart.total') }}</th>
                    <th class="px-3 py-2"><span class="sr-only">{{ t('polycart.cart.remove') }}</span></th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-default">
                  <tr v-for="line in shown.lines" :key="line.id" data-testid="polycart-line">
                    <td class="px-3 py-2">
                      <span v-if="line.purchasable_type === null" class="flex items-center gap-2">
                        <UBadge color="neutral" variant="subtle" :label="t('polycart.cart.custom')" />
                        <span>{{ item(line) }}</span>
                      </span>
                      <span v-else class="font-mono">{{ item(line) }}</span>
                    </td>
                    <td class="px-3 py-2 text-right tabular-nums">{{ polycartMoney(line.unit_price) }}</td>
                    <td class="px-3 py-2">
                      <form class="flex items-center gap-2" data-testid="polycart-line-form" @submit.prevent="updateLine(line)">
                        <input
                          v-model.number="quantities[line.id]"
                          type="number"
                          min="0"
                          class="w-20 rounded-md border border-default bg-default px-2 py-1 text-sm"
                          :aria-label="t('polycart.cart.quantity')"
                          data-testid="polycart-line-quantity"
                        >
                        <UButton type="submit" size="xs" variant="ghost" icon="i-lucide-check" :label="t('polycart.cart.update')" data-testid="polycart-line-update" />
                      </form>
                      <p v-if="editedLine === line.id && action.error('lines')" class="mt-1 text-xs text-error">{{ action.error('lines') }}</p>
                    </td>
                    <td class="px-3 py-2 text-right tabular-nums">{{ polycartMoney(line.total) }}</td>
                    <td class="px-3 py-2 text-right">
                      <UButton size="xs" color="neutral" variant="ghost" icon="i-lucide-trash" :label="t('polycart.cart.remove')" data-testid="polycart-line-remove" @click="removeLine(line)" />
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <p v-if="shown.fully_priced === false" class="mt-3 text-sm text-warning" data-testid="polycart-unpriced">{{ t('polycart.cart.unpriced_lines') }}</p>
          </template>
        </UCard>

        <UCard>
          <template #header>
            <p class="font-medium">{{ t('polycart.cart.members') }}</p>
          </template>

          <div class="flex flex-col gap-4">
            <form class="flex items-end gap-2" data-testid="polycart-visibility-form" @submit.prevent="setVisibility">
              <UFormField :label="t('polycart.cart.visible_to')" :error="action.error('visibility')">
                <select v-model="visibility" class="w-72 rounded-md border border-default bg-default px-2.5 py-1.5 text-sm" data-testid="polycart-visibility">
                  <option v-for="option in POLYCART_VISIBILITIES" :key="option" :value="option">{{ t(`polycart.visibility.${option}`) }}</option>
                </select>
              </UFormField>
              <UButton type="submit" variant="outline" icon="i-lucide-check" :label="t('polycart.cart.save')" data-testid="polycart-set-visibility" />
            </form>

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

            <form v-if="shown.scope_type !== null" class="flex flex-wrap items-end gap-2" data-testid="polycart-share-form" @submit.prevent="shareCart">
              <UFormField :label="t('polycart.cart.member_type')" :hint="t('polycart.cart.member_type_hint')" :error="action.error('member_type')">
                <UInput v-model="share.member_type" class="w-40" data-testid="polycart-share-type" />
              </UFormField>
              <UFormField :label="t('polycart.cart.member_id')" :error="action.error('member_id')">
                <UInput v-model="share.member_id" class="w-32" data-testid="polycart-share-id" />
              </UFormField>
              <UFormField :label="t('polycart.cart.role')" :error="action.error('role')">
                <UInput v-model="share.role" class="w-40" data-testid="polycart-share-role" />
              </UFormField>
              <UButton type="submit" icon="i-lucide-user-plus" :label="t('polycart.cart.share')" :loading="action.busy.value" data-testid="polycart-share" />
            </form>
          </div>
        </UCard>

        <UCard v-if="children.length > 0">
          <template #header>
            <p class="font-medium">{{ t('polycart.cart.children') }}</p>
          </template>
          <ul class="flex flex-col gap-2">
            <li v-for="child in children" :key="child.id" class="flex items-center gap-2 text-sm" data-testid="polycart-child">
              <UBadge color="primary" variant="subtle" :label="child.type" />
              <RouterLink :to="polycartCartPath(child.id)" class="underline-offset-2 hover:underline">{{ polycartCartTitle(child) }}</RouterLink>
            </li>
          </ul>
        </UCard>

        <UCard data-testid="polycart-details">
          <template #header>
            <p class="font-medium">{{ t('polycart.cart.details') }}</p>
          </template>
          <form class="flex flex-col gap-3" data-testid="polycart-details-form" @submit.prevent="updateDetails">
            <UFormField :label="t('polycart.cart.label')" :error="action.error('label')">
              <UInput v-model="details.label" class="w-full" data-testid="polycart-details-label" />
            </UFormField>
            <UFormField :label="t('polycart.cart.meta')" :error="metaError ?? action.error('meta')">
              <UTextarea v-model="details.meta" :rows="6" class="w-full font-mono" data-testid="polycart-details-meta" />
            </UFormField>
            <div>
              <UButton type="submit" icon="i-lucide-check" :label="t('polycart.cart.save')" :loading="action.busy.value" data-testid="polycart-update" />
            </div>
          </form>
        </UCard>
      </template>
    </template>
  </div>
</template>
