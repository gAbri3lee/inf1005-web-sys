document.addEventListener('DOMContentLoaded', function() {
    // Add active class to current nav link
    const currentPath = window.location.pathname.split('/').pop();
    const navLinks = document.querySelectorAll('.nav-link');
    navLinks.forEach(link => {
        if (link.getAttribute('href') === currentPath) {
            link.classList.add('active');
        }
    });

    // Simple form validation for booking
    const bookingForm = document.querySelector('form[action^="booking.php"]');
    if (bookingForm) {
        bookingForm.addEventListener('submit', function(e) {
            const checkIn = document.getElementById('check_in').value;
            const checkOut = document.getElementById('check_out').value;
            if (new Date(checkIn) >= new Date(checkOut)) {
                e.preventDefault();
                alert('Check-out date must be after check-in date.');
            }
        });
    }

    // Dynamic price calculation for booking
    const checkInInput = document.getElementById('check_in');
    const checkOutInput = document.getElementById('check_out');
    if (checkInInput && checkOutInput) {
        const pricePerNight = parseFloat(document.querySelector('.fw-bold.text-primary.fs-4').textContent.replace('Price: $', '').replace(' / night', ''));
        const updatePrice = () => {
            const checkIn = new Date(checkInInput.value);
            const checkOut = new Date(checkOutInput.value);
            if (checkIn && checkOut && checkOut > checkIn) {
                const days = (checkOut - checkIn) / (1000 * 60 * 60 * 24);
                const totalPrice = days * pricePerNight;
                const priceDisplay = document.querySelector('.fw-bold.text-primary.fs-4');
                priceDisplay.innerHTML = `Total Price: $${totalPrice.toFixed(2)} (${days} nights)`;
            }
        };
        checkInInput.addEventListener('change', updatePrice);
        checkOutInput.addEventListener('change', updatePrice);
    }
});
