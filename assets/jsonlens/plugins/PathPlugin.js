import { createElement } from '../utils/dom.js';

export const PathPlugin = {
	name: 'path',

	layoutContributions() {
		return [
			{
				zone: 'toolbar',
				order: 80,
				render({ state }) {
					return createElement('div', {
						className: 'jl-path-display',
						text: `Path: ${state.activePath || '$'}`
					});
				}
			}
		];
	}
};
