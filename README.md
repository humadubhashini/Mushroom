# Mushroom Direct

**AI-Based Direct Supply Chain and Disease Diagnostic System for Mushroom Cultivation in Sri Lanka**

A PHP/MySQL web application implementing the system described in the research project proposal and its accompanying SRS document. Built to run directly on **XAMPP** (Apache + MySQL + PHP), with no separate build step.

## Features (mapped to the SRS)

| Module | Pages | SRS Requirements |
|---|---|---|
| Registration, OTP email verification, admin approval of farmers, login, password reset, profile, role-based access (Farmer / Buyer / Admin / Agricultural Expert) | `auth/*`, `shared/profile.php` | FR-AUTH.1 – FR-AUTH.6, NFR-SEC.2, NFR-SEC.4 |
| Direct B2B marketplace: list, edit, remove, search/filter by type, location, quantity, price; bulk orders; order status; ratings & reviews | `farmer/*listing*`, `buyer/*` | FR-MKT.1 – FR-MKT.7 |
| Secure payment gateway (card / bank transfer / mobile wallet), digital receipts, transaction history, failure handling without double charging | `buyer/payment.php`, `shared/receipt.php`, `shared/transactions.php` | FR-PAY.1 – FR-PAY.5, NFR-SEC.3, NFR-REL.2 |
| AI "Snap & Detect" (camera capture, confidence score, all-class scores, description / treatment / pesticide / prevention, history, low-confidence flagging + expert review) | `farmer/diagnose.php`, `farmer/diagnosis_history.php`, `admin/diagnoses.php` | FR-AI.1 – FR-AI.6 |
| Treatment & pesticide recommendations, editable by experts | `farmer/diagnose.php`, `admin/diseases.php` | FR-REC.1 – FR-REC.3 |
| Knowledge Hub video tutorials, search, related-tutorial recommendations | `knowledge/*`, `admin/*tutorial*`, farmer dashboard | FR-EDU.1 – FR-EDU.4 |
| Admin: users (suspend / remove / create experts), listings, reports, disputes, AI log, audit log | `admin/*` | FR-ADM.1 – FR-ADM.4, NFR-SEC.5 |
| In-app notifications (🔔) for orders, payments, diagnoses, disputes | `shared/notifications.php` | FR-MKT.5, FR-NOT.1 – FR-NOT.3 |
| Quick-start guides for farmers, buyers, admins | `help.php` | SRS 2.6 |
| **English / සිංහල / தமிழ்** language switcher on every page (UI text, mushroom catalogue, diseases, treatments, tutorials and notifications) | `lang/*.php`, `?lang=` | NFR-LOC.1, NFR-LOC.2 |
| **Our Mushrooms** catalogue: each variety on its own page with description, growing info, cooking uses, price per 1 kg and the farmers currently selling it | `mushrooms/*`, `admin/mushroom_types.php` | FR-MKT.1, FR-MKT.3 |
| Trained CNN model (TensorFlow/Keras) + inference API | `ai_model/` | FR-AI.2, FR-AI.3, NFR-REL.3, NFR-MAINT.2 |

## Requirements

