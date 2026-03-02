[README.md](https://github.com/user-attachments/files/25694479/README.md)
# CampusBuzz Setup Instructions

Follow these steps to set up and run the CampusBuzz web application.

## Prerequisites
- A local server environment like **XAMPP**, **WAMP**, or **MAMP**.
  - **PHP** (7.4 or higher recommended)
  - **MySQL** / **MariaDB**
  - **Apache** Web Server

## 1. Database Setup
1. Start your local MySQL server (e.g., via XAMPP Control Panel).
2. Open your database management tool (e.g., **phpMyAdmin** at `http://localhost/phpmyadmin`).
3. Create a new database named `campusbuzz`.
4. Import the provided SQL file:
   - Go to the **Import** tab in phpMyAdmin.
   - Select the file: `database/database.sql`.
   - Click **Go** to run the script.
   This will create the necessary tables (`users`, `posts`) and insert some sample data.

## 2. Configuration
Open `includes/db.php` and verify the database connection settings match your local environment.
The default settings are configured for a standard XAMPP setup:
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'campusbuzz');
define('DB_USER', 'root');
define('DB_PASS', ''); // Empty password for default XAMPP
```
If you have set a password for your root user, update `DB_PASS` accordingly.

## 3. Running the Application
1. Move or copy the entire `CampusBuzz` folder into your server's document root directory (e.g., `C:\xampp\htdocs\`).
2. Start the **Apache** module in your server control panel.
3. Open your web browser and navigate to:
   `http://localhost/CampusBuzz/public/`

## 4. Test Accounts
The database comes with pre-populated users. You can log in with:
- **Username:** `john_doe`
- **Password:** `password123`

or create a new account via the **Sign Up** page.
