<article class="vn-news">
    <?php if (has_post_thumbnail()) : ?><a class="vn-news-image" href="<?php the_permalink(); ?>" tabindex="-1" aria-hidden="true"><?php the_post_thumbnail('medium_large', array('loading' => 'lazy', 'alt' => '')); ?></a><?php endif; ?>
    <time datetime="<?php echo esc_attr(get_the_date('c')); ?>"><?php echo esc_html(get_the_date('d.m.Y')); ?></time>
    <h3><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
    <?php if (has_excerpt()) : ?><p><?php echo esc_html(wp_strip_all_tags(get_the_excerpt())); ?></p><?php endif; ?>
</article>
