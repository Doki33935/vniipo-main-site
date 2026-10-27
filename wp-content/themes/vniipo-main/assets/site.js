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
