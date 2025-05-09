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

    // Add touch event listeners for cart swipe
    let touchStartY = 0;
    let touchEndY = 0;
    const cartContainer = document.querySelector('.cart-container');

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
});

// Save cart to localStorage whenever it changes
function saveCart() {
    localStorage.setItem('cart', JSON.stringify(cart));
    localStorage.setItem('quantities', JSON.stringify(quantities));
}

function increaseQuantity(productId) {
    quantities[productId] = (quantities[productId] || 0) + 1;
    updateQuantityDisplay(productId);
    saveCart();
}

function decreaseQuantity(productId) {
    if (quantities[productId] > 0) {
        quantities[productId]--;
        updateQuantityDisplay(productId);
        saveCart();
    }
}

function updateQuantityDisplay(productId) {
    document.getElementById('quantity-' + productId).textContent = quantities[productId] || 0;
}

function addToCart(id, name, price) {
    const quantity = quantities[id] || 1;
    if (quantity <= 0) return;

    const existingItem = cart.find(item => item.id === id);
    if (existingItem) {
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
    if (cartContainer && window.innerWidth <= 1023) {
        cartContainer.classList.add('expanded');
        // Animate new items
        const cartItems = cartContainer.querySelectorAll('.cart-item');
        cartItems.forEach((item, index) => {
            item.style.animationDelay = `${index * 0.1}s`;
        });
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
    const cartToggle = document.querySelector('.cart-toggle');
    
    if (!cartItems) return;
    
    cartItems.innerHTML = '';
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

    // Show/hide cart toggle based on cart content
    if (cartToggle) {
        cartToggle.style.display = count > 0 ? 'flex' : 'none';
    }

    // Update cart container visibility
    if (cartContainer) {
        cartContainer.style.display = count > 0 ? 'block' : 'none';
    }

    // Save cart to localStorage
    saveCart();
}

function checkout() {
    if (cart.length === 0) {
        showNotification('Your cart is empty!', 'error');
        return;
    }

    // Prepare order data
    const orderData = {
        items: cart,
        total: cart.reduce((sum, item) => sum + (item.price * item.quantity), 0)
    };

    // Save order to database
    fetch('save_order.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(orderData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showReceipt();
            // Clear cart after successful order
            cart = [];
            quantities = {};
            updateCart();
            showNotification('Order placed successfully!', 'success');
        } else {
            showNotification('Error saving order: ' + data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Error saving order. Please try again.', 'error');
    });
}

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

    // Generate receipt items
    let total = 0;
    receiptItems.innerHTML = '';
    
    cart.forEach(item => {
        const itemTotal = item.price * item.quantity;
        total += itemTotal;
        
        receiptItems.innerHTML += `
            <div class="receipt-item">
                <span class="item-name">${item.name}</span>
                <span class="item-quantity">${item.quantity}</span>
                <span class="item-price">₱${itemTotal.toFixed(2)}</span>
            </div>
        `;
    });

    // Add total
    receiptTotal.innerHTML = `
        <div class="receipt-total-row">
            <span>Total Amount:</span>
            <span>₱${total.toFixed(2)}</span>
        </div>
    `;

    // Show modal
    modal.style.display = 'flex';
}

function closeReceipt() {
    const modal = document.getElementById('receipt-modal');
    modal.style.display = 'none';
}

function printReceipt() {
    const receiptContent = document.querySelector('.receipt').innerHTML;
    const printWindow = window.open('', '', 'height=600,width=800');
    
    printWindow.document.write(`
        <html>
            <head>
                <title>BauApp Receipt</title>
                <style>
                    body { font-family: monospace; }
                    .receipt { width: 300px; margin: 0 auto; }
                    .receipt-header { text-align: center; margin-bottom: 20px; }
                    .receipt-item { display: flex; justify-content: space-between; margin: 5px 0; }
                    .receipt-total-row { display: flex; justify-content: space-between; margin-top: 20px; font-weight: bold; }
                    .receipt-footer { text-align: center; margin-top: 20px; }
                    @media print {
                        body { margin: 0; padding: 20px; }
                    }
                </style>
            </head>
            <body>
                <div class="receipt">
                    ${receiptContent}
                </div>
            </body>
        </html>
    `);
    
    printWindow.document.close();
    printWindow.focus();
    printWindow.print();
    printWindow.close();
}

// Enhanced Cart Functionality
function toggleCart() {
    const cartContainer = document.querySelector('.cart-container');
    
    if (cartContainer) {
        cartContainer.classList.toggle('expanded');
        
        // Update button text
        const viewCartBtn = document.querySelector('.view-cart-btn');
        if (viewCartBtn) {
            viewCartBtn.innerHTML = cartContainer.classList.contains('expanded') 
                ? '<i class="fas fa-times"></i> Close Cart' 
                : '<i class="fas fa-shopping-cart"></i> View Cart';
        }
        
        // Prevent body scroll when cart is open
        document.body.style.overflow = cartContainer.classList.contains('expanded') ? 'hidden' : '';
        
        // Add haptic feedback
        if ('vibrate' in navigator) {
            navigator.vibrate(50);
        }

        // Animate cart items
        if (cartContainer.classList.contains('expanded')) {
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
    const cartToggle = document.querySelector('.cart-toggle');
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

// Close cart when clicking outside
document.addEventListener('click', function(event) {
    const cartContainer = document.querySelector('.cart-container');
    const cartToggle = document.querySelector('.cart-toggle');
    
    if (cartContainer && cartToggle && 
        !cartContainer.contains(event.target) && 
        !cartToggle.contains(event.target) &&
        cartContainer.classList.contains('expanded')) {
        toggleCart();
    }
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
    // Add pull-to-refresh functionality
    let touchStartY = 0;
    let touchEndY = 0;
    const pullThreshold = 100;
    
    document.addEventListener('touchstart', function(e) {
        touchStartY = e.touches[0].clientY;
    }, { passive: true });
    
    document.addEventListener('touchend', function(e) {
        touchEndY = e.changedTouches[0].clientY;
        const pullDistance = touchStartY - touchEndY;
        
        if (pullDistance > pullThreshold && window.scrollY === 0) {
            refreshContent();
        }
    }, { passive: true });

    // Handle cart swipe gestures
    let cartStartX = 0;
    let cartEndX = 0;

    document.addEventListener('touchstart', function(e) {
        const cartContainer = document.querySelector('.cart-container');
        if (cartContainer && cartContainer.classList.contains('expanded')) {
            cartStartX = e.touches[0].clientX;
        }
    }, { passive: true });

    document.addEventListener('touchend', function(e) {
        const cartContainer = document.querySelector('.cart-container');
        if (cartContainer && cartContainer.classList.contains('expanded')) {
            cartEndX = e.changedTouches[0].clientX;
            const swipeDistance = cartStartX - cartEndX;
            
            if (swipeDistance > 50) {
                toggleCart();
            }
        }
    }, { passive: true });

    // Add haptic feedback
    function vibrateDevice() {
        if ('vibrate' in navigator) {
            navigator.vibrate(50);
        }
    }

    // Add haptic feedback to buttons
    document.querySelectorAll('button').forEach(button => {
        button.addEventListener('click', vibrateDevice);
    });

    // Add double-tap to zoom for product images
    document.querySelectorAll('.product-card img').forEach(img => {
        let lastTap = 0;
        img.addEventListener('click', function(e) {
            const currentTime = new Date().getTime();
            const tapLength = currentTime - lastTap;
            
            if (tapLength < 300 && tapLength > 0) {
                e.preventDefault();
                if (img.style.transform === 'scale(1.5)') {
                    img.style.transform = 'scale(1)';
                } else {
                    img.style.transform = 'scale(1.5)';
                }
            }
            lastTap = currentTime;
        });
    });

    // Add loading states
    function showLoading(element) {
        element.classList.add('loading-skeleton');
        element.style.pointerEvents = 'none';
    }

    function hideLoading(element) {
        element.classList.remove('loading-skeleton');
        element.style.pointerEvents = 'auto';
    }

    // Enhanced touch feedback
    document.querySelectorAll('.product-card, .category-card, button').forEach(element => {
        element.addEventListener('touchstart', function() {
            this.style.transform = 'scale(0.98)';
        }, { passive: true });
        
        element.addEventListener('touchend', function() {
            this.style.transform = '';
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

// Enhanced notification system
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
    
    // Add entrance animation
    notification.style.animation = 'slideIn 0.3s ease-out';
    
    // Vibrate on notification
    if ('vibrate' in navigator) {
        navigator.vibrate(100);
    }
    
    setTimeout(() => {
        notification.style.animation = 'fadeOut 0.3s ease-out';
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}

// Optimize touch interactions
document.addEventListener('touchstart', function() {}, {passive: true});

// Prevent double-tap zoom on mobile
document.addEventListener('touchend', function(event) {
    event.preventDefault();
    event.target.click();
}, {passive: false}); 