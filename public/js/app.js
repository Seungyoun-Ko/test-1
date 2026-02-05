(() => {
  // 상태
  let currentCountry = 'global';
  let currentQuery = '';
  let countries = [];

  // DOM 요소
  const countryTabs = document.getElementById('countryTabs');
  const searchInput = document.getElementById('searchInput');
  const searchBtn = document.getElementById('searchBtn');
  const newsGrid = document.getElementById('newsGrid');
  const statusText = document.getElementById('statusText');
  const refreshBtn = document.getElementById('refreshBtn');
  const emptyState = document.getElementById('emptyState');

  // ===== 초기화 =====
  async function init() {
    await loadCountries();
    await loadNews();
    bindEvents();
  }

  // ===== 국가 탭 로드 =====
  async function loadCountries() {
    try {
      const res = await fetch('/api/countries');
      countries = await res.json();
      renderCountryTabs();
    } catch {
      // 기본 탭
      countries = [{ code: 'global', name: '글로벌', flag: '🌍' }];
      renderCountryTabs();
    }
  }

  function renderCountryTabs() {
    countryTabs.innerHTML = countries.map(c =>
      `<button class="country-tab ${c.code === currentCountry ? 'active' : ''}" data-code="${c.code}">${c.flag} ${c.name}</button>`
    ).join('');
  }

  // ===== 뉴스 로드 =====
  async function loadNews() {
    showLoading();
    try {
      const params = new URLSearchParams({ country: currentCountry });
      if (currentQuery) params.set('q', currentQuery);
      const res = await fetch(`/api/news?${params}`);
      const data = await res.json();
      renderNews(data.articles);
      updateStatus(data.count, data.country);
    } catch {
      statusText.textContent = '뉴스를 불러오는 데 실패했습니다.';
      newsGrid.innerHTML = '';
      emptyState.classList.remove('hidden');
    }
  }

  function showLoading() {
    emptyState.classList.add('hidden');
    newsGrid.innerHTML = Array(6).fill('<div class="skeleton-card"></div>').join('');
    statusText.textContent = '뉴스를 불러오는 중...';
  }

  // ===== 뉴스 렌더링 =====
  function renderNews(articles) {
    if (!articles || articles.length === 0) {
      newsGrid.innerHTML = '';
      emptyState.classList.remove('hidden');
      return;
    }

    emptyState.classList.add('hidden');
    newsGrid.innerHTML = articles.map(article => {
      const date = article.pubDate ? formatDate(article.pubDate) : '';
      const langLabel = article.lang === 'ko' ? 'KO' : 'EN';
      const imageHtml = article.image
        ? `<img class="card-image" src="${escapeHtml(article.image)}" alt="" loading="lazy" onerror="this.outerHTML='<div class=\\'card-image-placeholder\\'>&#9889;</div>'">`
        : `<div class="card-image-placeholder">&#9889;</div>`;

      return `
        <article class="news-card">
          ${imageHtml}
          <div class="card-body">
            <div class="card-source">${escapeHtml(article.source)}</div>
            <h2 class="card-title">
              <a href="${escapeHtml(article.link)}" target="_blank" rel="noopener">${escapeHtml(article.title)}</a>
            </h2>
            <p class="card-snippet">${escapeHtml(article.snippet)}</p>
            <div class="card-meta">
              <time>${date}</time>
              <span class="card-lang">${langLabel}</span>
            </div>
          </div>
        </article>
      `;
    }).join('');
  }

  // ===== 상태 업데이트 =====
  function updateStatus(count, countryCode) {
    const country = countries.find(c => c.code === countryCode);
    const countryName = country ? country.name : countryCode;
    const queryInfo = currentQuery ? ` &middot; "${currentQuery}"` : '';
    statusText.innerHTML = `<strong>${countryName}</strong> 재생에너지 뉴스 <strong>${count}</strong>건${queryInfo}`;
  }

  // ===== 이벤트 바인딩 =====
  function bindEvents() {
    // 국가 탭 클릭
    countryTabs.addEventListener('click', (e) => {
      const tab = e.target.closest('.country-tab');
      if (!tab) return;
      currentCountry = tab.dataset.code;
      document.querySelectorAll('.country-tab').forEach(t => t.classList.remove('active'));
      tab.classList.add('active');
      loadNews();
    });

    // 검색
    searchBtn.addEventListener('click', doSearch);
    searchInput.addEventListener('keydown', (e) => {
      if (e.key === 'Enter') doSearch();
    });

    // 새로고침
    refreshBtn.addEventListener('click', async () => {
      refreshBtn.classList.add('spinning');
      try {
        await fetch('/api/refresh', { method: 'POST' });
        await loadNews();
      } finally {
        refreshBtn.classList.remove('spinning');
      }
    });
  }

  function doSearch() {
    currentQuery = searchInput.value.trim();
    loadNews();
  }

  // ===== 유틸리티 =====
  function formatDate(dateStr) {
    try {
      const d = new Date(dateStr);
      const now = new Date();
      const diff = now - d;
      const hours = Math.floor(diff / 3600000);
      if (hours < 1) return '방금 전';
      if (hours < 24) return `${hours}시간 전`;
      const days = Math.floor(hours / 24);
      if (days < 7) return `${days}일 전`;
      return d.toLocaleDateString('ko-KR', { year: 'numeric', month: 'short', day: 'numeric' });
    } catch {
      return '';
    }
  }

  function escapeHtml(str) {
    if (!str) return '';
    const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' };
    return str.replace(/[&<>"']/g, c => map[c]);
  }

  // 시작
  init();
})();
