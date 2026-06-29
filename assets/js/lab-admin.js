/**
 * Admin helpers for the Room Mockup (Lab) page:
 *  - WP media picker for the reference image.
 *  - Live preview box that follows the position/size inputs.
 */
(function () {
	'use strict';

	function ready(fn) {
		if (document.readyState !== 'loading') { fn(); }
		else { document.addEventListener('DOMContentLoaded', fn); }
	}

	ready(function () {
		// ---- Media picker ----
		var pickBtn = document.querySelector('[data-lab-pick]');
		var refInput = document.querySelector('[data-lab-ref-url]');
		var previewImg = document.querySelector('[data-lab-preview-img]');

		if (pickBtn && refInput && window.wp && window.wp.media) {
			var frame = null;
			pickBtn.addEventListener('click', function (e) {
				e.preventDefault();
				if (frame) { frame.open(); return; }
				frame = window.wp.media({
					title: 'Select reference image',
					button: { text: 'Use this image' },
					library: { type: 'image' },
					multiple: false
				});
				frame.on('select', function () {
					var att = frame.state().get('selection').first().toJSON();
					if (att && att.url) {
						refInput.value = att.url;
						if (previewImg) { previewImg.src = att.url; }
					}
				});
				frame.open();
			});
		}

		// ---- Demo pillow media picker ----
		var demoPickBtn = document.querySelector('[data-lab-demo-pick]');
		var demoRefInput = document.querySelector('[data-lab-demo-ref-url]');

		if (demoPickBtn && demoRefInput && window.wp && window.wp.media) {
			var demoFrame = null;
			demoPickBtn.addEventListener('click', function (e) {
				e.preventDefault();
				if (demoFrame) { demoFrame.open(); return; }
				demoFrame = window.wp.media({
					title: 'Select demo image',
					button: { text: 'Use this image' },
					library: { type: 'image' },
					multiple: false
				});
				demoFrame.on('select', function () {
					var att = demoFrame.state().get('selection').first().toJSON();
					if (att && att.url) { demoRefInput.value = att.url; }
				});
				demoFrame.open();
			});
		}

		// ---- Live preview box ----
		var box = document.querySelector('[data-lab-preview-box]');
		if (!box) { return; }

		function val(name, fallback) {
			var el = document.querySelector('[data-lab-input="' + name + '"]');
			var n = el ? parseFloat(el.value) : NaN;
			return isNaN(n) ? fallback : n;
		}

		function update() {
			box.style.left = val('pos_x', 50) + '%';
			box.style.top = val('pos_y', 50) + '%';
			box.style.width = val('base_width', 30) + '%';
		}

		['pos_x', 'pos_y', 'base_width'].forEach(function (name) {
			var el = document.querySelector('[data-lab-input="' + name + '"]');
			if (el) {
				el.addEventListener('input', update);
				el.addEventListener('change', update);
			}
		});

		update();
	});
})();
