const test = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const axios = require('axios');

function setup() {
    const source = fs.readFileSync('resources/js/authentication.js', 'utf8')
        .replace('export function', 'function');
    const context = { URL };
    vm.runInNewContext(source, context);
    const storage = new Map([['token', 'current-token']]);
    const browser = {
        localStorage: { getItem: key => storage.get(key) || null },
        location: { origin: 'https://swiftlinkng.com', href: '/dashboard' },
    };
    const auth = { logout: () => storage.delete('token') };
    const client = axios.create();
    context.installAuthentication(client, auth, browser);
    return { client, storage, browser };
}

function reject(status, message, beforeReject = () => {}) {
    return async config => {
        beforeReject();
        throw { config, response: { status, data: { message } } };
    };
}

test('dashboard requests use the current login even when a component captured an old token', async () => {
    const h = setup();
    h.client.defaults.adapter = async config => ({ config, status: 200, data: {} });
    const response = await h.client.get('/api/load-home', { headers: { Authorization: 'Bearer old-token' } });
    assert.equal(response.config.headers.Authorization, 'Bearer current-token');
});

test('an expired or revoked current login redirects to sign in', async () => {
    const h = setup();
    h.client.defaults.adapter = reject(401, 'Unauthenticated.');
    await assert.rejects(h.client.get('/api/load-home'));
    assert.equal(h.storage.has('token'), false);
    assert.equal(h.browser.location.href, '/login');
});

test('provider rejection, forbidden access and server errors preserve the login', async () => {
    for (const [status, message] of [[401, 'Provider credentials rejected'], [403, 'Forbidden'], [500, 'Server Error']]) {
        const h = setup();
        h.client.defaults.adapter = reject(status, message);
        await assert.rejects(h.client.get('/api/load-home'));
        assert.equal(h.storage.get('token'), 'current-token');
        assert.equal(h.browser.location.href, '/dashboard');
    }
});

test('a failed request from an old login cannot log out a newer session', async () => {
    const h = setup();
    h.client.defaults.adapter = reject(401, 'Unauthenticated.', () => h.storage.set('token', 'new-login'));
    await assert.rejects(h.client.get('/api/load-home'));
    assert.equal(h.storage.get('token'), 'new-login');
    assert.equal(h.browser.location.href, '/dashboard');
});

test('external requests and credential submission failures never clear a login', async () => {
    for (const url of ['https://provider.example/api/status', '/api/login', '/api/register', '/api/auth/password/reset']) {
        const h = setup();
        h.client.defaults.adapter = reject(401, 'Unauthenticated.');
        await assert.rejects(h.client.post(url));
        assert.equal(h.storage.get('token'), 'current-token');
    }
});

test('requests after logout discard a captured bearer token', async () => {
    const h = setup();
    h.storage.delete('token');
    h.client.defaults.adapter = async config => ({ config, status: 200, data: {} });
    const response = await h.client.get('/api/load-home', { headers: { Authorization: 'Bearer old-token' } });
    assert.equal(response.config.headers.Authorization, undefined);
});
