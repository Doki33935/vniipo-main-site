<?php
if (!defined('ABSPATH') || !function_exists('vniipo_preview_is_active') || !vniipo_preview_is_active()) { exit; }
$definition = $GLOBALS['vniipo_preview_page'];
$key = $definition['key'];
$content_page = vniipo_page_by_key($key);
if ($content_page && get_page_template_slug($content_page->ID) === 'page-presentation.php') {
    $GLOBALS['post'] = $content_page;
    setup_postdata($content_page);
    include __DIR__ . '/page-presentation.php';
    return;
}
get_header();
?>
<main id="primary" class="vn-page vn-wrap">
    <div class="vn-breadcrumbs"><a href="<?php echo esc_url(vniipo_site_url('/')); ?>"><?php esc_html_e('Главная', 'vniipo'); ?></a></div>
    <h1 class="vn-page-heading"><?php echo esc_html($definition['title']); ?></h1>
    <div class="vn-page-grid"><article class="vn-prose">
        <?php if ($content_page && trim($content_page->post_content) !== '') : ?>
            <?php echo wp_kses_post(apply_filters('the_content', $content_page->post_content)); ?>
        <?php elseif (empty($definition['listing']) && !(function_exists('vniipo_render_source_page') && vniipo_render_source_page($key))) : ?>
            <p class="vn-empty"><?php esc_html_e('Материалы раздела готовятся к публикации.', 'vniipo'); ?></p>
        <?php endif; ?>
        <?php if (!empty($definition['external'])) : ?><p><a class="vn-button" href="<?php echo esc_url($key === 'appeals' ? vniipo_appeals_url() : $definition['external']); ?>"><?php esc_html_e('Открыть официальный ресурс', 'vniipo'); ?> ↗</a></p><?php endif; ?>
        <ul class="vn-section-list">
            <?php foreach (vniipo_structure()['pages'] as $child) :
                $parent_path = rtrim(dirname(rtrim($child['path'], '/')), '/') . '/';
                if ($child['path'] === '/' || $parent_path !== $definition['path']) { continue; }
            ?>
                <li><a href="<?php echo esc_url(vniipo_preview_url($child['path'])); ?>"><?php echo esc_html($child['title']); ?> →</a></li>
            <?php endforeach; ?>
            <?php foreach (vniipo_structure()['links'] as $link) : if ($link['parent'] !== $key) { continue; } ?>
                <li><a href="<?php echo esc_url(isset($link['target']) ? vniipo_main_url($link['target']) : vniipo_site_url($link['url'])); ?>"><?php echo esc_html($link['title']); ?> →</a></li>
            <?php endforeach; ?>
        </ul>
        <?php if (!empty($definition['listing'])) { vniipo_render_listing($definition['listing'], $definition['document_category'] ?? ''); } ?>
        <?php if ($key === 'contact-info') { vniipo_render_confirmed_contacts(); } ?>
        <?php if (function_exists('vniipo_render_information_blocks')) { vniipo_render_information_blocks('page', $key); } ?>
    </article><aside class="vn-sidebar"><h2><?php esc_html_e('Разделы сайта', 'vniipo'); ?></h2><?php vniipo_primary_navigation(); ?></aside></div>
</main>
<?php get_footer(); ?>
