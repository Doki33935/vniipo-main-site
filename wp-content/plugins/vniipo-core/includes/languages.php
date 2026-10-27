<?php
if (!defined('ABSPATH')) { exit; }

// Конференции сохраняют единые записи и маршруты до отдельной миграции.
add_filter('pll_get_post_types', static function ($types) {
    foreach (array('vniipo_document', 'vniipo_faq', 'vniipo_banner', 'vniipo_album', 'vniipo_block') as $type) { $types[$type] = $type; }
    unset($types['conference']);
    return $types;
});
add_filter('pll_get_taxonomies', static function ($types) { $types['vniipo_document_category'] = 'vniipo_document_category'; return $types; });
add_filter('pll_copy_post_metas', static function ($keys) {
    return array_values(array_filter($keys, static function ($key) { return $key !== '_vniipo_key' && strpos($key, '_vniipo_source_') !== 0 && strpos($key, '_vniipo_translation_') !== 0; }));
});

function vniipo_page_key($id) {
    $key = get_post_meta($id, '_vniipo_key', true);
    if (!$key && function_exists('pll_get_post')) {
        $original = pll_get_post($id, 'ru');
        if ($original) { $key = get_post_meta($original, '_vniipo_key', true); }
    }
    return $key;
}

function vniipo_translated_page($key) {
    $page = vniipo_page_by_key($key);
    if ($page && function_exists('pll_current_language') && function_exists('pll_get_post') && pll_current_language()) {
        $id = pll_get_post($page->ID, pll_current_language());
        if ($id && get_post_status($id) === 'publish') { return get_post($id); }
    }
    return $page;
}

function vniipo_localized_title($key, $fallback = '') {
    $page = vniipo_translated_page($key);
    return $page ? $page->post_title : $fallback;
}

function vniipo_interface_translation($translation, $text, $domain) {
    if ($domain !== 'vniipo' || is_admin()) { return $translation; }
    $locale = function_exists('pll_current_language') ? (pll_current_language('locale') ?: get_locale()) : get_locale();
    if (!in_array($locale, array('en_US', 'zh_TW'), true)) { return $translation; }
    static $dictionary;
    if ($dictionary === null) { $dictionary = json_decode(file_get_contents(VNIIPO_CORE_DIR . '/content/interface-translations.json'), true, 512, JSON_THROW_ON_ERROR); }
    return $dictionary[$text][$locale] ?? $translation;
}
add_filter('gettext_vniipo', 'vniipo_interface_translation', 10, 3);

function vniipo_language_notice() {
    if (!function_exists('pll_current_language') || !pll_current_language() || pll_current_language() === 'ru') { return; }
    echo '<p class="vn-language-note">' . esc_html__('Некоторые материалы и конференционный сервис доступны на русском языке.', 'vniipo') . '</p>';
}
