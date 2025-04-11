<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Геокодер Яндекс.Карт</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>
    <div class="container">
        <h1>Геокодер Яндекс.Карт</h1>
        <form id="geocodeForm">
            <input type="text" id="address" placeholder="Введите адрес" required>
            <button type="submit">Найти</button>
        </form>
        <div id="result" class="hidden">
            <h2>Результат</h2>
            <p><strong>Адрес:</strong> <span id="fullAddress"></span></p>
            <p><strong>Компоненты адреса:</strong></p>
            <ul id="components"></ul>
            <p><strong>Координаты:</strong> 🌍 <span id="coordinates"></span></p>
            <p><strong>Ближайшее метро:</strong> 🚇 <span id="metro"></span></p>
        </div>
        <div id="error" class="hidden"></div>
    </div>
    <script src="script.js"></script>
</body>

</html>