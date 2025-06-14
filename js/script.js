// Cart and quantities management
let cart = [];
let quantities = {};

// Load cart from localStorage on page load
document.addEventListener('DOMContentLoaded', function() {
    const savedCart = localStorage.getItem('cart');
    const savedQuantities = localStorage.getItem('quantities');
    
    if (savedCart) {
        cart = JSON.parse(savedCart);
    }
    if (savedQuantities) {
        quantities = JSON.parse(savedQuantities);
    }
    updateCart();
    handleResponsiveLayout();
    initializeResponsiveFeatures();
    initializeMobileFeatures();

    // Initialize quantity displays for all products
    Object.keys(quantities).forEach(productId => {
        updateQuantityDisplay(productId);
    });

    // Add touch event listeners for cart swipe
    let touchStartY = 0;
    let touchEndY = 0;
    const cartContainer = document.querySelector('.cart-container');
    const cartToggle = document.querySelector('.cart-toggle-btn');
    const mainContent = document.querySelector('main');

    if (cartContainer) {
        cartContainer.addEventListener('touchstart', function(e) {
            touchStartY = e.touches[0].clientY;
        }, { passive: true });

        cartContainer.addEventListener('touchend', function(e) {
            touchEndY = e.changedTouches[0].clientY;
            const swipeDistance = touchEndY - touchStartY;
            
            if (swipeDistance > 100 && cartContainer.classList.contains('expanded')) {
                toggleCart();
            }
        }, { passive: true });
    }

    if (cartToggle && cartContainer) {
        cartToggle.addEventListener('click', function() {
            cartContainer.classList.toggle('expanded');
            cartContainer.classList.toggle('collapsed');
            
            // Add a small delay before hiding the main content
            if (cartContainer.classList.contains('expanded')) {
                mainContent.style.opacity = '0';
                mainContent.style.transition = 'opacity 0.3s ease';
                setTimeout(() => {
                    mainContent.style.display = 'none';
                }, 300);
            } else {
                mainContent.style.display = 'block';
                setTimeout(() => {
                    mainContent.style.opacity = '1';
                }, 50);
            }
        });
    }

    // Update the cart container styles when the page loads
    if (cartContainer) {
        cartContainer.style.transition = 'all 0.3s ease-in-out';
        cartContainer.style.display = 'block';
        cartContainer.style.opacity = '1';
        cartContainer.style.visibility = 'visible';
    }
});

// Save cart to localStorage whenever it changes
function saveCart() {
    localStorage.setItem('cart', JSON.stringify(cart));
    localStorage.setItem('quantities', JSON.stringify(quantities));
}

function increaseQuantity(productId) {
    // Check if product is out of stock
    const productCard = document.querySelector(`[onclick*="increaseQuantity(${productId})"]`).closest('.product-card');
    if (productCard && productCard.classList.contains('out-of-stock')) {
        showNotification('This product is out of stock!', 'error');
        return;
    }
    
    // Get current quantity and available stock
    const currentQuantity = quantities[productId] || 0;
    const stockQuantityElement = productCard.querySelector('.stock-quantity');
    const availableStock = parseInt(stockQuantityElement.textContent.split(' ')[0]); // Extract number from "X Stocks"
    
    // Check if increasing quantity would exceed available stock
    if (currentQuantity >= availableStock) {
        showNotification(`Cannot add more than ${availableStock} items. Available stock exceeded!`, 'error');
        return;
    }
    
    quantities[productId] = currentQuantity + 1;
    updateQuantityDisplay(productId);
    saveCart();
}

function decreaseQuantity(productId) {
    // Check if product is out of stock
    const productCard = document.querySelector(`[onclick*="decreaseQuantity(${productId})"]`).closest('.product-card');
    if (productCard && productCard.classList.contains('out-of-stock')) {
        showNotification('This product is out of stock!', 'error');
        return;
    }
    
    if (quantities[productId] > 0) {
        quantities[productId]--;
        updateQuantityDisplay(productId);
        saveCart();
    }
}

