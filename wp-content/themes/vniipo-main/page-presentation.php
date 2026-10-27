<?php
/** Template Name: Презентационная страница */
if (!defined('ABSPATH')) { exit; }
$preview = function_exists('vniipo_preview_is_active') && vniipo_preview_is_active();
$presentation_page = $preview ? ($GLOBALS['post'] ?? null) : get_queried_object();
if (!$presentation_page instanceof WP_Post) { return; }
$key = vniipo_page_key($presentation_page->ID);
$definition = vniipo_page_definition($key);
get_header();
?>
<main id="primary" class="vn-page vn-wrap vn-presentation">
    <div class="vn-breadcrumbs"><a href="<?php echo esc_url(vniipo_site_url('/')); ?>"><?php esc_html_e('Главная', 'vniipo'); ?></a></div>
    <section class="vn-presentation-hero">
        <div><h1><?php echo esc_html($presentation_page->post_title); ?></h1><?php if ($presentation_page->post_excerpt) : ?><p><?php echo esc_html($presentation_page->post_excerpt); ?></p><?php endif; ?><a class="vn-button vn-button-light" href="<?php echo esc_url(vniipo_main_url('contact-info')); ?>"><?php esc_html_e('Связаться с филиалом', 'vniipo'); ?> →</a></div>
        <?php if (has_post_thumbnail($presentation_page)) { echo get_the_post_thumbnail($presentation_page, 'large'); } ?>
    </section>
    <article class="vn-prose vn-presentation-content">
        <?php if (trim($presentation_page->post_content)) { echo apply_filters('the_content', $presentation_page->post_content); } elseif (!vniipo_render_source_page($key)) { echo '<p>' . esc_html__('Материалы раздела готовятся к публикации.', 'vniipo') . '</p>'; } ?>
        <?php if (!empty($definition['external'])) : ?><p><a class="vn-button" href="<?php echo esc_url($key === 'appeals' ? vniipo_appeals_url() : $definition['external']); ?>"><?php esc_html_e('Открыть официальный ресурс', 'vniipo'); ?> ↗</a></p><?php endif; ?>
        <?php if (!empty($definition['listing'])) { vniipo_render_listing($definition['listing'], $definition['document_category'] ?? ''); } ?>
        <?php if ($key === 'contact-info') { vniipo_render_confirmed_contacts(); } ?>
        <?php vniipo_render_information_blocks('page', $key); ?>
    </article>
    <div class="vn-section-cards">
        <?php foreach (vniipo_structure()['pages'] as $child) : if ($child['path'] === '/' || !$definition || rtrim(dirname(rtrim($child['path'], '/')), '/') . '/' !== $definition['path']) { continue; } ?>
            <a href="<?php echo esc_url(vniipo_main_url($child['key'])); ?>"><h2><?php echo esc_html(vniipo_localized_title($child['key'], $child['title'])); ?></h2><span><?php esc_html_e('Подробнее', 'vniipo'); ?> →</span></a>
        <?php endforeach; ?>
        <?php foreach (vniipo_structure()['links'] as $link) : if ($link['parent'] !== $key) { continue; } ?>
            <a href="<?php echo esc_url(isset($link['target']) ? vniipo_main_url($link['target']) : vniipo_site_url($link['url'])); ?>"><h2><?php echo esc_html($link['title']); ?></h2><span><?php esc_html_e('Подробнее', 'vniipo'); ?> →</span></a>
        <?php endforeach; ?>
    </div>
</main>
<?php get_footer(); ?>
