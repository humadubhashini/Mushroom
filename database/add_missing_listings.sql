-- Adds one demo listing (by farmer@mushroom.lk) for every mushroom type that has
-- no active marketplace listing. Safe to run more than once; keeps existing data.
-- Run in phpMyAdmin: select the mushroom_system database > SQL tab > paste > Go.
INSERT INTO listings (farmer_id, mushroom_type_id, mushroom_type, description, quantity_kg, price_per_kg, harvest_date, location)
SELECT u.id, m.id, m.name, m.description, 30, m.price_per_kg, CURDATE(), 'Gampaha'
FROM mushroom_types m
JOIN users u ON u.email = 'farmer@mushroom.lk'
WHERE m.is_active = 1
  AND NOT EXISTS (SELECT 1 FROM listings l WHERE l.mushroom_type_id = m.id AND l.status = 'active');