function updateQuantityDisplay(productId) {
    const quantityElement = document.getElementById('quantity-' + productId);
    const currentQuantity = quantities[productId] || 0;
    quantityElement.textContent = currentQuantity;
    
    // Get the product card and check stock limits
    const productCard = quantityElement.closest('.product-card');
    if (productCard) {
        const stockQuantityElement = productCard.querySelector('.stock-quantity');
        const availableStock = parseInt(stockQuantityElement.textContent.split(' ')[0]); // Extract number from "X Stocks"
        const quantityControls = productCard.querySelector('.quantity-controls');
        const plusButton = productCard.querySelector('[onclick*="increaseQuantity"]');
        
        // Add or remove 'at-limit' class based on current quantity vs available stock
        if (currentQuantity >= availableStock) {
            quantityControls.classList.add('at-limit');
            if (plusButton) {
                plusButton.disabled = true;
            }
        } else {
            quantityControls.classList.remove('at-limit');
            if (plusButton) {
                plusButton.disabled = false;
            }
        }
    }
}

function addToCart(id, name, price) {
    const quantity = quantities[id] || 1;
    if (quantity <= 0) return;

    // Check if product is out of stock
    const productCard = document.querySelector(`[onclick*="addToCart(${id},"]`).closest('.product-card');
    if (productCard && productCard.classList.contains('out-of-stock')) {
        showNotification('This product is out of stock!', 'error');
        return;
    }

    // Check if quantity exceeds available stock
    const stockQuantityElement = productCard.querySelector('.stock-quantity');
    const availableStock = parseInt(stockQuantityElement.textContent.split(' ')[0]); // Extract number from "X Stocks"
    
    if (quantity > availableStock) {
        showNotification(`Cannot add ${quantity} items. Only ${availableStock} items available in stock!`, 'error');
        return;
    }

    const existingItem = cart.find(item => item.id === id);
    if (existingItem) {
        // Check if adding this quantity would exceed available stock
        const totalQuantityAfterAdd = existingItem.quantity + quantity;
        if (totalQuantityAfterAdd > availableStock) {
            showNotification(`Cannot add ${quantity} more items. Total would exceed available stock of ${availableStock}!`, 'error');
            return;
        }
        existingItem.quantity += quantity;
    } else {
        cart.push({ id, name, price, quantity });
    }
    
    quantities[id] = 0;
    updateQuantityDisplay(id);
    updateCart();
    saveCart();
    
    // Show cart after adding item
    const cartContainer = document.querySelector('.cart-container');
    if (cartContainer) {
        cartContainer.classList.add('expanded');
        
        // Update button text
        const viewCartBtn = document.querySelector('.view-cart-btn');
        if (viewCartBtn) {
            viewCartBtn.innerHTML = '<i class="fas fa-times"></i> Close Cart';
        }

        // Animate new items
        const cartItems = cartContainer.querySelectorAll('.cart-item');
        cartItems.forEach((item, index) => {
            item.style.animationDelay = `${index * 0.1}s`;
            item.style.opacity = '0';
            item.style.transform = 'translateY(20px)';
            
            setTimeout(() => {
                item.style.transition = 'all 0.3s ease-out';
                item.style.opacity = '1';
                item.style.transform = 'translateY(0)';
            }, index * 100);
        });

        // Show main content in grid layout
        const mainContent = document.querySelector('main');
        if (mainContent) {
            mainContent.style.display = 'grid';
            mainContent.style.gridTemplateColumns = '1fr 2fr 1fr';
        }
    }
    
    showNotification(`${name} added to cart`);
}

function removeFromCart(id) {
    const item = cart.find(item => item.id === id);
    cart = cart.filter(item => item.id !== id);
    updateCart();
    saveCart();
    
    if (item) {
        showNotification(`${item.name} removed from cart`);
    }
}

