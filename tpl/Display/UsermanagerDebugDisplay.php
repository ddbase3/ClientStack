<?php
	$formatValue = $this->_['formatValue'];
	$formatList = $this->_['formatList'];
?>
<div class="clientstack-usermanager-debug">
	<h3>BASE3 Usermanager Debug</h3>

	<div class="um-meta">
		<div><strong>Quelle:</strong> <span class="mono">IUsermanager via DI</span></div>
		<div><strong>Implementierung:</strong> <span class="mono"><?php echo htmlspecialchars((string)$this->_['usermanagerClass']); ?></span></div>
		<div><strong>Generiert:</strong> <span class="mono"><?php echo htmlspecialchars((string)$this->_['generatedAt']); ?></span></div>
	</div>

	<div class="um-summary um-summary-<?php echo htmlspecialchars((string)$this->_['summary']['status']); ?>">
		<div class="um-summary-main">
			<span class="um-pill <?php echo htmlspecialchars((string)$this->_['summary']['status']); ?>">
				<?php echo htmlspecialchars(strtoupper((string)$this->_['summary']['status'])); ?>
			</span>
			<span>
				User
				<span class="mono"><?php echo htmlspecialchars((string)$this->_['summary']['user_id']); ?></span>
				/
				<span class="mono"><?php echo htmlspecialchars((string)$this->_['summary']['userid']); ?></span>
			</span>
		</div>

		<div class="um-summary-counts">
			<span><strong><?php echo (int)$this->_['summary']['groups']; ?></strong> Gruppen</span>
			<span><strong><?php echo (int)$this->_['summary']['roles']; ?></strong> Rollen</span>
			<span><strong><?php echo (int)$this->_['summary']['permissions']; ?></strong> Effektive Permissions</span>
			<span><strong><?php echo (int)$this->_['summary']['all_permissions']; ?></strong> Permission-Katalog</span>
			<span><strong><?php echo (int)$this->_['summary']['role_checks']; ?></strong> Rollenchecks</span>
			<span><strong><?php echo (int)$this->_['summary']['errors']; ?></strong> Fehler</span>
		</div>

		<button type="button" onclick="window.location.reload()">Neu laden</button>
	</div>

	<div class="um-section">
		<div class="um-section-head">
			<h4>Permission Lookup</h4>
			<div class="um-description">Prüft gezielt <span class="mono">IUsermanager::can()</span> gegen den aktuell verdrahteten Adapter. Der JSON-Endpunkt wird über <span class="mono">ILinkTargetService</span> erzeugt.</div>
		</div>

		<div class="um-actions">
			<label class="um-ref">
				Scope:
				<input type="text" id="um-scope" value="<?php echo htmlspecialchars((string)$this->_['defaultScope']); ?>">
			</label>

			<label class="um-ref">
				Target:
				<input type="text" id="um-target" value="<?php echo htmlspecialchars((string)$this->_['defaultTarget']); ?>" placeholder="z.B. 4611">
			</label>

			<label class="um-ref">
				Operation:
				<input type="text" id="um-operation" list="um-operation-options" value="<?php echo htmlspecialchars((string)$this->_['defaultOperation']); ?>">
			</label>

			<datalist id="um-operation-options">
				<?php foreach ((array)$this->_['operationOptions'] as $operation): ?>
					<option value="<?php echo htmlspecialchars((string)$operation); ?>"></option>
				<?php endforeach; ?>
			</datalist>

			<button type="button" onclick="umProbeSingle()">Einzelprüfung</button>
			<button type="button" onclick="umProbeAll()">Alle Operationen prüfen</button>
		</div>

		<div class="um-usage">
			<div class="um-usage-label">Konkrete Syntax für die aktuellen Eingaben:</div>
			<pre id="um-usage"><?php echo htmlspecialchars((string)$this->_['initialUsage']); ?></pre>
		</div>

		<div class="um-ajax-meta">
			<div><strong>Endpoint:</strong> <span class="mono" id="um-endpoint-label"><?php echo htmlspecialchars((string)$this->_['endpoint']); ?></span></div>
			<div><strong>Parameter:</strong> <span class="mono"><?php echo htmlspecialchars((string)$this->_['scopeParamName']); ?></span>, <span class="mono"><?php echo htmlspecialchars((string)$this->_['targetParamName']); ?></span>, <span class="mono"><?php echo htmlspecialchars((string)$this->_['operationParamName']); ?></span></div>
		</div>

		<div id="um-probe-status" class="um-empty">Noch keine AJAX-Prüfung ausgeführt.</div>

		<div class="um-tablewrap" id="um-probe-tablewrap" style="display: none;">
			<table class="um-table">
				<thead>
					<tr>
						<th>Status</th>
						<th>Scope</th>
						<th>Target</th>
						<th>Operation</th>
						<th>Ergebnis</th>
						<th>Konkreter Code</th>
						<th>Details</th>
					</tr>
				</thead>
				<tbody id="um-probe-body"></tbody>
			</table>
		</div>
	</div>

	<div class="um-section">
		<div class="um-section-head">
			<h4>Aktueller User</h4>
			<div class="um-description">Direkt aus <span class="mono">IUsermanager::getUser()</span>. Diese Anzeige prüft damit bewusst den aktuell verdrahteten Adapter.</div>
		</div>

		<?php if (empty($this->_['user'])): ?>
			<div class="um-empty">Kein User geliefert.</div>
		<?php else: ?>
			<div class="um-kv-grid">
				<?php foreach ((array)$this->_['user'] as $key => $value): ?>
					<div class="um-kv-row">
						<div class="um-kv-key"><?php echo htmlspecialchars((string)$key); ?></div>
						<div class="um-kv-value"><pre><?php echo htmlspecialchars($formatValue($value)); ?></pre></div>
					</div>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
	</div>

	<div class="um-section">
		<div class="um-section-head">
			<h4>Rollen-Checks</h4>
			<div class="um-description">Checks auf die vom Adapter gelieferten Rollen. Es werden keine künstlichen BASE3-Rollen ergänzt.</div>
		</div>

		<?php if (empty($this->_['roleChecks'])): ?>
			<div class="um-empty">Keine Rollenchecks möglich.</div>
		<?php else: ?>
			<div class="um-tablewrap">
				<table class="um-table">
					<thead><tr><th>Status</th><th>Check</th><th>Konkreter Code</th><th>Ergebnis</th><th>Details</th></tr></thead>
					<tbody>
						<?php foreach ((array)$this->_['roleChecks'] as $check): ?>
							<tr>
								<td><span class="um-pill <?php echo htmlspecialchars((string)$check['status']); ?>"><?php echo htmlspecialchars(strtoupper((string)$check['status'])); ?></span></td>
								<td><strong><?php echo htmlspecialchars((string)$check['label']); ?></strong></td>
								<td class="um-cell-code"><code><?php echo htmlspecialchars((string)$check['source']); ?></code></td>
								<td class="um-cell-mono"><?php echo htmlspecialchars((string)$check['result']); ?></td>
								<td><?php echo htmlspecialchars((string)$check['details']); ?></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		<?php endif; ?>
	</div>

	<div class="um-section">
		<div class="um-section-head">
			<h4>Gruppen</h4>
			<div class="um-description">Alle Gruppen des aktuellen Users aus <span class="mono">IUsermanager::getGroups()</span>.</div>
		</div>

		<?php if (empty($this->_['groups'])): ?>
			<div class="um-empty">Keine Gruppen geliefert.</div>
		<?php else: ?>
			<div class="um-tablewrap">
				<table class="um-table">
					<thead><tr><th>ID</th><th>Name</th><th>Info</th><th>Archive</th><th>Rollen</th></tr></thead>
					<tbody>
						<?php foreach ((array)$this->_['groups'] as $group): ?>
							<tr>
								<td class="um-cell-mono"><?php echo htmlspecialchars((string)($group['id'] ?? '')); ?></td>
								<td><strong><?php echo htmlspecialchars((string)($group['name'] ?? $group['value'] ?? '')); ?></strong></td>
								<td><?php echo htmlspecialchars($formatValue($group['info'] ?? '')); ?></td>
								<td class="um-cell-mono"><?php echo htmlspecialchars($formatValue($group['archive'] ?? '')); ?></td>
								<td><?php echo htmlspecialchars($formatList($group['roles'] ?? [])); ?></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		<?php endif; ?>
	</div>

	<div class="um-section">
		<div class="um-section-head">
			<h4>Rollen</h4>
			<div class="um-description">Effektive Rollen des aktuellen Users aus <span class="mono">IUsermanager::getRoles()</span>.</div>
		</div>

		<?php if (empty($this->_['roles'])): ?>
			<div class="um-empty">Keine Rollen geliefert.</div>
		<?php else: ?>
			<div class="um-tablewrap">
				<table class="um-table">
					<thead><tr><th>ID</th><th>Name</th><th>Label</th><th>Archive</th><th>Permissions an Rolle</th><th>Info</th></tr></thead>
					<tbody>
						<?php foreach ((array)$this->_['roles'] as $role): ?>
							<tr>
								<td class="um-cell-mono"><?php echo htmlspecialchars((string)($role['id'] ?? '')); ?></td>
								<td><strong><?php echo htmlspecialchars((string)($role['name'] ?? $role['value'] ?? '')); ?></strong></td>
								<td><?php echo htmlspecialchars($formatValue($role['label'] ?? '')); ?></td>
								<td class="um-cell-mono"><?php echo htmlspecialchars($formatValue($role['archive'] ?? '')); ?></td>
								<td><?php echo htmlspecialchars($formatList($role['permissions'] ?? [])); ?></td>
								<td><?php echo htmlspecialchars($formatValue($role['info'] ?? '')); ?></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		<?php endif; ?>
	</div>

	<div class="um-section">
		<div class="um-section-head">
			<h4>Effektive Permissions ohne Target</h4>
			<div class="um-description">Aus <span class="mono">IUsermanager::getPermissions()</span>. Bei ILIAS ist diese Liste normalerweise leer, weil Permissions objektbezogen sind.</div>
		</div>

		<?php if (empty($this->_['permissionScopes'])): ?>
			<div class="um-empty">Keine globalen effektiven Permissions geliefert.</div>
		<?php else: ?>
			<?php foreach ((array)$this->_['permissionScopes'] as $scope => $permissions): ?>
				<div class="um-subsection">
					<h5><?php echo htmlspecialchars((string)$scope); ?></h5>
					<div class="um-tablewrap">
						<table class="um-table">
							<thead><tr><th>ID</th><th>Permission</th><th>Target</th><th>Label</th><th>Archive</th><th>Info</th></tr></thead>
							<tbody>
								<?php foreach ((array)$permissions as $permission): ?>
									<tr>
										<td class="um-cell-mono"><?php echo htmlspecialchars((string)($permission['id'] ?? '')); ?></td>
										<td><strong><?php echo htmlspecialchars((string)($permission['permission'] ?? $permission['value'] ?? '')); ?></strong></td>
										<td class="um-cell-mono"><?php echo htmlspecialchars($formatValue($permission['target'] ?? '')); ?></td>
										<td><?php echo htmlspecialchars($formatValue($permission['label'] ?? '')); ?></td>
										<td class="um-cell-mono"><?php echo htmlspecialchars($formatValue($permission['archive'] ?? '')); ?></td>
										<td><?php echo htmlspecialchars($formatValue($permission['info'] ?? '')); ?></td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					</div>
				</div>
			<?php endforeach; ?>
		<?php endif; ?>
	</div>

	<div class="um-section">
		<div class="um-section-head">
			<h4>Permission-Katalog</h4>
			<div class="um-description">Aus <span class="mono">IUsermanager::getAllPermissions()</span>. Beim ILIAS-Adapter entspricht dies dem ILIAS-Operationskatalog, nicht einer globalen Grant-Liste.</div>
		</div>

		<?php if (empty($this->_['allPermissionScopes'])): ?>
			<div class="um-empty">Kein Permission-Katalog geliefert.</div>
		<?php else: ?>
			<?php foreach ((array)$this->_['allPermissionScopes'] as $scope => $permissions): ?>
				<div class="um-subsection">
					<h5><?php echo htmlspecialchars((string)$scope); ?></h5>
					<div class="um-tablewrap">
						<table class="um-table">
							<thead><tr><th>ID</th><th>Permission / Operation</th><th>Target</th><th>Label</th><th>Archive</th><th>Info</th></tr></thead>
							<tbody>
								<?php foreach ((array)$permissions as $permission): ?>
									<tr>
										<td class="um-cell-mono"><?php echo htmlspecialchars((string)($permission['id'] ?? '')); ?></td>
										<td><strong><?php echo htmlspecialchars((string)($permission['permission'] ?? $permission['value'] ?? '')); ?></strong></td>
										<td class="um-cell-mono"><?php echo htmlspecialchars($formatValue($permission['target'] ?? '')); ?></td>
										<td><?php echo htmlspecialchars($formatValue($permission['label'] ?? '')); ?></td>
										<td class="um-cell-mono"><?php echo htmlspecialchars($formatValue($permission['archive'] ?? '')); ?></td>
										<td><?php echo htmlspecialchars($formatValue($permission['info'] ?? '')); ?></td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					</div>
				</div>
			<?php endforeach; ?>
		<?php endif; ?>
	</div>

	<div class="um-section">
		<div class="um-section-head">
			<h4>Service Calls</h4>
			<div class="um-description">Rohstatus der aufgerufenen <span class="mono">IUsermanager</span>-Methoden.</div>
		</div>

		<div class="um-tablewrap">
			<table class="um-table">
				<thead><tr><th>Status</th><th>Methode</th><th>Fehler</th></tr></thead>
				<tbody>
					<?php foreach ((array)$this->_['calls'] as $call): ?>
						<tr>
							<td><span class="um-pill <?php echo htmlspecialchars((string)$call['status']); ?>"><?php echo htmlspecialchars(strtoupper((string)$call['status'])); ?></span></td>
							<td class="um-cell-mono"><?php echo htmlspecialchars((string)$call['label']); ?></td>
							<td><?php echo htmlspecialchars((string)$call['error']); ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	</div>
