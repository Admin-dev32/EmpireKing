<?php
/** Native single Post view. @package Empire_King */
if ( ! is_singular( 'post' ) ) {
	require get_theme_file_path( 'index.php' );
	return;
}
get_header();
$blog_url = empire_king_get_blog_url();
?>
<?php while ( have_posts() ) : the_post(); ?>
	<div class="ek-blog ek-blog--single">
		<nav class="ek-blog__breadcrumbs" aria-label="Breadcrumb">
			<ol>
				<li><a href="<?php echo esc_url( home_url( '/' ) ); ?>">Home</a></li>
				<li><?php if ( $blog_url ) : ?><a href="<?php echo esc_url( $blog_url ); ?>">Blog</a><?php else : ?><span>Blog</span><?php endif; ?></li>
				<li aria-current="page"><?php the_title(); ?></li>
			</ol>
		</nav>
		<article id="post-<?php the_ID(); ?>" <?php post_class( 'ek-blog__article' ); ?>>
			<header class="ek-blog__article-header">
				<h1><?php the_title(); ?></h1>
				<p class="ek-blog__meta"><time datetime="<?php echo esc_attr( get_the_date( DATE_W3C ) ); ?>"><?php echo esc_html( get_the_date() ); ?></time> <span aria-hidden="true">&middot;</span> By <?php echo esc_html( get_the_author() ); ?></p>
			</header>
			<?php if ( has_post_thumbnail() ) : ?><div class="ek-blog__hero"><?php the_post_thumbnail( 'large' ); ?></div><?php endif; ?>
			<div class="ek-blog__content">
				<?php the_content(); ?>
				<?php wp_link_pages( array( 'before' => '<nav class="ek-blog__pages" aria-label="Article pages">', 'after' => '</nav>' ) ); ?>
			</div>
		</article>
		<nav class="ek-blog__next-step" aria-label="Explore Empire King">
			<?php if ( $blog_url ) : ?><a href="<?php echo esc_url( $blog_url ); ?>">Back to Blog</a><?php endif; ?>
			<a href="<?php echo esc_url( home_url( '/#order' ) ); ?>">Order Online</a>
			<a href="<?php echo esc_url( home_url( '/#locations' ) ); ?>">Find a Location</a>
		</nav>
	</div>
<?php endwhile; ?>
<?php get_footer(); ?>
