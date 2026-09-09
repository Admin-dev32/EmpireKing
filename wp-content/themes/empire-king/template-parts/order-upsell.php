<?php
/** Order Now smart-upsell dialog; its cart-derived contents load on demand. */
defined( 'ABSPATH' ) || exit;
?>
<dialog class="ek-order-upsell" data-order-upsell aria-labelledby="order-upsell-title">
	<button class="ek-order-upsell__close" type="button" data-order-upsell-close aria-label="<?php esc_attr_e( 'Close recommendations', 'empire-king' ); ?>">
		<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
			<line x1="18" y1="6" x2="6" y2="18"></line>
			<line x1="6" y1="6" x2="18" y2="18"></line>
		</svg>
	</button>
	<div class="ek-order-upsell__header">
		<p class="ek-order-upsell__eyebrow"><?php esc_html_e( 'One More Thing', 'empire-king' ); ?></p>
		<h2 id="order-upsell-title"><?php esc_html_e( 'Complete Your Order', 'empire-king' ); ?></h2>
		<p class="ek-order-upsell__subcopy"><?php esc_html_e( 'A few favorites that go great with what you picked.', 'empire-king' ); ?></p>
	</div>
	<div class="ek-order-upsell__scroll-area">
		<div class="ek-order-upsell__products" data-order-upsell-products></div>
	</div>
	<p class="ek-order-upsell__status" data-order-upsell-status role="status" aria-live="polite"></p>
	<div class="ek-order-upsell__footer">
		<a class="ek-order-upsell__continue" href="<?php echo esc_url( wc_get_cart_url() ); ?>" data-order-upsell-continue><?php esc_html_e( 'Continue to Review Order', 'empire-king' ); ?></a>
	</div>
</dialog>
