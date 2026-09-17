-- ============================================================
-- AI-Based Direct Supply Chain and Disease Diagnostic System
-- for Mushroom Cultivation in Sri Lanka
-- Database schema for XAMPP (MySQL / MariaDB)
-- ============================================================

CREATE DATABASE IF NOT EXISTS mushroom_system CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE mushroom_system;

-- ------------------------------------------------------------
-- Users: farmers, buyers (hotels/restaurants), administrators
-- Covers FR-AUTH.1 - FR-AUTH.6
-- ------------------------------------------------------------
CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  role ENUM('farmer','buyer','admin') NOT NULL,
  full_name VARCHAR(150) NOT NULL,
  email VARCHAR(150) NOT NULL UNIQUE,
  phone VARCHAR(20),
  password_hash VARCHAR(255) NOT NULL,
  business_name VARCHAR(150) NULL COMMENT 'Farm name (farmer) or Hotel/Restaurant name (buyer)',
  address VARCHAR(255),
  profile_image VARCHAR(255),
  is_verified TINYINT(1) NOT NULL DEFAULT 0,
  status ENUM('active','suspended') NOT NULL DEFAULT 'active',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Marketplace listings created by farmers
-- Covers FR-MKT.1 - FR-MKT.3
-- ------------------------------------------------------------
CREATE TABLE listings (
  id INT AUTO_INCREMENT PRIMARY KEY,
  farmer_id INT NOT NULL,
  mushroom_type VARCHAR(100) NOT NULL,
  description TEXT,
  quantity_kg DECIMAL(10,2) NOT NULL,
  price_per_kg DECIMAL(10,2) NOT NULL,
  harvest_date DATE NULL,
  image VARCHAR(255),
  status ENUM('active','sold_out','removed') NOT NULL DEFAULT 'active',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (farmer_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Orders placed by buyers against a listing
-- Covers FR-MKT.4 - FR-MKT.6
-- ------------------------------------------------------------
CREATE TABLE orders (
  id INT AUTO_INCREMENT PRIMARY KEY,
  listing_id INT NOT NULL,
  buyer_id INT NOT NULL,
  farmer_id INT NOT NULL,
  quantity_kg DECIMAL(10,2) NOT NULL,
  total_price DECIMAL(10,2) NOT NULL,
  delivery_date DATE NULL,
  status ENUM('pending','confirmed','paid','completed','cancelled') NOT NULL DEFAULT 'pending',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (listing_id) REFERENCES listings(id),
  FOREIGN KEY (buyer_id) REFERENCES users(id),
  FOREIGN KEY (farmer_id) REFERENCES users(id)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Payments (secure digital payment gateway - simulated gateway,
-- structured so a real provider such as PayHere can be plugged
-- in without changing the rest of the schema)
-- Covers FR-PAY.1 - FR-PAY.5
-- ------------------------------------------------------------
CREATE TABLE payments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  order_id INT NOT NULL,
  amount DECIMAL(10,2) NOT NULL,
  method VARCHAR(50) NOT NULL DEFAULT 'card',
  gateway_reference VARCHAR(100),
  status ENUM('success','failed','pending') NOT NULL DEFAULT 'pending',
  paid_at TIMESTAMP NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Disease catalogue + treatment / pesticide recommendations
-- Covers FR-REC.1 - FR-REC.3
-- ------------------------------------------------------------
CREATE TABLE disease_types (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  description TEXT,
  treatment TEXT,
  pesticide_recommendation TEXT
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- AI "Snap & Detect" diagnosis history
-- Covers FR-AI.1 - FR-AI.6
-- ------------------------------------------------------------
CREATE TABLE diagnoses (
  id INT AUTO_INCREMENT PRIMARY KEY,
  farmer_id INT NOT NULL,
  image VARCHAR(255) NOT NULL,
  predicted_disease_id INT NULL,
  confidence DECIMAL(5,2) NOT NULL,
  low_confidence_flag TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (farmer_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (predicted_disease_id) REFERENCES disease_types(id)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Knowledge Hub: categorised expert video tutorials
-- Covers FR-EDU.1 - FR-EDU.4
-- ------------------------------------------------------------
CREATE TABLE tutorial_categories (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL
) ENGINE=InnoDB;

CREATE TABLE tutorials (
  id INT AUTO_INCREMENT PRIMARY KEY,
  category_id INT NULL,
  title VARCHAR(200) NOT NULL,
  description TEXT,
  video_url VARCHAR(255) NOT NULL,
  thumbnail VARCHAR(255),
  related_disease_id INT NULL COMMENT 'Used for FR-EDU.4 related-tutorial suggestions',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (category_id) REFERENCES tutorial_categories(id),
  FOREIGN KEY (related_disease_id) REFERENCES disease_types(id)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Buyer ratings/reviews of farmers after a completed order
-- Covers FR-MKT.7
-- ------------------------------------------------------------
CREATE TABLE reviews (
  id INT AUTO_INCREMENT PRIMARY KEY,
  order_id INT NOT NULL,
  buyer_id INT NOT NULL,
  farmer_id INT NOT NULL,
  rating TINYINT NOT NULL,
  comment TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
  FOREIGN KEY (buyer_id) REFERENCES users(id),
  FOREIGN KEY (farmer_id) REFERENCES users(id)
) ENGINE=InnoDB;

-- ============================================================
-- Seed data
-- ============================================================

-- Default administrator account (email: admin@mushroom.lk / password: Admin@123)
INSERT INTO users (role, full_name, email, phone, password_hash, is_verified, status)
VALUES ('admin', 'System Administrator', 'admin@mushroom.lk', '0770000000',
'$2y$12$NOeTd0910OqmU8U4Bncq5uKGA7ZUDMXBxTzln9MfVRTyZcHM1/4Ai', 1, 'active');
-- NOTE: the hash above is bcrypt for 'Admin@123'. Change this password after first login.

-- Disease catalogue used by the AI classifier and treatment engine
INSERT INTO disease_types (name, description, treatment, pesticide_recommendation) VALUES
('Healthy', 'No visible signs of disease or pest damage on the mushroom or growing bed.',
 'No treatment required. Continue standard hygiene and monitoring practices.',
 'Not applicable.'),
('Green Mold', 'A fast-spreading fungal contamination (Trichoderma spp.) that appears as green-tinted patches on the substrate or fruiting body.',
 'Isolate and remove the affected substrate immediately. Improve ventilation and reduce humidity. Sterilize tools and growing room surfaces between batches.',
 'Apply a registered fungicide such as a copper-based or carbendazim-based product, following local agricultural authority dosage guidelines.'),
('Bacterial Blotch', 'A bacterial infection (Pseudomonas tolaasii) causing brown/yellow blotches on the mushroom cap, usually linked to excess surface moisture.',
 'Reduce surface moisture, improve air circulation, and avoid overhead watering. Remove and discard affected mushrooms promptly.',
 'Apply a chlorine-based surface sanitizer (e.g., diluted sodium hypochlorite spray) on growing room surfaces between flushes.'),
('Pest Attack', 'Damage caused by pests such as sciarid flies or mites, visible as holes, larvae, or webbing on the mushroom or substrate.',
 'Introduce sticky traps and improve screening of vents/doors. Remove infested substrate and maintain strict hygiene.',
 'Apply a mushroom-safe insecticide (e.g., pyrethrin-based) around entry points, avoiding direct application on fruiting bodies.');

-- Knowledge Hub categories
INSERT INTO tutorial_categories (name) VALUES
('House Preparation'), ('Spawning / Seeding'), ('Disease Prevention'), ('Harvesting'), ('Post-Harvest Handling');

-- Sample tutorials (video_url can point to any hosted video, e.g. YouTube embed link)
INSERT INTO tutorials (category_id, title, description, video_url, related_disease_id) VALUES
(1, 'Preparing a Mushroom House from Scratch', 'Step-by-step guide to setting up a hygienic, climate-controlled mushroom growing house.', 'https://www.youtube.com/embed/dQw4w9WgXcQ', NULL),
(2, 'Spawning Oyster Mushroom Substrate', 'How to correctly inoculate substrate bags with mushroom spawn for high yield.', 'https://www.youtube.com/embed/dQw4w9WgXcQ', NULL),
(3, 'Preventing Green Mold Contamination', 'Practical hygiene steps to prevent Trichoderma (green mold) outbreaks in your growing room.', 'https://www.youtube.com/embed/dQw4w9WgXcQ', 2),
(3, 'Controlling Bacterial Blotch', 'Managing humidity and airflow to prevent bacterial blotch on mushroom caps.', 'https://www.youtube.com/embed/dQw4w9WgXcQ', 3),
(4, 'Harvesting Mushrooms at the Right Time', 'How to identify the optimal harvest window for maximum quality and shelf life.', 'https://www.youtube.com/embed/dQw4w9WgXcQ', NULL),
(5, 'Post-Harvest Storage and Packaging', 'Best practices for storing and packaging mushrooms before sale to maintain freshness.', 'https://www.youtube.com/embed/dQw4w9WgXcQ', NULL);
