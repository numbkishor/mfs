# Microfinance Loan Management System

A complete, production-quality Loan Management System built for a university
DBMS project, engineered the way a real commercial application would be:
modular Core PHP 8, a fully normalized MySQL database, and a clean
Bootstrap 5 interface — no frameworks, no ORM.

---

## 1. Technology Stack

| Layer      | Technology                          |
|------------|--------------------------------------|
| Frontend   | HTML5, CSS3, Bootstrap 5, Vanilla JS |
| Backend    | Core PHP 8 (`declare(strict_types=1)`, PDO) |
| Database   | MySQL 8 / MariaDB (InnoDB, 3NF)      |
| Dev Server | XAMPP (Apache + MySQL + PHP)         |

No Laravel, CodeIgniter, Symfony, React, Vue, Angular, Node.js or ORM is used
anywhere in this project.

---

## 2. Installation Guide (XAMPP)

1. **Copy the project** into your XAMPP `htdocs` folder, e.g.
   `C:\xampp\htdocs\microfinance-lms` (Windows) or
   `/opt/lampp/htdocs/microfinance-lms` (Linux).
2. **Start Apache and MySQL** from the XAMPP Control Panel.
3. **Create the database.** Open phpMyAdmin (`http://localhost/phpmyadmin`),
   go to the *Import* tab, and import `database/schema.sql`. This single
   script creates the `microfinance_lms` database, every table, all
   constraints/indexes, and seed/sample data.
   Alternatively, from a terminal:
   ```bash
   mysql -u root -p < database/schema.sql
   ```
4. **Check the configuration** in `config/config.php`:
   - `BASE_URL` must match the folder name you copied the project into
     (default: `/microfinance-lms`).
   - `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS` default to the standard
     XAMPP MySQL credentials (`root` / empty password). Change `DB_PASS`
     if your MySQL root user has a password.
5. **Visit the application** at `http://localhost/microfinance-lms/`.
   You'll be redirected to the Employee Login page.
6. **Log in** with the default administrator account (see Testing section
   below) and start exploring.

No `composer install` or `npm install` is required — Bootstrap, Bootstrap
Icons and Chart.js are loaded from a CDN, and there are zero PHP
dependencies outside the PHP core extensions (`pdo_mysql`, `mbstring`,
`openssl`) that ship with XAMPP by default.

---

## 3. Project / Folder Structure

```
microfinance-lms/
├── assets/                 CSS, JS, images (never PHP)
│   ├── css/style.css       Custom theme layered on Bootstrap 5
│   └── js/app.js           Sidebar toggle, validation, EMI calculator
├── auth/                   Login (employee + customer), logout
├── config/                 config.php (constants), database.php (PDO)
├── includes/               Shared PHP: session, auth/RBAC, CSRF,
│   │                       functions, loan/EMI/cash-flow helpers,
│   │                       header/sidebar/footer layout, partials/
│   └── partials/           Reusable list-table fragments
├── admin/                  Administrator area (full access)
│   ├── customers/          View/search all customers
│   ├── loan_types/         Full CRUD
│   ├── applications/       Review (approve/reject/return)
│   ├── loans/               View + full detail
│   ├── repayments/          Overview across all loans
│   ├── reports/             6 report types + report hub
│   └── users/                Employee account management
├── manager/                 Same shape as admin/, minus users/,
│   │                        loan_types/ and customers/ are read-only
├── officer/                 Loan Officer area — the only role that can
│   │                        create customers, submit applications and
│   │                        generate loans (repayment views are read-only)
├── cashier/                  Cashier desk — the only role that can
│   │                         disburse approved loans and collect
│   │                         repayment installments
├── customer/                 Read-only customer portal
├── database/schema.sql       Full DDL + seed data
├── uploads/                   User-uploaded files (protected, empty by default)
├── logs/                      app.log + php_errors.log (protected)
├── index.php                  Entry point / router
└── README.md                  This file
```

Every page in `admin/`, `manager/`, `officer/` and `customer/` starts by
requiring `includes/bootstrap.php`, then calls `require_login()` and
`require_role()` before doing anything else — this is the authentication
and RBAC middleware.

