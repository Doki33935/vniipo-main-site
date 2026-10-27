<?php
/** Контракт импорта и редакторских блоков, только для изолированного стенда. */
if (!defined('ABSPATH') || !defined('WP_CLI') || !WP_CLI) { exit(1); }
if (!in_array(wp_get_environment_type(), array('local', 'development'), true)) { WP_CLI::error('Проверка разрешена только на локальном стенде.'); }
function vniipo_content_assert($condition, $message) { if (!$condition) { throw new RuntimeException($message); } }
$snapshot = vniipo_source_snapshot();
vniipo_content_assert(count(vniipo_source_news_records()) === $snapshot['archive_discovered'], 'Не перенесены карточки всех новостей');
vniipo_content_assert(post_type_exists('vniipo_block'), 'Нет типа информационных блоков');
vniipo_content_assert(WP_Block_Type_Registry::get_instance()->is_registered('vniipo/information-blocks'), 'Нет блока Gutenberg');
vniipo_content_assert(shortcode_exists('vniipo_block'), 'Нет shortcode вставки блока');

global $wpdb;
$before_count = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts}");
$plan = vniipo_import_source(false);
vniipo_content_assert((int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts}") === $before_count, 'Dry run изменил записи');
vniipo_content_assert(!$plan['created'] && !$plan['updated'] && !$plan['conflicts'] && !$plan['missing_pages'] && $plan['unchanged'] === count($snapshot['records']), 'Повторный импорт не идемпотентен');
$repeat = vniipo_import_source(true);
vniipo_content_assert(!$repeat['created'] && !$repeat['updated'] && !$repeat['conflicts'] && $repeat['unchanged'] === count($snapshot['records']), 'Повторное применение изменило импорт');
vniipo_content_assert((int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts}") === $before_count, 'Повторное применение создало дубли');

$record = vniipo_source_news_records()[0];
$post = vniipo_source_find_post($record);
vniipo_content_assert($post !== null, 'Новость не импортирована в WordPress');
$original = $post->post_content;
try {
    wp_update_post(wp_slash(array('ID' => $post->ID, 'post_content' => $original . '<p>Редакторская правка для проверки защиты.</p>')));
    $conflict = vniipo_import_source(true);
    vniipo_content_assert(count($conflict['conflicts']) === 1, 'Не обнаружена редакторская правка');
    vniipo_content_assert(strpos(get_post($post->ID)->post_content, 'Редакторская правка') !== false, 'Редакторская правка перезаписана');
} finally { wp_update_post(wp_slash(array('ID' => $post->ID, 'post_content' => $original))); }

$ids = array();
try {
    foreach (array(
        array('Тестовый опубликованный блок A', 'publish', 'home-top', '', 2),
        array('Тестовый опубликованный блок B', 'publish', 'home-top', '', 1),
        array('Тестовый черновик скрыт', 'draft', 'home-top', '', 0),
        array('Тестовый блок только филиала', 'publish', 'page', 'branch', 0),
    ) as $item) {
        $id = wp_insert_post(array('post_type' => 'vniipo_block', 'post_title' => $item[0], 'post_status' => $item[1], 'menu_order' => $item[4], 'post_content' => '<!-- wp:paragraph --><p>Текст пользовательского информационного блока.</p><!-- /wp:paragraph -->', 'meta_input' => array('_vniipo_block_area' => $item[2], '_vniipo_block_page' => $item[3])), true);
        vniipo_content_assert(!is_wp_error($id), 'Не удалось создать тестовый блок');
        $ids[] = $id;
    }
    $html = vniipo_information_blocks_html('home-top');
    vniipo_content_assert(strpos($html, 'блок B') < strpos($html, 'блок A'), 'Не соблюдён порядок блоков');
    vniipo_content_assert(strpos($html, 'черновик скрыт') === false, 'Показан неопубликованный блок');
    vniipo_content_assert(strpos(vniipo_information_blocks_html('page', 'branch'), 'только филиала') !== false, 'Нет блока выбранной страницы');
    vniipo_content_assert(strpos(vniipo_information_blocks_html('page', 'education'), 'только филиала') === false, 'Блок попал на чужую страницу');
    vniipo_content_assert(strpos(do_shortcode('[vniipo_block id="' . $ids[0] . '"]'), 'блок A') !== false, 'Не работает shortcode');
    vniipo_content_assert(strpos(do_blocks('<!-- wp:vniipo/information-blocks {"blockId":' . $ids[0] . '} /-->'), 'блок A') !== false, 'Не работает динамический блок Gutenberg');
    vniipo_content_assert(do_shortcode('[vniipo_block id="' . $ids[2] . '"]') === '', 'Shortcode раскрыл черновик');
} finally { foreach ($ids as $id) { wp_delete_post($id, true); } }

foreach (vniipo_structure()['pages'] as $definition) {
    $page = vniipo_page_by_key($definition['key']);
    vniipo_content_assert($page !== null, 'Пропала страница ' . $definition['key']);
    vniipo_content_assert(wp_parse_url(get_permalink($page), PHP_URL_PATH) === $definition['path'], 'Импорт изменил иерархию ' . $definition['key']);
}
WP_CLI::success('Импорт, отсутствие дублей, защита редакторских правок, иерархия, публикация/порядок/привязка блоков, shortcode и Gutenberg проверены.');
