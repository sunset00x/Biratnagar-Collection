<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Dashboard | Biratnagar Collection</title>
  <link rel="stylesheet" href="/biratnagar-collection/admin/assets/css/admin.css">
</head>
<body>
  <div class="sidebar">
    <h2>Admin Panel</h2>
    <a href="index.php">Dashboard</a>
    <a href="orders.php">Orders</a>
       <a href="product-add.php">Add Product</a>
    <a href="categories.php">Categories</a>
    <a href="reviews.php">Reviews</a>
    <a href="messages.php">Message</a>
    <a href="products.php">Products</a>
    <a href="coupons.php">Coupons</a>
    <a href="sms-settings.php">SMS Settings</a>
<a href="settings.php">Settings</a> 
 
    <a href="logout.php" style="margin-top: 50px; color: #ff8888;">Logout</a>
  </div>
  <div class="main-content">
    <div class="top-header">
      <h1>Welcome, <?= htmlspecialchars($_SESSION['admin_name'] ?? 'Admin') ?></h1>
    </div>