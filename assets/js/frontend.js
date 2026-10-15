/**
 * WPCalibrate External Product Images - Frontend Broken Image Fallback Handler.
 *
 * Catches failed remote image loads browser-side and gracefully swaps them
 * to the configured store fallback without blocking server rendering or causing infinite loops.
 */
(function() {
	'use strict';

	function handleBrokenImage(img) {
		var fallbackUrl = img.getAttribute('data-wpcalibrate-fallback');
		if (fallbackUrl && fallbackUrl !== img.src) {
			// Prevent infinite loops by removing the fallback attribute before changing src.
			img.removeAttribute('data-wpcalibrate-fallback');
			img.classList.add('wpcalibrate-fallback-applied');
			img.src = fallbackUrl;
		}
	}

	// Capture phase error listener on images.
	window.addEventListener('error', function(event) {
		var target = event.target;
		if (target && target.tagName === 'IMG' && target.hasAttribute('data-wpcalibrate-fallback')) {
			handleBrokenImage(target);
		}
	}, true);

	// Check on initial load in case images failed before listener attached.
	document.addEventListener('DOMContentLoaded', function() {
		var remoteImages = document.querySelectorAll('img[data-wpcalibrate-fallback]');
		remoteImages.forEach(function(img) {
			if (img.complete && img.naturalWidth === 0) {
				handleBrokenImage(img);
			}
		});
	});
})();
