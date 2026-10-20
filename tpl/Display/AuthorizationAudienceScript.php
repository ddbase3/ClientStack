<script>
(() => {
	if (window.BASE3AudienceEditor) return;
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
		row.dataset.kind = kind;
		const role = row.querySelector('[data-audience-role]');
		const numberActive = kind === 'user' || (kind === 'role' && !role);
		row.querySelector('[data-audience-number-wrap]').hidden = !numberActive;
		const roleWrap = row.querySelector('[data-audience-role-wrap]');
		if (roleWrap) roleWrap.hidden = kind !== 'role';
		for (const type of ['group', 'criterion']) {
			const wrap = row.querySelector('[data-audience-' + type + '-wrap]');
			if (wrap) wrap.hidden = kind !== type;
		}
		const number = row.querySelector('[data-audience-number]');
		const group = row.querySelector('[data-audience-group]');
		const criterion = row.querySelector('[data-audience-criterion]');
		const config = row.querySelector('[data-audience-json]');
		const configActive = kind === 'expression' || (kind === 'criterion' && criterion.value !== '');
		row.querySelector('[data-audience-config-wrap]').hidden = !configActive;
		number.disabled = !numberActive;
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
		for (const row of editor.querySelectorAll('[data-audience-list] > [data-audience-row]')) configureRow(editor, row);
		editor.addEventListener('change', (event) => {
			const row = event.target.closest('[data-audience-row]');
			if (!row) return;
			if (event.target.matches('[data-audience-kind]')) configureRow(editor, row, true);
			if (event.target.matches('[data-audience-criterion]')) configureRow(editor, row, true);
		});
		editor.addEventListener('click', (event) => {
			const remove = event.target.closest('[data-audience-remove]');
			if (remove) { remove.closest('[data-audience-row]').remove(); return; }
			if (!event.target.closest('[data-audience-add]')) return;
			const template = editor.querySelector('[data-audience-template]');
			const fragment = template.content.cloneNode(true);
			const index = String(Number(editor.dataset.counter || 0));
			editor.dataset.counter = String(Number(index) + 1);
			for (const input of fragment.querySelectorAll('[name]')) input.name = input.name.replace('__INDEX__', index);
			const row = fragment.querySelector('[data-audience-row]');
			editor.querySelector('[data-audience-list]').append(fragment);
			configureRow(editor, row, true);
			row.querySelector('[data-audience-kind]').focus();
		});
	}
	window.BASE3AudienceEditor = {bindAll(root) {
		if (root.matches && root.matches('[data-audience-editor]')) bind(root);
		root.querySelectorAll('[data-audience-editor]').forEach(bind);
	}};
})();
</script>
