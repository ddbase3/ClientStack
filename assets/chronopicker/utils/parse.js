import { createLocalDate } from './dateMath.js';

const TOKENS = {
	YYYY: '(?<YYYY>\\d{4})',
	MM: '(?<MM>\\d{2})',
	DD: '(?<DD>\\d{2})',
	HH: '(?<HH>\\d{2})',
	mm: '(?<mm>\\d{2})'
};

const DEFAULT_STRINGS = {
	invalidDateObject: 'Invalid Date object.',
	expectedFormat: 'Expected format {format}.',
	hourOutOfRange: 'Hour must be between 00 and 23.',
	minuteOutOfRange: 'Minute must be between 00 and 59.',
	invalidDate: 'Date is not valid.'
};

function getString(options, key, replacements = {}) {
	const value = options.strings?.[key] ?? DEFAULT_STRINGS[key] ?? key;
	return Object.entries(replacements).reduce((text, [name, replacement]) => {
		return text.split(`{${name}}`).join(String(replacement));
	}, String(value));
}

export function parseChronoValue(value, options = {}) {
	const mode = options.mode || 'date';
	const format = options.format || (mode === 'datetime' ? 'YYYY-MM-DD HH:mm' : 'YYYY-MM-DD');

	if (value === null || value === undefined || value === '') {
		return {
			ok: true,
			date: null,
			value: '',
			error: ''
		};
	}

	if (value instanceof Date) {
		if (Number.isNaN(value.getTime())) {
			return invalid(getString(options, 'invalidDateObject'));
		}

		return {
			ok: true,
			date: value,
			value,
			error: ''
		};
	}

	const text = String(value).trim();
	const regex = compileFormat(format);
	const match = regex.exec(text);

	if (!match) {
		return invalid(getString(options, 'expectedFormat', { format }));
	}

	const groups = match.groups || {};
	const year = Number(groups.YYYY);
	const month = Number(groups.MM || 1);
	const day = Number(groups.DD || 1);
	const hour = Number(groups.HH || 0);
	const minute = Number(groups.mm || 0);

	if (hour < 0 || hour > 23) {
		return invalid(getString(options, 'hourOutOfRange'));
	}

	if (minute < 0 || minute > 59) {
		return invalid(getString(options, 'minuteOutOfRange'));
	}

	const date = createLocalDate(year, month, day, hour, minute);

	if (!isExactDate(date, year, month, day, hour, minute)) {
		return invalid(getString(options, 'invalidDate'));
	}

	return {
		ok: true,
		date,
		value: text,
		error: ''
	};
}

function compileFormat(format) {
	let pattern = '^';
	let index = 0;
	const tokenNames = Object.keys(TOKENS).sort((left, right) => right.length - left.length);

	while (index < format.length) {
		const token = tokenNames.find((candidate) => format.slice(index).startsWith(candidate));

		if (token) {
			pattern += TOKENS[token];
			index += token.length;
			continue;
		}

		pattern += escapeRegExp(format[index]);
		index++;
	}

	pattern += '$';

	return new RegExp(pattern);
}

function escapeRegExp(value) {
	return value.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
}

function isExactDate(date, year, month, day, hour, minute) {
	return date.getFullYear() === year
		&& date.getMonth() === month - 1
		&& date.getDate() === day
		&& date.getHours() === hour
		&& date.getMinutes() === minute;
}

function invalid(error) {
	return {
		ok: false,
		date: null,
		value: '',
		error
	};
}
