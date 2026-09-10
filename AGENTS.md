# Empire King repository instructions

## Read first

Read `package.json`, `.wp-env.json`, `.gitignore`, `README.md`, and `PROJECT-UPDATES.md` before making changes. Treat the root PHP site and `assets/` as protected legacy reference material.

## Git workflow

- `dev/jorge` is the active developer branch; `live-dev` is the shared integration branch; `main` is stable.
- Git Live Sync Watcher manages ordinary checkpoints on `dev/jorge`. Do not manually commit, push, merge, rebase, reset, change branches, or modify remotes.

## Local WordPress

Use `npm run env:start`, `npm run env:stop`, `npm run env:status`, and `npm run wp -- <wp-cli arguments>`. The local site is `http://localhost:8888`.

## Guardrails

- Do not modify, move, delete, rename, or reorganize `index.php`, `contact.php`, `order.php`, `visit.php`, `includes/`, or `assets/`.
- Do not edit WordPress core or WooCommerce plugin/core files.
- Never add credentials or secrets to the repository.
- Catering is out of scope: do not add catering code, content, placeholders, or recommendations.
- Do not invent business information such as domains, hours, contact details, addresses, prices, deals, payment gateways, or integrations.

## Architecture

This repository is the Avenue H site; Avenue I is a separate WordPress/WooCommerce installation with no shared cart, session, order, or payment routing. `wp-content/themes/empire-king` is the reusable theme, and Avenue H order links remain local to `/order-now/` with no H/I selector. WooCommerce owns product, cart, and checkout behavior. Business logic that must survive a theme change belongs in a dedicated plugin, not theme code.
