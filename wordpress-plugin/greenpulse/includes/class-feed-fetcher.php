<?php
/**
 * RSS Feed Fetcher Class
 *
 * Core RSS fetching and filtering engine for GreenPulse WordPress plugin.
 * Fetches renewable energy news from multiple sources and filters by country and keywords.
 *
 * @package GreenPulse
 * @since 1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * GreenPulse Feed Fetcher Class
 *
 * Handles fetching, filtering, and caching of RSS feeds from renewable energy news sources.
 */
class GreenPulse_Feed_Fetcher {

	/**
	 * RSS feed sources
	 *
	 * @var array
	 */
	private $feeds = array();

	/**
	 * Renewable energy keywords (English + Korean)
	 *
	 * @var array
	 */
	private $keywords = array();

	/**
	 * Country list with codes, names, and flags
	 *
	 * @var array
	 */
	private $countries = array();

	/**
	 * Country-specific filtering keywords
	 *
	 * @var array
	 */
	private $country_keywords = array();

	/**
	 * Cache duration in seconds (default: 10 minutes)
	 *
	 * @var int
	 */
	private $cache_duration = 600;

	/**
	 * Constructor - Initialize feeds, keywords, and countries
	 */
	public function __construct() {
		$this->init_feeds();
		$this->init_keywords();
		$this->init_countries();
		$this->init_country_keywords();
	}

	/**
	 * Initialize RSS feed sources
	 */
	private function init_feeds() {
		$this->feeds = array(
			array(
				'url'    => 'https://www.renewableenergyworld.com/feed/',
				'source' => 'Renewable Energy World',
				'lang'   => 'en',
			),
			array(
				'url'    => 'https://cleantechnica.com/feed/',
				'source' => 'CleanTechnica',
				'lang'   => 'en',
			),
			array(
				'url'    => 'https://www.pv-magazine.com/feed/',
				'source' => 'PV Magazine',
				'lang'   => 'en',
			),
			array(
				'url'    => 'https://reneweconomy.com.au/feed/',
				'source' => 'RenewEconomy',
				'lang'   => 'en',
			),
			array(
				'url'    => 'https://www.rechargenews.com/rss',
				'source' => 'Recharge News',
				'lang'   => 'en',
			),
			array(
				'url'    => 'https://www.theguardian.com/environment/rss',
				'source' => 'The Guardian Environment',
				'lang'   => 'en',
			),
			array(
				'url'    => 'https://rss.nytimes.com/services/xml/rss/nyt/Climate.xml',
				'source' => 'NYT Climate',
				'lang'   => 'en',
			),
			array(
				'url'    => 'https://feeds.bbci.co.uk/news/science_and_environment/rss.xml',
				'source' => 'BBC Sci & Env',
				'lang'   => 'en',
			),
			array(
				'url'    => 'https://www.reuters.com/sustainability/rss',
				'source' => 'Reuters Sustainability',
				'lang'   => 'en',
			),
			array(
				'url'    => 'https://www.electimes.com/rss/allArticle.xml',
				'source' => '전기신문',
				'lang'   => 'ko',
			),
			array(
				'url'    => 'https://www.e2news.com/rss/allArticle.xml',
				'source' => '이투뉴스',
				'lang'   => 'ko',
			),
		);
	}

	/**
	 * Initialize renewable energy keywords
	 */
	private function init_keywords() {
		$this->keywords = array(
			// English keywords
			'renewable energy',
			'solar',
			'wind energy',
			'wind power',
			'wind farm',
			'hydrogen',
			'green hydrogen',
			'geothermal',
			'hydropower',
			'biomass',
			'offshore wind',
			'onshore wind',
			'solar panel',
			'photovoltaic',
			'tidal energy',
			'wave energy',
			'battery storage',
			'energy storage',
			'carbon neutral',
			'net zero',
			'net-zero',
			'carbon tax',
			'green deal',
			'clean energy',
			'energy transition',
			'decarbonization',
			'decarbonisation',
			'climate policy',
			'emissions reduction',
			'feed-in tariff',
			'power purchase agreement',
			'green bond',
			'carbon credit',
			'emissions trading',
			'gigafactory',
			'electric grid',
			'smart grid',
			'microgrid',
			'carbon capture',
			'CCS',
			'CCUS',
			'green investment',
			'sustainability',
			'sustainable energy',
			'EV charging',
			'heat pump',
			'electrification',
			// Korean keywords
			'재생에너지',
			'신재생에너지',
			'태양광',
			'풍력',
			'수소',
			'그린수소',
			'지열',
			'수력',
			'바이오매스',
			'탄소중립',
			'넷제로',
			'탄소세',
			'그린뉴딜',
			'에너지전환',
			'탈탄소',
			'온실가스',
			'기후변화',
			'전력망',
			'스마트그리드',
			'ESS',
			'에너지저장',
			'해상풍력',
			'육상풍력',
			'탄소배출권',
		);
	}

