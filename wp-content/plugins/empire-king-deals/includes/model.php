<?php
defined( 'ABSPATH' ) || exit;

function ek_deals_is_owner( $user_id = 0 ) {
	$owner = (int) get_option( 'empire_king_deals_owner_user_id', 0 );
	return $owner > 0 && $owner === ( $user_id ? (int) $user_id : get_current_user_id() );
}
add_filter( 'map_meta_cap', function ( $caps, $cap, $user_id ) {
	if ( 'manage_ek_deals' === $cap ) return ek_deals_is_owner( $user_id ) ? array( 'exist' ) : array( 'do_not_allow' );
	return $caps;
}, 10, 3 );

add_action( 'init', function () {
	$capabilities = array_fill_keys( array( 'edit_post', 'read_post', 'delete_post', 'edit_posts', 'edit_others_posts', 'publish_posts', 'read_private_posts', 'delete_posts', 'delete_private_posts', 'delete_published_posts', 'delete_others_posts', 'edit_private_posts', 'edit_published_posts', 'create_posts' ), 'manage_ek_deals' );
	register_post_type( 'ek_deal', array(
		'labels' => array( 'name' => 'Deals', 'singular_name' => 'Deal', 'add_new_item' => 'Add Deal', 'edit_item' => 'Edit Deal', 'all_items' => 'All Deals' ),
		'public' => false, 'publicly_queryable' => false, 'show_ui' => true, 'show_in_menu' => ek_deals_is_owner(),
		'exclude_from_search' => true, 'show_in_nav_menus' => false, 'show_in_rest' => false,
		'has_archive' => false, 'rewrite' => false, 'query_var' => false,
		'supports' => array( 'title', 'thumbnail' ), 'capabilities' => $capabilities, 'map_meta_cap' => false, 'menu_icon' => 'dashicons-tickets-alt',
	) );
	register_taxonomy( 'ek_deal_category', 'ek_deal', array(
		'labels' => array( 'name' => 'Categories', 'singular_name' => 'Deal Category' ),
		'public' => false, 'publicly_queryable' => false, 'show_ui' => true, 'show_in_nav_menus' => false,
		'show_in_rest' => false, 'rewrite' => false, 'query_var' => false, 'meta_box_cb' => false,
		'capabilities' => array_fill_keys( array( 'manage_terms', 'edit_terms', 'delete_terms', 'assign_terms' ), 'manage_ek_deals' ),
	) );
} );
add_filter( 'wp_sitemaps_post_types', function ( $types ) { unset( $types['ek_deal'] ); return $types; } );
add_filter( 'wp_sitemaps_taxonomies', function ( $types ) { unset( $types['ek_deal_category'] ); return $types; } );

function ek_deals_destinations() {
	return array( '' => 'Full Menu', 'burger' => 'Burgers', 'sandwich' => 'Sandwiches', 'fries' => 'Fries', 'salads' => 'Salads', 'drinks' => 'Drinks', 'ice-cream' => 'Ice Cream', 'family-pack-combos' => 'Family Packs' );
}
function ek_deals_locations() {
	return array( 'both' => 'Both Locations', 'Avenue H' => 'Avenue H Only', 'Avenue I' => 'Avenue I Only' );
}
function ek_deals_date( $value ) {
	if ( ! is_string( $value ) || ! preg_match( '/^\d{4}-\d{2}-\d{2}$/D', $value ) ) return '';
	$date = DateTimeImmutable::createFromFormat( '!Y-m-d', $value, wp_timezone() );
	return $date && $date->format( 'Y-m-d' ) === $value ? $value : '';
}
function ek_deals_data( $id ) {
	$data = (array) get_post_meta( $id, '_ek_deal', true );
	return array_merge( array( 'label' => '', 'description' => '', 'fine_print' => '', 'active' => false, 'featured' => false, 'start' => '', 'end' => '', 'location' => 'both', 'destination' => '', 'order' => 0 ), $data );
}
function ek_deals_state( $id, $today = null ) {
	if ( 'publish' !== get_post_status( $id ) ) return 'Draft';
	$data = ek_deals_data( $id );
	if ( ! $data['active'] ) return 'Disabled';
	$today = $today ?? wp_date( 'Y-m-d' );
	if ( $data['end'] && $today > $data['end'] ) return 'Expired';
	if ( $data['start'] && $today < $data['start'] ) return 'Upcoming';
	return 'Live';
}
function ek_deals_live() {
	$posts = get_posts( array( 'post_type' => 'ek_deal', 'post_status' => 'publish', 'numberposts' => -1, 'orderby' => 'ID', 'order' => 'ASC' ) );
	$records = array();
	foreach ( $posts as $post ) {
		if ( 'Live' !== ek_deals_state( $post->ID ) ) continue;
		$terms = wp_get_object_terms( $post->ID, 'ek_deal_category', array( 'orderby' => 'term_id', 'order' => 'ASC' ) );
		$records[] = array_merge( ek_deals_data( $post->ID ), array( 'id' => $post->ID, 'title' => $post->post_title, 'anchor' => 'deal-' . $post->post_name . '-' . $post->ID, 'category' => ! is_wp_error( $terms ) && $terms ? $terms[0] : null ) );
	}
	usort( $records, function ( $a, $b ) { return (int) $a['order'] <=> (int) $b['order'] ?: $a['id'] <=> $b['id']; } );
	return $records;
}

// Enforce the single-category contract even for assignments outside the editor.
add_action( 'set_object_terms', function ( $id, $terms, $tt_ids, $taxonomy ) {
	if ( 'ek_deal_category' !== $taxonomy || count( $tt_ids ) <= 1 ) return;
	$term = get_term_by( 'term_taxonomy_id', min( $tt_ids ), $taxonomy );
	if ( $term ) wp_set_object_terms( $id, array( $term->term_id ), $taxonomy, false );
}, 10, 4 );
