export class JsonLensPluginManager {
	constructor(context) {
		this.context = context;
		this.plugins = [];
	}

	install(plugins = []) {
		for (const pluginDefinition of plugins) {
			const plugin = this.normalizePlugin(pluginDefinition);

			if (!plugin || !plugin.name) {
				throw new Error('JsonLens plugins require a unique name.');
			}

			if (this.plugins.some((installedPlugin) => installedPlugin.name === plugin.name)) {
				throw new Error(`JsonLens plugin already installed: ${plugin.name}`);
			}

			this.installCommands(plugin);
			this.installViews(plugin);

			if (typeof plugin.install === 'function') {
				plugin.install(this.context);
			}

			this.plugins.push(plugin);
		}
	}

	normalizePlugin(pluginDefinition) {
		if (typeof pluginDefinition === 'function') {
			return pluginDefinition(this.context);
		}

		return pluginDefinition;
	}

	installCommands(plugin) {
		if (!plugin.commands) {
			return;
		}

		for (const [commandName, handler] of Object.entries(plugin.commands)) {
			this.context.commands.register(commandName, (payload) => handler(this.context, payload));
		}
	}

	installViews(plugin) {
		if (!plugin.views) {
			return;
		}

		for (const view of plugin.views) {
			this.context.registerView(view);
		}
	}

	getLayoutContributions(zone) {
		const contributions = [];

		for (const plugin of this.plugins) {
			if (typeof plugin.layoutContributions !== 'function') {
				continue;
			}

			for (const contribution of plugin.layoutContributions(this.context) || []) {
				if (contribution.zone === zone) {
					contributions.push(contribution);
				}
			}
		}

		return contributions.sort((left, right) => (left.order || 0) - (right.order || 0));
	}

	destroy() {
		for (const plugin of [...this.plugins].reverse()) {
			if (typeof plugin.destroy === 'function') {
				plugin.destroy(this.context);
			}
		}

		this.plugins = [];
	}
}