function updateCart() {
    const cartItems = document.getElementById('cart-items');
    const cartCount = document.getElementById('cart-count');
    const cartTotal = document.getElementById('cart-total');
    const totalAmount = document.getElementById('total-amount');
    const mobileCartCount = document.getElementById('mobile-cart-count');
    const mobileCartTotal = document.getElementById('mobile-cart-total');
    const cartContainer = document.querySelector('.cart-container');
    const viewCartBtn = document.querySelector('.view-cart-btn');
    
    if (!cartItems) return;
    
    // Clear cart items but preserve discount info if it exists
    const existingDiscountInfo = cartItems.querySelector('.discount-info');
    cartItems.innerHTML = '';
    
    // Restore discount info if it exists
    if (existingDiscountInfo) {
        cartItems.appendChild(existingDiscountInfo);
    }
    
    let total = 0;
    let count = 0;

    cart.forEach(item => {
        const itemTotal = item.price * item.quantity;
        total += itemTotal;
        count += item.quantity;

        cartItems.innerHTML += `
            <div class="cart-item">
                <div class="cart-item-details">
                    <span class="item-name">${item.name}</span>
                    <span class="item-quantity">x${item.quantity}</span>
                </div>
                <div class="cart-item-actions">
                    <span class="item-price">₱${itemTotal.toFixed(2)}</span>
                    <button onclick="removeFromCart(${item.id})" class="remove-btn">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
        `;
    });

    // Update all cart counters and totals
    [cartCount, mobileCartCount].forEach(el => {
        if (el) el.textContent = count;
    });
    
    [cartTotal, mobileCartTotal, totalAmount].forEach(el => {
        if (el) el.textContent = `₱${total.toFixed(2)}`;
    });

    // Update cart container visibility
    if (cartContainer) {
        if (count === 0) {
            cartContainer.classList.remove('expanded');
            if (viewCartBtn) {
                viewCartBtn.innerHTML = '<i class="fas fa-shopping-cart"></i> View Cart';
            }
        }
    }

    // Save cart to localStorage
    saveCart();
    
    // Update discount button visibility if the function exists
    if (typeof updateDiscountButton === 'function') {
        updateDiscountButton();
    }
}

function checkout() {
    if (cart.length === 0) {
        showNotification('Your cart is empty!', 'error');
        return;
    }

    // Show receipt modal
    showReceipt();
}

// Performance optimizations
const raf = window.requestAnimationFrame;
const now = () => performance.now();

// Smooth animation helper
function smoothAnimation(element, properties, duration = 300) {
    const start = now();
    const startProps = {};
    const endProps = {};
    
    // Get start values
    for (const prop in properties) {
        startProps[prop] = parseFloat(getComputedStyle(element)[prop]);
        endProps[prop] = properties[prop];
    }
    
    function animate() {
        const elapsed = now() - start;
        const progress = Math.min(elapsed / duration, 1);
        
        // Use spring easing for smoother animation
        const springProgress = 1 - Math.pow(1 - progress, 3);
        
        for (const prop in properties) {
            const value = startProps[prop] + (endProps[prop] - startProps[prop]) * springProgress;
            element.style[prop] = value + (prop === 'opacity' ? '' : 'px');
        }
        
        if (progress < 1) {
            raf(animate);
        }
    }
    
    raf(animate);
}

// Enhanced notification system with smoother animations
function showNotification(message, type = 'success') {
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    notification.innerHTML = `
        <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i>
        ${message}
    `;
    
    // Remove existing notifications
    document.querySelectorAll('.notification').forEach(n => n.remove());
    
    document.body.appendChild(notification);
    
    // Use smooth animation helper
    smoothAnimation(notification, {
        opacity: 1,
        transform: 'translateX(0) scale(1)'
    }, 200);
    
    // Vibrate with optimized pattern
    if ('vibrate' in navigator) {
        navigator.vibrate([30, 20, 30]);
    }
    
    setTimeout(() => {
        smoothAnimation(notification, {
            opacity: 0,
            transform: 'translateX(100%) scale(0.95)'
        }, 200);
        
        setTimeout(() => notification.remove(), 200);
    }, 2500);
}

