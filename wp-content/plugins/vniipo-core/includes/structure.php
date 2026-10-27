<?php
if (!defined('ABSPATH')) { exit; }

function vniipo_structure() {
    static $data;
    if ($data === null) {
        $data = json_decode(file_get_contents(VNIIPO_CORE_DIR . '/content/structure.json'), true, 512, JSON_THROW_ON_ERROR);
    }
    return $data;
}

function vniipo_page_definition($key) {
    foreach (vniipo_structure()['pages'] as $page) {
        if ($page['key'] === $key) { return $page; }
    }
    return null;
}

function vniipo_page_by_key($key) {
    $ids = get_posts(array('post_type' => 'page', 'post_status' => 'publish', 'lang' => '', 'meta_key' => '_vniipo_key', 'meta_value' => $key, 'posts_per_page' => 1, 'fields' => 'ids', 'no_found_rows' => true));
    return $ids ? get_post($ids[0]) : null;
}

function vniipo_url($key) {
    $page = function_exists('vniipo_translated_page') ? vniipo_translated_page($key) : vniipo_page_by_key($key);
    if ($page) { return get_permalink($page); }
    $definition = vniipo_page_definition($key);
    return $definition ? home_url($definition['path']) : home_url('/');
}

function vniipo_scaffold($set_front_page = false) {
    $ids = array();
    $created = 0;
    $position = 0;
    foreach (vniipo_structure()['pages'] as $item) {
        $position++;
        $slug = $item['path'] === '/' ? 'home' : basename(trim($item['path'], '/'));
        $parent_path = '/' . trim(dirname(trim($item['path'], '/')), './') . '/';
        $parent_path = $parent_path === '//' ? '/' : $parent_path;
        $parent_id = $parent_path === '/' ? 0 : ($ids[$parent_path] ?? 0);
        if ($parent_path !== '/' && !$parent_id) { throw new RuntimeException('Родитель не найден: ' . $item['path']); }

        $existing = get_posts(array('post_type' => 'page', 'post_status' => array_values(get_post_stati()), 'meta_key' => '_vniipo_key', 'meta_value' => $item['key'], 'posts_per_page' => 2));
        if (count($existing) > 1) { throw new RuntimeException('Дублирующий ключ: ' . $item['key']); }
        if ($existing) {
            // Не возвращаем удалённые/перемещённые редактором страницы и не переписываем контент.
            if ($existing[0]->post_status === 'trash') { throw new RuntimeException('Страница находится в корзине: ' . $item['key']); }
            $id = $existing[0]->ID;
        } else {
            $collision = get_page_by_path($item['path'] === '/' ? 'home' : trim($item['path'], '/'));
            if ($collision) { throw new RuntimeException('Адрес уже занят существующей страницей: ' . $item['path']); }
            $id = wp_insert_post(array('post_type' => 'page', 'post_status' => 'publish', 'post_title' => $item['title'], 'post_name' => $slug, 'post_parent' => $parent_id, 'menu_order' => $position, 'post_content' => '', 'meta_input' => array('_vniipo_key' => $item['key'])), true);
            if (is_wp_error($id)) { throw new RuntimeException($id->get_error_message()); }
            $created++;
        }
        $ids[$item['path']] = $id;
    }
    $menu = wp_get_nav_menu_object('vniipo-primary');
    if (!$menu) {
        $menu_id = wp_create_nav_menu('vniipo-primary');
        if (is_wp_error($menu_id)) { throw new RuntimeException($menu_id->get_error_message()); }
    } else { $menu_id = $menu->term_id; }
    $items = wp_get_nav_menu_items($menu_id) ?: array();
    foreach (vniipo_structure()['pages'] as $item) {
        if (empty($item['menu'])) { continue; }
        $exists = array_filter($items, static function ($menu_item) use ($ids, $item) { return (int) $menu_item->object_id === $ids[$item['path']] && $menu_item->object === 'page'; });
        if (!$exists) {
            $result = wp_update_nav_menu_item($menu_id, 0, array('menu-item-title' => $item['title'], 'menu-item-object' => 'page', 'menu-item-object-id' => $ids[$item['path']], 'menu-item-type' => 'post_type', 'menu-item-status' => 'publish'));
            if (is_wp_error($result)) { throw new RuntimeException($result->get_error_message()); }
        }
    }
    $locations = get_theme_mod('nav_menu_locations', array());
    // Существующее назначение меню сохраняется.
    if (empty($locations['primary'])) { $locations['primary'] = $menu_id; set_theme_mod('nav_menu_locations', $locations); }
    if ($set_front_page) {
        update_option('show_on_front', 'page');
        update_option('page_on_front', $ids['/']);
    }
    return array('created' => $created, 'total' => count($ids));
}
