const list = document.querySelector('#stats-list');

function escapeHtml(value = '') {
  return String(value).replace(/[&<>"']/g, char => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[char]));
}

async function loadStatistics() {
  try {
    const response = await fetch('?api=statistics');
    const data = await response.json();
    if (!response.ok) throw new Error(data.error || 'Statistik belum dapat dimuat.');
    document.querySelector('#stats-total').textContent = Number(data.total).toLocaleString('id-ID');
    document.querySelector('#stats-adviser-total').textContent = Number(data.advisers.length).toLocaleString('id-ID');
    const highest = Math.max(1, ...data.advisers.map(item => Number(item.students)));
    list.innerHTML = data.advisers.length ? data.advisers.map((item, index) => {
      const count = Number(item.students);
      const width = Math.max(2, count / highest * 100);
      return `<li class="stats-row"><span class="stats-rank">${String(index + 1).padStart(2, '0')}</span><div class="stats-person"><strong>${escapeHtml(item.name)}</strong><div class="stats-bar" role="img" aria-label="${count} mahasiswa"><span style="width:${width}%"></span></div></div><span class="stats-count"><strong>${count.toLocaleString('id-ID')}</strong><small>mahasiswa</small></span></li>`;
    }).join('') : '<li class="stats-loading">Belum ada data pembimbing.</li>';
  } catch (error) {
    list.innerHTML = `<li class="stats-loading">${escapeHtml(error.message)}</li>`;
  }
}

loadStatistics();
