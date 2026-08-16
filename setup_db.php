<?php
/**
 * Jayambe Travels - Comprehensive Database Seeder & Setup Script
 * Seeds all Gujarat routes with 20 buses per route across all bus classes
 * Seeds both Online and Counter Offline reservations for Admin sync testing
 */

require_once __DIR__ . '/config/db.php';

echo "<pre style='font-family: monospace; background: #0f172a; color: #10b981; padding: 20px; border-radius: 8px;'>";
echo "=== JAYAMBE TRAVELS DATABASE INITIALIZER ===\n\n";

$pdo = get_db_connection();
if (!$pdo) {
    die("FATAL: Could not connect to MySQL server. Please ensure XAMPP MySQL is running.\n");
}

echo "[1/4] Executing Schema Definition (schema.sql)...\n";
$schemaFile = __DIR__ . '/schema.sql';
if (file_exists($schemaFile)) {
    $sql = file_get_contents($schemaFile);
    $pdo->exec($sql);
    echo "  -> Tables (including seat_locks) and base routes created successfully!\n";
} else {
    die("FATAL: schema.sql not found at {$schemaFile}\n");
}

echo "\n[2/4] Generating 20 Buses per Route across Bus Classes...\n";

// Fetch all routes
$routesStmt = $pdo->query("SELECT id, source_city, destination_city, distance_km, estimated_time FROM routes ORDER BY id ASC");
$routes = $routesStmt->fetchAll();

// Bus class profiles
$classes = [
    [
        'class' => 'Volvo AC 2+2',
        'is_ac' => 1,
        'is_sleeper' => 0,
        'seats' => 40,
        'multiplier' => 2.2,
        'amenities' => 'Climate Control AC, Pushback Leather Seats, High Speed WiFi, Individual USB Charging, Mineral Water, Live GPS Tracking, Reading Light, Emergency SOS'
    ],
    [
        'class' => 'Electric AC',
        'is_ac' => 1,
        'is_sleeper' => 0,
        'seats' => 40,
        'multiplier' => 1.85,
        'amenities' => 'Eco-Friendly Electric, Silent Cabin, Dual AC, Fast Mobile Charging, Air Suspension, Live GPS, Digital LED Display'
    ],
    [
        'class' => 'Sleeper 2+1',
        'is_ac' => 1,
        'is_sleeper' => 1,
        'seats' => 30,
        'multiplier' => 2.0,
        'amenities' => 'Upper/Lower Berths, Fresh Bedding & Pillow, Individual AC Vents, Charging Socket, Privacy Curtains, Night Reading Lamps'
    ],
    [
        'class' => 'AC Seater',
        'is_ac' => 1,
        'is_sleeper' => 0,
        'seats' => 44,
        'multiplier' => 1.5,
        'amenities' => 'Air Conditioned, Reclining Seats, USB Charging Ports, Overhead Luggage Rack, Music System'
    ],
    [
        'class' => 'Gurjarnagri',
        'is_ac' => 0,
        'is_sleeper' => 0,
        'seats' => 48,
        'multiplier' => 1.15,
        'amenities' => 'Gujarat State High Speed Express, High Back Cushioned Seats, Large Glass Windows, First Aid Box'
    ],
    [
        'class' => 'Express',
        'is_ac' => 0,
        'is_sleeper' => 0,
        'seats' => 50,
        'multiplier' => 1.0,
        'amenities' => 'Standard Fast Passenger, Comfortable 2x2 Seating, Wide Aisle, Punctual Service'
    ]
];

// Gujarat RTO district codes
$rtoCodes = ['01', '02', '03', '04', '05', '06', '07', '08', '09', '10', '11', '12', '13', '14', '15', '16', '17', '18', '19', '20', '21', '23', '24', '25', '27'];
$seriesLetters = ['CZ', 'BX', 'AX', 'DT', 'EY', 'FT', 'GW', 'HX', 'JZ', 'KY', 'LZ', 'MB', 'NC', 'PD', 'QE', 'RF', 'SG', 'TH', 'UJ', 'VK'];

