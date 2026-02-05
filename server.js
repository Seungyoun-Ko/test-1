const express = require('express');
const path = require('path');
const { fetchAllFeeds, getFilteredArticles } = require('./src/fetcher');
const { countries } = require('./src/config');

const app = express();
const PORT = process.env.PORT || 3000;

// 정적 파일 서빙
app.use(express.static(path.join(__dirname, 'public')));

// API: 국가 목록
app.get('/api/countries', (_req, res) => {
  res.json(countries);
});

// API: 뉴스 목록 (국가 필터, 키워드 검색)
app.get('/api/news', async (req, res) => {
  try {
    const { country = 'global', q = '' } = req.query;
    const articles = await getFilteredArticles(country, q);
    res.json({ country, query: q, count: articles.length, articles });
  } catch (err) {
    console.error('뉴스 조회 오류:', err.message);
    res.status(500).json({ error: '뉴스를 불러오는 데 실패했습니다.' });
  }
});

// API: 수동 새로고침
app.post('/api/refresh', async (_req, res) => {
  try {
    const count = await fetchAllFeeds();
    res.json({ message: `${count}개 기사 갱신 완료` });
  } catch (err) {
    console.error('갱신 오류:', err.message);
    res.status(500).json({ error: '피드 갱신에 실패했습니다.' });
  }
});

// SPA 폴백
app.get('*', (_req, res) => {
  res.sendFile(path.join(__dirname, 'public', 'index.html'));
});

app.listen(PORT, () => {
  console.log(`🌱 재생에너지 뉴스 서버 실행 중: http://localhost:${PORT}`);
  // 서버 시작 시 최초 피드 수집
  fetchAllFeeds().then(count => {
    console.log(`📡 초기 피드 수집 완료: ${count}개 기사`);
  }).catch(err => {
    console.error('초기 피드 수집 실패:', err.message);
  });
});
