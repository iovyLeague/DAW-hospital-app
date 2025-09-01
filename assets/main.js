document.addEventListener('click', function (e) {
    const btn = e.target.closest('.message-toggle');
    if (!btn) return;
  
    const cell = btn.closest('.message-cell');
    const isExpanded = cell.getAttribute('data-expanded') === 'true';
    cell.setAttribute('data-expanded', String(!isExpanded));
    btn.setAttribute('aria-expanded', String(!isExpanded));
    btn.textContent = isExpanded ? 'Show more' : 'Show less';
  });