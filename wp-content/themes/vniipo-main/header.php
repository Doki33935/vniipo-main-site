<?php if (!defined('ABSPATH')) { exit; } ?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php wp_head(); ?>
</head>
<body <?php body_class(vniipo_conference_context() ? 'vn-conference-context' : 'vn-main-context'); ?>>
<?php wp_body_open(); ?>
<a class="vn-skip" href="#primary"><?php esc_html_e('Перейти к содержимому', 'vniipo'); ?></a>
<header class="vn-header">
    <div class="vn-utility"><div class="vn-wrap">
        <a href="https://vniipo.ru/"><?php esc_html_e('Официальный сайт ВНИИПО', 'vniipo'); ?> ↗</a>
        <div class="vn-utility-links">
            <?php if (function_exists('pll_the_languages')) : ?>
                <ul class="vn-languages" aria-label="<?php esc_attr_e('Язык сайта', 'vniipo'); ?>"><?php pll_the_languages(array('show_flags' => 0, 'show_names' => 1, 'hide_if_no_translation' => 1)); ?></ul>
            <?php endif; ?>
            <a href="<?php echo esc_url(home_url('/editor/login/')); ?>"><?php esc_html_e('Вход для сотрудников', 'vniipo'); ?></a>
        </div>
    </div></div>
    <div class="vn-wrap vn-masthead">
        <a class="vn-emblem" href="<?php echo esc_url(vniipo_site_url('/')); ?>" aria-label="<?php esc_attr_e('На главную страницу', 'vniipo'); ?>">
            <?php $logo = (int) get_theme_mod('custom_logo'); ?>
            <?php if ($logo) : echo wp_get_attachment_image($logo, array(100, 100), false, array('alt' => '')); else : ?>
                <img src="<?php echo esc_url(get_stylesheet_directory_uri() . '/assets/institute-emblem.png'); ?>" width="90" height="90" alt="">
            <?php endif; ?>
        </a>
        <a class="vn-brand" href="<?php echo esc_url(vniipo_site_url('/')); ?>">
            <strong>ФГБУ ВНИИПО МЧС России<br><?php esc_html_e('Оренбургский филиал', 'vniipo'); ?></strong>
            <span><?php esc_html_e('Всероссийский научно-исследовательский институт противопожарной обороны', 'vniipo'); ?></span>
        </a>
        <a class="vn-ministry" href="https://mchs.gov.ru/" aria-label="<?php esc_attr_e('МЧС России — официальный сайт', 'vniipo'); ?>">
            <img src="<?php echo esc_url(get_stylesheet_directory_uri() . '/assets/mchs-emblem.png'); ?>" width="90" height="123" alt="">
        </a>
    </div>
    <div class="vn-nav-bar"><div class="vn-wrap">
        <button class="vn-menu-toggle" type="button" aria-controls="vn-navigation" aria-expanded="false"><span aria-hidden="true">☰</span> <?php esc_html_e('Меню', 'vniipo'); ?></button>
        <nav class="vn-nav" id="vn-navigation" aria-label="<?php esc_attr_e('Основная навигация', 'vniipo'); ?>">
            <?php vniipo_primary_navigation(); ?>
            <div class="vn-nav-search"><?php get_search_form(); ?></div>
        </nav>
    </div></div>
</header>
<?php if (wp_get_environment_type() === 'local' && !(function_exists('vniipo_preview_is_active') && vniipo_preview_is_active())) : ?>
    <div class="vn-wrap"><p class="vn-demo-note"><?php esc_html_e('Проект нового сайта. Материалы разделов заполняются и проверяются перед публикацией.', 'vniipo'); ?></p></div>
<?php endif; ?>
<?php if (function_exists('fyremezzonine_manager_can_manage_conferences') && fyremezzonine_manager_can_manage_conferences()) : ?>
    <div class="vn-wrap vn-editor-links">
        <a href="<?php echo esc_url(home_url('/editor/conferences/')); ?>">Конференции</a>
        <a href="<?php echo esc_url(home_url('/editor/registrations/')); ?>">Заявки</a>
        <a href="<?php echo esc_url(home_url('/editor/summary/')); ?>">Оперативная сводка</a>
        <a href="<?php echo esc_url(home_url('/editor/partnership/')); ?>">Партнёрство</a>
        <a href="<?php echo esc_url(wp_logout_url(home_url('/'))); ?>">Выйти</a>
    </div>
<?php elseif (function_exists('fyremezzonine_manager_is_section_manager') && fyremezzonine_manager_is_section_manager()) : ?>
    <div class="vn-wrap vn-editor-links"><a href="<?php echo esc_url(fyremezzonine_manager_section_statistics_url()); ?>">Статистика секций</a><a href="<?php echo esc_url(wp_logout_url(home_url('/'))); ?>">Выйти</a></div>
<?php endif; ?>
