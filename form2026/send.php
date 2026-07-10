<?php
declare(strict_types=1);

require __DIR__ . '/config.php';
require __DIR__ . '/vendor/phpmailer/src/Exception.php';
require __DIR__ . '/vendor/phpmailer/src/PHPMailer.php';
require __DIR__ . '/vendor/phpmailer/src/SMTP.php';

use PHPMailer\PHPMailer\Exception as PHPMailerException;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;

header('Content-Type: application/json; charset=utf-8');

$logFile = __DIR__ . '/logs/mail.log';

function logLine(string $logFile, string $line): void
{
    $ts = date('Y-m-d H:i:s');
    @file_put_contents($logFile, "[{$ts}] {$line}" . PHP_EOL, FILE_APPEND | LOCK_EX);
}

function respond(bool $success, string $message = ''): void
{
    $payload = ['success' => $success, 'message' => $message];
    if (defined('DEBUG_MODE') && DEBUG_MODE && $message !== '') {
        $payload['debug'] = $message;
    }
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

// ловим фатальные ошибки (например, если забыли залить папку vendor/),
// чтобы вместо белого экрана вернуть понятный JSON и запись в лог
register_shutdown_function(function () use ($logFile) {
    $error = error_get_last();
    if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
        logLine($logFile, 'FATAL: ' . $error['message'] . ' in ' . $error['file'] . ':' . $error['line']);
        if (!headers_sent()) {
            header('Content-Type: application/json; charset=utf-8');
        }
        echo json_encode([
            'success' => false,
            'message' => 'Внутренняя ошибка сервера. Подробности в logs/mail.log',
        ], JSON_UNESCAPED_UNICODE);
    }
});

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    respond(false, 'Метод не поддерживается');
}

// honeypot: если скрытое поле заполнено — это бот, тихо отвечаем "успехом"
if (!empty($_POST['website'])) {
    logLine($logFile, 'Honeypot triggered, ignoring submission');
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

logLine($logFile, "Incoming submission: name=\"{$name}\" phone={$phoneFormatted}");

$subject = 'Заявка на пробный урок — ' . $name;

$body = "Новая заявка на пробный урок с сайта guitardo.ru/form2026\n\n"
    . "Имя Фамилия: {$name}\n"
    . "Телефон: {$phoneFormatted}\n"
    . 'Дата: ' . date('d.m.Y H:i') . "\n";

$mail = new PHPMailer(true);
$smtpTranscript = '';

try {
    $mail->isSMTP();
    $mail->Host       = SMTP_HOST;
    $mail->Port       = SMTP_PORT;
    $mail->SMTPSecure = SMTP_SECURE;
    $mail->SMTPAuth   = true;
    $mail->Username   = SMTP_USERNAME;
    $mail->Password   = SMTP_PASSWORD;
    $mail->CharSet    = 'UTF-8';

    // подробный протокол диалога с SMTP-сервером — уходит в лог, не в ответ пользователю
    $mail->SMTPDebug = SMTP::DEBUG_CONNECTION;
    $mail->Debugoutput = function (string $str, int $level) use (&$smtpTranscript) {
        $smtpTranscript .= trim($str) . "\n";
    };

    $mail->setFrom(FROM_EMAIL, FROM_NAME);
    $mail->addAddress(TO_EMAIL);
    $mail->addReplyTo(FROM_EMAIL, FROM_NAME);

    $mail->Subject = $subject;
    $mail->Body    = $body;
    $mail->isHTML(false);

    $mail->send();

    logLine($logFile, 'SMTP transcript:' . PHP_EOL . $smtpTranscript);
    logLine($logFile, 'Mail sent OK to ' . TO_EMAIL);
} catch (PHPMailerException $e) {
    logLine($logFile, 'SMTP transcript:' . PHP_EOL . $smtpTranscript);
    logLine($logFile, 'Mail send FAILED: ' . $mail->ErrorInfo . ' | exception: ' . $e->getMessage());
    respond(false, 'Не удалось отправить письмо. Попробуйте позже.' . (defined('DEBUG_MODE') && DEBUG_MODE ? ' [' . $mail->ErrorInfo . ']' : ''));
} catch (Throwable $e) {
    logLine($logFile, 'Unexpected error: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
    respond(false, 'Внутренняя ошибка сервера.' . (defined('DEBUG_MODE') && DEBUG_MODE ? ' [' . $e->getMessage() . ']' : ''));
}

respond(true);
