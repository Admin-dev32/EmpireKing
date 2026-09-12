<?php
/**
 * Theme setup and assets for Empire King.
 *
 * @package Empire_King
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once get_theme_file_path( 'inc/order-now.php' );
require_once get_theme_file_path( 'inc/order-upsell.php' );

function empire_king_setup() {
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support(
		'html5',
		array( 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script' )
	);
	add_theme_support( 'woocommerce' );
	add_theme_support( 'wc-product-gallery-zoom' );
	add_theme_support( 'wc-product-gallery-lightbox' );
	add_theme_support( 'wc-product-gallery-slider' );

	register_nav_menus(
		array(
			'primary' => esc_html__( 'Primary Menu', 'empire-king' ),
		)
	);
}
add_action( 'after_setup_theme', 'empire_king_setup' );

/** Returns the per-installation location configuration, with Avenue H defaults. */
function empire_king_get_location_settings() {
	$defaults = array(
		'display_name'   => 'Avenue H',
		'badge'          => 'H',
		'street_address' => '1036 W Avenue H',
		'city'           => 'Lancaster',
		'state'          => 'CA',
		'zip'            => '93534',
		'phone'          => '',
		'directions_url' => '',
		'latitude'       => '',
		'longitude'      => '',
	);

	foreach ( $defaults as $key => $default ) {
		$value = get_theme_mod( 'empire_king_location_' . $key, $default );
		$defaults[ $key ] = is_string( $value ) ? trim( $value ) : $default;
	}

	return $defaults;
}

/** Returns one configured location value. */
function empire_king_get_location_setting( $key ) {
	$settings = empire_king_get_location_settings();
	return isset( $settings[ $key ] ) ? $settings[ $key ] : '';
}

/** Returns the configured address on one line. */
function empire_king_get_location_address( $include_name = false ) {
	$settings = empire_king_get_location_settings();
	$city_line = trim( implode( ', ', array_filter( array( $settings['city'], trim( $settings['state'] . ( $settings['zip'] ? ' ' . $settings['zip'] : '' ) ) ) ) ) );
	$address = array_filter( array( $settings['street_address'], $city_line ) );
	if ( $include_name && $settings['display_name'] ) {
		array_unshift( $address, $settings['display_name'] );
	}
	return implode( ', ', $address );
}

/** Returns the configured address as safe HTML lines for an address element. */
function empire_king_get_location_address_lines() {
	$settings  = empire_king_get_location_settings();
	$city_line = trim( implode( ', ', array_filter( array( $settings['city'], trim( $settings['state'] . ( $settings['zip'] ? ' ' . $settings['zip'] : '' ) ) ) ) ) );
	return array_filter( array( $settings['street_address'], $city_line ) );
}

/** Returns the city and region label used in location presentation. */
function empire_king_get_location_locality_label( $spell_out_state = false ) {
	$settings = empire_king_get_location_settings();
	$state    = $settings['state'];
	if ( $spell_out_state && 'CA' === strtoupper( $state ) ) {
		$state = 'California';
	}
	return implode( ', ', array_filter( array( $settings['city'], $state ) ) );
}

/** Returns a Google Maps search URL, or an explicitly configured directions URL. */
function empire_king_get_location_directions_url() {
	$settings = empire_king_get_location_settings();
	if ( $settings['directions_url'] ) {
		return $settings['directions_url'];
	}
	return add_query_arg( array( 'api' => '1', 'query' => empire_king_get_location_address( true ) ), 'https://www.google.com/maps/search/' );
}

/** Sanitizes optional phone storage without inventing a presentation format. */
function empire_king_sanitize_location_phone( $value ) {
	return preg_replace( '/[^0-9+().\-\s]/', '', (string) $value );
}

/** Sanitizes an optional geographic coordinate within its allowed range. */
function empire_king_sanitize_location_coordinate( $value, $minimum, $maximum ) {
	$value = trim( (string) $value );
	if ( '' === $value || ! is_numeric( $value ) ) {
		return '';
	}

	$coordinate = (float) $value;
	return is_finite( $coordinate ) && $coordinate >= $minimum && $coordinate <= $maximum ? $value : '';
}

/** Sanitizes an optional map latitude. */
function empire_king_sanitize_location_latitude( $value ) {
	return empire_king_sanitize_location_coordinate( $value, -90, 90 );
}

/** Sanitizes an optional map longitude. */
function empire_king_sanitize_location_longitude( $value ) {
	return empire_king_sanitize_location_coordinate( $value, -180, 180 );
}

