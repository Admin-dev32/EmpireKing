<?php
/** Theme presentation for one live promotion. */
$deal = $args['deal'];
$lead = ! empty( $args['lead'] );
$image_id = get_post_thumbnail_id( $deal['id'] );
$alt = $image_id ? get_post_meta( $image_id, '_wp_attachment_image_alt', true ) : '';
?>
<article class="ek-deals__card<?php echo $lead ? ' ek-deals__card--lead' : ''; ?><?php echo $image_id ? ' ek-deals__card--has-art' : ''; ?>" id="<?php echo esc_attr( $deal['anchor'] ); ?>">
	<?php if ( $image_id ) : ?><div class="ek-deals__art"><?php echo wp_get_attachment_image( $image_id, $lead ? 'large' : 'medium_large', false, array( 'alt' => $alt ?: $deal['title'], 'loading' => $lead ? 'eager' : 'lazy', 'fetchpriority' => $lead ? 'high' : 'auto' ) ); ?></div><?php endif; ?>
	<div class="ek-deals__copy">
		<?php if ( $lead && $deal['featured'] ) : ?><span class="ek-deals__badge">FEATURED</span><?php endif; ?>
		<?php if ( $lead ) : ?><h2><?php echo esc_html( $deal['title'] ); ?></h2><?php else : ?><h3><?php echo esc_html( $deal['title'] ); ?></h3><?php endif; ?>
		<?php if ( $deal['label'] ) : ?><p class="ek-deals__offer"><?php echo esc_html( $deal['label'] ); ?></p><?php endif; ?>
		<?php if ( $deal['description'] ) : ?><p><?php echo esc_html( $deal['description'] ); ?></p><?php endif; ?>
		<p class="ek-deals__availability"><?php echo esc_html( 'both' === $deal['location'] ? 'Available at both Lancaster locations.' : 'Available at ' . $deal['location'] . '.' ); ?></p>
		<?php if ( $deal['end'] ) : ?><p class="ek-deals__fine">Available through <time datetime="<?php echo esc_attr( $deal['end'] ); ?>"><?php echo esc_html( $deal['end'] ); ?></time>.</p><?php endif; ?>
		<?php if ( $deal['fine_print'] ) : ?><p class="ek-deals__fine"><?php echo esc_html( $deal['fine_print'] ); ?></p><?php endif; ?>
		<a class="ek-deals__order" href="<?php echo esc_url( home_url( '/order-now/' ) ); ?>" data-direct-order data-order-category="<?php echo esc_attr( $deal['destination'] ); ?>"<?php if ( 'both' !== $deal['location'] ) : ?> data-order-location="<?php echo esc_attr( $deal['location'] ); ?>"<?php endif; ?>>Order This Deal <span aria-hidden="true">→</span></a>
	</div>
</article>
