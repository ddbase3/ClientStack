<?php
	$id = (string)$this->_['id'];
	$areas = (array)$this->_['scopes'];
	$labels = (array)$this->_['labels'];
	$statuses = ['all', 'failed', 'warning', 'error', 'passed', 'not_applicable', 'pending'];
	$esc = static fn(mixed $value): string => htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
	$json = static fn(mixed $value): string => json_encode($value, JSON_THROW_ON_ERROR | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE);
?>
<section id="<?php echo $esc($id); ?>" class="base3-diagnostic-suite" aria-label="<?php echo $esc($labels['areas_title']); ?>">
	<header class="base3-diagnostic-header">
		<h2><?php echo $esc($labels['areas_title']); ?></h2>
		<p><?php echo $esc($labels['areas_intro']); ?></p>
	</header>

	<?php if($areas === []): ?>
		<p class="base3-diagnostic-empty"><?php echo $esc($labels['no_scopes']); ?></p>
	<?php else: ?>
		<div class="base3-diagnostic-area-list" role="group" aria-label="<?php echo $esc($labels['areas_title']); ?>">
			<?php foreach($areas as $area): ?>
				<div class="base3-diagnostic-area" data-diagnostic-area="<?php echo $esc($area['name']); ?>">
					<div class="base3-diagnostic-area-row">
						<div class="base3-diagnostic-area-info">
							<strong><?php echo $esc($area['title']); ?></strong>
							<span><?php echo $esc($area['description']); ?></span>
						</div>
						<span class="base3-diagnostic-area-count"><?php echo count($area['checks']); ?> <?php echo $esc($labels['checks_label']); ?></span>
						<button type="button" class="btn btn-default base3-diagnostic-launch" data-diagnostic-launch="<?php echo $esc($area['name']); ?>" aria-controls="<?php echo $esc($id); ?>-results" aria-expanded="false">
							<?php echo $esc($labels['run_area']); ?>
						</button>
					</div>
				</div>
			<?php endforeach; ?>

			<section id="<?php echo $esc($id); ?>-results" class="base3-diagnostic-results" data-diagnostic-results aria-label="<?php echo $esc($labels['results_title']); ?>" hidden>
				<div class="base3-diagnostic-result-bar">
					<div class="base3-diagnostic-summary" role="group" aria-label="<?php echo $esc($labels['summary']); ?>">
						<span class="base3-diagnostic-stat"><?php echo $esc($labels['total']); ?> <strong data-diagnostic-total>0</strong></span>
						<?php foreach(['passed', 'failed', 'warning', 'error', 'not_applicable', 'pending'] as $status): ?>
							<span class="base3-diagnostic-stat is-<?php echo $esc($status); ?>" data-diagnostic-summary-entry="<?php echo $esc($status); ?>" hidden><?php echo $esc($labels[$status]); ?> <strong data-diagnostic-summary="<?php echo $esc($status); ?>">0</strong></span>
						<?php endforeach; ?>
					</div>
					<div class="base3-diagnostic-tools">
						<label class="base3-diagnostic-sr-only" for="<?php echo $esc($id); ?>-search"><?php echo $esc($labels['search']); ?></label>
						<input id="<?php echo $esc($id); ?>-search" type="search" data-diagnostic-search placeholder="<?php echo $esc($labels['search_placeholder']); ?>" autocomplete="off">
						<label class="base3-diagnostic-sr-only" for="<?php echo $esc($id); ?>-filter"><?php echo $esc($labels['filter_label']); ?></label>
						<select id="<?php echo $esc($id); ?>-filter" data-diagnostic-filter>
							<?php foreach($statuses as $status): ?>
								<option value="<?php echo $esc($status); ?>"><?php echo $esc($labels[$status]); ?></option>
							<?php endforeach; ?>
						</select>
						<button type="button" class="btn btn-default" data-diagnostic-export disabled><?php echo $esc($labels['export_json']); ?></button>
					</div>
				</div>
				<div class="base3-diagnostic-progress-line" data-diagnostic-progress-line hidden>
					<label for="<?php echo $esc($id); ?>-progress" data-diagnostic-progress-label><?php echo $esc($labels['checking']); ?></label>
					<progress id="<?php echo $esc($id); ?>-progress" data-diagnostic-progress max="1" value="0" aria-label="<?php echo $esc($labels['progress']); ?>"></progress>
					<span data-diagnostic-progress-text>0 / 0</span>
				</div>
				<p class="base3-diagnostic-announcement" role="status" aria-live="polite" data-diagnostic-announcement></p>
				<p class="base3-diagnostic-empty" data-diagnostic-no-checks hidden><?php echo $esc($labels['no_checks']); ?></p>
				<p class="base3-diagnostic-empty" data-diagnostic-no-results hidden><?php echo $esc($labels['no_results']); ?></p>
				<ul class="base3-diagnostic-list" data-diagnostic-list></ul>
			</section>
		</div>

		<template data-diagnostic-check-template>
			<li class="base3-diagnostic-check" data-diagnostic-check="" data-status="pending">
				<button type="button" class="base3-diagnostic-check-heading" data-diagnostic-toggle aria-expanded="false">
					<strong data-diagnostic-title></strong>
					<span class="base3-diagnostic-status" data-diagnostic-badge></span>
					<span class="base3-diagnostic-chevron" aria-hidden="true">&#8964;</span>
				</button>
				<div class="base3-diagnostic-measures" data-diagnostic-measures hidden></div>
				<div class="base3-diagnostic-check-extra" data-diagnostic-extra hidden>
					<p class="base3-diagnostic-check-message" data-diagnostic-message></p>
					<p class="base3-diagnostic-check-description" data-diagnostic-description></p>
					<div class="base3-diagnostic-remediation" data-diagnostic-remediation hidden>
						<strong><?php echo $esc($labels['remediation']); ?>:</strong>
						<span data-diagnostic-remediation-text></span>
					</div>
				</div>
			</li>
		</template>
	<?php endif; ?>
