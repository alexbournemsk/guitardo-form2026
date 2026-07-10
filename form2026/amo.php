<?php
declare(strict_types=1);

/**
 * Отправка заявки в amoCRM в раздел "Неразобранное" указанной воронки.
 * Использует API v4, endpoint /api/v4/leads/unsorted/forms.
 *
 * Возвращает массив: ['ok' => bool, 'http' => int, 'error' => string].
 */
function sendToAmo(string $name, string $phoneFormatted, string $amoLog): array
{
    $logAmo = static function (string $line) use ($amoLog): void {
        $ts = date('Y-m-d H:i:s');
        @file_put_contents($amoLog, "[{$ts}] {$line}" . PHP_EOL, FILE_APPEND | LOCK_EX);
    };

    if (!AMO_ENABLED) {
        return ['ok' => true, 'http' => 0, 'error' => 'amo disabled'];
    }

    // Простые проверки конфигурации, чтобы сразу увидеть "не заполнено"
    if (
        AMO_BASE_DOMAIN === '' || strpos(AMO_BASE_DOMAIN, 'ВАШ_ПОДДОМЕН') !== false
        || AMO_ACCESS_TOKEN === '' || strpos(AMO_ACCESS_TOKEN, 'ВСТАВЬТЕ') !== false
        || (int) AMO_PIPELINE_ID <= 0
    ) {
        $logAmo('CONFIG NOT SET: заполните AMO_BASE_DOMAIN, AMO_ACCESS_TOKEN, AMO_PIPELINE_ID в config.php');
        return ['ok' => false, 'http' => 0, 'error' => 'amoCRM не настроен (домен/токен/ID воронки)'];
    }

    $now = time();

    $payload = [[
        'source_name' => AMO_SOURCE_NAME,
        'source_uid'  => 'form2026-' . uniqid('', true),
        'pipeline_id' => (int) AMO_PIPELINE_ID,
        'created_at'  => $now,
        'metadata'    => [
            'form_id'      => 'trial-form-2026',
            'form_name'    => 'Запись на пробный урок',
            'form_page'    => 'https://guitardo.ru/form2026',
            'form_sent_at' => $now,
            'ip'           => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
            'referer'      => $_SERVER['HTTP_REFERER'] ?? 'https://guitardo.ru/form2026',
        ],
        '_embedded'   => [
            'contacts' => [[
                'name' => $name,
                'custom_fields_values' => [[
                    'field_code' => 'PHONE',
                    'values'     => [[
                        'value'     => $phoneFormatted,
                        'enum_code' => 'WORK',
                    ]],
                ]],
            ]],
            'leads' => [[
                'name' => 'Заявка на пробный урок — ' . $name,
            ]],
        ],
    ]];

    $url = 'https://' . AMO_BASE_DOMAIN . '/api/v4/leads/unsorted/forms';
    $json = json_encode($payload, JSON_UNESCAPED_UNICODE);

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $json,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . AMO_ACCESS_TOKEN,
        ],
        CURLOPT_TIMEOUT        => 20,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);

    $response = curl_exec($ch);
    $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr  = curl_error($ch);
    curl_close($ch);

    if ($response === false) {
        $logAmo('CURL ERROR: ' . $curlErr);
        return ['ok' => false, 'http' => 0, 'error' => 'Ошибка соединения с amoCRM: ' . $curlErr];
    }

    // amoCRM отвечает 200 при успешном создании неразобранной заявки
    if ($httpCode >= 200 && $httpCode < 300) {
        $logAmo("OK (HTTP {$httpCode}) name=\"{$name}\" phone={$phoneFormatted}");
        return ['ok' => true, 'http' => $httpCode, 'error' => ''];
    }

    $logAmo("FAILED HTTP {$httpCode}: " . $response);

    $hint = '';
    if ($httpCode === 401) {
        $hint = ' (неверный или просроченный токен)';
    } elseif ($httpCode === 400) {
        $hint = ' (проверьте ID воронки и структуру данных)';
    } elseif ($httpCode === 403) {
        $hint = ' (нет прав у интеграции)';
    }

    return ['ok' => false, 'http' => $httpCode, 'error' => 'amoCRM вернул HTTP ' . $httpCode . $hint];
}
