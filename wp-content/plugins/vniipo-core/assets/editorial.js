(() => {
  document.querySelectorAll('.vniipo-media-picker').forEach(button => {
    button.addEventListener('click', () => {
      const input = document.getElementById(button.dataset.target);
      if (!input || !window.wp?.media) return;
      const multiple = button.dataset.multiple === '1';
      const frame = wp.media({ title: 'Выбрать файлы', multiple, ...(multiple ? { library: { type: 'image' } } : {}) });
      frame.on('select', () => {
        input.value = frame.state().get('selection').toArray().map(item => item.id).join(',');
        input.dispatchEvent(new Event('change', { bubbles: true }));
      });
      frame.open();
    });
  });
})();
