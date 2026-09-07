<?php
/** Shared location selector for the Home gateway and Blog pickup CTA. @package Empire_King */
$maps_key = isset( $args['maps_key'] ) ? $args['maps_key'] : false;
?>
<dialog id="order-location-selector" class="order-location-dialog" aria-labelledby="order-location-dialog-title" aria-describedby="order-location-dialog-description">
	<div class="order-location-dialog__topbar">
		<p class="order-location-dialog__label">Empire King Burger</p>
		<button class="order-location-dialog__close" type="button" aria-label="Close location selector"><svg aria-hidden="true" viewBox="0 0 24 24"><path d="m6 6 12 12M18 6 6 18" /></svg></button>
	</div>
	<div class="order-location-dialog__content">
		<h2 id="order-location-dialog-title">Choose Your Pickup Location</h2>
		<p id="order-location-dialog-description">Choose the location you'd like to order from.</p>
		<?php if ( $maps_key ) : ?><div id="order-location-map" class="order-location-map" role="region" aria-label="Map showing Empire King locations in Lancaster"></div><?php endif; ?>
		<div class="order-location-options" aria-label="Choose a location">
			<button class="order-location-option" type="button" data-location="Avenue H" aria-pressed="false"><svg class="order-location-option__icon" aria-hidden="true" viewBox="0 0 24 24"><path d="M12 21s7-6.1 7-12a7 7 0 1 0-14 0c0 5.9 7 12 7 12Z" /><circle cx="12" cy="9" r="2.25" /></svg><span class="order-location-option__details"><strong>Avenue H</strong><span>1036 W Avenue H<br>Lancaster, CA 93534</span></span><span class="order-location-option__arrow" aria-hidden="true">›</span><span class="order-location-option__selected">Selected</span></button>
			<button class="order-location-option" type="button" data-location="Avenue I" aria-pressed="false"><svg class="order-location-option__icon" aria-hidden="true" viewBox="0 0 24 24"><path d="M12 21s7-6.1 7-12a7 7 0 1 0-14 0c0 5.9 7 12 7 12Z" /><circle cx="12" cy="9" r="2.25" /></svg><span class="order-location-option__details"><strong>Avenue I</strong><span>810 W Ave I<br>Lancaster, CA</span></span><span class="order-location-option__arrow" aria-hidden="true">›</span><span class="order-location-option__selected">Selected</span></button>
		</div>
	</div>
</dialog>
