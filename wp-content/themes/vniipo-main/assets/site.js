(() => {
  const button = document.querySelector('.vn-menu-toggle');
  const nav = document.querySelector('#vn-navigation');
  if (!button || !nav) return;
  const mobile = window.matchMedia('(max-width: 720px)');
  const reset = () => {
    button.setAttribute('aria-expanded', 'false');
    nav.hidden = mobile.matches;
  };
  reset();
  mobile.addEventListener('change', reset);
  button.addEventListener('click', () => {
    const expanded = button.getAttribute('aria-expanded') === 'true';
    button.setAttribute('aria-expanded', String(!expanded));
    nav.hidden = expanded;
  });
  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && mobile.matches && !nav.hidden) {
      reset();
      button.focus();
    }
  });
})();

document.querySelectorAll('[data-vn-gallery]').forEach(gallery => {
  const slides = Array.from(gallery.querySelectorAll('.vn-gallery-slide'));
  const controls = gallery.querySelector('.vn-gallery-controls');
  if (!slides.length || !controls) return;
  let current = 0;
  const show = index => {
    current = (index + slides.length) % slides.length;
    slides.forEach((slide, position) => { slide.hidden = position !== current; });
    gallery.querySelector('[data-gallery-status]').textContent = `${current + 1} / ${slides.length}`;
  };
  if (slides.length > 1) {
    controls.hidden = false;
    gallery.querySelector('[data-gallery-prev]').addEventListener('click', () => show(current - 1));
    gallery.querySelector('[data-gallery-next]').addEventListener('click', () => show(current + 1));
    gallery.addEventListener('keydown', event => {
      if (event.key === 'ArrowLeft' || event.key === 'ArrowRight') {
        event.preventDefault();
        show(current + (event.key === 'ArrowRight' ? 1 : -1));
      }
    });
  }
  show(0);
});
