(() => {
  'use strict';

  const adjustForms = Array.from(document.querySelectorAll('.js-dashboard-adjust-form'));
  const modalElement = document.getElementById('roomAdjustConfirmModal');
  const modalCopy = document.querySelector('.js-adjust-modal-copy');
  const modalSummary = document.querySelector('.js-adjust-modal-summary');
  const paymentBlock = document.querySelector('.js-adjust-modal-payment');
  const cardNameInput = document.querySelector('.js-adjust-card-name');
  const cardNumberInput = document.querySelector('.js-adjust-card-number');
  const expiryInput = document.querySelector('.js-adjust-expiry');
  const cvvInput = document.querySelector('.js-adjust-cvv');
  const confirmButton = document.querySelector('.js-adjust-modal-confirm');
  const closeButtons = Array.from(document.querySelectorAll('[data-adjust-modal-close]'));
  const supportsBootstrapModal = Boolean(window.bootstrap && window.bootstrap.Modal);

  if (!modalElement || adjustForms.length === 0) {
    return;
  }

  const confirmModal = supportsBootstrapModal ? new window.bootstrap.Modal(modalElement) : null;
  let pendingForm = null;

  function parseDate(value) {
    const raw = String(value || '').trim();
    if (!/^\d{4}-\d{2}-\d{2}$/.test(raw)) {
      return null;
    }

    const date = new Date(`${raw}T00:00:00`);
    return Number.isNaN(date.getTime()) ? null : date;
  }

  function formatCurrency(value) {
    return `$${Number(value || 0).toFixed(2)}`;
  }

  function formatDate(value) {
    const date = parseDate(value);
    if (!date) {
      return value;
    }

    return new Intl.DateTimeFormat('en-US', {
      year: 'numeric',
      month: 'short',
      day: 'numeric',
    }).format(date);
  }

  function digitsOnly(value) {
    return String(value || '').replace(/\D+/g, '');
  }

  function formatCardNumber(value) {
    const digits = digitsOnly(value).slice(0, 16);
    return digits.replace(/(.{4})/g, '$1 ').trim();
  }

  function formatExpiry(value) {
    const digits = digitsOnly(value).slice(0, 4);
    if (digits.length <= 2) {
      return digits;
    }
    return `${digits.slice(0, 2)}/${digits.slice(2)}`;
  }

  function expiryValid(value) {
    const match = String(value || '').match(/^(0[1-9]|1[0-2])\/(\d{2})$/);
    if (!match) {
      return false;
    }

    const month = Number(match[1]);
    const year = 2000 + Number(match[2]);
    const end = new Date(year, month, 0, 23, 59, 59, 999);
    return end.getTime() >= Date.now();
  }

  function setInvalid(input, isInvalid) {
    if (!input) {
      return;
    }

    input.classList.toggle('is-invalid', isInvalid);
    input.classList.toggle('is-valid', !isInvalid && input.value !== '');
  }

  function clearPaymentInputs() {
    [cardNameInput, cardNumberInput, expiryInput, cvvInput].forEach((input) => {
      if (!input) {
        return;
      }
      input.value = '';
      input.classList.remove('is-invalid', 'is-valid');
    });
  }

  function resetPendingState() {
    pendingForm = null;
    modalElement.dataset.requiresPayment = 'false';
    clearPaymentInputs();
  }

  function focusPrimaryField() {
    if (modalElement.dataset.requiresPayment === 'true' && cardNameInput) {
      cardNameInput.focus();
      return;
    }

    if (confirmButton) {
      confirmButton.focus();
    }
  }

  function openModal() {
    if (supportsBootstrapModal && confirmModal) {
      confirmModal.show();
      return;
    }

    modalElement.classList.add('dashboard-modal-fallback-visible');
    modalElement.style.display = 'block';
    modalElement.removeAttribute('aria-hidden');
    modalElement.setAttribute('aria-modal', 'true');
    document.body.classList.add('dashboard-modal-open');
    window.setTimeout(focusPrimaryField, 30);
  }

  function closeModal() {
    if (supportsBootstrapModal && confirmModal) {
      confirmModal.hide();
      return;
    }

    modalElement.classList.remove('dashboard-modal-fallback-visible');
    modalElement.style.display = 'none';
    modalElement.setAttribute('aria-hidden', 'true');
    modalElement.removeAttribute('aria-modal');
    document.body.classList.remove('dashboard-modal-open');
    resetPendingState();
  }

  function syncHiddenPaymentFields(form, values) {
    const fieldNames = ['card_name', 'card_number', 'expiry', 'cvv'];
    fieldNames.forEach((fieldName) => {
      const hiddenInput = form.querySelector(`input[name="${fieldName}"]`);
      if (!hiddenInput) {
        return;
      }

      hiddenInput.value = values[fieldName] || '';
    });
  }

  function validatePaymentInputs() {
    const cardName = cardNameInput ? cardNameInput.value.trim() : '';
    const cardDigits = cardNumberInput ? digitsOnly(cardNumberInput.value) : '';
    const expiry = expiryInput ? expiryInput.value.trim() : '';
    const cvvDigits = cvvInput ? digitsOnly(cvvInput.value) : '';

    const invalidName = cardName === '';
    const invalidCard = cardDigits.length !== 16;
    const invalidExpiry = !expiryValid(expiry);
    const invalidCvv = cvvDigits.length !== 3;

    setInvalid(cardNameInput, invalidName);
    setInvalid(cardNumberInput, invalidCard);
    setInvalid(expiryInput, invalidExpiry);
    setInvalid(cvvInput, invalidCvv);

    if (invalidName || invalidCard || invalidExpiry || invalidCvv) {
      const firstInvalid = [cardNameInput, cardNumberInput, expiryInput, cvvInput].find((input) => input && input.classList.contains('is-invalid'));
      if (firstInvalid && typeof firstInvalid.focus === 'function') {
        firstInvalid.focus();
      }
      return null;
    }

    return {
      card_name: cardName,
      card_number: cardDigits,
      expiry,
      cvv: cvvDigits,
    };
  }

  function nightsBetween(checkIn, checkOut) {
    const start = parseDate(checkIn);
    const end = parseDate(checkOut);
    if (!start || !end) {
      return 0;
    }

    return Math.round((end.getTime() - start.getTime()) / 86400000);
  }

  function buildSummaryHtml({ originalCheckIn, originalCheckOut, newCheckIn, newCheckOut, oldTotal, newTotal, roomRate, newNights }) {
    return `
      <div class="dashboard-modal-summary-grid">
        <div>
          <span class="dashboard-entry-label">Current stay</span>
          <strong>${formatDate(originalCheckIn)} to ${formatDate(originalCheckOut)}</strong>
        </div>
        <div>
          <span class="dashboard-entry-label">Updated stay</span>
          <strong>${formatDate(newCheckIn)} to ${formatDate(newCheckOut)}</strong>
        </div>
        <div>
          <span class="dashboard-entry-label">Rate / night</span>
          <strong>${formatCurrency(roomRate)}</strong>
        </div>
        <div>
          <span class="dashboard-entry-label">Updated nights</span>
          <strong>${newNights}</strong>
        </div>
        <div>
          <span class="dashboard-entry-label">Current total</span>
          <strong>${formatCurrency(oldTotal)}</strong>
        </div>
        <div>
          <span class="dashboard-entry-label">Updated total</span>
          <strong>${formatCurrency(newTotal)}</strong>
        </div>
      </div>
    `;
  }

  adjustForms.forEach((form) => {
    form.addEventListener('submit', (event) => {
      const roomName = form.dataset.roomName || 'your stay';
      const originalCheckIn = form.dataset.originalCheckIn || '';
      const originalCheckOut = form.dataset.originalCheckOut || '';
      const oldTotal = Number.parseFloat(form.dataset.originalTotal || '0') || 0;
      const roomRate = Number.parseFloat(form.dataset.roomRate || '0') || 0;
      const checkInInput = form.querySelector('input[name="check_in"]');
      const checkOutInput = form.querySelector('input[name="check_out"]');
      const newCheckIn = checkInInput ? checkInInput.value : '';
      const newCheckOut = checkOutInput ? checkOutInput.value : '';
      const newNights = nightsBetween(newCheckIn, newCheckOut);

      if (!newCheckIn || !newCheckOut || newNights <= 0) {
        return;
      }

      event.preventDefault();

      const newTotal = roomRate * newNights;
      const priceDifference = newTotal - oldTotal;
      const requiresPayment = priceDifference > 0.009;
      let copy = `You are about to update ${roomName}.`;

      if (requiresPayment) {
        copy = `This change extends your stay. Enter card details below to pay the additional ${formatCurrency(priceDifference)} before confirming.`;
      } else if (priceDifference < -0.009) {
        copy = `This change shortens your stay. A refund of ${formatCurrency(Math.abs(priceDifference))} will be returned to your card within 7 business days after confirmation.`;
      } else {
        copy = 'This updates your booking details without changing the total price.';
      }

      pendingForm = form;
      if (modalCopy) {
        modalCopy.textContent = copy;
      }
      if (modalSummary) {
        modalSummary.innerHTML = buildSummaryHtml({
          originalCheckIn,
          originalCheckOut,
          newCheckIn,
          newCheckOut,
          oldTotal,
          newTotal,
          roomRate,
          newNights,
        });
      }
      if (paymentBlock) {
        paymentBlock.classList.toggle('d-none', !requiresPayment);
      }
      clearPaymentInputs();
      syncHiddenPaymentFields(form, {
        card_name: '',
        card_number: '',
        expiry: '',
        cvv: '',
      });
      modalElement.dataset.requiresPayment = requiresPayment ? 'true' : 'false';

      openModal();
    });
  });

  if (cardNumberInput) {
    cardNumberInput.addEventListener('input', () => {
      const formatted = formatCardNumber(cardNumberInput.value);
      if (cardNumberInput.value !== formatted) {
        cardNumberInput.value = formatted;
      }
      setInvalid(cardNumberInput, digitsOnly(cardNumberInput.value).length > 0 && digitsOnly(cardNumberInput.value).length !== 16);
    });
  }

  if (expiryInput) {
    expiryInput.addEventListener('input', () => {
      const formatted = formatExpiry(expiryInput.value);
      if (expiryInput.value !== formatted) {
        expiryInput.value = formatted;
      }
      setInvalid(expiryInput, expiryInput.value !== '' && !expiryValid(expiryInput.value));
    });
  }

  if (cvvInput) {
    cvvInput.addEventListener('input', () => {
      const digits = digitsOnly(cvvInput.value).slice(0, 3);
      if (cvvInput.value !== digits) {
        cvvInput.value = digits;
      }
      setInvalid(cvvInput, digits.length > 0 && digits.length !== 3);
    });
  }

  if (confirmButton) {
    confirmButton.addEventListener('click', () => {
      if (!pendingForm) {
        closeModal();
        return;
      }

      if (modalElement.dataset.requiresPayment === 'true') {
        const paymentValues = validatePaymentInputs();
        if (!paymentValues) {
          return;
        }
        syncHiddenPaymentFields(pendingForm, paymentValues);
      } else {
        syncHiddenPaymentFields(pendingForm, {
          card_name: '',
          card_number: '',
          expiry: '',
          cvv: '',
        });
      }

      const formToSubmit = pendingForm;
      if (!supportsBootstrapModal) {
        modalElement.classList.remove('dashboard-modal-fallback-visible');
        modalElement.style.display = 'none';
        modalElement.setAttribute('aria-hidden', 'true');
        modalElement.removeAttribute('aria-modal');
        document.body.classList.remove('dashboard-modal-open');
      }
      resetPendingState();
      formToSubmit.submit();
    });
  }

  closeButtons.forEach((button) => {
    button.addEventListener('click', () => {
      closeModal();
    });
  });

  modalElement.addEventListener('click', (event) => {
    if (!supportsBootstrapModal && event.target === modalElement) {
      closeModal();
    }
  });

  document.addEventListener('keydown', (event) => {
    if (event.key !== 'Escape') {
      return;
    }

    const isFallbackVisible = modalElement.classList.contains('dashboard-modal-fallback-visible');
    if (!supportsBootstrapModal && isFallbackVisible) {
      closeModal();
    }
  });

  if (supportsBootstrapModal) {
    modalElement.addEventListener('shown.bs.modal', focusPrimaryField);
    modalElement.addEventListener('hidden.bs.modal', () => {
      resetPendingState();
    });
  }
})();

(() => {
  'use strict';

  const phoneInput = document.getElementById('account_phone');
  if (!phoneInput) {
    return;
  }

  function setPhoneValidityMessage() {
    const value = String(phoneInput.value || '').trim();

    if (value === '' || /^\d+$/.test(value)) {
      phoneInput.setCustomValidity('');
      return;
    }

    phoneInput.setCustomValidity('Numbers only');
  }

  phoneInput.addEventListener('input', () => {
    setPhoneValidityMessage();
  });

  phoneInput.addEventListener('invalid', () => {
    setPhoneValidityMessage();
  });
})();
