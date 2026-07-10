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
			<span><strong><?php echo (int)$this->_['summary']['permissions']; ?></strong> Permissions</span>
			<span><strong><?php echo (int)$this->_['summary']['checks']; ?></strong> Checks</span>
			<span><strong><?php echo (int)$this->_['summary']['errors']; ?></strong> Fehler</span>
		</div>

		<button type="button" onclick="window.location.reload()">Neu prüfen</button>
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
			<h4>Adapter Checks</h4>
			<div class="um-description">Gezielte Checks auf Rollen und Permissions über <span class="mono">hasRole()</span> und <span class="mono">can()</span>.</div>
		</div>

		<div class="um-tablewrap">
			<table class="um-table">
				<thead>
					<tr>
						<th>Status</th>
						<th>Check</th>
						<th>Quelle</th>
						<th>Ergebnis</th>
						<th>Details</th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ((array)$this->_['checks'] as $check): ?>
						<tr>
							<td>
								<span class="um-pill <?php echo htmlspecialchars((string)$check['status']); ?>">
									<?php echo htmlspecialchars(strtoupper((string)$check['status'])); ?>
								</span>
							</td>
							<td><strong><?php echo htmlspecialchars((string)$check['label']); ?></strong></td>
							<td class="um-cell-mono"><?php echo htmlspecialchars((string)$check['source']); ?></td>
							<td class="um-cell-mono"><?php echo htmlspecialchars((string)$check['result']); ?></td>
							<td><?php echo htmlspecialchars((string)$check['details']); ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
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
					<thead>
						<tr>
							<th>ID</th>
							<th>Name</th>
							<th>Info</th>
							<th>Archive</th>
							<th>Rollen</th>
						</tr>
					</thead>
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
					<thead>
						<tr>
							<th>ID</th>
							<th>Name</th>
							<th>Label</th>
							<th>Archive</th>
							<th>Permissions an Rolle</th>
							<th>Info</th>
						</tr>
					</thead>
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
			<h4>Permissions</h4>
			<div class="um-description">Effektive Permissions des aktuellen Users aus <span class="mono">IUsermanager::getPermissions()</span>, gruppiert nach Scope.</div>
		</div>

		<?php if (empty($this->_['permissionScopes'])): ?>
			<div class="um-empty">Keine Permissions geliefert.</div>
		<?php else: ?>
			<?php foreach ((array)$this->_['permissionScopes'] as $scope => $permissions): ?>
				<div class="um-subsection">
					<h5><?php echo htmlspecialchars((string)$scope); ?></h5>
					<div class="um-tablewrap">
						<table class="um-table">
							<thead>
								<tr>
									<th>ID</th>
									<th>Permission</th>
									<th>Target</th>
									<th>Label</th>
									<th>Archive</th>
									<th>Info</th>
								</tr>
							</thead>
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
				<thead>
					<tr>
						<th>Status</th>
						<th>Methode</th>
						<th>Fehler</th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ((array)$this->_['calls'] as $call): ?>
						<tr>
							<td>
								<span class="um-pill <?php echo htmlspecialchars((string)$call['status']); ?>">
									<?php echo htmlspecialchars(strtoupper((string)$call['status'])); ?>
								</span>
							</td>
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
.clientstack-usermanager-debug {
	background: #ffffff;
	border: 1px solid #d6d6d6;
	padding: 16px;
	border-radius: 4px;
	max-width: 100%;
	font-family: Arial, sans-serif;
	color: #333;
}

.clientstack-usermanager-debug h3 {
	margin-top: 0;
	margin-bottom: 12px;
	font-size: 1.1em;
}

.um-meta {
	margin-bottom: 16px;
	font-size: 13px;
	color: #555;
	display: flex;
	gap: 18px;
	flex-wrap: wrap;
}

.mono {
	font-family: Consolas, monospace;
}

