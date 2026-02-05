# CLAUDE.md

This file provides guidance to Claude Code when working with this repository.

## Project Overview

**GreenPulse** - 주요국 재생에너지 뉴스 애그리게이터. RSS 피드를 수집하여 재생에너지 관련 정책, 대규모 프로젝트, 트렌드 뉴스를 국가별로 필터링해 보여주는 웹 애플리케이션.

- **Stack:** Node.js + Express (백엔드), Vanilla HTML/CSS/JS (프론트엔드)
- **Data:** RSS 기반 뉴스 수집 (rss-parser), 10분 메모리 캐시

## Build & Development Commands

- **Install dependencies:** `npm install`
- **Start server:** `npm start` (포트 3000)
- **Dev mode (auto-reload):** `npm run dev`

## Code Style & Conventions

- 한국어 UI, 영문 코드 (변수·함수명은 영문 camelCase)
- 사용자 입력은 반드시 escapeHtml 처리 (XSS 방지)
- RSS 피드 수집 실패 시 해당 소스만 건너뛰고 전체 서비스는 유지

## Project Structure

```
/
├── server.js              # Express 서버 엔트리포인트
├── src/
│   ├── config.js          # 국가·키워드·RSS 피드 설정
│   └── fetcher.js         # RSS 수집·필터링 엔진
├── public/
│   ├── index.html         # 메인 페이지
│   ├── css/style.css      # 스타일
│   └── js/app.js          # 프론트엔드 로직
├── package.json
└── .gitignore
```

## Key Configuration (src/config.js)

- **countries:** 국가 목록 (글로벌, 미국, 유럽, 중국, 일본, 한국, 인도, 호주)
- **keywords:** 재생에너지 키워드 (영문+한글, 에너지원·정책·기술 분류)
- **countryKeywords:** 국가별 필터링 키워드
- **feeds:** RSS 피드 URL 및 소스명

## API Endpoints

- `GET /api/countries` - 국가 목록
- `GET /api/news?country=<code>&q=<query>` - 뉴스 조회 (국가·검색 필터)
- `POST /api/refresh` - 피드 수동 갱신

## Git Workflow

- Use descriptive commit messages that explain the "why" behind changes.
- Keep commits focused and atomic.
