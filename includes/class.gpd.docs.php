<?php
/**
 * Class GPD_Docs
 *
 * Provides documentation and help pages for the plugin
 *
 * @since 2.3.0
 * @updated 2.5.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class GPD_Docs {
	private static $instance = null;

	public static function instance() {
		if ( self::$instance === null ) {
			self::$instance = new self();
			self::$instance->init_hooks();
		}
		return self::$instance;
	}

	private function init_hooks() {
		add_action( 'admin_menu', array( $this, 'add_docs_pages' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_styles' ) );
	}

	/**
	 * Enqueue styles for the documentation page
	 */
	public function enqueue_styles( $hook ) {
		if ( $hook === 'business_page_gpd-docs' ) {
			wp_enqueue_style( 'gpd-admin-styles', plugin_dir_url( __FILE__ ) . '../assets/admin-style.css' );

			// Add inline styles for docs page
			wp_add_inline_style(
				'gpd-admin-styles',
				'
				.gpd-docs-nav {
					display: flex;
					margin-bottom: 20px;
					border-bottom: 1px solid #ccc;
				}
				.gpd-docs-nav a {
					padding: 10px 15px;
					text-decoration: none;
					font-weight: 500;
				}
				.gpd-docs-nav a.active {
					border-bottom: 2px solid #2271b1;
					color: #2271b1;
				}
				.gpd-docs-section {
					background: #fff;
					padding: 20px;
					margin-bottom: 20px;
					border: 1px solid #ccc;
				}
				.gpd-docs-intro {
					margin-bottom: 20px;
				}
				.gpd-shortcode-example {
					background: #f0f0f0;
					padding: 10px;
					margin: 10px 0;
					font-family: monospace;
					border-left: 3px solid #2271b1;
				}
				.gpd-docs h3 {
					margin-top: 1.5em;
				}
			'
			);
		}
	}

	/**
	 * Add documentation pages to the admin menu
	 */
	public function add_docs_pages() {
		add_submenu_page(
			'edit.php?post_type=business',
			__( 'Documentation', 'google-places-directory' ),
			__( 'Documentation', 'google-places-directory' ),
			'manage_options',
			'gpd-docs',
			array( $this, 'render_docs_page' )
		);
	}

	/**
	 * Render the documentation page
	 */
	public function render_docs_page() {
		// Determine which tab to show
		$active_tab = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'shortcodes';
		?>
		<div class="wrap gpd-docs">
			<h1><?php esc_html_e( 'Google Places Directory Documentation', 'google-places-directory' ); ?></h1>

			<div class="gpd-docs-intro">
				<p><?php _e( 'This plugin allows you to import businesses from Google Places API and display them on your website using various shortcodes.', 'google-places-directory' ); ?></p>
			</div>
			<?php do_action( 'gpd_docs_before_nav' ); ?>

			<div class="gpd-docs-nav">
				<a href="<?php echo esc_url( add_query_arg( 'tab', 'shortcodes' ) ); ?>" class="<?php echo $active_tab === 'shortcodes' ? 'active' : ''; ?>">
					<?php esc_html_e( 'Shortcodes', 'google-places-directory' ); ?>
				</a>
				<a href="<?php echo esc_url( add_query_arg( 'tab', 'settings' ) ); ?>" class="<?php echo $active_tab === 'settings' ? 'active' : ''; ?>">
					<?php esc_html_e( 'Settings', 'google-places-directory' ); ?>
				</a>
				<a href="<?php echo esc_url( add_query_arg( 'tab', 'fse' ) ); ?>" class="<?php echo $active_tab === 'fse' ? 'active' : ''; ?>">
					<?php esc_html_e( 'Full Site Editing', 'google-places-directory' ); ?>
				</a>
				<a href="<?php echo esc_url( add_query_arg( 'tab', 'api' ) ); ?>" class="<?php echo $active_tab === 'api' ? 'active' : ''; ?>">
					<?php esc_html_e( 'API Information', 'google-places-directory' ); ?>
				</a>
			</div>
			<?php do_action( 'gpd_docs_after_nav' ); ?>

			<?php
			// Load the appropriate tab content
			switch ( $active_tab ) {
				case 'settings':
					$this->render_settings_docs();
					break;
				case 'fse':
					$this->render_fse_docs();
					break;
				case 'api':
					$this->render_api_docs();
					break;
				case 'shortcodes':
				default:
					$this->render_shortcodes_docs();
					break;
			}
			?>
		</div>
		<?php
	}

	/**
	 * Render shortcodes documentation
	 */
	private function render_shortcodes_docs() {
		ob_start();
		do_action( 'gpd_docs_before_shortcodes' );
		?>
		<div class="gpd-docs-section">
			<h2><?php esc_html_e( 'Display Business Photos', 'google-places-directory' ); ?></h2>
			<p><?php _e( 'Use the <code>[gpd-photos]</code> shortcode to display photos for a specific business.', 'google-places-directory' ); ?></p>

			<h3><?php esc_html_e( 'Parameters', 'google-places-directory' ); ?></h3>
			<table class="widefat" style="width: 95%">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Parameter', 'google-places-directory' ); ?></th>
						<th><?php esc_html_e( 'Description', 'google-places-directory' ); ?></th>
						<th><?php esc_html_e( 'Default', 'google-places-directory' ); ?></th>
						<th><?php esc_html_e( 'Options', 'google-places-directory' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<tr>
						<td><code>id</code></td>
						<td><?php esc_html_e( 'The business post ID', 'google-places-directory' ); ?></td>
						<td>0 (current post)</td>
						<td><?php esc_html_e( 'Any valid business post ID', 'google-places-directory' ); ?></td>
					</tr>
					<tr>
						<td><code>layout</code></td>
						<td><?php esc_html_e( 'The gallery layout style', 'google-places-directory' ); ?></td>
						<td>grid</td>
						<td>grid, masonry, carousel</td>
					</tr>
					<tr>
						<td><code>columns</code></td>
						<td><?php esc_html_e( 'Number of columns for grid/masonry layout', 'google-places-directory' ); ?></td>
						<td>3</td>
						<td>1, 2, 3, 4</td>
					</tr>
					<tr>
						<td><code>size</code></td>
						<td><?php esc_html_e( 'The image size to use', 'google-places-directory' ); ?></td>
						<td>medium</td>
						<td>thumbnail, medium, large, full</td>
					</tr>
					<tr>
						<td><code>limit</code></td>
						<td><?php esc_html_e( 'Maximum number of photos to display (0 = all)', 'google-places-directory' ); ?></td>
						<td>0</td>
						<td><?php esc_html_e( 'Any positive number', 'google-places-directory' ); ?></td>
					</tr>
					<tr>
						<td><code>show_caption</code></td>
						<td><?php esc_html_e( 'Whether to show photo captions', 'google-places-directory' ); ?></td>
						<td>false</td>
						<td>true, false</td>
					</tr>
					<tr>
						<td><code>class</code></td>
						<td><?php esc_html_e( 'Additional CSS classes', 'google-places-directory' ); ?></td>
						<td>empty</td>
						<td><?php esc_html_e( 'Any CSS class name', 'google-places-directory' ); ?></td>
					</tr>
				</tbody>
			</table>

			<h3><?php esc_html_e( 'Examples', 'google-places-directory' ); ?></h3>
			<div class="gpd-shortcode-example">[gpd-photos id="123" layout="grid" columns="3" size="medium" limit="6"]</div>
			<div class="gpd-shortcode-example">[gpd-photos layout="carousel" size="large" show_caption="true"]</div>
			<div class="gpd-shortcode-example">[gpd-photos layout="masonry" columns="4" class="my-custom-gallery"]</div>
		</div>
		<?php
		do_action( 'gpd_docs_after_shortcodes' );
		$content = ob_get_clean();
		echo apply_filters( 'gpd_shortcodes_docs_content', $content );
	}

	/**
	 * Render settings documentation
	 */
	private function render_settings_docs() {
		ob_start();
		do_action( 'gpd_docs_before_settings' );
		?>
		<div class="gpd-docs-section">
			<h2><?php esc_html_e( 'API Key Settings', 'google-places-directory' ); ?></h2>
			<p><?php _e( 'The plugin requires a valid Google API key with the Places API (New) enabled.', 'google-places-directory' ); ?></p>

			<h3><?php esc_html_e( 'Obtaining a Google API Key', 'google-places-directory' ); ?></h3>
			<ol>
				<li><?php _e( 'Go to the <a href="https://console.cloud.google.com/apis/dashboard" target="_blank">Google Cloud Console</a>', 'google-places-directory' ); ?></li>
				<li><?php _e( 'Create a new project or select an existing one', 'google-places-directory' ); ?></li>
				<li><?php _e( 'Enable the <strong>Places API (New)</strong> for your project', 'google-places-directory' ); ?></li>
				<li><?php _e( 'Create credentials to get your API key', 'google-places-directory' ); ?></li>
				<li><?php _e( 'Set up billing (required for the Places API)', 'google-places-directory' ); ?></li>
				<li><?php _e( 'Optional: Add API key restrictions for security', 'google-places-directory' ); ?></li>
			</ol>

			<h3><?php esc_html_e( 'Plugin Settings', 'google-places-directory' ); ?></h3>
			<p><?php _e( 'Configure your plugin settings at <strong>Businesses → Settings</strong>', 'google-places-directory' ); ?></p>
			<ul>
				<li><?php _e( '<strong>API Key</strong>: Enter your Google API key with Places API (New) enabled', 'google-places-directory' ); ?></li>
				<li><?php _e( '<strong>Photos to Import</strong>: Set the maximum number of photos to import per business', 'google-places-directory' ); ?></li>
				<li><?php _e( 'Use the "Test Connection" button to verify your API key works correctly', 'google-places-directory' ); ?></li>
			</ul>
		</div>

		<div class="gpd-docs-section">
			<h2><?php esc_html_e( 'Photo Management', 'google-places-directory' ); ?></h2>
			<p><?php _e( 'The plugin provides several features for managing business photos:', 'google-places-directory' ); ?></p>

			<ul>
				<li><?php _e( '<strong>Photo Import</strong>: Photos are automatically imported when you import businesses', 'google-places-directory' ); ?></li>
				<li><?php _e( '<strong>Photo Limit</strong>: Control how many photos are imported per business in the plugin settings', 'google-places-directory' ); ?></li>
				<li><?php _e( '<strong>Photo Refresh</strong>: Refresh photos for individual businesses from their edit screen', 'google-places-directory' ); ?></li>
				<li><?php _e( '<strong>Featured Image</strong>: The first imported photo is automatically set as the featured image', 'google-places-directory' ); ?></li>
			</ul>

			<h3><?php esc_html_e( 'Business Photo Column', 'google-places-directory' ); ?></h3>
			<p><?php _e( 'The Businesses list includes a Photos column that shows:', 'google-places-directory' ); ?></p>
			<ul>
				<li><?php _e( 'Number of photos attached to each business', 'google-places-directory' ); ?></li>
				<li><?php _e( 'Star icon for businesses with a featured image', 'google-places-directory' ); ?></li>
				<li><?php _e( 'Thumbnail preview on hover', 'google-places-directory' ); ?></li>
				<li><?php _e( '"Add Photos" link for businesses without photos', 'google-places-directory' ); ?></li>
			</ul>

			<p><?php _e( 'You can sort and filter businesses by their photo status using the column header and filter dropdown.', 'google-places-directory' ); ?></p>
		</div>
		<?php
		do_action( 'gpd_docs_after_settings' );
		$content = ob_get_clean();
		echo apply_filters( 'gpd_settings_docs_content', $content );
	}

	/**
	 * Render FSE documentation
	 */
	private function render_fse_docs() {
		ob_start();
		do_action( 'gpd_docs_before_fse' );
		?>
		<div class="gpd-docs-section">
			<h2><?php esc_html_e( 'Using Shortcodes in Full Site Editor (FSE)', 'google-places-directory' ); ?></h2>
			<p><?php _e( 'The plugin\'s shortcodes work seamlessly with the WordPress Full Site Editor. Here\'s how to use them in your FSE templates:', 'google-places-directory' ); ?></p>

			<h3><?php esc_html_e( 'Adding Shortcodes to Templates', 'google-places-directory' ); ?></h3>
			<ol>
				<li><?php _e( 'Edit a template in the Full Site Editor', 'google-places-directory' ); ?></li>
				<li><?php _e( 'Add a "Shortcode" block where you want the business content to appear', 'google-places-directory' ); ?></li>
				<li><?php _e( 'Enter one of the plugin\'s shortcodes with your desired parameters', 'google-places-directory' ); ?></li>
				<li><?php _e( 'Save the template', 'google-places-directory' ); ?></li>
			</ol>

			<h3><?php esc_html_e( 'Single Business Template Example', 'google-places-directory' ); ?></h3>
			<p><?php _e( 'For a single business template, you might want to add:', 'google-places-directory' ); ?></p>
			<ol>
				<li><?php _e( 'The business title (using WordPress core Title block)', 'google-places-directory' ); ?></li>
				<li><?php _e( 'The business content (using WordPress core Content block)', 'google-places-directory' ); ?></li>
				<li><?php _e( 'A Shortcode block with <code>[gpd-photos columns="3" layout="grid"]</code> to show business photos', 'google-places-directory' ); ?></li>
				<li><?php _e( 'A Shortcode block with <code>[gpd-business-map height="400px"]</code> to show the business location', 'google-places-directory' ); ?></li>
			</ol>

			<h3><?php esc_html_e( 'Archive Template Example', 'google-places-directory' ); ?></h3>
			<p><?php _e( 'For a business archive or search results page:', 'google-places-directory' ); ?></p>
			<ol>
				<li><?php _e( 'Add a Shortcode block with <code>[gpd-business-search show_map="true"]</code> at the top', 'google-places-directory' ); ?></li>
				<li><?php _e( 'Use WordPress core Query Loop block to display business posts', 'google-places-directory' ); ?></li>
				<li><?php _e( 'Optionally add a Shortcode block with <code>[gpd-business-map height="500px"]</code> to show all businesses on a map', 'google-places-directory' ); ?></li>
			</ol>
		</div>

		<div class="gpd-docs-section">
			<h2><?php esc_html_e( 'Template Parts for Custom Styling', 'google-places-directory' ); ?></h2>
			<p><?php _e( 'For more customized styling and layout, you can create template parts specifically for businesses:', 'google-places-directory' ); ?></p>

			<h3><?php esc_html_e( 'Creating a Business Card Template Part', 'google-places-directory' ); ?></h3>
			<ol>
				<li><?php _e( 'Go to Appearance → Editor → Template Parts', 'google-places-directory' ); ?></li>
				<li><?php _e( 'Add a new Template Part (e.g., "Business Card")', 'google-places-directory' ); ?></li>
				<li><?php _e( 'Design your template part using WordPress blocks', 'google-places-directory' ); ?></li>
				<li><?php _e( 'Include shortcodes where needed (e.g., a small photo gallery)', 'google-places-directory' ); ?></li>
				<li><?php _e( 'Use this template part in your business templates', 'google-places-directory' ); ?></li>
			</ol>

			<h3><?php esc_html_e( 'Responsive Considerations', 'google-places-directory' ); ?></h3>
			<p><?php _e( 'The plugin\'s shortcodes are designed to be responsive and work well across different screen sizes:', 'google-places-directory' ); ?></p>
			<ul>
				<li><?php _e( 'Photo galleries adjust from 3 columns to 2 on tablets and 1 on mobile', 'google-places-directory' ); ?></li>
				<li><?php _e( 'Maps maintain aspect ratio across screen sizes', 'google-places-directory' ); ?></li>
				<li><?php _e( 'Search forms adapt to available width', 'google-places-directory' ); ?></li>
			</ul>
			<p><?php _e( 'You can further customize responsiveness using block editor settings and custom CSS.', 'google-places-directory' ); ?></p>
		</div>
		<?php
		do_action( 'gpd_docs_after_fse' );
		$content = ob_get_clean();
		echo apply_filters( 'gpd_fse_docs_content', $content );
	}

	/**
	 * Render API documentation
	 */
	private function render_api_docs() {
		ob_start();
		do_action( 'gpd_docs_before_api' );
		?>
		<div class="gpd-docs-section">
			<h2><?php esc_html_e( 'Places API (New) Information', 'google-places-directory' ); ?></h2>
			<p><?php _e( 'This plugin uses Google\'s new Places API, which was updated in May 2025.', 'google-places-directory' ); ?></p>

			<h3><?php esc_html_e( 'API Features Used', 'google-places-directory' ); ?></h3>
			<ul>
				<li><?php _e( '<strong>Text Search</strong>: Used for finding businesses by name or type', 'google-places-directory' ); ?></li>
				<li><?php _e( '<strong>Place Details</strong>: Used to get comprehensive information about a specific business', 'google-places-directory' ); ?></li>
				<li><?php _e( '<strong>Place Photos</strong>: Used to retrieve business photos', 'google-places-directory' ); ?></li>
			</ul>

			<h3><?php esc_html_e( 'API Usage and Billing', 'google-places-directory' ); ?></h3>
			<p><?php _e( 'The Places API is a billing-required API with the following pricing structure:', 'google-places-directory' ); ?></p>
			<ul>
				<li><?php _e( '<strong>Text Search</strong>: $5 per 1,000 requests', 'google-places-directory' ); ?></li>
				<li><?php _e( '<strong>Place Details</strong>: $4 per 1,000 requests', 'google-places-directory' ); ?></li>
				<li><?php _e( '<strong>Photos</strong>: $7 per 1,000 requests', 'google-places-directory' ); ?></li>
			</ul>
			<p><?php _e( 'Google provides a monthly free credit that often covers moderate usage. Monitor your usage in the Google Cloud Console.', 'google-places-directory' ); ?></p>

			<h3><?php esc_html_e( 'Optimizing API Usage', 'google-places-directory' ); ?></h3>
			<p><?php _e( 'To reduce API costs and improve performance:', 'google-places-directory' ); ?></p>
			<ul>
				<li><?php _e( 'Set a reasonable photo limit in settings (3-5 is recommended)', 'google-places-directory' ); ?></li>
				<li><?php _e( 'Import businesses in batches rather than all at once', 'google-places-directory' ); ?></li>
				<li><?php _e( 'Only refresh photos when necessary', 'google-places-directory' ); ?></li>
			</ul>
		</div>

		<div class="gpd-docs-section">
			<h2><?php esc_html_e( 'Troubleshooting API Issues', 'google-places-directory' ); ?></h2>
			<p><?php _e( 'If you encounter problems with the Google Places API:', 'google-places-directory' ); ?></p>

			<h3><?php esc_html_e( 'Common Issues and Solutions', 'google-places-directory' ); ?></h3>
			<table class="widefat" style="width: 95%">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Issue', 'google-places-directory' ); ?></th>
						<th><?php esc_html_e( 'Possible Solutions', 'google-places-directory' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<tr>
						<td><?php esc_html_e( 'API Key Error', 'google-places-directory' ); ?></td>
						<td>
							<ul>
								<li><?php _e( 'Verify your API key is correct', 'google-places-directory' ); ?></li>
								<li><?php _e( 'Check that Places API (New) is enabled for your key', 'google-places-directory' ); ?></li>
								<li><?php _e( 'Ensure billing is properly set up', 'google-places-directory' ); ?></li>
							</ul>
						</td>
					</tr>
					<tr>
						<td><?php esc_html_e( 'Photos Not Importing', 'google-places-directory' ); ?></td>
						<td>
							<ul>
								<li><?php _e( 'Make sure photo limit is greater than 0 in settings', 'google-places-directory' ); ?></li>
								<li><?php _e( 'Check if business has photos available on Google', 'google-places-directory' ); ?></li>
								<li><?php _e( 'Try refreshing photos from the business edit screen', 'google-places-directory' ); ?></li>
								<li><?php _e( 'Enable WordPress debug logging to see specific errors', 'google-places-directory' ); ?></li>
							</ul>
						</td>
					</tr>
					<tr>
						<td><?php esc_html_e( 'Quota Exceeded', 'google-places-directory' ); ?></td>
						<td>
							<ul>
								<li><?php _e( 'Check your usage in Google Cloud Console', 'google-places-directory' ); ?></li>
								<li><?php _e( 'Increase your quota or wait until it resets', 'google-places-directory' ); ?></li>
								<li><?php _e( 'Optimize your API usage by limiting requests', 'google-places-directory' ); ?></li>
							</ul>
						</td>
					</tr>
				</tbody>
			</table>

			<h3><?php esc_html_e( 'Testing Your API Connection', 'google-places-directory' ); ?></h3>
			<p><?php _e( 'Use the "Test Connection" button on the Settings page to verify your API key is working properly.', 'google-places-directory' ); ?></p>
			<p><?php _e( 'For more detailed troubleshooting, check the WordPress debug log for specific error messages.', 'google-places-directory' ); ?></p>
		</div>
		<?php
		do_action( 'gpd_docs_after_api' );
		$content = ob_get_clean();
		echo apply_filters( 'gpd_api_docs_content', $content );
	}
}

// Initialize the docs
GPD_Docs::instance();
