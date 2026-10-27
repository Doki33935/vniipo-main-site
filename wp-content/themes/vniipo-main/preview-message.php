<?php
if (!defined('ABSPATH') || !function_exists('vniipo_preview_is_active') || !vniipo_preview_is_active()) { exit; }
get_header();
?>
<main id="primary" class="vn-page vn-wrap vn-section">
    <h1 class="vn-page-heading"><?php esc_html_e('Формы в предпросмотре отключены', 'vniipo'); ?></h1>
    <p><?php esc_html_e('Здесь администраторы знакомятся с оформлением и структурой нового сайта. Регистрация, отправка заявок и работа кабинетов остаются в действующем конференционном сервисе.', 'vniipo'); ?></p>
    <div class="vn-actions"><a class="vn-button" href="<?php echo esc_url(home_url('/')); ?>"><?php esc_html_e('Открыть действующий сайт', 'vniipo'); ?></a><a class="vn-button" href="<?php echo esc_url(vniipo_preview_url('/')); ?>"><?php esc_html_e('Вернуться к просмотру', 'vniipo'); ?></a></div>
</main>
<?php get_footer(); ?>
