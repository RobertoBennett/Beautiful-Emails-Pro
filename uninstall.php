<?php
/**
 * Удаление плагина Beautiful Emails
 */

// Защита от прямого доступа
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Удаляем настройки плагина
delete_option('beautiful_emails_settings');

// Удаляем транзиенты, если они использовались
delete_transient('beautiful_emails_cache');