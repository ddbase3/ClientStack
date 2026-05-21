import { createElement } from '../utils/dom.js';

export const RawViewPlugin = {
	name: 'rawView',

	views: [
		{
			name: 'raw',
			label: 'Raw',
			render(container, lens, renderContext) {
				return createElement('pre', {
					className: 'jl-raw',
					text: renderContext.result.raw
				});
			}
		}
	]
};
