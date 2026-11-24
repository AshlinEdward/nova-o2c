# Nova O2C – Order-to-Cash ERP

## 1. Project Overview

Nova O2C is a multi-company Order-to-Cash ERP application developed using Laravel. It manages the business process from customer and sales order management through inventory allocation, shipment, invoicing, payment collection, sales returns and customer ledger tracking.

The application supports company-level data isolation and role-based access control.

## 2. Live Application

**Production URL:**  
https://nova-o2c-production-vppmvu.laravel.cloud/login

**GitHub Repository:**  
https://github.com/AshlinEdward/nova-o2c

## 3. Technology Stack

- Backend: PHP 8.2, Laravel 11
- Database: MySQL
- Frontend: HTML, CSS, Bootstrap, JavaScript, Blade
- Development Environment: XAMPP
- Version Control: Git and GitHub
- Hosting: Laravel Cloud

## 4. Application Modules

- Authentication and role-based authorization
- Multi-company management and data isolation
- Customer management
- Product management
- Warehouse management
- Inventory and stock management
- Inventory cost layers
- Pricing, price lists, slabs and promotions
- Sales order management
- Credit review and approval
- Shipment management
- Invoice generation
- Payment recording and allocation
- Sales returns and credit notes
- Customer ledger
- Reports
- Webhook delivery tracking

## 5. User Roles

The application includes five predefined roles:

| Role | Purpose |
|---|---|
| Admin | Administrative access |
| Sales Executive | Sales operations |
| Sales Manager | Sales management and credit review |
| Warehouse | Warehouse and inventory operations |
| Accounts | Payment and accounting operations |

## 6. Demo Login Credentials

Nine demo users are created through the `RoleAndUserSeeder`.

**Common demo password:** `password`

| Company | Role | Email |
|---|---|---|
| Gujarat | Admin | admin.gj@nova.test |
| Gujarat | Sales Executive | sales.gj@nova.test |
| Gujarat | Sales Executive | sales2.gj@nova.test |
| Gujarat | Sales Manager | salesmgr.gj@nova.test |
| Gujarat | Warehouse | wh.gj@nova.test |
| Gujarat | Accounts | accounts.gj@nova.test |
| Delhi | Admin | admin.dl@nova.test |
| Delhi | Sales Executive | sales.dl@nova.test |
| Delhi | Warehouse | wh.dl@nova.test |

Use these accounts to explore the application under different roles and company contexts.

**Note:** These are demonstration credentials only. User creation and role assignment through the application UI are not currently implemented. The demo roles and accounts are provisioned through Laravel seeders.

## 7. Database Setup

The project uses MySQL.

For a local setup:

1. Create a MySQL database named `nova_o2c`.
2. Configure the database connection in your `.env` file.
3. Install the Composer dependencies.
4. Generate the application key.
5. Run migrations and seeders.

Example commands:

```bash
composer install
php artisan key:generate
php artisan migrate
```

To populate the demo data, execute the seeders in dependency order:

```bash
php artisan db:seed --class=CompanySeeder
php artisan db:seed --class=RoleAndUserSeeder
php artisan db:seed --class=ProductSeeder
php artisan db:seed --class=WarehouseSeeder
php artisan db:seed --class=CustomerSeeder
php artisan db:seed --class=PricingSeeder
php artisan db:seed --class=OpeningInventorySeeder
php artisan db:seed --class=OpeningLedgerSeeder
```

For a production environment, append `--force` to each seeder command.

**Important:** Do not blindly rerun seeders on an existing database. Some seeders may create duplicate records. Use a fresh database for a clean demo setup.

## 8. Running the Application Locally

Start the Laravel development server:

```bash
php artisan serve
```

Open:

http://127.0.0.1:8000

## 9. Project Structure

```text
nova-o2c/
|-- app/
|   |-- Http/
|   |   |-- Controllers/
|   |   |-- Middleware/
|   |-- Models/
|   |-- Services/
|-- bootstrap/
|-- database/
|   |-- migrations/
|   |-- seeders/
|-- resources/
|   |-- views/
|-- routes/
|   |-- web.php
|-- tests/
|   |-- Feature/
|   |-- Unit/
|-- .env.example
|-- composer.json
|-- README.md
```

## 10. Testing

The project test suite was executed successfully during development.

**Latest recorded result:**
- 11 tests passed
- 45 assertions passed

Run the tests using:

```bash
php artisan test
```

## 11. Deployment

The application is hosted on Laravel Cloud.

- Source control: GitHub
- Deployment branch: `main`
- Database: Laravel MySQL
- Production environment: `production`
- Application region: Singapore

Changes pushed to the connected GitHub `main` branch trigger the Laravel Cloud deployment workflow.

Database migrations and seeders are separate operations and must be executed when required.

## 12. Current Limitations

- User creation and role assignment are currently handled through database seeders, not an application UI.
- Demo accounts use a common demonstration password.
- The application is intended as an ERP assessment/demo project.

## 13. Developer Notes

This project demonstrates a multi-company Order-to-Cash workflow, Laravel MVC architecture, service-layer business logic, MySQL relationships, database transactions, role-based access control, inventory management, payment allocation, customer ledger tracking and reporting.

**Project:** Nova O2C  
**Framework:** Laravel 11  
**Database:** MySQL