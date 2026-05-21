import { ChronoEventBus } from './core/ChronoEventBus.js';
import { ChronoStateStore } from './core/ChronoStateStore.js';
import { ChronoPluginManager } from './core/ChronoPluginManager.js';
import { ChronoCommandRegistry } from './core/ChronoCommandRegistry.js';
import { NativeInputAdapter } from './adapters/NativeInputAdapter.js';
import { DatePickerPlugin } from './plugins/DatePickerPlugin.js';
import { DateTimePlugin } from './plugins/DateTimePlugin.js';
import { KeyboardPlugin } from './plugins/KeyboardPlugin.js';
import { createButton, createElement, clearElement, resolveElement } from './utils/dom.js';
import { addMonths, createLocalDate, monthNames } from './utils/dateMath.js';
import { formatChronoValue } from './utils/format.js';
import { parseChronoValue } from './utils/parse.js';

const DEFAULT_PLUGINS = [
	DatePickerPlugin,
	DateTimePlugin,
	KeyboardPlugin
];

export class ChronoPicker {
	constructor(target, options = {}) {
		this.target = target ? resolveElement(target) : null;
		this.options = this.normalizeOptions(options);
		this.events = new ChronoEventBus();
		this.store = new ChronoStateStore(this.createInitialState());
		this.commands = new ChronoCommandRegistry();
		this.inputAdapter = new NativeInputAdapter();
		this.root = null;
		this.popover = null;
		this.initialized = false;
		this.outsideClickHandler = null;
		this.inputEventDisposers = [];
		this.pluginManager = new ChronoPluginManager(this.createPluginContext());
	}

	normalizeOptions(options) {
		const mode = options.mode || 'date';
		const displayMode = options.displayMode || 'auto';
		const format = options.format || (mode === 'datetime' ? 'YYYY-MM-DD HH:mm' : 'YYYY-MM-DD');

		return {
			mode,
			displayMode,
			value: '',
			format,
			weekStartsOn: 1,
			minuteStep: 1,
			showHeader: true,
			showFooter: true,
			closeOnSelect: mode === 'date',
			placeholder: format,
			className: '',
			min: null,
			max: null,
			plugins: DEFAULT_PLUGINS,
			pluginOptions: {},
			onChange: null,
			...options,
			mode,
			displayMode,
			format
		};
	}

	createInitialState() {
		const now = new Date();

		return {
			value: '',
			selectedDate: null,
			viewYear: now.getFullYear(),
			viewMonth: now.getMonth() + 1,
			open: false,
			parseError: '',
			mode: this.options.mode
		};
	}

	createPluginContext() {
		return {
			picker: this,
			store: this.store,
			events: this.events,
			commands: this.commands,
			getState: () => this.getState(),
			setState: (patch) => this.setState(patch),
			execute: (commandName, payload) => this.execute(commandName, payload),
			requestRender: () => this.requestRender(),
			getOptions: () => this.options,
			getPluginOptions: (pluginName) => this.getPluginOptions(pluginName)
		};
	}

	init() {
		if (this.initialized) {
			return this;
		}

		if (!this.target) {
			throw new Error('ChronoPicker requires a DOM element or selector.');
		}

		this.registerCoreCommands();
		this.setupTarget();
		this.setValue(this.readInitialValue(), {
			render: false,
			silent: true,
			writeInput: this.isInputMode()
		});
		this.pluginManager.install(this.options.plugins);
		this.initialized = true;
		this.render();

		return this;
	}

	setupTarget() {
		if (this.isInputMode()) {
			this.inputAdapter.prepare(this.target, {
				placeholder: this.options.placeholder
			});
			this.root = createElement('div', {
				className: this.buildRootClassName('popover')
			});
			this.root.hidden = true;
			document.body.appendChild(this.root);
			this.bindInputTarget();
			return;
		}

		this.root = createElement('div', {
			className: this.buildRootClassName('inline')
		});
	}

	bindInputTarget() {
		this.inputEventDisposers.push(this.inputAdapter.on(this.target, 'focus', () => this.execute('open')));
		this.inputEventDisposers.push(this.inputAdapter.on(this.target, 'click', () => this.execute('open')));
		this.inputEventDisposers.push(this.inputAdapter.on(this.target, 'input', () => {
			this.setValue(this.inputAdapter.read(this.target), {
				render: true,
				silent: true,
				writeInput: false
			});
		}));

		this.outsideClickHandler = (event) => {
			if (!this.root || !this.getState().open) {
				return;
			}

			if (event.target === this.target || this.root.contains(event.target)) {
				return;
			}

			this.execute('close');
		};

		document.addEventListener('mousedown', this.outsideClickHandler);
	}

