<script>
(() => {
	if (window.BASE3AudienceEditor) return;
	const userWidgets = new WeakMap();
	let selectModulePromise = null;

	function loadSelectModule(editor) {
		const url = editor.dataset.audienceSelectModule;
		if (!url) return Promise.resolve(null);
		if (!selectModulePromise) {
			selectModulePromise = import(url).catch(() => {
				selectModulePromise = null;
				return null;
			});
		}
		return selectModulePromise;
	}

	async function userRequest(editor, action, parameters = {}, signal = undefined) {
		const body = new URLSearchParams({
			scope: editor.dataset.audienceSearchScope,
			csrf: editor.dataset.audienceSearchCsrf,
			action,
			...parameters
		});
		const response = await fetch(editor.dataset.audienceSearchEndpoint, {
			method: 'POST',
			body,
			credentials: 'same-origin',
			cache: 'no-store',
			signal,
			headers: {'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest'}
		});
		if (!response.ok) throw new Error('User lookup failed (HTTP ' + response.status + ').');
		return response.json();
	}

	async function enhanceUserRow(editor, row) {
		if (userWidgets.has(row) || !editor.dataset.audienceSearchEndpoint) return;
		const module = await loadSelectModule(editor);
		if (!module?.ModularSelect || !editor.isConnected || !row.isConnected ||
			editor.dataset.audienceBound !== 'true' || userWidgets.has(row)) return;
		const number = row.querySelector('[data-audience-number]');
		const host = row.querySelector('[data-audience-user-select]');
		const value = number.value.trim();
		const state = {instance: null, controller: null};
		try {
			state.instance = new module.ModularSelect(host, {
				ariaLabel: editor.dataset.userSelectLabel,
				value: value || null,
				selectedItems: value ? [{value, label: editor.dataset.userLabel + ' ' + value}] : [],
				required: true,
				limit: 20,
				minChars: 1,
				debounceMs: 250,
				strings: {
					placeholder: editor.dataset.userSearchPlaceholder,
					loading: editor.dataset.userSearchLoading,
					noResults: editor.dataset.userSearchEmpty,
					searchError: editor.dataset.userSearchError,
					required: editor.dataset.userSelectRequired,
					options: editor.dataset.userSearchOptions,
					showOptions: editor.dataset.userSearchShowOptions,
					hideOptions: editor.dataset.userSearchHideOptions,
					remove: editor.dataset.userSearchRemove,
					loadMore: editor.dataset.userSearchLoadMore,
					minChars: editor.dataset.userSearchMinChars,
					results: editor.dataset.userSearchResults
				},
				source: async ({query, limit, offset, signal}) => {
					const data = await userRequest(editor, 'search', {query, limit, offset}, signal);
					if (!Array.isArray(data.items) || typeof data.hasMore !== 'boolean') {
						throw new Error('Invalid user search response.');
					}
					return data;
				},
				onChange(selectedValue) { number.value = selectedValue ?? ''; }
			}).init();
			userWidgets.set(row, state);
			configureRow(editor, row);
		} catch (_) {
			state.instance?.destroy();
			return; // Numeric fallback remains usable if the module cannot initialize.
		}

		// Resolve only already assigned IDs, without fetching a full user directory.
		if (value && row.querySelector('[data-audience-kind]').value === 'user') {
			state.controller = new AbortController();
			try {
				const data = await userRequest(editor, 'resolve', {id: value}, state.controller.signal);
				if (userWidgets.get(row) === state && number.value === value &&
					data.item?.value === value) {
					state.instance.setValue(value, {selectedItems: [data.item], silent: true});
				}
			} catch (_) {
				// Keep the existing ID visible even if its name is unavailable.
			} finally {
				state.controller = null;
			}
		}
	}

	function destroyUserRow(row) {
		const state = userWidgets.get(row);
		if (!state) return;
		state.controller?.abort();
		state.instance.destroy();
		userWidgets.delete(row);
	}

	function readConfig(row) {
		try { return JSON.parse(row.querySelector('[data-audience-json]').value || '{}'); }
		catch (_) { return {}; }
	}
	function fieldInput(name, displayName, definition, value, arrayPlaceholder) {
		const label = document.createElement('label');
		label.className = 'base3-audience-field';
		label.textContent = displayName + ((definition.required) ? ' *' : '');
		const types = Array.isArray(definition.type) ? definition.type : [definition.type];
		let input;
		if (Array.isArray(definition.enum)) {
			input = document.createElement('select');
			for (const option of definition.enum) {
				const item = document.createElement('option');
				item.value = String(option);
				item.textContent = String(option);
				input.append(item);
			}
		} else if (types.includes('boolean')) {
			input = document.createElement('input');
			input.type = 'checkbox';
		} else if (types.includes('integer') || types.includes('number')) {
			input = document.createElement('input');
			input.type = 'number';
			input.step = types.includes('integer') ? '1' : 'any';
		} else {
			input = document.createElement('input');
			input.type = 'text';
			if (types.includes('array')) input.placeholder = arrayPlaceholder;
		}
		input.dataset.criterionProperty = name;
		input.dataset.criterionType = types[0] || 'string';
		if (input.type === 'checkbox') input.checked = value === true;
		else if (Array.isArray(value)) input.value = value.join(', ');
		else if (value !== undefined && value !== null) input.value = String(value);
		if (definition.description) input.title = String(definition.description);
		label.append(input);
		return label;
	}
	function configureCriterion(editor, row, reset = false) {
		const kind = row.querySelector('[data-audience-kind]').value;
		const name = row.querySelector('[data-audience-criterion]').value;
		const schema = JSON.parse(editor.dataset.schemas || '{}')[name] || {};
		const properties = schema.properties || {};
		const container = row.querySelector('[data-audience-properties]');
		const jsonWrap = row.querySelector('[data-audience-json-wrap]');
		const area = row.querySelector('[data-audience-json]');
		const schemaText = row.querySelector('[data-audience-schema]');
		container.replaceChildren();
		if (kind !== 'criterion') return;
		if (reset) area.value = '{}';
		if (name === '') {
			jsonWrap.hidden = true;
			schemaText.textContent = '';
			return;
		}
		const simple = schema.type === 'object' && Object.values(properties).every((prop) => {
			const type = Array.isArray(prop.type) ? prop.type : [prop.type];
			return type.some((t) => ['string', 'integer', 'number', 'boolean', 'array'].includes(t))
				&& !type.includes('object') && (!type.includes('array') || prop.items);
		});
		jsonWrap.hidden = simple;
		if (!simple) {
			schemaText.textContent = schema.description || editor.dataset.schemaJsonHint;
			return;
		}
		const current = readConfig(row);
		function sync() {
			const data = {};
			for (const input of container.querySelectorAll('[data-criterion-property]')) {
				const type = input.dataset.criterionType;
				if (type === 'boolean') data[input.dataset.criterionProperty] = input.checked;
				else if (input.value.trim() !== '') {
					if (type === 'array') {
						const itemType = (properties[input.dataset.criterionProperty].items || {}).type;
						data[input.dataset.criterionProperty] = input.value.split(',').map((v) => v.trim()).filter(Boolean)
							.map((v) => itemType === 'integer' ? Number(v) : v);
					} else if (type === 'integer' || type === 'number') data[input.dataset.criterionProperty] = Number(input.value);
					else data[input.dataset.criterionProperty] = input.value.trim();
				}
			}
			area.value = JSON.stringify(data);
		}
		for (const [prop, definition] of Object.entries(properties)) {
			const displayName = String(definition.title || prop);
			const field = fieldInput(prop, displayName, {...definition, required: (schema.required || []).includes(prop)}, current[prop], editor.dataset.arrayPlaceholder);
			container.append(field);
			field.addEventListener('input', sync);
			field.addEventListener('change', sync);
		}
		schemaText.textContent = Object.keys(properties).length ? editor.dataset.schemaFieldsHint : editor.dataset.schemaNoFields;
		sync();
	}
	function configureRow(editor, row, reset = false) {
		const kind = row.querySelector('[data-audience-kind]').value;
		const previousKind = row.dataset.kind;
		const number = row.querySelector('[data-audience-number]');
		const userWidget = userWidgets.get(row)?.instance;
		if (reset && previousKind && previousKind !== kind) {
			// Never carry a user/role ID over to an unrelated assignment type.
			number.value = '';
			userWidget?.setValue(null, {silent: true});
		}
		row.dataset.kind = kind;
		const role = row.querySelector('[data-audience-role]');
		const enhancedUser = kind === 'user' && !!userWidget;
		const numberActive = (kind === 'user' && !enhancedUser) || (kind === 'role' && !role);
		row.querySelector('[data-audience-number-wrap]').hidden = !numberActive;
		row.querySelector('[data-audience-user-wrap]').hidden = !enhancedUser;
		if (userWidget) userWidget.setDisabled(kind !== 'user');
		const roleWrap = row.querySelector('[data-audience-role-wrap]');
		if (roleWrap) roleWrap.hidden = kind !== 'role';
		for (const type of ['group', 'criterion']) {
			const wrap = row.querySelector('[data-audience-' + type + '-wrap]');
			if (wrap) wrap.hidden = kind !== type;
		}
		const group = row.querySelector('[data-audience-group]');
		const criterion = row.querySelector('[data-audience-criterion]');
		const config = row.querySelector('[data-audience-json]');
		const configActive = kind === 'expression' || (kind === 'criterion' && criterion.value !== '');
		row.querySelector('[data-audience-config-wrap]').hidden = !configActive;
		// The original [value] form input stays canonical. In enhanced user mode it
		// becomes hidden, but enabled, and ModularSelect updates its value.
		number.type = enhancedUser ? 'hidden' : 'number';
		number.disabled = !numberActive && !enhancedUser;
		number.required = numberActive;
		if (role) {
			role.disabled = kind !== 'role';
			role.required = !role.disabled;
		}
		row.querySelector('[data-audience-number-label]').textContent = kind === 'role' ? editor.dataset.roleLabel : editor.dataset.userLabel;
		if (group) { group.disabled = kind !== 'group'; group.required = !group.disabled; }
		criterion.disabled = kind !== 'criterion';
		criterion.required = !criterion.disabled;
		config.disabled = !configActive;
		config.required = configActive;
		row.querySelector('[data-audience-json-wrap]').hidden = kind !== 'expression';
		row.querySelector('[data-audience-json-label]').textContent = kind === 'expression' ? editor.dataset.expressionLabel : editor.dataset.configLabel;
		if (kind === 'criterion') configureCriterion(editor, row, reset);
		else {
			// Remove fields from the previous criterion without changing the underlying JSON value.
			row.querySelector('[data-audience-properties]').replaceChildren();
			row.querySelector('[data-audience-schema]').textContent = '';
		}
	}
	function bind(editor) {
		if (editor.dataset.audienceBound === 'true') return;
		editor.dataset.audienceBound = 'true';
		for (const row of editor.querySelectorAll('[data-audience-list] > [data-audience-row]')) {
			configureRow(editor, row);
			if (row.dataset.kind === 'user') void enhanceUserRow(editor, row);
		}
		editor.addEventListener('change', (event) => {
			const row = event.target.closest('[data-audience-row]');
			if (!row) return;
			if (event.target.matches('[data-audience-kind]')) {
				configureRow(editor, row, true);
				if (row.dataset.kind === 'user') void enhanceUserRow(editor, row);
			}
			if (event.target.matches('[data-audience-criterion]')) configureRow(editor, row, true);
		});
		editor.addEventListener('click', (event) => {
			const remove = event.target.closest('[data-audience-remove]');
			if (remove) {
				const row = remove.closest('[data-audience-row]');
				destroyUserRow(row);
				row.remove();
				return;
			}
			if (!event.target.closest('[data-audience-add]')) return;
			const template = editor.querySelector('[data-audience-template]');
			const fragment = template.content.cloneNode(true);
			const index = String(Number(editor.dataset.counter || 0));
			editor.dataset.counter = String(Number(index) + 1);
			for (const input of fragment.querySelectorAll('[name]')) input.name = input.name.replace('__INDEX__', index);
			const row = fragment.querySelector('[data-audience-row]');
			editor.querySelector('[data-audience-list]').append(fragment);
			configureRow(editor, row, true);
			if (row.dataset.kind === 'user') void enhanceUserRow(editor, row);
			row.querySelector('[data-audience-kind]').focus();
		});
	}
	function unbindAll(root) {
		if (root.matches?.('[data-audience-editor]')) {
			for (const row of root.querySelectorAll('[data-audience-row]')) {
				destroyUserRow(row);
				configureRow(root, row);
			}
			root.dataset.audienceBound = 'false';
		}
		for (const editor of root.querySelectorAll('[data-audience-editor]')) {
			for (const row of editor.querySelectorAll('[data-audience-row]')) {
				destroyUserRow(row);
				configureRow(editor, row);
			}
			editor.dataset.audienceBound = 'false';
		}
	}
	document.addEventListener('base3:tab-control:unload', (event) => unbindAll(event.target));
	window.BASE3AudienceEditor = {
		bindAll(root) {
			if (root.matches?.('[data-audience-editor]')) bind(root);
			root.querySelectorAll('[data-audience-editor]').forEach(bind);
		},
		unbindAll
	};
})();
</script>
