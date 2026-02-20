<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NAIGO - Online Restaurant Reservation</title>
    <meta name="description" content="NAIGO - Reserve your table at the finest restaurants. Instant confirmation, exclusive dining experiences.">
    <link rel="stylesheet" href="../../css/serve_asset.php?file=design-system.css">
    <link rel="stylesheet" href="../../css/serve_asset.php?file=homepage.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
<div class="homepage">
    <!-- ===== Navbar ===== -->
    <nav class="home-navbar" id="homeNav">
        <a href="#" class="brand">
            <div class="navbar-logo-icon" aria-hidden="true" style="font-size: 1.25rem;"><i class="fa-solid fa-concierge-bell"></i></div>
            <span class="brand-text">NAIGO<span class="sub">Online Restaurant Reservation</span></span>
        </a>
        <div class="nav-links">
            <a href="../auth/login.php" class="nav-link nav-link-ghost">Login</a>
            <a href="../forms/signup.php" class="nav-link nav-link-gold">Register</a>
        </div>
    </nav>

    <!-- ===== Hero ===== -->
    <section class="hero">
        <div class="hero-content">
            <div class="hero-badge">
                <span class="hero-badge-dot"></span> Now Accepting Reservations
            </div>
            <h1>Reserve Your Table at the <span class="gold">Finest Restaurants</span></h1>
            <p class="hero-subtitle">
                Discover exceptional Filipino dining. Browse curated restaurants,
                pick your perfect table, and confirm your reservation instantly.
            </p>
            <div class="hero-actions">
                <a href="../forms/signup.php" class="hero-btn hero-btn-primary">
                    <i class="fa-solid fa-utensils"></i> Get Started
                </a>
                <a href="../auth/login.php" class="hero-btn hero-btn-secondary">
                    <i class="fa-solid fa-right-to-bracket"></i> Sign In
                </a>
            </div>
            <div class="stats-bar">
                <div class="stat-item">
                    <div class="stat-number">500+</div>
                    <div class="stat-label">Restaurants</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number">10K+</div>
                    <div class="stat-label">Reservations</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number">4.8★</div>
                    <div class="stat-label">Average Rating</div>
                </div>
            </div>
        </div>
    </section>

    <!-- ===== Features ===== -->
    <section class="features">
        <div class="features-grid">
            <div class="feature-card">
                <div class="feature-icon"><i class="fa-solid fa-bolt"></i></div>
                <h3>Instant Confirmation</h3>
                <p>Get your reservation confirmed in seconds. No waiting, no phone calls.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon"><i class="fa-solid fa-chair"></i></div>
                <h3>Choose Your Table</h3>
                <p>Select indoor, outdoor, private, or bar seating based on your preference.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon"><i class="fa-solid fa-xmark"></i></div>
                <h3>Free Cancellation</h3>
                <p>Plans change. Cancel your reservation anytime without any charges.</p>
            </div>
        </div>
    </section>

    <!-- ===== Footer ===== -->
    <?php include __DIR__ . '/../includes/layout/footer.php'; ?>
</div>

<script>
    // Navbar scroll effect
    window.addEventListener('scroll', () => {
        const nav = document.getElementById('homeNav');
        nav.classList.toggle('scrolled', window.scrollY > 50);
    });
</script>
</body>
</html>