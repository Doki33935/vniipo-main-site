<?php
if (!defined('ABSPATH')) { exit; }

/** Структура основного сайта. */
class VNIIPO_CLI {
    /**
     * Создаёт недостающие страницы и меню, сохраняя существующий контент.
     *
     * ## OPTIONS
     *
     * [--set-front-page]
     * : Назначить новую главную страницу. Использовать только на новом стенде.
     */
    public function scaffold($args, $assoc_args) {
        try {
            $result = vniipo_scaffold(isset($assoc_args['set-front-page']));
            WP_CLI::success(sprintf('Создано страниц: %d; всего в структуре: %d.', $result['created'], $result['total']));
        } catch (Throwable $error) { WP_CLI::error($error->getMessage()); }
    }
}
WP_CLI::add_command('vniipo', 'VNIIPO_CLI');
