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
}

function checkout() {
    if (cart.length === 0) {
        showNotification('Your cart is empty!', 'error');
        return;
    }

    // Show receipt modal
    showReceipt();
}

// Advanced Animation System
const AnimationSystem = {
    // Easing functions for smoother animations
    easings: {
        easeOutExpo: t => t === 1 ? 1 : 1 - Math.pow(2, -10 * t),
        easeOutBack: t => {
            const c1 = 1.70158;
            const c3 = c1 + 1;
            return 1 + c3 * Math.pow(t - 1, 3) + c1 * Math.pow(t - 1, 2);
        },
        easeOutElastic: t => {
            const c4 = (2 * Math.PI) / 3;
            return t === 0 ? 0 : t === 1 ? 1 : Math.pow(2, -10 * t) * Math.sin((t * 10 - 0.75) * c4) + 1;
        },
        easeOutBounce: t => {
            const n1 = 7.5625;
            const d1 = 2.75;
            if (t < 1 / d1) return n1 * t * t;
            if (t < 2 / d1) return n1 * (t -= 1.5 / d1) * t + 0.75;
            if (t < 2.5 / d1) return n1 * (t -= 2.25 / d1) * t + 0.9375;
            return n1 * (t -= 2.625 / d1) * t + 0.984375;
        }
    },

    // Advanced animation function with multiple properties and easing
    animate(element, properties, options = {}) {
        const {
            duration = 500,
            easing = 'easeOutExpo',
            delay = 0,
            onComplete = () => {}
        } = options;

        const startTime = performance.now();
        const startProps = {};
        const endProps = {};
        const easingFn = this.easings[easing] || this.easings.easeOutExpo;

        // Get start values and prepare end values
        for (const prop in properties) {
            const computedStyle = getComputedStyle(element);
            startProps[prop] = parseFloat(computedStyle[prop]) || 0;
            endProps[prop] = properties[prop];
        }

        // Animation frame function
        const animate = (currentTime) => {
            const elapsed = currentTime - startTime - delay;
            const progress = Math.min(elapsed / duration, 1);
            const easedProgress = easingFn(progress);

            // Apply properties with easing
            for (const prop in properties) {
                const value = startProps[prop] + (endProps[prop] - startProps[prop]) * easedProgress;
                element.style[prop] = value + (prop === 'opacity' ? '' : 'px');
            }

            if (progress < 1) {
                requestAnimationFrame(animate);
            } else {
                onComplete();
            }
        };

        // Start animation after delay
        if (delay > 0) {
            setTimeout(() => requestAnimationFrame(animate), delay);
        } else {
            requestAnimationFrame(animate);
        }
    },

    // Staggered animation for multiple elements
    stagger(elements, properties, options = {}) {
        const {
            staggerDelay = 50,
            ...animationOptions
        } = options;

        elements.forEach((element, index) => {
            this.animate(element, properties, {
                ...animationOptions,
                delay: index * staggerDelay
            });
        });
    },

    // Fade in with transform
    fadeIn(element, options = {}) {
        const {
            duration = 400,
            transform = 'translateY(20px)',
            ...rest
        } = options;

        element.style.opacity = '0';
        element.style.transform = transform;
        element.style.transition = 'none';

        requestAnimationFrame(() => {
            this.animate(element, {
                opacity: 1,
                transform: 'translateY(0)'
            }, {
                duration,
                ...rest
            });
        });
    },

    // Fade out with transform
    fadeOut(element, options = {}) {
        const {
            duration = 400,
            transform = 'translateY(-20px)',
            onComplete = () => {},
            ...rest
        } = options;

        this.animate(element, {
            opacity: 0,
            transform
        }, {
            duration,
            onComplete,
            ...rest
        });
    }
};

