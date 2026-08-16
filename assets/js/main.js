/**
 * Jayambe Travels - Core UI Interactivity Script
 */

document.addEventListener('DOMContentLoaded', () => {
    // 1. Initialize Journey Date Restrictions (Min date = Today)
    const dateInput = document.getElementById('journeyDateInput');
    if (dateInput) {
        const today = new Date();
        const yyyy = today.getFullYear();
        const mm = String(today.getMonth() + 1).padStart(2, '0');
        const dd = String(today.getDate()).padStart(2, '0');
        const minDateStr = `${yyyy}-${mm}-${dd}`;
        
        dateInput.min = minDateStr;
        if (!dateInput.value) {
            dateInput.value = minDateStr;
        }

        // Quick Date Selector Pills
        const datePills = document.querySelectorAll('.date-pill');
        datePills.forEach(pill => {
            pill.addEventListener('click', (e) => {
                e.preventDefault();
                datePills.forEach(p => p.classList.remove('active'));
                pill.classList.add('active');
                
                const offset = parseInt(pill.dataset.offset || '0', 10);
                const targetDate = new Date();
                targetDate.setDate(targetDate.getDate() + offset);
                
                const tY = targetDate.getFullYear();
                const tM = String(targetDate.getMonth() + 1).padStart(2, '0');
                const tD = String(targetDate.getDate()).padStart(2, '0');
                dateInput.value = `${tY}-${tM}-${tD}`;
            });
        });
    }

    // 2. Animated Source & Destination Swap Button
    const swapBtn = document.getElementById('btnSwapCities');
    const sourceSelect = document.getElementById('sourceCitySelect');
    const destSelect = document.getElementById('destCitySelect');

    if (swapBtn && sourceSelect && destSelect) {
        swapBtn.addEventListener('click', () => {
            const tempVal = sourceSelect.value;
            sourceSelect.value = destSelect.value;
            destSelect.value = tempVal;

            // Trigger rotation animation
            swapBtn.style.transform = 'rotate(360deg)';
            setTimeout(() => {
                swapBtn.style.transform = '';
            }, 400);
        });
    }

    // 3. Quick Route Chips Handler
    const routeChips = document.querySelectorAll('.route-chip');
    routeChips.forEach(chip => {
        chip.addEventListener('click', (e) => {
            e.preventDefault();
            const src = chip.dataset.source;
            const dst = chip.dataset.dest;
            if (sourceSelect && destSelect) {
                sourceSelect.value = src;
                destSelect.value = dst;
            }
        });
    });

    // 4. Navbar Scroll Shadow Effect
    const mainHeader = document.querySelector('.main-header');
    if (mainHeader) {
        window.addEventListener('scroll', () => {
            if (window.scrollY > 20) {
                mainHeader.classList.add('scrolled');
            } else {
                mainHeader.classList.remove('scrolled');
            }
        });
    }

    // 5. Mobile Navigation Toggle
    const mobileToggle = document.querySelector('.mobile-toggle');
    const navMenu = document.querySelector('.nav-menu');
    if (mobileToggle && navMenu) {
        mobileToggle.addEventListener('click', () => {
            const isVisible = navMenu.style.display === 'flex';
            navMenu.style.display = isVisible ? 'none' : 'flex';
            if (!isVisible) {
                navMenu.style.flexDirection = 'column';
                navMenu.style.position = 'absolute';
                navMenu.style.top = '76px';
                navMenu.style.left = '0';
                navMenu.style.right = '0';
                navMenu.style.background = '#0b132b';
                navMenu.style.padding = '20px';
                navMenu.style.borderBottom = '1px solid rgba(255,255,255,0.1)';
            }
        });
    }

    // 6. Bus Search Validation (Ensure Source != Destination)
    const searchForm = document.getElementById('busSearchForm');
    if (searchForm) {
        searchForm.addEventListener('submit', (e) => {
            const src = sourceSelect ? sourceSelect.value : '';
            const dst = destSelect ? destSelect.value : '';

            if (src && dst && src === dst) {
                e.preventDefault();
                alert('Source city and Destination city cannot be the same. Please select different cities.');
            }
        });
    }
});
