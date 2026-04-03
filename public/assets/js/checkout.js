(() => {
	'use strict';

	const form = document.querySelector('form[action^="checkout.php"]');
	if (!form) return;

	const cardEl = document.getElementById('card_number');
	const expiryEl = document.getElementById('expiry');
	const cvvEl = document.getElementById('cvv');

	function digitsOnly(v) {
		return String(v || '').replace(/\D+/g, '');
	}

	function formatCardNumber(value) {
		const digits = digitsOnly(value).slice(0, 16);
		return digits.replace(/(.{4})/g, '$1 ').trim();
	}

	function formatExpiry(value) {
		const digits = digitsOnly(value).slice(0, 4);
		if (digits.length <= 2) return digits;
		return digits.slice(0, 2) + '/' + digits.slice(2);
	}

	function expiryValid(value) {
		const m = String(value || '').match(/^(0[1-9]|1[0-2])\/(\d{2})$/);
		if (!m) return false;
		const month = Number(m[1]);
		const year = 2000 + Number(m[2]);
		if (!month || !year) return false;

		// End of expiry month
		const end = new Date(year, month, 0, 23, 59, 59, 999);
		return end.getTime() >= Date.now();
	}

	function setInvalid(input, isInvalid) {
		if (!input) return;
		input.classList.toggle('is-invalid', isInvalid);
		input.classList.toggle('is-valid', !isInvalid && input.value !== '');
	}

	if (cardEl) {
		cardEl.addEventListener('input', () => {
			const formatted = formatCardNumber(cardEl.value);
			if (cardEl.value !== formatted) cardEl.value = formatted;

			const digits = digitsOnly(cardEl.value);
			setInvalid(cardEl, digits.length > 0 && digits.length !== 16);
		});
	}

	if (expiryEl) {
		expiryEl.addEventListener('input', () => {
			const formatted = formatExpiry(expiryEl.value);
			if (expiryEl.value !== formatted) expiryEl.value = formatted;

			setInvalid(expiryEl, expiryEl.value !== '' && !expiryValid(expiryEl.value));
		});
	}

	if (cvvEl) {
		cvvEl.addEventListener('input', () => {
			const digits = digitsOnly(cvvEl.value).slice(0, 3);
			if (cvvEl.value !== digits) cvvEl.value = digits;
			setInvalid(cvvEl, digits.length > 0 && digits.length !== 3);
		});
	}

	form.addEventListener('submit', (e) => {
		let ok = true;

		if (cardEl) {
			const digits = digitsOnly(cardEl.value);
			const invalid = digits.length !== 16;
			setInvalid(cardEl, invalid);
			ok = ok && !invalid;
		}

		if (expiryEl) {
			const invalid = !expiryValid(expiryEl.value);
			setInvalid(expiryEl, invalid);
			ok = ok && !invalid;
		}

		if (cvvEl) {
			const digits = digitsOnly(cvvEl.value);
			const invalid = digits.length !== 3;
			setInvalid(cvvEl, invalid);
			ok = ok && !invalid;
		}

		if (!ok) {
			e.preventDefault();
			const first = form.querySelector('.is-invalid');
			if (first && typeof first.focus === 'function') first.focus();
		}
	});
})();
