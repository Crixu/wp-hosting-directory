<?php
/**
 * WP Hosting Directory Admin Class
 *
 * @package WP_Hosting_Directory
 */

/**
 * Class WP_Hosting_Directory_Admin
 */
class WP_Hosting_Directory_Admin {

	/**
	 * The plugin name.
	 *
	 * @var string
	 */
	private $plugin_name;

	/**
	 * The plugin version.
	 *
	 * @var string
	 */
	private $version;

	/**
	 * Constructor.
	 *
	 * @param string $plugin_name The plugin name.
	 * @param string $version The plugin version.
	 */
	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version     = $version;
	}

	/**
	 * Enqueue styles.
	 */
	public function enqueue_styles() {
		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/wp-hosting-directory-admin.css', array(), $this->version, 'all' );
	}

	/**
	 * Enqueue scripts.
	 */
	public function enqueue_scripts() {
		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/wp-hosting-directory-admin.js', array( 'jquery' ), $this->version, false );
	}

	/**
	 * Register custom post types for hosting providers and products.
	 */
	public function register_hosting_providers_cpt() {
		$provider_args = array(
			'public'          => true,
			'label'           => 'Hosting Providers',
			'supports'        => array( 'title', 'editor', 'custom-fields', 'templates' ),
			'map_meta_cap'    => true,
			'show_in_menu'    => true,
			'capability_type' => 'post',
			'has_archive'     => true,
			'rewrite'         => array( 'slug' => 'hosting_provider' ),
			'labels'          => array(
				'name'               => _x( 'Hosting Providers', 'post type general name', 'wp-hosting-directory' ),
				'singular_name'      => _x( 'Hosting Provider', 'post type singular name', 'wp-hosting-directory' ),
				'menu_name'          => _x( 'Hosting Providers', 'admin menu', 'wp-hosting-directory' ),
				'name_admin_bar'     => _x( 'Hosting Provider', 'add new on admin bar', 'wp-hosting-directory' ),
				'add_new'            => _x( 'Add New Hosting Provider', 'hosting provider', 'wp-hosting-directory' ),
				'add_new_item'       => __( 'Add New Hosting Provider', 'wp-hosting-directory' ),
				'new_item'           => __( 'New Hosting Provider', 'wp-hosting-directory' ),
				'edit_item'          => __( 'Edit Hosting Provider', 'wp-hosting-directory' ),
				'view_item'          => __( 'View Hosting Provider', 'wp-hosting-directory' ),
				'all_items'          => __( 'All Hosting Providers', 'wp-hosting-directory' ),
				'search_items'       => __( 'Search Hosting Providers', 'wp-hosting-directory' ),
				'parent_item_colon'  => __( 'Parent Hosting Providers:', 'wp-hosting-directory' ),
				'not_found'          => __( 'No hosting providers found.', 'wp-hosting-directory' ),
				'not_found_in_trash' => __( 'No hosting providers found in Trash.', 'wp-hosting-directory' ),
			),
		);
		register_post_type( 'hosting_provider', $provider_args );

		$product_args = array(
			'public'          => true,
			'label'           => 'Hosting Products',
			'supports'        => array( 'title', 'editor', 'custom-fields' ),
			'capability_type' => 'post',
			'map_meta_cap'    => true,
			'show_in_menu'    => true,
			'has_archive'     => true,
			'rewrite'         => array( 'slug' => 'hosting_product' ),
		);
		register_post_type( 'hosting_product', $product_args );
	}

	/**
	 * Fetch JSON Data from URL.
	 *
	 * @param string $url The URL to fetch data from.
	 * @return array|false The decoded JSON data or false on failure.
	 */
	private function fetch_hosting_data_from_url( $url ) {
		$response = wp_safe_remote_get( $url );

		if ( is_wp_error( $response ) ) {
			return false;
		}

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		if ( JSON_ERROR_NONE !== json_last_error() ) {
			return false;
		}

		return $data;
	}

	/**
	 * Save Hosting Data.
	 *
	 * @param array $data The hosting data to save.
	 */
	private function save_hosting_data( $data ) {
		$company = $data['company'];

		// Save Company Information.
		$company_post_id = wp_insert_post(
			array(
				'post_type'    => 'hosting_provider',
				'post_title'   => sanitize_text_field( $company['name'] ),
				'post_content' => '',
				'post_status'  => 'pending',
			)
		);

		if ( $company_post_id ) {
			foreach ( $company as $key => $value ) {
				if ( is_array( $value ) ) {
					$value = wp_json_encode( $value );
				}
				update_post_meta( $company_post_id, sanitize_key( $key ), maybe_serialize( $value ) );
			}
		}

		// Save Products Information.
		foreach ( $data['products'] as $product ) {
			$product_post_id = wp_insert_post(
				array(
					'post_type'    => 'hosting_product',
					'post_title'   => sanitize_text_field( $company['name'] . ' - ' . $product['name'] ),
					'post_content' => '',
					'post_status'  => 'pending',
					'post_parent'  => $company_post_id, // Link product to company.
				)
			);

			if ( $product_post_id ) {
				foreach ( $product as $key => $value ) {
					if ( is_array( $value ) ) {
						$value = wp_json_encode( $value );
					}
					update_post_meta( $product_post_id, sanitize_key( $key ), maybe_serialize( $value ) );
				}
			}
		}
	}

	/**
	 * Add review menu.
	 */
	public function add_review_menu() {
		add_menu_page(
			__( 'Hosting Review', 'wp-hosting-directory' ),
			__( 'Hosting Review', 'wp-hosting-directory' ),
			'manage_options',
			'hosting-review',
			array( $this, 'render_review_page' )
		);
	}

	/**
	 * Render review page.
	 */
	public function render_review_page() {
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Upload or Fetch Hosting Data JSON', 'wp-hosting-directory' ); ?></h1>
			<form method="post" enctype="multipart/form-data">
				<?php wp_nonce_field( 'upload_json_action', 'upload_json_nonce' ); ?>
				<h3><?php esc_html_e( 'Upload JSON File', 'wp-hosting-directory' ); ?></h3>
				<input type="file" name="hosting_json_file" accept=".json" />
				<h3><?php esc_html_e( 'Fetch JSON from URL', 'wp-hosting-directory' ); ?></h3>
				<input type="url" name="hosting_json_url" value="" />
				<h3><?php esc_html_e( 'Paste JSON Data', 'wp-hosting-directory' ); ?></h3>
				<textarea name="hosting_json_text" rows="10" cols="50"></textarea>
				<?php submit_button( __( 'Submit JSON', 'wp-hosting-directory' ) ); ?>
			</form>
		</div>
		<?php
		$this->handle_file_upload();
	}

	/**
	 * Handle file upload.
	 */
	private function handle_file_upload() {
		if ( ! isset( $_POST['upload_json_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['upload_json_nonce'] ) ), 'upload_json_action' ) ) {
			return;
		}

		$data = false;

		// Handle file upload.
		if ( isset( $_FILES['hosting_json_file'] ) && isset( $_FILES['hosting_json_file']['error'] ) && UPLOAD_ERR_OK === $_FILES['hosting_json_file']['error'] ) {
			if ( isset( $_FILES['hosting_json_file']['tmp_name'] ) && ! empty( $_FILES['hosting_json_file']['tmp_name'] ) ) {
				$file_tmp_path = sanitize_text_field( wp_unslash( $_FILES['hosting_json_file']['tmp_name'] ) );
				$file_type     = mime_content_type( $file_tmp_path );
				if ( 'application/json' === $file_type ) {
					$file_content = file_get_contents( $file_tmp_path );
					$data         = json_decode( $file_content, true );
				} else {
					echo '<div class="error"><p>' . esc_html__( 'Invalid file type. Please upload a JSON file.', 'wp-hosting-directory' ) . '</p></div>';
				}
			}
		}

		// Handle URL fetch.
		if ( isset( $_POST['hosting_json_url'] ) && ! empty( $_POST['hosting_json_url'] ) ) {
			$url  = esc_url_raw( wp_unslash( $_POST['hosting_json_url'] ) );
			$data = $this->fetch_hosting_data_from_url( $url );
		}

		// Handle pasted JSON.
		if ( isset( $_POST['hosting_json_text'] ) && ! empty( $_POST['hosting_json_text'] ) ) {
			$json_text = wp_unslash( sanitize_textarea_field( wp_unslash( $_POST['hosting_json_text'] ) ) );
			$data      = json_decode( $json_text, true );
		}

		// Process the JSON data.
		if ( JSON_ERROR_NONE === json_last_error() && $data ) {
			$this->save_hosting_data( $data );
			echo '<div class="updated"><p>' . esc_html__( 'JSON data processed and saved successfully.', 'wp-hosting-directory' ) . '</p></div>';
		} else {
			echo '<div class="error"><p>' . esc_html__( 'Invalid JSON data.', 'wp-hosting-directory' ) . '</p></div>';
		}
	}
}