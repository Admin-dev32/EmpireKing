<?php
/**
 * Fallback template.
 *
 * @package Empire_King
 */

get_header();
?>
<?php if ( have_posts() ) : ?>
	<?php while ( have_posts() ) : ?>
		<?php the_post(); ?>
		<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
			<header class="entry-header">
				<?php the_title( '<h1 class="entry-title">', '</h1>' ); ?>
			</header>
			<div class="entry-content">
				<?php the_content(); ?>
			</div>
		</article>
	<?php endwhile; ?>
<?php else : ?>
	<p><?php echo esc_html__( 'No content found.', 'empire-king' ); ?></p>
<?php endif; ?>
<?php
get_footer();
