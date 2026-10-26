CREATE DATABASE IF NOT EXISTS inventory_db CHARACTER SET utf8mb4;
USE inventory_db;

CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  email VARCHAR(120) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
CREATE TABLE categories (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL UNIQUE
);
CREATE TABLE suppliers (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  phone VARCHAR(30),
  email VARCHAR(120)
);
CREATE TABLE products (
  id INT AUTO_INCREMENT PRIMARY KEY,
  sku VARCHAR(40) NOT NULL UNIQUE,
  name VARCHAR(150) NOT NULL,
  category_id INT NULL,
  supplier_id INT NULL,
  cost_price DECIMAL(10,2) NOT NULL DEFAULT 0,
  sell_price DECIMAL(10,2) NOT NULL DEFAULT 0,
  quantity INT NOT NULL DEFAULT 0,
  reorder_level INT NOT NULL DEFAULT 5,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
  FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON DELETE SET NULL
);
CREATE TABLE stock_movements (
  id INT AUTO_INCREMENT PRIMARY KEY,
  product_id INT NOT NULL,
  type ENUM('IN','OUT') NOT NULL,
  quantity INT NOT NULL,
  note VARCHAR(200),
  user_id INT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

INSERT INTO categories(name) VALUES ('Electronics'),('Stationery'),('Groceries'),('Furniture');
INSERT INTO suppliers(name,phone,email) VALUES
('Abans Distributors','0112345678','sales@abans.example'),
('Office World','0771234567','info@officeworld.example');
INSERT INTO products(sku,name,category_id,supplier_id,cost_price,sell_price,quantity,reorder_level) VALUES
('EL-001','Wireless Mouse',1,1,1200,1800,25,10),
('EL-002','USB Keyboard',1,1,1800,2600,4,8),
('ST-001','A4 Paper Ream',2,2,950,1250,60,20),
('ST-002','Ball Pen (Box of 50)',2,2,600,900,0,5),
('FN-001','Office Chair',4,2,14500,19900,7,3);
INSERT INTO stock_movements(product_id,type,quantity,note) VALUES
(1,'IN',25,'Opening stock'),(2,'IN',12,'Opening stock'),(2,'OUT',8,'Sold'),
(3,'IN',60,'Opening stock'),(4,'IN',20,'Opening stock'),(4,'OUT',20,'Sold'),(5,'IN',7,'Opening stock');
