export class JsonLensViewer {
	constructor(lens) {
		this.lens = lens;
	}

	render(container) {
		return this.lens.renderView(container);
	}
}
