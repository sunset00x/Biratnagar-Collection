<?php
require_once __DIR__ . '/functions.php';

$cartCount = array_sum($_SESSION['cart'] ?? []);
$wishlistCount = 0;

if (isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM wishlist WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $wishlistCount = $stmt->fetchColumn();
}
$currentPage = $_GET['page'] ?? 'home';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="description" content="Biratnagar Collection — Your trusted departmental store in Biratnagar, Nepal." />
  <title>Biratnagar Collection | Collection</title>
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

  <div class="topbar">
    <div class="container topbar-inner">
      <span>📍 Delivering across Biratnagar & selected areas of Nepal</span>
      <span>Free delivery on orders above Rs. 2,000</span>
    </div>
  </div>

  <header class="header">
    <div class="container header-main">

      <button class="menu-toggle" id="menuToggle" aria-label="Menu">☰</button>

      <a href="index.php?page=home" class="brand">
        <span class="brand-mark">🛒</span>
        <span class="brand-text">
          Biratnagar<br />
          <span style="color:#f59e0b">Collection</span>
        </span>
      </a>

      <form class="search" action="index.php" method="GET">
        <input type="hidden" name="page" value="shop">
        <input name="q" type="search" value="<?= htmlspecialchars($_GET['q'] ?? '') ?>" placeholder="Search for rice, shampoo, biscuits, vegetables..." />
        <button type="submit">⌕</button>
      </form>

      <div class="header-actions">
        <a href="index.php?page=account" class="account-btn">👤 Account</a>
        <label class="sr-only" for="themeSelect"></label>
        <select class="theme-select" id="themeSelect" aria-label="Theme">
          <option value="light">☀ Light</option>
          <option value="dark">☾ Dark</option>
        </select>
        <a href="index.php?page=wishlist" class="icon-btn" aria-label="Wishlist">
          ♡
          <span class="badge" id="wishlistCount"><?= $wishlistCount ?></span>
        </a>
        <a href="index.php?page=cart" class="icon-btn" aria-label="Cart">
          🛒
          <span class="badge" id="cartCount"><?= $cartCount ?></span>
        </a>
      </div>
    </div>

    <nav class="nav" id="mainNav">
      <div class="container nav-inner">
        <a href="index.php?page=home" class="<?= $currentPage === 'home' ? 'active' : '' ?>">Home</a>
        <a href="index.php?page=categories" class="<?= $currentPage === 'categories' ? 'active' : '' ?>">Categories</a>
        <a href="index.php?page=shop" class="<?= $currentPage === 'shop' ? 'active' : '' ?>">Shop</a>
        <a href="index.php?page=offers" class="<?= $currentPage === 'offers' ? 'active' : '' ?>">Offers</a>
        <a href="index.php?page=new-arrivals" class="<?= $currentPage === 'new-arrivals' ? 'active' : '' ?>">New Arrivals</a>
          </div>
    </nav>
  </header>

  <main>