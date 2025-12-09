<?php
// Placeholder Supabase image URLs to be replaced with real public URLs later.
$ek_categories = [
    [
        'slug' => 'specials',
        'label' => 'Checkout Our Specials',
        'tagline' => 'Combos & hot deals.',
        'class' => 'is-special',
        'image' => 'https://YOUR_SUPABASE_URL/storage/v1/object/public/empire-king/categories/specials.jpg'
    ],
    [
        'slug' => 'burgers',
        'label' => 'Burgers',
        'tagline' => 'Smash, classic & loaded.',
        'class' => '',
        'image' => 'https://YOUR_SUPABASE_URL/storage/v1/object/public/empire-king/categories/burgers.jpg'
    ],
    [
        'slug' => 'fries-sides',
        'label' => 'Fries & Sides',
        'tagline' => 'Crispy fries, rings & more.',
        'class' => '',
        'image' => 'https://YOUR_SUPABASE_URL/storage/v1/object/public/empire-king/categories/fries-sides.jpg'
    ],
    [
        'slug' => 'chicken-nuggets',
        'label' => 'Chicken & Nuggets',
        'tagline' => 'Crispy bites & tenders.',
        'class' => '',
        'image' => 'https://YOUR_SUPABASE_URL/storage/v1/object/public/empire-king/categories/chicken-nuggets.jpg'
    ],
    [
        'slug' => 'corn-dogs-burritos',
        'label' => 'Corn Dogs & Burritos',
        'tagline' => 'Quick handheld favorites.',
        'class' => '',
        'image' => 'https://YOUR_SUPABASE_URL/storage/v1/object/public/empire-king/categories/corn-dogs-burritos.jpg'
    ],
    [
        'slug' => 'ice-cream-shakes',
        'label' => 'Ice Cream & Shakes',
        'tagline' => 'Soft serve, shakes & treats.',
        'class' => '',
        'image' => 'https://YOUR_SUPABASE_URL/storage/v1/object/public/empire-king/categories/ice-cream-shakes.jpg'
    ],
    [
        'slug' => 'drinks',
        'label' => 'Drinks',
        'tagline' => 'Sodas & refreshing drinks.',
        'class' => '',
        'image' => 'https://YOUR_SUPABASE_URL/storage/v1/object/public/empire-king/categories/drinks.jpg'
    ],
    [
        'slug' => 'family-packs',
        'label' => 'Family Packs',
        'tagline' => 'Shareable packs for the crew.',
        'class' => '',
        'image' => 'https://YOUR_SUPABASE_URL/storage/v1/object/public/empire-king/categories/family-packs.jpg'
    ],
];

include 'includes/header.php';
?>

<main class="ek-main ek-main-home">
<section class="ek-hero">
    <div class="ek-hero-media">
        <video src="/assets/media/hero-burger.mp4" autoplay muted loop playsinline poster="/assets/media/hero-burger-poster.jpg"></video>
        <!-- Replace the video and poster URLs above with Supabase media URLs later. -->
        <div class="ek-hero-overlay"></div>
    </div>
    <div class="ek-hero-content container animate-on-scroll" data-animate="fade-up">
        <span class="ek-hero-badge">Fast Food · Lancaster, CA</span>
        <h1>Fast Burgers, Snacks &amp; Ice Cream on Ave H</h1>
        <p>Order online in seconds. Pickup hot &amp; ready, or get it delivered.</p>
        <div class="ek-hero-actions">
            <a href="/order.php" class="btn btn-primary">Order Now</a>
            <a href="/order.php?cat=specials" class="btn btn-ghost">View Specials</a>
        </div>
        <div class="ek-hero-partners">
            <span>Also available on:</span>
            <div class="ek-hero-partner-logos">
                <span class="badge-pill">UberEats</span>
                <span class="badge-pill">DoorDash</span>
            </div>
        </div>
    </div>
</section>

