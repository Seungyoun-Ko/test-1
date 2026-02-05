// 주요국 재생에너지 RSS 피드 및 키워드 설정

const countries = [
  { code: 'global', name: '글로벌', flag: '🌍' },
  { code: 'us', name: '미국', flag: '🇺🇸' },
  { code: 'eu', name: '유럽', flag: '🇪🇺' },
  { code: 'cn', name: '중국', flag: '🇨🇳' },
  { code: 'jp', name: '일본', flag: '🇯🇵' },
  { code: 'kr', name: '한국', flag: '🇰🇷' },
  { code: 'in', name: '인도', flag: '🇮🇳' },
  { code: 'au', name: '호주', flag: '🇦🇺' },
];

// 재생에너지 관련 키워드 (영문 + 한글)
const keywords = [
  // 에너지원
  'renewable energy', 'solar', 'wind energy', 'wind power', 'wind farm',
  'hydrogen', 'green hydrogen', 'geothermal', 'hydropower', 'biomass',
  'offshore wind', 'onshore wind', 'solar panel', 'photovoltaic',
  'tidal energy', 'wave energy', 'battery storage', 'energy storage',
  // 정책·제도
  'carbon neutral', 'net zero', 'net-zero', 'carbon tax', 'green deal',
  'clean energy', 'energy transition', 'decarbonization', 'decarbonisation',
  'climate policy', 'emissions reduction', 'renewable portfolio',
  'feed-in tariff', 'power purchase agreement', 'green bond',
  'carbon credit', 'emissions trading', 'climate target',
  'renewable target', 'clean power', 'green energy',
  // 기술·산업
  'gigafactory', 'electric grid', 'smart grid', 'microgrid',
  'carbon capture', 'CCS', 'CCUS', 'green investment',
  'sustainability', 'sustainable energy', 'EV charging',
  'heat pump', 'electrification', 'power grid',
  // 한글 키워드
  '재생에너지', '신재생에너지', '태양광', '태양에너지', '풍력',
  '수소', '그린수소', '지열', '수력', '바이오매스',
  '탄소중립', '넷제로', '탄소세', '그린뉴딜',
  '에너지전환', '탈탄소', '온실가스', '기후변화',
  '전력망', '스마트그리드', 'ESS', '에너지저장',
  '해상풍력', '육상풍력', '탄소배출권',
];

// 국가별 키워드 (해당 국가 관련 뉴스 필터링용)
const countryKeywords = {
  global: [], // 전체 - 필터 없이 모든 재생에너지 뉴스
  us: ['united states', 'u.s.', 'usa', 'american', 'biden', 'congress', 'doe ', 'department of energy', 'epa ', 'ira ', 'inflation reduction act', 'texas', 'california', '미국'],
  eu: ['europe', 'european', 'eu ', 'germany', 'german', 'france', 'french', 'spain', 'italy', 'netherlands', 'denmark', 'european commission', 'brussels', 'repowereu', 'fit for 55', '유럽', '독일', '프랑스'],
  cn: ['china', 'chinese', 'beijing', 'shanghai', '중국', 'three gorges', 'longi', 'jinko', 'trina'],
  jp: ['japan', 'japanese', 'tokyo', '일본', 'meti ', 'fukushima'],
  kr: ['korea', 'korean', 'seoul', '한국', 'kepco', 'kwater', '한전', '한수원', '산업통상자원부', '환경부'],
  in: ['india', 'indian', 'delhi', 'mumbai', '인도', 'adani green', 'tata power', 'modi'],
  au: ['australia', 'australian', 'sydney', 'melbourne', '호주', 'snowy hydro'],
};

// RSS 피드 소스
const feeds = [
  // 글로벌 에너지 전문매체
  { url: 'https://www.renewableenergyworld.com/feed/', source: 'Renewable Energy World', lang: 'en' },
  { url: 'https://cleantechnica.com/feed/', source: 'CleanTechnica', lang: 'en' },
  { url: 'https://www.pv-magazine.com/feed/', source: 'PV Magazine', lang: 'en' },
  { url: 'https://reneweconomy.com.au/feed/', source: 'RenewEconomy', lang: 'en' },
  { url: 'https://www.rechargenews.com/rss', source: 'Recharge News', lang: 'en' },

  // 주요 언론 - 에너지/환경 섹션
  { url: 'https://www.theguardian.com/environment/rss', source: 'The Guardian - Environment', lang: 'en' },
  { url: 'https://rss.nytimes.com/services/xml/rss/nyt/Climate.xml', source: 'NYT Climate', lang: 'en' },
  { url: 'https://feeds.bbci.co.uk/news/science_and_environment/rss.xml', source: 'BBC Sci & Env', lang: 'en' },
  { url: 'https://www.reuters.com/sustainability/rss', source: 'Reuters Sustainability', lang: 'en' },

  // 한국 매체
  { url: 'https://www.electimes.com/rss/allArticle.xml', source: '전기신문', lang: 'ko' },
  { url: 'https://www.e2news.com/rss/allArticle.xml', source: '이투뉴스', lang: 'ko' },
];

module.exports = { countries, keywords, countryKeywords, feeds };
