/**
 * Gopi Travels - GopiPay™ Custom Dummy Payment Gateway Engine (Sandbox Simulator)
 *
 * First-party, fully interactive simulator featuring:
 * - Dynamic GopiPay UPI & QR Code Scanner with real-time countdown & simulated push notification
 * - Interactive 3D Card Visualizer with Visa/Mastercard/RuPay auto-detection & quick test chips
 * - 40+ Bank Net Banking with 2FA OTP simulation modal
 * - Digital Wallets (Paytm, PhonePe, Mobikwik, OlaMoney, Amazon Pay)
 * - Pay on Boarding (Conductor Cash / Spot UPI)
 * - Sandbox Test Mode (Toggle Success vs Bank Decline / Insufficient Funds)
 * - 1-Click Fast Instant Pay
 */

const GopiPayGateway = {
    selectedMethod: 'UPI',
    selectedBank: 'State Bank of India',
    selectedWallet: 'Paytm Wallet',
    simulationMode: 'success', // 'success' or 'failed'
    declineReason: '',
    otpTimer: null,
    timeLeft: 60,
    qrTimeLeft: 600,
    qrTimer: null,

    init() {
        this.setupCardVisualizer();
        this.setupBankSelection();
        this.setupWalletSelection();
        this.setupOtpFields();
        this.startQrTimer();
    },

    /**
     * Switch Gateway Payment Method Tab
     */
    switchTab(tabId) {
        document.querySelectorAll('.dummy-nav-tab').forEach(t => t.classList.remove('active'));
        document.querySelectorAll('.dummy-tab-pane').forEach(p => p.classList.remove('active'));

        const activeTabBtn = document.getElementById(`tabBtn_${tabId}`);
        const activePane = document.getElementById(`tabPane_${tabId}`);

        if (activeTabBtn) activeTabBtn.classList.add('active');
        if (activePane) activePane.classList.add('active');

        this.selectedMethod = tabId.toUpperCase();
    },

    /**
     * Interactive 3D Card Visualizer & Auto-formatting
     */
    setupCardVisualizer() {
        const numInput = document.getElementById('card_number');
        const holderInput = document.getElementById('card_holder');
        const expiryInput = document.getElementById('card_expiry');
        const cvvInput = document.getElementById('card_cvv');

        const dispNum = document.getElementById('disp_card_number');
        const dispHolder = document.getElementById('disp_card_holder');
        const dispExpiry = document.getElementById('disp_card_expiry');
        const dispLogo = document.getElementById('disp_card_logo');

        if (numInput && dispNum) {
            numInput.addEventListener('input', (e) => {
                let val = e.target.value.replace(/\D/g, '').substring(0, 16);
                let formatted = val.match(/.{1,4}/g)?.join(' ') || '';
                e.target.value = formatted;

                dispNum.textContent = formatted || '•••• •••• •••• ••••';

                // Detect card brand
                if (val.startsWith('4')) {
                    dispLogo.innerHTML = '<i class="fab fa-cc-visa" style="color:#60a5fa;"></i>';
                } else if (/^(5[1-5]|2[2-7])/.test(val)) {
                    dispLogo.innerHTML = '<i class="fab fa-cc-mastercard" style="color:#f87171;"></i>';
                } else if (/^(60|65|81|82)/.test(val)) {
                    dispLogo.innerHTML = '<span style="font-weight:900; color:#34d399; font-size:18px;">RuPay</span>';
                } else if (/^(34|37)/.test(val)) {
                    dispLogo.innerHTML = '<i class="fab fa-cc-amex" style="color:#38bdf8;"></i>';
                } else {
                    dispLogo.innerHTML = '<i class="fas fa-credit-card text-muted"></i>';
                }
            });
        }

        if (holderInput && dispHolder) {
            holderInput.addEventListener('input', (e) => {
                dispHolder.textContent = (e.target.value.trim() || 'PASSENGER NAME').toUpperCase();
            });
        }

        if (expiryInput && dispExpiry) {
            expiryInput.addEventListener('input', (e) => {
                let val = e.target.value.replace(/\D/g, '').substring(0, 4);
                if (val.length >= 2) {
                    val = val.substring(0, 2) + '/' + val.substring(2);
                }
                e.target.value = val;
                dispExpiry.textContent = val || 'MM/YY';
            });
        }

        if (cvvInput) {
            cvvInput.addEventListener('input', (e) => {
                e.target.value = e.target.value.replace(/\D/g, '').substring(0, 4);
            });
        }
    },

    /**
     * Autofill GopiPay Test Cards
     */
    fillTestCard(type) {
        this.switchTab('card');

        const numInput = document.getElementById('card_number');
        const holderInput = document.getElementById('card_holder');
        const expiryInput = document.getElementById('card_expiry');
        const cvvInput = document.getElementById('card_cvv');

        if (type === 'visa') {
            if (numInput) numInput.value = '4111 2222 3333 4444';
            if (holderInput) holderInput.value = 'GOPIPAY TEST VISA';
            if (expiryInput) expiryInput.value = '12/28';
            if (cvvInput) cvvInput.value = '123';
            this.setSimulationMode('success');
        } else if (type === 'mastercard') {
            if (numInput) numInput.value = '5500 0000 0000 0004';
            if (holderInput) holderInput.value = 'GOPIPAY TEST MASTERCARD';
            if (expiryInput) expiryInput.value = '08/29';
            if (cvvInput) cvvInput.value = '456';
            this.setSimulationMode('success');
        } else if (type === 'rupay') {
            if (numInput) numInput.value = '6070 1234 5678 9012';
            if (holderInput) holderInput.value = 'GOPIPAY TEST RUPAY';
            if (expiryInput) expiryInput.value = '11/27';
            if (cvvInput) cvvInput.value = '789';
            this.setSimulationMode('success');
        } else if (type === 'fail' || type === 'decline') {
            if (numInput) numInput.value = '4000 0000 0000 0002';
            if (holderInput) holderInput.value = 'GOPIPAY DECLINED CARD';
            if (expiryInput) expiryInput.value = '04/26';
            if (cvvInput) cvvInput.value = '000';
            this.setSimulationMode('failed', 'Transaction declined by bank through GopiPay Gateway (Insufficient Funds)');
        }

        if (numInput) numInput.dispatchEvent(new Event('input'));
        if (holderInput) holderInput.dispatchEvent(new Event('input'));
        if (expiryInput) expiryInput.dispatchEvent(new Event('input'));
    },

    // Backward-compatibility alias
    fillEasebuzzCard(type) {
        this.fillTestCard(type);
    },

    /**
     * Submit Card Payment via GopiPay
     */
    submitCardPayment() {
        const numInput = document.getElementById('card_number');
        const val = numInput ? numInput.value.replace(/\s/g, '') : '';

        if (!val || val.length < 15) {
            alert('Please enter a valid card number (or click a GopiPay Test Card chip).');
            if (numInput) numInput.focus();
            return;
        }

        const last4 = val.slice(-4);
        const btn = document.getElementById('btnPayCard');
        if (btn) {
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Connecting to GopiPay 3D Secure...';
        }

        setTimeout(() => {
            this.submitPayment(`GopiPay - Card (Ending in ${last4})`);
        }, 850);
    },

    /**
     * Net Banking Selection
     */
    setupBankSelection() {
        const bankItems = document.querySelectorAll('.bank-item-option');
        const otherBankSelect = document.getElementById('otherBankSelect');

        bankItems.forEach(item => {
            item.addEventListener('click', () => {
                bankItems.forEach(b => b.classList.remove('selected'));
                item.classList.add('selected');
                const bankName = item.dataset.bank || 'State Bank of India';
                this.selectedBank = bankName;

                const displayBankEl = document.getElementById('modalSelectedBankName');
                if (displayBankEl) displayBankEl.textContent = bankName;

                if (otherBankSelect) otherBankSelect.value = '';
            });
        });

        if (otherBankSelect) {
            otherBankSelect.addEventListener('change', (e) => {
                if (e.target.value) {
                    bankItems.forEach(b => b.classList.remove('selected'));
                    this.selectedBank = e.target.value;
                    const displayBankEl = document.getElementById('modalSelectedBankName');
                    if (displayBankEl) displayBankEl.textContent = e.target.value;
                }
            });
        }
    },

    /**
     * Wallet Selection
     */
    setupWalletSelection() {
        const walletItems = document.querySelectorAll('.wallet-card-option');
        walletItems.forEach(item => {
            item.addEventListener('click', () => {
                walletItems.forEach(w => w.classList.remove('selected'));
                item.classList.add('selected');
                this.selectedWallet = item.dataset.wallet || 'Paytm Wallet';
                const radio = item.querySelector('input[type="radio"]');
                if (radio) radio.checked = true;
            });
        });
    },

    /**
     * Set Simulation Mode (Success vs Failure)
     */
    setSimulationMode(mode, reason = '') {
        this.simulationMode = mode;
        this.declineReason = reason || (mode === 'failed' ? 'GopiPay simulated payment decline' : '');

        const hiddenStatus = document.getElementById('gopipaySimulationStatus') || document.getElementById('easebuzzSimulationStatus');
        const hiddenReason = document.getElementById('gopipayDeclineReason') || document.getElementById('easebuzzDeclineReason');
        const radioSuccess = document.getElementById('simRadioSuccess');
        const radioFailed = document.getElementById('simRadioFailed');

        if (hiddenStatus) hiddenStatus.value = mode;
        if (hiddenReason) hiddenReason.value = this.declineReason;

        if (mode === 'failed') {
            if (radioFailed) radioFailed.checked = true;
        } else {
            if (radioSuccess) radioSuccess.checked = true;
        }
    },

    /**
     * Start QR Code 10-Minute Expiry Countdown
     */
    startQrTimer() {
        const qrTimerEl = document.getElementById('qrCountdownDisplay');
        if (!qrTimerEl) return;

        if (this.qrTimer) clearInterval(this.qrTimer);
        this.qrTimeLeft = 600;

        this.qrTimer = setInterval(() => {
            this.qrTimeLeft--;
            const mins = String(Math.floor(this.qrTimeLeft / 60)).padStart(2, '0');
            const secs = String(this.qrTimeLeft % 60).padStart(2, '0');
            qrTimerEl.textContent = `${mins}:${secs}`;

            if (this.qrTimeLeft <= 0) {
                clearInterval(this.qrTimer);
                qrTimerEl.textContent = 'Expired';
            }
        }, 1000);
    },

    /**
     * UPI App Click Trigger
     */
    triggerUpiApp(appName) {
        const upiIdInput = document.getElementById('dummyUpiId');
        if (upiIdInput) {
            upiIdInput.value = `passenger@${appName.toLowerCase().replace(/[^a-z]/g, '')}`;
        }
        this.simulateUpiApproval(appName);
    },

    /**
     * Quick Fill UPI VPA ID
     */
    fillUpiId(vpa) {
        const upiIdInput = document.getElementById('dummyUpiId');
        if (upiIdInput) {
            upiIdInput.value = vpa;
        }
    },

    /**
     * Simulate Incoming UPI Push Notification & Modal
     */
    simulateUpiApproval(appName = 'Google Pay / PhonePe') {
        const upiIdInput = document.getElementById('dummyUpiId');
        const upiId = upiIdInput && upiIdInput.value ? upiIdInput.value : 'demo@upi';

        const modal = document.getElementById('upiSimModalOverlay');
        const appNameEl = document.getElementById('upiSimAppName');
        const vpaEl = document.getElementById('upiSimVpa');

        if (appNameEl) appNameEl.textContent = appName;
        if (vpaEl) vpaEl.textContent = upiId;

        if (modal) {
            modal.style.display = 'flex';
        } else {
            this.submitPayment('GopiPay - UPI (' + appName + ')');
        }
    },

    closeUpiModal() {
        const modal = document.getElementById('upiSimModalOverlay');
        if (modal) modal.style.display = 'none';
    },

    confirmUpiPushApproval() {
        const btn = document.getElementById('btnConfirmUpiPush');
        if (btn) {
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Authorizing GopiPay UPI Intent...';
        }

        setTimeout(() => {
            const upiIdInput = document.getElementById('dummyUpiId');
            const vpa = upiIdInput && upiIdInput.value ? upiIdInput.value : 'UPI / QR Code';
            this.submitPayment(`GopiPay - UPI (${vpa})`);
        }, 800);
    },

    /**
     * Setup 6-digit OTP fields
     */
    setupOtpFields() {
        const otpInputs = document.querySelectorAll('.otp-digit-field');
        otpInputs.forEach((input, index) => {
            input.addEventListener('input', () => {
                if (input.value.length === 1 && index < otpInputs.length - 1) {
                    otpInputs[index + 1].focus();
                }
            });

            input.addEventListener('keydown', (e) => {
                if (e.key === 'Backspace' && !input.value && index > 0) {
                    otpInputs[index - 1].focus();
                }
            });
        });
    },

    /**
     * Open 2FA OTP Modal (Net Banking)
     */
    openOtpModal() {
        const modal = document.getElementById('otpModalOverlay');
        if (!modal) {
            this.submitPayment('GopiPay - Net Banking (' + this.selectedBank + ')');
            return;
        }

        modal.style.display = 'flex';
        this.startOtpTimer();

        const firstOtp = document.querySelector('.otp-digit-field');
        if (firstOtp) setTimeout(() => firstOtp.focus(), 200);
    },

    closeOtpModal() {
        const modal = document.getElementById('otpModalOverlay');
        if (modal) modal.style.display = 'none';
        if (this.otpTimer) clearInterval(this.otpTimer);
    },

    startOtpTimer() {
        if (this.otpTimer) clearInterval(this.otpTimer);
        this.timeLeft = 60;
        const timerDisplay = document.getElementById('otpTimerCountdown');

        this.otpTimer = setInterval(() => {
            this.timeLeft--;
            if (timerDisplay) {
                const mins = String(Math.floor(this.timeLeft / 60)).padStart(2, '0');
                const secs = String(this.timeLeft % 60).padStart(2, '0');
                timerDisplay.textContent = `${mins}:${secs}`;
            }

            if (this.timeLeft <= 0) {
                clearInterval(this.otpTimer);
                if (timerDisplay) timerDisplay.textContent = '00:00 (Expired)';
            }
        }, 1000);
    },

    verifyAndPay() {
        const otpInputs = document.querySelectorAll('.otp-digit-field');
        let enteredOtp = '';
        otpInputs.forEach(i => enteredOtp += i.value);

        if (enteredOtp.length < 6) {
            alert('Please enter all 6 digits of the GopiPay 2FA OTP.');
            return;
        }

        const submitBtn = document.getElementById('btnConfirmOtpPay');
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Verifying with Bank 2FA Server...';
        }

        setTimeout(() => {
            this.submitPayment(`GopiPay - Net Banking (${this.selectedBank})`);
        }, 900);
    },

    /**
     * Submit Wallet Payment
     */
    submitWalletPayment() {
        const btn = document.getElementById('btnPayWallet');
        if (btn) {
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Debiting via GopiPay Wallet Hub...';
        }

        setTimeout(() => {
            this.submitPayment(`GopiPay - Wallet (${this.selectedWallet})`);
        }, 750);
    },

    /**
     * Submit Pay on Boarding Cash
     */
    submitCashPayment() {
        const btn = document.getElementById('btnPayCash');
        if (btn) {
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Confirming Reservation...';
        }

        setTimeout(() => {
            this.submitPayment('Cash on Boarding');
        }, 500);
    },

    /**
     * 1-Click Fast Instant Pay
     */
    fastPay() {
        const btn = document.getElementById('btnFastGopiPay') || document.getElementById('btnFastEasebuzzPay');
        if (btn) {
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Authorizing Instant 1-Click Pay...';
        }

        setTimeout(() => {
            this.submitPayment('GopiPay (1-Click Instant)');
        }, 400);
    },

    fastEasebuzzPay() {
        this.fastPay();
    },

    /**
     * Final Form Submission
     */
    submitPayment(methodName) {
        const form = document.getElementById('bookingPaymentForm') || document.getElementById('bookingConfirmForm');
        if (!form) return;

        const methodInput = document.getElementById('paymentMethodInput');
        const txnInput = document.getElementById('transactionIdInput');
        const simInput = document.getElementById('gopipaySimulationStatus') || document.getElementById('easebuzzSimulationStatus');
        const reasonInput = document.getElementById('gopipayDeclineReason') || document.getElementById('easebuzzDeclineReason');

        // Check radio toggle
        const radioFailed = document.getElementById('simRadioFailed');
        if (radioFailed && radioFailed.checked) {
            this.simulationMode = 'failed';
        }

        if (methodInput) methodInput.value = methodName;
        if (simInput) simInput.value = this.simulationMode;
        if (reasonInput) reasonInput.value = this.declineReason || (this.simulationMode === 'failed' ? 'GopiPay simulated payment decline' : '');

        // Generate official GopiPay transaction reference
        const datePrefix = new Date().toISOString().slice(2,10).replace(/-/g, '');
        const randHex = Math.random().toString(36).substring(2, 10).toUpperCase();
        const txnid = `GP-${datePrefix}-${randHex}`;

        if (txnInput) txnInput.value = txnid;

        form.submit();
    }
};

// Global aliases for backwards compatibility
window.GopiPayGateway = GopiPayGateway;
window.DummyGateway = GopiPayGateway;
window.EasebuzzGateway = GopiPayGateway;
window.NetBankingDemo = {
    openOtpModal() { GopiPayGateway.openOtpModal(); },
    closeOtpModal() { GopiPayGateway.closeOtpModal(); },
    verifyAndPay() { GopiPayGateway.verifyAndPay(); }
};

document.addEventListener('DOMContentLoaded', () => {
    GopiPayGateway.init();
});
