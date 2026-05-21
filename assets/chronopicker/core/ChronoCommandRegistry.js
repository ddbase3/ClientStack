export class ChronoCommandRegistry {
	constructor() {
		this.commands = new Map();
	}

	register(name, handler) {
		if (!name || typeof handler !== 'function') {
			throw new Error('ChronoPicker command registration requires a name and handler.');
		}

		this.commands.set(name, handler);
	}

	has(name) {
		return this.commands.has(name);
	}

	execute(name, payload) {
		const handler = this.commands.get(name);

		if (!handler) {
			throw new Error(`ChronoPicker command not registered: ${name}`);
		}

		return handler(payload);
	}

	clear() {
		this.commands.clear();
	}
}
