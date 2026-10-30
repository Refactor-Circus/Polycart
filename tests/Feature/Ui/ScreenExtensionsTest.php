<?php

declare(strict_types=1);

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use RefactorCircus\Atrium\Domains\Navigation\Data\NavItem;
use RefactorCircus\Atrium\Domains\Screens\Data\ResolvedScreen;
use RefactorCircus\Atrium\Domains\Screens\Data\Screen;
use RefactorCircus\Atrium\Domains\Screens\Data\SectionDefinition;
use RefactorCircus\Atrium\Domains\Screens\Data\TabDefinition;
use RefactorCircus\Atrium\Facades\Atrium;
use RefactorCircus\Polycart\Atrium\Data\CartTypeRow;
use RefactorCircus\Polycart\Atrium\PolycartPlugin;
use RefactorCircus\Polycart\Atrium\Support\Screens;
use RefactorCircus\Polycart\Domains\Cart\Models\CartModel;
use RefactorCircus\Polycart\Domains\Cart\Policies\CartPolicy;
use RefactorCircus\Polycart\Domains\CartType\Services\CartTypeRegistry;
use RefactorCircus\Polycart\Facades\Polycart;
use RefactorCircus\Polycart\Tests\Fixtures\Types\QuoteCart;
use RefactorCircus\Polycart\Tests\Fixtures\Types\RetailCart;
use Symfony\Component\Finder\SplFileInfo;

/*
 * Polycart's three Atrium pages declare themselves on Atrium's screen
 * extensions registry: other packages add tabs and sections to them without
 * Polycart knowing they exist. Sections render the fixture view
 * `polycart-tests::marker`, which names its screen and record.
 */

/**
 * Refuses every page: `authorizeScreen()` answers 403 before the screen is
 * resolved.
 */
final class ScreenExtensionsRefusingCartPolicy extends CartPolicy
{
    public function viewAny(Model $user): bool
    {
        return false;
    }

    public function view(Model $user, CartModel $cart): bool
    {
        return false;
    }
}

beforeEach(function (): void {
    // Atrium denies access outside local until a gate is defined.
    app()->detectEnvironment(fn (): string => 'local');

    $this->lobby = Polycart::create('cart', attributes: ['label' => 'Lobby']);
    $this->lobby->add(product(), 2);
    $this->annex = Polycart::create('cart', attributes: ['label' => 'Annex']);
});

dataset('polycart screens', [
    'the carts list' => ['polycart.carts'],
    'a cart' => ['polycart.cart'],
    'the cart types' => ['polycart.types'],
]);

/**
 * Where a screen is, what kind it is, text only its own content shows, and
 * the keys of its rows (none on a detail screen). The types screen is cut
 * down to two types, so every list has two rows.
 *
 * @return array{url: string, kind: string, content: string, rows: list<string>}
 */
function screenExtensionsPage(string $key): array
{
    $test = test();

    return match ($key) {
        'polycart.carts' => [
            'url' => route('atrium.polycart.carts.index'),
            'kind' => Screen::LIST,
            'content' => 'Lobby',
            'rows' => [$test->lobby->id, $test->annex->id],
        ],
        'polycart.cart' => [
            'url' => route('atrium.polycart.carts.show', $test->lobby),
            'kind' => Screen::DETAIL,
            // The cart's details; its id is also in the header's form actions.
            'content' => 'Cart ID',
            'rows' => [],
        ],
        'polycart.types' => (function (): array {
            config()->set('polycart.types', ['cart' => RetailCart::class, 'quote' => QuoteCart::class]);

            return [
                'url' => route('atrium.polycart.types.index'),
                'kind' => Screen::LIST,
                'content' => RetailCart::class,
                'rows' => ['cart', 'quote'],
            ];
        })(),
        default => throw new InvalidArgumentException("No Polycart screen [{$key}]."),
    };
}

/**
 * Register a public tab, and a public section in every slot either kind of
 * screen may declare, each marked with its slot.
 */
function screenExtensionsRegister(string $screen): void
{
    Atrium::screens()->tab($screen, TabDefinition::make('acme.tab')->label('Acme')->view('polycart-tests::marker')->public()
        ->resolve(fn (): array => ['marker' => 'the acme panel']));

    foreach (['header.actions', 'before', 'aside', 'after', 'row.actions'] as $slot) {
        Atrium::screens()->section($screen, $slot, SectionDefinition::make('acme.'.$slot)->view('polycart-tests::marker')->public()
            ->resolve(fn (): array => ['marker' => 'in '.$slot]));
    }
}

