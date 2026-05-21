import { createButton, createElement, createHighlightedText } from './utils/dom.js';
import { toChildPath } from './utils/jsonPath.js';

export class JsonLensTree {
	constructor(options = {}) {
		this.options = options;
	}

	render(value, context = {}) {
		return JsonLensTree.render(value, this.options, context);
	}

	static render(value, options = {}, context = {}) {
		const tree = createElement('div', {
			className: 'jl-tree',
			attrs: {
				role: 'tree'
			}
		});

		tree.appendChild(this.renderNode({
			key: null,
			value,
			path: '$',
			depth: 0,
			options,
			context
		}));

		return tree;
	}

	static renderNode({ key, value, path, depth, options, context }) {
		const expandable = this.isExpandable(value);
		const collapsed = this.isCollapsed(path, depth, value, options, context);
		const node = createElement('div', {
			className: this.getNodeClassName(value, collapsed),
			attrs: {
				role: 'treeitem',
				'aria-expanded': expandable ? String(!collapsed) : undefined
			},
			dataset: {
				path
			}
		});
		const line = createElement('div', {
			className: 'jl-node-line',
			onClick: () => {
				context.execute?.('setActivePath', {
					path,
					value
				});
			}
		});

		line.style.paddingLeft = `${depth * 16}px`;

		if (expandable) {
			line.appendChild(createButton({
				className: 'jl-node-toggle',
				text: collapsed ? '+' : '-',
				title: collapsed ? 'Expand node' : 'Collapse node',
				attrs: {
					'aria-label': collapsed ? 'Expand node' : 'Collapse node'
				},
				onClick: (event) => {
					event.stopPropagation();
					context.execute?.('setNodeCollapsed', {
						path,
						collapsed: !collapsed
					});
				}
			}));
		} else {
			line.appendChild(createElement('span', {
				className: 'jl-node-spacer'
			}));
		}

		if (key !== null) {
			line.appendChild(this.renderKey(key, options));
		}

		if (expandable) {
			line.appendChild(this.renderSummary(value, collapsed));
		} else {
			line.appendChild(this.renderPrimitive(value, options));
		}

		node.appendChild(line);

		if (expandable && !collapsed) {
			const children = createElement('div', {
				className: 'jl-node-children',
				attrs: {
					role: 'group'
				}
			});

			for (const child of this.getChildren(value)) {
				children.appendChild(this.renderNode({
					key: child.key,
					value: child.value,
					path: toChildPath(path, child.key),
					depth: depth + 1,
					options,
					context
				}));
			}

			node.appendChild(children);
		}

		return node;
	}

	static renderKey(key, options) {
		const label = createElement('span', {
			className: 'jl-node-key'
		});

		label.appendChild(createHighlightedText(String(key), options.searchTerm || ''));
		label.appendChild(document.createTextNode(': '));

		return label;
	}

	static renderSummary(value, collapsed) {
		const summary = createElement('span', {
			className: 'jl-node-summary'
		});

		if (Array.isArray(value)) {
			summary.textContent = collapsed ? `Array(${value.length})` : '[';
			return summary;
		}

		const keys = Object.keys(value);
		summary.textContent = collapsed ? `Object(${keys.length})` : '{';

		return summary;
	}

	static renderPrimitive(value, options) {
		const token = createElement('span', {
			className: `jl-token jl-token-${this.getPrimitiveType(value)}`
		});
		const text = this.formatPrimitive(value);

		token.appendChild(createHighlightedText(text, options.searchTerm || ''));

		return token;
	}

	static isExpandable(value) {
		return value !== null && typeof value === 'object';
	}

	static isCollapsed(path, depth, value, options, context) {
		if (!this.isExpandable(value) || path === '$') {
			return false;
		}

		const state = context.getState?.() || {};
		const tree = state.tree || {};
		const expandedPaths = tree.expandedPaths || {};
		const collapsedPaths = tree.collapsedPaths || {};

		if (expandedPaths[path]) {
			return false;
		}

		if (collapsedPaths[path]) {
			return true;
		}

		if (tree.expandMode === 'all') {
			return false;
		}

		if (tree.expandMode === 'collapsed') {
			return true;
		}

		return depth >= (options.collapsedDepth ?? 2);
	}

	static getChildren(value) {
		if (Array.isArray(value)) {
			return value.map((item, index) => ({
				key: index,
				value: item
			}));
		}

		return Object.keys(value).map((key) => ({
			key,
			value: value[key]
		}));
	}

	static getNodeClassName(value, collapsed) {
		const classes = ['jl-node'];

		if (this.isExpandable(value)) {
			classes.push('jl-node-expandable');
		}

		if (collapsed) {
			classes.push('jl-node-collapsed');
		}

		return classes.join(' ');
	}

	static getPrimitiveType(value) {
		if (value === null) {
			return 'null';
		}

		if (Array.isArray(value)) {
			return 'array';
		}

		return typeof value;
	}

	static formatPrimitive(value) {
		if (typeof value === 'string') {
			return JSON.stringify(value);
		}

		if (value === undefined) {
			return 'undefined';
		}

		return String(value);
	}
}
