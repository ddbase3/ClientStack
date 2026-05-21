import { ChronoPicker } from './ChronoPicker.js';

export class ChronoDateTimePicker extends ChronoPicker {
	constructor(target, options = {}) {
		super(target, {
			...options,
			mode: 'datetime',
			format: options.format || 'YYYY-MM-DD HH:mm'
		});
	}
}
