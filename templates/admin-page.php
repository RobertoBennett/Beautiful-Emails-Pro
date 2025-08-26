<?php
if (!defined('ABSPATH')) exit;

$options = get_option('beautiful_emails_settings');
?>

<div class="wrap beautiful-emails-admin">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <div class="be-admin-container">
        <div class="be-settings-panel">
            <form method="post" action="options.php" id="be-settings-form">
                <?php settings_fields('beautiful_emails_group'); ?>
                
                <!-- Вкладки -->
                <div class="nav-tab-wrapper">
                    <a href="#general" class="nav-tab nav-tab-active" data-tab="general">Основные</a>
                    <a href="#header" class="nav-tab" data-tab="header">Заголовок</a>
                    <a href="#colors" class="nav-tab" data-tab="colors">Цвета</a>
                    <a href="#buttons" class="nav-tab" data-tab="buttons">Кнопки</a>
                    <a href="#content" class="nav-tab" data-tab="content">Контент</a>
                    <a href="#advanced" class="nav-tab" data-tab="advanced">Дополнительно</a>
                </div>
                
                <!-- Вкладка: Основные настройки -->
                <div class="tab-content tab-general active">
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="enable_template">Включить шаблон</label>
                            </th>
                            <td>
                                <label class="switch">
                                    <input type="checkbox" name="beautiful_emails_settings[enable_template]" 
                                           id="enable_template" value="1" 
                                           <?php checked($options['enable_template'] ?? '1', '1'); ?>>
                                    <span class="slider"></span>
                                </label>
                                <p class="description">Применять красивое оформление ко всем исходящим email</p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="template_style">Стиль шаблона</label>
                            </th>
                            <td>
                                <select name="beautiful_emails_settings[template_style]" id="template_style">
                                    <option value="modern" <?php selected($options['template_style'] ?? 'modern', 'modern'); ?>>Современный</option>
                                    <option value="classic" <?php selected($options['template_style'] ?? '', 'classic'); ?>>Классический</option>
                                    <option value="minimal" <?php selected($options['template_style'] ?? '', 'minimal'); ?>>Минималистичный</option>
                                </select>
                            </td>
                        </tr>
                    </table>
                </div>
                
                <!-- Вкладка: Заголовок -->
                <div class="tab-content tab-header">
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="logo_url">Логотип</label>
                            </th>
                            <td>
                                <div class="logo-upload">
                                    <input type="text" name="beautiful_emails_settings[logo_url]" 
                                           id="logo_url" value="<?php echo esc_attr($options['logo_url'] ?? ''); ?>" 
                                           class="regular-text">
                                    <button type="button" class="button" id="upload_logo_button">Выбрать изображение</button>
                                    <?php if (!empty($options['logo_url'])): ?>
                                        <button type="button" class="button" id="remove_logo_button">Удалить</button>
                                    <?php endif; ?>
                                </div>
                                <?php if (!empty($options['logo_url'])): ?>
                                    <div class="logo-preview" style="margin-top: 10px;">
                                        <img src="<?php echo esc_url($options['logo_url']); ?>" style="max-width: 200px; height: auto;">
                                    </div>
                                <?php endif; ?>
                                <p class="description">Логотип будет отображаться в шапке письма</p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="show_site_title">Показывать название сайта</label>
                            </th>
                            <td>
                                <label class="switch">
                                    <input type="checkbox" name="beautiful_emails_settings[show_site_title]" 
                                           id="show_site_title" value="1" 
                                           <?php checked($options['show_site_title'] ?? '1', '1'); ?>>
                                    <span class="slider"></span>
                                </label>
                                <p class="description">Отображать название сайта под логотипом</p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="show_header_bg">Фон заголовка</label>
                            </th>
                            <td>
                                <label class="switch">
                                    <input type="checkbox" name="beautiful_emails_settings[show_header_bg]" 
                                           id="show_header_bg" value="1" 
                                           <?php checked($options['show_header_bg'] ?? '1', '1'); ?>>
                                    <span class="slider"></span>
                                </label>
                                <p class="description">Показывать цветной фон в заголовке</p>
                            </td>
                        </tr>
                        
                        <tr class="header-bg-options" <?php echo ($options['show_header_bg'] ?? '1') == '0' ? 'style="display:none;"' : ''; ?>>
                            <th scope="row">
                                <label for="header_bg_type">Тип фона</label>
                            </th>
                            <td>
                                <select name="beautiful_emails_settings[header_bg_type]" id="header_bg_type">
                                    <option value="gradient" <?php selected($options['header_bg_type'] ?? 'gradient', 'gradient'); ?>>Градиент</option>
                                    <option value="solid" <?php selected($options['header_bg_type'] ?? '', 'solid'); ?>>Сплошной цвет</option>
                                </select>
                            </td>
                        </tr>
                        
                        <tr class="header-bg-solid" <?php echo ($options['header_bg_type'] ?? 'gradient') != 'solid' ? 'style="display:none;"' : ''; ?>>
                            <th scope="row">
                                <label for="header_solid_color">Цвет фона</label>
                            </th>
                            <td>
                                <input type="text" name="beautiful_emails_settings[header_solid_color]" 
                                       id="header_solid_color" value="<?php echo esc_attr($options['header_solid_color'] ?? '#667eea'); ?>" 
                                       class="color-field">
                            </td>
                        </tr>
                    </table>
                </div>
                
                <!-- Вкладка: Цвета -->
                <div class="tab-content tab-colors">
                    <table class="form-table">
                        <tr>
                            <th scope="row">Готовые схемы</th>
                            <td>
                                <div class="color-schemes">
                                    <button type="button" class="button scheme-btn" data-scheme="modern">Современная</button>
                                    <button type="button" class="button scheme-btn" data-scheme="corporate">Корпоративная</button>
                                    <button type="button" class="button scheme-btn" data-scheme="fresh">Свежая</button>
                                    <button type="button" class="button scheme-btn" data-scheme="warm">Тёплая</button>
                                </div>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="primary_color">Основной цвет</label>
                            </th>
                            <td>
                                <input type="text" name="beautiful_emails_settings[primary_color]" 
                                       id="primary_color" value="<?php echo esc_attr($options['primary_color'] ?? '#667eea'); ?>" 
                                       class="color-field">
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="secondary_color">Дополнительный цвет</label>
                            </th>
                            <td>
                                <input type="text" name="beautiful_emails_settings[secondary_color]" 
                                       id="secondary_color" value="<?php echo esc_attr($options['secondary_color'] ?? '#764ba2'); ?>" 
                                       class="color-field">
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="background_color">Цвет фона</label>
                            </th>
                            <td>
                                <input type="text" name="beautiful_emails_settings[background_color]" 
                                       id="background_color" value="<?php echo esc_attr($options['background_color'] ?? '#f4f4f4'); ?>" 
                                       class="color-field">
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="content_bg_color">Фон контента</label>
                            </th>
                            <td>
                                <input type="text" name="beautiful_emails_settings[content_bg_color]" 
                                       id="content_bg_color" value="<?php echo esc_attr($options['content_bg_color'] ?? '#ffffff'); ?>" 
                                       class="color-field">
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="text_color">Цвет текста</label>
                            </th>
                            <td>
                                <input type="text" name="beautiful_emails_settings[text_color]" 
                                       id="text_color" value="<?php echo esc_attr($options['text_color'] ?? '#333333'); ?>" 
                                       class="color-field">
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="link_color">Цвет ссылок</label>
                            </th>
                            <td>
                                <input type="text" name="beautiful_emails_settings[link_color]" 
                                       id="link_color" value="<?php echo esc_attr($options['link_color'] ?? '#667eea'); ?>" 
                                       class="color-field">
                            </td>
                        </tr>
                    </table>
                </div>
                
                <!-- Вкладка: Кнопки -->
                <div class="tab-content tab-buttons">
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="convert_links_to_buttons">Конвертировать ссылки в кнопки</label>
                            </th>
                            <td>
                                <label class="switch">
                                    <input type="checkbox" name="beautiful_emails_settings[convert_links_to_buttons]" 
                                           id="convert_links_to_buttons" value="1" 
                                           <?php checked($options['convert_links_to_buttons'] ?? '0', '1'); ?>>
                                    <span class="slider"></span>
                                </label>
                                <p class="description">Автоматически преобразовывать ссылки с классом "button" или "btn" в кнопки</p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="button_style">Стиль кнопок</label>
                            </th>
                            <td>
                                <select name="beautiful_emails_settings[button_style]" id="button_style">
                                    <option value="rounded" <?php selected($options['button_style'] ?? 'rounded', 'rounded'); ?>>Скругленные</option>
                                    <option value="square" <?php selected($options['button_style'] ?? '', 'square'); ?>>Прямоугольные</option>
                                    <option value="pill" <?php selected($options['button_style'] ?? '', 'pill'); ?>>Овальные</option>
                                </select>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="button_bg_color">Цвет фона кнопки</label>
                            </th>
                            <td>
                                <input type="text" name="beautiful_emails_settings[button_bg_color]" 
                                       id="button_bg_color" value="<?php echo esc_attr($options['button_bg_color'] ?? '#667eea'); ?>" 
                                       class="color-field">
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="button_text_color">Цвет текста кнопки</label>
                            </th>
                            <td>
                                <input type="text" name="beautiful_emails_settings[button_text_color]" 
                                       id="button_text_color" value="<?php echo esc_attr($options['button_text_color'] ?? '#ffffff'); ?>" 
                                       class="color-field">
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="button_hover_bg">Цвет кнопки при наведении</label>
                            </th>
                            <td>
                                <input type="text" name="beautiful_emails_settings[button_hover_bg]" 
                                       id="button_hover_bg" value="<?php echo esc_attr($options['button_hover_bg'] ?? '#5a67d8'); ?>" 
                                       class="color-field">
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">Пример использования</th>
                            <td>
                                <code>&lt;a href="#" class="button"&gt;Текст кнопки&lt;/a&gt;</code>
                                <p class="description">Добавьте класс "button" или "btn" к любой ссылке, чтобы превратить её в кнопку</p>
                            </td>
                        </tr>
                    </table>
                </div>
                
                <!-- Вкладка: Контент -->
                <div class="tab-content tab-content">
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="footer_text">Текст подвала</label>
                            </th>
                            <td>
                                <input type="text" name="beautiful_emails_settings[footer_text]" 
                                       id="footer_text" value="<?php echo esc_attr($options['footer_text'] ?? 'Это письмо отправлено с сайта {site_name}'); ?>" 
                                       class="large-text">
                                <p class="description">Доступные переменные: {site_name}, {site_url}, {year}</p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="copyright_text">Копирайт</label>
                            </th>
                            <td>
                                <input type="text" name="beautiful_emails_settings[copyright_text]" 
                                       id="copyright_text" value="<?php echo esc_attr($options['copyright_text'] ?? '© {year} {site_name}. Все права защищены.'); ?>" 
                                       class="large-text">
                                <p class="description">Доступные переменные: {site_name}, {site_url}, {year}</p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="footer_bg_color">Фон подвала</label>
                            </th>
                            <td>
                                <input type="text" name="beautiful_emails_settings[footer_bg_color]" 
                                       id="footer_bg_color" value="<?php echo esc_attr($options['footer_bg_color'] ?? '#f8f9fa'); ?>" 
                                       class="color-field">
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="footer_text_color">Цвет текста подвала</label>
                            </th>
                            <td>
                                <input type="text" name="beautiful_emails_settings[footer_text_color]" 
                                       id="footer_text_color" value="<?php echo esc_attr($options['footer_text_color'] ?? '#6c757d'); ?>" 
                                       class="color-field">
                            </td>
                        </tr>
                    </table>
                </div>
                
                <!-- Вкладка: Дополнительно -->
                <div class="tab-content tab-advanced">
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="custom_css">Дополнительный CSS</label>
                            </th>
                            <td>
                                <textarea name="beautiful_emails_settings[custom_css]" 
                                          id="custom_css" rows="10" class="large-text code"><?php echo esc_textarea($options['custom_css'] ?? ''); ?></textarea>
                                <p class="description">Добавьте свои CSS стили для дополнительной настройки</p>
                            </td>
                        </tr>
                    </table>
                </div>
                
                <?php submit_button('Сохранить настройки'); ?>
            </form>
        </div>
        
        <!-- Панель предпросмотра -->
        <div class="be-preview-panel">
            <div class="preview-header">
                                <h3>Предпросмотр шаблона</h3>
                <button type="button" class="button" id="refresh-preview">
                    <span class="dashicons dashicons-update"></span> Обновить
                </button>
            </div>
            
            <div class="preview-container">
                <iframe id="email-preview" frameborder="0"></iframe>
            </div>
            
            <div class="preview-actions">
                <h4>Тестовая отправка</h4>
                <div class="test-email-form">
                    <input type="email" id="test-email" placeholder="Введите email для теста" class="regular-text">
                    <button type="button" class="button button-primary" id="send-test">Отправить тест</button>
                </div>
                <div id="test-result" class="notice" style="display:none;"></div>
            </div>
        </div>
    </div>
</div>