	/**
	 * Initialize country list
	 */
	private function init_countries() {
		$this->countries = array(
			array(
				'code' => 'global',
				'name' => '글로벌',
				'flag' => '🌍',
			),
			array(
				'code' => 'us',
				'name' => '미국',
				'flag' => '🇺🇸',
			),
			array(
				'code' => 'eu',
				'name' => '유럽',
				'flag' => '🇪🇺',
			),
			array(
				'code' => 'cn',
				'name' => '중국',
				'flag' => '🇨🇳',
			),
			array(
				'code' => 'jp',
				'name' => '일본',
				'flag' => '🇯🇵',
			),
			array(
				'code' => 'kr',
				'name' => '한국',
				'flag' => '🇰🇷',
			),
			array(
				'code' => 'in',
				'name' => '인도',
				'flag' => '🇮🇳',
			),
			array(
				'code' => 'au',
				'name' => '호주',
				'flag' => '🇦🇺',
			),
		);
	}

	/**
	 * Initialize country-specific keywords for filtering
	 */
	private function init_country_keywords() {
		$this->country_keywords = array(
			'global' => array(),
			'us'     => array(
				'united states',
				'u.s.',
				'usa',
				'american',
				'biden',
				'congress',
				'doe',
				'department of energy',
				'epa',
				'ira',
				'inflation reduction act',
				'texas',
				'california',
				'미국',
			),
			'eu'     => array(
				'europe',
				'european',
				'eu',
				'germany',
				'german',
				'france',
				'french',
				'spain',
				'italy',
				'netherlands',
				'denmark',
				'european commission',
				'brussels',
				'repowereu',
				'fit for 55',
				'유럽',
				'독일',
				'프랑스',
			),
			'cn'     => array(
				'china',
				'chinese',
				'beijing',
				'shanghai',
				'중국',
				'three gorges',
				'longi',
				'jinko',
				'trina',
			),
			'jp'     => array(
				'japan',
				'japanese',
				'tokyo',
				'일본',
				'meti',
				'fukushima',
			),
			'kr'     => array(
				'korea',
				'korean',
				'seoul',
				'한국',
				'kepco',
				'kwater',
				'한전',
				'한수원',
				'산업통상자원부',
				'환경부',
			),
			'in'     => array(
				'india',
				'indian',
				'delhi',
				'mumbai',
				'인도',
				'adani green',
				'tata power',
				'modi',
			),
			'au'     => array(
				'australia',
				'australian',
				'sydney',
				'melbourne',
				'호주',
				'snowy hydro',
			),
		);
	}

	/**
	 * Get countries list
	 *
	 * @return array Array of country objects
	 */
	public function get_countries() {
		return $this->countries;
	}

