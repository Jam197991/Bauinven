<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BauApp Ordering System</title>
    <link rel="stylesheet" href="css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
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
            color: var(--primary-color);
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
            color: var(--primary-color);
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
            background: var(--primary-color);
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
    </style>
</head>
<body>
    <div class="loading-overlay">
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
                <i class="fas fa-leaf logo-icon"></i>
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
        document.querySelector('.welcome-btn').addEventListener('click', function(e) {
            e.preventDefault();
            const loadingOverlay = document.querySelector('.loading-overlay');
            const loadingText = document.querySelector('.loading-text');
            const loadingProgress = document.querySelector('.loading-progress');
            const progressBar = document.querySelector('.loading-progress-bar');
            
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
    </script>
</body>
</html>
