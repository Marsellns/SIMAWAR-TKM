const test = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');

const source = fs.readFileSync(path.join(__dirname, '../public/js/report-modal-focus.js'), 'utf8');

function setup() {
    const listeners = new Map();
    let trapActive = false;
    let modal;
    let close;
    const document = {
        activeElement: null,
        addEventListener(type, listener) { listeners.set(type, listener); },
    };

    class Element {
        constructor(parent = null) {
            this.parent = parent;
            this.isConnected = true;
            this.attributes = new Map();
            this.listeners = new Map();
        }
        contains(element) {
            for (let node = element; node; node = node.parent) {
                if (node === this) return true;
            }
            return false;
        }
        closest(selector) {
            for (let node = this; node; node = node.parent) {
                if (selector === 'table' && node.isTable) return node;
            }
            return null;
        }
        hasAttribute(name) { return this.attributes.has(name); }
        setAttribute(name, value) { this.attributes.set(name, value); }
        removeAttribute(name) { this.attributes.delete(name); }
        addEventListener(type, listener) { this.listeners.set(type, listener); }
        focus() {
            if (trapActive && !modal.contains(this)) {
                document.activeElement = close;
                return;
            }
            document.activeElement?.listeners.get('blur')?.();
            document.activeElement = this;
        }
    }

    document.body = new Element();
    document.activeElement = document.body;
    modal = new Element();
    close = new Element(modal);
    const table = new Element();
    table.isTable = true;
    const row = new Element(table);
    vm.runInNewContext(source, { document, HTMLElement: Element, WeakMap, queueMicrotask });

    return {
        document, modal, close, row, table,
        emit(type, relatedTarget) { listeners.get(type)({ target: modal, relatedTarget }); },
        setTrap(active) { trapActive = active; },
    };
}

test('closing a row detail modal moves focus before aria-hidden is applied', async () => {
    const ui = setup();
    ui.emit('show.bs.modal', ui.row);
    ui.document.activeElement = ui.close;
    ui.setTrap(true);
    ui.emit('hide.bs.modal');
    ui.setTrap(false); // Bootstrap deactivates the trap after hide.bs.modal.
    await Promise.resolve();

    assert.equal(ui.document.activeElement, ui.row);
    assert.equal(ui.modal.contains(ui.document.activeElement), false);
    assert.equal(ui.row.attributes.get('tabindex'), '-1');
    ui.document.body.focus();
    assert.equal(ui.row.hasAttribute('tabindex'), false);
});

test('redrawn rows fall back to the table, and triggerless modals fall back to the page', async () => {
    const ui = setup();
    ui.emit('show.bs.modal', ui.row);
    ui.row.isConnected = false;
    ui.document.activeElement = ui.close;
    ui.emit('hide.bs.modal');
    await Promise.resolve();
    assert.equal(ui.document.activeElement, ui.table);

    ui.emit('hidden.bs.modal');
    ui.document.activeElement = ui.document.body;
    ui.emit('show.bs.modal');
    ui.document.activeElement = ui.close;
    ui.emit('hide.bs.modal');
    await Promise.resolve();
    assert.equal(ui.document.activeElement, ui.document.body);
});
