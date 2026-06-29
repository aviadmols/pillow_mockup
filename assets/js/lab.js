/**
 * Front-end logic for the experimental room-overlay lab ([pillow_mockup_lab]).
 *
 * Fully independent of frontend.js: it only acts on [data-pmg-lab] roots and
 * reads its own PMG_LAB_CONFIG. Flow: upload a photo -> downscale -> POST to
 * /lab-cutout -> overlay the transparent pillow PNG on the room image. Size
 * buttons scale the overlay instantly (CSS), no AI re-render.
 */
(function () {
	'use strict';

	if (typeof window.PMG_LAB_CONFIG === 'undefined') {
		return;
	}

	var CFG = window.PMG_LAB_CONFIG;
	var nonce = CFG.nonce;

	/* ----------------------------------------------------------------- */
	/* Helpers                                                            */
	/* ----------------------------------------------------------------- */

	function refreshNonce() {
		if (!CFG.nonceUrl) {
			return Promise.resolve();
		}
		return fetch(CFG.nonceUrl, { credentials: 'same-origin', cache: 'no-store' })
			.then(function (r) { return r.json(); })
			.then(function (d) { if (d && d.nonce) { nonce = d.nonce; } })
			.catch(function () {});
	}

	function api(path, body, retried, useFallback) {
		var base = (useFallback && CFG.restRouteUrl) ? CFG.restRouteUrl : CFG.restUrl;
		return fetch(base + path, {
			method: 'POST',
			headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': nonce },
			credentials: 'same-origin',
			body: JSON.stringify(Object.assign({ _wpnonce: nonce }, body || {}))
		}).then(function (res) {
			return res.json().then(function (data) {
				return { status: res.status, ok: res.ok, data: data || {} };
			}).catch(function () {
				return { status: res.status, ok: res.ok, data: {} };
			});
		}).then(function (result) {
			if (result.status === 405 && !useFallback && CFG.restRouteUrl) {
				return api(path, body, retried, true);
			}
			var d = result.data || {};
			var coreForbidden = (result.status === 401 || result.status === 403) && (!d.code || d.code.indexOf('rest_') === 0);
			if (coreForbidden && !retried) {
				return refreshNonce().then(function () { return api(path, body, true, useFallback); });
			}
			return result;
		});
	}

	/**
	 * Downscale an image file to a JPEG data URL bounded by maxPx on the long edge.
	 */
	function fileToDataUrl(file, maxPx) {
		maxPx = maxPx || 1280;

		function draw(src, width, height) {
			var scale = Math.min(1, maxPx / Math.max(width, height));
			var w = Math.round(width * scale);
			var h = Math.round(height * scale);
			var canvas = document.createElement('canvas');
			canvas.width = w;
			canvas.height = h;
			canvas.getContext('2d').drawImage(src, 0, 0, w, h);
			return canvas.toDataURL('image/jpeg', 0.9);
		}

		if (typeof window.createImageBitmap === 'function') {
			return createImageBitmap(file, { imageOrientation: 'from-image' })
				.then(function (bitmap) {
					var url = draw(bitmap, bitmap.width, bitmap.height);
					bitmap.close && bitmap.close();
					return url;
				})
				.catch(function () { return legacyRead(file, draw); });
		}
		return legacyRead(file, draw);
	}

	function legacyRead(file, draw) {
		return new Promise(function (resolve, reject) {
			var reader = new FileReader();
			reader.onload = function () {
				var img = new Image();
				img.onload = function () { resolve(draw(img, img.naturalWidth, img.naturalHeight)); };
				img.onerror = reject;
				img.src = reader.result;
			};
			reader.onerror = reject;
			reader.readAsDataURL(file);
		});
	}

	/* ----------------------------------------------------------------- */
	/* Widget                                                             */
	/* ----------------------------------------------------------------- */

	function LabWidget(root) {
		this.root = root;
		this.session = '';
		this.scale = 1;
		this.els = {
			file: root.querySelector('[data-lab-file]'),
			stage: root.querySelector('[data-lab-stage]'),
			overlay: root.querySelector('[data-lab-overlay]'),
			loading: root.querySelector('[data-lab-loading]'),
			upload: root.querySelector('[data-lab-upload]'),
			sizes: root.querySelector('[data-lab-sizes]'),
			notice: root.querySelector('[data-lab-notice]')
		};
		// Default scale = the selected (medium) size, else first.
		this.scale = this.scaleForSelected();
		this.bind();
	}

	LabWidget.prototype.scaleForSelected = function () {
		var selected = this.root.querySelector('[data-lab-size].is-selected');
		var id = selected ? selected.getAttribute('data-lab-size') : null;
		return this.scaleFor(id);
	};

	LabWidget.prototype.scaleFor = function (id) {
		var sizes = CFG.sizes || [];
		for (var i = 0; i < sizes.length; i++) {
			if (sizes[i].id === id) { return parseFloat(sizes[i].scale) || 1; }
		}
		return sizes.length ? (parseFloat(sizes[0].scale) || 1) : 1;
	};

	LabWidget.prototype.bind = function () {
		var self = this;

		if (this.els.upload) {
			this.els.upload.addEventListener('click', function () {
				if (self.els.file) {
					self.els.file.value = '';
					self.els.file.click();
				}
			});
		}

		if (this.els.file) {
			this.els.file.addEventListener('change', function () {
				var file = this.files && this.files[0];
				if (file) { self.handleFile(file); }
				this.value = '';
			});
		}

		if (this.els.sizes) {
			this.els.sizes.addEventListener('click', function (e) {
				var btn = e.target.closest ? e.target.closest('[data-lab-size]') : null;
				if (!btn) { return; }
				var all = self.els.sizes.querySelectorAll('[data-lab-size]');
				for (var i = 0; i < all.length; i++) { all[i].classList.remove('is-selected'); }
				btn.classList.add('is-selected');
				self.scale = self.scaleFor(btn.getAttribute('data-lab-size'));
				self.applyLayout();
			});
		}
	};

	LabWidget.prototype.notice = function (msg) {
		if (!this.els.notice) { return; }
		this.els.notice.textContent = msg || '';
		this.els.notice.hidden = !msg;
	};

	LabWidget.prototype.busy = function (on) {
		if (this.els.loading) { this.els.loading.hidden = !on; }
		this.root.classList.toggle('is-busy', !!on);
	};

	LabWidget.prototype.handleFile = function (file) {
		var self = this;
		if (!file || !file.type || file.type.indexOf('image/') !== 0) {
			window.alert(CFG.i18n.invalidFile);
			return;
		}
		this.notice('');
		this.busy(true);

		fileToDataUrl(file, CFG.maxPx).then(function (dataUrl) {
			return api('room-overlay', { image: dataUrl, session: self.session });
		}).then(function (res) {
			self.busy(false);
			var d = res.data || {};
			if (d.session) { self.session = d.session; }
			if (!res.ok || d.code !== 'ok' || !d.url) {
				self.notice(d.message || CFG.i18n.error);
				return;
			}
			self.showOverlay(d.url);
		}).catch(function () {
			self.busy(false);
			self.notice(CFG.i18n.error);
		});
	};

	LabWidget.prototype.showOverlay = function (url) {
		if (!this.els.overlay) { return; }
		this.els.overlay.src = url;
		this.els.overlay.hidden = false;
		if (this.els.sizes) { this.els.sizes.hidden = false; }
		this.applyLayout();
	};

	/**
	 * Position and scale the pillow overlay over the room image. The overlay is
	 * centred at (posX, posY) percent of the stage and its width is
	 * baseWidth * scale percent of the stage width.
	 */
	LabWidget.prototype.applyLayout = function () {
		var o = this.els.overlay;
		if (!o) { return; }
		var width = (parseFloat(CFG.baseWidth) || 30) * (this.scale || 1);
		o.style.position = 'absolute';
		o.style.left = (parseFloat(CFG.posX) || 50) + '%';
		o.style.top = (parseFloat(CFG.posY) || 50) + '%';
		o.style.width = width + '%';
		o.style.height = 'auto';
		o.style.transform = 'translate(-50%, -50%)';
	};

	/* ----------------------------------------------------------------- */
	/* Boot                                                               */
	/* ----------------------------------------------------------------- */

	function boot() {
		var roots = document.querySelectorAll('[data-pmg-lab]');
		if (!roots.length) { return; }
		refreshNonce();
		Array.prototype.forEach.call(roots, function (root) {
			if (root.querySelector('[data-lab-stage]')) {
				new LabWidget(root);
			}
		});
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', boot);
	} else {
		boot();
	}
})();
