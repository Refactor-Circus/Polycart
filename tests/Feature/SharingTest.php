<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use RefactorCircus\Polycart\Domains\Cart\Models\CartModel;
use RefactorCircus\Polycart\Domains\Scope\Exceptions\InvalidScopeException;
use RefactorCircus\Polycart\Domains\Scope\Services\ScopeTree;
use RefactorCircus\Polycart\Domains\Sharing\Enums\Visibility;
use RefactorCircus\Polycart\Domains\Sharing\Events\CartSharedActionEvent;
use RefactorCircus\Polycart\Domains\Sharing\Events\CartUnsharedActionEvent;
use RefactorCircus\Polycart\Domains\Sharing\Exceptions\SharingException;
use RefactorCircus\Polycart\Facades\Polycart;
use RefactorCircus\Polycart\Tests\Fixtures\Types\OrderStatus;

it('records the whole tree a cart is created in and makes the creator its owner', function (): void {
    $acme = organization();
    $sales = team($acme);
    $ann = person($sales);

    $cart = Polycart::create('cart', $ann, scope: $sales);

    expect($cart->paths->map(fn ($path): array => [class_basename($path->scope_type), $path->scope_id, $path->depth])->all())
        ->toBe([['Team', (string) $sales->id, 0], ['Organization', (string) $acme->id, 1]])
        ->and($cart->anchor?->is($sales))->toBeTrue()
        ->and($cart->boundary?->is($acme))->toBeTrue()
        ->and($cart->visibility)->toBe(Visibility::Private)
        ->and($cart->roleFor($ann))->toBe('owner');
});

it('refuses a cart in a team the owner is not on', function (): void {
    $acme = organization();

    Polycart::create('cart', person(team($acme)), scope: team($acme, 'Other'));
})->throws(InvalidScopeException::class, 'does not belong');

it('locks a cart to the tree it was created in', function (): void {
    $acme = organization();
    $cart = Polycart::create('cart', scope: team($acme));

    $cart->scope_id = (string) team($acme, 'Other')->id;
    $cart->save();
})->throws(InvalidScopeException::class, 'locked');

it('keeps one active cart per scope', function (): void {
    $acme = organization();
    $sales = team($acme, 'Sales');
    $ops = team($acme, 'Ops');
    $ann = person($sales, $ops);

    $inSales = $ann->cart(scope: $sales);

    expect($ann->cart(scope: $sales)->id)->toBe($inSales->id)
        ->and($ann->cart(scope: $ops)->id)->not->toBe($inSales->id)
        ->and($ann->cart()->id)->not->toBe($inSales->id)
        ->and($ann->cart()->isScoped())->toBeFalse();
});

it('keeps a new cart private to its creator', function (): void {
    $sales = team(organization());
    $ann = person($sales);
    $bob = person($sales);

    $cart = Polycart::create('cart', $ann, scope: $sales);

    expect($cart->roleFor($bob))->toBeNull()
        ->and($bob->accessibleCarts()->count())->toBe(0)
        ->and($ann->accessibleCarts()->pluck('id')->all())->toBe([$cart->id]);
});

it('shares with someone in another team of the same organization', function (): void {
    Event::fake([CartSharedActionEvent::class]);

    $acme = organization();
    $ann = person(team($acme, 'Sales'));
    $cam = person(team($acme, 'Ops'));

    $cart = Polycart::create('cart', $ann, scope: $ann->teams->first());
    Polycart::share($cart, $cam, 'editor');

    expect($cart->roleFor($cam))->toBe('editor')
        ->and($cart->allows($cam, 'update'))->toBeTrue()
        ->and($cart->allows($cam, 'share'))->toBeFalse()
        ->and($cam->can('update', $cart))->toBeTrue()
        ->and($cam->can('delete', $cart))->toBeFalse()
        ->and($cam->accessibleCarts()->pluck('id')->all())->toBe([$cart->id]);

    Event::assertDispatched(CartSharedActionEvent::class, fn (CartSharedActionEvent $event): bool => $event->member->role === 'editor');
});

