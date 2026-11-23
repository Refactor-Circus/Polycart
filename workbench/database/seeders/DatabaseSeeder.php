<?php

namespace Workbench\Database\Seeders;

use Closure;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use RefactorCircus\Polycart\Domains\Cart\Actions\ConvertCartAction;
use RefactorCircus\Polycart\Domains\Cart\Actions\CreateCartAction;
use RefactorCircus\Polycart\Domains\Cart\Actions\MergeCartsAction;
use RefactorCircus\Polycart\Domains\Cart\Actions\TransitionCartAction;
use RefactorCircus\Polycart\Domains\Cart\Actions\UpdateCartAction;
use RefactorCircus\Polycart\Domains\Cart\Models\CartModel;
use RefactorCircus\Polycart\Domains\CartLine\Actions\AddLinesAction;
use RefactorCircus\Polycart\Domains\CartLine\Actions\RemoveLineAction;
use RefactorCircus\Polycart\Domains\CartLine\Actions\UpdateLineAction;
use RefactorCircus\Polycart\Domains\Sharing\Actions\SetVisibilityAction;
use RefactorCircus\Polycart\Domains\Sharing\Actions\ShareCartAction;
use RefactorCircus\Polycart\Domains\Sharing\Enums\Visibility;
use RefactorCircus\Polycart\Facades\Polycart;
use Workbench\App\Carts\CartStatus;
use Workbench\App\Carts\OrderStatus;
use Workbench\App\Carts\QuoteStatus;
use Workbench\App\Models\Organization;
use Workbench\App\Models\Product;
use Workbench\App\Models\Team;
use Workbench\App\Models\User;
use Workbench\Database\Factories\UserFactory;

/**
 * A demo door-hardware store, built through Polycart's own Actions.
 *
 * Each step runs as a user (or a guest), from a source, on a day in the
 * past, so carts expire, histories have actors and dates, and the API reads
 * like a store that has been trading for a couple of months.
 */
class DatabaseSeeder extends Seeder
{
    /** @var array<string, Product> */
    private array $products = [];

