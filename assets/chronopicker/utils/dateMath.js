export const monthNames = [
	'January',
	'February',
	'March',
	'April',
	'May',
	'June',
	'July',
	'August',
	'September',
	'October',
	'November',
	'December'
];

export const weekdayNames = [
	'Sun',
	'Mon',
	'Tue',
	'Wed',
	'Thu',
	'Fri',
	'Sat'
];

export function pad2(value) {
	return String(value).padStart(2, '0');
}

export function createLocalDate(year, month, day, hour = 0, minute = 0) {
	return new Date(Number(year), Number(month) - 1, Number(day), Number(hour), Number(minute), 0, 0);
}

export function addMonths(date, offset) {
	return new Date(date.getFullYear(), date.getMonth() + offset, 1, date.getHours(), date.getMinutes(), 0, 0);
}

export function getMonthMatrix(year, month, weekStartsOn = 1) {
	const first = createLocalDate(year, month, 1);
	const firstWeekday = first.getDay();
	const leadingDays = (firstWeekday - weekStartsOn + 7) % 7;
	const start = new Date(first);
	start.setDate(first.getDate() - leadingDays);

	const weeks = [];

	for (let weekIndex = 0; weekIndex < 6; weekIndex++) {
		const week = [];

		for (let dayIndex = 0; dayIndex < 7; dayIndex++) {
			const date = new Date(start);
			date.setDate(start.getDate() + weekIndex * 7 + dayIndex);

			week.push({
				date,
				year: date.getFullYear(),
				month: date.getMonth() + 1,
				day: date.getDate(),
				iso: toIsoDate(date),
				isCurrentMonth: date.getMonth() === month - 1
			});
		}

		weeks.push(week);
	}

	return weeks;
}

export function toIsoDate(date) {
	return `${date.getFullYear()}-${pad2(date.getMonth() + 1)}-${pad2(date.getDate())}`;
}

export function isSameDay(left, right) {
	if (!left || !right) {
		return false;
	}

	return left.getFullYear() === right.getFullYear()
		&& left.getMonth() === right.getMonth()
		&& left.getDate() === right.getDate();
}

export function isWithinRange(date, min, max) {
	if (!date) {
		return false;
	}

	const minDate = normalizeLimit(min);
	const maxDate = normalizeLimit(max);

	if (minDate && stripTime(date) < stripTime(minDate)) {
		return false;
	}

	if (maxDate && stripTime(date) > stripTime(maxDate)) {
		return false;
	}

	return true;
}

export function clampDate(date, min, max) {
	const minDate = normalizeLimit(min);
	const maxDate = normalizeLimit(max);

	if (minDate && date < minDate) {
		return minDate;
	}

	if (maxDate && date > maxDate) {
		return maxDate;
	}

	return date;
}

function normalizeLimit(value) {
	if (!value) {
		return null;
	}

	if (value instanceof Date) {
		return value;
	}

	const match = String(value).match(/^(\d{4})-(\d{2})-(\d{2})/);

	if (!match) {
		return null;
	}

	return createLocalDate(Number(match[1]), Number(match[2]), Number(match[3]));
}

function stripTime(date) {
	return createLocalDate(date.getFullYear(), date.getMonth() + 1, date.getDate());
}
