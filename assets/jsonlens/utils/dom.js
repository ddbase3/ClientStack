export function resolveElement(target) {
	if (typeof target === 'string') {
		const element = document.querySelector(target);

		if (!element) {
			throw new Error(`JsonLens target not found: ${target}`);
		}

		return element;
	}

	return target;
}

export function clearElement(element) {
	while (element.firstChild) {
		element.removeChild(element.firstChild);
	}
}

export function createElement(tagName, options = {}) {
	const element = document.createElement(tagName);

	if (options.className) {
		element.className = options.className;
	}

	if (options.text !== undefined) {
		element.textContent = options.text;
	}

	if (options.attrs) {
		for (const [name, value] of Object.entries(options.attrs)) {
			if (value === undefined || value === null) {
				continue;
			}

			element.setAttribute(name, String(value));
		}
	}

	if (options.dataset) {
		for (const [name, value] of Object.entries(options.dataset)) {
			if (value === undefined || value === null) {
				continue;
			}

			element.dataset[name] = String(value);
		}
	}

	if (typeof options.onClick === 'function') {
		element.addEventListener('click', options.onClick);
	}

	if (typeof options.onInput === 'function') {
		element.addEventListener('input', options.onInput);
	}

	if (options.children) {
		appendChildren(element, options.children);
	}

	return element;
}

export function createButton(options = {}) {
	return createElement('button', {
		className: options.className || 'jl-button',
		text: options.text || '',
		attrs: {
			type: 'button',
			title: options.title,
			disabled: options.disabled ? 'disabled' : undefined,
			...(options.attrs || {})
		},
		dataset: options.dataset,
		onClick: options.onClick
	});
}

export function appendChildren(element, children) {
	for (const child of children) {
		if (child === null || child === undefined) {
			continue;
		}

		if (typeof child === 'string') {
			element.appendChild(document.createTextNode(child));
			continue;
		}

		element.appendChild(child);
	}
}

export function createHighlightedText(value, searchTerm) {
	const fragment = document.createDocumentFragment();
	const text = value == null ? '' : String(value);

	if (!searchTerm) {
		fragment.appendChild(document.createTextNode(text));
		return fragment;
	}

	const lowerText = text.toLowerCase();
	const lowerSearch = searchTerm.toLowerCase();
	let position = 0;
	let nextIndex = lowerText.indexOf(lowerSearch, position);

	while (nextIndex !== -1) {
		if (nextIndex > position) {
			fragment.appendChild(document.createTextNode(text.slice(position, nextIndex)));
		}

		const mark = document.createElement('mark');
		mark.className = 'jl-search-hit';
		mark.textContent = text.slice(nextIndex, nextIndex + searchTerm.length);
		fragment.appendChild(mark);

		position = nextIndex + searchTerm.length;
		nextIndex = lowerText.indexOf(lowerSearch, position);
	}

	if (position < text.length) {
		fragment.appendChild(document.createTextNode(text.slice(position)));
	}

	return fragment;
}
