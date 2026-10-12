<?php

declare(strict_types=1);

use Illuminate\Testing\Fluent\AssertableJson;
use RefactorCircus\Polycart\Domains\Cart\Mcp\Tools\ActiveCartTool;
use RefactorCircus\Polycart\Domains\Cart\Mcp\Tools\ClearCartTool;
use RefactorCircus\Polycart\Domains\Cart\Mcp\Tools\ConvertCartTool;
use RefactorCircus\Polycart\Domains\Cart\Mcp\Tools\CreateCartTool;
use RefactorCircus\Polycart\Domains\Cart\Mcp\Tools\DeleteCartTool;
use RefactorCircus\Polycart\Domains\Cart\Mcp\Tools\ListCartsTool;
use RefactorCircus\Polycart\Domains\Cart\Mcp\Tools\MergeCartsTool;
use RefactorCircus\Polycart\Domains\Cart\Mcp\Tools\ShowCartTool;
use RefactorCircus\Polycart\Domains\Cart\Mcp\Tools\TransitionCartTool;
use RefactorCircus\Polycart\Domains\Cart\Mcp\Tools\UpdateCartTool;
use RefactorCircus\Polycart\Domains\Cart\Models\CartModel;
use RefactorCircus\Polycart\Domains\CartLine\Mcp\Tools\AddLinesTool;
use RefactorCircus\Polycart\Domains\CartLine\Mcp\Tools\RemoveLinesTool;
use RefactorCircus\Polycart\Domains\CartLine\Mcp\Tools\UpdateLinesTool;
use RefactorCircus\Polycart\Domains\CartType\Mcp\Tools\ListCartTypesTool;
use RefactorCircus\Polycart\Facades\Polycart;
use Workbench\Database\Factories\UserFactory;

it('lists the cart types', function (): void {
    mcpTool(ListCartTypesTool::class)
        ->assertOk()
        ->assertStructuredContent(fn (AssertableJson $json): AssertableJson => $json
            ->has('data', 10)
            ->where('data.2.key', 'quote')
            ->where('data.2.converts_to', ['order'])
            ->etc());
});

it('creates, finds and lists carts', function (): void {
    $user = UserFactory::new()->create();

    mcpTool(CreateCartTool::class, ['type' => 'quote', 'owner_type' => 'user', 'owner_id' => (string) $user->id, 'label' => 'Lobby'])
        ->assertOk()
        ->assertStructuredContent(fn (AssertableJson $json): AssertableJson => $json
            ->where('data.type', 'quote')
            ->where('data.status', 'draft')
            ->where('data.label', 'Lobby')
            ->etc());

    mcpTool(ActiveCartTool::class, ['type' => 'quote', 'owner_type' => 'user', 'owner_id' => (string) $user->id])
        ->assertOk()
        ->assertStructuredContent(fn (AssertableJson $json): AssertableJson => $json->where('data.label', 'Lobby')->etc());

    mcpTool(ListCartsTool::class, ['type' => 'quote'])
        ->assertOk()
        ->assertStructuredContent(fn (AssertableJson $json): AssertableJson => $json->has('data', 1)->etc());

    mcpTool(ListCartsTool::class, ['type' => 'order'])
        ->assertOk()
        ->assertStructuredContent(fn (AssertableJson $json): AssertableJson => $json->has('data', 0)->etc());
});

