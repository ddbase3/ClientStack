import { ChronoPicker } from '../ChronoPicker.js';
import { createElement } from '../utils/dom.js';

export class ModularGridFilterAdapter {
	constructor(options = {}) {
		this.ChronoPicker = options.ChronoPicker || ChronoPicker;
		this.defaultMode = options.defaultMode || 'date';
		this.defaultFormat = options.defaultFormat || null;
	}

	createControl(field, options = {}) {
		const wrapper = createElement('label', {
			className: 'cp-filter-control'
		});
		const title = createElement('span', {
			className: 'cp-filter-label',
			text: field.label || field.key || 'Date'
		});
		const input = createElement('input', {
			className: 'cp-filter-input',
			attrs: {
				type: 'text',
				name: field.key || '',
				placeholder: field.format || this.getDefaultFormat(field)
			}
		});

		wrapper.appendChild(title);
		wrapper.appendChild(input);

		const picker = new this.ChronoPicker(input, {
			mode: field.mode || this.defaultMode,
			displayMode: 'popover',
			format: field.format || this.getDefaultFormat(field),
			value: options.value || '',
			onChange: (value, context) => {
				if (typeof options.onChange === 'function') {
					options.onChange({
						field,
						key: field.key,
						value,
						context
					});
				}
			}
		});

		picker.init();

		return {
			element: wrapper,
			input,
			picker
		};
	}

	getDefaultFormat(field) {
		if (this.defaultFormat) {
			return this.defaultFormat;
		}

		return (field.mode || this.defaultMode) === 'datetime' ? 'YYYY-MM-DD HH:mm' : 'YYYY-MM-DD';
	}
}
