# EatLink 🍔

EatLink is a modern, fast, and fully responsive food e-commerce platform built with PHP and MySQL. It features a stunning glassmorphism UI design and supports a complete multi-role ecosystem, connecting customers, shop owners, and delivery personnel in one unified application.

## 🚀 Features

- **Dynamic UI:** Beautiful, modern glassmorphism design with micro-animations and toast notifications.
- **Multi-Role System:**
  - 👤 **Customer:** Browse menus, search foods, manage a shopping cart, maintain a wishlist, place orders, and view order history.
  - 🏪 **Shop Owner:** Dedicated dashboard to add/edit/delete food items, view incoming orders, update order statuses, and securely assign tasks to delivery personnel.
  - 🚚 **Delivery Man:** View assigned deliveries, call customers, and complete orders using a secure "Swipe-to-Deliver" and 4-digit PIN confirmation system.
- **Asynchronous Interactions:** Extensive use of Vanilla JavaScript and the Fetch API to allow for seamless cart updates, wishlist toggles, and order assignments without page reloads.

## 💻 Tech Stack

- **Frontend:** HTML5, CSS3 (Vanilla), JavaScript (Vanilla JS)
- **Backend:** PHP 8+
- **Database:** MySQL
- **Architecture:** Procedural PHP with modular API endpoints for AJAX requests.

---

## 🛠️ How to Configure the Database

Follow these steps to set up the MySQL database required to run the application locally:

1. **Start your Database Server:**
   Open your local development environment (like XAMPP, WAMP, or MAMP) and ensure the **MySQL** service is running.

2. **Access phpMyAdmin:**
   Go to `http://localhost/phpmyadmin` in your web browser.

3. **Create the Database:**
   - Click on **New** in the left sidebar.
   - Enter `eatlink_db` as the database name.
   - Click **Create**.

4. **Import the Schema and Data:**
   - Select the newly created `eatlink_db` database.
   - Click on the **Import** tab at the top.
   - Click **Choose File** and navigate to `db/schema.sql` within the project folder. Click **Import** at the bottom of the page.
   - *(Optional)* To populate the database with sample products and users, repeat the import process using the `db/seed.sql` file.

5. **Verify Connection Settings:**
   The database configuration is located in `db/connection.php`. By default, it expects the following:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_NAME', 'eatlink_db');
   define('DB_USER', 'root');
   define('DB_PASS', '');
   ```
   *If your local MySQL setup requires a password or uses a different port, update the values in `db/connection.php` accordingly.*

---

## 🏃 How to Run the Project

1. **Install a Local Web Server:**
   Ensure you have [XAMPP](https://www.apachefriends.org/index.html) (or WAMP/MAMP) installed on your machine.

2. **Clone / Move the Project:**
   Move the entire `ecommerce-website` project folder into your web server's root directory:
   - **XAMPP:** Place it in `C:\xampp\htdocs\`
   - **WAMP:** Place it in `C:\wamp\www\`
   - **MAMP:** Place it in `/Applications/MAMP/htdocs/`

3. **Start the Server:**
   Open your control panel (e.g., XAMPP Control Panel) and **Start** both the **Apache** and **MySQL** services.

4. **Access the Application:**
   Open your web browser and navigate to the project URL. For example:
   ```
   http://localhost/ecommerce-website/index.php
   ```

5. **Test Accounts (If using demo data):**
   If you imported the `seed.sql` file (or are relying on the fallback demo login), you can use the following accounts:
   - **Customer:** `kumara@example.com` / `password123`
   - **Shop Owner:** `suresh@pizzahut.lk` / `password123`
   - **Delivery Man:** `amal@delivery.lk` / `password123`

---

*Designed and developed to provide a seamless, premium food ordering experience.*
