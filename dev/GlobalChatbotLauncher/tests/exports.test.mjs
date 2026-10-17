import assert from 'node:assert/strict';
import test from 'node:test';
import { GlobalChatbotLauncher, mountGlobalChatbotLauncher, initializeGlobalChatbotLaunchers } from '../src/index.js';

test('global launcher is exported as a platform-independent ES module', () => {
  assert.equal(typeof GlobalChatbotLauncher, 'function');
  assert.equal(typeof mountGlobalChatbotLauncher, 'function');
  assert.equal(typeof initializeGlobalChatbotLaunchers, 'function');
});
