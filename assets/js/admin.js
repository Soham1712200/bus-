/**
 * Gopi Travels - Admin & Branch Office Portal Master Script
 * Live Activity Feed Polling, Dynamic Stat Count-up, and Universal Filter
 */

const AdminPortal = {
    feedInterval: null,

    init() {
        this.initLiveFeed();
        this.initCountUp();
        this.initUniversalFilter();
    },

    /**
     * Poll Live Reservation Activity Feed
     */
    initLiveFeed() {
        const feedContainer = document.getElementById('liveFeedItemsContainer');
        if (!feedContainer) return;

        const fetchFeed = () => {
            fetch('../api/get_live_feed.php')
                .then(res => res.json())
                .then(data => {
                    if (data.status === 'success' && data.data) {
                        this.renderFeed(data.data);
                    }
                })
                .catch(err => console.error('Feed poll error:', err));
        };

        fetchFeed();
        this.feedInterval = setInterval(fetchFeed, 5000); // 5s Poll
    },

    renderFeed(items) {
        const feedContainer = document.getElementById('liveFeedItemsContainer');
        if (!feedContainer) return;

        if (items.length === 0) {
            feedContainer.innerHTML = '<div style="padding:20px; text-align:center; color:#94a3b8;">No recent bookings</div>';
            return;
        }

        let html = '';
        items.forEach(item => {
            const isOnline = (item.channel === 'online');
            const channelBadge = isOnline 
                ? '<span class="badge-channel-online"><i class="fas fa-globe"></i> Web</span>'
                : '<span class="badge-channel-counter"><i class="fas fa-store"></i> Counter</span>';

            html += `
                <div class="feed-item-row">
                    <div>
                        <div style="display:flex; align-items:center; gap:8px; margin-bottom:4px;">
                            ${channelBadge}
                            <strong style="font-size:13px; color:#0f172a;">${item.passenger}</strong>
                            <span style="font-size:11px; font-family:monospace; color:var(--primary);">${item.pnr}</span>
                        </div>
                        <div style="font-size:12px; color:#64748b;">
                            <span>${item.route}</span> &bull; 
                            <span>Seat(s): <strong>${item.seats}</strong></span>
                        </div>
                    </div>
                    <div style="text-align:right;">
                        <strong style="color:var(--primary); font-size:14px;">${item.amount}</strong>
                        <div style="font-size:10px; color:#94a3b8;">${item.time_ago}</div>
                    </div>
                </div>
            `;
        });
        feedContainer.innerHTML = html;
    },

    /**
     * Animate Numbers on Dashboard
     */
    initCountUp() {
        const counters = document.querySelectorAll('.stat-countup');
        counters.forEach(counter => {
            const target = parseFloat(counter.getAttribute('data-target') || '0');
            const isCurrency = counter.getAttribute('data-currency') === 'true';
            const duration = 1200;
            const stepTime = 20;
            const steps = duration / stepTime;
            const increment = target / steps;
            let current = 0;

            const timer = setInterval(() => {
                current += increment;
                if (current >= target) {
                    current = target;
                    clearInterval(timer);
                }
                counter.textContent = isCurrency 
                    ? '₹' + Math.round(current).toLocaleString('en-IN')
                    : Math.round(current).toLocaleString('en-IN');
            }, stepTime);
        });
    },

    /**
     * Universal Table Filter & Tab Switching
     */
    initUniversalFilter() {
        const searchInput = document.getElementById('adminUniversalSearch');
        if (searchInput) {
            searchInput.addEventListener('input', (e) => {
                const q = e.target.value.toLowerCase();
                const rows = document.querySelectorAll('.admin-data-row');
                rows.forEach(row => {
                    const text = row.textContent.toLowerCase();
                    row.style.display = text.includes(q) ? '' : 'none';
                });
            });
        }
    }
};

document.addEventListener('DOMContentLoaded', () => {
    AdminPortal.init();
});

window.AdminPortal = AdminPortal;