// Enhanced notification system with smoother animations
function showNotification(message, type = 'success') {
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    notification.innerHTML = `
        <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i>
        ${message}
    `;
    
    // Remove existing notifications with fade out
    document.querySelectorAll('.notification').forEach(n => {
        AnimationSystem.fadeOut(n, {
            duration: 200,
            onComplete: () => n.remove()
        });
    });
    
    document.body.appendChild(notification);
    
    // Enhanced entrance animation
    AnimationSystem.fadeIn(notification, {
        duration: 300,
        easing: 'easeOutBack',
        transform: 'translateX(100%) scale(0.8)'
    });
    
    // Vibrate with optimized pattern
    if ('vibrate' in navigator) {
        navigator.vibrate([30, 20, 30]);
    }
    
    setTimeout(() => {
        AnimationSystem.fadeOut(notification, {
            duration: 300,
            easing: 'easeOutExpo',
            transform: 'translateX(100%) scale(0.95)',
            onComplete: () => notification.remove()
        });
    }, 2500);
}

// Enhanced receipt display with smoother animations
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

    // Generate receipt items with enhanced staggered animation
    let total = 0;
    receiptItems.innerHTML = '';
    
    const receiptElements = [];
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
        receiptElements.push(receiptItem);
    });

    // Enhanced staggered animation for receipt items
    AnimationSystem.stagger(receiptElements, {
        opacity: 1,
        transform: 'translateY(0)'
    }, {
        duration: 300,
        easing: 'easeOutBack',
        staggerDelay: 50
    });

    // Add total with enhanced animation
    receiptTotal.innerHTML = `
        <div class="receipt-total-row">
            <span>Total Amount:</span>
            <span>₱${total.toFixed(2)}</span>
        </div>
    `;

    // Enhanced modal animation
    modal.style.display = 'flex';
    modal.style.opacity = '0';
    
    AnimationSystem.fadeIn(modal, {
        duration: 400,
        easing: 'easeOutBack',
        transform: 'scale(0.95)'
    });
}

