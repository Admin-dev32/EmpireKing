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
	$cart_item_key = isset( $_GET['cart_item_key'] ) ? sanitize_text_field( wp_unslash( $_GET['cart_item_key'] ) ) : '';
	$edit_data = null;

	// When opened in edit mode from Review Your Order, retrieve current line configuration
	if ( $cart_item_key && function_exists( 'WC' ) && WC()->cart ) {
		$cart_item = WC()->cart->get_cart_item( $cart_item_key );
		if ( $cart_item ) {
			$product_id = ! empty( $cart_item['product_id'] ) ? $cart_item['product_id'] : $product_id;
			$edit_data  = array(
				'cart_item_key' => $cart_item_key,
				'quantity'      => isset( $cart_item['quantity'] ) ? $cart_item['quantity'] : 1,
				'variation_id'  => isset( $cart_item['variation_id'] ) ? $cart_item['variation_id'] : 0,
				'variation'     => isset( $cart_item['variation'] ) && is_array( $cart_item['variation'] ) ? $cart_item['variation'] : array(),
				'wapf'          => isset( $cart_item['wapf'] ) && is_array( $cart_item['wapf'] ) ? $cart_item['wapf'] : array(),
			);
		}
	}

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

	$response_data = array( 'html' => $html );
	if ( $edit_data ) {
		$response_data['edit_data'] = $edit_data;
	}
	wp_send_json_success( $response_data );
}
add_action( 'wc_ajax_empire_king_product_sheet', 'empire_king_order_now_product_sheet' );

/** Return and consume errors handled by the custom product sheet only. */
function empire_king_order_now_sheet_error_notices() {
	check_ajax_referer( 'empire_king_sheet_error_notices', 'security' );

	$notices = wc_get_notices();
	$errors  = isset( $notices['error'] ) ? $notices['error'] : array();
	$messages = array();
	foreach ( $errors as $error ) {
		$message = is_array( $error ) && isset( $error['notice'] ) ? $error['notice'] : $error;
		if ( $message ) {
			$messages[] = wp_strip_all_tags( $message );
		}
	}

	// The sheet renders these errors itself, so retain non-error notices but prevent a later cart-page leak.
	unset( $notices['error'] );
	wc_set_notices( $notices );

	wp_send_json_success( array( 'messages' => $messages ) );
}
add_action( 'wc_ajax_empire_king_sheet_error_notices', 'empire_king_order_now_sheet_error_notices' );

/**
 * Safely updates an existing cart item with modified variations, APF add-ons, or quantity.
 *
 * Order integrity guarantee:
 * 1. Validates nonces and checks that the targeted cart item exists in the current session.
 * 2. Tests the replacement configuration against native WooCommerce & APF validation hooks.
 * 3. Retains an in-memory snapshot of the cart prior to removing the old item, preventing
 *    duplicate cart lines while ensuring zero-loss rollback if the replacement fails.
 * 4. Adds the validated replacement through native WC()->cart->add_to_cart(), ensuring APF's
 *    hooks attach add-on metadata and calculate prices authoritatively.
 * 5. Recalculates authoritative totals and responds with redirect to refresh the cart.
 */
