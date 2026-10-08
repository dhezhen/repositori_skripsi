const search = document.querySelector('#search');
const yearFilter = document.querySelector('#year');
const adviserFilter = document.querySelector('#adviser');
const sortFilter = document.querySelector('#sort');
const results = document.querySelector('#results');
const pagination = document.querySelector('#pagination');
let currentPage = 1;
let debounce;

function escapeHtml(value = '') {
  return String(value).replace(/[&<>"']/g, char => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[char]));
}

function params(page = 1) {
  const query = new URLSearchParams({api:'theses',page});
  if (search.value.trim()) query.set('q', search.value.trim());
  if (yearFilter.value) query.set('year', yearFilter.value);
  if (adviserFilter.value) query.set('adviser', adviserFilter.value);
  if (sortFilter.value) query.set('sort', sortFilter.value);
  return query;
}

function renderRecord(item, index) {
  const advisors = item.advisors?.length ? item.advisors.map(escapeHtml).join(' · ') : 'Belum tercatat';
  const examiners = item.examiners?.length ? item.examiners.map(escapeHtml).join(' · ') : 'Belum tercatat';
  const tag = item.program ? `<span class="tag">${escapeHtml(item.program)}</span>` : '';
  return `<article class="record" style="--delay:${index * 25}ms">
    <div class="record-year">${escapeHtml(item.year || '—')}</div>
    <div class="record-main"><h3>${escapeHtml(item.title)}</h3><div class="record-author"><span class="author-mark">${escapeHtml((item.name || '?').slice(0,1).toUpperCase())}</span><strong>${escapeHtml(item.name)}</strong>${tag}</div></div>
    <div class="record-advisor"><span class="meta-label">PEMBIMBING</span><span class="advisor">${advisors}</span></div>
    <button class="record-toggle" type="button" aria-label="Lihat nama penguji" aria-expanded="false">⌄</button>
    <div class="record-details"><span class="meta-label">PENGUJI</span><span class="advisor">${examiners}</span></div>
  </article>`;
}

function renderPages(page, pages) {
  if (pages <= 1) { pagination.innerHTML = ''; return; }
  let buttons = `<button class="page-btn arrow" data-page="${page-1}" ${page <= 1 ? 'disabled' : ''} aria-label="Halaman sebelumnya">←</button>`;
  const nums = new Set([1, pages, page-1, page, page+1].filter(n => n >= 1 && n <= pages));
  let previous = 0;
  [...nums].sort((a,b)=>a-b).forEach(n => {
    if (previous && n - previous > 1) buttons += '<span class="ellipsis">…</span>';
    buttons += `<button class="page-btn ${n===page?'active':''}" data-page="${n}">${n}</button>`;
    previous = n;
  });
  buttons += `<button class="page-btn arrow" data-page="${page+1}" ${page >= pages ? 'disabled' : ''} aria-label="Halaman berikutnya">→</button>`;
  pagination.innerHTML = buttons;
}

async function load() {
  results.innerHTML = '<div class="loading"><span class="loader"></span> Memuat koleksi</div>';
  try {
    const response = await fetch(`?${params(currentPage)}`);
    const data = await response.json();
    if (!response.ok) throw new Error(data.error || 'Data tidak dapat dimuat.');
    document.querySelector('#hero-count').textContent = Number(data.all).toLocaleString('id-ID');
    document.querySelector('#result-count').textContent = `(${Number(data.total).toLocaleString('id-ID')})`;
    document.querySelector('#result-note').textContent = data.total ? `Menampilkan ${((data.page-1)*12)+1}–${Math.min(data.page*12,data.total)} dari ${Number(data.total).toLocaleString('id-ID')} karya` : 'Coba kata kunci atau tahun yang berbeda.';
    const selected = yearFilter.value;
    const selectedAdviser = adviserFilter.value;
    yearFilter.innerHTML = '<option value="">Semua tahun</option>' + data.years.map(y=>`<option value="${escapeHtml(y.year)}">${escapeHtml(y.year)} · ${Number(y.count).toLocaleString('id-ID')}</option>`).join('');
    yearFilter.value = selected;
    adviserFilter.innerHTML = '<option value="">Semua dosen</option>' + data.advisors.map(name=>`<option value="${escapeHtml(name)}">${escapeHtml(name)}</option>`).join('');
    adviserFilter.value = selectedAdviser;
    results.innerHTML = data.items.length ? data.items.map(renderRecord).join('') : '<div class="empty"><span>⌕</span><h3>Belum ada karya yang cocok</h3><p>Ubah kata kunci atau pilih tahun lain untuk melihat hasil.</p></div>';
    renderPages(data.page, data.pages);
  } catch (error) {
    results.innerHTML = `<div class="empty"><h3>Data belum dapat dimuat</h3><p>${escapeHtml(error.message)}</p></div>`;
  }
}

search.addEventListener('input', () => { clearTimeout(debounce); debounce = setTimeout(() => { currentPage = 1; load(); }, 220); });
yearFilter.addEventListener('change', () => { currentPage = 1; load(); });
adviserFilter.addEventListener('change', () => { currentPage = 1; load(); });
sortFilter.addEventListener('change', () => { currentPage = 1; load(); });
pagination.addEventListener('click', event => { const button = event.target.closest('[data-page]'); if (button && !button.disabled) { currentPage = Number(button.dataset.page); load(); window.scrollTo({top: document.querySelector('.results-head').offsetTop - 28, behavior:'smooth'}); } });
results.addEventListener('click', event => { const button = event.target.closest('.record-toggle'); if (!button) return; const record = button.closest('.record'); const expanded = record.classList.toggle('expanded'); button.setAttribute('aria-expanded', String(expanded)); button.setAttribute('aria-label', expanded ? 'Sembunyikan nama penguji' : 'Lihat nama penguji'); button.textContent = expanded ? '⌃' : '⌄'; });
load();
