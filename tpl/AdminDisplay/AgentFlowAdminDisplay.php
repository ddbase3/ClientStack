<?php
$flow = is_array($this->_['flow'] ?? null) ? $this->_['flow'] : [];
$selfUrl = (string)($this->_['self_url'] ?? '');
$listEndpoint = (string)($this->_['list_endpoint'] ?? '');

$selected = is_array($flow['selected'] ?? null) ? $flow['selected'] : null;
$error = $flow['error'] ?? null;

$nodes = is_array($flow['nodes'] ?? null) ? $flow['nodes'] : [];
$resources = is_array($flow['resources'] ?? null) ? $flow['resources'] : [];
$conns = is_array($flow['connections'] ?? null) ? $flow['connections'] : [];

$resIndex = is_array($flow['resource_index'] ?? null) ? $flow['resource_index'] : [];
$meta = is_array($flow['meta'] ?? null) ? $flow['meta'] : null;

$esc = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

$short = function($v, int $max = 180) use ($esc): string {
	if ($v === null) return 'null';
	if (is_bool($v)) return $v ? 'true' : 'false';
	if (is_int($v) || is_float($v)) return (string)$v;

	if (is_string($v)) {
		$s = trim($v);
		if (mb_strlen($s) > $max) $s = mb_substr($s, 0, $max - 3) . '...';
		return $esc($s);
	}

	if (is_array($v)) {
		$keys = array_slice(array_keys($v), 0, 8);
		$txt = 'array(' . count($v) . '): ' . implode(', ', array_map('strval', $keys));
		if (count($v) > 8) $txt .= ', …';
		return $esc($txt);
	}

	return $esc((string)$v);
};

$describeConfigValue = function($v) use ($short): string {
	if (!is_array($v) || !isset($v['mode'])) return $short($v, 220);

	$mode = (string)($v['mode'] ?? '');
	return match ($mode) {
		'fixed'   => isset($v['value']) ? $short($v['value'], 220) : '(fixed)',
		'config'  => 'config: ' . (string)($v['section'] ?? '') . '.' . (string)($v['key'] ?? ''),
		'env'     => 'env: ' . (string)($v['key'] ?? ''),
		'context' => 'context: ' . (string)($v['key'] ?? ''),
		'default' => isset($v['value']) ? 'default: ' . $short($v['value'], 220) : 'default',
		'inherit' => 'inherit',
		default   => $mode !== '' ? $mode : $short($v, 220),
	};
};

$renderDockTree = function(string $id, int $depth, array &$seen) use (&$renderDockTree, $resIndex, $describeConfigValue, $esc): string {
	if ($id === '') return '';
	if (isset($seen[$id])) return '<div class="af-tree-row af-tree-cycle">↺ ' . $esc($id) . '</div>';

	$seen[$id] = true;

	$res = $resIndex[$id] ?? null;
	$type = is_array($res) ? (string)($res['type'] ?? '') : '';
	$docks = (is_array($res) && is_array($res['docks'] ?? null)) ? $res['docks'] : [];
	$config = (is_array($res) && is_array($res['config'] ?? null)) ? $res['config'] : [];

	$pad = 'style="--af-indent:' . $depth . '"';

	$out = '<div class="af-tree-row" ' . $pad . '>';
	$out .= '<div class="af-tree-main">';
	$out .= '<span class="af-chip af-mono">' . $esc($id) . '</span>';
	$out .= '<span class="af-type af-mono">' . $esc($type ?: '(unknown)') . '</span>';
	$out .= '</div>';

	if ($config) {
		$cfg = [];
		$i = 0;
		foreach ($config as $k => $v) {
			if ($i >= 4) { $cfg[] = '…'; break; }
			$cfg[] = (string)$k . ': ' . $describeConfigValue($v);
			$i++;
		}
		$out .= '<div class="af-tree-sub af-muted">' . $esc(implode(' | ', $cfg)) . '</div>';
	}

	$out .= '</div>';

	foreach ($docks as $dock => $targets) {
		if (!is_array($targets) || !$targets) continue;

		$out .= '<div class="af-tree-dock" ' . $pad . '>';
		$out .= '<span class="af-dockname af-mono">' . $esc((string)$dock) . '</span>';
		$out .= '<span class="af-muted">→</span>';
		$out .= '<span class="af-muted">' . $esc(implode(', ', array_map('strval', $targets))) . '</span>';
		$out .= '</div>';

		foreach ($targets as $t) {
			$out .= $renderDockTree((string)$t, $depth + 1, $seen);
		}
	}

	unset($seen[$id]);
	return $out;
};

