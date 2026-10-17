import assert from 'node:assert/strict';
import test from 'node:test';
import { appendFormattedHeaderTitle } from '../src/GlobalChatbotLauncher.js';

class TextNode {
  constructor(value) {
    this.nodeType = 3;
    this.textContent = value;
  }
}

class ElementNode {
  constructor(tagName, childNodes = [], attributes = {}) {
    this.nodeType = 1;
    this.tagName = tagName;
    this.childNodes = childNodes;
    this.attributes = attributes;
  }
  appendChild(node) { this.childNodes.push(node); }
  get textContent() { return this.childNodes.map(node => node.textContent).join(''); }
}

test('header keeps strong emphasis but never copies attributes or active elements', () => {
  const oldDocument = globalThis.document;
  const markup = 'Ask <strong class="unsafe" onclick="attack()">KIM</strong><img src="x" onerror="attack()"><script>attack()</script>';
  globalThis.document = {
    createElement: name => {
      if (name === 'template') {
        const template = { content: new ElementNode('FRAGMENT') };
        Object.defineProperty(template, 'innerHTML', {
          set(value) {
            assert.equal(value, markup);
            template.content.childNodes = [
              new TextNode('Ask '),
              new ElementNode('STRONG', [new TextNode('KIM')], { class: 'unsafe', onclick: 'attack()' }),
              new ElementNode('IMG', [], { onerror: 'attack()' }),
              new ElementNode('SCRIPT', [new TextNode('attack()')])
            ];
          }
        });
        return template;
      }
      return new ElementNode(name.toUpperCase());
    },
    createTextNode: value => new TextNode(value)
  };
  try {
    const title = new ElementNode('DIV');
    appendFormattedHeaderTitle(title, markup);
    assert.equal(title.textContent, 'Ask KIM');
    assert.deepEqual(title.childNodes.map(node => node.tagName ?? '#text'), ['#text', 'STRONG']);
    assert.deepEqual(title.childNodes[1].attributes, {});
    assert.equal(title.childNodes[1].textContent, 'KIM');
  } finally {
    globalThis.document = oldDocument;
  }
});