- XAMPP with PHP 8.1+ and MySQL/MariaDB (PHP's `gd` extension must be enabled — it is on by default in XAMPP).

## Installation (XAMPP)

1. **Copy the project** into your XAMPP web root, e.g.:
   ```
   C:\xampp\htdocs\mushroom-system   (Windows)
   /Applications/XAMPP/htdocs/mushroom-system   (Mac)
   /opt/lampp/htdocs/mushroom-system   (Linux)
   ```
   Any folder name works: the base URL is detected automatically.
   Check that `index.php` sits directly inside the folder (`htdocs\mushroom-system\index.php`), not inside a second `mushroom-system` folder. The page footer shows the version number (e.g. `v2.1`) so you can confirm the update is live.
2. **Start Apache and MySQL** from the XAMPP Control Panel.
3. **Create the database**: open `http://localhost/phpmyadmin`, click **Import**, choose `database/schema.sql` and press **Go**. This creates the `mushroom_system` database with all tables and demo data.
   > Re-importing drops and recreates the database (fresh start).
4. **Check the DB config**: `config/db.php` already matches XAMPP's defaults (`host=localhost`, `user=root`, `password=''`). Edit it only if your MySQL uses a password.
5. **Open the site**: `http://localhost/mushroom-system/`

## Languages

Use the 🌐 **English | සිංහල | தமிழ்** bar at the top of every page. The choice is remembered in a cookie. Interface text lives in `lang/en.php`, `lang/si.php` and `lang/ta.php`. Database content (mushroom types, diseases, treatments, tutorial titles) has `_si` / `_ta` columns, editable from the admin panel. The admin back-office pages are in English.

## Demo Accounts

| Role | Email | Password |
|---|---|---|
| Admin | admin@mushroom.lk | Admin@123 |
| Farmer | farmer@mushroom.lk | Farmer@123 |
| Buyer (hotel) | buyer@mushroom.lk | Buyer@123 |
| Agricultural Expert | expert@mushroom.lk | Expert@123 |

New farmer/buyer accounts are created via **Register**. New farmers must also be approved by an admin (**Admin Panel → Manage Users → Approve**) before they can publish listings. Each new account must enter a 6-digit OTP. XAMPP has no mail server by default, so with `DEMO_MODE = true` (in `config/app.php`) the OTP and password-reset links are also shown on screen. Set it to `false` once SMTP or an SMS service is configured.

**Payment test cards:** any 16-digit number with a future `MM/YY` expiry succeeds. `4000 0000 0000 0002` is always declined, so you can demonstrate failed payments.

You can switch accounts without logging out: open **Login** and sign in with another account.

## Troubleshooting

- **Snap & Detect says the "GD" extension is off.** XAMPP Control Panel → Apache → **Config** → **PHP (php.ini)**. Find `;extension=gd`, remove the `;`, save, then Stop and Start Apache.
- **Photo too large.** The limit is 10MB. iPhone HEIC photos are not supported; use JPG/PNG.
- **Database changed after an update.** Re-import `database/schema.sql` in phpMyAdmin. This resets the demo data.

## Suggested Demo Flow (for viva / evaluation)

1. Open **Our Mushrooms** → click a mushroom → see details and price per kg. Switch to සිංහල / தமிழ் at the top.
2. **Buyer** → Marketplace → filter → open a listing → place an order.
3. **Farmer** → 🔔 notification → Orders → **Confirm**.
4. **Buyer** → My Orders → **Pay Now** → try the decline card, then a valid card → receipt.
5. **Farmer** → **Mark Completed** → **Buyer** → **Rate Farmer**.
6. **Farmer** → **Snap & Detect** → upload a photo → diagnosis, confidence, treatment, related video.
7. **Expert / Admin** → AI Diagnosis Log → review a case → farmer sees the advice in history.
8. **Admin** → Reports, Disputes, Audit Log.

## Project Structure

```
config/              Database connection & app constants (BASE_URL, DEMO_MODE, AI_API_URL)
includes/            Shared bootstrap, auth guards, helpers, AI classifier
auth/                Register / OTP verify / login / logout / forgot & reset password
farmer/              Farmer dashboard, listings, orders, Snap & Detect, diagnosis history
buyer/               Buyer dashboard, marketplace, orders, payment, reviews
shared/              Profile, notifications, transactions, receipts, disputes (farmer + buyer)
knowledge/           Public Knowledge Hub (tutorials)
mushrooms/           Public "Our Mushrooms" catalogue (list + detail with price per kg)
admin/               Admin/expert panel: users, listings, tutorials, treatments, reports, disputes, AI log, audit log
ai_model/            Python CNN training script + Flask inference API
lang/                UI strings: en.php (English), si.php (Sinhala), ta.php (Tamil)
assets/              CSS and uploaded images (listings, diagnoses, profiles, tutorials)
database/schema.sql  Full schema + seed/demo data
help.php             In-app quick-start guides
```

## About the AI "Snap & Detect" Module

Two classifiers sit behind a single function, `classify_mushroom_image()` in `includes/ai_classifier.php` (NFR-MAINT.2):

1. **Trained CNN (as in the proposal methodology)**: `ai_model/train.py` trains a MobileNetV2 transfer-learning CNN with data augmentation on your labelled dataset (500+ images in `ai_model/dataset/<Healthy|Green Mold|Bacterial Blotch|Pest Attack>/`). It reports accuracy, precision and recall, and saves a confusion-matrix chart (NFR-REL.3). `ai_model/app.py` serves the model as a REST API.
   ```
   cd ai_model
   pip install -r requirements.txt
   python train.py --data dataset
   python app.py                    # http://127.0.0.1:5000/predict
   ```
   Then set `AI_API_URL` in `config/app.php` to `'http://127.0.0.1:5000/predict'`.
2. **Built-in fallback**: while `AI_API_URL` is empty (or the Python service is down), a colour-pattern analyser using PHP's GD extension runs instead. It measures green mould areas, and yellow/brown blotches or dark specks *inside* the mushroom cap (so soil or bag backgrounds are ignored), then reports a percentage for all four classes. Photos with too little visible mushroom are returned as low-confidence and sent for expert review. The website therefore works on plain XAMPP with no Python. It is a rule-based placeholder, not a trained model, so don't report its results as CNN accuracy.

Every result stores which model produced it (`diagnoses.model_version`). Expert-confirmed labels (`reviewed_disease_id`) can be exported to grow the training dataset.

## About the Payment Gateway

`buyer/payment.php` implements a **simulated** secure payment gateway: it validates card-shaped input, never stores raw card numbers (only a masked reference), and marks the order as paid. This satisfies the SRS's payment requirements for demonstration purposes without needing live merchant credentials. To go live, swap the validation/insert block for a real gateway SDK call (e.g. **PayHere**, the most common Sri Lankan payment gateway) while keeping the same `payments` table and success/failure branching.

## Technology Note

SRS section 2.4 suggests React/Node.js on cloud hosting. This implementation uses PHP + MySQL so it runs directly on **XAMPP**, as required for local demonstration. The modules, database design and REST-style AI service boundary follow the SRS, so the front end could be moved to React later without changing the database or the AI model.

## Known Limitations (carried over from the proposal)

- The system manages orders and payments only; physical delivery remains the farmer's/buyer's responsibility.
- AI diagnostic accuracy depends on photo lighting quality, as documented in the SRS.
