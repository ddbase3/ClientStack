/**
 * BASE3 global chatbot launcher.
 * Platform-neutral ES module. Host integrations supply only a render URL, labels,
 * optional branding and the centralized window settings.
 */
const mountedRoots = new WeakMap();

export class GlobalChatbotLauncher {
	constructor(configElement) {
		this.configElement = configElement;
		this.mounted = false;
	}

	mount() {
		if (this.mounted) return this;
		this.mounted = true;
		init(this.configElement);
		return this;
	}
}

export function mountGlobalChatbotLauncher(element) {
	if (!(element instanceof Element)) return null;
	if (mountedRoots.has(element)) return mountedRoots.get(element);
	const launcher = new GlobalChatbotLauncher(element);
	mountedRoots.set(element, launcher);
	return launcher.mount();
}

export function initializeGlobalChatbotLaunchers() {
	document.querySelectorAll('[data-base3-global-chatbot-launcher]').forEach(mountGlobalChatbotLauncher);
}

function init(config) {
	if (!config) {
		return;
	}

	if (document.getElementById('base3-chatbot-fab')) {
		return;
	}

	var ajaxUrl = config.getAttribute('data-ajax-url') || '';
	var buttonLabel = config.getAttribute('data-button-label') || '';
	var launcherIcon = config.getAttribute('data-launcher-icon') || '';
	var headerIcon = config.getAttribute('data-header-icon') || '';
	var headerTitle = config.getAttribute('data-header-title') || buttonLabel;
	var strings = {
		close: config.getAttribute('data-close-label') || '',
		loading: config.getAttribute('data-loading-label') || '',
		loadError: config.getAttribute('data-load-error') || '',
		resize: config.getAttribute('data-resize-label') || 'Resize chat window',
		modes: {
			overlay: config.getAttribute('data-mode-overlay-label') || '',
			modal: config.getAttribute('data-mode-modal-label') || '',
			docked: config.getAttribute('data-mode-docked-label') || '',
			cover: config.getAttribute('data-mode-cover-label') || '',
			fullscreen: config.getAttribute('data-mode-fullscreen-label') || ''
		}
	};
	var settings = readSettings(config);

	if (ajaxUrl === '' || getFirstEnabledMode(settings, '') === '') {
		return;
	}

	var state = {
		loaded: false,
		loading: false,
		mode: '',
		fullscreenReturnMode: '',
		resizeFrame: 0,
		sizeSaveTimer: 0
	};

	var launcher = createLauncher(buttonLabel, launcherIcon);
	var panel = createPanel(headerIcon, headerTitle, strings, settings);
	panel.dockTarget = config.getAttribute('data-dock-target') || '';
	panel.dockHeaderTarget = config.getAttribute('data-dock-header-target') || '';
	panel.originalDockStyles = null;
	panel.originalHeaderStyles = null;

	installAutoResize(panel, state, settings);
	installContainerResizeObserver(panel, state, settings);
	installManualResize(panel, state, settings);

	launcher.button.addEventListener('click', function() {
		var mode = getInitialMode(settings);
		openPanel(panel, launcher, state, settings, strings, mode, true);
		loadChatbot(panel, state, settings, ajaxUrl, strings);
	});

	Object.keys(panel.modeButtons).forEach(function(mode) {
		panel.modeButtons[mode].addEventListener('click', function() {
			setMode(panel, state, settings, strings, mode, true);
		});
	});

	panel.closeButton.addEventListener('click', function() {
		closePanel(panel, launcher, state, settings);
	});

	panel.backdrop.addEventListener('click', function() {
		if (state.mode === 'modal') closePanel(panel, launcher, state, settings);
	});

	panel.container.addEventListener('keydown', function(event) {
		if (event.key !== 'Tab' || !['modal', 'cover', 'fullscreen'].includes(state.mode)) return;
		var focusable = Array.from(panel.container.querySelectorAll(
			'button:not([disabled]):not([hidden]), a[href], input:not([disabled]), textarea:not([disabled]), select:not([disabled]), [tabindex]:not([tabindex="-1"])'
		)).filter(function(element) { return element.getClientRects().length > 0; });
		if (focusable.length === 0) return;
		var first = focusable[0];
		var last = focusable[focusable.length - 1];
		if (event.shiftKey && document.activeElement === first) {
			event.preventDefault(); last.focus();
		} else if (!event.shiftKey && document.activeElement === last) {
			event.preventDefault(); first.focus();
		}
	});

	document.addEventListener('keydown', function(event) {
		if (event.key !== 'Escape' || !panel.container.classList.contains('is-open')) {
			return;
		}

		if (state.mode === 'fullscreen') {
			return;
		}

		closePanel(panel, launcher, state, settings);
	});

	document.addEventListener('fullscreenchange', function() {
		if (!panel.container.classList.contains('is-open')) {
			return;
		}

		if (state.mode !== 'fullscreen' || document.fullscreenElement === panel.container) {
			return;
		}

		var returnMode = state.fullscreenReturnMode;
		if (returnMode !== '' && isModeEnabled(settings, returnMode)) {
			setMode(panel, state, settings, strings, returnMode, false);
			return;
		}

		var nextMode = getFirstEnabledMode(settings, 'fullscreen');
		if (nextMode !== '') {
			setMode(panel, state, settings, strings, nextMode, false);
			return;
		}

		closePanel(panel, launcher, state, settings);
	});

	restoreOpenState(panel, launcher, state, settings, strings, ajaxUrl);
}

