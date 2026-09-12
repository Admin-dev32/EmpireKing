<?php
/** Public Deals landing page; durable records are supplied by Empire King Deals. */
get_header();
$deals_landing_media = empire_king_get_deals_landing_media();
$welcome_logo_url    = empire_king_get_footer_logo_url();
$travel_background_url = empire_king_get_deals_travel_background_url();
$deals = function_exists( 'ek_deals_live' ) ? ek_deals_live() : array();
$location_name = empire_king_get_location_setting( 'display_name' );
$location_city = empire_king_get_location_setting( 'city' );
$location_locality = empire_king_get_location_locality_label( true );
$lead = null;
$groups = array();
$standalone = array();
if ( $deals ) {
	$lead = $deals[0];
	foreach ( $deals as $deal ) if ( $deal['featured'] ) { $lead = $deal; break; }
	foreach ( $deals as $deal ) {
		if ( $deal['category'] ) {
			$term = $deal['category'];
			if ( ! isset( $groups[ $term->term_id ] ) ) $groups[ $term->term_id ] = array( 'term' => $term, 'deals' => array(), 'order' => (int) get_term_meta( $term->term_id, '_ek_order', true ) );
			if ( $deal['id'] !== $lead['id'] ) $groups[ $term->term_id ]['deals'][] = $deal;
		} elseif ( $deal['id'] !== $lead['id'] ) $standalone[] = $deal;
	}
	uasort( $groups, function ( $a, $b ) { return $a['order'] <=> $b['order'] ?: $a['term']->term_id <=> $b['term']->term_id; } );
}
?>
<section class="ek-deals-welcome" aria-labelledby="deals-welcome" hidden>
	<?php if ( $deals_landing_media['background'] ) : ?>
		<img class="ek-deals-welcome__background" src="<?php echo esc_url( $deals_landing_media['background'] ); ?>" alt="" fetchpriority="high" decoding="async">
	<?php endif; ?>
	<?php if ( $deals_landing_media['hero_product'] ) : ?>
		<img class="ek-deals-welcome__hero-product" src="<?php echo esc_url( $deals_landing_media['hero_product'] ); ?>" alt="" aria-hidden="true" fetchpriority="high" decoding="async">
	<?php endif; ?>
	<div class="ek-deals-welcome__inner">
		<div class="ek-deals-welcome__copy">
			<?php if ( $welcome_logo_url ) : ?>
				<img class="ek-deals-welcome__logo" src="<?php echo esc_url( $welcome_logo_url ); ?>" alt="Empire King Burger">
			<?php else : ?>
				<p class="ek-deals-welcome__wordmark">Empire King Burger</p>
			<?php endif; ?>
			<p class="ek-deals-welcome__location"><?php echo esc_html( $location_locality ); ?></p>
			<p id="deals-welcome" class="ek-deals-welcome__headline">Welcome to Empire King Burger</p>
			<p class="ek-deals-welcome__description"><?php echo esc_html( sprintf( 'Start with our current deals or browse the %s menu.', $location_name ) ); ?></p>
		</div>
		<?php if ( $deals_landing_media['foregrounds'] ) : ?>
			<div class="ek-deals-welcome__showcase" aria-hidden="true">
				<?php foreach ( $deals_landing_media['foregrounds'] as $index => $foreground ) : ?>
					<img src="<?php echo esc_url( $foreground ); ?>" alt="" loading="<?php echo 0 === $index ? 'eager' : 'lazy'; ?>"<?php echo 0 === $index ? ' fetchpriority="high"' : ''; ?> decoding="async">
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
		<a class="ek-deals-welcome__deals-link" href="#deals-start">See Current Deals <span aria-hidden="true">↓</span></a>
	</div>
</section>
<a class="ek-deals__full-menu" href="<?php echo esc_url( home_url( '/order-now/' ) ); ?>">Check Out Our Full Menu <span aria-hidden="true">→</span></a>
<div class="ek-deals" data-deals-page>
	<div id="deals-start" class="ek-deals__sticky">
		<header class="ek-deals__intro"><p><?php echo esc_html( $location_locality ); ?></p><h1 tabindex="-1"><?php echo esc_html( sprintf( 'Deals & Specials in %s', $location_city ) ); ?></h1><p><?php echo esc_html( sprintf( 'Browse current Empire King Burger offers and order online from %s.', $location_name ) ); ?></p></header>
		<?php if ( $deals ) : ?>
		<nav class="ek-deals__pills" aria-label="Deal categories"><a href="#all-deals">All Deals</a>
		<?php foreach ( $groups as $group ) : $anchor = $group['deals'] ? 'deal-category-' . $group['term']->term_id : $lead['anchor']; ?>
			<a href="#<?php echo esc_attr( $anchor ); ?>"><?php echo esc_html( $group['term']->name ); ?></a>
		<?php endforeach; ?></nav>
		<?php endif; ?>
	</div>
	<?php if ( $deals ) : ?>
		<div id="all-deals"><?php get_template_part( 'template-parts/deal-card', null, array( 'deal' => $lead, 'lead' => true ) ); ?></div>
		<?php foreach ( $groups as $group ) : if ( ! $group['deals'] ) continue; ?>
			<section class="ek-deals__group" id="deal-category-<?php echo esc_attr( $group['term']->term_id ); ?>"><h2><?php echo esc_html( $group['term']->name ); ?></h2><div class="ek-deals__grid">
			<?php foreach ( $group['deals'] as $deal ) get_template_part( 'template-parts/deal-card', null, array( 'deal' => $deal, 'lead' => false ) ); ?>
			</div></section>
		<?php endforeach; ?>
		<?php if ( $standalone ) : ?><section class="ek-deals__group"><h2>More Deals</h2><div class="ek-deals__grid">
			<?php foreach ( $standalone as $deal ) get_template_part( 'template-parts/deal-card', null, array( 'deal' => $deal, 'lead' => false ) ); ?>
		</div></section><?php endif; ?>
	<?php else : ?>
		<section class="ek-deals__empty"><h2>Current deals are being updated.</h2><p><?php echo esc_html( sprintf( 'You can still browse the %s menu and order online.', $location_name ) ); ?></p><a class="ek-deals__order" href="<?php echo esc_url( home_url( '/order-now/' ) ); ?>">Order Now <span aria-hidden="true">→</span></a></section>
	<?php endif; ?>
	<section class="ek-deals__travel"><svg viewBox="0 0 48 48" aria-hidden="true"><path d="M24 44S7 29 7 18a17 17 0 0 1 34 0c0 11-17 26-17 26Z"/><circle cx="24" cy="18" r="6"/></svg><div><h2><?php echo esc_html( sprintf( 'Passing Through %s?', $location_city ) ); ?></h2><p><?php echo esc_html( sprintf( 'Visit Empire King Burger on %s.', $location_name ) ); ?></p><a class="ek-deals__order" href="<?php echo esc_url( home_url( '/#locations' ) ); ?>"><?php echo esc_html( sprintf( 'View %s', $location_name ) ); ?> <span aria-hidden="true">→</span></a></div></section>
</div>
<?php get_footer(); ?>
