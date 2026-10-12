<?php

declare(strict_types=1);

use RefactorCircus\Polycart\Domains\Cart\Models\CartModel;
use RefactorCircus\Polycart\Facades\Polycart;
use RefactorCircus\Polycart\Tests\Fixtures\Models\Service;
use Workbench\Database\Factories\UserFactory;

it('lists the registered types with what each allows', function (): void {
    $this->getJson('/polycart/types')
        ->assertOk()
        ->assertJsonPath('data.0.key', 'cart')
        ->assertJsonPath('data.0.lifetime', 'P45D')
        ->assertJsonPath('data.0.converts_to', ['saved', 'quote', 'order'])
        ->assertJsonPath('data.2.key', 'quote')
        ->assertJsonPath('data.2.statuses', ['draft', 'submitted', 'accepted', 'declined'])
        ->assertJsonPath('data.2.transitions.submitted', ['accepted', 'declined'])
        ->assertJsonPath('data.2.requires_price', true);
});

it('creates a cart for an owner named by alias', function (): void {
    $user = UserFactory::new()->create();

    $this->postJson('/polycart/carts', [
        'type' => 'quote',
        'owner_type' => 'user',
        'owner_id' => (string) $user->id,
        'label' => 'Lobby',
        'meta' => ['po_number' => 'PO-1'],
    ])
        ->assertCreated()
        ->assertJsonPath('data.type', 'quote')
        ->assertJsonPath('data.status', 'draft')
        ->assertJsonPath('data.owner_id', (string) $user->id)
        ->assertJsonPath('data.meta.po_number', 'PO-1')
        ->assertJsonPath('data.lines', []);
});

it('refuses an owner type outside the allowlist', function (): void {
    $this->postJson('/polycart/carts', ['type' => 'cart', 'owner_type' => Service::class, 'owner_id' => '1'])
        ->assertUnprocessable()
        ->assertJsonPath('message', '['.Service::class.'] is not listed in polycart.owners.');
});

it('refuses an unknown type with the reason', function (): void {
    $this->postJson('/polycart/carts', ['type' => 'nope'])
        ->assertUnprocessable()
        ->assertJsonPath('message', 'No cart type is registered under [nope].');
});

it('returns the same active cart for a guest session', function (): void {
    $first = $this->postJson('/polycart/carts/active', ['type' => 'cart', 'session_key' => 'abc'])->assertCreated()->json('data.id');
    $second = $this->postJson('/polycart/carts/active', ['type' => 'cart', 'session_key' => 'abc'])->json('data.id');

    expect($second)->toBe($first);

    $this->postJson('/polycart/carts/active', ['type' => 'cart'])->assertUnprocessable();
});

it('lists carts with filters and a cursor', function (): void {
    $user = UserFactory::new()->create();
    Polycart::create('cart', $user);
    Polycart::create('quote', $user, ['label' => 'Tower lobby']);
    Polycart::create('quote');

    $this->getJson('/polycart/carts?type=quote')->assertOk()->assertJsonCount(2, 'data');
    $this->getJson('/polycart/carts?owner_type=user&owner_id='.$user->id)->assertJsonCount(2, 'data');
    $this->getJson('/polycart/carts?search=lobby')->assertJsonCount(1, 'data')->assertJsonPath('data.0.lines_count', 0);

    $page = $this->getJson('/polycart/carts?per_page=2')->assertJsonCount(2, 'data');
    $this->getJson('/polycart/carts?per_page=2&cursor='.$page->json('meta.next_cursor'))->assertJsonCount(1, 'data');
});

