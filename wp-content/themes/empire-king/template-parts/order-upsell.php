<?php
/** Order Now smart-upsell dialog; its cart-derived contents load on demand. */
defined( 'ABSPATH' ) || exit;
?>
<dialog class="ek-order-upsell" data-order-upsell aria-labelledby="order-upsell-title">
	<button class="ek-order-upsell__close" type="button" data-order-upsell-close aria-label="<?php esc_attr_e( 'Close recommendations', 'empire-king' ); ?>">&times;</button>
	<div class="ek-order-upsell__header">
		<p><?php esc_html_e( 'Made for your order', 'empire-king' ); ?></p>
		<h2 id="order-upsell-title"><?php esc_html_e( 'Complete Your Order', 'empire-king' ); ?></h2>
		<span><?php esc_html_e( 'A few favorites that go great with what you picked.', 'empire-king' ); ?></span>
	</div>
	<div class="ek-order-upsell__products" data-order-upsell-products></div>
	<p class="ek-order-upsell__status" data-order-upsell-status role="status" aria-live="polite"></p>
	<a class="ek-order-upsell__continue" href="<?php echo esc_url( wc_get_cart_url() ); ?>" data-order-upsell-continue><?php esc_html_e( 'Continue to Review Order', 'empire-king' ); ?></a>
</dialog>
