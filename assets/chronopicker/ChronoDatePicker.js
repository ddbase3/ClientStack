import { ChronoPicker } from './ChronoPicker.js';

export class ChronoDatePicker extends ChronoPicker {
	constructor(target, options = {}) {
		super(target, {
			...options,
			mode: 'date',
			format: options.format || 'YYYY-MM-DD'
		});
	}
}
