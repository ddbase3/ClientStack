import { formatJson, rawJsonFromValue } from './utils/formatJson.js';
import { parseJson } from './utils/parseJson.js';

export class JsonLensFormatter {
	static parse(value, options = {}) {
		return parseJson(value, options);
	}

	static format(value, options = {}) {
		return formatJson(value, options.indent ?? 2);
	}

	static toRaw(value, options = {}) {
		return rawJsonFromValue(value, options.indent ?? 2);
	}
}
