import { createButton, createElement } from '../utils/dom.js';
import { rawJsonFromValue } from '../utils/formatJson.js';

export const ClipboardPlugin = {
	name: 'clipboard',

	commands: {
		copyAll(context) {
			const state = context.getState();
			const raw = state.result?.raw ?? '';

			return copyText(raw).then(() => {
				context.events.emit('copy:done', {
					type: 'all'
				});
			});
		},

		copyPath(context) {
			const state = context.getState();

			return copyText(state.activePath || '$').then(() => {
				context.events.emit('copy:done', {
					type: 'path'
				});
			});
		},

		copyValue(context) {
			const state = context.getState();
			const value = state.activeValue;
			const text = typeof value === 'string' ? value : rawJsonFromValue(value, context.getOptions().indent ?? 2);

			return copyText(text).then(() => {
				context.events.emit('copy:done', {
					type: 'value'
				});
			});
		}
	},

	layoutContributions(context) {
		return [
			{
				zone: 'toolbar',
				order: 60,
				render() {
					const group = createElement('div', {
						className: 'jl-toolbar-group jl-copy-actions'
					});

					group.appendChild(createButton({
						className: 'jl-button',
						text: 'Copy',
						title: 'Copy complete JSON',
						onClick: () => context.execute('copyAll')
					}));
					group.appendChild(createButton({
						className: 'jl-button',
						text: 'Copy path',
						title: 'Copy active JSON path',
						onClick: () => context.execute('copyPath')
					}));
					group.appendChild(createButton({
						className: 'jl-button',
						text: 'Copy value',
						title: 'Copy active value',
						onClick: () => context.execute('copyValue')
					}));

					return group;
				}
			}
		];
	}
};

function copyText(text) {
	if (typeof navigator !== 'undefined' && navigator.clipboard?.writeText) {
		return navigator.clipboard.writeText(text);
	}

	if (typeof document === 'undefined') {
		return Promise.resolve(text);
	}

	const textarea = document.createElement('textarea');
	textarea.value = text;
	textarea.setAttribute('readonly', 'readonly');
	textarea.style.position = 'fixed';
	textarea.style.left = '-9999px';
	document.body.appendChild(textarea);
	textarea.select();
	document.execCommand('copy');
	document.body.removeChild(textarea);

	return Promise.resolve(text);
}
