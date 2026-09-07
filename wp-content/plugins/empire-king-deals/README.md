# Empire King Deals

Private Deal records and categories belong to this plugin. The theme renders the one public `/deals/` Page. Production provisioning requires plugin activation and one published WordPress Page with slug `deals`; no page, categories, or offers are automatically seeded.

An administrator can use the nonce-protected **Claim Deals Management** notice once. Only the claimed user receives normal Deals management access; no Administrator role capabilities are added. This does not restrict server/database administrators. Ownership starts unclaimed.

Recovery (authorized server administrator): `wp option delete empire_king_deals_owner_user_id` enables a new claim. Alternatively `wp option update empire_king_deals_owner_user_id USER_ID` transfers ownership to a verified existing account. No identity is hardcoded.

Use Featured Image for approved Media Library artwork. A deal has zero or one category. Display Order sorts ascending with ID as tie-breaker. Featured replaces the prior featured flag; when not live the first live record leads. Active published deals are live within inclusive site-local date boundaries; expiration is evaluated on every query without cron or deletion. Invalid dates are cleared; an inverted date range never becomes live.

Location and order destination are allowlisted. No outbound domains are stored here: the shared theme gateway owns pickup URLs and the `ekb_cat` destination. Future product routing can extend this metadata contract. Only `/deals/` owns public SEO; records/categories have no public singles, archives, REST exposure, navigation entries, or sitemap entries.
