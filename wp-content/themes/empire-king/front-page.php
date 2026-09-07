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
$order_gateway_maps_key   = empire_king_get_google_maps_api_key();
$home_slides              = empire_king_get_home_slideshow_images();
$home_quality_image       = empire_king_get_home_quality_image_url();
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
				<div class="order-tabs" role="tablist" aria-label="Order method">
					<button id="pickup-tab" class="order-tab" type="button" role="tab" aria-selected="true" aria-controls="pickup-panel" data-order-mode="pickup">Pickup</button>
					<button id="delivery-tab" class="order-tab" type="button" role="tab" aria-selected="false" aria-controls="delivery-panel" data-order-mode="delivery" tabindex="-1">Delivery</button>
				</div>
				<div id="pickup-panel" class="order-tab-panel" role="tabpanel" aria-labelledby="pickup-tab">
					<button class="order-location-control" type="button" data-open-location-selector="pickup"><svg aria-hidden="true" viewBox="0 0 24 24"><path d="M12 21s7-6.1 7-12a7 7 0 1 0-14 0c0 5.9 7 12 7 12Z" /><circle cx="12" cy="9" r="2.25" /></svg><span class="order-location-control__unselected">Select Your Restaurant</span><span class="order-location-control__selected" hidden><span class="order-location-control__mode">Pickup From</span><strong></strong><span class="order-location-control__address"></span></span><span class="order-location-control__change" hidden>Change</span><span class="order-location-control__arrow" aria-hidden="true">›</span></button>
					<button class="order-gateway__button" type="button" data-order-submit="pickup">Order Now</button>
				</div>
				<div id="delivery-panel" class="order-tab-panel" role="tabpanel" aria-labelledby="delivery-tab" hidden>
					<button class="order-location-control" type="button" data-open-location-selector="delivery"><svg aria-hidden="true" viewBox="0 0 24 24"><path d="M12 21s7-6.1 7-12a7 7 0 1 0-14 0c0 5.9 7 12 7 12Z" /><circle cx="12" cy="9" r="2.25" /></svg><span class="order-location-control__unselected">Select Your Restaurant</span><span class="order-location-control__selected" hidden><span class="order-location-control__mode">Delivery From</span><strong></strong><span class="order-location-control__address"></span></span><span class="order-location-control__change" hidden>Change</span><span class="order-location-control__arrow" aria-hidden="true">›</span></button>
					<button class="order-gateway__button" type="button" data-order-submit="delivery">Order Now</button>
				</div>
				<p class="order-gateway__status" role="status" aria-live="polite"></p>
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

<?php empire_king_render_order_location_selector(); ?>

<?php $featured_categories = empire_king_get_featured_favorites(); ?>
<section id="favorites" class="featured-favorites" aria-labelledby="favorites-title" aria-describedby="favorites-note">
	<h2 id="favorites-title">Featured Favorites</h2>
	<p id="favorites-note" class="featured-favorites__note">Development preview &middot; Sample items and imagery placeholders</p>
	<div class="featured-favorites__categories" role="group" aria-label="Featured categories">
		<?php foreach ( $featured_categories as $key => $category ) : ?>
			<button class="featured-favorites__category" type="button" data-featured-category="<?php echo esc_attr( $key ); ?>" aria-pressed="<?php echo array_key_first( $featured_categories ) === $key ? 'true' : 'false'; ?>" aria-controls="favorites-products">
				<span class="featured-favorites__thumbnail" aria-hidden="true">
					<?php if ( $category['image'] ) : ?><img src="<?php echo esc_url( $category['image'] ); ?>" alt=""><?php else : ?><span class="featured-favorites__thumbnail-placeholder"></span><?php endif; ?>
				</span>
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
							<?php if ( $item['image'] ) : ?>
								<img src="<?php echo esc_url( $item['image'] ); ?>" alt="<?php echo esc_attr( $item['alt'] ); ?>" loading="lazy" decoding="async">
							<?php else : ?>
								<div class="featured-favorites__placeholder" aria-hidden="true"><span>Product image pending</span></div>
							<?php endif; ?>
						</div>
						<?php if ( ! empty( $item['tag'] ) ) : ?>
							<span class="featured-favorites__tag"><?php echo esc_html( $item['tag'] ); ?></span>
						<?php endif; ?>
						<h3><?php echo esc_html( $item['name'] ); ?></h3>
						<a class="featured-favorites__order" href="#order">Order Now</a>
					</article>
				<?php endforeach; ?>
			<?php endforeach; ?>
		</div>
		<button class="featured-favorites__arrow featured-favorites__arrow--next" type="button" data-featured-next aria-label="Next featured item" aria-controls="favorites-products" hidden><span aria-hidden="true">&#8594;</span></button>
	</div>
	<p class="screen-reader-text" data-featured-status role="status" aria-atomic="true"></p>
</section>

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

