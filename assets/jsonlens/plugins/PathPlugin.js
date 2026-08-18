import { createElement } from '../utils/dom.js';

export const PathPlugin = {
	name: 'path',

	layoutContributions(context) {
		return [
			{
				zone: 'toolbar',
				order: 80,
				render({ state }) {
					return createElement('div', {
						className: 'jl-path-display',
						text: context.getString('path', { path: state.activePath || '$' })
					});
				},
				update(element, { state }) {
					element.textContent = context.getString('path', { path: state.activePath || '$' });
				}
			}
		];
	}
};