/** Registers Home Menu Glimpse appearance settings. */
function empire_king_customize_register( $wp_customize ) {
	$wp_customize->add_section(
		'empire_king_location',
		array( 'title' => esc_html__( 'Empire King Location', 'empire-king' ) )
	);
	$location_fields = array(
		'display_name'   => array( 'label' => 'Location Display Name', 'sanitize' => 'sanitize_text_field' ),
		'badge'          => array( 'label' => 'Short Location Badge / Letter', 'sanitize' => 'sanitize_text_field' ),
		'street_address' => array( 'label' => 'Street Address', 'sanitize' => 'sanitize_text_field' ),
		'city'           => array( 'label' => 'City', 'sanitize' => 'sanitize_text_field' ),
		'state'          => array( 'label' => 'State', 'sanitize' => 'sanitize_text_field' ),
		'zip'            => array( 'label' => 'ZIP Code', 'sanitize' => 'sanitize_text_field' ),
		'phone'          => array( 'label' => 'Phone Number', 'sanitize' => 'empire_king_sanitize_location_phone' ),
		'directions_url' => array( 'label' => 'Directions / Google Maps URL (optional)', 'sanitize' => 'esc_url_raw' ),
		'latitude'       => array( 'label' => 'Map Latitude (optional)', 'sanitize' => 'empire_king_sanitize_location_latitude' ),
		'longitude'      => array( 'label' => 'Map Longitude (optional)', 'sanitize' => 'empire_king_sanitize_location_longitude' ),
	);
	foreach ( $location_fields as $key => $field ) {
		$setting = 'empire_king_location_' . $key;
		$wp_customize->add_setting( $setting, array( 'sanitize_callback' => $field['sanitize'] ) );
		$wp_customize->add_control( $setting, array( 'label' => esc_html__( $field['label'], 'empire-king' ), 'section' => 'empire_king_location', 'type' => 'text' ) );
	}

	$wp_customize->add_section(
		'empire_king_home_menu_glimpse',
		array(
			'title' => esc_html__( 'Home Menu Glimpse', 'empire-king' ),
		)
	);

	$wp_customize->add_setting(
		'empire_king_home_menu_glimpse_background_image',
		array(
			'sanitize_callback' => 'esc_url_raw',
		)
	);

	$wp_customize->add_control(
		new WP_Customize_Image_Control(
			$wp_customize,
			'empire_king_home_menu_glimpse_background_image',
			array(
				'label'   => esc_html__( 'Background Image', 'empire-king' ),
				'section' => 'empire_king_home_menu_glimpse',
			)
		)
	);

	$wp_customize->add_section(
		'empire_king_deals_page',
		array(
			'title' => esc_html__( 'Deals Page', 'empire-king' ),
		)
	);

	$wp_customize->add_setting(
		'empire_king_deals_travel_background_image',
		array(
			'sanitize_callback' => 'esc_url_raw',
		)
	);

	$wp_customize->add_control(
		new WP_Customize_Image_Control(
			$wp_customize,
			'empire_king_deals_travel_background_image',
			array(
				'label'   => esc_html__( 'Passing Through Background Image', 'empire-king' ),
				'section' => 'empire_king_deals_page',
			)
		)
	);

	$wp_customize->add_section(
		'empire_king_order_now_page',
		array(
			'title' => esc_html__( 'Order Now Page', 'empire-king' ),
		)
	);

	$wp_customize->add_setting(
		'empire_king_order_now_hero_side_image',
		array(
			'sanitize_callback' => 'esc_url_raw',
		)
	);

	$wp_customize->add_control(
		new WP_Customize_Image_Control(
			$wp_customize,
			'empire_king_order_now_hero_side_image',
			array(
				'label'   => esc_html__( 'Hero Side Image', 'empire-king' ),
				'section' => 'empire_king_order_now_page',
			)
		)
	);
}
add_action( 'customize_register', 'empire_king_customize_register' );