	/**
	 * Fetch all RSS feeds, filter by energy keywords, and cache results
	 *
	 * @return array Array of filtered articles sorted by date (newest first)
	 */
	public function fetch_all_feeds() {
		$all_articles = array();

		foreach ( $this->feeds as $feed_info ) {
			$feed = fetch_feed( $feed_info['url'] );

			// Skip if feed fetch failed.
			if ( is_wp_error( $feed ) ) {
				continue;
			}

			$max_items = $feed->get_item_quantity( 50 );
			$items     = $feed->get_items( 0, $max_items );

			foreach ( $items as $item ) {
				$title       = $item->get_title();
				$description = $item->get_description();
				$content     = $item->get_content();
				$full_text   = $title . ' ' . $description . ' ' . $content;

				// Filter by energy keywords.
				if ( ! $this->matches_energy_keywords( $full_text ) ) {
					continue;
				}

				// Build article object.
				$article = array(
					'title'     => $title,
					'link'      => $item->get_permalink(),
					'snippet'   => $this->create_snippet( $description ),
					'source'    => $feed_info['source'],
					'lang'      => $feed_info['lang'],
					'image'     => $this->extract_image( $item ),
					'pub_date'  => $item->get_date( 'Y-m-d H:i:s' ),
					'timestamp' => $item->get_date( 'U' ),
				);

				$all_articles[] = $article;
			}
		}

		// Sort by timestamp (newest first).
		usort(
			$all_articles,
			function ( $a, $b ) {
				return $b['timestamp'] - $a['timestamp'];
			}
		);

		// Cache results using WordPress transient.
		set_transient( 'greenpulse_articles', $all_articles, $this->cache_duration );

		return $all_articles;
	}

	/**
	 * Get filtered articles by country and search query
	 *
	 * @param string $country Country code (default: 'global').
	 * @param string $search  Search query (default: '').
	 * @return array Filtered articles
	 */
	public function get_filtered_articles( $country = 'global', $search = '' ) {
		// Get cached articles.
		$articles = get_transient( 'greenpulse_articles' );

		// If cache is empty, fetch fresh data.
		if ( false === $articles || empty( $articles ) ) {
			$articles = $this->fetch_all_feeds();
		}

		// Fallback to sample articles if still empty.
		if ( empty( $articles ) ) {
			$articles = self::get_sample_articles();
		}

		$filtered = array();

		foreach ( $articles as $article ) {
			$full_text = $article['title'] . ' ' . $article['snippet'];

			// Apply country filter.
			if ( 'global' !== $country && ! $this->matches_country( $full_text, $country ) ) {
				continue;
			}

			// Apply search filter.
			if ( ! empty( $search ) && stripos( $full_text, $search ) === false ) {
				continue;
			}

			$filtered[] = $article;
		}

		return $filtered;
	}

