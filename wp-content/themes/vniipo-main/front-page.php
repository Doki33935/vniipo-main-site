<?php get_header(); ?>
<main id="primary">
<section class="vn-hero"><div class="vn-hero-rule"></div><div class="vn-wrap vn-hero-grid">
    <div><p class="vn-eyebrow"><?php esc_html_e('Оренбургский филиал ВНИИПО', 'vniipo'); ?></p><h1><?php esc_html_e('Наука на службе пожарной безопасности', 'vniipo'); ?></h1><p><?php esc_html_e('Исследования, испытания и обмен профессиональным опытом. Информация о работе филиала, образовательной деятельности и научных мероприятиях.', 'vniipo'); ?></p><div class="vn-actions"><a class="vn-button vn-button-light" href="<?php echo esc_url(vniipo_main_url('branch')); ?>"><?php esc_html_e('О филиале', 'vniipo'); ?> <span aria-hidden="true">↗</span></a><a class="vn-button vn-button-outline" href="<?php echo esc_url(vniipo_main_url('research')); ?>"><?php esc_html_e('Направления работы', 'vniipo'); ?> <span aria-hidden="true">→</span></a></div></div>
    <div class="vn-seal" aria-hidden="true"><img src="<?php echo esc_url(get_template_directory_uri() . '/assets/oren-vniipo-logo.png'); ?>" alt=""><span class="vn-seal-label">ВНИИПО · ОРЕНБУРГ</span></div>
</div></section>
<section class="vn-section"><div class="vn-wrap"><p class="vn-eyebrow"><?php esc_html_e('Деятельность филиала', 'vniipo'); ?></p><div class="vn-directions">
<?php $directions = array(
    array('01', 'research', __('Научные исследования', 'vniipo'), __('Испытательная лаборатория, полигон и добровольная сертификация в области пожарной безопасности.', 'vniipo')),
    array('02', 'education', __('Образование', 'vniipo'), __('Сведения об образовательной деятельности и информация для слушателей.', 'vniipo')),
    array('03', 'congress', __('Конференции и выставки', 'vniipo'), __('Профессиональные встречи, семинары, круглые столы и материалы мероприятий.', 'vniipo')),
); foreach ($directions as $direction) : ?>
    <article class="vn-direction"><span class="vn-number"><?php echo esc_html($direction[0]); ?></span><h3><?php echo esc_html($direction[2]); ?></h3><p><?php echo esc_html($direction[3]); ?></p><a class="vn-text-link" href="<?php echo esc_url(vniipo_main_url($direction[1])); ?>"><?php esc_html_e('Подробнее', 'vniipo'); ?> →</a></article>
