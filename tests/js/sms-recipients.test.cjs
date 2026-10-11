const test = require('node:test');
const assert = require('node:assert/strict');
const parser = import('../../resources/js/utils/smsRecipients.mjs');

test('imports TXT lines and CSV cells while preserving leading zeros', async () => {
  const {parseSmsRecipients} = await parser;
  assert.deepEqual(parseSmsRecipients('\uFEFFPhone number\r\n"08123456789"\r\n"+44 7700 900123",08012345678').numbers,
    ['08123456789', '+447700900123', '08012345678']);
});
test('skips duplicates across Nigerian local and international formats', async () => {
  const {parseSmsRecipients} = await parser;
  assert.deepEqual(parseSmsRecipients('08123456789,+2348123456789,2348123456789'),
    {numbers:['08123456789'],duplicates:2,invalid:0});
});
test('reports invalid values without extracting numbers from names or spreadsheet formulas', async () => {
  const {parseSmsRecipients} = await parser;
  assert.deepEqual(parseSmsRecipients('number\n"Doe, Jane"\n8.12345E+09\n123'), {numbers:[],duplicates:0,invalid:3});
  assert.throws(() => parseSmsRecipients('="08123456789"'));
  assert.throws(() => parseSmsRecipients('"08123456789'));
});
