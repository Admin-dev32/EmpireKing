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
?>
<section class="home-hero" aria-labelledby="home-title">
	<div class="home-hero__copy">
		<p class="home-eyebrow">Lancaster, California</p>
		<h1 id="home-title">Made Fresh. Built to Satisfy.</h1>
		<p class="home-lead">Big burgers, satisfying combos, and local favorites from Empire King Burger.</p>
		<div class="home-actions">
			<a class="button button--primary" href="#locations">Order Now</a>
			<a class="button button--secondary" href="#locations">View Locations</a>
		</div>
	</div>
	<div class="media-placeholder media-placeholder--hero" aria-hidden="true"><span>Hero Food Photo</span></div>
</section>

<section id="favorites" class="home-section" aria-labelledby="favorites-title">
	<div class="section-intro">
		<p class="home-eyebrow">Prototype section</p>
		<h2 id="favorites-title">Featured Favorites</h2>
	</div>
	<div class="favorite-grid">
		<article class="favorite-card">
			<div class="media-placeholder media-placeholder--card" aria-hidden="true"><span>Product Photo</span></div>
			<div class="favorite-card__body"><h3>Burger Favorite</h3><p>Development-only placeholder description for the future featured item.</p><a href="#locations">Order</a></div>
		</article>
		<article class="favorite-card">
			<div class="media-placeholder media-placeholder--card" aria-hidden="true"><span>Product Photo</span></div>
			<div class="favorite-card__body"><h3>Combo Favorite</h3><p>Development-only placeholder description for the future featured item.</p><a href="#locations">Order</a></div>
		</article>
		<article class="favorite-card">
			<div class="media-placeholder media-placeholder--card" aria-hidden="true"><span>Product Photo</span></div>
			<div class="favorite-card__body"><h3>Family Favorite</h3><p>Development-only placeholder description for the future featured item.</p><a href="#locations">Order</a></div>
		</article>
	</div>
</section>

<section id="deals" class="home-section" aria-labelledby="deals-title">
	<div class="deal-band">
		<div class="deal-band__copy">
			<p class="home-eyebrow">Prototype promotion</p>
			<h2 id="deals-title">Current Deal</h2>
			<p>A limited-time offer will appear here.</p>
			<a class="button button--primary" href="#locations">Order Now</a>
		</div>
		<div class="media-placeholder media-placeholder--deal" aria-hidden="true"><span>Deal Photo</span></div>
	</div>
</section>

<section id="locations" class="home-section home-section--muted" aria-labelledby="locations-title">
	<div class="section-intro"><p class="home-eyebrow">Order by restaurant</p><h2 id="locations-title">Choose Your Location</h2></div>
	<div class="location-grid">
		<article class="location-card"><h3>Avenue H</h3><address>1036 W Avenue H<br>Lancaster, CA 93534</address></article>
		<article class="location-card"><h3>Avenue I</h3><address>810 W Ave I<br>Lancaster, CA</address></article>
	</div>
	<?php // Development state: store selection and routing are intentionally not wired in this prototype. ?>
</section>

<section id="about" class="home-section quality-section" aria-labelledby="quality-title">
	<div class="media-placeholder media-placeholder--quality" aria-hidden="true"><span>Quality / Restaurant Photo</span></div>
	<div class="quality-section__copy"><p class="home-eyebrow">Prototype section</p><h2 id="quality-title">Fresh Food. No Complicated Story.</h2><p>Development-only copy placeholder for the future About and food-quality introduction.</p></div>
</section>

<section id="contact" class="screen-reader-text" aria-label="Contact"></section>
<section id="legal" class="screen-reader-text" aria-label="Legal"></section>
<?php
get_footer();