function readSettings(config) {
	var group = config.getAttribute('data-config-group') || 'default';
	var name = config.getAttribute('data-config-name') || 'default';

	return {
		modes: {
			overlay: readBoolAttribute(config, 'data-mode-overlay-enabled', true),
			modal: readBoolAttribute(config, 'data-mode-modal-enabled', true),
			docked: readBoolAttribute(config, 'data-mode-docked-enabled', false),
			cover: readBoolAttribute(config, 'data-mode-cover-enabled', false),
			fullscreen: readBoolAttribute(config, 'data-mode-fullscreen-enabled', false)
		},
		resizable: {
			overlay: readBoolAttribute(config, 'data-resizable-overlay', false),
			modal: readBoolAttribute(config, 'data-resizable-modal', false),
			docked: readBoolAttribute(config, 'data-resizable-docked', false)
		},
		rememberLastMode: readBoolAttribute(config, 'data-remember-last-mode', false),
		reopen: {
			overlay: readBoolAttribute(config, 'data-reopen-overlay', false),
			docked: readBoolAttribute(config, 'data-reopen-docked', false)
		},
		storagePrefix: 'base3-global-chatbot:' + group + ':' + name
	};
}

function readBoolAttribute(element, name, defaultValue) {
	var value = element.getAttribute(name);
	if (value === null || value === '') {
		return defaultValue;
	}

	return value === '1';
}

function createLauncher(buttonLabel, launcherIcon) {
	var launcher = document.createElement('div');
	launcher.id = 'base3-chatbot-launcher';

	var button = document.createElement('button');
	button.type = 'button';
	button.id = 'base3-chatbot-fab';
	button.setAttribute('aria-label', buttonLabel);
	button.setAttribute('title', buttonLabel);
	button.setAttribute('aria-haspopup', 'dialog');
	button.setAttribute('aria-expanded', 'false');

	if (launcherIcon !== '') {
		button.classList.add('has-icon');

		var icon = document.createElement('img');
		icon.className = 'base3-chatbot-launcher-icon';
		icon.src = launcherIcon;
		icon.alt = '';
		button.appendChild(icon);
	} else {
		button.textContent = buttonLabel;
	}

	launcher.appendChild(button);
	document.body.appendChild(launcher);

	return {
		container: launcher,
		button: button
	};
}

/**
 * Preserve simple branding emphasis without rendering arbitrary host-supplied
 * elements or attributes. The template is inert; only fresh allowed nodes
 * are inserted into the live page.
 */
export function appendFormattedHeaderTitle(title, markup) {
	var template = document.createElement('template');
	template.innerHTML = markup;
	var allowedTags = ['STRONG', 'B', 'EM', 'I', 'BR'];
	var ignoredTags = ['SCRIPT', 'STYLE', 'TEMPLATE', 'IFRAME', 'OBJECT', 'EMBED'];

	function appendSafeChildren(source, target) {
		Array.from(source.childNodes).forEach(function(node) {
			if (node.nodeType === 3) {
				target.appendChild(document.createTextNode(node.textContent || ''));
				return;
			}

			if (node.nodeType !== 1 || ignoredTags.includes(node.tagName)) return;

			if (allowedTags.includes(node.tagName)) {
				var element = document.createElement(node.tagName.toLowerCase());
				appendSafeChildren(node, element);
				target.appendChild(element);
				return;
			}

			appendSafeChildren(node, target);
		});
	}

	appendSafeChildren(template.content, title);
}

