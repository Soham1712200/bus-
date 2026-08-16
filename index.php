<?php
/**
 * Jayambe Travels - Homepage
 * Hero Section, Animated Search Widget, Route Highlights & Fleet Showcase
 */

$pageTitle = 'Home - Gujarat Bus Reservation';
require_once __DIR__ . '/includes/header.php';

$pdo = get_db_connection();

// Fetch distinct cities for source and destination dropdowns
$cities = [
    'Ahmedabad', 'Surat', 'Vadodara', 'Rajkot', 'Bhavnagar', 
    'Junagadh', 'Jamnagar', 'Gandhinagar', 'Bhuj', 'Anand', 
    'Vapi', 'Navsari', 'Mehsana', 'Porbandar', 'Somnath', 'Dwarka'
];

if ($pdo) {
    try {
        $cityStmt = $pdo->query("SELECT DISTINCT source_city FROM routes UNION SELECT DISTINCT destination_city FROM routes ORDER BY 1 ASC");
        $fetched = $cityStmt->fetchAll(PDO::FETCH_COLUMN);
        if (!empty($fetched)) {
            $cities = $fetched;
        }
    } catch (Exception $e) {}
}

// Popular routes showcase data
$popularRoutes = [
    ['from' => 'Ahmedabad', 'to' => 'Surat', 'dist' => '265 km', 'time' => '4h 30m', 'fare' => '290'],
    ['from' => 'Surat', 'to' => 'Ahmedabad', 'dist' => '265 km', 'time' => '4h 30m', 'fare' => '290'],
    ['from' => 'Ahmedabad', 'to' => 'Rajkot', 'dist' => '215 km', 'time' => '3h 45m', 'fare' => '240'],
    ['from' => 'Surat', 'to' => 'Vadodara', 'dist' => '150 km', 'time' => '2h 45m', 'fare' => '170'],
    ['from' => 'Ahmedabad', 'to' => 'Bhuj', 'dist' => '335 km', 'time' => '6h 30m', 'fare' => '370'],
    ['from' => 'Ahmedabad', 'to' => 'Somnath', 'dist' => '410 km', 'time' => '7h 30m', 'fare' => '450']
];
?>

