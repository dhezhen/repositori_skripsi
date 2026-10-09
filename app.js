const search = document.querySelector('#search');
const yearFilter = document.querySelector('#year');
const topicFilter = document.querySelector('#topic');
const algorithmFilter = document.querySelector('#algorithm');
const adviserFilter = document.querySelector('#adviser');
const sortFilter = document.querySelector('#sort');
const searchButton = document.querySelector('.btn-cari');
const navSearchButton = document.querySelector('.search-btn');
const results = document.querySelector('#results');
const pagination = document.querySelector('#pagination');
let currentPage = 1;
let debounce;

const topicKeywords = {
  'Artificial Intelligence': ['artificial intelligence', 'sistem pakar', 'cerdas', 'diagnosa', 'diagnosis penyakit'],
  'Machine Learning': ['machine learning', 'deep learning', 'cnn', 'convolutional neural network', 'neural network', 'transfer learning', 'resnet', 'mobilenet', 'yolo'],
  'Computer Vision': ['pengolahan citra', 'citra digital', 'pengenalan wajah', 'deteksi objek', 'segmentasi', 'pengenalan pola', 'citra', 'gambar', 'computer vision', 'objek', 'wajah'],
  'Mobile Development': ['mobile', 'android', 'berbasis android', 'smartphone', 'aplikasi mobile', 'm-learning', 'm-commerce'],
  'Web Development': ['web', 'website', 'berbasis web', 'aplikasi web', 'sistem informasi geografis'],
  'Data Science': ['data science', 'data mining', 'klasifikasi', 'prediksi', 'peramalan', 'text mining', 'sentimen', 'sentiment analysis', 'rekomendasi'],
  'Game Development': ['game', 'augmented reality', 'virtual reality', 'permainan', 'role playing game', 'gamifikasi', 'edukasi game'],
  'IoT': ['iot', 'internet of things', 'mikrokontroler', 'arduino', 'raspberry', 'sensor', 'smart home', 'monitoring berbasis'],
  'Sistem Informasi': ['sistem informasi', 'sistem informasi manajemen', 'sistem pendukung keputusan', 'spk']
};

function extractTags(title) {
  const tags = [];
  const lowerTitle = title.toLowerCase();
  for (const [tag, keywords] of Object.entries(topicKeywords)) {
    if (keywords.some(kw => lowerTitle.includes(kw))) {
      tags.push(tag);
    }
  }
  return tags.slice(0, 4); // Limit to 4 tags for UI consistency
}

