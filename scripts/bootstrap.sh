#!/bin/sh
set -eu
cd /var/www/html
if ! wp core is-installed >/dev/null 2>&1; then
  printf '%s\n' "$WP_ADMIN_PASSWORD" | wp core install \
    --url="$SITE_URL" --title='Оренбургский филиал ВНИИПО' \
    --admin_user="$WP_ADMIN_USER" --admin_email="$WP_ADMIN_EMAIL" \
    --skip-email --prompt=admin_password
  # Только на только что созданном стенде скрываем стандартную тестовую запись.
  wp post update 1 --post_status=draft
fi
wp plugin activate fyremezzonine-conference-manager vniipo-core
wp theme activate vniipo-main
wp option update timezone_string Asia/Yekaterinburg
wp option update blog_public 0
wp rewrite structure '/%postname%/' --hard
wp vniipo scaffold --set-front-page
wp rewrite flush --hard