<!-- Hero Section -->
<section class="hero-section">
    <div class="hero-bg-graphic"></div>
    <div class="hero-glow-circle"></div>

    <div class="container">
        <div class="hero-content">
            <div class="hero-badge">
                <i class="fas fa-certificate"></i> Gujarat's Premier State Transport Partner
            </div>
            <h1 class="hero-title">
                Experience Seamless Travel Across <span>Gujarat</span>
            </h1>
            <p class="hero-subtitle">
                Book over 560 daily luxury Volvo, Electric AC, Sleeper, and Express buses connecting 28 major corridors with real-time seat selection and instant E-Tickets.
            </p>
        </div>

        <!-- Search Widget Card -->
        <div class="search-widget-card">
            <form action="search.php" method="GET" id="busSearchForm">
                <div class="search-form-grid">
                    
                    <!-- Source City -->
                    <div class="form-group-custom">
                        <label class="form-label-custom" for="sourceCitySelect">
                            <i class="fas fa-location-dot"></i> From (Source)
                        </label>
                        <div class="input-icon-wrapper">
                            <i class="fas fa-bus input-icon"></i>
                            <select name="source" id="sourceCitySelect" class="form-control-custom" required>
                                <option value="" disabled>Select Departure City</option>
                                <?php foreach ($cities as $city): ?>
                                    <option value="<?= htmlspecialchars($city); ?>" <?= $city === 'Ahmedabad' ? 'selected' : ''; ?>>
                                        <?= htmlspecialchars($city); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <!-- Swap Button -->
                    <div>
                        <button type="button" class="btn-swap-locations" id="btnSwapCities" title="Swap Source & Destination">
                            <i class="fas fa-right-left"></i>
                        </button>
                    </div>

                    <!-- Destination City -->
                    <div class="form-group-custom">
                        <label class="form-label-custom" for="destCitySelect">
                            <i class="fas fa-map-pin"></i> To (Destination)
                        </label>
                        <div class="input-icon-wrapper">
                            <i class="fas fa-location-arrow input-icon"></i>
                            <select name="destination" id="destCitySelect" class="form-control-custom" required>
                                <option value="" disabled>Select Arrival City</option>
                                <?php foreach ($cities as $city): ?>
                                    <option value="<?= htmlspecialchars($city); ?>" <?= $city === 'Surat' ? 'selected' : ''; ?>>
                                        <?= htmlspecialchars($city); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <!-- Date of Journey -->
                    <div class="form-group-custom">
                        <label class="form-label-custom" for="journeyDateInput">
                            <i class="fas fa-calendar-days"></i> Date of Journey
                        </label>
                        <div class="input-icon-wrapper">
                            <i class="fas fa-calendar input-icon"></i>
                            <input type="date" name="journey_date" id="journeyDateInput" class="form-control-custom" required>
                        </div>
                        <div class="quick-date-row">
                            <span class="date-pill active" data-offset="0">Today</span>
                            <span class="date-pill" data-offset="1">Tomorrow</span>
                            <span class="date-pill" data-offset="2">Day After</span>
                        </div>
                    </div>

                    <!-- Bus Class Filter -->
                    <div class="form-group-custom">
                        <label class="form-label-custom" for="busClassSelect">
                            <i class="fas fa-filter"></i> Bus Class
                        </label>
                        <div class="input-icon-wrapper">
                            <i class="fas fa-couch input-icon"></i>
                            <select name="bus_class" id="busClassSelect" class="form-control-custom">
                                <option value="">All Fleet Classes</option>
                                <option value="Volvo AC 2+2">Volvo AC Luxury</option>
                                <option value="Electric AC">Electric AC Express</option>
                                <option value="Sleeper 2+1">Sleeper 2+1 Berth</option>
                                <option value="AC Seater">AC Deluxe Seater</option>
                                <option value="Gurjarnagri">Gurjarnagri Express</option>
                                <option value="Express">Standard Express</option>
                            </select>
                        </div>
                    </div>

                    <!-- Submit Search CTA -->
                    <div>
                        <button type="submit" class="btn-search-bus">
                            <i class="fas fa-magnifying-glass"></i> Search Buses
                        </button>
                    </div>

                </div>

                <!-- Popular Gujarat Quick Chips -->
                <div class="popular-searches">
                    <strong><i class="fas fa-fire text-primary me-1"></i> Popular Gujarat Routes:</strong>
                    <?php foreach ($popularRoutes as $pRoute): ?>
                        <a href="search.php?source=<?= urlencode($pRoute['from']); ?>&destination=<?= urlencode($pRoute['to']); ?>&journey_date=<?= date('Y-m-d'); ?>" class="route-chip" data-source="<?= htmlspecialchars($pRoute['from']); ?>" data-dest="<?= htmlspecialchars($pRoute['to']); ?>">
                            <span><?= htmlspecialchars($pRoute['from']); ?></span>
                            <i class="fas fa-arrow-right" style="font-size:10px; color:var(--primary);"></i>
                            <span><?= htmlspecialchars($pRoute['to']); ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>
            </form>
        </div>
    </div>
</section>

<!-- Live Stats Section -->
<section style="background: #ffffff; border-bottom: 1px solid #e2e8f0; padding: 24px 0;">
    <div class="container">
        <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; text-align: center;">
            <div>
                <div style="font-size: 28px; font-weight: 800; color: var(--primary);">560+</div>
                <div style="font-size: 13px; color: #64748b; font-weight: 600;">Daily Connected Buses</div>
            </div>
            <div>
                <div style="font-size: 28px; font-weight: 800; color: var(--secondary);">28</div>
                <div style="font-size: 13px; color: #64748b; font-weight: 600;">Major Gujarat Corridors</div>
            </div>
            <div>
                <div style="font-size: 28px; font-weight: 800; color: var(--accent);">99.4%</div>
                <div style="font-size: 13px; color: #64748b; font-weight: 600;">On-Time Departure Rate</div>
            </div>
            <div>
                <div style="font-size: 28px; font-weight: 800; color: var(--accent-gold);">24x7</div>
                <div style="font-size: 13px; color: #64748b; font-weight: 600;">Gujarat Office & Support</div>
            </div>
        </div>
    </div>
</section>

