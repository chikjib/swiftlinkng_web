const test = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const crypto = require('node:crypto').webcrypto;
const source = fs.readFileSync('resources/js/components/services/TravelEsim.vue', 'utf8')
  .split('<script>')[1].split('</script>')[0]
  .replace(/^import .*;$/gm, '').replace('export default', 'component =');
function setup() {
  const storage = new Map();
  const calls = [];
  let cancelled = true;
  let networkFailure = false;
  const context = { component: null, crypto, Auth: {user:{id:1}},
    localStorage:{getItem:k=>storage.get(k)||null,setItem:(k,v)=>storage.set(k,v)},
    axios:{isCancel:e=>e.cancelled===true,post:async(url,body)=>{
      if (cancelled) throw {cancelled:true};
      calls.push(body);
      if(networkFailure) throw new Error('Offline');
      return {data:{data:{status:'pending',reference:body.reference}}};
    },get:async()=>({data:{data:[{package_code:'high',amount_kobo:'700000'},{package_code:'low',amount_kobo:'230000'},{package_code:'mid',amount_kobo:'480000'}]}})},
  };
  vm.runInNewContext(source,context);
  const definition=context.component;
  const state=definition.data();
  for(const [name,fn] of Object.entries(definition.methods)) state[name]=fn.bind(state);
  for(const [name,fn] of Object.entries(definition.computed)) Object.defineProperty(state,name,{get:()=>fn.call(state)});
  state.loadMine=async()=>{};
  return {state,calls,storage,authorize:()=>{cancelled=false;},fail:()=>{networkFailure=true;}};
}
test('cancelled payment remains resumable while another purchase gets its own reference',async()=>{
  const h=setup(),s=h.state;
  s.quote={id:'first'};s.selected={name:'First plan'};
  await s.purchase();
  assert.equal(s.busy,false);assert.equal(s.selected,null);assert.equal(s.savedPayments.length,1);
  const first=s.savedPayments[0];assert.equal(first.cancelled,true);
  h.authorize();s.quote={id:'second'};s.selected={name:'Second plan'};
  await s.purchase();
  assert.equal(h.calls[0].quote_id,'second');assert.notEqual(h.calls[0].reference,first.reference);
  assert.equal(s.savedPayments.length,1);
  await s.purchase(first);
  assert.equal(h.calls[1].quote_id,'first');assert.equal(h.calls[1].reference,first.reference);
  assert.equal(s.savedPayments.length,0);
});
test('unknown outcome keeps the original reference and clears the before-payment cancellation flag',async()=>{
  const h=setup(),s=h.state;s.quote={id:'first'};await s.purchase();
  const first=s.savedPayments[0];h.authorize();h.fail();await s.purchase(first);
  assert.equal(s.savedPayments.length,1);assert.equal(s.savedPayments[0].cancelled,false);
  assert.equal(s.savedPayments[0].reference,first.reference);
});
test('plans sort numerically from cheapest and preselect the cheapest',async()=>{
  const {state:s}=setup();s.location='GB';await s.loadPlans();
  assert.equal(s.plans.map(p=>p.package_code).join(','),'low,mid,high');
  assert.equal(s.chosen.package_code,'low');
});
