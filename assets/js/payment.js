/**
 * Gopi Travels - Demo Net Banking Gateway Simulator
 * Provides bank selection, simulated credentials, and 60-second interactive OTP verification
 */

const NetBankingDemo = {
    selectedBank: 'State Bank of India',
    otpTimer: null,
    timeLeft: 60,

    init() {
        const bankItems = document.querySelectorAll('.bank-item-option');
        const hiddenBankInput = document.getElementById('selectedBankInput');

        bankItems.forEach(item => {
            item.addEventListener('click', () => {
                bankItems.forEach(b => b.classList.remove('selected'));
                item.classList.add('selected');
                const bankName = item.dataset.bank || 'State Bank of India';
                this.selectedBank = bankName;
                if (hiddenBankInput) hiddenBankInput.value = bankName;

                const displayBankEl = document.getElementById('modalSelectedBankName');
                if (displayBankEl) displayBankEl.textContent = bankName;
            });
        });

        // Setup OTP auto advance inputs
        const otpInputs = document.querySelectorAll('.otp-digit-field');
        otpInputs.forEach((input, index) => {
            input.addEventListener('input', (e) => {
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
     * Open OTP Verification Modal
     */
    openOtpModal() {
        // Validate primary contact fields first
        const contactPhone = document.getElementById('contactPhone');
        const contactEmail = document.getElementById('contactEmail');
        
        if (contactPhone && !contactPhone.value) {
            alert('Please enter primary passenger mobile number.');
            contactPhone.focus();
            return;
        }

        const modal = document.getElementById('otpModalOverlay');
        if (!modal) return;

        modal.style.display = 'flex';
        this.startTimer();

        // Focus first OTP field
        const firstOtp = document.querySelector('.otp-digit-field');
        if (firstOtp) {
            setTimeout(() => firstOtp.focus(), 200);
        }
    },

    /**
     * Close OTP Modal
     */
    closeOtpModal() {
        const modal = document.getElementById('otpModalOverlay');
        if (modal) modal.style.display = 'none';
        if (this.otpTimer) clearInterval(this.otpTimer);
    },

    /**
     * Start 60-Second OTP Timer
     */
    startTimer() {
        if (this.otpTimer) clearInterval(this.otpTimer);
        this.timeLeft = 60;
        const timerDisplay = document.getElementById('otpTimerCountdown');
        const resendBtn = document.getElementById('btnResendOtp');

        if (resendBtn) resendBtn.style.display = 'none';

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
                if (resendBtn) resendBtn.style.display = 'inline-block';
            }
        }, 1000);
    },

    /**
     * Verify Demo OTP and Submit Booking
     */
    verifyAndPay() {
        const otpInputs = document.querySelectorAll('.otp-digit-field');
        let enteredOtp = '';
        otpInputs.forEach(i => enteredOtp += i.value);

        if (enteredOtp.length < 6) {
            alert('Please enter all 6 digits of the OTP.');
            return;
        }

        // Demo OTP is 123456 or any 6 digit test code
        const submitBtn = document.getElementById('btnConfirmOtpPay');
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Verifying with ' + this.selectedBank + '...';
        }

        // Simulate banking network roundtrip (1.2 seconds)
        setTimeout(() => {
            const form = document.getElementById('bookingPaymentForm');
            if (form) {
                form.submit();
            } else {
                alert('Payment authorized successfully!');
            }
        }, 1200);
    }
};

document.addEventListener('DOMContentLoaded', () => {
    NetBankingDemo.init();
});

window.NetBankingDemo = NetBankingDemo;
