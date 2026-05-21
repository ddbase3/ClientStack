import { formatJson, rawJsonFromValue } from '../utils/formatJson.js';

export class ObjectJsonAdapter {
	canHandle(value) {
		return typeof value !== 'string' && value !== undefined;
	}

	parse(value, options = {}) {
		try {
			const raw = rawJsonFromValue(value, options.indent ?? 2);

			return {
				ok: true,
				raw,
				value,
				formatted: formatJson(value, options.indent ?? 2),
				error: null,
				sourceType: 'object'
			};
		} catch (error) {
			return {
				ok: false,
				raw: String(value),
				value: null,
				formatted: '',
				error: error.message,
				sourceType: 'object'
			};
		}
	}
}