.um-summary {
	border: 1px solid #ddd;
	background: #f8f8f8;
	border-radius: 4px;
	padding: 12px;
	margin-bottom: 16px;
	display: flex;
	align-items: center;
	justify-content: space-between;
	gap: 12px;
	flex-wrap: wrap;
}

.um-summary-main {
	display: flex;
	align-items: center;
	gap: 10px;
	font-size: 14px;
}

.um-summary-counts {
	display: flex;
	align-items: center;
	gap: 14px;
	font-size: 13px;
	color: #555;
	flex-wrap: wrap;
}

.um-summary button {
	padding: 8px 16px;
	border: 1px solid #ccc;
	background: #f0f0f0;
	color: #333;
	border-radius: 4px;
	cursor: pointer;
	font-size: 14px;
	transition: background 0.2s, border-color 0.2s;
}

.um-summary button:hover {
	background: #e6e6e6;
	border-color: #bbb;
}

.um-section {
	border-top: 1px solid #eee;
	padding-top: 14px;
	margin-top: 14px;
}

.um-section:first-of-type {
	border-top: 0;
	padding-top: 0;
	margin-top: 0;
}

.um-section-head {
	margin-bottom: 10px;
}

.um-section h4 {
	margin: 0 0 4px 0;
	font-size: 1em;
	color: #333;
}

.um-subsection {
	margin-top: 12px;
}

.um-subsection h5 {
	margin: 0 0 6px 0;
	font-size: 0.95em;
	color: #333;
}

.um-description {
	font-size: 13px;
	color: #666;
}

.um-empty {
	border: 1px solid #ddd;
	background: #f8f8f8;
	border-radius: 4px;
	padding: 10px;
	font-size: 13px;
	color: #666;
}

.um-tablewrap {
	overflow-x: auto;
	-webkit-overflow-scrolling: touch;
}

.um-table {
	width: 100%;
	border-collapse: collapse;
	font-size: 13px;
}

.um-table th,
.um-table td {
	border-top: 1px solid #eee;
	padding: 8px 10px;
	vertical-align: top;
	text-align: left;
}

.um-table thead th {
	border-top: 0;
	border-bottom: 1px solid #ddd;
	font-weight: bold;
	white-space: nowrap;
}

.um-pill {
	display: inline-block;
	padding: 2px 8px;
	border-radius: 999px;
	border: 1px solid #ccc;
	background: #f6f6f6;
	font-size: 12px;
	white-space: nowrap;
	font-weight: bold;
}

.um-pill.ok {
	border-color: #8d8;
	background: #f6fff6;
	color: #2d6a2d;
}

.um-pill.warning {
	border-color: #e3c07a;
	background: #fffaf0;
	color: #8a5a00;
}

.um-pill.error {
	border-color: #d88;
	background: #fff5f5;
	color: #a33;
}

.um-pill.info {
	border-color: #9cd;
	background: #f3fbff;
	color: #135a7a;
}

.um-cell-mono {
	font-family: Consolas, monospace;
	white-space: nowrap;
	color: #444;
}

.um-kv-grid {
	border-top: 1px solid #eee;
}

.um-kv-row {
	display: grid;
	grid-template-columns: minmax(160px, 240px) 1fr;
	border-bottom: 1px solid #eee;
	font-size: 13px;
}

.um-kv-key {
	padding: 8px 10px;
	font-weight: bold;
	background: #fafafa;
	border-right: 1px solid #eee;
	word-break: break-word;
}

.um-kv-value {
	padding: 8px 10px;
	min-width: 0;
}

.um-kv-value pre {
	margin: 0;
	font-family: Consolas, monospace;
	white-space: pre-wrap;
	word-break: break-word;
}

@media (max-width: 700px) {
	.um-kv-row {
		grid-template-columns: 1fr;
	}

	.um-kv-key {
		border-right: 0;
		border-bottom: 1px solid #eee;
	}
}
</style>
