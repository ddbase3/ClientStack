<?php
	$controlId = (string) $this->_['controlId'];
	$tabs = (array) $this->_['tabs'];
	$activeTab = (string) $this->_['activeTab'];
	$activeDisplay = (string) $this->_['activeDisplay'];
	$activeUrl = (string) $this->_['activeUrl'];
	$content = (string) $this->_['content'];
	$emptyMessage = (string) $this->_['emptyMessage'];
?>
<div
	id="<?php echo htmlspecialchars($controlId); ?>"
	class="base3-tab-control"
	data-base3-tab-control
	aria-busy="false"
>
	<?php if(count($tabs) === 0): ?>
		<div class="base3-tab-control-empty">
			<?php echo htmlspecialchars($emptyMessage); ?>
		</div>
	<?php else: ?>
		<div class="base3-tab-control-tabbar base3-tab-control-primary-bar" data-base3-tab-overflow-bar>
			<nav class="base3-tab-control-primary" aria-label="Primary navigation" data-base3-tab-strip>
				<?php foreach($tabs as $tab): ?>
					<?php $firstDisplay = (array) $tab['displays'][0]; ?>
					<button
						type="button"
						class="base3-tab-control-primary-button"
						data-base3-tab-link
						data-base3-tab-target="<?php echo htmlspecialchars((string) $firstDisplay['name']); ?>"
						data-base3-tab-parent="<?php echo htmlspecialchars((string) $tab['name']); ?>"
						data-base3-tab-url="<?php echo htmlspecialchars((string) $firstDisplay['url']); ?>"
						aria-selected="<?php echo (string) $tab['name'] === $activeTab ? 'true' : 'false'; ?>"
					>
						<?php echo htmlspecialchars((string) $tab['label']); ?>
					</button>
				<?php endforeach; ?>
			</nav>
			<details class="base3-tab-control-overflow" data-base3-tab-overflow hidden>
				<summary class="base3-tab-control-overflow-toggle" aria-label="More tabs" title="More tabs">…</summary>
				<div class="base3-tab-control-overflow-menu" data-base3-tab-overflow-menu></div>
			</details>
		</div>

		<div class="base3-tab-control-secondary-wrap">
			<?php foreach($tabs as $tab): ?>
				<div
					class="base3-tab-control-tabbar base3-tab-control-secondary-bar"
					data-base3-tab-group="<?php echo htmlspecialchars((string) $tab['name']); ?>"
					data-base3-tab-overflow-bar
					<?php echo (string) $tab['name'] === $activeTab ? '' : ' hidden'; ?>
				>
					<nav class="base3-tab-control-secondary" aria-label="Secondary navigation" data-base3-tab-strip>
						<?php foreach((array) $tab['displays'] as $display): ?>
							<button
								type="button"
								class="base3-tab-control-secondary-button"
								data-base3-tab-link
								data-base3-tab-target="<?php echo htmlspecialchars((string) $display['name']); ?>"
								data-base3-tab-parent="<?php echo htmlspecialchars((string) $tab['name']); ?>"
								data-base3-tab-url="<?php echo htmlspecialchars((string) $display['url']); ?>"
								aria-selected="<?php echo (string) $display['name'] === $activeDisplay ? 'true' : 'false'; ?>"
							>
								<?php echo htmlspecialchars((string) $display['label']); ?>
							</button>
						<?php endforeach; ?>
					</nav>
					<details class="base3-tab-control-overflow" data-base3-tab-overflow hidden>
						<summary class="base3-tab-control-overflow-toggle" aria-label="More tabs" title="More tabs">…</summary>
						<div class="base3-tab-control-overflow-menu" data-base3-tab-overflow-menu></div>
					</details>
				</div>
			<?php endforeach; ?>
		</div>

		<div class="base3-tab-control-stage">
			<div class="base3-tab-control-loading" data-base3-tab-loading hidden>
				<span class="base3-tab-control-spinner" aria-hidden="true"></span>
				<span>Loading…</span>
			</div>

			<div class="base3-tab-control-message" data-base3-tab-message hidden></div>

			<div class="base3-tab-control-content" data-base3-tab-content>
				<section
					class="base3-tab-control-panel"
					data-base3-tab-panel
					data-base3-tab-target="<?php echo htmlspecialchars($activeDisplay); ?>"
					data-base3-tab-url="<?php echo htmlspecialchars($activeUrl); ?>"
					data-base3-tab-scripts-loaded="1"
				>
					<?php echo $content; ?>
				</section>
			</div>
		</div>
	<?php endif; ?>
