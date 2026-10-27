<?php
if (!defined('ABSPATH')) { exit; }

function vniipo_site_fields() {
    return array(
        'email' => array('Подтверждённый email', 'email'),
        'address' => array('Подтверждённый адрес для посетителей', 'text'),
        'phones' => array('Телефоны (по одному в строке)', 'textarea'),
        'hours' => array('График работы', 'text'),
        'map_url' => array('Ссылка на организацию в Яндекс Картах (https)', 'url'),
        'vk_url' => array('ВКонтакте — официальная страница', 'url'),
        'telegram_url' => array('Telegram — официальный канал', 'url'),
        'rutube_url' => array('Rutube — официальный канал', 'url'),
        'appeals_url' => array('Официальная страница обращений граждан', 'url'),
    );
}

function vniipo_sanitize_site_settings($input) {
    $result = array();
    foreach (vniipo_site_fields() as $key => $field) {
        $value = is_array($input) && isset($input[$key]) && is_scalar($input[$key]) ? (string) $input[$key] : '';
        $result[$key] = $field[1] === 'url' ? vniipo_public_https($value) : ($field[1] === 'email' ? sanitize_email($value) : ($field[1] === 'textarea' ? sanitize_textarea_field($value) : sanitize_text_field($value)));
    }
    if ($result['map_url'] && !preg_match('/^(?:www\.)?yandex\.(?:ru|com)$/i', wp_parse_url($result['map_url'], PHP_URL_HOST))) {
        $result['map_url'] = '';
        add_settings_error('vniipo_site_settings', 'map_host', 'Для карты используйте ссылку на yandex.ru или yandex.com.');
    }
    return $result;
}

function vniipo_site_settings() {
    $settings = get_option('vniipo_site_settings', array());
    return is_array($settings) ? $settings : array();
}

add_action('admin_init', static function () {
    register_setting('vniipo_site', 'vniipo_site_settings', array('type' => 'array', 'default' => array(), 'sanitize_callback' => 'vniipo_sanitize_site_settings', 'show_in_rest' => false));
});
add_action('admin_menu', static function () { add_options_page('Настройки сайта ВНИИПО', 'Сайт ВНИИПО', 'manage_options', 'vniipo-site', 'vniipo_site_settings_page'); });

function vniipo_site_settings_page() {
    if (!current_user_can('manage_options')) { return; }
    $settings = vniipo_site_settings();
    echo '<div class="wrap"><h1>Настройки сайта ВНИИПО</h1><p>Указывайте утверждённые публичные сведения. Пустые поля сохраняют контакты из снимка oren.vniipo.ru; карта, email и соцсети до заполнения не выводятся. Изменения отражаются в контактном блоке и на странице контактов. Подписка и Яндекс Директ отложены по решению заказчика.</p>';
    settings_errors('vniipo_site_settings');
    echo '<form action="options.php" method="post">';
    settings_fields('vniipo_site');
    echo '<table class="form-table" role="presentation">';
    foreach (vniipo_site_fields() as $key => $field) {
        echo '<tr><th><label for="vniipo-' . esc_attr($key) . '">' . esc_html($field[0]) . '</label></th><td>';
        if ($field[1] === 'textarea') { echo '<textarea class="large-text" rows="3" id="vniipo-' . esc_attr($key) . '" name="vniipo_site_settings[' . esc_attr($key) . ']">' . esc_textarea($settings[$key] ?? '') . '</textarea>'; }
        else { echo '<input class="regular-text" id="vniipo-' . esc_attr($key) . '" name="vniipo_site_settings[' . esc_attr($key) . ']" type="' . esc_attr($field[1]) . '" value="' . esc_attr($settings[$key] ?? '') . '">'; }
        echo '</td></tr>';
    }
    echo '</table>';
    submit_button();
    echo '</form></div>';
}

function vniipo_appeals_url() {
    return vniipo_public_https(vniipo_site_settings()['appeals_url'] ?? '') ?: 'https://vniipo.ru/deyatelnost/obrascheniya-grazhdan/';
}

function vniipo_render_confirmed_contacts() {
    $settings = vniipo_site_settings();
    if (!array_filter(array_intersect_key($settings, array_flip(array('email', 'address', 'phones', 'hours', 'map_url'))))) { return; }
    echo '<section class="vn-confirmed-contacts"><h2>' . esc_html__('Актуальные контакты', 'vniipo') . '</h2>';
    foreach (array('address', 'hours') as $key) { if (!empty($settings[$key])) { echo '<p>' . esc_html($settings[$key]) . '</p>'; } }
    foreach (preg_split('/\r?\n/', $settings['phones'] ?? '', -1, PREG_SPLIT_NO_EMPTY) as $phone) { echo '<p><a href="tel:' . esc_attr(preg_replace('/[^+0-9]/', '', $phone)) . '">' . esc_html($phone) . '</a></p>'; }
    if (!empty($settings['email'])) { echo '<p><a href="mailto:' . esc_attr($settings['email']) . '">' . esc_html($settings['email']) . '</a></p>'; }
    if (!empty($settings['map_url'])) { echo '<p><a class="vn-button" href="' . esc_url($settings['map_url']) . '" rel="noopener">' . esc_html__('Мы в Яндекс Картах', 'vniipo') . ' ↗</a></p>'; }
    echo '</section>';
}

function vniipo_render_social_links() {
    $settings = vniipo_site_settings();
    $links = array();
    foreach (array('vk_url' => 'ВКонтакте', 'telegram_url' => 'Telegram', 'rutube_url' => 'Rutube') as $key => $label) {
        if (!empty($settings[$key])) { $links[] = '<a href="' . esc_url($settings[$key]) . '" rel="noopener">' . esc_html($label) . ' ↗</a>'; }
    }
    if ($links) { echo '<section class="vn-sidebox"><h2>' . esc_html__('Мы в соцсетях', 'vniipo') . '</h2><div class="vn-sidebox-body vn-social-links">' . implode(' ', $links) . '</div></section>'; }
}
