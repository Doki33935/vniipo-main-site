(() => {
  const settings = window.vniipoSourceImport;
  const output = document.getElementById('vniipo-source-result');
  const plan = document.getElementById('vniipo-source-plan');
  const apply = document.getElementById('vniipo-source-apply');
  if (!settings || !output || !plan || !apply) return;
  const run = async write => {
    plan.disabled = apply.disabled = true;
    const summary = { created: 0, updated: 0, unchanged: 0, conflicts: [], missing_pages: [] };
    try {
      for (let offset = 0; offset < settings.total; offset += 10) {
        output.textContent = `${write ? 'Импорт' : 'Проверка'}: ${offset} / ${settings.total}…`;
        const body = new URLSearchParams({ action: 'vniipo_import_source', nonce: settings.nonce, offset: String(offset), apply: write ? '1' : '0' });
        const response = await fetch(settings.url, { method: 'POST', credentials: 'same-origin', body });
        const result = await response.json();
        if (!response.ok || !result.success) throw new Error(result.data?.message || 'Запрос не выполнен.');
        for (const key of ['created', 'updated', 'unchanged']) summary[key] += result.data[key];
        summary.conflicts.push(...result.data.conflicts);
        summary.missing_pages.push(...result.data.missing_pages);
      }
      output.textContent = `${write ? 'Импорт завершён' : 'План без изменения базы'}\nСоздать: ${summary.created}\nОбновить: ${summary.updated}\nБез изменений: ${summary.unchanged}\nКонфликты: ${summary.conflicts.length}\nОтсутствуют страницы: ${summary.missing_pages.join(', ') || 'нет'}\n${summary.conflicts.map(item => `#${item.id} ${item.title}: ${item.reason}`).join('\n')}`;
    } catch (error) {
      output.textContent += `\nОшибка: ${error.message}\nУже выполненные части сохранены. Повторный запуск проверит изменения и не создаст дубли.`;
    } finally { plan.disabled = apply.disabled = false; }
  };
  plan.addEventListener('click', () => run(false));
  apply.addEventListener('click', () => run(true));
})();