<section class="ek-section ek-specials">
    <div class="container">
        <header class="ek-section-header animate-on-scroll" data-animate="fade-up">
            <h2>Daily Specials</h2>
            <p>Quick combos at wallet-friendly prices.</p>
        </header>

        <div class="ek-specials-grid">
            <article class="ek-card ek-card-special animate-on-scroll" data-animate="fade-up">
                <div class="ek-card-body">
                    <h3>Deluxe Burger</h3>
                    <p class="ek-card-price">$9.25</p>
                    <p class="ek-card-tagline">Classic deluxe burger with fries &amp; drink options.</p>
                    <a href="/order.php?cat=specials" class="btn btn-outline">Order This Deal</a>
                </div>
            </article>

            <article class="ek-card ek-card-special animate-on-scroll" data-animate="fade-up">
                <div class="ek-card-body">
                    <h3>2 Burritos</h3>
                    <p class="ek-card-price">$9.25</p>
                    <p class="ek-card-tagline">Stuffed and ready to roll for your lunch break.</p>
                    <a href="/order.php?cat=specials" class="btn btn-outline">Order This Deal</a>
                </div>
            </article>

            <article class="ek-card ek-card-special animate-on-scroll" data-animate="fade-up">
                <div class="ek-card-body">
                    <h3>2 Corn Dogs</h3>
                    <p class="ek-card-price">$9.25</p>
                    <p class="ek-card-tagline">Golden, crispy classics with dipping sauces.</p>
                    <a href="/order.php?cat=specials" class="btn btn-outline">Order This Deal</a>
                </div>
            </article>

            <article class="ek-card ek-card-special animate-on-scroll" data-animate="fade-up">
                <div class="ek-card-body">
                    <h3>10pc Chicken Nugget</h3>
                    <p class="ek-card-price"><span class="ek-price-strike">$9.25</span> $7.75</p>
                    <p class="ek-card-tagline">Crunchy nuggets with your choice of dipping sauce.</p>
                    <a href="/order.php?cat=specials" class="btn btn-outline">Order This Deal</a>
                </div>
            </article>
        </div>
    </div>
</section>

<section class="ek-section ek-categories">
    <div class="container">
        <header class="ek-section-header animate-on-scroll" data-animate="fade-up">
            <h2>Choose What You're Craving</h2>
            <p>Jump straight into your favorite part of the menu.</p>
        </header>

        <div class="ek-category-grid">
            <?php foreach ($ek_categories as $cat): ?>
                <a
                    href="/order.php?cat=<?= htmlspecialchars($cat['slug']) ?>"
                    class="ek-category-card animate-on-scroll <?= !empty($cat['class']) ? $cat['class'] : '' ?>"
                    data-animate="fade-up"
                    style="--ek-category-bg-image: url('<?= htmlspecialchars($cat['image']) ?>');"
                >
                    <div class="ek-category-overlay"></div>
                    <div class="ek-category-content">
                        <h3><?= htmlspecialchars($cat['label']) ?></h3>
                        <p><?= htmlspecialchars($cat['tagline']) ?></p>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="ek-strip ek-strip-inline">
    <div class="container">
        <p>We also have: ice cream · milkshakes · breakfast burritos · loaded fries · churros</p>
    </div>
</section>

<section class="ek-section ek-visit-preview">
    <div class="container ek-visit-layout animate-on-scroll" data-animate="fade-up">
        <div class="ek-visit-text">
            <h2>Visit Us on Ave H</h2>
            <p>Find us at 1036 W Avenue H, Lancaster, CA 93534. Fast service, easy parking and late-night hours.</p>
            <ul class="ek-visit-hours">
                <li>Mon–Fri: 10 AM – 11 PM</li>
                <li>Saturday: Open until 1 AM</li>
                <li>Sunday: 10 AM – 11 PM</li>
            </ul>
            <div class="ek-visit-actions">
                <a href="/visit.php" class="btn btn-ghost">Take Me There</a>
                <a href="tel:16615223247" class="btn btn-link">Call Us</a>
            </div>
        </div>
        <div class="ek-visit-map-placeholder">
            <div class="ek-map-box">
                <p>Map preview will be shown on the Visit Us page.</p>
                <a href="/visit.php" class="btn btn-outline">Open Visit Us</a>
            </div>
        </div>
    </div>
</section>
</main>

<?php include 'includes/footer.php'; ?>
