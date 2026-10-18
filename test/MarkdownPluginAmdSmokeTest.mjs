import assert from 'node:assert/strict';
import fs from 'node:fs';
import path from 'node:path';
import os from 'node:os';
import vm from 'node:vm';
import { fileURLToPath, pathToFileURL } from 'node:url';

const here = path.dirname(fileURLToPath(import.meta.url));
const bundle = fs.readFileSync(path.join(here, '../assets/marked/marked.js'), 'utf8');

// Node 18 treats .js as CommonJS without a package scope. Use an isolated ESM
// test fixture without changing the browser modules or the ClientStack package.
async function loadMarkdownPluginForTest() {
	const testRoot = fs.mkdtempSync(path.join(os.tmpdir(), 'base3-markdown-test-'));
	try {
		fs.writeFileSync(path.join(testRoot, 'package.json'), '{"type":"module"}\n');
		const moduleRoot = path.join(testRoot, 'modularchatbot');
		for (const relative of [
			'plugins/MarkdownPlugin.js',
			'utils/loadScript.js',
			'utils/dom.js'
		]) {
			const source = path.join(here, '../assets/modularchatbot', relative);
			const target = path.join(moduleRoot, relative);
			fs.mkdirSync(path.dirname(target), { recursive: true });
			fs.copyFileSync(source, target);
		}
		const { MarkdownPlugin } = await import(pathToFileURL(path.join(moduleRoot, 'plugins/MarkdownPlugin.js')).href);
		return { MarkdownPlugin, testRoot };
	} catch (error) {
		fs.rmSync(testRoot, { recursive: true, force: true });
		throw error;
	}
}

class FakeElement {
	constructor() { this.innerHTML = ''; }
	querySelectorAll() { return []; }
}

async function verifyRenderer(MarkdownPlugin, { amd, label, invalidGlobal = false }) {
	const modules = new Map();
	const errors = [];
	let added = 0;
	const previous = Object.fromEntries(
		['document', 'Element', 'CSS', 'marked', 'define', 'requirejs']
			.map((name) => [name, globalThis[name]])
	);
	try {
		globalThis.Element = FakeElement;
		globalThis.CSS = { escape: (value) => value };
		delete globalThis.marked;
		if (invalidGlobal) globalThis.marked = { otherParser: true };
		if (amd) {
			const define = (name, factory) => modules.set(name, factory);
			define.amd = {};
			globalThis.define = define;
			globalThis.requirejs = (names, resolve, reject) => {
				const factory = modules.get(names[0]);
				if (!factory) return reject(new Error('Unknown AMD module'));
				resolve(factory());
			};
		} else {
			delete globalThis.define;
			delete globalThis.requirejs;
		}
		globalThis.document = {
			querySelector: () => null,
			createElement: () => {
				const events = new Map();
				return {
					dataset: {},
					addEventListener: (type, callback) => events.set(type, callback),
					fire: (type) => events.get(type)?.()
				};
			},
			head: { appendChild: (script) => {
				added++;
				vm.runInNewContext(bundle, { globalThis, define: globalThis.define }, { filename: 'marked.js' });
				queueMicrotask(() => script.fire('load'));
			} }
		};

		const context = {
			root: { querySelector: () => ({}) },
			resolveGlobal: (name) => name === 'marked' ? globalThis.marked : null,
			getPluginOptions: () => ({ scriptUrl: `/marked.js?${label}` }),
			getString: () => 'loading',
			events: { on: () => {}, emit: (name, error) => errors.push({ name, error }) }
		};
		await MarkdownPlugin.install(context);
		assert.equal(errors.length, 0, `${label}: no parser loading errors`);
		assert.equal(added, 1, `${label}: script loaded once`);
		if (amd) {
			assert.equal(typeof globalThis.marked?.parse, 'undefined', 'AMD host has no global parser');
			assert.equal(typeof context.markedRenderer?.parse, 'function', 'AMD parser is scoped to plugin');
		} else {
			assert.equal(typeof globalThis.marked?.parse, 'function', 'global parser exists');
		}

		const message = new FakeElement();
		const handled = MarkdownPlugin.renderMessageContent(context, {
			element: message,
			text: '**Nebelbrueck**',
			assistant: { element: { classList: { contains: () => false } } }
		});
		assert.equal(handled, true, `${label}: Markdown plugin handled message`);
		assert.match(message.innerHTML, /<strong>Nebelbrueck<\/strong>/, `${label}: bold rendered`);
		console.log(`PASS: ${label}`);
	} finally {
		for (const [name, value] of Object.entries(previous)) {
			if (value === undefined) delete globalThis[name];
			else globalThis[name] = value;
		}
	}
}

const { MarkdownPlugin, testRoot } = await loadMarkdownPluginForTest();
try {
	await verifyRenderer(MarkdownPlugin, { amd: true, label: 'Moodle RequireJS/AMD' });
	await verifyRenderer(MarkdownPlugin, { amd: false, label: 'ILIAS/WordPress classic global' });
	await verifyRenderer(MarkdownPlugin, { amd: true, label: 'Moodle with unrelated marked global', invalidGlobal: true });
} finally {
	fs.rmSync(testRoot, { recursive: true, force: true });
}

const source = fs.readFileSync(path.join(here, '../dev/ModularChatbot/src/plugins/MarkdownPlugin.js'), 'utf8');
const deployed = fs.readFileSync(path.join(here, '../assets/modularchatbot/plugins/MarkdownPlugin.js'), 'utf8');
assert.equal(source, deployed, 'dev source and deployed copy must be identical');
const sourceIndex = fs.readFileSync(path.join(here, '../dev/ModularChatbot/src/index.js'), 'utf8');
const deployedIndex = fs.readFileSync(path.join(here, '../assets/modularchatbot/index.js'), 'utf8');
assert.equal(sourceIndex, deployedIndex, 'dev index and deployed index must be identical');
assert.match(sourceIndex, /MarkdownPlugin\.js\?build=amd-host-parser-1/, 'versioned import busts previous MarkdownPlugin cache');
console.log('MarkdownPluginAmdSmokeTest OK: AMD, classic, conflicting global, cache key, dev/assets sync');
