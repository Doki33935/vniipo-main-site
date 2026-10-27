<?php
if (!defined('ABSPATH')) { exit; }

function vniipo_source_snapshot() {
    static $snapshot;
    if ($snapshot === null) {
        $path = VNIIPO_CORE_DIR . '/content/oren-source.json';
        $snapshot = is_file($path) ? json_decode(file_get_contents($path), true, 512, JSON_THROW_ON_ERROR) : array('records' => array(), 'media' => array(), 'links' => array());
    }
    return $snapshot;
}

function vniipo_source_page_record($key) {
    foreach (vniipo_source_snapshot()['records'] as $record) {
        if ($record['kind'] === 'page' && $record['key'] === $key) { return $record; }
    }
    return null;
}

function vniipo_source_news_records() {
    $records = array_values(array_filter(vniipo_source_snapshot()['records'], static function ($record) { return $record['kind'] === 'post'; }));
    usort($records, static function ($a, $b) { return strcmp($b['date'] ?? '', $a['date'] ?? ''); });
    return $records;
}

function vniipo_source_media_url($url) {
    $media = vniipo_source_snapshot()['media'][$url] ?? null;
    if ($media && ($media['status'] ?? '') === 'local' && preg_match('/^[a-f0-9]{20}\.(jpg|jpeg|png|gif|webp)$/', $media['file'])) {
        return plugins_url('vniipo-core/content/source-media/' . $media['file']);
    }
    return $url;
}

function vniipo_source_html($record) {
    $html = $record['html'];
    $replacements = array();
    foreach (vniipo_source_snapshot()['media'] as $url => $media) { $replacements[esc_url($url)] = esc_url(vniipo_source_media_url($url)); }
    foreach (vniipo_source_snapshot()['links'] as $url => $link) {
        $destination = function_exists('vniipo_preview_is_active') && vniipo_preview_is_active() ? vniipo_preview_url($link['path']) : vniipo_url($link['key']);
        foreach (array($url, preg_replace('~^https:~', 'http:', $url)) as $variant) {
            $replacements['href="' . esc_url($variant) . '"'] = 'href="' . esc_url($destination) . '"';
        }
    }
    $html = wp_kses_post(strtr($html, $replacements));
    if (($record['key'] ?? '') === 'contact-info') {
        $html .= '<p class="vn-source-note">На исходной странице подпись email of@vniipo.ru отличается от адреса ссылки of.vniipo@mail.ru. Адрес для отправки писем требует подтверждения владельцем сайта. Адреса в контактах и реквизитах сохранены с исходными подписями.</p>';
    }
    return $html;
}

function vniipo_source_note($record) {
    $date = substr(vniipo_source_snapshot()['fetched_at'] ?? '', 0, 10);
    return '<p class="vn-source-note">Источник: <a href="' . esc_url($record['source_url']) . '">oren.vniipo.ru</a>. Снимок от ' . esc_html($date) . '. Полный материал и исходные приложения доступны на сайте источника.</p>';
}

add_filter('the_content', static function ($content) {
    if (!function_exists('vniipo_preview_is_active') || !vniipo_preview_is_active()) { return $content; }
    $links = array();
    foreach (vniipo_structure()['pages'] as $page) {
        $links['href="' . esc_url(home_url($page['path'])) . '"'] = 'href="' . esc_url(vniipo_preview_url($page['path'])) . '"';
    }
    return strtr($content, $links);
}, 12);

function vniipo_render_source_page($key) {
    $record = vniipo_source_page_record($key);
    if (!$record) { return false; }
    echo vniipo_source_html($record) . vniipo_source_note($record);
    return true;
}

function vniipo_render_contact_summary() {
    $contacts = vniipo_source_snapshot()['contacts'] ?? array();
    if (!$contacts) { return false; }
    if (!empty($contacts['department_address'])) { echo '<p><strong>Научно-исследовательский отдел</strong><br>' . esc_html($contacts['department_address']) . '</p>'; }
    foreach ($contacts['phones'] ?? array() as $phone) { echo '<p class="vn-contact-phone"><a href="tel:' . esc_attr(preg_replace('/[^+0-9]/', '', $phone)) . '">' . esc_html($phone) . '</a></p>'; }
    if (!empty($contacts['hours'])) { echo '<p>' . esc_html($contacts['hours']) . '</p>'; }
    return true;
}

