<?php
/**
 * Empire King — Order Received / Digital Receipt Template
 *
 * Overrides woocommerce/templates/checkout/thankyou.php (v8.1.0).
 *
 * Hooks preserved in the order the native template fires them:
 *   woocommerce_before_thankyou               (inside if $order)
 *   woocommerce_thankyou_{payment_method}      (successful orders only)
 *   woocommerce_thankyou                       (successful orders only)
 *
 * The default woocommerce_order_details_table callback is detached only
 * for the duration of this template to prevent it duplicating the custom
 * receipt below. Every other woocommerce_thankyou callback is untouched.
 * The callback is restored immediately after do_action() returns.
 *
 * Failed orders render the native Pay / My Account actions. "Order
 * Confirmed" is never shown for failed orders (also guarded in the
 * functions.php filter empire_king_order_received_hero_text).
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

	<?php if ( $order ) : ?>

		<?php do_action( 'woocommerce_before_thankyou', $order->get_id() ); ?>

		<?php if ( $order->has_status( 'failed' ) ) : ?>

			<p class="woocommerce-notice woocommerce-notice--error woocommerce-thankyou-order-failed">
				<?php esc_html_e( 'Unfortunately your order cannot be processed as the originating bank/merchant has declined your transaction. Please attempt your purchase again.', 'woocommerce' ); ?>
			</p>

			<p class="woocommerce-notice woocommerce-notice--error woocommerce-thankyou-order-failed-actions">
				<a href="<?php echo esc_url( $order->get_checkout_payment_url() ); ?>" class="button pay">
					<?php esc_html_e( 'Pay', 'woocommerce' ); ?>
				</a>
				<?php if ( is_user_logged_in() ) : ?>
					<a href="<?php echo esc_url( wc_get_page_permalink( 'myaccount' ) ); ?>" class="button">
						<?php esc_html_e( 'My account', 'woocommerce' ); ?>
					</a>
				<?php endif; ?>
			</p>

		<?php else : ?>

			<?php
			/*
			 * ── SUCCESS NOTICE ───────────────────────────────────────────
			 * Renders: <p class="woocommerce-notice woocommerce-notice--success
			 *              woocommerce-thankyou-order-received">…</p>
			 * The text is filtered by empire_king_order_received_hero_text()
			 * to inject eyebrow / title / subcopy spans.
			 */
			wc_get_template( 'checkout/order-received.php', array( 'order' => $order ) );

			/*
			 * ── SUPPRESS DEFAULT ORDER-DETAILS TABLE CALLBACK ────────────
			 * woocommerce_order_details_table is the default callback on
			 * woocommerce_thankyou (priority 10). Detach it for this render
			 * so it does not duplicate the custom receipt below. Restore it
			 * immediately after do_action() so no other context is affected.
			 */
			remove_action( 'woocommerce_thankyou', 'woocommerce_order_details_table', 10 );

			/*
			 * ── PAYMENT-GATEWAY AND THANKYOU HOOKS ───────────────────────
			 * All other callbacks on woocommerce_thankyou (e.g. from Stripe,
			 * PayPal, other plugins) fire as usual.
			 */
			do_action( 'woocommerce_thankyou_' . $order->get_payment_method(), $order->get_id() );
			do_action( 'woocommerce_thankyou', $order->get_id() );

			add_action( 'woocommerce_thankyou', 'woocommerce_order_details_table', 10 );

			// ── LOCAL VARIABLES ──────────────────────────────────────────
			$order_date     = $order->get_date_created();
			$order_email    = $order->get_billing_email();
			$order_total    = $order->get_formatted_order_total();
			$payment_method = $order->get_payment_method();
			$payment_title  = $order->get_payment_method_title();
			?>

			<div class="ek-receipt">

				<?php /* ── SUMMARY ─────────────────────────────────────── */ ?>
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
				 * ── PAYMENT GATEWAY INSTRUCTIONS ────────────────────────
				 * COD: "Pay with cash upon delivery."
				 * WC stores this in gateway->instructions or ->description.
				 */
				$gateway_description = '';
				if ( $payment_method ) {
					$gateways = WC()->payment_gateways()->get_available_payment_gateways();
					if ( isset( $gateways[ $payment_method ] ) ) {
						$gw = $gateways[ $payment_method ];
						if ( ! empty( $gw->instructions ) ) {
							$gateway_description = $gw->instructions;
						} elseif ( ! empty( $gw->description ) ) {
							$gateway_description = $gw->description;
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
									<span class="ek-receipt__item-name"><?php echo esc_html( $item->get_name() ); ?></span>
									<span class="ek-receipt__item-total"><?php echo wp_kses_post( $order->get_formatted_line_subtotal( $item ) ); ?></span>
								</div>
								<div class="ek-receipt__item-qty">
									<?php
									/* translators: %s: item quantity */
									printf( esc_html__( '× %s', 'empire-king' ), esc_html( $item->get_quantity() ) );
									?>
								</div>
								<?php
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
				// $order->get_order_item_totals() returns authoritative Woo data.
				// No manual money calculation.
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
									$addr_parts = array_filter( array( $billing_addr1, $billing_addr2, $billing_city, $billing_state, $billing_post ) );
									echo esc_html( implode( ', ', $addr_parts ) );
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

		<?php endif; // end failed / else ?>

	<?php else : ?>

		<?php wc_get_template( 'checkout/order-received.php', array( 'order' => false ) ); ?>

	<?php endif; // end if $order ?>

</div><!-- .woocommerce-order -->
