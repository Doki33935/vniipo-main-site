# Разработка и установка

## Локальный стенд

Требования: Docker Desktop с Linux containers, Docker Compose v2, PowerShell 7, Git. Node.js 24 используется для `npm run check` и упаковки; приложение работает на PHP/WordPress.

```powershell
./scripts/dev.ps1
```

Первый запуск скачивает образы, генерирует `.env`, устанавливает WordPress с локальным администратором, активирует оба плагина и дочернюю тему, включает красивые URL и создаёт структуру. Время сайта — Asia/Yekaterinburg. Порт по умолчанию 8088; при его изменении одновременно поменять `SITE_PORT` и `SITE_URL` в `.env`.

В `.env` находятся случайные пароли БД и администратора. Файл игнорируется Git. Адрес `admin@example.invalid` — локальный placeholder, не почтовая интеграция. До рабочего запуска адрес заменить на согласованный.

Повторный bootstrap не переустанавливает WordPress, но назначает дочернюю тему и главную страницу. Это скрипт нового локального проекта, а не production deploy. Для добавления страниц к существующему сайту использовать только `wp vniipo scaffold` после backup и проверки конфликтов.

## Частые команды

```powershell
docker compose ps
docker compose logs --tail 80 wordpress
docker compose run --rm cli vniipo scaffold
docker compose run --rm cli eval-file /opt/vniipo/smoke.php
docker compose run --rm cli post list --post_type=conference
docker compose down
```

`docker compose down` останавливает новый стенд и сохраняет именованные тома. Не добавляйте `-v`, если данные нужны: удаление томов удалит локальную БД и файлы WordPress.

## Проверки

`npm run check` проверяет целостность реестра структуры, порядок родителей, уникальность URL, отсутствие конфликтов конференционных путей, ссылки и отсутствие цепочек/циклов 301. Он сверяет зафиксированные исходники с SHA-256, проверяет синтаксис JS и отсутствие типичных токенов/дампов в исходниках. Это базовая гигиена, а не полный secrets scanner или аудит безопасности.

PHP-проверка выполняется в контейнере:

```powershell
docker compose exec -T wordpress sh -c 'find /var/www/html/wp-content/themes/vniipo-main /var/www/html/wp-content/plugins/vniipo-core /opt/vniipo -name "*.php" -exec php -l {} \;'
docker compose run --rm cli eval-file /opt/vniipo/smoke.php
```

Smoke test разрешён только для `local`/`development`. Он проверяет подключение родителя, регистрацию типов и shortcodes, повторный запуск scaffold без дублей, сохранение редакторского содержимого, все URL страниц, конференционные таблицы и наследование карточки конференции. Он временно меняет содержимое страницы «Филиал» и восстанавливает его в `finally`; не запускать одновременно с редактором.

HTTP-проверки: главная, разделы, поиск, 404, архив и регистрация конференций, ответы 301 и их цели. В браузере проверить desktop/mobile, меню с клавиатуры, Escape, отсутствие горизонтального скролла, пустые состояния. Результаты записывать в `status.md`.

## Ручная установка компонентов

1. Создать чистый staging WordPress на PHP 8.3 с поддержкой MySQL/MariaDB.
2. Скопировать четыре каталога из `wp-content/themes` и `wp-content/plugins` в соответствующие каталоги staging.
3. Активировать Conference Manager и VNIIPO Core. Родительская тема должна быть установлена, активировать нужно `VNIIPO Main`.
4. Установить WP-CLI и выполнить `wp vniipo scaffold`; на новом стенде добавить `--set-front-page`.
5. Настроить постоянные ссылки `/%postname%/`, права файлов и редакторских пользователей.
6. Наполнить материалы, согласовать настройки и выполнить проверки.

Настоящая БД конференций переносится по отдельному плану `conference-integration.md`, а не заменяется bootstrap-скриптом. Инструкции старого проекта по Astra Linux полезны для аудита, но не подтверждают совместимость нового PHP-кода с прежней VM.

## Упаковка и обновление

```powershell
npm run check
npm run package
```

`dist/wordpress/wp-content` содержит исходники компонентов, `manifest.json` — SHA-256. Этот пакет не содержит WordPress core, БД, uploads и секретов. Перед обновлением staging сделать backup; production обновлять только после проверки и утверждения версии. Дочерняя тема должна обновляться вместе с совместимыми версиями обоих плагинов и родителя.

## Если Docker не отвечает

Проверить, что Docker Desktop запущен, выбран Linux engine и `docker version` показывает Server. При `permission denied` проверить доступ к Docker named pipe в текущей сессии. При отсутствии daemon исправить запуск Docker/WSL. `npm run check` не требует Docker; он не заменяет PHP и интеграционные проверки.
