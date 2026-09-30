const { test } = require('node:test');
const assert = require('node:assert/strict');
const { readFileSync } = require('node:fs');
const vm = require('node:vm');

const source = readFileSync('resources/views/kiosk/work-time.blade.php', 'utf8')
    .split('<script>')[1].split('</script>')[0]
    .replace("@json(route('kiosk.session'))", '"/terminal/session"')
    .replace("{{ asset('kiosk-sw.js') }}", '/kiosk-sw.js');

function screen(fetch) {
    const events = {};
    const token = { value: 'expired' };
    const button = { disabled: false };
    let errorVisible = false;
    let submitted = 0;
    const form = { addEventListener: (name, fn) => { events.submit = fn; } };
    const error = { classList: {
        add: () => { errorVisible = false; },
        remove: () => { errorVisible = true; },
    } };
    const document = {
        hidden: false,
        addEventListener: (name, fn) => { events[name] = fn; },
        getElementById: id => id === 'session-error' ? error : null,
        querySelector: () => ({}),
        querySelectorAll: selector => selector.startsWith('input') ? [token]
            : selector.startsWith('button') ? [button] : [form],
    };
    vm.runInNewContext(source, {
        document, fetch, AbortController, setTimeout, clearTimeout, navigator: {},
        window: { setInterval: fn => { events.interval = fn; },
            addEventListener: (name, fn) => { events[name] = fn; } },
        HTMLFormElement: { prototype: { submit() { submitted++; } } },
    });
    return { events, token, button, document,
        submit: () => events.submit({ preventDefault() {} }),
        get submitted() { return submitted; },
        get errorVisible() { return errorVisible; },
    };
}

test('refreshes token before submitting and ignores double clicks', async () => {
    let resolve;
    const page = screen(() => new Promise(done => { resolve = done; }));
    const first = page.submit();
    await page.submit();
    assert.equal(page.submitted, 0);
    assert.equal(page.button.disabled, true);
    resolve({ ok: true, json: async () => ({ token: 'fresh' }) });
    await first;
    assert.equal(page.token.value, 'fresh');
    assert.equal(page.submitted, 1);
});

test('connection failure prevents submission and allows a manual retry', async () => {
    let online = false;
    const page = screen(async () => {
        if (!online) throw new Error('offline');
        return { ok: true, json: async () => ({ token: 'fresh' }) };
    });
    await page.submit();
    assert.equal(page.submitted, 0);
    assert.equal(page.errorVisible, true);
    assert.equal(page.button.disabled, false);
    online = true;
    await page.submit();
    assert.equal(page.submitted, 1);
    assert.equal(page.errorVisible, false);
});

test('returning to the screen refreshes the session without submitting', async () => {
    let requests = 0;
    const page = screen(async () => {
        requests++;
        return { ok: true, json: async () => ({ token: 'fresh' }) };
    });
    page.document.hidden = true;
    page.events.interval();
    assert.equal(requests, 0);
    page.document.hidden = false;
    page.events.visibilitychange();
    await new Promise(resolve => setImmediate(resolve));
    assert.equal(requests, 1);
    assert.equal(page.token.value, 'fresh');
    assert.equal(page.submitted, 0);
});