// Departure schedule templates across 24 hours (20 slots per day)
$departureSlots = [
    '05:00:00', '06:15:00', '07:00:00', '08:00:00', '09:15:00',
    '10:30:00', '11:45:00', '13:00:00', '14:15:00', '15:30:00',
    '16:45:00', '17:30:00', '18:45:00', '19:30:00', '20:15:00',
    '21:00:00', '21:45:00', '22:30:00', '23:15:00', '23:55:00'
];

$busInsertStmt = $pdo->prepare("INSERT INTO buses (
    bus_number, route_id, bus_class, total_seats, fare, departure_time, arrival_time, is_ac, is_sleeper, amenities, status
) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Active')");

$totalBusesInserted = 0;
$busIndex = 1000;

foreach ($routes as $route) {
    $routeId = $route['id'];
    $dist = $route['distance_km'];
    $duration = $route['estimated_time'];
    
    // Parse duration in minutes
    preg_match('/(\d+)\s*hrs?(?:\s*(\d+)\s*mins?)?/', $duration, $matches);
    $durHours = isset($matches[1]) ? (int)$matches[1] : 3;
    $durMins = isset($matches[2]) ? (int)$matches[2] : 0;
    $totalDurSeconds = ($durHours * 3600) + ($durMins * 60);
    
    $baseKmRate = 1.10;
    $baseRouteFare = max(100, round($dist * $baseKmRate));
    
    for ($i = 0; $i < 20; $i++) {
        $busIndex++;
        $classProfile = $classes[$i % count($classes)];
        
        $rto = $rtoCodes[($routeId + $i) % count($rtoCodes)];
        $series = $seriesLetters[($i + ($routeId * 2)) % count($seriesLetters)];
        $num = sprintf("%04d", (1000 + ($routeId * 25) + $i) % 9999);
        $busNumber = "GJ-{$rto}-{$series}-{$num}";
        
        $depTime = $departureSlots[$i];
        $depSec = strtotime("1970-01-01 $depTime UTC");
        $arrSec = ($depSec + $totalDurSeconds) % 86400;
        $arrTime = gmdate('H:i:s', $arrSec);
        
        $calculatedFare = round(($baseRouteFare * $classProfile['multiplier']) / 10) * 10;
        
        $busInsertStmt->execute([
            $busNumber,
            $routeId,
            $classProfile['class'],
            $classProfile['seats'],
            $calculatedFare,
            $depTime,
            $arrTime,
            $classProfile['is_ac'],
            $classProfile['is_sleeper'],
            $classProfile['amenities']
        ]);
        
        $totalBusesInserted++;
    }
}

echo "  -> Successfully generated {$totalBusesInserted} buses across " . count($routes) . " routes (20 buses per route)!\n";

echo "\n[3/4] Generating Sample Online & Offline Counter Bookings...\n";

$sampleDates = [
    date('Y-m-d'),
    date('Y-m-d', strtotime('+1 day')),
    date('Y-m-d', strtotime('+2 days')),
    date('Y-m-d', strtotime('+3 days'))
];

// Sample demo passengers
$passengerNames = [
    ['name' => 'Ketan Shah', 'phone' => '9825100001', 'email' => 'ketan.shah@gmail.com'],
    ['name' => 'Bhavin Mehta', 'phone' => '9879000002', 'email' => 'bhavin.m@yahoo.com'],
    ['name' => 'Pooja Trivedi', 'phone' => '9426000003', 'email' => 'pooja.t@rediffmail.com'],
    ['name' => 'Hardik Prajapati', 'phone' => '9898000004', 'email' => 'hardik.p@gmail.com'],
    ['name' => 'Meera Vaghela', 'phone' => '9724000005', 'email' => 'meera.v@outlook.com']
];

$sampleBusStmt = $pdo->query("SELECT id, fare, total_seats, is_sleeper FROM buses LIMIT 25");
$sampleBuses = $sampleBusStmt->fetchAll();

