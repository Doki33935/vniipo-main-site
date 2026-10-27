<?php get_header(); ?>
<main id="primary" class="vn-page vn-wrap vn-section"><p class="vn-eyebrow">404</p><h1 class="vn-page-heading"><?php esc_html_e('Страница не найдена', 'vniipo'); ?></h1><p><?php esc_html_e('Воспользуйтесь поиском или перейдите на главную страницу.', 'vniipo'); ?></p><?php get_search_form(); ?><div class="vn-actions"><a class="vn-button" href="<?php echo esc_url(home_url('/')); ?>"><?php esc_html_e('На главную', 'vniipo'); ?></a></div></main>
<?php get_footer(); ?>