// Enhanced receipt display with smooth animations
function showReceipt() {
    const modal = document.getElementById('receipt-modal');
    const receiptItems = document.querySelector('.receipt-items');
    const receiptTotal = document.querySelector('.receipt-total');
    const receiptDate = document.getElementById('receipt-date');
    const receiptTime = document.getElementById('receipt-time');
    
    // Set date and time
    const now = new Date();
    receiptDate.textContent = now.toLocaleDateString();
    receiptTime.textContent = now.toLocaleTimeString();

    // Generate receipt items with staggered animation
    let total = 0;
    receiptItems.innerHTML = '';
    
    cart.forEach((item, index) => {
        const itemTotal = item.price * item.quantity;
        total += itemTotal;
        
        const receiptItem = document.createElement('div');
        receiptItem.className = 'receipt-item';
        receiptItem.style.opacity = '0';
        receiptItem.style.transform = 'translateY(20px)';
        
        receiptItem.innerHTML = `
            <span class="item-name">${item.name}</span>
            <span class="item-quantity">${item.quantity}</span>
            <span class="item-price">₱${itemTotal.toFixed(2)}</span>
        `;
        
        receiptItems.appendChild(receiptItem);
        
        // Use smooth animation helper for staggered items
        setTimeout(() => {
            smoothAnimation(receiptItem, {
                opacity: 1,
                transform: 'translateY(0)'
            }, 200);
        }, index * 50);
    });

    // Check if discount is applied (from dashboard discount functionality)
    let finalTotal = total;
    let discountInfo = null;
    
    // Try to get discount info from the dashboard if it exists
    if (typeof window.discountInfo !== 'undefined' && window.discountInfo) {
        discountInfo = window.discountInfo;
        const discountAmount = discountInfo.selectedProducts.reduce((sum, item) => sum + item.total, 0) * discountInfo.discountRate;
        finalTotal = total - discountAmount;
        
        // Add discount information to receipt
        const discountInfoElement = document.createElement('div');
        discountInfoElement.className = 'receipt-discount-info';
        discountInfoElement.style.cssText = 'background: #e8f5e8; padding: 1rem; margin: 1rem 0; border-radius: 8px; border-left: 4px solid #28a745;';
        
        const totalDiscountedItems = discountInfo.selectedProducts.reduce((sum, item) => sum + item.quantity, 0);
        
        discountInfoElement.innerHTML = `
            <h4 style="margin: 0 0 0.5rem 0; color: #28a745;">
                <i class="fas fa-percentage"></i> ${discountInfo.customerType} Discount Applied
            </h4>
            <p style="margin: 0.2rem 0; font-size: 0.9rem;">
                <strong>Customer:</strong> ${discountInfo.customerName}
            </p>
            <p style="margin: 0.2rem 0; font-size: 0.9rem;">
                <strong>ID Number:</strong> ${discountInfo.customerId}
            </p>
            <p style="margin: 0.2rem 0; font-size: 0.9rem;">
                <strong>Discounted Items:</strong> ${discountInfo.selectedProducts.length} products (${totalDiscountedItems} total items)
            </p>
            <p style="margin: 0.2rem 0; font-size: 0.9rem;">
                <strong>Discount Amount:</strong> ₱${discountAmount.toFixed(2)}
            </p>
        `;
        
        receiptItems.appendChild(discountInfoElement);
    }

    // Add total with spring animation
    receiptTotal.innerHTML = `
        <div class="receipt-total-row">
            <span>Total Amount:</span>
            <span>₱${finalTotal.toFixed(2)}</span>
        </div>
    `;

    // Show modal with smooth transition
    modal.style.display = 'flex';
    smoothAnimation(modal, {
        opacity: 1
    }, 200);
}

// Enhanced cart toggle with smooth animations
function toggleCart() {
    const cartContainer = document.querySelector('.cart-container');
    const mainContent = document.querySelector('main');
    const viewCartBtn = document.querySelector('.view-cart-btn');
    
    if (cart.length === 0) {
        showNotification('Your cart is empty!', 'error');
        return;
    }

    if (cartContainer) {
        const isExpanding = !cartContainer.classList.contains('expanded');
        
        // Toggle expanded class
        cartContainer.classList.toggle('expanded');
        
        // Update button text
        if (viewCartBtn) {
            viewCartBtn.innerHTML = isExpanding 
                ? '<i class="fas fa-times"></i> Close Cart' 
                : '<i class="fas fa-shopping-cart"></i> View Cart';
        }

        // Update main content layout
        if (mainContent) {
            if (isExpanding) {
                mainContent.style.display = 'grid';
                mainContent.style.gridTemplateColumns = '1fr 2fr 1fr';
            } else {
                mainContent.style.display = 'grid';
                mainContent.style.gridTemplateColumns = '1fr 2fr';
            }
        }

        // Ensure cart is visible when expanded
        if (isExpanding) {
            cartContainer.style.display = 'block';
            cartContainer.style.opacity = '1';
            cartContainer.style.visibility = 'visible';
            cartContainer.style.transform = 'translateY(0)';
        }
    }
}