it('gives a shared team access to whoever is on it, from the next request', function (): void {
    $acme = organization();
    $sales = team($acme, 'Sales');
    $ops = team($acme, 'Ops');
    $ann = person($sales);
    $dan = person($sales);

    $cart = Polycart::create('cart', $ann, scope: $sales);
    Polycart::share($cart, $ops, 'viewer');

    expect($cart->roleFor($dan))->toBeNull();

    // Within one request, what Dan reaches is read once.
    $dan->teams()->attach($ops);
    expect($cart->roleFor($dan))->toBeNull();

    // A new request (a fresh scoped instance) reads it again.
    app()->forgetScopedInstances();
    expect($cart->roleFor($dan))->toBe('viewer')
        ->and($dan->accessibleCarts()->count())->toBe(1);

    // Code that changes someone's teams mid-request forgets what they reach.
    $dan->teams()->detach($ops);
    app(ScopeTree::class)->forget($dan);
    expect($cart->roleFor($dan))->toBeNull()
        ->and($dan->accessibleCarts()->count())->toBe(0);
});

it('reads what an actor reaches once per request', function (): void {
    $acme = organization();
    $sales = team($acme, 'Sales');
    $ann = person($sales);
    $carts = collect(range(1, 5))->map(fn (): CartModel => Polycart::create('cart', person($sales), scope: $sales)->setVisibility('scope'));

    $carts->each->load('members');
    app()->forgetScopedInstances();

    DB::enableQueryLog();
    $roles = $carts->map(fn (CartModel $cart): ?string => $cart->roleFor($ann))->all();

    // Ann's teams and their organization are read for the first cart only.
    expect($roles)->toBe(array_fill(0, 5, 'viewer'))
        ->and(count(DB::getQueryLog()))->toBeLessThanOrEqual(2);
});

it('gives the strongest of someone\'s roles', function (): void {
    $sales = team(organization());
    $ann = person($sales);
    $bob = person($sales);

    $cart = Polycart::create('cart', $ann, scope: $sales);
    Polycart::share($cart, $sales, 'viewer');
    Polycart::share($cart, $bob, 'editor');

    expect($cart->roleFor($bob))->toBe('editor');
});

it('refuses to share outside the boundary', function (): void {
    $ann = person(team(organization('Acme')));
    $cart = Polycart::create('cart', $ann, scope: $ann->teams->first());

    Polycart::share($cart, person(team(organization('Globex'))), 'viewer');
})->throws(SharingException::class, 'outside');

it('refuses to share a team from another organization', function (): void {
    $ann = person(team(organization('Acme')));
    $cart = Polycart::create('cart', $ann, scope: $ann->teams->first());

    Polycart::share($cart, team(organization('Globex')), 'viewer');
})->throws(SharingException::class, 'outside');

it('refuses to share an unscoped cart', function (): void {
    $sales = team(organization());

    Polycart::share(Polycart::create('cart', person($sales)), person($sales), 'viewer');
})->throws(SharingException::class, 'not in a scope');

it('refuses a role the type does not have', function (): void {
    $sales = team(organization());
    $cart = Polycart::create('cart', person($sales), scope: $sales);

    Polycart::share($cart, person($sales), 'buyer');
})->throws(SharingException::class, '[buyer] is not a role of a [cart] cart');

it('refuses to share a type that is never shared', function (): void {
    $sales = team(organization());
    $cart = Polycart::create('saved', person($sales), scope: $sales);

    Polycart::share($cart, person($sales), 'viewer');
})->throws(SharingException::class, 'cannot be shared');

it('never leaves a cart without an owner', function (): void {
    Event::fake([CartUnsharedActionEvent::class]);

    $sales = team(organization());
    $ann = person($sales);
    $bob = person($sales);
    $cart = Polycart::create('cart', $ann, scope: $sales);

    Polycart::share($cart, $bob, 'owner');
    Polycart::unshare($cart, $ann);

    expect($cart->roleFor($ann))->toBeNull();
    Event::assertDispatched(CartUnsharedActionEvent::class, fn (CartUnsharedActionEvent $event): bool => $event->memberId === (string) $ann->id);

    Polycart::share($cart, $bob, 'viewer');
})->throws(SharingException::class, 'top role');

