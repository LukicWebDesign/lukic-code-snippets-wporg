<?php
/**
 * Snippet: Hide Author Slugs
 * Description: Protects author usernames by encrypting URL slugs and securing REST API endpoints.
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'Lukic_hide_author_slugs_init' ) ) {

	class Lukic_Hide_Author_Slugs {

		/**
		 * Constructor
		 */
		public function __construct() {
			// Add submenu page
			add_action( 'admin_menu', array( $this, 'add_submenu_page' ) );

			// Encrypt author link
			add_filter( 'author_link', array( $this, 'filter_author_link' ), 10, 3 );

			// Handle encrypted slug in main query
			add_filter( 'request', array( $this, 'detect_author_archive' ) );

			// Redirect old slugs and ?author=N queries
			add_action( 'template_redirect', array( $this, 'redirect_old_slugs' ) );

			// Protect REST API
			add_filter( 'rest_prepare_user', array( $this, 'protect_rest_api' ), 10, 3 );
		}

		/**
		 * Add submenu page
		 */
		public function add_submenu_page() {
			add_submenu_page(
				'lukic-code-snippets',
				__( 'Hide Author Slugs', 'lukic-code-snippets' ),
				__( 'Hide Author Slugs', 'lukic-code-snippets' ),
				'manage_options',
				'lukic-hide-author-slugs',
				array( $this, 'render_page' )
			);
		}

		/**
		 * Render the settings page
		 */
		public function render_page() {
			// Include the header partial if available, otherwise simple header
			if ( function_exists( 'Lukic_display_header' ) ) {
				Lukic_display_header( __( 'Hide Author Slugs', 'lukic-code-snippets' ), array() );
			} else {
				echo '<h1>' . esc_html__( 'Hide Author Slugs', 'lukic-code-snippets' ) . '</h1>';
			}
			?>
			<div class="wrap Lukic-settings-wrap">
				<div class="Lukic-settings-intro">
					<p><?php esc_html_e( 'This module protects your site by encrypting author URLs and hiding usernames from the REST API. No configuration is required.', 'lukic-code-snippets' ); ?></p>
				</div>
				
				<div class="Lukic-settings-container">
					<div class="">
						<div class="Lukic-card-header">
							<h2><?php esc_html_e( 'Status', 'lukic-code-snippets' ); ?></h2>
						</div>
						<div class="Lukic-card-body">
							<p>
								<span class="dashicons dashicons-yes" style="color: #46b450;"></span>
								<strong><?php esc_html_e( 'Active:', 'lukic-code-snippets' ); ?></strong>
								<?php esc_html_e( 'Author slugs are currently being encrypted.', 'lukic-code-snippets' ); ?>
							</p>
							<p>
								<?php esc_html_e( 'Example:', 'lukic-code-snippets' ); ?>
								<code><?php echo esc_url( get_author_posts_url( get_current_user_id() ) ); ?></code>
							</p>
						</div>
					</div>
				</div>
			</div>
			<?php
		}

		/**
		 * Encrypt user ID to a hash
		 */
		private function encrypt_slug( $user_id ) {
			$salt = wp_salt( 'auth' );
			// Simple reversible encryption for this purpose
			// Using base64 to make it URL safe, but with a salt to make it unique to the site
			$string = $user_id . '|' . $salt;
			return md5( $string ); 
			// Note: MD5 is one-way. We need a way to lookup. 
			// Actually, for "No Configuration" and performance, we might need a reversible encryption 
			// OR we just check all users (slow) OR we store the hash in user meta.
			// Storing in user meta is best for performance.
		}

		/**
		 * Get user ID from hash
		 */
		private function get_user_by_hash( $hash ) {
			// Try to find user with this hash in meta
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key, WordPress.DB.SlowDBQuery.slow_db_query_meta_value
			$users = get_users( array(
				'meta_key'   => 'lukic_author_slug_hash',
				'meta_value' => $hash,
				'number'     => 1,
				'fields'     => 'ID',
			) );

			if ( ! empty( $users ) ) {
				return $users[0];
			}

			return false;
		}

		/**
		 * Get or create hash for user
		 */
		private function get_user_hash( $user_id ) {
			$hash = get_user_meta( $user_id, 'lukic_author_slug_hash', true );
			
			if ( empty( $hash ) ) {
				// Generate a random 12-char hex string
				$hash = substr( md5( $user_id . wp_salt() . time() ), 0, 12 );
				update_user_meta( $user_id, 'lukic_author_slug_hash', $hash );
			}

			return $hash;
		}

		/**
		 * Filter the author link to use the hash
		 */
		public function filter_author_link( $link, $author_id, $author_nicename ) {
			$hash = $this->get_user_hash( $author_id );
			if ( $hash ) {
				return str_replace( $author_nicename, $hash, $link );
			}
			return $link;
		}

		/**
		 * Detect if the requested author is a hash
		 */
		public function detect_author_archive( $query_vars ) {
			if ( isset( $query_vars['author_name'] ) ) {
				$hash = $query_vars['author_name'];
				$user_id = $this->get_user_by_hash( $hash );

				if ( $user_id ) {
					$query_vars['author'] = $user_id;
					unset( $query_vars['author_name'] );
				}
			}
			return $query_vars;
		}

		/**
		 * Redirect old slugs and ?author=N queries
		 */
		public function redirect_old_slugs() {
			if ( is_author() ) {
				$obj = get_queried_object();
				if ( ! $obj instanceof WP_User ) {
					return;
				}

				$user_id = $obj->ID;
				$hash    = $this->get_user_hash( $user_id );
				
				// If we are here, WordPress found the author.
				// We need to check if the URL contains the hash or the nicename/ID.
				
				// Check if it's a ?author=N query
				// phpcs:ignore WordPress.Security.NonceVerification.Recommended
				if ( isset( $_GET['author'] ) && intval( wp_unslash( $_GET['author'] ) ) == $user_id ) {
					wp_safe_redirect( get_author_posts_url( $user_id ), 301 );
					exit;
				}

				// Check if the current URL contains the nicename instead of hash
				// This is a bit tricky because get_author_posts_url is already filtered.
				// We can check the request URI.
				$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';
				if ( strpos( $request_uri, '/' . $obj->user_nicename ) !== false ) {
					wp_safe_redirect( get_author_posts_url( $user_id ), 301 );
					exit;
				}
			}
		}

		/**
		 * Protect REST API
		 */
		public function protect_rest_api( $response, $user, $request ) {
			if ( ! isset( $response->data['slug'] ) ) {
				return $response;
			}

			$hash = $this->get_user_hash( $user->ID );
			
			// Replace slug
			$response->data['slug'] = $hash;
			
			// Replace link
			if ( isset( $response->data['link'] ) ) {
				$response->data['link'] = get_author_posts_url( $user->ID );
			}

			return $response;
		}

	}

	/**
	 * Initialize
	 */
	function Lukic_hide_author_slugs_init() {
		new Lukic_Hide_Author_Slugs();
	}
	add_action( 'init', 'Lukic_hide_author_slugs_init' );
}
