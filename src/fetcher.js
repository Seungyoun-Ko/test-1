const RSSParser = require('rss-parser');
const { feeds, keywords, countryKeywords } = require('./config');
const sampleArticles = require('./sampleData');

const parser = new RSSParser({
  timeout: 10000,
  headers: { 'User-Agent': 'GreenEnergyNews/1.0' },
});

// 메모리 캐시
let cachedArticles = [];
let lastFetchTime = 0;
const CACHE_DURATION = 10 * 60 * 1000; // 10분

/**
 * 텍스트에 재생에너지 키워드가 포함되어 있는지 확인
 */
function matchesEnergyKeywords(text) {
  const lower = text.toLowerCase();
  return keywords.some(kw => lower.includes(kw.toLowerCase()));
}

/**
 * 텍스트에 특정 국가 키워드가 포함되어 있는지 확인
 */
function matchesCountry(text, countryCode) {
  if (countryCode === 'global') return true;
  const cKws = countryKeywords[countryCode];
  if (!cKws || cKws.length === 0) return true;
  const lower = text.toLowerCase();
  return cKws.some(kw => lower.includes(kw.toLowerCase()));
}

/**
 * HTML 태그 제거
 */
function stripHtml(html) {
  if (!html) return '';
  return html.replace(/<[^>]*>/g, '').replace(/&[^;]+;/g, ' ').trim();
}

/**
 * 기사에서 대표 이미지 URL 추출
 */
function extractImage(item) {
  // enclosure (미디어)
  if (item.enclosure && item.enclosure.url) return item.enclosure.url;
  // media:content
  if (item['media:content'] && item['media:content'].$.url) return item['media:content'].$.url;
  // content 안의 첫 번째 img 태그
  const content = item['content:encoded'] || item.content || '';
  const imgMatch = content.match(/<img[^>]+src=["']([^"']+)["']/);
  if (imgMatch) return imgMatch[1];
  return null;
}

/**
 * 모든 RSS 피드 수집
 */
async function fetchAllFeeds() {
  const results = await Promise.allSettled(
    feeds.map(async (feed) => {
      try {
        const parsed = await parser.parseURL(feed.url);
        return (parsed.items || []).map(item => {
          const title = item.title || '';
          const snippet = stripHtml(item.contentSnippet || item['content:encoded'] || item.content || item.summary || '');
          const fullText = `${title} ${snippet}`;

          return {
            title,
            link: item.link || '',
            snippet: snippet.slice(0, 300),
            source: feed.source,
            lang: feed.lang,
            image: extractImage(item),
            pubDate: item.pubDate || item.isoDate || null,
            timestamp: new Date(item.pubDate || item.isoDate || Date.now()).getTime(),
            fullText,
          };
        });
      } catch (err) {
        console.warn(`피드 수집 실패 [${feed.source}]: ${err.message}`);
        return [];
      }
    })
  );

  // 재생에너지 키워드 필터링 + 중복 제거
  const seen = new Set();
  const articles = [];

  for (const result of results) {
    const items = result.status === 'fulfilled' ? result.value : [];
    for (const item of items) {
      if (!matchesEnergyKeywords(item.fullText)) continue;
      const key = item.link || item.title;
      if (seen.has(key)) continue;
      seen.add(key);
      articles.push(item);
    }
  }

  // 최신순 정렬
  articles.sort((a, b) => b.timestamp - a.timestamp);

  // RSS 수집 결과가 없으면 샘플 데이터로 폴백
  if (articles.length === 0) {
    console.log('RSS 피드 수집 결과 없음 → 샘플 데이터 사용');
    cachedArticles = sampleArticles;
  } else {
    cachedArticles = articles;
  }
  lastFetchTime = Date.now();
  return cachedArticles.length;
}

/**
 * 필터링된 기사 반환 (국가, 검색어)
 */
async function getFilteredArticles(countryCode = 'global', searchQuery = '') {
  // 캐시가 없거나 만료된 경우 새로 수집
  if (cachedArticles.length === 0 || Date.now() - lastFetchTime > CACHE_DURATION) {
    await fetchAllFeeds();
  }

  let articles = cachedArticles;

  // 국가 필터
  if (countryCode !== 'global') {
    articles = articles.filter(a => matchesCountry(a.fullText, countryCode));
  }

  // 검색어 필터
  if (searchQuery.trim()) {
    const q = searchQuery.toLowerCase();
    articles = articles.filter(a => a.fullText.toLowerCase().includes(q));
  }

  // fullText 필드는 응답에서 제거
  return articles.map(({ fullText, ...rest }) => rest);
}

module.exports = { fetchAllFeeds, getFilteredArticles };
