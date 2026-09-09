<?php
/** Avenue H transactional menu with a reusable WooCommerce product sheet. */
$default_category_id = (int) get_option( 'default_product_cat' );
$categories = get_terms( array( 'taxonomy' => 'product_cat', 'hide_empty' => true, 'exclude' => $default_category_id ? array( $default_category_id ) : array() ) );
$categories = is_wp_error( $categories ) ? array() : $categories;
usort( $categories, static function ( $a, $b ) { if ( 'burger' === $a->slug ) return -1; if ( 'burger' === $b->slug ) return 1; return strnatcasecmp( $a->name, $b->name ); } );
$category_slugs = wp_list_pluck( $categories, 'slug' );
$active_category = $categories ? $categories[0]->slug : '';
$products = function_exists( 'wc_get_products' ) ? wc_get_products( array( 'status' => 'publish', 'limit' => -1, 'type' => array( 'simple', 'variable', 'grouped', 'external' ), 'orderby' => 'menu_order', 'order' => 'ASC' ) ) : array();
$location_routes = empire_king_get_order_gateway_routes();
$avenue_i_url = isset( $location_routes['Avenue I']['pickup'] ) ? $location_routes['Avenue I']['pickup'] : '';
$product_background = empire_king_order_now_background();
get_header();
?>
<div class="ek-order-now"<?php if ( $product_background ) : ?> style="--ek-product-background: url('<?php echo esc_url( $product_background ); ?>')"<?php endif; ?>>
	<section class="ek-order-now__hero" aria-labelledby="order-now-title">
		<details class="ek-order-now__location" data-order-now-store><summary><svg aria-hidden="true" viewBox="0 0 24 24"><path d="M12 21s7-6.1 7-12a7 7 0 1 0-14 0c0 5.9 7 12 7 12Z" /><circle cx="12" cy="9" r="2.25" /></svg><span>Avenue H</span><svg class="ek-order-now__location-chevron" aria-hidden="true" viewBox="0 0 24 24"><path d="m7 10 5 5 5-5" /></svg></summary><div class="ek-order-now__location-options"><button type="button" data-order-now-current>Avenue H</button><?php if ( $avenue_i_url ) : ?><a href="<?php echo esc_url( $avenue_i_url ); ?>">Avenue I</a><?php endif; ?></div></details>
		<div class="ek-order-now__hero-copy"><h1 id="order-now-title"><span>Order</span> <strong>Online</strong></h1><p>Fresh made to order.</p></div>
		<div class="ek-order-now__hero-media" aria-hidden="true"></div>
	</section>
	<section class="ek-order-now__menu-surface" aria-label="Order menu categories">
		<?php if ( $categories ) : ?><nav class="ek-order-now__categories" aria-label="Product categories" data-order-now-categories><?php foreach ( $categories as $index => $category ) : $category_url = get_term_link( $category ); if ( ! is_wp_error( $category_url ) ) : ?><a class="ek-order-now__category<?php echo 0 === $index ? ' is-active' : ''; ?>" href="<?php echo esc_url( $category_url ); ?>" data-order-now-category="<?php echo esc_attr( $category->slug ); ?>"><?php echo esc_html( $category->name ); ?></a><?php endif; endforeach; ?></nav><?php endif; ?>
		<div class="ek-order-now__products" data-order-now-products>
			<?php foreach ( $products as $product ) : $product_categories = array_values( array_intersect( wp_get_post_terms( $product->get_id(), 'product_cat', array( 'fields' => 'slugs' ) ), $category_slugs ) ); if ( $product_categories ) : $image_id = $product->get_image_id(); $short_description = $product->get_short_description(); ?>
				<article class="ek-order-now__product" data-order-now-product data-categories="<?php echo esc_attr( implode( ' ', $product_categories ) ); ?>"<?php echo $active_category && ! in_array( $active_category, $product_categories, true ) ? ' hidden' : ''; ?>><?php if ( $image_id ) : ?><div class="ek-order-now__product-image"><?php echo wp_get_attachment_image( $image_id, 'woocommerce_thumbnail', false, array( 'alt' => $product->get_name(), 'loading' => 'lazy', 'decoding' => 'async' ) ); ?></div><?php endif; ?><div class="ek-order-now__product-copy"><h2><a class="ek-order-now__product-link" href="<?php echo esc_url( $product->get_permalink() ); ?>" data-order-now-open="<?php echo esc_attr( $product->get_id() ); ?>"><?php echo esc_html( $product->get_name() ); ?></a></h2><?php if ( $product->get_price_html() ) : ?><p class="ek-order-now__product-price"><?php echo wp_kses_post( $product->get_price_html() ); ?></p><?php endif; ?><?php if ( $short_description ) : ?><div class="ek-order-now__product-description"><?php echo wp_kses_post( $short_description ); ?></div><?php endif; ?></div></article>
			<?php endif; endforeach; ?>
		</div>
		<p class="screen-reader-text" data-order-now-status role="status" aria-atomic="true"></p>
	</section>
	<?php get_template_part( 'template-parts/product-sheet' ); ?>
	<?php get_template_part( 'template-parts/order-upsell' ); ?>
	<?php echo empire_king_order_now_checkout_bar(); // WooCommerce escapes cart values in its own formatted subtotal markup. ?>
</div>
<?php get_footer(); ?>
