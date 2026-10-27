<?php get_header(); ?>
<main id="primary" class="vn-page vn-wrap"><div class="vn-breadcrumbs"><a href="<?php echo esc_url(vniipo_site_url('/')); ?>"><?php esc_html_e('Главная', 'vniipo'); ?></a></div>
<?php if (is_singular()) : while (have_posts()) : the_post(); ?>
    <h1 class="vn-page-heading"><?php the_title(); ?></h1><div class="vn-page-grid"><article class="vn-prose"><?php if (has_post_thumbnail()) { the_post_thumbnail('large'); } if (function_exists('vniipo_render_document_details')) { vniipo_render_document_details(get_the_ID()); } the_content(); if (get_post_type() === 'vniipo_album') { vniipo_render_gallery(get_the_ID()); } ?></article><aside class="vn-sidebar"><h2><?php esc_html_e('Читайте также', 'vniipo'); ?></h2><a href="<?php echo esc_url(vniipo_main_url(get_post_type() === 'vniipo_document' ? 'documents' : (get_post_type() === 'vniipo_album' ? 'media' : 'news'))); ?>"><?php esc_html_e('Все материалы', 'vniipo'); ?> →</a></aside></div>
<?php endwhile; else : ?>
    <h1 class="vn-page-heading"><?php echo esc_html(is_search() ? sprintf(__('Поиск: %s', 'vniipo'), get_search_query()) : __('Публикации', 'vniipo')); ?></h1>
    <?php get_search_form(); ?><div class="vn-section"><?php if (have_posts()) : ?><div class="vn-news-grid"><?php while (have_posts()) : the_post(); get_template_part('template-parts/news-card'); endwhile; ?></div><div class="vn-pager"><?php the_posts_pagination(); ?></div><?php else : ?><p class="vn-empty"><?php esc_html_e('Материалы не найдены. Попробуйте другой запрос.', 'vniipo'); ?></p><?php endif; ?></div>
<?php endif; ?>
</main>
<?php get_footer(); ?>
