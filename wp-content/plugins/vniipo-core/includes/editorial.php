<?php
if (!defined('ABSPATH')) { exit; }

function vniipo_editorial_fields($type) {
    return $type === 'vniipo_document' ? array(
        '_vniipo_document_number' => array('Номер документа', 'text'),
        '_vniipo_document_date' => array('Дата документа', 'date'),
        '_vniipo_document_version' => array('Редакция / версия', 'text'),
        '_vniipo_document_file' => array('Файл документа (ID из медиабиблиотеки)', 'number'),
        '_vniipo_document_source' => array('Официальный источник (https)', 'url'),
    ) : array('_vniipo_gallery_images' => array('Изображения (ID через запятую)', 'text'));
}

function vniipo_sanitize_image_ids($value) {
    if (!is_scalar($value)) { return ''; }
    $ids = array_unique(array_filter(array_map('absint', preg_split('/[\s,]+/', (string) $value))));
    return implode(',', array_filter($ids, 'wp_attachment_is_image'));
}

function vniipo_valid_date($value) {
    if (!is_scalar($value)) { return ''; }
    if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', (string) $value, $parts)) { return ''; }
    return checkdate((int) $parts[2], (int) $parts[3], (int) $parts[1]) ? $value : '';
}

function vniipo_public_https($value) {
    if (!is_scalar($value)) { return ''; }
    $url = esc_url_raw((string) $value, array('https'));
    return wp_parse_url($url, PHP_URL_SCHEME) === 'https' && wp_parse_url($url, PHP_URL_HOST) ? $url : '';
}

add_action('init', static function () {
    register_post_type('vniipo_album', array(
        'labels' => array('name' => 'Фотоальбомы', 'singular_name' => 'Фотоальбом', 'add_new_item' => 'Добавить фотоальбом'),
        'public' => true, 'show_in_rest' => true, 'has_archive' => false,
        'rewrite' => array('slug' => 'photo-album'),
        'supports' => array('title', 'editor', 'excerpt', 'thumbnail', 'revisions', 'custom-fields'),
        'menu_icon' => 'dashicons-format-gallery',
    ));
    register_taxonomy('vniipo_document_category', 'vniipo_document', array(
        'labels' => array('name' => 'Разделы документов', 'singular_name' => 'Раздел документов'),
        'hierarchical' => true, 'show_ui' => true, 'show_in_rest' => true, 'public' => false,
        'rewrite' => false,
    ));
    foreach (array('vniipo_document', 'vniipo_album') as $type) {
        foreach (vniipo_editorial_fields($type) as $key => $field) {
            $sanitize = $key === '_vniipo_gallery_images' ? 'vniipo_sanitize_image_ids' : ($field[1] === 'date' ? 'vniipo_valid_date' : ($field[1] === 'url' ? 'vniipo_public_https' : ($field[1] === 'number' ? 'absint' : 'sanitize_text_field')));
            register_post_meta($type, $key, array('type' => $field[1] === 'number' ? 'integer' : 'string', 'single' => true, 'show_in_rest' => true, 'revisions_enabled' => true, 'sanitize_callback' => $sanitize, 'auth_callback' => static function ($allowed, $meta, $id) { return current_user_can('edit_post', $id); }));
        }
        add_action('add_meta_boxes_' . $type, static function ($post) {
            add_meta_box('vniipo-editorial', $post->post_type === 'vniipo_album' ? 'Фотографии альбома' : 'Реквизиты и файл документа', 'vniipo_editorial_meta_box', $post->post_type, 'normal');
        });
        add_action('save_post_' . $type, 'vniipo_save_editorial');
    }
}, 20);

function vniipo_editorial_meta_box($post) {
    wp_nonce_field('vniipo_editorial', 'vniipo_editorial_nonce');
    foreach (vniipo_editorial_fields($post->post_type) as $key => $field) {
        echo '<p><label for="' . esc_attr($key) . '"><strong>' . esc_html($field[0]) . '</strong></label><br><input class="widefat" id="' . esc_attr($key) . '" name="' . esc_attr($key) . '" type="' . esc_attr($field[1]) . '" value="' . esc_attr(get_post_meta($post->ID, $key, true)) . '"></p>';
        if (in_array($key, array('_vniipo_document_file', '_vniipo_gallery_images'), true)) {
            echo '<button type="button" class="button vniipo-media-picker" data-target="' . esc_attr($key) . '" data-multiple="' . ($post->post_type === 'vniipo_album' ? '1' : '0') . '">Выбрать из медиабиблиотеки</button>';
        }
    }
    echo '<p>Замена файла сохраняет адрес карточки документа. Предыдущие значения полей входят в ревизии WordPress. Для альбома порядок ID определяет порядок фотографий; подписи берутся из медиабиблиотеки.</p>';
    wp_enqueue_media();
    wp_enqueue_script('vniipo-editorial', plugins_url('vniipo-core/assets/editorial.js'), array('jquery', 'media-views'), '0.4.0', true);
}

