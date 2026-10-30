@use(RefactorCircus\Polycart\Atrium\Format)

{{-- The `polycart.carts` screen: header actions always render; an open
     registered tab's panel replaces the list, and `before`, `after` and each
     cart row's `row.actions` render only around it. A row is the cart, so row
     sections receive it as `$record`; their column appears only when some
     row has a section to show. --}}
@php
    $tabOpen = $resolved->openRegisteredTab !== null;
    $rowActions = ! $tabOpen && collect($carts->items())->contains(fn ($cart) => $resolved->rowSections('row.actions', $cart) !== []);
@endphp

<x-atrium::layout :title="__('polycart::polycart.carts')">
    <x-atrium::page-header :title="__('polycart::polycart.carts')">
        @if ($resolved->sections('header.actions') !== [])
            <x-slot:actions>
                <x-atrium::screen-sections :screen="$resolved" slot="header.actions" />
            </x-slot:actions>
        @endif
    </x-atrium::page-header>

    <x-atrium::screen-tabs :screen="$resolved" class="mt-4" />

    @if ($tabOpen)
        <x-atrium::screen-tab-panel :screen="$resolved" class="mt-5" />
    @else
        <div class="mt-5 flex flex-col gap-4">
            <x-atrium::flash />

            <x-atrium::screen-sections :screen="$resolved" slot="before" />

            {{-- Type options come from the registry, so the filter can never offer a
                 type the API would not recognise. --}}
            <x-atrium::card>
                <form method="GET" action="{{ route('atrium.polycart.carts.index') }}" class="flex flex-wrap items-end gap-3">
                    <x-atrium::form.select
                        name="type"
                        :label="__('polycart::polycart.type')"
                        :placeholder="__('polycart::polycart.all_types')"
                        :options="collect($types)->mapWithKeys(fn ($type) => [$type => $type])"
                        :selected="$filters['type'] ?? null"
                        wrapper="w-48" />

                    <x-atrium::form.input name="status" :label="__('polycart::polycart.status')" :value="$filters['status'] ?? null" wrapper="w-40" />
                    <x-atrium::form.input name="source" :label="__('polycart::polycart.source')" :value="$filters['source'] ?? null" wrapper="w-32" />
                    <x-atrium::form.input name="search" :label="__('polycart::polycart.search')" :value="$filters['search'] ?? null" wrapper="w-56" />

                    <x-atrium::icon-button icon="funnel" :label="__('polycart::polycart.filter')" variant="primary" type="submit" data-testid="filter-carts" />
                    <x-atrium::icon-button icon="x-mark" :label="__('polycart::polycart.clear_filters')" variant="ghost" :href="route('atrium.polycart.carts.index')" data-testid="reset-filters" />
                </form>
            </x-atrium::card>

            @if ($carts->isEmpty())
                <x-atrium::empty-state :title="__('polycart::polycart.no_carts')" />
            @else
                <x-atrium::table striped>
                    <x-slot:head>
                        <x-atrium::table.row>
                            <x-atrium::table.cell heading>{{ __('polycart::polycart.cart') }}</x-atrium::table.cell>
                            <x-atrium::table.cell heading>{{ __('polycart::polycart.type') }}</x-atrium::table.cell>
                            <x-atrium::table.cell heading>{{ __('polycart::polycart.status') }}</x-atrium::table.cell>
                            <x-atrium::table.cell heading>{{ __('polycart::polycart.owner') }}</x-atrium::table.cell>
                            <x-atrium::table.cell heading>{{ __('polycart::polycart.source') }}</x-atrium::table.cell>
                            <x-atrium::table.cell heading numeric>{{ __('polycart::polycart.lines') }}</x-atrium::table.cell>
                            <x-atrium::table.cell heading>{{ __('polycart::polycart.updated') }}</x-atrium::table.cell>
                            <x-atrium::table.cell heading>{{ __('polycart::polycart.expires') }}</x-atrium::table.cell>
                            @if ($rowActions)
                                <x-atrium::table.cell heading data-testid="row-actions-heading" />
                            @endif
                        </x-atrium::table.row>
                    </x-slot:head>

                    @foreach ($carts as $cart)
                        <x-atrium::table.row>
                            <x-atrium::table.cell>
                                <a class="font-medium underline-offset-2 hover:underline"
                                   href="{{ route('atrium.polycart.carts.show', $cart) }}">{{ $cart->label ?? $cart->id }}</a>
                            </x-atrium::table.cell>
                            <x-atrium::table.cell><x-atrium::badge variant="primary">{{ $cart->type }}</x-atrium::badge></x-atrium::table.cell>
                            <x-atrium::table.cell>
                                @if ($cart->status)
                                    @include('polycart::ui.partials.cart-status', ['cart' => $cart])
                                @else
                                    <span class="opacity-60">—</span>
                                @endif
                            </x-atrium::table.cell>
                            <x-atrium::table.cell>{{ Format::owner($cart) }}</x-atrium::table.cell>
                            <x-atrium::table.cell><x-atrium::badge>{{ $cart->source ?? '—' }}</x-atrium::badge></x-atrium::table.cell>
                            <x-atrium::table.cell numeric>{{ $cart->lines_count }}</x-atrium::table.cell>
                            <x-atrium::table.cell>{{ $cart->updated_at?->diffForHumans() }}</x-atrium::table.cell>
                            <x-atrium::table.cell>
                                @if ($cart->expires_at)
                                    <span @class(['text-warning' => $cart->isExpired()])>{{ $cart->expires_at->diffForHumans() }}</span>
                                @else
                                    <span class="opacity-60">{{ __('polycart::polycart.never') }}</span>
                                @endif
                            </x-atrium::table.cell>
                            @if ($rowActions)
                                <x-atrium::table.cell data-testid="row-actions">
                                    <x-atrium::screen-sections :screen="$resolved" slot="row.actions" :row="$cart" />
                                </x-atrium::table.cell>
                            @endif
                        </x-atrium::table.row>
                    @endforeach
                </x-atrium::table>

                <x-atrium::pagination :paginator="$carts" />
            @endif

            <x-atrium::screen-sections :screen="$resolved" slot="after" />
        </div>
    @endif
</x-atrium::layout>
