<?php
/**
 * Instant Indexing module.
 *
 * @since      1.0.56
 * @package    RankMath
 * @author     Rank Math <support@rankmath.com>
 */

namespace RankMath\Instant_Indexing;

use RankMath\KB;
use RankMath\Helper;
use RankMath\Module\Base;
use RankMath\Traits\Hooker;
use RankMath\Traits\Ajax;
use RankMath\Admin\Options;
use MyThemeShop\Helpers\Param;

defined( 'ABSPATH' ) || exit;

/**
 * Instant_Indexing class.
 */
class Instant_Indexing extends Base {

	use Hooker, Ajax;

	/**
	 * API Object.
	 *
	 * @var string
	 */
	private $api;

	/**
	 * Keep log of submitted objects to avoid double submissions.
	 *
	 * @var array
	 */
	private $submitted = [];

	/**
	 * Restrict to one request every X seconds to a given URL.
	 */
	const THROTTLE_LIMIT = 5;

	/**
	 * Constructor.
	 */
	public function __construct() {
		parent::__construct();

		$this->action( 'admin_enqueue_scripts', 'enqueue', 20 );

		if ( ! $this->is_configured() ) {
			$this->reset_api_key();
		}

		$post_types = Helper::get_settings( 'instant_indexing.bing_post_types', [] );
		foreach ( $post_types as $post_type ) {
			$this->action( 'save_post_' . $post_type, 'save_post', 10, 3 );
			$this->filter( "bulk_actions-edit-{$post_type}", 'post_bulk_actions', 11 );
			$this->filter( "handle_bulk_actions-edit-{$post_type}", 'handle_post_bulk_actions', 10, 3 );
		}

		$this->filter( 'post_row_actions', 'post_row_actions', 10, 2 );
		$this->filter( 'page_row_actions', 'post_row_actions', 10, 2 );
		$this->filter( 'admin_init', 'handle_post_row_actions' );

		$this->action( 'template_redirect', 'serve_api_key' );
		$this->action( 'rest_api_init', 'init_rest_api' );
	}

	/**
	 * Load the REST API endpoints.
	 */
	public function init_rest_api() {
		$rest = new Rest();
		$rest->register_routes();
	}

	/**
	 * Add bulk actions for applicable posts, pages, CPTs.
	 *
	 * @param  array $actions Actions.
	 * @return array          New actions.
	 */
	public function post_bulk_actions( $actions ) {
		$actions['rank_math_indexnow'] = esc_html__( 'Instant Indexing: Submit Pages', 'rank-math' );
		return $actions;
	}

	/**
	 * Action links for the post listing screens.
	 *
	 * @param array  $actions Action links.
	 * @param object $post    Current post object.
	 * @return array
	 */
	public function post_row_actions( $actions, $post ) {
		if ( ! Helper::has_cap( 'general' ) ) {
			return $actions;
		}

		if ( 'publish' !== $post->post_status ) {
			return $actions;
		}

		$post_types = Helper::get_settings( 'instant_indexing.bing_post_types', [] );
		if ( ! in_array( $post->post_type, $post_types, true ) ) {
			return $actions;
		}

		$link = wp_nonce_url(
			add_query_arg(
				[
					'action'        => 'rank_math_instant_index_post',
					'index_post_id' => $post->ID,
					'method'        => 'bing_submit',
				]
			),
			'rank_math_instant_index_post'
		);

		$actions['indexnow_submit'] = '<a href="' . esc_url( $link ) . '" class="rm-instant-indexing-action rm-indexnow-submit">' . __( 'Instant Indexing: Submit Page', 'rank-math' ) . '</a>';

		return $actions;
	}

	/**
	 * Handle post row action link actions.
	 *
	 * @return void
	 */
	public function handle_post_row_actions() {
		if ( 'rank_math_instant_index_post' !== Param::get( 'action' ) ) {
			return;
		}

		$post_id = absint( Param::get( 'index_post_id' ) );
		if ( ! $post_id ) {
			return;
		}

		if ( ! wp_verify_nonce( Param::get( '_wpnonce' ), 'rank_math_instant_index_post' ) ) {
			return;
		}

		if ( ! Helper::has_cap( 'general' ) ) {
			return;
		}

		$this->api_submit( get_permalink( $post_id ), true );

		Helper::redirect( remove_query_arg( [ 'action', 'index_post_id', 'method', '_wpnonce' ] ) );
		exit;
	}

