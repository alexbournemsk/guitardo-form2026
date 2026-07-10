(function () {
  var form = document.getElementById('trial-form');
  var phoneInput = document.getElementById('phone');
  var nameInput = document.getElementById('name');
  var consentInput = document.getElementById('consent');
  var submitBtn = document.getElementById('submit-btn');
  var statusEl = document.getElementById('form-status');

  function formatPhone(value) {
    var digits = value.replace(/\D/g, '');

    if (digits.startsWith('8')) {
      digits = '7' + digits.slice(1);
    }
    if (!digits.startsWith('7')) {
      digits = '7' + digits;
    }
    digits = digits.slice(0, 11);

    var rest = digits.slice(1);
    var result = '+7';
    if (rest.length > 0) result += ' (' + rest.slice(0, 3);
    if (rest.length >= 3) result += ')';
    if (rest.length > 3) result += ' ' + rest.slice(3, 6);
    if (rest.length > 6) result += '-' + rest.slice(6, 8);
    if (rest.length > 8) result += '-' + rest.slice(8, 10);
    return result;
  }

  phoneInput.addEventListener('focus', function () {
    if (!phoneInput.value) phoneInput.value = '+7 (';
  });

  phoneInput.addEventListener('input', function () {
    phoneInput.value = formatPhone(phoneInput.value);
  });

  function isPhoneValid(value) {
    var digits = value.replace(/\D/g, '');
    return digits.length === 11;
  }

  function setError(field, message) {
    var el = form.querySelector('[data-error-for="' + field + '"]');
    if (el) el.textContent = message || '';
  }

  function validate() {
    var valid = true;

    if (!nameInput.value.trim()) {
      setError('name', 'Укажите имя и фамилию');
      valid = false;
    } else {
      setError('name', '');
    }

    if (!isPhoneValid(phoneInput.value)) {
      setError('phone', 'Укажите корректный номер телефона');
      valid = false;
    } else {
      setError('phone', '');
    }

    if (!consentInput.checked) {
      setError('consent', 'Необходимо ваше согласие');
      valid = false;
    } else {
      setError('consent', '');
    }

    return valid;
  }

  form.addEventListener('submit', function (e) {
    e.preventDefault();
    statusEl.textContent = '';
    statusEl.className = 'form-status';

    if (!validate()) return;

    submitBtn.disabled = true;
    submitBtn.textContent = 'Отправляем...';

    var formData = new FormData(form);

    fetch('send.php', {
      method: 'POST',
      body: formData,
      headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
      .then(function (res) { return res.json(); })
      .then(function (data) {
        if (data.success) {
          statusEl.textContent = 'Спасибо! Заявка отправлена, мы скоро свяжемся с вами.';
          statusEl.className = 'form-status success';
          form.reset();
        } else {
          statusEl.textContent = data.message || 'Не удалось отправить заявку. Попробуйте позже.';
          statusEl.className = 'form-status error';
        }
      })
      .catch(function () {
        statusEl.textContent = 'Ошибка соединения. Попробуйте позже.';
        statusEl.className = 'form-status error';
      })
      .finally(function () {
        submitBtn.disabled = false;
        submitBtn.textContent = 'Записаться на пробный урок';
      });
  });
})();
