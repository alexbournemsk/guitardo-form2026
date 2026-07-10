<?php
declare(strict_types=1);

require __DIR__ . '/config.php';
require __DIR__ . '/vendor/phpmailer/src/Exception.php';
require __DIR__ . '/vendor/phpmailer/src/PHPMailer.php';
require __DIR__ . '/vendor/phpmailer/src/SMTP.php';

use PHPMailer\PHPMailer\Exception as PHPMailerException;
use PHPMailer\PHPMailer\PHPMailer;

header('Content-Type: application/json; charset=utf-8');

function respond(bool $success, string $message = ''): void
{
    echo json_encode(['success' => $success, 'message' => $message], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    respond(false, 'Метод не поддерживается');
}

// honeypot: если скрытое поле заполнено — это бот, тихо отвечаем "успехом"
if (!empty($_POST['website'])) {
    respond(true);
}

$name = trim((string)($_POST['name'] ?? ''));
$phone = trim((string)($_POST['phone'] ?? ''));
$consent = (string)($_POST['consent'] ?? '');

if ($name === '' || mb_strlen($name) > 150) {
    respond(false, 'Укажите корректное имя и фамилию');
}

$phoneDigits = preg_replace('/\D/', '', $phone);
if (!$phoneDigits || strlen($phoneDigits) !== 11 || $phoneDigits[0] !== '7') {
    respond(false, 'Укажите корректный номер телефона');
}
$phoneFormatted = '+7 (' . substr($phoneDigits, 1, 3) . ') ' . substr($phoneDigits, 4, 3)
    . '-' . substr($phoneDigits, 7, 2) . '-' . substr($phoneDigits, 9, 2);

if ($consent !== 'on') {
    respond(false, 'Необходимо согласие с политикой обработки данных');
}

$name = strip_tags($name);

$subject = 'Заявка на пробный урок — ' . $name;

$body = "Новая заявка на пробный урок с сайта guitardo.ru/form2026\n\n"
    . "Имя Фамилия: {$name}\n"
    . "Телефон: {$phoneFormatted}\n"
    . 'Дата: ' . date('d.m.Y H:i') . "\n";

$mail = new PHPMailer(true);

try {
    $mail->isSMTP();
    $mail->Host       = SMTP_HOST;
    $mail->Port       = SMTP_PORT;
    $mail->SMTPSecure = SMTP_SECURE;
    $mail->SMTPAuth   = true;
    $mail->Username   = SMTP_USERNAME;
    $mail->Password   = SMTP_PASSWORD;
    $mail->CharSet    = 'UTF-8';

    $mail->setFrom(FROM_EMAIL, FROM_NAME);
    $mail->addAddress(TO_EMAIL);
    $mail->addReplyTo(FROM_EMAIL, FROM_NAME);

    $mail->Subject = $subject;
    $mail->Body    = $body;
    $mail->isHTML(false);

    $mail->send();
} catch (PHPMailerException $e) {
    error_log('Mail send failed: ' . $mail->ErrorInfo);
    respond(false, 'Не удалось отправить письмо. Попробуйте позже.');
}

respond(true);