	/**
	 * Handle bulk actions for applicable posts, pages, CPTs.
	 *
	 * @param  string $redirect   Redirect URL.
	 * @param  string $doaction   Performed action.
	 * @param  array  $object_ids Post IDs.
	 *
	 * @return string             New redirect URL.
	 */
	public function handle_post_bulk_actions( $redirect, $doaction, $object_ids ) {
		if ( 'rank_math_indexnow' !== $doaction || empty( $object_ids ) ) {
			return $redirect;
		}

		if ( ! Helper::has_cap( 'general' ) ) {
			return $redirect;
		}

		$urls = [];
		foreach ( $object_ids as $object_id ) {
			$urls[] = get_permalink( $object_id );
		}

		$this->api_submit( $urls, true );

		return $redirect;
	}

	/**
	 * Register admin page.
	 */
	public function register_admin_page() {
		$tabs = [
			'url-submission' => [
				'icon'    => 'rm-icon rm-icon-instant-indexing',
				'title'   => esc_html__( 'Submit URLs', 'rank-math' ),
				'desc'    => esc_html__( 'Send URLs directly to the IndexNow API.', 'rank-math' ) . ' <a href="' . KB::get( 'instant-indexing' ) . '" target="_blank">' . esc_html__( 'Learn more', 'rank-math' ) . '</a>',
				'classes' => 'rank-math-advanced-option',
				'file'    => dirname( __FILE__ ) . '/views/console.php',
			],
			'settings'       => [
				'icon'  => 'rm-icon rm-icon-settings',
				'title' => esc_html__( 'Settings', 'rank-math' ),
				/* translators: Link to kb article */
				'desc'  => sprintf( esc_html__( 'Instant Indexing module settings. %s.', 'rank-math' ), '<a href="' . KB::get( 'instant-indexing' ) . '" target="_blank">' . esc_html__( 'Learn more', 'rank-math' ) . '</a>' ),
				'file'  => dirname( __FILE__ ) . '/views/options.php',
			],
			'history'        => [
				'icon'    => 'rm-icon rm-icon-htaccess',
				'title'   => esc_html__( 'History', 'rank-math' ),
				'desc'    => esc_html__( 'The last 100 IndexNow API requests.', 'rank-math' ),
				'classes' => 'rank-math-advanced-option',
				'file'    => dirname( __FILE__ ) . '/views/history.php',
			],
		];

		/**
		 * Allow developers to add new sections in the IndexNow settings.
		 *
		 * @param array $tabs
		 */
		$tabs = $this->do_filter( 'settings/instant_indexing', $tabs );

		new Options(
			[
				'key'        => 'rank-math-options-instant-indexing',
				'title'      => esc_html__( 'Instant Indexing', 'rank-math' ),
				'menu_title' => esc_html__( 'Instant Indexing', 'rank-math' ),
				'capability' => 'rank_math_general',
				'tabs'       => $tabs,
				'position'   => 100,
			]
		);
	}

	/**
	 * When a post from a watched post type is published or updated, submit its URL
	 * to the API and add notice about it.
	 *
	 * @param  int    $post_id Post ID.
	 * @param  object $post    Post object.
	 *
	 * @return void
	 */
	public function save_post( $post_id, $post ) {
		if ( in_array( $post_id, $this->submitted, true ) ) {
			return;
		}

		if ( ! in_array( $post->post_status, [ 'publish', 'trash' ], true ) ) {
			return;
		}

		if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
			return;
		}

		/**
		 * Filter the URL to be submitted to IndexNow.
		 * Returning false will prevent the URL from being submitted.
		 *
		 * @param string  $url  URL to be submitted.
		 * @param WP_POST $post Post object.
		 */
		$send_url = $this->do_filter( 'instant_indexing/publish_url', get_permalink( $post ), $post );

		// Early exit if filter is set to false.
		if ( ! $send_url ) {
			return;
		}

