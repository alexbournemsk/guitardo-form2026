<?php
declare(strict_types=1);

// ---- Настройки -------------------------------------------------
const TO_EMAIL   = 'ag@guitardo.ru';
const FROM_EMAIL = 'noreply@guitardo.ru'; // должен быть в домене guitardo.ru
const FROM_NAME  = 'Guitardo — форма записи';
// ------------------------------------------------------------------

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

$subject = '=?UTF-8?B?' . base64_encode('Заявка на пробный урок — ' . $name) . '?=';

$body = "Новая заявка на пробный урок с сайта guitardo.ru/form2026\n\n"
    . "Имя Фамилия: {$name}\n"
    . "Телефон: {$phoneFormatted}\n"
    . 'Дата: ' . date('d.m.Y H:i') . "\n";

$fromNameEncoded = '=?UTF-8?B?' . base64_encode(FROM_NAME) . '?=';

$headers = [];
$headers[] = 'From: ' . $fromNameEncoded . ' <' . FROM_EMAIL . '>';
$headers[] = 'Reply-To: ' . FROM_EMAIL;
$headers[] = 'Content-Type: text/plain; charset=UTF-8';
$headers[] = 'X-Mailer: PHP/' . phpversion();

$sent = mail(TO_EMAIL, $subject, $body, implode("\r\n", $headers));

if (!$sent) {
    respond(false, 'Не удалось отправить письмо. Попробуйте позже.');
}

respond(true);
