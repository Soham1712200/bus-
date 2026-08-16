<?php
/**
 * Jayambe Travels - Admin & Branch Office Portal Footer
 */
?>
    <!-- Admin Global Footer -->
    <footer class="admin-footer" style="background: #0b132b; border-top: 1px solid rgba(255,255,255,0.08); padding: 24px 0; margin-top: 60px; color: #94a3b8; font-size: 13px;">
        <div style="max-width: 1400px; margin: 0 auto; padding: 0 20px; display: flex; justify-content: space-between; align-items: center;">
            <div>
                &copy; <?= date('Y'); ?> <strong>Jayambe Travels</strong> &bull; Centralized Operations & Fleet Management Engine
            </div>
            <div style="display: flex; gap: 18px; align-items: center;">
                <span><i class="fas fa-server text-accent me-1"></i> Shared MySQL Schema: <code>jayambe_travels</code></span>
                <span><i class="fas fa-bolt text-primary me-1"></i> Engine v2.4 (Gujarat State Fleet)</span>
            </div>
        </div>
    </footer>

    <!-- Master Admin Script -->
    <script src="../assets/js/admin.js"></script>
</body>
</html>