		$this->api_submit( $send_url, false );
		$this->submitted[] = $post_id;
	}

	/**
	 * Is module configured.
	 *
	 * @return boolean
	 */
	private function is_configured() {
		return (bool) Helper::get_settings( 'instant_indexing.indexnow_api_key' );
	}

	/**
	 * Enqueue CSS & JS.
	 *
	 * @param string $hook Page hook name.
	 * @return void
	 */
	public function enqueue( $hook ) {
		if ( 'rank-math_page_rank-math-options-instant-indexing' !== $hook ) {
			return;
		}

		$uri = untrailingslashit( plugin_dir_url( __FILE__ ) );
		wp_enqueue_script( 'rank-math-instant-indexing', $uri . '/assets/js/instant-indexing.js', [ 'jquery' ], rank_math()->version, true );
		Helper::add_json(
			'indexNow',
			[
				'restUrl'                => rest_url( \RankMath\Rest\Rest_Helper::BASE . '/in' ),
				'refreshHistoryInterval' => 30000,
				'i18n'                   => [
					'submitError'       => esc_html__( 'An error occurred while submitting the URL.', 'rank-math' ),
					'clearHistoryError' => esc_html__( 'Error: could not clear history.', 'rank-math' ),
					'getHistoryError'   => esc_html__( 'Error: could not get history.', 'rank-math' ),
					'noHistory'         => esc_html__( 'No submissions yet.', 'rank-math' ),
				],
			]
		);
	}

	/**
	 * Generate new random API key.
	 */
	private function generate_api_key() {
		$api_key = wp_generate_uuid4();
		$api_key = preg_replace( '[-]', '', $api_key );

		return $api_key;
	}

	/**
	 * Generate and save a new API key.
	 */
	private function reset_api_key() {
		$settings = Helper::get_settings( 'instant_indexing', [] );
		$settings['indexnow_api_key'] = $this->generate_api_key();
		update_option( 'rank-math-options-instant-indexing', $settings );
	}

	/**
	 * Serve API key for search engines.
	 */
	public function serve_api_key() {
		$api_key = Helper::get_settings( 'instant_indexing.indexnow_api_key' );
		global $wp;
		$current_url = home_url( $wp->request );

		if ( isset( $current_url ) && trailingslashit( get_home_url() ) . $api_key . '.txt' === $current_url ) {
			header( 'Content-Type: text/plain' );
			header( 'X-Robots-Tag: noindex' );
			status_header( 200 );
			echo esc_html( $api_key );

			exit();
		}
	}

	/**
	 * Submit URL to IndexNow API.
	 *
	 * @param string $url                  URL to be submitted.
	 * @param bool   $is_manual_submission Whether the URL is submitted manually by the user.
	 *
	 * @return bool
	 */
	private function api_submit( $url, $is_manual_submission ) {
		$api = Api::get();

		if ( ! $is_manual_submission ) {
			$logs = array_values( array_reverse( $api->get_log() ) );
			if ( ! empty( $logs[0] ) && $logs[0]['url'] === $url && time() - $logs[0]['time'] < self::THROTTLE_LIMIT ) {
				return;
			}
		}

		$submitted = $api->submit( $url, $is_manual_submission );

		if ( ! $is_manual_submission ) {
			return $submitted;
		}

		$count = is_array( $url ) ? count( $url ) : 1;
		$this->add_submit_message_notice( $submitted, $count );

		return $submitted;
	}

	/**
	 * Add notice after submitting one or more URLs.
	 *
	 * @param bool $success Whether the submission was successful.
	 * @param int  $count   Number of submitted URLs.
	 *
	 * @return void
	 */
	private function add_submit_message_notice( $success, $count ) {
		$notification_type    = 'error';
		$notification_message = __( 'Error submitting page to IndexNow.', 'rank-math' );

		if ( $success ) {
			$notification_type    = 'success';
			$notification_message = sprintf(
				/* translators: %s: Number of pages submitted. */
				_n( '%s page submitted to IndexNow.', '%s pages submitted to IndexNow.', $count, 'rank-math' ),
				$count
			);
		}

		Helper::add_notification( $notification_message, [ 'type' => $notification_type ] );
	}

}
