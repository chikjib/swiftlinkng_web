const protectedEndpoints = [
    "/api/purchase/airtime",
    "/api/purchase/data",
    "/api/purchase/talkmore",
    "/api/purchase/cable",
    "/api/purchase/electricity",
    "/api/purchase/exam",
    "/api/purchase/a2cash",
    "/api/send/sms",
    "/api/fund/withdraw",
    "/api/user/bonus/transfer",
];

let pinPromptPromise = null;
let processingCount = 0;

function injectStyles() {
    if (document.getElementById("swift-transaction-security-styles")) return;
    const style = document.createElement("style");
    style.id = "swift-transaction-security-styles";
    style.textContent = `
      .swift-pin-layer,.swift-processing-layer{position:fixed;inset:0;z-index:100000;background:rgba(9,10,13,.78);display:flex;align-items:center;justify-content:center;padding:20px;backdrop-filter:blur(3px)}
      .swift-pin-card{width:min(100%,390px);border-radius:24px;background:#fff;color:#17171c;padding:24px;box-shadow:0 24px 70px rgba(0,0,0,.35)}
      .swift-pin-title{font-size:22px;font-weight:800;margin:0 0 6px}.swift-pin-copy{color:#666975;margin:0 0 18px;line-height:1.45}
      .swift-pin-input{width:100%;height:54px;border:1.5px solid #d9dae0;border-radius:14px;padding:0 16px;font-size:24px;font-weight:800;letter-spacing:10px;text-align:center;outline:none}.swift-pin-input:focus{border-color:#e30613;box-shadow:0 0 0 3px rgba(227,6,19,.1)}
      .swift-pin-actions{display:flex;gap:10px;margin-top:18px}.swift-pin-actions button{flex:1;height:48px;border-radius:13px;border:1px solid #e30613;font-weight:800}.swift-pin-cancel{background:#fff;color:#e30613}.swift-pin-submit{background:#e30613;color:#fff}.swift-pin-error{min-height:20px;color:#c1121f;font-size:13px;font-weight:700;margin-top:8px}.swift-pin-forgot{display:block;margin-top:15px;color:#e30613;font-size:13px;font-weight:800;text-align:center;text-decoration:none}.swift-pin-forgot:hover,.swift-pin-forgot:focus{color:#b40008;text-decoration:none}
      .swift-processing-card{text-align:center;color:#fff}.swift-processing-logo{width:82px;height:82px;object-fit:contain;animation:swiftLogoPulse 1.15s ease-in-out infinite}.swift-processing-title{font-size:20px;font-weight:800;margin-top:16px}.swift-processing-copy{color:rgba(255,255,255,.78);margin-top:4px}
      @keyframes swiftLogoPulse{0%{transform:rotate(0) scale(.9);opacity:.72}50%{transform:rotate(180deg) scale(1.08);opacity:1}100%{transform:rotate(360deg) scale(.9);opacity:.72}}
    `;
    document.head.appendChild(style);
}