	readInitialValue() {
		if (this.options.value) {
			return this.options.value;
		}

		if (this.isInputMode()) {
			return this.inputAdapter.read(this.target);
		}

		return '';
	}

	registerCoreCommands() {
		this.commands.register('open', () => {
			this.setState({ open: true });
			this.requestRender();
		});

		this.commands.register('close', () => {
			this.setState({ open: false });
			this.requestRender();
		});

		this.commands.register('toggle', () => {
			this.setState({ open: !this.getState().open });
			this.requestRender();
		});

		this.commands.register('previousMonth', () => {
			this.moveViewMonth(-1);
		});

		this.commands.register('nextMonth', () => {
			this.moveViewMonth(1);
		});

		this.commands.register('setValue', (payload) => {
			this.setValue(payload?.value ?? payload, {
				render: true,
				silent: false,
				writeInput: true
			});
		});

		this.commands.register('selectDate', (payload) => {
			if (!payload?.date) {
				return;
			}

			const state = this.getState();
			const current = state.selectedDate || payload.date;
			const selectedDate = createLocalDate(
				payload.date.getFullYear(),
				payload.date.getMonth() + 1,
				payload.date.getDate(),
				current.getHours(),
				current.getMinutes()
			);

			this.applySelectedDate(selectedDate, {
				close: this.options.closeOnSelect,
				writeInput: true
			});
		});

		this.commands.register('setTime', (payload) => {
			const state = this.getState();
			const base = state.selectedDate || createLocalDate(state.viewYear, state.viewMonth, 1);
			const selectedDate = createLocalDate(
				base.getFullYear(),
				base.getMonth() + 1,
				base.getDate(),
				payload?.hour ?? base.getHours(),
				payload?.minute ?? base.getMinutes()
			);

			this.applySelectedDate(selectedDate, {
				close: false,
				writeInput: true
			});
		});

		this.commands.register('today', () => {
			this.applySelectedDate(new Date(), {
				close: this.options.closeOnSelect,
				writeInput: true
			});
		});

		this.commands.register('clear', () => {
			this.setState({
				value: '',
				selectedDate: null,
				parseError: ''
			});

			if (this.isInputMode()) {
				this.inputAdapter.write(this.target, '');
			}

			this.emitChange('', null);
			this.requestRender();
		});
	}

	moveViewMonth(offset) {
		const state = this.getState();
		const next = addMonths(createLocalDate(state.viewYear, state.viewMonth, 1), offset);

		this.setState({
			viewYear: next.getFullYear(),
			viewMonth: next.getMonth() + 1
		});
		this.requestRender();
	}

	setValue(value, options = {}) {
		const result = parseChronoValue(value, {
			mode: this.options.mode,
			format: this.options.format
		});

		if (!result.ok) {
			this.setState({
				value: value == null ? '' : String(value),
				selectedDate: null,
				parseError: result.error
			});

			if (options.render !== false && this.initialized) {
				this.requestRender();
			}

			return;
		}

		if (!result.date) {
			this.setState({
				value: '',
				selectedDate: null,
				parseError: ''
			});

			if (options.writeInput && this.isInputMode()) {
				this.inputAdapter.write(this.target, '');
			}

			if (options.render !== false && this.initialized) {
				this.requestRender();
			}

			return;
		}

		this.applySelectedDate(result.date, {
			close: false,
			silent: options.silent,
			writeInput: options.writeInput,
			render: options.render
		});
	}

	applySelectedDate(date, options = {}) {
		const value = formatChronoValue(date, this.options.format);

		this.setState({
			value,
			selectedDate: date,
			viewYear: date.getFullYear(),
			viewMonth: date.getMonth() + 1,
			parseError: '',
			open: options.close ? false : this.getState().open
		});

		if (options.writeInput && this.isInputMode()) {
			this.inputAdapter.write(this.target, value);
		}

		if (!options.silent) {
			this.emitChange(value, date);
		}

		if (options.render !== false && this.initialized) {
			this.requestRender();
		}
	}

	emitChange(value, date) {
		const context = {
			picker: this,
			date,
			mode: this.options.mode,
			format: this.options.format
		};

		this.events.emit('change', {
			value,
			date,
			context
		});

		if (typeof this.options.onChange === 'function') {
			this.options.onChange(value, context);
		}
	}

	getValue() {
		return this.getState().value;
	}

