export const RangePlugin = {
	name: 'range',

	install(context) {
		context.events.emit('range:available', {
			message: 'RangePlugin is reserved for a later patch and has no UI in the initial baseline.'
		});
	}
};
