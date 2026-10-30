const test = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const source = fs.readFileSync('resources/js/components/services/Internet.vue', 'utf8')
  .split('<script>')[1].split('</script>')[0]
  .replace(/^import .*;$/gm, '').replace('export default', 'component =');
function setup(get) {
  const context = { component: null, axios: {get}, localStorage: {getItem: () => 'token'} };
  vm.runInNewContext(source, context);
  const definition = context.component;
  const state = definition.data();
  for (const [key, fn] of Object.entries(definition.methods)) state[key] = fn.bind(state);
  for (const [key, fn] of Object.entries(definition.computed)) Object.defineProperty(state, key, {get: () => fn.call(state)});
  return state;
}
const catalogue = [
  {id: 5, network: 'fibrex', products: [{plan: 'fibre-50', name: 'Fibre X 50Mbps', amount: 30000}]},
  {id: 6, network: 'smile', products: [{plan: 'smile-10', name: 'Smile 10GB', amount: 3500}]},
];
test('Fibre X packages are available after first catalogue load without switching providers', async () => {
  let resolve;
  const state = setup(() => new Promise(r => { resolve = r; }));
  const ready = state.load();
  assert.equal(state.loading, true);
  assert.equal(state.selected, '');
  resolve({data: {data: catalogue}});
  await ready;
  assert.equal(state.loading, false);
  assert.equal(state.selected, 'fibrex');
  assert.equal(state.plans.length, 1);
  assert.equal(state.plans[0].subcategory_id, 5);
  state.choose(state.plans[0]);
  assert.equal(state.plan.plan, 'fibre-50');
});
test('retry preserves the current provider and clears a stale package selection', async () => {
  const state = setup(async () => ({data: {data: catalogue}}));
  await state.load();
  state.select('smile');
  state.choose(state.plans[0]);
  await state.load();
  assert.equal(state.selected, 'smile');
  assert.equal(state.plan, null);
  assert.equal(state.plans[0].plan, 'smile-10');
  assert.equal(state.failed, false);
});
