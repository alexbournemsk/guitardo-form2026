<?php
declare(strict_types=1);

/**
 * Диагностика подключения к amoCRM.
 * Откройте этот файл в браузере: https://guitardo.ru/form2026/amo_test.php
 *
 * Скрипт проверит токен и покажет список воронок с их ID и этапами —
 * скопируйте ID воронки "Первичные продажи (оффлайн)" в config.php
 * (константа AMO_PIPELINE_ID).
 *
 * ВАЖНО: после того как узнали ID и всё заработало — УДАЛИТЕ этот файл
 * с хостинга, чтобы он не был доступен посторонним.
 */

require __DIR__ . '/config.php';

header('Content-Type: text/plain; charset=utf-8');

if (strpos(AMO_BASE_DOMAIN, 'ВАШ_ПОДДОМЕН') !== false || strpos(AMO_ACCESS_TOKEN, 'ВСТАВЬТЕ') !== false) {
    echo "Сначала заполните AMO_BASE_DOMAIN и AMO_ACCESS_TOKEN в config.php.\n";
    exit;
}

$url = 'https://' . AMO_BASE_DOMAIN . '/api/v4/leads/pipelines';

$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER     => [
        'Authorization: Bearer ' . AMO_ACCESS_TOKEN,
    ],
    CURLOPT_TIMEOUT        => 20,
]);
$response = curl_exec($ch);
$httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlErr  = curl_error($ch);
curl_close($ch);

echo "Запрос: {$url}\n";
echo "HTTP: {$httpCode}\n\n";

if ($response === false) {
    echo "Ошибка соединения: {$curlErr}\n";
    exit;
}

if ($httpCode === 401) {
    echo "401 — неверный или просроченный токен. Проверьте AMO_ACCESS_TOKEN.\n";
    exit;
}
if ($httpCode !== 200) {
    echo "Ответ сервера:\n{$response}\n";
    exit;
}

$data = json_decode($response, true);
$pipelines = $data['_embedded']['pipelines'] ?? [];

if (!$pipelines) {
    echo "Воронки не найдены. Полный ответ:\n{$response}\n";
    exit;
}

echo "=== ВОРОНКИ (скопируйте нужный ID в AMO_PIPELINE_ID) ===\n\n";
foreach ($pipelines as $p) {
    echo "Воронка: {$p['name']}\n";
    echo "  ID = {$p['id']}\n";
    $statuses = $p['_embedded']['statuses'] ?? [];
    foreach ($statuses as $s) {
        echo "    этап: {$s['name']} (status_id={$s['id']})\n";
    }
    echo "\n";
}

echo "Токен рабочий. Впишите ID воронки \"Первичные продажи (оффлайн)\" в config.php\n";
echo "и удалите этот файл (amo_test.php) с хостинга.\n";
