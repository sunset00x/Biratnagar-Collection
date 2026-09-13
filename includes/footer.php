</main>

  <footer class="footer">
    <div class="container">
      <div class="footer-grid">

        <div class="footer-brand">
          <div class="brand">
            <span class="brand-mark">🛒</span>
            <span class="brand-text" style="color:white">
              Biratnagar<br />
              <span style="color:#f59e0b">Collection</span>
            </span>
          </div>
          <p>
            Your trusted everyday departmental store for groceries,
            household essentials, fresh products and more.
          </p>
          <div class="socials">
            <span>f</span><span>◎</span><span>𝕏</span><span>▶</span>
          </div>
        </div>

        <div>
          <h3>Quick Links</h3>
          <a href="index.php?page=home">Home</a>
          <a href="index.php?page=shop">Shop</a>
          <a href="index.php?page=categories">Categories</a>
          <a href="index.php?page=offers">Offers</a>
          <a href="index.php?page=about">About Us</a>
        </div>

        <div>
          <h3>Customer Service</h3>
          <a href="index.php?page=contact">Contact Us</a>
          <a href="index.php?page=faq">FAQ</a>
          <a href="index.php?page=delivery">Delivery Information</a>
          <a href="index.php?page=privacy">Privacy Policy</a>
          <a href="index.php?page=terms">Terms & Conditions</a>
        </div>

        <div>
          <h3>Popular Categories</h3>
          <a href="index.php?page=shop&category=1">Groceries</a>
          <a href="index.php?page=shop&category=8">Fresh</a>
          <a href="index.php?page=shop&category=2">Beverages</a>
          <a href="index.php?page=shop&category=7">Snacks</a>
          <a href="index.php?page=shop&category=3">Personal Care</a>
        </div>
      </div>

      <div class="payment">
        <span>© 2026 Biratnagar Collection . All rights reserved.</span>
        <span>Payment options: Cash on Delivery · eSewa · Khalti · Bank Transfer</span>
      </div>
      <div class="copyright">
       
      </div>
    </div>
  </footer>

  <div class="toast" id="toast"></div>

  <script>
    const themeSelect = document.getElementById("themeSelect");
    function applyTheme(theme) {
      document.documentElement.dataset.theme = theme;
      themeSelect.value = theme;
      localStorage.setItem("brp_theme", theme);
    }
    applyTheme(localStorage.getItem("brp_theme") || "light");
    themeSelect.addEventListener("change", e => applyTheme(e.target.value));

    function toast(message) {
      const el = document.getElementById("toast");
      el.textContent = message;
      el.classList.add("show");
      clearTimeout(window.toastTimer);
      window.toastTimer = setTimeout(() => el.classList.remove("show"), 2600);
    }

    function addToCart(id, qty = 1) {
      fetch(`index.php?action=cart_add&id=${id}&qty=${qty}`)
        .then(res => res.json())
        .then(data => {
            if(data.success) {
               document.getElementById("cartCount").textContent = data.cartCount;
               toast(data.message);
            } else {
               toast(data.message || "Error adding item.");
            }
        });
    }

    function buyNow(id) {
      addToCart(id);
      window.location.href = "index.php?page=checkout";
    }

    function toggleWishlist(id) {
      fetch(`index.php?action=wishlist_toggle&id=${id}`)
        .then(res => res.json())
        .then(data => {
            if(data.success) {
               document.getElementById("wishlistCount").textContent = data.wishlistCount;
               toast(data.message);
               setTimeout(() => location.reload(), 800);
            } else {
               toast("Please login to manage wishlist.");
               window.location.href = "index.php?page=login";
            }
        });
    }

    document.getElementById("menuToggle")?.addEventListener("click", () => {
      document.getElementById("mainNav").classList.toggle("open");
    });
  </script>
  <?php require_once __DIR__ . '/chatbot-widget.php'; ?>
</body>
</html>