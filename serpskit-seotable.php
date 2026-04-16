<?php
/**
 * Plugin Name: SERPsKit SEOTable
 * Plugin URI: https://serpskit.com/wordpress-plugins/
 * Description: A lightweight, SEO-optimized, fully responsive tables with color-coded badges, customizable headers, hover effects, and mobile-friendly layouts.
 * Version: 1.1.0
 * Author: SERPsKit
 * Author URI: https://serpskit.com/
 * License: GPLv2 or later
 * Text Domain: serpskit-seotable
 * Requires at least: 5.8
 * Requires PHP: 7.4
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'SST_VERSION', '1.1.0' );
define( 'SST_PATH', plugin_dir_path( __FILE__ ) );
define( 'SST_URL', plugin_dir_url( __FILE__ ) );
define( 'SST_BASENAME', plugin_basename( __FILE__ ) );

require_once SST_PATH . 'includes/class-admin-settings.php';

final class SERPsKit_SEOTable {

	private static $instance = null;

	public static function instance() {
		if ( null === self::$instance ) self::$instance = new self();
		return self::$instance;
	}

	private function __construct() {
		add_action( 'init', array( $this, 'register_block' ) );
		add_action( 'enqueue_block_editor_assets', array( $this, 'editor_assets' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'frontend_assets' ) );
		add_filter( 'plugin_action_links_' . SST_BASENAME, array( $this, 'settings_link' ) );
		if ( is_admin() ) new SST_Admin_Settings();
	}

	public static function get_settings() {
		$defaults = array(
			'heading_bg_color'    => '#2d6a4f',
			'heading_font_color'  => '#ffffff',
			'heading_font_size'   => '12',
			'heading_font_weight' => '600',
			'heading_text_case'   => 'capitalize',
			'border_size'         => '1',
			'border_color'        => '#e0e0e0',
			'cell_border_color'   => '#e8e8e8',
			'font_color'          => '#1a1a1a',
			'font_size'           => '13',
			'font_weight'         => '400',
			'badge_font_size'     => '10',
			'row_even_bg'         => '#f7faf8',
			'row_odd_bg'          => '#ffffff',
			'row_hover_bg'        => '#eaf4ef',
			'name_font_color'     => '#1b4332',
		);
		return wp_parse_args( get_option( 'sst_settings', array() ), $defaults );
	}

	public function register_block() {
		register_block_type( 'serpskit/seotable', array(
			'api_version'     => 2,
			'editor_script'   => 'sst-block-editor',
			'editor_style'    => 'sst-block-editor-style',
			'style'           => 'sst-block-style',
			'render_callback' => array( $this, 'render_block' ),
			'attributes'      => array(
				'tableData' => array( 'type' => 'string', 'default' => '' ),
				'caption'   => array( 'type' => 'string', 'default' => '' ),
				'tableId'   => array( 'type' => 'string', 'default' => '' ),
			),
		) );
	}

	public function render_block( $attributes ) {
		$raw = isset( $attributes['tableData'] ) ? $attributes['tableData'] : '';
		if ( empty( $raw ) ) return '';
		$data = json_decode( $raw, true );
		if ( ! $data || empty( $data['headers'] ) ) return '';

		$s       = self::get_settings();
		$caption = isset( $attributes['caption'] ) ? $attributes['caption'] : '';
		$uid     = 'sst-' . substr( md5( $raw ), 0, 8 );

		$vars = sprintf(
			'--sst-heading-bg:%s;--sst-heading-fc:%s;--sst-heading-fs:%spx;--sst-heading-fw:%s;--sst-heading-case:%s;'
			. '--sst-border-size:%spx;--sst-border-color:%s;--sst-cell-border:%s;'
			. '--sst-fc:%s;--sst-fs:%spx;--sst-fw:%s;--sst-badge-fs:%spx;'
			. '--sst-row-even:%s;--sst-row-odd:%s;--sst-row-hover:%s;--sst-name-fc:%s;',
			esc_attr( $s['heading_bg_color'] ),
			esc_attr( $s['heading_font_color'] ),
			esc_attr( $s['heading_font_size'] ),
			esc_attr( $s['heading_font_weight'] ),
			esc_attr( $s['heading_text_case'] ),
			esc_attr( $s['border_size'] ),
			esc_attr( $s['border_color'] ),
			esc_attr( $s['cell_border_color'] ),
			esc_attr( $s['font_color'] ),
			esc_attr( $s['font_size'] ),
			esc_attr( $s['font_weight'] ),
			esc_attr( $s['badge_font_size'] ),
			esc_attr( $s['row_even_bg'] ),
			esc_attr( $s['row_odd_bg'] ),
			esc_attr( $s['row_hover_bg'] ),
			esc_attr( $s['name_font_color'] )
		);

		$headers   = $data['headers'];
		$rows      = isset( $data['rows'] ) ? $data['rows'] : array();
		$aligns    = isset( $data['aligns'] ) ? $data['aligns'] : array();
		$colWidths = isset( $data['colWidths'] ) ? $data['colWidths'] : array();

		ob_start();
		?>
		<div class="serpskit-seotable-wrap" id="<?php echo esc_attr( $uid ); ?>" style="<?php echo $vars; ?>">
			<div class="sst-overflow">
				<table class="sst-table" role="table">
					<?php if ( $caption ) : ?>
						<caption><?php echo esc_html( $caption ); ?></caption>
					<?php endif; ?>
					<colgroup>
						<?php foreach ( $headers as $i => $h ) :
							$w = isset( $colWidths[ $i ] ) && intval( $colWidths[ $i ] ) > 0 ? intval( $colWidths[ $i ] ) : 0;
						?>
							<?php if ( $w ) : ?>
								<col style="width:<?php echo $w; ?>px">
							<?php else : ?>
								<col>
							<?php endif; ?>
						<?php endforeach; ?>
					</colgroup>
					<thead>
						<tr>
							<?php foreach ( $headers as $i => $h ) :
								$align = isset( $aligns[ $i ] ) ? $aligns[ $i ] : 'left';
							?>
								<th style="text-align:<?php echo esc_attr( $align ); ?>"><?php echo esc_html( $h ); ?></th>
							<?php endforeach; ?>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $rows as $row ) : ?>
							<tr>
								<?php
								$cells = isset( $row['cells'] ) ? $row['cells'] : array();
								foreach ( $cells as $ci => $cell ) :
									$align    = isset( $aligns[ $ci ] ) ? $aligns[ $ci ] : 'left';
									$text     = isset( $cell['text'] ) ? $cell['text'] : '';
									$bold     = ! empty( $cell['bold'] );
									$badge    = isset( $cell['badge'] ) ? $cell['badge'] : null;
									$brandTag = isset( $cell['brandTag'] ) ? $cell['brandTag'] : '';
									$subtext  = isset( $cell['subtext'] ) ? $cell['subtext'] : '';
								?>
									<td style="text-align:<?php echo esc_attr( $align ); ?>">
										<?php if ( $bold && $text ) : ?>
											<div class="sst-cell-name"><?php echo esc_html( $text ); ?></div>
										<?php elseif ( $text && ( ! $badge || empty( $badge['type'] ) ) ) : ?>
											<?php echo esc_html( $text ); ?>
										<?php elseif ( $text ) : ?>
											<?php echo esc_html( $text ); ?><br>
										<?php endif; ?>
										<?php if ( $badge && ! empty( $badge['type'] ) && ! empty( $badge['text'] ) ) : ?>
											<span class="sst-badge sst-badge-<?php echo esc_attr( $badge['type'] ); ?>"><?php echo esc_html( $badge['text'] ); ?></span>
										<?php endif; ?>
										<?php if ( $brandTag ) : ?>
											<span class="sst-brand-tag"><?php echo esc_html( $brandTag ); ?></span>
										<?php endif; ?>
										<?php if ( $subtext ) : ?>
											<small class="sst-subtext"><?php echo esc_html( $subtext ); ?></small>
										<?php endif; ?>
									</td>
								<?php endforeach; ?>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	public function editor_assets() {
		wp_register_script( 'sst-block-editor', SST_URL . 'assets/js/block.js', array(
			'wp-blocks','wp-element','wp-block-editor','wp-components','wp-i18n','wp-data',
		), SST_VERSION, true );
		wp_localize_script( 'sst-block-editor', 'sstSettings', self::get_settings() );
		wp_register_style( 'sst-block-editor-style', SST_URL . 'assets/css/editor.css', array(), SST_VERSION );
	}

	public function frontend_assets() {
		if ( ! has_block( 'serpskit/seotable' ) ) return;
		wp_enqueue_style( 'sst-block-style', SST_URL . 'assets/css/frontend.css', array(), SST_VERSION );
	}

	public function settings_link( $links ) {
		array_unshift( $links, '<a href="' . admin_url( 'admin.php?page=serpskit-seotable' ) . '">Settings</a>' );
		return $links;
	}
}

SERPsKit_SEOTable::instance();