    /** @var array<int, int> Steps taken so far on each day, to keep a day's steps in order. */
    private array $clock = [];

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        try {
            $this->demo();
        } finally {
            Carbon::setTestNow();
            Auth::forgetUser();
        }
    }

    private function demo(): void
    {
        // People and the tree they work in.
        $admin = $this->user('Admin', 'admin@example.com');
        $ada = $this->user('Ada Lovelace', 'ada@acme.test');
        $grace = $this->user('Grace Hopper', 'grace@acme.test');
        $linus = $this->user('Linus Torvalds', 'linus@acme.test');
        $alan = $this->user('Alan Turing', 'alan@globex.test');
        $margaret = $this->user('Margaret Hamilton', 'margaret@globex.test');
        $ken = $this->user('Ken Thompson', 'ken@example.com');

        $acme = Organization::query()->create(['name' => 'Acme']);
        $globex = Organization::query()->create(['name' => 'Globex']);

        $sales = $this->team($acme, 'Acme Sales', [$admin, $ada, $grace]);
        $operations = $this->team($acme, 'Acme Operations', [$ada, $linus]);
        $purchasing = $this->team($globex, 'Globex Purchasing', [$alan, $margaret]);
        $facilities = $this->team($globex, 'Globex Facilities', [$margaret]);

        $this->catalogue();

        $create = app(CreateCartAction::class);
        $share = app(ShareCartAction::class);
        $visibility = app(SetVisibilityAction::class);
        $transition = app(TransitionCartAction::class);
        $convert = app(ConvertCartAction::class);
        $update = app(UpdateCartAction::class);

        // Ada's lobby cart: shared with Grace, open to the team, and joined
        // by the lines she added as a guest before signing in.
        $lobby = $this->step($ada, 12, 'http', fn (): CartModel => $create->execute('cart', $ada, [
            'label' => 'HQ lobby refresh',
            'meta' => ['site' => 'Acme HQ', 'floor' => 'Ground'],
        ], $sales));
        $this->lines($ada, 12, 'http', $lobby, [
            ['LV-100', 6, ['finish' => 'brass']],
            ['DC-400', 4],
            ['KP-210', 6],
        ]);
        $this->step($ada, 11, 'http', fn () => $share->execute($lobby, $grace, 'editor'));
        $this->step($ada, 11, 'http', fn () => $visibility->execute($lobby, Visibility::Scope));
        $this->lines($grace, 9, 'http', $lobby, [['DS-050', 6]]);

        $guest = $this->step(null, 3, 'http', fn (): CartModel => $create->execute('cart', 'guest-7f3a9c'));
        $this->lines(null, 3, 'http', $guest, [['HG-020', 12], ['LV-100', 2, ['finish' => 'brass']]]);
        $this->step($ada, 2, 'http', fn () => app(MergeCartsAction::class)->execute($guest, $lobby));

        // Grace's cart is at the payment step.
        $warehouse = $this->step($grace, 5, 'http', fn (): CartModel => $create->execute('cart', $grace, [
            'label' => 'Warehouse side doors',
        ], $sales));
        $this->lines($grace, 5, 'http', $warehouse, [['EX-900', 2], ['CY-030', 4], ['HG-020', 4]]);
        $this->step($grace, 1, 'http', fn () => $transition->execute($warehouse, CartStatus::CheckingOut));

        // Linus checked out: his cart became an order, which was paid,
        // fulfilled, and then reordered as a fresh cart.
        $server = $this->step($linus, 40, 'http', fn (): CartModel => $create->execute('cart', $linus, [
            'label' => 'Server room access',
            'meta' => ['site' => 'Acme DC1'],
        ], $operations));
        $this->lines($linus, 40, 'http', $server, [['CR-480', 2], ['SL-399', 1], ['DC-400', 2]]);
        $this->step($linus, 39, 'http', fn () => $transition->execute($server, CartStatus::CheckingOut));
        $serverOrder = $this->step($linus, 39, 'http', fn (): CartModel => $convert->execute($server, 'order'));
        $this->step($linus, 39, 'http', fn () => $transition->execute($server, CartStatus::CheckedOut));
        $this->step($linus, 39, 'http', fn () => $share->execute($serverOrder, $ada, 'buyer'));
        $this->step($ada, 38, 'http', fn () => $transition->execute($serverOrder, OrderStatus::Paid));
        $this->step($admin, 34, 'http', fn () => $transition->execute($serverOrder, OrderStatus::Fulfilled));
        $reorder = $this->step($linus, 4, 'mcp', fn (): CartModel => $convert->execute($serverOrder, 'cart'));
        $this->step($linus, 4, 'mcp', fn () => $update->execute($reorder, ['label' => 'Server room access (reorder)']));

        // Alan gave up on his cart; Margaret's ran past its lifetime.
        $abandoned = $this->step($alan, 22, 'http', fn (): CartModel => $create->execute('cart', $alan, [
            'label' => 'Spare cylinders',
        ], $purchasing));
        $this->lines($alan, 22, 'http', $abandoned, [['CY-030', 10], ['KP-210', 2]]);
        $this->step($alan, 20, 'code', fn () => $transition->execute($abandoned, CartStatus::Abandoned));

        $stale = $this->step($margaret, 48, 'http', fn (): CartModel => $create->execute('cart', $margaret, [
            'label' => 'Fire door audit fixes',
        ], $facilities));
        $this->lines($margaret, 47, 'http', $stale, [['DC-400', 8], ['KP-210', 8], ['HG-020', 8]]);

        // Ken shops on his own, through an agent.
        $home = $this->step($ken, 1, 'mcp', fn (): CartModel => $create->execute('cart', $ken, ['label' => 'Home office']));
        $this->lines($ken, 1, 'mcp', $home, [['SL-399', 1], ['DS-050', 2]]);

        // The demo user's own cart.
        $own = $this->step($admin, 0, 'http', fn (): CartModel => $create->execute('cart', $admin, ['label' => 'Showroom samples'], $sales));
        $this->lines($admin, 0, 'http', $own, [['LV-100', 1], ['LV-100', 1, ['finish' => 'brass']], ['KP-210', 1]]);

        // Quotes. The clinic quote started as Ada's cart, retyped in place,
        // and waits on the demo user's approval.
        $clinicCart = $this->step($ada, 9, 'http', fn (): CartModel => $create->execute('cart', $ada, [
            'label' => 'Riverside clinic access control',
            'meta' => ['customer' => 'Riverside Clinic', 'po_number' => 'RC-2210'],
        ], $sales));
        $this->lines($ada, 9, 'http', $clinicCart, [['CR-480', 6], ['EX-900', 3], ['SL-399', 4]]);
        $clinic = $this->step($ada, 8, 'http', fn (): CartModel => $convert->execute($clinicCart, 'quote', copy: false));
        $this->custom($ada, 8, $clinic, 'Installation labour (2 technicians, 3 days)', 288000);
        $this->step($ada, 8, 'http', fn () => $share->execute($clinic, $grace, 'contributor'));
        $this->step($ada, 8, 'http', fn () => $share->execute($clinic, $admin, 'approver'));
        $this->step($ada, 8, 'http', fn () => $share->execute($clinic, $operations, 'reader'));
        $this->step($grace, 7, 'http', fn () => app(UpdateLineAction::class)->execute(
            $clinic->lines()->where('quantity', 4)->firstOrFail(),
            5,
        ));
        $this->step($grace, 6, 'http', fn () => $transition->execute($clinic, QuoteStatus::AwaitingApproval));

        // Approved and sent; the customer has yet to answer.
        $school = $this->quote($grace, 15, $sales, 'Northside school lockdown kit', ['customer' => 'Northside School'], [
            ['KP-210', 30], ['DC-400', 30], ['EX-900', 6],
        ]);
        $this->custom($grace, 15, $school, 'Engraved room plates', 3600, 30, $this->products['EP-000']);
        $this->step($grace, 14, 'http', fn () => $share->execute($school, $ada, 'approver'));
        $this->step($grace, 14, 'http', fn () => $transition->execute($school, QuoteStatus::AwaitingApproval));
        $this->step($ada, 13, 'http', fn () => $transition->execute($school, QuoteStatus::Approved));
        $this->step($grace, 13, 'http', fn () => $transition->execute($school, QuoteStatus::Sent));

        // Rejected once, revised, and back in draft.
        $plant = $this->quote($alan, 10, $purchasing, 'Plant 3 badge readers', ['customer' => 'Globex Plant 3'], [
            ['CR-480', 12], ['CY-030', 12],
        ]);
        $this->step($alan, 10, 'http', fn () => $share->execute($plant, $margaret, 'approver'));
        $this->step($alan, 9, 'http', fn () => $transition->execute($plant, QuoteStatus::AwaitingApproval));
        $this->step($margaret, 8, 'http', fn () => $transition->execute($plant, QuoteStatus::Rejected));
        $this->step($alan, 7, 'http', fn () => $transition->execute($plant, QuoteStatus::Draft));
        $this->step($alan, 7, 'http', fn () => app(RemoveLineAction::class)->execute($plant->lines()->firstOrFail()));
        $this->lines($alan, 7, 'http', $plant, [['CR-480', 8]]);
        $this->step($alan, 7, 'http', fn () => $update->execute($plant, ['meta' => ['customer' => 'Globex Plant 3', 'revision' => 2]]));

        // Accepted, then copied into an order Alan has yet to pay for.
        $offices = $this->quote($margaret, 30, $facilities, 'Regional offices rekey', ['customer' => 'Globex', 'po_number' => 'GX-7731'], [
            ['CY-030', 40], ['LV-100', 20],
        ]);
        $this->custom($margaret, 30, $offices, 'Master key system design', 45000);
        $this->step($margaret, 29, 'http', fn () => $transition->execute($offices, QuoteStatus::AwaitingApproval));
        $this->step($margaret, 29, 'http', fn () => $transition->execute($offices, QuoteStatus::Approved));
        $this->step($margaret, 28, 'http', fn () => $transition->execute($offices, QuoteStatus::Sent));
        $this->step($margaret, 21, 'http', fn () => $transition->execute($offices, QuoteStatus::Accepted));
        $officesOrder = $this->step($margaret, 21, 'http', fn (): CartModel => $convert->execute($offices, 'order'));
        $this->step($margaret, 21, 'http', fn () => $share->execute($officesOrder, $alan, 'buyer'));
        $this->step($margaret, 21, 'http', fn () => $visibility->execute($officesOrder, Visibility::Boundary));

        // Declined by the customer.
        $gym = $this->quote($ada, 25, $sales, 'Downtown gym lockers', ['customer' => 'Downtown Gym'], [['KP-210', 60]]);
        $this->step($ada, 25, 'http', fn () => $transition->execute($gym, QuoteStatus::AwaitingApproval));
        $this->step($admin, 24, 'http', fn () => $transition->execute($gym, QuoteStatus::Approved));
        $this->step($ada, 24, 'http', fn () => $transition->execute($gym, QuoteStatus::Sent));
        $this->step($ada, 18, 'http', fn () => $transition->execute($gym, QuoteStatus::Declined));

        // Sent and never answered, so it has expired.
        $hotel = $this->quote($grace, 75, $sales, 'Harbour hotel refit', ['customer' => 'Harbour Hotel'], [['SL-399', 80], ['DS-050', 80]]);
        $this->step($grace, 74, 'http', fn () => $transition->execute($hotel, QuoteStatus::AwaitingApproval));
        $this->step($ada, 74, 'http', fn () => $transition->execute($hotel, QuoteStatus::Approved));
        $this->step($grace, 73, 'http', fn () => $transition->execute($hotel, QuoteStatus::Sent));

        // Accepted and retyped in place as an order, which was refunded.
        $kiosk = $this->quote($linus, 50, $operations, 'Visitor kiosk doors', ['customer' => 'Acme visitor centre'], [['EX-900', 2], ['CR-480', 2]]);
        $this->step($linus, 49, 'http', fn () => $transition->execute($kiosk, QuoteStatus::AwaitingApproval));
        $this->step($ada, 49, 'http', fn () => $transition->execute($kiosk, QuoteStatus::Approved));
        $this->step($linus, 48, 'http', fn () => $transition->execute($kiosk, QuoteStatus::Sent));
        $this->step($linus, 46, 'http', fn () => $transition->execute($kiosk, QuoteStatus::Accepted));
        $kioskOrder = $this->step($linus, 46, 'http', fn (): CartModel => $convert->execute($kiosk, 'order', copy: false));
        $this->step($linus, 45, 'http', fn () => $transition->execute($kioskOrder, OrderStatus::Paid));
        $this->step($admin, 31, 'http', fn () => $transition->execute($kioskOrder, OrderStatus::Refunded));

        // An order cancelled before payment.
        $garage = $this->step($ken, 16, 'http', fn (): CartModel => $create->execute('order', $ken, ['label' => 'Garage side door']));
        $this->lines($ken, 16, 'http', $garage, [['LV-100', 1], ['DC-400', 1]]);
        $this->step($ken, 15, 'http', fn () => $transition->execute($garage, OrderStatus::Cancelled));

        // Wishlists: one shared with the team, one open to the whole
        // organization, one private, and one that became a cart.
        $wishes = $this->step($grace, 20, 'http', fn (): CartModel => $create->execute('wishlist', $grace, ['label' => 'Showroom wishlist'], $sales));
        $this->lines($grace, 20, 'http', $wishes, [['SL-399', 2], ['CR-480', 1], ['LV-100', 4, ['finish' => 'brass']]]);
        $this->step($grace, 19, 'http', fn () => $share->execute($wishes, $sales, 'viewer'));

        $standards = $this->step($margaret, 12, 'http', fn (): CartModel => $create->execute('wishlist', $margaret, [
            'label' => 'Globex approved hardware',
            'meta' => ['department' => 'Facilities'],
        ], $facilities));
        $this->lines($margaret, 12, 'http', $standards, [['LV-100', 1], ['CY-030', 1], ['DC-400', 1], ['EX-900', 1]]);
        $this->step($margaret, 12, 'http', fn () => $visibility->execute($standards, Visibility::Boundary));

        $someday = $this->step($ken, 6, 'mcp', fn (): CartModel => $create->execute('wishlist', $ken, ['label' => 'Someday']));
        $this->lines($ken, 6, 'mcp', $someday, [['CR-480', 1], ['SL-399', 2]]);

        $lab = $this->step($alan, 8, 'http', fn (): CartModel => $create->execute('wishlist', $alan, ['label' => 'Lab upgrades'], $purchasing));
        $this->lines($alan, 8, 'http', $lab, [['KP-210', 4], ['SL-399', 1]]);
        $this->step($alan, 2, 'http', fn (): CartModel => $convert->execute($lab, 'cart'));
    }

    private function user(string $name, string $email): User
    {
        return UserFactory::new()->create(['name' => $name, 'email' => $email]);
    }

    /**
     * @param  array<int, User>  $users
     */
    private function team(Organization $organization, string $name, array $users): Team
    {
        $team = $organization->teams()->create(['name' => $name]);
        $team->users()->attach(array_map(fn (User $user): int => $user->id, $users));

        return $team;
    }

    private function catalogue(): void
    {
        $products = [
            ['LV-100', 'Lever handle set', 8900],
            ['ML-245', 'Mortise lock', 24500],
            ['DC-400', 'Door closer', 18900],
            ['EX-900', 'Panic exit device', 61200],
            ['KP-210', 'Keypad lock', 32900],
            ['HG-020', 'Ball-bearing hinge (pair)', 2400],
            ['CY-030', 'Euro cylinder', 5800],
            ['SL-399', 'Smart deadbolt', 39900],
            ['DS-050', 'Floor door stop', 1200],
            ['CR-480', 'Access card reader', 48000],
            // Priced on request, so a quote has to name its price.
            ['EP-000', 'Engraved door plate', null],
        ];

        foreach ($products as [$sku, $name, $price]) {
            $this->products[$sku] = Product::query()->create(['sku' => $sku, 'name' => $name, 'price' => $price]);
        }
    }

    /**
     * A quote started in a team, with its first lines.
     *
     * @param  array<string, mixed>  $meta
     * @param  array<int, array{0: string, 1: int, 2?: array<string, mixed>}>  $lines
     */
    private function quote(User $owner, int $daysAgo, Team $team, string $label, array $meta, array $lines): CartModel
    {
        $quote = $this->step($owner, $daysAgo, 'http', fn (): CartModel => app(CreateCartAction::class)->execute('quote', $owner, [
            'label' => $label,
            'meta' => $meta,
        ], $team));

        $this->lines($owner, $daysAgo, 'http', $quote, $lines);

        return $quote;
    }

    /**
     * Catalogue lines, by SKU, priced by the product.
     *
     * @param  array<int, array{0: string, 1: int, 2?: array<string, mixed>}>  $lines
     */
    private function lines(?User $actor, int $daysAgo, string $source, CartModel $cart, array $lines): void
    {
        $this->step($actor, $daysAgo, $source, fn () => app(AddLinesAction::class)->execute($cart, array_map(fn (array $line): array => [
            'purchasable' => $this->products[$line[0]],
            'quantity' => $line[1],
            'options' => $line[2] ?? [],
        ], $lines)));
    }

    /**
     * A line priced by hand: a service with no product, or a product priced
     * on request.
     */
    private function custom(User $actor, int $daysAgo, CartModel $cart, string $description, int $unitPrice, int $quantity = 1, ?Product $product = null): void
    {
        $this->step($actor, $daysAgo, 'http', fn () => app(AddLinesAction::class)->execute($cart, [[
            'purchasable' => $product,
            'quantity' => $quantity,
            'meta' => ['description' => $description],
            'unit_price' => $unitPrice,
        ]]));
    }

    /**
     * Run one step as a user (or a guest), from a source, some days ago.
     * Steps on the same day happen in the order they are taken.
     *
     * @template TResult
     *
     * @param  Closure(): TResult  $callback
     * @return TResult
     */
    private function step(?User $actor, int $daysAgo, string $source, Closure $callback): mixed
    {
        $step = $this->clock[$daysAgo] = ($this->clock[$daysAgo] ?? 0) + 1;

        Carbon::setTestNow(Carbon::now()->subDays($daysAgo)->subHours(6)->addMinutes(11 * $step));

        if ($actor === null) {
            Auth::forgetUser();
        } else {
            Auth::setUser($actor);
        }

        try {
            return Polycart::usingSource($source, $callback);
        } finally {
            Carbon::setTestNow();
        }
    }
}
