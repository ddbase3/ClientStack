import { parseJson } from '../utils/parseJson.js';

export class StringJsonAdapter {
	canHandle(value) {
		return typeof value === 'string';
	}

	parse(value, options = {}) {
		return parseJson(value, options);
	}
}
