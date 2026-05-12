# Digital Credit Ledger & Scoring System

A professional, high-performance web application designed for small businesses to manage customer credits, track repayments, and automate trust-based lending. This system replaces traditional paper ledgers with a secure, data-driven digital solution.

## 🚀 Key Features

### 💎 Smart Business Suite
- **Dynamic Trust Scoring**: A tiered engine that automatically adjusts customer creditworthiness based on repayment behavior.
  - **Elite Rewards**: Early payments grant massive bonuses.
  - **Overdue Penalties**: Real-time business protection with daily point deductions.
- **Itemized Tracking**: Record exactly what was taken on credit (e.g., "3 bags of rice") instead of just a generic amount.
- **Executive Dashboard**: Real-time business health metrics including Repayment Rate, Total Portfolio at Risk, and Monthly Disbursement.

### 📧 Automation & Communication
- **Automated Daily Reminders**: Scans for overdue debts every morning at 8:00 AM and notifies customers via SMTP.
- **Digital Receipts**: Instant payment confirmation emails sent directly to customers.
- **Professional PDF Statements**: Filterable reports for specific date ranges, perfect for auditing and dispute resolution.

### 🛡️ Secure & Scalable
- **Role-Based Access Control (RBAC)**: Distinct permissions for Staff (Viewers) and Admins (Managers).
- **RESTful API Engine**: Secured with Laravel Sanctum, allowing for seamless integration with external shops, mobile apps, or ERP systems.
- **Security Hardening**: Integrated rate limiting, strict CSRF protection, and comprehensive validation.

## 🛠️ Technology Stack
- **Framework**: Laravel 12.0
- **Database**: SQLite (Zero-configuration, high performance for local shops)
- **Frontend**: Tailwind CSS (Executive, high-end UI design)
- **Reports**: Barryvdh/Laravel-Dompdf (Professional PDF Generator)
- **Security**: Laravel Sanctum (Token-based API security)
- **Mail**: SMTP Integration (Configured for Gmail)

## 📦 Installation & Setup

1. **Clone the repository**
2. **Install Dependencies**:
   ```bash
   composer install
   npm install && npm run build
   ```
3. **Configuration**:
   Copy `.env.example` to `.env` and configure your SMTP settings and Application Name.
4. **Initialize Database**:
   ```bash
   php artisan key:generate
   touch database/database.sqlite
   php artisan migrate
   ```
5. **Run the System**:
   ```bash
   php artisan serve
   ```

## 🧪 Testing & Verification
The system includes a robust suite of automated tests (Feature & Unit) ensuring 100% stability.
```bash
php artisan test
```

---
*Created with care by Antigravity AI for a modern, high-performance business experience.*
