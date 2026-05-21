import { createElement } from '../utils/dom.js';
import { pad2 } from '../utils/dateMath.js';

export const DateTimePlugin = {
	name: 'dateTime',

	layoutContributions(context) {
		return [
			{
				zone: 'footer',
				order: 10,
				render({ state, options }) {
					if (options.mode !== 'datetime') {
						return null;
					}

					return renderTimeControls(context, state, options);
				}
			}
		];
	}
};

function renderTimeControls(context, state, options) {
	const selected = state.selectedDate || new Date(state.viewYear, state.viewMonth - 1, 1, 0, 0);
	const wrapper = createElement('div', {
		className: 'cp-time-controls'
	});
	const hourSelect = createElement('select', {
		className: 'cp-time-select',
		attrs: {
			'aria-label': 'Hour'
		},
		onChange: (event) => context.execute('setTime', {
			hour: Number(event.target.value),
			minute: selected.getMinutes()
		})
	});
	const minuteSelect = createElement('select', {
		className: 'cp-time-select',
		attrs: {
			'aria-label': 'Minute'
		},
		onChange: (event) => context.execute('setTime', {
			hour: selected.getHours(),
			minute: Number(event.target.value)
		})
	});

	for (let hour = 0; hour < 24; hour++) {
		hourSelect.appendChild(createOption(hour, pad2(hour), selected.getHours() === hour));
	}

	for (let minute = 0; minute < 60; minute += options.minuteStep || 1) {
		minuteSelect.appendChild(createOption(minute, pad2(minute), selected.getMinutes() === minute));
	}

	wrapper.appendChild(createElement('span', {
		className: 'cp-time-label',
		text: 'Time'
	}));
	wrapper.appendChild(hourSelect);
	wrapper.appendChild(createElement('span', {
		className: 'cp-time-separator',
		text: ':'
	}));
	wrapper.appendChild(minuteSelect);

	return wrapper;
}

function createOption(value, label, selected) {
	const option = document.createElement('option');
	option.value = String(value);
	option.textContent = label;

	if (selected) {
		option.selected = true;
	}

	return option;
}