function createPanel(headerIcon, headerTitle, strings, settings) {
	var backdrop = document.createElement('div');
	backdrop.id = 'base3-chatbot-backdrop';
	document.body.appendChild(backdrop);

	var panel = document.createElement('div');
	panel.id = 'base3-chatbot-panel';
	panel.setAttribute('role', 'dialog');
	panel.setAttribute('aria-modal', 'false');
	panel.setAttribute('aria-hidden', 'true');

	var header = document.createElement('div');
	header.id = 'base3-chatbot-panel-header';

	var brand = document.createElement('div');
	brand.id = 'base3-chatbot-panel-brand';

	if (headerIcon !== '') {
		var logo = document.createElement('img');
		logo.id = 'base3-chatbot-panel-logo';
		logo.src = headerIcon;
		logo.alt = '';
		brand.appendChild(logo);
	}

	if (headerTitle !== '') {
		var title = document.createElement('div');
		title.id = 'base3-chatbot-panel-title';
		appendFormattedHeaderTitle(title, headerTitle);
		brand.appendChild(title);
		panel.setAttribute('aria-label', title.textContent || '');
	}

	var actions = document.createElement('div');
	actions.id = 'base3-chatbot-panel-actions';

	var modeButtons = {};
	['overlay', 'modal', 'docked', 'cover', 'fullscreen'].forEach(function(mode) {
		if (!isModeEnabled(settings, mode)) {
			return;
		}

		var button = createHeaderButton(
			'base3-chatbot-mode-' + mode,
			strings.modes[mode],
			getModeIcon(mode)
		);
		button.setAttribute('data-chatbot-mode', mode);
		modeButtons[mode] = button;
		actions.appendChild(button);
	});

	var closeButton = createHeaderButton(
		'base3-chatbot-close',
		strings.close,
		'<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>'
	);
	actions.appendChild(closeButton);

	header.appendChild(brand);
	header.appendChild(actions);

	var body = document.createElement('div');
	body.id = 'base3-chatbot-panel-body';
	body.innerHTML = '<div class="base3-chatbot-loading"></div>';
	body.firstChild.textContent = strings.loading;

	var resizeHandle = document.createElement('div');
	resizeHandle.className = 'base3-chatbot-resize-handle';
	resizeHandle.setAttribute('role', 'button');
	resizeHandle.setAttribute('aria-label', strings.resize);
	resizeHandle.setAttribute('aria-keyshortcuts', 'ArrowLeft ArrowRight ArrowUp ArrowDown');
	resizeHandle.tabIndex = -1;

	panel.appendChild(header);
	panel.appendChild(body);
	panel.appendChild(resizeHandle);
	document.body.appendChild(panel);

	return {
		container: panel,
		header: header,
		body: body,
		backdrop: backdrop,
		modeButtons: modeButtons,
		closeButton: closeButton,
		resizeHandle: resizeHandle,
		contentResizeObserver: null,
		containerResizeObserver: null,
		mutationObserver: null,
		windowResizeHandler: null
	};
}

function createHeaderButton(id, label, iconHtml) {
	var button = document.createElement('button');
	button.type = 'button';
	button.id = id;
	button.className = 'base3-chatbot-header-action';
	button.setAttribute('aria-label', label);
	button.setAttribute('title', label);
	button.innerHTML = iconHtml;

	return button;
}

function getModeIcon(mode) {
	switch (mode) {
		case 'overlay':
			return '<svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="2" fill="none" stroke="currentColor" stroke-width="1.8"/><rect x="11" y="11" width="8" height="8" rx="1" fill="currentColor"/></svg>';

		case 'modal':
			return '<svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="2" fill="none" stroke="currentColor" stroke-width="1.8"/><rect x="7" y="7" width="10" height="10" rx="1" fill="none" stroke="currentColor" stroke-width="1.8"/></svg>';

		case 'docked':
			return '<svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="2" fill="none" stroke="currentColor" stroke-width="1.8"/><path d="M14 3v18" fill="none" stroke="currentColor" stroke-width="1.8"/><path d="M15 4h5v16h-5z" fill="currentColor"/></svg>';

		case 'cover':
			return '<svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="2" fill="none" stroke="currentColor" stroke-width="1.8"/><rect x="5.5" y="5.5" width="13" height="13" rx="1" fill="currentColor"/></svg>';

		case 'fullscreen':
			return '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M8 3H3v5M16 3h5v5M8 21H3v-5M16 21h5v-5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>';
	}

	return '';
}

