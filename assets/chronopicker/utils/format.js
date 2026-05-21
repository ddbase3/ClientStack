import { pad2 } from './dateMath.js';

export function formatChronoValue(date, format = 'YYYY-MM-DD') {
	if (!date) {
		return '';
	}

	const replacements = {
		YYYY: String(date.getFullYear()),
		MM: pad2(date.getMonth() + 1),
		DD: pad2(date.getDate()),
		HH: pad2(date.getHours()),
		mm: pad2(date.getMinutes())
	};

	return Object.keys(replacements).reduce((result, token) => {
		return result.replaceAll(token, replacements[token]);
	}, format);
}
