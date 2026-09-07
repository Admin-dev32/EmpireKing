<?php
// Temporary, read-only runtime checks. Removed after validation.
defined( 'ABSPATH' ) || exit;
function verify_deals( $value, $label ) { if ( ! $value ) throw new Exception( $label ); echo "PASS: $label\n"; }
$p = get_post_type_object('ek_deal');
$t = get_taxonomy('ek_deal_category');
verify_deals($p && !$p->publicly_queryable && !$p->has_archive && !$p->rewrite && !$p->show_in_rest, 'Private Deal registration');
verify_deals($t && !$t->public && !$t->publicly_queryable && !$t->rewrite, 'Private categories');
verify_deals(!get_option('empire_king_deals_owner_user_id'), 'Ownership unclaimed');
verify_deals(map_meta_cap('manage_ek_deals', 987655) === array('do_not_allow'), 'Unclaimed access denied');
$owner_filter = function() { return 987654; };
add_filter('pre_option_empire_king_deals_owner_user_id', $owner_filter);
verify_deals(map_meta_cap('manage_ek_deals', 987654) === array('exist'), 'Owner access granted');
verify_deals(map_meta_cap('manage_ek_deals', 987655) === array('do_not_allow'), 'Non-owner denied');
remove_filter('pre_option_empire_king_deals_owner_user_id', $owner_filter);
verify_deals(ek_deals_date('2024-02-29') === '2024-02-29' && ek_deals_date('2025-02-29') === '' && ek_deals_date('2026-2-01') === '', 'Date validation');
$id = 987654321;
$record = (object) array('ID'=>$id, 'post_status'=>'publish', 'post_type'=>'ek_deal', 'post_title'=>'', 'post_name'=>'');
wp_cache_set($id, $record, 'posts');
$data = array('active'=>true, 'start'=>'2026-09-01', 'end'=>'2026-09-07');
$meta_filter = function($value, $object_id, $key) use ($id, &$data) { return $object_id === $id && $key === '_ek_deal' ? array($data) : $value; };
add_filter('get_post_metadata', $meta_filter, 10, 3);
verify_deals(ek_deals_state($id, '2026-08-31') === 'Upcoming', 'Upcoming');
verify_deals(ek_deals_state($id, '2026-09-01') === 'Live' && ek_deals_state($id, '2026-09-07') === 'Live', 'Inclusive schedule');
verify_deals(ek_deals_state($id, '2026-09-08') === 'Expired', 'Expired');
$data['active'] = false;
verify_deals(ek_deals_state($id, '2026-09-03') === 'Disabled', 'Disabled');
$record->post_status = 'draft'; wp_cache_set($id, $record, 'posts');
verify_deals(ek_deals_state($id) === 'Draft', 'Draft');
ob_start(); ek_deals_editor(new WP_Post($record)); $editor = ob_get_clean();
verify_deals(str_contains($editor, 'ek_deals_nonce') && str_contains($editor, 'ek_category') && str_contains($editor, 'Order Destination'), 'Editor fields and save nonce');
remove_filter('get_post_metadata', $meta_filter, 10); wp_cache_delete($id, 'posts');
verify_deals(count(ek_deals_live()) === 0 && array_sum((array)wp_count_posts('ek_deal')) === 0, 'No persisted deals');
verify_deals(wp_count_terms(array('taxonomy'=>'ek_deal_category','hide_empty'=>false)) == 0, 'No seeded categories');
verify_deals(!isset(apply_filters('wp_sitemaps_post_types', array('ek_deal'=>$p))['ek_deal']), 'Deal sitemap exclusion');
verify_deals(!isset(apply_filters('wp_sitemaps_taxonomies', array('ek_deal_category'=>$t))['ek_deal_category']), 'Category sitemap exclusion');
