# Mushroom Direct

**AI-Based Direct Supply Chain and Disease Diagnostic System for Mushroom Cultivation in Sri Lanka**

A PHP/MySQL web application implementing the system described in the research project proposal and its accompanying SRS document. Built to run directly on **XAMPP** (Apache + MySQL + PHP), with no separate build step.

## Features (mapped to the SRS)

| Module | SRS Requirements |
|---|---|
| Registration, login, role-based access (Farmer / Buyer / Admin) | FR-AUTH.1 – FR-AUTH.6 |
| Direct B2B marketplace (list, search, order) | FR-MKT.1 – FR-MKT.7 |
| Simulated secure payment gateway | FR-PAY.1 – FR-PAY.5 |
| AI "Snap & Detect" disease diagnosis | FR-AI.1 – FR-AI.6 |
| Treatment & pesticide recommendations | FR-REC.1 – FR-REC.3 |
| Knowledge Hub video tutorials | FR-EDU.1 – FR-EDU.4 |
| Admin panel (users, listings, tutorials, AI log) | FR-ADM.1 – FR-ADM.4 |
| Order/payment notifications (in-app flash messages) | FR-NOT.1 – FR-NOT.3 |

## Requirements

- XAMPP with PHP 8.1+ and MySQL/MariaDB (PHP's `gd` extension must be enabled — it is on by default in XAMPP).

## Installation (XAMPP)

1. **Copy the project** into your XAMPP web root, e.g.:
   ```
   C:\xampp\htdocs\mushroom-system   (Windows)
   /Applications/XAMPP/htdocs/mushroom-system   (Mac)
   /opt/lampp/htdocs/mushroom-system   (Linux)
   ```
2. **Start Apache and MySQL** from the XAMPP Control Panel.
3. **Create the database**: open `http://localhost/phpmyadmin`, click **Import**, and import `database/schema.sql`. This creates the `mushroom_system` database, all tables, and seed data (disease catalogue, tutorial categories, sample tutorials, and a default admin account).
4. **Check the DB config**: `config/db.php` already matches XAMPP's defaults (`host=localhost`, `user=root`, `password=''`). Edit it only if your local MySQL uses different credentials.
5. **Check the base URL**: `config/app.php` sets `BASE_URL` to `/mushroom-system`. If you copied the project into a differently named folder, update this constant to match.
6. **Open the site**: go to `http://localhost/mushroom-system/index.php`.

## Default Logins

| Role | Email | Password |
|---|---|---|
| Admin | admin@mushroom.lk | Admin@123 |

Farmer and buyer accounts are created via the **Register** page. Change the admin password after first login (no "change password" screen is provided yet — update it directly in the `users` table via phpMyAdmin using `password_hash()`, or extend the admin panel).

## Project Structure

```
config/            Database connection & app constants
includes/          Shared bootstrap, auth guards, helpers, AI classifier
auth/              Register / login / logout
farmer/            Farmer dashboard, listings, orders, Snap & Detect
buyer/             Buyer dashboard, marketplace, orders, payment, reviews
knowledge/         Public Knowledge Hub (tutorials)
admin/             Admin dashboard, user/listing/tutorial management, AI log
assets/            CSS, JS, and uploaded images (listings, diagnoses, tutorials)
database/schema.sql  Full schema + seed data
```

## About the AI "Snap & Detect" Module

`includes/ai_classifier.php` currently ships with a **lightweight colour-signature heuristic** built on PHP's GD extension (already bundled with XAMPP), so the full marketplace + diagnosis + payment flow runs end-to-end without needing a GPU, Python, or any external service. It classifies an uploaded photo into **Healthy**, **Green Mold**, **Bacterial Blotch**, or **Pest Attack** based on colour/brightness patterns, and always returns a confidence score, exactly matching the FR-AI requirements and the diagnosis history/low-confidence-flagging behaviour in the SRS.

This is a stand-in for the trained Convolutional Neural Network described in the proposal's methodology (Section 4.5 — trained on 500+ labelled images, evaluated with a confusion matrix). To plug in the real model once it is trained:

1. Expose the trained CNN through a small inference API (e.g. a Python Flask endpoint, or TensorFlow.js if you want it in-browser).
2. In `includes/ai_classifier.php`, replace the body of `classify_mushroom_image()` with a call to that API.
3. Keep the same return shape: `['disease' => string, 'confidence' => float, 'disease_id' => int|null]`.

No other file needs to change — this is exactly the separation required by NFR-MAINT.2 in the SRS.

## About the Payment Gateway

`buyer/payment.php` implements a **simulated** secure payment gateway: it validates card-shaped input, never stores raw card numbers (only a masked reference), and marks the order as paid. This satisfies the SRS's payment requirements for demonstration purposes without needing live merchant credentials. To go live, swap the validation/insert block for a real gateway SDK call (e.g. **PayHere**, the most common Sri Lankan payment gateway) while keeping the same `payments` table and success/failure branching.

## Known Limitations (carried over from the proposal)

- Interface is English-only for this release (Sinhala/Tamil planned).
- The system manages orders and payments only; physical delivery remains the farmer's/buyer's responsibility.
- AI diagnostic accuracy depends on photo lighting quality, as documented in the SRS.
