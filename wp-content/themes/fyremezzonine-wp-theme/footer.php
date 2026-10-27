<?php
/**
 * Footer template.
 *
 * @package Fyremezzonine
 */
?>
<footer class="site-footer">
    <div class="section-inner footer-inner">
        <span>&copy; 2026 ФГБУ ВНИИПО МЧС России, Оренбургский филиал</span>
        <nav class="footer-links" aria-label="Юридическая информация">
            <a href="<?php echo esc_url(function_exists('get_privacy_policy_url') ? get_privacy_policy_url() : home_url('/privacy-policy/')); ?>">Политика обработки данных</a>
            <a href="https://legal.max.ru/pp" target="_blank" rel="noopener">Политика MAX</a>
        </nav>
    </div>
</footer>
<?php wp_footer(); ?>
</body>
</html>