// Enhanced cart toggle with smoother animations
function toggleCart() {
    const cartContainer = document.querySelector('.cart-container');
    const mainContent = document.querySelector('main');
    
    if (cart.length === 0) {
        showNotification('Your cart is empty!', 'error');
        return;
    }

    if (cartContainer) {
        const isExpanding = !cartContainer.classList.contains('expanded');
        
        // Enhanced cart animation
        AnimationSystem.animate(cartContainer, {
            transform: isExpanding ? 'translateY(0)' : 'translateY(100%)',
            opacity: isExpanding ? 1 : 0
        }, {
            duration: 400,
            easing: 'easeOutBack'
        });
        
        cartContainer.classList.toggle('expanded');
        
        // Update button text with smooth transition
        const viewCartBtn = document.querySelector('.view-cart-btn');
        if (viewCartBtn) {
            viewCartBtn.innerHTML = isExpanding 
                ? '<i class="fas fa-times"></i> Close Cart' 
                : '<i class="fas fa-shopping-cart"></i> View Cart';
        }

        // Enhanced main content transition
        if (mainContent) {
            AnimationSystem.animate(mainContent, {
                opacity: isExpanding ? 0.5 : 1
            }, {
                duration: 300,
                easing: 'easeOutExpo'
            });
            
            mainContent.style.gridTemplateColumns = isExpanding ? '1fr 2fr 1fr' : '1fr 2fr';
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
            AnimationSystem.animate(document.body, {
                transform: `translateY(${pullProgress * 50}px)`
            }, {
                duration: 100,
                easing: 'easeOutExpo'
            });
        }
    }, { passive: true });
    
    document.addEventListener('touchend', function(e) {
        if (!isPulling) return;
        
        const pullDistance = touchStartY - e.changedTouches[0].clientY;
        
        AnimationSystem.animate(document.body, {
            transform: 'translateY(0)'
        }, {
            duration: 300,
            easing: 'easeOutBack',
            onComplete: () => {
                if (pullDistance > pullThreshold) {
                    refreshContent();
                }
            }
        });
        
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
                AnimationSystem.animate(cartContainer, {
                    transform: `translateX(${swipeDistance * 0.5}px)`
                }, {
                    duration: 100,
                    easing: 'easeOutExpo'
                });
            }
        }
    }, { passive: true });

    document.addEventListener('touchend', function(e) {
        if (!isSwiping) return;
        
        const cartContainer = document.querySelector('.cart-container');
        if (cartContainer && cartContainer.classList.contains('expanded')) {
            const swipeDistance = cartStartX - e.changedTouches[0].clientX;
            
            AnimationSystem.animate(cartContainer, {
                transform: 'translateX(0)'
            }, {
                duration: 300,
                easing: 'easeOutBack',
                onComplete: () => {
                    if (swipeDistance > 50) {
                        toggleCart();
                    }
                }
            });
        }
        
        isSwiping = false;
    }, { passive: true });

    // Enhanced touch feedback
    document.querySelectorAll('.product-card, .category-card, button').forEach(element => {
        element.addEventListener('touchstart', function() {
            AnimationSystem.animate(this, {
                transform: 'scale(0.98)'
            }, {
                duration: 100,
                easing: 'easeOutExpo'
            });
        }, { passive: true });
        
        element.addEventListener('touchend', function() {
            AnimationSystem.animate(this, {
                transform: 'scale(1)'
            }, {
                duration: 200,
                easing: 'easeOutBack'
            });
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
    if (addOrderBtn) {
        const originalText = addOrderBtn.innerHTML;
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
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Order added successfully!', 'success');
            // Clear the cart after successful order
            cart = [];
            updateCart();
            saveCart();
            closeReceipt();
        } else {
            showNotification(data.message || 'Error adding order', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Error adding order', 'error');
    })
    .finally(() => {
        // Reset button state
        if (addOrderBtn) {
            addOrderBtn.innerHTML = originalText;
            addOrderBtn.disabled = false;
        }
    });
}

// Enhanced category selection with optimized animations
function selectCategory(categoryId) {
    const productsGrid = document.querySelector('.products-grid');
    const categories = document.querySelectorAll('.category-card');
    
    // Show loading state with smooth transition
    if (productsGrid) {
        productsGrid.style.opacity = '0.5';
        productsGrid.style.transition = 'opacity 0.3s ease';
        productsGrid.innerHTML = '<div class="loading-products"><i class="fas fa-spinner fa-spin"></i> Loading products...</div>';
    }

    // Update active category with smooth transition
    categories.forEach(category => {
        if (category.getAttribute('href').includes(categoryId)) {
            AnimationSystem.animate(category, {
                transform: 'translateY(-8px) scale(1.02)',
                boxShadow: '0 6px 12px rgba(0, 0, 0, 0.15)'
            }, {
                duration: 300,
                easing: 'easeOutBack'
            });
            category.classList.add('active');
        } else {
            category.classList.remove('active');
            category.style.transform = '';
            category.style.boxShadow = '';
        }
    });

    // Fetch products with optimized loading
    fetch(`get_products.php?category=${categoryId}`)
        .then(response => response.text())
        .then(html => {
            if (productsGrid) {
                // Fade out current content
                AnimationSystem.fadeOut(productsGrid, {
                    duration: 200,
                    onComplete: () => {
                        // Update content
                        productsGrid.innerHTML = html;
                        
                        // Reset quantities for new products
                        const newProducts = productsGrid.querySelectorAll('.product-card');
                        newProducts.forEach(product => {
                            const productId = product.querySelector('.quantity-btn').getAttribute('onclick').match(/\d+/)[0];
                            quantities[productId] = 0;
                            updateQuantityDisplay(productId);
                        });
                        
                        // Fade in new content with staggered animation
                        const productCards = productsGrid.querySelectorAll('.product-card');
                        AnimationSystem.stagger(productCards, {
                            opacity: 1,
                            transform: 'translateY(0)'
                        }, {
                            duration: 300,
                            easing: 'easeOutBack',
                            staggerDelay: 30
                        });
                    }
                });
            }
        })
        .catch(error => {
            console.error('Error:', error);
            if (productsGrid) {
                productsGrid.innerHTML = '<p class="error-message"><i class="fas fa-exclamation-circle"></i> Error loading products</p>';
            }
        });
} 