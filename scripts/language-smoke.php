<?php
if (!defined('ABSPATH') || !defined('WP_CLI') || !WP_CLI) { exit(1); }
if (!in_array(wp_get_environment_type(), array('local', 'development'), true)) { WP_CLI::error('Только локальный стенд.'); }
if (!function_exists('pll_get_post')) { WP_CLI::error('Нужен Polylang.'); }
$operation = $args[0] ?? '';
if ($operation === 'prepare') {
    $state = array('pages' => array());
    $seeds = get_posts(array('post_type' => 'page', 'post_status' => array('draft', 'publish'), 'lang' => '', 'meta_key' => '_vniipo_translation_original', 'posts_per_page' => -1));
    foreach ($seeds as $page) {
        $state['pages'][$page->ID] = $page->post_status;
        wp_update_post(array('ID' => $page->ID, 'post_status' => 'publish'));
    }
    $research = vniipo_page_by_key('research');
    $state['research'] = array('id' => $research->ID, 'template' => get_post_meta($research->ID, '_wp_page_template', true));
    update_post_meta($research->ID, '_wp_page_template', 'page-presentation.php');
    $state['homes'] = array();
    foreach (array('ru', 'en', 'zh-hant') as $language) {
        $id = pll_get_post(vniipo_page_by_key('home')->ID, $language);
        $state['homes'][$language] = get_permalink($id);
    }
    echo wp_json_encode($state, JSON_UNESCAPED_SLASHES);
    return;
}
if ($operation === 'cleanup' && !empty($args[1])) {
    $state = json_decode(file_get_contents($args[1]), true, 512, JSON_THROW_ON_ERROR);
    foreach ($state['pages'] as $id => $status) { wp_update_post(array('ID' => (int) $id, 'post_status' => $status)); }
    if ($state['research']['template'] !== '') { update_post_meta($state['research']['id'], '_wp_page_template', $state['research']['template']); }
    else { delete_post_meta($state['research']['id'], '_wp_page_template'); }
    WP_CLI::success('Переводы возвращены в исходные статусы, шаблон восстановлен.');
    return;
}
WP_CLI::error('Нужны prepare либо cleanup <state>.');
