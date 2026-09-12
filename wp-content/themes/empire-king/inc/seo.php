<?php
/**
 * Technical SEO for the shared Empire King theme.
 *
 * @package Empire_King
 */

defined( 'ABSPATH' ) || exit;

/** Returns the configured city and state for search presentation. */
function empire_king_seo_locality() {
	return empire_king_get_location_locality_label();
}

/** Supplies location-specific titles for strategic landing pages. */
function empire_king_seo_document_title( $title ) {
	$location_name = empire_king_get_location_setting( 'display_name' );
	$locality      = empire_king_seo_locality();

	if ( is_front_page() ) {
		return sprintf( 'Empire King Burger - %s | %s', $location_name, $locality );
	}

	if ( is_page( 'deals' ) ) {
		return sprintf( 'Deals & Specials - %s | Empire King Burger', $location_name );
	}

	if ( is_page( 'order-now' ) ) {
		return sprintf( 'Order Online - %s | Empire King Burger', $location_name );
	}

	return $title;
}
add_filter( 'pre_get_document_title', 'empire_king_seo_document_title' );

/** Returns the custom description for a strategic landing page. */
function empire_king_seo_meta_description() {
	$location_name = empire_king_get_location_setting( 'display_name' );
	$locality      = empire_king_seo_locality();

	if ( is_front_page() ) {
		return sprintf( 'Empire King Burger at %s in %s. Browse the menu, current deals, and order online.', $location_name, $locality );
	}

	if ( is_page( 'deals' ) ) {
		return sprintf( 'Current Empire King Burger deals and specials at %s in %s. Browse current offers and order online.', $location_name, $locality );
	}

	if ( is_page( 'order-now' ) ) {
		return sprintf( 'Order Empire King Burger online from %s in %s. Browse burgers, sandwiches, fries, drinks and more.', $location_name, $locality );
	}

	return '';
}

/** Prints the theme's single custom meta description when one is defined. */
function empire_king_seo_print_meta_description() {
	$description = empire_king_seo_meta_description();
	if ( $description ) {
		echo '<meta name="description" content="' . esc_attr( $description ) . '">' . "\n";
	}
}
add_action( 'wp_head', 'empire_king_seo_print_meta_description', 1 );

/** Consolidates Order Now state URLs to the clean page permalink. */
function empire_king_seo_canonical_url( $canonical_url, $post ) {
	if ( $post instanceof WP_Post && 'order-now' === $post->post_name ) {
		return get_permalink( $post );
	}

	return $canonical_url;
}
add_filter( 'get_canonical_url', 'empire_king_seo_canonical_url', 10, 2 );

/** Determines whether the current request is a non-strategic WooCommerce destination. */
function empire_king_seo_is_woocommerce_noindex_request() {
	if ( is_singular( 'product' ) || is_tax( 'product_cat' ) ) {
		return true;
	}

	if ( function_exists( 'is_shop' ) && is_shop() ) {
		$shop_page_id = function_exists( 'wc_get_page_id' ) ? (int) wc_get_page_id( 'shop' ) : 0;
		$order_page   = get_page_by_path( 'order-now' );
		if ( ! $order_page || $shop_page_id !== (int) $order_page->ID ) {
			return true;
		}
	}

	$conditional_functions = array( 'is_cart', 'is_checkout', 'is_account_page', 'is_order_received_page' );
	foreach ( $conditional_functions as $conditional_function ) {
		if ( function_exists( $conditional_function ) && call_user_func( $conditional_function ) ) {
			return true;
		}
	}

	return false;
}

/** Applies noindex, follow to utility and low-value archive requests. */
function empire_king_seo_robots( $robots ) {
	$should_noindex = empire_king_seo_is_woocommerce_noindex_request()
		|| is_search()
		|| is_author()
		|| is_date()
		|| is_tag();

	if ( $should_noindex ) {
		unset( $robots['index'], $robots['nofollow'] );
		$robots['noindex'] = true;
		$robots['follow']  = true;
	}

	return $robots;
}
add_filter( 'wp_robots', 'empire_king_seo_robots' );

/** Excludes non-strategic WooCommerce post types from core XML sitemaps. */
function empire_king_seo_sitemap_post_types( $post_types ) {
	unset( $post_types['product'] );
	return $post_types;
}
add_filter( 'wp_sitemaps_post_types', 'empire_king_seo_sitemap_post_types' );

/** Excludes non-strategic WooCommerce taxonomies from core XML sitemaps. */
function empire_king_seo_sitemap_taxonomies( $taxonomies ) {
	unset( $taxonomies['product_cat'] );
	return $taxonomies;
}
add_filter( 'wp_sitemaps_taxonomies', 'empire_king_seo_sitemap_taxonomies' );

/** Prints one location-specific Restaurant entity on the Home page. */
function empire_king_seo_print_restaurant_schema() {
	if ( ! is_front_page() ) {
		return;
	}

	$location = empire_king_get_location_settings();
	$home_url = home_url( '/' );
	$schema   = array(
		'@context' => 'https://schema.org',
		'@type'    => 'Restaurant',
		'@id'      => trailingslashit( $home_url ) . '#restaurant',
		'name'     => 'Empire King Burger',
		'url'      => $home_url,
		'address'  => array_filter(
			array(
				'@type'           => 'PostalAddress',
				'streetAddress'   => $location['street_address'],
				'addressLocality' => $location['city'],
				'addressRegion'   => $location['state'],
				'postalCode'      => $location['zip'],
				'addressCountry'  => 'US',
			)
		),
		'menu'     => home_url( '/order-now/' ),
	);

	$latitude  = empire_king_sanitize_location_latitude( $location['latitude'] );
	$longitude = empire_king_sanitize_location_longitude( $location['longitude'] );
	if ( '' !== $latitude && '' !== $longitude ) {
		$schema['geo'] = array(
			'@type'     => 'GeoCoordinates',
			'latitude'  => $latitude,
			'longitude' => $longitude,
		);
	}

	if ( $location['phone'] ) {
		$schema['telephone'] = $location['phone'];
	}

	$logo_url = empire_king_get_logo_url();
	if ( $logo_url ) {
		$schema['logo']  = $logo_url;
		$schema['image'] = $logo_url;
	}

	echo '<script type="application/ld+json">' . wp_json_encode( $schema, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP ) . '</script>' . "\n";
}
add_action( 'wp_head', 'empire_king_seo_print_restaurant_schema', 20 );
