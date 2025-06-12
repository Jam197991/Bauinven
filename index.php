<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BauApp Ordering System</title>
    <link rel="stylesheet" href="css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="img/bau.jpg" rel="icon">
    <style>
        :root {
            --primary-color: #2e7d32;
            --accent-color: #4caf50;
            --text-color: #333;
            --background-color: #f5f5f5;
            --card-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            --hover-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            --transition-speed: 0.3s;
        }

        .loading-overlay {
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

        .loading-overlay.active {
            opacity: 1;
        }

        .loading-container {
            position: relative;
            width: 120px;
            height: 120px;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .loading-leaf {
            position: absolute;
            font-size: 2.5rem;
            color: #2e7d32;
            opacity: 0;
            transform: scale(0.5);
            transition: all 0.3s ease;
        }

        .loading-leaf.main {
            font-size: 3.5rem;
            animation: mainLeafSpin 2s infinite ease-in-out;
            opacity: 1;
            transform: scale(1);
        }

        .loading-leaf.orbit {
            animation: orbitLeaf 3s infinite linear;
        }

        .loading-leaf.orbit:nth-child(2) {
            animation-delay: -1s;
        }

        .loading-leaf.orbit:nth-child(3) {
            animation-delay: -2s;
        }

        .loading-text {
            margin-top: 2rem;
            color: #2e7d32;
            font-size: 1.2rem;
            font-weight: 500;
            opacity: 0;
            transform: translateY(10px);
            transition: all 0.3s ease;
        }

        .loading-text.active {
            opacity: 1;
            transform: translateY(0);
        }

        .loading-dots {
            display: inline-block;
            animation: loadingDots 1.5s infinite;
        }

        .loading-progress {
            width: 200px;
            height: 4px;
            background: rgba(46, 125, 50, 0.1);
            border-radius: 2px;
            margin-top: 1rem;
            overflow: hidden;
            opacity: 0;
            transform: translateY(10px);
            transition: all 0.3s ease;
        }

        .loading-progress.active {
            opacity: 1;
            transform: translateY(0);
        }

        .loading-progress-bar {
            width: 0%;
            height: 100%;
            background: #2e7d32;
            border-radius: 2px;
            transition: width 1.5s ease;
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

        .chef-login {
            position: absolute;
            top: 20px;
            right: 20px;
            z-index: 100;
            display: flex;
            gap: 1rem;
        }

        .chef-login-btn {
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
            text-decoration: none;
        }

        .chef-login-btn:hover {
            transform: translateY(-2px);
            box-shadow: var(--hover-shadow);
        }

        .inventory-login-btn {
            background: #FF9800;
        }

        .inventory-login-btn:hover {
            background: #F57C00;
        }

        .chef-login-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }

        .chef-login-form {
            background: white;
            padding: 2rem;
            border-radius: 15px;
            box-shadow: var(--card-shadow);
            width: 100%;
            max-width: 400px;
            animation: fadeIn 0.3s ease-out;
        }

        .chef-login-form h2 {
            color: var(--text-color);
            margin-bottom: 1.5rem;
            text-align: center;
        }

        .form-group {
            margin-bottom: 1rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--text-color);
        }

        .form-group input {
            width: 100%;
            padding: 0.8rem;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 1rem;
        }

        .form-group input:focus {
            outline: none;
            border-color: var(--primary-color);
        }

        .chef-login-submit {
            width: 100%;
            background: var(--primary-color);
            color: white;
            padding: 1rem;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 500;
            transition: all var(--transition-speed);
        }

        .chef-login-submit:hover {
            background: var(--accent-color);
        }

        .error-message {
            color: #dc3545;
            margin-top: 1rem;
            text-align: center;
            display: none;
        }

        .chef-login-modal .loading-overlay {
            display: none;
            position: absolute;
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

        .chef-login-modal .loading-overlay.active {
            opacity: 1;
        }

        .chef-login-modal .loading-container {
            position: relative;
            width: 120px;
            height: 120px;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .chef-login-modal .loading-leaf {
            position: absolute;
            font-size: 2.5rem;
            color: var(--primary-color);
            opacity: 0;
            transform: scale(0.5);
            transition: all 0.3s ease;
        }

        .chef-login-modal .loading-leaf.main {
            font-size: 3.5rem;
            animation: mainLeafSpin 2s infinite ease-in-out;
            opacity: 1;
            transform: scale(1);
        }

        .chef-login-modal .loading-leaf.orbit {
            animation: orbitLeaf 3s infinite linear;
        }

        .chef-login-modal .loading-leaf.orbit:nth-child(2) {
            animation-delay: -1s;
        }

        .chef-login-modal .loading-leaf.orbit:nth-child(3) {
            animation-delay: -2s;
        }

        .chef-login-modal .loading-text {
            margin-top: 2rem;
            color: var(--primary-color);
            font-size: 1.2rem;
            font-weight: 500;
            opacity: 0;
            transform: translateY(10px);
            transition: all 0.3s ease;
        }

        .chef-login-modal .loading-text.active {
            opacity: 1;
            transform: translateY(0);
        }

        .chef-login-modal .loading-dots {
            display: inline-block;
            animation: loadingDots 1.5s infinite;
        }

        .form-control {
            width: 100%;
            padding: 0.8rem;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 1rem;
            margin-bottom: 1rem;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary-color);
        }
    </style>
</head>
<body>
    <div class="chef-login">
        <a href="#" onclick="showChefLogin(); return false;" class="chef-login-btn">
            <i class="fas fa-utensils"></i>
            Chef Login
        </a>
        <a href="login.php" class="chef-login-btn inventory-login-btn">
            <i class="fas fa-boxes"></i>
            Inventory Staff
        </a>
    </div>

    <div class="chef-login-modal" id="chefLoginModal">
        <div class="chef-login-form">
            <h2><i class="fas fa-utensils"></i> Login</h2>
            <form id="chefLoginForm" action="chef_login.php" method="POST">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" required>
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <button type="submit" class="chef-login-submit">Login</button>
                <div class="error-message" id="loginError"></div>
            </form>
            <div class="loading-overlay">
                <div class="loading-container">
                    <i class="fas fa-leaf loading-leaf main"></i>
                    <i class="fas fa-leaf loading-leaf orbit"></i>
                    <i class="fas fa-leaf loading-leaf orbit"></i>
                    <i class="fas fa-leaf loading-leaf orbit"></i>
                </div>
                <div class="loading-text">Logging in<span class="loading-dots">...</span></div>
            </div>
        </div>
    </div>

    <div class="loading-overlay" id="loadingOverlay">
        <div class="loading-container">
            <i class="fas fa-leaf loading-leaf main"></i>
            <i class="fas fa-leaf loading-leaf orbit"></i>
            <i class="fas fa-leaf loading-leaf orbit"></i>
            <i class="fas fa-leaf loading-leaf orbit"></i>
        </div>
        <div class="loading-text">Start Ordering<span class="loading-dots">...</span></div>
        <div class="loading-progress">
            <div class="loading-progress-bar"></div>
        </div>
    </div>
    <div class="welcome-container">
        <div class="floating-elements">
            <div class="floating-item" style="--delay: 0s; --x: 10%; --y: 20%;">
                <i class="fas fa-leaf"></i>
            </div>
            <div class="floating-item" style="--delay: 1s; --x: 80%; --y: 30%;">
                <i class="fas fa-seedling"></i>
            </div>
            <div class="floating-item" style="--delay: 2s; --x: 20%; --y: 70%;">
                <i class="fas fa-apple-alt"></i>
            </div>
            <div class="floating-item" style="--delay: 3s; --x: 70%; --y: 80%;">
                <i class="fas fa-carrot"></i>
            </div>
        </div>
        <div class="welcome-content">
            <div class="logo-container">
                <img src="img/bau.jpg" style="width:100px;height:100px">
            </div>
            <h1>Welcome to BauApp</h1>
            <h2>Food Ordering System</h2>
            <p>Your one-stop resto products</p>
            <div class="welcome-features">
                <div class="feature">
                    <i class="fas fa-truck"></i>
                    <span>Fast Delivery</span>
                </div>
                <div class="feature">
                    <i class="fas fa-leaf"></i>
                    <span>Fresh Products</span>
                </div>
                <div class="feature">
                    <i class="fas fa-shield-alt"></i>
                    <span>Quality Assured</span>
                </div>
            </div>
            <a href="dashboard.php" class="welcome-btn">
                <span>Start Ordering</span>
                <i class="fas fa-arrow-right"></i>
            </a>
        </div>
    </div>
    <script src="js/script.js"></script>

    <script>
        // Function to show the login modal
        function showChefLogin() {
            document.getElementById('chefLoginModal').style.display = 'flex';
        }
        
        // Close the modal when clicking outside of it
        window.onclick = function(event) {
            const modal = document.getElementById('chefLoginModal');
            if (event.target === modal) {
                modal.style.display = 'none';
                // Reset form and error message when closing
                document.getElementById('chefLoginForm').reset();
                document.getElementById('loginError').textContent = '';
            }
        };
        
        // Handle form submission
        document.getElementById('chefLoginForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Show loading animation
            const loadingOverlay = this.parentElement.querySelector('.loading-overlay');
            const loadingText = loadingOverlay.querySelector('.loading-text');
            loadingOverlay.style.display = 'flex';
            setTimeout(() => {
                loadingOverlay.classList.add('active');
                loadingText.classList.add('active');
            }, 50);
            
            const formData = new FormData(this);
            
            // Send login request to server
            fetch('chef_login.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                // Hide loading animation
                loadingOverlay.classList.remove('active');
                loadingText.classList.remove('active');
                
                if (data.success) {
                    // Login successful - redirect to chef.php
                    setTimeout(() => {
                        loadingOverlay.style.display = 'none';
                        window.location.href = 'chef.php';
                    }, 300);
                } else {
                    // Login failed
                    setTimeout(() => {
                        loadingOverlay.style.display = 'none';
                        const errorMessage = document.getElementById('loginError');
                        errorMessage.textContent = data.message || 'Login failed. Please try again.';
                        errorMessage.style.display = 'block';
                    }, 300);
                }
            })
            .catch(error => {
                // Hide loading animation
                loadingOverlay.classList.remove('active');
                loadingText.classList.remove('active');
                setTimeout(() => {
                    loadingOverlay.style.display = 'none';
                    const errorMessage = document.getElementById('loginError');
                    errorMessage.textContent = 'An error occurred. Please try again.';
                    errorMessage.style.display = 'block';
                }, 300);
                console.error('Error:', error);
            });
        });
    </script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const welcomeBtn = document.querySelector('.welcome-btn');
            const loadingOverlay = document.getElementById('loadingOverlay');
            const loadingText = loadingOverlay.querySelector('.loading-text');
            const loadingProgress = loadingOverlay.querySelector('.loading-progress');
            const progressBar = loadingOverlay.querySelector('.loading-progress-bar');

            welcomeBtn.addEventListener('click', function(e) {
                e.preventDefault();
                
                // Show loading overlay with fade effect
                loadingOverlay.style.display = 'flex';
                setTimeout(() => {
                    loadingOverlay.classList.add('active');
                    loadingText.classList.add('active');
                    loadingProgress.classList.add('active');
                    progressBar.style.width = '100%';
                }, 50);
                
                // Redirect after animation
                setTimeout(() => {
                    window.location.href = 'dashboard.php';
                }, 2000);
            });
        });

        function showChefLogin() {
            const modal = document.getElementById('chefLoginModal');
            modal.style.display = 'flex';
        }

        // Close modal when clicking outside
        document.getElementById('chefLoginModal').addEventListener('click', function(e) {
            if (e.target === this) {
                this.style.display = 'none';
            }
        });

        function showInventoryLogin() {
            const modal = document.getElementById('inventoryLoginModal');
            modal.style.display = 'flex';
        }

        // Close modal when clicking outside
        document.getElementById('inventoryLoginModal').addEventListener('click', function(e) {
            if (e.target === this) {
                this.style.display = 'none';
            }
        });

        // Inventory login form submission
        document.getElementById('inventoryLoginForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const form = this;
            const errorMessage = form.querySelector('.error-message');
            const loadingOverlay = form.parentElement.querySelector('.loading-overlay');
            const loadingText = loadingOverlay.querySelector('.loading-text');
            
            // Show loading animation
            loadingOverlay.style.display = 'flex';
            setTimeout(() => {
                loadingOverlay.classList.add('active');
                loadingText.classList.add('active');
            }, 50);

            const formData = new FormData(form);
            
            fetch('inv_login.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Hide loading animation
                    loadingOverlay.classList.remove('active');
                    loadingText.classList.remove('active');
                    setTimeout(() => {
                        loadingOverlay.style.display = 'none';
                        // Redirect to appropriate dashboard
                        window.location.href = data.redirect;
                    }, 300);
                } else {
                    // Hide loading animation
                    loadingOverlay.classList.remove('active');
                    loadingText.classList.remove('active');
                    setTimeout(() => {
                        loadingOverlay.style.display = 'none';
                        errorMessage.textContent = data.message;
                        errorMessage.style.display = 'block';
                    }, 300);
                }
            })
            .catch(error => {
                // Hide loading animation
                loadingOverlay.classList.remove('active');
                loadingText.classList.remove('active');
                setTimeout(() => {
                    loadingOverlay.style.display = 'none';
                    errorMessage.textContent = 'An error occurred. Please try again.';
                    errorMessage.style.display = 'block';
                }, 300);
            });
        });
    </script>
</body>
</html>
