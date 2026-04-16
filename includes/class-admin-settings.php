<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class SST_Admin_Settings {

	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_menu' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
	}

	public function add_menu() {
		add_menu_page( 'SERPsKit SEOTable', 'SEOTable', 'manage_options', 'serpskit-seotable', array( $this, 'render_page' ), 'dashicons-editor-table', 80 );
	}

	public function register_settings() {
		register_setting( 'sst_settings_group', 'sst_settings', array(
			'type' => 'array', 'sanitize_callback' => array( $this, 'sanitize' ), 'default' => array(),
		) );
	}

	public function sanitize( $input ) {
		$clean = array();
		$colors = array('heading_bg_color','heading_font_color','border_color','cell_border_color','font_color','row_even_bg','row_odd_bg','row_hover_bg','name_font_color');
		foreach ( $colors as $f ) $clean[$f] = isset($input[$f]) ? sanitize_hex_color($input[$f]) : '';

		$ints = array('heading_font_size','border_size','font_size','badge_font_size');
		foreach ( $ints as $f ) $clean[$f] = isset($input[$f]) ? absint($input[$f]) : '';

		$weights = array('heading_font_weight','font_weight');
		foreach ( $weights as $f ) $clean[$f] = isset($input[$f]) && in_array($input[$f],array('400','500','600','700'),true) ? $input[$f] : '400';

		$clean['heading_text_case'] = isset($input['heading_text_case']) && in_array($input['heading_text_case'],array('none','capitalize','uppercase','lowercase'),true) ? $input['heading_text_case'] : 'capitalize';

		return $clean;
	}

	public function enqueue_admin_assets( $hook ) {
		if ( 'toplevel_page_serpskit-seotable' !== $hook ) return;
		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_script( 'wp-color-picker' );
		wp_enqueue_style( 'sst-admin-css', SST_URL . 'assets/css/admin.css', array(), SST_VERSION );
		wp_enqueue_script( 'sst-admin-js', SST_URL . 'assets/js/admin.js', array('wp-color-picker','jquery'), SST_VERSION, true );
	}

	public function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) return;
		$s = SERPsKit_SEOTable::get_settings();
		?>
		<div class="wrap sst-admin-wrap">
			<div class="sst-admin-header">
				<h1><span class="dashicons dashicons-editor-table"></span> SERPsKit SEOTable</h1>
				<p class="sst-admin-desc">Customize the appearance of your tables across the site.</p>
			</div>

			<form method="post" action="options.php" class="sst-admin-form">
				<?php settings_fields( 'sst_settings_group' ); ?>

				<div class="sst-admin-grid">

					<div class="sst-admin-card">
						<h2>Table Heading</h2>
						<table class="form-table">
							<tr><th>Background Color</th><td><input type="text" name="sst_settings[heading_bg_color]" value="<?php echo esc_attr($s['heading_bg_color']); ?>" class="sst-color-picker" data-default-color="#2d6a4f"></td></tr>
							<tr><th>Font Color</th><td><input type="text" name="sst_settings[heading_font_color]" value="<?php echo esc_attr($s['heading_font_color']); ?>" class="sst-color-picker" data-default-color="#ffffff"></td></tr>
							<tr><th>Font Size (px)</th><td><input type="number" name="sst_settings[heading_font_size]" value="<?php echo esc_attr($s['heading_font_size']); ?>" min="9" max="24" class="small-text"></td></tr>
							<tr><th>Font Weight</th><td>
								<select name="sst_settings[heading_font_weight]">
									<option value="400" <?php selected($s['heading_font_weight'],'400'); ?>>Normal (400)</option>
									<option value="500" <?php selected($s['heading_font_weight'],'500'); ?>>Medium (500)</option>
									<option value="600" <?php selected($s['heading_font_weight'],'600'); ?>>Semi-Bold (600)</option>
									<option value="700" <?php selected($s['heading_font_weight'],'700'); ?>>Bold (700)</option>
								</select>
							</td></tr>
							<tr><th>Text Case</th><td>
								<select name="sst_settings[heading_text_case]">
									<option value="none" <?php selected($s['heading_text_case'],'none'); ?>>As Typed</option>
									<option value="capitalize" <?php selected($s['heading_text_case'],'capitalize'); ?>>Title Case</option>
									<option value="uppercase" <?php selected($s['heading_text_case'],'uppercase'); ?>>UPPERCASE</option>
									<option value="lowercase" <?php selected($s['heading_text_case'],'lowercase'); ?>>lowercase</option>
								</select>
							</td></tr>
						</table>
					</div>

					<div class="sst-admin-card">
						<h2>Borders</h2>
						<table class="form-table">
							<tr><th>Table Border Size (px)</th><td><input type="number" name="sst_settings[border_size]" value="<?php echo esc_attr($s['border_size']); ?>" min="0" max="5" class="small-text"></td></tr>
							<tr><th>Table Border Color</th><td><input type="text" name="sst_settings[border_color]" value="<?php echo esc_attr($s['border_color']); ?>" class="sst-color-picker" data-default-color="#e0e0e0"></td></tr>
							<tr><th>Cell Border Color</th><td><input type="text" name="sst_settings[cell_border_color]" value="<?php echo esc_attr($s['cell_border_color']); ?>" class="sst-color-picker" data-default-color="#e8e8e8"></td></tr>
						</table>
					</div>

					<div class="sst-admin-card">
						<h2>Table Body / Data</h2>
						<table class="form-table">
							<tr><th>Data Font Color</th><td><input type="text" name="sst_settings[font_color]" value="<?php echo esc_attr($s['font_color']); ?>" class="sst-color-picker" data-default-color="#1a1a1a"></td></tr>
							<tr><th>Data Font Size (px)</th><td><input type="number" name="sst_settings[font_size]" value="<?php echo esc_attr($s['font_size']); ?>" min="10" max="20" class="small-text"></td></tr>
							<tr><th>Data Font Weight</th><td>
								<select name="sst_settings[font_weight]">
									<option value="400" <?php selected($s['font_weight'],'400'); ?>>Normal (400)</option>
									<option value="500" <?php selected($s['font_weight'],'500'); ?>>Medium (500)</option>
									<option value="600" <?php selected($s['font_weight'],'600'); ?>>Semi-Bold (600)</option>
									<option value="700" <?php selected($s['font_weight'],'700'); ?>>Bold (700)</option>
								</select>
							</td></tr>
							<tr><th>Name/Title Font Color</th><td><input type="text" name="sst_settings[name_font_color]" value="<?php echo esc_attr($s['name_font_color']); ?>" class="sst-color-picker" data-default-color="#1b4332"></td></tr>
							<tr><th>Badge Font Size (px)</th><td><input type="number" name="sst_settings[badge_font_size]" value="<?php echo esc_attr($s['badge_font_size']); ?>" min="8" max="18" class="small-text"> <span class="description">Font size for all color badges.</span></td></tr>
						</table>
					</div>

					<div class="sst-admin-card">
						<h2>Row Colors</h2>
						<table class="form-table">
							<tr><th>Even Row Background</th><td><input type="text" name="sst_settings[row_even_bg]" value="<?php echo esc_attr($s['row_even_bg']); ?>" class="sst-color-picker" data-default-color="#f7faf8"></td></tr>
							<tr><th>Odd Row Background</th><td><input type="text" name="sst_settings[row_odd_bg]" value="<?php echo esc_attr($s['row_odd_bg']); ?>" class="sst-color-picker" data-default-color="#ffffff"></td></tr>
							<tr><th>Hover Background</th><td><input type="text" name="sst_settings[row_hover_bg]" value="<?php echo esc_attr($s['row_hover_bg']); ?>" class="sst-color-picker" data-default-color="#eaf4ef"></td></tr>
						</table>
					</div>

				</div>

				<div class="sst-admin-card sst-preview-card">
					<h2>Live Preview</h2>
					<div class="sst-preview-wrap">
						<div class="sst-overflow" id="sst-preview-table">
							<table class="sst-table" id="sst-preview">
								<thead><tr><th># Tent</th><th>Capacity</th><th>Floor Area</th><th>Waterproof</th><th>Season</th></tr></thead>
								<tbody>
									<tr><td><div class="sst-cell-name">1. NEMO Aurora</div><span class="sst-brand-tag">NEMO</span></td><td>3-Person</td><td>n/s</td><td><span class="sst-badge sst-badge-green">2000 mm</span></td><td><span class="sst-badge sst-badge-blue">3-Season</span></td></tr>
									<tr><td><div class="sst-cell-name">2. Big Agnes Copper Spur</div><span class="sst-brand-tag">Big Agnes</span></td><td>2-Person</td><td>29 sq ft</td><td><span class="sst-badge sst-badge-amber">1200 mm</span></td><td><span class="sst-badge sst-badge-green">4-Season</span></td></tr>
									<tr><td><div class="sst-cell-name">3. Coleman WeatherMaster</div><span class="sst-brand-tag">Coleman</span></td><td>10-Person</td><td>n/s</td><td><span class="sst-badge sst-badge-gray">Water-resistant</span></td><td><span class="sst-badge sst-badge-blue">3-Season</span></td></tr>
								</tbody>
							</table>
						</div>
					</div>
				</div>

				<?php submit_button( 'Save Settings' ); ?>
			</form>

			<div class="sst-admin-footer"><p>SERPsKit SEOTable v<?php echo SST_VERSION; ?> &middot; Developed by <a href="https://serpskit.com/" target="_blank">SERPsKit</a> | <a href="https://serpskit.com/wordpress-plugins/" target="_blank">See More Plugins</a> </p></div>
		</div>
		<?php
	}
}
