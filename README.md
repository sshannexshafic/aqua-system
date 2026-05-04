cat > /home/claude/aquaculturesystem_final/README.md << 'EOF'
# Mugwe Fish Pond — Aquaculture Management System

A full-featured web application for managing fish ponds, stocks, water quality, feeding records, harvest data, and veterinary health monitoring. Built for Mugwe Fish Pond, Busolwe Town Council, Butaleja District, Uganda.

---

## Project Structure

```
aquaculturesystem/
├── admin/                  # Admin-only pages
│   ├── dashboard.php       # Admin overview dashboard
│   ├── ponds.php           # Pond CRUD management
│   ├── fish_stocks.php     # Fish stock management
│   ├── users.php           # User account management
│   └── reports.php         # Reports hub
│
├── farmer/                 # Farmer-role pages
│   ├── dashboard.php       # Farmer overview
│   ├── my_ponds.php        # Farmer's assigned ponds
│   ├── feed_records.php    # Feed logging
│   ├── harvest.php         # Harvest recording
│   └── water_quality.php   # Water quality logging
│
├── vet/                    # Veterinarian pages
│   ├── dashboard.php       # Vet overview & alerts
│   ├── health_records.php  # Diagnoses & treatments
│   ├── mortality.php       # Mortality event logging
│   └── recommendations.php # Recommendations to farmers
│
├── reports/                # Shared report pages
│   ├── financial_summary.php
│   ├── fish_growth.php
│   ├── pond_report.php
│   └── water_quality.php
│
├── public/                 # Public (unauthenticated) pages
│   ├── index.php           # Landing page
│   ├── login.php           # Login form
│   ├── register.php        # Self-registration
│   ├── forgot_password.php # Password reset (email token)
│   └── logout.php          # Session destroy
│
├── includes/               # Shared PHP components
│   ├── header.php          # HTML head + sidebar trigger
│   ├── footer.php          # Closing HTML + JS
│   ├── sidebar.php         # Navigation sidebar
│   ├── auth.php            # login(), isLoggedIn(), requireRole()
│   ├── db_connection.php   # Database class wrapper
│   └── functions.php       # Utility helpers
│
├── assets/
│   ├── css/
│   │   ├── style.css       # Global / public page styles
│   │   ├── dashboard.css   # Dashboard layout & components
│   │   └── responsive.css  # Breakpoints & media queries
│   ├── js/
│   │   ├── main.js         # Sidebar, alerts, table search, UX
│   │   ├── charts.js       # Canvas bar, line, donut & gauge charts
│   │   └── validations.js  # Client-side form validation
│   └── images/
│       ├── logo.png        # Farm logo (add your own)
│       ├── ponds/          # Pond photos (optional)
│       └── fish-species/   # Fish species images (optional)
│
├── config/
│   ├── database.php        # DB credentials + SMS + SMTP config
│   └── sms_config.php      # Africa's Talking SMS helper
│
└── database/
    └── schema.sql          # Full database schema with seed data
```

---

## Setup Instructions

### 1. Requirements
- PHP 7.4 or 8.x
- MySQL 5.7 or MariaDB 10.3+
- Apache / Nginx with mod_rewrite
- Hosting that supports PHP (Hostinger, Truehost, cPanel Uganda, etc.)

### 2. Database Setup
```bash
mysql -u root -p < database/schema.sql
```
Or import via phpMyAdmin → Import → select `database/schema.sql`

### 3. Configure Credentials
Edit `config/database.php`:
```php
public static $DB_HOST = 'localhost';
public static $DB_NAME = 'aquaculture_system';
public static $DB_USER = 'your_db_user';
public static $DB_PASS = 'your_db_password';
```

### 4. Configure SMS Alerts (Optional)
Sign up at [africastalking.com](https://africastalking.com) and update:
```php
public static $SMS_USERNAME = 'your_at_username';
public static $SMS_API_KEY  = 'your_api_key';
public static $MANAGER_PHONE = '+256772XXXXXX';
```

### 5. Configure Email (Optional — for password reset)
Edit the `sendEmail()` function in `public/forgot_password.php` or install PHPMailer via Composer for reliable SMTP delivery.

### 6. First Login
- URL: `yoursite.com/public/login.php`  
- Username: `admin`  
- Password: `password`  
- **Change this password immediately after first login!**

---

## User Roles

| Role    | Access                                                   |
|---------|----------------------------------------------------------|
| Admin   | All pages, user management, reports, ponds, fish stocks  |
| Farmer  | Own ponds, feed records, harvest, water quality logging  |
| Vet     | Health records, mortality, recommendations to farmers    |

---

## Key Features

- **Multi-role authentication** — admin, farmer, vet with role-based redirects
- **Pond management** — size, depth, type, status, farmer assignment
- **Fish stock tracking** — species, quantity, weight, stocking source
- **Water quality monitoring** — pH, temperature, dissolved oxygen with SMS alert on critical readings
- **Feed records** — type, quantity, cost per feeding event
- **Harvest records** — quantity, weight, price, revenue, buyer info
- **Vet health records** — diagnosis, treatment, severity, follow-up scheduling
- **Mortality tracking** — cause, estimated losses in UGX
- **Vet recommendations** — prioritised, categorised, status-tracked advice to farmers
- **Financial reports** — monthly and pond-level P&L summaries
- **Responsive design** — works on Android phones on MTN/Airtel data
- **Canvas charts** — no Chart.js dependency, lightweight bar/line/donut/gauge charts
- **Client-side validation** — all forms validated before submission

---

## Technology Stack

| Layer    | Technology                        |
|----------|-----------------------------------|
| Backend  | PHP 7.4+ (no framework)           |
| Database | MySQL / MariaDB                   |
| Frontend | Vanilla HTML, CSS, JavaScript     |
| Charts   | Custom Canvas (no Chart.js)       |
| SMS      | Africa's Talking API              |
| Email    | PHP mail() / PHPMailer            |
| Fonts    | Google Fonts (Inter, Poppins)     |

---

## Security Notes

- All user inputs sanitised with `htmlspecialchars` and `strip_tags`
- Passwords hashed with `password_hash(PASSWORD_DEFAULT)`
- PDO prepared statements throughout — no raw SQL with user input
- Role-based access control on every page
- Session-based authentication
- Change `ENCRYPTION_KEY` in `config/database.php` before production

---

## Customisation

- Replace `assets/images/logo.png` with the farm logo
- Update farm name/location in `config/database.php` → `$SYSTEM_NAME`
- Update footer text in `includes/footer.php`
- Adjust water quality thresholds in `includes/functions.php` → `getWaterQualityStatus()`

---

**Built with care for Ugandan fish farmers 🐟**  
© 2026 Aquaculture management system
EOF