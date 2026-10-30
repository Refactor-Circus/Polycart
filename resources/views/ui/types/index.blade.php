{{-- The `polycart.types` screen: header actions always render; an open
     registered tab's panel replaces the table, and `before`, `after` and each
     type row's `row.actions` render only around it. A row is the type's
     CartTypeRow, so row sections receive it as `$record`; their column
     appears only when some row has a section to show. --}}
@php
    $tabOpen = $resolved->openRegisteredTab !== null;
    $rowActions = ! $tabOpen && collect($rows)->contains(fn ($row) => $resolved->rowSections('row.actions', $row) !== []);
@endphp

<x-atrium::layout :title="__('polycart::polycart.types')">
    <x-atrium::page-header :title="__('polycart::polycart.types')" :description="__('polycart::polycart.types_description')">
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
        <x-atrium::screen-sections :screen="$resolved" slot="before" class="mt-5" />

        <div class="mt-5">
            <x-atrium::table striped>
                <x-slot:head>
                    <x-atrium::table.row>
                        <x-atrium::table.cell heading>{{ __('polycart::polycart.type') }}</x-atrium::table.cell>
                        <x-atrium::table.cell heading>{{ __('polycart::polycart.statuses') }}</x-atrium::table.cell>
                        <x-atrium::table.cell heading>{{ __('polycart::polycart.converts_to') }}</x-atrium::table.cell>
                        <x-atrium::table.cell heading>{{ __('polycart::polycart.parents') }}</x-atrium::table.cell>
                        <x-atrium::table.cell heading>{{ __('polycart::polycart.rules') }}</x-atrium::table.cell>
                        <x-atrium::table.cell heading>{{ __('polycart::polycart.lifetime') }}</x-atrium::table.cell>
                        @if ($rowActions)
                            <x-atrium::table.cell heading data-testid="row-actions-heading" />
                        @endif
                    </x-atrium::table.row>
                </x-slot:head>

                @foreach ($rows as $row)
                    @php($type = $row->type)

                    <x-atrium::table.row>
                        <x-atrium::table.cell>
                            <a class="font-medium underline-offset-2 hover:underline"
                               href="{{ route('atrium.polycart.carts.index', ['type' => $type->key()]) }}">{{ $type->key() }}</a>
                            <div class="font-mono text-xs opacity-60">{{ $type::class }}</div>
                        </x-atrium::table.cell>
                        <x-atrium::table.cell>
                            @if ($type->statuses())
                                <div class="flex flex-wrap items-center gap-3">
                                    @foreach ($type->statuses()::cases() as $status)
                                        <span class="inline-flex items-center gap-1 text-sm">
                                            <x-atrium::status-dot :variant="\RefactorCircus\Polycart\Atrium\Badges::forStatus((string) $status->value)" :label="$status->value" data-status="{{ $status->value }}" />
                                            {{ $status->value }}
                                        </span>
                                    @endforeach
                                </div>
                            @else
                                <span class="opacity-60">—</span>
                            @endif
                        </x-atrium::table.cell>
                        <x-atrium::table.cell>{{ implode(', ', $type->convertsTo()) ?: '—' }}</x-atrium::table.cell>
                        <x-atrium::table.cell>
                            @if ($type->parents() === null)
                                {{ __('polycart::polycart.any') }}
                            @elseif ($type->parents() === [])
                                {{ __('polycart::polycart.root_only') }}
                            @else
                                {{ implode(', ', $type->parents()) }}
                            @endif
                        </x-atrium::table.cell>
                        <x-atrium::table.cell>
                            @if ($type->requiresPrice())
                                <x-atrium::badge variant="warning">{{ __('polycart::polycart.requires_price') }}</x-atrium::badge>
                            @endif
                            @if ($type->requiresParent())
                                <x-atrium::badge variant="primary">{{ __('polycart::polycart.requires_parent') }}</x-atrium::badge>
                            @endif
                            @unless ($type->mergesLines())
                                <x-atrium::badge>{{ __('polycart::polycart.separate_lines') }}</x-atrium::badge>
                            @endunless
                        </x-atrium::table.cell>
                        <x-atrium::table.cell>{{ $type->lifetime()?->forHumans() ?? __('polycart::polycart.never') }}</x-atrium::table.cell>
                        @if ($rowActions)
                            <x-atrium::table.cell data-testid="row-actions">
                                <x-atrium::screen-sections :screen="$resolved" slot="row.actions" :row="$row" />
                            </x-atrium::table.cell>
                        @endif
                    </x-atrium::table.row>
                @endforeach
            </x-atrium::table>
        </div>

        <x-atrium::screen-sections :screen="$resolved" slot="after" class="mt-5" />
    @endif
</x-atrium::layout>
