const test = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const source = fs.readFileSync(require('node:path').resolve(__dirname, '../../resources/js/components/services/International.vue'), 'utf8');
function harness() {
  const timers = new Map(), requests = [];
  let timerId = 0;
  const context = {
    axios: { post: (url, body) => new Promise((resolve, reject) => requests.push({url, body, resolve, reject})) },
    Auth: {user: {id: 1}},
    setTimeout: callback => { const id = ++timerId; timers.set(id, callback); return id; },
    clearTimeout: id => timers.delete(id),
  };
  const script = source.match(/<script>([\s\S]*?)<\/script>/)[1].replace(/^import .*;$/gm, '').replace('export default', 'component =');
  vm.runInNewContext(script, context);
  const state = context.component.data();
  for (const [key, method] of Object.entries(context.component.methods)) state[key] = method.bind(state);
  for (const [key, getter] of Object.entries(context.component.computed)) Object.defineProperty(state, key, {get: getter.bind(state)});
  Object.assign(state, {country: 'GH', phone: '0241234567', operator: {id: 7, min: 1, max: 100}, amount: '50'});
  return {state, requests, fire: () => { for (const [id, callback] of timers) {timers.delete(id); callback();} }};
}
const settle = () => new Promise(resolve => setImmediate(resolve));
test('airtime calculates the payable NGN total before Continue', async () => {
  const h = harness(); h.state.scheduleAmountPrice();
  assert.equal(h.state.pricePending, true); assert.equal(h.requests.length, 0);
  h.fire(); assert.equal(h.requests[0].url, '/api/international/preview');
  assert.equal(h.requests[0].body.amount, '50');
  h.requests[0].resolve({data: {data: {amount_kobo: 618000}}}); await settle();
  assert.equal(h.state.prices['50'], 618000); assert.equal(h.state.pricePending, false);
  assert.equal(h.state.quote, null);
});
test('late responses cannot overwrite the total after the amount changes', async () => {
  const h = harness(); h.state.scheduleAmountPrice(); h.fire();
  h.state.amount = '25'; h.state.scheduleAmountPrice(); h.fire();
  h.requests[0].resolve({data: {data: {amount_kobo: 618000}}}); await settle();
  assert.equal(h.state.prices['50'], undefined); assert.equal(h.state.pricePending, true);
  h.requests[1].resolve({data: {data: {amount_kobo: 309000}}}); await settle();
  assert.equal(h.state.prices['25'], 309000); assert.equal(h.state.pricePending, false);
});
test('invalid amounts clear the old total without calling the provider', () => {
  const h = harness(); h.state.prices = {'50': 618000}; h.state.amount = '101'; h.state.scheduleAmountPrice(); h.fire();
  assert.equal(h.state.prices['50'], undefined); assert.equal(h.requests.length, 0);
  assert.match(h.state.message, /limits/);
});
test('price errors and success flags are positioned below Continue', async () => {
  const h = harness(); h.state.scheduleAmountPrice(); h.fire();
  h.requests[0].reject({response: {data: {message: 'Price unavailable'}}}); await settle();
  assert.equal(h.state.message, 'Price unavailable'); assert.equal(h.state.pricePending, false);
  assert.ok(source.indexOf('v-if="message"') > source.indexOf('Continue</button>'));
  assert.ok(source.indexOf('v-if="statusMessage"') > source.indexOf('Continue</button>'));
});

test('country search matches names, ISO codes and calling prefixes', () => {
  const {state} = harness();
  state.countries = [{code:'GH',name:'Ghana',calling_code:'+233'}, {code:'NG',name:'Nigeria',calling_code:'+234'}];
  state.countrySearch = ' nigeria ';
  assert.equal(state.filteredCountries.length, 1); assert.equal(state.filteredCountries[0].code, 'NG');
  state.countrySearch = 'gh'; assert.equal(state.filteredCountries[0].code, 'GH');
  state.countrySearch = '+234'; assert.equal(state.filteredCountries[0].code, 'NG');
  state.countrySearch = 'missing country'; assert.equal(state.filteredCountries.length, 0);
});

test('Sochitel data products sharing an amount keep separate prices and selection IDs', async () => {
  const {state,requests} = harness();
  state.type = 'data';
  state.operator = {id:7,provider:'sochitel',plans:[{amount:'50.00',product_id:11,name:'5GB 30 days'},{amount:'50.00',product_id:12,name:'10GB 7 days'}]};
  const loading = state.loadPrices();
  assert.equal(requests[0].body.product_id,11);
  requests[0].resolve({data:{data:{amount_kobo:618000}}}); await settle();
  assert.equal(requests[1].body.product_id,12);
  requests[1].resolve({data:{data:{amount_kobo:772500}}}); await loading;
  state.selectPlan(state.operator.plans[1]);
  assert.equal(state.prices[state.priceKey()],772500);
  assert.equal(state.input().provider,'sochitel');
  assert.equal(state.input().product_id,12);
  state.selectPlan(state.operator.plans[0]);
  assert.equal(state.prices[state.priceKey()],618000);
});
