<?php
/** Создание/удаление только собственных данных smoke test в пустом локальном WordPress. */
if (!defined('ABSPATH') || !defined('WP_CLI') || !WP_CLI) { exit(1); }
if (!in_array(wp_get_environment_type(), array('local', 'development'), true)) { WP_CLI::error('Fixtures разрешены только на локальном стенде.'); }
$fixtures = get_posts(array('post_type' => array('conference', 'post', 'vniipo_document', 'vniipo_faq'), 'post_status' => 'any', 'meta_key' => '_vniipo_test_fixture', 'meta_value' => '1', 'posts_per_page' => -1));
if (($args[0] ?? '') === 'cleanup') {
    global $wpdb;
    foreach ($fixtures as $fixture) {
        if ($fixture->post_type === 'conference') { $wpdb->delete(fyremezzonine_manager_activity_table_name(), array('conference_id' => $fixture->ID), array('%d')); }
        wp_delete_post($fixture->ID, true);
    }
    WP_CLI::success('Fixtures удалены.');
    return;
}
if ($fixtures) { WP_CLI::success('Fixtures уже существуют.'); return; }
if (get_posts(array('post_type' => 'conference', 'post_status' => 'any', 'posts_per_page' => 1))) { WP_CLI::error('Для fixtures требуется стенд без конференций.'); }
$id = wp_insert_post(array('post_type' => 'conference', 'post_status' => 'publish', 'post_name' => 'vniipo-integration-check', 'post_title' => 'Интеграционная проверка конференции', 'post_content' => '<p>Только тестовый материал для проверки наследуемой карточки и формы регистрации.</p>', 'meta_input' => array('_vniipo_test_fixture' => '1', '_conference_start_date' => '2099-10-27', '_conference_end_date' => '2099-10-28', '_conference_registration_deadline' => '2099-10-26')), true);
if (is_wp_error($id)) { WP_CLI::error($id->get_error_message()); }
foreach (array('vniipo_document' => 'Проверка публикации документа', 'vniipo_faq' => 'Проверка вопроса и ответа') as $type => $title) {
    $result = wp_insert_post(array('post_type' => $type, 'post_status' => 'publish', 'post_title' => $title, 'post_content' => '<p>Содержимое тестового материала.</p>', 'meta_input' => array('_vniipo_test_fixture' => '1')), true);
    if (is_wp_error($result)) { WP_CLI::error($result->get_error_message()); }
}
for ($i = 1; $i <= 13; $i++) {
    $result = wp_insert_post(array('post_type' => 'post', 'post_status' => 'publish', 'post_title' => sprintf('Проверка новости %02d', $i), 'post_content' => '<p>Тестовое содержимое новости.</p>', 'meta_input' => array('_vniipo_test_fixture' => '1')), true);
    if (is_wp_error($result)) { WP_CLI::error($result->get_error_message()); }
}
WP_CLI::success('Создана тестовая конференция: ' . get_permalink($id));
