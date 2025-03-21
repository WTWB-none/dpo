<?php
header('Content-Type: application/json');

// Подключение к БД (настройте под свои параметры)
$db = new PDO('mysql:host=localhost;dbname=feedback_db', 'username', 'password');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Получение данных
$fullName = $_POST['fullName'] ?? '';
$email = $_POST['email'] ?? '';
$phone = $_POST['phone'] ?? '';
$comment = $_POST['comment'] ?? '';
$time = time();

// Проверка на повторную заявку
$stmt = $db->prepare("SELECT created_at FROM feedback WHERE email = ? ORDER BY created_at DESC LIMIT 1");
$stmt->execute([$email]);
$lastRequest = $stmt->fetchColumn();

if ($lastRequest && ($time - $lastRequest) < 3600) {
    $remainingTime = 3600 - ($time - $lastRequest);
    $remainingMinutes = ceil($remainingTime / 60);
    echo json_encode([
        'success' => false,
        'message' => "Повторная заявка возможна через $remainingMinutes минут"
    ]);
    exit;
}

// Сохранение в БД
$stmt = $db->prepare("INSERT INTO feedback (full_name, email, phone, comment, created_at) VALUES (?, ?, ?, ?, ?)");
$stmt->execute([$fullName, $email, $phone, $comment, $time]);

// Отправка письма
$managerEmail = 'manager@example.com';
$subject = 'Новая заявка с формы обратной связи';
$message = "ФИО: $fullName\nEmail: $email\nТелефон: $phone\nКомментарий: $comment\nВремя: " . date('d.m.Y H:i:s', $time);
$headers = 'From: noreply@yourdomain.com' . "\r\n" .
           'Content-type: text/plain; charset=utf-8' . "\r\n";

mail($managerEmail, $subject, $message, $headers);

echo json_encode([
    'success' => true,
    'time' => $time
]);

// Создание таблицы в БД (выполнить один раз):
/*
CREATE TABLE feedback (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    phone VARCHAR(50) NOT NULL,
    comment TEXT NOT NULL,
    created_at INT NOT NULL
);
*/
?>
