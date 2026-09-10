<?php
/**
 * Corporate Home visual-direction prototype.
 *
 * Development-only content and media placeholders must be replaced with
 * approved copy and CMS-managed media before production use.
 *
 * @package Empire_King
 */

get_header();
$order_gateway_background = empire_king_get_order_gateway_background_url();
$order_gateway_combo      = empire_king_get_order_gateway_combo_url();
$home_location_marker_logo = empire_king_get_footer_logo_url();
$home_slides              = empire_king_get_home_slideshow_images();
$home_menu_glimpse        = empire_king_get_home_menu_glimpse();
?>
<section id="order" class="order-gateway<?php echo $order_gateway_background ? ' order-gateway--has-background' : ''; ?><?php echo $order_gateway_combo ? ' order-gateway--has-combo' : ''; ?>" aria-labelledby="home-title">
	<?php if ( $order_gateway_background ) : ?>
		<div class="order-gateway__background" aria-hidden="true" style="--ek-order-background: url('<?php echo esc_url( $order_gateway_background ); ?>');"></div>
	<?php endif; ?>
	<div class="order-gateway__inner">
		<?php if ( $order_gateway_combo ) : ?>
			<div class="order-gateway__combo" aria-hidden="true"><img src="<?php echo esc_url( $order_gateway_combo ); ?>" alt=""></div>
		<?php endif; ?>
		<div class="order-gateway__content">
			<div class="order-gateway__intro">
				<h1 id="home-title">Ready to Order?</h1>
				<p class="order-gateway__accent">Start here</p>
			</div>
			<div class="order-gateway__card">
				<p class="order-gateway__location">Avenue H &middot; Lancaster, California</p>
				<a class="order-gateway__button" href="<?php echo esc_url( home_url( '/order-now/' ) ); ?>">Order Now</a>
			</div>
		</div>
	</div>
</section>

<?php if ( $home_slides ) : ?>
	<section class="home-slideshow" aria-roledescription="carousel" aria-label="Empire King Burger slideshow">
		<div class="home-slideshow__frame">
			<?php foreach ( $home_slides as $index => $slide ) : ?>
				<figure class="home-slideshow__slide<?php echo 0 === $index ? ' is-active' : ''; ?>" aria-hidden="<?php echo 0 === $index ? 'false' : 'true'; ?>">
					<img src="<?php echo esc_url( $slide['url'] ); ?>" alt="<?php echo esc_attr( $slide['alt'] ); ?>">
				</figure>
			<?php endforeach; ?>
			<?php if ( count( $home_slides ) > 1 ) : ?>
				<button class="home-slideshow__arrow home-slideshow__arrow--previous" type="button" aria-label="Previous slide"><span aria-hidden="true">‹</span></button>
				<button class="home-slideshow__arrow home-slideshow__arrow--next" type="button" aria-label="Next slide"><span aria-hidden="true">›</span></button>
			<?php endif; ?>
		</div>
		<?php if ( count( $home_slides ) > 1 ) : ?>
			<div class="home-slideshow__controls">
				<div class="home-slideshow__pagination" aria-label="Select slideshow image">
					<?php foreach ( $home_slides as $index => $slide ) : ?>
						<button class="home-slideshow__dot" type="button" aria-label="Show slide <?php echo esc_attr( $index + 1 ); ?>" aria-current="<?php echo 0 === $index ? 'true' : 'false'; ?>"></button>
					<?php endforeach; ?>
				</div>
				<button class="home-slideshow__toggle" type="button" aria-label="Pause slideshow" aria-pressed="false"><span aria-hidden="true">Ⅱ</span></button>
			</div>
		<?php endif; ?>
	</section>
<?php endif; ?>

