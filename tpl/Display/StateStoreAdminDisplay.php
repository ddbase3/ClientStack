<?php
$modularGridCssUrl = (string) $this->_['modularGridCssUrl'];
$modularGridJsUrl = (string) $this->_['modularGridJsUrl'];
$serviceUrl = (string) $this->_['service'];
$typeOptions = is_array($this->_['typeOptions'] ?? null) ? $this->_['typeOptions'] : [];
$expiresStateOptions = is_array($this->_['expiresStateOptions'] ?? null) ? $this->_['expiresStateOptions'] : [];
?>
<link rel="stylesheet" href="<?php echo htmlspecialchars($modularGridCssUrl, ENT_QUOTES); ?>" />

<style>
	.state-store-admin-shell {
		max-width: 1700px;
	}

	.state-store-admin-shell h1 {
		margin: 0 0 8px 0;
		font-size: 24px;
		line-height: 1.2;
		font-weight: 600;
	}

	.state-store-admin-shell p {
		margin: 0 0 12px 0;
		max-width: 1200px;
		color: #555;
		line-height: 1.45;
	}

	.state-store-admin-grid .state-store-admin-panel {
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

	.state-store-admin-grid .state-store-admin-panel--filters {
		flex-wrap: wrap;
		align-items: flex-start;
		overflow-x: visible;
	}

	.state-store-admin-grid .state-store-admin-panel > * {
		flex: 0 0 auto;
	}

	.state-store-admin-grid .state-store-admin-main {
		border: 1px solid #e2e2e2;
		border-radius: 8px;
		background: #fff;
		padding: 4px 0;
	}

	.state-store-admin-grid .mg-control-group {
		flex-direction: row;
		align-items: center;
		gap: 6px;
		min-width: auto;
	}

	.state-store-admin-grid .mg-label {
		white-space: nowrap;
		color: #666;
		font-size: 12px;
	}

	.state-store-admin-grid .mg-input,
	.state-store-admin-grid .mg-select,
	.state-store-admin-grid .mg-button {
		min-height: 28px;
		font-size: 13px;
	}

	.state-store-admin-grid input[type="search"].mg-input {
		width: 360px;
	}

	.state-store-admin-grid .mg-select {
		width: auto;
		min-width: 150px;
	}

	.state-store-admin-grid .mg-table-scroll {
		height: 600px;
		overflow: auto;
		padding-bottom: 4px;
	}

	.state-store-admin-grid .mg-table thead th {
		position: sticky;
		top: 0;
		z-index: 12;
		background: #fff;
	}

	.state-store-admin-grid .mg-table th,
	.state-store-admin-grid .mg-table td {
		padding: 6px 8px;
		font-size: 13px;
		vertical-align: top;
	}

	.state-store-admin-top-actions {
		display: inline-flex;
		align-items: center;
		gap: 8px;
		flex: 0 0 auto;
	}

	.state-store-admin-button {
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

	.state-store-admin-button:hover {
		background: #f5f5f5;
	}

	.state-store-admin-button:focus-visible {
		outline: 2px solid #86a8cf;
		outline-offset: 2px;
	}

	.state-store-admin-button-primary {
		background: #2f5d91;
		border-color: #2f5d91;
		color: #fff;
	}

	.state-store-admin-button-primary:hover {
		background: #284f7c;
	}

	.state-store-admin-button-danger {
		border-color: #c8a2a2;
		color: #8a1f1f;
	}

	.state-store-admin-button-danger:hover {
		background: #fff0f0;
	}

	.state-store-admin-output {
		margin-top: 12px;
		padding: 8px 10px;
		border: 1px solid #e2e2e2;
		border-radius: 8px;
		background: #fff;
		font-size: 13px;
		color: #555;
	}

	.state-store-admin-output strong {
		color: #222;
	}

	.state-store-admin-cell-stack {
		display: grid;
		gap: 2px;
		min-width: 0;
	}

	.state-store-admin-cell-main {
		font-weight: 600;
		color: #222;
		min-width: 0;
		overflow-wrap: anywhere;
	}

	.state-store-admin-cell-sub {
		font-size: 12px;
		color: #666;
		min-width: 0;
		overflow-wrap: anywhere;
	}

	.state-store-admin-value {
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

	.state-store-admin-pill {
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

	.state-store-admin-pill-array {
		background: #edf6ff;
		border-color: #c3dff5;
	}

	.state-store-admin-pill-bool {
		background: #eef7ee;
		border-color: #bddfbd;
	}

	.state-store-admin-pill-null {
		background: #f2f2f2;
		border-color: #d4d4d4;
		color: #666;
	}

	.state-store-admin-pill-raw {
		background: #fff7e8;
		border-color: #efd39b;
		color: #644600;
	}

	.state-store-admin-pill-expired {
		background: #fff0f0;
		border-color: #e4b9b9;
		color: #8a1f1f;
	}

	.state-store-admin-modal {
		position: fixed;
		inset: 0;
		z-index: 9000;
		display: none;
		align-items: center;
		justify-content: center;
		padding: 24px;
		background: rgba(0, 0, 0, 0.35);
	}

	.state-store-admin-modal.is-open {
		display: flex;
	}

	.state-store-admin-dialog {
		display: grid;
		grid-template-rows: auto 1fr auto;
		gap: 12px;
		width: min(920px, 100%);
		max-height: min(780px, 100%);
		border: 1px solid #d6d6d6;
		border-radius: 8px;
		background: #fff;
		box-shadow: 0 16px 50px rgba(0, 0, 0, 0.20);
		padding: 16px;
	}

	.state-store-admin-dialog-header {
		display: flex;
		align-items: flex-start;
		justify-content: space-between;
		gap: 12px;
	}

	.state-store-admin-dialog-title {
		margin: 0;
		font-size: 18px;
		line-height: 1.25;
		font-weight: 600;
	}

	.state-store-admin-dialog-body {
		display: grid;
		gap: 12px;
		min-height: 0;
		overflow: auto;
	}

	.state-store-admin-form-row {
		display: grid;
		gap: 5px;
	}

	.state-store-admin-form-row-inline {
		grid-template-columns: repeat(2, minmax(0, 1fr));
		gap: 12px;
	}

	.state-store-admin-form-label {
		color: #555;
		font-size: 12px;
		font-weight: 600;
		line-height: 1.3;
	}

	.state-store-admin-form-input,
	.state-store-admin-form-select,
	.state-store-admin-form-textarea {
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

	.state-store-admin-form-input[readonly] {
		background: #f7f7f7;
		color: #555;
	}

	.state-store-admin-form-textarea {
		min-height: 300px;
		resize: vertical;
		font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
		font-size: 12px;
		white-space: pre;
	}

	.state-store-admin-form-hint {
		color: #666;
		font-size: 12px;
		line-height: 1.35;
	}

	.state-store-admin-dialog-footer {
		display: flex;
		align-items: center;
		justify-content: space-between;
		gap: 12px;
	}

	.state-store-admin-dialog-footer-main,
	.state-store-admin-dialog-footer-extra {
		display: flex;
		align-items: center;
		gap: 8px;
	}

	.state-store-admin-error {
		display: none;
		padding: 8px 10px;
		border: 1px solid #e4b9b9;
		border-radius: 6px;
		background: #fff0f0;
		color: #8a1f1f;
		font-size: 13px;
		line-height: 1.4;
	}

	.state-store-admin-error.is-visible {
		display: block;
	}
</style>

<div class="state-store-admin-shell">
	<h1>Base3 State Store</h1>
	<p>
		Existing state store entries from <code>base3_statestore</code>. Only values can be edited; entries can also be deleted. New entries and key changes are intentionally disabled.
	</p>

	<div class="state-store-admin-grid">
		<div id="state-store-admin-grid"></div>
		<div id="state-store-admin-output" class="state-store-admin-output"></div>
	</div>
</div>

<div id="state-store-admin-modal" class="state-store-admin-modal" aria-hidden="true">
	<div class="state-store-admin-dialog" role="dialog" aria-modal="true" aria-labelledby="state-store-admin-modal-title">
		<div class="state-store-admin-dialog-header">
			<h2 id="state-store-admin-modal-title" class="state-store-admin-dialog-title">State store value</h2>
			<button type="button" id="state-store-admin-close" class="state-store-admin-button">Close</button>
		</div>

		<div class="state-store-admin-dialog-body">
			<div id="state-store-admin-error" class="state-store-admin-error"></div>

			<input type="hidden" id="state-store-admin-id" />

			<label class="state-store-admin-form-row">
				<span class="state-store-admin-form-label">Key</span>
				<input type="text" id="state-store-admin-key" class="state-store-admin-form-input" autocomplete="off" readonly />
			</label>

			<div class="state-store-admin-form-row-inline">
				<label class="state-store-admin-form-row">
					<span class="state-store-admin-form-label">Updated at</span>
					<input type="text" id="state-store-admin-updated-at" class="state-store-admin-form-input" readonly />
				</label>

				<label class="state-store-admin-form-row">
					<span class="state-store-admin-form-label">Expires at</span>
					<input type="text" id="state-store-admin-expires-at" class="state-store-admin-form-input" readonly />
				</label>
			</div>

			<label class="state-store-admin-form-row">
				<span class="state-store-admin-form-label">Value type</span>
				<select id="state-store-admin-type" class="state-store-admin-form-select">
					<option value="string">String</option>
					<option value="int">Integer</option>
					<option value="float">Float</option>
					<option value="bool">Boolean</option>
					<option value="array">Array / JSON</option>
					<option value="null">Null</option>
					<option value="raw">Raw text</option>
				</select>
			</label>

			<label class="state-store-admin-form-row">
				<span class="state-store-admin-form-label">Value</span>
				<textarea id="state-store-admin-value" class="state-store-admin-form-textarea" spellcheck="false"></textarea>
				<span id="state-store-admin-value-hint" class="state-store-admin-form-hint"></span>
			</label>
		</div>

		<div class="state-store-admin-dialog-footer">
			<div class="state-store-admin-dialog-footer-extra">
				<button type="button" id="state-store-admin-delete-current" class="state-store-admin-button state-store-admin-button-danger">Delete</button>
			</div>
			<div class="state-store-admin-dialog-footer-main">
				<button type="button" id="state-store-admin-cancel" class="state-store-admin-button">Cancel</button>
				<button type="button" id="state-store-admin-save" class="state-store-admin-button state-store-admin-button-primary">Save</button>
			</div>
		</div>
	</div>
</div>

<script>
	(function() {
		const ENDPOINT_URL = <?php echo json_encode($serviceUrl, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
		const MODULAR_GRID_URL = <?php echo json_encode($modularGridJsUrl, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
		const TYPE_OPTIONS = <?php echo json_encode($typeOptions, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
		const EXPIRES_STATE_OPTIONS = <?php echo json_encode($expiresStateOptions, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
		const GRID_SELECTOR = '#state-store-admin-grid';
		const LOG_SELECTOR = '#state-store-admin-output';
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

		function renderKey(value, row) {
			const wrapper = createElement('state-store-admin-cell-stack');
			const main = createElement('state-store-admin-cell-main', getText(row.key));
			const sub = createElement('state-store-admin-cell-sub', 'Updated: ' + getText(row.updated_at));

			wrapper.appendChild(main);
			wrapper.appendChild(sub);

			return wrapper;
		}

		function renderType(value, row) {
			const type = getText(row.type, 'string');
			const pill = document.createElement('span');
			pill.className = ('state-store-admin-pill state-store-admin-pill-' + type).trim();
			pill.textContent = type;

			return pill;
		}

		function renderExpires(value, row) {
			const wrapper = createElement('state-store-admin-cell-stack');
			const main = createElement('state-store-admin-cell-main', getText(row.expires_label));
			const state = getText(row.expires_state, 'persistent');
			const pill = document.createElement('span');

			pill.className = ('state-store-admin-pill state-store-admin-pill-' + state).trim();
			pill.textContent = state;

			wrapper.appendChild(main);
			wrapper.appendChild(pill);

			return wrapper;
		}

		function renderValue(value, row) {
			const pre = document.createElement('pre');
			pre.className = 'state-store-admin-value';
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
			return document.querySelector('#state-store-admin-modal');
		}

		function getEditorElements() {
			return {
				modal: document.querySelector('#state-store-admin-modal'),
				title: document.querySelector('#state-store-admin-modal-title'),
				error: document.querySelector('#state-store-admin-error'),
				id: document.querySelector('#state-store-admin-id'),
				key: document.querySelector('#state-store-admin-key'),
				updatedAt: document.querySelector('#state-store-admin-updated-at'),
				expiresAt: document.querySelector('#state-store-admin-expires-at'),
				type: document.querySelector('#state-store-admin-type'),
				value: document.querySelector('#state-store-admin-value'),
				valueHint: document.querySelector('#state-store-admin-value-hint'),
				deleteButton: document.querySelector('#state-store-admin-delete-current')
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
				elements.valueHint.textContent = 'Array values must be valid JSON. JSON objects and JSON arrays are saved as JSON in the state store.';
				return;
			}

			if(selectedType === 'bool') {
				elements.value.disabled = false;
				elements.value.placeholder = 'true';
				elements.valueHint.textContent = 'Allowed values: true/false, yes/no, on/off or 1/0. The stored DB value is JSON encoded.';
				return;
			}

			if(selectedType === 'int') {
				elements.value.disabled = false;
				elements.value.placeholder = '123';
				elements.valueHint.textContent = 'The value is saved as JSON encoded integer.';
				return;
			}

			if(selectedType === 'float') {
				elements.value.disabled = false;
				elements.value.placeholder = '123.45';
				elements.valueHint.textContent = 'The value is saved as JSON encoded float.';
				return;
			}

			if(selectedType === 'null') {
				elements.value.value = '';
				elements.value.disabled = true;
				elements.value.placeholder = '';
				elements.valueHint.textContent = 'Null values do not use the text field. The stored DB value is JSON null.';
				return;
			}

			if(selectedType === 'raw') {
				elements.value.disabled = false;
				elements.value.placeholder = 'Raw stored text';
				elements.valueHint.textContent = 'Raw text is saved exactly as entered, without JSON encoding. Use this only for non-JSON legacy values.';
				return;
			}

			elements.value.disabled = false;
			elements.value.placeholder = 'Value';
			elements.valueHint.textContent = 'The value is saved as JSON encoded string.';
		}

		function openEditor(record) {
			const elements = getEditorElements();

			if(!elements.modal || !record) {
				return;
			}

			currentEditorRecord = record;
			setEditorError('');

			elements.title.textContent = 'Edit state store value';
			elements.id.value = getText(record.id, '');
			elements.key.value = getText(record.key, '');
			elements.updatedAt.value = getText(record.updated_at, '');
			elements.expiresAt.value = getText(record.expires_label, '');
			elements.type.value = getText(record.type, 'string');
			elements.value.value = getText(record.value_edit, '');
			elements.deleteButton.hidden = false;

			updateEditorHint();

			elements.modal.classList.add('is-open');
			elements.modal.setAttribute('aria-hidden', 'false');

			window.setTimeout(() => {
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
				throw new Error(getText(response && response.error, 'State store entry not found.'));
			}

			return response.record;
		}

		async function openEditorForRow(row) {
			try {
				setLog('Loading state store entry...');
				const record = await loadRecord(row);
				openEditor(record);
				setLog('Loaded state store entry ' + getText(record.key) + '.');
			}
			catch(error) {
				setLog('Failed to load state store entry: ' + getText(error && error.message, String(error)));
			}
		}

		async function saveEditor() {
			const elements = getEditorElements();

			setEditorError('');

			const payload = {
				mode: 'save',
				id: elements.id.value,
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

				const record = response.record || currentEditorRecord || {};
				setLog('Saved state store value ' + getText(record.key) + '.');
			}
			catch(error) {
				setEditorError(getText(error && error.message, String(error)));
			}
		}

		async function deleteRecord(row) {
			if(!row || !row.id) {
				setLog('Missing state store entry id.');
				return;
			}

			const label = getText(row.key);

			if(!window.confirm('Delete state store entry "' + label + '"?')) {
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
				setLog('Deleted state store entry ' + label + '.');
			}
			catch(error) {
				setLog('Failed to delete state store entry ' + label + ': ' + getText(error && error.message, String(error)));
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

		function bindEditorEvents() {
			const closeButton = document.querySelector('#state-store-admin-close');
			const cancelButton = document.querySelector('#state-store-admin-cancel');
			const saveButton = document.querySelector('#state-store-admin-save');
			const deleteButton = document.querySelector('#state-store-admin-delete-current');
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

		function createStateStoreActionsPlugin() {
			return {
				name: 'stateStoreActions',

				layoutContributions() {
					return [
						{
							zone: 'topLine1',
							order: 5,
							render() {
								const wrapper = document.createElement('div');
								wrapper.className = 'state-store-admin-top-actions';

								const refreshButton = createButton(
									'state-store-admin-button',
									'Refresh'
								);

								refreshButton.addEventListener('click', async () => {
									await refreshGrid();
									setLog('Reloaded state store entries from database.');
								});

								wrapper.appendChild(refreshButton);

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
				key: 'string',
				type: 'string',
				value_preview: 'string',
				updated_at: 'string',
				expires_at: 'string',
				expires_state: 'string'
			};

			const layout = {
				type: 'stack',
				className: 'mg-layout-root',
				children: [
					{
						type: 'zone',
						key: 'topLine1',
						className: 'state-store-admin-panel state-store-admin-panel--main'
					},
					{
						type: 'zone',
						key: 'topLine2',
						className: 'state-store-admin-panel state-store-admin-panel--filters'
					},
					{
						type: 'view',
						key: 'main',
						className: 'state-store-admin-main'
					},
					{
						type: 'zone',
						key: 'statusZone',
						className: 'state-store-admin-panel state-store-admin-panel--status'
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
					const sortKey = request.sortKey || 'key';
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
					key: 'key',
					direction: 'asc'
				},
				plugins: [
					createStateStoreActionsPlugin(),
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
						placeholder: 'Search key, type, value or timestamp'
					},
					filters: {
						zone: 'topLine2',
						order: 10,
						stateKey: 'filters',
						showClearButton: true,
						clearLabel: 'Clear filters',
						fields: [
							{
								key: 'key',
								label: 'Key',
								type: 'text',
								placeholder: 'Key',
								width: 340
							},
							{
								key: 'type',
								label: 'Type',
								type: 'select',
								options: TYPE_OPTIONS
							},
							{
								key: 'expires_state',
								label: 'Expiration',
								type: 'select',
								options: EXPIRES_STATE_OPTIONS
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
						key: 'state-store-admin-grid-v1',
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
								label: 'Edit value',
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
						label: 'Key',
						width: 460,
						headerMenu: {
							defaultSortKey: 'key',
							defaultSortDirection: 'asc',
							sortOptions: [
								{ key: 'key', label: 'Key' },
								{ key: 'updated_at', label: 'Updated at' }
							]
						},
						render(value, row) {
							return renderKey(value, row);
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
						width: 560,
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
						key: 'expires_at',
						label: 'Expires',
						width: 210,
						headerMenu: {
							defaultSortKey: 'expires_at',
							defaultSortDirection: 'asc',
							sortOptions: [
								{ key: 'expires_at', label: 'Expires at' },
								{ key: 'expires_state', label: 'Expiration state' }
							]
						},
						render(value, row) {
							return renderExpires(value, row);
						}
					},
					{
						key: 'updated_at',
						label: 'Updated at',
						width: 180,
						headerMenu: {
							defaultSortKey: 'updated_at',
							defaultSortDirection: 'desc',
							sortOptions: [
								{ key: 'updated_at', label: 'Updated at' }
							]
						}
					},
					{
						key: 'stored_value_raw',
						label: 'Stored raw value',
						width: 420,
						visible: false,
						headerMenu: {
							defaultSortKey: 'stored_value_raw',
							defaultSortDirection: 'asc',
							sortOptions: [
								{ key: 'stored_value_raw', label: 'Stored raw value' }
							]
						}
					}
				]
			});

			await grid.init();
			setLog('Initial state store entries loaded.');
		}

		initGrid().catch((error) => {
			console.error('StateStoreAdminDisplay failed:', error);
			setLog('State store grid failed: ' + getText(error && error.message, String(error)));
		});
	})();
</script>
