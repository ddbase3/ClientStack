<?php
$modularGridCssUrl = (string) $this->_['modularGridCssUrl'];
$modularGridJsUrl = (string) $this->_['modularGridJsUrl'];
$serviceUrl = (string) $this->_['service'];
$typeOptions = $this->_['typeOptions'];
?>
<link rel="stylesheet" href="<?php echo htmlspecialchars($modularGridCssUrl, ENT_QUOTES); ?>" />

<style>
	.configuration-admin-shell {
		max-width: 1700px;
	}

	.configuration-admin-shell h1 {
		margin: 0 0 8px 0;
		font-size: 24px;
		line-height: 1.2;
		font-weight: 600;
	}

	.configuration-admin-shell p {
		margin: 0 0 12px 0;
		max-width: 1200px;
		color: #555;
		line-height: 1.45;
	}

	.configuration-admin-grid .configuration-admin-panel {
		display: flex;
		align-items: center;
		flex-wrap: nowrap;
		gap: 8px;
		min-width: 0;
		width: 100%;
		padding: 8px 10px;
		border: 1px solid #e2e2e2;
		border-radius: 8px;
		background: #fff;
		overflow-x: auto;
	}

	.configuration-admin-grid .configuration-admin-panel--filters {
		flex-wrap: wrap;
		align-items: flex-start;
		overflow-x: visible;
	}

	.configuration-admin-grid .configuration-admin-panel > * {
		flex: 0 0 auto;
	}

	.configuration-admin-grid .configuration-admin-main {
		border: 1px solid #e2e2e2;
		border-radius: 8px;
		background: #fff;
		padding: 4px 0;
	}

	.configuration-admin-grid .mg-control-group {
		flex-direction: row;
		align-items: center;
		gap: 6px;
		min-width: auto;
	}

	.configuration-admin-grid .mg-label {
		white-space: nowrap;
		color: #666;
		font-size: 12px;
	}

	.configuration-admin-grid .mg-input,
	.configuration-admin-grid .mg-select,
	.configuration-admin-grid .mg-button {
		min-height: 28px;
		font-size: 13px;
	}

	.configuration-admin-grid input[type="search"].mg-input {
		width: 320px;
	}

	.configuration-admin-grid .mg-select {
		width: auto;
		min-width: 128px;
	}

	.configuration-admin-grid .mg-table-scroll {
		height: 600px;
		overflow: auto;
		padding-bottom: 4px;
	}

	.configuration-admin-grid .mg-table thead th {
		position: sticky;
		top: 0;
		z-index: 12;
		background: #fff;
	}

	.configuration-admin-grid .mg-table th,
	.configuration-admin-grid .mg-table td {
		padding: 6px 8px;
		font-size: 13px;
		vertical-align: top;
	}

	.configuration-admin-top-actions {
		display: inline-flex;
		align-items: center;
		gap: 8px;
		flex: 0 0 auto;
	}

	.configuration-admin-button {
		appearance: none;
		border: 1px solid #cfcfcf;
		border-radius: 4px;
		background: #fff;
		color: #222;
		cursor: pointer;
		font: inherit;
		font-size: 13px;
		line-height: 1.3;
		min-height: 28px;
		padding: 4px 10px;
		white-space: nowrap;
	}

	.configuration-admin-button:hover {
		background: #f5f5f5;
	}

	.configuration-admin-button:focus-visible {
		outline: 2px solid #86a8cf;
		outline-offset: 2px;
	}

	.configuration-admin-button-primary {
		background: #2f5d91;
		border-color: #2f5d91;
		color: #fff;
	}

	.configuration-admin-button-primary:hover {
		background: #284f7c;
	}

	.configuration-admin-button-danger {
		border-color: #c8a2a2;
		color: #8a1f1f;
	}

	.configuration-admin-button-danger:hover {
		background: #fff0f0;
	}

	.configuration-admin-output {
		margin-top: 12px;
		padding: 8px 10px;
		border: 1px solid #e2e2e2;
		border-radius: 8px;
		background: #fff;
		font-size: 13px;
		color: #555;
	}

	.configuration-admin-output strong {
		color: #222;
	}

	.configuration-admin-cell-stack {
		display: grid;
		gap: 2px;
		min-width: 0;
	}

	.configuration-admin-cell-main {
		font-weight: 600;
		color: #222;
		min-width: 0;
		overflow-wrap: anywhere;
	}

	.configuration-admin-cell-sub {
		font-size: 12px;
		color: #666;
		min-width: 0;
		overflow-wrap: anywhere;
	}

	.configuration-admin-value {
		margin: 0;
		max-height: 120px;
		overflow: auto;
		color: #333;
		font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
		font-size: 12px;
		line-height: 1.45;
		white-space: pre-wrap;
		word-break: break-word;
	}

	.configuration-admin-pill {
		display: inline-flex;
		align-items: center;
		padding: 1px 6px;
		border: 1px solid #d6d6d6;
		border-radius: 999px;
		background: #fafafa;
		font-size: 11px;
		line-height: 1.35;
		color: #444;
		white-space: nowrap;
	}

	.configuration-admin-pill-array {
		background: #edf6ff;
		border-color: #c3dff5;
	}

	.configuration-admin-pill-bool {
		background: #eef7ee;
		border-color: #bddfbd;
	}

	.configuration-admin-pill-null {
		background: #f2f2f2;
		border-color: #d4d4d4;
		color: #666;
	}

	.configuration-admin-modal {
		position: fixed;
		inset: 0;
		z-index: 9000;
		display: none;
		align-items: center;
		justify-content: center;
		padding: 24px;
		background: rgba(0, 0, 0, 0.35);
	}

	.configuration-admin-modal.is-open {
		display: flex;
	}

	.configuration-admin-dialog {
		display: grid;
		grid-template-rows: auto 1fr auto;
		gap: 12px;
		width: min(860px, 100%);
		max-height: min(760px, 100%);
		border: 1px solid #d6d6d6;
		border-radius: 8px;
		background: #fff;
		box-shadow: 0 16px 50px rgba(0, 0, 0, 0.20);
		padding: 16px;
	}

	.configuration-admin-dialog-header {
		display: flex;
		align-items: flex-start;
		justify-content: space-between;
		gap: 12px;
	}

	.configuration-admin-dialog-title {
		margin: 0;
		font-size: 18px;
		line-height: 1.25;
		font-weight: 600;
	}

	.configuration-admin-dialog-body {
		display: grid;
		gap: 12px;
		min-height: 0;
		overflow: auto;
	}

	.configuration-admin-form-row {
		display: grid;
		gap: 5px;
	}

	.configuration-admin-form-row-inline {
		grid-template-columns: repeat(2, minmax(0, 1fr));
		gap: 12px;
	}

	.configuration-admin-form-label {
		color: #555;
		font-size: 12px;
		font-weight: 600;
		line-height: 1.3;
	}

	.configuration-admin-form-input,
	.configuration-admin-form-select,
	.configuration-admin-form-textarea {
		width: 100%;
		border: 1px solid #cfcfcf;
		border-radius: 4px;
		background: #fff;
		color: #222;
		font: inherit;
		font-size: 13px;
		line-height: 1.4;
		padding: 7px 9px;
	}

	.configuration-admin-form-textarea {
		min-height: 260px;
		resize: vertical;
		font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
		font-size: 12px;
		white-space: pre;
	}

	.configuration-admin-form-hint {
		color: #666;
		font-size: 12px;
		line-height: 1.35;
	}

	.configuration-admin-dialog-footer {
		display: flex;
		align-items: center;
		justify-content: space-between;
		gap: 12px;
	}

	.configuration-admin-dialog-footer-main,
	.configuration-admin-dialog-footer-extra {
		display: flex;
		align-items: center;
		gap: 8px;
	}

	.configuration-admin-error {
		display: none;
		padding: 8px 10px;
		border: 1px solid #e4b9b9;
		border-radius: 6px;
		background: #fff0f0;
		color: #8a1f1f;
		font-size: 13px;
		line-height: 1.4;
	}

	.configuration-admin-error.is-visible {
		display: block;
	}
