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

	// Persist active tab before form submit (Save → redirect loses the hash)
	document.addEventListener('submit', function (e) {
		var form = e.target.closest('.bparfw-settings__form');
		if (!form) return;

		var activeTab = document.querySelector('.bparfw-settings__tab.is-active');
		if (activeTab) {
			var tabName = activeTab.getAttribute('data-bparfw-tab');
			if (tabName) {
				sessionStorage.setItem('bparfwActiveTab', tabName);
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
		var errorEl = document.querySelector('[data-bparfw-color-error="' + key + '"]');
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

		if (errorEl) {
			errorEl.hidden = true;
		}
		if (hexInput) {
			hexInput.classList.remove('is-invalid');
		}
	}

	/**
	 * Show validation error on a hex input.
	 *
	 * @param {string} key
	 */
	function showColorError(key) {
		var hexInput = document.querySelector('[data-bparfw-color-hex="' + key + '"]');
		var errorEl = document.querySelector('[data-bparfw-color-error="' + key + '"]');
		if (hexInput) {
			hexInput.classList.add('is-invalid');
		}
		if (errorEl) {
			errorEl.hidden = false;
		}
	}

	/**
	 * Check all hex fields are valid; return true if all pass.
	 *
	 * @return {boolean}
	 */
	function validateAllColors() {
		var allHexInputs = document.querySelectorAll('[data-bparfw-color-hex]');
		var allValid = true;
		allHexInputs.forEach(function (input) {
			var key = input.getAttribute('data-bparfw-color-hex');
			if (input.value === '' || !HEX_REGEX.test(input.value)) {
				showColorError(key);
				allValid = false;
			} else {
				var errorEl = document.querySelector('[data-bparfw-color-error="' + key + '"]');
				input.classList.remove('is-invalid');
				if (errorEl) errorEl.hidden = true;
			}
		});
		return allValid;
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
			var key = input.getAttribute('data-bparfw-color-hex');
			var value = input.value;

			if (value === '' || !HEX_REGEX.test(value)) {
				showColorError(key);
			} else {
				var errorEl = document.querySelector('[data-bparfw-color-error="' + key + '"]');
				input.classList.remove('is-invalid');
				if (errorEl) errorEl.hidden = true;
				// Ensure uppercase
				input.value = value.toUpperCase();
				syncColorUI(key, value);
			}
		}
	});

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

		if (!validateAllColors()) {
			e.preventDefault();
			// Scroll to first invalid field
			var firstError = document.querySelector('[data-bparfw-color-hex].is-invalid');
			if (firstError) {
				firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
				firstError.focus();
			}
		}
	});
})();
