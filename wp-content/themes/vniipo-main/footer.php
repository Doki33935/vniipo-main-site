<footer class="vn-footer"><div class="vn-wrap">
    <div class="vn-footer-top"><p class="vn-footer-title">ФГБУ ВНИИПО МЧС России<br><?php esc_html_e('Оренбургский филиал', 'vniipo'); ?></p><nav class="vn-footer-links" aria-label="<?php esc_attr_e('Ссылки в подвале', 'vniipo'); ?>"><a href="<?php echo esc_url(vniipo_main_url('contacts')); ?>"><?php esc_html_e('Контакты', 'vniipo'); ?></a><a href="<?php echo esc_url(vniipo_main_url('requisites')); ?>"><?php esc_html_e('Реквизиты', 'vniipo'); ?></a><a href="<?php echo esc_url(vniipo_main_url('appeals')); ?>"><?php esc_html_e('Обращения граждан', 'vniipo'); ?></a><?php if (get_privacy_policy_url()) : ?><a href="<?php echo esc_url(get_privacy_policy_url()); ?>"><?php esc_html_e('Обработка персональных данных', 'vniipo'); ?></a><?php endif; ?></nav></div>
    <div class="vn-footer-bottom"><span>© <?php echo esc_html(wp_date('Y')); ?> ФГБУ ВНИИПО МЧС России</span><span><a href="https://vniipo.ru/">ВНИИПО</a> · <a href="https://mchs.gov.ru/">МЧС России</a></span></div>
</div></footer>
<?php wp_footer(); ?>
</body></html>
