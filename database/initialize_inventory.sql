-- Initialize inventory table with sample data
-- This script populates the inventory table with initial quantities for all products

-- Insert initial inventory data for all products
INSERT INTO `inventory` (`product_id`, `quantity`) VALUES
(1, 50),   -- Fresh Lettuce
(2, 75),   -- Organic Tomatoes
(3, 30),   -- Green Beans
(4, 60),   -- Carrots
(5, 40),   -- Fresh Mangoes
(6, 100),  -- Organic Bananas
(7, 25),   -- Fresh Apples
(8, 80),   -- Local Oranges
(9, 20),   -- Farm Fresh Eggs
(10, 35),  -- Fresh Milk
(11, 15),  -- Local Cheese
(12, 45),  -- Bibingka
(13, 55),  -- Puto
(14, 10),  -- Kakanin Set
(15, 30),  -- Farm Fresh Adobo
(16, 25),  -- Vegetable Stir Fry
(17, 20),  -- Farm Fresh Sinigang
(18, 40),  -- Organic Rice
(19, 15),  -- Local Honey
(20, 35)   -- Organic Coffee
ON DUPLICATE KEY UPDATE `quantity` = VALUES(`quantity`);

-- Insert sample stock movements for demonstration
INSERT INTO `stock_movements` (`product_id`, `movement_type`, `quantity`, `unit_price`, `total_amount`, `supplier_id`, `notes`) VALUES
(1, 'in', 100, 25.00, 2500.00, NULL, 'Initial stock'),
(2, 'in', 150, 20.00, 3000.00, NULL, 'Initial stock'),
(3, 'in', 80, 15.00, 1200.00, NULL, 'Initial stock'),
(4, 'in', 120, 18.00, 2160.00, NULL, 'Initial stock'),
(5, 'in', 60, 45.00, 2700.00, NULL, 'Initial stock'),
(6, 'in', 200, 25.00, 5000.00, NULL, 'Initial stock'),
(7, 'in', 50, 40.00, 2000.00, NULL, 'Initial stock'),
(8, 'in', 100, 30.00, 3000.00, NULL, 'Initial stock'),
(9, 'in', 40, 75.00, 3000.00, NULL, 'Initial stock'),
(10, 'in', 70, 45.00, 3150.00, NULL, 'Initial stock'),
(11, 'in', 30, 100.00, 3000.00, NULL, 'Initial stock'),
(12, 'in', 90, 25.00, 2250.00, NULL, 'Initial stock'),
(13, 'in', 110, 20.00, 2200.00, NULL, 'Initial stock'),
(14, 'in', 20, 100.00, 2000.00, NULL, 'Initial stock'),
(15, 'in', 60, 75.00, 4500.00, NULL, 'Initial stock'),
(16, 'in', 50, 65.00, 3250.00, NULL, 'Initial stock'),
(17, 'in', 40, 80.00, 3200.00, NULL, 'Initial stock'),
(18, 'in', 80, 125.00, 10000.00, NULL, 'Initial stock'),
(19, 'in', 30, 100.00, 3000.00, NULL, 'Initial stock'),
(20, 'in', 70, 150.00, 10500.00, NULL, 'Initial stock'); 