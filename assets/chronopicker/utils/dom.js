export function resolveElement(target) {
	if (typeof target === 'string') {
		const element = document.querySelector(target);

		if (!element) {
			throw new Error(`ChronoPicker target not found: ${target}`);
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

	if (typeof options.onChange === 'function') {
		element.addEventListener('change', options.onChange);
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
		className: options.className || 'cp-button',
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
