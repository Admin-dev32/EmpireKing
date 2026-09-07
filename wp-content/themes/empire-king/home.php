<?php
/** Native posts index using the main WordPress query. @package Empire_King */
get_header();
?>
<section class="ek-blog" aria-labelledby="blog-title">
	<header class="ek-blog__header"><h1 id="blog-title">Empire King Burger Blog</h1></header>
	<?php if ( have_posts() ) : ?>
		<div class="ek-blog__grid">
			<?php while ( have_posts() ) : the_post(); ?>
				<article id="post-<?php the_ID(); ?>" <?php post_class( 'ek-blog__card' ); ?>>
					<?php if ( has_post_thumbnail() ) : ?>
						<a class="ek-blog__image" href="<?php the_permalink(); ?>" aria-label="<?php the_title_attribute(); ?>"><?php the_post_thumbnail( 'large', array( 'loading' => 'lazy' ) ); ?></a>
					<?php endif; ?>
					<div class="ek-blog__card-body">
						<time datetime="<?php echo esc_attr( get_the_date( DATE_W3C ) ); ?>"><?php echo esc_html( get_the_date() ); ?></time>
						<h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
						<div class="ek-blog__excerpt"><?php the_excerpt(); ?></div>
						<a class="ek-blog__read" href="<?php the_permalink(); ?>" aria-label="<?php echo esc_attr( 'Read more: ' . get_the_title() ); ?>">Read More <span aria-hidden="true">&#8594;</span></a>
					</div>
				</article>
			<?php endwhile; ?>
		</div>
		<?php the_posts_pagination( array( 'mid_size' => 1, 'prev_text' => 'Previous', 'next_text' => 'Next' ) ); ?>
	<?php else : ?>
		<p>No stories have been published yet.</p>
	<?php endif; ?>
</section>
<?php get_footer(); ?>
