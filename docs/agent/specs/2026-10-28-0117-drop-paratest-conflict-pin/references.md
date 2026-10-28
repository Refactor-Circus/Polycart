# References

## Keystone PR #8 (sibling package `../Keystone`)

- Location: Refactor-Circus/Keystone, PR #8; checkout at `~/Projects/packages/Keystone`.
- Relevance: the same change in the suite's base package.
- Pattern to borrow: remove the `conflict` entry, require `pestphp/pest ^5.3.4`, update pest and paratest only.

## Polycart `composer.json`

- Location: `composer.json:30` (pest constraint), `composer.json:162-164` (conflict block).
- Relevance: the two places to edit.
