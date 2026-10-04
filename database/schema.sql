-- ============================================================
-- AI-Based Direct Supply Chain and Disease Diagnostic System
-- for Mushroom Cultivation in Sri Lanka
-- Database schema for XAMPP (MySQL / MariaDB)
-- ============================================================

-- NOTE: importing this file drops and recreates the mushroom_system database
-- (fresh install). Back up any existing data first.
SET NAMES utf8mb4;
DROP DATABASE IF EXISTS mushroom_system;
CREATE DATABASE mushroom_system CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE mushroom_system;

-- ------------------------------------------------------------
-- Users: farmers, buyers (hotels/restaurants), administrators
-- Covers FR-AUTH.1 - FR-AUTH.6
-- ------------------------------------------------------------
CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  role ENUM('farmer','buyer','admin','expert') NOT NULL,
  full_name VARCHAR(150) NOT NULL,
  email VARCHAR(150) NOT NULL UNIQUE,
  phone VARCHAR(20),
  password_hash VARCHAR(255) NOT NULL,
  business_name VARCHAR(150) NULL COMMENT 'Farm name (farmer) or Hotel/Restaurant name (buyer)',
  address VARCHAR(255),
  profile_image VARCHAR(255),
  is_verified TINYINT(1) NOT NULL DEFAULT 0,
  otp_code VARCHAR(255) NULL COMMENT 'Hashed one-time verification code (FR-AUTH.2)',
  otp_expires_at DATETIME NULL,
  reset_token VARCHAR(255) NULL COMMENT 'Hashed password-reset token (FR-AUTH.4)',
  reset_expires_at DATETIME NULL,
  status ENUM('active','suspended') NOT NULL DEFAULT 'active',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Marketplace listings created by farmers
-- Covers FR-MKT.1 - FR-MKT.3
-- ------------------------------------------------------------
-- ------------------------------------------------------------
-- Catalogue of mushroom varieties shown on the "Our Mushrooms" pages,
-- with reference price per kg and Sinhala / Tamil translations
-- ------------------------------------------------------------
CREATE TABLE mushroom_types (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  name_si VARCHAR(150) NULL,
  name_ta VARCHAR(150) NULL,
  scientific_name VARCHAR(100) NULL,
  description TEXT,
  description_si TEXT,
  description_ta TEXT,
  growing_info TEXT,
  growing_info_si TEXT,
  growing_info_ta TEXT,
  uses TEXT,
  uses_si TEXT,
  uses_ta TEXT,
  price_per_kg DECIMAL(10,2) NOT NULL,
  color VARCHAR(7) NOT NULL DEFAULT '#c9b79c' COMMENT 'Cap colour for the built-in illustration',
  image VARCHAR(255) NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  sort_order INT NOT NULL DEFAULT 0
) ENGINE=InnoDB;

