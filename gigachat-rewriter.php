<?php
/**
 * Plugin Name: GigaChat Рерайт Постов
 * Plugin URI: https://github.com/elizarov-n906/gigachat-rewriter
 * Description: Массовый рерайт выбранных записей WordPress с использованием API GigaChat.
 * Version: 2.0.3
 * Author: Elizarov_n906
 * Author URI: https://github.com/elizarov-n906
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: gigachat-rewriter
 */

if (!defined('ABSPATH')) {
    exit;
}

// =============================================================================
// СТИЛИ И СКРИПТЫ
// =============================================================================
add_action('admin_enqueue_scripts', 'gcr_admin_assets');
function gcr_admin_assets($hook) {
    if ($hook !== 'dashboard_page_gcr-settings') return;

    wp_enqueue_style('gcr-admin-style', false);
    wp_add_inline_style('gcr-admin-style', '
        body.dashboard_page_gcr-settings #wpcontent { padding-left: 0 !important; }
        body.dashboard_page_gcr-settings .wrap { margin: 0 !important; padding: 0 !important; background: #f0f0f1; min-height: 100vh; }
        .smartypress-ai-header { background: #252d32; border-bottom: 1px solid rgba(255,255,255,.1); padding: 1rem 2rem; margin: 0 0 20px 0; }
        .smartypress-ai-header-content { display: flex; align-items: center; gap: .5rem; justify-content: space-between; }
        .smartypress-ai-header-info { display: flex; align-items: baseline; gap: .75rem; flex: 1; }
        .smartypress-ai-header-title { margin: 0; font-size: 20px; font-weight: 600; color: #fff; line-height: 1.4; }
        .smartypress-ai-header-version { font-size: 13px; color: rgba(255,255,255,0.6); font-weight: 400; }
        .smartypress-ai-header-actions { display: flex; align-items: center; gap: .5rem; margin-left: auto; }
        .smartypress-ai-header-button { display: inline-flex; align-items: center; gap: .5rem; padding: .5rem 1rem; background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,.2); border-radius: 4px; color: #fff; text-decoration: none; font-size: 14px; font-weight: 500; transition: all .2s ease; cursor: pointer; }
        .smartypress-ai-header-button:hover { background: rgba(255,255,255,0.2); border-color: rgba(255,255,255,.3); color: #fff; text-decoration: none; }
        .smartypress-ai-admin-main { display: flex; flex: 1; overflow: hidden; padding: 0 20px 20px; }
        .smartypress-ai-admin-content { flex: 1; width: 100%; overflow-y: auto; max-width: 1400px; margin: 0 auto; }
        .spai-alt-layout { display: grid; grid-template-columns: minmax(0, 1fr); gap: 24px; align-items: start; }
        .spai-alt-table-card { background: #fff; border: 1px solid #c3c4c7; border-radius: 4px; overflow: visible; margin-bottom: 24px; }
        .spai-alt-toolbar { display: flex; justify-content: space-between; gap: 16px; padding: 16px; border-bottom: 1px solid #c3c4c7; align-items: center; }
        .spai-alt-toolbar h2 { margin: 0; font-size: 16px; font-weight: 600; }
        .spai-alt-table { border: 0; width: 100%; border-collapse: collapse; }
        .spai-alt-table th, .spai-alt-table td { padding: 12px 16px; vertical-align: middle; text-align: left; border-bottom: 1px solid #f0f0f1; }
        .spai-alt-table th { font-weight: 600; color: #1d2327; background: #f6f7f7; }
        .spai-alt-meta-badge { position: relative; display: inline-block; width: fit-content; padding: 4px 10px; border-radius: 4px; font-size: 12px; background: #f0f0f1; color: #50575e; cursor: default; white-space: nowrap; font-weight: 500; }
        .spai-alt-meta-badge.has-value { background: #edfaef; color: #008a20; }
        .spai-alt-meta-badge:hover .spai-alt-meta-tooltip, .spai-alt-meta-badge:focus-visible .spai-alt-meta-tooltip { visibility: visible; opacity: 1; transform: translate(-50%, 0); pointer-events: auto; }
        .spai-alt-meta-tooltip { position: absolute; z-index: 100; bottom: calc(100% + 8px); left: 50%; width: max-content; max-width: 360px; padding: 10px 12px; border-radius: 4px; background: #1d2327; box-shadow: 0 4px 12px rgba(0,0,0,0.18); color: #fff; font-size: 12px; font-weight: 400; line-height: 1.5; text-align: left; white-space: normal; visibility: hidden; opacity: 0; transform: translate(-50%, 4px); transition: opacity .15s ease, transform .15s ease; pointer-events: none; }
        .spai-alt-meta-tooltip:after { content: ""; position: absolute; top: 100%; left: 50%; margin-left: -5px; border-width: 5px; border-style: solid; border-color: #1d2327 transparent transparent transparent; }
        .smartypress-ai-footer { background: #f6f7f7; border-top: 1px solid #c3c4c7; padding: 1rem; margin-top: 2rem; text-align: center; color: #646970; font-size: 13px; }
        .form-table th { width: 180px; padding: 1rem 0; font-weight: 600; }
        .form-table td { padding: 1rem 0; }
    ');

    wp_localize_script('jquery', 'gcrData', [
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonce'   => wp_create_nonce('gcr_nonce')
    ]);
}

// =============================================================================
// ЛОГИРОВАНИЕ
// =============================================================================
function gcr_log($message, $data = null) {
    $upload_dir = wp_upload_dir();
    $log_file = $upload_dir['basedir'] . '/gcr-debug.log';
    
    $timestamp = date('Y-m-d H:i:s');
    $log_entry = "[{$timestamp}] {$message}\n";
    
    if ($data !== null) {
        if (is_array($data) || is_object($data)) {
            $log_entry .= "Данные: " . json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n";
        } else {
            $log_entry .= "Данные: " . (string)$data . "\n";
        }
    }
    
    $log_entry .= "---\n";
    file_put_contents($log_file, $log_entry, FILE_APPEND | LOCK_EX);
}

// =============================================================================
// АДМИНКА: ССЫЛКИ И МЕНЮ
// =============================================================================
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'gcr_add_settings_link');
function gcr_add_settings_link($links) {
    $settings_link = '<a href="' . esc_url(admin_url('admin.php?page=gcr-settings')) . '">Настройки</a>';
    array_unshift($links, $settings_link);
    return $links;
}

add_action('admin_menu', 'gcr_add_admin_menu');
function gcr_add_admin_menu() {
    add_submenu_page(
        'index.php',
        esc_html__('GigaChat Рерайт', 'gigachat-rewriter'),
        esc_html__('GigaChat Рерайт', 'gigachat-rewriter'),
        'manage_options',
        'gcr-settings',
        'gcr_render_page'
    );
}

// =============================================================================
// НАСТРОЙКИ
// =============================================================================
add_action('admin_init', 'gcr_register_settings');
function gcr_register_settings() {
    register_setting('gcr_options_group', 'gcr_auth_key', ['sanitize_callback' => 'sanitize_text_field']);
    register_setting('gcr_options_group', 'gcr_scope', ['default' => 'GIGACHAT_API_PERS', 'sanitize_callback' => 'sanitize_text_field']);
    register_setting('gcr_options_group', 'gcr_model', ['default' => 'GigaChat', 'sanitize_callback' => 'sanitize_text_field']);
    register_setting('gcr_options_group', 'gcr_prompt_title', ['sanitize_callback' => 'sanitize_textarea_field']);
    register_setting('gcr_options_group', 'gcr_prompt_content', ['sanitize_callback' => 'sanitize_textarea_field']);
}

// =============================================================================
// ОТРИСОВКА СТРАНИЦЫ
// =============================================================================
function gcr_render_page() {
    if (!current_user_can('manage_options')) return;

    if (isset($_POST['gcr_save_settings']) && check_admin_referer('gcr_settings_nonce')) {
        update_option('gcr_auth_key', sanitize_text_field(wp_unslash($_POST['gcr_auth_key'])));
        update_option('gcr_scope', sanitize_text_field(wp_unslash($_POST['gcr_scope'])));
        update_option('gcr_model', sanitize_text_field(wp_unslash($_POST['gcr_model'])));
        update_option('gcr_prompt_title', sanitize_textarea_field(wp_unslash($_POST['gcr_prompt_title'])));
        update_option('gcr_prompt_content', sanitize_textarea_field(wp_unslash($_POST['gcr_prompt_content'])));
        echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Настройки сохранены.', 'gigachat-rewriter') . '</p></div>';
    }

    $auth_key = get_option('gcr_auth_key', '');
    $scope = get_option('gcr_scope', 'GIGACHAT_API_PERS');
    $model = get_option('gcr_model', 'GigaChat');
    
    $default_prompt_title = __('Ты - редактор новостного портала. Перефразируй заголовок, сохранив главную суть.
ПРАВИЛА:
1. Верни ТОЛЬКО ОДИН заголовок.
2. ЗАПРЕЩЕНО писать "Вот заголовок" и т.д.
3. ЗАПРЕЩЕНО использовать Markdown или HTML.
4. Язык: русский.', 'gigachat-rewriter');

    $default_prompt_content = __('Ты - жесткий SEO-копирайтер. Сделай глубокий рерайт текста, сохранив 100% информационной плотности, фактов и деталей.
ЗАПРЕЩЕНО:
1. Делать саммари или краткий пересказ. Текст должен быть равен или больше оригинала.
2. ИЗМЕНЯТЬ URL-адреса в тегах <img>. Ты ОБЯЗАН скопировать каждый тег <img> ПОЛНОСТЬЮ, включая src, width и height, без изменения ни одного символа в ссылке.
3. Сливать текст в один блок. Сохраняй структуру абзацев через <p>.
4. Писать вступления типа "Краткий пересказ текста:" или "Резюме".
5. Использовать Markdown. Только чистый HTML.
ПРАВИЛА:
- Ответ должен начинаться СРАЗУ с <p> или <h2>.
- Сохрани подзаголовки (H2, H3), перефразировав их.
- Язык: русский.', 'gigachat-rewriter');

    $prompt_title = get_option('gcr_prompt_title', $default_prompt_title);
    $prompt_content = get_option('gcr_prompt_content', $default_prompt_content);

    $all_posts = get_posts([
        'post_type'      => 'post',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'orderby'        => 'date',
        'order'          => 'DESC',
    ]);
    
    $upload_dir = wp_upload_dir();
    $log_url = $upload_dir['baseurl'] . '/gcr-debug.log';
    ?>
    <div class="wrap elizarov_n906">
        <div class="smartypress-ai-header">
            <div class="smartypress-ai-header-content">
                <div class="smartypress-ai-header-info">
                    <div>
                        <h1 class="smartypress-ai-header-title"><?php esc_html_e('GigaChat Рерайт Постов', 'gigachat-rewriter'); ?></h1>
                        <span class="smartypress-ai-header-version">Версия 2.0.3</span>
                    </div>
                </div>
                <div class="smartypress-ai-header-actions">
                    <a href="<?php echo esc_url($log_url); ?>" target="_blank" class="smartypress-ai-header-button"><?php esc_html_e('Отладочный лог', 'gigachat-rewriter'); ?></a>
                    <a href="https://developers.sber.ru/studio" target="_blank" class="smartypress-ai-header-button">Sber AI Studio</a>
                </div>
            </div>
        </div>

        <div class="smartypress-ai-admin-main">
            <div class="smartypress-ai-admin-content">
                <div class="spai-alt-layout">
                    
                    <div class="spai-alt-table-card">
                        <div class="spai-alt-toolbar">
                            <h2><?php esc_html_e('Настройки подключения', 'gigachat-rewriter'); ?></h2>
                        </div>
                        <div style="padding: 20px;">
                            <form method="post">
                                <?php wp_nonce_field('gcr_settings_nonce'); ?>
                                <table class="form-table" style="margin:0;">
                                    <tr>
                                        <th><label for="gcr_auth_key"><?php esc_html_e('Authorization Key', 'gigachat-rewriter'); ?></label></th>
                                        <td><input type="text" id="gcr_auth_key" name="gcr_auth_key" value="<?php echo esc_attr($auth_key); ?>" class="regular-text" style="width: 100%; max-width: 500px;"></td>
                                    </tr>
                                    <tr>
                                        <th><label for="gcr_scope"><?php esc_html_e('Scope', 'gigachat-rewriter'); ?></label></th>
                                        <td>
                                            <select id="gcr_scope" name="gcr_scope" style="width: 100%; max-width: 500px;">
                                                <option value="GIGACHAT_API_PERS" <?php selected($scope, 'GIGACHAT_API_PERS'); ?>><?php esc_html_e('PERS (Физлицо)', 'gigachat-rewriter'); ?></option>
                                                <option value="GIGACHAT_API_B2B" <?php selected($scope, 'GIGACHAT_API_B2B'); ?>><?php esc_html_e('B2B (Бизнес)', 'gigachat-rewriter'); ?></option>
                                                <option value="GIGACHAT_API_CORP" <?php selected($scope, 'GIGACHAT_API_CORP'); ?>><?php esc_html_e('CORP (Корп)', 'gigachat-rewriter'); ?></option>
                                            </select>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th><label for="gcr_model"><?php esc_html_e('Модель', 'gigachat-rewriter'); ?></label></th>
                                        <td><input type="text" id="gcr_model" name="gcr_model" value="<?php echo esc_attr($model); ?>" class="regular-text" style="width: 100%; max-width: 500px;"></td>
                                    </tr>
                                    <tr>
                                        <th><label for="gcr_prompt_title"><?php esc_html_e('Промпт заголовка', 'gigachat-rewriter'); ?></label></th>
                                        <td><textarea id="gcr_prompt_title" name="gcr_prompt_title" rows="4" class="large-text" style="width: 100%; max-width: 500px;"><?php echo esc_textarea($prompt_title); ?></textarea></td>
                                    </tr>
                                    <tr>
                                        <th><label for="gcr_prompt_content"><?php esc_html_e('Промпт текста', 'gigachat-rewriter'); ?></label></th>
                                        <td><textarea id="gcr_prompt_content" name="gcr_prompt_content" rows="8" class="large-text" style="width: 100%; max-width: 500px;"><?php echo esc_textarea($prompt_content); ?></textarea></td>
                                    </tr>
                                </table>
                                <div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #c3c4c7;">
                                    <button type="submit" name="gcr_save_settings" class="button button-primary"><?php esc_html_e('Сохранить настройки', 'gigachat-rewriter'); ?></button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="spai-alt-table-card">
                        <div class="spai-alt-toolbar">
                            <h2><?php esc_html_e('Список записей для рерайта', 'gigachat-rewriter'); ?></h2>
                            <div style="display:flex;gap:8px;">
                                <button type="button" class="button" onclick="gcr_select_all()"><?php esc_html_e('Выбрать все', 'gigachat-rewriter'); ?></button>
                                <button type="button" class="button" onclick="gcr_deselect_all()"><?php esc_html_e('Снять выделение', 'gigachat-rewriter'); ?></button>
                                <button type="button" class="button button-primary" id="gcr-start-btn" onclick="gcr_start_batch()"><?php esc_html_e('Рерайт выбранных', 'gigachat-rewriter'); ?></button>
                            </div>
                        </div>
                        
                        <div style="padding: 0 16px 16px;">
                            <span id="gcr-status" style="display:block; margin: 16px 0; font-weight: 500; color: #2271b1;"></span>
                            
                            <table class="spai-alt-table">
                                <thead>
                                    <tr>
                                        <th width="30"><input type="checkbox" onclick="gcr_toggle_all(this)" /></th>
                                        <th>ID</th>
                                        <th><?php esc_html_e('Заголовок', 'gigachat-rewriter'); ?></th>
                                        <th><?php esc_html_e('Дата публикации', 'gigachat-rewriter'); ?></th>
                                        <th><?php esc_html_e('Дата обработки', 'gigachat-rewriter'); ?></th>
                                        <th><?php esc_html_e('Статистика токенов', 'gigachat-rewriter'); ?></th>
                                        <th><?php esc_html_e('Статус', 'gigachat-rewriter'); ?></th>
                                    </tr>
                                </thead>
                                <tbody id="gcr-posts-list">
                                    <?php foreach ($all_posts as $post): 
                                        $is_done = get_post_meta($post->ID, '_gcr_is_rewritten', true);
                                        $rewrite_date = get_post_meta($post->ID, '_gcr_rewritten_date', true);
                                        $token_stats = json_decode(get_post_meta($post->ID, '_gcr_token_stats', true), true);
                                        
                                        $date_display = $rewrite_date ? date_i18n('d.m.Y H:i', strtotime($rewrite_date)) : '<span style="color:#a7aaad">-</span>';
                                        
                                        $stats_display = '<span style="color:#a7aaad">-</span>';
                                        if ($is_done && $token_stats) {
                                            $stats_display = '<div class="spai-alt-meta-badge has-value">
                                                ' . number_format_i18n($token_stats['total']) . ' ' . _x('ток.', 'tokens abbreviation', 'gigachat-rewriter') . '
                                                <div class="spai-alt-meta-tooltip">
                                                    <strong>' . esc_html__('Входные (prompt):', 'gigachat-rewriter') . '</strong> ' . number_format_i18n($token_stats['prompt']) . '<br>
                                                    <strong>' . esc_html__('Выходные (completion):', 'gigachat-rewriter') . '</strong> ' . number_format_i18n($token_stats['completion']) . '<br>
                                                    <strong>' . esc_html__('Всего:', 'gigachat-rewriter') . '</strong> ' . number_format_i18n($token_stats['total']) . '
                                                </div>
                                            </div>';
                                        }
                                    ?>
                                    <tr id="row-<?php echo intval($post->ID); ?>">
                                        <td><input type="checkbox" class="gcr-post-check" value="<?php echo intval($post->ID); ?>"></td>
                                        <td style="color:#646970;font-size:12px;"><?php echo intval($post->ID); ?></td>
                                        <td><a href="<?php echo esc_url(get_edit_post_link($post->ID)); ?>" target="_blank" style="color:#2271b1;text-decoration:none;font-weight:500;"><?php echo esc_html($post->post_title); ?></a></td>
                                        <td style="font-size:13px;color:#50575e;"><?php echo get_the_date('d.m.Y', $post->ID); ?></td>
                                        <td style="font-size:13px;"><?php echo $date_display; ?></td>
                                        <td><?php echo $stats_display; ?></td>
                                        <td class="gcr-status-cell">
                                            <?php echo $is_done ? '<span style="color:#008a20;font-weight:500;">' . esc_html__('Готово', 'gigachat-rewriter') . '</span>' : '<span style="color:#646970;">' . esc_html__('Ожидание', 'gigachat-rewriter') . '</span>'; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                </div>
            </div>
        </div>

        <div class="smartypress-ai-footer">
            <?php printf(esc_html__('Автор плагина: %s', 'gigachat-rewriter'), 'elizarov_n906'); ?>
        </div>
    </div>

    <script>
    function gcr_toggle_all(source) {
        document.querySelectorAll('.gcr-post-check').forEach(cb => cb.checked = source.checked);
    }
    function gcr_select_all() {
        document.querySelectorAll('.gcr-post-check').forEach(cb => cb.checked = true);
    }
    function gcr_deselect_all() {
        document.querySelectorAll('.gcr-post-check').forEach(cb => cb.checked = false);
    }

    async function gcr_start_batch() {
        const checks = document.querySelectorAll('.gcr-post-check:checked');
        if(checks.length === 0) { alert('<?php echo esc_js(__('Выберите хотя бы одну запись!', 'gigachat-rewriter')); ?>'); return; }
        
        const btn = document.getElementById('gcr-start-btn');
        const status = document.getElementById('gcr-status');
        btn.disabled = true;
        let count = 0;

        for(const cb of checks) {
            const postId = cb.value;
            const row = document.getElementById('row-' + postId);
            const cell = row.querySelector('.gcr-status-cell');
            
            status.innerText = '<?php echo esc_js(__('Обработка', 'gigachat-rewriter')); ?>' + ' ' + (count + 1) + ' из ' + checks.length + '...';
            cell.innerHTML = '<span style="color:#dba617;font-weight:500;"><?php echo esc_js(__('В процессе...', 'gigachat-rewriter')); ?></span>';

            try {
                const formData = new FormData();
                formData.append('action', 'gcr_rewrite_single');
                formData.append('post_id', postId);
                formData.append('nonce', gcrData.nonce);

                const response = await fetch(gcrData.ajaxUrl, {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();

                if(result.success) {
                    cell.innerHTML = '<span style="color:#008a20;font-weight:500;"><?php echo esc_js(__('Успешно', 'gigachat-rewriter')); ?></span>';
                } else {
                    cell.innerHTML = '<span style="color:#b32d2e;font-weight:500;"><?php echo esc_js(__('Ошибка:', 'gigachat-rewriter')); ?>' + ' ' + (result.data || '<?php echo esc_js(__('Неизвестно', 'gigachat-rewriter')); ?>') + '</span>';
                }
            } catch(e) {
                cell.innerHTML = '<span style="color:#b32d2e;font-weight:500;"><?php echo esc_js(__('Сбой сети', 'gigachat-rewriter')); ?></span>';
            }
            
            count++;
            await new Promise(r => setTimeout(r, 2000));
        }

        status.innerText = '<?php echo esc_js(__('Готово! Перезагрузка страницы для обновления статистики...', 'gigachat-rewriter')); ?>';
        btn.disabled = false;
        setTimeout(() => { location.reload(); }, 1500);
    }
    </script>
    <?php
}

// =============================================================================
// AJAX HANDLER
// =============================================================================
add_action('wp_ajax_gcr_rewrite_single', 'gcr_handle_ajax');
function gcr_handle_ajax() {
    check_ajax_referer('gcr_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error(__('Нет прав доступа', 'gigachat-rewriter'));
    }

    $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
    if (!$post_id) {
        wp_send_json_error(__('ID записи не указан', 'gigachat-rewriter'));
    }

    $result = gcr_rewrite_single_post($post_id);
    
    if (is_wp_error($result)) {
        wp_send_json_error($result->get_error_message());
    }
    
    wp_send_json_success($result);
}

// =============================================================================
// УТИЛИТЫ ОЧИСТКИ
// =============================================================================
function gcr_clean_string($str) {
    return trim(preg_replace('/[^\x20-\x7E]/', '', $str));
}

function gcr_clean_response($text, $is_title = false) {
    $text = preg_replace('/<reasoning>.*?<\/reasoning>/is', '', $text);
    $text = preg_replace('/<think>.*?<\/think>/is', '', $text);
    $text = preg_replace('/^(```html|```|Краткий пересказ|Резюме|Вот переписанный|Конечно, вот).*?\n/im', '', $text);
    $text = preg_replace('/```\s*$/im', '', $text);

    if ($is_title) {
        $lines = explode("\n", trim($text));
        $text = trim($lines[0]);
        $text = str_replace(['#', '*', '`'], '', $text);
        $text = strip_tags($text);
    } else {
        $text = preg_replace('/^###\s+(.*)$/m', '<h3>$1</h3>', $text);
        $text = preg_replace('/^##\s+(.*)$/m', '<h2>$1</h2>', $text);
        $text = preg_replace('/\*\*(.*?)\*\*/', '<strong>$1</strong>', $text);
    }

    $text = preg_replace('/\n{3,}/', "\n\n", $text);
    return trim($text);
}

// =============================================================================
// ЛОГИКА API
// =============================================================================
function gcr_get_token() {
    $auth_key = gcr_clean_string(get_option('gcr_auth_key'));
    $scope = gcr_clean_string(get_option('gcr_scope', 'GIGACHAT_API_PERS'));

    if (empty($auth_key)) {
        return new WP_Error('missing_creds', __('Введите Authorization Key в настройках.', 'gigachat-rewriter'));
    }

    $rquid = wp_generate_uuid4();

    $response = wp_remote_post('https://ngw.devices.sberbank.ru:9443/api/v2/oauth', [
        'headers' => [
            'Authorization' => 'Basic ' . $auth_key,
            'RqUID'         => $rquid,
            'Content-Type'  => 'application/x-www-form-urlencoded',
        ],
        'body'    => ['scope' => $scope],
        'timeout' => 30,
        // sslverify оставляем false только если у пользователя действительно проблемы с сертификатами Сбера
        // В идеале нужно настроить системные CA, но для open-source это компромисс
        'sslverify' => false, 
    ]);

    if (is_wp_error($response)) {
        return new WP_Error('connection_failed', __('Ошибка соединения: ', 'gigachat-rewriter') . $response->get_error_message());
    }

    $http_code = wp_remote_retrieve_response_code($response);
    $body = json_decode(wp_remote_retrieve_body($response), true);

    if ($http_code !== 200) {
        $error_msg = isset($body['message']) ? $body['message'] : (isset($body['error_description']) ? $body['error_description'] : __('Неизвестная ошибка', 'gigachat-rewriter'));
        return new WP_Error('token_fail', sprintf('HTTP %d: %s', $http_code, $error_msg));
    }

    if (isset($body['access_token'])) {
        return $body['access_token'];
    }

    return new WP_Error('token_fail', __('Токен не найден в ответе.', 'gigachat-rewriter'));
}

function gcr_rewrite_single_post($post_id) {
    gcr_log("=== НАЧАЛО РЕРАЙТА ПОСТА ID: " . $post_id . " ===");

    $token = gcr_get_token();
    if (is_wp_error($token)) {
        gcr_log("ОШИБКА ТОКЕНА", $token->get_error_message());
        return $token;
    }

    $post = get_post($post_id);
    if (!$post) {
        gcr_log("ОШИБКА: Пост не найден");
        return new WP_Error('post_not_found', __('Пост не найден', 'gigachat-rewriter'));
    }
    
    gcr_log("Пост найден: " . $post->post_title);

    $prompt_title = get_option('gcr_prompt_title');
    $prompt_content = get_option('gcr_prompt_content');
    $model = get_option('gcr_model', 'GigaChat');
    
    gcr_log("Модель", $model);

    $update_data = ['ID' => $post->ID];
    $title_updated = false;
    $content_updated = false;
    
    $total_prompt_tokens = 0;
    $total_completion_tokens = 0;
    $total_tokens = 0;

    // 1. Рерайт заголовка
    if (!empty(trim($prompt_title))) {
        gcr_log("=== РЕРАЙТ ЗАГОЛОВКА ===");
        $response_title = wp_remote_post('https://gigachat.devices.sberbank.ru/api/v1/chat/completions', [
            'headers' => [
                'Authorization' => 'Bearer ' . $token,
                'Content-Type'  => 'application/json',
                'Accept'        => 'application/json',
            ],
            'body'    => json_encode([
                'model'       => $model,
                'messages'    => [
                    ['role' => 'user', 'content' => $prompt_title . "\n\n" . $post->post_title]
                ],
                'temperature' => 0.7,
            ]),
            'timeout'   => 60,
            'sslverify' => false,
        ]);

        if (!is_wp_error($response_title)) {
            $body_title = json_decode(wp_remote_retrieve_body($response_title), true);
            gcr_log("Ответ API заголовка (usage)", $body_title['usage'] ?? 'Нет данных');
            
            if (isset($body_title['choices'][0]['message']['content'])) {
                $new_title = gcr_clean_response($body_title['choices'][0]['message']['content'], true);
                if (!empty($new_title) && strlen($new_title) > 3) {
                    $update_data['post_title'] = $new_title;
                    $title_updated = true;
                    gcr_log("Заголовок обновлен", $new_title);
                }
            }
            if (isset($body_title['usage'])) {
                $total_prompt_tokens += $body_title['usage']['prompt_tokens'] ?? 0;
                $total_completion_tokens += $body_title['usage']['completion_tokens'] ?? 0;
                $total_tokens += $body_title['usage']['total_tokens'] ?? 0;
            }
        }
    }

    // 2. Рерайт контента
    if (!empty(trim($prompt_content))) {
        gcr_log("=== РЕРАЙТ КОНТЕНТА ===");
        gcr_log("Исходный контент длина", strlen($post->post_content));
        
        $response_content = wp_remote_post('https://gigachat.devices.sberbank.ru/api/v1/chat/completions', [
            'headers' => [
                'Authorization' => 'Bearer ' . $token,
                'Content-Type'  => 'application/json',
                'Accept'        => 'application/json',
            ],
            'body'    => json_encode([
                'model'       => $model,
                'messages'    => [
                    ['role' => 'user', 'content' => $prompt_content . "\n\n" . $post->post_content]
                ],
                'temperature' => 0.7,
            ]),
            'timeout'   => 90,
            'sslverify' => false,
        ]);

        if (is_wp_error($response_content)) {
            gcr_log("ОШИБКА API контента", $response_content->get_error_message());
            return new WP_Error('api_error', __('Ошибка контента: ', 'gigachat-rewriter') . $response_content->get_error_message());
        }

        $body_raw = wp_remote_retrieve_body($response_content);
        gcr_log("HTTP код контента", wp_remote_retrieve_response_code($response_content));
        
        $body_content = json_decode($body_raw, true);
        
        if (isset($body_content['choices'][0]['message']['content'])) {
            $raw_content = $body_content['choices'][0]['message']['content'];
            gcr_log("Получен контент от API, длина", strlen($raw_content));
            
            $new_content = gcr_clean_response($raw_content, false);
            
            if (!empty($new_content) && strlen($new_content) > 50) {
                $update_data['post_content'] = $new_content;
                $content_updated = true;
                gcr_log("Контент обновлен, длина", strlen($new_content));
            } else {
                gcr_log("ОШИБКА: Контент пустой после очистки");
            }
        } else {
            gcr_log("ОШИБКА: API не вернул choices[0].message.content");
        }
        
        if (isset($body_content['usage'])) {
            $total_prompt_tokens += $body_content['usage']['prompt_tokens'] ?? 0;
            $total_completion_tokens += $body_content['usage']['completion_tokens'] ?? 0;
            $total_tokens += $body_content['usage']['total_tokens'] ?? 0;
            gcr_log("Использование токенов (контент)", $body_content['usage']);
        }
    }

    // 3. Обновляем пост и сохраняем метаданные
    if ($title_updated || $content_updated) {
        gcr_log("=== ОБНОВЛЕНИЕ ПОСТА ===");
        $result = wp_update_post($update_data, true);
        
        if (is_wp_error($result)) {
            gcr_log("ОШИБКА ОБНОВЛЕНИЯ ПОСТА", $result->get_error_message());
            return new WP_Error('update_failed', __('Ошибка обновления: ', 'gigachat-rewriter') . $result->get_error_message());
        }
        
        update_post_meta($post->ID, '_gcr_is_rewritten', '1');
        update_post_meta($post->ID, '_gcr_rewritten_date', current_time('mysql'));
        
        $token_stats = [
            'prompt'     => $total_prompt_tokens,
            'completion' => $total_completion_tokens,
            'total'      => $total_tokens
        ];
        update_post_meta($post->ID, '_gcr_token_stats', json_encode($token_stats));
        
        gcr_log("Пост успешно обновлен. Токены: ", $token_stats);
        
        $result_msg = __('Успешно', 'gigachat-rewriter');
        if ($title_updated) $result_msg .= ' (' . __('Заголовок', 'gigachat-rewriter') . ')';
        if ($content_updated) $result_msg .= ' (' . __('Текст', 'gigachat-rewriter') . ')';
        
        return $result_msg;
    } else {
        gcr_log("ОШИБКА: Ничего не обновлено");
        return new WP_Error('nothing_updated', __('Ничего не обновлено. Проверьте промпты или ответ API.', 'gigachat-rewriter'));
    }
}