function empire_king_ajax_edit_cart_item() {
	check_ajax_referer( 'empire_king_edit_cart_item', 'security' );

	$cart = function_exists( 'WC' ) && WC()->cart ? WC()->cart : null;
	if ( ! $cart ) {
		wp_send_json_error( array( 'message' => __( 'Cart unavailable.', 'empire-king' ) ), 400 );
	}

	$cart_item_key = isset( $_POST['cart_item_key'] ) ? sanitize_text_field( wp_unslash( $_POST['cart_item_key'] ) ) : '';
	$original_item = $cart->get_cart_item( $cart_item_key );
	if ( ! $original_item ) {
		wp_send_json_error( array( 'message' => __( 'This item is no longer in your cart.', 'empire-king' ) ), 404 );
	}

	$product_id   = isset( $_POST['product_id'] ) ? absint( $_POST['product_id'] ) : $original_item['product_id'];
	$quantity     = isset( $_POST['quantity'] ) ? wc_stock_amount( wp_unslash( $_POST['quantity'] ) ) : $original_item['quantity'];
	$variation_id = isset( $_POST['variation_id'] ) ? absint( $_POST['variation_id'] ) : $original_item['variation_id'];
	$variations   = array();

	foreach ( $_POST as $key => $value ) {
		if ( 0 === strpos( $key, 'attribute_' ) ) {
			$variations[ sanitize_text_field( $key ) ] = wp_unslash( $value );
		}
	}

	// Clear previous notices to isolate errors from this validation pass
	wc_clear_notices();

	// Validate the replacement item with WooCommerce and APF filters
	$passed_validation = apply_filters( 'woocommerce_add_to_cart_validation', true, $product_id, $quantity, $variation_id, $variations );

	if ( ! $passed_validation ) {
		$notices = wc_get_notices( 'error' );
		$message = ! empty( $notices ) ? wp_strip_all_tags( $notices[0]['notice'] ) : __( 'Could not validate your product options.', 'empire-king' );
		wc_clear_notices();
		wp_send_json_error( array( 'message' => $message ) );
	}

	// Rollback snapshot: preserve entire cart contents in case add_to_cart fails
	$cart_snapshot = $cart->cart_contents;

	// Unset old line item before adding replacement to ensure only ONE line remains
	unset( $cart->cart_contents[ $cart_item_key ] );

	// Native add_to_cart triggers APF's woocommerce_add_cart_item_data to parse $_REQUEST['wapf']
	$new_cart_item_key = $cart->add_to_cart( $product_id, $quantity, $variation_id, $variations );

	if ( ! $new_cart_item_key ) {
		// Rollback: restore previous cart contents intact
		$cart->cart_contents = $cart_snapshot;
		$cart->calculate_totals();
		$notices = wc_get_notices( 'error' );
		$message = ! empty( $notices ) ? wp_strip_all_tags( $notices[0]['notice'] ) : __( 'Could not update item. Original item has been preserved.', 'empire-king' );
		wc_clear_notices();
		wp_send_json_error( array( 'message' => $message ) );
	}

	// Authoritatively calculate totals and persist session
	$cart->calculate_totals();
	WC()->session->set( 'cart', $cart->get_cart_for_session() );

	wc_add_notice( __( 'Cart updated.', 'woocommerce' ), 'success' );

	wp_send_json_success( array(
		'message'           => __( 'Cart updated.', 'empire-king' ),
		'new_cart_item_key' => $new_cart_item_key,
		'redirect'          => wc_get_cart_url(),
	) );
}
add_action( 'wc_ajax_empire_king_edit_cart_item', 'empire_king_ajax_edit_cart_item' );

/**
 * Redirect direct visits from generic WooCommerce single-product pages to the
 * interactive transactional menu on /order-now/, preserving the product ID so the
 * menu sheet can automatically open that item for customization.
 *
 * Uses a permanent 301 redirect for SEO consolidation. Avoids admin product editing,
 * AJAX requests, REST API, cron, and redirect loops.
 */
function empire_king_redirect_single_product_pages() {
	if ( is_admin() || wp_doing_ajax() || wp_doing_cron() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
		return;
	}
	if ( function_exists( 'is_product' ) && is_product() ) {
		$product_id = get_queried_object_id();
		$target_url = home_url( '/order-now/' );
		if ( $product_id ) {
			$target_url = add_query_arg( 'product_id', $product_id, $target_url );
		}
		wp_safe_redirect( $target_url, 301 );
		exit;
	}
}
add_action( 'template_redirect', 'empire_king_redirect_single_product_pages' );

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
		<a class="ek-order-now__checkout-action" href="<?php echo esc_url( wc_get_cart_url() ); ?>" data-order-upsell-trigger><?php esc_html_e( 'Review Order', 'empire-king' ); ?></a>
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

