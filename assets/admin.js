jQuery(document).ready(function($) {
    // Флаг для отслеживания изменений
    var hasChanges = false;
    
    // Инициализация color picker
    if ($.fn.wpColorPicker) {
        $('.color-field').each(function() {
            $(this).wpColorPicker({
                change: function(event, ui) {
                    hasChanges = true;
                    setTimeout(function() {
                        updatePreview();
                    }, 100);
                },
                clear: function() {
                    hasChanges = true;
                    setTimeout(function() {
                        updatePreview();
                    }, 100);
                }
            });
        });
    }
    
    // Отслеживание изменений в форме
    $('#be-settings-form').on('change', 'input, select, textarea', function() {
        hasChanges = true;
    });
    
    // Переключение вкладок
    $(document).on('click', '.nav-tab', function(e) {
        e.preventDefault();
        var tab = $(this).data('tab');
        
        $('.nav-tab').removeClass('nav-tab-active');
        $(this).addClass('nav-tab-active');
        
        $('.tab-content').removeClass('active').hide();
        $('.tab-' + tab).addClass('active').show();
        
        return false;
    });
    
    // Загрузка логотипа
    $(document).on('click', '#upload_logo_button', function(e) {
        e.preventDefault();
        
        if (!wp.media) {
            alert('WordPress media library not available');
            return;
        }
        
        var custom_uploader = wp.media({
            title: 'Выберите логотип',
            button: {
                text: 'Использовать это изображение'
            },
            multiple: false
        });
        
        custom_uploader.on('select', function() {
            var attachment = custom_uploader.state().get('selection').first().toJSON();
            $('#logo_url').val(attachment.url);
            
            // Обновляем превью и добавляем кнопку удаления
            var previewHtml = '<div class="logo-preview" style="margin-top: 10px;">' +
                             '<img src="' + attachment.url + '" style="max-width: 200px; height: auto;">' +
                             '</div>';
            
            $('.logo-preview').remove();
            $('#logo_url').closest('td').append(previewHtml);
            
            // Добавляем кнопку удаления если её нет
            if (!$('#remove_logo_button').length) {
                $('#upload_logo_button').after(' <button type="button" class="button" id="remove_logo_button">Удалить</button>');
            }
            
            hasChanges = true;
            updatePreview();
        });
        
        custom_uploader.open();
        return false;
    });
    
    // Удаление логотипа
    $(document).on('click', '#remove_logo_button', function(e) {
        e.preventDefault();
        $('#logo_url').val('');
        $('.logo-preview').remove();
        $(this).remove();
        hasChanges = true;
        updatePreview();
        return false;
    });
    
    // Управление видимостью настроек заголовка
    $('#show_header_bg').on('change', function() {
        if ($(this).is(':checked')) {
            $('.header-bg-options').fadeIn();
            if ($('#header_bg_type').val() === 'solid') {
                $('.header-bg-solid').fadeIn();
            }
        } else {
            $('.header-bg-options').fadeOut();
            $('.header-bg-solid').fadeOut();
        }
        updatePreview();
    });
    
    // Переключение типа фона заголовка
    $('#header_bg_type').on('change', function() {
        if ($(this).val() === 'solid') {
            $('.header-bg-solid').fadeIn();
        } else {
            $('.header-bg-solid').fadeOut();
        }
        updatePreview();
    });
    
    // Функция обновления превью
    function updatePreview() {
        if (!be_ajax || !be_ajax.ajax_url) {
            console.error('AJAX configuration not found');
            return;
        }
        
        // Собираем ВСЕ данные формы, включая unchecked checkboxes
        var settings = {};
        
        // Сначала устанавливаем все checkbox в 0
        $('#be-settings-form').find('input[type="checkbox"]').each(function() {
            var name = $(this).attr('name');
            if (name && name.indexOf('beautiful_emails_settings') !== -1) {
                var key = name.replace('beautiful_emails_settings[', '').replace(']', '');
                settings[key] = '0';
            }
        });
        
        // Теперь собираем все значения
        $('#be-settings-form').find('input, select, textarea').each(function() {
            var $this = $(this);
            var name = $this.attr('name');
            if (name && name.indexOf('beautiful_emails_settings') !== -1) {
                var key = name.replace('beautiful_emails_settings[', '').replace(']', '');
                if ($this.attr('type') === 'checkbox') {
                    if ($this.is(':checked')) {
                        settings[key] = '1';
                    }
                } else if ($this.attr('type') === 'radio') {
                    if ($this.is(':checked')) {
                        settings[key] = $this.val();
                    }
                } else {
                    settings[key] = $this.val() || '';
                }
            }
        });
        
        // Отладка - выводим собранные настройки
        console.log('Settings to preview:', settings);
        
        $.ajax({
            url: be_ajax.ajax_url,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'be_preview_template',
                nonce: be_ajax.nonce,
                settings: settings
            },
            success: function(response) {
                if (response.success && response.data) {
                    var iframe = document.getElementById('email-preview');
                    if (iframe) {
                        var iframeDoc = iframe.contentDocument || iframe.contentWindow.document;
                        iframeDoc.open();
                        iframeDoc.write(response.data);
                        iframeDoc.close();
                    }
                } else {
                    console.error('Preview update failed:', response);
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX error:', status, error);
                console.log('Response:', xhr.responseText);
            }
        });
    }
    
    // Обработка сохранения формы
    $('#be-settings-form').on('submit', function(e) {
        // Убеждаемся, что все checkbox имеют значения
        $(this).find('input[type="checkbox"]').each(function() {
            var name = $(this).attr('name');
            // Удаляем существующие скрытые поля для этого checkbox
            $('input[type="hidden"][name="' + name + '"]').remove();
            
            if (!$(this).is(':checked')) {
                // Добавляем скрытое поле со значением 0 для unchecked checkbox
                $(this).after('<input type="hidden" name="' + name + '" value="0" />');
            }
        });
        
        hasChanges = false;
    });
    
    // Предупреждение при уходе со страницы с несохраненными изменениями
    window.onbeforeunload = function() {
        if (hasChanges) {
            return 'У вас есть несохраненные изменения. Вы уверены, что хотите покинуть страницу?';
        }
    };
    
    // Инициализация превью
    setTimeout(function() {
        updatePreview();
        
        // Проверяем начальное состояние элементов
        if ($('#show_header_bg').is(':checked')) {
            $('.header-bg-options').show();
            if ($('#header_bg_type').val() === 'solid') {
                $('.header-bg-solid').show();
            }
        } else {
            $('.header-bg-options').hide();
            $('.header-bg-solid').hide();
        }
    }, 500);
    
    // Обновление превью по кнопке
    $(document).on('click', '#refresh-preview', function(e) {
        e.preventDefault();
        var $icon = $(this).find('.dashicons');
        $icon.addClass('dashicons-update-spin');
        
        updatePreview();
        
        setTimeout(function() {
            $icon.removeClass('dashicons-update-spin');
        }, 1000);
        
        return false;
    });
    
    // Отправка тестового письма
    $(document).on('click', '#send-test', function(e) {
        e.preventDefault();
        
        var email = $('#test-email').val();
        
        if (!email || !email.match(/^[^\s@]+@[^\s@]+\.[^\s@]+$/)) {
            alert('Пожалуйста, введите корректный email адрес');
            return false;
        }
        
        var $button = $(this);
        $button.prop('disabled', true).text('Отправка...');
        
        $.ajax({
            url: be_ajax.ajax_url,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'be_send_test_email',
                nonce: be_ajax.nonce,
                email: email
            },
            success: function(response) {
                var $result = $('#test-result');
                
                if (response.success) {
                    $result.removeClass('error notice-error')
                           .addClass('success notice-success')
                           .html(response.data)
                           .show();
                } else {
                    $result.removeClass('success notice-success')
                           .addClass('error notice-error')
                           .html(response.data || 'Ошибка отправки')
                           .show();
                }
                
                setTimeout(function() {
                    $result.fadeOut();
                }, 5000);
            },
            error: function(xhr, status, error) {
                $('#test-result')
                    .removeClass('success')
                    .addClass('error notice-error')
                    .html('Ошибка AJAX запроса: ' + error)
                    .show();
            },
                        complete: function() {
                $button.prop('disabled', false).text('Отправить тест');
            }
        });
        
        return false;
    });
    
    // Автообновление превью при изменениях
    var saveTimeout;
    $(document).on('change input', '#be-settings-form input, #be-settings-form select, #be-settings-form textarea', function() {
        clearTimeout(saveTimeout);
        saveTimeout = setTimeout(function() {
            updatePreview();
        }, 500);
    });
    
    // Цветовые схемы
    var colorSchemes = {
        'modern': {
            'primary_color': '#667eea',
            'secondary_color': '#764ba2',
            'link_color': '#667eea',
            'button_bg_color': '#667eea',
            'button_hover_bg': '#5a67d8',
            'header_solid_color': '#667eea'
        },
        'corporate': {
            'primary_color': '#2c3e50',
            'secondary_color': '#34495e',
            'link_color': '#3498db',
            'button_bg_color': '#3498db',
            'button_hover_bg': '#2980b9',
            'header_solid_color': '#2c3e50'
        },
        'fresh': {
            'primary_color': '#00d2ff',
            'secondary_color': '#3a7bd5',
            'link_color': '#3a7bd5',
            'button_bg_color': '#00d2ff',
            'button_hover_bg': '#00a8cc',
            'header_solid_color': '#00d2ff'
        },
        'warm': {
            'primary_color': '#f093fb',
            'secondary_color': '#f5576c',
            'link_color': '#f5576c',
            'button_bg_color': '#f5576c',
            'button_hover_bg': '#da304a',
            'header_solid_color': '#f093fb'
        }
    };
    
    // Применение цветовой схемы
    $(document).on('click', '.scheme-btn', function(e) {
        e.preventDefault();
        
        var scheme = $(this).data('scheme');
        if (colorSchemes[scheme]) {
            var colors = colorSchemes[scheme];
            
            for (var key in colors) {
                var $field = $('#' + key);
                if ($field.length) {
                    $field.val(colors[key]);
                    if ($.fn.wpColorPicker && $field.hasClass('color-field')) {
                        $field.wpColorPicker('color', colors[key]);
                    }
                }
            }
            
            // Подсвечиваем выбранную схему
            $('.scheme-btn').removeClass('button-primary');
            $(this).addClass('button-primary');
            
            hasChanges = true;
            updatePreview();
        }
        
        return false;
    });
    
    // Управление заголовком сайта
    $('#show_site_title').on('change', function() {
        updatePreview();
    });
    
    // Управление конвертацией ссылок в кнопки
    $('#convert_links_to_buttons').on('change', function() {
        updatePreview();
    });
    
    // Изменение стиля кнопок
    $('#button_style').on('change', function() {
        updatePreview();
    });
    
    // Добавляем визуальную обратную связь при сохранении
    $(document).on('submit', '#be-settings-form', function() {
        // Показываем индикатор сохранения
        var $submitButton = $(this).find('#submit');
        var originalText = $submitButton.val();
        $submitButton.val('Сохранение...').prop('disabled', true);
        
        // Возвращаем текст кнопки после небольшой задержки
        setTimeout(function() {
            $submitButton.val(originalText).prop('disabled', false);
        }, 1000);
    });
    
    // Функция для проверки корректности цветов
    function isValidColor(color) {
        return /^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/.test(color);
    }
    
    // Валидация цветовых полей при изменении
    $('.color-field').on('change', function() {
        var $this = $(this);
        var color = $this.val();
        
        if (color && !isValidColor(color)) {
            // Если цвет некорректный, добавляем # в начало если его нет
            if (!color.startsWith('#')) {
                color = '#' + color;
                $this.val(color);
            }
            
            // Проверяем еще раз
            if (!isValidColor(color)) {
                // Если все еще некорректный, устанавливаем цвет по умолчанию
                $this.val('#000000');
            }
        }
    });
    
    // Добавляем подсказки для полей
    function addTooltips() {
        // Добавляем подсказки к важным элементам
        $('#logo_url').attr('title', 'URL изображения логотипа. Рекомендуемый размер: 200x50 px');
        $('#show_site_title').attr('title', 'Показывать название сайта под логотипом');
        $('#show_header_bg').attr('title', 'Включить цветной фон в заголовке письма');
        $('#convert_links_to_buttons').attr('title', 'Автоматически преобразовывать ссылки с классом "button" или "btn" в стилизованные кнопки');
    }
    
    // Инициализируем подсказки
    addTooltips();
    
    // Обработка клавиши Escape для закрытия color picker
    $(document).on('keydown', function(e) {
        if (e.keyCode === 27) { // ESC key
            $('.wp-picker-open').find('.wp-color-result').click();
        }
    });
    
    // Функция для экспорта настроек
    function exportSettings() {
        var settings = {};
        $('#be-settings-form').find('input, select, textarea').each(function() {
            var $this = $(this);
            var name = $this.attr('name');
            if (name && name.indexOf('beautiful_emails_settings') !== -1) {
                var key = name.replace('beautiful_emails_settings[', '').replace(']', '');
                if ($this.attr('type') === 'checkbox') {
                    settings[key] = $this.is(':checked') ? '1' : '0';
                } else if ($this.attr('type') === 'radio') {
                    if ($this.is(':checked')) {
                        settings[key] = $this.val();
                    }
                } else {
                    settings[key] = $this.val() || '';
                }
            }
        });
        
        return JSON.stringify(settings, null, 2);
    }
    
    // Функция для импорта настроек
    function importSettings(jsonString) {
        try {
            var settings = JSON.parse(jsonString);
            
            for (var key in settings) {
                var $field = $('[name="beautiful_emails_settings[' + key + ']"]');
                
                if ($field.length) {
                    if ($field.attr('type') === 'checkbox') {
                        $field.prop('checked', settings[key] === '1');
                    } else if ($field.hasClass('color-field')) {
                        $field.val(settings[key]);
                        $field.wpColorPicker('color', settings[key]);
                    } else {
                        $field.val(settings[key]);
                    }
                }
            }
            
            hasChanges = true;
            updatePreview();
            alert('Настройки успешно импортированы!');
            
        } catch (e) {
            alert('Ошибка при импорте настроек: ' + e.message);
        }
    }
    
    // Добавляем кнопки экспорта/импорта (опционально)
    if ($('#be-settings-form').length) {
        var exportImportHtml = '<div class="export-import-buttons" style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #ccc;">' +
            '<h3>Экспорт/Импорт настроек</h3>' +
            '<button type="button" id="export-settings" class="button">Экспортировать настройки</button> ' +
            '<button type="button" id="import-settings" class="button">Импортировать настройки</button>' +
            '<textarea id="settings-json" style="display:none; width:100%; height:200px; margin-top:10px;" placeholder="Вставьте JSON настроек сюда"></textarea>' +
            '</div>';
        
        // Добавляем после кнопки сохранения
        $('.submit').after(exportImportHtml);
    }
    
    // Обработчик экспорта
    $(document).on('click', '#export-settings', function() {
        var json = exportSettings();
        $('#settings-json').val(json).show();
        $('#settings-json').select();
        
        // Копируем в буфер обмена
        try {
            document.execCommand('copy');
            alert('Настройки скопированы в буфер обмена!');
        } catch (err) {
            alert('Выделите и скопируйте настройки вручную');
        }
    });
    
    // Обработчик импорта
    $(document).on('click', '#import-settings', function() {
        var $textarea = $('#settings-json');
        
        if ($textarea.is(':visible') && $textarea.val()) {
            if (confirm('Вы уверены? Это заменит все текущие настройки.')) {
                importSettings($textarea.val());
            }
        } else {
            $textarea.show().focus();
        }
    });
    
    // Скрываем textarea при клике вне его
    $(document).on('click', function(e) {
        if (!$(e.target).closest('.export-import-buttons').length) {
            $('#settings-json').hide();
        }
    });
    
    // Проверка наличия изменений перед переходом по ссылкам в админке
    $('a').on('click', function(e) {
        if (hasChanges && !$(this).hasClass('nav-tab')) {
            var confirmLeave = confirm('У вас есть несохраненные изменения. Вы уверены, что хотите покинуть страницу?');
            if (!confirmLeave) {
                e.preventDefault();
                return false;
            }
        }
    });
    
    // Финальная проверка при загрузке страницы
    $(window).on('load', function() {
        // Убеждаемся, что все элементы инициализированы
        console.log('Beautiful Emails plugin loaded successfully');
        
        // Проверяем, есть ли сохраненные настройки
        if (be_ajax && be_ajax.saved_options) {
            console.log('Loaded options:', be_ajax.saved_options);
        }
        
        // Финальное обновление превью
        updatePreview();
    });
    
}); // End jQuery document ready