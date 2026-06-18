-- ============================================================
--  EatLink Seed Data  (run AFTER schema.sql)
--  Passwords are bcrypt of "password123"
-- ============================================================
USE eatlink_db;

-- ── CATEGORIES ────────────────────────────────────────────
INSERT INTO categories (name, icon) VALUES
('Burgers','🍔'),('Pizza','🍕'),('Chicken','🍗'),
('Sandwiches','🥪'),('Desserts','🍰'),('Drinks','🥤'),
('Sides','🍟'),('Salads','🥗');

-- ── BRANDS ────────────────────────────────────────────────
INSERT INTO brands (name, icon, color) VALUES
('Pizza Hut','🍕','#E8001C'),('McDonald\'s','M','#FFC72C'),
('KFC','🍗','#E4002B'),('Burger King','🍔','#FF8800'),
('Subway','🥪','#009639'),('Domino\'s','🍕','#006491');

-- ── USERS (password = "password123") ─────────────────────
INSERT INTO users (name, email, password, phone, role, address) VALUES
-- Customers
('Kumara Chathuranga','kumara@example.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','0771234567','customer','123 Main St, Colombo 03'),
('Nimal Perera','nimal@example.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','0779876543','customer','45 Flower Rd, Colombo 07'),
-- Shop Owners
('Suresh Pizza Hut','suresh@pizzahut.lk','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','0112345678','shop_owner','Pizza Hut, Majestic City, Colombo'),
('Kamal Burger King','kamal@burgerking.lk','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','0114567890','shop_owner','Burger King, Liberty Plaza, Colombo'),
-- Delivery Men
('Amal Delivery','amal@delivery.lk','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','0756781234','delivery_man','Colombo 05'),
('Saman Rider','saman@delivery.lk','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','0751239876','delivery_man','Colombo 06');

-- ── SHOPS ─────────────────────────────────────────────────
INSERT INTO shops (owner_id, name, description, address, phone) VALUES
(3,'Pizza Hut Glasgow','Best pizza in town, fresh daily','Majestic City, Colombo 03','0112345678'),
(4,'Burger King Liberty','Flame-grilled burgers since 1953','Liberty Plaza, Colombo 03','0114567890');

-- ── PRODUCTS ──────────────────────────────────────────────
INSERT INTO products (shop_id, category_id, brand_id, name, description, price, original_price, discount_percent, is_new, delivery_type, image) VALUES
(1,2,1,'Pepperoni Supreme Pizza','Our signature pepperoni pizza with fresh mozzarella, premium pepperoni, and house-made tomato sauce on a hand-tossed crust. A timeless classic that never disappoints.',4200,4667,10,0,'free','pizza'),
(1,2,1,'Margherita Classic','Simple yet perfect - San Marzano tomatoes, fresh buffalo mozzarella, and fragrant basil on a thin-crust base. The pizza that started it all.',3800,4471,15,0,'paid','pizza'),
(1,2,1,'BBQ Chicken Pizza','Smoky BBQ sauce, tender grilled chicken, caramelised onions, and melted cheddar on a golden crust. Bold flavors in every bite.',4500,0,0,0,'free','pizza'),
(1,2,1,'Cheese Burst Delight','Triple cheese blend bursts through the crust with every bite. Loaded with mozzarella, cheddar, and parmesan plus your choice of toppings.',4900,0,0,1,'free','pizza'),
(2,1,4,'Juicy Beef Patties','Bring the steakhouse experience home with our ultra-juicy beef patties. Crafted from premium, 100% all-natural beef with the perfect fat-to-lean ratio, these patties are engineered to stay incredibly tender and burst with savory flavor. A lifelong upgrade. Just sear, build your masterpiece, and bite into pure satisfaction.',3908,4342,10,0,'free','burger'),
(2,1,4,'Classic Double Burger','Double the pleasure with two flame-grilled beef patties, American cheese, fresh lettuce, tomato, pickles, and our signature sauce. A legend in every bite.',3880,4312,10,0,'paid','burger'),
(2,1,4,'Crispy Chicken Stack','Three layers of crispy buttermilk fried chicken, coleslaw, and spicy mayo stacked high on a brioche bun. Crunchy on the outside, juicy on the inside.',3100,0,0,0,'free','burger'),
(2,1,4,'Veggie Royale Burger','A hearty plant-based patty with fresh avocado, roasted peppers, baby spinach, and chipotle aioli. 100% delicious and 0% compromise.',2800,0,0,1,'free','burger');

-- ── PRODUCT IMAGES ────────────────────────────────────────
INSERT INTO product_images (product_id, image_path, is_primary, sort_order) VALUES
(5,'images/burger.png',1,0),(5,'images/burger.png',0,1),
(5,'images/burger.png',0,2),(5,'images/pizza.png',0,3),
(1,'images/pizza.png',1,0),(1,'images/pizza.png',0,1),
(1,'images/burger.png',0,2);

-- ── DEMO REVIEWS ──────────────────────────────────────────
INSERT INTO reviews (user_id, product_id, rating, comment) VALUES
(1,5,4,'Bring the steakhouse experience home with our ultra-juicy beef patties. Crafted from premium, 100% all-natural beef with the perfect fat-to-lean ratio, these patties are amazing.'),
(2,5,4,'Bring the steakhouse experience home with our ultra-juicy beef patties. Crafted from premium, 100% all-natural beef with the perfect fat-to-lean ratio, these patties are great.'),
(1,1,5,'Absolutely delicious pizza! The crust was perfectly crispy and the toppings were fresh. Will definitely order again.'),
(2,1,4,'Great pizza, fast delivery. Only giving 4 stars because I wish the portion was bigger.');
