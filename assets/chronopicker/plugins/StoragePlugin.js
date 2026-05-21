export const StoragePlugin = {
	name: 'storage',

	install(context) {
		const options = {
			key: 'ChronoPickerState',
			sections: ['value'],
			...(context.getPluginOptions('storage') || {})
		};
		const storage = getStorage();

		if (!storage) {
			return;
		}

		try {
			const raw = storage.getItem(options.key);

			if (raw) {
				const state = JSON.parse(raw);
				if (state.value) {
					context.execute('setValue', state.value);
				}
			}
		} catch (error) {
			context.events.emit('storage:error', error);
		}

		this.unsubscribe = context.store.subscribe((state) => {
			const storedState = {};

			for (const section of options.sections) {
				storedState[section] = state[section];
			}

			try {
				storage.setItem(options.key, JSON.stringify(storedState));
			} catch (error) {
				context.events.emit('storage:error', error);
			}
		});
	},

	destroy() {
		if (typeof this.unsubscribe === 'function') {
			this.unsubscribe();
			this.unsubscribe = null;
		}
	}
};

function getStorage() {
	if (typeof window === 'undefined' || !window.localStorage) {
		return null;
	}

	return window.localStorage;
}
