<?php
/**
 * Plugin Name: GreenPulse
 * Plugin URI: https://example.com/greenpulse
 * Description: 주요국 재생에너지 뉴스 애그리게이터
 * Version: 1.0.0
 * Author: GreenPulse Team
 * Author URI: https://example.com
 * Text Domain: greenpulse
 * Domain Path: /languages
 * Requires at least: 5.0
 * Requires PHP: 7.2
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 */
define( 'GREENPULSE_VERSION', '1.0.0' );
define( 'GREENPULSE_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'GREENPULSE_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/**
 * The code that runs during plugin activation.
 */
function activate_greenpulse() {
	// Set default options
	$defaults = array(
		'greenpulse_ad_code_1'       => '', // top ad
		'greenpulse_ad_code_2'       => '', // middle ad, between news cards
		'greenpulse_ad_code_3'       => '', // bottom ad
		'greenpulse_ad_interval'     => 4,  // show ad every N cards
		'greenpulse_cache_duration'  => 1800, // 30 min in seconds
	);

	foreach ( $defaults as $option_name => $default_value ) {
		if ( false === get_option( $option_name ) ) {
			add_option( $option_name, $default_value );
		}
	}

	// Schedule cron event
	if ( ! wp_next_scheduled( 'greenpulse_fetch_feeds' ) ) {
		wp_schedule_event( time(), 'greenpulse_30min', 'greenpulse_fetch_feeds' );
	}
}
register_activation_hook( __FILE__, 'activate_greenpulse' );

/**
 * The code that runs during plugin deactivation.
 */
function deactivate_greenpulse() {
	// Clean up transients
	global $wpdb;
	$wpdb->query(
		"DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_greenpulse_%' OR option_name LIKE '_transient_timeout_greenpulse_%'"
	);

	// Clear scheduled cron
	$timestamp = wp_next_scheduled( 'greenpulse_fetch_feeds' );
	if ( $timestamp ) {
		wp_unschedule_event( $timestamp, 'greenpulse_fetch_feeds' );
	}
}
register_deactivation_hook( __FILE__, 'deactivate_greenpulse' );

/**
 * Add custom cron schedule interval (30 minutes).
 *
 * @param array $schedules Array of existing schedules.
 * @return array Modified schedules array.
 */
function greenpulse_add_cron_interval( $schedules ) {
	$schedules['greenpulse_30min'] = array(
		'interval' => 1800, // 30 minutes in seconds
		'display'  => esc_html__( 'Every 30 Minutes', 'greenpulse' ),
	);
	return $schedules;
}
add_filter( 'cron_schedules', 'greenpulse_add_cron_interval' );

/**
 * Include required files.
 */
require_once GREENPULSE_PLUGIN_DIR . 'includes/class-feed-fetcher.php';
require_once GREENPULSE_PLUGIN_DIR . 'includes/class-shortcode.php';
require_once GREENPULSE_PLUGIN_DIR . 'includes/class-admin.php';

/**
 * Initialize the shortcode.
 */
function greenpulse_init_shortcode() {
	$shortcode = new GreenPulse_Shortcode();
	add_shortcode( 'greenpulse', array( $shortcode, 'render' ) );
}
add_action( 'init', 'greenpulse_init_shortcode' );

/**
 * Initialize the admin menu.
 */
function greenpulse_init_admin() {
	if ( is_admin() ) {
		$admin = new GreenPulse_Admin();
		add_action( 'admin_menu', array( $admin, 'register_menu' ) );
		add_action( 'admin_init', array( $admin, 'register_settings' ) );
	}
}
add_action( 'plugins_loaded', 'greenpulse_init_admin' );

/**
 * Register AJAX handlers for loading news.
 */
function greenpulse_ajax_load_news() {
	check_ajax_referer( 'greenpulse_nonce', 'nonce' );

	$country = isset( $_GET['country'] ) ? sanitize_text_field( wp_unslash( $_GET['country'] ) ) : 'global';
	$query   = isset( $_GET['q'] ) ? sanitize_text_field( wp_unslash( $_GET['q'] ) ) : '';

	$fetcher  = new GreenPulse_Feed_Fetcher();
	$articles = $fetcher->get_filtered_articles( $country, $query );

	$ad_code    = get_option( 'greenpulse_ad_code_2', '' );
	$ad_interval = absint( get_option( 'greenpulse_ad_interval', 4 ) );
	$html       = GreenPulse_Shortcode::render_news_cards( $articles, $ad_code, $ad_interval );

	$country_name = $country;
	$countries    = $fetcher->get_countries();
	foreach ( $countries as $c ) {
		if ( $c['code'] === $country ) {
			$country_name = $c['name'];
			break;
		}
	}

	wp_send_json_success( array(
		'html'         => $html,
		'count'        => count( $articles ),
		'country_name' => $country_name,
	) );
}
add_action( 'wp_ajax_greenpulse_load_news', 'greenpulse_ajax_load_news' );
add_action( 'wp_ajax_nopriv_greenpulse_load_news', 'greenpulse_ajax_load_news' );

/**
 * AJAX handler for refreshing feeds.
 */
function greenpulse_ajax_refresh() {
	check_ajax_referer( 'greenpulse_nonce', 'nonce' );

	delete_transient( 'greenpulse_articles' );

	$fetcher = new GreenPulse_Feed_Fetcher();
	$fetcher->fetch_all_feeds();

	wp_send_json_success( array( 'message' => '피드를 새로고침했습니다.' ) );
}
add_action( 'wp_ajax_greenpulse_refresh', 'greenpulse_ajax_refresh' );
add_action( 'wp_ajax_nopriv_greenpulse_refresh', 'greenpulse_ajax_refresh' );

/**
 * Track if shortcode is present on the current page.
 *
 * @var bool
 */
$greenpulse_shortcode_present = false;

/**
 * Check if shortcode is present in content.
 *
 * @param string $content Post content.
 * @return string Unchanged content.
 */
function greenpulse_check_shortcode_presence( $content ) {
	global $greenpulse_shortcode_present;

	if ( has_shortcode( $content, 'greenpulse' ) ) {
		$greenpulse_shortcode_present = true;
	}

	return $content;
}
add_filter( 'the_content', 'greenpulse_check_shortcode_presence', 1 );

/**
 * Enqueue frontend assets only when shortcode is present.
 */
function greenpulse_enqueue_assets() {
	global $greenpulse_shortcode_present, $post;

	// Check post content for shortcode
	if ( is_a( $post, 'WP_Post' ) && has_shortcode( $post->post_content, 'greenpulse' ) ) {
		$greenpulse_shortcode_present = true;
	}

	// Only enqueue if shortcode is present
	if ( $greenpulse_shortcode_present ) {
		// Enqueue CSS
		wp_enqueue_style(
			'greenpulse-css',
			GREENPULSE_PLUGIN_URL . 'assets/css/greenpulse.css',
			array(),
			GREENPULSE_VERSION,
			'all'
		);

		// Enqueue JS
		wp_enqueue_script(
			'greenpulse-js',
			GREENPULSE_PLUGIN_URL . 'assets/js/greenpulse.js',
			array( 'jquery' ),
			GREENPULSE_VERSION,
			true
		);

		// Localize script with AJAX URL and nonce
		wp_localize_script(
			'greenpulse-js',
			'greenpulse_ajax',
			array(
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'nonce'    => wp_create_nonce( 'greenpulse_nonce' ),
			)
		);
	}
}
add_action( 'wp_enqueue_scripts', 'greenpulse_enqueue_assets' );

/**
 * Cron job handler for fetching feeds.
 */
function greenpulse_cron_fetch_feeds() {
	$fetcher = new GreenPulse_Feed_Fetcher();
	$fetcher->refresh_feeds();
}
add_action( 'greenpulse_fetch_feeds', 'greenpulse_cron_fetch_feeds' );
