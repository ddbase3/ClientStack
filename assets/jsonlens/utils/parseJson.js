export function parseJson(input, options = {}) {
	const indent = options.indent ?? 2;
	const raw = input == null ? '' : String(input);

	if (raw.trim() === '') {
		return {
			ok: false,
			raw,
			value: null,
			formatted: '',
			error: 'Empty JSON input.',
			sourceType: 'string'
		};
	}

	try {
		const value = JSON.parse(raw);

		return {
			ok: true,
			raw,
			value,
			formatted: JSON.stringify(value, null, indent),
			error: null,
			sourceType: 'string'
		};
	} catch (error) {
		return {
			ok: false,
			raw,
			value: null,
			formatted: '',
			error: error.message,
			sourceType: 'string'
		};
	}
}
