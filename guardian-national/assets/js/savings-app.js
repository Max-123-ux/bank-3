// Session timer
let sessionTimer;
const SESSION_DURATION = 60000; // 60 seconds

function startSessionTimer() {
    clearTimeout(sessionTimer);
    updateTimerDisplay();
    
    sessionTimer = setTimeout(() => {
        alert('Your session has expired. Please login again.');
        window.location.href = '../portal/logout.php?timeout=1';
    }, SESSION_DURATION);
}

function updateTimerDisplay() {
    const timerElement = document.getElementById('session-timer');
    if (timerElement) {
        let timeLeft = SESSION_DURATION;
        const updateDisplay = setInterval(() => {
            timeLeft -= 1000;
            const seconds = Math.floor(timeLeft / 1000);
            timerElement.textContent = `Session expires in: ${seconds}s`;
            
            if (timeLeft <= 0) {
                clearInterval(updateDisplay);
            }
        }, 1000);
    }
}

// Reset timer on user activity
document.addEventListener('click', startSessionTimer);
document.addEventListener('keypress', startSessionTimer);

// Form validation
function validateNationalID(id) {
    const regex = /^GNS-\d{3}-\d{3}$/;
    return regex.test(id);
}

function formatCurrency(amount) {
    return 'K ' + parseFloat(amount).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
}

// Confirm actions
function confirmAction(message) {
    return confirm(message);
}

// Print certificate
function printCertificate() {
    window.print();
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', () => {
    startSessionTimer();
    
    // Format currency displays
    document.querySelectorAll('.currency-display').forEach(el => {
        const amount = el.textContent;
        el.textContent = formatCurrency(amount);
    });
});