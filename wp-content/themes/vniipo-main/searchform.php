<form class="vn-search" role="search" method="get" action="<?php echo esc_url(vniipo_site_url('/')); ?>">
    <label><span class="screen-reader-text" style="position:absolute;clip-path:inset(50%);width:1px;height:1px;overflow:hidden"><?php esc_html_e('Поиск по сайту', 'vniipo'); ?></span><input type="search" name="s" value="<?php echo esc_attr(get_search_query()); ?>" placeholder="<?php esc_attr_e('Поиск по сайту', 'vniipo'); ?>" required></label>
    <button type="submit"><?php esc_html_e('Найти', 'vniipo'); ?></button>
</form>