</div>

<style>
#<?php echo $controlId; ?>.base3-tab-control {
	--base3-tab-border: #d6d6d6;
	--base3-tab-surface: #ffffff;
	--base3-tab-muted-surface: #f4f5f6;
	--base3-tab-hover-surface: #e9ecef;
	--base3-tab-active-surface: #ffffff;
	--base3-tab-text: #333333;
	--base3-tab-muted-text: #666666;
	--base3-tab-accent: #4d7188;
	position: relative;
	max-width: 100%;
	color: var(--base3-tab-text);
}

#<?php echo $controlId; ?> .base3-tab-control-tabbar {
	position: relative;
	display: flex;
	align-items: stretch;
	min-width: 0;
}

#<?php echo $controlId; ?> .base3-tab-control-primary-bar {
	padding: 0 2px;
	border-bottom: 1px solid var(--base3-tab-border);
}

#<?php echo $controlId; ?> .base3-tab-control-primary,
#<?php echo $controlId; ?> .base3-tab-control-secondary {
	flex: 1 1 auto;
	display: flex;
	align-items: stretch;
	gap: 4px;
	min-width: 0;
	overflow: hidden;
}

#<?php echo $controlId; ?> .base3-tab-control-primary-button,
#<?php echo $controlId; ?> .base3-tab-control-secondary-button {
	flex: 0 0 auto;
}

#<?php echo $controlId; ?> .base3-tab-control-secondary-wrap {
	padding: 8px;
	border-bottom: 1px solid var(--base3-tab-border);
	background: var(--base3-tab-muted-surface);
}

#<?php echo $controlId; ?> .base3-tab-control-overflow {
	position: relative;
	flex: 0 0 auto;
	align-self: stretch;
}

#<?php echo $controlId; ?> .base3-tab-control-overflow[hidden] {
	display: none !important;
}

#<?php echo $controlId; ?> .base3-tab-control-overflow-toggle {
	display: flex;
	align-items: center;
	justify-content: center;
	min-width: 2.35rem;
	height: 100%;
	box-sizing: border-box;
	padding: 0 0.55rem;
	border: 0;
	border-radius: 4px;
	background: transparent;
	color: var(--base3-tab-muted-text);
	cursor: pointer;
	font: inherit;
	font-size: 1.15rem;
	font-weight: 700;
	line-height: 1;
	list-style: none;
}

#<?php echo $controlId; ?> .base3-tab-control-overflow-toggle::-webkit-details-marker {
	display: none;
}

#<?php echo $controlId; ?> .base3-tab-control-overflow-toggle:hover,
#<?php echo $controlId; ?> .base3-tab-control-overflow-toggle:focus-visible,
#<?php echo $controlId; ?> .base3-tab-control-overflow[open] > .base3-tab-control-overflow-toggle,
#<?php echo $controlId; ?> .base3-tab-control-overflow.has-active > .base3-tab-control-overflow-toggle {
	background: var(--base3-tab-hover-surface);
	color: var(--base3-tab-accent);
}

#<?php echo $controlId; ?> .base3-tab-control-overflow-menu {
	position: absolute;
	top: calc(100% + 4px);
	right: 0;
	z-index: 100;
	display: grid;
	gap: 2px;
	min-width: 13rem;
	max-width: min(26rem, calc(100vw - 2rem));
	padding: 4px;
	border: 1px solid var(--base3-tab-border);
	border-radius: 5px;
	background: var(--base3-tab-surface);
	box-shadow: 0 6px 18px rgba(0, 0, 0, 0.14);
}

#<?php echo $controlId; ?> .base3-tab-control-overflow-menu .base3-tab-control-primary-button,
#<?php echo $controlId; ?> .base3-tab-control-overflow-menu .base3-tab-control-secondary-button {
	display: block;
	width: 100%;
	margin: 0;
	padding: 7px 10px;
	border: 0;
	border-radius: 3px;
	background: transparent;
	color: var(--base3-tab-text);
	text-align: left;
}

#<?php echo $controlId; ?> .base3-tab-control-overflow-menu .base3-tab-control-primary-button:hover,
#<?php echo $controlId; ?> .base3-tab-control-overflow-menu .base3-tab-control-primary-button:focus-visible,
#<?php echo $controlId; ?> .base3-tab-control-overflow-menu .base3-tab-control-secondary-button:hover,
#<?php echo $controlId; ?> .base3-tab-control-overflow-menu .base3-tab-control-secondary-button:focus-visible {
	background: var(--base3-tab-hover-surface);
}

