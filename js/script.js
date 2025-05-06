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
                    <button onclick="removeFromCart(${item.id})" class="remove-btn">×</button>
                </div>
            </div>
        `;
    });

    cartCount.textContent = count;
    cartTotal.textContent = `₱${total.toFixed(2)}`;
    totalAmount.textContent = `₱${total.toFixed(2)}`;
    mobileCartCount.textContent = count;
    mobileCartTotal.textContent = `₱${total.toFixed(2)}`;
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

// Mobile cart toggle functionality
function toggleCart() {
    const cartContainer = document.querySelector('.cart-container');
    cartContainer.classList.toggle('expanded');
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

// Close cart when clicking outside on mobile
document.addEventListener('click', function(event) {
    const cartContainer = document.querySelector('.cart-container');
    const cartToggle = document.querySelector('.cart-toggle');
    
    if (window.innerWidth <= 1023 && 
        !cartContainer.contains(event.target) && 
        !cartToggle.contains(event.target) &&
        cartContainer.classList.contains('expanded')) {
        cartContainer.classList.remove('expanded');
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

// Add notification
function showNotification(message, type = 'success') {
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    notification.innerHTML = `
        <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i>
        ${message}
    `;
    document.body.appendChild(notification);
    setTimeout(() => {
        notification.remove();
    }, 3000);
}

// Optimize touch interactions
document.addEventListener('touchstart', function() {}, {passive: true});

// Prevent double-tap zoom on mobile
document.addEventListener('touchend', function(event) {
    event.preventDefault();
    event.target.click();
}, {passive: false}); 