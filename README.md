# Penang Smart Parking System

A web-based smart parking management system for municipal-style parking in Penang, Malaysia.

## Features

- **Customer Portal**: Register/login, manage vehicles, start/end/extend parking, wallet management, pay compounds, submit appeals
- **Officer Portal**: Camera-based vehicle scan, AI plate recognition, violation detection, compound review
- **Admin Portal**: User management, zone configuration, reports, audit logs, system settings
- **AI Integration**: Google Gemini Vision API for vehicle license plate recognition
- **Payment**: PayPal integration for wallet reload
- **Security**: CSRF protection, SQL injection prevention, role-based access control, secure file uploads

## Technology Stack

- **Backend**: PHP 8.3+, MySQL, PDO
- **Frontend**: HTML5, CSS3, Vanilla JavaScript, Bootstrap 5, Font Awesome
- **Charts**: Chart.js
- **Maps**: Leaflet
- **AI**: Google Gemini Vision API
- **Payments**: PayPal API
- **Deployment**: XAMPP / Apache / MySQL / PHP 8.3+ / cPanel compatible

## Installation

### 1. Clone/Extract the Project

```bash
# Place in your web root (e.g., C:\xampp\htdocs\)
cd C:\xampp\htdocs\
git clone <repo-url> Penang_Smart_Parking
cd Penang_Smart_Parking
```

### 2. Configure Environment

```bash
# Copy environment template
cp .env.example .env

# Edit .env with your settings
# Required: Database credentials, Gemini API key, PayPal credentials
```

### 3. Create Database

```bash
# Create database and tables
mysql -u root -p < database/schema.sql

# Optional: Load demo data
mysql -u root -p penang_parking < database/seed.sql
```

### 4. Set Permissions

```bash
# Ensure storage directories are writable
chmod 750 storage/
chmod 750 storage/evidence/
chmod 750 storage/logs/
chmod 750 storage/temp/
chmod 750 storage/uploads/
```

### 5. Configure Web Server

Ensure mod_rewrite is enabled for Apache. When the project is placed in
`C:\xampp\htdocs\Penang_Smart_Parking`, open:

```text
http://localhost/Penang_Smart_Parking/
```

The root `.htaccess` forwards application routes to `public/index.php` while
leaving assets, API scripts, and other real files accessible.

## Demo Accounts

| Role | Email | Password |
|------|-------|----------|
| Super Admin | superadmin@penangparking.gov | admin123 |
| Admin | admin@penangparking.gov | admin123 |
| Officer | officer1@penangparking.gov | admin123 |
| Customer | customer1@test.com | admin123 |

## Project Structure

```
/
├── config/              # Configuration files
│   ├── app.php         # Application constants
│   ├── bootstrap.php   # Bootstrap & routing
│   ├── database.php    # DB connection
│   ├── env.php         # Environment loader
│   ├── gemini.php      # AI config
│   └── paypal.php      # Payment config
├── app/
│   ├── controllers/    # Request handlers
│   ├── helpers/        # Security, auth, plate helpers
│   ├── middleware/     # Auth & permission checks
│   ├── models/         # Database models
│   ├── services/       # Business logic services
│   └── views/          # HTML templates
├── api/                 # REST API endpoints
├── assets/             # CSS, JS, images
├── cron/               # Maintenance scripts
├── database/           # Schema & seed SQL
├── public/             # Web root
│   ├── index.php      # Main router
│   └── .htaccess      # URL rewriting
├── storage/            # Evidence, logs, uploads
├── .env                # Environment variables (NOT in git)
├── .env.example        # Environment template
└── .gitignore
```

## Security Features

- **CSRF Protection**: All state-changing requests validated
- **SQL Injection**: PDO prepared statements throughout
- **XSS Prevention**: Output escaping with `e()` helper
- **File Upload Security**: MIME validation, size limits, random filenames
- **Authentication**: Secure sessions, password hashing (bcrypt)
- **Authorization**: Role-based access control (RBAC)
- **Rate Limiting**: Login attempt throttling
- **Secret Management**: API keys in `.env`, never exposed to client

## API Endpoints

| Endpoint | Method | Auth | Description |
|----------|--------|------|-------------|
| `/api/ai/plate-recognition.php` | POST | Required | AI plate detection |
| `/api/parking/start.php` | POST | Customer | Start parking session |
| `/api/parking/end.php` | POST | Customer | End parking session |
| `/api/parking/extend.php` | POST | Customer | Extend parking session |
| `/api/parking/zones.php` | GET | Public | List active zones |
| `/api/parking/session.php` | GET | Required | Get customer sessions |
| `/api/paypal/create-order.php` | POST | Customer | Create PayPal order |
| `/api/paypal/capture-order.php` | POST | Required | Capture PayPal order |
| `/api/paypal/webhook.php` | POST | None | PayPal webhook handler |
| `/api/wallet/reload.php` | POST | Customer | Admin wallet reload (demo) |
| `/api/compound/appeal.php` | POST | Customer | Submit compound appeal |
| `/api/compound/pay.php` | POST | Customer | Pay compound |

## AI Integration

The system uses Google Gemini Vision API for license plate recognition:

1. Officer captures vehicle photo
2. Image sent to `/api/ai/plate-recognition.php`
3. Gemini returns plate number + confidence
4. PHP rule engine evaluates parking status
5. Violation determined by backend (not AI)
6. Officer reviews before compound issuance

**Important**: AI only reads plates. All enforcement decisions are made by the PHP backend rule engine.

## Cron Jobs

Add to crontab for automatic maintenance:

```cron
*/5 * * * * cd /path/to/penang_parking && php cron/maintenance.php >> /path/to/logs/cron.log 2>&1
```

The maintenance script handles:
- Expiring old parking sessions
- Marking overdue compounds
- Cleaning temporary files
- Sending parking-ending-soon notifications

## Development Notes

- All SQL uses PDO prepared statements
- Wallet operations use database transactions
- Evidence images stored outside public web root
- Audit logging for all administrative actions
- No framework dependencies - pure PHP for easy deployment

## License

Proprietary - Penang Smart Parking System
