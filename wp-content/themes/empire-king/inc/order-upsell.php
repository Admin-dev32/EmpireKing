<?php
/** Live cart-derived recommendations for the Order Now upsell. */
defined( 'ABSPATH' ) || exit;

/** Central menu-role map shared by every location using this theme. */
function empire_king_order_upsell_role_map() {
	return array(
		'family'     => array( 'family-pack-combos' ),
		'light_main' => array( 'salads', 'special-healthy-meal' ),
		'main'       => array( 'burger', 'sandwich', 'baskets', 'all-combos', 'big-boys-combo', 'daily-specials', 'kids-meals' ),
		'side'       => array( 'fries', 'sides' ),
		'drink'      => array( 'drinks' ),
		'dessert'    => array( 'ice-cream' ),
	);
}

/** Return the most specific menu role represented by a product's categories. */
function empire_king_order_upsell_product_role( $product_id ) {
	$slugs = wp_get_post_terms( $product_id, 'product_cat', array( 'fields' => 'slugs' ) );
	if ( is_wp_error( $slugs ) ) {
		return '';
	}
	foreach ( empire_king_order_upsell_role_map() as $role => $role_slugs ) {
		if ( array_intersect( $slugs, $role_slugs ) ) {
			return $role;
		}
	}
	return '';
}

/** Only products supported by the existing shared product sheet may be offered. */
function empire_king_order_upsell_is_valid_product( $product, $excluded_ids ) {
	return $product instanceof WC_Product
		&& ! in_array( $product->get_id(), $excluded_ids, true )
		&& 'publish' === $product->get_status()
		&& $product->is_visible()
		&& $product->is_purchasable()
		&& $product->is_in_stock()
		&& $product->is_type( array( 'simple', 'variable' ) );
}

/** Popularity breaks role ties; stable catalog ordering keeps zero-sales stores deterministic. */
function empire_king_order_upsell_compare_products( $left, $right ) {
	$sales_order = $right->get_total_sales() <=> $left->get_total_sales();
	if ( 0 !== $sales_order ) {
		return $sales_order;
	}
	$menu_order = $left->get_menu_order() <=> $right->get_menu_order();
	if ( 0 !== $menu_order ) {
		return $menu_order;
	}
	$name_order = strnatcasecmp( $left->get_name(), $right->get_name() );
	return 0 !== $name_order ? $name_order : $left->get_id() <=> $right->get_id();
}

/** Choose complementary roles, promoting whichever part of a meal is absent. */
function empire_king_order_upsell_target_roles( $cart_roles ) {
	$has = static function ( $role ) use ( $cart_roles ) {
		return in_array( $role, $cart_roles, true );
	};
	if ( $has( 'family' ) ) {
		return array( 'drink', 'dessert', 'side' );
	}
	if ( $has( 'main' ) || $has( 'light_main' ) ) {
		$targets = array();
		if ( ! $has( 'side' ) ) {
			$targets[] = 'side';
		}
		if ( ! $has( 'drink' ) ) {
			$targets[] = 'drink';
		}
		$targets[] = 'dessert';
		return array_values( array_unique( array_merge( $targets, array( 'side', 'drink' ) ) ) );
	}
	if ( $has( 'side' ) ) {
		return array( 'main', 'drink', 'dessert' );
	}
	if ( $has( 'drink' ) ) {
		return array( 'main', 'side', 'dessert' );
	}
	if ( $has( 'dessert' ) ) {
		return array( 'main', 'drink', 'side' );
	}
	return array( 'side', 'drink', 'main', 'dessert' );
}