function screenExtensionsTestId(string $id): string
{
    return 'data-testid="'.$id.'"';
}

/**
 * A screen resolved for a viewer outside a request to its page, for viewers
 * the page itself refuses before it resolves anything.
 */
function screenExtensionsResolveAs(?Authenticatable $user, Screen $screen): ResolvedScreen
{
    // A fresh request's worth of remembered answers, as this viewer.
    app()->forgetScopedInstances();

    $request = Request::create('/atrium');
    app()->instance('request', $request);
    $request->setUserResolver(fn (): ?Authenticatable => $user);

    return Atrium::screens()->resolve($screen, $request);
}

/**
 * A section on the cart page shown to viewers granted `view` on the cart.
 */
function screenExtensionsViewSection(): void
{
    Atrium::screens()->section('polycart.cart', 'before', SectionDefinition::make('acme.viewers')->view('polycart-tests::marker')->permission('view'));
}

it('declares each screen with its key, kind and standard slots', function (string $key): void {
    $page = screenExtensionsPage($key);

    $resolved = $this->get($page['url'])->assertOk()->assertViewHas('resolved')->viewData('resolved');

    expect($resolved)->toBeInstanceOf(ResolvedScreen::class)
        ->and($resolved->screen->key)->toBe($key)
        ->and($resolved->screen->kind)->toBe($page['kind'])
        ->and(array_map(fn (TabDefinition $tab): string => $tab->key, $resolved->screen->tabs))->toBe([Screen::OVERVIEW])
        ->and($resolved->openTab)->toBe(Screen::OVERVIEW);

    if ($page['kind'] === Screen::DETAIL) {
        expect($resolved->screen->record?->getKey())->toBe($this->lobby->id)
            ->and($resolved->screen->slots)->toBe(['header.actions', 'before', 'after', 'aside'])
            ->and($resolved->screen->rowSlots)->toBe([]);
    } else {
        expect($resolved->screen->record)->toBeNull()
            ->and($resolved->screen->slots)->toBe(['header.actions', 'before', 'after'])
            ->and($resolved->screen->rowSlots)->toBe(['row.actions']);
    }
})->with('polycart screens');

it('passes the cart types to their screen as CartTypeRow rows', function (): void {
    $page = screenExtensionsPage('polycart.types');

    $rows = $this->get($page['url'])->assertOk()->assertViewHas('rows')->viewData('rows');

    expect($rows)->each->toBeInstanceOf(CartTypeRow::class)
        ->and(array_map(fn (CartTypeRow $row): string => $row->getKey(), $rows))->toBe(['cart', 'quote'])
        ->and(array_map(fn (CartTypeRow $row): string => $row->type::class, $rows))->toBe([RetailCart::class, QuoteCart::class])
        ->and(app(CartTypeRegistry::class)->has('quote'))->toBeTrue();
});

it('renders each registered section once in its slot, each row\'s once per row, and none on an undeclared slot', function (string $key): void {
    $page = screenExtensionsPage($key);
    screenExtensionsRegister($key);

    $response = $this->get($page['url'])->assertOk();
    $html = (string) $response->getContent();

    $declared = $page['kind'] === Screen::DETAIL ? ['header.actions', 'before', 'aside', 'after'] : ['header.actions', 'before', 'after'];
    $undeclared = $page['kind'] === Screen::DETAIL ? 'row.actions' : 'aside';

    foreach ($declared as $slot) {
        expect(substr_count($html, screenExtensionsTestId('section-acme.'.$slot)))->toBe(1, "the {$slot} section");
        $response->assertSee('in '.$slot.' on '.$key);
    }

    expect($html)->not->toContain(screenExtensionsTestId('section-acme.'.$undeclared))
        ->not->toContain('in '.$undeclared.' on');

    // Each row's section receives that row as its record.
    expect(substr_count($html, screenExtensionsTestId('section-acme.row.actions')))->toBe(count($page['rows']));

    foreach ($page['rows'] as $row) {
        expect(substr_count($html, 'in row.actions on '.$key.' for '.$row))->toBe(1, "the row.actions section of row {$row}");
    }
})->with('polycart screens');

