# One-time approved menu migration

This exports the approved local WooCommerce catalog and imports that data into an existing WordPress/WooCommerce installation. It does not deploy a theme, activate/deactivate plugins, migrate a database, or connect to production. Only run the importer later on the intended installation, after reviewing its dry run and taking a normal database/uploads backup. Apply is not atomic: if it stops after a write, fix the reported issue and rerun, or restore that backup.

## Build locally (PowerShell, from the repository root)

The CLI container does not mount `tools/`; copy these two scripts into a fresh temporary directory inside the **local** wp-env CLI container. Find its name with `docker ps` (use the `cli-1` container, not `tests-cli-1`). Substitute that name below.

```powershell
docker exec <local-cli-container> mkdir -p /tmp/ek-menu-migration
docker cp tools/production-migration/export-local.php <local-cli-container>:/tmp/ek-menu-migration/export-local.php
docker cp tools/production-migration/import-production.php <local-cli-container>:/tmp/ek-menu-migration/import-production.php
npm run wp -- --require=/tmp/ek-menu-migration/export-local.php ek-menu-export --output=/tmp/production-migration-output
docker cp <local-cli-container>:/tmp/production-migration-output ./production-migration-output
tar -czf production-migration-output/empireking-menu-migration.tar.gz -C production-migration-output catalog.json media import-production.php
Get-FileHash production-migration-output/empireking-menu-migration.tar.gz -Algorithm SHA256
```

The exporter requires a local WordPress environment and an absent or empty output folder. On reruns choose a fresh temporary output path. The generated output folder is ignored by Git. Export verifies 117 published parents, 73 published variations, zero Sold Individually, 33 Featured products, 13 Deals, the three approved sentinel products, unambiguous keys and SHA-256 hashes for every referenced image. No source product or Deal is edited.

## Import commands (for the later migration operator)

Extract the archive in a private directory outside the public web root. With WordPress available to WP-CLI:

```sh
wp --path=/path/to/wordpress --require=/private/menu/import-production.php ek-menu-import
wp --path=/path/to/wordpress --require=/private/menu/import-production.php ek-menu-import --dry-run
wp --path=/path/to/wordpress --require=/private/menu/import-production.php ek-menu-import --apply
```

Default package location is beside the importer; override with `--package=/private/menu`. No option means dry run. `--apply` is the only write mode. Do not pass both modes. Run with the compatible APF plugin active. The dry run lists matches, new products, retired products, variation updates/creates/retirements, Deal mappings and blockers. Resolve every blocker before apply. Product/variation SKU collisions and ambiguous matches abort before writes.

## What is transferred

- Parent identity matches by unique nonempty SKU first, then unique normalized exact name if no SKU match exists. Normalization decodes HTML entities, strips markup, folds case and collapses whitespace; it does not perform fuzzy matching. Numeric local IDs never match production records. A safely matched production parent retains its ID even when its product type changes.
- Local product properties replace the corresponding destination configuration, including type, prices, sale dates, descriptions, SKU, slug, categories/tags, featured/catalog/stock state, Sold Individually, product attributes/defaults, related-product references and images. Sales totals, reviews and unrelated plugin metadata are not copied or cleared.
- Variation IDs are kept only for one unambiguous exact attribute map under the matched parent. New combinations are created. Obsolete variations become draft (excluded from WooCommerce's publish/private child lookup), with their IDs/history retained. Banana Split becomes simple at 6.75 and any obsolete variations are made draft. Old published parent products outside this package become draft and hidden, and their active variations become draft, never deleted. Old published Deals outside the 13 source slugs also become draft and are listed in the dry run.
- APF 1.7.1 was inspected locally: 101 products store `_wapf_fieldgroup`, with `id: p_<product ID>` and `rule_groups[].rules[].value[].id` product references. Export replaces those references with stable keys; import regenerates production IDs. Field/choice identifiers and modifier configuration remain intact. The single APF metadata key is replaced or removed according to local state; unrelated metadata is untouched. Published global APF groups cause a blocker because merging those rules would change the approved per-product configuration.
- Attributes are product-specific in this approved catalog. Unknown global attributes, product types, downloads, shipping classes, brands or APF structures cause export to stop instead of exporting unhandled ID relationships.
- Only featured/gallery/variation/Deal image files referenced by these records are bundled. Attachments are deduplicated by file SHA-256 and tagged `_ekm_media_sha256` in production for reuse on subsequent runs. Unrelated Media Library files are retained.
- Deals match by exact unique slug. Their `_ek_deal` data, category, artwork and stable product relationships are imported. The exact root `/deals/` page is updated or created without duplicating it. This is a data migration: rendering that page and editing Deals still require the separate Deals plugin/theme functionality. GrandRestaurant remains active; this tool does not deploy or activate Empire King. If the Deal post type/taxonomy is absent, it is registered only in the import process so the records can be saved for later plugin deployment.

Orders/HPOS/order items, users/customers, payment tokens, gateway and global Woo settings, tax/shipping configuration, WordPress configuration, domains and notification settings are outside the importer. Do not run broad database search/replace or use local IDs as production IDs. Keep the installation quiet during apply so catalog edits cannot race its preflight plan; check the resulting menu before reopening ordering.
