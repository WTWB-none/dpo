document
  .getElementById("feedback-form")
  .addEventListener("submit", function (e) {
    e.preventDefault();

    // Очистка предыдущих ошибок
    const inputs = document.querySelectorAll("input, textarea");
    inputs.forEach((input) => input.classList.remove("error"));

    // Получение значений
    const fullName = document.getElementById("fullName").value.trim();
    const email = document.getElementById("email").value.trim();
    const phone = document.getElementById("phone").value.trim();
    const comment = document.getElementById("comment").value.trim();

    // Валидация
    let isValid = true;
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    const phoneRegex = /^\+?\d{10,12}$/;

    if (!fullName) {
      document.getElementById("fullName").classList.add("error");
      isValid = false;
    }
    if (!email || !emailRegex.test(email)) {
      document.getElementById("email").classList.add("error");
      isValid = false;
    }
    if (!phone || !phoneRegex.test(phone)) {
      document.getElementById("phone").classList.add("error");
      isValid = false;
    }
    if (!comment) {
      document.getElementById("comment").classList.add("error");
      isValid = false;
    }

    if (!isValid) return;

    // AJAX запрос
    const xhr = new XMLHttpRequest();
    xhr.open("POST", "process.php", true);
    xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");

    xhr.onload = function () {
      if (xhr.status === 200) {
        const response = JSON.parse(xhr.responseText);
        const container = document.getElementById("feedback-container");

        if (response.success) {
          const [surname, name, patronymic] = fullName.split(" ");
          const contactTime = new Date(response.time * 1000 + 5400000); // +1.5 часа в миллисекундах
          const formattedTime = contactTime.toLocaleString("ru-RU", {
            hour: "2-digit",
            minute: "2-digit",
            second: "2-digit",
            day: "2-digit",
            month: "2-digit",
            year: "numeric",
          });

          container.innerHTML = `
                    <div class="success-message">
                        Оставлено сообщение из формы обратной связи.<br>
                        Имя: ${name || ""}<br>
                        Фамилия: ${surname || ""}<br>
                        Отчество: ${patronymic || ""}<br>
                        E-mail: ${email}<br>
                        Телефон: ${phone}<br>
                        С Вами свяжутся после ${formattedTime}
                    </div>
                `;
        } else {
          container.innerHTML += `<div class="error-message">${response.message}</div>`;
        }
      }
    };

    const data = `fullName=${encodeURIComponent(fullName)}&email=${encodeURIComponent(email)}&phone=${encodeURIComponent(phone)}&comment=${encodeURIComponent(comment)}`;
    xhr.send(data);
  });
