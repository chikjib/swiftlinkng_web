const test=require('node:test');
const assert=require('node:assert/strict');
const fs=require('node:fs');
const vm=require('node:vm');
function component(file,context) {
  const source=fs.readFileSync(file,'utf8').split('<script>')[1].split('</script>')[0].replace(/^import .*;$/gm,'').replace('export default','component =');
  vm.runInNewContext(source,context);
  const definition=context.component,state=definition.data();
  for(const [key,method] of Object.entries(definition.methods)) state[key]=method.bind(state);
  return state;
}
function setup() {
  const storage=new Map([['token','old-token']]),redirects=[],calls=[];
  const localStorage={getItem:k=>storage.get(k)||null,setItem:(k,v)=>storage.set(k,v)};
  const axios={post:async(url,body,options)=>{calls.push({url,body,options});return {data:{data:{requestSuccessful:true,responseBody:{checkoutUrl:'https://checkout.monnify.com/test',transactionReference:'MNFY|TEST'}}}};}};
  const context={component:null,URL,axios,localStorage,window:{localStorage,location:{assign:url=>redirects.push(url)}},VclTwitch:{}};
  const state=component('resources/js/components/data/FundWallet.vue',context);
  state.form.amount=1000;
  return {state,context,storage,redirects,calls,axios};
}
test('ATM funding creates a checkout with the current token and stores its reference',async()=>{
  const h=setup();h.storage.set('token','new-token');await h.state.makePayment();
  assert.equal(h.calls[0].url,'/api/generate-payment-link');assert.equal(h.calls[0].body.amount,1000);
  assert.equal(h.calls[0].options.headers.Authorization,'Bearer new-token');
  assert.equal(h.storage.get('transactionReference'),'MNFY|TEST');assert.equal(h.redirects[0],'https://checkout.monnify.com/test');
});
test('invalid amounts never contact the gateway',async()=>{
  for(const amount of ['',0,99,-1,'invalid',Infinity]) {const h=setup();h.state.form.amount=amount;await h.state.makePayment();assert.equal(h.calls.length,0);assert.match(h.state.errorflag,/at least/);}
});
test('provider rejection, missing URL and insecure URL never redirect',async()=>{
  for(const payload of [{requestSuccessful:false,responseMessage:'Card funding unavailable'},{requestSuccessful:true,responseBody:{}},{requestSuccessful:true,responseBody:{checkoutUrl:'http://checkout.monnify.com/test'}}]) {
    const h=setup();h.axios.post=async()=>({data:{data:payload}});await h.state.makePayment();assert.equal(h.redirects.length,0);assert.ok(h.state.errorflag);assert.equal(h.state.loading,false);
  }
});
test('network errors allow retry',async()=>{
  const h=setup();h.axios.post=async()=>{throw new Error('Network Error');};await h.state.makePayment();assert.equal(h.state.errorflag,'Network Error');assert.equal(h.state.loading,false);
});
test('repeated clicks do not open duplicate checkout requests',async()=>{
  const h=setup();let complete,count=0;h.axios.post=()=>{count++;return new Promise(r=>complete=r);};
  const first=h.state.makePayment();await h.state.makePayment();assert.equal(count,1);
  complete({data:{data:{requestSuccessful:true,responseBody:{checkoutUrl:'https://checkout.monnify.com/test'}}}});await first;
});
test('return from checkout verifies URL paymentReference and shows successful payment',async()=>{
  const h=setup();await h.state.makePayment();let request;
  h.axios.get=async(...args)=>{request=args;return {data:{data:{requestSuccessful:true,responseBody:{paymentStatus:'PAID'}}}};};
  const callback=component('resources/js/components/callback.vue',h.context);callback.$route={query:{paymentReference:'2813922764'}};
  await callback.verifyPayment();assert.equal(request[0],'/api/verify-payment/2813922764');assert.equal(request[1].params.reference_type,'paymentReference');assert.equal(callback.successflag,'Payment successful');
});
test('unpaid return never reports success',async()=>{
  const h=setup();h.axios.get=async()=>({data:{data:{requestSuccessful:true,responseBody:{paymentStatus:'PENDING'}}}});
  const callback=component('resources/js/components/callback.vue',h.context);callback.$route={query:{paymentReference:'2813922764'}};await callback.verifyPayment();assert.equal(callback.successflag,'');assert.match(callback.pendingflag,/PENDING/);
});