$bookingInsertStmt = $pdo->prepare("INSERT INTO bookings (
    booking_reference, user_id, bus_id, journey_date, seat_numbers, total_passengers,
    base_fare, gst_amount, total_amount, payment_method, payment_status, transaction_id,
    passenger_name, passenger_phone, passenger_email, passenger_details, booking_status,
    booking_channel, booked_by_user_id, boarding_point, counter_payment_mode
) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Paid', ?, ?, ?, ?, ?, 'Confirmed', ?, ?, ?, ?)");

$sampleBookingCount = 0;
foreach ($sampleBuses as $bIndex => $sBus) {
    foreach ($sampleDates as $dIndex => $sDate) {
        $pInfo = $passengerNames[($bIndex + $dIndex) % count($passengerNames)];
        
        $pnr = 'JAY-' . strtoupper(substr(md5($bIndex . $dIndex . microtime()), 0, 6));
        $isCounter = ($bIndex % 2 === 1);
        $channel = $isCounter ? 'counter' : 'online';
        $paymentMethod = $isCounter ? 'Counter Cash / POS' : 'Net Banking (SBI)';
        $counterMode = $isCounter ? 'Cash' : 'Online NetBanking';
        $agentId = $isCounter ? 3 : null; // Counter Agent Ramesh Shah
        $txn = $isCounter ? 'TXN-POS-' . strtoupper(bin2hex(random_bytes(3))) : 'TXN-NET-' . strtoupper(bin2hex(random_bytes(3)));
        
        $isSleeper = (bool)$sBus['is_sleeper'];
        $seatsChosen = $isSleeper ? ($isCounter ? ['L3', 'L4'] : ['L1', 'L2']) : ($isCounter ? ['4A', '4B'] : ['3A', '3B']);
        $seatStr = implode(',', $seatsChosen);
        $paxCount = count($seatsChosen);
        
        $baseTotal = $sBus['fare'] * $paxCount;
        $gst = round($baseTotal * 0.05, 2);
        $grandTotal = $baseTotal + $gst;
        
        $paxDetails = json_encode([
            [
                'name' => $pInfo['name'],
                'age' => 32,
                'gender' => 'Male',
                'seat' => $seatsChosen[0]
            ],
            [
                'name' => 'Rina ' . explode(' ', $pInfo['name'])[1],
                'age' => 29,
                'gender' => 'Female',
                'seat' => $seatsChosen[1]
            ]
        ]);
        
        $bookingInsertStmt->execute([
            $pnr,
            1, // User ID 1 (Jayesh Patel)
            $sBus['id'],
            $sDate,
            $seatStr,
            $paxCount,
            $baseTotal,
            $gst,
            $grandTotal,
            $paymentMethod,
            $txn,
            $pInfo['name'],
            $pInfo['phone'],
            $pInfo['email'],
            $paxDetails,
            $channel,
            $agentId,
            'Geeta Mandir Central Terminal Platform 2',
            $counterMode
        ]);
        $sampleBookingCount++;
    }
}

echo "  -> Inserted {$sampleBookingCount} sample bookings (Online + Counter Offline)!\n";

echo "\n[4/4] Setup Completed Successfully!\n";
echo "--------------------------------------------------------\n";
echo "✓ Total Routes: " . count($routes) . "\n";
echo "✓ Total Buses: {$totalBusesInserted} (20 per route)\n";
echo "✓ Sample Bookings: {$sampleBookingCount}\n";
echo "✓ SuperAdmin Account: admin@jayambetravels.com / password123\n";
echo "✓ Counter Agent (Ahmedabad): counter.ahmedabad@jayambetravels.com / password123\n";
echo "✓ Counter Agent (Surat): counter.surat@jayambetravels.com / password123\n";
echo "✓ Counter Agent (Vadodara): counter.vadodara@jayambetravels.com / password123\n";
echo "--------------------------------------------------------\n";
echo "Admin Portal: http://localhost/bus/admin/\n";
echo "Customer Site: http://localhost/bus/\n";
echo "</pre>";
