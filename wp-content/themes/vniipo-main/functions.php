<?php
if (!defined('ABSPATH')) { exit; }

function vniipo_theme_setup() {
    load_child_theme_textdomain('vniipo', get_stylesheet_directory() . '/languages');
    add_theme_support('custom-logo', array('height' => 90, 'width' => 90, 'flex-height' => true, 'flex-width' => true));
    remove_action('wp_enqueue_scripts', 'fyremezzonine_assets');
}
add_action('after_setup_theme', 'vniipo_theme_setup', 20);

function vniipo_conference_context() {
    if (is_singular('conference') || is_post_type_archive('conference')) { return true; }
    $path = (string) wp_parse_url(wp_unslash($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH);
    $base = (string) wp_parse_url(home_url('/'), PHP_URL_PATH);
    $path = substr($path, strlen(rtrim($base, '/')));
    if (preg_match('~^/(editor(?:/|$)|registration/?$|partnership/?$)~', $path)) { return true; }
    $post = get_post();
    return $post && strpos($post->post_content, '[conference_') !== false;
}

function vniipo_theme_assets() {
    $dependencies = array();
    if (vniipo_conference_context()) {
        wp_enqueue_style('vniipo-conference', get_template_directory_uri() . '/style.css', array(), wp_get_theme(get_template())->get('Version'));
        wp_enqueue_script('fyremezzonine-theme-effects', get_template_directory_uri() . '/assets/theme-effects.js', array(), wp_get_theme(get_template())->get('Version'), true);
        $dependencies[] = 'vniipo-conference';
    }
    wp_enqueue_style('vniipo-main', get_stylesheet_uri(), $dependencies, wp_get_theme()->get('Version'));
    wp_enqueue_script('vniipo-main', get_stylesheet_directory_uri() . '/assets/site.js', array(), wp_get_theme()->get('Version'), true);
}
add_action('wp_enqueue_scripts', 'vniipo_theme_assets', 20);

function vniipo_main_url($key) {
    if (function_exists('vniipo_preview_is_active') && vniipo_preview_is_active()) {
        $definition = vniipo_page_definition($key);
        return vniipo_preview_url($definition ? $definition['path'] : '/');
    }
    return function_exists('vniipo_url') ? vniipo_url($key) : home_url('/');
}

function vniipo_site_url($path = '/') {
    return function_exists('vniipo_preview_is_active') && vniipo_preview_is_active() ? vniipo_preview_url($path) : home_url($path);
}

function vniipo_primary_navigation() {
    if (function_exists('vniipo_preview_is_active') && vniipo_preview_is_active()) {
        vniipo_nav_fallback();
        return;
    }
    wp_nav_menu(array('theme_location' => 'primary', 'container' => false, 'depth' => 1, 'fallback_cb' => 'vniipo_nav_fallback'));
}

function vniipo_home_menu_icon($title, $item, $args) {
    if (($args->theme_location ?? '') === 'primary' && (int) $item->object_id === (int) get_option('page_on_front')) {
        return '<span class="vn-homepage-link"><svg class="vn-home-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M3 11 12 3 21 11M5 10v11h5v-7h4v7h5V10"/></svg><span class="vn-home-label">' . esc_html__('Главная', 'vniipo') . '</span></span>';
    }
    return $title;
}
add_filter('nav_menu_item_title', 'vniipo_home_menu_icon', 10, 3);

function vniipo_nav_fallback() {
    echo '<ul>';
    if (function_exists('vniipo_structure')) {
        foreach (vniipo_structure()['pages'] as $page) {
            if (!empty($page['menu'])) {
                $title = esc_html($page['title']);
                if ($page['key'] === 'home') {
                    $title = '<span class="vn-homepage-link"><svg class="vn-home-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M3 11 12 3 21 11M5 10v11h5v-7h4v7h5V10"/></svg><span class="vn-home-label">' . esc_html__('Главная', 'vniipo') . '</span></span>';
                }
                echo '<li><a href="' . esc_url(vniipo_main_url($page['key'])) . '">' . $title . '</a></li>';
            }
        }
    }
    echo '</ul>';
}

function vniipo_render_news($count = 3) {
    $news = new WP_Query(array('post_type' => 'post', 'post_status' => 'publish', 'posts_per_page' => $count, 'ignore_sticky_posts' => true, 'no_found_rows' => true));
    if (!$news->have_posts()) { echo '<p class="vn-empty">' . esc_html__('Новости появятся после публикации редактором.', 'vniipo') . '</p>'; return; }
    echo '<div class="vn-news-grid">';
    while ($news->have_posts()) {
        $news->the_post();
        get_template_part('template-parts/news-card');
    }
    echo '</div>';
    wp_reset_postdata();
}

function vniipo_render_listing($type) {
    $paged = max(1, (int) get_query_var('paged'), (int) get_query_var('page'));
    $query = new WP_Query(array('post_type' => $type, 'post_status' => 'publish', 'posts_per_page' => 12, 'paged' => $paged));
    if (!$query->have_posts()) { echo '<p class="vn-empty">' . esc_html__('В этом разделе пока нет опубликованных материалов.', 'vniipo') . '</p>'; return; }
    echo '<div class="' . esc_attr($type === 'vniipo_faq' ? 'vn-faq' : 'vn-documents') . '">';
    while ($query->have_posts()) {
        $query->the_post();
        if ($type === 'vniipo_faq') {
            echo '<details><summary>' . esc_html(get_the_title()) . '</summary><div>';
            the_content();
            echo '</div></details>';
        } else { get_template_part('template-parts/news-card'); }
    }
    echo '</div><div class="vn-pager">' . wp_kses_post((string) paginate_links(array('total' => $query->max_num_pages, 'current' => $paged))) . '</div>';
    wp_reset_postdata();
}
