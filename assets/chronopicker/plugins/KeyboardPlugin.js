export const KeyboardPlugin = {
	name: 'keyboard',

	install(context) {
		this.keydownHandler = (event) => {
			if (event.key === 'Escape') {
				context.execute('close');
			}
		};

		document.addEventListener('keydown', this.keydownHandler);
	},

	destroy() {
		if (this.keydownHandler) {
			document.removeEventListener('keydown', this.keydownHandler);
			this.keydownHandler = null;
		}
	}
};
