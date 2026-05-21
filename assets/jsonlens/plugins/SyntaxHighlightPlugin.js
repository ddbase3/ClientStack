import { createElement } from '../utils/dom.js';
import { renderJsonTokens } from '../utils/renderTokens.js';

export const SyntaxHighlightPlugin = {
	name: 'syntaxHighlight',

	views: [
		{
			name: 'pretty',
			label: 'Pretty',
			render(container, lens, renderContext) {
				const pre = createElement('pre', {
					className: 'jl-pretty'
				});
				const code = createElement('code', {
					className: 'jl-code'
				});
				const search = renderContext.state.search || {};

				code.appendChild(renderJsonTokens(renderContext.result.formatted, {
					searchTerm: search.term || ''
				}));
				pre.appendChild(code);

				return pre;
			}
		}
	]
};
