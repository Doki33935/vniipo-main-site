<?php
if (!defined('ABSPATH')) { exit; }

function vniipo_block_areas() {
    return array(
        'home-top' => 'Главная — перед новостями',
        'home-after-news' => 'Главная — после новостей',
        'home-bottom' => 'Главная — внизу основной колонки',
        'sidebar' => 'Главная — боковая колонка',
        'page' => 'Страницы разделов — после основного содержимого',
        'manual' => 'Только вставка в редакторе или shortcode',
    );
}

function vniipo_sanitize_block_area($area) {
    return isset(vniipo_block_areas()[$area]) ? $area : 'manual';
}

function vniipo_sanitize_block_page($key) {
    return $key === '' || vniipo_page_definition($key) ? $key : '';
}

function vniipo_register_information_blocks() {
    register_post_type('vniipo_block', array(
        'labels' => array('name' => 'Информационные блоки', 'singular_name' => 'Информационный блок', 'add_new_item' => 'Добавить информационный блок', 'edit_item' => 'Изменить информационный блок'),
        'public' => false,
        'show_ui' => true,
        'show_in_rest' => true,
        'exclude_from_search' => true,
        'supports' => array('title', 'editor', 'thumbnail', 'revisions', 'page-attributes', 'custom-fields'),
        'menu_icon' => 'dashicons-screenoptions',
    ));
    foreach (array('_vniipo_block_area' => 'vniipo_sanitize_block_area', '_vniipo_block_page' => 'vniipo_sanitize_block_page') as $key => $sanitize) {
        register_post_meta('vniipo_block', $key, array('type' => 'string', 'single' => true, 'show_in_rest' => true, 'sanitize_callback' => $sanitize, 'auth_callback' => static function ($allowed, $meta_key, $post_id) { return current_user_can('edit_post', $post_id); }));
    }
    wp_register_script('vniipo-information-blocks-editor', plugins_url('assets/information-blocks.js', VNIIPO_CORE_DIR . '/vniipo-core.php'), array('wp-blocks', 'wp-element', 'wp-components', 'wp-block-editor'), '0.3.0', true);
    register_block_type('vniipo/information-blocks', array(
        'api_version' => 3,
        'editor_script' => 'vniipo-information-blocks-editor',
        'attributes' => array('area' => array('type' => 'string', 'default' => 'manual'), 'blockId' => array('type' => 'integer', 'default' => 0)),
        'render_callback' => static function ($attributes) { return vniipo_information_blocks_html($attributes['area'] ?? 'manual', '', absint($attributes['blockId'] ?? 0)); },
    ));
}
add_action('init', 'vniipo_register_information_blocks');

function vniipo_information_block_meta_box($post) {
    wp_nonce_field('vniipo_information_block', 'vniipo_information_block_nonce');
    $area = get_post_meta($post->ID, '_vniipo_block_area', true) ?: 'manual';
    $page = get_post_meta($post->ID, '_vniipo_block_page', true);
    echo '<p><label for="vniipo-block-area"><strong>Где показывать</strong></label></p><select name="vniipo_block_area" id="vniipo-block-area" style="max-width:100%">';
    foreach (vniipo_block_areas() as $key => $label) { echo '<option value="' . esc_attr($key) . '" ' . selected($area, $key, false) . '>' . esc_html($label) . '</option>'; }
    echo '</select><p><label for="vniipo-block-page"><strong>Страница раздела</strong></label></p><select name="vniipo_block_page" id="vniipo-block-page" style="max-width:100%"><option value="">Все страницы разделов</option>';
    foreach (vniipo_structure()['pages'] as $definition) {
        if ($definition['key'] === 'home') { continue; }
        echo '<option value="' . esc_attr($definition['key']) . '" ' . selected($page, $definition['key'], false) . '>' . esc_html($definition['title']) . '</option>';
    }
    echo '</select><p><label for="vniipo-block-order"><strong>Порядок</strong></label></p><input type="number" min="0" step="1" name="vniipo_block_order" id="vniipo-block-order" value="' . absint($post->menu_order) . '" style="width:100%"><p>Меньшее число показывается раньше. Страница учитывается для области «Страницы разделов». Черновики не показываются.</p><p>Для вставки этого блока в содержимое: <code>[vniipo_block id="' . absint($post->ID) . '"]</code>. В редакторе также доступен блок «Информационные блоки ВНИИПО».</p>';
}
add_action('add_meta_boxes_vniipo_block', static function ($post) { add_meta_box('vniipo-information-placement', 'Размещение блока', 'vniipo_information_block_meta_box', 'vniipo_block', 'side', 'high'); });

