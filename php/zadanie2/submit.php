<?php
require 'vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();
$host = $_SERVER["DB_HOST"];
$dbname = $_SERVER["DB_NAME"];
$user = $_SERVER["DB_USER"];
$password = $_SERVER["DB_PASS"];

// Проверка текущего времени на сервере
$dt = new DateTime('now');
echo "Серверное время: " . $dt->format("H:i:s d.m.Y") . "<br>";

try {
    // Создание соединения с базой данных PostgreSQL через PDO
    $conn = new PDO("pgsql:host=$host;dbname=$dbname", $user, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Установка часового пояса Москвы
    $moscow_timezone = new DateTimeZone('Europe/Moscow');
    $dt = new DateTime('now', $moscow_timezone);
    $dt->modify('+1 hour +30 minutes');
    $response_time = $dt->format("H:i:s d.m.Y");

    // Получение данных из формы
    $fio = $_POST['fio'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $comment = $_POST['comment'];


    // Автоматическое форматирование номера телефона для России
    $phone = preg_replace('/\D/', '', $phone); // Удаляем все нецифровые символы
    if (strlen($phone) == 11 && $phone[0] == '8') {
        $phone = "+7" . substr($phone, 1);
    } elseif (strlen($phone) == 11) {
        $phone = "+" . $phone;
    } else {
        echo "Некорректный формат номера телефона.";
        exit;
    }

    // Форматирование комментария: удаление лишних пробелов и специальных символов
    $comment = trim($comment); // Удаляем пробелы в начале и конце
    $comment = preg_replace('/\s+/', ' ', $comment); // Удаляем лишние пробелы
    $comment = htmlspecialchars($comment, ENT_QUOTES, 'UTF-8'); // Экранируем специальные символы

    // Проверка на повторную заявку за последний час
    $sql = "SELECT * FROM feedback WHERE email = :email AND created_at >= NOW() - INTERVAL '1 hour'";
    $stmt = $conn->prepare($sql);
    $stmt->execute(['email' => $email]);

    if ($stmt->rowCount() > 0) {
        echo "Повторная заявка возможна через час.";
    } else {
        // Вставка данных в базу
        $sql = "INSERT INTO feedback (fio, email, phone, comment, created_at) VALUES (:fio, :email, :phone, :comment, :created_at)";
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            'fio' => $fio,
            'email' => $email,
            'phone' => $phone,
            'comment' => $comment,
            'created_at' => (new DateTime('now', $moscow_timezone))->format("Y-m-d H:i:s")
        ]);

        if ($stmt->rowCount() > 0) {
            // Подключение библиотеки PHPMailer
            require 'vendor/autoload.php';
            $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
            $dotenv->load();
            $MAIL = $_SERVER["MAIL"];
            $KEY = $_SERVER["KEY"];
            $mail = new PHPMailer\PHPMailer\PHPMailer();
            $mail->isSMTP();
            $mail->Host = 'smtp.mail.ru';
            $mail->SMTPAuth = true;
            $mail->Username = $MAIL; // Ваш email
            $mail->Password = $KEY; // Ваш пароль
            $mail->SMTPSecure = 'ssl';
            $mail->Port = 465;
            $mail->CharSet = 'UTF-8';

            $mail->setFrom($MAIL, 'Форма обратной связи');
            $mail->addAddress($email);

            $mail->isHTML(true);
            $mail->Subject = 'Новая заявка';
            $mail->Body = "Имя: $fio<br>E-mail: $email<br>Телефон: $phone<br>Комментарий: $comment";

            if ($mail->send()) {
                echo "Сообщение отправлено.<br>Имя: $fio<br>E-mail: $email<br>Телефон: $phone<br>С Вами свяжутся после $response_time";
            } else {
                echo "Ошибка при отправке письма: " . $mail->ErrorInfo;
            }
        } else {
            echo "Ошибка при добавлении записи в базу данных.";
        }
    }
} catch (PDOException $e) {
    echo "Ошибка подключения к базе данных: " . $e->getMessage();
}

// Закрытие соединения с базой данных
$conn = null;
?>