<?php
/**
 * Empire King — Order Received / Digital Receipt Template
 *
 * Overrides woocommerce/templates/checkout/thankyou.php (v8.1.0).
 *
 * Hook firing order (matches native WooCommerce exactly):
 *
 *   woocommerce_before_thankyou               always, inside if $order
 *   woocommerce_thankyou_{payment_method}      always (failed + successful)
 *   woocommerce_thankyou                       always (failed + successful)
 *
 * For successful orders:
 *   - woocommerce_thankyou_{payment_method} output is captured via ob_start()
 *     and placed inside .ek-receipt__payment-instructions when non-empty.
 *     It is NOT called a second time anywhere.
 *   - woocommerce_order_details_table (the default woocommerce_thankyou
 *     callback at priority 10) is detached before woocommerce_thankyou
 *     fires and restored immediately after. All other callbacks are intact.
 *
 * For failed orders:
 *   - Native failed-order UI (message + Pay + My Account) is rendered.
 *   - Both hooks fire normally after the UI (no capture, no suppression).
 *   - "Order Confirmed" is never shown (also guarded in functions.php).
 *
 * No manual gateway lookup. No reading $gw->instructions / $gw->description.
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

			<?php
			/*
			 * ── FAILED ORDER: fire both native hooks ─────────────────────
			 * Native WooCommerce fires these for ALL orders regardless of
			 * status. Preserve that compatibility. No capture, no suppression.
			 */
			do_action( 'woocommerce_thankyou_' . $order->get_payment_method(), $order->get_id() );
			do_action( 'woocommerce_thankyou', $order->get_id() );
			?>

		<?php else : ?>

			<?php
			/*
			 * ── SUCCESS NOTICE ────────────────────────────────────────────
			 * Renders the standard Woo success <p> notice. Text is filtered
			 * by empire_king_order_received_hero_text() in functions.php to
			 * inject the eyebrow / title / subcopy spans.
			 */
			wc_get_template( 'checkout/order-received.php', array( 'order' => $order ) );

			/*
			 * ── CAPTURE PAYMENT-SPECIFIC HOOK OUTPUT (once) ──────────────
			 * WC_Gateway_COD::thankyou_page() (and equivalents for other
			 * gateways) are hooked to woocommerce_thankyou_{method}.
			 * Capture its output here to place it inside the receipt's
			 * .ek-receipt__payment-instructions container.
			 * This is the ONE and ONLY invocation of this hook.
			 */
			ob_start();
			do_action( 'woocommerce_thankyou_' . $order->get_payment_method(), $order->get_id() );
			$ek_payment_hook_output = trim( ob_get_clean() );

			/*
			 * ── GENERIC THANKYOU HOOK (suppress only the default table) ──
			 * Remove the default woocommerce_order_details_table callback
			 * (priority 10) so the native Woo order-details table does not
			 * duplicate the custom receipt below. Every other callback on
			 * woocommerce_thankyou remains intact. Restore immediately after.
			 */
			remove_action( 'woocommerce_thankyou', 'woocommerce_order_details_table', 10 );
			do_action( 'woocommerce_thankyou', $order->get_id() );
			add_action( 'woocommerce_thankyou', 'woocommerce_order_details_table', 10 );

			// ── LOCAL VARIABLES FOR THE RECEIPT ──────────────────────────
			$order_date    = $order->get_date_created();
			$order_email   = $order->get_billing_email();
			$order_total   = $order->get_formatted_order_total();
			$payment_title = $order->get_payment_method_title();
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
				 * ── PAYMENT INSTRUCTIONS ─────────────────────────────────
				 * Emit the captured woocommerce_thankyou_{method} output
				 * inside the styled container when the hook produced content.
				 * This is the only place this output appears. No second call.
				 */
				if ( $ek_payment_hook_output ) :
					?>
					<div class="ek-receipt__payment-instructions">
						<?php
						// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						echo $ek_payment_hook_output;
						?>
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
				// Authoritative Woo data. No manual money calculation.
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
