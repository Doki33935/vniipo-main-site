<?php
if (!defined('ABSPATH') || !defined('WP_CLI') || !WP_CLI) { exit(1); }
if (!in_array(wp_get_environment_type(), array('local', 'development'), true)) { WP_CLI::error('Только локальные проверки.'); }
function vniipo_editorial_assert($condition, $message) { if (!$condition) { throw new RuntimeException($message); } }
vniipo_editorial_assert(post_type_exists('vniipo_album') && taxonomy_exists('vniipo_document_category'), 'Нет альбомов/категорий документов');
vniipo_editorial_assert(count(vniipo_gallery_items()) === 3, 'Нет трёх исходных фотографий полигона');
vniipo_editorial_assert(vniipo_valid_date('2026-02-29') === '' && vniipo_valid_date('2024-02-29') !== '', 'Не проверяется календарная дата');
vniipo_editorial_assert(vniipo_public_https('javascript:alert(1)') === '' && vniipo_public_https('http://example.com/') === '', 'Некорректный протокол ссылки');
vniipo_editorial_assert(vniipo_sanitize_image_ids(array()) === '', 'Неверный тип списка изображений');
$bad = vniipo_sanitize_site_settings(array('email' => 'invalid', 'map_url' => 'https://example.com/map', 'vk_url' => 'javascript:alert(1)', 'address' => '<script>test</script>Оренбург'));
vniipo_editorial_assert($bad['email'] === '' && $bad['map_url'] === '' && $bad['vk_url'] === '' && strpos($bad['address'], '<script>') === false, 'Настройки не очищены');
$good = vniipo_sanitize_site_settings(array('map_url' => 'https://yandex.ru/maps/', 'vk_url' => 'https://vk.com/'));
vniipo_editorial_assert($good['map_url'] !== '' && $good['vk_url'] !== '', 'Не принимаются корректные ссылки');

$user = wp_get_current_user();
$admins = get_users(array('role' => 'administrator', 'number' => 1));
wp_set_current_user($admins[0]->ID);
$original_post = $_POST;
$ids = array();
try {
    $doc = wp_insert_post(array('post_type' => 'vniipo_document', 'post_status' => 'draft', 'post_title' => 'Проверка реквизитов документа'), true);
    $ids[] = $doc;
    $_POST = array('vniipo_editorial_nonce' => wp_create_nonce('vniipo_editorial'), '_vniipo_document_number' => 'ТЕСТ-42', '_vniipo_document_date' => '2026-10-28', '_vniipo_document_version' => '1.0', '_vniipo_document_source' => 'https://oren.vniipo.ru/');
    vniipo_save_editorial($doc);
    vniipo_editorial_assert(get_post_meta($doc, '_vniipo_document_number', true) === 'ТЕСТ-42' && get_post_meta($doc, '_vniipo_document_date', true) === '2026-10-28', 'Поля документа не сохранены');
    $_POST['vniipo_editorial_nonce'] = 'incorrect';
    $_POST['_vniipo_document_number'] = 'ATTEMPT';
    vniipo_save_editorial($doc);
    vniipo_editorial_assert(get_post_meta($doc, '_vniipo_document_number', true) === 'ТЕСТ-42', 'Нет защиты nonce');
    $registered = get_registered_meta_keys('post', 'vniipo_document');
    vniipo_editorial_assert(!empty($registered['_vniipo_document_version']['revisions_enabled']), 'Нет ревизий полей документа');
    ob_start(); vniipo_render_document_details($doc); $html = ob_get_clean();
    vniipo_editorial_assert(strpos($html, 'ТЕСТ-42') !== false && strpos($html, 'Официальный источник') !== false, 'Реквизиты не выводятся');
    $photos = get_posts(array('post_type' => 'attachment', 'post_status' => 'inherit', 'post_mime_type' => 'image', 'posts_per_page' => 2, 'fields' => 'ids', 'lang' => ''));
    $album = wp_insert_post(array('post_type' => 'vniipo_album', 'post_status' => 'draft', 'post_title' => 'Проверка фотоальбома', 'meta_input' => array('_vniipo_gallery_images' => implode(',', array_merge($photos, $photos)))), true);
    $ids[] = $album;
    vniipo_editorial_assert(count(vniipo_gallery_items($album)) === count($photos), 'Не устранены дубли фотографий');
    ob_start(); vniipo_render_gallery($album); $html = ob_get_clean();
    vniipo_editorial_assert(strpos($html, 'data-vn-gallery') !== false && strpos($html, 'data-gallery-next') !== false, 'Нет управления галереей');
} finally {
    $_POST = $original_post;
    foreach ($ids as $id) { wp_delete_post($id, true); }
    wp_set_current_user($user->ID);
}
WP_CLI::success('Документы, даты, ревизии, nonce, очистка настроек, исходная галерея и альбомы проверены.');
