<?php
/**
 * Admin settings page
 *
 * @package GreenPulse
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * GreenPulse Admin Settings Class
 */
class GreenPulse_Admin {

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_menu' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_post_greenpulse_refresh', array( $this, 'handle_refresh' ) );
	}

	/**
	 * Add settings page to admin menu
	 */
	public function add_menu() {
		add_options_page(
			'GreenPulse 설정',
			'GreenPulse',
			'manage_options',
			'greenpulse',
			array( $this, 'render_page' )
		);
	}

	/**
	 * Register settings and fields
	 */
	public function register_settings() {
		// Register settings.
		register_setting(
			'greenpulse_settings',
			'greenpulse_ad_code_1',
			array(
				'type'              => 'string',
				'sanitize_callback' => 'wp_kses_post',
				'default'           => '',
			)
		);

		register_setting(
			'greenpulse_settings',
			'greenpulse_ad_code_2',
			array(
				'type'              => 'string',
				'sanitize_callback' => 'wp_kses_post',
				'default'           => '',
			)
		);

		register_setting(
			'greenpulse_settings',
			'greenpulse_ad_code_3',
			array(
				'type'              => 'string',
				'sanitize_callback' => 'wp_kses_post',
				'default'           => '',
			)
		);

		register_setting(
			'greenpulse_settings',
			'greenpulse_ad_interval',
			array(
				'type'              => 'integer',
				'sanitize_callback' => 'absint',
				'default'           => 4,
			)
		);

		register_setting(
			'greenpulse_settings',
			'greenpulse_cache_duration',
			array(
				'type'              => 'integer',
				'sanitize_callback' => 'absint',
				'default'           => 1800,
			)
		);

		// Add sections.
		add_settings_section(
			'greenpulse_ads_section',
			'광고 설정',
			array( $this, 'render_ads_section' ),
			'greenpulse'
		);

		add_settings_section(
			'greenpulse_cache_section',
			'캐시 설정',
			array( $this, 'render_cache_section' ),
			'greenpulse'
		);

		// Add fields to ads section.
		add_settings_field(
			'greenpulse_ad_code_1',
			'상단 광고 코드',
			array( $this, 'render_ad_code_1_field' ),
			'greenpulse',
			'greenpulse_ads_section'
		);

		add_settings_field(
			'greenpulse_ad_code_2',
			'뉴스 사이 광고 코드',
			array( $this, 'render_ad_code_2_field' ),
			'greenpulse',
			'greenpulse_ads_section'
		);

		add_settings_field(
			'greenpulse_ad_code_3',
			'하단 광고 코드',
			array( $this, 'render_ad_code_3_field' ),
			'greenpulse',
			'greenpulse_ads_section'
		);

		add_settings_field(
			'greenpulse_ad_interval',
			'광고 삽입 간격',
			array( $this, 'render_ad_interval_field' ),
			'greenpulse',
			'greenpulse_ads_section'
		);

		// Add fields to cache section.
		add_settings_field(
			'greenpulse_cache_duration',
			'캐시 유지 시간(초)',
			array( $this, 'render_cache_duration_field' ),
			'greenpulse',
			'greenpulse_cache_section'
		);
	}

	/**
	 * Render ads section description
	 */
	public function render_ads_section() {
		echo '<p>Google AdSense 등의 광고 코드를 입력하세요.</p>';
	}

	/**
	 * Render cache section description
	 */
	public function render_cache_section() {
		echo '<p>RSS 피드 캐시 설정을 조정하세요.</p>';
	}

	/**
	 * Render ad code 1 field
	 */
	public function render_ad_code_1_field() {
		$value = get_option( 'greenpulse_ad_code_1', '' );
		?>
		<textarea name="greenpulse_ad_code_1" rows="5" cols="50" class="large-text"><?php echo esc_textarea( $value ); ?></textarea>
		<p class="description">상단에 표시될 광고 코드</p>
		<?php
	}

	/**
	 * Render ad code 2 field
	 */
	public function render_ad_code_2_field() {
		$value = get_option( 'greenpulse_ad_code_2', '' );
		?>
		<textarea name="greenpulse_ad_code_2" rows="5" cols="50" class="large-text"><?php echo esc_textarea( $value ); ?></textarea>
		<p class="description">뉴스 카드 사이에 표시될 광고 코드</p>
		<?php
	}

	/**
	 * Render ad code 3 field
	 */
	public function render_ad_code_3_field() {
		$value = get_option( 'greenpulse_ad_code_3', '' );
		?>
		<textarea name="greenpulse_ad_code_3" rows="5" cols="50" class="large-text"><?php echo esc_textarea( $value ); ?></textarea>
		<p class="description">하단에 표시될 광고 코드</p>
		<?php
	}

	/**
	 * Render ad interval field
	 */
	public function render_ad_interval_field() {
		$value = get_option( 'greenpulse_ad_interval', 4 );
		?>
		<input type="number" name="greenpulse_ad_interval" value="<?php echo esc_attr( $value ); ?>" min="1" step="1" />
		<p class="description">뉴스 카드 N개마다 광고 표시</p>
		<?php
	}

	/**
	 * Render cache duration field
	 */
	public function render_cache_duration_field() {
		$value = get_option( 'greenpulse_cache_duration', 1800 );
		?>
		<input type="number" name="greenpulse_cache_duration" value="<?php echo esc_attr( $value ); ?>" min="60" step="1" />
		<p class="description">기본값: 1800초 (30분)</p>
		<?php
	}

	/**
	 * Render settings page
	 */
	public function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// Show success message if feed was refreshed.
		if ( isset( $_GET['refreshed'] ) && 'true' === $_GET['refreshed'] ) {
			?>
			<div class="notice notice-success is-dismissible">
				<p>피드가 성공적으로 갱신되었습니다.</p>
			</div>
			<?php
		}
		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

			<div class="notice notice-info">
				<p><strong>사용 방법:</strong> <code>[greenpulse]</code> 숏코드를 페이지에 추가하세요.</p>
			</div>

			<form action="options.php" method="post">
				<?php
				settings_fields( 'greenpulse_settings' );
				do_settings_sections( 'greenpulse' );
				submit_button( '설정 저장' );
				?>
			</form>

			<hr>

			<h2>피드 관리</h2>
			<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post">
				<?php wp_nonce_field( 'greenpulse_refresh', 'greenpulse_refresh_nonce' ); ?>
				<input type="hidden" name="action" value="greenpulse_refresh" />
				<?php submit_button( '수동 피드 갱신', 'secondary', 'refresh', false ); ?>
			</form>
		</div>
		<?php
	}

	/**
	 * Handle manual feed refresh
	 */
	public function handle_refresh() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Unauthorized' );
		}

		if ( ! isset( $_POST['greenpulse_refresh_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['greenpulse_refresh_nonce'] ) ), 'greenpulse_refresh' ) ) {
			wp_die( 'Invalid nonce' );
		}

		// Delete the transient cache.
		delete_transient( 'greenpulse_news_cache' );

		// Redirect back to settings page.
		wp_redirect(
			add_query_arg(
				array(
					'page'      => 'greenpulse',
					'refreshed' => 'true',
				),
				admin_url( 'options-general.php' )
			)
		);
		exit;
	}
}