#<?php echo $controlId; ?> .base3-tab-control-overflow-menu .base3-tab-control-primary-button[aria-selected="true"],
#<?php echo $controlId; ?> .base3-tab-control-overflow-menu .base3-tab-control-secondary-button[aria-selected="true"] {
	background: var(--base3-tab-hover-surface);
	color: var(--base3-tab-accent);
}

#<?php echo $controlId; ?> .base3-tab-control-primary-button,
#<?php echo $controlId; ?> .base3-tab-control-secondary-button {
	appearance: none;
	border: 0;
	background: transparent;
	color: var(--base3-tab-text);
	font: inherit;
	white-space: nowrap;
	cursor: pointer;
	transition: background 120ms ease, border-color 120ms ease, color 120ms ease;
}

#<?php echo $controlId; ?> .base3-tab-control-primary-button {
	padding: 11px 14px 10px;
	border: 1px solid transparent;
	border-bottom: 0;
	border-radius: 4px 4px 0 0;
	margin-bottom: -1px;
	font-weight: 600;
}

#<?php echo $controlId; ?> .base3-tab-control-primary-button:hover,
#<?php echo $controlId; ?> .base3-tab-control-primary-button:focus-visible {
	background: var(--base3-tab-hover-surface);
}

#<?php echo $controlId; ?> .base3-tab-control-primary-button[aria-selected="true"] {
	background: var(--base3-tab-active-surface);
	border-color: var(--base3-tab-border);
	color: var(--base3-tab-accent);
}

#<?php echo $controlId; ?> .base3-tab-control-secondary-button {
	padding: 7px 11px;
	border-radius: 4px;
	color: var(--base3-tab-muted-text);
}

#<?php echo $controlId; ?> .base3-tab-control-secondary-button:hover,
#<?php echo $controlId; ?> .base3-tab-control-secondary-button:focus-visible {
	background: var(--base3-tab-hover-surface);
	color: var(--base3-tab-text);
}

#<?php echo $controlId; ?> .base3-tab-control-secondary-button[aria-selected="true"] {
	background: var(--base3-tab-accent);
	color: #ffffff;
}

#<?php echo $controlId; ?> .base3-tab-control-stage {
	position: relative;
	min-height: 120px;
	padding-top: 14px;
}

#<?php echo $controlId; ?> .base3-tab-control-panel[hidden],
#<?php echo $controlId; ?> [data-base3-tab-group][hidden] {
	display: none !important;
}

#<?php echo $controlId; ?> .base3-tab-control-loading {
	position: absolute;
	top: 14px;
	right: 0;
	z-index: 2;
	display: inline-flex;
	align-items: center;
	gap: 7px;
	padding: 7px 10px;
	border: 1px solid var(--base3-tab-border);
	border-radius: 4px;
	background: rgba(255, 255, 255, 0.94);
	color: var(--base3-tab-muted-text);
	font-size: 12px;
	box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
}

#<?php echo $controlId; ?> .base3-tab-control-loading[hidden] {
	display: none !important;
}

#<?php echo $controlId; ?> .base3-tab-control-spinner {
	width: 14px;
	height: 14px;
	border: 2px solid #d7d7d7;
	border-top-color: var(--base3-tab-accent);
	border-radius: 999px;
	animation: base3-tab-control-spin 700ms linear infinite;
}

#<?php echo $controlId; ?> .base3-tab-control-message {
	margin-bottom: 12px;
	padding: 9px 11px;
	border: 1px solid #d9a8a8;
	border-radius: 4px;
	background: #fff6f6;
	color: #8a2f2f;
	font-size: 13px;
}

#<?php echo $controlId; ?> .base3-tab-control-message[hidden] {
	display: none !important;
}

#<?php echo $controlId; ?> .base3-tab-control-empty {
	padding: 18px;
	border: 1px solid var(--base3-tab-border);
	border-radius: 4px;
	background: var(--base3-tab-muted-surface);
	color: var(--base3-tab-muted-text);
}

#<?php echo $controlId; ?>[aria-busy="true"] .base3-tab-control-content {
	opacity: 0.72;
}

@keyframes base3-tab-control-spin {
	to { transform: rotate(360deg); }
}
</style>