function vniipo_render_source_blocks($area) {
    foreach (vniipo_source_snapshot()['records'] as $record) {
        if ($record['kind'] !== 'block' || $record['area'] !== $area) { continue; }
        $existing = get_posts(array('post_type' => 'vniipo_block', 'post_status' => array_values(get_post_stati()), 'meta_key' => '_vniipo_source_uid', 'meta_value' => $record['uid'], 'posts_per_page' => 1, 'fields' => 'ids'));
        if ($existing) { continue; }
        echo '<section class="vn-information-block"><h2>' . esc_html($record['title']) . '</h2><div class="vn-information-block-content">' . vniipo_source_html($record) . vniipo_source_note($record) . '</div></section>';
    }
}

function vniipo_source_news_fallback() {
    static $fallback;
    if ($fallback === null) {
        $imported = get_posts(array('post_type' => 'post', 'post_status' => array_values(get_post_stati()), 'meta_key' => '_vniipo_source_uid', 'posts_per_page' => 1, 'fields' => 'ids', 'no_found_rows' => true));
        $fallback = !$imported && count(vniipo_source_news_records()) > 0;
    }
    return $fallback;
}

function vniipo_render_source_news($count = 6, $paged = 1) {
    $records = vniipo_source_news_records();
    echo '<div class="vn-news-grid">';
    foreach (array_slice($records, ($paged - 1) * $count, $count) as $record) {
        $url = $record['source_url'];
        echo '<article class="vn-news">';
        if (!empty($record['thumbnail'])) { echo '<a class="vn-news-image" href="' . esc_url($url) . '" tabindex="-1" aria-hidden="true"><img src="' . esc_url(vniipo_source_media_url($record['thumbnail'])) . '" loading="lazy" alt=""></a>'; }
        if (!empty($record['date'])) { echo '<time datetime="' . esc_attr($record['date']) . '">' . esc_html(wp_date('d.m.Y', strtotime($record['date']))) . '</time>'; }
        echo '<h3><a href="' . esc_url($url) . '">' . esc_html($record['title']) . ' ↗</a></h3></article>';
    }
    echo '</div>';
    if (count($records) > $count && $count === 12) { echo '<div class="vn-pager">' . wp_kses_post((string) paginate_links(array('total' => (int) ceil(count($records) / $count), 'current' => $paged))) . '</div>'; }
}

function vniipo_source_post_hash($post_id) {
    $post = get_post($post_id);
    return hash('sha256', wp_json_encode(array($post->post_title, $post->post_content, $post->post_excerpt, $post->post_status, $post->post_date, (int) get_post_thumbnail_id($post_id), $post->post_type === 'vniipo_block' ? get_post_meta($post_id, '_vniipo_block_area', true) : '', $post->post_type === 'vniipo_block' ? get_post_meta($post_id, '_vniipo_block_page', true) : '')));
}

function vniipo_source_find_post($record) {
    $type = array('page' => 'page', 'post' => 'post', 'block' => 'vniipo_block')[$record['kind']];
    $posts = get_posts(array('post_type' => $type, 'post_status' => array_values(get_post_stati()), 'meta_key' => '_vniipo_source_uid', 'meta_value' => $record['uid'], 'posts_per_page' => 2));
    if (count($posts) > 1) { throw new RuntimeException('Дублирующий source UID: ' . $record['uid']); }
    if ($posts) { return $posts[0]; }
    if ($type === 'page') {
        $pages = get_posts(array('post_type' => 'page', 'post_status' => array_values(get_post_stati()), 'meta_key' => '_vniipo_key', 'meta_value' => $record['key'], 'posts_per_page' => 2));
        if (count($pages) > 1) { throw new RuntimeException('Дублирующий ключ страницы: ' . $record['key']); }
        return $pages ? $pages[0] : null;
    }
    return null;
}