function openPanel(panel, launcher, state, settings, strings, mode, requestFullscreen) {
	panel.container.classList.add('is-open');
	launcher.container.classList.add('panel-open');
	launcher.button.setAttribute('aria-expanded', 'true');
	panel.container.setAttribute('aria-hidden', 'false');
	panel.closeButton.focus({ preventScroll: true });
	setMode(panel, state, settings, strings, mode, requestFullscreen);
}

function closePanel(panel, launcher, state, settings) {
	panel.container.classList.remove('is-open');
	panel.backdrop.classList.remove('is-visible');
	launcher.container.classList.remove('panel-open');
	launcher.button.setAttribute('aria-expanded', 'false');
	panel.container.setAttribute('aria-hidden', 'true');
	launcher.button.focus({ preventScroll: true });
	removeDockedShell(panel);
	clearStoredOpenMode(settings);

	exitPanelFullscreen(panel);
}

function setMode(panel, state, settings, strings, mode, requestFullscreen) {
	if (!isModeEnabled(settings, mode)) {
		mode = getFirstEnabledMode(settings, '');
	}

	if (mode === '') {
		return;
	}

	var previousMode = state.mode;
	if (mode === 'fullscreen' && previousMode !== '' && previousMode !== 'fullscreen') {
		state.fullscreenReturnMode = previousMode;
	}

	if (previousMode === 'fullscreen' && mode !== 'fullscreen') {
		exitPanelFullscreen(panel);
	}

	clearModeClasses(panel.container);
	clearExplicitPanelSize(panel.container);
	panel.container.classList.add('mode-' + mode);
	panel.container.setAttribute('data-mode', mode);
	panel.container.setAttribute('aria-modal', (mode === 'modal' || mode === 'cover' || mode === 'fullscreen') ? 'true' : 'false');
	state.mode = mode;

	var resizable = isResizableMode(settings, mode);
	panel.container.classList.toggle('is-resizable', resizable);
	panel.resizeHandle.tabIndex = resizable ? 0 : -1;
	if (resizable) {
		applyStoredSize(panel, state, settings, mode);
	}

	panel.backdrop.classList.toggle(
		'is-visible',
		panel.container.classList.contains('is-open') && mode === 'modal'
	);

	updateModeButtons(panel, mode);
	updateDockedShell(panel, state);
	persistLastMode(settings, mode);
	persistOpenMode(settings, mode, panel.container.classList.contains('is-open'));

	if (mode === 'overlay') {
		scheduleInitialPanelResize(panel, state, settings);
	}

	if (mode === 'fullscreen' && requestFullscreen) {
		enterFullscreen(panel, state, settings, strings);
	}
}

function exitPanelFullscreen(panel) {
	if (document.fullscreenElement !== panel.container || typeof document.exitFullscreen !== 'function') {
		return;
	}

	var result = document.exitFullscreen();
	if (result && typeof result.catch === 'function') {
		result.catch(function() {});
	}
}

function enterFullscreen(panel, state, settings, strings) {
	if (document.fullscreenElement === panel.container) {
		return;
	}

	if (typeof panel.container.requestFullscreen !== 'function') {
		return;
	}

	var result = panel.container.requestFullscreen();
	if (!result || typeof result.catch !== 'function') {
		return;
	}

	result.catch(function() {
		var returnMode = state.fullscreenReturnMode;
		if (returnMode !== '' && isModeEnabled(settings, returnMode)) {
			setMode(panel, state, settings, strings, returnMode, false);
		}
	});
}

function clearModeClasses(container) {
	['overlay', 'modal', 'docked', 'cover', 'fullscreen'].forEach(function(mode) {
		container.classList.remove('mode-' + mode);
	});
}

function updateModeButtons(panel, currentMode) {
	Object.keys(panel.modeButtons).forEach(function(mode) {
		panel.modeButtons[mode].hidden = mode === currentMode;
	});
}

