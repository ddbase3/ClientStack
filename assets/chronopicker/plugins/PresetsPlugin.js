export const PresetsPlugin = {
	name: 'presets',

	install(context) {
		context.events.emit('presets:available', {
			message: 'PresetsPlugin is reserved for a later patch and has no UI in the initial baseline.'
		});
	}
};