<?php $featured_categories = empire_king_get_featured_favorites(); ?>
<?php if ( $featured_categories ) : ?>
<section id="favorites" class="featured-favorites" aria-labelledby="favorites-title">
	<h2 id="favorites-title">Featured Picks</h2>
	<div class="featured-favorites__categories" role="group" aria-label="Featured categories">
		<?php foreach ( $featured_categories as $key => $category ) : ?>
			<button class="featured-favorites__category" type="button" data-featured-category="<?php echo esc_attr( $key ); ?>" aria-pressed="<?php echo array_key_first( $featured_categories ) === $key ? 'true' : 'false'; ?>" aria-controls="favorites-products">
				<span><?php echo esc_html( $category['label'] ); ?></span>
			</button>
		<?php endforeach; ?>
	</div>
	<div class="featured-favorites__showcase">
		<button class="featured-favorites__arrow featured-favorites__arrow--previous" type="button" data-featured-previous aria-label="Previous featured item" aria-controls="favorites-products" hidden><span aria-hidden="true">&#8592;</span></button>
		<div id="favorites-products" class="featured-favorites__products">
			<?php foreach ( $featured_categories as $key => $category ) : ?>
				<?php foreach ( $category['items'] as $index => $item ) : ?>
					<article class="featured-favorites__product" data-featured-item="<?php echo esc_attr( $key ); ?>" data-slot="<?php echo esc_attr( $index ); ?>" <?php echo array_key_first( $featured_categories ) !== $key || $index > 1 ? 'hidden' : ''; ?>>
						<div class="featured-favorites__image">
							<?php echo $item['image_id'] ? wp_get_attachment_image( $item['image_id'], 'woocommerce_thumbnail', false, array( 'alt' => $item['name'], 'loading' => 'lazy', 'decoding' => 'async' ) ) : wc_placeholder_img( 'woocommerce_thumbnail', array( 'alt' => $item['name'], 'loading' => 'lazy', 'decoding' => 'async' ) ); ?>
						</div>
						<span class="featured-favorites__tag">Featured</span>
						<h3><?php echo esc_html( $item['name'] ); ?></h3>
						<a class="featured-favorites__order" href="<?php echo esc_url( add_query_arg( 'product_id', $item['id'], home_url( '/order-now/' ) ) ); ?>">Order Now</a>
					</article>
				<?php endforeach; ?>
			<?php endforeach; ?>
		</div>
		<button class="featured-favorites__arrow featured-favorites__arrow--next" type="button" data-featured-next aria-label="Next featured item" aria-controls="favorites-products" hidden><span aria-hidden="true">&#8594;</span></button>
	</div>
	<p class="screen-reader-text" data-featured-status role="status" aria-atomic="true"></p>
</section>
<?php endif; ?>

<?php
$home_stories = empire_king_get_home_stories();
$stories_background = empire_king_get_stories_background_url();
$stories_blog_url = empire_king_get_blog_url();
?>
<?php if ( $home_stories ) : ?>
<section id="latest-stories" class="home-stories<?php echo $stories_background ? ' home-stories--custom-background' : ''; ?>" aria-labelledby="stories-title"<?php if ( $stories_background ) : ?> style="background-image: url('<?php echo esc_url( $stories_background ); ?>');"<?php endif; ?>>
	<h2 id="stories-title">Latest From Empire King</h2>
	<?php if ( $home_stories[0]['preview'] ) : ?>
		<p class="home-stories__preview-note">Local design preview &middot; Sample stories, not published news</p>
	<?php endif; ?>
	<div class="home-stories__showcase">
		<button class="home-stories__arrow home-stories__arrow--previous" type="button" data-stories-previous aria-label="Previous stories" aria-controls="stories-cards" hidden>&#8592;</button>
		<div id="stories-cards" class="home-stories__cards">
			<?php foreach ( $home_stories as $story ) : ?>
				<article class="home-stories__card">
					<div class="home-stories__image">
						<?php if ( $story['image_id'] ) : ?>
							<?php echo wp_get_attachment_image( $story['image_id'], 'large', false, array( 'loading' => 'lazy', 'decoding' => 'async' ) ); ?>
						<?php else : ?>
							<div class="home-stories__placeholder" aria-hidden="true"><span><?php echo $story['preview'] ? 'Story image placeholder' : ''; ?></span></div>
						<?php endif; ?>
					</div>
					<div class="home-stories__body">
						<h3><?php if ( $story['url'] ) : ?><a href="<?php echo esc_url( $story['url'] ); ?>"><?php echo esc_html( $story['title'] ); ?></a><?php else : ?><?php echo esc_html( $story['title'] ); ?><?php endif; ?></h3>
						<?php if ( $story['url'] ) : ?>
							<a class="home-stories__read" href="<?php echo esc_url( $story['url'] ); ?>" aria-label="<?php echo esc_attr( 'Read more: ' . $story['title'] ); ?>">Read More <span aria-hidden="true">&#8599;</span></a>
						<?php else : ?>
							<span class="home-stories__read" aria-disabled="true">Read More <span aria-hidden="true">&#8599;</span></span>
						<?php endif; ?>
					</div>
				</article>
			<?php endforeach; ?>
		</div>
		<button class="home-stories__arrow home-stories__arrow--next" type="button" data-stories-next aria-label="Next stories" aria-controls="stories-cards" hidden>&#8594;</button>
	</div>
	<div class="home-stories__pagination" role="group" aria-label="Choose first visible story" hidden>
		<?php foreach ( $home_stories as $index => $story ) : ?>
			<button class="home-stories__dot" type="button" aria-label="<?php echo esc_attr( 'Show story ' . ( $index + 1 ) . ': ' . $story['title'] ); ?>" aria-controls="stories-cards" aria-current="<?php echo 0 === $index ? 'true' : 'false'; ?>"></button>
		<?php endforeach; ?>
	</div>
	<?php if ( $stories_blog_url ) : ?>
		<p class="home-stories__all"><a href="<?php echo esc_url( $stories_blog_url ); ?>">View All Stories</a></p>
	<?php endif; ?>
	<p class="screen-reader-text" data-stories-status role="status" aria-atomic="true"></p>
</section>
<?php endif; ?>