function isModeEnabled(settings, mode) {
	return settings.modes[mode] === true;
}

function getInitialMode(settings) {
	if (settings.rememberLastMode) {
		var rememberedMode = storageGet(window.localStorage, getLastModeStorageKey(settings));
		if (rememberedMode !== null && isModeEnabled(settings, rememberedMode)) {
			return rememberedMode;
		}
	} else {
		storageRemove(window.localStorage, getLastModeStorageKey(settings));
	}

	return getFirstEnabledMode(settings, '');
}

function getFirstEnabledMode(settings, excludedMode) {
	var modes = ['overlay', 'modal', 'docked', 'cover', 'fullscreen'];
	for (var i = 0; i < modes.length; i++) {
		if (modes[i] !== excludedMode && isModeEnabled(settings, modes[i])) {
			return modes[i];
		}
	}

	return '';
}

function persistLastMode(settings, mode) {
	if (!settings.rememberLastMode) {
		return;
	}

	storageSet(window.localStorage, getLastModeStorageKey(settings), mode);
}

function persistOpenMode(settings, mode, open) {
	if (!open) {
		clearStoredOpenMode(settings);
		return;
	}

	if (mode === 'overlay' && settings.reopen.overlay) {
		storageSet(window.sessionStorage, getOpenModeStorageKey(settings), mode);
		return;
	}

	if (mode === 'docked' && settings.reopen.docked) {
		storageSet(window.sessionStorage, getOpenModeStorageKey(settings), mode);
		return;
	}

	clearStoredOpenMode(settings);
}

function clearStoredOpenMode(settings) {
	storageRemove(window.sessionStorage, getOpenModeStorageKey(settings));
}

function restoreOpenState(panel, launcher, state, settings, strings, ajaxUrl) {
	var mode = storageGet(window.sessionStorage, getOpenModeStorageKey(settings));
	if (mode === null) {
		return;
	}

	var mayReopen = (
		(mode === 'overlay' && settings.reopen.overlay)
		|| (mode === 'docked' && settings.reopen.docked)
	);

	if (!mayReopen || !isModeEnabled(settings, mode)) {
		clearStoredOpenMode(settings);
		return;
	}

	openPanel(panel, launcher, state, settings, strings, mode, false);
	loadChatbot(panel, state, settings, ajaxUrl, strings);
}

function getLastModeStorageKey(settings) {
	return settings.storagePrefix + ':last-mode';
}

function getOpenModeStorageKey(settings) {
	return settings.storagePrefix + ':open-mode';
}

function getSizeStorageKey(settings, mode) {
	return settings.storagePrefix + ':size:' + mode;
}

function isResizableMode(settings, mode) {
	return settings.resizable[mode] === true;
}

function clearExplicitPanelSize(container) {
	container.style.removeProperty('width');
	container.style.removeProperty('height');
}

function applyStoredSize(panel, state, settings, mode) {
	var raw = storageGet(window.localStorage, getSizeStorageKey(settings, mode));
	if (raw === null) {
		return;
	}

	var size;
	try {
		size = JSON.parse(raw);
	} catch (e) {
		storageRemove(window.localStorage, getSizeStorageKey(settings, mode));
		return;
	}

	if (!size || typeof size.width !== 'number' || size.width <= 0) {
		return;
	}

	panel.container.style.width = String(Math.round(size.width)) + 'px';

	if (mode !== 'docked' && typeof size.height === 'number' && size.height > 0) {
		panel.container.style.height = String(Math.round(size.height)) + 'px';
	}
}

function scheduleSizePersistence(panel, state, settings) {
	if (state.sizeSaveTimer) {
		window.clearTimeout(state.sizeSaveTimer);
	}

	state.sizeSaveTimer = window.setTimeout(function() {
		state.sizeSaveTimer = 0;
		saveCurrentSize(panel, state, settings);
	}, 120);
}

function saveCurrentSize(panel, state, settings) {
	if (!panel.container.classList.contains('is-open') || !isResizableMode(settings, state.mode)) {
		return;
	}

	var rect = panel.container.getBoundingClientRect();
	if (rect.width <= 0 || rect.height <= 0) {
		return;
	}

	var size = {
		width: Math.round(rect.width)
	};

	if (state.mode !== 'docked') {
		size.height = Math.round(rect.height);
	}

	storageSet(
		window.localStorage,
		getSizeStorageKey(settings, state.mode),
		JSON.stringify(size)
	);
}

