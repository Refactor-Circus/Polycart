# Scoping and sharing

Many applications organise people into a tree: a user belongs to one or more teams, and each team belongs to an organization. Polycart can place a cart inside that tree. Doing so:

- keeps the cart in its place for good
- keeps collaboration inside the organization
- lets a user who is on several teams have a separate cart in each

This guide covers:

- [Concepts](#concepts)
- [Connecting your tree](#connecting-your-tree)
- [Creating carts in a scope](#creating-carts-in-a-scope)
- [The lock](#the-lock)
- [Members and sharing](#members-and-sharing)
- [Visibility](#visibility)
- [Checking access](#checking-access)
- [Querying](#querying)
- [Over the JSON API and MCP](#over-the-json-api-and-mcp)
- [In the Atrium dashboard](#in-the-atrium-dashboard)
- [Events and exceptions](#events-and-exceptions)
- [Storage](#storage)
- [Unscoped carts](#unscoped-carts)
- [Performance notes](#performance-notes)

What each role may do is decided by the cart's type. That is covered in [Roles and permissions](roles-and-permissions.md).

## Concepts

| Term | Meaning | Example |
| --- | --- | --- |
| **Scope** | A level of your tree that can hold carts. | A team, an organization, a department |
| **Chain** | A scope and every level above it, nearest first. | Sales team → Acme organization |
| **Anchor** | The scope a cart was created in, which is depth 0 of its chain. | Sales team |
| **Boundary** | The top of the cart's chain. Sharing never crosses it. | Acme organization |
| **Participant** | Someone who works with carts and belongs to scopes. | A user |
| **Reach** | Every scope a participant belongs to directly, plus every level above them. | Sales, Ops, Acme |
| **Owner** | The creator of the cart, stored in `owner_type` and `owner_id`. | Ann |
| **Member** | A participant or a whole scope that the cart is shared with, together with a role. | Bob as `editor`, the Ops team as `viewer` |
| **Visibility** | Who can see the cart without being a member. | `private`, `scope`, `boundary` |

## Connecting your tree

Implement two interfaces. Polycart creates no users, teams or organizations; your application owns those models.

### `CartScope`: each level of the tree

```php
use RefactorCircus\Polycart\Domains\Scope\Contracts\CartScope;

class Organization extends Model implements CartScope
{
    public function parentCartScope(): ?CartScope
    {
        return null;                    // the top of the tree
    }
}

class Team extends Model implements CartScope
{
    public function parentCartScope(): ?CartScope
    {
        return $this->organization;     // one level up
    }
}
```

Trees can be any depth: department → division → organization, and so on. Polycart follows `parentCartScope()` until it returns `null`. It stops at 32 levels, or when it meets a scope it has already visited, so a cycle in your data cannot loop forever. Every scope must be an Eloquent model.

### `CartParticipant`: the people

```php
use RefactorCircus\Polycart\Domains\Cart\Concerns\HasCarts;
use RefactorCircus\Polycart\Domains\Scope\Contracts\CartParticipant;

class User extends Authenticatable implements CartParticipant
{
    use HasCarts;

    public function cartScopes(): iterable
    {
        return $this->teams;            // the scopes they belong to directly
    }
}
```

Return only the scopes the user belongs to *directly*. Polycart walks up from each one itself, so a user on the Sales and Ops teams reaches Sales, Ops and Acme.

`cartScopes()` is read once per request (or queued job) for each user, the first time their access is checked, so joining or leaving a team takes effect on the next request. Code that changes someone's teams and checks their access again in the same request calls `app(ScopeTree::class)->forget($user)` (`RefactorCircus\Polycart\Domains\Scope\Services\ScopeTree`; `flush()` forgets everyone). If you cache `cartScopes()` yourself, clear that cache when team membership changes.

### Registering aliases for the API and MCP

The JSON API and MCP tools only accept models you list, each under an alias:

```php
// config/polycart.php
'owners' => [
    'user' => App\Models\User::class,
],

'scopes' => [
    'team' => App\Models\Team::class,
    'organization' => App\Models\Organization::class,
],
```

Code that calls Polycart directly is not limited by these lists.

## Creating carts in a scope

Pass the scope when you create a cart or ask for an active one:

```php
use RefactorCircus\Polycart\Facades\Polycart;

$cart = Polycart::create('quote', $user, ['label' => 'Lobby'], scope: $salesTeam);

$cart = Polycart::active('cart', $user, $salesTeam);
$cart = $user->cart(scope: $salesTeam);          // the same, through HasCarts
```

On creation, Polycart:

1. records the whole chain (Sales at depth 0, Acme at depth 1)
2. stores the anchor (`scope_type`, `scope_id`) and the boundary (`boundary_type`, `boundary_id`) on the cart
3. sets visibility to the type's `defaultVisibility()`, which is `private` unless the type says otherwise
4. adds the owner as a member with the type's `creatorRole()`, if the owner is a model

### The owner must be in the scope

When the owner is a `CartParticipant`, the scope must be within the owner's reach. Otherwise an `InvalidScopeException` is thrown ("does not belong"). Reach includes the levels above the owner's teams, so a Sales member can also create a cart at the Acme level.

Owners that are not participants skip this check. That covers guests identified by a session key, owners that don't implement the interface, and carts with no owner at all.

### One active cart per scope

An active cart is identified by its type, its owner and its anchor. A user on two teams therefore has one current cart in each, plus one unscoped cart:

```php
$user->cart(scope: $sales);   // Sales cart
$user->cart(scope: $ops);     // a different cart
$user->cart();                // the unscoped cart
```

### Nested carts

Child carts, such as a project's sets and openings, live where their parent lives:

- **No scope given:** the child inherits its parent's anchor, boundary and chain.
- **Scope given:** it must share the parent's **boundary**. A different team in the same organization is allowed; another organization is not.
- **Scoped child, unscoped parent:** refused.

```php
$project = Polycart::create('project', $user, scope: $sales);
$set = Polycart::create('set', $user, ['parent_id' => $project->id]);   // inherits Sales → Acme
```

## The lock

Once a cart is saved, its place in the tree never changes:

- **No direct edits:** changing `scope_type`, `scope_id`, `boundary_type` or `boundary_id` throws `InvalidScopeException` ("locked"). So does calling `placeIn()` on a saved cart.
- **Copies keep their place:** a cart converted by copy (cart → quote) takes the source's anchor, boundary, chain and visibility, and its members come with it.
- **In-place conversion changes nothing:** a cart retyped in place keeps its place, because it is the same row.
- **Merging stays inside a scope:** lines move only between carts with the same anchor. An unscoped cart, such as a guest's, can be merged into any cart. A scoped cart cannot be merged into a cart in another scope.

## Members and sharing

A cart's members decide who can use it. There are two kinds:

- **A participant**, such as a user, holds the role personally.
- **A whole scope**, such as a team or an organization, gives the role to everyone who reaches that scope *at the time access is checked*. Sharing with the Ops team covers people who join Ops later and stops covering people who leave. Sharing with the organization itself covers everyone in it.

```php
Polycart::share($cart, $colleague, 'editor');    // one person
Polycart::share($cart, $opsTeam, 'viewer');      // everyone on Ops, now and later
Polycart::share($cart, $colleague, 'viewer');    // sharing again changes the role

Polycart::unshare($cart, $colleague);

$cart->members;                                  // CartMember models: member_type, member_id, role
```

### Rules

Sharing is refused with a `SharingException` when:

| Rule | Message |
| --- | --- |
| The type's `shareable()` returns false | `A [saved] cart cannot be shared.` |
| The role is not one of the type's `roles()` | `[buyer] is not a role of a [cart] cart.` |
| The cart has no scope, so there is no boundary to share within | `... is not in a scope ...` |
| The member does not reach the cart's boundary. This covers a user or team from another organization, and any model that is neither a participant nor a scope. | `... is outside ...` |
| The change would remove or demote the last member holding the type's top role | `... must keep at least one member with its top role.` |

The last rule means a cart always has an owner. To hand a cart over, share it with the new person as `owner` first, then remove or demote the old one.

### Roles when converting

- **Copy:** members are copied to the new cart, and the owner gets the target type's `creatorRole()`.
- **In place:** members stay on the cart.

Either way, a member whose role the target type does not have is moved to the target's weakest role (`visibilityRole()`). They keep read access rather than silently losing all access, or gaining more than they had. See [Roles and permissions](roles-and-permissions.md#converting-between-types).

## Visibility

Visibility widens access beyond members, but only inside the cart's own tree:

| Visibility | Who sees the cart without being a member |
| --- | --- |
| `private` (the default) | Nobody. Only members, starting with the creator. |
| `scope` | Everyone who reaches the cart's anchor. For a cart anchored at Sales, that is the Sales team. For a cart anchored at the organization, that is everyone in it. |
| `boundary` | Everyone who reaches the cart's boundary, i.e. the whole organization. |

People who see a cart only through its visibility get the type's `visibilityRole()`, which is its weakest role (`viewer` by default). If someone is also a member, they get the stronger of the two roles.

```php
use RefactorCircus\Polycart\Domains\Sharing\Enums\Visibility;

$cart->setVisibility(Visibility::Scope);
$cart->setVisibility('boundary');
$cart->setVisibility(Visibility::Private);
```

Setting `scope` or `boundary` is refused when the type is not shareable or the cart has no scope. Setting `private` is always allowed. A type can choose its default visibility with `defaultVisibility()`.

## Checking access

Someone's role on a cart is the **strongest** of:

1. their own membership
2. the membership of any scope they reach, such as their team being shared in
3. the type's `visibilityRole()`, when the cart's visibility covers a scope they reach

"Strongest" means earliest in the type's `roles()` list. Checks happen when they are made; nothing is cached on the cart.

```php
$cart->roleFor($user);                   // 'editor', or null for no access
$cart->allows($user, 'update');          // does their role grant this ability?

$user->can('view', $cart);               // through the policy in polycart.policies
$user->can('checkout', $cart);           // any ability the type defines
$user->can('transition', [$cart, 'processing']);
$user->can('convert', [$cart, 'order']);
```

Domain calls are **not** permission-checked. This includes `$cart->add()`, `Polycart::share()` and `$cart->convertTo()`. They trust the code calling them, just as Eloquent does. Check access in your controllers with the policy, or let the JSON API and MCP tools do it for you.

## Querying

```php
use RefactorCircus\Polycart\Domains\Cart\Models\CartModel;

$user->accessibleCarts()->ofType('quote')->get();     // everything this user can see
CartModel::query()->accessibleBy($user)->get();            // the same, from the model

CartModel::query()->inScope($acme)->get();                 // every cart anywhere under Acme
CartModel::query()->inScope($sales)->get();                // Sales carts only

$cart->anchor;       // the scope it lives in (the Team model)
$cart->boundary;     // the top of its tree (the Organization model)
$cart->paths;        // CartPath rows: scope_type, scope_id, depth
$cart->members;      // CartMember rows
$cart->isScoped();
$cart->sharesScopeWith($otherCart);
```

`accessibleBy()` applies the same rules as `roleFor()`: direct membership, scope membership, and visibility.

## Over the JSON API and MCP

With `polycart.authorization` on, which is the default, every call acts as the authenticated user:

- **Unauthenticated calls** are refused: `401` over HTTP, `Unauthorized.` over MCP.
- **Listing** returns only carts the user can access.
- **Creating carts:** `POST /carts` and `POST /carts/active` always make the user the owner. Any `owner_type`, `owner_id` or `session_key` in the request is ignored. The user must belong to the given scope.
- **Every change** goes through the Gate, using the ability listed in [Roles and permissions](roles-and-permissions.md#what-the-api-and-mcp-check).

| HTTP | MCP tool | Ability needed |
| --- | --- | --- |
| `GET /carts/{cart}/members` | `list-members` | `view` |
| `POST /carts/{cart}/members` `{member_type, member_id, role}` | `share-cart` | `share` |
| `DELETE /carts/{cart}/members/{member}` | `unshare-cart` `{cart, member}` | `share` |
| `PUT /carts/{cart}/visibility` `{visibility}` | `set-visibility` | `share` |

`member_type` is an alias from `polycart.owners` (a person) or `polycart.scopes` (a team or organization). Carts can be created with `scope_type` and `scope_id`, and listed with those two filters to get everything under a scope. Cart responses include `scope_type`, `scope_id`, `boundary_type`, `boundary_id`, `visibility`, and, on `show`, `members`.

Turn `polycart.authorization` off only for trusted server-to-server callers. Route middleware is then the only check, and callers may name any owner from `polycart.owners`.

## In the Atrium dashboard

The cart page shows the cart's chain, such as `Team #3 › Organization #1`, and a **Members** panel where you can:

- change visibility
- add a person or team with one of the type's roles
- remove a member

Refusals such as "outside" or "last owner" show as errors on the page. With `polycart.authorization` on, the dashboard applies the same per-user policies as the JSON API: the visibility control needs `share` on the cart, adding a member `create` on `CartMemberModel`, and removing one `delete` on that member. A control the signed-in user may not use is not shown, and the cart list holds only the carts they can access. Operators (`polycart.atrium.show_all`) see every cart and every control.

## Events and exceptions

| Event | Fired when | Carries |
| --- | --- | --- |
| `CartSharingActionEvent` / `CartSharedActionEvent` | A member is added, or their role changes | start: `cart`, `member` (the model), `role`; finish: `cart`, `member` (the `CartMemberModel`) |
| `CartUnsharingActionEvent` / `CartUnsharedActionEvent` | A member is removed | start: `cart`, `member`; finish: `cart`, `memberType`, `memberId` |
| `VisibilityChangingActionEvent` / `VisibilityChangedActionEvent` | Visibility changes | start: `cart`, `to`; finish: `cart`, `from`, `to` |

| Exception | Named constructor | Meaning |
| --- | --- | --- |
| `InvalidScopeException` | `notAScope` | The scope model does not implement `CartScope` |
| | `outside` | The owner does not belong to the scope |
| | `locked` | Something tried to move a saved cart |
| | `differentTree` | A child is in another boundary from its parent, or a merge would cross scopes |
| `SharingException` | `notShareable`, `unknownRole`, `unscoped`, `outsideBoundary`, `lastOwner` | See [Rules](#rules) |

Both extend `PolycartException`, so JSON callers get `422` with the message.

## Storage

| Table | Holds |
| --- | --- |
| `polycart_carts` | `scope_type`, `scope_id` (anchor), `boundary_type`, `boundary_id`, `visibility`, and `source` (the surface the cart was created through; see [Sources and history](sources-and-history.md)) |
| `polycart_cart_paths` | One row per level of each cart's chain: `cart_id`, `scope_type`, `scope_id`, `depth`. Written once, never changed. |
| `polycart_cart_members` | `cart_id`, `member_type`, `member_id`, `role`. Unique per cart and member. |

These are the default names; `polycart.tables` renames any of them (`carts`, `cart_paths`, `cart_members`, `cart_lines`) before migrating.

Morph types are stored with `getMorphClass()`, so they follow your morph map.

## Unscoped carts

Carts without a scope still work: guest carts, or apps with no teams. They have no chain, no boundary, and cannot be shared or opened to a team or organization. Only their creator can use them. Nothing about scoping is required; leave `scope` out and Polycart behaves as it did before.

## Performance notes

- The first access check in a request calls `cartScopes()` on the participant and walks up each scope's chain; later checks in the same request reuse the answer. With eager-loaded parents, as in `$this->teams()->with('organization')->get()`, that is one query.
- `accessibleBy()` adds one condition per scope the user reaches. That is fine for a normal number of teams; if some users sit in hundreds, consider caching `cartScopes()`.
- `inScope()` uses the indexed `polycart_cart_paths` table, so querying a whole organization does not depend on how deep its tree is.