// Responsive Enhancements
function handleResponsiveLayout() {
    const header = document.querySelector('header');
    const headerTop = document.querySelector('.header-top');
    const headerActions = document.querySelector('.header-actions');
    const cartContainer = document.querySelector('.cart-container');
    const productsGrid = document.querySelector('.products-grid');
    const categoriesGrid = document.querySelector('.categories-grid');
    
    // Adjust header layout for mobile
    if (window.innerWidth <= 768) {
        header.style.padding = '0.8rem';
        headerTop.style.flexDirection = 'column';
        headerTop.style.alignItems = 'flex-start';
        headerActions.style.flexDirection = 'column';
        headerActions.style.width = '100%';
        
        // Adjust grid layouts
        if (productsGrid) {
            productsGrid.style.gridTemplateColumns = 'repeat(auto-fill, minmax(140px, 1fr))';
            productsGrid.style.gap = '1rem';
        }
        
        if (categoriesGrid) {
            categoriesGrid.style.gridTemplateColumns = '1fr';
        }
    } else {
        header.style.padding = 'clamp(1rem, 3vw, 2rem)';
        headerTop.style.flexDirection = 'row';
        headerTop.style.alignItems = 'center';
        headerActions.style.flexDirection = 'row';
        headerActions.style.width = 'auto';
        
        // Reset grid layouts
        if (productsGrid) {
            productsGrid.style.gridTemplateColumns = 'repeat(auto-fill, minmax(200px, 1fr))';
            productsGrid.style.gap = '1.5rem';
        }
        
        if (categoriesGrid) {
            categoriesGrid.style.gridTemplateColumns = 'repeat(auto-fill, minmax(150px, 1fr))';
        }
    }

    // Adjust cart container for mobile
    if (window.innerWidth <= 1023) {
        cartContainer.style.position = 'fixed';
        cartContainer.style.bottom = '0';
        cartContainer.style.left = '0';
        cartContainer.style.right = '0';
        cartContainer.style.top = 'auto';
        cartContainer.style.maxHeight = '80vh';
        cartContainer.style.borderRadius = '15px 15px 0 0';
        cartContainer.style.transform = 'translateY(100%)';
    } else {
        cartContainer.style.position = 'sticky';
        cartContainer.style.top = '2rem';
        cartContainer.style.bottom = 'auto';
        cartContainer.style.left = 'auto';
        cartContainer.style.right = 'auto';
        cartContainer.style.maxHeight = 'calc(100vh - 4rem)';
        cartContainer.style.borderRadius = '15px';
        cartContainer.style.transform = 'none';
    }
}

// Initialize responsive features
function initializeResponsiveFeatures() {
    // Add smooth scrolling for mobile navigation
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });

    // Optimize images for mobile
    const images = document.querySelectorAll('img');
    images.forEach(img => {
        img.addEventListener('load', function() {
            this.style.opacity = '1';
        });
        img.style.opacity = '0';
        img.style.transition = 'opacity 0.3s ease';
    });

    // Add lazy loading for images
    if ('loading' in HTMLImageElement.prototype) {
        images.forEach(img => {
            img.loading = 'lazy';
        });
    } else {
        // Fallback for browsers that don't support lazy loading
        const lazyLoadScript = document.createElement('script');
        lazyLoadScript.src = 'https://cdnjs.cloudflare.com/ajax/libs/lazysizes/5.3.2/lazysizes.min.js';
        document.body.appendChild(lazyLoadScript);
    }

    // Add touch feedback
    document.querySelectorAll('button').forEach(button => {
        button.addEventListener('touchstart', function() {
            this.style.transform = 'scale(0.95)';
        });
        button.addEventListener('touchend', function() {
            this.style.transform = '';
        });
    });

    // Add smooth transitions for mobile menu
    const cartToggle = document.querySelector('.cart-toggle-btn');
    if (cartToggle) {
        cartToggle.addEventListener('click', function() {
            const cartContainer = document.querySelector('.cart-container');
            cartContainer.style.transition = 'transform 0.3s cubic-bezier(0.4, 0, 0.2, 1)';
        });
    }
}