function empire_king_enqueue_styles() {
	if ( is_home() || is_singular( 'post' ) ) {
		wp_enqueue_style( 'empire-king-blog', get_theme_file_uri( 'assets/css/blog.css' ), array( 'empire-king-style' ), wp_get_theme()->get( 'Version' ) );
	}
	wp_enqueue_style(
		'empire-king-style',
		get_stylesheet_uri(),
		array(),
		wp_get_theme()->get( 'Version' )
	);

	wp_enqueue_style(
		'empire-king-header',
		get_theme_file_uri( 'assets/css/header.css' ),
		array( 'empire-king-style' ),
		wp_get_theme()->get( 'Version' )
	);

	wp_enqueue_style(
		'empire-king-footer',
		get_theme_file_uri( 'assets/css/footer.css' ),
		array( 'empire-king-style' ),
		wp_get_theme()->get( 'Version' )
	);

	wp_enqueue_script(
		'empire-king-header-navigation',
		get_theme_file_uri( 'assets/js/header-navigation.js' ),
		array(),
		wp_get_theme()->get( 'Version' ),
		array(
			'in_footer' => true,
			'strategy'  => 'defer',
		)
	);

	if ( is_page( 'deals' ) ) {
		wp_enqueue_style( 'empire-king-deals', get_theme_file_uri( 'assets/css/deals.css' ), array( 'empire-king-style' ), wp_get_theme()->get( 'Version' ) );
		wp_enqueue_script( 'empire-king-deals-landing', get_theme_file_uri( 'assets/js/deals-landing.js' ), array(), wp_get_theme()->get( 'Version' ), array( 'in_footer' => true, 'strategy' => 'defer' ) );
	}
	if ( is_page( 'order-now' ) || is_cart() ) {
		wp_enqueue_style( 'empire-king-order-now', get_theme_file_uri( 'assets/css/order-now.css' ), array( 'empire-king-style', 'empire-king-header' ), wp_get_theme()->get( 'Version' ) );
		$order_dependencies = class_exists( 'WooCommerce' ) ? array( 'jquery', 'wc-add-to-cart', 'wc-add-to-cart-variation', 'wc-cart-fragments' ) : array();
		if ( class_exists( '\SW_WAPF\Includes\Classes\Woocommerce_Service' ) ) {
			$order_dependencies[] = 'wapf-frontend-js';
		}
		wp_enqueue_script( 'empire-king-order-now', get_theme_file_uri( 'assets/js/order-now.js' ), $order_dependencies, wp_get_theme()->get( 'Version' ), array( 'in_footer' => true, 'strategy' => 'defer' ) );
		if ( class_exists( 'WC_AJAX' ) ) {
			wp_localize_script(
				'empire-king-order-now',
				'empireKingOrderNow',
				array(
					'sheetUrl'    => WC_AJAX::get_endpoint( 'empire_king_product_sheet' ),
					'cartUrl'     => WC_AJAX::get_endpoint( 'add_to_cart' ),
					'noticeUrl'   => WC_AJAX::get_endpoint( 'empire_king_sheet_error_notices' ),
					'noticeNonce' => wp_create_nonce( 'empire_king_sheet_error_notices' ),
					'editCartUrl' => WC_AJAX::get_endpoint( 'empire_king_edit_cart_item' ),
					'editNonce'   => wp_create_nonce( 'empire_king_edit_cart_item' ),
					'isCart'      => is_cart(),
				)
			);
		}
	}
	if ( is_page( 'order-now' ) && class_exists( 'WC_AJAX' ) ) {
		wp_enqueue_style( 'empire-king-order-upsell', get_theme_file_uri( 'assets/css/order-upsell.css' ), array( 'empire-king-order-now' ), wp_get_theme()->get( 'Version' ) );
		wp_enqueue_script( 'empire-king-order-upsell', get_theme_file_uri( 'assets/js/order-upsell.js' ), array( 'empire-king-order-now' ), wp_get_theme()->get( 'Version' ), array( 'in_footer' => true, 'strategy' => 'defer' ) );
		wp_localize_script(
			'empire-king-order-upsell',
			'empireKingOrderUpsell',
			array(
				'recommendationsUrl' => WC_AJAX::get_endpoint( 'empire_king_order_upsell' ),
				'cartUrl'            => wc_get_cart_url(),
			)
		);
	}
	if ( is_cart() ) {
		wp_enqueue_style( 'empire-king-cart', get_theme_file_uri( 'assets/css/cart.css' ), array( 'empire-king-style', 'empire-king-header', 'empire-king-order-now' ), wp_get_theme()->get( 'Version' ) );
	}
	if ( function_exists( 'is_checkout' ) && is_checkout() && ! is_wc_endpoint_url() ) {
		wp_enqueue_style( 'empire-king-checkout', get_theme_file_uri( 'assets/css/checkout.css' ), array( 'empire-king-style', 'empire-king-header' ), wp_get_theme()->get( 'Version' ) );
	}
	if ( function_exists( 'is_order_received_page' ) && is_order_received_page() ) {
		wp_enqueue_style( 'empire-king-order-received', get_theme_file_uri( 'assets/css/order-received.css' ), array( 'empire-king-style', 'empire-king-header' ), wp_get_theme()->get( 'Version' ) );
	}
	if ( is_front_page() ) {
		wp_enqueue_style( 'empire-king-home-locations', get_theme_file_uri( 'assets/css/home-locations.css' ), array( 'empire-king-home' ), wp_get_theme()->get( 'Version' ) );
		if ( empire_king_get_home_stories() ) {
			wp_enqueue_style( 'empire-king-home-stories', get_theme_file_uri( 'assets/css/home-stories.css' ), array( 'empire-king-home' ), wp_get_theme()->get( 'Version' ) );
			wp_enqueue_script( 'empire-king-home-stories', get_theme_file_uri( 'assets/js/home-stories.js' ), array(), wp_get_theme()->get( 'Version' ), array( 'in_footer' => true, 'strategy' => 'defer' ) );
		}
		wp_enqueue_style( 'empire-king-featured-favorites', get_theme_file_uri( 'assets/css/featured-favorites.css' ), array( 'empire-king-home' ), wp_get_theme()->get( 'Version' ) );
		wp_enqueue_script( 'empire-king-featured-favorites', get_theme_file_uri( 'assets/js/featured-favorites.js' ), array(), wp_get_theme()->get( 'Version' ), array( 'in_footer' => true, 'strategy' => 'defer' ) );
		wp_enqueue_style(
			'empire-king-home',
			get_theme_file_uri( 'assets/css/home.css' ),
			array( 'empire-king-style' ),
			wp_get_theme()->get( 'Version' )
		);
		$home_menu_glimpse_css_path = get_theme_file_path( 'assets/css/home-menu-glimpse.css' );
		wp_enqueue_style( 'empire-king-home-menu-glimpse', get_theme_file_uri( 'assets/css/home-menu-glimpse.css' ), array( 'empire-king-home' ), file_exists( $home_menu_glimpse_css_path ) ? filemtime( $home_menu_glimpse_css_path ) : null );
		wp_enqueue_script( 'empire-king-home-menu-glimpse', get_theme_file_uri( 'assets/js/home-menu-glimpse.js' ), array(), wp_get_theme()->get( 'Version' ), array( 'in_footer' => true, 'strategy' => 'defer' ) );

		wp_enqueue_style( 'empire-king-order-gateway', get_theme_file_uri( 'assets/css/order-gateway.css' ), array( 'empire-king-home' ), wp_get_theme()->get( 'Version' ) );
		empire_king_enqueue_google_maps_api();
		$home_locations_script_path = get_theme_file_path( 'assets/js/home-locations.js' );
		wp_enqueue_script(
			'empire-king-home-locations',
			get_theme_file_uri( 'assets/js/home-locations.js' ),
			empire_king_get_google_maps_api_key() ? array( 'empire-king-google-maps-api' ) : array(),
			file_exists( $home_locations_script_path ) ? filemtime( $home_locations_script_path ) : null,
			array(
				'in_footer' => true,
				'strategy'  => 'defer',
			)
		);
		wp_localize_script(
			'empire-king-home-locations',
			'empireKingLocation',
			array(
				'address'   => empire_king_get_location_address( true ),
				'label'     => sprintf( 'Empire King Burger — %s', empire_king_get_location_setting( 'display_name' ) ),
				'latitude'  => empire_king_get_location_setting( 'latitude' ),
				'longitude' => empire_king_get_location_setting( 'longitude' ),
			)
		);

		$home_slides = empire_king_get_home_slideshow_images();
		if ( $home_slides ) {
			wp_enqueue_style(
				'empire-king-home-slideshow',
				get_theme_file_uri( 'assets/css/home-slideshow.css' ),
				array( 'empire-king-home' ),
				wp_get_theme()->get( 'Version' )
			);
		}

		if ( $home_slides ) {
			wp_enqueue_script(
				'empire-king-home-slideshow',
				get_theme_file_uri( 'assets/js/home-slideshow.js' ),
				array(),
				wp_get_theme()->get( 'Version' ),
				array(
					'in_footer' => true,
					'strategy'  => 'defer',
				)
			);
		}

	}
}
add_action( 'wp_enqueue_scripts', 'empire_king_enqueue_styles' );

