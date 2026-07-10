<?php
declare(strict_types=1);

// ---- Куда отправлять заявки -------------------------------------
const TO_EMAIL = 'ag@guitardo.ru';

// ---- SMTP-настройки (почта на Яндекс.360) ------------------------
// SMTP_PASSWORD — это НЕ обычный пароль от почты, а "пароль приложения":
// id.yandex.ru -> войти под ящиком SMTP_USERNAME -> Безопасность ->
// Пароли приложений -> создать пароль типа "Почта".
// Если письма не уходят — проверьте в admin.yandex.ru (панель организации),
// что для ящика разрешён доступ по IMAP/SMTP.
const SMTP_HOST       = 'smtp.yandex.ru';
const SMTP_PORT       = 465;
const SMTP_SECURE     = 'ssl'; // 'ssl' для порта 465, 'tls' для порта 587
const SMTP_USERNAME   = 'noreply@guitardo.ru'; // логин ящика, от имени которого отправляем
const SMTP_PASSWORD   = 'ЗАМЕНИТЕ_НА_ПАРОЛЬ_ПРИЛОЖЕНИЯ_ИЗ_ID.YANDEX.RU';

// From должен совпадать с ящиком, через который идёт авторизация (SMTP_USERNAME)
const FROM_EMAIL = SMTP_USERNAME;
const FROM_NAME  = 'Guitardo — форма записи';
