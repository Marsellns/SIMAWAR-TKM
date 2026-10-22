const test = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');
const postcss = require('postcss');

const source = fs.readFileSync(path.join(__dirname, '../resources/views/reports/scripts.blade.php'), 'utf8');
const css = fs.readFileSync(path.join(__dirname, '../public/css/simaster.css'), 'utf8');

test('filter trigger uses an existing hero action area when one is designated', () => {
    const start = source.indexOf('const placeInHeader = function');
    const code = source.slice(start, source.indexOf('const moveExportActions = function', start));
    const actions = { children: [], appendChild(control) { this.children.push(control); } };
    const context = {
        reportRoot: { querySelector: () => actions },
        document: { querySelector: () => { throw new Error('unexpected fallback launcher'); } },
    };
    vm.runInNewContext(code + '\nthis.placeInHeader = placeInHeader;', context);
    const trigger = { textContent: 'Filter' };
    assert.equal(context.placeInHeader(trigger), actions);
    assert.deepEqual(actions.children, [trigger]);
});

test('native forms and period pickers keep a single action area', () => {
    const start = source.indexOf('overlay.innerHTML =');
    const template = source.slice(start, source.indexOf('const popupBody =', start));
    for (const [periodMenu, formSubmit, expected] of [[null, {}, 0], [{}, null, 0], [null, null, 1]]) {
        const context = { overlay: {}, popupId: 'filter-test', periodMenu, formSubmit };
        vm.runInNewContext(template, context);
        assert.equal((context.overlay.innerHTML.match(/simaster-filter-apply/g) || []).length, expected);
        assert.equal((context.overlay.innerHTML.match(/simaster-filter-close/g) || []).length, 1);
        assert.match(context.overlay.innerHTML, /aria-labelledby="filter-test-title"/);
        assert.doesNotMatch(context.overlay.innerHTML, /Tutup &amp; Terapkan/);
    }
});

test('moving native actions preserves the original form, filter values and handlers', () => {
    class Element {
        constructor(tag, text = '') { this.tag = tag; this.text = text; this.children = []; }
        get textContent() { return this.text + this.children.map(child => child.textContent).join(''); }
        set textContent(value) { this.text = value; }
        appendChild(child) {
            child.remove();
            this.children.push(child);
            child.parentElement = this;
        }
        remove() {
            if (this.parentElement) this.parentElement.children = this.parentElement.children.filter(child => child !== this);
            this.parentElement = null;
        }
        getAttribute(name) { return this[name] || null; }
    }
    const start = source.indexOf('if (formSubmit && !periodMenu)');
    const code = source.slice(start, source.indexOf('reportRoot.appendChild(overlay)', start));
    for (const wrapped of [false, true]) {
        const form = new Element('form');
        form.method = 'get';
        form.action = '/infrastruktur/sewa-lahan';
        const year = new Element('select');
        year.name = 'tahun'; year.value = '2026';
        const owner = new Element('select');
        owner.name = 'ownership_scope'; owner.value = 'TP';
        form.appendChild(year); form.appendChild(owner);
        const originalParent = wrapped ? new Element('div') : form;
        if (wrapped) form.appendChild(originalParent);
        const submit = new Element('button', 'Terapkan Filter');
        submit.type = 'submit';
        const reset = new Element('a', 'Reset');
        reset.href = '/infrastruktur/sewa-lahan';
        let submissions = 0;
        submit.click = () => submissions++;
        originalParent.appendChild(submit); originalParent.appendChild(reset);
        form.querySelectorAll = () => [reset];
        vm.runInNewContext(code, { formSubmit: submit, filterForm: form, periodMenu: null,
            document: { createElement: tag => new Element(tag) } });
        assert.equal(submit.parentElement, reset.parentElement);
        assert.equal(submit.parentElement.parentElement, form);
        assert.equal(form.children.filter(child => child.className === 'simaster-filter-form-actions').length, 1);
        assert.equal(form.children[0], year);
        assert.equal(year.value, '2026');
        assert.equal(owner.value, 'TP');
        assert.equal(reset.href, '/infrastruktur/sewa-lahan');
        assert.equal(form.method, 'get');
        submit.click();
        assert.equal(submissions, 1);
    }
});

test('compact filters fit narrow and short viewports, including triggers near the bottom', () => {
    const start = source.indexOf('const positionPopup = function');
    const code = source.slice(start, source.indexOf('const openPopup =', start)) + '\npositionPopup();';
    for (const [width, height, triggerTop, naturalHeight] of [[320, 640, 120, 350], [1200, 800, 740, 400], [390, 240, 160, 620]]) {
        const popup = { style: {}, get offsetHeight() { return Math.min(naturalHeight, parseFloat(this.style.maxHeight)); } };
        const viewportWidth = width - 15; // A visible scrollbar must not obscure the panel.
        vm.runInNewContext(code, { popup, window: { innerWidth: width, innerHeight: height },
            document: { documentElement: { clientWidth: viewportWidth } },
            trigger: { getBoundingClientRect: () => ({ right: width - 16, top: triggerTop, bottom: triggerTop + 38 }) } });
        assert.ok(parseFloat(popup.style.left) >= 12);
        assert.ok(parseFloat(popup.style.left) + parseFloat(popup.style.width) <= viewportWidth - 12);
        assert.ok(parseFloat(popup.style.top) >= 12);
        assert.ok(parseFloat(popup.style.top) + popup.offsetHeight <= height - 12);
    }
});

test('legacy hidden filters stay hidden and the popup has one scrolling body', () => {
    const rules = [];
    postcss.parse(css).walkRules(rule => rules.push(rule));
    const hidden = rules.find(rule => rule.selectors.includes('.simaster-filter-drawer .simaster-filter-source .d-none'));
    assert.ok(hidden.nodes.some(node => node.prop === 'display' && node.value === 'none' && node.important));
    const layout = rules.filter(rule => rule.selectors.some(selector => selector.includes('.simaster-filter-source') && selector.includes('.d-flex'))
        && rule.nodes.some(node => node.prop === 'display' && node.value === 'flex'));
    for (const rule of layout) {
        for (const selector of rule.selectors) assert.ok(selector.includes(':not(.d-none)'), selector);
    }
    const menu = rules.find(rule => rule.selector === '.simaster-filter-context-menu');
    assert.ok(menu.nodes.some(node => node.prop === 'overflow' && node.value === 'visible'));
    const body = rules.find(rule => rule.selector === '.simaster-filter-drawer-body');
    assert.ok(body.nodes.some(node => node.prop === 'overflow-y' && node.value === 'auto'));
});
