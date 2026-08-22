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
- **Demo Net Banking Payment Simulation**:
  - Bank selection (SBI, HDFC, ICICI, Axis, Bank of Baroda, Kotak, PNB).
  - Interactive OTP verification modal with 60s countdown timer and 1-click **Auto-fill Demo OTP (`123456`)**.
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
