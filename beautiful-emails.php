<?php
/**
 * Plugin Name: Beautiful Emails Pro
 * Plugin URI: https://github.com/RobertoBennett/Beautiful-Emails-Pro
 * Description: Расширенное оформление email уведомлений WordPress с кнопками и настройкой заголовка
 * Version: 2.0.0
 * Author: Robert Bennett
 * Text Domain: Beautiful Emails Pro
 */

// Защита от прямого доступа
if (!defined('ABSPATH')) {
    exit;
}

// Константы плагина
define('BE_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('BE_PLUGIN_URL', plugin_dir_url(__FILE__));
define('BE_PLUGIN_VERSION', '2.0.0');

// Функция для валидации HEX цветов
if (!function_exists('sanitize_hex_color')) {
    function sanitize_hex_color($color) {
        if ('' === $color) {
            return '';
        }
        
        // 3 или 6 символов hex цвета
        if (preg_match('|^#([A-Fa-f0-9]{3}){1,2}$|', $color)) {
            return $color;
        }
        
        return '';
    }
}

// Основной класс плагина
class BeautifulEmails {
    
    private static $instance = null;
    private $options;
    
    public static function get_instance() {
        if (self::$instance == null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->options = get_option('beautiful_emails_settings');
        $this->init();
    }
    
    private function init() {
        // Хуки активации/деактивации
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        
        // Инициализация функционала
        add_action('init', array($this, 'load_textdomain'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        
        // AJAX обработчики
        add_action('wp_ajax_be_preview_template', array($this, 'ajax_preview_template'));
        add_action('wp_ajax_be_send_test_email', array($this, 'ajax_send_test_email'));
        
        // Применение шаблона к письмам
        if ($this->get_option('enable_template', '1') == '1') {
            add_filter('wp_mail_content_type', array($this, 'set_html_content_type'));
            add_filter('wp_mail', array($this, 'apply_email_template'));
        }
    }
    
    public function activate() {
        // Установка настроек по умолчанию
        $default_settings = array(
            'enable_template' => '1',
            'primary_color' => '#667eea',
            'secondary_color' => '#764ba2',
            'background_color' => '#f4f4f4',
            'content_bg_color' => '#ffffff',
            'text_color' => '#333333',
            'link_color' => '#667eea',
            'footer_bg_color' => '#f8f9fa',
            'footer_text_color' => '#6c757d',
            'logo_url' => '',
            'show_site_title' => '1',
            'show_header_bg' => '1',
            'header_bg_type' => 'gradient',
            'header_solid_color' => '#667eea',
            'button_style' => 'rounded',
            'button_bg_color' => '#667eea',
            'button_text_color' => '#ffffff',
            'button_hover_bg' => '#5a67d8',
            'convert_links_to_buttons' => '0',
            'footer_text' => 'Это письмо отправлено с сайта {site_name}',
            'copyright_text' => '© {year} {site_name}. Все права защищены.',
            'template_style' => 'modern',
            'custom_css' => ''
        );
        
        if (!get_option('beautiful_emails_settings')) {
            add_option('beautiful_emails_settings', $default_settings);
        }
    }
    
    public function deactivate() {
        // Очистка при деактивации
    }
    
    public function load_textdomain() {
        load_plugin_textdomain('beautiful-emails', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }
    
    public function add_admin_menu() {
        add_menu_page(
            __('Beautiful Emails', 'beautiful-emails'),
            __('Beautiful Emails', 'beautiful-emails'),
            'manage_options',
            'beautiful-emails',
            array($this, 'admin_page'),
            'dashicons-email-alt',
            30
        );
    }
    
    public function register_settings() {
        register_setting(
            'beautiful_emails_group', 
            'beautiful_emails_settings',
            array($this, 'sanitize_settings')
        );
    }
    
    public function sanitize_settings($input) {
        $sanitized = array();
        
        // Checkbox поля - проверяем их наличие
        $checkbox_fields = array(
            'enable_template',
            'show_site_title', 
            'show_header_bg',
            'convert_links_to_buttons'
        );
        
        foreach ($checkbox_fields as $field) {
            $sanitized[$field] = isset($input[$field]) && $input[$field] == '1' ? '1' : '0';
        }
        
        // Текстовые поля
        $text_fields = array(
            'logo_url',
            'template_style',
            'header_bg_type',
            'button_style',
            'footer_text',
            'copyright_text'
        );
        
        foreach ($text_fields as $field) {
            $sanitized[$field] = isset($input[$field]) ? sanitize_text_field($input[$field]) : '';
        }
        
        // URL поле для логотипа
        if (isset($input['logo_url'])) {
            $sanitized['logo_url'] = esc_url_raw($input['logo_url']);
        }
        
        // Цветовые поля
        $color_fields = array(
            'primary_color',
            'secondary_color',
            'background_color',
            'content_bg_color',
            'text_color',
            'link_color',
            'footer_bg_color',
            'footer_text_color',
            'header_solid_color',
            'button_bg_color',
            'button_text_color',
            'button_hover_bg'
        );
        
        foreach ($color_fields as $field) {
            if (isset($input[$field])) {
                $color = sanitize_hex_color($input[$field]);
                $sanitized[$field] = $color ? $color : '#000000';
            }
        }
        
        // CSS поле
        $sanitized['custom_css'] = isset($input['custom_css']) ? wp_strip_all_tags($input['custom_css']) : '';
        
        // Обновляем внутренние настройки
        $this->options = $sanitized;
        
        return $sanitized;
    }
    
    public function enqueue_admin_scripts($hook) {
        if ($hook != 'toplevel_page_beautiful-emails') {
            return;
        }
        
        // Загружаем WordPress медиа скрипты
        wp_enqueue_media();
        
        // Color Picker
        wp_enqueue_style('wp-color-picker');
        wp_enqueue_script('wp-color-picker');
        
        // Наши скрипты
        wp_enqueue_style('beautiful-emails-admin', BE_PLUGIN_URL . 'assets/admin.css', array('wp-color-picker'), BE_PLUGIN_VERSION);
        
        wp_enqueue_script(
            'beautiful-emails-admin', 
            BE_PLUGIN_URL . 'assets/admin.js', 
            array('jquery', 'wp-color-picker', 'media-upload'), 
            BE_PLUGIN_VERSION, 
            true
        );
        
        // Локализация
        wp_localize_script('beautiful-emails-admin', 'be_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('be_ajax_nonce'),
            'saved_options' => $this->options
        ));
    }
    
    public function get_option($key, $default = '') {
        return isset($this->options[$key]) ? $this->options[$key] : $default;
    }
    
    public function set_html_content_type() {
        return 'text/html';
    }
    
    public function apply_email_template($args) {
        // Проверяем, не применен ли уже шаблон
        if (strpos($args['message'], '<!DOCTYPE html>') !== false) {
            return $args;
        }
        
        $args['message'] = $this->get_email_template($args['message'], $args['subject']);
        
        if (!is_array($args['headers'])) {
            $args['headers'] = array();
        }
        $args['headers'][] = 'Content-Type: text/html; charset=UTF-8';
        
        return $args;
    }
    
    // Функция конвертации ссылок в кнопки
    private function convert_links_to_buttons($content) {
        if ($this->get_option('convert_links_to_buttons', '0') != '1') {
            return $content;
        }
        
        $button_bg = $this->get_option('button_bg_color', '#667eea');
        $button_text = $this->get_option('button_text_color', '#ffffff');
        $button_style = $this->get_option('button_style', 'rounded');
        
        // Определяем радиус кнопки
        $border_radius = '4px';
        if ($button_style == 'pill') {
            $border_radius = '50px';
        } elseif ($button_style == 'square') {
            $border_radius = '0';
        }
        
        // Паттерн для поиска ссылок с классом button или btn
        $pattern = '/<a\s+([^>]*class=["\'][^"\']*(?:button|btn)[^"\']*["\'][^>]*)>(.*?)<\/a>/i';
        
        $replacement = '<table role="presentation" cellspacing="0" cellpadding="0" border="0" style="margin: 20px 0;">
            <tr>
                <td style="border-radius: ' . $border_radius . '; background: ' . $button_bg . ';" align="center">
                    <a $1 style="display: inline-block; padding: 12px 30px; font-family: sans-serif; font-size: 16px; font-weight: bold; color: ' . $button_text . '; text-decoration: none; border-radius: ' . $border_radius . ';">$2</a>
                </td>
            </tr>
        </table>';
        
        $content = preg_replace($pattern, $replacement, $content);
        
        return $content;
    }
    
    public function get_email_template($message, $subject = '') {
        $site_name = get_bloginfo('name');
        $site_url = home_url();
        $current_year = date('Y');
        
        // Получаем настройки
        $logo_url = $this->get_option('logo_url', '');
        $show_site_title = $this->get_option('show_site_title', '1');
        $show_header_bg = $this->get_option('show_header_bg', '1');
        $header_bg_type = $this->get_option('header_bg_type', 'gradient');
        $header_solid_color = $this->get_option('header_solid_color', '#667eea');
        $primary_color = $this->get_option('primary_color', '#667eea');
        $secondary_color = $this->get_option('secondary_color', '#764ba2');
        $bg_color = $this->get_option('background_color', '#f4f4f4');
        $content_bg = $this->get_option('content_bg_color', '#ffffff');
        $text_color = $this->get_option('text_color', '#333333');
        $link_color = $this->get_option('link_color', '#667eea');
        $footer_bg = $this->get_option('footer_bg_color', '#f8f9fa');
        $footer_text_color = $this->get_option('footer_text_color', '#6c757d');
        $button_bg = $this->get_option('button_bg_color', '#667eea');
        $button_text = $this->get_option('button_text_color', '#ffffff');
        $button_hover = $this->get_option('button_hover_bg', '#5a67d8');
        $button_style = $this->get_option('button_style', 'rounded');
        $custom_css = $this->get_option('custom_css', '');
        
        // Определяем фон заголовка
        $header_background = 'transparent';
        if ($show_header_bg == '1') {
            if ($header_bg_type == 'gradient') {
                $header_background = 'linear-gradient(135deg, ' . $primary_color . ' 0%, ' . $secondary_color . ' 100%)';
            } elseif ($header_bg_type == 'solid') {
                $header_background = $header_solid_color;
            }
        }
        
        // Определяем отступы заголовка
        $header_padding = ($show_header_bg == '1') ? '40px 30px' : '20px 30px';
        
        // Конвертируем ссылки в кнопки если нужно
        $message = $this->convert_links_to_buttons($message);
        
        // Заменяем переменные
        $footer_text = str_replace(
            array('{site_name}', '{site_url}', '{year}'),
            array($site_name, $site_url, $current_year),
            $this->get_option('footer_text', 'Это письмо отправлено с сайта {site_name}')
        );
        
        $copyright_text = str_replace(
            array('{site_name}', '{site_url}', '{year}'),
            array($site_name, $site_url, $current_year),
            $this->get_option('copyright_text', '© {year} {site_name}. Все права защищены.')
        );
        
        // Определяем радиус кнопок
        $button_radius = '4px';
        if ($button_style == 'pill') {
            $button_radius = '50px';
        } elseif ($button_style == 'square') {
            $button_radius = '0';
        }
        
        // HTML шаблон
        $html = '<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>' . esc_html($subject) . '</title>
    <style type="text/css">
        /* Сброс стилей */
        body, table, td, a { -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }
        table, td { mso-table-lspace: 0pt; mso-table-rspace: 0pt; }
        img { -ms-interpolation-mode: bicubic; border: 0; outline: none; text-decoration: none; }
        
        /* Основные стили */
        body {
            margin: 0 !important;
            padding: 0 !important;
            min-width: 100% !important;
            width: 100% !important;
            background-color: ' . $bg_color . ';
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
        }
        
        /* Стили ссылок */
        a { color: ' . $link_color . '; text-decoration: none; }
        a:hover { text-decoration: underline; }
        
        /* Стили кнопок */
        .email-button {
            display: inline-block;
            padding: 14px 32px;
            background-color: ' . $button_bg . ';
            color: ' . $button_text . ' !important;
            text-decoration: none !important;
            font-weight: bold;
            font-size: 16px;
            border-radius: ' . $button_radius . ';
            transition: background-color 0.3s ease;
        }
        
        .email-button:hover {
            background-color: ' . $button_hover . ' !important;
            text-decoration: none !important;
        }
        
        /* Контейнер */
        .email-container {
            max-width: 600px;
            width: 100%;
            margin: 0 auto;
        }
        
        /* Адаптивность */
        @media screen and (max-width: 600px) {
            .email-container {
                width: 100% !important;
                margin: auto !important;
            }
            
            .responsive-padding {
                padding: 20px 15px !important;
            }
            
            h1 { font-size: 22px !important; }
            .content-text { font-size: 15px !important; }
            .footer-text { font-size: 12px !important; }
            
            .responsive-img {
                height: auto !important;
                max-width: 100% !important;
                width: auto !important;
            }
            
            .email-button {
                display: block !important;
                width: 100% !important;
                text-align: center !important;
            }
        }
        
        ' . $custom_css . '
    </style>
</head>
<body style="margin: 0; padding: 0; background-color: ' . $bg_color . ';">
    <div role="article" aria-roledescription="email" lang="ru">
        
        <!-- Основной контейнер -->
        <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="margin: auto; background-color: ' . $bg_color . ';">
            <tr>
                <td style="padding: 20px 0;">
                    
                    <!-- Email контейнер -->
                    <div class="email-container" style="margin: auto;">
                        <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="margin: auto; background-color: ' . $content_bg . '; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">';
        
        // Header - показываем только если есть логотип или включен заголовок
        if (!empty($logo_url) || $show_site_title == '1') {
            $html .= '
                            <!-- Header -->
                            <tr>
                                <td class="header-padding" style="background: ' . $header_background . '; padding: ' . $header_padding . '; text-align: center;">
                                    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                                        <tr>
                                            <td style="text-align: center;">';
            
            if (!empty($logo_url)) {
                $html .= '
                                                <img src="' . esc_url($logo_url) . '" alt="' . esc_attr($site_name) . '" class="responsive-img" style="max-width: 200px; height: auto; display: block; margin: 0 auto' . ($show_site_title == '1' ? ' 15px' : '') . ';">
                ';
            }
            
            if ($show_site_title == '1') {
                $title_color = ($show_header_bg == '1') ? '#ffffff' : $text_color;
                $html .= '
                                                <h1 style="margin: 0; color: ' . $title_color . '; font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, sans-serif; font-size: 26px; font-weight: 300; text-align: center; line-height: 1.2;">
                                                    ' . esc_html($site_name) . '
                                                </h1>
                ';
            }
            
            $html .= '
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>';
        }
        
        $html .= '
                            <!-- Content -->
                            <tr>
                                <td class="content-padding responsive-padding" style="padding: 40px 30px;">
                                    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                                        <tr>
                                            <td class="content-text" style="color: ' . $text_color . '; font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, sans-serif; font-size: 16px; line-height: 1.6;">
                                                ' . wpautop($message) . '
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                            
                            <!-- Footer -->
                            <tr>
                                <td class="footer-padding responsive-padding" style="background-color: ' . $footer_bg . '; padding: 25px 30px; text-align: center; border-top: 1px solid rgba(0,0,0,0.05);">
                                    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                                        <tr>
                                            <td class="footer-text" style="color: ' . $footer_text_color . '; font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, sans-serif; font-size: 14px; line-height: 1.5; text-align: center; padding-bottom: 10px;">
                                                ' . $footer_text . '
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="footer-text" style="color: ' . $footer_text_color . '; font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, sans-serif; font-size: 12px; line-height: 1.5; text-align: center; opacity: 0.8;">
                                                ' . $copyright_text . '
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                            
                        </table>
                    </div>
                    
                </td>
            </tr>
        </table>
        
    </div>
</body>
</html>';
        
        return $html;
    }
    
    public function ajax_preview_template() {
        check_ajax_referer('be_ajax_nonce', 'nonce');
        
        // Получаем сохраненные настройки
        $saved_options = get_option('beautiful_emails_settings', array());
        
        // Обновляем настройки из AJAX запроса для превью
        if (isset($_POST['settings']) && is_array($_POST['settings'])) {
            // Объединяем сохраненные настройки с временными для превью
            $this->options = array_merge($saved_options, $_POST['settings']);
        } else {
            $this->options = $saved_options;
        }
        
        $test_message = "Это тестовое сообщение для предпросмотра шаблона email.\n\n";
        $test_message .= "Второй параграф с примером текста. Здесь может быть <strong>жирный текст</strong> или обычная <a href='#'>ссылка</a>.\n\n";
        $test_message .= "<a href='#' class='button'>Это станет кнопкой</a>\n\n";
        $test_message .= "С уважением,\nКоманда сайта";
        
        $template = $this->get_email_template($test_message, 'Тестовое письмо');
        
        wp_send_json_success($template);
    }
    
    public function ajax_send_test_email() {
        check_ajax_referer('be_ajax_nonce', 'nonce');
        
        $email = sanitize_email($_POST['email']);
        if (!is_email($email)) {
            wp_send_json_error('Неверный email адрес');
        }
        
        $test_message = "Это тестовое письмо от плагина Beautiful Emails.\n\n";
        $test_message .= "Если вы видите это сообщение с красивым оформлением, значит плагин работает корректно!\n\n";
        $test_message .= "<a href='" . home_url() . "' class='button'>Перейти на сайт</a>\n\n";
        $test_message .= "С уважением,\nВаш сайт";
        
        $subject = 'Тестовое письмо - ' . get_bloginfo('name');
        $headers = array('Content-Type: text/html; charset=UTF-8');
        
        $result = wp_mail($email, $subject, $test_message, $headers);
        
        if ($result) {
            wp_send_json_success('Письмо успешно отправлено на ' . $email);
        } else {
            wp_send_json_error('Ошибка при отправке письма');
        }
    }
    
    public function admin_page() {
        include BE_PLUGIN_PATH . 'templates/admin-page.php';
    }
}

// Запуск плагина
BeautifulEmails::get_instance();