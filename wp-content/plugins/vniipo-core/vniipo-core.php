<?php
/**
 * Plugin Name: VNIIPO Core
 * Description: Структура, документы и вопросы основного сайта Оренбургского филиала ВНИИПО.
 * Version: 0.1.0
 * Requires at least: 6.9
 * Requires PHP: 8.3
 * Text Domain: vniipo
 */
if (!defined('ABSPATH')) { exit; }

define('VNIIPO_CORE_DIR', __DIR__);
require_once __DIR__ . '/includes/structure.php';

function vniipo_register_content_types() {
    foreach (array('vniipo_document' => array('Документы', 'Документ'), 'vniipo_faq' => array('Вопросы и ответы', 'Вопрос и ответ'), 'vniipo_banner' => array('Анонсы и баннеры', 'Анонс')) as $type => $labels) {
        register_post_type($type, array(
            'labels' => array('name' => $labels[0], 'singular_name' => $labels[1], 'add_new_item' => 'Добавить: ' . $labels[1]),
            'public' => true,
            'show_in_rest' => true,
            'has_archive' => false,
            'rewrite' => array('slug' => array('vniipo_document' => 'document', 'vniipo_faq' => 'question', 'vniipo_banner' => 'announcement')[$type]),
            'supports' => array('title', 'editor', 'excerpt', 'thumbnail', 'revisions'),
            'menu_icon' => $type === 'vniipo_document' ? 'dashicons-media-document' : 'dashicons-format-status',
        ));
    }
}
add_action('init', 'vniipo_register_content_types');

function vniipo_redirect_aliases() {
    if (is_admin() || wp_doing_ajax() || !in_array($_SERVER['REQUEST_METHOD'] ?? 'GET', array('GET', 'HEAD'), true)) { return; }
    $path = wp_parse_url(wp_unslash($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH);
    $base = untrailingslashit((string) wp_parse_url(home_url('/'), PHP_URL_PATH));
    if ($base && strpos($path, $base . '/') === 0) { $path = substr($path, strlen($base)); }
    $path = '/' . trim($path, '/') . '/';
    foreach (vniipo_structure()['redirects'] as $redirect) {
        if ($path === $redirect['from']) {
            wp_safe_redirect(home_url($redirect['to']), 301, 'VNIIPO Core');
            exit;
        }
    }
}
add_action('template_redirect', 'vniipo_redirect_aliases', 1);

if (defined('WP_CLI') && WP_CLI) {
    require_once __DIR__ . '/includes/cli.php';
}
