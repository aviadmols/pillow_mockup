/**
 * Pillow Mockup Generator — admin helpers.
 */
(function () {
	'use strict';

	document.addEventListener('DOMContentLoaded', function () {
		// Confirm destructive actions.
		document.querySelectorAll('[data-pmg-confirm]').forEach(function (el) {
			el.addEventListener('click', function (e) {
				if (!window.confirm(el.getAttribute('data-pmg-confirm'))) {
					e.preventDefault();
				}
			});
		});
	});
})();