</div>

<style>
.clientstack-usermanager-debug { background: #ffffff; border: 1px solid #d6d6d6; padding: 16px; border-radius: 4px; max-width: 100%; font-family: Arial, sans-serif; color: #333; }
.clientstack-usermanager-debug h3 { margin-top: 0; margin-bottom: 12px; font-size: 1.1em; }
.um-meta { margin-bottom: 16px; font-size: 13px; color: #555; display: flex; gap: 18px; flex-wrap: wrap; }
.mono { font-family: Consolas, monospace; }
.um-summary { border: 1px solid #ddd; background: #f8f8f8; border-radius: 4px; padding: 12px; margin-bottom: 16px; display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap; }
.um-summary-main { display: flex; align-items: center; gap: 10px; font-size: 14px; }
.um-summary-counts { display: flex; align-items: center; gap: 14px; font-size: 13px; color: #555; flex-wrap: wrap; }
.um-summary button, .um-actions button { padding: 8px 16px; border: 1px solid #ccc; background: #f0f0f0; color: #333; border-radius: 4px; cursor: pointer; font-size: 14px; transition: background 0.2s, border-color 0.2s; }
.um-summary button:hover, .um-actions button:hover { background: #e6e6e6; border-color: #bbb; }
.um-section { border-top: 1px solid #eee; padding-top: 14px; margin-top: 14px; }
.um-section:first-of-type { border-top: 0; padding-top: 0; margin-top: 0; }
.um-section-head { margin-bottom: 10px; }
.um-section h4 { margin: 0 0 4px 0; font-size: 1em; color: #333; }
.um-subsection h5 { margin: 14px 0 6px 0; font-size: 0.95em; }
.um-description { font-size: 13px; color: #666; }
.um-actions { border: 1px solid #ddd; background: #f8f8f8; border-radius: 4px; padding: 12px; margin-bottom: 12px; display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
.um-ref { display: flex; align-items: center; gap: 8px; font-size: 13px; color: #555; }
.um-ref input { width: 170px; padding: 6px 10px; border: 1px solid #ccc; border-radius: 4px; background: #fff; color: #333; }
.um-usage { border: 1px solid #ddd; background: #fbfbfb; border-radius: 4px; padding: 10px; margin-bottom: 10px; }
.um-usage-label { font-size: 13px; color: #555; margin-bottom: 6px; }
.um-usage pre { margin: 0; white-space: pre-wrap; word-break: break-word; font-family: Consolas, monospace; }
.um-ajax-meta { display: flex; gap: 16px; flex-wrap: wrap; margin-bottom: 10px; font-size: 12px; color: #666; }
.um-tablewrap { overflow-x: auto; -webkit-overflow-scrolling: touch; }
.um-table { width: 100%; border-collapse: collapse; font-size: 13px; }
.um-table th, .um-table td { border-top: 1px solid #eee; padding: 8px 10px; vertical-align: top; text-align: left; }
.um-table thead th { border-top: 0; border-bottom: 1px solid #ddd; font-weight: bold; white-space: nowrap; }
.um-pill { display: inline-block; padding: 2px 8px; border-radius: 999px; border: 1px solid #ccc; background: #f6f6f6; font-size: 12px; white-space: nowrap; font-weight: bold; }
.um-pill.ok { border-color: #8d8; background: #f6fff6; color: #2d6a2d; }
.um-pill.warning { border-color: #e3c07a; background: #fffaf0; color: #8a5a00; }
.um-pill.error { border-color: #d88; background: #fff5f5; color: #a33; }
.um-pill.info { border-color: #9cd; background: #f3fbff; color: #135a7a; }
.um-cell-mono { font-family: Consolas, monospace; white-space: nowrap; color: #444; }
.um-cell-code code { font-family: Consolas, monospace; white-space: nowrap; color: #444; }
.um-empty { color: #777; font-style: italic; border: 1px solid #eee; background: #fafafa; border-radius: 4px; padding: 8px 10px; font-size: 13px; margin-bottom: 10px; }
.um-kv-grid { display: grid; grid-template-columns: minmax(120px, 220px) minmax(0, 1fr); border-top: 1px solid #eee; font-size: 13px; }
.um-kv-row { display: contents; }
.um-kv-key, .um-kv-value { border-bottom: 1px solid #eee; padding: 8px 10px; }
.um-kv-key { font-family: Consolas, monospace; background: #fafafa; color: #444; }
.um-kv-value pre { margin: 0; white-space: pre-wrap; word-break: break-word; font-family: Consolas, monospace; }
@media (max-width: 800px) { .um-kv-grid { grid-template-columns: 1fr; } .um-kv-row { display: block; } .um-kv-key { border-bottom: 0; } }
</style>

<script>
	const UM_ENDPOINT = <?php echo json_encode((string)$this->_['endpoint']); ?>;
	const UM_SCOPE_PARAM = <?php echo json_encode((string)$this->_['scopeParamName']); ?>;
	const UM_TARGET_PARAM = <?php echo json_encode((string)$this->_['targetParamName']); ?>;
	const UM_OPERATION_PARAM = <?php echo json_encode((string)$this->_['operationParamName']); ?>;
	const UM_MODE_PARAM = <?php echo json_encode((string)$this->_['modeParamName']); ?>;

	function umProbeSingle() {
		umProbe('single');
	}

	function umProbeAll() {
		umProbe('all');
	}

	function umProbe(mode) {
		const scope = String(document.getElementById('um-scope').value || '').trim();
		const target = String(document.getElementById('um-target').value || '').trim();
		const operation = String(document.getElementById('um-operation').value || '').trim();
		const status = document.getElementById('um-probe-status');
		const usage = document.getElementById('um-usage');
		const tablewrap = document.getElementById('um-probe-tablewrap');
		const body = document.getElementById('um-probe-body');
		const url = new URL(UM_ENDPOINT, window.location.href);

		url.searchParams.set(UM_SCOPE_PARAM, scope);
		url.searchParams.set(UM_TARGET_PARAM, target);
		url.searchParams.set(UM_OPERATION_PARAM, operation);
		url.searchParams.set(UM_MODE_PARAM, mode);

		status.textContent = 'Prüfe...';
		status.style.display = 'block';
		tablewrap.style.display = 'none';
		body.innerHTML = '';

		fetch(url.toString(), { credentials: 'same-origin' })
			.then(response => response.json())
			.then(payload => {
				if (!payload || payload.status !== 'ok') {
					throw new Error(payload && payload.message ? payload.message : 'Unknown JSON response.');
				}

				const data = payload.data || {};
				usage.textContent = data.usage || '';
				status.textContent = String(data.total || 0) + ' Check(s), ' + String(data.allowed || 0) + ' erlaubt, ' + String(data.errors || 0) + ' Fehler.';

				(data.rows || []).forEach(row => {
					const tr = document.createElement('tr');
					tr.appendChild(umCell(umPill(row.status || 'info')));
					tr.appendChild(umTextCell(row.scope || '', 'um-cell-mono'));
					tr.appendChild(umTextCell(row.target === null || row.target === undefined ? '' : String(row.target), 'um-cell-mono'));
					tr.appendChild(umTextCell(row.label || '', ''));
					tr.appendChild(umTextCell(row.result || '', 'um-cell-mono'));
					tr.appendChild(umCodeCell(row.usage || ''));
					tr.appendChild(umTextCell(row.details || '', ''));
					body.appendChild(tr);
				});

				tablewrap.style.display = (data.rows || []).length > 0 ? 'block' : 'none';
			})
			.catch(error => {
				status.textContent = 'Fehler: ' + error.message;
				usage.textContent = '';
			});
	}

	function umCell(child) {
		const td = document.createElement('td');
		td.appendChild(child);
		return td;
	}

	function umTextCell(value, className) {
		const td = document.createElement('td');
		td.textContent = value;
		if (className !== '') td.className = className;
		return td;
	}

	function umCodeCell(value) {
		const td = document.createElement('td');
		const code = document.createElement('code');
		code.textContent = value;
		td.className = 'um-cell-code';
		td.appendChild(code);
		return td;
	}

	function umPill(status) {
		const span = document.createElement('span');
		span.className = 'um-pill ' + status;
		span.textContent = String(status).toUpperCase();
		return span;
	}
</script>
