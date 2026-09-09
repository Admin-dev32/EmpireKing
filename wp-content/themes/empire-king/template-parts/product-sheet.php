<?php
/**
 * Shared product customization sheet dialog.
 * Reused between /order-now/ (menu item selection) and /cart/ (cart item editing).
 *
 * @package Empire_King
 */

defined( 'ABSPATH' ) || exit;
?>
<dialog class="ek-order-now__sheet" data-order-now-sheet aria-labelledby="order-now-product-title">
	<button class="ek-order-now__sheet-close" type="button" data-order-now-close aria-label="<?php esc_attr_e( 'Close product details', 'empire-king' ); ?>" autofocus>&times;</button>
	<div data-order-now-sheet-content></div>
	<p class="ek-order-now__sheet-status" data-order-now-sheet-status role="status" aria-live="polite" aria-atomic="true"></p>
</dialog>
