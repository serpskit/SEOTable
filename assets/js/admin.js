(function($) {
	'use strict';

	$(document).ready(function() {
		// Initialize WordPress color pickers with live preview update.
		$('.sst-color-picker').wpColorPicker({
			change: function() {
				setTimeout(updatePreview, 50);
			},
			clear: function() {
				setTimeout(updatePreview, 50);
			}
		});

		// Listen for changes on non-color inputs too.
		$('.sst-admin-form input[type="number"], .sst-admin-form select').on('change input', function() {
			updatePreview();
		});

		function updatePreview() {
			var headingBg    = $('#sst_heading_bg_color').val() || '#2d6a4f';
			var headingFc    = $('#sst_heading_font_color').val() || '#ffffff';
			var headingFs    = $('#sst_heading_font_size').val() || '12';
			var headingFw    = $('#sst_heading_font_weight').val() || '600';
			var borderSize   = $('#sst_border_size').val() || '1';
			var borderColor  = $('#sst_border_color').val() || '#e0e0e0';
			var cellBorder   = $('#sst_cell_border_color').val() || '#e8e8e8';
			var fontColor    = $('#sst_font_color').val() || '#1a1a1a';
			var fontSize     = $('#sst_font_size').val() || '13';
			var fontWeight   = $('#sst_font_weight').val() || '400';
			var rowEven      = $('#sst_row_even_bg').val() || '#f7faf8';
			var rowOdd       = $('#sst_row_odd_bg').val() || '#ffffff';
			var rowHover     = $('#sst_row_hover_bg').val() || '#eaf4ef';
			var nameFc       = $('#sst_name_font_color').val() || '#1b4332';

			var $wrap = $('.sst-preview-wrap');
			var $table = $wrap.find('.sst-table');

			// Container border.
			$wrap.css({
				'border-width': borderSize + 'px',
				'border-color': borderColor
			});

			// Header.
			$table.find('thead tr').css('background', headingBg);
			$table.find('thead th').css({
				'color': headingFc,
				'font-size': headingFs + 'px',
				'font-weight': headingFw
			});

			// Body cells.
			$table.find('tbody td').css({
				'color': fontColor,
				'font-size': fontSize + 'px',
				'font-weight': fontWeight,
				'border-bottom-color': cellBorder
			});

			// Row backgrounds.
			$table.find('tbody tr').each(function(i) {
				$(this).css('background', (i % 2 === 0) ? rowOdd : rowEven);
			});

			// Hover — use data attributes for JS hover.
			$table.find('tbody tr')
				.off('mouseenter.sst mouseleave.sst')
				.on('mouseenter.sst', function() {
					$(this).css('background', rowHover);
				})
				.on('mouseleave.sst', function() {
					var idx = $(this).index();
					$(this).css('background', (idx % 2 === 0) ? rowOdd : rowEven);
				});

			// Name color.
			$table.find('.sst-cell-name').css('color', nameFc);
		}

		// Run initial preview update.
		updatePreview();
	});

})(jQuery);
