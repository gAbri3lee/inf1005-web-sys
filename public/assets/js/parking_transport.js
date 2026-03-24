document.addEventListener('DOMContentLoaded', function () {
  const tabButtons = document.querySelectorAll('.transport-tab-btn');
  const tabPanels = document.querySelectorAll('.transport-tab-panel');
  const copyAddressBtn = document.getElementById('copyAddressBtn');
  const hotelAddress = document.getElementById('hotelAddress');
  const copyStatus = document.getElementById('copyStatus');
  const toggleButtons = document.querySelectorAll('.toggle-extra-btn');

  tabButtons.forEach(function (button) {
    button.addEventListener('click', function () {
      const targetId = this.getAttribute('data-target');

      tabButtons.forEach(function (btn) {
        btn.classList.remove('active');
      });

      tabPanels.forEach(function (panel) {
        panel.classList.remove('active');
      });

      this.classList.add('active');

      const targetPanel = document.getElementById(targetId);
      if (targetPanel) {
        targetPanel.classList.add('active');
      }
    });
  });

  if (copyAddressBtn && hotelAddress) {
    copyAddressBtn.addEventListener('click', function () {
      const textToCopy = hotelAddress.textContent.trim();

      navigator.clipboard.writeText(textToCopy).then(function () {
        if (copyStatus) {
          copyStatus.textContent = 'Address copied.';
        }
        copyAddressBtn.textContent = 'Copied';

        setTimeout(function () {
          copyAddressBtn.textContent = 'Copy Address';
          if (copyStatus) {
            copyStatus.textContent = '';
          }
        }, 1800);
      }).catch(function () {
        if (copyStatus) {
          copyStatus.textContent = 'Copy failed. Please copy manually.';
        }
      });
    });
  }

  toggleButtons.forEach(function (button) {
    button.addEventListener('click', function () {
      const extraId = this.getAttribute('data-extra');
      const extraContent = document.getElementById(extraId);

      if (!extraContent) return;

      extraContent.classList.toggle('show');

      if (extraContent.classList.contains('show')) {
        this.textContent = 'Show Less';
      } else {
        this.textContent = 'Show More';
      }
    });
  });
});