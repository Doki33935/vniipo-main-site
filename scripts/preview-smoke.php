<?php
/** Изолированный тест предпросмотра. stdout prepare содержит cookies: направлять только в .local-файл. */
if (!defined('ABSPATH') || !defined('WP_CLI') || !WP_CLI) { exit(1); }
if (!in_array(wp_get_environment_type(), array('local', 'development'), true)) { WP_CLI::error('Проверка разрешена только на локальном стенде.'); }
require_once ABSPATH . 'wp-admin/includes/plugin.php';
require_once ABSPATH . 'wp-admin/includes/user.php';

function vniipo_preview_test_snapshot() {
    global $wpdb;
    return array(
        'settings' => $wpdb->get_results("SELECT option_name, option_value FROM {$wpdb->options} WHERE option_name IN ('stylesheet','template','page_on_front','show_on_front') ORDER BY option_name", ARRAY_A),
        'posts' => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts}"),
        'postmeta' => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->postmeta}"),
        'registrations' => (int) $wpdb->get_var('SELECT COUNT(*) FROM ' . $wpdb->prefix . 'conference_registrations'),
    );
}

function vniipo_preview_test_cookies($user_id) {
    $expiration = time() + HOUR_IN_SECONDS;
    $token = WP_Session_Tokens::get_instance($user_id)->create($expiration);
    return array('token' => $token, 'header' => LOGGED_IN_COOKIE . '=' . wp_generate_auth_cookie($user_id, $expiration, 'logged_in', $token) . '; ' . AUTH_COOKIE . '=' . wp_generate_auth_cookie($user_id, $expiration, 'auth', $token));
}

$operation = $args[0] ?? '';
if ($operation === 'prepare') {
    $admin = get_users(array('role' => 'administrator', 'number' => 1));
    if (!$admin) { WP_CLI::error('Нужен локальный администратор.'); }
    $state = array('stylesheet' => get_stylesheet(), 'core_active' => is_plugin_active('vniipo-core/vniipo-core.php'), 'admin_id' => $admin[0]->ID);
    $login = 'vniipo_preview_test_' . strtolower(wp_generate_password(10, false, false));
    $editor_id = wp_insert_user(array('user_login' => $login, 'user_pass' => wp_generate_password(40), 'role' => 'editor'));
    if (is_wp_error($editor_id)) { WP_CLI::error($editor_id->get_error_message()); }
    update_user_meta($editor_id, '_vniipo_preview_test', '1');
    $state['editor_id'] = $editor_id;
    $state['admin'] = vniipo_preview_test_cookies($admin[0]->ID);
    $state['editor'] = vniipo_preview_test_cookies($editor_id);
    if ($state['core_active']) { deactivate_plugins('vniipo-core/vniipo-core.php', true); }
    switch_theme('fyremezzonine-wp-theme');
    $state['snapshot'] = vniipo_preview_test_snapshot();
    echo wp_json_encode($state);
    return;
}
if (!in_array($operation, array('assert', 'cleanup'), true) || empty($args[1])) { WP_CLI::error('Укажите prepare либо assert/cleanup и путь к локальному state JSON.'); }
$state = json_decode(file_get_contents($args[1]), true);
if (!is_array($state) || empty($state['snapshot'])) { WP_CLI::error('Некорректный state.'); }
if ($operation === 'assert') {
    if ($state['snapshot'] !== vniipo_preview_test_snapshot() || is_plugin_active('vniipo-core/vniipo-core.php')) { WP_CLI::error('Изменились настройки публичного сайта, страницы, метаданные или заявки.'); }
    WP_CLI::success('Публичная тема, главная, записи, метаданные и заявки сохранены; VNIIPO Core остаётся неактивным.');
    return;
}
WP_Session_Tokens::get_instance((int) $state['admin_id'])->destroy($state['admin']['token']);
if (get_user_meta((int) $state['editor_id'], '_vniipo_preview_test', true) === '1') { wp_delete_user((int) $state['editor_id']); }
if ($state['core_active']) { $result = activate_plugin('vniipo-core/vniipo-core.php', '', false, true); if (is_wp_error($result)) { WP_CLI::error($result->get_error_message()); } }
switch_theme($state['stylesheet']);
WP_CLI::success('Исходная локальная тема и VNIIPO Core восстановлены, тестовая сессия и пользователь удалены.');
