import { JsonLensTree } from '../JsonLensTree.js';

export const TreeViewPlugin = {
	name: 'treeView',

	views: [
		{
			name: 'tree',
			label: 'Tree',
			render(container, lens, renderContext) {
				const search = renderContext.state.search || {};

				return JsonLensTree.render(renderContext.result.value, {
					collapsedDepth: renderContext.options.collapsedDepth,
					searchTerm: search.term || ''
				}, renderContext.context);
			}
		}
	]
};