CREATE TABLE listings (
  id INT AUTO_INCREMENT PRIMARY KEY,
  farmer_id INT NOT NULL,
  mushroom_type_id INT NULL,
  mushroom_type VARCHAR(100) NOT NULL,
  description TEXT,
  quantity_kg DECIMAL(10,2) NOT NULL,
  price_per_kg DECIMAL(10,2) NOT NULL,
  harvest_date DATE NULL,
  location VARCHAR(100) NULL COMMENT 'District / town of the farm (FR-MKT.3 filter)',
  image VARCHAR(255),
  status ENUM('active','sold_out','removed') NOT NULL DEFAULT 'active',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (farmer_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (mushroom_type_id) REFERENCES mushroom_types(id) ON DELETE SET NULL
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
  failure_reason VARCHAR(255) NULL,
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
  name VARCHAR(100) NOT NULL COMMENT 'English name; must match the CNN class label',
  name_si VARCHAR(150) NULL,
  name_ta VARCHAR(150) NULL,
  description TEXT,
  description_si TEXT,
  description_ta TEXT,
  treatment TEXT,
  treatment_si TEXT,
  treatment_ta TEXT,
  pesticide_recommendation TEXT,
  pesticide_recommendation_si TEXT,
  pesticide_recommendation_ta TEXT,
  prevention TEXT,
  prevention_si TEXT,
  prevention_ta TEXT
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
  model_version VARCHAR(50) NULL COMMENT 'Which classifier produced the result',
  reviewed_disease_id INT NULL COMMENT 'Expert-confirmed label (useful for CNN retraining)',
  expert_note TEXT NULL,
  reviewed_by INT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (farmer_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (predicted_disease_id) REFERENCES disease_types(id),
  FOREIGN KEY (reviewed_disease_id) REFERENCES disease_types(id),
  FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Knowledge Hub: categorised expert video tutorials
-- Covers FR-EDU.1 - FR-EDU.4
-- ------------------------------------------------------------
CREATE TABLE tutorial_categories (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  name_si VARCHAR(150) NULL,
  name_ta VARCHAR(150) NULL
) ENGINE=InnoDB;

CREATE TABLE tutorials (
  id INT AUTO_INCREMENT PRIMARY KEY,
  category_id INT NULL,
  title VARCHAR(200) NOT NULL,
  title_si VARCHAR(255) NULL,
  title_ta VARCHAR(255) NULL,
  description TEXT,
  description_si TEXT,
  description_ta TEXT,
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

-- ------------------------------------------------------------
-- In-app notifications
-- Covers FR-MKT.5, FR-NOT.1 - FR-NOT.3
-- ------------------------------------------------------------
CREATE TABLE notifications (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  message VARCHAR(255) NOT NULL,
  link VARCHAR(255) NULL,
  is_read TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Order / payment disputes between farmer and buyer
-- Covers FR-ADM.4
-- ------------------------------------------------------------
CREATE TABLE disputes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  order_id INT NOT NULL,
  raised_by INT NOT NULL,
  reason TEXT NOT NULL,
  status ENUM('open','resolved','rejected') NOT NULL DEFAULT 'open',
  admin_response TEXT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  resolved_at TIMESTAMP NULL,
  FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
  FOREIGN KEY (raised_by) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Audit log of administrative actions
-- Covers NFR-SEC.5
-- ------------------------------------------------------------
CREATE TABLE admin_logs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  admin_id INT NOT NULL,
  action VARCHAR(255) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- Seed data
-- ============================================================

-- Default administrator account (email: admin@mushroom.lk / password: Admin@123)
INSERT INTO users (role, full_name, email, phone, password_hash, is_verified, status)
VALUES ('admin', 'System Administrator', 'admin@mushroom.lk', '0770000000',
'$2y$12$NOeTd0910OqmU8U4Bncq5uKGA7ZUDMXBxTzln9MfVRTyZcHM1/4Ai', 1, 'active');
-- NOTE: the hash above is bcrypt for 'Admin@123'. Change this password after first login.

-- Demo accounts for testing / viva demonstration
--   farmer@mushroom.lk / Farmer@123
--   buyer@mushroom.lk  / Buyer@123
--   expert@mushroom.lk / Expert@123  (Agricultural Expert / Content Contributor)
INSERT INTO users (role, full_name, email, phone, password_hash, business_name, address, is_verified, status) VALUES
('farmer', 'Sunil Perera', 'farmer@mushroom.lk', '0771234567',
 '$2y$10$3VNXD.zfOjOgoeT4VRt.G.UWGnQ/en0Im340VRsLy0FbxsEmY60Ay', 'Perera Mushroom Farm', 'Gampaha', 1, 'active'),
('buyer', 'Nimali Fernando', 'buyer@mushroom.lk', '0712345678',
 '$2y$10$Luu5dRmLzkSeHm0/QROdGOhcZHlOYksvDcJMuApx6c5tyvzoKayWm', 'Ocean View Hotel', 'Colombo 03', 1, 'active'),
('expert', 'Dr. Kamal Silva', 'expert@mushroom.lk', '0759876543',
 '$2y$10$ygd5e3VkspGEpVzYGZKiHeyiA8nQUayTgK3uvA/Lwi.0lB8bMIbye', 'Department of Agriculture', 'Peradeniya', 1, 'active');

-- Mushroom varieties catalogue (reference prices per kg - update via Admin > Mushroom Types)
INSERT INTO mushroom_types (name, name_si, name_ta, scientific_name, description, description_si, description_ta, growing_info, growing_info_si, growing_info_ta, uses, uses_si, uses_ta, price_per_kg, color, sort_order) VALUES
('Oyster Mushroom', 'ඔයිස්ටර් හතු', 'சிப்பி காளான்', 'Pleurotus ostreatus',
 'The most widely grown mushroom in Sri Lanka. Soft, fan-shaped white to grey caps with a mild taste.',
 'ශ්‍රී ලංකාවේ වැඩිපුරම වගා කරන හතු වර්ගයයි. මෘදු, විජිනි හැඩැති සුදු සිට අළු පැහැති හිස් සහ මෘදු රසයක් ඇත.',
 'இலங்கையில் அதிகம் வளர்க்கப்படும் காளான். மென்மையான, விசிறி வடிவ வெள்ளை முதல் சாம்பல் நிறத் தொப்பிகளுடன் மிதமான சுவை கொண்டது.',
 'Grown on sawdust bags; first harvest about 35-45 days after spawning. Suitable for most parts of Sri Lanka.',
 'ලී කුඩු පැකට් වල වගා කරයි; බීජ දැමීමෙන් දින 35-45 කින් පමණ පළමු අස්වැන්න ලැබේ. ශ්‍රී ලංකාවේ බොහෝ ප්‍රදේශවලට සුදුසුය.',
 'மரத்தூள் பைகளில் வளர்க்கப்படுகிறது; வித்திட்டு 35-45 நாட்களில் முதல் அறுவடை. இலங்கையின் பெரும்பாலான பகுதிகளுக்கு ஏற்றது.',
 'Curries, devilled dishes, soups, fried rice and kottu.',
 'කරි, ඩෙවල්, සුප්, ෆ්‍රයිඩ් රයිස් සහ කොත්තු.',
 'கறி, டெவல், சூப், ஃப்ரைட் ரைஸ் மற்றும் கொத்து.',
 650.00, '#d9d4c7', 1),
('Abalone Mushroom', 'අබලෝනි හතු', 'அபலோன் காளான்', 'Pleurotus cystidiosus',
 'Thick, firm brownish caps with a meaty texture; very popular with hotels.',
 'ඝන, තද දුඹුරු පැහැති හිස් සහ මස් වැනි වයනයක් ඇත; හෝටල් අතර ඉතා ජනප්‍රියයි.',
 'தடிமனான, உறுதியான பழுப்பு நிறத் தொப்பிகளும் இறைச்சி போன்ற அமைப்பும் கொண்டது; ஹோட்டல்களில் மிகவும் பிரபலமானது.',
 'Prefers warm temperatures (25-30°C); grown on sawdust bags.',
 'උණුසුම් උෂ්ණත්වයට (25-30°C) කැමතියි; ලී කුඩු පැකට් වල වගා කරයි.',
 'வெப்பமான வெப்பநிலையை (25-30°C) விரும்புகிறது; மரத்தூள் பைகளில் வளர்க்கப்படுகிறது.',
 'Stir-fries, grilled dishes and hotel menus as a meat substitute.',
 'බැදුම්, ග්‍රිල් කළ කෑම සහ මස් වෙනුවට හෝටල් මෙනු වල.',
 'வறுவல், கிரில் உணவுகள், இறைச்சிக்கு மாற்றாக ஹோட்டல் உணவுகளில்.',
 900.00, '#9c7a5b', 2),
('Button Mushroom', 'බොත්තම් හතු', 'மொட்டு காளான்', 'Agaricus bisporus',
 'The classic white, round mushroom used worldwide; high demand from hotels and restaurants.',
 'ලොව පුරා භාවිතා වන සුදු, රවුම් හතු වර්ගයයි; හෝටල් සහ අවන්හල් වලින් ඉහළ ඉල්ලුමක් ඇත.',
 'உலகம் முழுவதும் பயன்படுத்தப்படும் வெள்ளை வட்டக் காளான்; ஹோட்டல்கள் மற்றும் உணவகங்களில் அதிக தேவை உள்ளது.',
 'Needs cool conditions (16-22°C); mainly grown in the hill country (e.g. Nuwara Eliya) on compost.',
 'සීතල දේශගුණයක් (16-22°C) අවශ්‍යයි; ප්‍රධාන වශයෙන් කඳුකරයේ (උදා: නුවරඑළිය) කොම්පෝස්ට් මත වගා කරයි.',
 'குளிர்ந்த சூழல் (16-22°C) தேவை; முக்கியமாக மலைநாட்டில் (எ.கா. நுவரெலியா) உரத்தில் வளர்க்கப்படுகிறது.',
 'Salads, pizza, pasta, soups and sauces.',
 'සලාද, පීසා, පැස්ටා, සුප් සහ සෝස්.',
 'சாலட், பீட்சா, பாஸ்தா, சூப் மற்றும் சாஸ்.',
 1400.00, '#f3efe6', 3),
('Milky Mushroom', 'කිරි හතු', 'பால் காளான்', 'Calocybe indica',
 'Large, firm, milky-white mushroom with a long shelf life; very well suited to hot climates.',
 'විශාල, තද, කිරි සුදු පැහැති හතු; වැඩි කාලයක් නරක් නොවී තබාගත හැකි අතර උණුසුම් දේශගුණයට ඉතා සුදුසුය.',
 'பெரிய, உறுதியான, பால் வெள்ளை நிறக் காளான்; நீண்ட நாட்கள் கெடாமல் இருக்கும், வெப்பமான காலநிலைக்கு மிகவும் ஏற்றது.',
 'Grows well at 30-35°C on paddy straw; a soil casing layer is required.',
 'පිදුරු මත 30-35°C උෂ්ණත්වයේ හොඳින් වැවේ; පස් ආවරණයක් (casing) අවශ්‍යයි.',
 'வைக்கோலில் 30-35°C வெப்பநிலையில் நன்றாக வளரும்; மண் மூடுதல் (casing) தேவை.',
 'Curries, soups and pickles.',
 'කරි, සුප් සහ අච්චාරු.',
 'கறி, சூப் மற்றும் ஊறுகாய்.',
 1000.00, '#fbf8f0', 4),
('Paddy Straw Mushroom', 'පිදුරු හතු', 'வைக்கோல் காளான்', 'Volvariella volvacea',
 'Small, egg-shaped mushroom with a delicate flavour, grown on paddy straw.',
 'සියුම් රසයක් ඇති, බිත්තර හැඩැති කුඩා හතු වර්ගයකි; වී පිදුරු මත වගා කරයි.',
 'மென்மையான சுவையுடைய, முட்டை வடிவச் சிறிய காளான்; நெல் வைக்கோலில் வளர்க்கப்படுகிறது.',
 'A fast crop (10-15 days) in hot, humid conditions using paddy straw beds.',
 'උණුසුම්, තෙත් තත්ත්වයන් යටතේ වී පිදුරු ඇඳන් මත ඉක්මන් අස්වැන්නක් (දින 10-15) ලැබේ.',
 'வெப்பமான, ஈரப்பதமான சூழலில் வைக்கோல் படுக்கைகளில் விரைவான அறுவடை (10-15 நாட்கள்).',
 'Asian-style soups, stir-fries and curries.',
 'ආසියානු ක්‍රමයේ සුප්, බැදුම් සහ කරි.',
 'ஆசிய பாணி சூப், வறுவல் மற்றும் கறி.',
 900.00, '#6f6458', 5),
('Pink Oyster Mushroom', 'රෝස ඔයිස්ටර් හතු', 'இளஞ்சிவப்பு சிப்பி காளான்', 'Pleurotus djamor',
 'A bright pink oyster mushroom with an attractive colour; a premium choice for restaurants.',
 'ආකර්ෂණීය දීප්තිමත් රෝස පැහැති ඔයිස්ටර් හතු; අවන්හල් සඳහා ඉහළ මට්ටමේ තේරීමකි.',
 'கவர்ச்சிகரமான இளஞ்சிவப்பு நிற சிப்பி காளான்; உணவகங்களுக்கு உயர்தரத் தேர்வு.',
 'Grows quickly in warm temperatures on sawdust bags; best sold fresh.',
 'උණුසුම් උෂ්ණත්වයේ ලී කුඩු පැකට් වල ඉක්මනින් වැවේ; නැවුම්ව විකිණීම වඩාත් සුදුසුය.',
 'வெப்பமான சூழலில் மரத்தூள் பைகளில் விரைவாக வளரும்; புதிதாக விற்பனை செய்வது சிறந்தது.',
 'Garnishes, stir-fries and fried dishes.',
 'කෑම අලංකරණය, බැදුම් සහ ෆ්‍රයි කළ කෑම.',
 'உணவு அலங்காரம், வறுவல் மற்றும் பொரித்த உணவுகள்.',
 800.00, '#e79ab0', 6),
('Shiitake Mushroom', 'ෂිටාකේ හතු', 'ஷிடேக் காளான்', 'Lentinula edodes',
 'A brown, umbrella-shaped mushroom with a rich, savoury flavour and medicinal value.',
 'පොහොසත් රසයක් සහ ඖෂධීය වටිනාකමක් ඇති, කුඩ හැඩැති දුඹුරු හතු වර්ගයකි.',
 'செழுமையான சுவையும் மருத்துவ மதிப்பும் கொண்ட, குடை வடிவ பழுப்பு நிறக் காளான்.',
 'Grown on hardwood sawdust blocks in cool conditions; takes longer to grow (2-3 months).',
 'සීතල තත්ත්වයන් යටතේ තද ලී කුඩු කුට්ටි මත වගා කරයි; වැඩි කාලයක් (මාස 2-3) ගතවේ.',
 'குளிர்ந்த சூழலில் கடின மரத்தூள் கட்டிகளில் வளர்க்கப்படுகிறது; வளர நீண்ட காலம் (2-3 மாதங்கள்) எடுக்கும்.',
 'Soups, noodles, Asian cuisine and dried mushroom products.',
 'සුප්, නූඩ්ල්ස්, ආසියානු කෑම සහ වියළි හතු නිෂ්පාදන.',
 'சூப், நூடுல்ஸ், ஆசிய உணவுகள் மற்றும் உலர்ந்த காளான் பொருட்கள்.',
 2500.00, '#7a4a2a', 7);

-- Demo marketplace listings (farmer id = 2)
INSERT INTO listings (farmer_id, mushroom_type_id, mushroom_type, description, quantity_kg, price_per_kg, harvest_date, location) VALUES
(2, 1, 'Oyster Mushroom', 'Fresh American oyster mushrooms, grown on sawdust substrate without chemicals.', 120, 650, CURDATE(), 'Gampaha'),
(2, 2, 'Abalone Mushroom', 'Firm-textured abalone mushrooms, ideal for hotel kitchens.', 40, 900, CURDATE(), 'Gampaha'),
(2, 3, 'Button Mushroom', 'Clean white button mushrooms, graded and packed in 1kg trays.', 60, 1400, CURDATE(), 'Gampaha');

-- Disease catalogue used by the AI classifier and treatment engine
INSERT INTO disease_types (name, name_si, name_ta, description, description_si, description_ta, treatment, treatment_si, treatment_ta, pesticide_recommendation, pesticide_recommendation_si, pesticide_recommendation_ta, prevention, prevention_si, prevention_ta) VALUES
('Healthy', 'නිරෝගී', 'ஆரோக்கியமானது',
 'No visible signs of disease or pest damage on the mushroom or growing bed.',
 'හතු හෝ වගා ඇඳේ රෝග හෝ පළිබෝධ හානි ලක්ෂණ නොපෙනේ.',
 'காளான் அல்லது வளர்ப்புப் படுக்கையில் நோய் அல்லது பூச்சி சேதத்தின் அறிகுறிகள் இல்லை.',
 'No treatment required. Continue standard hygiene and monitoring practices.',
 'ප්‍රතිකාර අවශ්‍ය නොවේ. සාමාන්‍ය පිරිසිදුකම සහ නිරීක්ෂණය දිගටම කරගෙන යන්න.',
 'சிகிச்சை தேவையில்லை. வழக்கமான சுகாதாரம் மற்றும் கண்காணிப்பைத் தொடரவும்.',
 'Not applicable.', 'අදාළ නොවේ.', 'பொருந்தாது.',
 'Keep the growing room clean, maintain 80-90% humidity and 22-28°C, and inspect the beds daily.',
 'වගා කාමරය පිරිසිදුව තබා, 80-90% ආර්ද්‍රතාවයක් සහ 22-28°C උෂ්ණත්වයක් පවත්වා, දිනපතා ඇඳන් පරීක්ෂා කරන්න.',
 'வளர்ப்பு அறையைச் சுத்தமாக வைத்து, 80-90% ஈரப்பதம் மற்றும் 22-28°C வெப்பநிலையைப் பேணி, தினமும் படுக்கைகளைச் சோதிக்கவும்.'),
('Green Mold', 'කොළ පාට පුස්', 'பச்சை பூஞ்சை',
 'A fast-spreading fungal contamination (Trichoderma spp.) that appears as green-tinted patches on the substrate or fruiting body.',
 'ට්‍රයිකොඩර්මා (Trichoderma) දිලීරය නිසා ඇතිවන, ඉක්මනින් පැතිරෙන ආසාදනයකි. මාධ්‍යයේ හෝ හතු මත කොළ පැහැති පැල්ලම් ලෙස පෙනේ.',
 'டிரைக்கோடெர்மா (Trichoderma) பூஞ்சையால் ஏற்படும், வேகமாகப் பரவும் தொற்று. ஊடகம் அல்லது காளான் மீது பச்சை நிறத் திட்டுகளாகத் தோன்றும்.',
 'Isolate and remove the affected substrate immediately. Improve ventilation and reduce humidity. Sterilize tools and growing room surfaces between batches.',
 'ආසාදිත පැකට්/මාධ්‍ය වහාම ඉවත් කර වෙන් කරන්න. වාතාශ්‍රය වැඩි කර ආර්ද්‍රතාව අඩු කරන්න. කණ්ඩායම් අතර උපකරණ සහ වගා කාමරය විෂබීජහරණය කරන්න.',
 'பாதிக்கப்பட்ட பைகள்/ஊடகத்தை உடனடியாக அகற்றி தனிமைப்படுத்தவும். காற்றோட்டத்தை அதிகரித்து ஈரப்பதத்தைக் குறைக்கவும். கருவிகளையும் வளர்ப்பு அறையையும் கிருமி நீக்கம் செய்யவும்.',
 'Apply a registered fungicide such as a copper-based or carbendazim-based product, following local agricultural authority dosage guidelines.',
 'කෘෂිකර්ම දෙපාර්තමේන්තුවේ මාත්‍රා උපදෙස් අනුව තඹ මිශ්‍ර හෝ කාබෙන්ඩසිම් මිශ්‍ර ලියාපදිංචි දිලීර නාශකයක් භාවිතා කරන්න.',
 'விவசாயத் திணைக்களத்தின் அளவு வழிகாட்டலின்படி செம்பு அல்லது கார்பென்டசிம் அடிப்படையிலான பதிவுசெய்யப்பட்ட பூஞ்சைக்கொல்லியைப் பயன்படுத்தவும்.',
 'Pasteurise or sterilise the substrate properly, use clean spawn, wash hands and wear clean clothes before entering, and keep bags off the floor.',
 'මාධ්‍යය නිසි ලෙස පැස්ටරීකරණය/විෂබීජහරණය කරන්න, පිරිසිදු බීජ භාවිතා කරන්න, ඇතුළු වීමට පෙර අත් සෝදා පිරිසිදු ඇඳුම් අඳින්න, පැකට් බිම නොතබන්න.',
 'ஊடகத்தைச் சரியாகக் கிருமி நீக்கம் செய்யவும், சுத்தமான வித்துகளைப் பயன்படுத்தவும், உள்ளே செல்லும் முன் கைகளைக் கழுவி சுத்தமான உடை அணியவும், பைகளைத் தரையில் வைக்க வேண்டாம்.'),
('Bacterial Blotch', 'බැක්ටීරියා පැල්ලම් රෝගය', 'பாக்டீரியா கறை நோய்',
 'A bacterial infection (Pseudomonas tolaasii) causing brown/yellow blotches on the mushroom cap, usually linked to excess surface moisture.',
 'සූඩොමොනාස් ටොලාසි (Pseudomonas tolaasii) බැක්ටීරියාව නිසා හතු හිස මත දුඹුරු/කහ පැල්ලම් ඇතිවේ. සාමාන්‍යයෙන් මතුපිට තෙතමනය වැඩිවීම නිසා ඇතිවේ.',
 'சூடோமோனாஸ் டொலாசி (Pseudomonas tolaasii) பாக்டீரியாவால் காளான் தொப்பியில் பழுப்பு/மஞ்சள் கறைகள் ஏற்படும்; பொதுவாக மேற்பரப்பு ஈரப்பதம் அதிகமாக இருப்பதால் ஏற்படுகிறது.',
 'Reduce surface moisture, improve air circulation, and avoid overhead watering. Remove and discard affected mushrooms promptly.',
 'මතුපිට තෙතමනය අඩු කරන්න, වාතය ගලායාම වැඩි කරන්න, ඉහළින් වතුර දැමීමෙන් වළකින්න. ආසාදිත හතු ඉක්මනින් ඉවත් කර විනාශ කරන්න.',
 'மேற்பரப்பு ஈரப்பதத்தைக் குறைத்து காற்றோட்டத்தை மேம்படுத்தவும்; மேலிருந்து நீர் ஊற்றுவதைத் தவிர்க்கவும். பாதிக்கப்பட்ட காளான்களை உடனே அகற்றவும்.',
 'Apply a chlorine-based surface sanitizer (e.g., diluted sodium hypochlorite spray) on growing room surfaces between flushes.',
 'අස්වනු අතර වගා කාමරයේ මතුපිටට තනුක කළ සෝඩියම් හයිපොක්ලෝරයිට් (ක්ලෝරීන්) ද්‍රාවණයක් ඉසින්න.',
 'அறுவடைகளுக்கு இடையில் வளர்ப்பு அறை மேற்பரப்புகளில் நீர்த்த சோடியம் ஹைபோகுளோரைட் (குளோரின்) கரைசலைத் தெளிக்கவும்.',
 'Avoid water droplets on the caps, keep humidity steady (not above 90%), ensure good air circulation and keep watering equipment clean.',
 'හතු හිස මත වතුර බිඳු රැඳීමෙන් වළකින්න, ආර්ද්‍රතාව ස්ථාවරව (90% ට අඩුවෙන්) තබන්න, හොඳ වාතාශ්‍රයක් පවත්වන්න, වතුර දමන උපකරණ පිරිසිදුව තබන්න.',
 'தொப்பிகளில் நீர்த்துளிகள் தங்குவதைத் தவிர்க்கவும், ஈரப்பதத்தை நிலையாக (90%க்குக் குறைவாக) வைத்திருக்கவும், நல்ல காற்றோட்டத்தை உறுதிசெய்யவும், நீர் ஊற்றும் கருவிகளைச் சுத்தமாக வைக்கவும்.'),
('Pest Attack', 'පළිබෝධ හානිය', 'பூச்சித் தாக்குதல்',
 'Damage caused by pests such as sciarid flies or mites, visible as holes, larvae, or webbing on the mushroom or substrate.',
 'ස්කියාරිඩ් මැස්සන් හෝ මයිටාවන් වැනි පළිබෝධකයන් නිසා හතු හෝ මාධ්‍යයේ සිදුරු, කීටයන් හෝ දැල් ලෙස හානි පෙනේ.',
 'சியாரிட் ஈக்கள் அல்லது சிலந்திப் பூச்சிகள் போன்றவற்றால் காளான் அல்லது ஊடகத்தில் துளைகள், புழுக்கள் அல்லது வலைகள் காணப்படும்.',
 'Introduce sticky traps and improve screening of vents/doors. Remove infested substrate and maintain strict hygiene.',
 'ඇලෙන උගුල් (sticky traps) යොදන්න, වාතාශ්‍ර කවුළු සහ දොරවල් දැලකින් ආවරණය කරන්න. ආසාදිත මාධ්‍ය ඉවත් කර දැඩි පිරිසිදුකම පවත්වන්න.',
 'ஒட்டும் பொறிகளை வைத்து, காற்றோட்டத் துவாரங்கள் மற்றும் கதவுகளுக்கு வலை அமைக்கவும். பாதிக்கப்பட்ட ஊடகத்தை அகற்றி கடுமையான சுகாதாரத்தைப் பேணவும்.',
 'Apply a mushroom-safe insecticide (e.g., pyrethrin-based) around entry points, avoiding direct application on fruiting bodies.',
 'හතු සඳහා ආරක්ෂිත (පයිරත්‍රින් මිශ්‍ර) කෘමිනාශකයක් ඇතුල්වීමේ ස්ථාන අවට පමණක් යොදන්න; හතු මතට කෙලින්ම ඉසීමෙන් වළකින්න.',
 'காளானுக்குப் பாதுகாப்பான (பைரெத்ரின் அடிப்படையிலான) பூச்சிக்கொல்லியை நுழைவு இடங்களைச் சுற்றி மட்டும் பயன்படுத்தவும்; காளான் மீது நேரடியாகத் தெளிக்க வேண்டாம்.',
 'Fit fine insect mesh on vents and doors, hang yellow sticky traps, remove spent substrate quickly and keep the surroundings clean.',
 'වාතාශ්‍ර කවුළු සහ දොරවලට සියුම් කෘමි දැල් සවි කරන්න, කහ පාට ඇලෙන උගුල් එල්ලන්න, භාවිතා කළ මාධ්‍ය ඉක්මනින් ඉවත් කර අවට පරිසරය පිරිසිදුව තබන්න.',
 'காற்றோட்டத் துவாரங்கள் மற்றும் கதவுகளில் நுண்ணிய பூச்சி வலை பொருத்தவும், மஞ்சள் ஒட்டும் பொறிகளைத் தொங்கவிடவும், பயன்படுத்திய ஊடகத்தை விரைவில் அகற்றி சுற்றுப்புறத்தைச் சுத்தமாக வைக்கவும்.');

-- Knowledge Hub categories
INSERT INTO tutorial_categories (name, name_si, name_ta) VALUES
('House Preparation', 'හතු නිවාසය සකස් කිරීම', 'காளான் வீடு தயாரித்தல்'),
('Spawning / Seeding', 'බීජ දැමීම', 'வித்திடுதல்'),
('Disease Prevention', 'රෝග වැළැක්වීම', 'நோய் தடுப்பு'),
('Harvesting', 'අස්වනු නෙලීම', 'அறுவடை'),
('Post-Harvest Handling', 'අස්වනු නෙලීමෙන් පසු හැසිරවීම', 'அறுவடைக்குப் பிந்தைய கையாளுதல்');

-- Sample tutorials. video_url may be a YouTube *embed* or watch link (shown inline) or any
-- other link (shown as a "Watch video" button). Replace these with the expert
-- videos recorded for the project via Admin > Manage Tutorials.
INSERT INTO tutorials (category_id, title, title_si, title_ta, description, description_si, description_ta, video_url, related_disease_id) VALUES
(1, 'Preparing a Mushroom House from Scratch', 'මුල සිට හතු නිවාසයක් සකස් කිරීම', 'காளான் வீட்டை ஆரம்பத்திலிருந்து தயாரித்தல்',
 'Step-by-step guide to setting up a hygienic, climate-controlled mushroom growing house.',
 'සනීපාරක්ෂිත, දේශගුණය පාලිත හතු වගා නිවාසයක් පියවරෙන් පියවර සකස් කරන ආකාරය.',
 'சுகாதாரமான, காலநிலை கட்டுப்படுத்தப்பட்ட காளான் வளர்ப்பு வீட்டை படிப்படியாக அமைக்கும் வழிகாட்டி.',
 'https://www.youtube.com/results?search_query=mushroom+house+preparation+sri+lanka', NULL),
(2, 'Spawning Oyster Mushroom Substrate', 'ඔයිස්ටර් හතු පැකට් වලට බීජ දැමීම', 'சிப்பி காளான் ஊடகத்தில் வித்திடுதல்',
 'How to correctly inoculate substrate bags with mushroom spawn for high yield.',
 'ඉහළ අස්වැන්නක් සඳහා මාධ්‍ය පැකට් වලට නිවැරදිව බීජ දමන ආකාරය.',
 'அதிக விளைச்சலுக்கு ஊடகப் பைகளில் சரியாக வித்திடுவது எப்படி.',
 'https://www.youtube.com/results?search_query=oyster+mushroom+spawning+bags', NULL),
(3, 'Preventing Green Mold Contamination', 'කොළ පාට පුස් ආසාදනය වැළැක්වීම', 'பச்சை பூஞ்சை தொற்றைத் தடுத்தல்',
 'Practical hygiene steps to prevent Trichoderma (green mold) outbreaks in your growing room.',
 'වගා කාමරයේ ට්‍රයිකොඩර්මා (කොළ පුස්) පැතිරීම වැළැක්වීමට ප්‍රායෝගික පිරිසිදුකම් පියවර.',
 'வளர்ப்பு அறையில் டிரைக்கோடெர்மா (பச்சை பூஞ்சை) பரவலைத் தடுக்கும் நடைமுறை சுகாதார வழிமுறைகள்.',
 'https://www.youtube.com/results?search_query=trichoderma+green+mold+mushroom+prevention', 2),
(3, 'Controlling Bacterial Blotch', 'බැක්ටීරියා පැල්ලම් රෝගය පාලනය', 'பாக்டீரியா கறை நோயைக் கட்டுப்படுத்தல்',
 'Managing humidity and airflow to prevent bacterial blotch on mushroom caps.',
 'හතු හිස මත බැක්ටීරියා පැල්ලම් වැළැක්වීමට ආර්ද්‍රතාව සහ වාතය ගලායාම පාලනය.',
 'காளான் தொப்பியில் பாக்டீரியா கறையைத் தடுக்க ஈரப்பதம் மற்றும் காற்றோட்டத்தை நிர்வகித்தல்.',
 'https://www.youtube.com/results?search_query=bacterial+blotch+mushroom+control', 3),
(3, 'Managing Sciarid Flies and Mites', 'ස්කියාරිඩ් මැස්සන් සහ මයිටාවන් පාලනය', 'சியாரிட் ஈக்கள் மற்றும் சிலந்திப் பூச்சிகளைக் கட்டுப்படுத்தல்',
 'Using screens, sticky traps and hygiene to keep pests out of the mushroom house.',
 'දැල්, ඇලෙන උගුල් සහ පිරිසිදුකම මගින් පළිබෝධකයන් හතු නිවාසයෙන් ඈත් කිරීම.',
 'வலைகள், ஒட்டும் பொறிகள் மற்றும் சுகாதாரம் மூலம் பூச்சிகளைக் காளான் வீட்டிலிருந்து தடுத்தல்.',
 'https://www.youtube.com/results?search_query=mushroom+sciarid+fly+control', 4),
(4, 'Harvesting Mushrooms at the Right Time', 'නිවැරදි වේලාවට හතු නෙලීම', 'சரியான நேரத்தில் காளான் அறுவடை',
 'How to identify the optimal harvest window for maximum quality and shelf life.',
 'උපරිම ගුණාත්මකභාවය සහ කල් පැවැත්ම සඳහා සුදුසුම අස්වනු කාලය හඳුනාගැනීම.',
 'சிறந்த தரம் மற்றும் நீண்ட ஆயுளுக்கு உகந்த அறுவடை நேரத்தை அடையாளம் காணுதல்.',
 'https://www.youtube.com/results?search_query=when+to+harvest+oyster+mushrooms', NULL),
(5, 'Post-Harvest Storage and Packaging', 'අස්වනු නෙලීමෙන් පසු ගබඩා කිරීම සහ ඇසුරුම් කිරීම', 'அறுவடைக்குப் பின் சேமிப்பு மற்றும் பொதியிடல்',
 'Best practices for storing and packaging mushrooms before sale to maintain freshness.',
 'විකිණීමට පෙර හතු නැවුම්ව තබා ගැනීමට ගබඩා කිරීම සහ ඇසුරුම් කිරීමේ හොඳම ක්‍රම.',
 'விற்பனைக்கு முன் காளான்களைப் புதிதாக வைத்திருக்க சேமிப்பு மற்றும் பொதியிடலின் சிறந்த நடைமுறைகள்.',
 'https://www.youtube.com/results?search_query=mushroom+post+harvest+packaging', NULL);
