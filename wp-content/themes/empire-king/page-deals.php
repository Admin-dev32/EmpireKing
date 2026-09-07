<?php
/** Public Deals landing page; durable records are supplied by Empire King Deals. */
get_header();
$deals = function_exists( 'ek_deals_live' ) ? ek_deals_live() : array();
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
<div class="ek-deals" data-deals-page>
	<header class="ek-deals__intro"><p>Lancaster, California</p><h1>Deals &amp; Specials in Lancaster</h1><p>Browse current Empire King Burger offers and choose your restaurant to order online.</p></header>
	<?php if ( $deals ) : ?>
		<nav class="ek-deals__pills" aria-label="Deal categories"><a href="#all-deals">All Deals</a>
		<?php foreach ( $groups as $group ) : $anchor = $group['deals'] ? 'deal-category-' . $group['term']->term_id : $lead['anchor']; ?>
			<a href="#<?php echo esc_attr( $anchor ); ?>"><?php echo esc_html( $group['term']->name ); ?></a>
		<?php endforeach; ?></nav>
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
		<section class="ek-deals__empty"><h2>Current deals are being updated.</h2><p>You can still browse the menu and order from your preferred Empire King Burger location.</p><a class="ek-deals__order" href="<?php echo esc_url( home_url( '/#order' ) ); ?>" data-direct-order>Order Now <span aria-hidden="true">→</span></a></section>
	<?php endif; ?>
	<section class="ek-deals__travel"><svg viewBox="0 0 48 48" aria-hidden="true"><path d="M24 44S7 29 7 18a17 17 0 0 1 34 0c0 11-17 26-17 26Z"/><circle cx="24" cy="18" r="6"/></svg><div><h2>Passing Through Lancaster?</h2><p>Choose the Empire King Burger location that works best for your stop.</p><a class="ek-deals__order" href="<?php echo esc_url( home_url( '/#locations' ) ); ?>">View Locations <span aria-hidden="true">→</span></a></div></section>
</div>
<?php empire_king_render_order_location_selector(); get_footer(); ?>
