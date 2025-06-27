<?php
include 'includes/database.php';

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get categories
$categories_sql = "SELECT * FROM categories";
$categories_result = $conn->query($categories_sql);

// Get best sellers
$best_sellers_sql = "SELECT p.*, c.category_name, c.category_type, 
                     COUNT(oi.product_id) as order_count
                     FROM products p 
                     JOIN categories c ON p.category_id = c.category_id
                     LEFT JOIN order_items oi ON p.product_id = oi.product_id
                     GROUP BY p.product_id
                     ORDER BY order_count DESC
                     LIMIT 4";
$best_sellers_result = $conn->query($best_sellers_sql);

// Get products for selected category
$selected_category = isset($_GET['category']) ? $_GET['category'] : null;
$products_sql = "SELECT p.*, c.category_name, c.category_type, COALESCE(i.quantity, 0) as inventory_quantity, i.updated_at as inventory_updated_at
                 FROM products p 
                 JOIN categories c ON p.category_id = c.category_id
                 LEFT JOIN inventory i ON p.product_id = i.product_id";
if ($selected_category) {
    $products_sql .= " WHERE p.category_id = " . intval($selected_category);
}
$products_result = $conn->query($products_sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BauApp - Ordering System</title>
    <link href="img/bau.jpg" rel="icon">
    <link rel="stylesheet" href="css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        @media print {
            .receipt-actions {
                display: none !important;
            }
            button, .btn, .add-to-cart-btn, .quantity-btn, .checkout-btn, .print-btn, .add-order-btn, .close-btn, .view-cart-btn, .admin-btn, .logout-btn {
                display: none !important;
            }
        }

        .best-sellers-section {
            background: white;
            padding: 1.5rem;
            border-radius: 15px;
            box-shadow: var(--card-shadow);
            margin-bottom: 2rem;
            animation: fadeIn 0.5s ease-out;
        }

        .best-sellers-section h2 {
            color: var(--text-color);
            margin-bottom: 1.5rem;
            font-size: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .best-sellers-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
        }

        .best-seller-card {
            background: white;
            border-radius: 10px;
            padding: 1rem;
            box-shadow: var(--card-shadow);
            transition: all 0.3s ease;
            border: 1px solid #eee;
            position: relative;
            overflow: hidden;
        }

        .best-seller-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--hover-shadow);
        }

        .best-seller-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            background: var(--accent-color);
            color: white;
            padding: 0.3rem 0.6rem;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 0.3rem;
        }

        .best-seller-icon {
            width: 100%;
            height: 100px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--background-color);
            border-radius: 8px;
            margin-bottom: 1rem;
            overflow: hidden;
        }

        .best-seller-icon img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 8px;
        }

        .best-seller-icon i {
            font-size: 3rem;
            color: var(--primary-color);
        }

        .best-seller-info h3 {
            color: var(--text-color);
            font-size: 1.1rem;
            margin-bottom: 0.5rem;
        }

        .best-seller-info p {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 0.8rem;
        }

        .best-seller-price {
            color: var(--primary-color);
            font-size: 1.2rem;
            font-weight: bold;
            margin-bottom: 1rem;
        }

        .best-seller-stats {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: #666;
            font-size: 0.9rem;
        }

        .best-seller-stats i {
            color: var(--accent-color);
        }

        @media (max-width: 768px) {
            .best-sellers-grid {
                grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            }
        }

        .best-sellers-btn {
            background: var(--primary-color);
            color: white;
            padding: 0.8rem 1.5rem;
            border: none;
            border-radius: 25px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: 500;
            transition: all var(--transition-speed);
        }

        .best-sellers-btn:hover {
            transform: translateY(-2px);
            box-shadow: var(--hover-shadow);
        }

        .best-sellers-btn.active {
            background: var(--accent-color);
        }

        .best-sellers-section {
            display: none;
            background: white;
            padding: 1.5rem;
            border-radius: 15px;
            box-shadow: var(--card-shadow);
            margin-bottom: 2rem;
            animation: fadeIn 0.5s ease-out;
        }

        .best-sellers-section.active {
            display: block;
        }

        @media (max-width: 768px) {
            .header-buttons {
                flex-direction: column;
                width: 100%;
            }

            .best-sellers-btn {
                width: 100%;
                justify-content: center;
            }
        }

        .header-buttons {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .view-cart-btn,
        .best-sellers-btn,
        .admin-btn {
            background: var(--accent-color);
            color: white;
            padding: 0.8rem 1.5rem;
            border: none;
            border-radius: 25px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: 500;
            transition: all var(--transition-speed);
            text-decoration: none;
            font-size: 1rem;
            min-width: 140px;
            justify-content: center;
        }

        .view-cart-btn:hover,
        .best-sellers-btn:hover,
        .admin-btn:hover {
            transform: translateY(-2px);
            box-shadow: var(--hover-shadow);
            color: white;
        }

        .view-cart-btn i,
        .best-sellers-btn i,
        .admin-btn i {
            font-size: 1.1rem;
            width: 20px;
            text-align: center;
        }

        .best-sellers-btn.active {
            background: var(--primary-color);
        }

        @media (max-width: 768px) {
            .header-buttons {
                flex-direction: column;
                width: 100%;
            }

            .view-cart-btn,
            .best-sellers-btn,
            .admin-btn {
                width: 100%;
            }
        }

        .logout-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.95);
            z-index: 9999;
            justify-content: center;
            align-items: center;
            flex-direction: column;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .logout-overlay.active {
            opacity: 1;
        }

        .logout-container {
            position: relative;
            width: 120px;
            height: 120px;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .logout-leaf {
            position: absolute;
            font-size: 2.5rem;
            color: var(--primary-color);
            opacity: 0;
            transform: scale(0.5);
            transition: all 0.3s ease;
        }

        .logout-leaf.main {
            font-size: 3.5rem;
            animation: mainLeafSpin 2s infinite ease-in-out;
            opacity: 1;
            transform: scale(1);
        }

        .logout-leaf.orbit {
            animation: orbitLeaf 3s infinite linear;
        }

        .logout-leaf.orbit:nth-child(2) {
            animation-delay: -1s;
        }

        .logout-leaf.orbit:nth-child(3) {
            animation-delay: -2s;
        }

        .logout-text {
            margin-top: 2rem;
            color: var(--primary-color);
            font-size: 1.2rem;
            font-weight: 500;
            opacity: 0;
            transform: translateY(10px);
            transition: all 0.3s ease;
        }

        .logout-text.active {
            opacity: 1;
            transform: translateY(0);
        }

        .logout-dots {
            display: inline-block;
            animation: loadingDots 1.5s infinite;
        }

        @keyframes mainLeafSpin {
            0% {
                transform: rotate(0deg) scale(1);
            }
            50% {
                transform: rotate(180deg) scale(1.1);
            }
            100% {
                transform: rotate(360deg) scale(1);
            }
        }

        @keyframes orbitLeaf {
            0% {
                transform: rotate(0deg) translateX(40px) rotate(0deg) scale(0.8);
                opacity: 0.6;
            }
            100% {
                transform: rotate(360deg) translateX(40px) rotate(-360deg) scale(0.8);
                opacity: 0.6;
            }
        }

        @keyframes loadingDots {
            0%, 20% {
                content: '.';
            }
            40% {
                content: '..';
            }
            60%, 100% {
                content: '...';
            }
        }

        /* Bigger inventory info */
        .inventory-info {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 0.8rem;
            margin: 0.6rem 0;
            border-left: 4px solid #dee2e6;
        }

        .stock-label {
            display: block;
            font-size: 0.85rem;
            color: #6c757d;
            font-weight: 500;
            margin-bottom: 0.3rem;
        }

        .stock-quantity {
            display: inline-block;
            font-size: 0.9rem;
            font-weight: bold;
            padding: 0.2rem 0.6rem;
            border-radius: 12px;
            margin-right: 0.3rem;
        }

        .stock-quantity.low-stock {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .stock-quantity.medium-stock {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }

        .stock-quantity.high-stock {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .stock-quantity.out-of-stock-badge {
            background: #dc3545;
            color: white;
            border: 1px solid #dc3545;
        }

        .out-of-stock-message {
            display: block;
            color: #dc3545;
            font-weight: 600;
            font-size: 0.8rem;
            margin-top: 0.3rem;
            text-align: center;
        }

        .out-of-stock-message i {
            margin-right: 0.4rem;
        }

        .stock-updated {
            display: block;
            font-size: 0.8rem;
            color: #6c757d;
            margin-top: 0.4rem;
            font-style: italic;
        }

        /* Update product card to accommodate inventory info */
        .product-card {
            background: white;
            border-radius: 12px;
            padding: 1rem;
            box-shadow: var(--card-shadow);
            transition: all 0.3s ease;
            border: 1px solid #eee;
            display: flex;
            flex-direction: column;
            min-height: 220px;
            max-width: 180px;
        }

        .product-card:hover {
            transform: translateY(-3px);
            box-shadow: var(--hover-shadow);
        }

        .product-card .product-icon {
            width: 100%;
            height: 80px;
            margin: 0 auto 0.8rem;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--background-color);
            border-radius: 8px;
            overflow: hidden;
        }

        .product-card .product-icon img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 8px;
        }

        .product-card .product-icon i {
            font-size: 2rem;
            color: var(--primary-color);
        }

        .product-card h3 {
            font-size: 1rem;
            margin-bottom: 0.4rem;
            line-height: 1.2;
        }

        .product-card p {
            font-size: 0.85rem;
            margin-bottom: 0.5rem;
            line-height: 1.2;
            color: #666;
        }

        .product-card .price {
            font-size: 1.1rem;
            font-weight: bold;
            color: var(--primary-color);
            margin-bottom: 0.5rem;
        }

        /* Out of Stock Product Card Styles */
        .product-card.out-of-stock {
            opacity: 0.7;
            position: relative;
        }

        .product-card.out-of-stock::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(220, 53, 69, 0.1);
            border-radius: 15px;
            pointer-events: none;
        }

        .quantity-controls.disabled {
            opacity: 0.5;
            pointer-events: none;
        }

        .quantity-controls.disabled .quantity-btn {
            background: #6c757d;
            cursor: not-allowed;
        }

        .add-to-cart-btn.disabled {
            background: #6c757d;
            cursor: not-allowed;
            opacity: 0.7;
        }

        .add-to-cart-btn.disabled:hover {
            transform: none;
            box-shadow: none;
        }

        /* Bigger quantity controls */
        .quantity-controls {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.6rem;
            margin: 0.6rem 0;
        }

        .quantity-btn {
            width: 32px;
            height: 32px;
            border: none;
            border-radius: 50%;
            background: var(--accent-color);
            color: white;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.9rem;
            transition: all 0.2s ease;
        }

        .quantity-btn:hover:not(:disabled) {
            transform: scale(1.1);
            background: var(--primary-color);
        }

        .quantity-btn:disabled {
            background: #6c757d;
            cursor: not-allowed;
            opacity: 0.6;
        }

        .quantity-btn:disabled:hover {
            transform: none;
            background: #6c757d;
        }

        .quantity-controls span {
            font-weight: bold;
            min-width: 24px;
            text-align: center;
            font-size: 1rem;
        }

        .quantity-controls.at-limit .quantity-btn[onclick*="increaseQuantity"] {
            background: #6c757d;
            cursor: not-allowed;
            opacity: 0.6;
        }

        .quantity-controls.at-limit .quantity-btn[onclick*="increaseQuantity"]:hover {
            transform: none;
            background: #6c757d;
        }

        /* Bigger add to cart button */
        .add-to-cart-btn {
            background: var(--primary-color);
            color: white;
            border: none;
            border-radius: 20px;
            padding: 0.7rem 1rem;
            cursor: pointer;
            font-size: 0.9rem;
            font-weight: 500;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.3rem;
            margin-top: auto;
        }

        .add-to-cart-btn:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: var(--hover-shadow);
            background: var(--accent-color);
        }

        .add-to-cart-btn i {
            font-size: 0.9rem;
        }

        /* Update products grid for bigger cards */
        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
            gap: 1rem;
            padding: 1.5rem 0;
        }

        @media (max-width: 768px) {
            .products-grid {
                grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
                gap: 0.8rem;
            }
            
            .product-card {
                min-height: 200px;
                max-width: 150px;
                padding: 0.8rem;
            }
        }

        /* Compact Category Card Styles to match Product Cards */
        .category-card {
            display: block;
            text-decoration: none;
            color: var(--text-color);
            background: white;
            border-radius: 12px;
            padding: 1rem;
            transition: all 0.3s ease;
            border: 1px solid #eee;
            position: relative;
            overflow: hidden;
            min-height: 220px;
            max-width: 180px;
        }

        .category-card:hover {
            transform: translateY(-3px);
            box-shadow: var(--hover-shadow);
            border-color: var(--primary-color);
        }

        .category-card .category-icon {
            width: 100%;
            height: 80px;
            margin: 0 auto 0.8rem;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--background-color);
            border-radius: 8px;
            overflow: hidden;
        }

        .category-card .category-icon img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 8px;
        }

        .category-card .category-icon i {
            font-size: 2rem;
            color: var(--primary-color);
        }

        .category-card h3 {
            font-size: 1rem;
            margin-bottom: 0.4rem;
            line-height: 1.2;
            text-align: center;
        }

        .category-card .category-type {
            display: block;
            padding: 0.3rem 0.6rem;
            background: var(--background-color);
            border-radius: 12px;
            font-size: 0.8rem;
            color: var(--primary-color);
            text-align: center;
            margin-top: 0.5rem;
        }

        .category-card.active {
            border-color: var(--primary-color);
            background: rgba(var(--primary-color-rgb), 0.05);
        }

        .category-card.active .category-icon {
            background: var(--primary-color);
        }

        .category-card.active .category-icon i {
            color: white;
        }

        /* Update categories grid to match products grid */
        .categories-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
            gap: 1rem;
            padding: 1.5rem 0;
        }

        @media (max-width: 768px) {
            .categories-grid {
                grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
                gap: 0.8rem;
            }
            
            .category-card {
                min-height: 200px;
                max-width: 150px;
                padding: 0.8rem;
            }
        }

        /* Smooth Transitions and Loading States */
        .products-grid {
            transition: all 0.3s ease-in-out;
        }

        .loading-products {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
            color: var(--primary-color);
            font-size: 1rem;
            gap: 0.5rem;
        }

        .loading-products i {
            font-size: 1.2rem;
        }

        .category-card {
            transition: all 0.3s ease-in-out;
        }

        .category-card.active {
            transform: scale(1.05);
            border-color: var(--primary-color);
            background: rgba(var(--primary-color-rgb), 0.05);
            box-shadow: 0 4px 12px rgba(var(--primary-color-rgb), 0.2);
        }

        .products-section {
            transition: all 0.3s ease-in-out;
        }

        .products-section h2 {
            transition: all 0.3s ease-in-out;
        }

        /* Search Styles */
        .search-container {
            position: relative;
            flex: 1;
            max-width: 400px;
            margin-right: 1rem;
        }

        .search-box {
            position: relative;
            display: flex;
            align-items: center;
            background: white;
            border-radius: 25px;
            padding: 0.5rem 1rem;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }

        .search-box:focus-within {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            transform: translateY(-1px);
        }

        .search-icon {
            color: #666;
            margin-right: 0.5rem;
            font-size: 1rem;
        }

        #search-input {
            flex: 1;
            border: none;
            outline: none;
            font-size: 0.9rem;
            color: var(--text-color);
            background: transparent;
        }

        #search-input::placeholder {
            color: #999;
        }

        .clear-search-btn {
            background: none;
            border: none;
            color: #666;
            cursor: pointer;
            padding: 0.3rem;
            border-radius: 50%;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .clear-search-btn:hover {
            background: #f0f0f0;
            color: #333;
        }

        .search-results {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
            max-height: 400px;
            overflow-y: auto;
            z-index: 1000;
            display: none;
            margin-top: 0.5rem;
            border: 1px solid #ddd;
        }

        .search-results.active {
            display: block !important;
            animation: slideDown 0.3s ease-out;
        }

        .search-result-item {
            display: flex;
            align-items: center;
            padding: 0.8rem 1rem;
            border-bottom: 1px solid #f0f0f0;
            cursor: pointer;
            transition: all 0.2s ease;
            color: #000000;
        }

        .search-result-item:last-child {
            border-bottom: none;
        }

        .search-result-item:hover {
            background: #f8f9fa;
            color: #000000;
        }

        .search-result-item.selected {
            background: var(--primary-color);
            color: white;
        }

        .search-result-icon {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            background: var(--background-color);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 0.8rem;
            flex-shrink: 0;
        }

        .search-result-icon img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 8px;
        }

        .search-result-icon i {
            font-size: 1.2rem;
            color: var(--primary-color);
        }

        .search-result-info {
            flex: 1;
            min-width: 0;
            color: #000000;
        }

        .search-result-name {
            font-weight: 500;
            margin-bottom: 0.2rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            color: #000000;
        }

        .search-result-category {
            font-size: 0.8rem;
            color: #333333;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .search-result-price {
            font-weight: bold;
            color: var(--primary-color);
            margin-left: 0.5rem;
        }

        .search-result-stock {
            font-size: 0.8rem;
            color: #333333;
            margin-left: 0.5rem;
        }

        .no-search-results {
            padding: 1rem;
            text-align: center;
            color: #333333;
            font-style: italic;
        }

        .search-highlight {
            background: #fff3cd;
            padding: 0.1rem 0.2rem;
            border-radius: 3px;
            color: #000000;
        }

        /* Search highlight effect for product cards */
        .product-card.search-highlighted {
            animation: searchPulse 5s ease-in-out;
            border: 3px solid var(--primary-color);
            box-shadow: 0 0 30px rgba(46, 125, 50, 0.6);
            transform: scale(1.05);
            z-index: 10;
            position: relative;
        }

        @keyframes searchPulse {
            0% {
                transform: scale(1.05);
                box-shadow: 0 0 30px rgba(46, 125, 50, 0.6);
            }
            50% {
                transform: scale(1.08);
                box-shadow: 0 0 40px rgba(46, 125, 50, 0.8);
            }
            100% {
                transform: scale(1.05);
                box-shadow: 0 0 30px rgba(46, 125, 50, 0.6);
            }
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Responsive search */
        @media (max-width: 768px) {
            .header-actions {
                flex-direction: column;
                gap: 1rem;
            }

            .search-container {
                max-width: 100%;
                margin-right: 0;
                order: 1;
            }

            .cart-summary {
                order: 2;
            }

            .header-buttons {
                order: 3;
                flex-direction: row;
                justify-content: center;
            }
        }

        /* Discount Modal Styles */
        .discount-content {
            background: white;
            border-radius: 15px;
            max-width: 800px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            animation: slideInUp 0.3s ease-out;
        }

        @keyframes slideInUp {
            from {
                opacity: 0;
                transform: translateY(50px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .discount-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1.5rem;
            border-bottom: 2px solid var(--primary-color);
            background: linear-gradient(135deg, var(--primary-color), var(--accent-color));
            color: white;
            border-radius: 15px 15px 0 0;
        }

        .discount-header h2 {
            margin: 0;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 1.5rem;
        }

        .discount-body {
            padding: 1.5rem;
        }

        .customer-info-section,
        .discount-products-section,
        .discount-summary {
            margin-bottom: 2rem;
        }

        .customer-info-section h3,
        .discount-products-section h3,
        .discount-summary h3 {
            color: var(--text-color);
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 1.2rem;
        }

        .form-group {
            margin-bottom: 1rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--text-color);
            font-weight: 500;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 0.8rem;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(46, 125, 50, 0.1);
        }

        .discount-products-list {
            max-height: 300px;
            overflow-y: auto;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 1rem;
        }

        .discount-product-item {
            display: flex;
            align-items: center;
            padding: 0.8rem;
            border: 1px solid #eee;
            border-radius: 8px;
            margin-bottom: 0.5rem;
            transition: all 0.3s ease;
        }

        .discount-product-item:hover {
            background: var(--background-color);
            border-color: var(--primary-color);
        }

        .discount-product-item.selected {
            background: rgba(46, 125, 50, 0.1);
            border-color: var(--primary-color);
        }

        .discount-product-checkbox {
            margin-right: 1rem;
            transform: scale(1.2);
        }

        .discount-product-info {
            flex: 1;
        }

        .discount-product-name {
            font-weight: 500;
            color: var(--text-color);
            margin-bottom: 0.2rem;
        }

        .discount-product-details {
            font-size: 0.9rem;
            color: #666;
        }

        .discount-product-price {
            font-weight: bold;
            color: var(--primary-color);
            margin-left: 1rem;
        }

        /* New quantity controls for discount */
        .discount-product-controls {
            display: flex;
            align-items: center;
            gap: 1rem;
            flex-shrink: 0;
        }

        .discount-quantity-controls {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.5rem;
        }

        .discount-quantity-controls label {
            font-size: 0.8rem;
            color: var(--text-color);
            font-weight: 500;
            margin: 0;
        }

        .quantity-input-group {
            display: flex;
            align-items: center;
            gap: 0.3rem;
            background: white;
            border: 2px solid #ddd;
            border-radius: 8px;
            padding: 0.2rem;
        }

        .discount-qty-btn {
            width: 28px;
            height: 28px;
            border: none;
            border-radius: 6px;
            background: var(--accent-color);
            color: white;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.8rem;
            transition: all 0.2s ease;
        }

        .discount-qty-btn:hover {
            background: var(--primary-color);
            transform: scale(1.1);
        }

        .discount-qty-btn:disabled {
            background: #6c757d;
            cursor: not-allowed;
            opacity: 0.6;
        }

        .discount-qty-input {
            width: 50px;
            height: 28px;
            border: none;
            text-align: center;
            font-size: 0.9rem;
            font-weight: 500;
            color: var(--text-color);
            background: transparent;
        }

        .discount-qty-input:focus {
            outline: none;
        }

        .discount-qty-input::-webkit-inner-spin-button,
        .discount-qty-input::-webkit-outer-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }

        .discount-qty-input[type=number] {
            -moz-appearance: textfield;
        }

        .discount-summary {
            background: var(--background-color);
            padding: 1rem;
            border-radius: 8px;
            border: 2px solid var(--primary-color);
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 0.5rem 0;
            border-bottom: 1px solid #ddd;
        }

        .summary-row:last-child {
            border-bottom: none;
        }

        .summary-row.total {
            font-size: 1.2rem;
            font-weight: bold;
            color: var(--primary-color);
            border-top: 2px solid var(--primary-color);
            margin-top: 0.5rem;
            padding-top: 0.5rem;
        }

        .discount-actions {
            display: flex;
            gap: 1rem;
            padding: 1.5rem;
            border-top: 1px solid #ddd;
            background: #f8f9fa;
            border-radius: 0 0 15px 15px;
        }

        .apply-discount-btn,
        .cancel-btn {
            flex: 1;
            padding: 1rem;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
        }

        .apply-discount-btn {
            background: var(--primary-color);
            color: white;
        }

        .apply-discount-btn:hover {
            background: var(--accent-color);
            transform: translateY(-2px);
            box-shadow: var(--hover-shadow);
        }

        .cancel-btn {
            background: #6c757d;
            color: white;
        }

        .cancel-btn:hover {
            background: #5a6268;
            transform: translateY(-2px);
            box-shadow: var(--hover-shadow);
        }

        /* Cart Actions Styles */
        .cart-actions {
            display: flex;
            flex-direction: column;
            gap: 0.8rem;
            margin-top: 1rem;
        }

        .discount-btn {
            background: var(--accent-color);
            color: white;
            border: none;
            border-radius: 8px;
            padding: 0.8rem;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
        }

        .discount-btn:hover {
            background: var(--primary-color);
            transform: translateY(-2px);
            box-shadow: var(--hover-shadow);
        }

        .discount-btn.active {
            background: var(--primary-color);
        }

        /* Responsive Discount Modal */
        @media (max-width: 768px) {
            .discount-content {
                width: 95%;
                max-height: 95vh;
            }

            .discount-header {
                padding: 1rem;
            }

            .discount-header h2 {
                font-size: 1.2rem;
            }

            .discount-body {
                padding: 1rem;
            }

            .discount-actions {
                flex-direction: column;
                padding: 1rem;
            }

            .apply-discount-btn,
            .cancel-btn {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="logout-overlay">
        <div class="logout-container">
            <i class="fas fa-leaf logout-leaf main"></i>
            <i class="fas fa-leaf logout-leaf orbit"></i>
            <i class="fas fa-leaf logout-leaf orbit"></i>
            <i class="fas fa-leaf logout-leaf orbit"></i>
        </div>
        <div class="logout-text">Going Back<span class="logout-dots">...</span></div>
    </div>
    <div class="dashboard-container">
        <header>
            <div class="header-top">
                <h1><i class="fas fa-leaf"></i> BauApp Ordering System</h1>
                <a href="index.php" class="logout-btn">
                    <i class="fas fa-power-off"></i>
                    <span>Back</span>
                </a>
            </div>
            <div class="header-actions">
                <div class="search-container">
                    <div class="search-box">
                        <i class="fas fa-search search-icon"></i>
                        <input type="text" id="search-input" placeholder="Search products..." autocomplete="off">
                        <button type="button" id="clear-search" class="clear-search-btn" style="display: none;">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <div id="search-results" class="search-results"></div>
                </div>
                <div class="cart-summary">
                    <i class="fas fa-shopping-cart"></i>
                    <span id="cart-count">0</span> items
                    <span id="cart-total">₱0.00</span>
                </div>
                <div class="header-buttons">
                    
                    <button onclick="toggleBestSellers()" class="best-sellers-btn">
                        <i class="fas fa-crown"></i> Best Sellers
                    </button>
                    <a href="admin_orders.php" class="admin-btn">
                        <i class="fas fa-shopping-bag"></i> View Orders
                    </a>
                </div>
            </div>
        </header>

        <main>
            <?php if (!$selected_category): ?>
            <div class="best-sellers-section">
                <h2><i class="fas fa-crown"></i> Best Sellers</h2>
                <div class="best-sellers-grid">
                    <?php
                    if ($best_sellers_result->num_rows > 0) {
                        $rank = 1;
                        while($product = $best_sellers_result->fetch_assoc()) {
                            echo '<div class="best-seller-card">';
                            echo '<div class="best-seller-badge"><i class="fas fa-fire"></i> #' . $rank . '</div>';
                            
                            // Display image if available, otherwise show icon
                            if (!empty($product['image_url']) && file_exists($product['image_url'])) {
                                echo '<div class="best-seller-icon">';
                                echo '<img src="' . htmlspecialchars($product['image_url']) . '" alt="' . htmlspecialchars($product['product_name']) . '" class="best-seller-image">';
                                echo '</div>';
                            } else {
                                echo '<div class="best-seller-icon"><i class="fas fa-' . ($product['category_type'] == 'vegetable' ? 'carrot' : 'apple-alt') . '"></i></div>';
                            }
                            
                            echo '<div class="best-seller-info">';
                            echo '<h3>' . $product['product_name'] . '</h3>';
                            echo '<p>' . $product['description'] . '</p>';
                            echo '<div class="best-seller-price">₱' . number_format($product['price'], 2) . '</div>';
                            echo '<div class="best-seller-stats">';
                            echo '<i class="fas fa-shopping-cart"></i> ' . $product['order_count'] . ' orders';
                            echo '</div>';
                            echo '</div>';
                            echo '</div>';
                            $rank++;
                        }
                    }
                    ?>
                </div>
            </div>
            <?php endif; ?>

            <div class="categories-section">
                <h2><i class="fas fa-tags"></i> Select Category</h2>
                <div class="categories-grid">
                    <?php
                    if ($categories_result->num_rows > 0) {
                        while($category = $categories_result->fetch_assoc()) {
                            $active_class = ($selected_category == $category['category_id']) ? 'active' : '';
                            echo '<a href="javascript:void(0)" onclick="selectCategory(' . $category['category_id'] . ', \'' . addslashes($category['category_name']) . '\')" class="category-card ' . $active_class . '" data-category-id="' . $category['category_id'] . '">';
                            
                            // Display image if available, otherwise show icon
                            if (!empty($category['image_url']) && file_exists($category['image_url'])) {
                                echo '<div class="category-icon">';
                                echo '<img src="' . htmlspecialchars($category['image_url']) . '" alt="' . htmlspecialchars($category['category_name']) . '" class="category-image">';
                                echo '</div>';
                            } else {
                                echo '<div class="category-icon"><i class="fas fa-' . ($category['category_type'] == 'vegetable' ? 'carrot' : 'apple-alt') . ' fa-3x"></i></div>';
                            }
                            
                            echo '<h3>' . $category['category_name'] . '</h3>';
                            echo '<span class="category-type"><i class="fas fa-' . ($category['category_type'] == 'vegetable' ? 'carrot' : 'apple-alt') . '"></i> ' . ucfirst($category['category_type']) . '</span>';
                            echo '</a>';
                        }
                    }
                    ?>
                </div>
            </div>

            <div class="products-section">
                <h2>
                    <?php if ($selected_category): ?>
                        <i class="fas fa-box"></i> Select Items
                    <?php else: ?>
                        <i class="fas fa-hand-pointer"></i> Please Select a Category
                    <?php endif; ?>
                </h2>
                <div class="products-grid">
                    <?php
                    if ($selected_category && $products_result->num_rows > 0) {
                        while($product = $products_result->fetch_assoc()) {
                            $is_out_of_stock = $product['inventory_quantity'] <= 0;
                            $card_class = $is_out_of_stock ? 'product-card out-of-stock' : 'product-card';
                            
                            echo '<div class="' . $card_class . '" data-product-id="' . $product['product_id'] . '">';
                            
                            // Display image if available, otherwise show icon
                            if (!empty($product['image_url']) && file_exists($product['image_url'])) {
                                echo '<div class="product-icon">';
                                echo '<img src="' . htmlspecialchars($product['image_url']) . '" alt="' . htmlspecialchars($product['product_name']) . '" class="product-image">';
                                echo '</div>';
                            } else {
                                echo '<div class="product-icon"><i class="fas fa-' . ($product['category_type'] == 'food' ? 'utensils' : 'shopping-bag') . ' fa-3x"></i></div>';
                            }
                            
                            echo '<h3>' . $product['product_name'] . '</h3>';
                            echo '<p>' . $product['description'] . '</p>';
                            echo '<p class="price">₱' . number_format($product['price'], 2) . '</p>';
                            
                            // Add inventory quantity display
                            $quantity_class = $product['inventory_quantity'] <= 10 ? 'low-stock' : ($product['inventory_quantity'] <= 30 ? 'medium-stock' : 'high-stock');
                            if ($is_out_of_stock) {
                                $quantity_class = 'out-of-stock-badge';
                            }
                            
                            echo '<div class="inventory-info">';
                            echo '<span class="stock-label">Available Stock:</span>';
                            echo '<span class="stock-quantity ' . $quantity_class . '">' . $product['inventory_quantity'] . ' Stocks</span>';
                            
                            if ($is_out_of_stock) {
                                echo '<div class="out-of-stock-message"><i class="fas fa-exclamation-triangle"></i> Out of Stock</div>';
                            }
                            
                            echo '</div>';
                            
                            if (!$is_out_of_stock) {
                                echo '<div class="quantity-controls">';
                                echo '<button onclick="decreaseQuantity(' . $product['product_id'] . ')" class="quantity-btn"><i class="fas fa-minus"></i></button>';
                                echo '<span id="quantity-' . $product['product_id'] . '">0</span>';
                                echo '<button onclick="increaseQuantity(' . $product['product_id'] . ')" class="quantity-btn"><i class="fas fa-plus"></i></button>';
                                echo '</div>';
                                echo '<button onclick="addToCart(' . $product['product_id'] . ', \'' . $product['product_name'] . '\', ' . $product['price'] . ')" class="add-to-cart-btn"><i class="fas fa-cart-plus"></i> Add to Cart</button>';
                            } else {
                                echo '<div class="quantity-controls disabled">';
                                echo '<button class="quantity-btn" disabled><i class="fas fa-minus"></i></button>';
                                echo '<span id="quantity-' . $product['product_id'] . '">0</span>';
                                echo '<button class="quantity-btn" disabled><i class="fas fa-plus"></i></button>';
                                echo '</div>';
                                echo '<button class="add-to-cart-btn disabled" disabled><i class="fas fa-ban"></i> Out of Stock</button>';
                            }
                            echo '</div>';
                        }
                    } elseif ($selected_category) {
                        echo '<p class="no-products"><i class="fas fa-box-open"></i> No products found in this category.</p>';
                    }
                    ?>
                </div>
            </div>

            <div class="cart-container">
                <h2><i class="fas fa-shopping-basket"></i> Your Order</h2>
                <div id="cart-items"></div>
                <div class="cart-total">
                    <h3>Total: <span id="total-amount">₱0.00</span></h3>
                </div>
                <div class="cart-actions">
                    <button onclick="openDiscountModal()" class="discount-btn" id="discount-btn" style="display: none;">
                        <i class="fas fa-percentage"></i> Add Discount
                    </button>
                    <button onclick="checkout()" class="checkout-btn"><i class="fas fa-credit-card"></i> Proceed to Checkout</button>
                </div>
            </div>
        </main>
    </div>

    <!-- Receipt Modal -->
    <div id="receipt-modal" class="modal">
        <div class="receipt">
            <div class="receipt-header">
                <h2>BauApp</h2>
                <p>Your Receipt</p>
                <p>Date: <span id="receipt-date"></span></p>
                <p>Time: <span id="receipt-time"></span></p>
            </div>
            <div class="receipt-items"></div>
            <div class="receipt-total"></div>
            <div class="receipt-footer">
                <p><i><h5>NOTE: NOT SERVE AS YOUR ORDER OFFICIAL RECEIPT</h5><i></p>
                <p><i><h5>FOR INVENTORY & PRICE LIST ONLY</h5></i></p>
                <br>
                <p>Thank you for your purchase!</p>
            </div>
            <div class="receipt-actions">
                <button onclick="printReceipt()" class="print-btn">
                    <i class="fas fa-print"></i> Print Receipt
                </button>
                <button onclick="addOrder()" class="add-order-btn">
                    <i class="fas fa-plus"></i> Add Order
                </button>
                <button onclick="closeReceipt()" class="close-btn">
                    <i class="fas fa-times"></i> Close
                </button>
            </div>
        </div>
    </div>

    <!-- Discount Modal -->
    <div id="discount-modal" class="modal">
        <div class="discount-content">
            <div class="discount-header">
                <h2><i class="fas fa-percentage"></i> Add Discount</h2>
                <button onclick="closeDiscountModal()" class="close-btn">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="discount-body">
                <div class="customer-info-section">
                    <h3><i class="fas fa-user"></i> Customer Information</h3>
                    <div class="form-group">
                        <label for="customer-type">Customer Type:</label>
                        <select id="customer-type" required>
                            <option value="">Select customer type</option>
                            <option value="PWD">PWD (Persons with Disabilities)</option>
                            <option value="SENIOR CITEZEN">SC (Senior Citizen)</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="customer-name">Full Name:</label>
                        <input type="text" id="customer-name" placeholder="Enter customer's full name" required>
                    </div>
                    <div class="form-group">
                        <label for="customer-id">ID Number:</label>
                        <input type="text" id="customer-id" placeholder="Enter customer's ID number" required>
                    </div>
                </div>
                
                <div class="discount-products-section">
                    <h3><i class="fas fa-tags"></i> Select Products for Discount</h3>
                    <div class="discount-products-list" id="discount-products-list">
                        <!-- Products will be populated here -->
                    </div>
                </div>
                
                <div class="discount-summary">
                    <h3><i class="fas fa-calculator"></i> Discount Summary</h3>
                    <div class="summary-row">
                        <span>Original Total:</span>
                        <span id="original-total">₱0.00</span>
                    </div>
                    <div class="summary-row">
                        <span>Discount Amount:</span>
                        <span id="discount-amount">₱0.00</span>
                    </div>
                    <div class="summary-row total">
                        <span>Final Total:</span>
                        <span id="final-total">₱0.00</span>
                    </div>
                </div>
            </div>
            <div class="discount-actions">
                <button onclick="applyDiscount()" class="apply-discount-btn">
                    <i class="fas fa-check"></i> Apply Discount
                </button>
                <button onclick="closeDiscountModal()" class="cancel-btn">
                    <i class="fas fa-times"></i> Cancel
                </button>
            </div>
        </div>
    </div>

    <script src="js/script.js"></script>
    <script>
        function toggleBestSellers() {
            const bestSellersSection = document.querySelector('.best-sellers-section');
            const bestSellersBtn = document.querySelector('.best-sellers-btn');
            const categoriesSection = document.querySelector('.categories-section');
            const productsSection = document.querySelector('.products-section');
            const cartContainer = document.querySelector('.cart-container');
            
            // Close cart if it's open
            if (cartContainer.classList.contains('expanded')) {
                toggleCart();
            }
            
            bestSellersSection.classList.toggle('active');
            bestSellersBtn.classList.toggle('active');
            
            if (bestSellersSection.classList.contains('active')) {
                categoriesSection.style.display = 'none';
                productsSection.style.display = 'none';
                bestSellersBtn.innerHTML = '<i class="fas fa-times"></i> Close Best Sellers';
                // Clear any selected category
                const url = new URL(window.location.href);
                url.searchParams.delete('category');
                window.history.pushState({}, '', url);
            } else {
                categoriesSection.style.display = 'block';
                productsSection.style.display = 'block';
                bestSellersBtn.innerHTML = '<i class="fas fa-crown"></i> Best Sellers';
            }
        }

        // Modify the existing toggleCart function
        function toggleCart() {
            const cartContainer = document.querySelector('.cart-container');
            const mainContent = document.querySelector('main');
            const bestSellersSection = document.querySelector('.best-sellers-section');
            
            if (cart.length === 0) {
                showNotification('Your cart is empty!', 'error');
                return;
            }

            if (cartContainer) {
                cartContainer.classList.toggle('expanded');
                
                // Update button text
                const viewCartBtn = document.querySelector('.view-cart-btn');
                if (viewCartBtn) {
                    viewCartBtn.innerHTML = cartContainer.classList.contains('expanded') 
                        ? '<i class="fas fa-times"></i> Close Cart' 
                        : '<i class="fas fa-shopping-cart"></i> View Cart';
                }

                // Hide best sellers when cart is expanded
                if (cartContainer.classList.contains('expanded')) {
                    bestSellersSection.classList.remove('active');
                    const bestSellersBtn = document.querySelector('.best-sellers-btn');
                    if (bestSellersBtn) {
                        bestSellersBtn.classList.remove('active');
                        bestSellersBtn.innerHTML = '<i class="fas fa-crown"></i> Best Sellers';
                    }
                }

                // Toggle main content layout
                if (mainContent) {
                    if (cartContainer.classList.contains('expanded')) {
                        mainContent.style.display = 'grid';
                        mainContent.style.gridTemplateColumns = '1fr 2fr 1fr';
                    } else {
                        mainContent.style.display = 'grid';
                        mainContent.style.gridTemplateColumns = '1fr 2fr';
                    }
                }
            }
        }

        // Add logout animation
        document.querySelector('.logout-btn').addEventListener('click', function(e) {
            e.preventDefault();
            const logoutOverlay = document.querySelector('.logout-overlay');
            const logoutText = document.querySelector('.logout-text');
            
            // Show logout overlay with fade effect
            logoutOverlay.style.display = 'flex';
            setTimeout(() => {
                logoutOverlay.classList.add('active');
                logoutText.classList.add('active');
            }, 50);
            
            // Redirect after animation
            setTimeout(() => {
                window.location.href = 'index.php';
            }, 2000);
        });

        // Category selection with smooth transitions
        function selectCategory(categoryId, categoryName) {
            // Update URL without page refresh
            const url = new URL(window.location.href);
            url.searchParams.set('category', categoryId);
            window.history.pushState({}, '', url);

            // Update active category card
            document.querySelectorAll('.category-card').forEach(card => {
                card.classList.remove('active');
            });
            document.querySelector(`[data-category-id="${categoryId}"]`).classList.add('active');

            // Show loading state
            const productsSection = document.querySelector('.products-section');
            const productsGrid = document.querySelector('.products-grid');
            
            // Add fade out effect
            productsGrid.style.opacity = '0';
            productsGrid.style.transform = 'translateY(20px)';
            
            setTimeout(() => {
                // Show loading indicator
                productsGrid.innerHTML = '<div class="loading-products"><i class="fas fa-spinner fa-spin"></i> Loading products...</div>';
                productsGrid.style.opacity = '1';
                productsGrid.style.transform = 'translateY(0)';
                
                // Load products via AJAX
                loadProducts(categoryId, categoryName);
            }, 300);
        }

        // Category selection that shows only a specific product
        function selectCategoryAndShowProduct(categoryId, categoryName, selectedProduct) {
            // Update URL without page refresh
            const url = new URL(window.location.href);
            url.searchParams.set('category', categoryId);
            window.history.pushState({}, '', url);

            // Update active category card
            document.querySelectorAll('.category-card').forEach(card => {
                card.classList.remove('active');
            });
            document.querySelector(`[data-category-id="${categoryId}"]`).classList.add('active');

            // Show loading state
            const productsSection = document.querySelector('.products-section');
            const productsGrid = document.querySelector('.products-grid');
            
            // Add fade out effect
            productsGrid.style.opacity = '0';
            productsGrid.style.transform = 'translateY(20px)';
            
            setTimeout(() => {
                // Show loading indicator
                productsGrid.innerHTML = '<div class="loading-products"><i class="fas fa-spinner fa-spin"></i> Loading product...</div>';
                productsGrid.style.opacity = '1';
                productsGrid.style.transform = 'translateY(0)';
                
                // Load and display only the selected product
                loadSingleProduct(selectedProduct, categoryName);
            }, 300);
        }

        // AJAX function to load products
        function loadProducts(categoryId, categoryName) {
            const formData = new FormData();
            formData.append('category_id', categoryId);
            formData.append('action', 'load_products');

            fetch('load_products.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(html => {
                const productsGrid = document.querySelector('.products-grid');
                
                // Fade out loading
                productsGrid.style.opacity = '0';
                productsGrid.style.transform = 'translateY(20px)';
                
                setTimeout(() => {
                    // Update products grid
                    productsGrid.innerHTML = html;
                    
                    // Update section title
                    const productsSectionTitle = document.querySelector('.products-section h2');
                    productsSectionTitle.innerHTML = `<i class="fas fa-box"></i> ${categoryName}`;
                    
                    // Fade in new content
                    productsGrid.style.opacity = '1';
                    productsGrid.style.transform = 'translateY(0)';
                    
                    // Reinitialize quantity displays
                    Object.keys(quantities).forEach(productId => {
                        updateQuantityDisplay(productId);
                    });
                    
                    // Check if there's a pending product to highlight
                    if (window.pendingProductHighlight) {
                        setTimeout(() => {
                            highlightProductInGrid(window.pendingProductHighlight);
                            window.pendingProductHighlight = null;
                        }, 500);
                    }
                }, 200);
            })
            .catch(error => {
                console.error('Error loading products:', error);
                const productsGrid = document.querySelector('.products-grid');
                productsGrid.innerHTML = '<p class="no-products"><i class="fas fa-exclamation-triangle"></i> Error loading products. Please try again.</p>';
                productsGrid.style.opacity = '1';
                productsGrid.style.transform = 'translateY(0)';
            });
        }

        // Function to load and display only a single product
        function loadSingleProduct(product, categoryName) {
            console.log('loadSingleProduct called with:', product, categoryName);
            
            const productsGrid = document.querySelector('.products-grid');
            
            // Create HTML for the single product
            const productHtml = createProductCard(product);
            
            // Add "Show All Products" button - make sure category_id is available
            let categoryId = product.category_id;
            console.log('Category ID for show all button:', categoryId);
            
            // If category_id is not available, try to get it from the product data
            if (!categoryId) {
                // Try to get category_id from the category card
                const categoryCards = document.querySelectorAll('.category-card');
                for (let card of categoryCards) {
                    if (card.querySelector('h3').textContent === categoryName) {
                        categoryId = card.dataset.categoryId;
                        break;
                    }
                }
            }
            
            const showAllButton = `
                <div class="show-all-products-container" style="text-align: center; margin-top: 2rem;">
                    <button onclick="showAllProductsInCategory(${categoryId}, '${categoryName}')" class="show-all-btn" style="background: var(--accent-color); color: white; padding: 0.8rem 1.5rem; border: none; border-radius: 25px; cursor: pointer; font-weight: 500; display: inline-flex; align-items: center; gap: 0.5rem;">
                        <i class="fas fa-list"></i>
                        Show All Products in ${categoryName}
                    </button>
                </div>
            `;
            
            // Fade out loading
            productsGrid.style.opacity = '0';
            productsGrid.style.transform = 'translateY(20px)';
            
            setTimeout(() => {
                // Update products grid with only the selected product and show all button
                productsGrid.innerHTML = productHtml + showAllButton;
                
                // Update section title to show it's a search result
                const productsSectionTitle = document.querySelector('.products-section h2');
                productsSectionTitle.innerHTML = `<i class="fas fa-search"></i> Search Result: ${product.product_name}`;
                
                // Fade in new content
                productsGrid.style.opacity = '1';
                productsGrid.style.transform = 'translateY(0)';
                
                // Highlight the product
                setTimeout(() => {
                    highlightProductInGrid(product.product_id);
                }, 300);
                
                // Show success notification
                showNotification(`Showing: ${product.product_name}`, 'success');
            }, 200);
        }

        // Function to create a product card HTML
        function createProductCard(product) {
            const is_out_of_stock = product.is_out_of_stock;
            const card_class = is_out_of_stock ? 'product-card out-of-stock' : 'product-card';
            
            let html = `<div class="${card_class}" data-product-id="${product.product_id}">`;
            
            // Product icon
            if (product.image_url && product.image_url !== '') {
                html += `<div class="product-icon">
                    <img src="${product.image_url}" alt="${product.product_name}" class="product-image">
                </div>`;
            } else {
                html += `<div class="product-icon">
                    <i class="fas fa-${product.category_type === 'food' ? 'utensils' : 'shopping-bag'} fa-3x"></i>
                </div>`;
            }
            
            // Product info
            html += `<h3>${product.product_name}</h3>`;
            html += `<p>${product.description}</p>`;
            html += `<p class="price">₱${parseFloat(product.price).toFixed(2)}</p>`;
            
            // Inventory info
            const quantity_class = product.inventory_quantity <= 10 ? 'low-stock' : (product.inventory_quantity <= 30 ? 'medium-stock' : 'high-stock');
            const final_quantity_class = is_out_of_stock ? 'out-of-stock-badge' : quantity_class;
            
            html += `<div class="inventory-info">
                <span class="stock-label">Available Stock:</span>
                <span class="stock-quantity ${final_quantity_class}">${product.inventory_quantity} Stocks</span>`;
            
            if (is_out_of_stock) {
                html += `<div class="out-of-stock-message"><i class="fas fa-exclamation-triangle"></i> Out of Stock</div>`;
            }
            
            html += `</div>`;
            
            // Quantity controls and add to cart button
            if (!is_out_of_stock) {
                html += `<div class="quantity-controls">
                    <button onclick="decreaseQuantity(${product.product_id})" class="quantity-btn"><i class="fas fa-minus"></i></button>
                    <span id="quantity-${product.product_id}">0</span>
                    <button onclick="increaseQuantity(${product.product_id})" class="quantity-btn"><i class="fas fa-plus"></i></button>
                </div>`;
                html += `<button onclick="addToCart(${product.product_id}, '${product.product_name.replace(/'/g, "\\'")}', ${product.price})" class="add-to-cart-btn"><i class="fas fa-cart-plus"></i> Add to Cart</button>`;
            } else {
                html += `<div class="quantity-controls disabled">
                    <button class="quantity-btn" disabled><i class="fas fa-minus"></i></button>
                    <span id="quantity-${product.product_id}">0</span>
                    <button class="quantity-btn" disabled><i class="fas fa-plus"></i></button>
                </div>`;
                html += `<button class="add-to-cart-btn disabled" disabled><i class="fas fa-ban"></i> Out of Stock</button>`;
            }
            
            html += `</div>`;
            
            return html;
        }

        // Function to show all products in a category
        function showAllProductsInCategory(categoryId, categoryName) {
            console.log('showAllProductsInCategory called with:', categoryId, categoryName);
            
            // Validate inputs
            if (!categoryId || !categoryName) {
                console.error('Invalid categoryId or categoryName:', categoryId, categoryName);
                showNotification('Error: Invalid category information', 'error');
                return;
            }
            
            // Update URL without page refresh
            const url = new URL(window.location.href);
            url.searchParams.set('category', categoryId);
            window.history.pushState({}, '', url);

            // Update active category card
            document.querySelectorAll('.category-card').forEach(card => {
                card.classList.remove('active');
            });
            const categoryCard = document.querySelector(`[data-category-id="${categoryId}"]`);
            if (categoryCard) {
                categoryCard.classList.add('active');
            } else {
                console.warn('Category card not found for ID:', categoryId);
            }

            // Show loading state
            const productsGrid = document.querySelector('.products-grid');
            if (!productsGrid) {
                console.error('Products grid not found');
                showNotification('Error: Products section not found', 'error');
                return;
            }
            
            // Add fade out effect
            productsGrid.style.opacity = '0';
            productsGrid.style.transform = 'translateY(20px)';
            
            setTimeout(() => {
                // Show loading indicator
                productsGrid.innerHTML = '<div class="loading-products"><i class="fas fa-spinner fa-spin"></i> Loading all products...</div>';
                productsGrid.style.opacity = '1';
                productsGrid.style.transform = 'translateY(0)';
                
                // Load all products via AJAX
                const formData = new FormData();
                formData.append('category_id', categoryId);
                formData.append('action', 'load_products');

                fetch('load_products.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => {
                    console.log('Response status:', response.status);
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.text();
                })
                .then(html => {
                    console.log('Received HTML length:', html.length);
                    
                    if (html.trim() === '') {
                        throw new Error('Empty response from server');
                    }
                    
                    // Fade out loading
                    productsGrid.style.opacity = '0';
                    productsGrid.style.transform = 'translateY(20px)';
                    
                    setTimeout(() => {
                        // Update products grid
                        productsGrid.innerHTML = html;
                        
                        // Update section title
                        const productsSectionTitle = document.querySelector('.products-section h2');
                        if (productsSectionTitle) {
                            productsSectionTitle.innerHTML = `<i class="fas fa-box"></i> ${categoryName}`;
                        }
                        
                        // Fade in new content
                        productsGrid.style.opacity = '1';
                        productsGrid.style.transform = 'translateY(0)';
                        
                        // Reinitialize quantity displays
                        if (typeof quantities !== 'undefined') {
                            Object.keys(quantities).forEach(productId => {
                                updateQuantityDisplay(productId);
                            });
                        }
                        
                        // Show success notification
                        showNotification(`Showing all products in ${categoryName}`, 'success');
                    }, 200);
                })
                .catch(error => {
                    console.error('Error loading products:', error);
                    const productsGrid = document.querySelector('.products-grid');
                    if (productsGrid) {
                        productsGrid.innerHTML = '<p class="no-products"><i class="fas fa-exclamation-triangle"></i> Error loading products. Please try again.</p>';
                        productsGrid.style.opacity = '1';
                        productsGrid.style.transform = 'translateY(0)';
                    }
                    showNotification('Error loading products: ' + error.message, 'error');
                });
            }, 300);
        }

        // Handle browser back/forward buttons
        window.addEventListener('popstate', function() {
            const urlParams = new URLSearchParams(window.location.search);
            const categoryId = urlParams.get('category');
            
            if (categoryId) {
                // Find category name and reload
                const categoryCard = document.querySelector(`[data-category-id="${categoryId}"]`);
                if (categoryCard) {
                    const categoryName = categoryCard.querySelector('h3').textContent;
                    selectCategory(categoryId, categoryName);
                }
            } else {
                // No category selected, show default state
                document.querySelectorAll('.category-card').forEach(card => {
                    card.classList.remove('active');
                });
                const productsSectionTitle = document.querySelector('.products-section h2');
                productsSectionTitle.innerHTML = '<i class="fas fa-hand-pointer"></i> Please Select a Category';
                const productsGrid = document.querySelector('.products-grid');
                productsGrid.innerHTML = '';
                productsGrid.style.opacity = '1';
                productsGrid.style.transform = 'translateY(0)';
            }
        });

        // Search functionality
        let searchTimeout;
        let selectedSearchIndex = -1;
        let searchResults = [];

        const searchInput = document.getElementById('search-input');
        const searchResultsContainer = document.getElementById('search-results');
        const clearSearchBtn = document.getElementById('clear-search');

        // Search input event listener
        searchInput.addEventListener('input', function() {
            const query = this.value.trim();
            
            // Show/hide clear button
            clearSearchBtn.style.display = query ? 'flex' : 'none';
            
            // Clear previous timeout
            clearTimeout(searchTimeout);
            
            if (query.length >= 2) {
                // Add small delay to avoid too many requests
                searchTimeout = setTimeout(() => {
                    performSearch(query);
                }, 300);
            } else {
                hideSearchResults();
            }
        });

        // Clear search button
        clearSearchBtn.addEventListener('click', function() {
            searchInput.value = '';
            hideSearchResults();
            this.style.display = 'none';
            searchInput.focus();
        });

        // Keyboard navigation for search results
        searchInput.addEventListener('keydown', function(e) {
            if (!searchResultsContainer.classList.contains('active')) return;

            switch(e.key) {
                case 'ArrowDown':
                    e.preventDefault();
                    navigateSearchResults(1);
                    break;
                case 'ArrowUp':
                    e.preventDefault();
                    navigateSearchResults(-1);
                    break;
                case 'Enter':
                    e.preventDefault();
                    selectSearchResult();
                    break;
                case 'Escape':
                    hideSearchResults();
                    break;
            }
        });

        // Click outside to close search results
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.search-container')) {
                hideSearchResults();
            }
        });

        // Perform search
        function performSearch(query) {
            fetch(`search_products.php?q=${encodeURIComponent(query)}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        searchResults = data.products;
                        displaySearchResults(searchResults, query);
                    } else {
                        showNoSearchResults();
                    }
                })
                .catch(error => {
                    showNoSearchResults();
                });
        }

        // Display search results
        function displaySearchResults(products, query) {
            if (products.length === 0) {
                showNoSearchResults();
                return;
            }

            const resultsHtml = products.map((product, index) => {
                const highlightedName = highlightSearchTerm(product.product_name, query);
                const highlightedCategory = highlightSearchTerm(product.category_name, query);
                
                return `
                    <div class="search-result-item" data-index="${index}" data-product-id="${product.product_id}">
                        <div class="search-result-icon">
                            ${product.image_url && product.image_url !== '' 
                                ? `<img src="${product.image_url}" alt="${product.product_name}">`
                                : `<i class="fas fa-${product.category_type === 'food' ? 'utensils' : 'shopping-bag'}"></i>`
                            }
                        </div>
                        <div class="search-result-info">
                            <div class="search-result-name">${highlightedName}</div>
                            <div class="search-result-category">${highlightedCategory}</div>
                        </div>
                        <div class="search-result-price">₱${parseFloat(product.price).toFixed(2)}</div>
                        <div class="search-result-stock">${product.is_out_of_stock ? 'Out of Stock' : `${product.inventory_quantity} in stock`}</div>
                    </div>
                `;
            }).join('');

            searchResultsContainer.innerHTML = resultsHtml;
            searchResultsContainer.classList.add('active');
            selectedSearchIndex = -1;
        }

        // Show no search results
        function showNoSearchResults() {
            searchResultsContainer.innerHTML = `
                <div class="no-search-results">
                    <i class="fas fa-search"></i>
                    <p>No products found</p>
                </div>
            `;
            searchResultsContainer.classList.add('active');
        }

        // Hide search results
        function hideSearchResults() {
            searchResultsContainer.classList.remove('active');
            selectedSearchIndex = -1;
        }

        // Navigate search results with arrow keys
        function navigateSearchResults(direction) {
            const items = searchResultsContainer.querySelectorAll('.search-result-item');
            if (items.length === 0) return;

            // Remove previous selection
            if (selectedSearchIndex >= 0 && items[selectedSearchIndex]) {
                items[selectedSearchIndex].classList.remove('selected');
            }

            // Calculate new index
            selectedSearchIndex += direction;
            if (selectedSearchIndex < 0) selectedSearchIndex = items.length - 1;
            if (selectedSearchIndex >= items.length) selectedSearchIndex = 0;

            // Add selection to new item
            if (items[selectedSearchIndex]) {
                items[selectedSearchIndex].classList.add('selected');
                items[selectedSearchIndex].scrollIntoView({ block: 'nearest' });
            }
        }

        // Select search result
        function selectSearchResult() {
            if (selectedSearchIndex >= 0 && searchResults[selectedSearchIndex]) {
                const product = searchResults[selectedSearchIndex];
                handleProductSelection(product);
            }
        }

        // Handle product selection from search
        function handleProductSelection(product) {
            // Hide search results
            hideSearchResults();
            searchInput.value = '';
            clearSearchBtn.style.display = 'none';

            // Get category information for the product
            fetch(`get_product_category.php?product_id=${product.product_id}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.category_id) {
                        // Navigate to the category
                        const categoryCard = document.querySelector(`[data-category-id="${data.category_id}"]`);
                        if (categoryCard) {
                            const categoryName = categoryCard.querySelector('h3').textContent;
                            
                            // Show notification
                            showNotification(`Loading ${product.product_name}...`, 'success');
                            
                            // Navigate to category and show only the selected product
                            selectCategoryAndShowProduct(data.category_id, categoryName, product);
                        } else {
                            showNotification(`Selected: ${product.product_name}`, 'success');
                        }
                    } else {
                        showNotification(`Selected: ${product.product_name}`, 'success');
                    }
                })
                .catch(error => {
                    showNotification(`Selected: ${product.product_name}`, 'success');
                });
        }

        // Highlight search terms
        function highlightSearchTerm(text, query) {
            if (!query) return text;
            const regex = new RegExp(`(${query.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')})`, 'gi');
            return text.replace(regex, '<span class="search-highlight">$1</span>');
        }

        // Highlight product in grid
        function highlightProductInGrid(productId) {
            const productCard = document.querySelector(`[data-product-id="${productId}"]`);
            if (productCard) {
                // Add highlight class
                productCard.classList.add('search-highlighted');
                
                // Scroll to the product
                productCard.scrollIntoView({ 
                    behavior: 'smooth', 
                    block: 'center' 
                });
                
                // Show success notification
                const productName = productCard.querySelector('h3')?.textContent || 'Product';
                showNotification(`Found: ${productName}`, 'success');
                
                // Remove highlight after 5 seconds
                setTimeout(() => {
                    productCard.classList.remove('search-highlighted');
                }, 5000);
            } else {
                // If product card not found, show a notification
                showNotification('Product loaded in the category', 'success');
            }
        }

        // Add click event listeners to search results
        searchResultsContainer.addEventListener('click', function(e) {
            const resultItem = e.target.closest('.search-result-item');
            if (resultItem) {
                const index = parseInt(resultItem.dataset.index);
                if (searchResults[index]) {
                    handleProductSelection(searchResults[index]);
                }
            }
        });

        // Discount functionality
        let discountApplied = false;
        let discountInfo = null;
        let selectedDiscountProducts = [];

        // Make discount variables globally accessible
        window.discountApplied = discountApplied;
        window.discountInfo = discountInfo;
        window.selectedDiscountProducts = selectedDiscountProducts;

        // Show/hide discount button based on cart items
        function updateDiscountButton() {
            const discountBtn = document.getElementById('discount-btn');
            if (discountBtn) {
                if (cart.length > 0) {
                    discountBtn.style.display = 'flex';
                } else {
                    discountBtn.style.display = 'none';
                }
            }
        }

        // Open discount modal
        function openDiscountModal() {
            if (cart.length === 0) {
                showNotification('Your cart is empty!', 'error');
                return;
            }

            const modal = document.getElementById('discount-modal');
            if (modal) {
                modal.style.display = 'flex';
                populateDiscountProducts();
                updateDiscountSummary();
            }
        }

        // Close discount modal
        function closeDiscountModal() {
            const modal = document.getElementById('discount-modal');
            if (modal) {
                modal.style.display = 'none';
                // Reset form
                document.getElementById('customer-type').value = '';
                document.getElementById('customer-name').value = '';
                document.getElementById('customer-id').value = '';
                selectedDiscountProducts = [];
                updateDiscountSummary();
            }
        }

        // Populate discount products list
        function populateDiscountProducts() {
            const productsList = document.getElementById('discount-products-list');
            if (!productsList) return;

            productsList.innerHTML = '';
            
            cart.forEach(item => {
                const productItem = document.createElement('div');
                productItem.className = 'discount-product-item';
                productItem.innerHTML = `
                    <div class="discount-product-info">
                        <div class="discount-product-name">${item.name}</div>
                        <div class="discount-product-details">Available: ${item.quantity} | Unit Price: ₱${item.price.toFixed(2)}</div>
                    </div>
                    <div class="discount-product-controls">
                        <div class="discount-quantity-controls">
                            <label for="discount-qty-${item.id}">Discount Qty:</label>
                            <div class="quantity-input-group">
                                <button type="button" onclick="decreaseDiscountQuantity(${item.id}, ${item.quantity})" class="discount-qty-btn">
                                    <i class="fas fa-minus"></i>
                                </button>
                                <input type="number" id="discount-qty-${item.id}" 
                                       value="0" min="0" max="${item.quantity}" 
                                       onchange="updateDiscountQuantity(${item.id}, '${item.name}', ${item.price}, ${item.quantity})"
                                       class="discount-qty-input">
                                <button type="button" onclick="increaseDiscountQuantity(${item.id}, ${item.quantity})" class="discount-qty-btn">
                                    <i class="fas fa-plus"></i>
                                </button>
                            </div>
                        </div>
                        <div class="discount-product-price">
                            <span id="discount-total-${item.id}">₱0.00</span>
                        </div>
                    </div>
                `;
                productsList.appendChild(productItem);
            });
        }

        // Increase discount quantity
        function increaseDiscountQuantity(productId, maxQuantity) {
            const input = document.getElementById(`discount-qty-${productId}`);
            const currentValue = parseInt(input.value) || 0;
            if (currentValue < maxQuantity) {
                input.value = currentValue + 1;
                updateDiscountQuantity(productId, '', 0, maxQuantity);
            }
        }

        // Decrease discount quantity
        function decreaseDiscountQuantity(productId, maxQuantity) {
            const input = document.getElementById(`discount-qty-${productId}`);
            const currentValue = parseInt(input.value) || 0;
            if (currentValue > 0) {
                input.value = currentValue - 1;
                updateDiscountQuantity(productId, '', 0, maxQuantity);
            }
        }

        // Update discount quantity and recalculate
        function updateDiscountQuantity(productId, productName, productPrice, maxQuantity) {
            const input = document.getElementById(`discount-qty-${productId}`);
            const quantity = parseInt(input.value) || 0;
            
            // Ensure quantity doesn't exceed available quantity
            if (quantity > maxQuantity) {
                input.value = maxQuantity;
                quantity = maxQuantity;
            }
            
            // Find the cart item to get current product info
            const cartItem = cart.find(item => item.id === productId);
            if (!cartItem) return;
            
            // Update the discount total display
            const discountTotal = quantity * cartItem.price;
            document.getElementById(`discount-total-${productId}`).textContent = `₱${discountTotal.toFixed(2)}`;
            
            // Update selected discount products
            updateSelectedDiscountProducts();
        }

        // Update selected discount products based on current quantities
        function updateSelectedDiscountProducts() {
            selectedDiscountProducts = [];
            
            cart.forEach(item => {
                const discountQtyInput = document.getElementById(`discount-qty-${item.id}`);
                if (discountQtyInput) {
                    const discountQuantity = parseInt(discountQtyInput.value) || 0;
                    if (discountQuantity > 0) {
                        selectedDiscountProducts.push({
                            id: item.id,
                            name: item.name,
                            price: item.price,
                            quantity: discountQuantity, // Only the discounted quantity
                            total: item.price * discountQuantity
                        });
                    }
                }
            });
            
            updateDiscountSummary();
        }

        // Update discount summary
        function updateDiscountSummary() {
            const originalTotal = cart.reduce((total, item) => total + (item.price * item.quantity), 0);
            const selectedTotal = selectedDiscountProducts.reduce((total, item) => total + item.total, 0);
            
            // Calculate discount (20% for PWD/SC)
            const discountRate = 0.20; // 20% discount
            const discountAmount = selectedTotal * discountRate;
            const finalTotal = originalTotal - discountAmount;
            
            document.getElementById('original-total').textContent = `₱${originalTotal.toFixed(2)}`;
            document.getElementById('discount-amount').textContent = `₱${discountAmount.toFixed(2)}`;
            document.getElementById('final-total').textContent = `₱${finalTotal.toFixed(2)}`;
        }

        // Apply discount
        function applyDiscount() {
            const customerType = document.getElementById('customer-type').value;
            const customerName = document.getElementById('customer-name').value.trim();
            const customerId = document.getElementById('customer-id').value.trim();
            
            // Validate form
            if (!customerType || !customerName || !customerId) {
                showNotification('Please fill in all customer information fields!', 'error');
                return;
            }
            
            if (selectedDiscountProducts.length === 0) {
                showNotification('Please select at least one product for discount!', 'error');
                return;
            }
            
            // Calculate discount amount
            const discountAmount = selectedDiscountProducts.reduce((total, item) => total + (item.price * item.quantity), 0) * 0.20;
            if (discountAmount === 0) {
                showNotification('Please enter a discount quantity greater than 0 for at least one product!', 'error');
                return;
            }
            
            // Store discount information
            discountInfo = {
                customerType: customerType,
                customerName: customerName,
                customerId: customerId, 
                selectedProducts: selectedDiscountProducts,
                discountRate: 0.20,
                appliedAt: new Date()
            };
            
            discountApplied = true;
            
            // Update global variables
            window.discountApplied = discountApplied;
            window.discountInfo = discountInfo;
            window.selectedDiscountProducts = selectedDiscountProducts;
         
            // Update cart display to show discountv
            updateCartWithDiscount();
            
            // Close modal
            closeDiscountModal();
            
            // Show success notification 
            showNotification(`Discount applied! Saved ₱${discountAmount.toFixed(2)} for ${customerType} customer.`, 'success');
        }

        // Update cart display with discount information
        function updateCartWithDiscount() {
            const cartItems = document.getElementById('cart-items');
            const discountBtn = document.getElementById('discount-btn');
            
            if (discountApplied && discountInfo) {
                const totalDiscountedItems = discountInfo.selectedProducts.reduce((sum, item) => sum + item.quantity, 0);
                const discountAmount = discountInfo.selectedProducts.reduce((sum, item) => sum + (item.price * item.quantity), 0) * 0.20;

                // Build a breakdown of discounted items
                let discountedItemsHtml = '';
                discountInfo.selectedProducts.forEach(item => {
                    discountedItemsHtml += `
                        <div style="font-size:0.9rem; margin-left:1rem;">
                            <span>${item.name} x${item.quantity}</span>
                            <span style="color:#28a745;">-₱${(item.price * item.quantity * 0.20).toFixed(2)}</span>
                        </div>
                    `;
                });

                // Add discount info to cart display
                const discountInfoHtml = `
                    <div class="discount-info" style="background: #e8f5e8; padding: 1rem; border-radius: 8px; margin-bottom: 1rem; border-left: 4px solid var(--primary-color);">
                        <h4 style="margin: 0 0 0.5rem 0; color: var(--primary-color);">
                            <i class="fas fa-percentage"></i> ${discountInfo.customerType} Discount Applied
                        </h4>
                        <p style="margin: 0.2rem 0; font-size: 0.9rem;">
                            <strong>Customer:</strong> ${discountInfo.customerName}
                        </p>
                        <p style="margin: 0.2rem 0; font-size: 0.9rem;">
                            <strong>ID:</strong> ${discountInfo.customerId}
                        </p>
                        <p style="margin: 0.2rem 0; font-size: 0.9rem;">
                            <strong>Discounted Items:</strong> ${discountInfo.selectedProducts.length} products (${totalDiscountedItems} total items)
                        </p>
                        <p style="margin: 0.2rem 0; font-size: 0.9rem;">
                            <strong>Discount Amount:</strong> <span style="color:#28a745;">-₱${discountAmount.toFixed(2)}</span>
                        </p>
                        ${discountedItemsHtml}
                        <button onclick="removeDiscount()" style="background: #dc3545; color: white; border: none; padding: 0.3rem 0.8rem; border-radius: 4px; font-size: 0.8rem; cursor: pointer; margin-top: 0.5rem;">
                            <i class="fas fa-times"></i> Remove Discount
                        </button>
                    </div>
                `;
                
                // Remove any existing discount info first
                const oldDiscountInfo = cartItems.querySelector('.discount-info');
                if (oldDiscountInfo) oldDiscountInfo.remove();

                // Insert discount info at the beginning of cart items
                cartItems.insertAdjacentHTML('afterbegin', discountInfoHtml);

                // Update discount button
                if (discountBtn) {
                    discountBtn.innerHTML = '<i class="fas fa-check"></i> Discount Applied';
                    discountBtn.classList.add('active');
                    discountBtn.disabled = true;
                }
            }
        }

        // Remove discount
        function removeDiscount() {
            discountApplied = false;
            discountInfo = null;
            selectedDiscountProducts = [];
            
            // Update global variables
            window.discountApplied = discountApplied;
            window.discountInfo = discountInfo;
            window.selectedDiscountProducts = selectedDiscountProducts;
            
            // Remove discount info from cart display
            const discountInfoElement = document.querySelector('.discount-info');
            if (discountInfoElement) {
                discountInfoElement.remove();
            }
            
            // Reset discount button
            const discountBtn = document.getElementById('discount-btn');
            if (discountBtn) {
                discountBtn.innerHTML = '<i class="fas fa-percentage"></i> Add Discount';
                discountBtn.classList.remove('active');
                discountBtn.disabled = false;
            }
            
            // Update cart totals
            updateCart();
            
            showNotification('Discount removed successfully!', 'success');
        }

        // Override the existing updateCart function to include discount functionality
        const originalUpdateCart = window.updateCart;
        window.updateCart = function() {
            if (originalUpdateCart) {
                originalUpdateCart();
            }
            
            // Update discount button visibility
            updateDiscountButton();
            
            // If discount is applied, update the display
            if (discountApplied && discountInfo) {
                updateCartWithDiscount();
            }
        };

        // Override the existing checkout function to include discount information
        const originalCheckout = window.checkout;
        window.checkout = function() {
            if (cart.length === 0) {
                showNotification('Your cart is empty!', 'error');
                return;
            }

            // Show receipt modal with discount information
            showReceiptWithDiscount();
        };

        // Show receipt with discount information
        function showReceiptWithDiscount() {
            const modal = document.getElementById('receipt-modal');
            const receiptItems = document.querySelector('.receipt-items');
            const receiptTotal = document.querySelector('.receipt-total');
            
            if (!modal || !receiptItems || !receiptTotal) return;
            
            // Set current date and time
            const now = new Date();
            document.getElementById('receipt-date').textContent = now.toLocaleDateString();
            document.getElementById('receipt-time').textContent = now.toLocaleTimeString();
            
            // Clear previous items
            receiptItems.innerHTML = '';
            
            let total = 0;
            
            // Add cart items
            cart.forEach(item => {
                const itemTotal = item.price * item.quantity;
                total += itemTotal;
                
                // Check if this item has discount and get the discounted quantity
                const hasDiscount = discountApplied && discountInfo && 
                    discountInfo.selectedProducts.some(discItem => discItem.id === item.id);
                
                const discountedItem = hasDiscount ? 
                    discountInfo.selectedProducts.find(discItem => discItem.id === item.id) : null;
                
                const discountAmount = hasDiscount ? discountedItem.total * 0.20 : 0;
                const finalItemTotal = itemTotal - discountAmount;
                
                receiptItems.innerHTML += `
                    <div class="receipt-item">
                        <span>${item.name} x${item.quantity}</span>
                        <span>₱${itemTotal.toFixed(2)}</span>
                    </div>
                    ${hasDiscount ? `<div class="receipt-discount" style="color: #28a745; font-size: 0.9rem; margin-left: 1rem;">
                        <span>${discountInfo.customerType} Discount (20%) on ${discountedItem.quantity} item(s)</span>
                        <span>-₱${discountAmount.toFixed(2)}</span>
                    </div>` : ''}
                `;
            });
            
            // Calculate final total with discount
            const finalTotal = discountApplied && discountInfo ? 
                total - (discountInfo.selectedProducts.reduce((sum, item) => sum + item.total, 0) * 0.20) : 
                total;
            
            // Add discount information to receipt if applied
            if (discountApplied && discountInfo) {
                const totalDiscountedItems = discountInfo.selectedProducts.reduce((sum, item) => sum + item.quantity, 0);
                receiptItems.innerHTML += `
                    <div class="receipt-discount-info" style="background: #e8f5e8; padding: 1rem; margin: 1rem 0; border-radius: 8px; border-left: 4px solid #28a745;">
                        <h4 style="margin: 0 0 0.5rem 0; color: #28a745;">
                            <i class="fas fa-percentage"></i> ${discountInfo.customerType} Discount Applied
                        </h4>
                        <p style="margin: 0.2rem 0; font-size: 0.9rem;">
                            <strong>Discounted Items:</strong> ${discountInfo.selectedProducts.length} products (${totalDiscountedItems} total items)
                        </p>
                        <p style="margin: 0.2rem 0; font-size: 0.9rem;">
                            <strong>Discount Amount:</strong> ₱${(total - finalTotal).toFixed(2)}
                        </p>
                    </div>
                `;
            }
            
            // Update total
            receiptTotal.innerHTML = `
                <div class="receipt-total-row">
                    <span>Total:</span>
                    <span>₱${finalTotal.toFixed(2)}</span>
                </div>
            `;
            
            // Show modal
            modal.style.display = 'flex';
        }

        // Close modal when clicking outside
        document.addEventListener('click', function(e) {
            const discountModal = document.getElementById('discount-modal');
            if (e.target === discountModal) {
                closeDiscountModal();
            }
        });

        // Initialize discount button visibility
        document.addEventListener('DOMContentLoaded', function() {
            updateDiscountButton();
        });

        // Function to add order to database
        function addOrder() {
            if (cart.length === 0) {
                showNotification('Your cart is empty!', 'error');
                return;
            }

            // Prepare order data
            let items = [];
            // Flatten cart: one object per item, not per group
            cart.forEach(item => {
                // Check if this item is discounted and how many are discounted
                let discountedQty = 0;
                if (discountApplied && discountInfo && discountInfo.selectedProducts) {
                    const disc = discountInfo.selectedProducts.find(d => d.id === item.id);
                    if (disc) discountedQty = disc.quantity;
                }
                // Add discounted items
                for (let i = 0; i < discountedQty; i++) {
                    items.push({
                        id: item.id,
                        name: item.name,
                        price: item.price,
                        is_pwd_discounted: true,
                        discounted_price: (item.price * 0.8)
                    });
                }
                // Add non-discounted items
                for (let i = 0; i < item.quantity - discountedQty; i++) {
                    items.push({
                        id: item.id,
                        name: item.name,
                        price: item.price,
                        is_pwd_discounted: false,
                        discounted_price: item.price
                    });
                }
            });

            const orderData = {
                items: items,
                total: parseFloat(document.getElementById('total-amount').textContent.replace('₱', '')),
                discount_info: discountApplied && discountInfo ? {
                    customerType: discountInfo.customerType,
                    customerName: discountInfo.customerName,
                    customerId: discountInfo.customerId,
                    selectedProducts: discountInfo.selectedProducts
                } : null
            };

            // Show loading state
            const addOrderBtn = document.querySelector('.add-order-btn');
            const originalText = addOrderBtn.innerHTML;
            addOrderBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving Order...';
            addOrderBtn.disabled = true;

            // Send order to server
            fetch('save_order_with_discount.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(orderData)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Clear cart
                    cart = [];
                    quantities = {};
                    localStorage.removeItem('cart');
                    localStorage.removeItem('quantities');
                    
                    // Reset discount
                    discountApplied = false;
                    discountInfo = null;
                    selectedDiscountProducts = [];
                    window.discountApplied = false;
                    window.discountInfo = null;
                    window.selectedDiscountProducts = [];
                    
                    // Update cart display
                    updateCart();
                    
                    // Close receipt modal
                    closeReceipt();
                    
                    // Show success message
                    showNotification(`Order saved successfully! Order ID: #${data.order_id}`, 'success');
                    
                    // Reset discount button
                    const discountBtn = document.getElementById('discount-btn');
                    if (discountBtn) {
                        discountBtn.innerHTML = '<i class="fas fa-percentage"></i> Add Discount';
                        discountBtn.classList.remove('active');
                        discountBtn.disabled = false;
                        discountBtn.style.display = 'none';
                    }
                } else {
                    showNotification('Error saving order: ' + data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('Error saving order. Please try again.', 'error');
            })
            .finally(() => {
                // Reset button state
                addOrderBtn.innerHTML = originalText;
                addOrderBtn.disabled = false;
            });
        }
    </script>
</body>
</html>
