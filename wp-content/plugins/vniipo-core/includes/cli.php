<?php
if (!defined('ABSPATH')) { exit; }

/** Структура основного сайта. */
class VNIIPO_CLI {
    /**
     * Импортирует подготовленный снимок oren.vniipo.ru без затирания редакторских изменений.
     *
     * ## OPTIONS
     *
     * [--apply]
     * : Выполнить импорт. Без параметра показывается только план.
     *
     * @subcommand import-source
     */
    public function import_source($args, $assoc_args) {
        try {
            $result = vniipo_import_source(isset($assoc_args['apply']));
            WP_CLI::log(wp_json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
            if ($result['conflicts'] || $result['missing_pages']) { WP_CLI::warning('Импорт завершён с пропущенными материалами. Проверьте конфликты и недостающие страницы.'); }
        } catch (Throwable $error) { WP_CLI::error($error->getMessage()); }
    }

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
