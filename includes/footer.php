<?php
// REUSABLE FOOTER COMPONENT
?>
<footer class="footer">
  <div class="container">
    <div class="footer-content">
      <!-- Brand Section -->
      <div class="footer-brand">
        <div class="footer-logo">
          <img src="images/Logo.png" alt="EatLink Logo" style="max-height: 40px;">
        </div>
        <p class="footer-description">
          Savor the moments. Order your favorite meals from top-rated restaurants and local brands, delivered hot and fresh right to your doorstep.
        </p>
      </div>

      <!-- Links Grid -->
      <div class="footer-links-grid">
        <!-- Quick Links -->
        <div class="footer-section">
          <h5 class="footer-heading">Quick Links</h5>
          <ul class="footer-links">
            <li><a href="index.php">Home</a></li>
            <li><a href="menu.php">Explore Menu</a></li>
            <li><a href="cart.php">My Cart</a></li>
            <li><a href="dashboard/user.php">My Account</a></li>
            <li><a href="dashboard/user.php">Track Orders</a></li>
          </ul>
        </div>

        <!-- Our Partners -->
        <div class="footer-section">
          <h5 class="footer-heading">Our Partners</h5>
          <ul class="footer-links">
            <li><a href="#">Pizza Hut</a></li>
            <li><a href="#">McDonald's</a></li>
            <li><a href="#">KFC</a></li>
            <li><a href="#">Burger King</a></li>
            <li><a href="#">Subway</a></li>
          </ul>
        </div>

        <!-- Contact Us -->
        <div class="footer-section">
          <h5 class="footer-heading">Contact Us</h5>
          <ul class="footer-contact">
            <li class="contact-item">
              <span class="contact-icon material-symbols-outlined" style="font-size:1.1rem; vertical-align:middle;">location_on</span>
              <span>45 Galle Road, Colombo 03, Sri Lanka</span>
            </li>
            <li class="contact-item">
              <span class="contact-icon material-symbols-outlined" style="font-size:1.1rem; vertical-align:middle;">call</span>
              <span>+94 11 234 5678</span>
            </li>
            <li class="contact-item">
              <span class="contact-icon material-symbols-outlined" style="font-size:1.1rem; vertical-align:middle;">mail</span>
              <span>info@eatlink.com</span>
            </li>
            <li class="contact-item">
              <span class="contact-icon material-symbols-outlined" style="font-size:1.1rem; vertical-align:middle;">schedule</span>
              <span>Daily: 8:00 AM - 11:00 PM</span>
            </li>
          </ul>
        </div>
      </div>
    </div>

    <!-- Footer Bottom -->
    <div class="footer-bottom">
      <p>&copy; <?= date('Y') ?> EatLink. All rights reserved.</p>
    </div>
  </div>
</footer>
