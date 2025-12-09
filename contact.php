// contact.php
<?php include __DIR__ . '/includes/header.php'; ?>
<main class="ek-main ek-main-contact">
  <section class="ek-section ek-contact-hero">
    <div class="container">
      <div class="ek-contact-hero-inner animate-on-scroll" data-animate="fade-up">
        <span class="ek-hero-badge">Questions &amp; Feedback</span>
        <h1>Contact Empire King Burger 3 Ave H</h1>
        <p>Have a question about your order, our menu or your experience? Send us a message and we'll get back to you as soon as we can.</p>
        <div class="ek-contact-hero-actions">
          <a href="/order.php" class="btn btn-primary">Order Online</a>
          <a href="tel:16615223247" class="btn btn-ghost">Call Us</a>
        </div>
      </div>
    </div>
  </section>

  <section class="ek-section ek-contact-body">
    <div class="container ek-contact-layout animate-on-scroll" data-animate="fade-up">
      <div class="ek-contact-info">
        <h2>Best ways to reach us</h2>
        <p>For fast food orders, updates or changes, we recommend calling us directly or using our online ordering system.</p>
        <ul class="ek-contact-list">
          <li>
            <strong>Phone:</strong>
            <a href="tel:16615223247">661-522-3247</a>
          </li>
          <li>
            <strong>Order Online:</strong>
            <a href="/order.php">Start your order</a>
          </li>
        </ul>
        <p>You can also use the form to ask about menu items, opening hours, feedback or anything else related to Empire King Burger 3 Ave H.</p>
      </div>

      <div class="ek-contact-form-wrapper">
        <h2>Send us a message</h2>
        <!-- NOTE: Handle form submission server-side in a future step (e.g. send email or store in a database). -->
        <form class="ek-contact-form" method="post" action="/contact.php">
          <div class="ek-form-row">
            <label for="ek-name">Name</label>
            <input type="text" id="ek-name" name="name" placeholder="Your name" required>
          </div>

          <div class="ek-form-row">
            <label for="ek-phone">Phone</label>
            <input type="tel" id="ek-phone" name="phone" placeholder="Your phone number">
          </div>

          <div class="ek-form-row">
            <label for="ek-email">Email</label>
            <input type="email" id="ek-email" name="email" placeholder="you@example.com">
          </div>

          <div class="ek-form-row">
            <label for="ek-message">Message</label>
            <textarea id="ek-message" name="message" rows="4" placeholder="Tell us how we can help" required></textarea>
          </div>

          <div class="ek-form-actions">
            <button type="submit" class="btn btn-primary">Send Message</button>
            <a href="/order.php" class="btn btn-outline">Order Online Instead</a>
          </div>
        </form>
      </div>
    </div>
  </section>
</main>
<?php include __DIR__ . '/includes/footer.php'; ?>
