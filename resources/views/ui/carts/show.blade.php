@use(RefactorCircus\Polycart\Atrium\Format)
@use(RefactorCircus\Polycart\Domains\Sharing\Models\CartMemberModel)

{{-- The `polycart.cart` screen: header actions always render after the
     cart's own; an open registered tab's panel replaces the cart, and
     `before`, `aside` (a side column) and `after` render only around it. --}}
@php
    $tabOpen = $resolved->openRegisteredTab !== null;
    $aside = ! $tabOpen && $resolved->sections('aside') !== [];
@endphp

<x-atrium::layout :title="$cart->label ?? $cart->id">
    <x-atrium::page-header :title="$cart->label ?? $cart->id">
        <x-slot:actions>
            <x-atrium::badge variant="primary">{{ $cart->type }}</x-atrium::badge>

            @if ($cart->status)
                @include('polycart::ui.partials.cart-status', ['cart' => $cart])
            @endif

            @polycartCan('update', $cart)
                <form method="POST" action="{{ route('atrium.polycart.carts.clear', $cart) }}">
                    @csrf
                    <x-atrium::icon-button icon="archive-box-x-mark" :label="__('polycart::polycart.clear')" variant="outline" type="submit" data-testid="clear-cart" />
                </form>
            @endpolycartCan

            @polycartCan('delete', $cart)
                <form method="POST" action="{{ route('atrium.polycart.carts.destroy', $cart) }}">
                    @csrf
                    @method('DELETE')
                    <x-atrium::icon-button icon="trash" :label="__('polycart::polycart.delete')" variant="danger" type="submit" data-testid="delete-cart" />
                </form>
            @endpolycartCan

            <x-atrium::screen-sections :screen="$resolved" slot="header.actions" />
        </x-slot:actions>
    </x-atrium::page-header>

    <x-atrium::screen-tabs :screen="$resolved" class="mt-4" />

    @if ($tabOpen)
        <x-atrium::screen-tab-panel :screen="$resolved" class="mt-5" />
    @else
        <div class="mt-5 flex flex-col gap-5">
            <x-atrium::flash />

            <x-atrium::screen-sections :screen="$resolved" slot="before" />

            <div @class(['grid gap-6 lg:grid-cols-3' => $aside])>
                <div @class(['flex flex-col gap-5', 'lg:col-span-2' => $aside])>
                    @if (! $type)
                        <x-atrium::alert variant="warning">{{ __('polycart::polycart.unknown_type', ['type' => $cart->type]) }}</x-atrium::alert>
                    @endif

                    <div class="grid gap-4 sm:grid-cols-3">
                        <x-atrium::stat :label="__('polycart::polycart.subtotal')" :value="Format::money($cart->subtotal())" />
                        <x-atrium::stat :label="__('polycart::polycart.quantity')" :value="$cart->quantity()" />
                        <x-atrium::stat :label="__('polycart::polycart.lines')" :value="$cart->lines->count()" />
                    </div>

                    <x-atrium::card>
                        <x-atrium::description-list>
                            <x-atrium::description-list.item :term="__('polycart::polycart.cart_id')" class="font-mono">{{ $cart->id }}</x-atrium::description-list.item>
                            <x-atrium::description-list.item :term="__('polycart::polycart.owner')">{{ Format::owner($cart) }}</x-atrium::description-list.item>
                            <x-atrium::description-list.item :term="__('polycart::polycart.source')">{{ $cart->source ?? '—' }}</x-atrium::description-list.item>
                            <x-atrium::description-list.item :term="__('polycart::polycart.expires')">{{ $cart->expires_at?->diffForHumans() ?? __('polycart::polycart.never') }}</x-atrium::description-list.item>
                            <x-atrium::description-list.item :term="__('polycart::polycart.updated')">{{ $cart->updated_at?->diffForHumans() }}</x-atrium::description-list.item>
                            <x-atrium::description-list.item :term="__('polycart::polycart.scope')">
                                @forelse ($cart->paths as $path)
                                    <x-atrium::badge>{{ class_basename($path->scope_type) }} #{{ $path->scope_id }}</x-atrium::badge>
                                    @unless ($loop->last) <span class="opacity-60">›</span> @endunless
                                @empty
                                    <span class="opacity-60">{{ __('polycart::polycart.unscoped') }}</span>
                                @endforelse
                            </x-atrium::description-list.item>

                            @if ($cart->parent)
                                <x-atrium::description-list.item :term="__('polycart::polycart.parent')">
                                    <a class="underline-offset-2 hover:underline" href="{{ route('atrium.polycart.carts.show', $cart->parent) }}">
                                        {{ $cart->parent->label ?? $cart->parent->id }} ({{ $cart->parent->type }})
                                    </a>
                                </x-atrium::description-list.item>
                            @endif
                        </x-atrium::description-list>
                    </x-atrium::card>

                    @if ($nextStatuses !== [] || $conversions !== [])
                        <x-atrium::card :title="__('polycart::polycart.lifecycle')" data-testid="lifecycle-card">
                            <div class="flex flex-wrap items-end gap-6">
                                {{-- Only the moves the type allows from here, and the viewer may make, are offered. --}}
                                @if ($nextStatuses !== [])
                                    <form method="POST" action="{{ route('atrium.polycart.carts.transition', $cart) }}" class="flex items-end gap-2">
                                        @csrf
                                        <x-atrium::form.select
                                            name="status"
                                            :label="__('polycart::polycart.move_to')"
                                            :options="collect($nextStatuses)->mapWithKeys(fn ($status) => [$status->value => $status->value])"
                                            wrapper="w-48" />
                                        <x-atrium::icon-button icon="arrow-right" :label="__('polycart::polycart.move')" variant="primary" type="submit" data-testid="transition-cart" />
                                    </form>
                                @endif

                                @if ($conversions !== [])
                                    <form method="POST" action="{{ route('atrium.polycart.carts.convert', $cart) }}" class="flex items-end gap-2">
                                        @csrf
                                        <x-atrium::form.select
                                            name="to"
                                            :label="__('polycart::polycart.convert_to')"
                                            :options="collect($conversions)->mapWithKeys(fn ($to) => [$to => $to])"
                                            wrapper="w-48" />
                                        <x-atrium::form.checkbox name="copy" :label="__('polycart::polycart.keep_original')" checked wrapper="w-fit shrink-0" />
                                        <x-atrium::icon-button icon="arrows-right-left" :label="__('polycart::polycart.convert')" variant="primary" type="submit" data-testid="convert-cart" />
                                    </form>
                                @endif
                            </div>
                        </x-atrium::card>
                    @endif

                    <x-atrium::card :title="__('polycart::polycart.lines')">
                        @if ($cart->lines->isEmpty())
                            <x-atrium::empty-state :title="__('polycart::polycart.no_lines')" />
                        @else
                            <x-atrium::table>
                                <x-slot:head>
                                    <x-atrium::table.row>
                                        <x-atrium::table.cell heading>{{ __('polycart::polycart.item') }}</x-atrium::table.cell>
                                        <x-atrium::table.cell heading>{{ __('polycart::polycart.options') }}</x-atrium::table.cell>
                                        <x-atrium::table.cell heading numeric>{{ __('polycart::polycart.unit_price') }}</x-atrium::table.cell>
                                        <x-atrium::table.cell heading>{{ __('polycart::polycart.quantity') }}</x-atrium::table.cell>
                                        <x-atrium::table.cell heading numeric>{{ __('polycart::polycart.total') }}</x-atrium::table.cell>
                                        <x-atrium::table.cell heading />
                                    </x-atrium::table.row>
                                </x-slot:head>

                                @foreach ($cart->lines as $line)
                                    <x-atrium::table.row>
                                        <x-atrium::table.cell>
                                            @if ($line->isCustom())
                                                <x-atrium::badge>{{ __('polycart::polycart.custom') }}</x-atrium::badge>
                                                <span class="text-sm">{{ $line->meta['description'] ?? '' }}</span>
                                            @else
                                                <span class="font-mono text-sm">{{ class_basename($line->purchasable_type) }} #{{ $line->purchasable_id }}</span>
                                            @endif
                                        </x-atrium::table.cell>
                                        <x-atrium::table.cell>
                                            @forelse ($line->options as $key => $value)
                                                <x-atrium::badge>{{ $key }}={{ is_scalar($value) ? $value : json_encode($value) }}</x-atrium::badge>
                                            @empty
                                                <span class="opacity-60">—</span>
                                            @endforelse
                                        </x-atrium::table.cell>
                                        <x-atrium::table.cell numeric>{{ Format::money($line->unit_price) }}</x-atrium::table.cell>
                                        <x-atrium::table.cell>
                                            @polycartCan('update', $line)
                                                <form method="POST" action="{{ route('atrium.polycart.carts.lines.update', [$cart, $line]) }}" class="flex items-center gap-2">
                                                    @csrf
                                                    @method('PATCH')
                                                    <x-atrium::form.input name="quantity" type="number" min="0" :value="$line->quantity" wrapper="w-20" />
                                                    <x-atrium::icon-button icon="check" :label="__('polycart::polycart.update')" size="sm" variant="ghost" type="submit" data-testid="update-line" />
                                                </form>
                                            @else
                                                <span class="tabular-nums">{{ $line->quantity }}</span>
                                            @endpolycartCan
                                        </x-atrium::table.cell>
                                        <x-atrium::table.cell numeric>{{ Format::money($line->total()) }}</x-atrium::table.cell>
                                        <x-atrium::table.cell>
                                            @polycartCan('delete', $line)
                                                <form method="POST" action="{{ route('atrium.polycart.carts.lines.destroy', [$cart, $line]) }}">
                                                    @csrf
                                                    @method('DELETE')
                                                    <x-atrium::icon-button icon="trash" :label="__('polycart::polycart.remove')" size="sm" variant="ghost" type="submit" data-testid="remove-line" />
                                                </form>
                                            @endpolycartCan
                                        </x-atrium::table.cell>
                                    </x-atrium::table.row>
                                @endforeach
                            </x-atrium::table>

                            @unless ($cart->isFullyPriced())
                                <p class="mt-3 text-sm text-warning">{{ __('polycart::polycart.unpriced_lines') }}</p>
                            @endunless
                        @endif
                    </x-atrium::card>

                    <x-atrium::card :title="__('polycart::polycart.members')">
                        <div class="flex flex-col gap-4">
                            @polycartCan('share', $cart)
                                <form method="POST" action="{{ route('atrium.polycart.carts.visibility', $cart) }}" class="flex items-end gap-2">
                                    @csrf
                                    @method('PUT')
                                    <x-atrium::form.select
                                        name="visibility"
                                        :label="__('polycart::polycart.visibility')"
                                        :options="collect($visibilities)->mapWithKeys(fn ($visibility) => [$visibility->value => __('polycart::polycart.visibility_'.$visibility->value)])"
                                        :selected="$cart->visibility?->value"
                                        wrapper="w-72" />
                                    <x-atrium::icon-button icon="check" :label="__('polycart::polycart.save')" variant="outline" type="submit" data-testid="set-visibility" />
                                </form>
                            @endpolycartCan

                            @if ($cart->members->isEmpty())
                                <x-atrium::empty-state :title="__('polycart::polycart.no_members')" />
                            @else
                                <ul class="flex flex-col gap-2">
                                    @foreach ($cart->members as $member)
                                        <li class="flex items-center justify-between gap-2 text-sm">
                                            <span class="flex items-center gap-2">
                                                <span class="font-mono">{{ class_basename($member->member_type) }} #{{ $member->member_id }}</span>
                                                <x-atrium::badge variant="primary">{{ $member->role }}</x-atrium::badge>
                                            </span>
                                            @polycartCan('delete', $member)
                                                <form method="POST" action="{{ route('atrium.polycart.carts.members.destroy', [$cart, $member]) }}">
                                                    @csrf
                                                    @method('DELETE')
                                                    <x-atrium::icon-button icon="user-minus" :label="__('polycart::polycart.remove')" size="sm" variant="ghost" type="submit" data-testid="remove-member" />
                                                </form>
                                            @endpolycartCan
                                        </li>
                                    @endforeach
                                </ul>
                            @endif

                            @polycartCan('create', CartMemberModel::class, [$cart])
                            @if ($cart->isScoped() && $type?->shareable() && $memberTypes !== [])
                                <form method="POST" action="{{ route('atrium.polycart.carts.members.store', $cart) }}" class="flex flex-wrap items-end gap-2">
                                    @csrf
                                    <x-atrium::form.select
                                        name="member_type"
                                        :label="__('polycart::polycart.member_type')"
                                        :options="collect($memberTypes)->mapWithKeys(fn ($alias) => [$alias => $alias])"
                                        wrapper="w-40" />
                                    <x-atrium::form.input name="member_id" :label="__('polycart::polycart.member_id')" required wrapper="w-32" />
                                    <x-atrium::form.select
                                        name="role"
                                        :label="__('polycart::polycart.role')"
                                        :options="collect($roles)->mapWithKeys(fn ($role) => [$role => $role])"
                                        wrapper="w-40" />
                                    <x-atrium::icon-button icon="user-plus" :label="__('polycart::polycart.share')" variant="primary" type="submit" data-testid="share-cart" />
                                </form>
                            @endif
                            @endpolycartCan
                        </div>
                    </x-atrium::card>

                    @if ($cart->children->isNotEmpty())
                        <x-atrium::card :title="__('polycart::polycart.children')">
                            <ul class="flex flex-col gap-2">
                                @foreach ($cart->children as $child)
                                    <li class="flex items-center gap-2 text-sm">
                                        <x-atrium::badge variant="primary">{{ $child->type }}</x-atrium::badge>
                                        <a class="underline-offset-2 hover:underline" href="{{ route('atrium.polycart.carts.show', $child) }}">{{ $child->label ?? $child->id }}</a>
                                    </li>
                                @endforeach
                            </ul>
                        </x-atrium::card>
                    @endif

                    @polycartCan('update', $cart)
                    <x-atrium::card :title="__('polycart::polycart.details')" data-testid="details-card">
                        <form method="POST" action="{{ route('atrium.polycart.carts.update', $cart) }}" class="flex flex-col gap-3">
                            @csrf
                            @method('PATCH')
                            <x-atrium::form.input name="label" :label="__('polycart::polycart.label')" :value="$cart->label" />
                            <x-atrium::form.textarea name="meta" :label="__('polycart::polycart.meta')" :value="json_encode($cart->meta ?? (object) [], JSON_PRETTY_PRINT)" rows="6" class="font-mono" />
                            <div>
                                <x-atrium::icon-button icon="check" :label="__('polycart::polycart.save')" variant="primary" type="submit" data-testid="update-cart" />
                            </div>
                        </form>
                    </x-atrium::card>
                    @endpolycartCan
                </div>

                @if ($aside)
                    <aside class="flex flex-col gap-4" data-testid="screen-aside">
                        <x-atrium::screen-sections :screen="$resolved" slot="aside" />
                    </aside>
                @endif
            </div>

            <x-atrium::screen-sections :screen="$resolved" slot="after" />
        </div>
    @endif
</x-atrium::layout>
