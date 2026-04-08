<div class="clientstack-log">
	<h3>System Log</h3>

	<div class="log-meta">
		<div><strong>Quelle:</strong> <span class="mono">ILogger::getLogs()</span></div>
		<div><strong>Letztes Update:</strong> <span id="log-lastupdate" class="mono">–</span></div>
	</div>

	<div class="log-actions">
		<label class="log-scope">
			Scope:
			<select id="log-scope" onchange="logRefresh(true)"></select>
		</label>

		<button type="button" onclick="logRefresh(true)">Jetzt aktualisieren</button>

		<label class="log-autorefresh">
			<input type="checkbox" id="log-autorefresh" checked onchange="logToggleAutoRefresh()">
			Auto-Refresh (3s)
		</label>

		<label id="log-loading">Bitte warten…</label>
	</div>

	<div class="log-tablewrap">
		<table class="log-table">
			<thead>
				<tr>
					<th>Zeit</th>
					<th>Scope</th>
					<th>Level</th>
					<th>Log</th>
				</tr>
			</thead>
			<tbody id="log-body">
				<tr><td colspan="4" class="log-muted">–</td></tr>
			</tbody>
		</table>
	</div>
</div>

<style>
.clientstack-log {
	background: #ffffff;
	border: 1px solid #d6d6d6;
	padding: 16px;
	border-radius: 4px;
	max-width: 100%;
	font-family: Arial, sans-serif;
	color: #333;
}

.clientstack-log h3 {
	margin-top: 0;
	margin-bottom: 12px;
	font-size: 1.1em;
}

.log-meta {
	margin-bottom: 12px;
	font-size: 13px;
	color: #555;
	display: flex;
	gap: 18px;
	flex-wrap: wrap;
}

.mono {
	font-family: Consolas, monospace;
}

.log-actions {
	display: flex;
	gap: 10px;
	align-items: center;
	margin-bottom: 12px;
	flex-wrap: wrap;
}

.log-actions button {
	padding: 8px 16px;
	border: 1px solid #ccc;
	background: #f0f0f0;
	color: #333;
	border-radius: 4px;
	cursor: pointer;
	font-size: 14px;
	transition: background 0.2s, border-color 0.2s;
}

.log-actions button:hover {
	background: #e6e6e6;
	border-color: #bbb;
}

.log-scope {
	display: flex;
	align-items: center;
	gap: 8px;
	font-size: 13px;
	color: #555;
}

.log-scope select {
	padding: 6px 10px;
	border: 1px solid #ccc;
	border-radius: 4px;
	background: #fff;
	color: #333;
}

.log-autorefresh {
	font-size: 13px;
	color: #555;
	display: flex;
	align-items: center;
	gap: 6px;
	user-select: none;
}

#log-loading {
	display: none;
	color: #666;
	display: flex;
	align-items: center;
	font-style: italic;
	font-size: 13px;
	gap: 6px;
	user-select: none;
}

/* table */
.log-tablewrap {
	overflow-x: auto;
	-webkit-overflow-scrolling: touch;
}

.log-table {
	width: 100%;
	border-collapse: collapse;
	font-size: 13px;
}

.log-table th,
.log-table td {
	border-top: 1px solid #eee;
	padding: 8px 10px;
	vertical-align: top;
	text-align: left;
}

.log-table thead th {
	border-top: 0;
	border-bottom: 1px solid #ddd;
	font-weight: bold;
	white-space: nowrap;
}

.log-muted {
	color: #777;
	font-style: italic;
}

.log-pill {
	display: inline-block;
	padding: 2px 8px;
	border-radius: 999px;
	border: 1px solid #ccc;
	background: #f6f6f6;
	font-size: 12px;
	white-space: nowrap;
}