---

## 4. Architecture

The system follows a lightweight **modular MVC-like pattern** without a
framework:

- **Model** — SQL lives in `database/schema.sql`; data access goes through
  PDO prepared statements, mostly via small reusable query functions in
  `includes/functions.php` (e.g. `fetch_customers_list()`,
  `fetch_applications_list()`, `fetch_loans_list()`) and business-logic
  helpers in `includes/loan_helpers.php` (EMI calculation, amortization
  schedule generation, live cash-flow aggregation).
- **View** — `includes/header.php` / `sidebar.php` / `footer.php` form a
  shared page shell; `includes/partials/*.php` hold reusable table
  fragments (customers, applications, loans) so the same markup isn't
  duplicated across the Administrator, Manager and Loan Officer versions
  of a page.
- **Controller** — each `.php` file under a role folder is a thin
  controller: it validates input, talks to the database, and then
  includes the shared view partials. POST handlers always verify CSRF
  tokens first and use prepared statements exclusively.

### Why some pages are "thin" per role

Rather than duplicating the same 150-line list page four times, the
query logic (`fetch_*_list()` functions) and table markup
(`includes/partials/*.php`) are shared. Each role's `index.php` simply
calls the shared function with different parameters (e.g. scoped to the
logged-in officer) and passes different permission flags (`$canEdit`,
`$canDelete`, `$showReview`) into the shared partial. This keeps the
required "one file per module per role" folder structure while avoiding
duplicated logic.

---

## 5. Database Design (3NF)

All nine required tables are implemented in `database/schema.sql`:
`roles`, `users`, `customers`, `loan_types`, `loan_applications`, `loans`,
`repayment_schedules`, `payments`, `disbursements`. There is intentionally
**no `cash_flow` table** — cash flow is always computed live via SQL
aggregates in `get_cash_flow_summary()` and `get_monthly_cash_flow()`
(see `includes/loan_helpers.php`).

### Normalization notes

- Every non-key attribute depends only on its table's primary key (3NF).
- Customer identity data (`users`) is separated from customer-specific
  profile data (`customers`) via a 1-to-1 relationship, because a
  `users` row can belong to any of the four roles.
- `loan_applications` stores the *requested* terms; `loans` stores the
  *approved/locked-in* terms (principal, rate, EMI) — kept separate
  because an application can be rejected/returned without ever becoming
  a loan, and a loan's terms must never change even if the loan type's
  rate changes later.
- `repayment_schedules` (the EMI plan) is separate from `payments` (the
  actual money received) — a single installment can be paid in several
  partial payments, and this design supports that without redundancy.
- Every monetary/date figure needed for reporting is derivable via JOINs
  across these tables — nothing is duplicated or stored redundantly.

### Entity-Relationship Overview (textual)

```
roles (1) ───< (many) users
users (1) ───< (many) users          [self-reference: created_by]
users (1) ──── (1) customers          [customers.user_id]
users (1) ───< (many) customers       [customers.registered_by]
customers (1) ───< (many) loan_applications
loan_types (1) ───< (many) loan_applications
loan_applications (1) ──── (1) loans   [loans.application_id]
customers (1) ───< (many) loans
loan_types (1) ───< (many) loans
loans (1) ──── (1) disbursements
loans (1) ───< (many) repayment_schedules
repayment_schedules (1) ───< (many) payments
loans (1) ───< (many) payments
users (1) ───< (many) payments        [received_by]
```

Every foreign key uses `ON UPDATE CASCADE` and an appropriate
`ON DELETE` policy (`RESTRICT` for financial history, `CASCADE` only for
strictly dependent rows like a customer's own EMI schedule, `SET NULL`
for optional audit references). `CHECK` constraints guard amounts,
dates and rates at the database layer as a second line of defense behind
PHP-level validation.

---

## 6. User Roles & Portals

