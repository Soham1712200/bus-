# Gopi Travels - Bus Booking Web Application

A full-stack, modular, and error-free Bus Reservation Web Application built with **PHP, MySQL, HTML5, CSS3, and JavaScript (ES6+)**.

---

## 🚌 Features & Highlights

- **Pre-Populated Gujarat Bus Network**:
  - 28 Highway Corridors (Ahmedabad, Surat, Vadodara, Rajkot, Bhavnagar, Junagadh, Jamnagar, Bhuj, etc.).
  - 20 Buses per Route (560 total buses across *Volvo AC 2+2, Electric AC, Sleeper 2+1, AC Seater, Gurjarnagri, Express*).
- **Live Interactive Seat Matrix**:
  - Real-time seat selection for **2x2 Seater** and **2x1 Sleeper** (with Lower & Upper berth decks).
  - Dynamic fare calculation (Base Fare, 5% GST, Toll/Reservation fee).
  - Dynamic passenger detail inputs generated for each selected seat.
- **GopiPay™ Express Gateway (First-Party Custom Dummy Payment Gateway)**:
  - **Dynamic UPI & QR Code Scanner**: Live interactive QR code with 10-minute auto-expiry countdown, quick test VPA chips (`demo@gopipay`, `traveler@ybl`, `gopipay@icici`), and simulated UPI Push notification intent modal (Google Pay, PhonePe, Paytm, BHIM).
  - **Interactive 3D Card Visualizer**: Real-time card type detection (Visa, Mastercard, RuPay, Amex) with 1-click test card chips (Visa `4111 2222 3333 4444`, Mastercard `5500 0000 0000 0004`, RuPay `6070 1234 5678 9012`, and Bank Decline `4000 0000 0000 0002`).
  - **40+ Bank Net Banking with 2FA OTP Simulator**: Two-factor SMS OTP authentication screen with 6 individual digit inputs and a 1-click autofill test helper (`123456`).
  - **Digital Wallets**: Paytm, PhonePe, MobiKwik, and Ola Money integration.
  - **Pay on Boarding**: Guaranteed seat reservation with cash or UPI collection by conductor upon boarding.
  - **HMAC SHA-256 Checksum**: Deterministic transaction verification and tamper-proof security tokens.
  - **Developer Sandbox Controls**: Real-time toggle to simulate Successful transaction (`HTTP 200 OK`) vs Simulated Bank Decline (`Insufficient Funds`).
  - **1-Click Instant GopiPay**: Instant test checkout button for seamless paired-programming and review.
- **Printable E-Ticket**:
  - Official Electronic Reservation Slip (ERS) layout with unique PNR (`JAY-XXXXXX`).
  - High-resolution inline SVG Barcode (Code-128) & SVG QR Code.
  - Dedicated print stylesheet (`@media print`) for clean physical printing and PDF export.
- **Standalone Modules**:
  - **`print-ticket.php`**: Retrieve and print ticket anytime using PNR or registered Mobile Number.
  - **`cancel-ticket.php`**: Instant ticket cancellation with dynamic refund slab calculations.
  - **`routes.php`**: Searchable Gujarat Route Timetable directory.
  - **`contact.php`**: Gujarat Branch Offices directory & 24x7 helpline inquiry form.
  - **`my-bookings.php`**: Passenger account dashboard & booking history.

---

## ⚙️ Installation & Setup (XAMPP)

1. Place the project inside XAMPP's web directory:
   ```bash
   /Applications/XAMPP/xamppfiles/htdocs/bus/
   ```
2. Start **Apache** and **MySQL** in XAMPP.
3. Open your browser and navigate to the 1-click database setup installer:
   ```
   http://localhost/bus/install.php
   ```
4. Click **"Initialize Database & Fleet"** to automatically create the `gopi_travels` schema, seed 560 buses, and create demo user accounts.

### Demo Passenger Account
- **Email**: `demo@gopitravels.com`
- **Password**: `password123`
