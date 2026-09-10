# Empire King Burger

Production-oriented WordPress and WooCommerce site for Empire King Burger — Avenue H.

## Architecture

This repository is the Avenue H site at `empireking3aveh.com`. Avenue I runs independently at `empireking2avei.com`; each location has its own WordPress install, WooCommerce store, database, cart/session, orders, payments, and operational tablet. The sites do not use WordPress Multisite or shared commerce state. On Avenue H, ordering routes locally to `/order-now/` with no H/I location selector.

The reusable classic theme lives at `wp-content/themes/empire-king`; WooCommerce owns product, cart, and checkout behavior. Durable business logic belongs in a plugin when needed.

## Local development

Prerequisites: Node.js 22, npm, Docker Desktop, and the WordPress environment dependencies used by `@wordpress/env`.

```sh
npm ci
npm run env:start
```

- Site: http://localhost:8888
- Start: `npm run env:start`
- Stop: `npm run env:stop`
- Status: `npm run env:status`
- WP-CLI: `npm run wp -- <command>`
- Activate theme: `npm run theme:activate`

wp-env runs WordPress 7.1, PHP 8.3, and WooCommerce 11.1.0 locally. The custom theme is mapped through `.wp-env.json`.

## Workflow and scope

Work on `dev/jorge`; Git Live Sync Watcher manages ordinary checkpoint commits and pushes. Do not manually commit, push, merge, rebase, reset, switch branches, or change remotes.

The root legacy files (`index.php`, `contact.php`, `order.php`, `visit.php`, `includes/`, and `assets/`) are protected reference code. Catering is explicitly out of scope.

The current implementation is the **Avenue H WordPress/WooCommerce site**. Avenue H local-business content and order links remain local to this installation; Avenue I is maintained as a separate site.