| Role           | Portal            | Highlights                                             |
|----------------|--------------------|---------------------------------------------------------|
| Administrator  | `/auth/login_employee.php` | Full access: loan types, employee accounts, all reports |
| Manager        | `/auth/login_employee.php` | Reviews & approves/rejects/returns applications, reports |
| Loan Officer   | `/auth/login_employee.php` | Registers customers, views their history, submits applications, generates loans |
| Cashier        | `/auth/login_employee.php` | Disburses approved loans, collects repayment installments |
| Customer       | `/auth/login_customer.php` | Read-only: profile, loans, EMI schedule, payment history |

Roles are **always** read from the database at login time (`roles` table
joined to `users.role_id`) and stored server-side in the session — never
accepted from the client. Customers cannot self-register; only a Loan
Officer can create a customer account (which also provisions their login
credentials in the same transaction).

---

## 7. Security

- **Prepared statements everywhere** — PDO with `ATTR_EMULATE_PREPARES`
  disabled; no string-concatenated SQL with user input anywhere.
- **Password hashing** — `password_hash()` (bcrypt) /
  `password_verify()`; passwords are transparently rehashed if the cost
  factor ever changes.
- **CSRF protection** — every state-changing form includes a per-session
  token (`includes/csrf.php`); it's verified server-side before any write.
- **Session hardening** — HttpOnly + SameSite cookies, session ID
  regeneration on login and periodically during a session, idle timeout.
- **RBAC middleware** — `require_login()` / `require_role()` gate every
  protected page; unauthorized access redirects to the correct portal.
- **Output escaping** — the `e()` helper (`htmlspecialchars`) wraps all
  dynamic output to prevent XSS.
- **Login rate limiting** — repeated failed logins for the same username
  trigger a temporary lockout.
- **Least privilege by folder** — Administrator/Manager/Loan Officer
  code is physically separated by folder, so a Loan Officer's browser
  session can never even reach `admin/users/*.php` — `require_role()`
  would redirect it away regardless.
- **Sensitive folders blocked** — `config/`, `includes/`, `database/`,
  `logs/` all carry `.htaccess` rules (`Deny from all`) so their PHP/SQL
  source is never served directly.

---

## 8. User Manual (quick tour)

1. **Employees** sign in at the Employee Login tab with the username and
   password issued by an Administrator.
2. A **Loan Officer** registers a new customer (Customers → New), then
   submits a loan application on their behalf (Applications → New),
   choosing a loan type and requested amount/duration within that type's
   configured limits.
3. A **Manager** (or Administrator) reviews pending applications and
   approves, rejects, or returns them with notes.
4. Once approved, the **Loan Officer** generates the loan (this builds
   the full EMI schedule automatically). The loan then appears on the
   **Cashier's** desk, and the Cashier records the disbursement.
5. As the customer repays, the **Cashier** collects each payment
   against the next due installment; partial payments are supported and
   the installment's status updates automatically (`pending` →
   `partial`/`paid`, or `overdue` if the due date has passed unpaid).
6. **Customers** sign in at the Customer Login tab to view their profile,
   loans, EMI schedule, outstanding balance, and download their full
   payment history as a CSV.
7. **Administrators/Managers** use the Reports Centre for cash flow,
   customer, loan, collection, overdue and monthly financial reports —
   all printable via the browser's print dialog.

---

## 9. Developer Guide

- PHP files use `declare(strict_types=1);` and PSR-friendly naming.
- Add a new page by copying the pattern used throughout: require
  `includes/bootstrap.php`, call `require_login()`/`require_role()`,
  do your data work, set `$pageTitle`/`$activeMenu`/`$breadcrumb`, then
  require `includes/header.php` … your markup … `includes/footer.php`.
- Add a new sidebar link in `includes/sidebar.php`'s `$menus` array.
- Shared list queries live in `includes/functions.php`
  (`fetch_customers_list`, `fetch_applications_list`, `fetch_loans_list`)
  — extend these rather than writing a new ad-hoc query in a view file.
- Loan math (EMI, amortization schedule, cash flow) lives in
  `includes/loan_helpers.php` — this is the only place that should ever
  compute interest.
