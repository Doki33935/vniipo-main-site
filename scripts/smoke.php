<?php
/** Запускается только через wp eval-file на локальном стенде. */
if (!defined('ABSPATH') || !defined('WP_CLI') || !WP_CLI) { exit(1); }
if (!in_array(wp_get_environment_type(), array('local', 'development'), true)) { WP_CLI::error('Smoke test разрешён только на локальном стенде.'); }
function vniipo_assert($condition, $message) { if (!$condition) { throw new RuntimeException($message); } }

vniipo_assert(get_stylesheet() === 'vniipo-main', 'Дочерняя тема не активна');
vniipo_assert(get_template() === 'fyremezzonine-wp-theme', 'Родительская тема не подключена');
vniipo_assert(get_locale() === 'ru_RU', 'Не задан русский язык сайта');
foreach (array('conference', 'vniipo_document', 'vniipo_faq', 'vniipo_banner') as $type) { vniipo_assert(post_type_exists($type), 'Не зарегистрирован тип ' . $type); }
foreach (array('conference_registration_form', 'conference_submission_form', 'conference_operational_summary') as $shortcode) { vniipo_assert(shortcode_exists($shortcode), 'Потерян shortcode ' . $shortcode); }
$first = vniipo_scaffold();
vniipo_assert($first['created'] === 0, 'Bootstrap не создал полную структуру');
$page = vniipo_page_by_key('branch');
vniipo_assert($page !== null, 'Нет страницы филиала');
$original = $page->post_content;
$sentinel = '<p>Содержимое редактора для проверки повторного запуска.</p>';
$front_page = get_option('page_on_front');
$menu_locations = get_theme_mod('nav_menu_locations');
try {
    wp_update_post(array('ID' => $page->ID, 'post_content' => $sentinel));
    $again = vniipo_scaffold();
    vniipo_assert($again['created'] === 0, 'Повторный запуск создал дубли');
    vniipo_assert(get_post($page->ID)->post_content === $sentinel, 'Перезаписан редакторский контент');
    vniipo_assert(get_option('page_on_front') === $front_page, 'Scaffold изменил главную без явного параметра');
    vniipo_assert(get_theme_mod('nav_menu_locations') === $menu_locations, 'Scaffold изменил назначенное меню');
} finally { wp_update_post(array('ID' => $page->ID, 'post_content' => $original)); }
$faq = vniipo_page_by_key('faq');
try {
    wp_update_post(array('ID' => $faq->ID, 'post_status' => 'trash'));
    $blocked = false;
    try { vniipo_scaffold(); } catch (RuntimeException $error) { $blocked = strpos($error->getMessage(), 'корзине') !== false; }
    vniipo_assert($blocked, 'Scaffold повторно создал страницу из корзины');
} finally { wp_update_post(array('ID' => $faq->ID, 'post_status' => 'publish', 'post_name' => $faq->post_name)); }
foreach (vniipo_structure()['pages'] as $item) {
    $p = vniipo_page_by_key($item['key']);
    vniipo_assert($p !== null, 'Нет страницы ' . $item['key']);
    $actual = wp_parse_url(get_permalink($p), PHP_URL_PATH);
    vniipo_assert($actual === $item['path'], 'Неверный URL ' . $item['key'] . ': ' . $actual);
}
vniipo_assert(get_post_type_archive_link('conference') === home_url('/conferences/'), 'Изменён архив конференций');
global $wpdb;
foreach (array('conference_registrations', 'conference_partner_requests', 'conference_activity_log') as $suffix) {
    $table = $wpdb->prefix . $suffix;
    vniipo_assert($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table)) === $table, 'Нет таблицы ' . $suffix);
}
vniipo_assert(locate_template('single-conference.php') === get_template_directory() . '/single-conference.php', 'Не наследуется шаблон конференции');
WP_CLI::success('Структура, повторный запуск, сохранение контента, наследование темы и контракт Conference Manager проверены.');
