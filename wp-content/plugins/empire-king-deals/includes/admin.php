<?php
defined( 'ABSPATH' ) || exit;

add_action( 'admin_notices', function () {
	if ( get_option( 'empire_king_deals_owner_user_id' ) || ! current_user_can( 'manage_options' ) ) return;
	echo '<div class="notice notice-info"><p><a class="button" href="' . esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=ek_deals_claim' ), 'ek_deals_claim' ) ) . '">Claim Deals Management</a></p></div>';
} );
add_action( 'admin_post_ek_deals_claim', function () {
	if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Not authorized.', '', array( 'response' => 403 ) );
	check_admin_referer( 'ek_deals_claim' );
	// add_option is atomic: a competing request cannot replace an existing owner.
	add_option( 'empire_king_deals_owner_user_id', get_current_user_id(), '', false );
	wp_safe_redirect( admin_url( ek_deals_is_owner() ? 'edit.php?post_type=ek_deal' : 'index.php' ) );
	exit;
} );
add_action( 'add_meta_boxes_ek_deal', function () {
	add_meta_box( 'ek-deal-details', 'Deal Details', 'ek_deals_editor', 'ek_deal', 'normal', 'high' );
} );
function ek_deals_editor( $post ) {
	$data = ek_deals_data( $post->ID );
	wp_nonce_field( 'ek_deals_save', 'ek_deals_nonce' );
	echo '<p>Use the Featured Image panel for approved promotional artwork.</p><table class="form-table"><tbody>';
	foreach ( array( 'label' => 'Offer Label', 'description' => 'Short Description', 'fine_print' => 'Fine Print', 'start' => 'Start Date', 'end' => 'End Date', 'order' => 'Display Order' ) as $key => $label ) {
		echo '<tr><th><label for="ek-' . esc_attr( $key ) . '">' . esc_html( $label ) . '</label></th><td>';
		if ( in_array( $key, array( 'description', 'fine_print' ), true ) ) {
			echo '<textarea class="large-text" rows="3" id="ek-' . esc_attr( $key ) . '" name="ek_deal[' . esc_attr( $key ) . ']">' . esc_textarea( $data[ $key ] ) . '</textarea>';
		} else {
			$type = in_array( $key, array( 'start', 'end' ), true ) ? 'date' : ( 'order' === $key ? 'number' : 'text' );
			echo '<input class="regular-text" type="' . esc_attr( $type ) . '" id="ek-' . esc_attr( $key ) . '" name="ek_deal[' . esc_attr( $key ) . ']" value="' . esc_attr( $data[ $key ] ) . '">';
		}
		echo '</td></tr>';
	}
	foreach ( array( 'active' => 'Active', 'featured' => 'Featured (replaces the previous featured deal)' ) as $key => $label ) {
		echo '<tr><th>' . esc_html( $label ) . '</th><td><label><input type="checkbox" name="ek_deal[' . esc_attr( $key ) . ']" value="1" ' . checked( $data[ $key ], true, false ) . '> Enabled</label></td></tr>';
	}
	foreach ( array( 'location' => array( 'Location Availability', ek_deals_locations() ), 'destination' => array( 'Order Destination', ek_deals_destinations() ) ) as $key => $field ) {
		echo '<tr><th><label for="ek-' . esc_attr( $key ) . '">' . esc_html( $field[0] ) . '</label></th><td><select id="ek-' . esc_attr( $key ) . '" name="ek_deal[' . esc_attr( $key ) . ']">';
		foreach ( $field[1] as $value => $label ) echo '<option value="' . esc_attr( $value ) . '" ' . selected( $data[ $key ], $value, false ) . '>' . esc_html( $label ) . '</option>';
		echo '</select></td></tr>';
	}
	$products = function_exists( 'wc_get_products' ) ? wc_get_products( array( 'status' => 'publish', 'limit' => -1, 'type' => array( 'simple', 'variable' ), 'orderby' => 'name', 'order' => 'ASC' ) ) : array();
	echo '<tr><th><label for="ek-product-id">Linked Menu Product</label></th><td><select class="regular-text" id="ek-product-id" name="ek_deal[product_id]"><option value="0">No linked product / Use menu destination</option>';
	foreach ( $products as $product ) {
		if ( ! $product->is_visible() || $product->get_parent_id() ) continue;
		echo '<option value="' . esc_attr( $product->get_id() ) . '" ' . selected( $data['product_id'], $product->get_id(), false ) . '>' . esc_html( $product->get_name() . ' (#' . $product->get_id() . ')' ) . '</option>';
	}
	echo '</select><p class="description">Choose the exact WooCommerce product to open, or leave this unlinked to use the menu destination.</p></td></tr>';
	$assigned = wp_get_object_terms( $post->ID, 'ek_deal_category', array( 'fields' => 'ids' ) );
	echo '<tr><th><label for="ek-category">Deal Category</label></th><td><select id="ek-category" name="ek_category"><option value="0">None</option>';
	$terms = get_terms( array( 'taxonomy' => 'ek_deal_category', 'hide_empty' => false ) );
	foreach ( is_wp_error( $terms ) ? array() : $terms as $term ) echo '<option value="' . esc_attr( $term->term_id ) . '" ' . selected( ! is_wp_error( $assigned ) && $assigned ? $assigned[0] : 0, $term->term_id, false ) . '>' . esc_html( $term->name ) . '</option>';
	echo '</select></td></tr></tbody></table><p>Dates use the WordPress site timezone. End Date includes the entire day. Blank dates impose no boundary.</p>';
}
add_action( 'save_post_ek_deal', function ( $id ) {
	if ( wp_is_post_autosave( $id ) || wp_is_post_revision( $id ) || ! current_user_can( 'manage_ek_deals' ) ) return;
	if ( ! isset( $_POST['ek_deals_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ek_deals_nonce'] ) ), 'ek_deals_save' ) ) return;
	$raw = isset( $_POST['ek_deal'] ) && is_array( $_POST['ek_deal'] ) ? wp_unslash( $_POST['ek_deal'] ) : array();
	$raw = array_filter( $raw, 'is_scalar' );
	$data = array();
	foreach ( array( 'label', 'description', 'fine_print' ) as $key ) $data[ $key ] = sanitize_textarea_field( $raw[ $key ] ?? '' );
	foreach ( array( 'active', 'featured' ) as $key ) $data[ $key ] = isset( $raw[ $key ] ) && '1' === $raw[ $key ];
	foreach ( array( 'start', 'end' ) as $key ) $data[ $key ] = ek_deals_date( $raw[ $key ] ?? '' );
	$data['location'] = array_key_exists( $raw['location'] ?? '', ek_deals_locations() ) ? $raw['location'] : 'both';
	$data['destination'] = array_key_exists( $raw['destination'] ?? '', ek_deals_destinations() ) ? ( $raw['destination'] ?? '' ) : '';
	$data['product_id'] = ek_deals_product_id( $raw['product_id'] ?? 0 );
	$data['order'] = (int) ( $raw['order'] ?? 0 );
	if ( $data['featured'] ) {
		$others = get_posts( array( 'post_type' => 'ek_deal', 'post_status' => array( 'publish', 'draft', 'pending', 'private', 'future', 'trash' ), 'numberposts' => -1, 'fields' => 'ids', 'exclude' => array( $id ) ) );
		foreach ( $others as $other ) { $other_data = ek_deals_data( $other ); if ( $other_data['featured'] ) { $other_data['featured'] = false; update_post_meta( $other, '_ek_deal', $other_data ); } }
	}
	update_post_meta( $id, '_ek_deal', $data );
	$category = isset( $_POST['ek_category'] ) && is_scalar( $_POST['ek_category'] ) ? absint( $_POST['ek_category'] ) : 0;
	wp_set_object_terms( $id, $category && term_exists( $category, 'ek_deal_category' ) ? array( $category ) : array(), 'ek_deal_category', false );
} );

