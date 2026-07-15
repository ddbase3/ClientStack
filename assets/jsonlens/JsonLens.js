import { JsonLensEventBus } from './core/JsonLensEventBus.js';
import { JsonLensStateStore } from './core/JsonLensStateStore.js';
import { JsonLensPluginManager } from './core/JsonLensPluginManager.js';
import { JsonLensCommandRegistry } from './core/JsonLensCommandRegistry.js';
import { StringJsonAdapter } from './adapters/StringJsonAdapter.js';
import { ObjectJsonAdapter } from './adapters/ObjectJsonAdapter.js';
import { TreeViewPlugin } from './plugins/TreeViewPlugin.js';
import { SyntaxHighlightPlugin } from './plugins/SyntaxHighlightPlugin.js';
import { RawViewPlugin } from './plugins/RawViewPlugin.js';
import { ClipboardPlugin } from './plugins/ClipboardPlugin.js';
import { SearchPlugin } from './plugins/SearchPlugin.js';
import { PathPlugin } from './plugins/PathPlugin.js';
import { createButton, createElement, clearElement, resolveElement } from './utils/dom.js';
import { parseJson } from './utils/parseJson.js';

const DEFAULT_PLUGINS = [
	TreeViewPlugin,
	SyntaxHighlightPlugin,
	RawViewPlugin,
	ClipboardPlugin,
	SearchPlugin,
	PathPlugin
];

export class JsonLens {
	constructor(target, options = {}) {
		this.target = target ? resolveElement(target) : null;
		this.options = this.normalizeOptions(options);
		this.events = new JsonLensEventBus();
		this.store = new JsonLensStateStore(this.createInitialState());
		this.commands = new JsonLensCommandRegistry();
		this.views = new Map();
		this.root = null;
		this.body = null;
		this.initialized = false;
		this.pluginManager = new JsonLensPluginManager(this.createPluginContext());
	}

	normalizeOptions(options) {
		return {
			value: null,
			mode: 'tree',
			indent: 2,
			collapsedDepth: 2,
			showToolbar: true,
			className: '',
			adapter: null,
			plugins: DEFAULT_PLUGINS,
			pluginOptions: {},
			...options
		};
	}

	createInitialState() {
		return {
			value: this.options.value,
			result: null,
			mode: this.options.mode,
			activePath: '$',
			activeValue: null,
			search: {
				term: ''
			},
			tree: {
				expandMode: null,
				expandedPaths: {},
				collapsedPaths: {}
			}
		};
	}

	createPluginContext() {
		return {
			lens: this,
			store: this.store,
			events: this.events,
			commands: this.commands,
			getState: () => this.getState(),
			peekState: () => this.getState(),
			setState: (patch) => this.setState(patch),
			execute: (commandName, payload) => this.execute(commandName, payload),
			requestRender: () => this.requestRender(),
			requestViewRender: () => this.requestViewRender(),
			getOptions: () => this.options,
			getPluginOptions: (pluginName) => this.getPluginOptions(pluginName),
			registerView: (view) => this.registerView(view)
		};
	}

	init() {
		if (this.initialized) {
			return this;
		}

		if (!this.target) {
			throw new Error('JsonLens requires a DOM element or selector.');
		}

		this.registerCoreCommands();
		this.setValue(this.options.value, {
			render: false,
			resetTree: true
		});
		this.pluginManager.install(this.options.plugins);
		this.initialized = true;
		this.render();

		return this;
	}

	registerCoreCommands() {
		this.commands.register('setMode', (payload) => {
			const mode = typeof payload === 'string' ? payload : payload?.mode;

			if (!mode || !this.views.has(mode)) {
				return;
			}

			this.setState({ mode });
			this.requestRender();
		});

		this.commands.register('setValue', (payload) => {
			this.setValue(payload?.value ?? payload, {
				render: true,
				resetTree: true
			});
		});

		this.commands.register('expandAll', () => {
			this.setState({
				tree: {
					...this.getState().tree,
					expandMode: 'all',
					expandedPaths: {},
					collapsedPaths: {}
				}
			});
			this.requestRender();
		});

		this.commands.register('collapseAll', () => {
			this.setState({
				tree: {
					...this.getState().tree,
					expandMode: 'collapsed',
					expandedPaths: {},
					collapsedPaths: {}
				}
			});
			this.requestRender();
		});

		this.commands.register('setNodeCollapsed', (payload) => {
			if (!payload || !payload.path) {
				return;
			}

			const tree = this.getState().tree || {};
			const expandedPaths = { ...(tree.expandedPaths || {}) };
			const collapsedPaths = { ...(tree.collapsedPaths || {}) };

			if (payload.collapsed) {
				delete expandedPaths[payload.path];
				collapsedPaths[payload.path] = true;
			} else {
				delete collapsedPaths[payload.path];
				expandedPaths[payload.path] = true;
			}

			this.setState({
				tree: {
					...tree,
					expandMode: null,
					expandedPaths,
					collapsedPaths
				}
			});
			this.requestRender();
		});

		this.commands.register('setActivePath', (payload) => {
			this.setState({
				activePath: payload?.path || '$',
				activeValue: payload?.value
			});
			this.requestRender();
		});
	}

	setValue(value, options = {}) {
		const result = this.parseValue(value);
		const statePatch = {
			value,
			result,
			activePath: '$',
			activeValue: result.ok ? result.value : null
		};

		if (options.resetTree) {
			statePatch.tree = {
				expandMode: null,
				expandedPaths: {},
				collapsedPaths: {}
			};
		}

		this.setState(statePatch);
		this.events.emit(result.ok ? 'value:parsed' : 'value:error', result);

		if (options.render !== false && this.initialized) {
			this.requestRender();
		}
	}