	getDate() {
		return this.getState().selectedDate;
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

	getPluginOptions(pluginName) {
		return this.options.pluginOptions?.[pluginName] || {};
	}

	requestRender() {
		if (!this.initialized) {
			return;
		}

		this.render();
	}

	render() {
		if (!this.root) {
			return;
		}

		clearElement(this.root);
		this.root.className = this.buildRootClassName(this.isInputMode() ? 'popover' : 'inline');
		this.root.hidden = this.isInputMode() && !this.getState().open;

		if (this.isInputMode() && this.getState().open) {
			this.positionPopover();
		}

		if (this.options.showHeader) {
			this.root.appendChild(this.renderHeader());
		}

		this.root.appendChild(this.renderZone('main'));

		if (this.options.showFooter) {
			this.root.appendChild(this.renderFooter());
		}

		if (!this.isInputMode() && !this.root.parentNode) {
			this.target.appendChild(this.root);
		}
	}

	renderHeader() {
		const state = this.getState();
		const header = createElement('div', {
			className: 'cp-header'
		});

		header.appendChild(createButton({
			className: 'cp-button cp-nav-button',
			text: '‹',
			title: 'Previous month',
			onClick: () => this.execute('previousMonth')
		}));
		header.appendChild(createElement('div', {
			className: 'cp-title',
			text: `${monthNames[state.viewMonth - 1]} ${state.viewYear}`
		}));
		header.appendChild(createButton({
			className: 'cp-button cp-nav-button',
			text: '›',
			title: 'Next month',
			onClick: () => this.execute('nextMonth')
		}));

		return header;
	}

	renderZone(zone) {
		const element = createElement('div', {
			className: `cp-zone cp-zone-${zone}`
		});

		for (const contribution of this.pluginManager.getLayoutContributions(zone)) {
			const rendered = contribution.render({
				picker: this,
				state: this.getState(),
				options: this.options
			});

			if (rendered) {
				element.appendChild(rendered);
			}
		}

		return element;
	}

	renderFooter() {
		const footer = createElement('div', {
			className: 'cp-footer'
		});
		const pluginFooter = this.renderZone('footer');

		if (pluginFooter.childNodes.length > 0) {
			footer.appendChild(pluginFooter);
		}

		const actions = createElement('div', {
			className: 'cp-actions'
		});

		actions.appendChild(createButton({
			className: 'cp-button',
			text: 'Today',
			onClick: () => this.execute('today')
		}));
		actions.appendChild(createButton({
			className: 'cp-button',
			text: 'Clear',
			onClick: () => this.execute('clear')
		}));

		if (this.isInputMode()) {
			actions.appendChild(createButton({
				className: 'cp-button cp-primary-button',
				text: 'Done',
				onClick: () => this.execute('close')
			}));
		}

		footer.appendChild(actions);

		if (this.getState().parseError) {
			footer.appendChild(createElement('div', {
				className: 'cp-error',
				text: this.getState().parseError
			}));
		}

		return footer;
	}

	positionPopover() {
		const rect = this.target.getBoundingClientRect();
		const scrollX = window.pageXOffset || document.documentElement.scrollLeft;
		const scrollY = window.pageYOffset || document.documentElement.scrollTop;

		this.root.style.position = 'absolute';
		this.root.style.left = `${rect.left + scrollX}px`;
		this.root.style.top = `${rect.bottom + scrollY + 4}px`;
		this.root.style.zIndex = '10000';
		this.root.style.minWidth = `${Math.max(rect.width, 280)}px`;
	}

	isInputMode() {
		if (this.options.displayMode === 'inline') {
			return false;
		}

		if (this.options.displayMode === 'popover') {
			return true;
		}

		return this.inputAdapter.canHandle(this.target);
	}

	buildRootClassName(displayMode) {
		const classes = [
			'cp-root',
			`cp-mode-${this.options.mode}`,
			`cp-display-${displayMode}`
		];

		if (this.getState().open) {
			classes.push('cp-open');
		}

		if (this.options.className) {
			classes.push(this.options.className);
		}

		return classes.join(' ');
	}

	destroy() {
		this.pluginManager.destroy();
		this.commands.clear();
		this.events.clear();

		for (const dispose of this.inputEventDisposers) {
			dispose();
		}

		this.inputEventDisposers = [];

		if (this.outsideClickHandler) {
			document.removeEventListener('mousedown', this.outsideClickHandler);
			this.outsideClickHandler = null;
		}

		if (this.root && this.root.parentNode) {
			this.root.parentNode.removeChild(this.root);
		}

		this.root = null;
		this.initialized = false;
	}
}
