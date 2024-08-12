<?php
/**
 * The public-facing functionality of the plugin.
 *
 * @package    WP_Hosting_Directory
 * @subpackage WP_Hosting_Directory/public
 */

/**
 * The public-facing functionality of the plugin.
 */
class WP_Hosting_Directory_Public {

	/**
	 * The ID of this plugin.
	 *
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @param string $plugin_name       The name of the plugin.
	 * @param string $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version     = $version;
	}

	/**
	 * Register the stylesheets for the public-facing side of the site.
	 */
	public function enqueue_styles() {
		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/wp-hosting-directory-public.css', array(), $this->version, 'all' );
	}

	/**
	 * Register the JavaScript for the public-facing side of the site.
	 */
	public function enqueue_scripts() {
		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/wp-hosting-directory-public.js', array( 'jquery' ), $this->version, false );
	}

	/**
	 * Shortcode for displaying the hosting directory.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string Shortcode output.
	 */
	public function hosting_directory_shortcode( $atts ) {
		$atts = shortcode_atts(
			array(
				'filter' => '',
			),
			$atts,
			'hosting_directory'
		);

		$args = array(
			'post_type'      => 'hosting_provider',
			'posts_per_page' => -1,
		);

		if ( ! empty( $atts['filter'] ) ) {
			$args['meta_query'] = array(
				array(
					'key'     => 'provider_type',
					'value'   => $atts['filter'],
					'compare' => '=',
				),
			);
		}

		$query  = new WP_Query( $args );
		$output = '<div class="hosting-directory">';

        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $output .= '<div class="hosting-provider">';
                $output .= '<h3>' . get_the_title() . '</h3>';
                $output .= '<p>' . get_the_excerpt() . '</p>';
                
                // Display custom fields
                $custom_field_1 = get_post_meta(get_the_ID(), 'custom_field_1', true);
                $custom_field_2 = get_post_meta(get_the_ID(), 'custom_field_2', true);
                
                if ($custom_field_1) {
                    $output .= '<p><strong>Custom Field 1:</strong> ' . esc_html($custom_field_1) . '</p>';
                }
                if ($custom_field_2) {
                    $output .= '<p><strong>Custom Field 2:</strong> ' . esc_html($custom_field_2) . '</p>';
                }
                
                $output .= '<a href="' . get_permalink() . '">' . esc_html__('Learn More', 'wp-hosting-directory') . '</a>';
                $output .= '</div>';
            }
        }
     else {
			$output .= '<p>' . esc_html__( 'No hosting providers found.', 'wp-hosting-directory' ) . '</p>';
		}

		$output .= '</div>';
		wp_reset_postdata();

		return $output;
	}
}

function wp_hosting_directory_register_meta() {
	register_meta('post', 'custom_field_1', array(
		'show_in_rest' => true,
		'single' => true,
		'type' => 'string',
	));
	register_meta('post', 'custom_field_2', array(
		'show_in_rest' => true,
		'single' => true,
		'type' => 'string',
	));
}
add_action('init', 'wp_hosting_directory_register_meta');

function wp_hosting_directory_enqueue_editor_assets() {
	wp_enqueue_script(
		'wp-hosting-directory-sidebar',
		plugins_url('js/sidebar.js', __FILE__),
		array('wp-plugins', 'wp-edit-post', 'wp-element', 'wp-components', 'wp-data'),
		filemtime(plugin_dir_path(__FILE__) . 'js/sidebar.js'),
		true
	);
}
add_action('enqueue_block_editor_assets', 'wp_hosting_directory_enqueue_editor_assets');