it('shows, updates and deletes a cart', function (): void {
    $cart = Polycart::create('cart');
    $cart->add(product(price: 1200), 2);

    $this->getJson("/polycart/carts/{$cart->id}")
        ->assertOk()
        ->assertJsonPath('data.subtotal', 2400)
        ->assertJsonPath('data.quantity', 2)
        ->assertJsonPath('data.fully_priced', true)
        ->assertJsonPath('data.lines.0.total', 2400);

    $this->patchJson("/polycart/carts/{$cart->id}", ['label' => 'Renamed', 'meta' => ['a' => 1]])
        ->assertOk()
        ->assertJsonPath('data.label', 'Renamed')
        ->assertJsonPath('data.meta', ['a' => 1]);

    $this->deleteJson("/polycart/carts/{$cart->id}")->assertNoContent();

    expect(CartModel::query()->find($cart->id))->toBeNull();

    // A plain message, as the MCP tools give, rather than one naming the model class.
    $this->getJson("/polycart/carts/{$cart->id}")->assertNotFound()->assertExactJson(['message' => 'Not found.']);
});

it('manages lines', function (): void {
    $cart = Polycart::create('cart');
    $product = product(price: 1000);

    $line = $this->postJson("/polycart/carts/{$cart->id}/lines", ['lines' => [
        ['purchasable_type' => 'product', 'purchasable_id' => (string) $product->id, 'quantity' => 2, 'options' => ['finish' => 'gold']],
        ['meta' => ['description' => 'Survey'], 'unit_price' => 5000],
    ]])
        ->assertCreated()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.unit_price', 1500)
        ->assertJsonPath('data.0.total', 3000)
        ->assertJsonPath('data.1.purchasable_type', null)
        ->json('data.0.id');

    $other = $cart->lines()->whereKeyNot($line)->sole()->id;

    $this->patchJson("/polycart/carts/{$cart->id}/lines", ['lines' => [
        ['id' => $line, 'quantity' => 5],
        ['id' => $other, 'quantity' => 2, 'unit_price' => 4000],
    ]])
        ->assertOk()
        ->assertJsonPath('data.lines.0.quantity', 5)
        ->assertJsonPath('data.lines.1.total', 8000)
        ->assertJsonPath('data.subtotal', 5 * 1500 + 8000);

    $this->patchJson("/polycart/carts/{$cart->id}/lines", ['lines' => [['id' => $line, 'quantity' => 0]]])
        ->assertOk()
        ->assertJsonCount(1, 'data.lines');

    $this->deleteJson("/polycart/carts/{$cart->id}/lines", ['lines' => [$other]])
        ->assertOk()
        ->assertJsonPath('data.lines', [])
        ->assertJsonPath('data.subtotal', 0);
});

it('removes lines named in the query string', function (): void {
    $cart = Polycart::create('cart');
    $a = $cart->add(product(sku: 'A'));
    $b = $cart->add(product(sku: 'B'));

    $this->deleteJson("/polycart/carts/{$cart->id}/lines?lines[]={$a->id}&lines[]={$b->id}")->assertOk();

    expect($cart->lines()->count())->toBe(0);
});

it('changes lines all or nothing, and only in their own cart', function (): void {
    $cart = Polycart::create('cart');
    $mine = $cart->add(product(sku: 'A'), 2);
    $theirs = Polycart::create('cart')->add(product(sku: 'B'));

    $this->patchJson("/polycart/carts/{$cart->id}/lines", ['lines' => [
        ['id' => $mine->id, 'quantity' => 9],
        ['id' => $theirs->id, 'quantity' => 1],
    ]])
        ->assertUnprocessable()
        ->assertJsonPath('reason', 'line_not_found')
        ->assertJsonPath('line', 1);

    $this->deleteJson("/polycart/carts/{$cart->id}/lines", ['lines' => [$mine->id, $theirs->id]])
        ->assertUnprocessable()
        ->assertJsonPath('line', 1);

    expect($mine->fresh()?->quantity)->toBe(2)
        ->and($theirs->fresh())->not->toBeNull();

    $this->patchJson("/polycart/carts/{$cart->id}/lines", ['lines' => [['id' => $mine->id]]])
        ->assertJsonValidationErrors(['lines.0.quantity']);

    $this->deleteJson("/polycart/carts/{$cart->id}/lines", ['lines' => [$mine->id, $mine->id]])
        ->assertJsonValidationErrors(['lines.0']);
});

