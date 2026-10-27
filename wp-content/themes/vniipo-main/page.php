<?php get_header(); ?>
<main id="primary" class="vn-page vn-wrap">
<?php while (have_posts()) : the_post(); $page_id = get_the_ID(); $key = get_post_meta($page_id, '_vniipo_key', true); $definition = function_exists('vniipo_page_definition') ? vniipo_page_definition($key) : null; ?>
    <div class="vn-breadcrumbs"><a href="<?php echo esc_url(vniipo_site_url('/')); ?>"><?php esc_html_e('Главная', 'vniipo'); ?></a><?php foreach (array_reverse(get_post_ancestors($page_id)) as $ancestor) : ?> / <a href="<?php echo esc_url(get_permalink($ancestor)); ?>"><?php echo esc_html(get_the_title($ancestor)); ?></a><?php endforeach; ?></div>
    <h1 class="vn-page-heading"><?php the_title(); ?></h1>
    <div class="vn-page-grid"><article class="vn-prose">
        <?php if (trim(get_the_content()) !== '') : the_content(); elseif ($definition && empty($definition['listing'])) : ?><p class="vn-empty"><?php esc_html_e('Материалы раздела готовятся к публикации.', 'vniipo'); ?></p><?php endif; ?>
        <?php if (!empty($definition['external'])) : ?><p><a class="vn-button" href="<?php echo esc_url($definition['external']); ?>"><?php echo esc_html($key === 'appeals' || $key === 'symbols' ? __('Перейти на сайт ВНИИПО', 'vniipo') : __('Открыть официальный ресурс', 'vniipo')); ?> ↗</a></p><?php endif; ?>
        <?php $children = get_pages(array('parent' => $page_id, 'sort_column' => 'menu_order,post_title', 'post_status' => 'publish')); if ($children) : ?><ul class="vn-section-list"><?php foreach ($children as $child) : ?><li><a href="<?php echo esc_url(get_permalink($child)); ?>"><?php echo esc_html($child->post_title); ?> →</a></li><?php endforeach; ?></ul><?php endif; ?>
        <?php if (function_exists('vniipo_structure')) : ?><ul class="vn-section-list"><?php foreach (vniipo_structure()['links'] as $link) : if ($link['parent'] !== $key) { continue; } ?><li><a href="<?php echo esc_url(isset($link['target']) ? vniipo_main_url($link['target']) : home_url($link['url'])); ?>"><?php echo esc_html($link['title']); ?> →</a></li><?php endforeach; ?></ul><?php endif; ?>
        <?php if (!empty($definition['listing'])) { vniipo_render_listing($definition['listing']); } ?>
    </article><aside class="vn-sidebar"><h2><?php esc_html_e('Разделы сайта', 'vniipo'); ?></h2><?php wp_nav_menu(array('theme_location' => 'primary', 'container' => false, 'depth' => 1, 'fallback_cb' => 'vniipo_nav_fallback')); ?></aside></div>
<?php endwhile; ?>
</main>
<?php get_footer(); ?>
