/**
 * Beplus Advanced Reviews For Woocommerce — Admin Settings
 * Handles tab switching and toggle interactions.
 */
(function () {
	'use strict';

	/**
	 * Activate a tab panel.
	 *
	 * @param {string} tabName
	 */
	function activateTab(tabName) {
		var tabs = document.querySelectorAll('.bparfw-settings__tab');
		var panels = document.querySelectorAll('.bparfw-settings__panel');

		tabs.forEach(function (tab) {
			var isActive = tab.getAttribute('data-bparfw-tab') === tabName;
			tab.classList.toggle('is-active', isActive);
		});

		panels.forEach(function (panel) {
			var isActive = panel.getAttribute('data-bparfw-panel') === tabName;
			panel.classList.toggle('is-active', isActive);
		});
	}

	/**
	 * Handle toggle switch change.
	 *
	 * @param {HTMLInputElement} input
	 */
	function handleToggle(input) {
		var toggleGroup = input.getAttribute('data-bparfw-toggle');
		var stateLabel = document.querySelector('[data-bparfw-state-label="' + toggleGroup + '"]');
		var offNote = document.querySelector('[data-bparfw-off-note="' + toggleGroup + '"]');
		var panel = document.querySelector('[data-bparfw-panel-section="' + toggleGroup + '"]');
		var isOn = input.checked;

		if (stateLabel) {
			stateLabel.textContent = isOn ? 'On' : 'Off';
		}
		if (offNote) {
			offNote.hidden = isOn;
		}
		if (panel) {
			panel.hidden = !isOn;
		}
	}

	// Tab click handler
	document.addEventListener('click', function (e) {
		var tab = e.target.closest('.bparfw-settings__tab');
		if (!tab) return;

		e.preventDefault();
		var tabName = tab.getAttribute('data-bparfw-tab');
		if (tabName) {
			activateTab(tabName);

			if (window.history && window.history.replaceState) {
				window.history.replaceState(null, '', '#tab-' + tabName);
			}
		}
	});

	// Toggle change handler
	document.addEventListener('change', function (e) {
		var input = e.target;
		if (input.matches && input.matches('.bparfw-toggle__input')) {
			handleToggle(input);
		}
	});

	// Restore active tab from hash on load
	document.addEventListener('DOMContentLoaded', function () {
		// After a save, sessionStorage takes priority (WordPress redirect strips the hash)
		var savedTab = sessionStorage.getItem('bparfwActiveTab');
		if (savedTab) {
			sessionStorage.removeItem('bparfwActiveTab');
			activateTab(savedTab);
			return;
		}

		var hash = window.location.hash;
		if (hash && hash.indexOf('#tab-') === 0) {
			var tabName = hash.replace('#tab-', '');
			if (tabName) {
				activateTab(tabName);
			}
		}
	});

	// =============================================
	// Color picker sync & validation
	// =============================================
	var HEX_REGEX = /^#[0-9A-Fa-f]{6}$/;

	/**
	 * Update all UI elements for a color: swatch, hex input, status chip, reset button.
	 *
	 * @param {string} key
	 * @param {string} value
	 */
	function syncColorUI(key, value) {
		var swatch = document.querySelector('[data-bparfw-color-swatch="' + key + '"]');
		var hexInput = document.querySelector('[data-bparfw-color-hex="' + key + '"]');
		var statusEl = document.querySelector('[data-bparfw-color-status="' + key + '"]');
		var resetBtn = document.querySelector('[data-bparfw-color-reset="' + key + '"]');
		var nativeInput = document.querySelector('[data-bparfw-color-input="' + key + '"]');
		var defaultVal = nativeInput ? nativeInput.getAttribute('data-bparfw-color-default') : '';

		if (swatch) {
			swatch.style.backgroundColor = value;
		}
		if (hexInput && hexInput.value.toUpperCase() !== value.toUpperCase()) {
			hexInput.value = value;
		}
		if (nativeInput && nativeInput.value.toUpperCase() !== value.toUpperCase()) {
			nativeInput.value = value;
		}

		var isDefault = (value.toUpperCase() === defaultVal.toUpperCase());
		if (statusEl) {
			if (isDefault) {
				statusEl.innerHTML = '<span class="bparfw-color-field__chip bparfw-color-field__chip--default">Default</span>';
			} else {
				statusEl.innerHTML = '<span class="bparfw-color-field__chip bparfw-color-field__chip--custom">Custom</span>';
			}
		}

		if (resetBtn) {
			resetBtn.hidden = isDefault;
		}

		if (hexInput) {
			setColorError(hexInput, false);
		}
	}

	/**
	 * Update the validation state of a hex input.
	 *
	 * @param {HTMLInputElement} input
	 * @param {boolean} invalid
	 */
	function setColorError(input, invalid) {
		var key = input.getAttribute('data-bparfw-color-hex');
		var errorEl = document.querySelector('[data-bparfw-color-error="' + key + '"]');
		input.classList.toggle('is-invalid', invalid);
		if (errorEl) {
			errorEl.hidden = !invalid;
		}
		if (invalid) {
			input.setAttribute('aria-invalid', 'true');
			if (errorEl) input.setAttribute('aria-describedby', errorEl.id);
		} else {
			input.removeAttribute('aria-invalid');
			input.removeAttribute('aria-describedby');
		}
	}

	/**
	 * Validate a hex input and normalize the value if valid.
	 *
	 * @param {HTMLInputElement} input
	 * @return {boolean}
	 */
	function validateColor(input) {
		if (!HEX_REGEX.test(input.value)) {
			setColorError(input, true);
			return false;
		}
		input.value = input.value.toUpperCase();
		syncColorUI(input.getAttribute('data-bparfw-color-hex'), input.value);
		return true;
	}

	// Native color input change → sync hex + swatch
	document.addEventListener('input', function (e) {
		var input = e.target;
		if (input.matches && input.matches('[data-bparfw-color-input]')) {
			var key = input.getAttribute('data-bparfw-color-input');
			syncColorUI(key, input.value);
		}
	});

	// Hex input change → sync native + swatch (only if valid)
	document.addEventListener('input', function (e) {
		var input = e.target;
		if (input.matches && input.matches('[data-bparfw-color-hex]')) {
			var key = input.getAttribute('data-bparfw-color-hex');
			var value = input.value;

			// Auto-prepend # if missing
			if (value.length > 0 && value[0] !== '#') {
				value = '#' + value;
				input.value = value;
			}

			if (HEX_REGEX.test(value)) {
				syncColorUI(key, value);
			}
		}
	});

	// Hex input blur → validate
	document.addEventListener('blur', function (e) {
		var input = e.target;
		if (input.matches && input.matches('[data-bparfw-color-hex]')) {
			validateColor(input);
		}
	}, true);

	// Individual reset button click
	document.addEventListener('click', function (e) {
		var resetBtn = e.target.closest('[data-bparfw-color-reset]');
		if (!resetBtn) return;

		e.preventDefault();
		var key = resetBtn.getAttribute('data-bparfw-color-reset');
		var nativeInput = document.querySelector('[data-bparfw-color-input="' + key + '"]');
		if (nativeInput) {
			var defaultVal = nativeInput.getAttribute('data-bparfw-color-default') || '#000000';
			syncColorUI(key, defaultVal);
		}
	});

	// Global "Reset to Defaults" button click
	document.addEventListener('click', function (e) {
		var resetAllBtn = e.target.closest('#bparfw-reset-colors');
		if (!resetAllBtn) return;

		e.preventDefault();
		if (!confirm('Reset all colors to their default values? This cannot be undone.')) {
			return;
		}

		var allNativeInputs = document.querySelectorAll('[data-bparfw-color-input]');
		allNativeInputs.forEach(function (input) {
			var key = input.getAttribute('data-bparfw-color-input');
			var defaultVal = input.getAttribute('data-bparfw-color-default') || '#000000';
			syncColorUI(key, defaultVal);
		});
	});

	// Block form submit if any hex field is invalid
	document.addEventListener('submit', function (e) {
		var form = e.target.closest('.bparfw-settings__form');
		if (!form) return;

		var firstError;
		form.querySelectorAll('[data-bparfw-color-hex]').forEach(function (input) {
			if (!validateColor(input) && !firstError) firstError = input;
		});

		if (firstError) {
			e.preventDefault();
			form.closest('.bparfw-settings').querySelector('[data-bparfw-tab="styling"]').click();
			firstError.focus();
			return;
		}

		// Save → redirect loses the hash.
		var activeTab = document.querySelector('.bparfw-settings__tab.is-active');
		if (activeTab) sessionStorage.setItem('bparfwActiveTab', activeTab.getAttribute('data-bparfw-tab'));
	});
})();
