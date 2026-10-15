/**
 * WPCalibrate External Product Images - Admin Product & Settings Script
 */
(function() {
	'use strict';

	document.addEventListener('DOMContentLoaded', function() {
		// 1. External Featured Image Live Preview & Clear
		var featuredInput = document.getElementById('wpcalibrate_epi_featured_url');
		var previewWrap   = document.getElementById('wpcalibrate_featured_preview_wrap');
		var previewImg    = document.getElementById('wpcalibrate_featured_preview_img');
		var clearBtn      = document.getElementById('wpcalibrate_clear_featured_btn');

		if (featuredInput && previewWrap && previewImg) {
			featuredInput.addEventListener('input', function() {
				var val = featuredInput.value.trim();
				if (val.length > 5 && (val.indexOf('http://') === 0 || val.indexOf('https://') === 0)) {
					previewImg.src = val;
					previewWrap.style.display = 'flex';
				} else if (val.length === 0) {
					previewWrap.style.display = 'none';
				}
			});

			if (clearBtn) {
				clearBtn.addEventListener('click', function(e) {
					e.preventDefault();
					featuredInput.value = '';
					previewWrap.style.display = 'none';
					var altInput = document.getElementById('wpcalibrate_epi_featured_alt');
					if (altInput) {
						altInput.value = '';
					}
				});
			}
		}

		// 2. Settings: Fallback Mode dropdown toggles custom media picker row
		var fallbackSelect = document.getElementById('field_default_fallback');
		var customRow      = document.getElementById('row_custom_fallback');

		if (fallbackSelect && customRow) {
			fallbackSelect.addEventListener('change', function() {
				if (fallbackSelect.value === 'custom_attachment') {
					customRow.style.display = '';
				} else {
					customRow.style.display = 'none';
				}
			});
		}

		// 3. Settings: WordPress Media Library picker for custom fallback
		var selectFallbackBtn = document.getElementById('wpcalibrate_select_fallback_btn');
		var removeFallbackBtn = document.getElementById('wpcalibrate_remove_fallback_btn');
		var fallbackIdInput   = document.getElementById('field_custom_fallback_id');
		var fallbackPreview   = document.getElementById('custom_fallback_preview');
		var fallbackImg       = document.getElementById('custom_fallback_img');

		if (selectFallbackBtn && window.wp && window.wp.media) {
			var frame;
			selectFallbackBtn.addEventListener('click', function(e) {
				e.preventDefault();
				if (frame) {
					frame.open();
					return;
				}

				frame = wp.media({
					title: 'Select Fallback Product Image',
					button: { text: 'Use this image' },
					multiple: false,
					library: { type: 'image' }
				});

				frame.on('select', function() {
					var attachment = frame.state().get('selection').first().toJSON();
					if (attachment && attachment.id) {
						fallbackIdInput.value = attachment.id;
						var url = attachment.sizes && attachment.sizes.thumbnail ? attachment.sizes.thumbnail.url : attachment.url;
						if (fallbackImg) {
							fallbackImg.src = url;
						}
						if (fallbackPreview) {
							fallbackPreview.style.display = 'block';
						}
						if (removeFallbackBtn) {
							removeFallbackBtn.style.display = 'inline-block';
						}
					}
				});

				frame.open();
			});

			if (removeFallbackBtn) {
				removeFallbackBtn.addEventListener('click', function(e) {
					e.preventDefault();
					fallbackIdInput.value = '0';
					if (fallbackPreview) {
						fallbackPreview.style.display = 'none';
					}
					removeFallbackBtn.style.display = 'none';
				});
			}
		}
	});
})();
