/**
 * Pillow Mockup Generator — front-end widget controller.
 * Vanilla JS, no inline styles (state is driven by classes / data attributes).
 */
(function () {
	'use strict';

	if (typeof window.PMG_CONFIG === 'undefined') {
		return;
	}

	var CFG = window.PMG_CONFIG;

	/**
	 * POST JSON to a plugin REST endpoint.
	 */
	function api(path, body) {
		return fetch(CFG.restUrl + path, {
			method: 'POST',
			headers: {
				'Content-Type': 'application/json',
				'X-PMG-Nonce': CFG.nonce
			},
			body: JSON.stringify(Object.assign({ nonce: CFG.nonce }, body || {}))
		}).then(function (res) {
			return res.json().then(function (data) {
				return { status: res.status, ok: res.ok, data: data || {} };
			}).catch(function () {
				return { status: res.status, ok: res.ok, data: {} };
			});
		});
	}

	/**
	 * Downscale an image file to a JPEG data URL bounded by maxPx on the longest edge.
	 */
	function fileToDataUrl(file, maxPx) {
		maxPx = maxPx || 1280;

		function draw(bitmapOrImg, width, height) {
			var scale = Math.min(1, maxPx / Math.max(width, height));
			var w = Math.round(width * scale);
			var h = Math.round(height * scale);
			var canvas = document.createElement('canvas');
			canvas.width = w;
			canvas.height = h;
			var ctx = canvas.getContext('2d');
			ctx.drawImage(bitmapOrImg, 0, 0, w, h);
			return canvas.toDataURL('image/jpeg', 0.9);
		}

		if (typeof window.createImageBitmap === 'function') {
			return createImageBitmap(file, { imageOrientation: 'from-image' })
				.then(function (bitmap) {
					var url = draw(bitmap, bitmap.width, bitmap.height);
					bitmap.close && bitmap.close();
					return url;
				})
				.catch(function () {
					return legacyRead(file, draw);
				});
		}
		return legacyRead(file, draw);
	}

	function legacyRead(file, draw) {
		return new Promise(function (resolve, reject) {
			var reader = new FileReader();
			reader.onload = function () {
				var img = new Image();
				img.onload = function () {
					resolve(draw(img, img.naturalWidth, img.naturalHeight));
				};
				img.onerror = reject;
				img.src = reader.result;
			};
			reader.onerror = reject;
			reader.readAsDataURL(file);
		});
	}

	/**
	 * One widget instance.
	 */
	function Widget(root) {
		this.root = root;
		this.state = {
			session: '',
			attemptsLeft: 0,
			hasLead: false,
			pending: null,
			image: ''
		};
		this.lottie = null;

		this.els = {
			file: root.querySelector('[data-pmg-file]'),
			dropzone: root.querySelector('[data-pmg-dropzone]'),
			result: root.querySelector('[data-pmg-result]'),
			lottie: root.querySelector('[data-pmg-lottie]'),
			loadingText: root.querySelector('[data-pmg-loading-text]'),
			attempts: root.querySelector('[data-pmg-attempts]'),
			retryBtn: root.querySelector('[data-pmg-action="retry"]'),
			previewNotice: root.querySelector('[data-pmg-preview-notice]'),
			form: root.querySelector('[data-pmg-form]'),
			formNotice: root.querySelector('[data-pmg-form-notice]')
		};

		this.bind();
	}

	Widget.prototype.setState = function (state) {
		this.root.setAttribute('data-state', state);
		if (state === 'loading') {
			this.startLottie();
		} else {
			this.stopLottie();
		}
	};

	Widget.prototype.busy = function (on) {
		this.root.classList.toggle('is-busy', !!on);
	};

	Widget.prototype.startLottie = function () {
		var self = this;
		if (!this.els.lottie) {
			return;
		}
		if (typeof window.lottie === 'undefined' || !CFG.lottieUrl) {
			this.root.classList.add('is-no-lottie');
			return;
		}
		try {
			this.lottie = window.lottie.loadAnimation({
				container: this.els.lottie,
				renderer: 'svg',
				loop: true,
				autoplay: true,
				path: CFG.lottieUrl
			});
			this.lottie.addEventListener('data_failed', function () {
				self.root.classList.add('is-no-lottie');
			});
		} catch (e) {
			this.root.classList.add('is-no-lottie');
		}
	};

	Widget.prototype.stopLottie = function () {
		if (this.lottie) {
			try { this.lottie.destroy(); } catch (e) {}
			this.lottie = null;
		}
		if (this.els.lottie) {
			this.els.lottie.innerHTML = '';
		}
	};

	Widget.prototype.notice = function (el, message) {
		if (!el) {
			return;
		}
		if (message) {
			el.textContent = message;
			el.hidden = false;
		} else {
			el.textContent = '';
			el.hidden = true;
		}
	};

	Widget.prototype.bind = function () {
		var self = this;

		if (this.els.file) {
			this.els.file.addEventListener('change', function () {
				if (this.files && this.files[0]) {
					self.handleFile(this.files[0]);
				}
			});
		}

		// Drag & drop on the dropzone.
		if (this.els.dropzone) {
			['dragenter', 'dragover'].forEach(function (ev) {
				self.els.dropzone.addEventListener(ev, function (e) {
					e.preventDefault();
					this.classList.add('is-dragover');
				});
			});
			['dragleave', 'drop'].forEach(function (ev) {
				self.els.dropzone.addEventListener(ev, function (e) {
					e.preventDefault();
					this.classList.remove('is-dragover');
				});
			});
			this.els.dropzone.addEventListener('drop', function (e) {
				var dt = e.dataTransfer;
				if (dt && dt.files && dt.files[0]) {
					self.handleFile(dt.files[0]);
				}
			});
		}

		// Action buttons.
		this.root.querySelectorAll('[data-pmg-action]').forEach(function (btn) {
			btn.addEventListener('click', function () {
				self.onAction(btn.getAttribute('data-pmg-action'));
			});
		});

		// Details form.
		if (this.els.form) {
			this.els.form.addEventListener('submit', function (e) {
				e.preventDefault();
				self.submitLead();
			});
		}
	};

	Widget.prototype.onAction = function (action) {
		switch (action) {
			case 'change':
				if (!this.state.hasLead) {
					this.openDetails('change');
				} else {
					this.els.file && this.els.file.click();
				}
				break;
			case 'retry':
				if (!this.state.hasLead) {
					this.openDetails('retry');
				} else if (this.state.attemptsLeft > 0) {
					this.generate(null);
				}
				break;
			case 'love':
				if (!this.state.hasLead) {
					this.openDetails('love');
				} else {
					this.finalize();
				}
				break;
			case 'back-to-preview':
				this.setState('preview');
				break;
		}
	};

	Widget.prototype.openDetails = function (pending) {
		this.state.pending = pending;
		this.notice(this.els.formNotice, '');
		this.setState('details');
	};

	Widget.prototype.handleFile = function (file) {
		var self = this;
		if (!file.type || file.type.indexOf('image/') !== 0) {
			alert(CFG.i18n.invalidFile);
			return;
		}
		this.setState('loading');
		this.busy(true);
		fileToDataUrl(file, CFG.maxPx).then(function (dataUrl) {
			self.state.image = dataUrl;
			self.generate(dataUrl);
		}).catch(function () {
			self.busy(false);
			self.setState('upload');
			alert(CFG.i18n.invalidFile);
		});
	};

	Widget.prototype.generate = function (imageDataUrl) {
		var self = this;
		this.setState('loading');
		this.busy(true);

		api('generate', {
			session: this.state.session,
			image: imageDataUrl || ''
		}).then(function (res) {
			self.busy(false);
			var d = res.data;
			if (d.session) {
				self.state.session = d.session;
			}

			if (res.status === 403 && d.code === 'need_details') {
				self.openDetails(self.state.pending || 'retry');
				return;
			}
			if (res.status === 429 || d.code === 'max_attempts') {
				self.state.attemptsLeft = 0;
				self.showResult();
				self.notice(self.els.previewNotice, d.message || '');
				self.updateToolbar();
				return;
			}
			if (!res.ok || d.code !== 'ok') {
				self.fail(d.message);
				return;
			}

			self.state.hasLead = !!d.has_lead;
			self.state.attemptsLeft = typeof d.attempts_left === 'number' ? d.attempts_left : self.state.attemptsLeft;
			if (d.mockup_url) {
				self.els.result.src = d.mockup_url;
			}
			self.showResult();
			self.updateToolbar();
		}).catch(function () {
			self.busy(false);
			self.fail();
		});
	};

	Widget.prototype.showResult = function () {
		this.notice(this.els.previewNotice, '');
		this.setState('preview');
	};

	Widget.prototype.updateToolbar = function () {
		var s = this.state;
		if (this.els.retryBtn) {
			var noTries = s.hasLead && s.attemptsLeft <= 0;
			this.els.retryBtn.disabled = noTries;
		}
		if (this.els.attempts) {
			if (s.hasLead) {
				this.els.attempts.hidden = false;
				this.els.attempts.textContent = s.attemptsLeft + ' ' + (CFG.attemptsLabel || '');
			} else {
				this.els.attempts.hidden = true;
			}
		}
	};

	Widget.prototype.fail = function (message) {
		this.notice(this.els.previewNotice, message || CFG.i18n.genericError);
		// If we already have a result, stay on preview; otherwise return to upload.
		if (this.els.result && this.els.result.getAttribute('src')) {
			this.setState('preview');
		} else {
			this.setState('upload');
			alert(message || CFG.i18n.genericError);
		}
	};

	Widget.prototype.fieldError = function (name, message) {
		var input = this.root.querySelector('[data-pmg-input="' + name + '"]');
		var err = this.root.querySelector('[data-pmg-error="' + name + '"]');
		var field = input ? input.closest('.pmg__field') : null;
		if (err) {
			err.textContent = message || '';
		}
		if (field) {
			field.classList.toggle('is-invalid', !!message);
		}
	};

	Widget.prototype.submitLead = function () {
		var self = this;
		var get = function (name) {
			var el = self.root.querySelector('[data-pmg-input="' + name + '"]');
			return el ? el.value.trim() : '';
		};
		var name = get('name');
		var phone = get('phone');
		var email = get('email');

		['name', 'phone', 'email'].forEach(function (f) { self.fieldError(f, ''); });

		var valid = true;
		if (!name) { this.fieldError('name', '•'); valid = false; }
		if (!phone) { this.fieldError('phone', '•'); valid = false; }
		if (!email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) { this.fieldError('email', '•'); valid = false; }
		if (!valid) {
			return;
		}

		this.busy(true);
		this.notice(this.els.formNotice, '');

		api('lead', {
			session: this.state.session,
			name: name,
			phone: phone,
			email: email
		}).then(function (res) {
			self.busy(false);
			var d = res.data;
			if (d.session) {
				self.state.session = d.session;
			}
			if (res.status === 422 && d.errors) {
				Object.keys(d.errors).forEach(function (f) {
					self.fieldError(f, d.errors[f]);
				});
				return;
			}
			if (!res.ok || d.code !== 'ok') {
				self.notice(self.els.formNotice, d.message || CFG.i18n.genericError);
				return;
			}

			self.state.hasLead = true;
			self.state.attemptsLeft = typeof d.attempts_left === 'number' ? d.attempts_left : self.state.attemptsLeft;

			var pending = self.state.pending;
			self.state.pending = null;

			if (pending === 'love') {
				self.finalize();
			} else if (pending === 'retry') {
				self.generate(null);
			} else if (pending === 'change') {
				self.setState('preview');
				self.updateToolbar();
				self.els.file && self.els.file.click();
			} else {
				self.setState('preview');
				self.updateToolbar();
			}
		}).catch(function () {
			self.busy(false);
			self.notice(self.els.formNotice, CFG.i18n.genericError);
		});
	};

	Widget.prototype.finalize = function () {
		var self = this;
		this.setState('loading');
		this.busy(true);

		api('finalize', { session: this.state.session }).then(function (res) {
			self.busy(false);
			var d = res.data;
			if (res.status === 403 && d.code === 'need_details') {
				self.openDetails('love');
				return;
			}
			if (!res.ok || d.code !== 'done') {
				self.fail(d.message);
				return;
			}
			self.setState('done');
		}).catch(function () {
			self.busy(false);
			self.fail();
		});
	};

	// Boot all widgets on the page.
	document.addEventListener('DOMContentLoaded', function () {
		var roots = document.querySelectorAll('[data-pmg]');
		Array.prototype.forEach.call(roots, function (root) {
			new Widget(root);
		});
	});
})();
