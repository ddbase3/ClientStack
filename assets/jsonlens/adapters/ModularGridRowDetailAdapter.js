import { JsonLens } from '../JsonLens.js';
import { createElement } from '../utils/dom.js';

export class ModularGridRowDetailAdapter {
	constructor(options = {}) {
		this.JsonLens = options.JsonLens || JsonLens;
		this.jsonLabels = options.jsonLabels || ['Arguments JSON', 'Result JSON', 'Request Payload', 'Response Payload'];
		this.mode = options.mode || 'tree';
		this.collapsedDepth = options.collapsedDepth ?? 2;
		this.showToolbar = options.showToolbar ?? true;
		this.plugins = options.plugins || null;
	}

	renderSections(sections = []) {
		const container = createElement('div', {
			className: 'jl-row-detail-sections'
		});

		for (const section of sections) {
			container.appendChild(this.renderSection(section));
		}

		return container;
	}

	renderSection(section) {
		const wrapper = createElement('section', {
			className: 'jl-row-detail-section'
		});
		const label = section?.label || '';
		const value = section?.value ?? '';

		wrapper.appendChild(createElement('h4', {
			className: 'jl-row-detail-section-title',
			text: label
		}));
		wrapper.appendChild(this.renderSectionValue({ label, value }));

		return wrapper;
	}

	renderSectionValue(section) {
		if (this.shouldRenderJson(section)) {
			return this.JsonLens.createElement({
				value: section.value,
				mode: this.mode,
				collapsedDepth: this.collapsedDepth,
				showToolbar: this.showToolbar,
				plugins: this.plugins || undefined
			});
		}

		return createElement('div', {
			className: 'jl-row-detail-text',
			text: section.value == null ? '' : String(section.value)
		});
	}

	shouldRenderJson(section) {
		if (!section) {
			return false;
		}

		if (this.jsonLabels.includes(section.label)) {
			return this.JsonLens.canParse(section.value);
		}

		return this.JsonLens.canParse(section.value);
	}
}
