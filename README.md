# Expense Tracker (expense-tracker)

A simple web app to keep a track of expenses. Built using HTML, CSS, Javascript, PHP and MySQL(for databases). It allows users to sign up, log in, and manage their expenses data efficiently.

---

## Features
- User authentication (Login/Signup)
- Dynamic Interaction of Data
- CRUD operations for users data
- Responsive design

---

## Prerequisites

1. **XAMPP or WAMP:** Ensure you have a local server environment installed (e.g., XAMPP or WAMP).
2. **PHP:** Version 7.4 or higher.
3. **MySQL:** Installed with phpMyAdmin access.

---

## Steps to Test the Project Locally (local host)

### 1. Clone the Repository
```bash
git clone https://github.com/iSamarthDubey/expense-tracker.git
cd expense-tracker
```

### 2. Start the Local Server

1. Open XAMPP or WAMP and start the Apache and MySQL services.
2. Access phpMyAdmin by navigating to `http://localhost/phpmyadmin` in your browser.

### 3. Set Up the Database

1. Open phpMyAdmin.
2. Create a new database by clicking on **New** in the sidebar and entering a database name (i.e., `expense_tracker`).
3. Run the following SQL script to set up the required tables:

```mysql
-- Create the database
CREATE DATABASE IF NOT EXISTS expense_tracker;

-- Select the database
USE expense_tracker;

-- Create the users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create the expenses table
CREATE TABLE IF NOT EXISTS expenses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    date DATE NOT NULL,
    category VARCHAR(255) NOT NULL,
    description VARCHAR(255) NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    status ENUM('Approved', 'Planned', 'Unwanted') NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

```

4. Import any additional data if necessary.

### 4. Configure the Project

Update the database configuration to match your local setup:
   ```php
   <?php
   $servername = "localhost";
   $username = "root";
   $password = "";
   $dbname = "expense_tracker";
   ?>
   ```

### 5. Access the Project

1. Move the project folder into your local server's directory:
   - **XAMPP:** `htdocs` folder (e.g., `C:\xampp\htdocs\expense-tracker`)

2. Open your browser and navigate to:
   ```
   http://localhost/expense-tracker
   ```

3. Test the login and signup functionality and more ..

---

## Troubleshooting

- **Error: "Unable to connect to the database"**
  - Ensure your database credentials are correct.
  - Verify that the MySQL service is running.

- **PHP Errors:**
  - Check your PHP version. It must be 7.4 or higher.

---

## Contribution
Feel free to fork this repository and submit pull requests. Your contributions are welcome!

---

## License
This project is licensed under the MIT License. See the `LICENSE` file for details.
