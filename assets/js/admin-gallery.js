/**
 * WPCalibrate External Product Images - Admin Gallery Manager Script
 * Handles Drag-and-drop, accessible keyboard Move Up/Down, Bulk Add, and Deduplication.
 */
(function() {
	'use strict';

	document.addEventListener('DOMContentLoaded', function() {
		var list = document.getElementById('wpcalibrate_gallery_list');
		if (!list) {
			return;
		}

		var addBtn        = document.getElementById('wpcalibrate_add_gallery_btn');
		var urlInput      = document.getElementById('wpcalibrate_new_gallery_url');
		var altInput      = document.getElementById('wpcalibrate_new_gallery_alt');
		var toggleBulkBtn = document.getElementById('wpcalibrate_toggle_bulk_btn');
		var bulkWrap      = document.getElementById('wpcalibrate_bulk_gallery_wrap');
		var bulkTextarea  = document.getElementById('wpcalibrate_bulk_gallery_textarea');
		var confirmBulk   = document.getElementById('wpcalibrate_confirm_bulk_btn');
		var cancelBulk    = document.getElementById('wpcalibrate_cancel_bulk_btn');

		// Re-index all list inputs
		function reindex() {
			var items = list.querySelectorAll('.wpcalibrate-gallery-item');
			items.forEach(function(item, index) {
				item.setAttribute('data-index', index);
				var urlInp = item.querySelector('.wpcalibrate-gallery-url-input');
				var altInp = item.querySelector('.wpcalibrate-gallery-alt-input');
				var cachedInp = item.querySelector('input[name*="[cached_attachment_id]"]');

				if (urlInp) {
					urlInp.name = '_wpcalibrate_epi_gallery[' + index + '][url]';
				}
				if (altInp) {
					altInp.name = '_wpcalibrate_epi_gallery[' + index + '][alt]';
				}
				if (cachedInp) {
					cachedInp.name = '_wpcalibrate_epi_gallery[' + index + '][cached_attachment_id]';
				}

				// Update Move Up/Down button states
				var upBtn = item.querySelector('.btn-move-up');
				var downBtn = item.querySelector('.btn-move-down');
				if (upBtn) {
					upBtn.disabled = (index === 0);
				}
				if (downBtn) {
					downBtn.disabled = (index === items.length - 1);
				}
			});
		}

		// Check if URL already exists in gallery
		function hasUrl(url) {
			var existing = list.querySelectorAll('.wpcalibrate-gallery-url-input');
			for (var i = 0; i < existing.length; i++) {
				if (existing[i].value.trim().toLowerCase() === url.toLowerCase()) {
					return true;
				}
			}
			return false;
		}

		// Add item to DOM
		function addItem(url, alt) {
			url = url.trim();
			alt = (alt || '').trim();

			if (!url || (!url.startsWith('http://') && !url.startsWith('https://'))) {
				alert('Please enter a valid HTTP or HTTPS image URL.');
				return false;
			}

			if (hasUrl(url)) {
				alert('This image URL is already present in this gallery.');
				return false;
			}

			var li = document.createElement('li');
			li.className = 'wpcalibrate-gallery-item';
			li.draggable = true;

			li.innerHTML = '<span class="wpcalibrate-gallery-handle dashicons dashicons-menu" title="Drag to reorder"></span>' +
				'<img src="' + escapeHtml(url) + '" class="wpcalibrate-gallery-thumb" alt="" />' +
				'<div class="wpcalibrate-gallery-fields">' +
					'<input type="hidden" class="wpcalibrate-gallery-url-input" value="' + escapeHtml(url) + '" />' +
					'<input type="text" class="widefat wpcalibrate-gallery-alt-input" value="' + escapeHtml(alt) + '" placeholder="Alt text" />' +
					'<input type="hidden" name="" value="0" />' +
				'</div>' +
				'<div class="wpcalibrate-gallery-actions">' +
					'<button type="button" class="wpcalibrate-gallery-btn-move btn-move-up dashicons dashicons-arrow-up-alt2" title="Move Up"></button>' +
					'<button type="button" class="wpcalibrate-gallery-btn-move btn-move-down dashicons dashicons-arrow-down-alt2" title="Move Down"></button>' +
					'<button type="button" class="button-link-delete dashicons dashicons-trash btn-remove-item" title="Remove"></button>' +
				'</div>';

			list.appendChild(li);
			bindItemEvents(li);
			reindex();
			return true;
		}

		function escapeHtml(str) {
			return str.replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/'/g, '&#039;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
		}

		// Bind actions for an item (move up, move down, remove, drag)
		var draggedItem = null;

		function bindItemEvents(item) {
			var upBtn = item.querySelector('.btn-move-up');
			var downBtn = item.querySelector('.btn-move-down');
			var removeBtn = item.querySelector('.btn-remove-item');

			if (upBtn) {
				upBtn.addEventListener('click', function() {
					var prev = item.previousElementSibling;
					if (prev) {
						list.insertBefore(item, prev);
						reindex();
					}
				});
			}

			if (downBtn) {
				downBtn.addEventListener('click', function() {
					var next = item.nextElementSibling;
					if (next) {
						list.insertBefore(next, item);
						reindex();
					}
				});
			}

			if (removeBtn) {
				removeBtn.addEventListener('click', function() {
					item.remove();
					reindex();
				});
			}

			// Drag-and-drop
			item.setAttribute('draggable', 'true');
			item.addEventListener('dragstart', function(e) {
				draggedItem = item;
				item.classList.add('is-dragging');
				e.dataTransfer.effectAllowed = 'move';
			});

			item.addEventListener('dragend', function() {
				draggedItem = null;
				item.classList.remove('is-dragging');
				reindex();
			});

			item.addEventListener('dragover', function(e) {
				e.preventDefault();
				e.dataTransfer.dropEffect = 'move';
				if (!draggedItem || draggedItem === item) {
					return;
				}
				var rect = item.getBoundingClientRect();
				var mid = rect.top + rect.height / 2;
				if (e.clientY < mid) {
					list.insertBefore(draggedItem, item);
				} else {
					list.insertBefore(draggedItem, item.nextSibling);
				}
			});
		}

		// Bind existing items on page load
		var initialItems = list.querySelectorAll('.wpcalibrate-gallery-item');
		initialItems.forEach(bindItemEvents);
		reindex();

		// Add Single button
		if (addBtn && urlInput) {
			addBtn.addEventListener('click', function(e) {
				e.preventDefault();
				var u = urlInput.value;
				var a = altInput ? altInput.value : '';
				if (addItem(u, a)) {
					urlInput.value = '';
					if (altInput) {
						altInput.value = '';
					}
				}
			});
		}

		// Toggle Bulk Add
		if (toggleBulkBtn && bulkWrap) {
			toggleBulkBtn.addEventListener('click', function(e) {
				e.preventDefault();
				bulkWrap.style.display = (bulkWrap.style.display === 'none') ? 'block' : 'none';
			});
		}

		if (cancelBulk && bulkWrap) {
			cancelBulk.addEventListener('click', function(e) {
				e.preventDefault();
				bulkWrap.style.display = 'none';
			});
		}

		// Confirm Bulk Add
		if (confirmBulk && bulkTextarea) {
			confirmBulk.addEventListener('click', function(e) {
				e.preventDefault();
				var lines = bulkTextarea.value.split('\n');
				var addedCount = 0;
				lines.forEach(function(line) {
					var url = line.trim();
					if (url && (url.startsWith('http://') || url.startsWith('https://'))) {
						if (addItem(url, '')) {
							addedCount++;
						}
					}
				});
				bulkTextarea.value = '';
				if (bulkWrap) {
					bulkWrap.style.display = 'none';
				}
			});
		}
	});
})();
