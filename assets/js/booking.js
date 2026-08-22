/**
 * Gopi Travels - Dynamic Live Seat Matrix & Passenger Form Engine
 * Handles 2x2 Seater & 2x1 Sleeper Layouts, Real-time Pricing, and Multi-Passenger Inputs
 */

const BookingApp = {
    activeBusId: null,
    activeBusFare: 0,
    isSleeper: false,
    selectedSeats: [],
    maxSeats: 6,
    bookedSeats: {},

    /**
     * Toggle Seat Selector Accordion for a Bus Card
     */
    toggleSeatMatrix(busId, fare, isSleeper, busNumber, bookedSeatsList = []) {
        const container = document.getElementById(`seatContainer_${busId}`);
        const btn = document.getElementById(`btnSelectSeat_${busId}`);
        
        if (!container) return;

        // If clicking on already open container, toggle close
        if (container.classList.contains('open')) {
            container.classList.remove('open');
            if (btn) {
                btn.classList.remove('active');
                btn.innerHTML = '<i class="fas fa-couch"></i> Select Seats';
            }
            this.activeBusId = null;
            return;
        }

        // Close any other open containers
        document.querySelectorAll('.seat-selection-container.open').forEach(el => {
            el.classList.remove('open');
        });
        document.querySelectorAll('.btn-select-seat.active').forEach(b => {
            b.classList.remove('active');
            b.innerHTML = '<i class="fas fa-couch"></i> Select Seats';
        });

        // Set active bus
        this.activeBusId = busId;
        this.activeBusFare = parseFloat(fare);
        this.isSleeper = (isSleeper == 1 || isSleeper === true);
        this.selectedSeats = [];
        this.bookedSeats[busId] = bookedSeatsList;

        container.classList.add('open');
        if (btn) {
            btn.classList.add('active');
            btn.innerHTML = '<i class="fas fa-times"></i> Close Matrix';
        }

        // Fetch live sync seats from API (integrates counter bookings and active locks)
        const journeyDateInput = document.querySelector('input[name="journey_date"]');
        const journeyDate = journeyDateInput ? journeyDateInput.value : new Date().toISOString().split('T')[0];

        fetch(`api/sync_seats.php?action=get_matrix&bus_id=${busId}&journey_date=${journeyDate}`)
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success' && data.seats) {
                    const online = data.seats.booked_online || [];
                    const counter = data.seats.booked_offline || [];
                    const locked = Object.keys(data.seats.locked || {});
                    this.bookedSeats[busId] = [...new Set([...online, ...counter, ...locked])];
                }
                this.renderSeats(busId);
                this.updatePricing(busId);
            })
            .catch(() => {
                this.renderSeats(busId);
                this.updatePricing(busId);
            });
    },

    /**
     * Render the Seat Grid (2x2 Seater or 2x1 Sleeper)
     */
    renderSeats(busId) {
        const matrixContainer = document.getElementById(`seatMatrix_${busId}`);
        if (!matrixContainer) return;

        const booked = this.bookedSeats[busId] || [];

        if (this.isSleeper) {
            // Sleeper layout with Lower & Upper decks
            matrixContainer.innerHTML = `
                <div class="deck-tabs">
                    <button type="button" class="deck-tab-btn active" onclick="BookingApp.switchDeck('${busId}', 'lower')">
                        <i class="fas fa-layer-group"></i> Lower Berth Deck (15 Berths)
                    </button>
                    <button type="button" class="deck-tab-btn" onclick="BookingApp.switchDeck('${busId}', 'upper')">
                        <i class="fas fa-layer-group"></i> Upper Berth Deck (15 Berths)
                    </button>
                </div>
                <div id="deckLower_${busId}" class="deck-view active">
                    <div class="sleeper-grid-layout">
                        ${this.generateSleeperBerths('L', 1, 15, booked, busId)}
                    </div>
                </div>
                <div id="deckUpper_${busId}" class="deck-view" style="display:none;">
                    <div class="sleeper-grid-layout">
                        ${this.generateSleeperBerths('U', 1, 15, booked, busId)}
                    </div>
                </div>
            `;
        } else {
            // Seater 2x2 layout (40 seats)
            matrixContainer.innerHTML = `
                <div class="seater-grid-layout">
                    ${this.generateSeaterSeats(40, booked, busId)}
                </div>
            `;
        }
    },

    /**
     * Switch Deck View for Sleeper Buses
     */
    switchDeck(busId, deck) {
        const lowerView = document.getElementById(`deckLower_${busId}`);
        const upperView = document.getElementById(`deckUpper_${busId}`);
        const tabs = document.querySelectorAll(`#seatMatrix_${busId} .deck-tab-btn`);

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

    /**
     * Generate 2x2 Seater Seat Elements
     */
    generateSeaterSeats(totalSeats, bookedList, busId) {
        let html = '';
        const rows = Math.ceil(totalSeats / 4);
        const seatCols = ['A', 'B', 'C', 'D'];

        for (let r = 1; r <= rows; r++) {
            for (let c = 0; c < 4; c++) {
                const seatNo = `${r}${seatCols[c]}`;
                const isBooked = bookedList.includes(seatNo);
                const isSelected = this.selectedSeats.includes(seatNo);
                
                // Female reserved demo (first row seats)
                const isFemale = (seatNo === '1A' || seatNo === '1B') && !isBooked;

                let cls = 'bus-seat-unit';
                if (isBooked) cls += ' booked';
                else if (isSelected) cls += ' selected';
                else if (isFemale) cls += ' female-reserved';

                const clickAction = isBooked ? '' : `onclick="BookingApp.toggleSeat('${busId}', '${seatNo}')"`;
                const titleText = isBooked ? 'Already Booked' : (isFemale ? 'Female Reserved - Available' : `Seat ${seatNo}`);

                html += `
                    <div class="${cls}" data-seat="${seatNo}" ${clickAction} title="${titleText}">
                        <i class="fas fa-couch"></i>
                        <span>${seatNo}</span>
                    </div>
                `;
            }
        }
        return html;
    },

    /**
     * Generate 2x1 Sleeper Berths
     */
    generateSleeperBerths(prefix, start, count, bookedList, busId) {
        let html = '';
        for (let i = start; i <= count; i++) {
            const seatNo = `${prefix}${i}`;
            const isBooked = bookedList.includes(seatNo);
            const isSelected = this.selectedSeats.includes(seatNo);

            let cls = 'sleeper-berth-unit';
            if (isBooked) cls += ' booked';
            else if (isSelected) cls += ' selected';

            const clickAction = isBooked ? '' : `onclick="BookingApp.toggleSeat('${busId}', '${seatNo}')"`;

            html += `
                <div class="${cls}" data-seat="${seatNo}" ${clickAction}>
                    <i class="fas fa-bed"></i>
                    <span>${seatNo}</span>
                    <small style="font-size:9px; color: inherit;">${prefix === 'L' ? 'Lower' : 'Upper'}</small>
                </div>
            `;
        }
        return html;
    },

    /**
     * Toggle Seat Selection
     */
    toggleSeat(busId, seatNo) {
        const index = this.selectedSeats.indexOf(seatNo);

        if (index > -1) {
            // Deselect
            this.selectedSeats.splice(index, 1);
        } else {
            // Select (check limit)
            if (this.selectedSeats.length >= this.maxSeats) {
                alert(`You can select a maximum of ${this.maxSeats} seats per booking.`);
                return;
            }
            this.selectedSeats.push(seatNo);
        }

        // Update UI element classes
        const seatEls = document.querySelectorAll(`#seatMatrix_${busId} [data-seat="${seatNo}"]`);
        seatEls.forEach(el => {
            if (this.selectedSeats.includes(seatNo)) {
                el.classList.add('selected');
            } else {
                el.classList.remove('selected');
            }
        });

        // Update pricing & passenger form
        this.updatePricing(busId);
        this.updatePassengerInputs(busId);
    },

    /**
     * Update Live Pricing Breakdown
     */
    updatePricing(busId) {
        const count = this.selectedSeats.length;
        const baseFare = count * this.activeBusFare;
        const gst = Math.round(baseFare * 0.05 * 100) / 100;
        const serviceFee = count > 0 ? 15.00 : 0.00;
        const total = baseFare + gst + serviceFee;

        // Update display elements
        const seatsDisplay = document.getElementById(`selectedSeatsList_${busId}`);
        const countDisplay = document.getElementById(`selectedCount_${busId}`);
        const baseDisplay = document.getElementById(`baseFareTotal_${busId}`);
        const gstDisplay = document.getElementById(`gstAmount_${busId}`);
        const grandDisplay = document.getElementById(`grandTotal_${busId}`);
        const proceedBtn = document.getElementById(`btnProceed_${busId}`);
        const hiddenSeatsInput = document.getElementById(`inputSeatNumbers_${busId}`);

        if (seatsDisplay) {
            if (count === 0) {
                seatsDisplay.innerHTML = '<span style="color:#94a3b8; font-size:13px;">No seats selected yet</span>';
            } else {
                seatsDisplay.innerHTML = this.selectedSeats.map(s => `<span class="selected-seat-tag">${s}</span>`).join(' ');
            }
        }

        if (countDisplay) countDisplay.textContent = count;
        if (baseDisplay) baseDisplay.textContent = `₹${baseFare.toFixed(2)}`;
        if (gstDisplay) gstDisplay.textContent = `₹${gst.toFixed(2)}`;
        if (grandDisplay) grandDisplay.textContent = `₹${total.toFixed(2)}`;
        if (hiddenSeatsInput) hiddenSeatsInput.value = this.selectedSeats.join(',');

        if (proceedBtn) {
            proceedBtn.disabled = (count === 0);
        }
    },

    /**
     * Dynamically Generate Passenger Details Inputs
     */
    updatePassengerInputs(busId) {
        const container = document.getElementById(`passengerDetailsList_${busId}`);
        if (!container) return;

        if (this.selectedSeats.length === 0) {
            container.innerHTML = '<p style="color:#94a3b8; font-size:13px; margin: 10px 0;">Please select seats above to enter passenger details.</p>';
            return;
        }

        let html = '<div style="display:flex; flex-direction:column; gap:12px; margin-top:14px;">';
        this.selectedSeats.forEach((seat, idx) => {
            html += `
                <div class="passenger-mini-card" style="background:#ffffff; border:1px solid #e2e8f0; border-radius:8px; padding:12px;">
                    <div style="font-size:13px; font-weight:700; color:#0f172a; margin-bottom:8px; display:flex; justify-content:space-between;">
                        <span><i class="fas fa-user me-1 text-primary"></i> Passenger ${idx + 1}</span>
                        <span class="badge" style="background:#ffedd5; color:#c2410c; padding:2px 8px; border-radius:4px;">Seat: ${seat}</span>
                    </div>
                    <div style="display:grid; grid-template-columns: 2fr 1fr 1.2fr; gap:8px;">
                        <div>
                            <input type="text" name="passenger_names[]" class="form-control-custom" style="height:38px; padding:6px 10px; font-size:13px;" placeholder="Full Name" required>
                        </div>
                        <div>
                            <input type="number" name="passenger_ages[]" class="form-control-custom" style="height:38px; padding:6px 10px; font-size:13px;" placeholder="Age" min="1" max="120" required>
                        </div>
                        <div>
                            <select name="passenger_genders[]" class="form-control-custom" style="height:38px; padding:6px 10px; font-size:13px;" required>
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                    </div>
                </div>
            `;
        });
        html += '</div>';
        container.innerHTML = html;
    }
};

window.BookingApp = BookingApp;
