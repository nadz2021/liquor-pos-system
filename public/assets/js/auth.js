(function(){
  const root = document.documentElement;

  // Theme
  const savedTheme = localStorage.getItem('theme');
  if (savedTheme === 'dark' || savedTheme === 'light') {
    root.setAttribute('data-theme', savedTheme);
  } else {
    // default: match system if possible
    const prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
    root.setAttribute('data-theme', prefersDark ? 'dark' : 'light');
  }

  const themeBtn = document.getElementById('themeToggle');
  if (themeBtn) {
    themeBtn.addEventListener('click', () => {
      const now = root.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
      root.setAttribute('data-theme', now);
      localStorage.setItem('theme', now);
      themeBtn.setAttribute('aria-label', now === 'dark' ? 'Switch to light' : 'Switch to dark');
      themeBtn.innerText = now === 'dark' ? '☀️' : '🌙';
    });
  }

  // Role chips (optional quick-fill)
  const username = document.querySelector('input[name="username"]');
  const pin = document.querySelector('input[name="pin"]');
  const chips = document.querySelectorAll('[data-rolechip]');

  function setActiveChip(el){
    chips.forEach(c => c.classList.remove('active'));
    if (el) el.classList.add('active');
  }

  chips.forEach(chip => {
    chip.addEventListener('click', () => {
      setActiveChip(chip);
      const u = chip.getAttribute('data-username');
      if (u && username) username.value = u;
      if (pin) pin.focus();
    });
  });

  // PIN pad
  const pad = document.getElementById('pinpad');
  const form = document.getElementById('loginForm');

  function appendDigit(d){
    if (!pin) return;
    pin.value = (pin.value || '') + d;
    pin.dispatchEvent(new Event('input'));
  }
  function backspace(){
    if (!pin) return;
    pin.value = (pin.value || '').slice(0, -1);
    pin.dispatchEvent(new Event('input'));
  }
  function clearPin(){
    if (!pin) return;
    pin.value = '';
    pin.dispatchEvent(new Event('input'));
  }
  function submit(){
    if (form) form.submit();
  }

  if (pad) {
    pad.addEventListener('click', (e) => {
      const btn = e.target.closest('button');
      if (!btn) return;

      const t = btn.getAttribute('data-pad');
      if (!t) return;

      if (t === 'bs') return backspace();
      if (t === 'clr') return clearPin();
      if (t === 'enter') return submit();
      appendDigit(t);
    });
  }

  // Keyboard: Enter submits
  if (pin) {
    pin.addEventListener('keydown', (e) => {
      if (e.key === 'Enter') submit();
    });
  }

  // Set icon initial
  if (themeBtn) {
    const now = root.getAttribute('data-theme');
    themeBtn.innerText = now === 'dark' ? '☀️' : '🌙';
  }
})();