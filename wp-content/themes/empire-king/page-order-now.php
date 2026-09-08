<?php
/** Avenue H transactional menu landing shell. Product presentation follows in a later phase. */
$default_category_id = (int) get_option( 'default_product_cat' );
$categories = get_terms(
	array(
		'taxonomy'   => 'product_cat',
		'hide_empty' => true,
		'exclude'    => $default_category_id ? array( $default_category_id ) : array(),
	)
);
$categories = is_wp_error( $categories ) ? array() : $categories;
usort(
	$categories,
	static function ( $a, $b ) {
		if ( 'burger' === $a->slug ) {
			return -1;
		}
		if ( 'burger' === $b->slug ) {
			return 1;
		}
		return strnatcasecmp( $a->name, $b->name );
	}
);
get_header();
?>
<div class="ek-order-now">
	<section class="ek-order-now__hero" aria-labelledby="order-now-title">
		<a class="ek-order-now__location" href="<?php echo esc_url( home_url( '/#locations' ) ); ?>">
			<svg aria-hidden="true" viewBox="0 0 24 24"><path d="M12 21s7-6.1 7-12a7 7 0 1 0-14 0c0 5.9 7 12 7 12Z" /><circle cx="12" cy="9" r="2.25" /></svg>
			<span>Avenue H</span>
			<svg class="ek-order-now__location-chevron" aria-hidden="true" viewBox="0 0 24 24"><path d="m7 10 5 5 5-5" /></svg>
		</a>
		<div class="ek-order-now__hero-copy">
			<h1 id="order-now-title"><span>Order</span> <strong>Online</strong></h1>
			<p>Fresh made to order.</p>
		</div>
		<div class="ek-order-now__hero-media" aria-hidden="true"></div>
	</section>
	<section class="ek-order-now__menu-surface" aria-label="Order menu categories">
		<?php if ( $categories ) : ?>
			<nav class="ek-order-now__categories" aria-label="Product categories">
				<?php foreach ( $categories as $index => $category ) : $category_url = get_term_link( $category ); ?>
					<?php if ( ! is_wp_error( $category_url ) ) : ?>
						<a class="ek-order-now__category<?php echo 0 === $index ? ' is-active' : ''; ?>" href="<?php echo esc_url( $category_url ); ?>"><?php echo esc_html( $category->name ); ?></a>
					<?php endif; ?>
				<?php endforeach; ?>
			</nav>
		<?php endif; ?>
	</section>
</div>
<?php get_footer(); ?>
