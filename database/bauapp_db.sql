-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 10, 2025 at 03:52 PM
-- Server version: 10.4.25-MariaDB
-- PHP Version: 8.1.10

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `bauapp_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `category_id` int(11) NOT NULL,
  `category_name` varchar(50) NOT NULL,
  `category_type` enum('food','product') NOT NULL,
  `image_url` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`category_id`, `category_name`, `category_type`, `image_url`) VALUES
(1, 'Vegetables Corner', 'product', 'images/categories/vegetables.jpg'),
(2, 'Fruits Corner', 'product', 'images/categories/fruits.jpg'),
(3, 'Dairy Products', 'product', 'images/categories/dairy.jpg'),
(4, 'Local Delicacies', 'food', 'images/categories/delicacies.jpg'),
(5, 'Farm Fresh Meals', 'food', 'images/categories/meals.jpg'),
(6, 'Organic Products', 'product', 'images/categories/organic.jpg');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `order_id` int(11) NOT NULL,
  `order_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `total_amount` decimal(10,2) NOT NULL,
  `status` varchar(20) DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `item_id` int(11) NOT NULL,
  `order_id` int(11) DEFAULT NULL,
  `product_id` int(11) DEFAULT NULL,
  `quantity` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `product_id` int(11) NOT NULL,
  `product_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `category_id` int(11) DEFAULT NULL,
  `image_url` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`product_id`, `product_name`, `description`, `price`, `category_id`, `image_url`, `created_at`) VALUES
(1, 'Fresh Lettuce', 'Crisp and fresh lettuce from local farms', '49.99', 1, 'images/products/lettuce.jpg', '2025-05-10 03:56:18'),
(2, 'Organic Tomatoes', 'Fresh organic tomatoes', '39.99', 1, 'images/products/tomatoes.jpg', '2025-05-10 03:56:18'),
(3, 'Green Beans', 'Fresh green beans', '29.99', 1, 'images/products/greenbeans.jpg', '2025-05-10 03:56:18'),
(4, 'Carrots', 'Fresh local carrots', '34.99', 1, 'images/products/carrots.jpg', '2025-05-10 03:56:18'),
(5, 'Fresh Mangoes', 'Sweet local mangoes', '89.99', 2, 'images/products/mangoes.jpg', '2025-05-10 03:56:18'),
(6, 'Organic Bananas', 'Fresh organic bananas', '49.99', 2, 'images/products/bananas.jpg', '2025-05-10 03:56:18'),
(7, 'Fresh Apples', 'Imported fresh apples', '79.99', 2, 'images/products/apples.jpg', '2025-05-10 03:56:18'),
(8, 'Local Oranges', 'Sweet local oranges', '59.99', 2, 'images/products/oranges.jpg', '2025-05-10 03:56:18'),
(9, 'Farm Fresh Eggs', 'Dozen of farm fresh eggs', '149.99', 3, 'images/products/eggs.jpg', '2025-05-10 03:56:18'),
(10, 'Fresh Milk', 'Fresh cow milk', '89.99', 3, 'images/products/milk.jpg', '2025-05-10 03:56:18'),
(11, 'Local Cheese', 'Fresh local cheese', '199.99', 3, 'images/products/cheese.jpg', '2025-05-10 03:56:18'),
(12, 'Bibingka', 'Traditional rice cake', '49.99', 4, 'images/products/bibingka.jpg', '2025-05-10 03:56:18'),
(13, 'Puto', 'Steamed rice cake', '39.99', 4, 'images/products/puto.jpg', '2025-05-10 03:56:18'),
(14, 'Kakanin Set', 'Assorted rice cakes', '199.99', 4, 'images/products/kakanin.jpg', '2025-05-10 03:56:18'),
(15, 'Farm Fresh Adobo', 'Traditional chicken adobo', '149.99', 5, 'images/products/adobo.jpg', '2025-05-10 03:56:18'),
(16, 'Vegetable Stir Fry', 'Fresh vegetable stir fry', '129.99', 5, 'images/products/stirfry.jpg', '2025-05-10 03:56:18'),
(17, 'Farm Fresh Sinigang', 'Traditional sour soup', '159.99', 5, 'images/products/sinigang.jpg', '2025-05-10 03:56:18'),
(18, 'Organic Rice', 'Premium quality organic rice', '249.99', 6, 'images/products/rice.jpg', '2025-05-10 03:56:18'),
(19, 'Local Honey', 'Pure honey from local beekeepers', '199.99', 6, 'images/products/honey.jpg', '2025-05-10 03:56:18'),
(20, 'Organic Coffee', 'Fresh ground organic coffee', '299.99', 6, 'images/products/coffee.jpg', '2025-05-10 03:56:18');

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('cashier','chef') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `username`, `password`, `role`) VALUES
(1, 'chef', '$2y$10$8K1p/a0dR1Ux5Y5Y5Y5Y5O5Y5Y5Y5Y5Y5Y5Y5Y5Y5Y5Y5Y5Y5Y', 'chef');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`category_id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`order_id`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`item_id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `order_items_ibfk_1` (`order_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`product_id`),
  ADD KEY `category_id` (`category_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `category_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `order_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `item_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=47;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `product_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`);

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`category_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