<!-- Why Choose Jayambe Travels -->
<section class="section-padding">
    <div class="container">
        <div class="section-header">
            <span class="section-tag">Why Travel With Us</span>
            <h2 class="section-title">Designed For Maximum Passenger Comfort</h2>
            <p class="section-desc">Reliable state transport services with modern amenities and verified safety standards.</p>
        </div>

        <div class="feature-grid">
            <div class="feature-card">
                <div class="feature-icon-wrap">
                    <i class="fas fa-couch"></i>
                </div>
                <h3>Live Seat Selection</h3>
                <p>Choose your preferred window, aisle, or sleeper berths with real-time interactive coach layout.</p>
            </div>

            <div class="feature-card">
                <div class="feature-icon-wrap">
                    <i class="fas fa-building-columns"></i>
                </div>
                <h3>Instant Net Banking</h3>
                <p>Simulate seamless payments with instant OTP verification across top Indian banks.</p>
            </div>

            <div class="feature-card">
                <div class="feature-icon-wrap">
                    <i class="fas fa-qrcode"></i>
                </div>
                <h3>Instant Digital E-Ticket</h3>
                <p>Instant printable digital tickets with high-security QR and Barcode verification.</p>
            </div>

            <div class="feature-card">
                <div class="feature-icon-wrap">
                    <i class="fas fa-rotate-left"></i>
                </div>
                <h3>Easy Cancellation</h3>
                <p>Transparent cancellation policies with instant refund slab calculations anytime online.</p>
            </div>
        </div>
    </div>
</section>

<!-- Fleet Showcase Section -->
<section class="section-padding" style="background: #f1f5f9;">
    <div class="container">
        <div class="section-header">
            <span class="section-tag">Our Modern Fleet</span>
            <h2 class="section-title">Choose From Varied Fleet Classes</h2>
            <p class="section-desc">From ultra-luxury Volvo multi-axle coaches to eco-friendly electric and economical express buses.</p>
        </div>

        <div class="fleet-grid">
            <!-- Volvo AC -->
            <div class="fleet-card">
                <div class="fleet-header">
                    <div>
                        <h3 style="font-size:18px; font-weight:800;">Volvo AC Multi-Axle</h3>
                        <span style="font-size:12px; color:#94a3b8;">Luxury 2+2 Seater</span>
                    </div>
                    <span class="fleet-badge badge-volvo">Premium</span>
                </div>
                <div class="fleet-body">
                    <p style="font-size:14px; color:#64748b;">
                        Top-of-the-line air-suspended multi-axle luxury coach with automatic climate control and leather pushback seats.
                    </p>
                    <div class="fleet-amenities-pills">
                        <span class="fleet-pill"><i class="fas fa-snowflake text-primary"></i> Dual AC</span>
                        <span class="fleet-pill"><i class="fas fa-wifi text-primary"></i> WiFi</span>
                        <span class="fleet-pill"><i class="fas fa-plug text-primary"></i> USB Charging</span>
                        <span class="fleet-pill"><i class="fas fa-bottle-water text-primary"></i> Mineral Water</span>
                    </div>
                    <a href="search.php?bus_class=Volvo+AC+2%2B2" class="btn-search-bus" style="height:44px; font-size:14px; margin-top:14px;">
                        View Volvo Schedules <i class="fas fa-arrow-right ms-1"></i>
                    </a>
                </div>
            </div>

            <!-- Electric AC -->
            <div class="fleet-card">
                <div class="fleet-header" style="background: linear-gradient(135deg, #064e3b, #065f46);">
                    <div>
                        <h3 style="font-size:18px; font-weight:800;">Electric AC Express</h3>
                        <span style="font-size:12px; color:#a7f3d0;">Eco Green 2+2</span>
                    </div>
                    <span class="fleet-badge badge-electric">100% Electric</span>
                </div>
                <div class="fleet-body">
                    <p style="font-size:14px; color:#64748b;">
                        Zero emission, ultra-silent electric coach engineered for comfortable and sustainable intercity Gujarat transit.
                    </p>
                    <div class="fleet-amenities-pills">
                        <span class="fleet-pill"><i class="fas fa-bolt text-emerald"></i> Silent Drive</span>
                        <span class="fleet-pill"><i class="fas fa-snowflake text-emerald"></i> Climate AC</span>
                        <span class="fleet-pill"><i class="fas fa-mobile-screen text-emerald"></i> Fast USB</span>
                        <span class="fleet-pill"><i class="fas fa-location-dot text-emerald"></i> GPS Tracking</span>
                    </div>
                    <a href="search.php?bus_class=Electric+AC" class="btn-search-bus" style="height:44px; font-size:14px; margin-top:14px; background:linear-gradient(135deg, #10b981, #059669);">
                        View Electric Buses <i class="fas fa-arrow-right ms-1"></i>
                    </a>
                </div>
            </div>

            <!-- Sleeper 2+1 -->
            <div class="fleet-card">
                <div class="fleet-header" style="background: linear-gradient(135deg, #3b0764, #581c87);">
                    <div>
                        <h3 style="font-size:18px; font-weight:800;">AC Sleeper 2+1</h3>
                        <span style="font-size:12px; color:#e9d5ff;">Upper & Lower Berths</span>
                    </div>
                    <span class="fleet-badge badge-sleeper">Night Travel</span>
                </div>
                <div class="fleet-body">
                    <p style="font-size:14px; color:#64748b;">
                        Spacious full-flat sleeper berths with individual curtains, reading lights, and fresh linens for overnight journeys.
                    </p>
                    <div class="fleet-amenities-pills">
                        <span class="fleet-pill"><i class="fas fa-bed text-primary"></i> Flat Berths</span>
                        <span class="fleet-pill"><i class="fas fa-mattress-pillow text-primary"></i> Fresh Linen</span>
                        <span class="fleet-pill"><i class="fas fa-lightbulb text-primary"></i> Reading Light</span>
                        <span class="fleet-pill"><i class="fas fa-plug text-primary"></i> Bedside Socket</span>
                    </div>
                    <a href="search.php?bus_class=Sleeper+2%2B1" class="btn-search-bus" style="height:44px; font-size:14px; margin-top:14px; background:linear-gradient(135deg, #8b5cf6, #6d28d9);">
                        View Sleeper Coaches <i class="fas fa-arrow-right ms-1"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Popular Gujarat Route Grid -->