function escapeHtml(value = '') {
  return String(value).replace(/[&<>"']/g, char => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[char]));
}

function params(page = 1) {
  const query = new URLSearchParams({api:'theses',page});
  if (search.value.trim()) query.set('q', search.value.trim());
  if (topicFilter.value) query.set('topic', topicFilter.value);
  if (yearFilter.value) query.set('year', yearFilter.value);
  if (algorithmFilter.value) query.set('algo', algorithmFilter.value);
  if (adviserFilter.value) query.set('adviser', adviserFilter.value);
  if (sortFilter.value) query.set('sort', sortFilter.value);
  return query;
}

function renderRecord(item, index) {
  const advisors = item.advisors?.length ? item.advisors.map(escapeHtml).join('<br>') : 'Belum tercatat';

  const extractedTags = extractTags(item.title);
  let tagsHtml = extractedTags.map(tag => `<span class="tag blue">${escapeHtml(tag)}</span>`).join('');
  if (item.program && extractedTags.length < 4) {
      tagsHtml += `<span class="tag">${escapeHtml(item.program)}</span>`;
  }

  return `<article class="record" style="--delay:${index * 25}ms">
    <div class="record-year">${escapeHtml(item.year || '-')}</div>
    <div class="record-main">
      <h3>${escapeHtml(item.title)}</h3>
      <div class="record-author">
        <div class="author-icon">👤</div>
        <strong>${escapeHtml(item.name)}</strong>
      </div>
      <div class="record-tags">${tagsHtml}</div>
    </div>
    <div class="record-advisor"><span class="meta-label">PEMBIMBING</span><span class="advisor">${advisors}</span></div>
  </article>`;
}

function renderPages(page, pages) {
  if (pages <= 1) { pagination.innerHTML = ''; return; }
  let buttons = `<button class="page-btn arrow" data-page="${page-1}" ${page <= 1 ? 'disabled' : ''}>&lt; Sebelumnya</button>`;
  const nums = new Set([1, pages, page-1, page, page+1].filter(n => n >= 1 && n <= pages));
  let previous = 0;
  [...nums].sort((a,b)=>a-b).forEach(n => {
    if (previous && n - previous > 1) buttons += '<span class="ellipsis">…</span>';
    buttons += `<button class="page-btn num ${n===page?'active':''}" data-page="${n}">${n}</button>`;
    previous = n;
  });
  buttons += `<button class="page-btn arrow" data-page="${page+1}" ${page >= pages ? 'disabled' : ''}>Berikutnya &gt;</button>`;
  pagination.innerHTML = buttons;
}

async function load() {
  results.innerHTML = '<div class="loading"><div class="loader"></div> Memuat koleksi...</div>';
  try {
    const response = await fetch(`?${params(currentPage)}`);
    const data = await response.json();
    if (!response.ok) throw new Error(data.error || 'Data tidak dapat dimuat.');

    const countEl = document.querySelector('#hero-count');
    if (countEl) countEl.textContent = Number(data.all).toLocaleString('id-ID');
    const yearsEl = document.querySelector('#hero-years');
    if (yearsEl && data.years.length) yearsEl.textContent = `${data.years[data.years.length - 1].year}–${data.years[0].year}`;

    document.querySelector('#result-count').textContent = `(${Number(data.total).toLocaleString('id-ID')})`;
    document.querySelector('#result-note').textContent = data.total ? `Menampilkan ${((data.page-1)*12)+1}–${Math.min(data.page*12,data.total)} dari ${Number(data.total).toLocaleString('id-ID')} karya` : 'Coba kata kunci atau tahun yang berbeda.';

    const selected = yearFilter.value;
    const selectedAdviser = adviserFilter.value;
    yearFilter.innerHTML = '<option value="">Semua tahun</option>' + data.years.map(y=>`<option value="${escapeHtml(y.year)}">${escapeHtml(y.year)}</option>`).join('');
    yearFilter.value = selected;
    adviserFilter.innerHTML = '<option value="">Semua dosen</option>' + data.advisors.map(name=>`<option value="${escapeHtml(name)}">${escapeHtml(name)}</option>`).join('');
    adviserFilter.value = selectedAdviser;

    results.innerHTML = data.items.length ? data.items.map(renderRecord).join('') : '<div class="empty"><h3>Belum ada karya yang cocok</h3><p>Ubah kata kunci atau pilih tahun lain untuk melihat hasil.</p></div>';
    renderPages(data.page, data.pages);
  } catch (error) {
    results.innerHTML = `<div class="empty"><h3>Data belum dapat dimuat</h3><p>${escapeHtml(error.message)}</p></div>`;
  }
}

search.addEventListener('input', () => { clearTimeout(debounce); debounce = setTimeout(() => { currentPage = 1; load(); }, 220); });
search.addEventListener('keydown', (e) => { if (e.key === 'Enter') { clearTimeout(debounce); currentPage = 1; load(); } });
searchButton.addEventListener('click', () => { clearTimeout(debounce); currentPage = 1; load(); });
navSearchButton.addEventListener('click', () => search.focus());
yearFilter.addEventListener('change', () => { currentPage = 1; load(); });
topicFilter.addEventListener('change', () => { currentPage = 1; load(); });
algorithmFilter.addEventListener('change', () => { currentPage = 1; load(); });
adviserFilter.addEventListener('change', () => { currentPage = 1; load(); });
sortFilter.addEventListener('change', () => { currentPage = 1; load(); });
document.querySelectorAll('[data-algo]').forEach(button => {
  button.addEventListener('click', () => {
    search.value = '';
    topicFilter.value = '';
    algorithmFilter.value = button.dataset.algo;
    currentPage = 1;
    load();
    document.querySelector('.results-head').scrollIntoView({behavior:'smooth', block:'start'});
  });
});
pagination.addEventListener('click', event => { const button = event.target.closest('[data-page]'); if (button && !button.disabled) { currentPage = Number(button.dataset.page); load(); window.scrollTo({top: document.querySelector('.results-head').offsetTop - 28, behavior:'smooth'}); } });
// Tautan dari halaman Topik Riset membawa ?algo=... untuk langsung memfilter katalog.
const initialAlgorithm = new URLSearchParams(location.search).get('algo');
if (initialAlgorithm) {
  algorithmFilter.value = initialAlgorithm;
  if (algorithmFilter.value) requestAnimationFrame(() => document.querySelector('.filters-shell').scrollIntoView({block:'start'}));
}
load();
