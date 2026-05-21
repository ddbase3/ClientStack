import { createHighlightedText } from './dom.js';

export function renderJsonTokens(jsonText, options = {}) {
	const fragment = document.createDocumentFragment();
	const text = jsonText == null ? '' : String(jsonText);
	const pattern = /("(?:\\u[a-fA-F0-9]{4}|\\[^u]|[^\\"])*"|true|false|null|-?\d+(?:\.\d+)?(?:[eE][+-]?\d+)?)/g;
	let lastIndex = 0;
	let match = pattern.exec(text);

	while (match) {
		if (match.index > lastIndex) {
			fragment.appendChild(document.createTextNode(text.slice(lastIndex, match.index)));
		}

		const token = match[0];
		const span = document.createElement('span');
		span.className = `jl-token jl-token-${getTokenType(text, token, match.index)}`;
		span.appendChild(createHighlightedText(token, options.searchTerm || ''));
		fragment.appendChild(span);

		lastIndex = match.index + token.length;
		match = pattern.exec(text);
	}

	if (lastIndex < text.length) {
		fragment.appendChild(document.createTextNode(text.slice(lastIndex)));
	}

	return fragment;
}

function getTokenType(text, token, index) {
	if (token[0] === '"') {
		const rest = text.slice(index + token.length);
		return /^\s*:/.test(rest) ? 'key' : 'string';
	}

	if (token === 'true' || token === 'false') {
		return 'boolean';
	}

	if (token === 'null') {
		return 'null';
	}

	return 'number';
}
