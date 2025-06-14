-- Update database schema to include discount fields
-- Run this SQL to add discount functionality to your existing database

USE bauapp_db;

-- Add discount fields to orders table
ALTER TABLE orders 
ADD COLUMN discount_type VARCHAR(20) DEFAULT NULL COMMENT 'PWD or SC',
ADD COLUMN discount_name VARCHAR(100) DEFAULT NULL COMMENT 'Customer name for discount',
ADD COLUMN discount_id VARCHAR(50) DEFAULT NULL COMMENT 'Customer ID number for discount';

-- Add discount fields to order_items table
ALTER TABLE order_items 
ADD COLUMN is_pwd_discounted BOOLEAN DEFAULT FALSE COMMENT 'Whether this item has PWD/SC discount applied',
ADD COLUMN discounted_price DECIMAL(10,2) DEFAULT NULL COMMENT 'Price after discount is applied';

-- Update existing orders to have default values
UPDATE orders SET 
    discount_type = NULL,
    discount_name = NULL,
    discount_id = NULL
WHERE discount_type IS NULL;

-- Update existing order_items to have default values
UPDATE order_items SET 
    is_pwd_discounted = FALSE,
    discounted_price = price
WHERE is_pwd_discounted IS NULL;

-- Add indexes for better performance
CREATE INDEX idx_orders_discount_type ON orders(discount_type);
CREATE INDEX idx_order_items_discounted ON order_items(is_pwd_discounted); 