</section>
<style>
#<?php echo $id; ?> { --diagnostic-border: #dfe4ea; --diagnostic-muted: #65717d; --diagnostic-accent: #30649a; font: inherit; color: inherit; width: 100%; }
#<?php echo $id; ?> *, #<?php echo $id; ?> *::before, #<?php echo $id; ?> *::after { box-sizing: border-box; }
#<?php echo $id; ?> [hidden] { display: none !important; }
#<?php echo $id; ?> .base3-diagnostic-sr-only { position: absolute; width: 1px; height: 1px; padding: 0; margin: -1px; overflow: hidden; clip: rect(0,0,0,0); white-space: nowrap; border: 0; }
#<?php echo $id; ?> .base3-diagnostic-header { margin: 0 0 10px; }
#<?php echo $id; ?> .base3-diagnostic-header h2 { margin: 0 0 3px; font-size: 1.12rem; font-weight: 600; }
#<?php echo $id; ?> .base3-diagnostic-header p { margin: 0; font-size: .84rem; color: var(--diagnostic-muted); }
#<?php echo $id; ?> .base3-diagnostic-area-list { border: 1px solid var(--diagnostic-border); border-radius: 5px; background: #fff; overflow: hidden; }
#<?php echo $id; ?> .base3-diagnostic-area { border-bottom: 1px solid var(--diagnostic-border); }
#<?php echo $id; ?> .base3-diagnostic-area:last-of-type { border-bottom: 0; }
#<?php echo $id; ?> .base3-diagnostic-area-row { display: flex; align-items: center; gap: 12px; padding: 8px 11px; }
#<?php echo $id; ?> .base3-diagnostic-area.is-active > .base3-diagnostic-area-row { background: #f6f9fc; }
#<?php echo $id; ?> .base3-diagnostic-area-info { flex: 1 1 auto; min-width: 0; }
#<?php echo $id; ?> .base3-diagnostic-area-info strong { display: block; font-size: .9rem; font-weight: 600; }
#<?php echo $id; ?> .base3-diagnostic-area-info span { display: block; margin-top: 1px; font-size: .79rem; color: var(--diagnostic-muted); }
#<?php echo $id; ?> .base3-diagnostic-area-count { font-size: .78rem; color: var(--diagnostic-muted); white-space: nowrap; }
#<?php echo $id; ?> button, #<?php echo $id; ?> input, #<?php echo $id; ?> select { font: inherit; }
#<?php echo $id; ?> button { cursor: pointer; }
#<?php echo $id; ?> button:focus-visible, #<?php echo $id; ?> input:focus-visible, #<?php echo $id; ?> select:focus-visible { outline: 2px solid var(--diagnostic-accent); outline-offset: 2px; }
#<?php echo $id; ?> button:disabled { opacity: .6; cursor: not-allowed; }
#<?php echo $id; ?> .base3-diagnostic-launch { padding: 4px 10px; min-height: 28px; font-size: .81rem; white-space: nowrap; }
#<?php echo $id; ?> .base3-diagnostic-results { padding: 9px 11px 10px; border-top: 1px solid var(--diagnostic-border); background: #fbfcfd; }
#<?php echo $id; ?> .base3-diagnostic-result-bar { display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 8px; margin-bottom: 6px; }
#<?php echo $id; ?> .base3-diagnostic-summary { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; font-size: .79rem; color: var(--diagnostic-muted); }
#<?php echo $id; ?> .base3-diagnostic-stat { white-space: nowrap; font-variant-numeric: tabular-nums; }
#<?php echo $id; ?> .base3-diagnostic-stat strong { font-weight: 600; color: #354150; }
#<?php echo $id; ?> .is-passed strong { color: #23744b; }
#<?php echo $id; ?> .is-failed strong, #<?php echo $id; ?> .is-error strong { color: #af3630; }
#<?php echo $id; ?> .is-warning strong { color: #9a690d; }
#<?php echo $id; ?> .base3-diagnostic-tools { display: flex; align-items: center; justify-content: flex-end; gap: 5px; flex-wrap: wrap; }
#<?php echo $id; ?> .base3-diagnostic-tools input, #<?php echo $id; ?> .base3-diagnostic-tools select { border: 1px solid #cbd2d9; border-radius: 4px; background: #fff; color: inherit; padding: 4px 7px; min-height: 28px; font-size: .79rem; }
#<?php echo $id; ?> .base3-diagnostic-tools input { width: 172px; max-width: 100%; }
#<?php echo $id; ?> .base3-diagnostic-tools select { max-width: 155px; }
#<?php echo $id; ?> .base3-diagnostic-tools button { padding: 4px 8px; min-height: 28px; font-size: .79rem; border-radius: 4px; }
#<?php echo $id; ?> .base3-diagnostic-progress-line { display: flex; align-items: center; gap: 7px; margin: 4px 0 6px; font-size: .77rem; color: var(--diagnostic-muted); }
#<?php echo $id; ?> .base3-diagnostic-progress-line progress { flex: 1; max-width: 210px; height: 5px; accent-color: var(--diagnostic-accent); }
#<?php echo $id; ?> .base3-diagnostic-progress-line span { font-variant-numeric: tabular-nums; }
#<?php echo $id; ?> .base3-diagnostic-announcement { position: absolute; width: 1px; height: 1px; margin: -1px; overflow: hidden; clip: rect(0,0,0,0); white-space: nowrap; }
#<?php echo $id; ?> .base3-diagnostic-announcement.is-visible { position: static; width: auto; height: auto; margin: 2px 0 6px; overflow: visible; clip: auto; white-space: normal; font-size: .78rem; color: var(--diagnostic-muted); }
#<?php echo $id; ?> .base3-diagnostic-list { padding: 0; margin: 0; list-style: none; background: #fff; border: 1px solid var(--diagnostic-border); border-radius: 4px; }
#<?php echo $id; ?> .base3-diagnostic-check { border-bottom: 1px solid #e9edf1; }
#<?php echo $id; ?> .base3-diagnostic-check:last-child { border-bottom: 0; }
#<?php echo $id; ?> .base3-diagnostic-check-heading { display: flex; align-items: center; gap: 8px; width: 100%; padding: 6px 9px 3px; text-align: left; border: 0; background: transparent; color: inherit; font-size: .82rem; }
#<?php echo $id; ?> .base3-diagnostic-check-heading::before { content: ''; display: inline-block; flex: 0 0 7px; height: 7px; background: #a6afb9; border-radius: 50%; }
#<?php echo $id; ?> [data-status="passed"] .base3-diagnostic-check-heading::before { background: #2d8a5a; }
#<?php echo $id; ?> [data-status="warning"] .base3-diagnostic-check-heading::before { background: #cf9527; }
#<?php echo $id; ?> [data-status="failed"] .base3-diagnostic-check-heading::before, #<?php echo $id; ?> [data-status="error"] .base3-diagnostic-check-heading::before { background: #c64a45; }
#<?php echo $id; ?> .base3-diagnostic-check-heading strong { flex: 1 1 auto; min-width: 0; font-size: .83rem; font-weight: 600; }
#<?php echo $id; ?> .base3-diagnostic-status { flex: 0 0 auto; font-size: .76rem; color: var(--diagnostic-muted); }
#<?php echo $id; ?> [data-status="passed"] .base3-diagnostic-status { color: #23744b; }
#<?php echo $id; ?> [data-status="warning"] .base3-diagnostic-status { color: #98690d; }
#<?php echo $id; ?> [data-status="failed"] .base3-diagnostic-status, #<?php echo $id; ?> [data-status="error"] .base3-diagnostic-status { color: #af3630; }
#<?php echo $id; ?> .base3-diagnostic-chevron { flex: 0 0 auto; margin-left: 3px; line-height: 1; color: #8b96a1; transform: rotate(0deg); }
#<?php echo $id; ?> [aria-expanded="true"] .base3-diagnostic-chevron { transform: rotate(180deg); }
#<?php echo $id; ?> .base3-diagnostic-measures { display: flex; flex-wrap: wrap; gap: 3px 13px; padding: 0 10px 6px 24px; font-size: .76rem; color: var(--diagnostic-muted); }
#<?php echo $id; ?> .base3-diagnostic-measure { display: inline-flex; align-items: baseline; gap: 4px; margin: 0; }
#<?php echo $id; ?> .base3-diagnostic-measure dt { font-weight: 400; }
#<?php echo $id; ?> .base3-diagnostic-measure dd { margin: 0; color: #4b5563; font-weight: 600; font-variant-numeric: tabular-nums; }
#<?php echo $id; ?> .base3-diagnostic-check-extra { margin: 0 10px 7px 24px; border-left: 2px solid #d7dfe7; padding: 1px 0 0 8px; font-size: .79rem; line-height: 1.35; }
#<?php echo $id; ?> [data-status="warning"] .base3-diagnostic-check-extra { border-left-color: #ddb65d; }
#<?php echo $id; ?> [data-status="failed"] .base3-diagnostic-check-extra, #<?php echo $id; ?> [data-status="error"] .base3-diagnostic-check-extra { border-left-color: #de8b86; }
#<?php echo $id; ?> .base3-diagnostic-check-extra p { margin: 2px 0; }
#<?php echo $id; ?> .base3-diagnostic-check-message { font-weight: 500; }
#<?php echo $id; ?> .base3-diagnostic-check-description { color: var(--diagnostic-muted); font-size: .76rem; }
#<?php echo $id; ?> .base3-diagnostic-remediation { padding-top: 3px; }
#<?php echo $id; ?> .base3-diagnostic-remediation strong { font-weight: 600; }
#<?php echo $id; ?> .base3-diagnostic-empty { padding: 8px; margin: 6px 0; font-size: .8rem; color: var(--diagnostic-muted); }
@media(max-width:650px) { #<?php echo $id; ?> .base3-diagnostic-area-row { flex-wrap: wrap; gap: 6px; } #<?php echo $id; ?> .base3-diagnostic-area-info { flex-basis: 100%; } #<?php echo $id; ?> .base3-diagnostic-result-bar { align-items: flex-start; } #<?php echo $id; ?> .base3-diagnostic-tools { justify-content: flex-start; } }
@media(prefers-reduced-motion:reduce) { #<?php echo $id; ?> * { transition: none !important; } }
</style>
<script>
(() => {
	"use strict";
	const root = document.getElementById(<?php echo $json($id); ?>);
	if(!root) return;
	const areas = <?php echo $json($areas); ?>;
	const initialScope = <?php echo $json((string)$this->_['initialScope']); ?>;
	const csrf = <?php echo $json((string)$this->_['csrf']); ?>;
	const labels = <?php echo $json($labels); ?>;
	const results = root.querySelector("[data-diagnostic-results]");
	if(!results) return;
	const list = root.querySelector("[data-diagnostic-list]");
	const template = root.querySelector("[data-diagnostic-check-template]");
	const exportButton = root.querySelector("[data-diagnostic-export]");
	const search = root.querySelector("[data-diagnostic-search]");
	const filterSelect = root.querySelector("[data-diagnostic-filter]");
	const progressLine = root.querySelector("[data-diagnostic-progress-line]");
	const progress = root.querySelector("[data-diagnostic-progress]");
	const progressText = root.querySelector("[data-diagnostic-progress-text]");
	const progressLabel = root.querySelector("[data-diagnostic-progress-label]");
	const announcement = root.querySelector("[data-diagnostic-announcement]");
	let cards = [];
	let selectedArea = null;
	let filter = "all";
	let generation = 0;
	let activeController = null;
	let completedAt = null;

	function refreshOverview() {
		const totals = {passed: 0, failed: 0, warning: 0, error: 0, not_applicable: 0, pending: 0};
		for(const card of cards) {
			const status = card.dataset.status;
			if(Object.prototype.hasOwnProperty.call(totals, status)) totals[status]++;
		}
		root.querySelector("[data-diagnostic-total]").textContent = String(cards.length);
		for(const status of Object.keys(totals)) {
			root.querySelector('[data-diagnostic-summary="' + status + '"]').textContent = String(totals[status]);
			root.querySelector('[data-diagnostic-summary-entry="' + status + '"]').hidden = totals[status] === 0;
		}
		const complete = cards.length - totals.pending;
		progress.value = complete;
		progressText.textContent = complete + " / " + cards.length;
		applyFilter();
	}

	function applyFilter() {
		const query = search.value.trim().toLocaleLowerCase();
		let visible = 0;
		for(const card of cards) {
			const matchesStatus = filter === "all" || card.dataset.status === filter;
			const matchesText = !query || card.textContent.toLocaleLowerCase().includes(query);
			card.hidden = !(matchesStatus && matchesText);
			if(!card.hidden) visible++;
		}
		root.querySelector("[data-diagnostic-no-results]").hidden = cards.length === 0 || visible > 0;
	}

	function setExpanded(card, expanded) {
		card.querySelector("[data-diagnostic-extra]").hidden = !expanded;
		card.querySelector("[data-diagnostic-toggle]").setAttribute("aria-expanded", String(expanded));
	}

	function setResult(card, result) {
		const allowed = ["passed", "failed", "warning", "error", "not_applicable"];
		const status = result && allowed.includes(result.status) ? result.status : "error";
		card.dataset.status = status;
		card.querySelector("[data-diagnostic-badge]").textContent = labels[status] || labels.unknown;
		card.querySelector("[data-diagnostic-message]").textContent =
			result && typeof result.message === "string" ? result.message : labels.request_failed;
		const remediation = card.querySelector("[data-diagnostic-remediation]");
		const advice = result && typeof result.remediation === "string" ? result.remediation : "";
		remediation.hidden = advice === "";
		remediation.querySelector("[data-diagnostic-remediation-text]").textContent = advice;
		const measures = card.querySelector("[data-diagnostic-measures]");
		measures.replaceChildren();
		for(const detail of result && Array.isArray(result.details) ? result.details : []) {
			if(!detail || typeof detail.label !== "string" || !["string", "number", "boolean"].includes(typeof detail.value)) continue;
			const dl = document.createElement("dl");
			dl.className = "base3-diagnostic-measure";
			const dt = document.createElement("dt");
			dt.textContent = detail.label;
			const dd = document.createElement("dd");
			dd.textContent = String(detail.value);
			dd.dataset.diagnosticValue = JSON.stringify(detail.value);
			dl.append(dt, dd);
			measures.appendChild(dl);
		}
		measures.hidden = measures.childElementCount === 0;
		setExpanded(card, status !== "passed");
	}

	function currentReport() {
		const counts = {total: cards.length};
		for(const status of ["passed", "failed", "warning", "error", "not_applicable", "pending"]) {
			counts[status] = cards.filter(card => card.dataset.status === status).length;
		}
		return {
			schemaVersion: 1,
			scope: {id: selectedArea.name, title: selectedArea.title, description: selectedArea.description},
			completedAt,
			summary: counts,
			checks: cards.map(card => ({
				id: card.dataset.diagnosticCheck,
				title: card.querySelector("[data-diagnostic-title]").textContent.trim(),
				description: card.querySelector("[data-diagnostic-description]").textContent.trim(),
				status: card.dataset.status,
				message: card.querySelector("[data-diagnostic-message]").textContent,
				remediation: card.querySelector("[data-diagnostic-remediation-text]").textContent,
				details: Array.from(card.querySelectorAll("[data-diagnostic-measures] dl"), item => ({
					label: item.querySelector("dt").textContent,
					value: JSON.parse(item.querySelector("dd").dataset.diagnosticValue)
				}))
			}))
		};
	}

	async function copyReport() {
		if(completedAt === null || selectedArea === null || !root.isConnected) return;
		try {
			if(!navigator.clipboard || typeof navigator.clipboard.writeText !== "function") {
				throw new Error("Clipboard API unavailable");
			}
			await navigator.clipboard.writeText(JSON.stringify(currentReport(), null, 2));
			announcement.textContent = labels.export_copied;
			announcement.classList.add("is-visible");
		} catch(error) {
			announcement.textContent = labels.export_failed;
			announcement.classList.add("is-visible");
		}
	}

	async function runAll() {
		if(!selectedArea) return;
		generation++;
		const current = generation;
		completedAt = null;
		exportButton.disabled = true;
		if(activeController) activeController.abort();
		activeController = null;
		for(const card of cards) {
			card.dataset.status = "pending";
			card.querySelector("[data-diagnostic-badge]").textContent = labels.pending;
			card.querySelector("[data-diagnostic-message]").textContent = "";
			card.querySelector("[data-diagnostic-remediation]").hidden = true;
			const measures = card.querySelector("[data-diagnostic-measures]");
			measures.replaceChildren();
			measures.hidden = true;
			setExpanded(card, false);
		}
		results.setAttribute("aria-busy", "true");
		progressLine.hidden = false;
		announcement.classList.remove("is-visible");
		progressLabel.textContent = labels.checking;
		announcement.textContent = labels.run_started;
		refreshOverview();
		for(const card of cards) {
			if(generation !== current || !root.isConnected) return;
			const controller = new AbortController();
			activeController = controller;
			let result;
			try {
				const body = new URLSearchParams({
					diagnostic_csrf: csrf,
					diagnostic_check: card.dataset.diagnosticCheck
				});
				const response = await fetch(selectedArea.endpoint, {
					method: "POST",
					credentials: "same-origin",
					cache: "no-store",
					headers: {"Content-Type": "application/x-www-form-urlencoded; charset=UTF-8", "Accept": "application/json"},
					body: body.toString(),
					signal: controller.signal
				});
				if(!response.ok) throw new Error("Check request failed");
				result = await response.json();
			} catch(error) {
				if(controller.signal.aborted || generation !== current) return;
				result = {status: "error", message: labels.request_failed, details: [], remediation: ""};
			}
			if(generation !== current || !root.isConnected) return;
			setResult(card, result);
			refreshOverview();
		}
		if(generation === current && root.isConnected) {
			activeController = null;
			results.setAttribute("aria-busy", "false");
			progressLine.hidden = true;
			completedAt = new Date().toISOString();
			exportButton.disabled = false;
			progressLabel.textContent = labels.finished;
			announcement.textContent = labels.run_finished;
		}
	}

	function runArea(name) {
		const area = areas.find(item => item.name === name);
		if(!area) return;
		generation++;
		if(activeController) activeController.abort();
		activeController = null;
		selectedArea = area;
		completedAt = null;
		exportButton.disabled = true;
		const areaRow = Array.from(root.querySelectorAll("[data-diagnostic-area]"))
			.find(row => row.dataset.diagnosticArea === name);
		if(!areaRow) return;
		areaRow.appendChild(results);
		results.hidden = false;
		root.querySelector("[data-diagnostic-no-checks]").hidden = area.checks.length !== 0;
		for(const button of root.querySelectorAll("[data-diagnostic-launch]")) {
			const active = button.dataset.diagnosticLaunch === name;
			button.setAttribute("aria-expanded", String(active));
			button.closest("[data-diagnostic-area]").classList.toggle("is-active", active);
			button.textContent = active ? labels.run_again : labels.run_area;
		}
		filter = "all";
		search.value = "";
		filterSelect.value = "all";
		list.replaceChildren();
		cards = [];
		for(const [index, check] of area.checks.entries()) {
			const card = template.content.firstElementChild.cloneNode(true);
			card.dataset.diagnosticCheck = check.name;
			card.querySelector("[data-diagnostic-title]").textContent = check.title;
			card.querySelector("[data-diagnostic-description]").textContent = check.description;
			card.querySelector("[data-diagnostic-badge]").textContent = labels.pending;
			const extraId = root.id + "-check-" + index;
			card.querySelector("[data-diagnostic-extra]").id = extraId;
			const toggle = card.querySelector("[data-diagnostic-toggle]");
			toggle.setAttribute("aria-controls", extraId);
			toggle.addEventListener("click", () => setExpanded(card, toggle.getAttribute("aria-expanded") !== "true"));
			list.appendChild(card);
			cards.push(card);
		}
		progress.max = Math.max(1, cards.length);
		refreshOverview();
		runAll();
	}

	for(const button of root.querySelectorAll("[data-diagnostic-launch]")) {
		button.addEventListener("click", () => runArea(button.dataset.diagnosticLaunch));
	}
	filterSelect.addEventListener("change", () => {
		filter = filterSelect.value;
		applyFilter();
	});
	search.addEventListener("input", applyFilter);
	exportButton.addEventListener("click", copyReport);
	const panel = root.closest("[data-base3-tab-panel]");
	if(panel) panel.addEventListener("base3:tab-control:unload", () => {
		generation++;
		completedAt = null;
		exportButton.disabled = true;
		if(activeController) activeController.abort();
	}, {once: true});
	if(initialScope) runArea(initialScope);
})();
</script>
