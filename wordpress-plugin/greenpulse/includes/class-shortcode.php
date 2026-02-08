<?php
/**
 * Shortcode rendering class for GreenPulse
 *
 * @package GreenPulse
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class GreenPulse_Shortcode {

	/**
	 * Main shortcode handler [greenpulse]
	 */
	public function render( $atts ) {
		$atts = shortcode_atts(
			array( 'default_country' => 'global' ),
			$atts,
			'greenpulse'
		);

		$ad_code_top    = get_option( 'greenpulse_ad_code_1', '' );
		$ad_code_bottom = get_option( 'greenpulse_ad_code_3', '' );

		$fetcher   = new GreenPulse_Feed_Fetcher();
		$countries = $fetcher->get_countries();

		ob_start();
		?>
		<div class="greenpulse-wrap">
			<!-- Header -->
			<div class="greenpulse-header">
				<h2 class="greenpulse-logo">&#9889; GreenPulse</h2>
				<p class="greenpulse-tagline">주요국 재생에너지 정책 &middot; 프로젝트 &middot; 트렌드</p>
			</div>

			<!-- Country Filter Tabs -->
			<div class="greenpulse-filters">
				<div class="greenpulse-country-tabs">
					<?php foreach ( $countries as $c ) : ?>
						<button
							class="greenpulse-tab<?php echo 'global' === $c['code'] ? ' active' : ''; ?>"
							data-country="<?php echo esc_attr( $c['code'] ); ?>"
						>
							<?php echo esc_html( $c['flag'] . ' ' . $c['name'] ); ?>
						</button>
					<?php endforeach; ?>
				</div>
				<div class="greenpulse-search-box">
					<input type="text" class="greenpulse-search-input" id="greenpulse-search-input"
						   placeholder="키워드 검색 (예: 태양광, hydrogen, net zero ...)" />
					<button class="greenpulse-search-btn" id="greenpulse-search-btn">&#128269;</button>
				</div>
			</div>

			<!-- Top Ad -->
			<?php if ( ! empty( $ad_code_top ) ) : ?>
				<div class="greenpulse-ad greenpulse-ad-top"><?php echo wp_kses_post( $ad_code_top ); ?></div>
			<?php endif; ?>

			<!-- Status Bar -->
			<div class="greenpulse-status-bar">
				<span id="greenpulse-status-text">뉴스를 불러오는 중...</span>
				<button class="greenpulse-btn-refresh" id="greenpulse-refresh-btn" title="새로고침">&#8635;</button>
			</div>

			<!-- News Grid -->
			<div id="greenpulse-news-grid" class="greenpulse-news-grid">
				<div class="greenpulse-skeleton-card"><div class="greenpulse-skeleton greenpulse-skeleton-image"></div><div class="greenpulse-skeleton-body"><div class="greenpulse-skeleton greenpulse-skeleton-source"></div><div class="greenpulse-skeleton greenpulse-skeleton-title"></div><div class="greenpulse-skeleton greenpulse-skeleton-snippet"></div></div></div>
				<div class="greenpulse-skeleton-card"><div class="greenpulse-skeleton greenpulse-skeleton-image"></div><div class="greenpulse-skeleton-body"><div class="greenpulse-skeleton greenpulse-skeleton-source"></div><div class="greenpulse-skeleton greenpulse-skeleton-title"></div><div class="greenpulse-skeleton greenpulse-skeleton-snippet"></div></div></div>
				<div class="greenpulse-skeleton-card"><div class="greenpulse-skeleton greenpulse-skeleton-image"></div><div class="greenpulse-skeleton-body"><div class="greenpulse-skeleton greenpulse-skeleton-source"></div><div class="greenpulse-skeleton greenpulse-skeleton-title"></div><div class="greenpulse-skeleton greenpulse-skeleton-snippet"></div></div></div>
			</div>

			<!-- Empty State -->
			<div id="greenpulse-empty" class="greenpulse-empty greenpulse-hidden">
				<div class="greenpulse-empty-icon">&#127758;</div>
				<p class="greenpulse-empty-title">검색 결과가 없습니다</p>
				<p class="greenpulse-empty-message">다른 국가를 선택하거나 검색어를 변경해 보세요.</p>
			</div>

			<!-- Bottom Ad -->
			<?php if ( ! empty( $ad_code_bottom ) ) : ?>
				<div class="greenpulse-ad greenpulse-ad-bottom"><?php echo wp_kses_post( $ad_code_bottom ); ?></div>
			<?php endif; ?>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Render news cards HTML (called from AJAX)
	 */
	public static function render_news_cards( $articles, $ad_code = '', $ad_interval = 4 ) {
		if ( empty( $articles ) ) {
			return '';
		}

		ob_start();
		$count = 0;
		foreach ( $articles as $article ) {
			if ( ! empty( $ad_code ) && $ad_interval > 0 && $count > 0 && 0 === $count % $ad_interval ) {
				echo '<div class="greenpulse-ad greenpulse-ad-middle">' . wp_kses_post( $ad_code ) . '</div>';
			}

			$title   = isset( $article['title'] ) ? $article['title'] : '';
			$link    = isset( $article['link'] ) ? $article['link'] : '#';
			$snippet = isset( $article['snippet'] ) ? $article['snippet'] : '';
			$source  = isset( $article['source'] ) ? $article['source'] : '';
			$lang    = isset( $article['lang'] ) ? $article['lang'] : 'en';
			$image   = isset( $article['image'] ) ? $article['image'] : '';
			$date    = isset( $article['pub_date'] ) ? $article['pub_date'] : '';
			?>
			<article class="greenpulse-card">
				<?php if ( ! empty( $image ) ) : ?>
					<img class="greenpulse-card-image" src="<?php echo esc_url( $image ); ?>" alt="" loading="lazy">
				<?php else : ?>
					<div class="greenpulse-card-placeholder">&#9889;</div>
				<?php endif; ?>
				<div class="greenpulse-card-body">
					<div class="greenpulse-card-source"><?php echo esc_html( $source ); ?></div>
					<h3 class="greenpulse-card-title">
						<a href="<?php echo esc_url( $link ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $title ); ?></a>
					</h3>
					<p class="greenpulse-card-snippet"><?php echo esc_html( mb_substr( $snippet, 0, 150 ) ); ?></p>
					<div class="greenpulse-card-meta">
						<span class="greenpulse-card-date"><?php echo esc_html( self::format_date( $date ) ); ?></span>
						<span class="greenpulse-card-lang"><?php echo esc_html( strtoupper( $lang ) ); ?></span>
					</div>
				</div>
			</article>
			<?php
			$count++;
		}
		return ob_get_clean();
	}

	/**
	 * Format date as Korean relative time
	 */
	public static function format_date( $date_string ) {
		$timestamp = strtotime( $date_string );
		if ( ! $timestamp ) {
			return '';
		}
		$diff = time() - $timestamp;
		if ( $diff < 3600 ) {
			return max( 1, floor( $diff / 60 ) ) . '분 전';
		}
		if ( $diff < 86400 ) {
			return floor( $diff / 3600 ) . '시간 전';
		}
		if ( $diff < 604800 ) {
			return floor( $diff / 86400 ) . '일 전';
		}
		return date( 'Y년 n월 j일', $timestamp );
	}
}
