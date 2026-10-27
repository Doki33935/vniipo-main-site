(() => {
  const { createElement: el, Fragment } = wp.element;
  const { InspectorControls, useBlockProps } = wp.blockEditor;
  const { PanelBody, SelectControl, TextControl, Placeholder } = wp.components;
  wp.blocks.registerBlockType('vniipo/information-blocks', {
    apiVersion: 3,
    title: 'Информационные блоки ВНИИПО',
    description: 'Показывает опубликованный блок по ID или блоки выбранной области.',
    icon: 'screenoptions',
    category: 'widgets',
    attributes: { area: { type: 'string', default: 'manual' }, blockId: { type: 'integer', default: 0 } },
    edit({ attributes, setAttributes }) {
      return el(Fragment, null,
        el(InspectorControls, null, el(PanelBody, { title: 'Источник блоков' },
          el(TextControl, { label: 'ID конкретного блока (0 — вся область)', type: 'number', min: 0, value: attributes.blockId, onChange: value => setAttributes({ blockId: Math.max(0, parseInt(value, 10) || 0) }) }),
          el(SelectControl, { label: 'Область', value: attributes.area, options: [
            { label: 'Ручная вставка', value: 'manual' }, { label: 'Главная — перед новостями', value: 'home-top' },
            { label: 'Главная — после новостей', value: 'home-after-news' }, { label: 'Главная — внизу', value: 'home-bottom' },
            { label: 'Боковая колонка', value: 'sidebar' },
          ], onChange: area => setAttributes({ area }) }),
        )),
        el('div', useBlockProps(), el(Placeholder, { icon: 'screenoptions', label: 'Информационные блоки ВНИИПО' },
          attributes.blockId ? `Опубликованный блок № ${attributes.blockId}` : `Опубликованные блоки области «${attributes.area}». Содержимое редактируется в меню «Информационные блоки».`,
        )),
      );
    },
    save() { return null; },
  });
})();
