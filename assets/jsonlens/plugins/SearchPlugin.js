import { createElement } from '../utils/dom.js';

export const SearchPlugin = {
	name: 'search',

	commands: {
		setSearchTerm(context, payload) {
			const term = typeof payload === 'string' ? payload : payload?.term || '';

			context.setState({
				search: {
					...(context.getState().search || {}),
					term
				}
			});
			context.requestRender();
		}
	},

	layoutContributions(context) {
		return [
			{
				zone: 'toolbar',
				order: 40,
				render({ state }) {
					const wrapper = createElement('label', {
						className: 'jl-search'
					});
					const input = createElement('input', {
						className: 'jl-search-input',
						attrs: {
							type: 'search',
							placeholder: 'Search JSON',
							value: state.search?.term || ''
						},
						onInput: (event) => context.execute('setSearchTerm', event.target.value)
					});

					wrapper.appendChild(input);

					return wrapper;
				}
			}
		];
	}
};