it('manages lines and totals', function (): void {
    $cart = Polycart::create('cart');
    $product = product(price: 1000);

    mcpTool(AddLinesTool::class, [
        'cart' => $cart->id,
        'lines' => [
            ['purchasable_type' => 'product', 'purchasable_id' => (string) $product->id, 'quantity' => 3],
        ],
    ])
        ->assertOk()
        ->assertStructuredContent(fn (AssertableJson $json): AssertableJson => $json->has('data', 1)->where('data.0.total', 3000)->etc());

    $line = $cart->lines()->firstOrFail();

    mcpTool(UpdateLinesTool::class, ['cart' => $cart->id, 'lines' => [['id' => $line->id, 'quantity' => 1, 'unit_price' => 900]]])
        ->assertOk()
        ->assertStructuredContent(fn (AssertableJson $json): AssertableJson => $json->where('data.lines.0.total', 900)->where('data.subtotal', 900)->etc());

    mcpTool(ShowCartTool::class, ['cart' => $cart->id])
        ->assertOk()
        ->assertStructuredContent(fn (AssertableJson $json): AssertableJson => $json->where('data.subtotal', 900)->has('data.lines', 1)->etc());

    mcpTool(RemoveLinesTool::class, ['cart' => $cart->id, 'lines' => [$line->id]])
        ->assertOk()
        ->assertStructuredContent(fn (AssertableJson $json): AssertableJson => $json->has('data.lines', 0)->etc());

    expect($cart->lines()->count())->toBe(0);
});

it('updates, clears and deletes a cart', function (): void {
    $cart = Polycart::create('cart');
    $cart->add(product());

    mcpTool(UpdateCartTool::class, ['cart' => $cart->id, 'label' => 'Named'])
        ->assertOk()
        ->assertStructuredContent(fn (AssertableJson $json): AssertableJson => $json->where('data.label', 'Named')->etc());

    mcpTool(ClearCartTool::class, ['cart' => $cart->id])
        ->assertOk()
        ->assertStructuredContent(fn (AssertableJson $json): AssertableJson => $json->has('data.lines', 0)->etc());

    mcpTool(DeleteCartTool::class, ['cart' => $cart->id])->assertOk();

    expect(CartModel::query()->find($cart->id))->toBeNull();
});

it('transitions, converts and merges', function (): void {
    $cart = Polycart::create('cart', 'guest');
    $cart->add(product());

    $quote = $cart->convertTo('quote');

    mcpTool(TransitionCartTool::class, ['cart' => $quote->id, 'status' => 'submitted'])
        ->assertOk()
        ->assertStructuredContent(fn (AssertableJson $json): AssertableJson => $json->where('data.status', 'submitted')->etc());

    mcpTool(ConvertCartTool::class, ['cart' => $quote->id, 'to' => 'order', 'copy' => false])
        ->assertOk()
        ->assertStructuredContent(fn (AssertableJson $json): AssertableJson => $json->where('data.type', 'order')->where('data.status', 'pending')->etc());

    $mine = Polycart::create('cart');

    mcpTool(MergeCartsTool::class, ['cart' => $cart->id, 'into' => $mine->id])
        ->assertOk()
        ->assertStructuredContent(fn (AssertableJson $json): AssertableJson => $json->where('data.id', $mine->id)->has('data.lines', 1)->etc());
});

it('explains a refusal so an agent can correct it', function (): void {
    $quote = Polycart::create('quote');

    mcpTool(TransitionCartTool::class, ['cart' => $quote->id, 'status' => 'accepted'])
        ->assertHasErrors(['cannot move from [draft] to [accepted]']);

    mcpTool(ConvertCartTool::class, ['cart' => $quote->id, 'to' => 'saved'])
        ->assertHasErrors(['cannot be converted']);

    mcpTool(AddLinesTool::class, ['cart' => $quote->id, 'lines' => [['purchasable_type' => 'user', 'purchasable_id' => '1']]])
        ->assertHasErrors(['not listed in polycart.purchasables']);
});

it('answers not found for a missing cart or a line in another cart', function (): void {
    mcpTool(ShowCartTool::class, ['cart' => 'missing'])->assertHasErrors(['Not found.']);

    $line = Polycart::create('cart')->add(product());

    mcpTool(RemoveLinesTool::class, ['cart' => Polycart::create('cart')->id, 'lines' => [$line->id]])
        ->assertHasErrors(['Line 0: Line ['.$line->id.'] is not in this cart. (reason: line_not_found)']);
});
