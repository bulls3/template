CREATE DATABASE IF NOT EXISTS inventory_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE inventory_db;

-- Users
CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Categories
CREATE TABLE IF NOT EXISTS categories (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Suppliers
CREATE TABLE IF NOT EXISTS suppliers (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    email VARCHAR(150) NULL,
    phone VARCHAR(50) NULL,
    address TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Products (linked to category + supplier)
CREATE TABLE IF NOT EXISTS products (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    category_id INT UNSIGNED NOT NULL,
    supplier_id INT UNSIGNED NULL,
    name VARCHAR(150) NOT NULL,
    sku VARCHAR(50) NOT NULL UNIQUE,
    description TEXT NULL,
    price DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
    quantity INT NOT NULL DEFAULT 0,
    min_stock INT NOT NULL DEFAULT 5,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_products_category FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_products_supplier FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB;

-- Stock movements (linked to product + user)
CREATE TABLE IF NOT EXISTS stock_movements (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    type ENUM('in', 'out') NOT NULL,
    quantity INT NOT NULL,
    notes VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_stock_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_stock_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

-- Sample data
INSERT INTO categories (name, description) VALUES
('Electronics', 'Electronic devices and accessories'),
('Office Supplies', 'Stationery and office materials'),
('Furniture', 'Desks, chairs and office furniture');

INSERT INTO suppliers (name, email, phone, address) VALUES
('TechSupply Co', 'orders@techsupply.com', '+1-555-0101', '123 Tech Park Ave'),
('OfficeMart Ltd', 'sales@officemart.com', '+1-555-0202', '45 Business Blvd'),
('FurniWorld', 'info@furniworld.com', '+1-555-0303', '78 Industrial Road');

INSERT INTO products (category_id, supplier_id, name, sku, description, price, quantity, min_stock) VALUES
(1, 1, 'Wireless Mouse', 'ELC-001', 'Ergonomic wireless mouse', 25.99, 50, 10),
(1, 1, 'USB Keyboard', 'ELC-002', 'Full-size USB keyboard', 39.99, 30, 8),
(2, 2, 'A4 Paper Ream', 'OFF-001', '500 sheets A4 paper', 6.50, 100, 20),
(2, 2, 'Ballpoint Pens (Box)', 'OFF-002', 'Box of 50 blue pens', 8.99, 40, 10),
(3, 3, 'Office Chair', 'FUR-001', 'Adjustable swivel chair', 149.00, 12, 3),
(3, 3, 'Standing Desk', 'FUR-002', 'Height-adjustable desk', 299.00, 5, 2);

-- Default admin: admin@inventory.com / password
INSERT INTO users (name, email, password) VALUES
('Admin User', 'admin@inventory.com', '$2y$10$bmvXni6FyXmobXuuHqpn5.ex4UfXlmDCkresavBJHpoE91.5GhCue');
