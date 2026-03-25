document.addEventListener('DOMContentLoaded', function () {
	const toggles = document.querySelectorAll('.register-toggle');

	toggles.forEach(function (button) {
		button.addEventListener('click', function () {
			const targetId = button.getAttribute('data-target');
			if (!targetId) return;

			const input = document.getElementById(targetId);
			if (!input) return;

			const isPassword = input.getAttribute('type') === 'password';
			input.setAttribute('type', isPassword ? 'text' : 'password');
			button.textContent = isPassword ? 'Hide' : 'Show';
			button.setAttribute('aria-pressed', isPassword ? 'true' : 'false');
		});
	});

	const form = document.querySelector('.register-form');
	const passwordInput = document.getElementById('password');
	const confirmInput = document.getElementById('confirm_password');
	const help = document.getElementById('password_help');
	const ruleItems = help ? help.querySelectorAll('[data-rule]') : [];

	function rulesFor(pw) {
		const value = String(pw || '');
		return {
			length: value.length >= 8,
			upper: /[A-Z]/.test(value),
			lower: /[a-z]/.test(value),
			digit: /\d/.test(value),
			symbol: /[^A-Za-z\d]/.test(value),
		};
	}

	function updatePasswordHelp() {
		if (!passwordInput || !help) return;
		const r = rulesFor(passwordInput.value);
		ruleItems.forEach(function (li) {
			const key = li.getAttribute('data-rule');
			const ok = Boolean(r[key]);
			li.classList.toggle('is-met', ok);
			li.classList.toggle('is-missing', !ok);
		});
	}

	function passwordMeetsAll() {
		if (!passwordInput) return true;
		const r = rulesFor(passwordInput.value);
		return r.length && r.upper && r.lower && r.digit && r.symbol;
	}

	if (passwordInput) {
		passwordInput.addEventListener('input', function () {
			updatePasswordHelp();
			passwordInput.classList.toggle('is-invalid', passwordInput.value !== '' && !passwordMeetsAll());
		});
		updatePasswordHelp();
	}

	if (confirmInput && passwordInput) {
		confirmInput.addEventListener('input', function () {
			const mismatch = confirmInput.value !== '' && confirmInput.value !== passwordInput.value;
			confirmInput.classList.toggle('is-invalid', mismatch);
		});
	}

	if (form && passwordInput) {
		form.addEventListener('submit', function (e) {
			const okStrength = passwordMeetsAll();
			if (!okStrength) {
				e.preventDefault();
				passwordInput.classList.add('is-invalid');
				passwordInput.focus();
				updatePasswordHelp();
				return;
			}
			if (confirmInput && confirmInput.value !== passwordInput.value) {
				e.preventDefault();
				confirmInput.classList.add('is-invalid');
				confirmInput.focus();
			}
		});
	}
});
