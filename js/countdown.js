// Countdown Timer
function initCountdown(targetDate) {
    const countdownElement = document.getElementById('countdown');
    if (!countdownElement) return;

    const target = new Date(targetDate).getTime();

    function updateCountdown() {
        const now = new Date().getTime();
        const distance = target - now;

        if (distance < 0) {
            countdownElement.innerHTML = '<div class="countdown-expired">Acara telah berlangsung</div>';
            return;
        }

        const days = Math.floor(distance / (1000 * 60 * 60 * 24));
        const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
        const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
        const seconds = Math.floor((distance % (1000 * 60)) / 1000);

        const daysEl = countdownElement.querySelector('.days');
        const hoursEl = countdownElement.querySelector('.hours');
        const minutesEl = countdownElement.querySelector('.minutes');
        const secondsEl = countdownElement.querySelector('.seconds');

        if (daysEl) daysEl.textContent = days;
        if (hoursEl) hoursEl.textContent = hours;
        if (minutesEl) minutesEl.textContent = minutes;
        if (secondsEl) secondsEl.textContent = seconds;
    }

    updateCountdown();
    setInterval(updateCountdown, 1000);
}

// Auto-initialize if countdown element exists
document.addEventListener('DOMContentLoaded', function() {
    const countdownElement = document.getElementById('countdown');
    if (countdownElement) {
        const targetDate = countdownElement.getAttribute('data-date');
        if (targetDate) {
            initCountdown(targetDate);
        }
    }
});
