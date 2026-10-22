<?php
	$id = (string)$this->_['id'];
	$esc = static fn(mixed $value): string => htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
	$checks = (array)$this->_['checks'];
	$labels = (array)$this->_['labels'];
	$statuses = ['all', 'failed', 'warning', 'error', 'passed', 'not_applicable', 'pending'];
?>
<section id="<?php echo $esc($id); ?>" class="base3-diagnostic-suite" data-diagnostic-scope="<?php echo $esc($this->_['scope']); ?>" aria-label="<?php echo $esc($this->_['title']); ?>">
	<header class="base3-diagnostic-header">
		<div>
			<h2><?php echo $esc($this->_['title']); ?></h2>
			<p><?php echo $esc($this->_['description']); ?></p>
		</div>
		<div class="base3-diagnostic-actions">
			<button type="button" class="base3-diagnostic-export" data-diagnostic-export disabled>
				<span aria-hidden="true">&#10697;</span> <?php echo $esc($labels['export_json']); ?>
			</button>
			<button type="button" class="base3-diagnostic-rerun" data-diagnostic-rerun>
				<span aria-hidden="true">&#8635;</span> <?php echo $esc($labels['run_again']); ?>
			</button>
		</div>
	</header>

	<div class="base3-diagnostic-summary" aria-label="<?php echo $esc($labels['summary']); ?>">
		<div class="base3-diagnostic-stat is-total">
			<span class="base3-diagnostic-stat-label"><?php echo $esc($labels['total']); ?></span>
			<strong data-diagnostic-total><?php echo count($checks); ?></strong>
		</div>
		<?php foreach(['passed', 'failed', 'warning', 'error', 'not_applicable', 'pending'] as $status): ?>
			<div class="base3-diagnostic-stat is-<?php echo $esc($status); ?>">
				<span class="base3-diagnostic-stat-label"><?php echo $esc($labels[$status]); ?></span>
				<strong data-diagnostic-summary="<?php echo $esc($status); ?>"><?php echo $status === 'pending' ? count($checks) : 0; ?></strong>
			</div>
		<?php endforeach; ?>
	</div>

	<div class="base3-diagnostic-progress-area">
		<div class="base3-diagnostic-progress-line">
			<strong data-diagnostic-progress-label><?php echo $esc($labels['checking']); ?></strong>
			<span data-diagnostic-progress-text>0 / <?php echo count($checks); ?></span>
		</div>
		<progress data-diagnostic-progress max="<?php echo max(1, count($checks)); ?>" value="0" aria-label="<?php echo $esc($labels['progress']); ?>"></progress>
		<p class="base3-diagnostic-announcement" role="status" aria-live="polite" data-diagnostic-announcement>
			<?php echo $esc($labels['run_started']); ?>
		</p>
	</div>

	<div class="base3-diagnostic-toolbar">
		<div class="base3-diagnostic-search">
			<label for="<?php echo $esc($id); ?>-search"><?php echo $esc($labels['search']); ?></label>
			<input id="<?php echo $esc($id); ?>-search" type="search" data-diagnostic-search placeholder="<?php echo $esc($labels['search_placeholder']); ?>" autocomplete="off">
		</div>
		<div class="base3-diagnostic-filters" role="group" aria-label="<?php echo $esc($labels['filter_label']); ?>">
			<?php foreach($statuses as $status): ?>
				<button type="button" data-diagnostic-filter="<?php echo $esc($status); ?>" aria-pressed="<?php echo $status === 'all' ? 'true' : 'false'; ?>">
					<?php echo $esc($labels[$status]); ?> <span data-diagnostic-count="<?php echo $esc($status); ?>"><?php echo $status === 'all' || $status === 'pending' ? count($checks) : 0; ?></span>
				</button>
			<?php endforeach; ?>
		</div>
	</div>

	<?php if(count($checks) === 0): ?>
		<p class="base3-diagnostic-empty"><?php echo $esc($labels['no_checks']); ?></p>
	<?php else: ?>
		<p class="base3-diagnostic-empty" data-diagnostic-no-results hidden><?php echo $esc($labels['no_results']); ?></p>
		<ul class="base3-diagnostic-list" data-diagnostic-list>
			<?php foreach($checks as $check): ?>
				<li class="base3-diagnostic-check" data-diagnostic-check="<?php echo $esc($check['name']); ?>" data-status="pending">
					<div class="base3-diagnostic-check-main">
						<div class="base3-diagnostic-check-heading">
							<h3><?php echo $esc($check['title']); ?></h3>
							<span class="base3-diagnostic-badge" data-diagnostic-badge><?php echo $esc($labels['pending']); ?></span>
						</div>
						<p class="base3-diagnostic-check-description" data-diagnostic-description><?php echo $esc($check['description']); ?></p>
						<p class="base3-diagnostic-check-message" data-diagnostic-message></p>
					</div>
					<div class="base3-diagnostic-measures" data-diagnostic-measures hidden></div>
					<div class="base3-diagnostic-remediation" data-diagnostic-remediation hidden>
						<strong><?php echo $esc($labels['remediation']); ?></strong>
						<p data-diagnostic-remediation-text></p>
					</div>
				</li>
			<?php endforeach; ?>
		</ul>
	<?php endif; ?>