$renderInputs = function(array $node) use ($short, $esc): string {
	$inputs = is_array($node['inputs'] ?? null) ? $node['inputs'] : [];
	if (!$inputs) return '<div class="af-muted">–</div>';

	$out = [];
	$i = 0;
	foreach ($inputs as $k => $v) {
		if ($i >= 12) { $out[] = '<div class="af-muted">…</div>'; break; }
		$out[] =
			'<div class="af-kv">' .
			'<div class="af-k af-mono">' . $esc((string)$k) . '</div>' .
			'<div class="af-v">' . $short($v) . '</div>' .
			'</div>';
		$i++;
	}
	return implode('', $out);
};

$selectedId = $selected['id'] ?? '';
?>

<div class="af-wrap" data-selected-flow="<?php echo $esc($selectedId); ?>">

	<div class="af-head">
		<div>
			<div class="af-title">Agent Flow Viewer</div>
			<div class="af-subtitle">Scan: <span class="af-mono">DIR_PLUGIN/*/local/*/*flow*.json</span></div>
		</div>

		<div class="af-picker">
			<span class="af-label">Flow</span>
			<select id="af-flow-select" disabled>
				<option>Loading…</option>
			</select>
		</div>
	</div>

	<?php if ($error) { ?>
		<div class="af-alert af-alert-err"><?php echo $esc($error); ?></div>
	<?php } ?>

	<?php if ($selected && $meta) { ?>
		<div class="af-meta">
			<?php foreach ([
				'Plugin' => $selected['plugin'] ?? '',
				'File' => $selected['relpath'] ?? '',
				'Modified' => $meta['modified_at'] ?? '',
				'Nodes' => $meta['node_count'] ?? 0,
				'Resources' => $meta['resource_count'] ?? 0,
				'Connections' => $meta['connection_count'] ?? 0,
			] as $k => $v) { ?>
				<div class="af-meta-item">
					<div class="af-muted"><?php echo $esc($k); ?></div>
					<div class="af-mono"><?php echo $esc($v); ?></div>
				</div>
			<?php } ?>
		</div>
	<?php } ?>

	<div class="af-grid">

		<!-- Flow Pipeline -->
		<div class="af-box">
			<div class="af-box-head">
				<div class="af-box-title">Flow Pipeline</div>
			</div>

			<?php if (!$nodes) { ?>
				<div class="af-empty">No nodes.</div>
			<?php } else { ?>
				<div class="af-pipewrap">
					<div class="af-pipe">
						<?php foreach ($nodes as $i => $n) {
							if (!is_array($n)) continue;

							$id = (string)($n['id'] ?? '');
							$type = (string)($n['type'] ?? '');
							$docks = is_array($n['docks'] ?? null) ? $n['docks'] : [];
						?>
							<div class="af-node">
								<div class="af-node-head">
									<div class="af-node-id af-mono"><?php echo $esc($id ?: '–'); ?></div>
									<div class="af-node-type af-mono"><?php echo $esc($type ?: '(none)'); ?></div>
								</div>

								<div class="af-node-body">
									<div class="af-block">
										<div class="af-block-title">Inputs</div>
										<div class="af-kvlist"><?php echo $renderInputs($n); ?></div>
									</div>

									<div class="af-block">
										<div class="af-block-title">Docks → Resources</div>

										<?php if (!$docks) { ?>
											<div class="af-muted">–</div>
										<?php } else { ?>
											<?php foreach ($docks as $dock => $targets) {
												$targets = is_array($targets) ? $targets : [];
											?>
												<div class="af-dock">
													<div class="af-dock-head">
														<div class="af-dockname af-mono"><?php echo $esc((string)$dock); ?></div>
														<div class="af-dockcount"><?php echo $esc((string)count($targets)); ?></div>
													</div>
													<div class="af-tree">
														<?php foreach ($targets as $t) {
															$seen = [];
															echo $renderDockTree((string)$t, 0, $seen);
														} ?>
													</div>
												</div>
											<?php } ?>
										<?php } ?>
									</div>
								</div>
							</div>

							<?php if ($i < count($nodes) - 1) { ?>
								<div class="af-arrow">→</div>
							<?php } ?>
						<?php } ?>
					</div>
				</div>
			<?php } ?>
		</div>

		<!-- Connections -->
		<div class="af-box">
			<div class="af-box-head">
				<div class="af-box-title">Connections</div>
			</div>

			<?php if (!$conns) { ?>
				<div class="af-empty">No explicit connections.</div>
			<?php } else { ?>
				<div class="af-connlist">
					<?php foreach ($conns as $c) {
						if (!is_array($c)) continue;
						$rows = [
							'from' => (string)($c['from'] ?? ''),
							'output' => (string)($c['output'] ?? ''),
							'to' => (string)($c['to'] ?? ''),
							'input' => (string)($c['input'] ?? ''),
						];
					?>
						<div class="af-conn">
							<?php foreach ($rows as $k => $v) { ?>
								<div class="af-conn-row">
									<div class="af-conn-k af-muted"><?php echo $esc($k); ?></div>
									<div class="af-conn-v af-mono"><?php echo $esc($v !== '' ? $v : '–'); ?></div>
								</div>
							<?php } ?>
						</div>
					<?php } ?>
				</div>
			<?php } ?>
		</div>

		<!-- Resources Index -->
		<div class="af-box">
			<div class="af-box-head">
				<div class="af-box-title">Resources Index</div>
			</div>

			<?php if (!$resources) { ?>
				<div class="af-empty">No resources.</div>
			<?php } else { ?>
				<div class="af-reslist">
					<?php foreach ($resources as $r) {
						if (!is_array($r)) continue;

						$id = (string)($r['id'] ?? '');
						$type = (string)($r['type'] ?? '');
						$config = is_array($r['config'] ?? null) ? $r['config'] : [];

						$cfg = [];
						$i = 0;
						foreach ($config as $k => $v) {
							if ($i >= 3) { $cfg[] = '…'; break; }
							$cfg[] = (string)$k . ': ' . $describeConfigValue($v);
							$i++;
						}
					?>
						<div class="af-resitem">
							<div class="af-reshead">
								<span class="af-chip af-mono"><?php echo $esc($id ?: '–'); ?></span>
								<span class="af-type af-mono"><?php echo $esc($type ?: '(none)'); ?></span>
							</div>
							<?php if ($cfg) { ?>
								<div class="af-muted af-rescfg"><?php echo $esc(implode(' | ', $cfg)); ?></div>
							<?php } ?>
						</div>
					<?php } ?>
				</div>
			<?php } ?>
		</div>

	</div>
