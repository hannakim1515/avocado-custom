(() => {
  document.documentElement.classList.add('combat-editor-page');
  document.body.classList.add('combat-editor-page');
  const menu = document.querySelector('.adminGnbArea');
  const controls = document.querySelector('.adminHeader aside');
  if (!menu || !controls) return;
  if (!menu.id) menu.id = 'combat-admin-menu';
  const toggle = document.createElement('button');
  toggle.type = 'button';
  toggle.className = 'combat-menu-toggle';
  toggle.textContent = '관리 메뉴';
  toggle.setAttribute('aria-controls', menu.id);
  toggle.setAttribute('aria-expanded', 'false');
  toggle.addEventListener('click', () => {
    const open = toggle.getAttribute('aria-expanded') !== 'true';
    toggle.setAttribute('aria-expanded', String(open));
    document.body.classList.toggle('combat-menu-open', open);
  });
  controls.prepend(toggle);
})();
