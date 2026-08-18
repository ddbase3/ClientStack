function stageText(activity) {
	const source = `${activity.label} ${activity.description}`.toLowerCase();

	if (/final|output|response|answer|compose/.test(source)) {
		return 'Antwort wird erstellt';
	}
	if (/verify|review|check|guard|validat/.test(source)) {
		return 'Ergebnis wird geprüft';
	}
	if (/plan|strategy|orchestrat|decid/.test(source)) {
		return 'Vorgehen wird geplant';
	}
	if (/context|memory|prompt|prepare|input/.test(source)) {
		return 'Kontext wird vorbereitet';
	}
	if (/tool|execute|action|retriev|search/.test(source)) {
		return 'Informationen werden verarbeitet';
	}
	if (activity.status === 'completed') {
		return 'Nächster Schritt wird vorbereitet';
	}

	return 'Anfrage wird verarbeitet';
}

function resolveText(activity) {
	if (activity.status === 'failed') {
		return 'Nächster Schritt wird geprüft';
	}
	if (activity.kind === 'tool') {
		return activity.status === 'completed'
			? 'Informationen werden verarbeitet'
			: 'Informationen werden abgerufen';
	}
	return stageText(activity);
}

function setText(state, text) {
	state.text.textContent = text;
	state.element.classList.remove('is-leaving');
}

export const ShimmerAgentActivityRenderer = {
	name: 'shimmer',

	createState(assistant) {
		const thinking = assistant?.thinking?.isConnected ? assistant.thinking : null;
		const element = thinking || document.createElement('div');
		element.classList.add('base3-chatbot-activity', 'base3-chatbot-activity-shimmer');
		element.setAttribute('role', 'status');
		element.setAttribute('aria-live', 'polite');

		let icon = element.querySelector('.base3-chatbot-thinking-icon');
		if (!icon) {
			icon = document.createElement('span');
			icon.className = 'base3-chatbot-activity-shimmer-icon';
			icon.setAttribute('aria-hidden', 'true');
			icon.textContent = '✦';
		}

		const dots = element.querySelector('.base3-chatbot-thinking-dots');
		if (dots) {
			dots.remove();
		}

		const text = document.createElement('span');
		text.className = 'base3-chatbot-activity-shimmer-text';
		text.textContent = 'Anfrage wird vorbereitet';
		if (!icon.isConnected) {
			element.appendChild(icon);
		}
		element.appendChild(text);

		if (!thinking) {
			assistant.activity.appendChild(element);
		}

		return {
			element,
			text,
			turnId: ''
		};
	},

	setTurnId(state, turnId) {
		state.turnId = turnId;
	},

	update(state, activity) {
		setText(state, resolveText(activity));
	},

	onToken(state) {
		setText(state, 'Antwort wird erstellt');
	},

	complete(state) {
		if (!state.element.isConnected) {
			return;
		}
		state.element.classList.add('is-leaving');
		globalThis.setTimeout(() => {
			state.element.remove();
		}, 180);
	}
};
