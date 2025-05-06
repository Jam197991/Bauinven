-- Create database
CREATE DATABASE IF NOT EXISTS bauapp_db;
USE bauapp_db;

-- Create categories table
CREATE TABLE IF NOT EXISTS categories (
    category_id INT PRIMARY KEY AUTO_INCREMENT,
    category_name VARCHAR(50) NOT NULL,
    category_type ENUM('food', 'product') NOT NULL,
    image_url VARCHAR(255)
);

-- Create products table
CREATE TABLE IF NOT EXISTS products (
    product_id INT PRIMARY KEY AUTO_INCREMENT,
    product_name VARCHAR(100) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    category_id INT,
    image_url VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(category_id)
);

-- Create orders table
CREATE TABLE IF NOT EXISTS orders (
    order_id INT PRIMARY KEY AUTO_INCREMENT,
    order_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    total_amount DECIMAL(10,2) NOT NULL,
    status VARCHAR(20) DEFAULT 'pending'
);

-- Create order_items table
CREATE TABLE IF NOT EXISTS order_items (
    item_id INT PRIMARY KEY AUTO_INCREMENT,
    order_id INT,
    product_id INT,
    quantity INT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(order_id),
    FOREIGN KEY (product_id) REFERENCES products(product_id)
);

-- Insert categories
INSERT INTO categories (category_name, category_type, image_url) VALUES
('Vegetables Corner', 'product', 'images/categories/vegetables.jpg'),
('Fruits Corner', 'product', 'images/categories/fruits.jpg'),
('Dairy Products', 'product', 'images/categories/dairy.jpg'),
('Local Delicacies', 'food', 'images/categories/delicacies.jpg'),
('Farm Fresh Meals', 'food', 'images/categories/meals.jpg'),
('Organic Products', 'product', 'images/categories/organic.jpg');

-- Insert products
INSERT INTO products (product_name, description, price, category_id, image_url) VALUES
-- Vegetables Corner
('Fresh Lettuce', 'Crisp and fresh lettuce from local farms', 49.99, 1, 'images/products/lettuce.jpg'),
('Organic Tomatoes', 'Fresh organic tomatoes', 39.99, 1, 'images/products/tomatoes.jpg'),
('Green Beans', 'Fresh green beans', 29.99, 1, 'images/products/greenbeans.jpg'),
('Carrots', 'Fresh local carrots', 34.99, 1, 'images/products/carrots.jpg'),

-- Fruits Corner
('Fresh Mangoes', 'Sweet local mangoes', 89.99, 2, 'images/products/mangoes.jpg'),
('Organic Bananas', 'Fresh organic bananas', 49.99, 2, 'images/products/bananas.jpg'),
('Fresh Apples', 'Imported fresh apples', 79.99, 2, 'images/products/apples.jpg'),
('Local Oranges', 'Sweet local oranges', 59.99, 2, 'images/products/oranges.jpg'),

-- Dairy Products
('Farm Fresh Eggs', 'Dozen of farm fresh eggs', 149.99, 3, 'images/products/eggs.jpg'),
('Fresh Milk', 'Fresh cow milk', 89.99, 3, 'images/products/milk.jpg'),
('Local Cheese', 'Fresh local cheese', 199.99, 3, 'images/products/cheese.jpg'),

-- Local Delicacies
('Bibingka', 'Traditional rice cake', 49.99, 4, 'images/products/bibingka.jpg'),
('Puto', 'Steamed rice cake', 39.99, 4, 'images/products/puto.jpg'),
('Kakanin Set', 'Assorted rice cakes', 199.99, 4, 'images/products/kakanin.jpg'),

-- Farm Fresh Meals
('Farm Fresh Adobo', 'Traditional chicken adobo', 149.99, 5, 'images/products/adobo.jpg'),
('Vegetable Stir Fry', 'Fresh vegetable stir fry', 129.99, 5, 'images/products/stirfry.jpg'),
('Farm Fresh Sinigang', 'Traditional sour soup', 159.99, 5, 'images/products/sinigang.jpg'),

-- Organic Products
('Organic Rice', 'Premium quality organic rice', 249.99, 6, 'images/products/rice.jpg'),
('Local Honey', 'Pure honey from local beekeepers', 199.99, 6, 'images/products/honey.jpg'),
('Organic Coffee', 'Fresh ground organic coffee', 299.99, 6, 'images/products/coffee.jpg'); 