// Handle window resize
let resizeTimer;
window.addEventListener('resize', function() {
    clearTimeout(resizeTimer);
    resizeTimer = setTimeout(function() {
        handleResponsiveLayout();
    }, 250);
});

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('receipt-modal');
    if (event.target == modal) {
        closeReceipt();
    }
}

// Add loading state to buttons
function setLoading(element, isLoading) {
    if (isLoading) {
        element.disabled = true;
        element.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Loading...';
    } else {
        element.disabled = false;
        element.innerHTML = element.getAttribute('data-original-text');
    }
}

// Add smooth scroll to top
function scrollToTop() {
    window.scrollTo({
        top: 0,
        behavior: 'smooth'
    });
}

// Enhanced Mobile Interactions
function initializeMobileFeatures() {
    let touchStartY = 0;
    let touchEndY = 0;
    const pullThreshold = 100;
    let isPulling = false;
    
    document.addEventListener('touchstart', function(e) {
        touchStartY = e.touches[0].clientY;
        isPulling = window.scrollY === 0;
    }, { passive: true });
    
    document.addEventListener('touchmove', function(e) {
        if (!isPulling) return;
        
        const pullDistance = touchStartY - e.touches[0].clientY;
        if (pullDistance > 0) {
            const pullProgress = Math.min(pullDistance / pullThreshold, 1);
            smoothAnimation(document.body, {
                transform: `translateY(${pullProgress * 50}px)`
            }, 100);
        }
    }, { passive: true });
    
    document.addEventListener('touchend', function(e) {
        if (!isPulling) return;
        
        const pullDistance = touchStartY - e.changedTouches[0].clientY;
        
        if (pullDistance > pullThreshold) {
            smoothAnimation(document.body, {
                transform: 'translateY(0)'
            }, 200);
            refreshContent();
        } else {
            smoothAnimation(document.body, {
                transform: 'translateY(0)'
            }, 200);
        }
        
        isPulling = false;
    }, { passive: true });

    // Enhanced cart swipe gestures
    let cartStartX = 0;
    let isSwiping = false;

    document.addEventListener('touchstart', function(e) {
        const cartContainer = document.querySelector('.cart-container');
        if (cartContainer && cartContainer.classList.contains('expanded')) {
            cartStartX = e.touches[0].clientX;
            isSwiping = true;
        }
    }, { passive: true });

    document.addEventListener('touchmove', function(e) {
        if (!isSwiping) return;
        
        const cartContainer = document.querySelector('.cart-container');
        if (cartContainer && cartContainer.classList.contains('expanded')) {
            const swipeDistance = cartStartX - e.touches[0].clientX;
            if (swipeDistance > 0) {
                smoothAnimation(cartContainer, {
                    transform: `translateX(${swipeDistance * 0.5}px)`
                }, 100);
            }
        }
    }, { passive: true });

    document.addEventListener('touchend', function(e) {
        if (!isSwiping) return;
        
        const cartContainer = document.querySelector('.cart-container');
        if (cartContainer && cartContainer.classList.contains('expanded')) {
            const swipeDistance = cartStartX - e.changedTouches[0].clientX;
            
            smoothAnimation(cartContainer, {
                transform: 'translateX(0)'
            }, 200);
            
            if (swipeDistance > 50) {
                toggleCart();
            }
        }
        
        isSwiping = false;
    }, { passive: true });

    // Enhanced touch feedback
    document.querySelectorAll('.product-card, .category-card, button').forEach(element => {
        element.addEventListener('touchstart', function() {
            smoothAnimation(this, {
                transform: 'scale(0.98)'
            }, 100);
        }, { passive: true });
        
        element.addEventListener('touchend', function() {
            smoothAnimation(this, {
                transform: 'scale(1)'
            }, 200);
        }, { passive: true });
    });
}