function vniipo_source_thumbnail($source_url) {
    if (!$source_url) { return 0; }
    $media = vniipo_source_snapshot()['media'][$source_url] ?? null;
    if (!$media || ($media['status'] ?? '') !== 'local' || !preg_match('/^[a-f0-9]{20}\.(jpg|jpeg|png|gif|webp)$/', $media['file'])) { return 0; }
    $existing = get_posts(array('post_type' => 'attachment', 'post_status' => 'inherit', 'meta_key' => '_vniipo_source_media', 'meta_value' => $media['sha256'], 'posts_per_page' => 1, 'fields' => 'ids'));
    if ($existing) { return (int) $existing[0]; }
    $path = VNIIPO_CORE_DIR . '/content/source-media/' . $media['file'];
    if (!is_file($path) || hash_file('sha256', $path) !== $media['sha256']) { throw new RuntimeException('Нарушена целостность изображения: ' . $media['file']); }
    $upload = wp_upload_bits($media['file'], null, file_get_contents($path));
    if ($upload['error']) { throw new RuntimeException($upload['error']); }
    $filetype = wp_check_filetype($upload['file']);
    if (strpos($filetype['type'] ?: '', 'image/') !== 0) { throw new RuntimeException('Некорректный MIME изображения.'); }
    $id = wp_insert_attachment(array('post_mime_type' => $filetype['type'], 'post_title' => pathinfo($media['file'], PATHINFO_FILENAME), 'post_status' => 'inherit', 'meta_input' => array('_vniipo_source_media' => $media['sha256'])), $upload['file'], 0, true);
    if (is_wp_error($id)) { throw new RuntimeException($id->get_error_message()); }
    require_once ABSPATH . 'wp-admin/includes/image.php';
    wp_update_attachment_metadata($id, wp_generate_attachment_metadata($id, $upload['file']));
    return (int) $id;
}

function vniipo_import_source($apply = false, $offset = 0, $limit = 0) {
    $all = vniipo_source_snapshot()['records'];
    $records = $limit ? array_slice($all, $offset, $limit) : array_slice($all, $offset);
    $result = array('total' => count($all), 'processed' => 0, 'created' => 0, 'updated' => 0, 'unchanged' => 0, 'conflicts' => array(), 'missing_pages' => array());
    $locked = false;
    if ($apply) {
        $locked = add_option('vniipo_source_import_lock', time(), '', false);
        if (!$locked && (int) get_option('vniipo_source_import_lock') < time() - 600) {
            delete_option('vniipo_source_import_lock');
            $locked = add_option('vniipo_source_import_lock', time(), '', false);
        }
        if (!$locked) { throw new RuntimeException('Другой импорт уже выполняется. При прерывании проверьте состояние блокировки перед повтором.'); }
    }
    try {
        foreach ($records as $record) {
            $result['processed']++;
            $post = vniipo_source_find_post($record);
            if ($record['kind'] === 'page' && !$post) { $result['missing_pages'][] = $record['key']; continue; }
            if ($post && !empty($record['source_unavailable']) && get_post_meta($post->ID, '_vniipo_source_complete', true) === '1') { $result['unchanged']++; continue; }
            if ($post) {
                $previous = get_post_meta($post->ID, '_vniipo_source_import_hash', true);
                if ($post->post_status === 'trash' || ($previous && !hash_equals($previous, vniipo_source_post_hash($post->ID))) || (!$previous && trim($post->post_content) !== '')) {
                    $result['conflicts'][] = array('id' => $post->ID, 'title' => $post->post_title, 'reason' => 'Материал изменён редактором или уже содержит независимый контент');
                    continue;
                }
            }
            $content = vniipo_source_html($record) . vniipo_source_note($record);
            $title = $record['kind'] === 'page' ? $post->post_title : $record['title'];
            $data = array('post_type' => array('page' => 'page', 'post' => 'post', 'block' => 'vniipo_block')[$record['kind']], 'post_title' => $title, 'post_content' => $content, 'post_status' => 'publish', 'post_excerpt' => $record['kind'] === 'post' ? $record['excerpt'] : '');
            if ($record['kind'] === 'post') {
                $data['post_name'] = $post ? $post->post_name : $record['slug'];
                if (empty($record['date']) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $record['date'])) { throw new RuntimeException('Нет подтверждённой даты новости: ' . $record['source_url']); }
                $data['post_date'] = $record['date'] . ' 00:00:00';
                $data['post_date_gmt'] = get_gmt_from_date($data['post_date']);
            }
            $thumbnail = 0;
            if ($apply && $record['kind'] === 'post') { $thumbnail = vniipo_source_thumbnail($record['thumbnail'] ?? ''); }
            $same_thumbnail = !$thumbnail || ($post && (int) get_post_thumbnail_id($post->ID) === $thumbnail);
            $same_date = $record['kind'] !== 'post' || ($post && $post->post_date === $data['post_date']);
            if ($post && $post->post_content === $content && $post->post_title === $title && $post->post_excerpt === $data['post_excerpt'] && $same_thumbnail && $same_date) { $result['unchanged']++; continue; }
            if ($post) { $data['ID'] = $post->ID; $result['updated']++; }
            else { $result['created']++; }
            if (!$apply) { continue; }
            $id = $post ? wp_update_post(wp_slash($data), true) : wp_insert_post(wp_slash($data), true);
            if (is_wp_error($id)) { throw new RuntimeException($id->get_error_message()); }
            update_post_meta($id, '_vniipo_source_uid', $record['uid']);
            update_post_meta($id, '_vniipo_source_url', esc_url_raw($record['source_url']));
            update_post_meta($id, '_vniipo_source_sha256', $record['source_sha256']);
            update_post_meta($id, '_vniipo_source_complete', empty($record['source_unavailable']) ? '1' : '0');
            if ($record['kind'] === 'block') { update_post_meta($id, '_vniipo_block_area', $record['area']); }
            if ($thumbnail) { set_post_thumbnail($id, $thumbnail); }
            update_post_meta($id, '_vniipo_source_import_hash', vniipo_source_post_hash($id));
        }
    } finally { if ($locked) { delete_option('vniipo_source_import_lock'); } }
    return $result;
}

