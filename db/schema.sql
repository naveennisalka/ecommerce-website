-- ============================================================
--  EatLink Database Schema
--  Run this in phpMyAdmin or MySQL CLI
--  mysql -u root -p < db/schema.sql
-- ============================================================

CREATE DATABASE IF NOT EXISTS eatlink_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE eatlink_db;

-- ── USERS (all roles) ──────────────────────────────────────
CREATE TABLE IF NOT EXISTS users (
  id            INT PRIMARY KEY AUTO_INCREMENT,
  name          VARCHAR(100) NOT NULL,
  email         VARCHAR(150) UNIQUE NOT NULL,
  password      VARCHAR(255) NOT NULL,
  phone         VARCHAR(20),
  role          ENUM('customer','shop_owner','delivery_man') DEFAULT 'customer',
  address       TEXT,
  avatar        VARCHAR(255),
  is_active     TINYINT(1) DEFAULT 1,
  created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ── SHOPS ─────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS shops (
  id            INT PRIMARY KEY AUTO_INCREMENT,
  owner_id      INT NOT NULL,
  name          VARCHAR(200) NOT NULL,
  description   TEXT,
  logo          VARCHAR(255),
  address       TEXT,
  phone         VARCHAR(20),
  is_active     TINYINT(1) DEFAULT 1,
  created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ── CATEGORIES ────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS categories (
  id            INT PRIMARY KEY AUTO_INCREMENT,
  name          VARCHAR(100) NOT NULL,
  icon          VARCHAR(20) DEFAULT '🍽️',
  created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ── BRANDS ────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS brands (
  id            INT PRIMARY KEY AUTO_INCREMENT,
  name          VARCHAR(100) NOT NULL,
  icon          VARCHAR(20) DEFAULT '🏪',
  color         VARCHAR(10) DEFAULT '#FF6B00',
  created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ── PRODUCTS ──────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS products (
  id              INT PRIMARY KEY AUTO_INCREMENT,
  shop_id         INT NOT NULL,
  category_id     INT,
  brand_id        INT,
  name            VARCHAR(200) NOT NULL,
  description     TEXT,
  price           DECIMAL(10,2) NOT NULL,
  original_price  DECIMAL(10,2),
  discount_percent INT DEFAULT 0,
  is_new          TINYINT(1) DEFAULT 0,
  delivery_type   ENUM('free','paid') DEFAULT 'free',
  stock           INT DEFAULT 100,
  image           VARCHAR(50) DEFAULT 'burger',
  is_active       TINYINT(1) DEFAULT 1,
  created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (shop_id)      REFERENCES shops(id) ON DELETE CASCADE,
  FOREIGN KEY (category_id)  REFERENCES categories(id) ON DELETE SET NULL,
  FOREIGN KEY (brand_id)     REFERENCES brands(id) ON DELETE SET NULL
);

-- ── PRODUCT IMAGES ────────────────────────────────────────
CREATE TABLE IF NOT EXISTS product_images (
  id            INT PRIMARY KEY AUTO_INCREMENT,
  product_id    INT NOT NULL,
  image_path    VARCHAR(255) NOT NULL,
  is_primary    TINYINT(1) DEFAULT 0,
  sort_order    INT DEFAULT 0,
  FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- ── CART ──────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS cart (
  id            INT PRIMARY KEY AUTO_INCREMENT,
  user_id       INT NOT NULL,
  product_id    INT NOT NULL,
  quantity      INT DEFAULT 1,
  created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_cart (user_id, product_id),
  FOREIGN KEY (user_id)    REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- ── WISHLIST ──────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS wishlist (
  id            INT PRIMARY KEY AUTO_INCREMENT,
  user_id       INT NOT NULL,
  product_id    INT NOT NULL,
  created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_wishlist (user_id, product_id),
  FOREIGN KEY (user_id)    REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- ── ORDERS ────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS orders (
  id                INT PRIMARY KEY AUTO_INCREMENT,
  user_id           INT NOT NULL,
  shop_id           INT NOT NULL,
  delivery_man_id   INT,
  status            ENUM('pending','confirmed','preparing','picked_up','on_the_way','delivered','cancelled') DEFAULT 'pending',
  total_amount      DECIMAL(10,2) NOT NULL,
  delivery_fee      DECIMAL(10,2) DEFAULT 0.00,
  delivery_address  TEXT NOT NULL,
  customer_phone    VARCHAR(20),
  notes             TEXT,
  delivery_pin      VARCHAR(6) DEFAULT NULL,
  created_at        TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at        TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id)         REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (shop_id)         REFERENCES shops(id) ON DELETE CASCADE,
  FOREIGN KEY (delivery_man_id) REFERENCES users(id) ON DELETE SET NULL
);

-- ── ORDER ITEMS ────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS order_items (
  id            INT PRIMARY KEY AUTO_INCREMENT,
  order_id      INT NOT NULL,
  product_id    INT NOT NULL,
  quantity      INT NOT NULL,
  unit_price    DECIMAL(10,2) NOT NULL,
  subtotal      DECIMAL(10,2) NOT NULL,
  FOREIGN KEY (order_id)   REFERENCES orders(id) ON DELETE CASCADE,
  FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- ── ORDER STATUS HISTORY ───────────────────────────────────
CREATE TABLE IF NOT EXISTS order_status_history (
  id            INT PRIMARY KEY AUTO_INCREMENT,
  order_id      INT NOT NULL,
  status        VARCHAR(50) NOT NULL,
  note          TEXT,
  changed_by    INT,
  created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (order_id)   REFERENCES orders(id) ON DELETE CASCADE,
  FOREIGN KEY (changed_by) REFERENCES users(id) ON DELETE SET NULL
);

-- ── NOTIFICATIONS ──────────────────────────────────────────
CREATE TABLE IF NOT EXISTS notifications (
  id            INT PRIMARY KEY AUTO_INCREMENT,
  user_id       INT NOT NULL,
  title         VARCHAR(200) NOT NULL,
  message       TEXT NOT NULL,
  type          ENUM('order','delivery','system','promotion') DEFAULT 'system',
  order_id      INT,
  is_read       TINYINT(1) DEFAULT 0,
  created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id)  REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE SET NULL
);

-- ── REVIEWS ───────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS reviews (
  id            INT PRIMARY KEY AUTO_INCREMENT,
  user_id       INT NOT NULL,
  product_id    INT NOT NULL,
  order_id      INT,
  rating        TINYINT NOT NULL CHECK (rating BETWEEN 1 AND 5),
  comment       TEXT,
  created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id)    REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
  FOREIGN KEY (order_id)   REFERENCES orders(id) ON DELETE SET NULL
);

-- ── DELIVERY ASSIGNMENTS ───────────────────────────────────
CREATE TABLE IF NOT EXISTS delivery_assignments (
  id                INT PRIMARY KEY AUTO_INCREMENT,
  order_id          INT NOT NULL UNIQUE,
  delivery_man_id   INT NOT NULL,
  pickup_address    TEXT NOT NULL,
  drop_address      TEXT NOT NULL,
  customer_name     VARCHAR(100),
  customer_phone    VARCHAR(20),
  package_price     DECIMAL(10,2),
  return_address    TEXT,
  status            ENUM('assigned','accepted','picked_up','delivered','returned') DEFAULT 'assigned',
  assigned_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  delivered_at      TIMESTAMP,
  FOREIGN KEY (order_id)        REFERENCES orders(id) ON DELETE CASCADE,
  FOREIGN KEY (delivery_man_id) REFERENCES users(id) ON DELETE CASCADE
);
