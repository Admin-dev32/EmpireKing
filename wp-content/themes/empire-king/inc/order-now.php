<?php
/** Order menu presentation adapters. WooCommerce owns all purchasing and cart rules. */
defined( 'ABSPATH' ) || exit;

/** Optional, deterministic theme-only image slot; never changes catalog media. */
function empire_king_order_now_background() {
	$directory = 'assets/images/order-now/product-card-background/';
	$files = glob( get_theme_file_path( $directory ) . '*', GLOB_NOSORT ) ?: array();
	$files = array_filter( $files, static function ( $file ) {
		return is_file( $file ) && preg_match( '/\.(webp|jpe?g|png)$/i', $file );
	} );
	natcasesort( $files );
	if ( count( $files ) > 1 && defined( 'WP_DEBUG' ) && WP_DEBUG ) {
		error_log( 'Empire King: product-card-background expects one image; using the first filename in natural order.' );
	}
	return $files ? get_theme_file_uri( $directory . rawurlencode( basename( reset( $files ) ) ) ) : '';
}

/** Public read-only presentation endpoint, limited to published menu products. */
function empire_king_order_now_product_sheet() {
	$product_id = isset( $_GET['product_id'] ) ? absint( $_GET['product_id'] ) : 0;
	$selected_product = wc_get_product( $product_id );
	if ( ! $selected_product || 'publish' !== $selected_product->get_status() || ! $selected_product->is_visible() || ! $selected_product->is_type( array( 'simple', 'variable' ) ) ) {
		wp_send_json_error( array( 'message' => __( 'This item is not available here right now.', 'empire-king' ) ), 404 );
	}

	global $product, $post;
	$previous_product = $product;
	$previous_post = $post;
	$product = $selected_product;
	$post = get_post( $product_id );
	ob_start();
	?>
	<div class="product ek-order-now__sheet-product" data-product-id="<?php echo esc_attr( $product_id ); ?>">
		<div class="ek-order-now__sheet-image ek-order-now__product-image"><?php echo $product->get_image( 'woocommerce_single' ); // WooCommerce generates escaped image markup. ?></div>
		<h2 id="order-now-product-title"><?php echo esc_html( $product->get_name() ); ?></h2>
		<div class="ek-order-now__product-price"><?php echo wp_kses_post( $product->get_price_html() ); ?></div>
		<?php if ( $product->get_short_description() ) : ?><div class="ek-order-now__sheet-description"><?php echo wp_kses_post( wpautop( $product->get_short_description() ) ); ?></div><?php endif; ?>
		<?php
		if ( $product->is_type( 'variable' ) ) {
			woocommerce_variable_add_to_cart();
		} else {
			woocommerce_simple_add_to_cart();
		}
		?>
	</div>
	<?php
	$html = ob_get_clean();
	$product = $previous_product;
	$post = $previous_post;
	wp_send_json_success( array( 'html' => $html ) );
}
add_action( 'wc_ajax_empire_king_product_sheet', 'empire_king_order_now_product_sheet' );

/** Include the existing header badge in WooCommerce's normal fragment response. */
function empire_king_order_now_cart_fragment( $fragments ) {
	$count = WC()->cart->get_cart_contents_count();
	$label = sprintf( _n( '%d item in cart', '%d items in cart', $count, 'empire-king' ), $count );
	$fragments['.header-cart-link__count'] = '<span class="header-cart-link__count" aria-hidden="true" data-cart-label="' . esc_attr( $label ) . '">' . esc_html( $count ) . '</span>';
	return $fragments;
}
add_filter( 'woocommerce_add_to_cart_fragments', 'empire_king_order_now_cart_fragment' );
