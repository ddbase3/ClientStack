export const MarkedDaysPlugin = {
	name: 'markedDays',

	install(context) {
		context.events.emit('markedDays:available', {
			message: 'MarkedDaysPlugin is reserved for a later patch and has no UI in the initial baseline.'
		});
	}
};