// Refresh content function
function refreshContent() {
    const productsGrid = document.querySelector('.products-grid');
    if (productsGrid) {
        showLoading(productsGrid);
        // Simulate content refresh
        setTimeout(() => {
            hideLoading(productsGrid);
            showNotification('Content refreshed', 'success');
        }, 1000);
    }
}

// Optimize performance for frequent operations
const debounce = (func, wait) => {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
};

// Optimize window resize handling
window.addEventListener('resize', debounce(function() {
    handleResponsiveLayout();
}, 100));

// Optimize scroll handling
window.addEventListener('scroll', debounce(function() {
    // Add any scroll-based animations here
}, 100));

// Optimize touch interactions
document.addEventListener('touchstart', function() {}, {passive: true});

// Prevent double-tap zoom on mobile
document.addEventListener('touchend', function(event) {
    event.preventDefault();
    event.target.click();
}, {passive: false});

function addOrder() {
    if (cart.length === 0) {
        showNotification('Cart is empty', 'error');
        return;
    }

    const orderData = {
        items: cart.map(item => ({
            id: item.id,
            quantity: item.quantity,
            price: item.price
        })),
        total: cart.reduce((sum, item) => sum + (item.price * item.quantity), 0)
    };

    // Show loading state
    const addOrderBtn = document.querySelector('.add-order-btn');
    const originalText = addOrderBtn ? addOrderBtn.innerHTML : '';
    
    if (addOrderBtn) {
        addOrderBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
        addOrderBtn.disabled = true;
    }

    fetch('save_order.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(orderData)
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            showNotification('Order added successfully!', 'success');
            // Clear the cart after successful order
            cart = [];
            updateCart();
            saveCart();
            closeReceipt();
            // Redirect to admin_orders.php after successful order
            setTimeout(() => {
                window.location.href = 'admin_orders.php';
            }, 1000); // Add a small delay to show the success message
        } else {
            throw new Error(data.message || 'Error adding order');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification(error.message || 'Error adding order', 'error');
    })
    .finally(() => {
        // Reset button state
        if (addOrderBtn) {
            addOrderBtn.innerHTML = originalText;
            addOrderBtn.disabled = false;
        }
    });
}

// Add closeReceipt function if it doesn't exist
function closeReceipt() {
    const modal = document.getElementById('receipt-modal');
    if (modal) {
        modal.style.display = 'none';
    }
}