</section>
<style>
#<?php echo $id; ?> {
	--diagnostic-border: #dce3e9;
	--diagnostic-muted: #64748b;
	--diagnostic-bg: #ffffff;
	--diagnostic-ink: #1e293b;
	--diagnostic-accent: #27669b;
	font-family: inherit;
	color: var(--diagnostic-ink);
	max-width: 100%;
}
#<?php echo $id; ?> *, #<?php echo $id; ?> *::before, #<?php echo $id; ?> *::after { box-sizing: border-box; }
#<?php echo $id; ?> [hidden] { display: none !important; }
#<?php echo $id; ?> .base3-diagnostic-header { display: flex; justify-content: space-between; align-items: flex-start; gap: 18px; margin-bottom: 20px; }
#<?php echo $id; ?> .base3-diagnostic-header h2 { margin: 0 0 5px; font-size: 1.5rem; line-height: 1.35; font-weight: 700; color: var(--diagnostic-ink); }
#<?php echo $id; ?> .base3-diagnostic-header p { margin: 0; color: var(--diagnostic-muted); line-height: 1.5; }
#<?php echo $id; ?> .base3-diagnostic-actions { display: flex; gap: 8px; align-items: center; flex-wrap: wrap; }
#<?php echo $id; ?> .base3-diagnostic-export { font: inherit; font-weight: 600; border: 1px solid var(--diagnostic-accent); border-radius: 7px; padding: 9px 14px; background: #fff; color: var(--diagnostic-accent); cursor: pointer; }
#<?php echo $id; ?> .base3-diagnostic-export:hover:enabled { background: #e9f2fb; }
#<?php echo $id; ?> .base3-diagnostic-export:disabled { opacity: .5; cursor: not-allowed; }
#<?php echo $id; ?> .base3-diagnostic-rerun { flex-shrink: 0; font: inherit; font-weight: 600; border: 1px solid var(--diagnostic-accent); border-radius: 7px; padding: 9px 14px; background: var(--diagnostic-accent); color: #fff; cursor: pointer; }
#<?php echo $id; ?> button:focus-visible, #<?php echo $id; ?> input:focus-visible { outline: 3px solid #3b82f6; outline-offset: 3px; }
#<?php echo $id; ?> .base3-diagnostic-rerun:hover { filter: brightness(.92); }
#<?php echo $id; ?> .base3-diagnostic-rerun:disabled { opacity: .6; cursor: wait; }
#<?php echo $id; ?> .base3-diagnostic-summary { display: grid; grid-template-columns: repeat(7, minmax(0, 1fr)); gap: 10px; margin-bottom: 17px; }
#<?php echo $id; ?> .base3-diagnostic-stat { border: 1px solid var(--diagnostic-border); border-radius: 9px; background: var(--diagnostic-bg); padding: 14px; min-width: 0; }
#<?php echo $id; ?> .base3-diagnostic-stat-label { display: block; color: var(--diagnostic-muted); font-size: .82rem; margin-bottom: 8px; }
#<?php echo $id; ?> .base3-diagnostic-stat strong { font-size: 1.7rem; line-height: 1; font-weight: 750; font-variant-numeric: tabular-nums; }
#<?php echo $id; ?> .is-passed strong { color: #166534; }
#<?php echo $id; ?> .is-failed strong, #<?php echo $id; ?> .is-error strong { color: #b42318; }
#<?php echo $id; ?> .is-warning strong { color: #965c04; }
#<?php echo $id; ?> .is-not_applicable strong { color: #475569; }
#<?php echo $id; ?> .base3-diagnostic-progress-area { background: #f8fafc; border: 1px solid var(--diagnostic-border); border-radius: 9px; padding: 13px 16px; margin-bottom: 18px; }
#<?php echo $id; ?> .base3-diagnostic-progress-line { display: flex; justify-content: space-between; gap: 12px; font-size: .88rem; margin-bottom: 8px; }
#<?php echo $id; ?> .base3-diagnostic-progress-line span { color: var(--diagnostic-muted); font-variant-numeric: tabular-nums; }
#<?php echo $id; ?> progress { display: block; width: 100%; height: 7px; appearance: auto; accent-color: var(--diagnostic-accent); }
#<?php echo $id; ?> .base3-diagnostic-announcement { margin: 8px 0 0; color: var(--diagnostic-muted); font-size: .85rem; }
#<?php echo $id; ?> .base3-diagnostic-toolbar { display: flex; align-items: end; justify-content: space-between; gap: 16px; flex-wrap: wrap; margin: 0 0 16px; }
#<?php echo $id; ?> .base3-diagnostic-search { flex: 1 1 270px; min-width: min(100%, 220px); }
#<?php echo $id; ?> .base3-diagnostic-search label { display: block; margin-bottom: 6px; font-size: .85rem; font-weight: 600; }
#<?php echo $id; ?> .base3-diagnostic-search input { display: block; font: inherit; padding: 9px 12px; border: 1px solid #a7b6c3; border-radius: 7px; min-height: 40px; width: 100%; max-width: 380px; background: #fff; color: #1e293b; }
#<?php echo $id; ?> .base3-diagnostic-filters { display: flex; flex-wrap: wrap; gap: 6px; }
#<?php echo $id; ?> .base3-diagnostic-filters button { font: inherit; font-size: .82rem; cursor: pointer; border-radius: 6px; background: #fff; border: 1px solid var(--diagnostic-border); color: #334155; padding: 8px 10px; }
#<?php echo $id; ?> .base3-diagnostic-filters button[aria-pressed="true"] { border-color: var(--diagnostic-accent); background: #e9f2fb; color: #174e79; font-weight: 700; }
#<?php echo $id; ?> .base3-diagnostic-filters button span { opacity: .8; padding-left: 3px; font-variant-numeric: tabular-nums; }
#<?php echo $id; ?> .base3-diagnostic-list { display: grid; padding: 0; margin: 0; gap: 12px; list-style: none; }
#<?php echo $id; ?> .base3-diagnostic-check { border: 1px solid var(--diagnostic-border); border-left: 4px solid #b1bdc9; border-radius: 9px; padding: 16px 20px; background: #fff; box-shadow: 0 2px 4px rgba(16,24,40,.035); }
#<?php echo $id; ?> .base3-diagnostic-check[data-status="passed"] { border-left-color: #218647; }
#<?php echo $id; ?> .base3-diagnostic-check[data-status="failed"], #<?php echo $id; ?> .base3-diagnostic-check[data-status="error"] { border-left-color: #c4312a; }
#<?php echo $id; ?> .base3-diagnostic-check[data-status="warning"] { border-left-color: #bb801b; }
#<?php echo $id; ?> .base3-diagnostic-check[data-status="not_applicable"] { border-left-color: #6b7280; }
#<?php echo $id; ?> .base3-diagnostic-check-heading { display: flex; align-items: flex-start; justify-content: space-between; gap: 12px; }
#<?php echo $id; ?> .base3-diagnostic-check h3 { margin: 0; font-size: 1.06rem; line-height: 1.4; font-weight: 700; }
#<?php echo $id; ?> .base3-diagnostic-badge { flex-shrink: 0; border-radius: 999px; padding: 5px 11px; background: #eef2f7; color: #334155; font-size: .78rem; font-weight: 700; }
#<?php echo $id; ?> [data-status="passed"] .base3-diagnostic-badge { background: #e5f5ea; color: #14532d; }
#<?php echo $id; ?> [data-status="failed"] .base3-diagnostic-badge, #<?php echo $id; ?> [data-status="error"] .base3-diagnostic-badge { background: #feeceb; color: #991b1b; }
#<?php echo $id; ?> [data-status="warning"] .base3-diagnostic-badge { background: #fff2d1; color: #854d0e; }
#<?php echo $id; ?> .base3-diagnostic-check-description { margin: 8px 0 0; color: var(--diagnostic-muted); line-height: 1.55; }
#<?php echo $id; ?> .base3-diagnostic-check-message { margin: 11px 0 0; font-weight: 600; line-height: 1.5; }
#<?php echo $id; ?> .base3-diagnostic-check-message:empty { display: none; }
#<?php echo $id; ?> .base3-diagnostic-measures { display: flex; flex-wrap: wrap; gap: 10px; margin-top: 14px; }
#<?php echo $id; ?> .base3-diagnostic-measure { padding: 10px 14px; min-width: 130px; border: 1px solid var(--diagnostic-border); border-radius: 7px; background: #f8fafc; }
#<?php echo $id; ?> .base3-diagnostic-measure dt { margin-bottom: 3px; color: var(--diagnostic-muted); font-size: .78rem; }
#<?php echo $id; ?> .base3-diagnostic-measure dd { margin: 0; font-size: 1.25rem; font-weight: 700; font-variant-numeric: tabular-nums; }
#<?php echo $id; ?> .base3-diagnostic-remediation { margin-top: 16px; border-top: 1px solid var(--diagnostic-border); padding-top: 13px; }
#<?php echo $id; ?> .base3-diagnostic-remediation strong { font-size: .87rem; }
#<?php echo $id; ?> .base3-diagnostic-remediation p { margin: 6px 0 0; color: #475569; line-height: 1.55; }
#<?php echo $id; ?> .base3-diagnostic-empty { border: 1px dashed var(--diagnostic-border); border-radius: 9px; padding: 22px; background: #f8fafc; color: var(--diagnostic-muted); }
@media(max-width:1000px) { #<?php echo $id; ?> .base3-diagnostic-summary { grid-template-columns: repeat(3, minmax(0, 1fr)); } }
@media(max-width:580px) {
	#<?php echo $id; ?> .base3-diagnostic-header { flex-direction: column; align-items: stretch; }
	#<?php echo $id; ?> .base3-diagnostic-summary { grid-template-columns: repeat(2, minmax(0, 1fr)); }
	#<?php echo $id; ?> .base3-diagnostic-check-heading { flex-direction: column; }
	#<?php echo $id; ?> .base3-diagnostic-filters { width: 100%; }
}
@media(prefers-reduced-motion:reduce) { #<?php echo $id; ?> * { scroll-behavior: auto !important; transition: none !important; } }
</style>
<script>
(function() {
	"use strict";
	const root = document.getElementById(<?php echo json_encode($id, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>);
	if(!root) return;
	const endpoint = <?php echo json_encode((string)$this->_['endpoint'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
	const csrf = <?php echo json_encode((string)$this->_['csrf'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
	const labels = <?php echo json_encode($labels, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE); ?>;
	const cards = Array.from(root.querySelectorAll("[data-diagnostic-check]"));
	const rerun = root.querySelector("[data-diagnostic-rerun]");
	const exportButton = root.querySelector("[data-diagnostic-export]");
	const scopeTitle = <?php echo json_encode((string)$this->_['title'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
	const scopeDescription = <?php echo json_encode((string)$this->_['description'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
	const search = root.querySelector("[data-diagnostic-search]");
	const progress = root.querySelector("[data-diagnostic-progress]");
	const progressText = root.querySelector("[data-diagnostic-progress-text]");
	const progressLabel = root.querySelector("[data-diagnostic-progress-label]");
	const announcement = root.querySelector("[data-diagnostic-announcement]");
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
		for(const status of Object.keys(totals)) {
			const summary = root.querySelector('[data-diagnostic-summary="' + status + '"]');
			if(summary) summary.textContent = String(totals[status]);
			const count = root.querySelector('[data-diagnostic-count="' + status + '"]');
			if(count) count.textContent = String(totals[status]);
		}
		const complete = cards.length - totals.pending;
		const all = root.querySelector('[data-diagnostic-count="all"]');
		if(all) all.textContent = String(cards.length);
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
		const noResults = root.querySelector("[data-diagnostic-no-results]");
		if(noResults) noResults.hidden = visible > 0;
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
	}

	function currentReport() {
		const counts = {total: cards.length};
		for(const status of ["passed", "failed", "warning", "error", "not_applicable", "pending"]) {
			counts[status] = cards.filter(card => card.dataset.status === status).length;
		}
		return {
			schemaVersion: 1,
			scope: {id: root.dataset.diagnosticScope, title: scopeTitle, description: scopeDescription},
			completedAt,
			summary: counts,
			checks: cards.map(card => ({
				id: card.dataset.diagnosticCheck,
				title: card.querySelector("h3").textContent.trim(),
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
		if(completedAt === null || !root.isConnected) return;
		try {
			if(!navigator.clipboard || typeof navigator.clipboard.writeText !== "function") {
				throw new Error("Clipboard API unavailable");
			}
			await navigator.clipboard.writeText(JSON.stringify(currentReport(), null, 2));
			announcement.textContent = labels.export_copied;
		} catch(error) {
			announcement.textContent = labels.export_failed;
		}
	}

	async function runAll() {
		generation++;
		completedAt = null;
		exportButton.disabled = true;
		const current = generation;
		if(activeController) activeController.abort();
		for(const card of cards) {
			card.dataset.status = "pending";
			card.querySelector("[data-diagnostic-badge]").textContent = labels.pending;
			card.querySelector("[data-diagnostic-message]").textContent = "";
			card.querySelector("[data-diagnostic-remediation]").hidden = true;
			const measures = card.querySelector("[data-diagnostic-measures]");
			measures.replaceChildren();
			measures.hidden = true;
		}
		rerun.disabled = true;
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
				const response = await fetch(endpoint, {
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
			rerun.disabled = false;
			completedAt = new Date().toISOString();
			exportButton.disabled = false;
			progressLabel.textContent = labels.finished;
			announcement.textContent = labels.run_finished;
		}
	}

	for(const button of root.querySelectorAll("[data-diagnostic-filter]")) {
		button.addEventListener("click", () => {
			filter = button.dataset.diagnosticFilter;
			for(const other of root.querySelectorAll("[data-diagnostic-filter]")) {
				other.setAttribute("aria-pressed", String(other === button));
			}
			applyFilter();
		});
	}
	search.addEventListener("input", applyFilter);
	exportButton.addEventListener("click", copyReport);
	rerun.addEventListener("click", runAll);
	const panel = root.closest("[data-base3-tab-panel]");
	if(panel) panel.addEventListener("base3:tab-control:unload", () => {
		generation++;
		completedAt = null;
		exportButton.disabled = true;
		if(activeController) activeController.abort();
	}, {once: true});
	refreshOverview();
	if(cards.length) runAll();
	else {
		rerun.disabled = true;
		progressLabel.textContent = labels.finished;
		announcement.textContent = labels.no_checks;
	}
})();
</script>