function installManualResize(panel, state, settings) {
	panel.resizeHandle.addEventListener('keydown', function(event) {
		if (!['ArrowLeft', 'ArrowRight', 'ArrowUp', 'ArrowDown'].includes(event.key)) return;
		var mode = state.mode;
		if (!panel.container.classList.contains('is-open') || !isResizableMode(settings, mode)) return;
		if (mode === 'docked' && (event.key === 'ArrowUp' || event.key === 'ArrowDown')) return;
		event.preventDefault();
		var step = event.shiftKey ? 40 : 10;
		var rect = panel.container.getBoundingClientRect();
		var bounds = getResizeBounds(mode);
		if (event.key === 'ArrowLeft' || event.key === 'ArrowRight') {
			var width = rect.width + (event.key === 'ArrowRight' ? step : -step);
			panel.container.style.width = String(Math.round(clamp(width, bounds.minWidth, bounds.maxWidth))) + 'px';
			if (mode === 'docked') updateDockedShell(panel, state);
		} else {
			var height = rect.height + (event.key === 'ArrowDown' ? step : -step);
			panel.container.style.height = String(Math.round(clamp(height, bounds.minHeight, bounds.maxHeight))) + 'px';
		}
		saveCurrentSize(panel, state, settings);
	});
	panel.resizeHandle.addEventListener('pointerdown', function(event) {
		var mode = state.mode;
		if (!panel.container.classList.contains('is-open') || !isResizableMode(settings, mode)) {
			return;
		}

		event.preventDefault();

		var startX = event.clientX;
		var startY = event.clientY;
		var startRect = panel.container.getBoundingClientRect();

		function onPointerMove(moveEvent) {
			var width = startRect.width;
			var height = startRect.height;

			if (mode === 'overlay') {
				width += startX - moveEvent.clientX;
				height += startY - moveEvent.clientY;
			} else if (mode === 'modal') {
				width += (moveEvent.clientX - startX) * 2;
				height += (moveEvent.clientY - startY) * 2;
			} else if (mode === 'docked') {
				width += startX - moveEvent.clientX;
			}

			var bounds = getResizeBounds(mode);
			width = clamp(width, bounds.minWidth, bounds.maxWidth);
			panel.container.style.width = String(Math.round(width)) + 'px';

			if (mode !== 'docked') {
				height = clamp(height, bounds.minHeight, bounds.maxHeight);
				panel.container.style.height = String(Math.round(height)) + 'px';
			}

			if (mode === 'docked') {
				updateDockedShell(panel, state);
			}
		}

		function onPointerUp() {
			window.removeEventListener('pointermove', onPointerMove);
			window.removeEventListener('pointerup', onPointerUp);
			window.removeEventListener('pointercancel', onPointerUp);
			saveCurrentSize(panel, state, settings);
		}

		window.addEventListener('pointermove', onPointerMove);
		window.addEventListener('pointerup', onPointerUp);
		window.addEventListener('pointercancel', onPointerUp);
	});
}

function getResizeBounds(mode) {
	var mobile = window.innerWidth <= 767;

	if (mode === 'docked') {
		var dockMaxWidth = Math.max(1, window.innerWidth * (mobile ? 0.9 : 0.8));
		return {
			minWidth: Math.min(mobile ? 280 : 360, dockMaxWidth),
			maxWidth: dockMaxWidth,
			minHeight: window.innerHeight,
			maxHeight: window.innerHeight
		};
	}

	var padding = mode === 'modal' ? (mobile ? 24 : 48) : (mobile ? 24 : 40);
	var maxWidth = Math.max(1, window.innerWidth - padding);
	var maxHeight = Math.max(1, window.innerHeight - padding);

	return {
		minWidth: Math.min(420, maxWidth),
		maxWidth: maxWidth,
		minHeight: Math.min(350, maxHeight),
		maxHeight: maxHeight
	};
}

function installContainerResizeObserver(panel, state, settings) {
	if (typeof ResizeObserver === 'undefined') {
		return;
	}

	panel.containerResizeObserver = new ResizeObserver(function() {
		if (!panel.container.classList.contains('is-open')) {
			return;
		}

		if (state.mode === 'docked') {
			updateDockedShell(panel, state);
		}

		if (isResizableMode(settings, state.mode)) {
			scheduleSizePersistence(panel, state, settings);
		}
	});

	panel.containerResizeObserver.observe(panel.container);
}

