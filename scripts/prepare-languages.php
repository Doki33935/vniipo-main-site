<?php
/** Только новый сайт/staging. Создаёт языки и черновики, не публикует переводы. */
if (!defined('ABSPATH') || !defined('WP_CLI') || !WP_CLI) { exit(1); }
if (!in_array(wp_get_environment_type(), array('local', 'development', 'staging'), true)) { WP_CLI::error('Настройку сначала выполняют на staging.'); }
if (!function_exists('PLL') || !function_exists('pll_set_post_language')) { WP_CLI::error('Установите и активируйте Polylang 3.9.1.'); }
if (!in_array('apply', $args, true)) { WP_CLI::log('План: ru_RU, en_US, zh_TW; язык существующих материалов — ru, только если ещё не задан; 16 черновиков переводов основных страниц. Для применения добавьте аргумент apply.'); return; }

$languages = array(
    array('slug' => 'ru', 'locale' => 'ru_RU', 'name' => 'Русский', 'rtl' => false, 'term_group' => 0),
    array('slug' => 'en', 'locale' => 'en_US', 'name' => 'English', 'rtl' => false, 'term_group' => 1),
    array('slug' => 'zh-hant', 'locale' => 'zh_TW', 'name' => '繁體中文', 'rtl' => false, 'term_group' => 2),
);
foreach ($languages as $language) {
    if (!PLL()->model->get_language($language['slug'])) {
        $result = PLL()->model->languages->add($language);
        if (is_wp_error($result)) { WP_CLI::error($result->get_error_message()); }
    }
}
foreach (array('default_lang' => 'ru', 'force_lang' => 1, 'hide_default' => true, 'redirect_lang' => true, 'browser' => false, 'media_support' => false) as $key => $value) {
    $result = PLL()->options->set($key, $value);
    if ($result->has_errors()) { WP_CLI::error($result->get_error_message()); }
}
PLL()->options->save();
$types = array('post', 'page', 'vniipo_document', 'vniipo_faq', 'vniipo_banner', 'vniipo_album', 'vniipo_block');
$ids = get_posts(array('post_type' => $types, 'post_status' => array_values(get_post_stati()), 'lang' => '', 'posts_per_page' => -1, 'fields' => 'ids', 'suppress_filters' => true));
foreach ($ids as $id) { if (!pll_get_post_language($id)) { pll_set_post_language($id, 'ru'); } }

$seeds = json_decode(file_get_contents(VNIIPO_CORE_DIR . '/content/translation-drafts.json'), true, 512, JSON_THROW_ON_ERROR);
$created = 0;
foreach ($seeds as $key => $seed) {
    $original = vniipo_page_by_key($key);
    if (!$original) { WP_CLI::warning('Нет исходной страницы: ' . $key); continue; }
    $translations = pll_get_post_translations($original->ID);
    $translations['ru'] = $original->ID;
    foreach (array('en', 'zh-hant') as $language) {
        if (!empty($translations[$language])) { continue; }
        $content = $seed[$language];
        $record = vniipo_source_page_record($key);
        $url = $record['source_url'] ?? 'https://oren.vniipo.ru/';
        $label = $language === 'en' ? 'Russian source material' : '俄語原始資料';
        $html = '<p>' . esc_html($content['text']) . '</p><p><a href="' . esc_url($url) . '">' . esc_html($label) . '</a></p>';
        $parent = $original->post_parent ? (int) pll_get_post($original->post_parent, $language) : 0;
        $id = wp_insert_post(wp_slash(array('post_type' => 'page', 'post_status' => 'draft', 'post_title' => $content['title'], 'post_name' => $key . '-' . $language, 'post_parent' => $parent, 'post_content' => $html, 'post_excerpt' => $content['text'], 'meta_input' => array('_vniipo_translation_original' => $original->ID, '_vniipo_translation_review' => 'draft', '_wp_page_template' => $key === 'home' ? 'default' : 'page-presentation.php'))), true);
        if (is_wp_error($id)) { WP_CLI::error($id->get_error_message()); }
        pll_set_post_language($id, $language);
        $translations[$language] = $id;
        $created++;
    }
    pll_save_post_translations($translations);
}
flush_rewrite_rules(false);
WP_CLI::success('Языки подготовлены. Создано черновиков: ' . $created . '. Опубликуйте только после проверки перевода. Конференционные записи не изменены.');