it('opens the overview without ?tab= and the registered tab\'s panel with it', function (string $key): void {
    $page = screenExtensionsPage($key);
    screenExtensionsRegister($key);

    $this->get($page['url'])
        ->assertOk()
        ->assertSee(screenExtensionsTestId('tab-'.Screen::OVERVIEW), false)
        ->assertSee(screenExtensionsTestId('tab-acme.tab'), false)
        ->assertDontSee(screenExtensionsTestId('tab-panel-acme.tab'), false)
        ->assertSee($page['content'], false);

    app()->forgetScopedInstances();

    $open = $this->get($page['url'].'?tab=acme.tab')
        ->assertOk()
        ->assertSee(screenExtensionsTestId('tab-panel-acme.tab'), false)
        ->assertSee('the acme panel on '.$key)
        ->assertSee(screenExtensionsTestId('section-acme.header.actions'), false)
        ->assertDontSee($page['content'], false);

    expect($open->viewData('resolved')->openTab)->toBe('acme.tab');

    // Only the header's actions stay beside an open tab.
    foreach (['before', 'aside', 'after', 'row.actions'] as $slot) {
        $open->assertDontSee(screenExtensionsTestId('section-acme.'.$slot), false);
    }

    $open->assertDontSee(screenExtensionsTestId('screen-aside'), false);
})->with('polycart screens');

it('lays the header actions, tab bar and slots around the page', function (string $key): void {
    $page = screenExtensionsPage($key);
    screenExtensionsRegister($key);

    $response = $this->get($page['url'])->assertOk();
    $html = (string) $response->getContent();

    $response->assertSeeInOrder([
        screenExtensionsTestId('section-acme.header.actions'),
        screenExtensionsTestId('screen-tabs'),
        screenExtensionsTestId('section-acme.before'),
        $page['content'],
        ...($page['kind'] === Screen::DETAIL
            ? [screenExtensionsTestId('screen-aside'), screenExtensionsTestId('section-acme.aside')]
            : [screenExtensionsTestId('row-actions'), screenExtensionsTestId('section-acme.row.actions')]),
        screenExtensionsTestId('section-acme.after'),
    ], false);

    // The header's actions sit in the page header: after its title, before
    // the tab bar under it.
    expect(Str::between($html, '</h1>', screenExtensionsTestId('screen-tabs')))
        ->toContain(screenExtensionsTestId('section-acme.header.actions'));

    if ($page['kind'] === Screen::DETAIL) {
        // `aside` is the side column of the cart page.
        expect($html)->toMatch('/<aside[^>]*data-testid="screen-aside"[^>]*>\s*<div[^>]*data-testid="section-acme\.aside"/');

        return;
    }

    // `row.actions` is each row's trailing cell, under an empty header cell.
    expect($html)->toMatch('/<th[^>]*data-testid="row-actions-heading"[^>]*>\s*<\/th>\s*<\/tr>/')
        ->and(preg_match_all('/<td[^>]*data-testid="row-actions"[^>]*>\s*<div[^>]*data-testid="section-acme\.row\.actions"[^>]*>.*?<\/div>\s*<\/td>\s*<\/tr>/s', $html))->toBe(count($page['rows']));
})->with('polycart screens');

it('renders no tab bar or sections while nothing is registered', function (string $key): void {
    $page = screenExtensionsPage($key);

    $html = (string) $this->get($page['url'])
        ->assertOk()
        ->assertSee($page['content'], false)
        ->getContent();

    expect($html)
        ->not->toContain(screenExtensionsTestId('screen-tabs'))
        ->not->toContain('data-testid="tab-')
        ->not->toContain('data-testid="section-')
        ->not->toContain(screenExtensionsTestId('screen-aside'))
        ->not->toContain(screenExtensionsTestId('row-actions-heading'));
})->with('polycart screens');

