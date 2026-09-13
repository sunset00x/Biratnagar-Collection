# 🛒 Biratnagar Collection — Advanced E-Commerce Platform

**Biratnagar Collection** is an enterprise-grade e-commerce web application built for seamless online shopping and local delivery operations across Morang and Koshi Province, Nepal. The platform combines core e-commerce capabilities with AI-driven sales assistance, automated local SMS notifications, eSewa payment flows, dedicated driver dispatching, and affiliate network tracking.

---

## 🌟 Key Features

### 🛒 Customer Storefront & Checkout
* **Dynamic Product Catalog**: Filter by category, search by natural language, and view product variations.
* **Dual Payment Integration**: Supports Cash on Delivery (COD) and official **eSewa (ePay v2 API)** payment integration with signature verification.
* **Coupon & Discount Engine**: Apply percentage-based or flat discount codes with minimum order constraints.
* **Saved Items & Reordering**: Account dashboard featuring 1-click reordering based on past purchase history.

### 🤖 AI Salesman Assistant (`/api/chatbot.php`)
* **Multilingual / Neplish NLU**: Understands search queries in English, Nepali, and Neplish (e.g., *"Chiya patti under 300"*).
* **Recipe-to-Cart Bundler**: Auto-assembles grocery bundles when users ask for recipe ingredients (e.g., Biryani or Momo essentials).
* **Visual Search (Image Matching)**: Allows shoppers to upload photo samples of household items to match store catalog products.
* **Conversational Order Tracking**: Direct order status lookups using order receipts (e.g., `BRP-XXXXXXXX`).

### 📱 Nepalese SMS Notification System (`includes/sms.php`)
* Integrated gateway support for **Aakash SMS** and **Sparrow SMS**.
* Automated lifecycle triggers:
  * **Order Placed**: Sent immediately upon checkout completion.
  * **Out for Delivery**: Includes rider name and contact details.
  * **Order Delivered**: Sent upon successful handover.

### 🛵 Rider Delivery Dispatch Portal (`/driver/`)
* Mobile-optimized interface for local delivery drivers across Biratnagar.
* View assigned active deliveries, customer delivery addresses, and call buttons.
* Secure delivery confirmation using OTP verification.

### 💰 Loyalty Wallet & Affiliate Network
* **Store Wallet & Points**: Earn 1 point per Rs. 100 spent, redeemable for checkout discounts.
* **Affiliate Tracking**: Custom referral link tracking (`?ref=CODE`) with automated commission allocation.

### 📊 Admin Management Control (`/admin/`)
* Manage products, categories, active coupons, and order statuses.
* **SMS Gateway Panel**: Configure API keys, sender identities, and inspect real-time SMS delivery logs.
* **AI Analytics**: Track unmet customer inventory demands (zero-result search queries) to guide catalog expansion.

---

## 📁 Project Architecture

```text
biratnagar-collection/
├── admin/                     # Super Admin Dashboard
│   ├── includes/              # Admin layout headers/footers
│   ├── ai-analytics.php       # AI Search & Unmet Demand Analytics
│   ├── orders.php             # Order lifecycle management
│   └── sms-settings.php       # SMS Gateway configuration & logs
├── api/
│   └── chatbot.php            # LLM-powered AI Salesman REST endpoint
├── driver/
│   ├── index.php              # Delivery Rider mobile dashboard
│   └── logout.php             # Driver session termination
├── includes/
│   ├── db.php                 # PDO Database connection
│   ├── functions.php          # Core utility functions
│   ├── sms.php                # Aakash & Sparrow SMS API handler
│   ├── wallet.php             # User wallet & loyalty points engine
│   ├── affiliate.php          # Referral commission processor
│   ├── personalization.php   # Reorder & cross-sell recommendation helpers
│   └── chatbot-widget.php     # Floating AI Chatbot UI element
├── pages/
│   ├── about.php              # Minimal About Us page
│   ├── checkout.php           # Order checkout & eSewa payment flow
│   └── ...                    # Cart, Account, and Catalog pages
├── index.php                  # Main Application Router
└── README.md