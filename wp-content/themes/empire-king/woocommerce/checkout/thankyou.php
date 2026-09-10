<?php
/**
 * Empire King — Order Received / Digital Receipt Template
 *
 * Overrides woocommerce/templates/checkout/thankyou.php.
 *
 * Hooks preserved (in order):
 *   woocommerce_before_thankyou
 *   woocommerce_thankyou_{payment_method}   (successful orders)
 *   woocommerce_thankyou                    (successful orders)
 *
 * The default woocommerce_order_details_table callback is suppressed only for
 * the duration of this template to prevent it from duplicating the custom
 * receipt below. Every other woocommerce_thankyou callback is untouched.
 *
 * Failed orders render the native Pay / My Account actions; "Order Confirmed"
 * is never shown for failed orders (guarded in functions.php filter too).
 *
 * @package Empire_King
 * @see     WC_Shortcode_Checkout::order_received()
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** @var WC_Order|false $order */
?>
<div class="woocommerce-order">

	<?php
	/*
	 * ── BEFORE THANKYOU ─────────────────────────────────────────────────────
	 * Payment gateway instructions, nonces, or any plugin output that must
	 * appear before the receipt body belong here.
	 */
	do_action( 'woocommerce_before_thankyou', $order ? $order->get_id() : 0 );

	if ( $order ) :

		$order_id      = $order->get_id();
		$order_status  = $order->get_status();
		$is_failed     = $order->has_status( 'failed' );
		$payment_method = $order->get_payment_method();

		if ( $is_failed ) :
			/*
			 * ── FAILED ORDER BRANCH ───────────────────────────────────────
			 * Preserve native failed-order Pay / My Account behavior.
			 * Never show "Order Confirmed" here.
			 */
			?>
			<p class="woocommerce-notice woocommerce-notice--error woocommerce-thankyou-order-failed">
				<?php esc_html_e( 'Unfortunately your order cannot be processed as the originating bank/merchant has declined your transaction. Please attempt your purchase again.', 'woocommerce' ); ?>
			</p>

			<p class="woocommerce-thankyou-order-failed-actions">
				<a href="<?php echo esc_url( $order->get_checkout_payment_url() ); ?>" class="button pay">
					<?php esc_html_e( 'Pay', 'woocommerce' ); ?>
				</a>
				<?php if ( is_user_logged_in() ) : ?>
					<a href="<?php echo esc_url( wc_get_page_permalink( 'myaccount' ) ); ?>" class="button">
						<?php esc_html_e( 'My account', 'woocommerce' ); ?>
					</a>
				<?php endif; ?>
			</p>
			<?php

		else :
			/*
			 * ── SUCCESSFUL ORDER — DIGITAL RECEIPT ───────────────────────
			 */

			/*
			 * 1. Success notice (hero text filtered in functions.php by
			 *    empire_king_order_received_hero_text to include eyebrow /
			 *    title / subcopy spans).
			 */
			wc_get_template(
				'checkout/order-received.php',
				array(
					'order' => $order,
				)
			);

			/*
			 * 2. Suppress only the default woocommerce_order_details_table
			 *    callback for the duration of this template. All other
			 *    woocommerce_thankyou callbacks remain intact.
			 */
			remove_action( 'woocommerce_thankyou', 'woocommerce_order_details_table', 10 );

			/*
			 * 3. Fire gateway-specific and generic thankyou hooks.
			 *    Plugins (e.g. Stripe, PayPal) attach to these hooks.
			 */
			do_action( 'woocommerce_thankyou_' . $payment_method, $order_id );
			do_action( 'woocommerce_thankyou', $order_id );

			/*
			 * 4. Restore the default callback so it is not permanently
			 *    removed from other contexts (e.g. AJAX re-renders).
			 */
			add_action( 'woocommerce_thankyou', 'woocommerce_order_details_table', 10 );

			// ── RECEIPT SUMMARY ──────────────────────────────────────────
			$order_date  = $order->get_date_created();
			$order_email = $order->get_billing_email();
			$order_total = $order->get_formatted_order_total();
			$payment_title = $order->get_payment_method_title();
			?>

			<div class="ek-receipt">

				<div class="ek-receipt__summary">

					<div class="ek-receipt__summary-primary">
						<span class="ek-receipt__order-number">
							<?php
							/* translators: %s: order number */
							printf( esc_html__( 'ORDER #%s', 'empire-king' ), esc_html( $order->get_order_number() ) );
							?>
						</span>
						<span class="ek-receipt__order-total"><?php echo wp_kses_post( $order_total ); ?></span>
					</div>

					<div class="ek-receipt__summary-secondary">
						<?php if ( $order_date ) : ?>
							<span class="ek-receipt__date">
								<?php echo esc_html( wc_format_datetime( $order_date ) ); ?>
							</span>
						<?php endif; ?>
						<?php if ( $order_email ) : ?>
							<span class="ek-receipt__email"><?php echo esc_html( $order_email ); ?></span>
						<?php endif; ?>
						<?php if ( $payment_title ) : ?>
							<span class="ek-receipt__payment"><?php echo esc_html( $payment_title ); ?></span>
						<?php endif; ?>
					</div>

				</div><!-- .ek-receipt__summary -->

				<?php
				/*
				 * Payment method instructions (e.g. COD "Pay with cash upon
				 * delivery"). WC stores this in payment gateway description.
				 */
				$gateway_description = '';
				if ( $payment_method ) {
					$gateways = WC()->payment_gateways()->get_available_payment_gateways();
					if ( isset( $gateways[ $payment_method ] ) ) {
						$gw_obj = $gateways[ $payment_method ];
						/* Use thank_you_text when available (COD etc.), else description. */
						if ( ! empty( $gw_obj->instructions ) ) {
							$gateway_description = $gw_obj->instructions;
						} elseif ( ! empty( $gw_obj->description ) ) {
							$gateway_description = $gw_obj->description;
						}
					}
				}
				if ( $gateway_description ) :
					?>
					<div class="ek-receipt__payment-instructions">
						<?php echo wp_kses_post( wpautop( wptexturize( $gateway_description ) ) ); ?>
					</div>
				<?php endif; ?>

				<div class="ek-receipt__divider"></div>

				<?php
				// ── ORDER ITEMS ──────────────────────────────────────────
				$items = $order->get_items();
				if ( $items ) :
					?>
					<div class="ek-receipt__items">
						<h2 class="ek-receipt__section-title"><?php esc_html_e( 'YOUR ORDER', 'empire-king' ); ?></h2>

						<?php foreach ( $items as $item_id => $item ) : /** @var WC_Order_Item_Product $item */ ?>
							<div class="ek-receipt__item">
								<div class="ek-receipt__item-row">
									<span class="ek-receipt__item-name">
										<?php echo esc_html( $item->get_name() ); ?>
									</span>
									<span class="ek-receipt__item-total">
										<?php echo wp_kses_post( $order->get_formatted_line_subtotal( $item ) ); ?>
									</span>
								</div>
								<div class="ek-receipt__item-qty">
									<?php
									/* translators: %s: quantity */
									printf( esc_html__( '&times; %s', 'empire-king' ), esc_html( $item->get_quantity() ) );
									?>
								</div>
								<?php
								// APF / item meta (customizations: Remove Ingredients, Add Extras, etc.)
								$meta_html = wc_display_item_meta( $item, array( 'echo' => false ) );
								if ( $meta_html ) :
									?>
									<div class="ek-receipt__item-meta">
										<?php echo wp_kses_post( $meta_html ); ?>
									</div>
								<?php endif; ?>
							</div><!-- .ek-receipt__item -->
						<?php endforeach; ?>

					</div><!-- .ek-receipt__items -->

					<div class="ek-receipt__divider"></div>
				<?php endif; ?>

				<?php
				// ── TOTALS ───────────────────────────────────────────────
				$totals = $order->get_order_item_totals();
				if ( $totals ) :
					?>
					<div class="ek-receipt__totals">
						<?php foreach ( $totals as $key => $total ) : ?>
							<div class="ek-receipt__total-row ek-receipt__total-row--<?php echo esc_attr( $key ); ?>">
								<span class="ek-receipt__total-label"><?php echo wp_kses_post( $total['label'] ); ?></span>
								<span class="ek-receipt__total-value"><?php echo wp_kses_post( $total['value'] ); ?></span>
							</div>
						<?php endforeach; ?>
					</div><!-- .ek-receipt__totals -->

					<div class="ek-receipt__divider"></div>
				<?php endif; ?>

				<?php
				// ── BILLING DETAILS ──────────────────────────────────────
				$billing_first = $order->get_billing_first_name();
				$billing_last  = $order->get_billing_last_name();
				$billing_name  = trim( $billing_first . ' ' . $billing_last );
				$billing_addr1 = $order->get_billing_address_1();
				$billing_addr2 = $order->get_billing_address_2();
				$billing_city  = $order->get_billing_city();
				$billing_state = $order->get_billing_state();
				$billing_post  = $order->get_billing_postcode();
				$billing_phone = $order->get_billing_phone();
				$billing_email = $order->get_billing_email();

				$has_billing = $billing_name || $billing_addr1 || $billing_city || $billing_phone || $billing_email;
				if ( $has_billing ) :
					?>
					<div class="ek-receipt__billing">
						<h2 class="ek-receipt__section-title"><?php esc_html_e( 'BILLING DETAILS', 'empire-king' ); ?></h2>

						<?php if ( $billing_name ) : ?>
							<div class="ek-receipt__billing-row">
								<span class="ek-receipt__billing-label"><?php esc_html_e( 'Name', 'empire-king' ); ?></span>
								<span class="ek-receipt__billing-value"><?php echo esc_html( $billing_name ); ?></span>
							</div>
						<?php endif; ?>

						<?php if ( $billing_addr1 ) : ?>
							<div class="ek-receipt__billing-row">
								<span class="ek-receipt__billing-label"><?php esc_html_e( 'Address', 'empire-king' ); ?></span>
								<span class="ek-receipt__billing-value">
									<?php
									$address_parts = array_filter( array( $billing_addr1, $billing_addr2, $billing_city, $billing_state, $billing_post ) );
									echo esc_html( implode( ', ', $address_parts ) );
									?>
								</span>
							</div>
						<?php endif; ?>

						<?php if ( $billing_phone ) : ?>
							<div class="ek-receipt__billing-row">
								<span class="ek-receipt__billing-label"><?php esc_html_e( 'Phone', 'empire-king' ); ?></span>
								<span class="ek-receipt__billing-value"><?php echo esc_html( $billing_phone ); ?></span>
							</div>
						<?php endif; ?>

						<?php if ( $billing_email ) : ?>
							<div class="ek-receipt__billing-row">
								<span class="ek-receipt__billing-label"><?php esc_html_e( 'Email', 'empire-king' ); ?></span>
								<span class="ek-receipt__billing-value"><?php echo esc_html( $billing_email ); ?></span>
							</div>
						<?php endif; ?>

					</div><!-- .ek-receipt__billing -->
				<?php endif; ?>

			</div><!-- .ek-receipt -->

		<?php endif; // end if is_failed / else ?>

	<?php else : ?>
		<?php
		/*
		 * No order object — guest landing or invalid order key.
		 * Show the standard "Thank you" text only.
		 */
		wc_get_template( 'checkout/order-received.php', array( 'order' => false ) );
		?>
	<?php endif; // end if $order ?>

</div><!-- .woocommerce-order -->