- Run everything through `Database::getConnection()`, never open a raw
  `mysqli`/`PDO` connection elsewhere.

---

## 10. Deployment Guide

For a small pilot deployment beyond XAMPP:

1. Provision a LAMP/LEMP stack with PHP 8.1+ (`pdo_mysql`, `mbstring`,
   `openssl` extensions) and MySQL 8 / MariaDB 10.6+.
2. Import `database/schema.sql`, then **change the default administrator
   password immediately** via Admin → Employee Accounts → Edit.
3. Set `APP_ENV` to `production` in `config/config.php` (disables
   verbose error display, keeps error logging on).
4. Set `BASE_URL` to the real path/subdomain, and serve the app over
   HTTPS (set the session cookie `secure` flag on — this already happens
   automatically once `$_SERVER['HTTPS']` is set by your web server).
5. Point `DB_HOST`/`DB_USER`/`DB_PASS` at a dedicated, least-privilege
   MySQL user rather than `root`.
6. Ensure `uploads/` and `logs/` are writable by the web server user.
7. Set up a daily `mysqldump` backup of the database.

---

## 11. Testing

### Seeded test accounts (see `database/schema.sql`)

| Role          | Username        | Password       |
|---------------|-----------------|----------------|
| Administrator | `admin`         | `Admin@123`    |
| Manager       | `manager1`      | `Manager@123`  |
| Loan Officer  | `officer1`      | `Officer@123`  |
| Cashier       | `cashier1`      | `Cashier@123`  |
| Customer      | `fatema.begum`  | `Customer@123` |
| Customer      | `abdul.karim`   | `Customer@123` |
| Customer      | `nasrin.akter`  | `Customer@123` |

### Sample data included

- 4 loan types (Micro Business, Agriculture, Emergency, Home Improvement)
- 3 customers with full profiles
- 3 loan applications (one approved, one pending, one rejected)
- 1 active loan with a full 12-month EMI schedule and a real
  disbursement record
- 2 recorded payments against the first two installments (one is
  intentionally left overdue to demonstrate the overdue report)

### Suggested manual test scenarios

1. **Login rate limiting** — enter the wrong password 5 times for
   `officer1`; the 6th attempt should show a lockout message.
2. **End-to-end loan lifecycle** — as `officer1`, register a brand-new
   customer, submit an application, log in as `manager1` to approve it,
   then back in as `officer1` generate the loan; finally, as `cashier1`
   record the disbursement and collect a full repayment of installment #1.
   Expected: the schedule line flips to `paid`, and the Cash Flow report
   reflects the new disbursement/repayment.
3. **Partial payment** — record a payment smaller than the installment
   total; expected: status becomes `partial`, and a second payment for
   the remainder flips it to `paid`.
4. **RBAC boundaries** — while logged in as `officer1`, manually browse
   to `/admin/users/index.php`; expected: redirected away with a
   "permission" flash message.
5. **Customer portal isolation** — log in as `fatema.begum` and confirm
   only her own loan (`LN-000001`) is visible; she cannot browse another
   customer's data by editing the URL, since every customer query is
   scoped to `$_SESSION['customer_id']`.
6. **Overdue detection** — visit any dashboard; installment #3 on
   `LN-000001` (due 2026-04-15, unpaid) should already show as
   `overdue` on the Overdue Report, since `refresh_overdue_statuses()`
   runs on every dashboard load.

---

## 12. Future Improvements

- SMS/email notifications for upcoming and overdue installments.
- Configurable, multi-branch support (a `branches` table + branch-scoped
  RBAC).
- Document upload/verification for KYC (the `uploads/` folder and
  `disbursements.remarks` field are already in place to extend this).
- Two-factor authentication for Administrator accounts.
- Exportable PDF statements (currently CSV export is implemented for
  customer transaction history; PDF is a natural next step).
- Configurable EMI calculation strategies (flat-rate vs. reducing
  balance) per loan type — reducing balance is implemented today.

---

## 13. License / Academic Use

Built as a university DBMS coursework project. Feel free to adapt it for
learning purposes.