function vniipo_source_admin_page() {
    if (!current_user_can('manage_options')) { return; }
    $snapshot = vniipo_source_snapshot();
    echo '<div class="wrap"><h1>Синхронизация с oren.vniipo.ru</h1><p>Снимок: ' . esc_html($snapshot['fetched_at'] ?? 'не подготовлен') . '. Материалов: ' . count($snapshot['records']) . '.</p><p>Проверка покажет план без изменения БД. Импорт добавляет новости и заполняет только пустые или ранее импортированные неизменённые страницы. Редакторские изменения сохраняются как конфликты.</p><p>Недостающие страницы создаются отдельно командой <code>wp vniipo scaffold</code> на новом сайте или staging. Основная тема и настройки главной при импорте не изменяются.</p>';
    echo '<p><button class="button" id="vniipo-source-plan">Проверить план</button> <button class="button button-primary" id="vniipo-source-apply">Импортировать снимок</button></p><pre id="vniipo-source-result" style="white-space:pre-wrap;max-width:1000px" aria-live="polite"></pre></div>';
    wp_enqueue_script('vniipo-source-admin', plugins_url('vniipo-core/assets/source-admin.js'), array(), '0.3.0', true);
    wp_add_inline_script('vniipo-source-admin', 'window.vniipoSourceImport=' . wp_json_encode(array('url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('vniipo_source_import'), 'total' => count($snapshot['records']))) . ';', 'before');
}
add_action('admin_menu', static function () { add_management_page('Синхронизация oren.vniipo.ru', 'Синхронизация oren.vniipo.ru', 'manage_options', 'vniipo-source', 'vniipo_source_admin_page'); });

add_action('wp_ajax_vniipo_import_source', static function () {
    if (!current_user_can('manage_options')) { wp_send_json_error(array('message' => 'Требуются права администратора.'), 403); }
    check_ajax_referer('vniipo_source_import', 'nonce');
    try {
        $apply = isset($_POST['apply']) && $_POST['apply'] === '1';
        $offset = absint($_POST['offset'] ?? 0);
        wp_send_json_success(vniipo_import_source($apply, $offset, 10));
    } catch (Throwable $error) { wp_send_json_error(array('message' => $error->getMessage()), 400); }
});