/** Build up to four live recommendations without mutating the cart or product data. */
function empire_king_get_order_upsell_products( $limit = 4 ) {
	$cart = function_exists( 'WC' ) && WC()->cart ? WC()->cart : null;
	if ( ! $cart || $cart->is_empty() ) {
		return array();
	}

	$excluded_ids   = array();
	$cross_sell_ids = array();
	$cart_roles     = array();
	foreach ( $cart->get_cart() as $cart_item ) {
		// Parent IDs prevent recommending another variation of an item already represented.
		$product_id     = (int) $cart_item['product_id'];
		$excluded_ids[] = $product_id;
		$cart_roles[]   = empire_king_order_upsell_product_role( $product_id );
		$cart_product   = wc_get_product( $product_id );
		if ( $cart_product ) {
			$cross_sell_ids = array_merge( $cross_sell_ids, $cart_product->get_cross_sell_ids() );
		}
	}
	$excluded_ids = array_values( array_unique( array_filter( $excluded_ids ) ) );

	$recommendations = array();
	// Merchandised Woo cross-sells are explicit intent, so they always precede inferred fallbacks.
	foreach ( array_unique( array_map( 'absint', $cross_sell_ids ) ) as $cross_sell_id ) {
		$product = wc_get_product( $cross_sell_id );
		if ( empire_king_order_upsell_is_valid_product( $product, $excluded_ids ) ) {
			$recommendations[ $product->get_id() ] = $product;
			if ( count( $recommendations ) >= $limit ) {
				return array_values( $recommendations );
			}
		}
	}

	$by_role = array();
	$candidates = wc_get_products(
		array(
			'status'  => 'publish',
			'limit'   => -1,
			'type'    => array( 'simple', 'variable' ),
			'orderby' => 'menu_order',
			'order'   => 'ASC',
		)
	);
	foreach ( $candidates as $candidate ) {
		if ( ! empire_king_order_upsell_is_valid_product( $candidate, $excluded_ids ) || isset( $recommendations[ $candidate->get_id() ] ) ) {
			continue;
		}
		$role = empire_king_order_upsell_product_role( $candidate->get_id() );
		if ( $role ) {
			$by_role[ $role ][] = $candidate;
		}
	}
	foreach ( $by_role as &$role_products ) {
		usort( $role_products, 'empire_king_order_upsell_compare_products' );
	}
	unset( $role_products );

	$target_roles = empire_king_order_upsell_target_roles( array_values( array_unique( array_filter( $cart_roles ) ) ) );
	// Take one from each complementary role before filling extra slots from the same role.
	foreach ( $target_roles as $role ) {
		if ( ! empty( $by_role[ $role ] ) ) {
			$product = array_shift( $by_role[ $role ] );
			$recommendations[ $product->get_id() ] = $product;
			if ( count( $recommendations ) >= $limit ) {
				return array_values( $recommendations );
			}
		}
	}
	foreach ( $target_roles as $role ) {
		foreach ( $by_role[ $role ] ?? array() as $product ) {
			$recommendations[ $product->get_id() ] = $product;
			if ( count( $recommendations ) >= $limit ) {
				return array_values( $recommendations );
			}
		}
	}
	return array_values( $recommendations );
}

/** Public read-only endpoint; Woo's session supplies the current anonymous cart. */
function empire_king_order_upsell_endpoint() {
	$products = empire_king_get_order_upsell_products();
	ob_start();
	foreach ( $products as $product ) {
		?>
		<article class="ek-order-upsell__product" data-order-now-product>
			<div class="ek-order-upsell__image ek-order-now__product-image"><?php echo $product->get_image( 'woocommerce_thumbnail', array( 'loading' => 'lazy', 'decoding' => 'async' ) ); // WooCommerce returns escaped image markup. ?></div>
			<div class="ek-order-upsell__copy">
				<h3><?php echo esc_html( $product->get_name() ); ?></h3>
				<div class="ek-order-upsell__price ek-order-now__product-price"><?php echo wp_kses_post( $product->get_price_html() ); ?></div>
				<button type="button" data-order-now-open="<?php echo esc_attr( $product->get_id() ); ?>"><?php esc_html_e( 'Add to Order', 'empire-king' ); ?></button>
			</div>
		</article>
		<?php
	}
	wp_send_json_success(
		array(
			'count' => count( $products ),
			'html'  => ob_get_clean(),
		)
	);
}
add_action( 'wc_ajax_empire_king_order_upsell', 'empire_king_order_upsell_endpoint' );
