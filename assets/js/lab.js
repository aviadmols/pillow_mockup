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
		}).catch(function () {
			return { status: 0, ok: false, data: {} };
		});
	}

	/**
	 * POST via admin-ajax.php (multipart) — the most reliable transport on hosts
	 * where the REST route is blocked/redirected (returns 405). Refreshes the
	 * nonce once on a 403.
	 */
	function ajaxOverlay(body, retried) {
		if (!CFG.ajaxUrl) {
			return Promise.resolve({ status: 0, ok: false, data: {} });
		}
		var fd = new FormData();
		fd.append('action', CFG.ajaxAction || 'pmg_room_overlay');
		fd.append('_wpnonce', nonce);
		if (body) {
			Object.keys(body).forEach(function (k) { fd.append(k, body[k]); });
		}
		return fetch(CFG.ajaxUrl, {
			method: 'POST',
			credentials: 'same-origin',
			body: fd
		}).then(function (res) {
			return res.json().then(function (data) {
				return { status: res.status, ok: res.ok, data: data || {} };
			}).catch(function () {
				return { status: res.status, ok: res.ok, data: {} };
			});
		}).then(function (result) {
			if (result.status === 403 && !retried) {
				return refreshNonce().then(function () { return ajaxOverlay(body, true); });
			}
			return result;
		}).catch(function () {
			return { status: 0, ok: false, data: {} };
		});
	}

	/**
	 * Create the overlay. Some hosts block POST to every endpoint except the
	 * original (allow-listed) REST routes, so the dedicated /room-overlay route
	 * and admin-ajax both return 405. We therefore piggyback on /generate (the
	 * proven-working endpoint) via mode=lab_overlay, and only fall back to the
	 * dedicated transports if that path is itself blocked/unreachable.
	 */
	function requestOverlay(body) {
		var genBody = { mode: 'lab_overlay', image: body.image, session: body.session };
		return api('generate', genBody).then(function (res) {
			var blocked = !res || res.status === 0 || res.status === 404 || res.status === 405;
			if (!blocked) {
				return res;
			}
			return ajaxOverlay(body).then(function (r2) {
				if (r2 && r2.status && r2.status !== 0) {
					return r2;
				}
				return api('room-overlay', body);
			});
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
	/* Client-side background removal (chroma key)                        */
	/* ----------------------------------------------------------------- */

	/**
	 * Remove the chroma-GREEN background of the generated pillow entirely in the
	 * browser, so the pillow sits transparently on the room. The AI paints the
	 * pillow on a solid green screen; we flood-fill inward from the four borders
	 * through connected GREEN pixels only and turn them transparent. Because we
	 * key on green specifically (not "whatever colour the corner is"), a white or
	 * colourful pillow is never removed. A final despill pass neutralises the
	 * thin green fringe left along anti-aliased edges. No server library and no
	 * external service required.
	 *
	 * Always resolves: on any failure (CORS-tainted canvas, decode error, an
	 * image that is already transparent) it returns the original URL untouched.
	 */
	function chromaKeyToDataUrl(url) {
		return new Promise(function (resolve) {
			var img = new Image();
			img.crossOrigin = 'anonymous';
			img.onload = function () {
				try {
					var w = img.naturalWidth, h = img.naturalHeight;
					if (!w || !h) { resolve(url); return; }
					var canvas = document.createElement('canvas');
					canvas.width = w;
					canvas.height = h;
					var ctx = canvas.getContext('2d');
					ctx.drawImage(img, 0, 0);
					var imgData;
					try { imgData = ctx.getImageData(0, 0, w, h); }
					catch (e) { resolve(url); return; } // tainted canvas
					var data = imgData.data;
					if (cornerAlpha(data, w, h) < 200) { resolve(url); return; } // already cut out
					removeGreenBackground(data, w, h);
					despillGreen(data);
					ctx.putImageData(imgData, 0, 0);
					resolve(canvas.toDataURL('image/png'));
				} catch (e) {
					resolve(url);
				}
			};
			img.onerror = function () { resolve(url); };
			img.src = url;
		});
	}

	function cornerAlpha(data, w, h) {
		var idx = [0, (w - 1) * 4, (h - 1) * w * 4, ((h - 1) * w + (w - 1)) * 4];
		var sum = 0;
		for (var i = 0; i < idx.length; i++) { sum += data[idx[i] + 3]; }
		return sum / idx.length;
	}

	// A pixel belongs to the green screen when the green channel clearly
	// dominates both red and blue. A *ratio* test (not a fixed RGB match) keeps
	// it robust to lighting variation and anti-aliased edges, while staying
	// tight enough to preserve yellows, skin tones and whites on the pillow.
	function isGreenScreen(data, p) {
		if (data[p + 3] === 0) { return true; }
		var r = data[p], g = data[p + 1], b = data[p + 2];
		return g > 100 && g > r * 1.3 && g > b * 1.3;
	}

	function removeGreenBackground(data, w, h) {
		var total = w * h;
		var visited = new Uint8Array(total);
		var stack = [];

		function seed(i) {
			if (visited[i]) { return; }
			if (isGreenScreen(data, i * 4)) { visited[i] = 1; stack.push(i); }
		}

		var x, y;
		for (x = 0; x < w; x++) { seed(x); seed((h - 1) * w + x); }
		for (y = 0; y < h; y++) { seed(y * w); seed(y * w + (w - 1)); }

		while (stack.length) {
			var i = stack.pop();
			data[i * 4 + 3] = 0;
			x = i % w;
			y = (i - x) / w;
			if (x > 0) { var l = i - 1; if (!visited[l] && isGreenScreen(data, l * 4)) { visited[l] = 1; stack.push(l); } }
			if (x < w - 1) { var rr = i + 1; if (!visited[rr] && isGreenScreen(data, rr * 4)) { visited[rr] = 1; stack.push(rr); } }
			if (y > 0) { var u = i - w; if (!visited[u] && isGreenScreen(data, u * 4)) { visited[u] = 1; stack.push(u); } }
			if (y < h - 1) { var dn = i + w; if (!visited[dn] && isGreenScreen(data, dn * 4)) { visited[dn] = 1; stack.push(dn); } }
		}
	}

	// Neutralise the green fringe on kept pixels: where green spills above the
	// red/blue level, clamp it down to their max so edges don't glow green.
	function despillGreen(data) {
		for (var p = 0; p < data.length; p += 4) {
			if (data[p + 3] === 0) { continue; }
			var r = data[p], g = data[p + 1], b = data[p + 2];
			var cap = Math.max(r, b);
			if (g > cap + 12) { data[p + 1] = cap + 12; }
		}
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
			return requestOverlay({ image: dataUrl, session: self.session });
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
		var self = this;
		if (!this.els.overlay) { return; }
		chromaKeyToDataUrl(url).then(function (finalUrl) {
			self.els.overlay.src = finalUrl;
			self.els.overlay.hidden = false;
			if (self.els.sizes) { self.els.sizes.hidden = false; }
			self.applyLayout();
		});
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
		// Anchor the bottom-centre (the pillow's base) at (posX, posY) so resizing
		// grows/shrinks the pillow upward while its base stays pinned in place.
		o.style.transform = 'translate(-50%, -100%)';
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