<?php endforeach; ?>
</div></div></section>
<?php $banners = new WP_Query(array('post_type' => 'vniipo_banner', 'post_status' => 'publish', 'posts_per_page' => 3, 'no_found_rows' => true)); if ($banners->have_posts()) : ?>
<section class="vn-section vn-section-tint"><div class="vn-wrap"><div class="vn-section-head"><h2><?php esc_html_e('Анонсы мероприятий', 'vniipo'); ?></h2></div><div class="vn-news-grid"><?php while ($banners->have_posts()) : $banners->the_post(); ?><article class="vn-news"><?php if (has_post_thumbnail()) { the_post_thumbnail('large', array('loading' => 'lazy')); } ?><h3><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3><?php the_excerpt(); ?></article><?php endwhile; ?></div></div></section>
<?php endif; wp_reset_postdata(); ?>
<section class="vn-section vn-section-tint"><div class="vn-wrap"><div class="vn-section-head"><div><p class="vn-eyebrow"><?php esc_html_e('Информационная лента', 'vniipo'); ?></p><h2><?php esc_html_e('Новости филиала', 'vniipo'); ?></h2></div><a class="vn-text-link" href="<?php echo esc_url(vniipo_main_url('news')); ?>"><?php esc_html_e('Все новости', 'vniipo'); ?> →</a></div><?php vniipo_render_news(); ?></div></section>
<section class="vn-section"><div class="vn-wrap"><div class="vn-section-head"><div><p class="vn-eyebrow"><?php esc_html_e('Быстрый доступ', 'vniipo'); ?></p><h2><?php esc_html_e('Информация и сервисы', 'vniipo'); ?></h2></div></div><div class="vn-services">
<?php foreach (array(
    array('documents', __('Новые документы', 'vniipo'), __('Документы и материалы, опубликованные филиалом.', 'vniipo')),
    array('faq', __('Вопрос — ответ', 'vniipo'), __('Ответы на часто задаваемые вопросы.', 'vniipo')),
    array('testing-ground', __('Испытательный полигон', 'vniipo'), __('Информация о полигоне и его возможностях.', 'vniipo')),
    array('certification', __('Сертификация', 'vniipo'), __('Система добровольной сертификации МЧС России.', 'vniipo')),
) as $service) : ?><article class="vn-service"><h3><?php echo esc_html($service[1]); ?></h3><p><?php echo esc_html($service[2]); ?></p><a class="vn-text-link" href="<?php echo esc_url(vniipo_main_url($service[0])); ?>"><?php esc_html_e('Перейти в раздел', 'vniipo'); ?> →</a></article><?php endforeach; ?>
</div></div></section>
<section class="vn-section vn-section-tint"><div class="vn-wrap vn-congress"><div><p class="vn-eyebrow"><?php esc_html_e('Профессиональное сообщество', 'vniipo'); ?></p><h2><?php esc_html_e('Конференции, семинары, круглые столы', 'vniipo'); ?></h2><p><?php esc_html_e('Научные мероприятия филиала: темы, программы, материалы и регистрация участников.', 'vniipo'); ?></p><a class="vn-button" href="<?php echo esc_url(home_url('/conferences/')); ?>"><?php esc_html_e('Все конференции', 'vniipo'); ?> →</a></div><div class="vn-event-list">
<?php $events = new WP_Query(array('post_type' => 'conference', 'post_status' => 'publish', 'posts_per_page' => 3, 'no_found_rows' => true)); if ($events->have_posts()) : while ($events->have_posts()) : $events->the_post(); ?>
    <article class="vn-event"><small><?php echo esc_html(fyremezzonine_conference_date_range(get_the_ID()) ?: __('Дата уточняется', 'vniipo')); ?></small><h3><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3><a class="vn-text-link" href="<?php the_permalink(); ?>"><?php esc_html_e('О мероприятии', 'vniipo'); ?> →</a></article>
<?php endwhile; else : ?><p class="vn-empty"><?php esc_html_e('Опубликованные конференции появятся здесь после подключения контента портала.', 'vniipo'); ?></p><a class="vn-text-link" href="<?php echo esc_url(vniipo_main_url('conference-portal')); ?>"><?php esc_html_e('Действующий портал конференций', 'vniipo'); ?> ↗</a><?php endif; wp_reset_postdata(); ?>
</div></div></section>
<section class="vn-section"><div class="vn-wrap vn-bottom-grid"><div><p class="vn-eyebrow"><?php esc_html_e('Открытая информация', 'vniipo'); ?></p><h2><?php esc_html_e('Посетителям сайта', 'vniipo'); ?></h2><div class="vn-bottom-links"><?php foreach (array('appeals', 'anticorruption', 'antiterror', 'vacancies') as $key) : $definition = function_exists('vniipo_page_definition') ? vniipo_page_definition($key) : null; if (!$definition) { continue; } ?><a href="<?php echo esc_url(vniipo_main_url($key)); ?>"><?php echo esc_html($definition['title']); ?> →</a><?php endforeach; ?></div></div><div><p class="vn-eyebrow"><?php esc_html_e('Связаться с филиалом', 'vniipo'); ?></p><h2><?php esc_html_e('Контакты', 'vniipo'); ?></h2><p><?php esc_html_e('Контактная информация, реквизиты и порядок обращения граждан.', 'vniipo'); ?></p><a class="vn-text-link" href="<?php echo esc_url(vniipo_main_url('contacts')); ?>"><?php esc_html_e('Открыть контакты', 'vniipo'); ?> →</a></div></div></section>
</main>
<?php get_footer(); ?>