// APF normally supplies these settings only on canonical product pages.
add_action( 'wp_enqueue_scripts', static function () {
	if ( ( is_page( 'order-now' ) || is_cart() ) && wp_script_is( 'wapf-frontend-js', 'enqueued' ) && class_exists( '\SW_WAPF\Includes\Classes\Woocommerce_Service' ) ) {
		wp_localize_script( 'wapf-frontend-js', 'wapf_config', array(
			'page_type' => 'product',
			'display_options' => \SW_WAPF\Includes\Classes\Woocommerce_Service::get_price_display_options(),
		) );
	}
}, 20 );

add_filter( 'pre_get_document_title', function ( $title ) {
	return is_page( 'deals' ) ? sprintf( 'Deals & Specials in %s | Empire King Burger', empire_king_get_location_locality_label() ) : $title;
} );
add_action( 'wp_head', function () {
	if ( is_page( 'deals' ) ) echo '<meta name="description" content="' . esc_attr( sprintf( 'Current Empire King Burger deals and specials at %s in %s. Browse current offers and order online.', empire_king_get_location_setting( 'display_name' ), empire_king_get_location_locality_label( true ) ) ) . '">' . "\n";
} );

/** Enqueues Google Maps independently for the configured homepage map. */
function empire_king_enqueue_google_maps_api() {
	$maps_api_key = empire_king_get_google_maps_api_key();
	if ( $maps_api_key ) {
		wp_enqueue_script( 'empire-king-google-maps-api', add_query_arg( array( 'key' => $maps_api_key, 'v' => 'weekly' ), 'https://maps.googleapis.com/maps/api/js' ), array(), null, array( 'in_footer' => true, 'strategy' => 'defer' ) );
	}
}

