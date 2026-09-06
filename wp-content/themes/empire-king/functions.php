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

function empire_king_primary_nav_fallback() {
	?>
	<ul class="primary-menu">
		<li><a href="#favorites"><?php esc_html_e( 'Menu', 'empire-king' ); ?></a></li>
		<li><a href="#deals"><?php esc_html_e( 'Deals', 'empire-king' ); ?></a></li>
		<li><a href="#locations"><?php esc_html_e( 'Locations', 'empire-king' ); ?></a></li>
		<li><a href="#about"><?php esc_html_e( 'About', 'empire-king' ); ?></a></li>
		<li><a class="header-order-link" href="#locations"><?php esc_html_e( 'Order Now', 'empire-king' ); ?></a></li>
	</ul>
	<?php
}
