<?php
/**
 * Theme setup and assets for Empire King.
 *
 * @package Empire_King
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

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

	if ( is_front_page() ) {
		wp_enqueue_style(
			'empire-king-home',
			get_theme_file_uri( 'assets/css/home.css' ),
			array( 'empire-king-style' ),
			wp_get_theme()->get( 'Version' )
		);
	}
}
add_action( 'wp_enqueue_scripts', 'empire_king_enqueue_styles' );

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
	$files          = array();

	foreach ( $extensions as $extension ) {
		$matches = glob( $logo_directory . '/*.' . $extension );
		if ( false !== $matches ) {
			$files = array_merge( $files, $matches );
		}
	}

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
 * Prints approved fallback navigation when a WordPress menu is not assigned.
 *
 * @param array<string, mixed> $args Navigation arguments.
 */
function empire_king_primary_nav_fallback( $args = array() ) {
	$menu_class = isset( $args['menu_class'] ) ? $args['menu_class'] : 'primary-menu';
	?>
	<ul class="<?php echo esc_attr( $menu_class ); ?>">
		<li><a href="<?php echo esc_url( home_url( '/#locations' ) ); ?>"><?php esc_html_e( 'Menu', 'empire-king' ); ?></a></li>
		<li><a href="<?php echo esc_url( home_url( '/#deals' ) ); ?>"><?php esc_html_e( 'Deals', 'empire-king' ); ?></a></li>
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