function updateDockedShell(panel, state) {
	var docked = panel.container.classList.contains('is-open') && state.mode === 'docked';
	if (!docked) {
		removeDockedShell(panel);
		return;
	}

	var width = Math.ceil(panel.container.getBoundingClientRect().width || 0);
	if (width <= 0) {
		return;
	}

	document.body.classList.add('base3-chatbot-docked');
	document.body.style.setProperty('--base3-chatbot-dock-width', String(width) + 'px');
	applyDockTarget(panel, width);
}

function removeDockedShell(panel) {
	document.body.classList.remove('base3-chatbot-docked');
	document.body.style.removeProperty('--base3-chatbot-dock-width');
	restoreDockTarget(panel);
}

function applyDockTarget(panel, width) {
	if (!panel.dockTarget) return;
	var target = document.querySelector(panel.dockTarget);
	if (!target) return;
	if (!panel.originalDockStyles) {
		panel.originalDockStyles = {
			target: target,
			width: target.style.width,
			marginRight: target.style.marginRight
		};
	}
	target.style.width = 'calc(100% - ' + width + 'px)';
	target.style.marginRight = width + 'px';
	if (panel.dockHeaderTarget && window.innerWidth >= 992) {
		var header = document.querySelector(panel.dockHeaderTarget);
		if (header) {
			if (!panel.originalHeaderStyles) {
				panel.originalHeaderStyles = {
					target: header, left: header.style.left,
					right: header.style.right, width: header.style.width
				};
			}
			header.style.left = '0';
			header.style.right = width + 'px';
			header.style.width = 'auto';
		}
	}

}

function restoreDockTarget(panel) {
	if (panel.originalDockStyles) {
		var original = panel.originalDockStyles;
		original.target.style.width = original.width;
		original.target.style.marginRight = original.marginRight;
		panel.originalDockStyles = null;
	}
	if (panel.originalHeaderStyles) {
		var header = panel.originalHeaderStyles;
		header.target.style.left = header.left;
		header.target.style.right = header.right;
		header.target.style.width = header.width;
		panel.originalHeaderStyles = null;
	}
}

