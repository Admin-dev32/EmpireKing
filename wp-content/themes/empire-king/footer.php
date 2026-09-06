<?php
/**
 * Footer template.
 *
 * @package Empire_King
 */
?>
</main>
<footer class="site-footer">
	<p class="footer-brand">Empire King Burger</p>
	<nav aria-label="<?php echo esc_attr__( 'Footer navigation', 'empire-king' ); ?>">
		<ul class="footer-menu">
			<li><a href="#locations"><?php esc_html_e( 'Locations', 'empire-king' ); ?></a></li>
			<li><a href="#about"><?php esc_html_e( 'About', 'empire-king' ); ?></a></li>
			<li><a href="#contact"><?php esc_html_e( 'Contact', 'empire-king' ); ?></a></li>
			<li><a href="#legal"><?php esc_html_e( 'Legal', 'empire-king' ); ?></a></li>
		</ul>
	</nav>
</footer>
<?php wp_footer(); ?>
</body>
</html>