	parseValue(value) {
		const adapter = this.options.adapter || this.createAdapter(value);

		if (!adapter || typeof adapter.parse !== 'function') {
			throw new Error('JsonLens adapter must provide a parse(value, options) method.');
		}

		return adapter.parse(value, {
			indent: this.options.indent
		});
	}

	createAdapter(value) {
		if (typeof value === 'string') {
			return new StringJsonAdapter();
		}

		return new ObjectJsonAdapter();
	}

	registerView(view) {
		if (!view || !view.name || typeof view.render !== 'function') {
			throw new Error('JsonLens views require name and render().');
		}

		this.views.set(view.name, view);
	}

	getPluginOptions(pluginName) {
		return this.options.pluginOptions?.[pluginName] || {};
	}

	getState() {
		return this.store.getState();
	}

	setState(patch) {
		this.store.setState(patch);
	}

	execute(commandName, payload) {
		return this.commands.execute(commandName, payload);
	}

	requestRender() {
		if (!this.initialized) {
			return;
		}

		this.render();
	}

	requestViewRender() {
		if (!this.initialized || !this.body) {
			return;
		}

		this.renderBody();
	}

	render() {
		clearElement(this.target);

		this.root = createElement('div', {
			className: this.buildRootClassName()
		});

		if (this.options.showToolbar) {
			this.root.appendChild(this.renderToolbar());
		}

		this.body = createElement('div', {
			className: 'jl-body'
		});

		this.renderBody();
		this.root.appendChild(this.body);
		this.target.appendChild(this.root);
	}

	renderBody() {
		clearElement(this.body);
		this.renderView(this.body);
	}

	buildRootClassName() {
		const classes = ['jl-root'];

		if (this.options.className) {
			classes.push(this.options.className);
		}

		return classes.join(' ');
	}

	renderToolbar() {
		const toolbar = createElement('div', {
			className: 'jl-toolbar',
			attrs: {
				role: 'toolbar'
			}
		});

		toolbar.appendChild(this.renderModeButtons());
		toolbar.appendChild(this.renderTreeButtons());

		for (const contribution of this.pluginManager.getLayoutContributions('toolbar')) {
			const element = contribution.render({
				lens: this,
				state: this.getState(),
				options: this.options
			});

			if (element) {
				toolbar.appendChild(element);
			}
		}

		return toolbar;
	}

	renderModeButtons() {
		const group = createElement('div', {
			className: 'jl-toolbar-group jl-mode-group'
		});
		const state = this.getState();
		const modes = [
			{ name: 'tree', label: 'Tree' },
			{ name: 'pretty', label: 'Pretty' },
			{ name: 'raw', label: 'Raw' }
		];

		for (const mode of modes) {
			if (!this.views.has(mode.name)) {
				continue;
			}

			group.appendChild(createButton({
				className: state.mode === mode.name ? 'jl-button jl-button-active' : 'jl-button',
				text: mode.label,
				attrs: {
					'data-jl-mode': mode.name
				},
				onClick: () => this.execute('setMode', mode.name)
			}));
		}

		return group;
	}

	renderTreeButtons() {
		const group = createElement('div', {
			className: 'jl-toolbar-group jl-tree-actions'
		});

		group.appendChild(createButton({
			className: 'jl-button',
			text: 'Expand all',
			onClick: () => this.execute('expandAll')
		}));

		group.appendChild(createButton({
			className: 'jl-button',
			text: 'Collapse all',
			onClick: () => this.execute('collapseAll')
		}));

		return group;
	}

	renderView(container) {
		const state = this.getState();
		const result = state.result;

		if (!result) {
			container.appendChild(createElement('div', {
				className: 'jl-empty',
				text: 'No JSON value.'
			}));
			return;
		}

		if (!result.ok && state.mode !== 'raw') {
			container.appendChild(this.renderError(result));
			return;
		}

		const view = this.views.get(state.mode) || this.views.get('raw');

		if (!view) {
			container.appendChild(createElement('div', {
				className: 'jl-empty',
				text: `No JsonLens view registered for mode "${state.mode}".`
			}));
			return;
		}

		const rendered = view.render(container, this, {
			state,
			result,
			options: this.options,
			context: this.createRenderContext()
		});

		if (rendered) {
			container.appendChild(rendered);
		}
	}

	createRenderContext() {
		return {
			getState: () => this.getState(),
			execute: (commandName, payload) => this.execute(commandName, payload),
			options: this.options
		};
	}

	renderError(result) {
		const error = createElement('div', {
			className: 'jl-error'
		});

		error.appendChild(createElement('div', {
			className: 'jl-error-title',
			text: 'Invalid JSON'
		}));
		error.appendChild(createElement('div', {
			className: 'jl-error-message',
			text: result.error
		}));
		error.appendChild(createElement('pre', {
			className: 'jl-raw jl-error-raw',
			text: result.raw
		}));

		return error;
	}

	destroy() {
		this.pluginManager.destroy();
		this.commands.clear();
		this.events.clear();

		if (this.target) {
			clearElement(this.target);
		}

		this.initialized = false;
	}

	static canParse(value) {
		if (typeof value === 'string') {
			return parseJson(value).ok;
		}

		try {
			JSON.stringify(value);
			return value !== undefined;
		} catch (error) {
			return false;
		}
	}

	static createElement(options = {}) {
		const host = createElement('div', {
			className: 'jl-host'
		});
		const lens = new JsonLens(host, options);

		lens.init();
		host.__jsonLens = lens;

		return host;
	}
}