</style>

<div class="configuration-admin-shell">
	<h1>Configuration</h1>
	<p>
		Configuration values grouped by section and key. Scalar values can be edited directly; array values are edited as JSON and saved back as PHP arrays.
	</p>

	<div class="configuration-admin-grid">
		<div id="configuration-admin-grid"></div>
		<div id="configuration-admin-output" class="configuration-admin-output"></div>
	</div>
</div>

<div id="configuration-admin-modal" class="configuration-admin-modal" aria-hidden="true">
	<div class="configuration-admin-dialog" role="dialog" aria-modal="true" aria-labelledby="configuration-admin-modal-title">
		<div class="configuration-admin-dialog-header">
			<h2 id="configuration-admin-modal-title" class="configuration-admin-dialog-title">Configuration value</h2>
			<button type="button" id="configuration-admin-close" class="configuration-admin-button">Close</button>
		</div>

		<div class="configuration-admin-dialog-body">
			<div id="configuration-admin-error" class="configuration-admin-error"></div>

			<input type="hidden" id="configuration-admin-old-group" />
			<input type="hidden" id="configuration-admin-old-key" />

			<div class="configuration-admin-form-row-inline">
				<label class="configuration-admin-form-row">
					<span class="configuration-admin-form-label">Group</span>
					<input type="text" id="configuration-admin-group" class="configuration-admin-form-input" autocomplete="off" />
				</label>

				<label class="configuration-admin-form-row">
					<span class="configuration-admin-form-label">Key</span>
					<input type="text" id="configuration-admin-key" class="configuration-admin-form-input" autocomplete="off" />
				</label>
			</div>

			<label class="configuration-admin-form-row">
				<span class="configuration-admin-form-label">Type</span>
				<select id="configuration-admin-type" class="configuration-admin-form-select">
					<option value="string">String</option>
					<option value="int">Integer</option>
					<option value="float">Float</option>
					<option value="bool">Boolean</option>
					<option value="array">Array / JSON</option>
					<option value="null">Null</option>
				</select>
			</label>

			<label class="configuration-admin-form-row">
				<span class="configuration-admin-form-label">Value</span>
				<textarea id="configuration-admin-value" class="configuration-admin-form-textarea" spellcheck="false"></textarea>
				<span id="configuration-admin-value-hint" class="configuration-admin-form-hint"></span>
			</label>
		</div>

		<div class="configuration-admin-dialog-footer">
			<div class="configuration-admin-dialog-footer-extra">
				<button type="button" id="configuration-admin-delete-current" class="configuration-admin-button configuration-admin-button-danger">Delete</button>
			</div>
			<div class="configuration-admin-dialog-footer-main">
				<button type="button" id="configuration-admin-cancel" class="configuration-admin-button">Cancel</button>
				<button type="button" id="configuration-admin-save" class="configuration-admin-button configuration-admin-button-primary">Save</button>
			</div>
		</div>
	</div>
