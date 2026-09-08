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
			?><h3 class="ek-order-now__customize-heading"><?php esc_html_e( 'Customize your order', 'empire-king' ); ?></h3><?php
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

/** Render the page-local Woo checkout bar from the authoritative cart session. */
function empire_king_order_now_checkout_bar() {
	$cart = function_exists( 'WC' ) && WC()->cart ? WC()->cart : null;
	$count = $cart ? $cart->get_cart_contents_count() : 0;
	$item_label = sprintf( _n( '%d item', '%d items', $count, 'empire-king' ), $count );
	$label = sprintf( __( 'Your Order, %s', 'empire-king' ), $item_label );
	ob_start();
	?>
	<div class="ek-order-now__checkout-bar" data-order-now-checkout<?php echo $count ? '' : ' hidden'; ?>>
		<a class="ek-order-now__checkout-cart" href="<?php echo esc_url( wc_get_cart_url() ); ?>" aria-label="<?php echo esc_attr( $label ); ?>">
			<svg aria-hidden="true" viewBox="0 0 24 24"><path d="M3 4h2l2 11h10l2-8H6" /><circle cx="9" cy="19" r="1" /><circle cx="17" cy="19" r="1" /></svg>
			<span class="ek-order-now__checkout-summary"><strong><?php esc_html_e( 'Your Order', 'empire-king' ); ?></strong><small><span><?php echo esc_html( $item_label ); ?></span><b aria-hidden="true">&middot;</b><span class="ek-order-now__checkout-subtotal"><?php echo wp_kses_post( $cart ? $cart->get_cart_subtotal() : '' ); ?></span></small></span>
		</a>
		<a class="ek-order-now__checkout-action" href="<?php echo esc_url( wc_get_cart_url() ); ?>"><?php esc_html_e( 'Review Order', 'empire-king' ); ?></a>
	</div>
	<?php
	return ob_get_clean();
}

/** Include page-local cart controls in WooCommerce's normal fragment response. */
function empire_king_order_now_cart_fragment( $fragments ) {
	$count = WC()->cart->get_cart_contents_count();
	$label = sprintf( _n( '%d item in cart', '%d items in cart', $count, 'empire-king' ), $count );
	$fragments['.header-cart-link__count'] = '<span class="header-cart-link__count" aria-hidden="true" data-cart-label="' . esc_attr( $label ) . '">' . esc_html( $count ) . '</span>';
	$fragments['.ek-order-now__checkout-bar'] = empire_king_order_now_checkout_bar();
	return $fragments;
}
add_filter( 'woocommerce_add_to_cart_fragments', 'empire_king_order_now_cart_fragment' );

/** Add review-page context around the existing WooCommerce Cart Block. */
function empire_king_cart_review_intro( $content ) {
	if ( ! is_cart() || ! in_the_loop() || ! is_main_query() ) {
		return $content;
	}

	$intro = sprintf(
		'<div class="ek-cart-page__intro"><a class="ek-cart-page__back-link" href="%1$s">%2$s</a><h1>%3$s</h1></div>',
		esc_url( home_url( '/order-now/' ) ),
		esc_html__( 'Back to Menu', 'empire-king' ),
		esc_html__( 'Review Your Order', 'empire-king' )
	);

	$empty_heading = '<h2 class="wp-block-heading has-text-align-center with-empty-cart-icon wc-block-cart__empty-cart__title">Your cart is currently empty!</h2>';
	$empty_replacement = '<h2 class="wp-block-heading has-text-align-center with-empty-cart-icon wc-block-cart__empty-cart__title">' . esc_html__( 'Your order is empty', 'empire-king' ) . '</h2><a class="ek-cart-empty__browse" href="' . esc_url( home_url( '/order-now/' ) ) . '">' . esc_html__( 'Browse Menu', 'empire-king' ) . '</a>';

	return $intro . str_replace( $empty_heading, $empty_replacement, $content );
}
add_filter( 'the_content', 'empire_king_cart_review_intro', 20 );