function pinModal({ setup }) {
    injectStyles();
    return new Promise((resolve) => {
        const layer = document.createElement("div");
        layer.className = "swift-pin-layer";
        layer.innerHTML = `<div class="swift-pin-card" role="dialog" aria-modal="true">
          <h2 class="swift-pin-title">${setup ? "Create transaction PIN" : "Enter transaction PIN"}</h2>
          <p class="swift-pin-copy">${setup ? "Choose a 4-digit PIN to protect purchases and withdrawals." : "Confirm this transaction with your 4-digit PIN."}</p>
          <input class="swift-pin-input" type="password" inputmode="numeric" maxlength="4" autocomplete="one-time-code" aria-label="Transaction PIN">
          ${setup ? '<input class="swift-pin-input swift-pin-confirm" style="margin-top:10px" type="password" inputmode="numeric" maxlength="4" autocomplete="one-time-code" aria-label="Confirm transaction PIN" placeholder="Confirm PIN">' : ""}
          <div class="swift-pin-error"></div>
          <div class="swift-pin-actions"><button class="swift-pin-cancel" type="button">Cancel</button><button class="swift-pin-submit" type="button">${setup ? "Create PIN" : "Continue"}</button></div>
          ${setup ? "" : '<a class="swift-pin-forgot" href="/dashboard/profile">Forgot PIN? Reset it securely</a>'}
        </div>`;
        document.body.appendChild(layer);
        const pin = layer.querySelector(".swift-pin-input");
        const confirm = layer.querySelector(".swift-pin-confirm");
        const error = layer.querySelector(".swift-pin-error");
        const finish = (value) => { layer.remove(); resolve(value); };
        const submit = () => {
            const value = pin.value.trim();
            if (!/^\d{4}$/.test(value)) { error.textContent = "Enter exactly four digits."; return; }
            if (setup && confirm.value.trim() !== value) { error.textContent = "The PINs do not match."; return; }
            finish({ pin: value, confirmation: setup ? confirm.value.trim() : value });
        };
        layer.querySelector(".swift-pin-cancel").addEventListener("click", () => finish(null));
        layer.querySelector(".swift-pin-submit").addEventListener("click", submit);
        [pin, confirm].filter(Boolean).forEach((input) => input.addEventListener("keydown", (event) => { if (event.key === "Enter") submit(); }));
        setTimeout(() => pin.focus(), 30);
    });
}

async function requestPin(axios) {
    if (pinPromptPromise) return pinPromptPromise;
    pinPromptPromise = (async () => {
        const status = await axios.get("/api/transaction-pin/status", { swiftSkipTransactionSecurity: true });
        const hasPin = !!(status.data && status.data.data && status.data.data.has_transaction_pin);
        const entered = await pinModal({ setup: !hasPin });
        if (!entered) throw new axios.Cancel("Transaction cancelled.");
        if (!hasPin) {
            await axios.post("/api/transaction-pin", {
                pin: entered.pin,
                pin_confirmation: entered.confirmation,
            }, { swiftSkipTransactionSecurity: true });
        }
        return entered.pin;
    })();
    try { return await pinPromptPromise; } finally { pinPromptPromise = null; }
}

function isProtected(config) {
    if (config.swiftSkipTransactionSecurity) return false;
    const method = String(config.method || "get").toLowerCase();
    const url = String(config.url || "").split("?")[0];
    return method !== "get" && protectedEndpoints.some((endpoint) => url.endsWith(endpoint));
}

function showProcessing() {
    injectStyles();
    processingCount += 1;
    if (document.getElementById("swift-processing-layer")) return;
    const layer = document.createElement("div");
    layer.id = "swift-processing-layer";
    layer.className = "swift-processing-layer";
    layer.innerHTML = `<div class="swift-processing-card" role="status" aria-live="polite">
      <img class="swift-processing-logo" src="/frontend/images/swiftlogo.png" alt="Swiftlinkng">
      <div class="swift-processing-title">Processing transaction…</div>
      <div class="swift-processing-copy">Please wait. Do not close or retry.</div>
    </div>`;
    document.body.appendChild(layer);
}

function hideProcessing() {
    processingCount = Math.max(0, processingCount - 1);
    if (processingCount === 0) document.getElementById("swift-processing-layer")?.remove();
}

export function installTransactionSecurity(axios) {
    axios.interceptors.request.use(async (config) => {
        if (!isProtected(config)) return config;
        const pin = await requestPin(axios);
        config.headers = config.headers || {};
        config.headers["X-Transaction-Pin"] = pin;
        config.swiftTransactionRequest = true;
        showProcessing();
        return config;
    });
    axios.interceptors.response.use(
        (response) => { if (response.config && response.config.swiftTransactionRequest) hideProcessing(); return response; },
        (error) => { if (error.config && error.config.swiftTransactionRequest) hideProcessing(); return Promise.reject(error); }
    );
}
