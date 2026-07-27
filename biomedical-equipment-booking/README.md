# RapiFix - Biomedical Engineer Booking & Hospital Equipment Maintenance System

RapiFix is an on-demand full-stack web application designed for healthcare facilities to book certified Biomedical Equipment Technicians (BMET / BME) for emergency breakdown repairs, preventive maintenance (PM), and IEC 62353 electrical safety calibration.

---

## 🚀 Features

* **Multi-Role Portals**:
  * **Hospital Client**: Asset inventory management, emergency breakdown callouts, urgency level tagging (Critical, High, Medium, Low), live service status tracking.
  * **Biomedical Engineer (BME)**: Field workspace, availability toggle (`available`, `on_site`, `busy`, `offline`), digital job sheets, and IEC 62353 safety compliance calculator.
  * **Admin & Dispatcher Console**: Admin dispatch queue, engineer assignment, engineer account approval & verification workflow.

* **PDF & Printable Service Reports**:
  * Printable/downloadable **IEC 62353 Safety & Service Clearance Certificate** and **Field Assignment Work Orders** with hospital sign-off and verification codes.

* **Dual-Engine PDO Database**:
  * Production-ready MySQL schema (`config/schema.sql`).
  * Automatic SQLite fallback for instant 0-setup local development via PHP built-in server.

---

## 🛠️ Local Installation & Running

1. **Clone the Repository**:
   ```bash
   git clone https://github.com/YOUR_USERNAME/rapifix-biomedical-booking.git
   cd rapifix-biomedical-booking
   ```

2. **Run Local Dev Server**:
   ```bash
   php -S 127.0.0.1:8000
   ```
3. Open **`http://127.0.0.1:8000`** in your web browser.

---

## 🔑 Pre-seeded Demo Accounts (Password: `password123`)

* **Hospital Account**: `metro@hospital.org`
* **Engineer Account**: `marcus.vance@bme-pros.com`
* **Admin Account**: `admin@rapifix.com`

---

## 🌐 Deploying to Live Cloud Hosting from GitHub

Since PHP and MySQL require server-side execution:

### Method A: Railway.app (Free & 1-Click Deployment)
1. Push this repository to GitHub.
2. Sign up on [Railway.app](https://railway.app).
3. Click **New Project** &rarr; **Deploy from GitHub repo**.
4. Add a **MySQL** plugin service on Railway.
5. Import `config/schema.sql` into Railway MySQL.

### Method B: Render.com
1. Create a **Web Service** on Render connected to your GitHub repo.
2. Build Command: `(leave blank)`
3. Start Command: `php -S 0.0.0.0:$PORT`
4. Connect a MySQL database and update `config/db.php`.

### Method C: Standard cPanel / Shared Hosting (Hostinger, InfinityFree)
1. Import `config/schema.sql` into phpMyAdmin.
2. Upload the code files.
3. Update `DB_HOST`, `DB_USER`, `DB_PASS` in `config/db.php`.