</div>

<script>
	(function() {
		const ENDPOINT_URL = <?php echo json_encode($serviceUrl, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
		const MODULAR_GRID_URL = <?php echo json_encode($modularGridJsUrl, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
		const TYPE_OPTIONS = <?php echo json_encode($typeOptions, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
		const GRID_SELECTOR = '#configuration-admin-grid';
		const LOG_SELECTOR = '#configuration-admin-output';
		const BATCH_SIZE = 50;

		let grid = null;
		let currentEditorRecord = null;

		function getText(value, placeholder = '-') {
			if(value === null || value === undefined || value === '') {
				return placeholder;
			}

			return String(value);
		}

		function setLog(message) {
			const logElement = document.querySelector(LOG_SELECTOR);

			if(!logElement) {
				return;
			}

			logElement.replaceChildren();

			const label = document.createElement('strong');
			label.textContent = 'Last action:';

			logElement.appendChild(label);
			logElement.appendChild(document.createTextNode(' ' + getText(message, 'None')));
		}

		function createElement(className, text = null) {
			const element = document.createElement('div');
			element.className = className;

			if(text !== null && text !== undefined) {
				element.textContent = String(text);
			}

			return element;
		}

		function createButton(className, text) {
			const button = document.createElement('button');
			button.type = 'button';
			button.className = className;
			button.textContent = text;

			return button;
		}

		function renderName(value, row) {
			const wrapper = createElement('configuration-admin-cell-stack');
			const main = createElement('configuration-admin-cell-main', getText(row.key));
			const sub = createElement('configuration-admin-cell-sub', 'Group: ' + getText(row.group));

			wrapper.appendChild(main);
			wrapper.appendChild(sub);

			return wrapper;
		}

		function renderType(value, row) {
			const type = getText(row.type, 'string');
			const pill = document.createElement('span');
			pill.className = ('configuration-admin-pill configuration-admin-pill-' + type).trim();
			pill.textContent = type;

			return pill;
		}

		function renderValue(value, row) {
			const pre = document.createElement('pre');
			pre.className = 'configuration-admin-value';
			pre.textContent = getText(row.value_preview);

			return pre;
		}

		function buildFilterPayload(filters) {
			const result = {};

			Object.entries(filters || {}).forEach(([key, value]) => {
				if(value === '' || value === null || value === undefined) {
					return;
				}

				result[key] = value;
			});

			return result;
		}

		async function postJson(payload) {
			const response = await fetch(ENDPOINT_URL, {
				method: 'POST',
				headers: {
					'Content-Type': 'application/json'
				},
				body: JSON.stringify(payload)
			});

			if(!response.ok) {
				throw new Error('Request failed with status ' + String(response.status));
			}

			return response.json();
		}

		async function refreshGrid() {
			if(!grid) {
				return;
			}

			const commands = ['reloadData', 'reload', 'refreshData', 'refresh'];

			if(typeof grid.execute === 'function') {
				for(const commandName of commands) {
					try {
						const result = grid.execute(commandName);

						if(result && typeof result.then === 'function') {
							await result;
						}

						return;
					}
					catch(error) {}
				}
			}

			for(const methodName of commands) {
				if(typeof grid[methodName] === 'function') {
					const result = grid[methodName]();

					if(result && typeof result.then === 'function') {
						await result;
					}

					return;
				}
			}

			window.location.reload();
		}

		function getModalElement() {
			return document.querySelector('#configuration-admin-modal');
		}

		function getEditorElements() {
			return {
				modal: document.querySelector('#configuration-admin-modal'),
				title: document.querySelector('#configuration-admin-modal-title'),
				error: document.querySelector('#configuration-admin-error'),
				oldGroup: document.querySelector('#configuration-admin-old-group'),
				oldKey: document.querySelector('#configuration-admin-old-key'),
				group: document.querySelector('#configuration-admin-group'),
				key: document.querySelector('#configuration-admin-key'),
				type: document.querySelector('#configuration-admin-type'),
				value: document.querySelector('#configuration-admin-value'),
				valueHint: document.querySelector('#configuration-admin-value-hint'),
				deleteButton: document.querySelector('#configuration-admin-delete-current')
			};
		}

		function setEditorError(message) {
			const elements = getEditorElements();

			if(!elements.error) {
				return;
			}

			elements.error.textContent = getText(message, '');
			elements.error.classList.toggle('is-visible', getText(message, '') !== '');
		}

		function updateEditorHint() {
			const elements = getEditorElements();

			if(!elements.type || !elements.value || !elements.valueHint) {
				return;
			}

			const selectedType = elements.type.value || 'string';

			if(selectedType === 'array') {
				elements.value.disabled = false;
				elements.value.placeholder = '{\n\t"key": "value"\n}';
				elements.valueHint.textContent = 'Array values must be valid JSON. JSON objects and JSON arrays are both saved as PHP arrays.';
				return;
			}

			if(selectedType === 'bool') {
				elements.value.disabled = false;
				elements.value.placeholder = 'true';
				elements.valueHint.textContent = 'Allowed values: true/false, yes/no, on/off or 1/0.';
				return;
			}

			if(selectedType === 'int') {
				elements.value.disabled = false;
				elements.value.placeholder = '123';
				elements.valueHint.textContent = 'The value is saved as integer.';
				return;
			}

			if(selectedType === 'float') {
				elements.value.disabled = false;
				elements.value.placeholder = '123.45';
				elements.valueHint.textContent = 'The value is saved as float.';
				return;
			}

			if(selectedType === 'null') {
				elements.value.value = '';
				elements.value.disabled = true;
				elements.value.placeholder = '';
				elements.valueHint.textContent = 'Null values do not use the text field.';
				return;
			}

			elements.value.disabled = false;
			elements.value.placeholder = 'Value';
			elements.valueHint.textContent = 'The value is saved as string.';
		}

		function openEditor(record = null) {
			const elements = getEditorElements();

			if(!elements.modal) {
				return;
			}

			currentEditorRecord = record;
			setEditorError('');

			const isExisting = !!record;

			elements.title.textContent = isExisting ? 'Edit configuration value' : 'Add configuration value';
			elements.oldGroup.value = isExisting ? getText(record.group, '') : '';
			elements.oldKey.value = isExisting ? getText(record.key, '') : '';
			elements.group.value = isExisting ? getText(record.group, '') : '';
			elements.key.value = isExisting ? getText(record.key, '') : '';
			elements.type.value = isExisting ? getText(record.type, 'string') : 'string';
			elements.value.value = isExisting ? getText(record.value_edit, '') : '';
			elements.deleteButton.hidden = !isExisting;

			updateEditorHint();

			elements.modal.classList.add('is-open');
			elements.modal.setAttribute('aria-hidden', 'false');

			window.setTimeout(() => {
				if(elements.group.value === '') {
					elements.group.focus();
					return;
				}

				elements.value.focus();
			}, 0);
		}

		function closeEditor() {
			const modal = getModalElement();

			if(!modal) {
				return;
			}

			currentEditorRecord = null;
			setEditorError('');
			modal.classList.remove('is-open');
			modal.setAttribute('aria-hidden', 'true');
		}

		async function loadRecord(row) {
			const response = await postJson({
				mode: 'record',
				id: row && row.id ? row.id : ''
			});

			if(!response || response.ok !== true || !response.record) {
				throw new Error(getText(response && response.error, 'Configuration value not found.'));
			}

			return response.record;
		}

		async function openEditorForRow(row) {
			try {
				setLog('Loading configuration value...');
				const record = await loadRecord(row);
				openEditor(record);
				setLog('Loaded configuration value ' + getText(record.group) + '/' + getText(record.key) + '.');
			}
			catch(error) {
				setLog('Failed to load configuration value: ' + getText(error && error.message, String(error)));
			}
		}

		async function saveEditor() {
			const elements = getEditorElements();

			setEditorError('');

			const payload = {
				mode: 'save',
				oldGroup: elements.oldGroup.value,
				oldKey: elements.oldKey.value,
				group: elements.group.value,
				key: elements.key.value,
				type: elements.type.value,
				value: elements.value.value
			};

			try {
				const response = await postJson(payload);

				if(!response || response.ok !== true) {
					throw new Error(getText(response && response.error, 'Save failed.'));
				}

				closeEditor();
				await refreshGrid();

				const record = response.record || payload;
				setLog('Saved configuration value ' + getText(record.group) + '/' + getText(record.key) + '.');
			}
			catch(error) {
				setEditorError(getText(error && error.message, String(error)));
			}
		}

		async function deleteRecord(row) {
			if(!row || !row.id) {
				setLog('Missing configuration value id.');
				return;
			}

			const label = getText(row.group) + '/' + getText(row.key);

			if(!window.confirm('Delete configuration value "' + label + '"?')) {
				return;
			}

			try {
				const response = await postJson({
					mode: 'delete',
					id: row.id
				});

				if(!response || response.ok !== true) {
					throw new Error(getText(response && response.error, 'Delete failed.'));
				}

				await refreshGrid();
				setLog('Deleted configuration value ' + label + '.');
			}
			catch(error) {
				setLog('Failed to delete configuration value ' + label + ': ' + getText(error && error.message, String(error)));
			}
		}

		async function deleteCurrentEditorRecord() {
			if(!currentEditorRecord) {
				return;
			}

			const record = currentEditorRecord;
			closeEditor();
			await deleteRecord(record);
		}

		async function reloadConfiguration() {
			try {
				const response = await postJson({
					mode: 'reload'
				});

				if(!response || response.ok !== true) {
					throw new Error(getText(response && response.error, 'Reload failed.'));
				}

				await refreshGrid();
				setLog('Reloaded configuration from storage.');
			}
			catch(error) {
				setLog('Failed to reload configuration: ' + getText(error && error.message, String(error)));
			}
		}

		function bindEditorEvents() {
			const closeButton = document.querySelector('#configuration-admin-close');
			const cancelButton = document.querySelector('#configuration-admin-cancel');
			const saveButton = document.querySelector('#configuration-admin-save');
			const deleteButton = document.querySelector('#configuration-admin-delete-current');
			const modal = getModalElement();
			const elements = getEditorElements();

			if(closeButton) {
				closeButton.addEventListener('click', () => closeEditor());
			}

			if(cancelButton) {
				cancelButton.addEventListener('click', () => closeEditor());
			}

			if(saveButton) {
				saveButton.addEventListener('click', () => saveEditor());
			}

			if(deleteButton) {
				deleteButton.addEventListener('click', () => deleteCurrentEditorRecord());
			}

			if(elements.type) {
				elements.type.addEventListener('change', () => updateEditorHint());
			}

			if(elements.value) {
				elements.value.addEventListener('keydown', (event) => {
					if((event.ctrlKey || event.metaKey) && event.key === 'Enter') {
						event.preventDefault();
						saveEditor();
					}

					if(event.key === 'Escape') {
						event.preventDefault();
						closeEditor();
					}
				});
			}

			if(modal) {
				modal.addEventListener('click', (event) => {
					if(event.target === modal) {
						closeEditor();
					}
				});
			}

			document.addEventListener('keydown', (event) => {
				if(event.key === 'Escape' && modal && modal.classList.contains('is-open')) {
					event.preventDefault();
					closeEditor();
				}
			});
		}

		function createConfigurationActionsPlugin() {
			return {
				name: 'configurationActions',

				layoutContributions() {
					return [
						{
							zone: 'topLine1',
							order: 5,
							render() {
								const wrapper = document.createElement('div');
								wrapper.className = 'configuration-admin-top-actions';

								const addButton = createButton(
									'configuration-admin-button configuration-admin-button-primary',
									'Add configuration value'
								);

								const reloadButton = createButton(
									'configuration-admin-button',
									'Reload configuration'
								);

								addButton.addEventListener('click', () => openEditor(null));
								reloadButton.addEventListener('click', () => reloadConfiguration());

								wrapper.appendChild(addButton);
								wrapper.appendChild(reloadButton);

								return wrapper;
							}
						}
					];
				}
			};
		}

		async function initGrid() {
			const root = document.querySelector(GRID_SELECTOR);

			if(!root || root.dataset.initialized === '1') {
				return;
			}

			root.dataset.initialized = '1';
			bindEditorEvents();

			const modularGridModule = await import(MODULAR_GRID_URL);

			const {
				AjaxAdapter,
				ColumnVisibilityPlugin,
				FiltersPlugin,
				HeaderMenuPlugin,
				InfoPlugin,
				ModularGrid,
				ResetPlugin,
				RowActionsPlugin,
				SearchPlugin,
				SessionStoragePlugin
			} = modularGridModule;

			const sortTypes = {
				group: 'string',
				key: 'string',
				type: 'string',
				value_preview: 'string'
			};

			const layout = {
				type: 'stack',
				className: 'mg-layout-root',
				children: [
					{
						type: 'zone',
						key: 'topLine1',
						className: 'configuration-admin-panel configuration-admin-panel--main'
					},
					{
						type: 'zone',
						key: 'topLine2',
						className: 'configuration-admin-panel configuration-admin-panel--filters'
					},
					{
						type: 'view',
						key: 'main',
						className: 'configuration-admin-main'
					},
					{
						type: 'zone',
						key: 'statusZone',
						className: 'configuration-admin-panel configuration-admin-panel--status'
					}
				]
			};

			const adapter = new AjaxAdapter({
				url: ENDPOINT_URL,
				method: 'POST',
				rowsPath: 'data',
				totalPath: 'total',
				mapRequest(request) {
					const state = grid ? grid.getState() : {};
					const filters = buildFilterPayload(state.filters || {});
					const sortKey = request.sortKey || 'group';
					const sortDirection = request.sortDirection || 'asc';

					return {
						mode: 'page',
						page: request.page || 1,
						pageSize: request.pageSize || BATCH_SIZE,
						search: request.search || '',
						sort: [
							{
								key: sortKey,
								dir: sortDirection,
								type: sortTypes[sortKey] || 'string'
							}
						],
						filters,
						group: []
					};
				}
			});

			grid = new ModularGrid(GRID_SELECTOR, {
				layout,
				adapter,
				dataMode: 'server',
				server: {
					searchDebounceMs: 220,
					watchStateKeys: ['query', 'filters']
				},
				features: {
					paging: true
				},
				pageSize: BATCH_SIZE,
				sort: {
					key: 'group',
					direction: 'asc'
				},
				plugins: [
					createConfigurationActionsPlugin(),
					SearchPlugin,
					FiltersPlugin,
					HeaderMenuPlugin,
					InfoPlugin,
					RowActionsPlugin,
					ColumnVisibilityPlugin,
					ResetPlugin,
					SessionStoragePlugin
				],
				pluginOptions: {
					search: {
						zone: 'topLine1',
						order: 10,
						label: 'Search',
						placeholder: 'Search group, key, type or value'
					},
					filters: {
						zone: 'topLine2',
						order: 10,
						stateKey: 'filters',
						showClearButton: true,
						clearLabel: 'Clear filters',
						fields: [
							{
								key: 'group',
								label: 'Group',
								type: 'text',
								placeholder: 'Group',
								width: 220
							},
							{
								key: 'key',
								label: 'Key',
								type: 'text',
								placeholder: 'Key',
								width: 220
							},
							{
								key: 'type',
								label: 'Type',
								type: 'select',
								options: TYPE_OPTIONS
							}
						]
					},
					headerMenu: {
						showSortActions: true,
						showClearSortAction: true,
						showHideColumnAction: true
					},
					columnVisibility: {
						zone: ''
					},
					reset: {
						zone: 'topLine1',
						order: 30,
						label: 'Reset',
						sections: ['query', 'filters', 'columns']
					},
					sessionStorage: {
						key: 'configuration-admin-grid-v1',
						sections: ['query', 'filters', 'columns']
					},
					info: {
						zone: 'statusZone',
						order: 10,
						displayMode: 'loaded'
					},
					rowActions: {
						headerMenu: {
							enabled: true,
							buttonLabel: '...',
							items: [
								{
									type: 'columnVisibility',
									label: 'Columns',
									showReset: true,
									resetLabel: 'Reset columns'
								}
							]
						},
						items: [
							{
								key: 'edit',
								label: 'Edit',
								onClick(context) {
									openEditorForRow(context.row);
								}
							},
							{
								key: 'delete',
								label: 'Delete',
								onClick(context) {
									deleteRecord(context.row);
								}
							}
						]
					}
				},
				columns: [
					{
						key: 'key',
						label: 'Configuration',
						width: 340,
						headerMenu: {
							defaultSortKey: 'key',
							defaultSortDirection: 'asc',
							sortOptions: [
								{ key: 'key', label: 'Key' },
								{ key: 'group', label: 'Group' }
							]
						},
						render(value, row) {
							return renderName(value, row);
						}
					},
					{
						key: 'type',
						label: 'Type',
						width: 130,
						headerMenu: {
							defaultSortKey: 'type',
							defaultSortDirection: 'asc',
							sortOptions: [
								{ key: 'type', label: 'Type' }
							]
						},
						render(value, row) {
							return renderType(value, row);
						}
					},
					{
						key: 'value_preview',
						label: 'Value',
						width: 620,
						headerMenu: {
							defaultSortKey: 'value_preview',
							defaultSortDirection: 'asc',
							sortOptions: [
								{ key: 'value_preview', label: 'Value' }
							]
						},
						render(value, row) {
							return renderValue(value, row);
						}
					},
					{
						key: 'group',
						label: 'Group',
						width: 260,
						visible: false,
						headerMenu: {
							defaultSortKey: 'group',
							defaultSortDirection: 'asc',
							sortOptions: [
								{ key: 'group', label: 'Group' }
							]
						}
					}
				]
			});

			await grid.init();
			setLog('Initial configuration values loaded.');
		}

		initGrid().catch((error) => {
			console.error('ConfigurationAdminDisplay failed:', error);
			setLog('Configuration grid failed: ' + getText(error && error.message, String(error)));
		});
	})();
</script>
