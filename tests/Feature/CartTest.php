<?php

declare(strict_types=1);

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Event;
use RefactorCircus\Polycart\Domains\Cart\Events\CartCreatedActionEvent;
use RefactorCircus\Polycart\Domains\Cart\Models\CartModel;
use RefactorCircus\Polycart\Facades\Polycart;
use RefactorCircus\Polycart\Tests\Fixtures\Models\Quote;
use RefactorCircus\Polycart\Tests\Fixtures\Types\QuoteStatus;
use RefactorCircus\Polycart\Tests\Fixtures\Types\RetailCart;
use Workbench\App\Models\User;
use Workbench\Database\Factories\UserFactory;

function user(): User
{
    return UserFactory::new()->create();
}

it('starts a cart of a type for an owner', function (): void {
    Event::fake([CartCreatedActionEvent::class]);

    $user = user();
    $cart = Polycart::create('cart', $user, ['label' => 'Office refit']);

    expect($cart->type)->toBe('cart')
        ->and($cart->cartType())->toBeInstanceOf(RetailCart::class)
        ->and($cart->owner?->is($user))->toBeTrue()
        ->and($cart->label)->toBe('Office refit')
        ->and($cart->status)->toBeNull();

    Event::assertDispatched(CartCreatedActionEvent::class, fn (CartCreatedActionEvent $event): bool => $event->cart->is($cart) && $event->cart->type === 'cart');
});

it('hydrates each row as the model its type names', function (): void {
    $quote = Polycart::create('quote', user());
    $cart = Polycart::create('cart', user());

    expect($quote)->toBeInstanceOf(Quote::class)
        ->and(CartModel::query()->find($quote->id))->toBeInstanceOf(Quote::class)
        ->and(CartModel::query()->find($cart->id))->not->toBeInstanceOf(Quote::class)
        ->and(Polycart::find($quote->id))->toBeInstanceOf(Quote::class);
});

it('scopes a pinned subclass to its own type', function (): void {
    Polycart::create('quote', user());
    Polycart::create('cart', user());

    $quote = Quote::query()->create();

    expect(Quote::query()->count())->toBe(2)
        ->and(CartModel::query()->count())->toBe(3)
        ->and($quote->type)->toBe('quote')
        ->and($quote->reference())->toStartWith('Q-');
});

it('starts a cart in its type\'s first status', function (): void {
    $quote = Polycart::create('quote', user());

    expect($quote->currentStatus())->toBe(QuoteStatus::Draft)
        ->and($quote->hasStatus(QuoteStatus::Draft))->toBeTrue()
        ->and($quote->hasStatus('submitted'))->toBeFalse();
});

it('reuses an owner\'s active cart', function (): void {
    $user = user();

    $first = Polycart::active('cart', $user);
    $second = Polycart::active('cart', $user);

    expect($second->id)->toBe($first->id)
        ->and(Polycart::active('cart', user())->id)->not->toBe($first->id)
        ->and(Polycart::active('saved', $user)->id)->not->toBe($first->id);
});

it('keeps guest carts by session key', function (): void {
    $guest = Polycart::active('cart', 'session-abc');

    expect($guest->session_key)->toBe('session-abc')
        ->and($guest->owner_type)->toBeNull()
        ->and(Polycart::active('cart', 'session-abc')->id)->toBe($guest->id)
        ->and(Polycart::active('cart', 'session-xyz')->id)->not->toBe($guest->id);
});

it('starts a fresh active cart once the last has moved past its first status', function (): void {
    $user = user();
    $quote = Polycart::active('quote', $user);

    $quote->add(product());
    $quote->transitionTo(QuoteStatus::Submitted);

    expect(Polycart::active('quote', $user)->id)->not->toBe($quote->id);
});

it('expires a cart after its type\'s lifetime and replaces it', function (): void {
    Carbon::setTestNow('2026-01-01 12:00:00');

    $user = user();
    $cart = Polycart::active('cart', $user);

    expect($cart->expires_at?->toDateString())->toBe('2026-02-15')
        ->and($cart->isExpired())->toBeFalse();

    Carbon::setTestNow('2026-02-16 12:00:00');

    expect($cart->isExpired())->toBeTrue()
        ->and(Polycart::active('cart', $user)->id)->not->toBe($cart->id);
});

it('pushes the expiry back when the cart changes', function (): void {
    Carbon::setTestNow('2026-01-01 12:00:00');
    $cart = Polycart::active('cart', user());

    Carbon::setTestNow('2026-01-30 12:00:00');
    $cart->add(product());

    expect($cart->fresh()?->expires_at?->toDateString())->toBe('2026-03-16');
});

it('gives an owner model its own carts', function (): void {
    $user = user();

    $cart = $user->cart();

    expect($cart->type)->toBe('cart')
        ->and($user->cart()->id)->toBe($cart->id)
        ->and($user->cart('quote'))->toBeInstanceOf(Quote::class)
        ->and($user->carts()->count())->toBe(2);
});

it('prunes deleted carts of every type once the deleted grace period has passed', function (): void {
    Carbon::setTestNow('2026-01-01 12:00:00');
    $oldQuote = Polycart::create('quote', user());
    $oldOrder = Polycart::create('order', user());
    $kept = Polycart::create('quote', user());
    $oldQuote->delete();
    $oldOrder->delete();

    Carbon::setTestNow('2026-01-25 12:00:00');
    $recent = Polycart::create('quote', user());
    $recent->delete();

    Carbon::setTestNow('2026-02-15 12:00:00');
    $this->artisan('model:prune', ['--model' => [CartModel::class]])->assertSuccessful();

    expect(CartModel::withTrashed()->find($oldQuote->id))->toBeNull()
        ->and(CartModel::withTrashed()->find($oldOrder->id))->toBeNull()
        ->and(CartModel::withTrashed()->find($recent->id))->not->toBeNull()
        ->and(CartModel::query()->find($kept->id))->not->toBeNull();

    config()->set('polycart.prune_deleted_after_days', 7);
    $this->artisan('model:prune', ['--model' => [CartModel::class]])->assertSuccessful();

    expect(CartModel::withTrashed()->find($recent->id))->toBeNull();
});

it('filters carts by owner, type and status', function (): void {
    $user = user();
    Polycart::create('cart', $user);
    Polycart::create('quote', $user)->add(product())->cart->transitionTo(QuoteStatus::Submitted);
    Polycart::create('quote', user());

    expect(CartModel::query()->ownedBy($user)->count())->toBe(2)
        ->and(CartModel::query()->ofType('quote')->count())->toBe(2)
        ->and(CartModel::query()->ofType('cart', 'quote')->ownedBy($user)->count())->toBe(2)
        ->and(CartModel::query()->whereStatus(QuoteStatus::Submitted)->count())->toBe(1);
});

it('prunes carts once they are past their expiry and the grace period', function (): void {
    Carbon::setTestNow('2026-01-01 12:00:00');
    $stale = Polycart::create('cart', user());
    $quote = Polycart::create('quote', user());

    Carbon::setTestNow('2026-03-20 12:00:00');
    $fresh = Polycart::create('cart', user());

    $this->artisan('model:prune', ['--model' => [CartModel::class]])->assertSuccessful();

    expect(CartModel::withTrashed()->find($stale->id))->toBeNull()
        ->and(CartModel::query()->find($quote->id))->not->toBeNull()
        ->and(CartModel::query()->find($fresh->id))->not->toBeNull();
});