function loadChatbot(panel, state, settings, ajaxUrl, strings) {
	if (state.loaded || state.loading) {
		scheduleInitialPanelResize(panel, state, settings);
		return;
	}

	state.loading = true;
	panel.body.textContent = strings.loading;
	scheduleInitialPanelResize(panel, state, settings);

	fetch(ajaxUrl, { credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest' } })
		.then(function(response) {
			if (!response.ok) throw new Error('HTTP ' + response.status);
			return response.text();
		})
		.then(function(html) { return insertMarkupWithScripts(panel.body, html); })
		.then(function() {
			state.loaded = true;
			bindContentResizeObserver(panel, state, settings);
		})
		.catch(function(error) {
			showError(panel.body, strings.loadError, String(error.message || error));
		})
		.finally(function() {
			state.loading = false;
			scheduleInitialPanelResize(panel, state, settings);
		});
}

// Template responses may initialize their own components with classic or ES-module scripts.
// innerHTML does not execute them, so reinsert actual script nodes in document order.
async function insertMarkupWithScripts(target, html) {
	var template = document.createElement('template');
	template.innerHTML = html;
	var scripts = Array.from(template.content.querySelectorAll('script'));
	scripts.forEach(function(script) { script.remove(); });
	target.replaceChildren(template.content);

	for (var i = 0; i < scripts.length; i++) {
		var original = scripts[i];
		var script = document.createElement('script');
		Array.from(original.attributes).forEach(function(attribute) {
			script.setAttribute(attribute.name, attribute.value);
		});
		if (!script.src) script.textContent = original.textContent;
		var isExternal = script.hasAttribute('src');
		if (isExternal) script.async = false;
		var completed = isExternal ? new Promise(function(resolve, reject) {
			script.addEventListener('load', resolve, { once: true });
			script.addEventListener('error', function() { reject(new Error('Chatbot script could not load')); }, { once: true });
		}) : null;
		target.appendChild(script);
		if (completed) await completed;
	}
}

function showError(container, message, detail) {
	container.innerHTML = '';

	var error = document.createElement('div');
	error.className = 'base3-chatbot-error';
	error.appendChild(document.createTextNode(message));

	if (detail !== '') {
		error.appendChild(document.createElement('br'));
		error.appendChild(document.createTextNode(detail));
	}

	container.appendChild(error);
}

function installAutoResize(panel, state, settings) {
	if (panel.container.getAttribute('data-autoresize-installed') === '1') {
		return;
	}

	panel.container.setAttribute('data-autoresize-installed', '1');

	if (typeof ResizeObserver !== 'undefined') {
		panel.contentResizeObserver = new ResizeObserver(function() {
			schedulePanelResize(panel, state, settings);
		});
	}

	if (typeof MutationObserver !== 'undefined') {
		panel.mutationObserver = new MutationObserver(function() {
			bindContentResizeObserver(panel, state, settings);
			schedulePanelResize(panel, state, settings);
		});

		panel.mutationObserver.observe(panel.body, {
			childList: true,
			subtree: true,
			attributes: true,
			characterData: true
		});
	}

	panel.windowResizeHandler = function() {
		schedulePanelResize(panel, state, settings);
		if (state.mode === 'docked') {
			updateDockedShell(panel, state);
		}
	};

	window.addEventListener('resize', panel.windowResizeHandler);

	bindContentResizeObserver(panel, state, settings);
}

function bindContentResizeObserver(panel, state, settings) {
	if (!panel.contentResizeObserver) {
		return;
	}

	panel.contentResizeObserver.disconnect();
	panel.contentResizeObserver.observe(panel.body);

	Array.prototype.forEach.call(panel.body.children, function(child) {
		panel.contentResizeObserver.observe(child);
	});

	schedulePanelResize(panel, state, settings);
}

function scheduleInitialPanelResize(panel, state, settings) {
	schedulePanelResize(panel, state, settings);

	window.setTimeout(function() {
		schedulePanelResize(panel, state, settings);
	}, 80);

	window.setTimeout(function() {
		schedulePanelResize(panel, state, settings);
	}, 200);

	window.setTimeout(function() {
		schedulePanelResize(panel, state, settings);
	}, 500);

	window.setTimeout(function() {
		schedulePanelResize(panel, state, settings);
	}, 1000);
}

function schedulePanelResize(panel, state, settings) {
	if (!panel.container.classList.contains('is-open')) {
		return;
	}

	if (state.mode !== 'overlay' || isResizableMode(settings, 'overlay')) {
		return;
	}

	if (state.resizeFrame) {
		return;
	}

	state.resizeFrame = window.requestAnimationFrame(function() {
		state.resizeFrame = 0;
		updatePanelSize(panel, state, settings);
	});
}

function updatePanelSize(panel, state, settings) {
	if (state.mode !== 'overlay' || isResizableMode(settings, 'overlay')) {
		return;
	}

	var width = measurePanelWidth(panel);
	panel.container.style.setProperty('--base3-chatbot-panel-width', String(width) + 'px');
}

function measurePanelWidth(panel) {
	var minWidth = 420;
	var defaultWidth = 720;
	var viewportPadding = window.innerWidth <= 767 ? 24 : 40;
	var availableWidth = Math.max(0, window.innerWidth - viewportPadding);
	var effectiveMinWidth = Math.min(minWidth, availableWidth);
	var maxWidth = Math.max(effectiveMinWidth, availableWidth);
	var headerWidth = Math.ceil(panel.header.scrollWidth || 0);
	var contentWidth = Math.ceil(Math.max(panel.body.scrollWidth + 2, headerWidth));

	return clamp(Math.max(contentWidth, defaultWidth), effectiveMinWidth, maxWidth);
}

function storageGet(storage, key) {
	try {
		return storage.getItem(key);
	} catch (e) {
		return null;
	}
}

function storageSet(storage, key, value) {
	try {
		storage.setItem(key, value);
	} catch (e) {
	}
}

function storageRemove(storage, key) {
	try {
		storage.removeItem(key);
	} catch (e) {
	}
}

function clamp(value, min, max) {
	return Math.min(Math.max(value, min), max);
}


if (typeof document !== 'undefined') {
	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', initializeGlobalChatbotLaunchers, { once: true });
	} else {
		initializeGlobalChatbotLaunchers();
	}
}
