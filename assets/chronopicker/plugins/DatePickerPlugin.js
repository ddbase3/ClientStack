import { createButton, createElement } from '../utils/dom.js';
import { getMonthMatrix, isSameDay, isWithinRange, weekdayNames } from '../utils/dateMath.js';

export const DatePickerPlugin = {
	name: 'datePicker',

	layoutContributions(context) {
		return [
			{
				zone: 'main',
				order: 10,
				render({ state, options }) {
					return renderCalendar(context, state, options);
				}
			}
		];
	}
};

function renderCalendar(context, state, options) {
	const calendar = createElement('div', {
		className: 'cp-calendar',
		attrs: {
			role: 'grid',
			'aria-label': 'Calendar'
		}
	});
	const weekdays = createElement('div', {
		className: 'cp-weekdays'
	});
	const days = createElement('div', {
		className: 'cp-days'
	});
	const matrix = getMonthMatrix(state.viewYear, state.viewMonth, options.weekStartsOn);
	const labels = rotateWeekdays(weekdayNames, options.weekStartsOn);

	for (const label of labels) {
		weekdays.appendChild(createElement('div', {
			className: 'cp-weekday',
			text: label
		}));
	}

	for (const week of matrix) {
		for (const day of week) {
			const disabled = !isWithinRange(day.date, options.min, options.max);
			const selected = state.selectedDate && isSameDay(day.date, state.selectedDate);
			const today = isSameDay(day.date, new Date());
			const classes = ['cp-day'];

			if (!day.isCurrentMonth) {
				classes.push('cp-day-outside');
			}

			if (selected) {
				classes.push('cp-day-selected');
			}

			if (today) {
				classes.push('cp-day-today');
			}

			if (disabled) {
				classes.push('cp-day-disabled');
			}

			days.appendChild(createButton({
				className: classes.join(' '),
				text: String(day.day),
				disabled,
				attrs: {
					'aria-pressed': selected ? 'true' : 'false',
					'data-date': day.iso
				},
				onClick: () => context.execute('selectDate', {
					date: day.date
				})
			}));
		}
	}

	calendar.appendChild(weekdays);
	calendar.appendChild(days);

	return calendar;
}

function rotateWeekdays(labels, weekStartsOn) {
	const start = weekStartsOn ?? 1;

	return labels.slice(start).concat(labels.slice(0, start));
}