it('opens a cart to its team or its whole organization', function (): void {
    $acme = organization();
    $sales = team($acme, 'Sales');
    $ann = person($sales);
    $teammate = person($sales);
    $colleague = person(team($acme, 'Ops'));
    $outsider = person(team(organization('Globex')));

    $cart = Polycart::create('cart', $ann, scope: $sales);

    $cart->setVisibility(Visibility::Scope);
    expect($cart->roleFor($teammate))->toBe('viewer')
        ->and($cart->roleFor($colleague))->toBeNull()
        ->and($teammate->accessibleCarts()->count())->toBe(1)
        ->and($colleague->accessibleCarts()->count())->toBe(0);

    $cart->setVisibility(Visibility::Boundary);
    expect($cart->roleFor($colleague))->toBe('viewer')
        ->and($cart->roleFor($outsider))->toBeNull()
        ->and($colleague->accessibleCarts()->count())->toBe(1)
        ->and($outsider->accessibleCarts()->count())->toBe(0);
});

it('refuses to open an unscoped cart', function (): void {
    Polycart::create('cart')->setVisibility(Visibility::Boundary);
})->throws(SharingException::class);

it('finds carts anywhere under a scope', function (): void {
    $acme = organization();
    $sales = team($acme, 'Sales');
    $ops = team($acme, 'Ops');

    Polycart::create('cart', scope: $sales);
    Polycart::create('cart', scope: $ops);
    Polycart::create('cart', scope: team(organization('Globex')));

    expect(CartModel::query()->inScope($acme)->count())->toBe(2)
        ->and(CartModel::query()->inScope($sales)->count())->toBe(1);
});

it('keeps a child cart in its parent\'s tree', function (): void {
    $acme = organization();
    $sales = team($acme);
    $project = Polycart::create('project', scope: $sales);
    $set = Polycart::create('set', attributes: ['parent_id' => $project->id]);

    expect($set->scope_id)->toBe((string) $sales->id)
        ->and($set->paths)->toHaveCount(2);

    Polycart::create('set', attributes: ['parent_id' => $project->id], scope: team(organization('Globex')));
})->throws(InvalidScopeException::class, 'not in the same scope');

it('carries its tree and its members into a converted copy', function (): void {
    $sales = team(organization());
    $ann = person($sales);
    $bob = person($sales);

    $cart = Polycart::create('cart', $ann, scope: $sales);
    $cart->add(product());
    Polycart::share($cart, $bob, 'editor');
    $cart->setVisibility(Visibility::Scope);

    $order = $cart->convertTo('order');

    expect($order->scope_id)->toBe((string) $sales->id)
        ->and($order->paths)->toHaveCount(2)
        ->and($order->visibility)->toBe(Visibility::Scope)
        ->and($order->roleFor($ann))->toBe('owner')
        ->and($order->roleFor($bob))->toBe('editor');
});

it('merges a guest cart into a scoped cart, but never across scopes', function (): void {
    $acme = organization();
    $sales = team($acme, 'Sales');

    $guest = Polycart::create('cart', 'session-1');
    $guest->add(product());

    $mine = Polycart::create('cart', scope: $sales);
    Polycart::merge($guest, $mine);
    expect($mine->lines)->toHaveCount(1);

    Polycart::merge($mine, Polycart::create('cart', scope: team($acme, 'Ops')));
})->throws(InvalidScopeException::class, 'not in the same scope');

it('lets a type decide who may check out', function (): void {
    $sales = team(organization());
    $ann = person($sales);
    $buyer = person($sales);
    $editor = person($sales);

    $order = Polycart::create('order', $ann, scope: $sales);
    Polycart::share($order, $buyer, 'buyer');
    Polycart::share($order, $editor, 'editor');

    expect($buyer->can('checkout', $order))->toBeTrue()
        ->and($editor->can('checkout', $order))->toBeFalse()
        ->and($buyer->can('transition', [$order, OrderStatus::Processing->value]))->toBeTrue()
        ->and($editor->can('transition', [$order, OrderStatus::Processing->value]))->toBeFalse()
        ->and($editor->can('update', $order))->toBeTrue();
});

it('maps roles the target type lacks when converting in place', function (): void {
    $sales = team(organization());
    $ann = person($sales);
    $buyer = person($sales);
    $editor = person($sales);

    $order = Polycart::create('order', $ann, scope: $sales);
    Polycart::share($order, $buyer, 'buyer');
    Polycart::share($order, $editor, 'editor');

    // A plain cart has no buyer role, so the buyer drops to its weakest role
    // rather than silently losing access. Roles both types have are kept.
    $cart = $order->convertTo('cart', copy: false);

    expect($cart->roleFor($buyer))->toBe('viewer')
        ->and($cart->roleFor($editor))->toBe('editor')
        ->and($cart->roleFor($ann))->toBe('owner');
});