it('answers 403 before running any extension for a user the page refuses', function (string $key): void {
    $page = screenExtensionsPage($key);

    config()->set('polycart.authorization', true);
    Gate::policy(CartModel::class, ScreenExtensionsRefusingCartPolicy::class);

    $calls = 0;
    $count = function () use (&$calls): bool {
        $calls++;

        return true;
    };
    $resolve = function () use (&$calls): array {
        $calls++;

        return [];
    };

    Atrium::screens()->tab($key, TabDefinition::make('acme.tab')->view('polycart-tests::marker')->authorize($count)->resolve($resolve));

    foreach (['header.actions', 'before', 'aside', 'after', 'row.actions'] as $slot) {
        Atrium::screens()->section($key, $slot, SectionDefinition::make('acme.'.$slot)->view('polycart-tests::marker')->authorize($count)->resolve($resolve));
    }

    $this->actingAs(person(team(organization())))->get($page['url'])->assertForbidden();
    $this->actingAs(person(team(organization())))->get($page['url'].'?tab=acme.tab')->assertForbidden();

    expect($calls)->toBe(0);
})->with('polycart screens');

describe('permission() extensions, with polycart.authorization on', function (): void {
    beforeEach(function (): void {
        config()->set('polycart.authorization', true);

        $this->sales = team(organization(), 'Sales');
        $this->ann = person($this->sales);
        $this->stranger = person($this->sales);
        $this->cart = Polycart::create('cart', $this->ann, scope: $this->sales, attributes: ['label' => 'Annes cart']);
    });

    it('shows a permission(\'view\') section on a cart to its member and not to a stranger', function (): void {
        screenExtensionsViewSection();

        $this->actingAs($this->ann)->get(route('atrium.polycart.carts.show', $this->cart))
            ->assertOk()
            ->assertSee(screenExtensionsTestId('section-acme.viewers'), false);

        // The page refuses the stranger before it resolves anything, so their
        // view of the screen is resolved directly.
        expect(screenExtensionsResolveAs($this->stranger, Screens::detail('polycart.cart', $this->cart))->sections('before'))->toBe([])
            ->and(screenExtensionsResolveAs($this->ann, Screens::detail('polycart.cart', $this->cart))->sections('before'))->toHaveCount(1);
    });

    it('shows a permission(\'view\') section on a cart to an operator who is not a member', function (): void {
        config()->set('polycart.atrium.show_all', true);
        screenExtensionsViewSection();

        $this->actingAs($this->stranger)->get(route('atrium.polycart.carts.show', $this->cart))
            ->assertOk()
            ->assertSee(screenExtensionsTestId('section-acme.viewers'), false);
    });

    it('shows a permission(\'view\') section on a cart to anyone with authorization off', function (): void {
        config()->set('polycart.authorization', false);
        screenExtensionsViewSection();

        $this->actingAs($this->stranger)->get(route('atrium.polycart.carts.show', $this->cart))
            ->assertOk()
            ->assertSee(screenExtensionsTestId('section-acme.viewers'), false);
    });

    it('shows a permission(\'view\') section on each listed cart only to whom the cart lets view it', function (): void {
        Polycart::create('cart', $this->stranger, scope: $this->sales, attributes: ['label' => 'Strangers cart']);
        Atrium::screens()->section('polycart.carts', 'row.actions', SectionDefinition::make('acme.row-viewers')->view('polycart-tests::marker')->permission('view'));

        $html = (string) $this->actingAs($this->ann)->get(route('atrium.polycart.carts.index'))->assertOk()->getContent();

        // Ann lists only her own cart, and may view it.
        expect(substr_count($html, screenExtensionsTestId('section-acme.row-viewers')))->toBe(1)
            ->and($html)->toContain('on polycart.carts for '.$this->cart->id);

        // A cart she cannot view, as a row, is refused.
        expect(screenExtensionsResolveAs($this->stranger, Screens::list('polycart.carts'))->rowSections('row.actions', $this->cart))->toBe([])
            ->and(screenExtensionsResolveAs($this->ann, Screens::list('polycart.carts'))->rowSections('row.actions', $this->cart))->toHaveCount(1);
    });

    it('asks about a cart type row as the CartModel class', function (): void {
        $row = CartTypeRow::for(app(CartTypeRegistry::class)->get('cart'));
        $asked = [];

        Gate::after(function (mixed $user, string $ability, mixed $result, array $arguments) use (&$asked): void {
            if ($ability === 'viewAny') {
                $asked[] = $arguments;
            }
        });

        $resolved = screenExtensionsResolveAs($this->ann, Screens::list('polycart.types'));

        expect($resolved->screen->permits('viewAny', request(), $row))->toBeTrue()
            ->and($resolved->screen->permits('viewAny', request(), null))->toBeTrue()
            ->and($asked)->toBe([[CartModel::class], [CartModel::class]])
            ->and(screenExtensionsResolveAs(null, Screens::list('polycart.types'))->screen->permits('viewAny', request(), $row))->toBeFalse();
    });

    // D-044: a cart type row is asked about as the CartModel class (D-042),
    // so the class-level `viewAny` tells a signed-in member from a guest.
    it('shows a permission(\'viewAny\') row section on polycart.types to a signed-in member and not to a guest', function (): void {
        $page = screenExtensionsPage('polycart.types');
        Atrium::screens()->section('polycart.types', 'row.actions', SectionDefinition::make('acme.type-viewers')->view('polycart-tests::marker')->permission('viewAny'));

        $html = (string) $this->actingAs($this->ann)->get($page['url'])->assertOk()->getContent();

        expect(substr_count($html, screenExtensionsTestId('section-acme.type-viewers')))->toBe(count($page['rows']));

        foreach ($page['rows'] as $row) {
            expect($html)->toContain('on polycart.types for '.$row);
        }

        // The page refuses a guest before it resolves anything, so their view
        // of the screen is resolved directly.
        $row = CartTypeRow::for(app(CartTypeRegistry::class)->get('cart'));

        expect(screenExtensionsResolveAs(null, Screens::list('polycart.types'))->rowSections('row.actions', $row))->toBe([])
            ->and(screenExtensionsResolveAs($this->ann, Screens::list('polycart.types'))->rowSections('row.actions', $row))->toHaveCount(1);
    });

    // D-045: CartPolicy answers every ability on a cart (its `__call()` looks
    // a type-defined or unknown one up in the type's roles), and the Gate
    // asks a policy before any `Gate::define()`. So the cart's owner sees the
    // section and a stranger does not, whether or not the Gate defines it.
    it('decides a type-defined or unknown permission on a cart through CartPolicy', function (string $type, string $ability, bool $defined): void {
        $cart = $type === 'cart'
            ? $this->cart
            : Polycart::create($type, $this->ann, scope: $this->sales, attributes: ['label' => 'Annes order']);

        if ($defined) {
            Gate::define($ability, fn (): bool => true);
        }

        Atrium::screens()->section('polycart.cart', 'before', SectionDefinition::make('acme.extra')->view('polycart-tests::marker')->permission($ability));

        $this->actingAs($this->ann)->get(route('atrium.polycart.carts.show', $cart))
            ->assertOk()
            ->assertSee(screenExtensionsTestId('section-acme.extra'), false);

        // The page refuses the stranger before it resolves anything, so their
        // view of the screen is resolved directly.
        expect(screenExtensionsResolveAs($this->stranger, Screens::detail('polycart.cart', $cart))->sections('before'))->toBe([])
            ->and(screenExtensionsResolveAs($this->ann, Screens::detail('polycart.cart', $cart))->sections('before'))->toHaveCount(1);
    })->with([
        'a type-defined ability' => ['order', 'checkout'],
        'an unknown ability' => ['cart', 'acme.extra'],
    ])->with([
        'without a Gate definition' => [false],
        'with a Gate definition granting everyone' => [true],
    ]);
});

describe('audit trails and the History nav item', function (): void {
    it('leaves no audit trail in any view', function (): void {
        $files = collect(File::allFiles(dirname(__DIR__, 3).'/resources/views'))
            ->filter(fn (SplFileInfo $file): bool => str_contains((string) file_get_contents($file->getPathname()), 'x-atrium::audit'))
            ->map(fn (SplFileInfo $file): string => $file->getRelativePathname())
            ->values()
            ->all();

        expect($files)->toBe([]);
    });

    it('links no history page from its navigation', function (): void {
        $urls = array_map(fn (NavItem $item): ?string => $item->resolveUrl(), app(PolycartPlugin::class)->navigation());

        expect(array_filter($urls, fn (?string $url): bool => $url !== null && str_contains($url, 'history')))->toBe([])
            ->and($urls)->toContain(route('atrium.polycart.carts.index'), route('atrium.polycart.types.index'));
    });

    it('no longer gives the carts list a showHistory flag', function (): void {
        $this->get(route('atrium.polycart.carts.index'))->assertOk()->assertViewMissing('showHistory');
    });
});