</div>

<style>
.af-wrap {
	background: #fff;
	border: 1px solid #d6d6d6;
	border-radius: 8px;
	padding: 14px;
	font-family: Arial, sans-serif;
	color: #222;
}

.af-wrap, .af-wrap * {
	box-sizing: border-box;
}

.af-mono { font-family: Consolas, monospace; }
.af-muted { color: #6b6b6b; }

.af-head {
	display: flex;
	justify-content: space-between;
	align-items: flex-end;
	gap: 12px;
	flex-wrap: wrap;
	margin-bottom: 10px;
}

.af-title { font-size: 16px; font-weight: 700; }
.af-subtitle { font-size: 12px; color: #555; margin-top: 3px; }

.af-picker {
	display: flex;
	flex-direction: column;
	gap: 6px;
	min-width: 0;
}
.af-label { font-size: 12px; color: #555; }

#af-flow-select {
	padding: 7px 10px;
	border-radius: 6px;
	border: 1px solid #cfcfcf;
	min-width: 360px;
	max-width: 100%;
	background: #fff;
}

.af-alert {
	border-radius: 8px;
	padding: 10px 12px;
	margin: 10px 0;
	font-size: 13px;
}
.af-alert-err { border: 1px solid #e2b1b1; background: #fff5f5; color: #7a1e1e; }

.af-meta {
	display: grid;
	grid-template-columns: repeat(6, minmax(0, 1fr));
	gap: 8px;
	margin: 10px 0 14px;
}
@media (max-width: 1100px) {
	.af-meta { grid-template-columns: repeat(2, minmax(0, 1fr)); }
}
.af-meta-item {
	border: 1px solid #eee;
	border-radius: 8px;
	padding: 8px 10px;
	background: #fafafa;
	display: flex;
	flex-direction: column;
	gap: 4px;
	min-width: 0;
}

.af-meta-item .af-mono {
	overflow: hidden;
}

.af-grid {
	display: grid;
	grid-template-columns: repeat(3, minmax(0, 1fr));
	gap: 12px;
	align-items: start;
}
@media (max-width: 1100px) {
	.af-grid { grid-template-columns: 1fr; }
}

.af-box {
	border: 1px solid #e7e7e7;
	border-radius: 10px;
	padding: 12px;
	background: #fff;
	min-width: 0;
}

.af-box-head { margin-bottom: 10px; }
.af-box-title { font-weight: 700; font-size: 13px; }

.af-empty {
	border: 1px dashed #d8d8d8;
	border-radius: 10px;
	padding: 12px;
	color: #777;
	font-style: italic;
	background: #fcfcfc;
}

.af-pipewrap {
	width: 100%;
	overflow-x: auto;
	-webkit-overflow-scrolling: touch;
}

.af-pipe {
	display: flex;
	gap: 10px;
	align-items: stretch;
}

.af-node {
	flex: 0 0 auto;
	width: 100%;
	border: 1px solid #ddd;
	border-radius: 12px;
	background: #fff;
	display: flex;
	flex-direction: column;
	min-width: 0;
}

.af-arrow {
	flex: 0 0 40px;
	width: 40px;
	display: flex;
	align-items: center;
	justify-content: center;
	color: #888;
	font-size: 18px;
	user-select: none;
}

.af-node-head {
	padding: 10px 12px;
	border-bottom: 1px solid #eee;
	background: #fbfbfb;
	display: flex;
	justify-content: space-between;
	gap: 10px;
	min-width: 0;
}
.af-node-id, .af-node-type { word-break: break-word; }

.af-node-body {
	padding: 10px 12px;
	display: flex;
	flex-direction: column;
	gap: 10px;
	min-width: 0;
}

.af-block {
	border: 1px solid #eee;
	border-radius: 10px;
	padding: 10px;
	min-width: 0;
}

.af-block-title {
	font-size: 12px;
	font-weight: 700;
	margin-bottom: 8px;
}

.af-kv {
	display: grid;
	grid-template-columns: 140px minmax(0, 1fr);
	gap: 10px;
	font-size: 12px;
	min-width: 0;
}
.af-v { word-break: break-word; min-width: 0; }

.af-dock {
	border-top: 1px dashed #e7e7e7;
	margin-top: 10px;
	padding-top: 10px;
	min-width: 0;
}
.af-dock:first-child { border-top: 0; margin-top: 0; padding-top: 0; }

.af-dock-head {
	display: flex;
	justify-content: space-between;
	gap: 10px;
	margin-bottom: 6px;
	min-width: 0;
}
.af-dockname { font-weight: 700; font-size: 12px; }
.af-dockcount {
	font-size: 11px;
	border: 1px solid #d0d0d0;
	border-radius: 999px;
	padding: 2px 8px;
	background: #f6f6f6;
}

.af-tree {
	display: flex;
	flex-direction: column;
	gap: 6px;
	min-width: 0;
}

.af-tree-row {
	border: 1px solid #ededed;
	border-radius: 10px;
	padding: 8px 10px;
	background: #fbfbfb;
	margin-left: calc(var(--af-indent) * 16px);
	min-width: 0;
}

.af-tree-main {
	display: flex;
	gap: 8px;
	flex-wrap: wrap;
	min-width: 0;
}

.af-tree-sub {
	font-size: 11px;
	margin-top: 4px;
	word-break: break-word;
	min-width: 0;
}

.af-tree-dock {
	margin-left: calc(var(--af-indent) * 16px);
	font-size: 11px;
	display: flex;
	gap: 6px;
	flex-wrap: wrap;
}

.af-tree-cycle {
	border-color: #e1c7c7;
	background: #fff7f7;
	color: #7a1e1e;
}

.af-chip {
	border: 1px solid #cfcfcf;
	border-radius: 999px;
	padding: 2px 8px;
	font-size: 12px;
	background: #fff;
}

.af-type {
	border: 1px solid #d7d7d7;
	border-radius: 999px;
	padding: 2px 8px;
	font-size: 11px;
	background: #f7f7f7;
	color: #444;
}

.af-connlist,
.af-reslist {
	display: flex;
	flex-direction: column;
	gap: 10px;
	min-width: 0;
}

.af-conn,
.af-resitem {
	border: 1px solid #eee;
	border-radius: 10px;
	padding: 10px;
	background: #fff;
	min-width: 0;
}

.af-conn-row {
	display: grid;
	grid-template-columns: 72px minmax(0, 1fr);
	gap: 10px;
	font-size: 12px;
	min-width: 0;
}
.af-conn-v { word-break: break-word; min-width: 0; }

.af-reshead {
	display: flex;
	gap: 8px;
	flex-wrap: wrap;
	min-width: 0;
}
.af-rescfg {
	margin-top: 6px;
	font-size: 11px;
	word-break: break-word;
	min-width: 0;
}
</style>

<script>
const AF_SELF_URL = <?php echo json_encode($selfUrl); ?>;
const AF_LIST_ENDPOINT = <?php echo json_encode($listEndpoint); ?>;

(async function() {
	const sel = document.getElementById('af-flow-select');
	const wrap = document.querySelector('.af-wrap');
	const selected = wrap?.dataset?.selectedFlow ? String(wrap.dataset.selectedFlow) : '';

	try {
		const res = await fetch(AF_LIST_ENDPOINT, { headers: { Accept: 'application/json' } });
		const json = await res.json();
		const flows = Array.isArray(json?.data?.flows) ? json.data.flows : [];

		sel.innerHTML = '';
		for (const f of flows) {
			const o = document.createElement('option');
			o.value = f.id;
			o.textContent = f.label;
			sel.appendChild(o);
		}

		if (selected) sel.value = selected;
		if (!sel.value && flows.length) sel.value = flows[0].id;

		sel.disabled = false;
		sel.onchange = () => {
			if (sel.value) location.href = AF_SELF_URL + '&flow=' + encodeURIComponent(sel.value);
		};
	} catch (e) {
		sel.innerHTML = '<option>Failed to load flows</option>';
	}
})();
</script>