function vniipo_save_editorial($id) {
    if (wp_is_post_revision($id) || wp_is_post_autosave($id) || !current_user_can('edit_post', $id)) { return; }
    if (!isset($_POST['vniipo_editorial_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['vniipo_editorial_nonce'])), 'vniipo_editorial')) { return; }
    foreach (vniipo_editorial_fields(get_post_type($id)) as $key => $field) {
        if (isset($_POST[$key])) { update_post_meta($id, $key, wp_unslash($_POST[$key])); }
    }
}

function vniipo_render_document_details($id) {
    if (get_post_type($id) !== 'vniipo_document') { return; }
    $details = array();
    foreach (array('_vniipo_document_number' => __('Номер', 'vniipo'), '_vniipo_document_date' => __('Дата документа', 'vniipo'), '_vniipo_document_version' => __('Редакция', 'vniipo')) as $key => $label) {
        $value = get_post_meta($id, $key, true);
        if ($value !== '') { $details[] = '<div><dt>' . esc_html($label) . '</dt><dd>' . esc_html($value) . '</dd></div>'; }
    }
    if ($details) { echo '<dl class="vn-document-details">' . implode('', $details) . '</dl>'; }
    $file_id = (int) get_post_meta($id, '_vniipo_document_file', true);
    $file = $file_id && get_post_type($file_id) === 'attachment' ? wp_get_attachment_url($file_id) : false;
    if ($file) {
        $path = get_attached_file($file_id);
        $size = $path && is_file($path) ? size_format(filesize($path)) : '';
        $extension = strtoupper(pathinfo(wp_parse_url($file, PHP_URL_PATH), PATHINFO_EXTENSION));
        echo '<p><a class="vn-button" href="' . esc_url($file) . '">' . esc_html__('Скачать документ', 'vniipo') . ' <span>' . esc_html(trim($extension . ($size ? ' · ' . $size : ''))) . '</span></a></p>';
    }
    $source = vniipo_public_https(get_post_meta($id, '_vniipo_document_source', true));
    if ($source) { echo '<p><a href="' . esc_url($source) . '" rel="noopener">' . esc_html__('Официальный источник', 'vniipo') . ' ↗</a></p>'; }
}

function vniipo_gallery_items($album = 0) {
    if ($album) {
        $result = array();
        foreach (explode(',', get_post_meta($album, '_vniipo_gallery_images', true)) as $id) {
            if (!wp_attachment_is_image((int) $id)) { continue; }
            $result[] = array('url' => wp_get_attachment_image_url((int) $id, 'large'), 'alt' => get_post_meta((int) $id, '_wp_attachment_image_alt', true), 'caption' => wp_get_attachment_caption((int) $id));
        }
        return $result;
    }
    $record = vniipo_source_page_record('testing-ground');
    if (!$record) { return array(); }
    preg_match_all('/<img\b[^>]*src="([^"]+)"/i', $record['html'], $images);
    return array_map(static function ($url) { return array('url' => vniipo_source_media_url(html_entity_decode($url, ENT_QUOTES, 'UTF-8')), 'alt' => __('Испытательный полигон', 'vniipo'), 'caption' => __('Испытательный полигон', 'vniipo')); }, array_unique($images[1]));
}

function vniipo_render_gallery($album = 0) {
    $images = vniipo_gallery_items($album);
    if (!$images) { return; }
    static $sequence = 0;
    $gallery_id = 'vn-gallery-' . ++$sequence;
    echo '<div class="vn-gallery" data-vn-gallery role="region" aria-roledescription="' . esc_attr__('Карусель', 'vniipo') . '" aria-label="' . esc_attr($album ? get_the_title($album) : __('Виды полигона', 'vniipo')) . '" tabindex="0"><div class="vn-gallery-slides" id="' . esc_attr($gallery_id) . '">';
    foreach ($images as $index => $image) {
        echo '<figure class="vn-gallery-slide" role="group" aria-label="' . esc_attr(sprintf(__('%1$d из %2$d', 'vniipo'), $index + 1, count($images))) . '"><img src="' . esc_url($image['url']) . '" loading="lazy" alt="' . esc_attr($image['alt']) . '"><figcaption>' . esc_html($image['caption']) . '</figcaption></figure>';
    }
    echo '</div><div class="vn-gallery-controls" hidden><button type="button" data-gallery-prev aria-controls="' . esc_attr($gallery_id) . '">' . esc_html__('Предыдущее фото', 'vniipo') . '</button><span data-gallery-status aria-live="polite"></span><button type="button" data-gallery-next aria-controls="' . esc_attr($gallery_id) . '">' . esc_html__('Следующее фото', 'vniipo') . '</button></div></div>';
}

function vniipo_render_media_archive() {
    $albums = new WP_Query(array('post_type' => 'vniipo_album', 'post_status' => 'publish', 'posts_per_page' => 12, 'paged' => max(1, (int) get_query_var('paged'))));
    if ($albums->have_posts()) {
        echo '<div class="vn-news-grid">';
        while ($albums->have_posts()) { $albums->the_post(); get_template_part('template-parts/news-card'); }
        echo '</div><div class="vn-pager">' . wp_kses_post((string) paginate_links(array('total' => $albums->max_num_pages))) . '</div>';
        wp_reset_postdata();
    }
    echo '<h2>' . esc_html__('Виды полигона', 'vniipo') . '</h2>';
    vniipo_render_gallery();
    echo '<p class="vn-source-note"><a href="https://oren.vniipo.ru/poligon/">' . esc_html__('Источник фотографий: oren.vniipo.ru', 'vniipo') . '</a></p>';
}