function printReceipt() {
    const receipt = document.querySelector('.receipt');
    const receiptActions = document.querySelector('.receipt-actions');
    const originalDisplay = receiptActions.style.display;
    
    // Hide the action buttons temporarily
    receiptActions.style.display = 'none';
    
    // Create a new window for printing
    const printWindow = window.open('', '_blank');
    
    // Get the receipt content
    const receiptContent = receipt.innerHTML;
    
    // Add print-specific styles with enhanced design
    const printStyles = `
        <style>
            @import url('https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css');
            
            @media print {
                body { 
                    margin: 0; 
                    padding: 20px;
                    background: #fff;
                }
                .receipt { 
                    width: 80mm;
                    margin: 0 auto;
                    padding: 15px;
                    font-family: 'Courier New', monospace;
                    background: #fff;
                    box-shadow: 0 0 10px rgba(0,0,0,0.1);
                }
                .receipt-header { 
                    text-align: center; 
                    margin-bottom: 20px;
                    border-bottom: 2px dashed #000;
                    padding-bottom: 15px;
                }
                .receipt-header h2 {
                    font-size: 18px;
                    margin: 0 0 5px 0;
                    color: #2E7D32;
                }
                .receipt-header p {
                    margin: 5px 0;
                    font-size: 12px;
                }
                .receipt-header i {
                    font-size: 24px;
                    color: #2E7D32;
                    margin-bottom: 10px;
                }
                .receipt-items { 
                    margin: 20px 0;
                    border-top: 1px dashed #000;
                    border-bottom: 1px dashed #000;
                    padding: 10px 0;
                }
                .receipt-item { 
                    display: flex;
                    justify-content: space-between;
                    margin-bottom: 8px;
                    font-size: 12px;
                    line-height: 1.4;
                }
                .receipt-item .item-name {
                    flex: 1;
                    margin-right: 10px;
                }
                .receipt-item .item-quantity {
                    text-align: center;
                    width: 30px;
                }
                .receipt-item .item-price {
                    text-align: right;
                    width: 60px;
                }
                .receipt-total-row {
                    border-top: 2px dashed #000;
                    margin-top: 15px;
                    padding-top: 15px;
                    font-weight: bold;
                    font-size: 14px;
                    display: flex;
                    justify-content: space-between;
                }
                .receipt-footer { 
                    text-align: center;
                    margin-top: 20px;
                    font-size: 12px;
                    border-top: 1px dashed #000;
                    padding-top: 15px;
                }
                .receipt-footer i {
                    font-size: 16px;
                    color: #2E7D32;
                    margin-bottom: 5px;
                }
                .receipt-footer p {
                    margin: 5px 0;
                }
                button, .receipt-actions { 
                    display: none !important; 
                }
                .divider {
                    border-top: 1px dashed #000;
                    margin: 10px 0;
                }
                .store-info {
                    text-align: center;
                    margin-bottom: 15px;
                }
                .store-info i {
                    font-size: 24px;
                    color: #2E7D32;
                    margin-bottom: 5px;
                }
                .store-name {
                    font-size: 16px;
                    font-weight: bold;
                    margin: 5px 0;
                }
                .store-address {
                    font-size: 11px;
                    margin: 5px 0;
                }
            }
        </style>
    `;
    
    // Create enhanced receipt content
    const enhancedReceiptContent = `
        <div class="receipt">
            <div class="store-info">
                <i class="fas fa-leaf"></i>
                <div class="store-name">BauApp</div>
                <div class="store-address">Your One-Stop Resto Products</div>
            </div>
            <div class="receipt-header">
                <i class="fas fa-receipt"></i>
                <h2>ORDER RECEIPT</h2>
                <p><i class="fas fa-calendar"></i> Date: ${new Date().toLocaleDateString()}</p>
                <p><i class="fas fa-clock"></i> Time: ${new Date().toLocaleTimeString()}</p>
            </div>
            <div class="receipt-items">
                ${Array.from(receipt.querySelectorAll('.receipt-item')).map(item => `
                    <div class="receipt-item">
                        <span class="item-name">${item.querySelector('.item-name').textContent}</span>
                        <span class="item-quantity">${item.querySelector('.item-quantity').textContent}</span>
                        <span class="item-price">${item.querySelector('.item-price').textContent}</span>
                    </div>
                `).join('')}
            </div>
            <div class="receipt-total-row">
                <span><i class="fas fa-calculator"></i> Total Amount:</span>
                <span>${receipt.querySelector('.receipt-total-row span:last-child').textContent}</span>
            </div>
            <div class="receipt-footer">
                <i class="fas fa-heart"></i>
                <p>Thank you for your purchase!</p>
                <p>Please come again</p>
                <div class="divider"></div>
                <p><i class="fas fa-phone"></i> Contact: (123) 456-7890</p>
                <p><i class="fas fa-envelope"></i> Email: support@bauapp.com</p>
            </div>
        </div>
    `;
    
    // Write the content to the new window
    printWindow.document.write(`
        <!DOCTYPE html>
        <html>
        <head>
            <title>Receipt</title>
            ${printStyles}
        </head>
        <body>
            ${enhancedReceiptContent}
        </body>
        </html>
    `);
    
    // Wait for content to load then print
    printWindow.document.close();
    printWindow.onload = function() {
        printWindow.print();
        printWindow.close();
    };
    
    // Restore the action buttons
    receiptActions.style.display = originalDisplay;
    
    // Show success notification
    showNotification('Receipt printed successfully!', 'success');
} 