<?php // visit.php
include __DIR__ . '/includes/header.php'; ?>
<main class="ek-main ek-main-visit">
  <section class="ek-section ek-visit-hero">
    <div class="container">
      <div class="ek-visit-hero-inner animate-on-scroll" data-animate="fade-up">
        <span class="ek-hero-badge">Lancaster, CA · Ave H</span>
        <h1>Visit Empire King Burger 3 Ave H</h1>
        <p>Fast burgers, snacks &amp; ice cream on Avenue H. Easy parking, quick service and late-night hours.</p>
        <div class="ek-visit-hero-actions">
          <a href="#map" class="btn btn-primary">Take Me There</a>
          <a href="tel:16615223247" class="btn btn-ghost">Call Us</a>
          <a href="/order.php" class="btn btn-outline">Order Online</a>
        </div>
      </div>
    </div>
  </section>

  <section id="map" class="ek-section ek-visit-map">
    <div class="container ek-visit-layout animate-on-scroll" data-animate="fade-up">
      <div class="ek-visit-map-column">
        <div class="ek-map-embed-wrapper">
          <!-- NOTE: Replace the src with the real Google Maps embed URL for:
               1036 W Avenue H, Lancaster, CA 93534
               The current src is a placeholder. -->
          <iframe
            title="Map to Empire King Burger 3 Ave H"
            src="https://www.google.com/maps/embed?pb=PLACEHOLDER_EMBED_URL"
            width="100%"
            height="100%"
            style="border:0;"
            allowfullscreen=""
            loading="lazy"
            referrerpolicy="no-referrer-when-downgrade"
          ></iframe>
        </div>
      </div>
      <div class="ek-visit-info-column">
        <h2>Find Us on Ave H</h2>
        <p class="ek-visit-address">
          1036 W Avenue H<br>
          Lancaster, CA 93534
        </p>
        <div class="ek-visit-contact">
          <p><strong>Phone:</strong> <a href="tel:16615223247">661-522-3247</a></p>
        </div>

        <div class="ek-visit-hours-block">
          <h3>Hours</h3>
          <ul class="ek-visit-hours">
            <li><span>Mon – Fri:</span> <span>10 AM – 11 PM</span></li>
            <li><span>Saturday:</span> <span>Open until 1 AM</span></li>
            <li><span>Sunday:</span> <span>10 AM – 11 PM</span></li>
          </ul>
          <p class="ek-visit-note">Hours may vary on holidays.</p>
        </div>

        <div class="ek-visit-actions">
          <a
            href="https://maps.google.com/?q=1036+W+Avenue+H,+Lancaster,+CA+93534"
            target="_blank"
            rel="noopener noreferrer"
            class="btn btn-primary"
          >
            Open in Google Maps
          </a>
          <a href="/order.php" class="btn btn-ghost">Order Online</a>
        </div>
      </div>
    </div>
  </section>

  <section class="ek-strip ek-strip-inline ek-strip-visit animate-on-scroll" data-animate="fade-up">
    <div class="container">
      <p>Pickup on Ave H · Delivery available · Also on UberEats &amp; DoorDash</p>
    </div>
  </section>
</main>
<?php include __DIR__ . '/includes/footer.php'; ?>