.log-pill.info { border-color: #8d8; background: #f6fff6; color: #2d6a2d; }
.log-pill.notice { border-color: #9cd; background: #f3fbff; color: #135a7a; }
.log-pill.warning { border-color: #e3c07a; background: #fffaf0; color: #8a5a00; }
.log-pill.error, .log-pill.critical, .log-pill.alert, .log-pill.emergency { border-color: #d88; background: #fff5f5; color: #a33; }
.log-pill.debug { border-color: #ccc; background: #f6f6f6; color: #555; }

.log-cell-mono {
	font-family: Consolas, monospace;
}

.log-cell-wrap {
	white-space: normal;
	word-break: break-word;
}
</style>

<script>
	const LOG_ENDPOINT = <?php echo json_encode((string)$this->_['endpoint']); ?>;

	let logTimer = null;
	let logScopesLoaded = false;

	function logSetLoading(state) {
		document.getElementById("log-loading").style.display = state ? "flex" : "none";
	}

	function logEsc(s) {
		const div = document.createElement("div");
		div.textContent = String(s ?? "");
		return div.innerHTML;
	}

	function logLevelPill(level) {
		const l = String(level || "").toLowerCase();
		const cls = "log-pill " + (l || "info");
		return '<span class="' + cls + '">' + logEsc(l || "info") + '</span>';
	}

	function logRenderScopes(scopes, current) {
		const sel = document.getElementById("log-scope");
		sel.innerHTML = "";

		for (const s of scopes) {
			const opt = document.createElement("option");
			opt.value = s;
			opt.textContent = s;
			if (current && s === current) opt.selected = true;
			sel.appendChild(opt);
		}
	}

	function logRenderRows(rows) {
		const body = document.getElementById("log-body");

		if (!rows || rows.length === 0) {
			body.innerHTML = '<tr><td colspan="4" class="log-muted">Keine Logs gefunden.</td></tr>';
			return;
		}

		let html = "";
		for (const r of rows) {
			const ts = r.timestamp || "–";
			const sc = r.scope || "–";
			const lvl = r.level || "info";
			const msg = r.log || "";

			html += "<tr>" +
				'<td class="log-cell-mono" title="' + logEsc(ts) + '">' + logEsc(ts) + "</td>" +
				'<td class="log-cell-mono">' + logEsc(sc) + "</td>" +
				"<td>" + logLevelPill(lvl) + "</td>" +
				'<td class="log-cell-wrap">' + logEsc(msg) + "</td>" +
			"</tr>";
		}

		body.innerHTML = html;
	}

	async function logRefresh(forceScopes = false) {
		logSetLoading(true);

		try {
			const sel = document.getElementById("log-scope");
			const currentScope = sel && sel.value ? sel.value : "";

			const url = new URL(LOG_ENDPOINT + "tail", window.location.href);
			if (currentScope) url.searchParams.set("scope", currentScope);

			const response = await fetch(url.toString(), {
				method: "GET",
				headers: { "Accept": "application/json" }
			});

			const text = await response.text();
			let json;

			try {
				json = JSON.parse(text);
			} catch (e) {
				logSetLoading(false);
				return;
			}

			if (json.status !== "ok") {
				logSetLoading(false);
				return;
			}

			document.getElementById("log-lastupdate").textContent = json.timestamp || "–";

			// scopes only once (or forced)
			if (!logScopesLoaded || forceScopes) {
				logRenderScopes(json.data.scopes || [], json.data.scope || "");
				logScopesLoaded = true;
			}

			// keep selected scope stable
			if (json.data.scope && document.getElementById("log-scope").value !== json.data.scope) {
				document.getElementById("log-scope").value = json.data.scope;
			}

			logRenderRows(json.data.logs || []);

		} catch (err) {
			// silent UI (wie bei embedding queue)
		}

		logSetLoading(false);
	}

	function logToggleAutoRefresh() {
		const enabled = document.getElementById("log-autorefresh").checked;

		if (logTimer) {
			clearInterval(logTimer);
			logTimer = null;
		}

		if (enabled) {
			logTimer = setInterval(() => logRefresh(false), 3000);
		}
	}

	// init
	logToggleAutoRefresh();
	logRefresh(true);
</script>