<section id="locations" class="home-locations" aria-labelledby="locations-title">
	<div class="home-locations__intro">
		<p>Our Location</p>
		<h2 id="locations-title">Visit Avenue H</h2>
		<p>Find Empire King Burger on Avenue H in Lancaster.</p>
	</div>
	<div class="home-locations__stage">
		<div id="home-locations-map" class="home-locations__map" aria-label="Map showing the Empire King Avenue H location" role="region"<?php if ( $home_location_marker_logo ) : ?> data-marker-logo-url="<?php echo esc_url( $home_location_marker_logo ); ?>"<?php endif; ?>>
			<p class="home-locations__map-fallback">Interactive map unavailable. Avenue H location details are shown below.</p>
		</div>
		<article class="home-locations__detail">
				<div class="home-locations__detail-heading"><span class="home-locations__badge" aria-hidden="true">H</span><div><h3>Avenue H</h3><address>1036 W Avenue H<br>Lancaster, CA 93534</address></div></div>
				<div class="home-locations__actions">
					<a class="home-locations__order" href="<?php echo esc_url( home_url( '/order-now/' ) ); ?>">Order from Avenue H</a>
					<a class="home-locations__directions" href="https://www.google.com/maps/search/?api=1&amp;query=1036%20W%20Avenue%20H%2C%20Lancaster%2C%20CA%2093534" target="_blank" rel="noopener noreferrer">Get Directions <span aria-hidden="true">&#8599;</span></a>
				</div>
		</article>
	</div>
</section>

<section id="about" class="home-menu-glimpse" aria-labelledby="menu-glimpse-title">
	<div class="home-menu-glimpse__intro">
		<p>Pick Your Craving</p>
		<h2 id="menu-glimpse-title">One More Look Before You Order.</h2>
	</div>
	<?php if ( $home_menu_glimpse['categories'] ) : ?>
		<?php $default_menu_category = $home_menu_glimpse['categories'][0]; ?>
		<div class="home-menu-glimpse__tabs" role="tablist" aria-label="Menu categories">
			<?php foreach ( $home_menu_glimpse['categories'] as $category ) : ?>
				<button type="button" role="tab" class="home-menu-glimpse__tab" data-menu-glimpse-category="<?php echo esc_attr( $category['key'] ); ?>" aria-selected="<?php echo $category['key'] === $default_menu_category['key'] ? 'true' : 'false'; ?>" aria-controls="menu-glimpse-<?php echo esc_attr( $category['key'] ); ?>"><?php echo esc_html( $category['name'] ); ?></button>
			<?php endforeach; ?>
		</div>
		<div class="home-menu-glimpse__stage">
			<?php foreach ( $home_menu_glimpse['categories'] as $category_index => $category ) : ?>
				<section id="menu-glimpse-<?php echo esc_attr( $category['key'] ); ?>" class="home-menu-glimpse__panel" data-menu-glimpse-panel="<?php echo esc_attr( $category['key'] ); ?>" data-accent="<?php echo esc_attr( $category_index % 3 ); ?>" role="tabpanel" <?php echo $category['key'] !== $default_menu_category['key'] ? 'hidden' : ''; ?>>
					<img class="home-menu-glimpse__background" src="<?php echo esc_url( $category['background'] ); ?>" alt="" aria-hidden="true" loading="lazy" decoding="async">
					<?php foreach ( $category['foregrounds'] as $image_index => $image ) : ?>
						<figure class="home-menu-glimpse__frame home-menu-glimpse__frame--food<?php echo 0 === $image_index ? ' is-current' : ''; ?>" data-menu-glimpse-frame>
							<img src="<?php echo esc_url( $image['url'] ); ?>" alt="<?php echo esc_attr( $image['alt'] ); ?>" loading="lazy" decoding="async">
						</figure>
					<?php endforeach; ?>
					<div class="home-menu-glimpse__frame home-menu-glimpse__frame--cta" data-menu-glimpse-frame>
						<p>Ready to Order?</p><h3>Craving <?php echo esc_html( $category['name'] ); ?>?</h3><span>Start your Avenue H order.</span><a href="<?php echo esc_url( empire_king_get_local_order_url( $category['order_category'] ) ); ?>">Order Now</a>
					</div>
					<a class="home-menu-glimpse__fallback-order" href="<?php echo esc_url( empire_king_get_local_order_url( $category['order_category'] ) ); ?>">Order Now</a>
				</section>
			<?php endforeach; ?>
		</div>
		<p class="screen-reader-text" data-menu-glimpse-status role="status" aria-atomic="true"></p>
	<?php else : ?>
		<div class="home-menu-glimpse__empty"><p>Menu imagery is being prepared.</p><a href="<?php echo esc_url( home_url( '/order-now/' ) ); ?>">Order Now</a></div>
	<?php endif; ?>
</section>

<section id="contact" class="screen-reader-text" aria-label="Contact"></section>
<section id="legal" class="screen-reader-text" aria-label="Legal"></section>
<?php
get_footer();
