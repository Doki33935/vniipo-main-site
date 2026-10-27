<?php
/**
 * Plugin Name: VNIIPO Private Preview
 * Description: Закрытый просмотр основного сайта администраторами без смены публичной темы и создания страниц.
 * Version: 0.4.0
 * Requires at least: 6.9
 * Requires PHP: 7.3
 * Text Domain: vniipo
 */
if (!defined('ABSPATH')) { exit; }

function vniipo_preview_is_active() {
    return !empty($GLOBALS['vniipo_preview_active']);
}

function vniipo_preview_url($path = '/') {
    return home_url('/vniipo-preview/' . ltrim($path, '/'));
}

function vniipo_preview_request_path() {
    $path = (string) wp_parse_url(wp_unslash($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH);
    $base = rtrim((string) wp_parse_url(home_url('/'), PHP_URL_PATH), '/');
    $prefix = $base . '/vniipo-preview';
    if ($path !== $prefix && strpos($path, $prefix . '/') !== 0) { return null; }
    $relative = substr($path, strlen($prefix));
    return '/' . trim($relative, '/') . ($relative && trim($relative, '/') !== '' ? '/' : '');
}

function vniipo_preview_headers() {
    nocache_headers();
    header('X-Robots-Tag: noindex, nofollow, noarchive', true);
    header('Vary: Cookie', false);
}

function vniipo_preview_prepare() {
    $path = vniipo_preview_request_path();
    if ($path === null) { return; }
    if (!defined('DONOTCACHEPAGE')) { define('DONOTCACHEPAGE', true); }
    vniipo_preview_headers();
    // До init: обработчики форм конференционного плагина не должны принимать POST предпросмотра.
    if (!in_array($_SERVER['REQUEST_METHOD'] ?? 'GET', array('GET', 'HEAD'), true)) {
        header('Allow: GET, HEAD');
        wp_die('Предпросмотр доступен только для чтения.', 'Предпросмотр', array('response' => 405));
    }
    if (!is_user_logged_in()) { auth_redirect(); }
    if (!current_user_can('manage_options')) {
        wp_die('Предпросмотр доступен только администраторам сайта.', 'Доступ ограничен', array('response' => 403));
    }
    if (version_compare(PHP_VERSION, '8.3', '<')) {
        wp_die('Новая тема требует PHP 8.3. Для просмотра используйте отдельный staging с PHP 8.3; публичную версию PHP не меняйте автоматически.', 'Проверка окружения', array('response' => 503));
    }
    $core = WP_PLUGIN_DIR . '/vniipo-core/vniipo-core.php';
    if (!wp_get_theme('vniipo-main')->exists() || !wp_get_theme('fyremezzonine-wp-theme')->exists() || !is_file($core) || !function_exists('fyremezzonine_manager_register_post_type')) {
        wp_die('Установите VNIIPO Main и файлы VNIIPO Core. Действующие тема конференций и Conference Manager должны быть установлены и включены. Активировать новую тему и VNIIPO Core для просмотра не требуется.', 'Не хватает компонентов', array('response' => 503));
    }
    $GLOBALS['vniipo_preview_active'] = true;
    $GLOBALS['vniipo_preview_path'] = $path;
    // Фильтры действуют только в этом авторизованном запросе. switch_theme и update_option не используются.
    add_filter('pre_option_stylesheet', static function () { return 'vniipo-main'; });
    add_filter('pre_option_template', static function () { return 'fyremezzonine-wp-theme'; });
    if (!function_exists('vniipo_structure')) { require_once $core; }
    add_filter('post_link', 'vniipo_preview_permalink');
    add_filter('post_type_link', 'vniipo_preview_permalink');
    add_filter('page_link', 'vniipo_preview_permalink');
    add_filter('pre_get_document_title', static function () { return ($GLOBALS['vniipo_preview_title'] ?? 'Новый сайт') . ' — закрытый предпросмотр'; });
    add_filter('wp_robots', static function ($robots) { $robots['noindex'] = true; $robots['nofollow'] = true; return $robots; });
    add_filter('the_content', static function ($content) { return wp_kses_post(strip_shortcodes($content)); }, 99);
    remove_action('wp_head', 'rel_canonical');
    add_action('wp_body_open', 'vniipo_preview_notice');
    add_action('template_redirect', 'vniipo_preview_render', -100);
}
add_action('plugins_loaded', 'vniipo_preview_prepare', 1);

function vniipo_preview_permalink($url) {
    $base = home_url('/');
    if (strpos($url, $base) !== 0 || strpos($url, vniipo_preview_url('/')) === 0) { return $url; }
    return vniipo_preview_url(substr($url, strlen($base)));
}

function vniipo_preview_notice() {
    echo '<div class="vn-preview-notice" role="note">Закрытый предпросмотр нового сайта. Действующая тема конференций не переключена; отправка форм здесь отключена.';
    echo '<a href="' . esc_url(home_url('/')) . '">Действующий сайт</a><a href="' . esc_url(admin_url('tools.php?page=vniipo-preview')) . '">Инструкция</a></div>';
}

function vniipo_preview_set_query($query) {
    $GLOBALS['wp_query'] = $query;
    $GLOBALS['wp_the_query'] = $query;
    if ($query->have_posts()) { $GLOBALS['post'] = $query->post; }
    else { $GLOBALS['post'] = null; }
}

function vniipo_preview_render() {
    $path = $GLOBALS['vniipo_preview_path'];
    vniipo_preview_headers();
    foreach (vniipo_structure()['redirects'] as $redirect) {
        if ($path === $redirect['from']) { wp_safe_redirect(vniipo_preview_url($redirect['to']), 302, 'VNIIPO Preview'); exit; }
    }
    status_header(200);
    if (preg_match('~^/(editor(?:/|$)|registration(?:/|$)|partnership(?:/|$))~', $path)) {
        $query = new WP_Query();
        vniipo_preview_set_query($query);
        $GLOBALS['vniipo_preview_title'] = 'Формы в предпросмотре';
        include get_stylesheet_directory() . '/preview-message.php';
        exit;
    }
    if (isset($_GET['s']) && preg_match('~^/(?:page/([1-9][0-9]*)/)?$~', $path, $search_page)) {
        $search = sanitize_text_field(wp_unslash($_GET['s']));
        vniipo_preview_set_query(new WP_Query(array('s' => $search, 'post_type' => array('post', 'page', 'conference', 'vniipo_document', 'vniipo_faq', 'vniipo_album'), 'post_status' => 'publish', 'posts_per_page' => 12, 'paged' => isset($search_page[1]) ? (int) $search_page[1] : 1)));
        $GLOBALS['vniipo_preview_title'] = 'Поиск: ' . $search;
        include get_stylesheet_directory() . '/index.php';
        exit;
    }
    $definition_path = $path;
    $paged = 1;
    if (preg_match('~^(.+/)page/([1-9][0-9]*)/$~', $path, $matches)) { $definition_path = $matches[1]; $paged = (int) $matches[2]; }
    foreach (vniipo_structure()['pages'] as $definition) {
        if ($definition_path !== $definition['path'] || ($paged > 1 && empty($definition['listing']))) { continue; }
        $query = new WP_Query();
        $query->set('paged', $paged);
        vniipo_preview_set_query($query);
        $GLOBALS['vniipo_preview_page'] = $definition;
        $GLOBALS['vniipo_preview_title'] = $definition['title'];
        include get_stylesheet_directory() . ($path === '/' ? '/front-page.php' : '/preview-page.php');
        exit;
    }
    if ($path === '/conferences/') {
        vniipo_preview_set_query(new WP_Query(array('post_type' => 'conference', 'post_status' => 'publish', 'posts_per_page' => 12)));
        $GLOBALS['vniipo_preview_title'] = 'Конференции';
        include get_template_directory() . '/archive-conference.php';
        exit;
    }
    // Только опубликованные записи. URL кабинетов, регистрации и admin-post не исполняются в предпросмотре.
    if (!preg_match('~^/(editor(?:/|$)|registration(?:/|$)|partnership(?:/|$)|wp-)~', $path)) {
        $id = url_to_postid(home_url($path));
        $post = $id ? get_post($id) : null;
        if ($post && $post->post_status === 'publish' && in_array($post->post_type, array('post', 'page', 'conference', 'vniipo_document', 'vniipo_faq', 'vniipo_banner', 'vniipo_album'), true)) {
            vniipo_preview_set_query(new WP_Query(array('p' => $post->ID, 'post_type' => $post->post_type, 'post_status' => 'publish')));
            $GLOBALS['vniipo_preview_title'] = $post->post_title;
            include $post->post_type === 'conference' ? get_template_directory() . '/single-conference.php' : get_stylesheet_directory() . '/index.php';
            exit;
        }
    }
    $query = new WP_Query();
    $query->is_404 = true;
    vniipo_preview_set_query($query);
    status_header(404);
    $GLOBALS['vniipo_preview_title'] = 'Страница предпросмотра не найдена';
    include get_stylesheet_directory() . '/404.php';
    exit;
}

function vniipo_preview_admin_page() {
    if (!current_user_can('manage_options')) { return; }
    echo '<div class="wrap"><h1>Предпросмотр основного сайта ВНИИПО</h1><p>Публичный сайт конференций продолжает работать в активной теме. Новая тема применяется только к вашему запросу по закрытому адресу.</p>';
    echo '<p><a class="button button-primary" href="' . esc_url(vniipo_preview_url('/')) . '" target="_blank" rel="noopener">Открыть новый сайт</a></p>';
    echo '<p>Передайте этот адрес другим администраторам WordPress: <code>' . esc_html(vniipo_preview_url('/')) . '</code>. Каждый входит под своей учётной записью с правом manage_options.</p>';
    echo '<p>Для просмотра нужны PHP 8.3, установленная неактивная тема VNIIPO Main и файлы VNIIPO Core. Действующие тема конференций и Conference Manager сохраняются. Не активируйте новую тему и не запускайте scaffold на рабочем сайте ради предпросмотра.</p>';
    echo '<p>Страницы структуры формируются в памяти. Существующие опубликованные новости и конференции читаются из текущей БД. Это просмотр интерфейса; формы регистрации и кабинеты проверяются отдельно на staging.</p></div>';
}
add_action('admin_menu', static function () { add_management_page('Предпросмотр ВНИИПО', 'Предпросмотр ВНИИПО', 'manage_options', 'vniipo-preview', 'vniipo_preview_admin_page'); });
