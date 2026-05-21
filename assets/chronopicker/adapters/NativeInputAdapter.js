export class NativeInputAdapter {
	canHandle(element) {
		if (!element || !element.tagName) {
			return false;
		}

		const tagName = element.tagName.toLowerCase();

		return tagName === 'input' || tagName === 'textarea';
	}

	prepare(element, options = {}) {
		if (!this.canHandle(element)) {
			return;
		}

		if (options.placeholder && !element.getAttribute('placeholder')) {
			element.setAttribute('placeholder', options.placeholder);
		}

		element.setAttribute('autocomplete', 'off');
		element.classList.add('cp-input-bound');
	}

	read(element) {
		return element?.value || '';
	}

	write(element, value) {
		if (!element) {
			return;
		}

		element.value = value || '';
	}

	on(element, eventName, handler) {
		element.addEventListener(eventName, handler);

		return () => element.removeEventListener(eventName, handler);
	}
}
