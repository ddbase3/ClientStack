<div class="base3ilias-services">
	<h3>Base3Ilias Configuration</h3>

	<table class="base3ilias-services-table">
		<thead>
			<tr>
				<th>Group</th>
				<th>Key</th>
				<th>Value</th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ($this->_['configuration'] as $group => $config) { ?>
			<?php foreach ($config as $key => $value) { ?>
				<tr>
					<td class="service-interface">
						<?php echo $group; ?>
					</td>
					<td class="service-implementation">
						<?php echo $key; ?>
					</td>
					<td class="service-implementation">
						<?php echo htmlspecialchars($value, ENT_QUOTES); ?>
					</td>
				</tr>
			<?php } ?>
			<?php } ?>
		</tbody>
	</table>
</div>

<style>
.base3ilias-services {
	background: #ffffff;
	border: 1px solid #d6d6d6;
	padding: 16px;
	border-radius: 4px;
	max-width: 100%;
}

.base3ilias-services h3 {
	margin-top: 0;
	margin-bottom: 12px;
	font-size: 1.1em;
}

.base3ilias-services-table {
	width: 100%;
	border-collapse: collapse;
}

.base3ilias-services-table th,
.base3ilias-services-table td {
	padding: 8px 10px;
	border-bottom: 1px solid #e0e0e0;
	vertical-align: top;
	text-align: left;
}

.base3ilias-services-table th {
	background: #f5f5f5;
	font-weight: 600;
	border-bottom: 2px solid #cfcfcf;
}

.base3ilias-services-table tr:hover td {
	background: #fafafa;
}

.base3ilias-services-table td.service-interface,
.base3ilias-services-table td.service-implementation {
	font-family: monospace;
	font-size: 0.95em;
	white-space: nowrap;
}
</style>