<?php $location_routes = empire_king_get_order_gateway_routes(); ?>
<section id="locations" class="home-locations" aria-labelledby="locations-title">
	<div class="home-locations__intro">
		<p>Our Locations</p>
		<h2 id="locations-title">Find Your Empire King</h2>
		<p>Two Lancaster locations. Pick the one that works for you.</p>
	</div>
	<div class="home-locations__track" data-locations-track>
	<div class="home-locations__stage" data-locations-stage>
		<div id="home-locations-map" class="home-locations__map" aria-label="Map showing Empire King Avenue H and Avenue I locations" role="region">
			<p class="home-locations__map-fallback">Interactive map unavailable. Choose a location below.</p>
		</div>
		<div class="home-locations__dock" data-locations-dock aria-label="Choose an Empire King location">
			<div class="home-locations__choices" data-locations-choices>
				<button class="home-locations__choice" type="button" data-location-key="avenue-h" aria-controls="home-location-avenue-h" aria-expanded="false" aria-pressed="false">
					<span class="home-locations__choice-inner">
					<span class="home-locations__badge" aria-hidden="true">H</span>
					<span class="home-locations__choice-copy"><span class="home-locations__choice-name">Avenue H</span><span class="home-locations__choice-address">1036 W Avenue H<br>Lancaster, CA 93534</span></span>
				</span>
				</button>
				<button class="home-locations__choice" type="button" data-location-key="avenue-i" aria-controls="home-location-avenue-i" aria-expanded="false" aria-pressed="false">
					<span class="home-locations__choice-inner">
					<span class="home-locations__badge" aria-hidden="true">I</span>
					<span class="home-locations__choice-copy"><span class="home-locations__choice-name">Avenue I</span><span class="home-locations__choice-address">810 W Ave I<br>Lancaster, CA</span></span>
				</span>
				</button>
			</div>
			<article id="home-location-avenue-h" class="home-locations__detail home-locations__detail--avenue-h" data-location-panel="avenue-h" hidden>
				<div class="home-locations__detail-heading"><span class="home-locations__badge" aria-hidden="true">H</span><div><h3>Avenue H</h3><address>1036 W Avenue H<br>Lancaster, CA 93534</address></div></div>
				<div class="home-locations__actions">
					<a class="home-locations__order" href="<?php echo esc_url( $location_routes['Avenue H']['pickup'] ); ?>">Order from Avenue H</a>
					<a class="home-locations__directions" href="https://www.google.com/maps/search/?api=1&amp;query=1036%20W%20Avenue%20H%2C%20Lancaster%2C%20CA%2093534" target="_blank" rel="noopener noreferrer">Get Directions <span aria-hidden="true">&#8599;</span></a>
					<button class="home-locations__reset" type="button" data-locations-reset>Show Both Locations</button>
				</div>
			</article>
			<article id="home-location-avenue-i" class="home-locations__detail home-locations__detail--avenue-i" data-location-panel="avenue-i" hidden>
				<div class="home-locations__detail-heading"><span class="home-locations__badge" aria-hidden="true">I</span><div><h3>Avenue I</h3><address>810 W Ave I<br>Lancaster, CA</address></div></div>
				<div class="home-locations__actions">
					<a class="home-locations__order" href="<?php echo esc_url( $location_routes['Avenue I']['pickup'] ); ?>">Order from Avenue I</a>
					<a class="home-locations__directions" href="https://www.google.com/maps/search/?api=1&amp;query=810%20W%20Ave%20I%2C%20Lancaster%2C%20CA" target="_blank" rel="noopener noreferrer">Get Directions <span aria-hidden="true">&#8599;</span></a>
					<button class="home-locations__reset" type="button" data-locations-reset>Show Both Locations</button>
				</div>
			</article>
		</div>
	</div>
	</div>
	<div class="home-locations__handoff" data-locations-handoff aria-hidden="true"></div>
</section>

<section id="about" class="home-section quality-section home-quality" aria-labelledby="quality-title">
	<div class="home-quality__media">
		<?php if ( $home_quality_image ) : ?>
			<img src="<?php echo esc_url( $home_quality_image ); ?>" alt="Empire King Burger food" loading="lazy">
		<?php else : ?>
			<div class="home-quality__placeholder" aria-hidden="true"><span></span><span></span><span></span></div>
		<?php endif; ?>
	</div>
	<div class="home-quality__copy">
		<p class="home-quality__eyebrow">Quality First</p>
		<h2 id="quality-title">Fresh Food. No Complicated Story.</h2>
		<p class="home-quality__intro">Fresh, never frozen burger patties. Fresh vegetables. Beef bacon. Crinkle-cut fries. The kind of straightforward food Empire King is built around.</p>
		<dl class="home-quality__facts">
			<div><dt>Fresh</dt><dd>Never-frozen burger patties</dd></div>
			<div><dt>Fresh Veggies</dt><dd>Fresh vegetables</dd></div>
			<div><dt>Beef Bacon</dt><dd>Not pork bacon</dd></div>
		</dl>
	</div>
</section>

<section id="contact" class="screen-reader-text" aria-label="Contact"></section>
<section id="legal" class="screen-reader-text" aria-label="Legal"></section>
<?php
get_footer();
