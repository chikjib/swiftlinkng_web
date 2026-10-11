// Read delimited text without converting phone numbers to numeric values.
export function parseSmsRecipients(text) {
  const cells = [];
  let cell = '', quoted = false;
  text = String(text).replace(/^\uFEFF/, '');
  for (let i = 0; i < text.length; i++) {
    const char = text[i];
    if (char === '"') {
      if (quoted && text[i + 1] === '"') { cell += '"'; i++; }
      else if (quoted || !cell.trim()) quoted = !quoted;
      else throw new Error('Invalid CSV quotes. Use a file containing phone numbers only.');
    } else if (!quoted && /[,;\t\r\n]/.test(char)) {
      cells.push(cell); cell = '';
    } else cell += char;
  }
  if (quoted) throw new Error('The CSV contains an unclosed quote. Please correct the file.');
  cells.push(cell);
  const numbers = [], seen = new Set();
  let invalid = 0, duplicates = 0;
  for (const value of cells) {
    const raw = value.trim();
    if (!raw || /^(phone(\s*number)?|mobile(\s*number)?|number|recipients?)$/i.test(raw)) continue;
    const number = raw.replace(/[\s()-]/g, '');
    if (!/^\+?\d{10,15}$/.test(number)) { invalid++; continue; }
    const key = recipientKey(number);
    if (seen.has(key)) { duplicates++; continue; }
    seen.add(key); numbers.push(number);
  }
  return { numbers, invalid, duplicates };
}

export function recipientKey(number) {
  const digits = number.replace(/\D/g, '');
  return /^0\d{10}$/.test(digits) ? '234' + digits.slice(1) : digits;
}
