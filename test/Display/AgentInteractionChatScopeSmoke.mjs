import assert from 'node:assert/strict';
import { fileURLToPath } from 'node:url';
import { dirname, resolve } from 'node:path';

const root = resolve(dirname(fileURLToPath(import.meta.url)), '../../assets/modularchatbot');
const { AgentInteractionPlugin } = await import('file://' + resolve(root, 'plugins/AgentInteractionPlugin.js'));
const { Chatbot } = await import('file://' + resolve(root, 'Chatbot.js'));

class FakeElement {
	constructor(tagName = 'div') {
		this.tagName = tagName;
		this.children = [];
		this.textContent = '';
		this.dataset = {};
		this.listeners = new Map();
		this.classList = { add() {}, remove() {} };
	}
	append(...children) { this.children.push(...children); }
	appendChild(child) { this.children.push(child); }
	replaceChildren(...children) { this.children = children; }
	setAttribute(name, value) { this[name] = value; }
	addEventListener(event, handler) { this.listeners.set(event, handler); }
	click() { this.listeners.get('click')?.(); }
}

globalThis.document = { createElement: (tag) => new FakeElement(tag) };

function findButtons(node) {
	return node.children.flatMap((child) => [
		...(child.tagName === 'button' ? [child] : []),
		...findButtons(child)
	]);
}

function renderCard(requests) {
	const events = [];
	const assistant = { element: new FakeElement(), content: new FakeElement() };
	const chatbot = {
		showAssistant() {},
		hideThinking() {},
		resumeInteraction: (...params) => events.push(params)
	};
	const context = {
		chatbot,
		getString: (name) => name,
		signal: new AbortController().signal
	};
	const interaction = {
		id: 'review-1', resume_handle: 'once-handle',
		interaction_requests: requests
	};
	const result = AgentInteractionPlugin.onTransportEvent(context, 'agent.interaction.required', interaction, { assistant });
	assert.equal(result.complete, true);
	return { buttons: findButtons(assistant.content), events };
}

const single = renderCard([{ id: 'air-1', kind: 'approval', title: 'Test tool', action: { name: 'remote__write' } }]);
assert.equal(single.buttons.length, 3, 'single approval should display exact/chat/deny');
assert.deepEqual(single.buttons.map((b) => b.textContent), ['approve', 'approveConversation', 'deny']);
single.buttons[1].click();
assert.equal(single.events.length, 1);
assert.deepEqual(single.events[0][1], [{
	request_id: 'air-1', decision: 'approve', metadata: { approval_scope: 'conversation' }
}]);
assert.ok(single.buttons.every((button) => button.disabled), 'buttons must be disabled after explicit click');

const batch = renderCard([
	{ id: 'air-3', kind: 'approval', title: 'Tool A' },
	{ id: 'air-4', kind: 'approval', title: 'Tool B' }
]);
assert.equal(batch.buttons.length, 7, 'every approval must expose exact/chat/deny, plus one submit');
const [firstOnce, firstChat, firstDeny, secondOnce, secondChat, secondDeny, submit] = batch.buttons;
assert.equal(submit.disabled, true, 'batch must have no default approval');
firstChat.click();
assert.equal(submit.disabled, true, 'incomplete batch cannot be sent');
secondDeny.click();
assert.equal(submit.disabled, false);
submit.click();
assert.equal(batch.events.length, 1);
assert.deepEqual(batch.events[0][1], [
	{ request_id: 'air-3', decision: 'approve', metadata: { approval_scope: 'conversation' } },
	{ request_id: 'air-4', decision: 'deny', metadata: {} }
]);
assert.equal(firstOnce['aria-pressed'], 'false');
assert.equal(firstChat['aria-pressed'], 'true');
assert.equal(firstDeny['aria-pressed'], 'false');
assert.equal(secondOnce['aria-pressed'], 'false');
assert.equal(secondChat['aria-pressed'], 'false');
assert.equal(secondDeny['aria-pressed'], 'true');

const chatbot = Object.create(Chatbot.prototype);
const calls = [];
chatbot.getString = (name) => name;
chatbot.send = async (options) => { calls.push(options); };
const current = { resume_handle: 'one', interaction_requests: [{ id: 'air-1', kind: 'approval' }] };
await chatbot.resumeInteraction(current, [{ request_id: 'air-1', decision: 'approve', metadata: { approval_scope: 'conversation' } }]);
assert.deepEqual(chatbot.pendingInteraction.explicit_responses, [
	{ request_id: 'air-1', decision: 'approve', metadata: { approval_scope: 'conversation' } }
]);
assert.equal(calls.length, 1);
await chatbot.resumeInteraction(current, [{ request_id: 'air-1', decision: 'deny', metadata: { approval_scope: 'conversation' } }]);
assert.equal(calls.length, 1, 'reusable consent cannot accompany denial');
await chatbot.resumeInteraction({ ...current, interaction_requests: [{ id:'a',kind:'approval' },{ id:'b',kind:'approval' }] }, [
	{ request_id: 'a', decision: 'approve', metadata: { approval_scope: 'conversation' } }
]);
assert.equal(calls.length, 1, 'incomplete batch cannot resume');

async function submittedResume(responses) {
	const bot = Object.create(Chatbot.prototype);
	let payload = null;
	bot.sending = false;
	bot.pendingInteraction = {
		resume_handle: 'one',
		interaction_requests: responses.map((response) => ({ id: response.request_id, kind: 'approval' })),
		explicit_responses: responses
	};
	bot.options = { configGroup: 'one', configName: 'two' };
	bot.conversationId = 'chat-one';
	bot.elements = {
		input: { value: '' },
		suggestions: { replaceChildren() {}, classList: { remove() {} } },
		openingMessage: { hidden: false },
		messages: { classList: { remove() {} } }
	};
	bot.root = { classList: { add() {} } };
	bot.events = { emit() {} };
	bot.resolveTransportMode = () => 'rest';
	bot.setSending = (value) => { bot.sending = value; };
	bot.scrollToBottom = () => {};
	bot.createAssistantMessage = () => ({});
	bot.createTransport = () => ({ send: async ({ payload: data }) => { payload = data; } });
	bot.pluginManager = {
		prepareRequest: async (p) => p,
		transformRequest: (p) => p,
		getTransportEvents: () => []
	};
	await bot.send({ text:'Submit decisions', displayUserMessage:false, interactionDecision:true });
	assert.equal(payload?.resume_handle, 'one');
	return JSON.parse(payload.resume_responses);
}
const responses = await submittedResume([
	{ request_id: 'air-1', decision: 'approve', metadata: { approval_scope: 'conversation' } },
	{ request_id: 'air-2', decision: 'deny', metadata: {} }
]);
assert.deepEqual(responses.map(({ request_id, decision, metadata }) => ({ request_id, decision, metadata })), [
	{ request_id: 'air-1', decision: 'approve', metadata: { approval_scope: 'conversation' } },
	{ request_id: 'air-2', decision: 'deny', metadata: {} }
]);
console.log('ClientStack modular chatbot explicit per-request exact/chat approval: OK');
