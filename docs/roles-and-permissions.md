# Roles and permissions

Each **cart type** decides who can do what with its carts. A shopping cart might only need owners, editors and viewers. An order might need a separate *buyer* who can check out, and a quote might need an *approver*. Roles are not global: each type lists its own.

For how people become members, and how teams and organizations fit in, see [Scoping and sharing](scoping-and-sharing.md).

- [Roles and abilities](#roles-and-abilities)
- [The defaults](#the-defaults)
- [Defining roles for a type](#defining-roles-for-a-type)
- [Custom abilities](#custom-abilities)
- [Guarding status moves](#guarding-status-moves)
- [Guarding conversions](#guarding-conversions)
- [The creator, visibility and the top role](#the-creator-visibility-and-the-top-role)
- [Converting between types](#converting-between-types)
- [Checking permissions](#checking-permissions)
- [What the API and MCP check](#what-the-api-and-mcp-check)
- [Policies](#policies)
- [Examples](#examples)

## Roles and abilities

- An **ability** is something someone can do to a cart, such as `view`, `update` or `checkout`.
- A **role** is a named set of abilities, such as `editor` = `view` + `update`.
- A **member** holds one role on one cart.

A type declares its roles in `roles()`, **strongest first**, each with the abilities it grants. `*` grants every ability.

## The defaults

A type that does not override `roles()` gets:

```php
public function roles(): array
{
    return [
        'owner' => ['*'],
        'editor' => ['view', 'update'],
        'viewer' => ['view'],
    ];
}
```

The package itself checks six abilities:

| Ability | Covers |
| --- | --- |
| `view` | Reading the cart, its lines and its members |
| `update` | Changing the label or meta, adding, changing or removing lines, clearing the cart, merging into or out of it, and nesting a new cart under it |
| `delete` | Deleting the cart |
| `share` | Adding, changing or removing members, and changing visibility |
| `transition` | Moving between statuses. The default, which a type can override per move. |
| `convert` | Converting into another type. The default, which a type can override per target. |

## Defining roles for a type

Override `roles()` on the type. Only the carts of that type are affected.

```php
use RefactorCircus\Polycart\Domains\CartType\Support\CartType;

class OrderCart extends CartType
{
    public function roles(): array
    {
        return [
            'owner' => ['*'],
            'buyer' => ['view', 'update', 'checkout'],
            'editor' => ['view', 'update'],
            'viewer' => ['view'],
        ];
    }
}
```

Rules for the list:

- **Order matters.** The first role is the strongest. When someone qualifies for several roles, for example through their own membership, their team's membership and visibility, they get whichever comes first. Two further defaults also follow from the order:
  - the first role is what the creator gets (`creatorRole()`)
  - the last role is what visibility grants (`visibilityRole()`)
- **Names are yours.** Any string works. Sharing with a role the type does not list is refused (`[buyer] is not a role of a [cart] cart.`).
- **Keep the top role powerful.** A cart must always keep at least one member with its top role, so that role should usually be able to `share`.

To check a single role and ability:

```php
$type->grants('buyer', 'checkout');   // true
```

## Custom abilities

Add any ability name to a role, and it works everywhere abilities are checked:

```php
$cart->allows($user, 'checkout');
$user->can('checkout', $cart);                // through CartPolicy
Gate::forUser($user)->allows('checkout', $cart);
```

You don't have to write a policy method for it. The bundled `CartPolicy` answers any ability it has no method for by looking it up in the type's roles. The cart's owner passes every ability.

This is how types without a checkout differ from types with one. A quote type simply has no `checkout` ability anywhere, so `can('checkout', $quote)` is always false.

## Guarding status moves

By default, every status change needs `transition`. Override `transitionAbility()` to guard particular moves more strictly:

```php
use BackedEnum;

class OrderCart extends CartType
{
    public function transitionAbility(?BackedEnum $from, BackedEnum $to): string
    {
        return match ($to) {
            OrderStatus::Processing => 'checkout',   // placing the order
            OrderStatus::Cancelled => 'cancel',
            default => 'transition',
        };
    }
}
```

With the roles above:

- only `owner` (through `*`) and `buyer` can move an order to processing
- only `owner` can cancel, because nobody else holds `cancel`

Check a move directly:

```php
$user->can('transition', [$order, 'processing']);
```

The API's `POST /carts/{cart}/status` and the MCP `transition-cart` tool both use this. A status the type does not have falls back to `transition`, and the action then refuses it as an unknown status.

## Guarding conversions

Every conversion needs `convert` unless `conversionAbility()` names another ability for that target:

```php
public function conversionAbility(string $to): string
{
    return $to === 'order' ? 'checkout' : 'convert';
}
```

```php
$user->can('convert', [$cart, 'order']);
```

The source type decides this, because it is the cart being converted.

## The creator, visibility and the top role

| Hook | Default | Meaning |
| --- | --- | --- |
| `creatorRole()` | The first role | Role the owner gets when the cart is created |
| `visibilityRole()` | The last role | Role given to people who only see the cart through `scope` or `boundary` visibility |
| `defaultVisibility()` | `Visibility::Private` | Visibility of new carts |
| `shareable()` | `true` | Whether the type can be shared or opened up at all |

```php
class QuoteCart extends CartType
{
    public function roles(): array
    {
        return [
            'owner' => ['*'],
            'approver' => ['view', 'transition'],
            'contributor' => ['view', 'update'],
            'reader' => ['view'],
        ];
    }

    // Everyone in the team can read new quotes.
    public function defaultVisibility(): Visibility
    {
        return Visibility::Scope;
    }
}

class SavedCartType extends CartType
{
    // Personal lists are never shared.
    public function shareable(): bool
    {
        return false;
    }
}
```

## Converting between types

Different types can have different roles, so members are mapped when a cart changes type. This happens both when the cart is copied and when it is retyped in place:

- **The creator** gets the target type's `creatorRole()` on a copy.
- **A member whose role the target also has** keeps it.
- **A member whose role the target lacks** gets the target's `visibilityRole()`, its weakest role. They keep read access rather than silently losing everything, and they never gain more than before.

Example: an order has a `buyer`; the order is reopened as a plain cart, which has no `buyer` role. The buyer becomes a `viewer` of the cart. To give them more, share again with a role the cart type has.

## Checking permissions

| Call | Returns |
| --- | --- |
| `$cart->roleFor($user)` | The user's strongest role, or `null` |
| `$cart->allows($user, 'update')` | Whether that role grants the ability |
| `$user->can('update', $cart)` | The same, through the Gate |
| `$user->can('transition', [$cart, 'processing'])` | Uses `transitionAbility()` |
| `$user->can('convert', [$cart, 'order'])` | Uses `conversionAbility()` |
| `$cart->cartType()->grants('editor', 'update')` | Whether a role grants an ability, whoever holds it |

Domain calls such as `$cart->add()`, `$cart->transitionTo()` and `Polycart::share()` do not check permissions. Check first in your own controllers:

```php
public function checkout(Request $request, CartModel $order)
{
    $this->authorize('transition', [$order, OrderStatus::Processing->value]);

    $order->transitionTo(OrderStatus::Processing);
}
```

## What the API and MCP check

With `polycart.authorization` on, which is the default, each call is checked against the policy `polycart.policies` gives the model it touches:

| Operation | HTTP | MCP | Ability |
| --- | --- | --- | --- |
| List types | `GET /types` | `list-cart-types` | `viewAny` on `CartModel` |
| List carts | `GET /carts` | `list-carts` | `viewAny` on `CartModel`. Only accessible carts are returned. |
| Create or get active cart | `POST /carts`, `POST /carts/active` | `create-cart`, `active-cart` | `create` on `CartModel`. The user becomes the owner. `update` on the parent when `parent_id` is given. |
| Show a cart | `GET /carts/{cart}` | `show-cart` | `view` |
| Update, clear | `PATCH /carts/{cart}`, `POST .../clear` | `update-cart`, `clear-cart` | `update` |
| Lines | `POST/PATCH/DELETE .../lines` | `add-lines`, `update-lines`, `remove-lines` | `create` on `CartLineModel` for the cart, then `update` or `delete` on each named line |
| Delete | `DELETE /carts/{cart}` | `delete-cart` | `delete` |
| Change status | `POST .../status` | `transition-cart` | `transitionAbility($from, $to)` |
| Convert | `POST .../convert` | `convert-cart` | `conversionAbility($to)` |
| Merge | `POST .../merge` | `merge-carts` | `update` on **both** carts |
| Members | `.../members` | `list-members`, `share-cart`, `unshare-cart` | `viewAny` and `create` on `CartMemberModel` for the cart, `delete` on the member |
| History | `GET /history?subject_type=...&subject_id=...` | `list-polycart-history-tool` | `view` on the cart; Polycart's whole history needs the `viewAuditLog` Gate ability when defined |
| Visibility | `.../visibility` | `set-visibility` | `share` |

A failed check returns `403` over HTTP (`401` for a caller who is not signed in) and `Unauthorized.` over MCP. Turn `polycart.authorization` off only for trusted server-to-server callers.

The Atrium dashboard asks the same questions, with one exception: users made operators by `polycart.atrium.show_all` (`true`, or a Gate ability's name) see every cart there and may take every action on it. The API and MCP never treat anyone as an operator.

## Policies

Polycart registers a policy for each of its models from `polycart.policies`:

```php
'policies' => [
    CartModel::class => \RefactorCircus\Polycart\Domains\Cart\Policies\CartPolicy::class,
    CartLineModel::class => \RefactorCircus\Polycart\Domains\CartLine\Policies\CartLinePolicy::class,
    CartMemberModel::class => \RefactorCircus\Polycart\Domains\Sharing\Policies\CartMemberPolicy::class,
    CartPathModel::class => \RefactorCircus\Polycart\Domains\Scope\Policies\CartPathPolicy::class,
],
```

What the bundled policies allow:

- **`CartPolicy`**: a cart's owner may do anything with it. Everyone else gets what their role on the cart grants. Anyone signed in passes `viewAny` and `create`. The `CartModel` entry also covers every type's subclass.
- **`CartLinePolicy`**: reading lines needs `view` on the cart. Adding, changing or removing them needs `update`.
- **`CartMemberPolicy`**: listing members needs `view` on the cart. Sharing, changing a role or unsharing needs `share`.
- **`CartPathPolicy`**: reading a cart's tree paths needs `view` on the cart. Paths are written by the cart when it is created, so creating, changing or removing one is never allowed.

The line, member and path policies ask the Gate about the cart, so they follow whichever cart policy is registered.

### Replacing a policy

- **Per ability:** extend a bundled policy and add or override the method for that ability. Point its model at your class in `polycart.policies`.
- **Whole policy:** point the model at your own class:

  ```php
  'policies' => [
      CartModel::class => App\Policies\CartPolicy::class,
      // ...
  ],
  ```

- **For one typed model:** Laravel's policy discovery finds `App\Policies\QuotePolicy` for `App\Models\Quote` before falling back to the base policy.

The API and MCP check every call through the Gate, so a replaced policy applies there too.

## Examples

### Collaborative shopping list

```php
class TeamCart extends CartType
{
    public function roles(): array
    {
        return [
            'owner' => ['*'],
            'editor' => ['view', 'update'],
        ];
    }

    public function defaultVisibility(): Visibility
    {
        return Visibility::Scope;   // every teammate is an editor at once
    }
}
```

Because `editor` is the last role, it is the visibility role: everyone on the team can add lines without being shared in individually.

### Order with a separate buyer and approver

```php
class PurchaseOrder extends CartType
{
    public function statuses(): string
    {
        return PurchaseOrderStatus::class;   // Draft, Submitted, Approved, Placed
    }

    public function roles(): array
    {
        return [
            'owner' => ['*'],
            'approver' => ['view', 'approve'],
            'requester' => ['view', 'update', 'submit'],
            'viewer' => ['view'],
        ];
    }

    public function transitionAbility(?BackedEnum $from, BackedEnum $to): string
    {
        return match ($to) {
            PurchaseOrderStatus::Submitted => 'submit',
            PurchaseOrderStatus::Approved => 'approve',
            PurchaseOrderStatus::Placed => 'checkout',     // owner only, through '*'
            default => 'transition',
        };
    }
}

Polycart::share($order, $financeTeam, 'approver');
Polycart::share($order, $requester, 'requester');
```

- requesters submit
- anyone on the finance team approves, including whoever joins it later
- only the owner places the order

### Private saved list

```php
class Wishlist extends CartType
{
    public function shareable(): bool
    {
        return false;
    }
}
```

Only its creator can use it. Sharing it or opening it to a team is refused.