/** Builds a local order URL with optional category and campaign context. */
function empire_king_get_local_order_url( $category = '', $preserve_campaign = false ) {
	$query_args = array();
	if ( $category ) {
		$query_args['ekb_cat'] = sanitize_title( $category );
	}

	if ( $preserve_campaign ) {
		$campaign_keys = array( 'utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'utm_term', 'gclid', 'gbraid', 'wbraid', 'fbclid' );
		foreach ( $campaign_keys as $key ) {
			if ( isset( $_GET[ $key ] ) && is_scalar( $_GET[ $key ] ) ) {
				$value = sanitize_text_field( wp_unslash( $_GET[ $key ] ) );
				if ( '' !== $value ) {
					$query_args[ $key ] = $value;
				}
			}
		}
	}

	return $query_args ? add_query_arg( $query_args, home_url( '/order-now/' ) ) : home_url( '/order-now/' );
}

/** Gets the optional, repository-owned decorative stories background. */
function empire_king_get_stories_background_url() {
	$directory = get_theme_file_path( 'assets/images/home/stories-background' );
	$files = glob( $directory . '/*' );
	$files = array_filter(
		false === $files ? array() : $files,
		static function ( $file ) {
			return is_file( $file ) && in_array( strtolower( pathinfo( $file, PATHINFO_EXTENSION ) ), array( 'webp', 'jpg', 'jpeg', 'png' ), true );
		}
	);
	usort( $files, static function ( $a, $b ) { return strnatcasecmp( $a, $b ) ?: strcmp( $a, $b ); } );
	return $files ? get_theme_file_uri( 'assets/images/home/stories-background/' . rawurlencode( basename( $files[0] ) ) ) : false;
}

/** Blog navigation follows Reading settings; no fabricated archive URL. */
function empire_king_get_blog_url() {
	$page_id = (int) get_option( 'page_for_posts' );
	if ( $page_id && 'publish' === get_post_status( $page_id ) ) {
		return get_permalink( $page_id );
	}
	return 'posts' === get_option( 'show_on_front' ) && ! locate_template( 'front-page.php' ) ? home_url( '/' ) : false;
}

/**
 * Display records from native published Posts and Media Library images.
 * Local preview records are never persisted and cannot appear in production.
 *
 * @return array Story records, newest first.
 */
function empire_king_get_home_stories() {
	static $stories = null;
	if ( null !== $stories ) {
		return $stories;
	}
	$stories = array();
	$query = new WP_Query(
		array(
			'post_type' => 'post',
			'post_status' => 'publish',
			'posts_per_page' => 12,
			'orderby' => array( 'date' => 'DESC', 'ID' => 'DESC' ),
			'ignore_sticky_posts' => true,
			'has_password' => false,
			'no_found_rows' => true,
		)
	);
	foreach ( $query->posts as $story_post ) {
		$stories[] = array(
			'id' => $story_post->ID,
			'title' => get_the_title( $story_post ),
			'url' => get_permalink( $story_post ),
			'image_id' => get_post_thumbnail_id( $story_post ),
			'preview' => false,
		);
	}
	if ( ! $stories && 'local' === wp_get_environment_type() ) {
		// Visual-development records only, not Empire King news or real posts.
		foreach ( array( 'Sample Story One', 'Sample Story Two', 'Sample Story Three' ) as $title ) {
			$stories[] = array( 'id' => 0, 'title' => $title, 'url' => '', 'image_id' => 0, 'preview' => true );
		}
	}
	return $stories;
}

/**
 * Gets homepage featured picks from native WooCommerce Featured products.
 *
 * @return array Product categories containing eligible Featured products.
 */
function empire_king_get_featured_favorites() {
	if ( ! function_exists( 'wc_get_products' ) ) {
		return array();
	}

	$default_category_id = (int) get_option( 'default_product_cat' );
	$terms               = get_terms(
		array(
			'taxonomy'   => 'product_cat',
			'hide_empty' => true,
			'exclude'    => $default_category_id ? array( $default_category_id ) : array(),
		)
	);
	if ( is_wp_error( $terms ) || ! $terms ) {
		return array();
	}

	usort(
		$terms,
		static function ( $a, $b ) {
			if ( 'burger' === $a->slug ) return -1;
			if ( 'burger' === $b->slug ) return 1;
			return strnatcasecmp( $a->name, $b->name );
		}
	);

	$products = wc_get_products(
		array(
			'status'  => 'publish',
			'featured' => true,
			'limit'   => -1,
			'type'    => array( 'simple', 'variable', 'grouped', 'external' ),
			'orderby' => 'menu_order',
			'order'   => 'ASC',
		)
	);
	$items_by_term_id = array();
	foreach ( $products as $product ) {
		if ( ! $product->is_featured() || ! $product->is_visible() ) {
			continue;
		}
		$product_categories = wp_get_post_terms( $product->get_id(), 'product_cat', array( 'fields' => 'ids' ) );
		if ( is_wp_error( $product_categories ) ) {
			continue;
		}
		foreach ( $product_categories as $term_id ) {
			if ( ! isset( $items_by_term_id[ $term_id ] ) ) {
				$items_by_term_id[ $term_id ] = array();
			}
			$items_by_term_id[ $term_id ][] = array(
				'id'       => $product->get_id(),
				'name'     => $product->get_name(),
				'image_id' => $product->get_image_id(),
			);
		}
	}

	$categories = array();
	foreach ( $terms as $term ) {
		if ( empty( $items_by_term_id[ $term->term_id ] ) ) {
			continue;
		}
		$categories[ $term->slug ] = array(
			'label' => $term->name,
			'items' => $items_by_term_id[ $term->term_id ],
		);
	}

	return $categories;
}

/**
 * Gets the first eligible repository-owned logo asset, if supplied.
 *
 * @return string|false Logo URL or false when no logo asset is available.
 */
function empire_king_get_logo_url() {
	static $logo_url = null;

	if ( null !== $logo_url ) {
		return $logo_url;
	}

	$logo_directory = get_theme_file_path( 'assets/images/branding/logo' );
	$extensions     = array( 'svg', 'png', 'webp', 'jpg', 'jpeg' );
	$files          = glob( $logo_directory . '/*' );
	$files          = false === $files ? array() : array_filter(
		$files,
		static function ( $file ) use ( $extensions ) {
			return is_file( $file ) && in_array( strtolower( pathinfo( $file, PATHINFO_EXTENSION ) ), $extensions, true );
		}
	);

	natcasesort( $files );
	$first_file = reset( $files );

	if ( false === $first_file ) {
		$logo_url = false;
		return $logo_url;
	}

	$logo_url = get_theme_file_uri( 'assets/images/branding/logo/' . basename( $first_file ) );
	return $logo_url;
}

/**
 * Gets the dedicated footer logo asset, if one has been supplied.
 *
 * @return string|false Footer logo URL or false when no asset is available.
 */
function empire_king_get_footer_logo_url() {
	static $footer_logo_url = null;

	if ( null !== $footer_logo_url ) {
		return $footer_logo_url;
	}

	$logo_directory = get_theme_file_path( 'assets/images/branding/footer-logo' );
	$extensions     = array( 'svg', 'png', 'webp', 'jpg', 'jpeg' );
	$files          = glob( $logo_directory . '/*' );
	$files          = false === $files ? array() : array_filter(
		$files,
		static function ( $file ) use ( $extensions ) {
			return is_file( $file ) && in_array( strtolower( pathinfo( $file, PATHINFO_EXTENSION ) ), $extensions, true );
		}
	);

	natcasesort( $files );
	$first_file = reset( $files );

	if ( false === $first_file ) {
		$footer_logo_url = false;
		return $footer_logo_url;
	}

	$footer_logo_url = get_theme_file_uri( 'assets/images/branding/footer-logo/' . basename( $first_file ) );
	return $footer_logo_url;
}

/**
 * Gets the first optional decorative Order Gateway background asset.
 *
 * @return string|false Background URL or false when no asset is available.
 */
function empire_king_get_order_gateway_background_url() {
	static $background_url = null;

	if ( null !== $background_url ) {
		return $background_url;
	}

	$background_directory = get_theme_file_path( 'assets/images/home/order-gateway-background' );
	$extensions           = array( 'webp', 'png', 'jpg', 'jpeg' );
	$files                = glob( $background_directory . '/*' );
	$files                = false === $files ? array() : array_filter(
		$files,
		static function ( $file ) use ( $extensions ) {
			return is_file( $file ) && in_array( strtolower( pathinfo( $file, PATHINFO_EXTENSION ) ), $extensions, true );
		}
	);

	natcasesort( $files );
	$first_file = reset( $files );

	if ( false === $first_file ) {
		$background_url = false;
		return $background_url;
	}

	$background_url = get_theme_file_uri( 'assets/images/home/order-gateway-background/' . basename( $first_file ) );
	return $background_url;
}

/**
 * Gets the first optional transparent combo image for the Order Gateway.
 *
 * @return string|false Combo image URL or false when no asset is available.
 */
function empire_king_get_order_gateway_combo_url() {
	static $combo_url = null;

	if ( null !== $combo_url ) {
		return $combo_url;
	}

	$combo_directory = get_theme_file_path( 'assets/images/home/order-gateway-combo' );
	$extensions      = array( 'png', 'webp' );
	$files           = glob( $combo_directory . '/*' );
	$files           = false === $files ? array() : array_filter(
		$files,
		static function ( $file ) use ( $extensions ) {
			return is_file( $file ) && in_array( strtolower( pathinfo( $file, PATHINFO_EXTENSION ) ), $extensions, true );
		}
	);

	natcasesort( $files );
	$first_file = reset( $files );

	if ( false === $first_file ) {
		$combo_url = false;
		return $combo_url;
	}

	$combo_url = get_theme_file_uri( 'assets/images/home/order-gateway-combo/' . basename( $first_file ) );
	return $combo_url;
}

/**
 * Gets the folder-driven Home slideshow images in natural filename order.
 *
 * @return array<int, array{url: string, alt: string}>
 */
function empire_king_get_home_slideshow_images() {
	$slideshow_directory = get_theme_file_path( 'assets/images/home/slideshow' );
	$extensions          = array( 'webp', 'jpg', 'jpeg', 'png' );
	$files               = glob( $slideshow_directory . '/*' );
	$files               = false === $files ? array() : array_filter(
		$files,
		static function ( $file ) use ( $extensions ) {
			return is_file( $file ) && in_array( strtolower( pathinfo( $file, PATHINFO_EXTENSION ) ), $extensions, true );
		}
	);

	natcasesort( $files );
	$slides = array();

	foreach ( $files as $file ) {
		$filename = pathinfo( $file, PATHINFO_FILENAME );
		$alt_text = preg_replace( '/^\d+[-_\s]*/', '', $filename );
		$alt_text = preg_replace( '/[-_]+/', ' ', $alt_text );
		$alt_text = trim( preg_replace( '/\s+/', ' ', $alt_text ) );

		$slides[] = array(
			'url' => get_theme_file_uri( 'assets/images/home/slideshow/' . basename( $file ) ),
			'alt' => $alt_text,
		);
	}

	return $slides;
}

/** Gets the Customizer-selected Home Menu Glimpse background image URL. */
function empire_king_get_home_menu_glimpse_background_url() {
	$background_url = esc_url_raw( get_theme_mod( 'empire_king_home_menu_glimpse_background_image' ) );

	return $background_url ? $background_url : false;
}

/** Gets the Customizer-selected Passing Through background image URL. */
function empire_king_get_deals_travel_background_url() {
	$background_url = esc_url_raw( get_theme_mod( 'empire_king_deals_travel_background_image' ) );

	return $background_url ? $background_url : false;
}

/** Gets the Customizer-selected Order Now hero side image URL. */
function empire_king_get_order_now_hero_side_image_url() {
	$image_url = esc_url_raw( get_theme_mod( 'empire_king_order_now_hero_side_image' ) );

	return $image_url ? $image_url : false;
}

/** Gets Home Menu Glimpse categories and product imagery from WooCommerce. */
function empire_king_get_home_menu_glimpse() {
	if ( ! function_exists( 'wc_get_products' ) ) {
		return array( 'mode' => 'empty', 'categories' => array() );
	}

	$default_category_id = (int) get_option( 'default_product_cat' );
	$terms               = get_terms(
		array(
			'taxonomy'   => 'product_cat',
			'hide_empty' => true,
			'exclude'    => $default_category_id ? array( $default_category_id ) : array(),
		)
	);
	if ( is_wp_error( $terms ) || ! $terms ) {
		return array( 'mode' => 'empty', 'categories' => array() );
	}

	usort(
		$terms,
		static function ( $a, $b ) {
			if ( 'burger' === $a->slug ) return -1;
			if ( 'burger' === $b->slug ) return 1;
			return strnatcasecmp( $a->name, $b->name );
		}
	);

	$products = wc_get_products(
		array(
			'status'  => 'publish',
			'limit'   => -1,
			'type'    => array( 'simple', 'variable', 'grouped', 'external' ),
			'orderby' => 'menu_order',
			'order'   => 'ASC',
		)
	);
	$images_by_term_id = array();
	foreach ( $products as $product ) {
		if ( ! $product->is_visible() || ! $product->get_image_id() ) {
			continue;
		}
		$product_term_ids = wp_get_post_terms( $product->get_id(), 'product_cat', array( 'fields' => 'ids' ) );
		if ( is_wp_error( $product_term_ids ) ) {
			continue;
		}
		foreach ( $product_term_ids as $term_id ) {
			if ( isset( $images_by_term_id[ $term_id ] ) && count( $images_by_term_id[ $term_id ] ) >= 3 ) {
				continue;
			}
			$images_by_term_id[ $term_id ][] = array(
				'id'       => $product->get_id(),
				'name'     => $product->get_name(),
				'image_id' => $product->get_image_id(),
			);
		}
	}

	$categories = array();
	foreach ( $terms as $term ) {
		$categories[] = array(
			'key'      => $term->slug,
			'name'     => $term->name,
			'products' => isset( $images_by_term_id[ $term->term_id ] ) ? $images_by_term_id[ $term->term_id ] : array(),
		);
	}

	return array( 'mode' => 'catalog', 'categories' => $categories );
}

/** Gets optional user-supplied media for the Deals landing hero. */
function empire_king_get_deals_landing_media() {
	static $media = null;

	if ( null !== $media ) {
		return $media;
	}

	$directory       = get_theme_file_path( 'assets/images/deals-landing' );
	$background_path = $directory . '/background';
	$foreground_path = $directory . '/foreground';
	$hero_product_path = $directory . '/hero-product';
	$backgrounds     = glob( $background_path . '/*' );
	$foregrounds     = glob( $foreground_path . '/*.png' );
	$hero_products   = glob( $hero_product_path . '/*' );
	$backgrounds     = false === $backgrounds ? array() : array_filter(
		$backgrounds,
		static function ( $file ) {
			return is_file( $file ) && in_array( strtolower( pathinfo( $file, PATHINFO_EXTENSION ) ), array( 'webp', 'jpg', 'jpeg', 'png' ), true );
		}
	);
	$foregrounds = false === $foregrounds ? array() : array_filter( $foregrounds, 'is_file' );
	$hero_products = false === $hero_products ? array() : array_filter(
		$hero_products,
		static function ( $file ) {
			return is_file( $file ) && in_array( strtolower( pathinfo( $file, PATHINFO_EXTENSION ) ), array( 'png', 'webp' ), true );
		}
	);
	natcasesort( $backgrounds );
	natcasesort( $foregrounds );
	natcasesort( $hero_products );
	$background = reset( $backgrounds );
	$hero_product = reset( $hero_products );
	if ( count( $hero_products ) > 1 && defined( 'WP_DEBUG' ) && WP_DEBUG ) {
		error_log( 'Empire King Deals hero-product accepts one image; using the first filename in natural order.' );
	}

	$media = array(
		'background'  => $background ? get_theme_file_uri( 'assets/images/deals-landing/background/' . rawurlencode( basename( $background ) ) ) : false,
		'hero_product' => $hero_product ? get_theme_file_uri( 'assets/images/deals-landing/hero-product/' . rawurlencode( basename( $hero_product ) ) ) : false,
		'foregrounds' => array_map(
			static function ( $file ) {
				return get_theme_file_uri( 'assets/images/deals-landing/foreground/' . rawurlencode( basename( $file ) ) );
			},
			array_values( $foregrounds )
		),
	);

	return $media;
}

/**
 * Gets the Maps JavaScript API key from the local environment.
 *
 * The local MU loader supplies the preferred API key from .env, with the
 * previous Embed key accepted as a backwards-compatible fallback. Do not
 * place either key in this repository.
 *
 * @return string|false API key or false when no key is configured.
 */
function empire_king_get_google_maps_api_key() {
	$api_key = getenv( 'EMPIRE_KING_GOOGLE_MAPS_API_KEY' );
	if ( false === $api_key || '' === $api_key ) {
		$api_key = getenv( 'EMPIRE_KING_GOOGLE_MAPS_EMBED_KEY' );
	}

	if ( false === $api_key || '' === $api_key ) {
		$api_key = defined( 'EMPIRE_KING_GOOGLE_MAPS_API_KEY' ) ? EMPIRE_KING_GOOGLE_MAPS_API_KEY : '';
	}

	if ( ! $api_key ) {
		$api_key = defined( 'EMPIRE_KING_GOOGLE_MAPS_EMBED_KEY' ) ? EMPIRE_KING_GOOGLE_MAPS_EMBED_KEY : '';
	}

	if ( ! $api_key ) {
		return false;
	}

	return $api_key;
}

/**
 * Prints approved fallback navigation when a WordPress menu is not assigned.
 *
 * @param array<string, mixed> $args Navigation arguments.
 */
function empire_king_primary_nav_fallback( $args = array() ) {
	$menu_class = isset( $args['menu_class'] ) ? $args['menu_class'] : 'primary-menu';
	?>
	<ul class="<?php echo esc_attr( $menu_class ); ?>">
		<li><a href="<?php echo esc_url( home_url( '/order-now/' ) ); ?>"><?php esc_html_e( 'Menu', 'empire-king' ); ?></a></li>
		<li><a href="<?php echo esc_url( home_url( '/deals/' ) ); ?>"><?php esc_html_e( 'Deals', 'empire-king' ); ?></a></li>
		<li><a href="<?php echo esc_url( home_url( '/#locations' ) ); ?>"><?php esc_html_e( 'Locations', 'empire-king' ); ?></a></li>
		<li><a href="<?php echo esc_url( home_url( '/#about' ) ); ?>"><?php esc_html_e( 'About', 'empire-king' ); ?></a></li>
	</ul>
	<?php
}

/**
 * Renders the registered primary navigation or its approved fallback.
 *
 * @param string $menu_class CSS class for the menu list.
 */
function empire_king_render_primary_navigation( $menu_class ) {
	wp_nav_menu(
		array(
			'theme_location' => 'primary',
			'menu_class'     => $menu_class,
			'container'      => false,
			'depth'          => 1,
			'fallback_cb'    => 'empire_king_primary_nav_fallback',
		)
	);
}

/**
 * Changes the visible label for billing_address_1 on checkout to "Billing address".
 *
 * Preserves the actual field key, required state, autocomplete, validation,
 * country/state relationships, checkout AJAX, and gateway compatibility.
 *
 * @param array<string, mixed> $fields Checkout or billing fields.
 * @return array<string, mixed>
 */
function empire_king_checkout_billing_address_label( $fields ) {
	if ( isset( $fields['billing']['billing_address_1'] ) ) {
		$fields['billing']['billing_address_1']['label'] = esc_html__( 'Billing address', 'empire-king' );
	}
	if ( isset( $fields['billing_address_1'] ) ) {
		$fields['billing_address_1']['label'] = esc_html__( 'Billing address', 'empire-king' );
	}
	return $fields;
}
add_filter( 'woocommerce_checkout_fields', 'empire_king_checkout_billing_address_label', 20 );
add_filter( 'woocommerce_billing_fields', 'empire_king_checkout_billing_address_label', 20 );

/**
 * Refines the Order Received success hero message with an authoritative confirmed eyebrow
 * and prominent title for the digital receipt header.
 *
 * Failed orders are strictly bypassed so they never display confirmed status or success styling.
 *
 * @param string         $text  The default order received message text.
 * @param WC_Order|false $order The order object, or false if not available.
 * @return string Filtered hero HTML markup.
 */
function empire_king_order_received_hero_text( $text, $order ) {
	if ( ! $order || ( method_exists( $order, 'has_status' ) && $order->has_status( 'failed' ) ) ) {
		return $text;
	}

	return '<span class="ek-order-received__eyebrow">' . esc_html__( 'Order Confirmed', 'empire-king' ) . '</span>'
		. '<span class="ek-order-received__title">' . esc_html__( 'Order Received', 'empire-king' ) . '</span>'
		. '<span class="ek-order-received__subcopy">' . $text . '</span>';
}
add_filter( 'woocommerce_thankyou_order_received_text', 'empire_king_order_received_hero_text', 10, 2 );
