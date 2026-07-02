<?php
$modularGridCssUrl = (string) $this->_['modularGridCssUrl'];
$modularGridJsUrl = (string) $this->_['modularGridJsUrl'];
$modularDialogCssUrl = (string) $this->_['modularDialogCssUrl'];
$modularDialogJsUrl = (string) $this->_['modularDialogJsUrl'];
$serviceUrl = (string) $this->_['service'];
$typeOptions = is_array($this->_['typeOptions'] ?? null) ? $this->_['typeOptions'] : [];
$expiresStateOptions = is_array($this->_['expiresStateOptions'] ?? null) ? $this->_['expiresStateOptions'] : [];
?>
<link rel="stylesheet" href="<?php echo htmlspecialchars($modularGridCssUrl, ENT_QUOTES); ?>" />
<link rel="stylesheet" href="<?php echo htmlspecialchars($modularDialogCssUrl, ENT_QUOTES); ?>" />

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

        .state-store-admin-dialog-surface {
                width: min(920px, 100%);
                max-height: min(780px, 100%);
        }

        .state-store-admin-dialog-surface .md-shell-body {
                display: grid;
                gap: 12px;
        }

        .state-store-admin-editor {
                display: grid;
                gap: 12px;
                min-width: 0;
        }

        .state-store-admin-form-row {
                display: grid;
                gap: 5px;
        }

        .state-store-admin-form-row-inline {
                display: grid;
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

        @media (max-width: 720px) {
                .state-store-admin-form-row-inline {
                        grid-template-columns: 1fr;
                }
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

<template id="state-store-admin-editor-template">
        <div id="state-store-admin-editor" class="state-store-admin-editor">
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
</template>

<script>
        (function() {
                const ENDPOINT_URL = <?php echo json_encode($serviceUrl, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
                const MODULAR_GRID_URL = <?php echo json_encode($modularGridJsUrl, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
                const MODULAR_DIALOG_URL = <?php echo json_encode($modularDialogJsUrl, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
                const TYPE_OPTIONS = <?php echo json_encode($typeOptions, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
                const EXPIRES_STATE_OPTIONS = <?php echo json_encode($expiresStateOptions, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
                const GRID_SELECTOR = '#state-store-admin-grid';
                const LOG_SELECTOR = '#state-store-admin-output';
                const BATCH_SIZE = 50;

                let grid = null;
                let editorDialog = null;
                let editorContent = null;
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

                function createEditorContent() {
                        if(editorContent) {
                                return editorContent;
                        }

                        const template = document.querySelector('#state-store-admin-editor-template');

                        if(!template || !template.content) {
                                throw new Error('State store editor template not found.');
                        }

                        const fragment = template.content.cloneNode(true);
                        const content = fragment.querySelector('#state-store-admin-editor');

                        if(!content) {
                                throw new Error('State store editor content not found.');
                        }

                        editorContent = content;

                        return editorContent;
                }

                function getEditorElements() {
                        const root = editorContent;

                        return {
                                root,
                                error: root ? root.querySelector('#state-store-admin-error') : null,
                                id: root ? root.querySelector('#state-store-admin-id') : null,
                                key: root ? root.querySelector('#state-store-admin-key') : null,
                                updatedAt: root ? root.querySelector('#state-store-admin-updated-at') : null,
                                expiresAt: root ? root.querySelector('#state-store-admin-expires-at') : null,
                                type: root ? root.querySelector('#state-store-admin-type') : null,
                                value: root ? root.querySelector('#state-store-admin-value') : null,
                                valueHint: root ? root.querySelector('#state-store-admin-value-hint') : null
                        };
                }

                function clearEditorError() {
                        const elements = getEditorElements();

                        if(elements.error) {
                                elements.error.textContent = '';
                                elements.error.classList.remove('is-visible');
                        }

                        if(editorDialog && typeof editorDialog.execute === 'function') {
                                editorDialog.execute('clearStatus');
                        }
                }

                function setEditorStatus(message, type = '') {
                        if(!editorDialog || typeof editorDialog.execute !== 'function') {
                                return;
                        }

                        editorDialog.execute('setStatus', {
                                message: getText(message, ''),
                                type
                        });
                }

                function setEditorError(message) {
                        const elements = getEditorElements();
                        const errorText = getText(message, '');

                        if(elements.error) {
                                elements.error.textContent = errorText;
                                elements.error.classList.toggle('is-visible', errorText !== '');
                        }

                        if(errorText !== '') {
                                setEditorStatus(errorText, 'error');
                        }
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

                function buildEditorButtons() {
                        return [
                                {
                                        key: 'delete',
                                        label: 'Delete',
                                        danger: true,
                                        async action() {
                                                await deleteCurrentEditorRecord();
                                        }
                                },
                                {
                                        key: 'cancel',
                                        label: 'Cancel',
                                        action: 'close'
                                },
                                {
                                        key: 'save',
                                        label: 'Save',
                                        primary: true,
                                        busyLabel: 'Saving...',
                                        async action() {
                                                await saveEditor();
                                        }
                                }
                        ];
                }

                function initEditorDialog(modularDialogModule) {
                        if(editorDialog) {
                                return editorDialog;
                        }

                        if(!modularDialogModule || typeof modularDialogModule.createStandardDialog !== 'function') {
                                throw new Error('ModularDialog createStandardDialog export not found.');
                        }

                        const content = createEditorContent();

                        editorDialog = modularDialogModule.createStandardDialog({
                                id: 'state-store-admin-editor-dialog',
                                className: 'state-store-admin-dialog',
                                surfaceClassName: 'state-store-admin-dialog-surface',
                                size: 'large',
                                title: 'State store value',
                                content,
                                status: '',
                                closeButtonPlugin: {
                                        label: 'Close'
                                },
                                statusPlugin: {
                                        renderEmpty: false
                                },
                                buttons: buildEditorButtons()
                        });

                        editorDialog.on('afterClose', () => {
                                currentEditorRecord = null;
                                clearEditorError();
                        });

                        editorDialog.init();

                        return editorDialog;
                }

                function openEditor(record) {
                        const elements = getEditorElements();

                        if(!editorDialog || !elements.root) {
                                setLog('State store editor is not available.');
                                return false;
                        }

                        if(!record) {
                                return false;
                        }

                        currentEditorRecord = record;
                        clearEditorError();

                        editorDialog.execute('setTitle', 'Edit state store value');
                        editorDialog.execute('setButtons', buildEditorButtons());

                        elements.id.value = getText(record.id, '');
                        elements.key.value = getText(record.key, '');
                        elements.updatedAt.value = getText(record.updated_at, '');
                        elements.expiresAt.value = getText(record.expires_label, '');
                        elements.type.value = getText(record.type, 'string');
                        elements.value.value = getText(record.value_edit, '');

                        updateEditorHint();
                        editorDialog.open({ source: 'stateStoreEditor', record });

                        window.setTimeout(() => {
                                elements.value.focus();
                        }, 0);

                        return true;
                }

                function closeEditor() {
                        if(!editorDialog) {
                                return;
                        }

                        editorDialog.close({ source: 'stateStoreEditor' });
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
                        if(!editorDialog) {
                                setLog('State store editor is not available.');
                                return;
                        }

                        try {
                                setLog('Loading state store entry...');
                                const record = await loadRecord(row);

                                if(openEditor(record)) {
                                        setLog('Loaded state store entry ' + getText(record.key) + '.');
                                }
                        }
                        catch(error) {
                                setLog('Failed to load state store entry: ' + getText(error && error.message, String(error)));
                        }
                }

                async function saveEditor() {
                        const elements = getEditorElements();

                        if(!elements.id || !elements.type || !elements.value) {
                                setLog('State store editor controls are not available.');
                                return;
                        }

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

                                const record = response.record || currentEditorRecord || {};

                                closeEditor();
                                await refreshGrid();

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
                        const elements = getEditorElements();

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

                        const modularGridModule = await import(MODULAR_GRID_URL);
                        let editorInitializationError = '';

                        try {
                                const modularDialogModule = await import(MODULAR_DIALOG_URL);
                                initEditorDialog(modularDialogModule);
                                bindEditorEvents();
                        }
                        catch(error) {
                                console.error('State store editor dialog failed:', error);
                                editorInitializationError = 'State store editor failed: ' + getText(error && error.message, String(error));
                        }

                        const {
                                AjaxAdapter,
                                ColumnVisibilityPlugin,
                                FiltersPlugin,
                                HeaderMenuPlugin,
                                InfoPlugin,
                                InfiniteScrollPlugin,
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
                                        paging: false
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
                                        SessionStoragePlugin,
                                        InfiniteScrollPlugin
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
                                        infiniteScroll: {
                                                threshold: 180,
                                                pageSize: BATCH_SIZE,
                                                containerSelector: '.mg-table-scroll'
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

                        if(editorInitializationError !== '') {
                                setLog(editorInitializationError);
                                return;
                        }

                        setLog('Initial state store entries loaded.');
                }

                initGrid().catch((error) => {
                        console.error('StateStoreAdminDisplay failed:', error);
                        setLog('State store grid failed: ' + getText(error && error.message, String(error)));
                });
        })();
</script>