function vniipo_save_information_block($post_id) {
    if (wp_is_post_revision($post_id) || wp_is_post_autosave($post_id) || !current_user_can('edit_post', $post_id)) { return; }
    if (!isset($_POST['vniipo_information_block_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['vniipo_information_block_nonce'])), 'vniipo_information_block')) { return; }
    update_post_meta($post_id, '_vniipo_block_area', vniipo_sanitize_block_area(sanitize_key(wp_unslash($_POST['vniipo_block_area'] ?? 'manual'))));
    update_post_meta($post_id, '_vniipo_block_page', vniipo_sanitize_block_page(sanitize_key(wp_unslash($_POST['vniipo_block_page'] ?? ''))));
    if (isset($_POST['vniipo_block_order'])) {
        $order = absint($_POST['vniipo_block_order']);
        if ((int) get_post_field('menu_order', $post_id) !== $order) {
            remove_action('save_post_vniipo_block', 'vniipo_save_information_block');
            try { wp_update_post(array('ID' => $post_id, 'menu_order' => $order)); }
            finally { add_action('save_post_vniipo_block', 'vniipo_save_information_block'); }
        }
    }
}
add_action('save_post_vniipo_block', 'vniipo_save_information_block');

function vniipo_information_blocks_html($area, $page_key = '', $block_id = 0) {
    static $rendering = false;
    static $sequence = 0;
    if ($rendering || !isset(vniipo_block_areas()[$area])) { return ''; }
    $args = array('post_type' => 'vniipo_block', 'post_status' => 'publish', 'suppress_filters' => false, 'posts_per_page' => -1, 'orderby' => array('menu_order' => 'ASC', 'ID' => 'ASC'), 'no_found_rows' => true);
    if ($block_id) { $args['p'] = $block_id; }
    else { $args['meta_query'] = array(array('key' => '_vniipo_block_area', 'value' => $area)); }
    $blocks = get_posts($args);
    $html = '';
    $rendering = true;
    try {
        foreach ($blocks as $block) {
            $target = get_post_meta($block->ID, '_vniipo_block_page', true);
            if (!$block_id && $area === 'page' && $target !== '' && $target !== $page_key) { continue; }
            $heading_id = 'vn-block-title-' . absint($block->ID) . '-' . ++$sequence;
            $html .= '<section class="vn-information-block" aria-labelledby="' . esc_attr($heading_id) . '"><h2 id="' . esc_attr($heading_id) . '">' . esc_html($block->post_title) . '</h2>';
            if (has_post_thumbnail($block)) { $html .= get_the_post_thumbnail($block, 'large', array('loading' => 'lazy')); }
            $html .= '<div class="vn-information-block-content">' . apply_filters('the_content', $block->post_content) . '</div></section>';
        }
    } finally { $rendering = false; }
    return $html;
}

function vniipo_render_information_blocks($area, $page_key = '') {
    echo vniipo_information_blocks_html($area, $page_key);
    if (function_exists('vniipo_render_source_blocks')) { vniipo_render_source_blocks($area); }
}

add_shortcode('vniipo_blocks', static function ($attributes) {
    $attributes = shortcode_atts(array('area' => 'manual', 'page' => ''), $attributes);
    return vniipo_information_blocks_html($attributes['area'], $attributes['page']);
});
add_shortcode('vniipo_block', static function ($attributes) {
    $attributes = shortcode_atts(array('id' => 0), $attributes);
    return vniipo_information_blocks_html('manual', '', absint($attributes['id']));
});

add_filter('manage_vniipo_block_posts_columns', static function ($columns) { $columns['vniipo_area'] = 'Размещение'; return $columns; });
add_action('manage_vniipo_block_posts_custom_column', static function ($column, $id) {
    if ($column === 'vniipo_area') { echo esc_html(vniipo_block_areas()[get_post_meta($id, '_vniipo_block_area', true)] ?? 'Только ручная вставка'); }
}, 10, 2);
