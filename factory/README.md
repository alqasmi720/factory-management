# Foldline Factory Management

Laravel 12 / PHP 8.2 application using Blade, Bootstrap 5, vanilla JavaScript, and MySQL or MariaDB. The current project also supports SQLite for local development and automated tests.

## Setup

1. Install PHP 8.2+, Composer, and MySQL/MariaDB (or use the existing SQLite setup).
2. Run `composer install`.
3. Copy `.env.example` to `.env`, set `APP_KEY` with `php artisan key:generate`, and configure `DB_*` values. `APP_TIMEZONE` defaults to `Asia/Karachi`.
4. Create the configured database, then run `php artisan migrate --seed`.
5. Create the first owner interactively: `php artisan factory:create-owner owner@example.com "Factory Owner"`. The command prompts for a password and stores only a hash.
6. Start the application with `php artisan serve`, then sign in at `/login`.

The seeder creates fictional customer and supplier contacts only. It does not create financial transactions or a default password.

## Tests

Run `php artisan test`. The test suite uses the isolated in-memory SQLite connection from `phpunit.xml` and covers authenticated access, order creation and editing, decimal totals, split payment allocation, printable documents, CSV opening-balance import, and a role restriction.

## Financial scope and known decisions

- Money uses DECIMAL columns, two-decimal PKR handling, and the `OrderPricingService` manual-price path. BCMath is required for server-side decimal arithmetic.
- Customer and supplier payments have relational allocation tables. Payments can be left unallocated or split across multiple open orders or purchases. Server validation checks remaining payment and document balances.
- Authorized staff can void a payment with a reason. Original rows and allocations remain stored; voided payments are excluded from balances and the action is audited. Refunds and compensating cash entries are not implemented.
- Owner-only CSV preview/commit imports contacts and non-negative opening balances, reports row errors, skips existing duplicates, and audits imported rows. Opening balances represent current starting receivable/payable amounts; the import does not create historical transactions.
- Pricing formula, units/conversions, paper grades/rates, wastage, discounts, tax, cheque clearance, refunds, and cancellation treatment for advances require the factory's confirmed rules. No formula or tax calculation is claimed as verified.
- The app has staff login throttling and role gates for owner, manager, accounts, and operational staff. Owner-only account provisioning and business settings are included. Fine-grained configurable permissions, password reset, and MFA need completion.
- Invoices, receipts, and customer/supplier statements are available as print-friendly HTML and server-generated A4 PDF files using Dompdf. PDF response generation is covered by feature tests; visual page rendering could not be checked in the current environment because no PDF renderer is installed.
- Purchases have one description and total per record. Purchase itemization, inventory, expense screens, refunds, and comprehensive period statements are not implemented. No profit or cash-flow result is calculated.

## Backup and restore

Before business use, test backup restoration on a separate machine. For MySQL, make a scheduled encrypted dump outside the application directory and retain off-device copies. Example:

```sh
mysqldump --single-transaction --host="$DB_HOST" --user="$DB_USERNAME" --password "$DB_DATABASE" > /secure/backup/factory-$(date +%F-%H%M).sql
mysql --host="$DB_HOST" --user="$DB_USERNAME" --password "$DB_DATABASE" < /secure/backup/factory-backup.sql
```

Protect backups because they contain customer and financial records. For SQLite, stop writes and copy the database file to secure storage. Keep backups away from the web root and periodically test a restore.

## Before production deployment

Set `APP_ENV=production`, `APP_DEBUG=false`, a private `APP_KEY`, secure session/cookie settings, least-privilege database credentials, HTTPS, restricted filesystem permissions, and monitored logs. Configure and test backups, restoration, user roles, document storage, and factory-specific financial policies. Do not use this application as the sole real-world financial system until the remaining ledger, reversal, access-control, pricing, import, and document workflows have been validated with factory staff.