<script>
(function() {
	const root = document.getElementById(<?php echo json_encode($controlId); ?>);
	if(!root || root.dataset.base3TabInitialized === "1") {
		return;
	}

	root.dataset.base3TabInitialized = "1";

	const content = root.querySelector("[data-base3-tab-content]");
	const loading = root.querySelector("[data-base3-tab-loading]");
	const message = root.querySelector("[data-base3-tab-message]");
	let requestController = null;
	let requestNumber = 0;
	let overflowFrame = 0;
	let overflowObserver = null;

	function getOverflowBars() {
		return Array.from(root.querySelectorAll("[data-base3-tab-overflow-bar]"));
	}

	function closeOverflowMenus() {
		for(const overflow of root.querySelectorAll("[data-base3-tab-overflow]")) {
			overflow.open = false;
		}
	}

	function updateOverflowActive(bar) {
		const overflow = bar.querySelector("[data-base3-tab-overflow]");
		const menu = bar.querySelector("[data-base3-tab-overflow-menu]");
		if(!overflow || !menu) {
			return;
		}
		overflow.classList.toggle("has-active", Boolean(menu.querySelector('[aria-selected="true"]')));
	}

	function layoutOverflowBar(bar) {
		if(bar.hidden) {
			return;
		}

		const strip = bar.querySelector("[data-base3-tab-strip]");
		const overflow = bar.querySelector("[data-base3-tab-overflow]");
		const menu = bar.querySelector("[data-base3-tab-overflow-menu]");
		if(!strip || !overflow || !menu) {
			return;
		}

		while(menu.firstElementChild) {
			strip.appendChild(menu.firstElementChild);
		}
		overflow.hidden = true;
		overflow.open = false;
		overflow.classList.remove("has-active");

		if(strip.scrollWidth <= strip.clientWidth + 1) {
			return;
		}

		overflow.hidden = false;
		while(strip.lastElementChild && strip.scrollWidth > strip.clientWidth + 1) {
			menu.insertBefore(strip.lastElementChild, menu.firstElementChild);
		}
		updateOverflowActive(bar);
	}

	function layoutOverflow() {
		for(const bar of getOverflowBars()) {
			layoutOverflowBar(bar);
		}
	}

	function scheduleOverflowLayout() {
		if(overflowFrame) {
			return;
		}
		overflowFrame = window.requestAnimationFrame(() => {
			overflowFrame = 0;
			layoutOverflow();
		});
	}

	function installOverflowLayout() {
		if(typeof ResizeObserver !== "undefined") {
			overflowObserver = new ResizeObserver(scheduleOverflowLayout);
			overflowObserver.observe(root);
			for(const bar of getOverflowBars()) {
				overflowObserver.observe(bar);
			}
		}

		window.addEventListener("resize", scheduleOverflowLayout);
		if(document.fonts && document.fonts.ready) {
			document.fonts.ready.then(scheduleOverflowLayout);
		}
		scheduleOverflowLayout();
	}

	function getLinks() {
		return Array.from(root.querySelectorAll("[data-base3-tab-link]"));
	}

	function getConfiguredLink(target) {
		return getLinks().find((link) =>
			link.dataset.base3TabTarget === target &&
			link.dataset.base3TabParent
		) || null;
	}

	function getParent(target) {
		const link = getConfiguredLink(target);
		return link ? String(link.dataset.base3TabParent || "") : "";
	}

	function getUrl(link) {
		return String(
			link.dataset.base3TabUrl ||
			link.getAttribute("href") ||
			""
		);
	}

	function getPanel(target) {
		return Array.from(content.querySelectorAll("[data-base3-tab-panel]"))
			.find((panel) => panel.dataset.base3TabTarget === target) || null;
	}

	function cancelPendingRequest() {
		if(!requestController) {
			return;
		}

		requestController.abort();
		requestController = null;
		requestNumber++;
		setLoading(false);
	}

	function setLoading(state) {
		root.setAttribute("aria-busy", state ? "true" : "false");
		if(loading) {
			loading.hidden = !state;
		}
	}

	function setMessage(text) {
		if(!message) {
			return;
		}

		const value = String(text || "");
		message.textContent = value;
		message.hidden = value === "";
	}

	function setActive(target) {
		const parent = getParent(target);

		for(const link of getLinks()) {
			if(!link.dataset.base3TabParent) {
				continue;
			}

			const isPrimary = link.classList.contains("base3-tab-control-primary-button");
			const selected = isPrimary
				? link.dataset.base3TabParent === parent
				: link.dataset.base3TabTarget === target;

			link.setAttribute("aria-selected", selected ? "true" : "false");
		}

		for(const group of root.querySelectorAll("[data-base3-tab-group]")) {
			group.hidden = group.dataset.base3TabGroup !== parent;
		}

		for(const panel of content.querySelectorAll("[data-base3-tab-panel]")) {
			panel.hidden = panel.dataset.base3TabTarget !== target;
		}
		scheduleOverflowLayout();
	}

	function copyScriptAttributes(source, target) {
		for(const attribute of source.attributes) {
			target.setAttribute(attribute.name, attribute.value);
		}
	}

	async function executeScripts(panel, scripts) {
		for(const source of scripts) {
			const script = document.createElement("script");
			copyScriptAttributes(source, script);

			if(source.src) {
				await new Promise((resolve, reject) => {
					script.async = false;
					script.addEventListener("load", resolve, { once: true });
					script.addEventListener("error", reject, { once: true });
					panel.appendChild(script);
				});
				continue;
			}

			script.textContent = source.textContent || "";
			panel.appendChild(script);
		}
	}

	async function setPanelContent(panel, html, execute) {
		const template = document.createElement("template");
		template.innerHTML = String(html || "");

		const scripts = Array.from(template.content.querySelectorAll("script"));
		for(const script of scripts) {
			script.remove();
		}

		panel.replaceChildren(template.content.cloneNode(true));

		if(execute) {
			await executeScripts(panel, scripts);
			panel.dataset.base3TabScriptsLoaded = "1";
		}
	}

	function createPanel(target) {
		const panel = document.createElement("section");
		panel.className = "base3-tab-control-panel";
		panel.dataset.base3TabPanel = "";
		panel.dataset.base3TabTarget = target;
		panel.dataset.base3TabScriptsLoaded = "0";
		panel.hidden = true;
		content.appendChild(panel);
		return panel;
	}

	async function loadTarget(target, url) {
		if(!target || !url || !getConfiguredLink(target)) {
			return;
		}

		const existingPanel = getPanel(target);
		if(existingPanel) {
			cancelPendingRequest();
			setMessage("");
			setActive(target);
			existingPanel.dispatchEvent(new CustomEvent("base3:tab-control:activated", {
				bubbles: true,
				detail: { target, url }
			}));
			return;
		}

		cancelPendingRequest();

		const controller = new AbortController();
		requestController = controller;
		const currentRequest = ++requestNumber;

		setMessage("");
		setLoading(true);

		try {
			const response = await fetch(url, {
				method: "GET",
				credentials: "same-origin",
				headers: {
					"Accept": "text/html",
					"X-Requested-With": "XMLHttpRequest"
				},
				signal: controller.signal
			});

			if(!response.ok) {
				throw new Error("HTTP " + response.status);
			}

			const html = await response.text();
			if(currentRequest !== requestNumber) {
				return;
			}

			const panel = createPanel(target);

			panel.dispatchEvent(new CustomEvent("base3:tab-control:before-content", {
				bubbles: true,
				detail: { target, url }
			}));

			await setPanelContent(panel, html, true);

			if(currentRequest !== requestNumber) {
				return;
			}

			panel.dataset.base3TabUrl = url;
			setActive(target);

			panel.dispatchEvent(new CustomEvent("base3:tab-control:loaded", {
				bubbles: true,
				detail: { target, url }
			}));
		}
		catch(error) {
			if(error && error.name === "AbortError") {
				return;
			}

			setMessage("The selected tab could not be loaded.");
			root.dispatchEvent(new CustomEvent("base3:tab-control:error", {
				bubbles: true,
				detail: { target, url, error }
			}));
		}
		finally {
			if(currentRequest === requestNumber) {
				if(requestController === controller) {
					requestController = null;
				}
				setLoading(false);
			}
		}
	}

	root.addEventListener("click", (event) => {
		if(event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
			return;
		}

		const eventTarget = event.target instanceof Element ? event.target : null;
		const link = eventTarget ? eventTarget.closest("[data-base3-tab-link]") : null;
		if(!link || !root.contains(link)) {
			return;
		}

		if(link.matches("a[target='_blank'], a[download]")) {
			return;
		}

		const target = String(link.dataset.base3TabTarget || "");
		const url = getUrl(link);

		if(!target || !url || !getConfiguredLink(target)) {
			return;
		}

		event.preventDefault();
		closeOverflowMenus();
		loadTarget(target, url);
	});

	installOverflowLayout();
})();
</script>
