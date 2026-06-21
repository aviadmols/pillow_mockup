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

		// Model picker: toggle the custom text field when "Other" is selected.
		document.querySelectorAll('[data-pmg-model-select]').forEach(function (select) {
			var key = select.getAttribute('data-pmg-model-select');
			var custom = document.querySelector('[data-pmg-model-custom="' + key + '"]');
			if (!custom) {
				return;
			}
			var sync = function () {
				custom.hidden = select.value !== '__custom__';
			};
			select.addEventListener('change', sync);
			sync();
		});
	});
})();
