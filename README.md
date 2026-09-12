# Empire King Burger

Shared WordPress and WooCommerce theme code for independent Empire King Burger installations.

## Architecture

This repository supplies the shared theme used by the independent Avenue H and Avenue I WordPress installations. Each location has its own WordPress install, WooCommerce store, database, cart/session, orders, payments, and operational tablet. The sites do not use WordPress Multisite or shared commerce state. Location identity is configured per installation in WordPress under Appearance → Customize → Empire King Location. On Avenue H, ordering routes locally to `/order-now/` with no H/I location selector.

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

Production code follows one promotion path: `main` deploys the shared `empire-king` theme and `empire-king-deals` plugin to both Avenue H and Avenue I. Only those source-controlled directories are deployed. Each site's database, uploads, WordPress configuration, WooCommerce data, customers, orders, payments, and local order routing remain independent and untouched.
