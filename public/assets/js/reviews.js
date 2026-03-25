(function () {
	const STORAGE_KEY_Y = 'reviews_scroll_y';
	const STORAGE_KEY_RESTORE = 'reviews_scroll_restore';

	function shouldHandleClick(event, anchor) {
		if (!anchor || anchor.tagName !== 'A') return false;
		if (!anchor.classList.contains('js-preserve-scroll')) return false;
		if (event.defaultPrevented) return false;
		if (event.button !== 0) return false;
		if (event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return false;
		if (anchor.target && anchor.target.toLowerCase() !== '') return false;
		if (anchor.hasAttribute('download')) return false;
		return true;
	}

	document.addEventListener('click', function (event) {
		const anchor = event.target && event.target.closest ? event.target.closest('a') : null;
		if (!shouldHandleClick(event, anchor)) return;

		try {
			sessionStorage.setItem(STORAGE_KEY_Y, String(window.scrollY || 0));
			sessionStorage.setItem(STORAGE_KEY_RESTORE, '1');
		} catch (_) {
			// ignore storage failures
		}
	});

	function restoreScrollIfNeeded() {
		let restore = null;
		let y = null;
		try {
			restore = sessionStorage.getItem(STORAGE_KEY_RESTORE);
			y = sessionStorage.getItem(STORAGE_KEY_Y);
		} catch (_) {
			return;
		}

		if (restore !== '1' || y === null) return;

		const targetY = Math.max(0, parseInt(y, 10) || 0);

		try {
			sessionStorage.removeItem(STORAGE_KEY_RESTORE);
		} catch (_) {
			// ignore
		}

		const doScroll = function () {
			window.scrollTo(0, targetY);
		};

		// Restore ASAP, then again after load to account for images/layout.
		requestAnimationFrame(function () {
			doScroll();
			requestAnimationFrame(doScroll);
		});

		window.addEventListener('load', function () {
			doScroll();
		});
	}

	restoreScrollIfNeeded();
})();
