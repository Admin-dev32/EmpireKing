<?php
/**
 * Footer template.
 *
 * @package Empire_King
 */

$footer_logo_url = empire_king_get_footer_logo_url();
$privacy_url     = get_privacy_policy_url();
$terms_page      = get_page_by_path( 'terms', OBJECT, 'page' );
$terms_url       = $terms_page && 'publish' === get_post_status( $terms_page ) ? get_permalink( $terms_page ) : '';
?>
</main>
<footer class="site-footer">
	<div class="site-footer__inner">
		<div class="site-footer__brand">
			<a class="site-footer__logo" href="<?php echo esc_url( home_url( '/' ) ); ?>" aria-label="<?php esc_attr_e( 'Empire King Burger home', 'empire-king' ); ?>">
				<?php if ( $footer_logo_url ) : ?>
					<img src="<?php echo esc_url( $footer_logo_url ); ?>" alt="<?php esc_attr_e( 'Empire King Burger', 'empire-king' ); ?>">
				<?php else : ?>
					<span class="site-footer__wordmark">Empire King <strong>Burger</strong></span>
				<?php endif; ?>
			</a>
			<p class="site-footer__tagline"><?php esc_html_e( 'Burgers, sandwiches, fries & more.', 'empire-king' ); ?></p>
			<div class="site-footer__actions">
				<a class="site-footer__order" href="<?php echo esc_url( home_url( '/#order' ) ); ?>" data-direct-order>
					<span><?php esc_html_e( 'Order Now', 'empire-king' ); ?></span>
					<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h13m-5-5 5 5-5 5" /></svg>
				</a>
				<a class="site-footer__locations" href="<?php echo esc_url( home_url( '/#locations' ) ); ?>">
					<span><?php esc_html_e( 'View Locations', 'empire-king' ); ?></span>
					<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h13m-5-5 5 5-5 5" /></svg>
				</a>
			</div>
		</div>
		<nav class="site-footer__navigation" aria-label="<?php echo esc_attr__( 'Footer navigation', 'empire-king' ); ?>">
			<ul class="site-footer__links">
				<li><a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Home', 'empire-king' ); ?></a></li>
				<li><a href="<?php echo esc_url( home_url( '/#order' ) ); ?>"><?php esc_html_e( 'Order', 'empire-king' ); ?></a></li>
				<li><a href="<?php echo esc_url( home_url( '/#favorites' ) ); ?>"><?php esc_html_e( 'Featured Favorites', 'empire-king' ); ?></a></li>
				<li><a href="<?php echo esc_url( home_url( '/#latest-stories' ) ); ?>"><?php esc_html_e( 'Latest Stories', 'empire-king' ); ?></a></li>
				<li><a href="<?php echo esc_url( home_url( '/#locations' ) ); ?>"><?php esc_html_e( 'Locations', 'empire-king' ); ?></a></li>
			</ul>
		</nav>
	</div>
	<div class="site-footer__bottom">
		<p>&copy; <?php echo esc_html( wp_date( 'Y' ) ); ?> <?php esc_html_e( 'Empire King Burger. All rights reserved.', 'empire-king' ); ?></p>
		<?php if ( $privacy_url || $terms_url ) : ?>
			<ul class="site-footer__legal">
				<?php if ( $privacy_url ) : ?><li><a href="<?php echo esc_url( $privacy_url ); ?>"><?php esc_html_e( 'Privacy Policy', 'empire-king' ); ?></a></li><?php endif; ?>
				<?php if ( $terms_url ) : ?><li><a href="<?php echo esc_url( $terms_url ); ?>"><?php esc_html_e( 'Terms', 'empire-king' ); ?></a></li><?php endif; ?>
			</ul>
		<?php endif; ?>
	</div>
</footer>
<?php wp_footer(); ?>
</body>
</html>