<section class="section-padding">
    <div class="container">
        <div class="section-header">
            <span class="section-tag">High-Frequency Routes</span>
            <h2 class="section-title">Major Gujarat Corridors</h2>
            <p class="section-desc">20 buses operating daily on each route with frequent departures every 45-60 minutes.</p>
        </div>

        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px;">
            <?php foreach ($popularRoutes as $route): ?>
                <div style="background:#ffffff; border:1px solid #e2e8f0; border-radius:12px; padding:20px; display:flex; justify-content:space-between; align-items:center; box-shadow:var(--shadow-sm); transition:transform 0.2s;" onmouseover="this.style.transform='translateY(-3px)'" onmouseout="this.style.transform='translateY(0)'">
                    <div>
                        <div style="font-size:16px; font-weight:800; color:#0f172a; display:flex; align-items:center; gap:8px;">
                            <span><?= htmlspecialchars($route['from']); ?></span>
                            <i class="fas fa-arrow-right text-primary" style="font-size:12px;"></i>
                            <span><?= htmlspecialchars($route['to']); ?></span>
                        </div>
                        <div style="font-size:12px; color:#64748b; margin-top:4px;">
                            <span><i class="fas fa-road me-1"></i> <?= $route['dist']; ?></span> &bull; 
                            <span><i class="fas fa-clock me-1"></i> <?= $route['time']; ?></span>
                        </div>
                    </div>
                    <div style="text-align:right;">
                        <div style="font-size:12px; color:#94a3b8;">Starting from</div>
                        <div style="font-size:18px; font-weight:800; color:var(--primary);">₹<?= $route['fare']; ?></div>
                        <a href="search.php?source=<?= urlencode($route['from']); ?>&destination=<?= urlencode($route['to']); ?>&journey_date=<?= date('Y-m-d'); ?>" style="font-size:12px; font-weight:700; color:var(--secondary); display:inline-block; margin-top:2px;">
                            Book Now <i class="fas fa-chevron-right" style="font-size:10px;"></i>
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