	/**
	 * Check if text contains any renewable energy keywords
	 *
	 * @param string $text Text to check.
	 * @return bool True if matches, false otherwise
	 */
	private function matches_energy_keywords( $text ) {
		$text_lower = mb_strtolower( $text, 'UTF-8' );

		foreach ( $this->keywords as $keyword ) {
			$keyword_lower = mb_strtolower( $keyword, 'UTF-8' );
			if ( stripos( $text_lower, $keyword_lower ) !== false ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Check if text matches country-specific keywords
	 *
	 * @param string $text         Text to check.
	 * @param string $country_code Country code.
	 * @return bool True if matches, false otherwise
	 */
	private function matches_country( $text, $country_code ) {
		// Global always matches.
		if ( 'global' === $country_code ) {
			return true;
		}

		if ( ! isset( $this->country_keywords[ $country_code ] ) ) {
			return false;
		}

		$keywords   = $this->country_keywords[ $country_code ];
		$text_lower = mb_strtolower( $text, 'UTF-8' );

		foreach ( $keywords as $keyword ) {
			$keyword_lower = mb_strtolower( $keyword, 'UTF-8' );
			if ( stripos( $text_lower, $keyword_lower ) !== false ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Extract image URL from RSS item
	 *
	 * @param SimplePie_Item $item RSS feed item.
	 * @return string Image URL or empty string
	 */
	private function extract_image( $item ) {
		// Try enclosure.
		$enclosure = $item->get_enclosure();
		if ( $enclosure && $enclosure->get_thumbnail() ) {
			return $enclosure->get_thumbnail();
		}
		if ( $enclosure && $enclosure->get_link() && strpos( $enclosure->get_type(), 'image' ) !== false ) {
			return $enclosure->get_link();
		}

		// Try media:content or media:thumbnail.
		$media = $item->get_item_tags( 'http://search.yahoo.com/mrss/', 'content' );
		if ( $media && isset( $media[0]['attribs']['']['url'] ) ) {
			return $media[0]['attribs']['']['url'];
		}

		$media_thumb = $item->get_item_tags( 'http://search.yahoo.com/mrss/', 'thumbnail' );
		if ( $media_thumb && isset( $media_thumb[0]['attribs']['']['url'] ) ) {
			return $media_thumb[0]['attribs']['']['url'];
		}

		// Try first image in content.
		$content = $item->get_content();
		if ( preg_match( '/<img[^>]+src=["\']([^"\']+)["\']/', $content, $matches ) ) {
			return $matches[1];
		}

		return '';
	}

	/**
	 * Create snippet from HTML content
	 *
	 * @param string $html HTML content.
	 * @return string Cleaned snippet (max 300 chars)
	 */
	private function create_snippet( $html ) {
		$text = $this->strip_html_tags( $html );
		$text = preg_replace( '/\s+/', ' ', $text );
		$text = trim( $text );

		if ( mb_strlen( $text, 'UTF-8' ) > 300 ) {
			$text = mb_substr( $text, 0, 297, 'UTF-8' ) . '...';
		}

		return $text;
	}

	/**
	 * Strip HTML tags and decode entities
	 *
	 * @param string $html HTML content.
	 * @return string Plain text
	 */
	private function strip_html_tags( $html ) {
		$text = wp_strip_all_tags( $html );
		$text = html_entity_decode( $text, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
		return $text;
	}

	/**
	 * Get sample articles as fallback when feeds fail
	 *
	 * @return array Array of sample articles
	 */
	public static function get_sample_articles() {
		$now = time();

		return array(
			array(
				'title'     => 'DOE Announces $3.5 Billion Investment in Grid-Scale Battery Storage Across U.S.',
				'link'      => '#',
				'snippet'   => 'The U.S. Department of Energy today announced a historic $3.5 billion funding initiative to deploy grid-scale battery storage systems in 15 states, supporting renewable energy integration and grid resilience.',
				'source'    => 'Renewable Energy World',
				'lang'      => 'en',
				'image'     => '',
				'pub_date'  => gmdate( 'Y-m-d H:i:s', $now - 3600 ),
				'timestamp' => $now - 3600,
			),
			array(
				'title'     => 'European Commission Unveils €20 Billion Green Hydrogen Infrastructure Strategy',
				'link'      => '#',
				'snippet'   => 'The European Commission has announced a comprehensive €20 billion investment plan to build cross-border green hydrogen infrastructure, aiming to produce 10 million tonnes of renewable hydrogen annually by 2030.',
				'source'    => 'Recharge News',
				'lang'      => 'en',
				'image'     => '',
				'pub_date'  => gmdate( 'Y-m-d H:i:s', $now - 7200 ),
				'timestamp' => $now - 7200,
			),
			array(
				'title'     => '한국 정부, 서남해 해상풍력 8.2GW 확대 로드맵 발표',
				'link'      => '#',
				'snippet'   => '산업통상자원부는 2030년까지 서남해 해역에 총 8.2GW 규모의 해상풍력단지를 조성하는 종합 로드맵을 발표했다. 이는 약 15조원 규모의 투자와 5만개 일자리 창출 효과가 기대된다.',
				'source'    => '전기신문',
				'lang'      => 'ko',
				'image'     => '',
				'pub_date'  => gmdate( 'Y-m-d H:i:s', $now - 10800 ),
				'timestamp' => $now - 10800,
			),
			array(
				'title'     => 'China Completes World\'s Largest Solar Farm with 5GW Capacity in Qinghai Province',
				'link'      => '#',
				'snippet'   => 'China has completed construction of the world\'s largest solar photovoltaic farm in Qinghai Province, with a total installed capacity of 5 gigawatts. The facility will power over 2 million homes.',
				'source'    => 'Reuters Sustainability',
				'lang'      => 'en',
				'image'     => '',
				'pub_date'  => gmdate( 'Y-m-d H:i:s', $now - 14400 ),
				'timestamp' => $now - 14400,
			),
			array(
				'title'     => 'India Surpasses 200GW Renewable Energy Capacity Milestone Ahead of Schedule',
				'link'      => '#',
				'snippet'   => 'India has achieved its 200 gigawatt renewable energy capacity target six months ahead of schedule, driven by massive solar and wind installations. The country aims for 500GW by 2030.',
				'source'    => 'CleanTechnica',
				'lang'      => 'en',
				'image'     => '',
				'pub_date'  => gmdate( 'Y-m-d H:i:s', $now - 18000 ),
				'timestamp' => $now - 18000,
			),
			array(
				'title'     => 'Japan Approves $4 Billion Offshore Wind Expansion in Northern Waters',
				'link'      => '#',
				'snippet'   => 'The Japanese government has approved a $4 billion offshore wind energy expansion plan targeting 15GW of capacity in northern coastal waters by 2035, part of its carbon neutrality goals.',
				'source'    => 'PV Magazine',
				'lang'      => 'en',
				'image'     => '',
				'pub_date'  => gmdate( 'Y-m-d H:i:s', $now - 21600 ),
				'timestamp' => $now - 21600,
			),
			array(
				'title'     => 'Australia\'s Snowy 2.0 Pumped Hydro Project Reaches 60% Completion',
				'link'      => '#',
				'snippet'   => 'Australia\'s flagship Snowy 2.0 pumped-hydro energy storage project has reached 60% completion, with 2GW/350GWh capacity expected online by 2026 to support renewable energy integration.',
				'source'    => 'RenewEconomy',
				'lang'      => 'en',
				'image'     => '',
				'pub_date'  => gmdate( 'Y-m-d H:i:s', $now - 25200 ),
				'timestamp' => $now - 25200,
			),
			array(
				'title'     => 'Global Wind Power Installations Hit Record 120GW in 2025, GWEC Reports',
				'link'      => '#',
				'snippet'   => 'The Global Wind Energy Council reports that worldwide wind power installations reached a record 120 gigawatts in 2025, with China, the U.S., and Europe leading deployments.',
				'source'    => 'Recharge News',
				'lang'      => 'en',
				'image'     => '',
				'pub_date'  => gmdate( 'Y-m-d H:i:s', $now - 28800 ),
				'timestamp' => $now - 28800,
			),
			array(
				'title'     => '한전, 제주도 스마트그리드 실증단지 2단계 사업 착수',
				'link'      => '#',
				'snippet'   => '한국전력공사는 제주도에서 차세대 마이크로그리드 실증 2단계 사업에 착수했다. AI 기반 에너지관리시스템과 V2G 기술을 접목하여 재생에너지 변동성 대응 능력을 검증할 계획이다.',
				'source'    => '이투뉴스',
				'lang'      => 'ko',
				'image'     => '',
				'pub_date'  => gmdate( 'Y-m-d H:i:s', $now - 32400 ),
				'timestamp' => $now - 32400,
			),
			array(
				'title'     => 'EU Carbon Border Adjustment Mechanism (CBAM) Enters Full Implementation Phase',
				'link'      => '#',
				'snippet'   => 'The European Union\'s Carbon Border Adjustment Mechanism has entered its full implementation phase, imposing carbon tariffs on imports from high-emission countries to level the playing field for EU industries.',
				'source'    => 'The Guardian Environment',
				'lang'      => 'en',
				'image'     => '',
				'pub_date'  => gmdate( 'Y-m-d H:i:s', $now - 36000 ),
				'timestamp' => $now - 36000,
			),
			array(
				'title'     => 'Texas Solar Installations Surpass California for First Time in Q4 2025',
				'link'      => '#',
				'snippet'   => 'Texas has overtaken California in quarterly solar installations for the first time, adding 4.5GW in Q4 2025 compared to California\'s 3.8GW, driven by favorable policies and land availability.',
				'source'    => 'Renewable Energy World',
				'lang'      => 'en',
				'image'     => '',
				'pub_date'  => gmdate( 'Y-m-d H:i:s', $now - 39600 ),
				'timestamp' => $now - 39600,
			),
			array(
				'title'     => 'Chinese Researchers Achieve 33.7% Efficiency in Perovskite-Silicon Tandem Solar Cells',
				'link'      => '#',
				'snippet'   => 'A team from Tsinghua University has achieved a world-record 33.7% power conversion efficiency in perovskite-silicon tandem solar cells, bringing commercial viability of next-generation solar technology closer to reality.',
				'source'    => 'PV Magazine',
				'lang'      => 'en',
				'image'     => '',
				'pub_date'  => gmdate( 'Y-m-d H:i:s', $now - 43200 ),
				'timestamp' => $now - 43200,
			),
		);
	}
}
