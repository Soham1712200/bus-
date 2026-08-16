<?php
/**
 * Jayambe Travels - Global Footer
 */
?>
    </main>

    <!-- Main Global Footer -->
    <footer class="main-footer">
        <div class="container">
            <div class="footer-grid">
                <!-- Brand Info Col -->
                <div class="footer-col">
                    <div class="brand-logo" style="margin-bottom: 16px;">
                        <img src="assets/images/logo.svg" alt="Jayambe Travels" style="height: 44px; width: auto; max-width: 220px;" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                        <div style="display:none; align-items:center; gap:10px;">
                            <div class="logo-symbol">
                                <i class="fas fa-bus-simple"></i>
                            </div>
                            <div class="brand-text">
                                <div class="brand-name">Jayambe <span>Travels</span></div>
                                <div class="brand-tagline" style="color: #94a3b8;">Gujarat State Partner Network</div>
                            </div>
                        </div>
                    </div>
                    <p style="font-size: 13px; color: #94a3b8; line-height: 1.6; margin-bottom: 18px;">
                        Jayambe Travels provides seamless, punctual, and safe passenger transport across all districts of Gujarat with luxury Volvo, Electric AC, Sleeper, and Express coaches.
                    </p>
                    <div style="display: flex; gap: 10px;">
                        <span class="badge" style="background: rgba(255,255,255,0.06); padding: 6px 12px; border-radius: 6px; font-size: 12px; color: #cbd5e1;">
                            <i class="fas fa-shield-halved text-primary me-1"></i> ISO 9001:2015 Certified
                        </span>
                        <span class="badge" style="background: rgba(255,255,255,0.06); padding: 6px 12px; border-radius: 6px; font-size: 12px; color: #cbd5e1;">
                            <i class="fas fa-certificate text-primary me-1"></i> Govt. Approved Fleet
                        </span>
                    </div>
                </div>

                <!-- Quick Links Col -->
                <div class="footer-col">
                    <h4>Quick Links</h4>
                    <ul class="footer-links">
                        <li><a href="index.php"><i class="fas fa-angle-right me-1 text-primary"></i> Home</a></li>
                        <li><a href="search.php"><i class="fas fa-angle-right me-1 text-primary"></i> Search & Book Buses</a></li>
                        <li><a href="print-ticket.php"><i class="fas fa-angle-right me-1 text-primary"></i> Print E-Ticket</a></li>
                        <li><a href="cancel-ticket.php"><i class="fas fa-angle-right me-1 text-primary"></i> Cancel Booking & Refund</a></li>
                        <li><a href="routes.php"><i class="fas fa-angle-right me-1 text-primary"></i> Gujarat Route Timetable</a></li>
                        <li><a href="contact.php"><i class="fas fa-angle-right me-1 text-primary"></i> Helpdesk & Offices</a></li>
                    </ul>
                </div>

                <!-- Gujarat Bus Classes Col -->
                <div class="footer-col">
                    <h4>Fleet Classes</h4>
                    <ul class="footer-links">
                        <li><a href="search.php?bus_class=Volvo+AC+2%2B2"><i class="fas fa-crown me-1 text-primary"></i> Volvo AC Multi-Axle</a></li>
                        <li><a href="search.php?bus_class=Electric+AC"><i class="fas fa-bolt me-1 text-primary"></i> Electric AC Express</a></li>
                        <li><a href="search.php?bus_class=Sleeper+2%2B1"><i class="fas fa-bed me-1 text-primary"></i> AC Sleeper 2+1 Berth</a></li>
                        <li><a href="search.php?bus_class=AC+Seater"><i class="fas fa-snowflake me-1 text-primary"></i> Deluxe AC Seater</a></li>
                        <li><a href="search.php?bus_class=Gurjarnagri"><i class="fas fa-bus-simple me-1 text-primary"></i> Gurjarnagri 2+2</a></li>
                        <li><a href="search.php?bus_class=Express"><i class="fas fa-route me-1 text-primary"></i> Standard Fast Express</a></li>
                    </ul>
                </div>

                <!-- Central Offices Col -->
                <div class="footer-col">
                    <h4>Major Gujarat Offices</h4>
                    <div class="footer-office-list">
                        <div><strong style="color:#ffffff;">Ahmedabad:</strong> Head Office, Geeta Mandir Road</div>
                        <div><strong style="color:#ffffff;">Surat:</strong> Surat Central Branch Office, Ring Road</div>
                        <div><strong style="color:#ffffff;">Vadodara:</strong> Vadodara Branch Office, Sayajigunj</div>
                        <div><strong style="color:#ffffff;">Rajkot:</strong> Rajkot Branch Office, Shastri Maidan</div>
                        <div><strong style="color:#ffffff;">24x7 Helpline:</strong> <?= HELPLINE_NUMBER; ?></div>
                    </div>
                </div>
            </div>

            <!-- Footer Bottom -->
            <div class="footer-bottom">
                <div>
                    &copy; <?= date('Y'); ?> <strong>Jayambe Travels</strong>. All Rights Reserved. Built with Pure PHP & MySQL.
                </div>
                <div style="display: flex; gap: 16px;">
                    <a href="contact.php" style="color: #94a3b8;">Terms & Conditions</a>
                    <a href="contact.php" style="color: #94a3b8;">Cancellation Policy</a>
                    <a href="contact.php" style="color: #94a3b8;">Privacy Policy</a>
                </div>
            </div>
        </div>
    </footer>

    <!-- Core Scripts -->
    <script src="assets/js/main.js"></script>
    <script src="assets/js/booking.js"></script>
    <script src="assets/js/payment.js"></script>
</body>
</html>
