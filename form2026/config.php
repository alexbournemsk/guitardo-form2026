<?php
declare(strict_types=1);

// ---- Куда отправлять заявки -------------------------------------
const TO_EMAIL = 'ag@guitardo.ru';

// ---- SMTP-настройки (заполните данными вашего почтового ящика) --
// Спросите у хостинга/в почтовом клиенте: адрес SMTP-сервера, порт,
// логин (обычно полный email) и пароль от ящика.
// Обычно это mail.guitardo.ru или smtp.guitardo.ru, порт 465 (SSL) или 587 (TLS).
const SMTP_HOST       = 'mail.guitardo.ru';
const SMTP_PORT       = 465;
const SMTP_SECURE     = 'ssl'; // 'ssl' для порта 465, 'tls' для порта 587
const SMTP_USERNAME   = 'noreply@guitardo.ru'; // логин ящика, от имени которого отправляем
const SMTP_PASSWORD   = 'ЗАМЕНИТЕ_НА_ПАРОЛЬ_ОТ_ПОЧТОВОГО_ЯЩИКА';

// From должен совпадать с ящиком, через который идёт авторизация (SMTP_USERNAME)
const FROM_EMAIL = SMTP_USERNAME;
const FROM_NAME  = 'Guitardo — форма записи';
