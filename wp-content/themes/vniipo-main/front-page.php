<?php get_header(); ?>
<main id="primary" class="vn-wrap vn-home-layout">
    <div class="vn-home-main">
        <section class="vn-feature" aria-labelledby="vn-feature-title">
            <div class="vn-feature-content">
                <p class="vn-eyebrow"><?php esc_html_e('Наука · Испытания · Образование', 'vniipo'); ?></p>
                <h1 id="vn-feature-title">ФГБУ ВНИИПО МЧС России<span><?php esc_html_e('Оренбургский филиал', 'vniipo'); ?></span></h1>
                <p><?php esc_html_e('Научно-исследовательская, образовательная и конгрессно-выставочная деятельность в области пожарной безопасности.', 'vniipo'); ?></p>
                <a class="vn-button vn-button-light" href="<?php echo esc_url(vniipo_main_url('branch')); ?>"><?php esc_html_e('Информация о филиале', 'vniipo'); ?> <span aria-hidden="true">→</span></a>
            </div>
            <div class="vn-feature-art" aria-hidden="true"><img src="<?php echo esc_url(get_stylesheet_directory_uri() . '/assets/institute-emblem.png'); ?>" width="90" height="90" alt=""><span>ВНИИПО<br>МЧС РОССИИ</span></div>
        </section>

        <?php $banners = new WP_Query(array('post_type' => 'vniipo_banner', 'post_status' => 'publish', 'posts_per_page' => 2, 'no_found_rows' => true)); ?>
        <?php if ($banners->have_posts()) : ?>
            <section class="vn-announcements" aria-label="<?php esc_attr_e('Анонсы мероприятий', 'vniipo'); ?>">
                <?php while ($banners->have_posts()) : $banners->the_post(); ?>
                    <article class="vn-announcement">
                        <?php if (has_post_thumbnail()) { the_post_thumbnail('large', array('loading' => 'lazy')); } ?>
                        <h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
                        <?php the_excerpt(); ?>
                    </article>
                <?php endwhile; ?>
            </section>
        <?php endif; wp_reset_postdata(); ?>

        <nav class="vn-quick-links" aria-label="<?php esc_attr_e('Информационные разделы', 'vniipo'); ?>">
            <?php foreach (array('news' => __('Новости', 'vniipo'), 'faq' => __('Вопрос — ответ', 'vniipo'), 'documents' => __('Новые документы', 'vniipo')) as $key => $label) : ?>
                <a href="<?php echo esc_url(vniipo_main_url($key)); ?>"><?php echo esc_html($label); ?><span aria-hidden="true">→</span></a>
            <?php endforeach; ?>
        </nav>

        <?php if (function_exists('vniipo_render_information_blocks')) { vniipo_render_information_blocks('home-top'); } ?>

        <section class="vn-home-section">
            <div class="vn-section-head"><h2><?php esc_html_e('Новости филиала', 'vniipo'); ?></h2><a href="<?php echo esc_url(vniipo_main_url('news')); ?>"><?php esc_html_e('Все новости', 'vniipo'); ?> →</a></div>
            <?php vniipo_render_news(6); ?>
        </section>

        <?php if (function_exists('vniipo_render_information_blocks')) { vniipo_render_information_blocks('home-after-news'); } ?>

        <section class="vn-home-section">
            <div class="vn-section-head"><h2><?php esc_html_e('Основные направления', 'vniipo'); ?></h2></div>
            <div class="vn-directions">
                <?php foreach (array(
                    array('research', __('Научные исследования', 'vniipo'), __('Испытательная лаборатория, полигон и сертификация.', 'vniipo')),
                    array('education', __('Образовательная деятельность', 'vniipo'), __('Сведения об образовательной деятельности филиала.', 'vniipo')),
                    array('congress', __('Конференции и выставки', 'vniipo'), __('Семинары, круглые столы и научные мероприятия.', 'vniipo')),
                ) as $direction) : ?>
                    <article class="vn-direction"><h3><a href="<?php echo esc_url(vniipo_main_url($direction[0])); ?>"><?php echo esc_html($direction[1]); ?></a></h3><p><?php echo esc_html($direction[2]); ?></p><a class="vn-text-link" href="<?php echo esc_url(vniipo_main_url($direction[0])); ?>"><?php esc_html_e('Подробнее', 'vniipo'); ?> →</a></article>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="vn-home-section">
            <div class="vn-section-head"><h2><?php esc_html_e('Конференции и мероприятия', 'vniipo'); ?></h2><a href="<?php echo esc_url(vniipo_site_url('/conferences/')); ?>"><?php esc_html_e('Все мероприятия', 'vniipo'); ?> →</a></div>
            <?php $events = new WP_Query(array('post_type' => 'conference', 'post_status' => 'publish', 'posts_per_page' => 3, 'no_found_rows' => true)); ?>
            <?php if ($events->have_posts()) : ?>
                <div class="vn-event-list">
                    <?php while ($events->have_posts()) : $events->the_post(); ?>
                        <article class="vn-event"><small><?php echo esc_html(fyremezzonine_conference_date_range(get_the_ID()) ?: __('Дата уточняется', 'vniipo')); ?></small><h3><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3><a class="vn-text-link" href="<?php the_permalink(); ?>"><?php esc_html_e('О мероприятии', 'vniipo'); ?> →</a></article>
                    <?php endwhile; ?>
                </div>
            <?php else : ?>
                <p class="vn-empty"><?php esc_html_e('Опубликованные конференции появятся здесь после подключения контента портала.', 'vniipo'); ?> <a href="<?php echo esc_url(vniipo_main_url('conference-portal')); ?>"><?php esc_html_e('Действующий портал конференций', 'vniipo'); ?> ↗</a></p>
            <?php endif; wp_reset_postdata(); ?>
        </section>
        <?php if (function_exists('vniipo_render_information_blocks')) { vniipo_render_information_blocks('home-bottom'); } ?>
    </div>

    <aside class="vn-home-sidebar" aria-label="<?php esc_attr_e('Информация и сервисы', 'vniipo'); ?>">
        <?php if (function_exists('vniipo_render_information_blocks')) { vniipo_render_information_blocks('sidebar'); } ?>
        <section class="vn-sidebox"><h2><?php esc_html_e('Поиск по сайту', 'vniipo'); ?></h2><div class="vn-sidebox-body"><?php get_search_form(); ?></div></section>
        <a class="vn-appeal-banner" href="<?php echo esc_url(vniipo_main_url('appeals')); ?>"><span><?php esc_html_e('Обращения граждан', 'vniipo'); ?></span><small><?php esc_html_e('Порядок обращения и личного приёма', 'vniipo'); ?></small><b aria-hidden="true">→</b></a>
        <section class="vn-sidebox"><h2><?php esc_html_e('Информация для посетителей', 'vniipo'); ?></h2><ul class="vn-side-links">
            <?php foreach (array('structure', 'leadership', 'education-info', 'requisites', 'vacancies') as $key) : $definition = function_exists('vniipo_page_definition') ? vniipo_page_definition($key) : null; if (!$definition) { continue; } ?>
                <li><a href="<?php echo esc_url(vniipo_main_url($key)); ?>"><?php echo esc_html($definition['title']); ?></a></li>
            <?php endforeach; ?>
        </ul></section>
        <a class="vn-service-banner" href="<?php echo esc_url(vniipo_main_url('certification')); ?>"><span><?php esc_html_e('Сертификация', 'vniipo'); ?></span><small><?php esc_html_e('Система добровольной сертификации МЧС России', 'vniipo'); ?></small></a>
        <a class="vn-service-banner vn-service-banner-light" href="<?php echo esc_url(vniipo_main_url('testing-ground')); ?>"><span><?php esc_html_e('Испытательный полигон', 'vniipo'); ?></span><small><?php esc_html_e('Информация о полигоне филиала', 'vniipo'); ?></small></a>
        <section class="vn-sidebox"><h2><?php esc_html_e('Контактная информация', 'vniipo'); ?></h2><div class="vn-sidebox-body"><?php if (!(function_exists('vniipo_render_contact_summary') && vniipo_render_contact_summary())) : ?><p><?php esc_html_e('Контакты филиала, реквизиты и порядок обращения граждан.', 'vniipo'); ?></p><?php endif; ?><a href="<?php echo esc_url(vniipo_main_url('contact-info')); ?>"><?php esc_html_e('Все контакты', 'vniipo'); ?> →</a></div></section>
        <div class="vn-public-links"><a href="<?php echo esc_url(vniipo_main_url('anticorruption')); ?>"><?php esc_html_e('Противодействие коррупции', 'vniipo'); ?></a><a href="<?php echo esc_url(vniipo_main_url('antiterror')); ?>"><?php esc_html_e('Противодействие терроризму', 'vniipo'); ?></a></div>
    </aside>
</main>
<?php get_footer(); ?>
