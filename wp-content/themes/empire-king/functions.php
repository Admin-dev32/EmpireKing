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
	if ( is_page( 'order-now' ) ) {
		wp_enqueue_style( 'empire-king-order-now', get_theme_file_uri( 'assets/css/order-now.css' ), array( 'empire-king-style', 'empire-king-header' ), wp_get_theme()->get( 'Version' ) );
		$order_dependencies = class_exists( 'WooCommerce' ) ? array( 'jquery', 'wc-add-to-cart', 'wc-add-to-cart-variation', 'wc-cart-fragments' ) : array();
		wp_enqueue_script( 'empire-king-order-now', get_theme_file_uri( 'assets/js/order-now.js' ), $order_dependencies, wp_get_theme()->get( 'Version' ), array( 'in_footer' => true, 'strategy' => 'defer' ) );
		if ( class_exists( 'WC_AJAX' ) ) {
			wp_localize_script( 'empire-king-order-now', 'empireKingOrderNow', array( 'sheetUrl' => WC_AJAX::get_endpoint( 'empire_king_product_sheet' ), 'cartUrl' => WC_AJAX::get_endpoint( 'add_to_cart' ) ) );
		}
	}
	if ( is_singular( 'post' ) || is_page( 'deals' ) ) {
		empire_king_enqueue_order_gateway_assets( array( 'empire-king-style' ) );
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
		wp_enqueue_style( 'empire-king-home-menu-glimpse', get_theme_file_uri( 'assets/css/home-menu-glimpse.css' ), array( 'empire-king-home' ), wp_get_theme()->get( 'Version' ) );
		wp_enqueue_script( 'empire-king-home-menu-glimpse', get_theme_file_uri( 'assets/js/home-menu-glimpse.js' ), array(), wp_get_theme()->get( 'Version' ), array( 'in_footer' => true, 'strategy' => 'defer' ) );

		empire_king_enqueue_order_gateway_assets( array( 'empire-king-home' ) );
		wp_enqueue_script(
			'empire-king-home-locations',
			get_theme_file_uri( 'assets/js/home-locations.js' ),
			empire_king_get_google_maps_api_key() ? array( 'empire-king-google-maps-api' ) : array(),
			wp_get_theme()->get( 'Version' ),
			array(
				'in_footer' => true,
				'strategy'  => 'defer',
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

add_filter( 'pre_get_document_title', function ( $title ) {
	return is_page( 'deals' ) ? 'Deals & Specials in Lancaster, CA | Empire King Burger' : $title;
} );
add_action( 'wp_head', function () {
	if ( is_page( 'deals' ) ) echo '<meta name="description" content="Current Empire King Burger deals and specials in Lancaster, California. Browse current offers and choose your restaurant to order online.">' . "\n";
} );

/** Pickup routes are shared by the Home gateway and Blog direct-order flow. */
function empire_king_get_order_gateway_routes() {
	return array(
		'Avenue H' => array( 'pickup' => home_url( '/order-now/' ) ),
		'Avenue I' => array( 'pickup' => 'https://empireking2avei.com/order-now/' ),
	);
}

/** Enqueues the reusable location selector only on pages that render it. */
function empire_king_enqueue_order_gateway_assets( $style_dependencies ) {
	wp_enqueue_style( 'empire-king-order-gateway', get_theme_file_uri( 'assets/css/order-gateway.css' ), $style_dependencies, wp_get_theme()->get( 'Version' ) );
	$maps_api_key = empire_king_get_google_maps_api_key();
	if ( $maps_api_key ) {
		wp_enqueue_script( 'empire-king-google-maps-api', add_query_arg( array( 'key' => $maps_api_key, 'v' => 'weekly' ), 'https://maps.googleapis.com/maps/api/js' ), array(), null, array( 'in_footer' => true, 'strategy' => 'defer' ) );
	}
	wp_enqueue_script( 'empire-king-order-gateway', get_theme_file_uri( 'assets/js/order-gateway.js' ), $maps_api_key ? array( 'empire-king-google-maps-api' ) : array(), wp_get_theme()->get( 'Version' ), array( 'in_footer' => true, 'strategy' => 'defer' ) );
	wp_localize_script( 'empire-king-order-gateway', 'empireKingOrderGateway', array( 'routes' => empire_king_get_order_gateway_routes() ) );
}

/** Prints the one shared location selector for Home and native Blog posts. */
function empire_king_render_order_location_selector() {
	get_template_part( 'template-parts/order-location-selector', null, array( 'maps_key' => empire_king_get_google_maps_api_key() ) );
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
 * Development-only Featured Favorites provider; these are not confirmed menu items.
 * Replace this provider with an approved data adapter later. Keep the category
 * key/label/image and item name/image/alt shape; presentation owns the #order CTA.
 * No transactional catalog, store choice, or remote request belongs here.
 *
 * @return array Prototype categories containing display-only items.
 */
function empire_king_get_featured_favorites() {
	$categories = array(
		'burgers'      => array( 'label' => 'Burgers', 'names' => array( 'Burger Favorite', 'Double Burger Favorite' ) ),
		'chicken'      => array( 'label' => 'Chicken', 'names' => array( 'Chicken Favorite' ) ),
		'meals'        => array( 'label' => 'Meals', 'names' => array( 'Meal Favorite' ) ),
		'family-packs' => array( 'label' => 'Family Packs', 'names' => array( 'Family Pack Favorite' ) ),
	);
	foreach ( $categories as &$category ) {
		$category['image'] = '';
		$category['items'] = array_map(
			static function ( $name ) {
				return array( 'name' => $name, 'tag' => 'FEATURED', 'image' => '', 'alt' => '' );
			},
			$category['names']
		);
		unset( $category['names'] );
	}
	unset( $category );
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

/** Gets valid curated background and transparent foreground sequences for Home Menu Glimpse. */
function empire_king_get_home_menu_glimpse() {
	$directory  = get_theme_file_path( 'assets/images/home-menu-glimpse' );
	$folders    = glob( $directory . '/*', GLOB_ONLYDIR );
	$categories = array();
	$order_category_map = array(
		'burgers'      => 'burger',
		'sandwiches'   => 'sandwich',
		'fries'        => 'fries',
		'salads'       => 'salads',
		'drinks'       => 'drinks',
		'ice-cream'    => 'ice-cream',
		'family-packs' => 'family-pack-combos',
	);
	foreach ( false === $folders ? array() : $folders as $folder ) {
		$key         = sanitize_title( basename( $folder ) );
		$backgrounds = array_merge(
			glob( $folder . '/background/*.webp' ) ?: array(),
			glob( $folder . '/background/*.jpg' ) ?: array(),
			glob( $folder . '/background/*.jpeg' ) ?: array(),
			glob( $folder . '/background/*.png' ) ?: array()
		);
		$foregrounds = glob( $folder . '/foreground/*.png' );
		$backgrounds = false === $backgrounds ? array() : array_filter( $backgrounds, 'is_file' );
		$foregrounds = false === $foregrounds ? array() : array_filter( $foregrounds, 'is_file' );
		natsort( $backgrounds );
		natsort( $foregrounds );
		$foregrounds = array_slice( array_values( $foregrounds ), 0, 3 );
		if ( ! $key || 1 !== count( $backgrounds ) || ! $foregrounds ) continue;
		$label = ucwords( str_replace( '-', ' ', $key ) );
		$categories[] = array(
			'key'         => $key,
			'name'        => $label,
			'order_category' => isset( $order_category_map[ $key ] ) ? $order_category_map[ $key ] : '',
			'background'  => get_theme_file_uri( 'assets/images/home-menu-glimpse/' . rawurlencode( basename( $folder ) ) . '/background/' . rawurlencode( basename( reset( $backgrounds ) ) ) ),
			'foregrounds' => array_map( static function ( $file ) use ( $folder, $label ) {
				return array( 'url' => get_theme_file_uri( 'assets/images/home-menu-glimpse/' . rawurlencode( basename( $folder ) ) . '/foreground/' . rawurlencode( basename( $file ) ) ), 'alt' => $label . ' food' );
			}, array_values( $foregrounds ) ),
		);
	}
	$priority = array( 'burgers', 'sandwiches', 'chicken', 'fries', 'salads', 'drinks', 'ice-cream', 'family-packs' );
	usort( $categories, static function ( $a, $b ) use ( $priority ) {
		$a_rank = array_search( $a['key'], $priority, true );
		$b_rank = array_search( $b['key'], $priority, true );
		$a_rank = false === $a_rank ? count( $priority ) : $a_rank;
		$b_rank = false === $b_rank ? count( $priority ) : $b_rank;
		return $a_rank <=> $b_rank ?: strnatcasecmp( $a['name'], $b['name'] );
	} );
	return array( 'mode' => $categories ? 'curated' : 'empty', 'categories' => $categories );
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
