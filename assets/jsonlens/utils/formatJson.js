export function formatJson(value, indent = 2) {
	return JSON.stringify(value, null, indent);
}

export function rawJsonFromValue(value, indent = 2) {
	if (typeof value === 'string') {
		return value;
	}

	return JSON.stringify(value, null, indent);
}