function ek_deals_term_field( $term = null ) {
	wp_nonce_field( 'ek_deals_term', 'ek_deals_term_nonce' );
	$value = $term instanceof WP_Term ? (int) get_term_meta( $term->term_id, '_ek_order', true ) : 0;
	echo '<label for="ek-order">Display Order</label> <input id="ek-order" type="number" name="ek_order" value="' . esc_attr( $value ) . '">';
}
add_action( 'ek_deal_category_add_form_fields', function () { echo '<div class="form-field">'; ek_deals_term_field(); echo '</div>'; } );
add_action( 'ek_deal_category_edit_form_fields', function ( $term ) { echo '<tr class="form-field"><td colspan="2">'; ek_deals_term_field( $term ); echo '</td></tr>'; } );
function ek_deals_save_term( $id ) {
	if ( ! current_user_can( 'manage_ek_deals' ) || ! isset( $_POST['ek_deals_term_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ek_deals_term_nonce'] ) ), 'ek_deals_term' ) ) return;
	update_term_meta( $id, '_ek_order', isset( $_POST['ek_order'] ) && is_scalar( $_POST['ek_order'] ) ? (int) $_POST['ek_order'] : 0 );
}
add_action( 'created_ek_deal_category', 'ek_deals_save_term' );
add_action( 'edited_ek_deal_category', 'ek_deals_save_term' );
add_filter( 'manage_ek_deal_posts_columns', function ( $columns ) {
	return array( 'cb' => $columns['cb'], 'title' => 'Deal Name', 'ek_featured' => 'Featured', 'ek_category' => 'Category', 'ek_location' => 'Location', 'ek_schedule' => 'Schedule', 'ek_state' => 'Current State', 'ek_order' => 'Display Order' );
} );
add_action( 'manage_ek_deal_posts_custom_column', function ( $column, $id ) {
	$data = ek_deals_data( $id );
	if ( 'ek_state' === $column ) echo esc_html( ek_deals_state( $id ) );
	elseif ( 'ek_category' === $column ) { $names = wp_get_object_terms( $id, 'ek_deal_category', array( 'fields' => 'names' ) ); echo esc_html( is_wp_error( $names ) ? '' : implode( ', ', $names ) ); }
	elseif ( 'ek_schedule' === $column ) echo esc_html( ( $data['start'] ?: 'No start' ) . ' — ' . ( $data['end'] ?: 'No end' ) );
	elseif ( 'ek_featured' === $column ) echo $data['featured'] ? 'Yes' : '—';
	elseif ( 'ek_location' === $column ) echo esc_html( ek_deals_locations()[ $data['location'] ] ?? '' );
	elseif ( 'ek_order' === $column ) echo esc_html( $data['order'] );
}, 10, 2 );
