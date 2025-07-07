-- Seri Perangin Catering Inventory Management System: Table Creation

-- Users table (for authentication and staff management)
CREATE TABLE IF NOT EXISTS users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('Owner', 'Staff') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Inventory table (for inventory items)
CREATE TABLE IF NOT EXISTS inventory (
    item_id INT AUTO_INCREMENT PRIMARY KEY,
    item_name VARCHAR(100) NOT NULL,
    category VARCHAR(50) NOT NULL,
    unit VARCHAR(20) NOT NULL,
    expiry_date DATE DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Stock logs table (for stock in/out history)
CREATE TABLE IF NOT EXISTS stock_logs (
    log_id INT AUTO_INCREMENT PRIMARY KEY,
    item_id INT NOT NULL,
    type ENUM('in', 'out') NOT NULL,
    quantity INT NOT NULL,
    log_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    user_id INT,
    FOREIGN KEY (item_id) REFERENCES inventory(item_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL
); 