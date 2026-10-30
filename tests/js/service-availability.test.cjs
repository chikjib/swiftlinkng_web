const test = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
function setup(name, get) {
  const source = fs.readFileSync(`resources/js/components/services/${name}.vue`, 'utf8')
    .split('<script>')[1].split('</script>')[0]
    .replace(/^import .*;$/gm, '').replace('export default', 'component =');
  const context = { component: null, axios: {get}, Auth: {user: {id: 1}} };
  vm.runInNewContext(source, context);
  const state = context.component.data();
  for (const [key, fn] of Object.entries(context.component.methods)) state[key] = fn.bind(state);
  return state;
}
test('eSIM status failure remains unknown and retry loads an enabled catalogue', async () => {
  let fail = true;
  const state = setup('TravelEsim', async path => {
    if (fail) throw {response: {data: {message: 'Status request failed'}}};
    return {data: {data: path.includes('/status') ? {esim_enabled: true} : [{code: 'GB', name: 'United Kingdom'}]}};
  });
  await state.loadCatalogue();
  assert.equal(state.enabled, null);
  assert.equal(state.catalogueFailed, true);
  assert.equal(state.error, 'Status request failed');
  fail = false;
  await state.loadCatalogue();
  assert.equal(state.enabled, true);
  assert.equal(state.catalogueFailed, false);
  assert.equal(state.locations[0].code, 'GB');
});
test('betting catalogue failure preserves history and retry loads platforms', async () => {
  let fail = true;
  const state = setup('Betting', async path => {
    if (path.endsWith('/billers') && fail) throw {response: {data: {message: 'Provider connection failed'}}};
    return {data: {data: path.endsWith('/billers') ? [{biller_id: '2269'}] : [{id: 1}]}};
  });
  await state.load();
  assert.equal(state.billersLoaded, false);
  assert.equal(state.catalogueFailed, true);
  assert.equal(state.error, 'Provider connection failed');
  assert.equal(state.history.length, 1);
  fail = false;
  await state.load();
  assert.equal(state.billersLoaded, true);
  assert.equal(state.billers[0].biller_id, '2269');
});
test('international retry fetches countries again after a disabled response', async () => {
  let enabled = false;
  const state = setup('International', async () => {
    if (!enabled) throw {response: {data: {message: 'International top-up is currently unavailable.'}}};
    return {data: {data: [{code: 'GH'}]}};
  });
  await state.loadCountries();
  assert.equal(state.countriesFailed, true);
  assert.equal(state.message, 'International top-up is currently unavailable.');
  enabled = true;
  await state.loadCountries();
  assert.equal(state.countriesFailed, false);
  assert.equal(state.message, '');
  assert.equal(state.countries[0].code, 'GH');
});