it('refuses an update the type rejects and keeps the rest', function (): void {
    $quote = Polycart::create('quote');
    $a = $quote->add(product(sku: 'A'));
    $b = $quote->add(product(sku: 'B'));

    // Lowering one quantity is fine, but the type needs every line priced.
    $b->forceFill(['unit_price' => null])->saveQuietly();

    $this->patchJson("/polycart/carts/{$quote->id}/lines", ['lines' => [
        ['id' => $a->id, 'quantity' => 4],
        ['id' => $b->id, 'quantity' => 2],
    ]])
        ->assertUnprocessable()
        ->assertJsonPath('reason', 'missing_price')
        ->assertJsonPath('line', 1);

    expect($a->fresh()?->quantity)->toBe(1);
});

it('refuses a line the type rejects', function (): void {
    $quote = Polycart::create('quote');

    $this->postJson("/polycart/carts/{$quote->id}/lines", ['lines' => [
        ['purchasable_type' => 'product', 'purchasable_id' => (string) product()->id],
        ['purchasable_type' => 'product', 'purchasable_id' => (string) product(price: null, sku: 'B')->id],
    ]])
        ->assertUnprocessable()
        ->assertJsonPath('reason', 'missing_price')
        ->assertJsonPath('line', 1)
        ->assertJsonPath('message', fn (string $message): bool => str_starts_with($message, 'Line 1: ') && str_contains($message, 'needs a unit price'));

    // All or nothing: the first line was not kept either.
    expect($quote->lines()->count())->toBe(0);
});

it('clears a cart', function (): void {
    $cart = Polycart::create('cart');
    $cart->add(product());

    $this->postJson("/polycart/carts/{$cart->id}/clear")->assertOk()->assertJsonPath('data.lines', []);
});

it('moves a cart through its lifecycle', function (): void {
    $quote = Polycart::create('quote');

    $this->postJson("/polycart/carts/{$quote->id}/status", ['status' => 'submitted'])
        ->assertOk()
        ->assertJsonPath('data.status', 'submitted');

    $this->postJson("/polycart/carts/{$quote->id}/status", ['status' => 'draft'])
        ->assertUnprocessable()
        ->assertJsonPath('message', 'A [quote] cart cannot move from [submitted] to [draft].');
});

it('converts a cart by copy or in place', function (): void {
    $cart = Polycart::create('cart');
    $cart->add(product(), 2);

    $quote = $this->postJson("/polycart/carts/{$cart->id}/convert", ['to' => 'quote'])
        ->assertCreated()
        ->assertJsonPath('data.type', 'quote')
        ->assertJsonCount(1, 'data.lines')
        ->json('data.id');

    expect($quote)->not->toBe($cart->id);

    $this->postJson("/polycart/carts/{$quote}/convert", ['to' => 'order', 'copy' => false])
        ->assertCreated()
        ->assertJsonPath('data.id', $quote)
        ->assertJsonPath('data.status', 'pending');

    $this->postJson("/polycart/carts/{$quote}/convert", ['to' => 'saved'])->assertUnprocessable();
});

it('merges one cart into another', function (): void {
    $guest = Polycart::create('cart', 'abc');
    $guest->add(product());
    $mine = Polycart::create('cart');

    $this->postJson("/polycart/carts/{$guest->id}/merge", ['into' => $mine->id])
        ->assertOk()
        ->assertJsonPath('data.id', $mine->id)
        ->assertJsonCount(1, 'data.lines');

    $this->postJson("/polycart/carts/{$mine->id}/merge", ['into' => 'missing'])->assertNotFound();
});

it('validates input with the action rules', function (): void {
    $cart = Polycart::create('cart');

    $this->postJson("/polycart/carts/{$cart->id}/lines", ['lines' => [['quantity' => 0, 'purchasable_type' => 'product']]])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['lines.0.quantity', 'lines.0.purchasable_id']);

    $this->postJson("/polycart/carts/{$cart->id}/lines", ['lines' => []])->assertJsonValidationErrors(['lines']);
    $this->postJson("/polycart/carts/{$cart->id}/lines", ['purchasable_type' => 'product'])->assertJsonValidationErrors(['lines']);
});
