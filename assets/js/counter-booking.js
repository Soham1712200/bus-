/**
 * Gopi Travels - Offline Counter Booking Engine
 * Unified Real-Time 4-Color Seat Engine & Instant Cash/POS Checkout
 */

const CounterBooking = {
    busId: null,
    journeyDate: null,
    farePerSeat: 0,
    isSleeper: false,
    selectedSeats: [],
    sessionToken: 'COUNTER_SESSION_' + Math.random().toString(36).substring(2, 9),
    pollTimer: null,

    init() {
        const busSelect = document.getElementById('counterBusSelect');
        const dateInput = document.getElementById('counterDateInput');
        const tenderInput = document.getElementById('counterCashTenderInput');

        if (busSelect) {
            busSelect.addEventListener('change', () => this.onBusOrDateChange());
        }
        if (dateInput) {
            dateInput.addEventListener('change', () => this.onBusOrDateChange());
        }
        if (tenderInput) {
            tenderInput.addEventListener('input', () => this.calculateChange());
        }

        // Auto load initial bus if selected
        if (busSelect && busSelect.value) {
            this.onBusOrDateChange();
        }
    },

    onBusOrDateChange() {
        const busSelect = document.getElementById('counterBusSelect');
        const dateInput = document.getElementById('counterDateInput');
        if (!busSelect || !busSelect.value) return;

        const selectedOption = busSelect.options[busSelect.selectedIndex];
        this.busId = parseInt(busSelect.value, 10);
        this.journeyDate = dateInput ? dateInput.value : new Date().toISOString().split('T')[0];
        this.farePerSeat = parseFloat(selectedOption.getAttribute('data-fare') || '0');
        this.isSleeper = (selectedOption.getAttribute('data-sleeper') === '1');
        this.selectedSeats = [];

        // Fetch seat matrix
        this.loadSeatMatrix();

        // Start live sync interval (every 4s)
        if (this.pollTimer) clearInterval(this.pollTimer);
        this.pollTimer = setInterval(() => this.loadSeatMatrix(true), 4000);
    },

    loadSeatMatrix(isBackgroundPoll = false) {
        if (!this.busId || !this.journeyDate) return;

        const url = `../api/sync_seats.php?action=get_matrix&bus_id=${this.busId}&journey_date=${this.journeyDate}&session_token=${this.sessionToken}`;

        fetch(url)
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    this.renderSeatGrid(data);
                    this.updateSummaryCounters(data.summary);
                    if (!isBackgroundPoll) {
                        this.updatePriceTally();
                    }
                }
            })
            .catch(err => console.error('Seat sync error:', err));
    },

    renderSeatGrid(data) {
        const gridContainer = document.getElementById('counterSeatGridContainer');
        if (!gridContainer) return;

        const seats = data.seats || {};
        const bookedOnline = seats.booked_online || [];
        const bookedOffline = seats.booked_offline || [];
        const lockedSeats = seats.locked || {};
        const passengerMeta = seats.passenger_meta || {};
        const totalSeats = parseInt(data.bus.total_seats, 10) || 40;

        if (this.isSleeper) {
            // Sleeper layout with Lower & Upper decks
            gridContainer.innerHTML = `
                <div class="deck-tabs" style="margin-bottom: 14px;">
                    <button type="button" class="deck-tab-btn active" onclick="CounterBooking.switchDeck('lower')">
                        <i class="fas fa-layer-group"></i> Lower Berth Deck (15 Berths)
                    </button>
                    <button type="button" class="deck-tab-btn" onclick="CounterBooking.switchDeck('upper')">
                        <i class="fas fa-layer-group"></i> Upper Berth Deck (15 Berths)
                    </button>
                </div>
                <div id="deckLower" class="deck-view active">
                    <div class="sleeper-grid-layout">
                        ${this.generateSleeperBerthsHtml('L', 1, 15, bookedOnline, bookedOffline, lockedSeats, passengerMeta)}
                    </div>
                </div>
                <div id="deckUpper" class="deck-view" style="display:none;">
                    <div class="sleeper-grid-layout">
                        ${this.generateSleeperBerthsHtml('U', 1, 15, bookedOnline, bookedOffline, lockedSeats, passengerMeta)}
                    </div>
                </div>
            `;
        } else {
            // Seater 2x2 layout
            gridContainer.innerHTML = `
                <div class="seater-grid-layout">
                    ${this.generateSeaterSeatsHtml(totalSeats, bookedOnline, bookedOffline, lockedSeats, passengerMeta)}
                </div>
            `;
        }
    },

    switchDeck(deck) {
        const lowerView = document.getElementById('deckLower');
        const upperView = document.getElementById('deckUpper');
        const tabs = document.querySelectorAll('#counterSeatGridContainer .deck-tab-btn');

        tabs.forEach(t => t.classList.remove('active'));

        if (deck === 'lower') {
            if (lowerView) lowerView.style.display = 'block';
            if (upperView) upperView.style.display = 'none';
            if (tabs[0]) tabs[0].classList.add('active');
        } else {
            if (lowerView) lowerView.style.display = 'none';
            if (upperView) upperView.style.display = 'block';
            if (tabs[1]) tabs[1].classList.add('active');
        }
    },

    generateSeaterSeatsHtml(totalSeats, onlineList, counterList, lockedObj, metaObj) {
        let html = '';
        const rows = Math.ceil(totalSeats / 4);
        const cols = ['A', 'B', 'C', 'D'];

        for (let r = 1; r <= rows; r++) {
            for (let c = 0; c < 4; c++) {
                const seatNo = `${r}${cols[c]}`;
                const isOnline = onlineList.includes(seatNo);
                const isCounter = counterList.includes(seatNo);
                const lockInfo = lockedObj[seatNo];
                const isLockedOther = lockInfo && !lockInfo.is_my_lock;
                const isSelected = this.selectedSeats.includes(seatNo);

                let stateClass = 'state-available';
                let icon = 'fa-couch';
                let clickAction = `onclick="CounterBooking.toggleSeat('${seatNo}')"`;
                let titleText = `Available: Seat ${seatNo}`;

                if (isSelected) {
                    stateClass = 'state-selected';
                    icon = 'fa-check';
                } else if (isOnline) {
                    stateClass = 'state-online';
                    icon = 'fa-globe';
                    clickAction = '';
                    const pax = metaObj[seatNo] ? metaObj[seatNo].passenger : 'Web Passenger';
                    titleText = `Booked Online: ${pax}`;
                } else if (isCounter) {
                    stateClass = 'state-counter';
                    icon = 'fa-store';
                    clickAction = '';
                    const pax = metaObj[seatNo] ? metaObj[seatNo].passenger : 'Counter Ticket';
                    titleText = `Booked at Counter: ${pax}`;
                } else if (isLockedOther) {
                    stateClass = 'state-locked';
                    icon = 'fa-lock';
                    clickAction = '';
                    titleText = `Locked in progress by ${lockInfo.locked_by}`;
                }

                html += `
                    <div class="counter-seat-unit bus-seat-unit ${stateClass}" data-seat="${seatNo}" ${clickAction} title="${titleText}">
                        <i class="fas ${icon}"></i>
                        <span>${seatNo}</span>
                    </div>
                `;
            }
        }
        return html;
    },

    generateSleeperBerthsHtml(prefix, start, count, onlineList, counterList, lockedObj, metaObj) {
        let html = '';
        for (let i = start; i <= count; i++) {
            const seatNo = `${prefix}${i}`;
            const isOnline = onlineList.includes(seatNo);
            const isCounter = counterList.includes(seatNo);
            const lockInfo = lockedObj[seatNo];
            const isLockedOther = lockInfo && !lockInfo.is_my_lock;
            const isSelected = this.selectedSeats.includes(seatNo);

            let stateClass = 'state-available';
            let icon = 'fa-bed';
            let clickAction = `onclick="CounterBooking.toggleSeat('${seatNo}')"`;
            let titleText = `Available: Berth ${seatNo}`;

            if (isSelected) {
                stateClass = 'state-selected';
                icon = 'fa-check';
            } else if (isOnline) {
                stateClass = 'state-online';
                icon = 'fa-globe';
                clickAction = '';
                const pax = metaObj[seatNo] ? metaObj[seatNo].passenger : 'Web Passenger';
                titleText = `Booked Online: ${pax}`;
            } else if (isCounter) {
                stateClass = 'state-counter';
                icon = 'fa-store';
                clickAction = '';
                const pax = metaObj[seatNo] ? metaObj[seatNo].passenger : 'Counter Ticket';
                titleText = `Booked at Counter: ${pax}`;
            } else if (isLockedOther) {
                stateClass = 'state-locked';
                icon = 'fa-lock';
                clickAction = '';
                titleText = `Locked in progress by ${lockInfo.locked_by}`;
            }

            html += `
                <div class="counter-seat-unit sleeper-berth-unit ${stateClass}" data-seat="${seatNo}" ${clickAction} title="${titleText}">
                    <i class="fas ${icon}"></i>
                    <span>${seatNo}</span>
                    <small style="font-size:9px;">${prefix === 'L' ? 'Lower' : 'Upper'}</small>
                </div>
            `;
        }
        return html;
    },

    toggleSeat(seatNo) {
        const index = this.selectedSeats.indexOf(seatNo);

        if (index > -1) {
            // Deselect & unlock
            this.selectedSeats.splice(index, 1);
            this.unlockSeatRemote(seatNo);
        } else {
            // Select & lock
            if (this.selectedSeats.length >= 6) {
                alert('Maximum 6 seats can be selected per counter booking.');
                return;
            }
            this.selectedSeats.push(seatNo);
            this.lockSeatRemote(seatNo);
        }

        this.updatePriceTally();
        this.updatePassengerForm();
    },

    lockSeatRemote(seatNo) {
        const staffName = document.getElementById('activeStaffName') ? document.getElementById('activeStaffName').value : 'Counter Agent';
        const url = `../api/sync_seats.php?action=lock_seat&bus_id=${this.busId}&journey_date=${this.journeyDate}&seat_number=${seatNo}&session_token=${this.sessionToken}&channel=counter&agent_name=${encodeURIComponent(staffName)}`;

        fetch(url)
            .then(res => res.json())
            .then(data => {
                if (data.status !== 'success') {
                    alert(data.message || 'Seat is no longer available');
                    const idx = this.selectedSeats.indexOf(seatNo);
                    if (idx > -1) this.selectedSeats.splice(idx, 1);
                    this.loadSeatMatrix();
                    this.updatePriceTally();
                }
            });
    },

    unlockSeatRemote(seatNo) {
        const url = `../api/sync_seats.php?action=unlock_seat&bus_id=${this.busId}&journey_date=${this.journeyDate}&seat_number=${seatNo}&session_token=${this.sessionToken}`;
        fetch(url);
    },

    updateSummaryCounters(summary) {
        if (!summary) return;
        const availEl = document.getElementById('summaryCountAvailable');
        const onlineEl = document.getElementById('summaryCountOnline');
        const counterEl = document.getElementById('summaryCountCounter');
        const lockedEl = document.getElementById('summaryCountLocked');

        if (availEl) availEl.textContent = summary.available;
        if (onlineEl) onlineEl.textContent = summary.booked_online;
        if (counterEl) counterEl.textContent = summary.booked_offline;
        if (lockedEl) lockedEl.textContent = summary.locked;
    },

    updatePriceTally() {
        const count = this.selectedSeats.length;
        const base = count * this.farePerSeat;
        const gst = Math.round(base * 0.05 * 100) / 100;
        const total = base + gst;

        const countEl = document.getElementById('counterSelectedCount');
        const tagsEl = document.getElementById('counterSelectedSeatsTags');
        const baseEl = document.getElementById('counterBaseFareDisplay');
        const gstEl = document.getElementById('counterGstDisplay');
        const totalEl = document.getElementById('counterTotalAmountDisplay');
        const hiddenSeats = document.getElementById('counterHiddenSeats');
        const hiddenTotal = document.getElementById('counterHiddenTotal');
        const issueBtn = document.getElementById('btnIssueCounterTicket');

        if (countEl) countEl.textContent = count;
        if (baseEl) baseEl.textContent = `₹${base.toFixed(2)}`;
        if (gstEl) gstEl.textContent = `₹${gst.toFixed(2)}`;
        if (totalEl) totalEl.textContent = `₹${total.toFixed(2)}`;
        if (hiddenSeats) hiddenSeats.value = this.selectedSeats.join(',');
        if (hiddenTotal) hiddenTotal.value = total.toFixed(2);

        if (tagsEl) {
            tagsEl.innerHTML = count === 0
                ? '<span style="color:#94a3b8; font-size:13px;">No seats selected</span>'
                : this.selectedSeats.map(s => `<span class="badge" style="background:#0284c7; color:#fff; font-size:12px; padding:4px 8px; border-radius:4px; font-weight:800;">${s}</span>`).join(' ');
        }

        if (issueBtn) {
            issueBtn.disabled = (count === 0);
        }

        this.calculateChange();
    },

    calculateChange() {
        const tenderInput = document.getElementById('counterCashTenderInput');
        const hiddenTotal = document.getElementById('counterHiddenTotal');
        const changeDisplay = document.getElementById('counterChangeReturnDisplay');

        if (!tenderInput || !hiddenTotal || !changeDisplay) return;

        const total = parseFloat(hiddenTotal.value || '0');
        const tendered = parseFloat(tenderInput.value || '0');

        if (tendered >= total && total > 0) {
            const change = tendered - total;
            changeDisplay.textContent = `₹${change.toFixed(2)}`;
            changeDisplay.style.color = '#10b981';
        } else {
            changeDisplay.textContent = '₹0.00';
            changeDisplay.style.color = '#94a3b8';
        }
    },

    updatePassengerForm() {
        const container = document.getElementById('counterPassengerFieldsContainer');
        if (!container) return;

        if (this.selectedSeats.length === 0) {
            container.innerHTML = '<p style="color:#94a3b8; font-size:13px;">Select seat(s) on the matrix to enter passenger names.</p>';
            return;
        }

        let html = '<div style="display:flex; flex-direction:column; gap:10px;">';
        this.selectedSeats.forEach((seat, i) => {
            html += `
                <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; padding:10px; display:grid; grid-template-columns: 2fr 1fr 1fr; gap:8px; align-items:center;">
                    <div>
                        <input type="text" name="passenger_names[]" class="form-control-custom" style="height:36px; padding:4px 10px; font-size:13px;" placeholder="Passenger ${i + 1} Name" required>
                    </div>
                    <div>
                        <input type="number" name="passenger_ages[]" class="form-control-custom" style="height:36px; padding:4px 10px; font-size:13px;" placeholder="Age" min="1" max="120" value="30" required>
                    </div>
                    <div>
                        <select name="passenger_genders[]" class="form-control-custom" style="height:36px; padding:4px 8px; font-size:13px;" required>
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                        </select>
                    </div>
                </div>
            `;
        });
        html += '</div>';
        container.innerHTML = html;
    }
};

document.addEventListener('DOMContentLoaded', () => {
    CounterBooking.init();
});

window.CounterBooking = CounterBooking;