/** Use the assigned commerce pages' classic shortcodes in memory, before asset detection. */
add_filter( 'the_posts', static function ( $posts, $query ) {
	if ( is_admin() || ! $query->is_main_query() || ! $query->is_page() || ! function_exists( 'wc_get_page_id' ) ) {
		return $posts;
	}
	foreach ( $posts as $index => $page ) {
		foreach ( array( 'cart', 'checkout' ) as $kind ) {
			if ( (int) $page->ID === wc_get_page_id( $kind ) ) {
				$posts[ $index ] = clone $page;
				$posts[ $index ]->post_content = '[woocommerce_' . $kind . ']';
			}
		}
	}
	return $posts;
}, 10, 2 );

/** Review-page heading; the shortcode continues to own all cart output. */
add_filter( 'the_content', static function ( $content ) {
	if ( ! function_exists( 'is_cart' ) || ! is_cart() || ! in_the_loop() || ! is_main_query() ) {
		return $content;
	}
	return '<div class="ek-cart-page__intro"><a class="ek-cart-page__back-link" href="' . esc_url( home_url( '/order-now/' ) ) . '">' . esc_html__( 'Back to Menu', 'empire-king' ) . '</a><h1>' . esc_html__( 'Review Your Order', 'empire-king' ) . '</h1></div>' . $content;
}, 20 );
add_filter( 'wc_empty_cart_message', static function ( $text ) {
	return is_cart() ? __( 'Your order is empty', 'empire-king' ) : $text;
} );
add_filter( 'woocommerce_return_to_shop_redirect', static function ( $url ) {
	return is_cart() ? home_url( '/order-now/' ) : $url;
} );
add_filter( 'woocommerce_return_to_shop_text', static function ( $text ) {
	return is_cart() ? __( 'Browse Menu', 'empire-king' ) : $text;
} );

/** Keep product title clean on variable items and let WooCommerce render variation attributes in cart item metadata. */
add_filter( 'woocommerce_product_variation_title_include_attributes', '__return_false' );

/** Base checkout intro; classic shortcode continues to own all checkout output. */
add_filter( 'the_content', static function ( $content ) {
	if ( ! function_exists( 'is_checkout' ) || ! is_checkout() || is_wc_endpoint_url() || ! in_the_loop() || ! is_main_query() ) {
		return $content;
	}
	$cart_url = function_exists( 'wc_get_cart_url' ) ? wc_get_cart_url() : home_url( '/cart/' );
	$intro = '<div class="ek-checkout-page__intro">'
		. '<a class="ek-checkout-page__back-link" href="' . esc_url( $cart_url ) . '">' . esc_html__( 'Back to Review Order', 'empire-king' ) . '</a>'
		. '<h1>' . esc_html__( 'Checkout', 'empire-king' ) . '</h1>'
		. '</div>';
	return $intro . $content;
}, 20 );

/** Distinct body class for base checkout page to scope styling away from endpoints. */
add_filter( 'body_class', static function ( $classes ) {
	if ( function_exists( 'is_checkout' ) && is_checkout() && ! is_wc_endpoint_url() ) {
		$classes[] = 'ek-base-checkout';
	}
	return $classes;
} );

/** Wrap order review heading and order review container into a unified column for layout and sticky positioning. */
add_action( 'woocommerce_checkout_before_order_review_heading', static function () {
	echo '<div class="ek-checkout-summary-col">';
}, 5 );
add_action( 'woocommerce_checkout_after_order_review', static function () {
	echo '</div>';
}, 50 );

/** Use 'Order Summary' heading on base checkout to match Review Order terminology. */
add_filter( 'gettext', static function ( $translation, $text, $domain ) {
	if ( 'woocommerce' === $domain && 'Your order' === $text && function_exists( 'is_checkout' ) && is_checkout() && ! is_wc_endpoint_url() ) {
		return __( 'Order Summary', 'empire-king' );
	}
	return $translation;
}, 10, 3 );
