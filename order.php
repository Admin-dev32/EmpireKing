// order.php
<?php
$currentCategory = filter_input(INPUT_GET, 'cat', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
$currentCategory = $currentCategory ? trim($currentCategory) : 'specials';

$ek_order_categories = [
    [
        'slug' => 'specials',
        'label' => 'Specials',
        'description' => 'Combos & hot deals.',
    ],
    [
        'slug' => 'burgers',
        'label' => 'Burgers',
        'description' => 'Smash, classic & loaded burgers.',
    ],
    [
        'slug' => 'fries-sides',
        'label' => 'Fries & Sides',
        'description' => 'Crispy fries, rings & more.',
    ],
    [
        'slug' => 'chicken-nuggets',
        'label' => 'Chicken & Nuggets',
        'description' => 'Crispy bites, tenders & nuggets.',
    ],
    [
        'slug' => 'corn-dogs-burritos',
        'label' => 'Corn Dogs & Burritos',
        'description' => 'Quick handheld favorites.',
    ],
    [
        'slug' => 'ice-cream-shakes',
        'label' => 'Ice Cream & Shakes',
        'description' => 'Soft serve, shakes & sweet treats.',
    ],
    [
        'slug' => 'drinks',
        'label' => 'Drinks',
        'description' => 'Sodas & refreshing drinks.',
    ],
    [
        'slug' => 'family-packs',
        'label' => 'Family Packs',
        'description' => 'Shareable packs for the crew.',
    ],
];

$validSlugs = array_column($ek_order_categories, 'slug');
if (!in_array($currentCategory, $validSlugs, true)) {
    $currentCategory = 'specials';
}
?>
<?php include __DIR__ . '/includes/header.php'; ?>
<main class="ek-main ek-main-order" data-current-cat="<?= htmlspecialchars($currentCategory) ?>">
  <section class="ek-order-hero">
    <div class="container">
      <div class="ek-order-hero-inner animate-on-scroll" data-animate="fade-up">
        <div class="ek-order-hero-text">
          <h1>Order Online</h1>
          <p>Build your order fast. Pickup hot &amp; ready, or get it delivered.</p>
          <ul class="ek-order-options">
            <li>✅ Pickup on Ave H</li>
            <li>✅ Delivery available</li>
            <li>✅ Also on UberEats &amp; DoorDash</li>
          </ul>
        </div>
        <div class="ek-order-hero-badge">
          <span>Empire King Burger 3</span>
          <small>Ave H · Lancaster, CA</small>
        </div>
      </div>
    </div>
  </section>

  <section class="ek-order-categories">
    <div class="container">
      <div class="ek-order-cat-scroll">
        <?php foreach ($ek_order_categories as $cat):
          $isActive = ($currentCategory === $cat['slug']);
        ?>
          <a
            href="/order.php?cat=<?= htmlspecialchars($cat['slug']) ?>"
            class="ek-order-cat-pill <?= $isActive ? 'is-active' : '' ?>"
            data-cat="<?= htmlspecialchars($cat['slug']) ?>"
          >
            <?= htmlspecialchars($cat['label']) ?>
          </a>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

  <section class="ek-order-body">
    <div class="container">
      <?php foreach ($ek_order_categories as $cat): ?>
        <section
          id="ek-cat-<?= htmlspecialchars($cat['slug']) ?>"
          class="ek-order-category-section animate-on-scroll"
          data-animate="fade-up"
          data-cat-section="<?= htmlspecialchars($cat['slug']) ?>"
        >
          <header class="ek-order-category-header">
            <h2><?= htmlspecialchars($cat['label']) ?></h2>
            <p><?= htmlspecialchars($cat['description']) ?></p>
          </header>

          <div class="ek-order-products-placeholder">
            <p>
              Products for <strong><?= htmlspecialchars($cat['label']) ?></strong>
              will be loaded here via WooCommerce integration.
            </p>
            <p class="ek-order-products-note">
              For now, this is a visual placeholder so we can design the layout and navigation.
            </p>
            <a href="/order.php?cat=<?= htmlspecialchars($cat['slug']) ?>" class="btn btn-outline">
              View <?= htmlspecialchars($cat['label']) ?> in store
            </a>
          </div>
        </section>
      <?php endforeach; ?>
    </div>
  </section>
</main>
<?php include __DIR__ . '/includes/footer.php'; ?>
