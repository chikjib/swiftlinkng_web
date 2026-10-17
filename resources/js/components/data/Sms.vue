<template>
  <main class="swift-service-page">
    <h1 class="swift-service-heading"><button class="swift-back" type="button" @click="$router.back()"><i class="fas fa-arrow-left"></i></button>Bulk SMS</h1>
    <div v-if="successflag" class="alert alert-success swift-alert">{{ successflag }}</div><div v-if="errorflag" class="alert alert-danger swift-alert">{{ errorflag }}</div>
    <form class="swift-form-stack" @submit.prevent="sendsms">
      <div class="swift-form-group"><label for="sender-id">Sender ID</label><div class="swift-field-wrap"><i class="fas fa-signature"></i><input id="sender-id" v-model="form.senderID" maxlength="13" required class="form-control" placeholder="Your sender name" /></div></div>
      <div class="swift-form-group"><label for="sms-numbers">Recipients</label><textarea id="sms-numbers" v-model="form.phone" required class="form-control" rows="4" placeholder="Enter phone numbers separated by commas, e.g. 08123456789,08140003288"></textarea></div>
      <details class="sms-import-option">
        <summary>Or add recipients from a file <span>(optional)</span></summary>
        <div class="swift-form-group sms-import-fields">
        <label for="sms-recipient-file">Choose a text file (.txt or .csv)</label>
        <input id="sms-recipient-file" type="file" accept=".txt,.csv,text/plain,text/csv" class="form-control" :disabled="isloading || importing" aria-describedby="sms-upload-help" @change="importRecipients" />
        <small id="sms-upload-help">Enter numbers only, one number per line or comma-separated. Text format, max 2 MB.</small>
        <p v-if="importing" role="status">Importing numbers…</p>
        <p v-if="importSummary" role="status">{{ importSummary }}</p>
        <p v-if="importError" class="text-danger" role="alert">{{ importError }}</p>
        </div>
      <section class="swift-service-card">
        <h2>Saved recipient lists</h2>
        <p>Saving is optional. Imported numbers are only saved when you choose “Save list”.</p>
        <label for="sms-list-name">List name</label>
        <input id="sms-list-name" v-model="listName" class="form-control" maxlength="100" placeholder="e.g. Customers" :disabled="listBusy" />
        <button type="button" class="btn btn-outline-danger mt-2" :disabled="listBusy || importing || isloading || !recipientCount || !listName.trim()" @click="saveRecipientList">{{ listBusy ? 'Please wait…' : 'Save list' }}</button>
        <p v-if="listNotice" role="status">{{ listNotice }}</p>
        <p v-if="listError" class="text-danger" role="alert">{{ listError }}</p>
        <button v-if="listsFailed" type="button" class="btn btn-outline-secondary" @click="loadRecipientLists">Retry loading lists</button>
        <p v-if="listsLoading" role="status">Loading saved lists…</p>
        <div v-for="list in recipientLists" :key="list.id" class="sms-saved-list">
          <span><strong>{{ list.name }}</strong><small>{{ list.recipients.length }} recipient(s)</small></span>
          <button type="button" class="btn btn-outline-danger" :disabled="isloading || importing || listBusy" @click="useRecipientList(list)">Add to recipients</button>
          <button type="button" class="btn btn-outline-secondary" :disabled="listBusy" @click="deleteRecipientList(list)">Delete</button>
        </div>
      </section>
      </details>
      <div class="swift-form-group"><label for="sms-message">Message</label><textarea id="sms-message" v-model="message" required class="form-control" rows="5" placeholder="Type your message"></textarea><div class="d-flex justify-content-between mt-2"><span>Page {{ page }}</span><strong>{{ message.length }} / {{ page * 160 }}</strong></div></div>
      <section class="swift-service-card swift-price-note"><span>Estimated total</span><strong>₦{{ totalCost.toFixed(2) }}</strong></section>
      <p>{{ recipientCount }} recipient(s) × {{ page }} page(s) × ₦{{ Number(form.amount || 0).toFixed(2) }} per recipient per page</p>
      <button class="swift-primary-action" type="submit" :disabled="isloading || importing">{{ isloading ? 'Sending…' : 'Send SMS' }}</button>
      <section class="swift-service-card sms-guidelines" aria-labelledby="sms-guidelines-heading">
        <h2 id="sms-guidelines-heading">Important Guidelines Before Sending SMS</h2>
        <ul>
          <li>SMS only deliver between 8am to 8pm</li>
          <li><strong>DND Restrictions:</strong> Numbers on DND may not receive promotional SMS. Always send a test message before sending in bulk.</li>
          <li><strong>Restricted Names:</strong> Avoid mentioning banks or payment companies in your sender ID or message.</li>
          <li><strong>International Brands:</strong> Do not include names like WhatsApp or Facebook.</li>
          <li><strong>Number Formatting:</strong> Avoid 4 or more consecutive digits, as networks may flag them as OTPs. Use 5,000 instead of 5000.</li>
          <li><strong>SMS Length:</strong> One SMS contains 160 characters. Longer messages are split into 153-character pages. The ₦ symbol is converted to N.</li>
          <li><strong>Sender ID Safety:</strong> Violating these guidelines may result in your sender ID being blocked by mobile networks.</li>
        </ul>
      </section>
    </form>
  </main>
</template>

<script>
import { parseSmsRecipients, recipientKey } from "../../utils/smsRecipients.mjs";
const token = window.localStorage.getItem("token");

export default {
  // props: ["user"],

  data() {
    return {
      user: this.auth.user,
      subcategories: {},
      plans: {},

      form: {},

      category_id: null,
      subcategory_id: null,
      plan_id: null,
      phonenumber: null,
      amount: 0,
      loading: false,
      successflag: "",
      errorflag: "",
      page: 1,
      message: "",
      isloading: false,
      importing: false,
      importSummary: "",
      importError: "",
      recipientLists: [], listName: "", listBusy: false, listsLoading: false, listsFailed: false, listError: "", listNotice: "",
    };
  },

  mounted() {
    this.loadplan();
    this.loadRecipientLists();
  },

  computed: {
    recipients() { return String(this.form.phone || '').split(/[,;\r\n]+/).map(n => n.trim()).filter(Boolean); },
    recipientCount() { return this.recipients.length; },
    totalCost() { return Math.round(Number(this.form.amount || 0) * 100) * this.recipientCount * this.page / 100; },
  },

  watch: {
    message(after, before) {
      this.page = Math.max(1, Math.ceil(this.message.length / 160));
    },
  },

  methods: {
    listRequestOptions() { return {headers: {Authorization: `Bearer ${token}`}}; },
    async loadRecipientLists() {
      this.listsLoading = true;
      this.listsFailed = false;
      this.listError = '';
      try { this.recipientLists = (await axios.get('/api/sms/recipient-lists', this.listRequestOptions())).data.data; }
      catch { this.listsFailed = true; this.listError = 'Unable to load saved lists. You can still enter or import numbers.'; }
      finally { this.listsLoading = false; }
    },
    async saveRecipientList() {
      if (this.listBusy || this.importing || this.isloading) return;
      this.listBusy = true; this.listError = ''; this.listNotice = '';
      try {
        const parsed = parseSmsRecipients(this.form.phone || '');
        if (parsed.invalid || !parsed.numbers.length) throw new Error('Correct invalid recipients before saving the list.');
        if (parsed.numbers.length > 1000) throw new Error('Save up to 1,000 numbers per list.');
        const response = await axios.post('/api/sms/recipient-lists', {name: this.listName.trim(), recipients: parsed.numbers}, this.listRequestOptions());
        this.recipientLists.unshift(response.data.data);
        this.listName = ''; this.listNotice = 'List saved to your account for later use.';
      } catch (error) { this.listError = Object.values(error.response?.data?.errors || {}).flat()[0] || error.response?.data?.message || error.message || 'Unable to save the list.'; }
      finally { this.listBusy = false; }
    },
    useRecipientList(list) {
      const numbers = [...this.recipients];
      const seen = new Set(numbers.map(recipientKey));
      let added = 0;
      for (const number of list.recipients) {
        if (!seen.has(recipientKey(number))) { seen.add(recipientKey(number)); numbers.push(number); added++; }
      }
      this.form.phone = numbers.join(',');
      this.listNotice = `${added} number(s) added from “${list.name}”. Review recipients before sending.`;
    },
    async deleteRecipientList(list) {
      if (!window.confirm(`Delete saved list “${list.name}”? Current recipients will stay in the form.`)) return;
      this.listBusy = true; this.listError = ''; this.listNotice = '';
      try {
        await axios.delete(`/api/sms/recipient-lists/${list.id}`, this.listRequestOptions());
        this.recipientLists = this.recipientLists.filter(item => item.id !== list.id);
        this.listNotice = 'Saved list deleted.';
      } catch { this.listError = 'Unable to delete the list. Please try again.'; }
      finally { this.listBusy = false; }
    },
    async importRecipients(event) {
      const input = event.target;
      const file = input.files?.[0];
      if (!file) return;
      this.importSummary = '';
      this.importError = '';
      this.importing = true;
      try {
        if (!/\.(txt|csv)$/i.test(file.name)) throw new Error('Please select a .txt or .csv file.');
        if (file.size > 2 * 1024 * 1024) throw new Error('Choose a file smaller than 2 MB.');
        const parsed = parseSmsRecipients(await file.text());
        if (!parsed.numbers.length) throw new Error('No valid phone numbers found. Use 10–15 digits per number, with an optional + prefix.');
        const existing = this.recipients;
        const seen = new Set(existing.map(recipientKey));
        const added = [];
        let duplicates = parsed.duplicates;
        for (const number of parsed.numbers) {
          const key = recipientKey(number);
          if (seen.has(key)) { duplicates++; continue; }
          seen.add(key);
          added.push(number);
        }
        this.form.phone = [...existing, ...added].join(',');
        this.importSummary = `${added.length} number(s) added. ${duplicates} duplicate(s) and ${parsed.invalid} invalid entry/entries skipped. Review the recipients before sending.`;
      } catch (error) {
        this.importError = error.message || 'Unable to read this file. Please try again.';
      } finally {
        this.importing = false;
        input.value = '';
      }
    },

    sendsms() {
      if (this.isloading || this.importing) return;
      this.form.phone = this.recipients.join(',');
      if (!this.recipientCount) { this.errorflag = 'Enter at least one recipient.'; return; }
      this.form.message = this.message;
      this.isloading = !this.isloading;

      axios
        .post(`/api/send/sms`, this.form, {
          headers: {
            Authorization: `Bearer ${token}`,
            "Content-Type": "application/json",
          },
        })
        .then((response) => {
        //   console.log(response.data);
          this.successflag = response.data.message;
          this.$toasted.show(response.data.message);
          this.isloading = false;
        //   this.$router.go(this.$router.currentRoute)
        })
        .catch((error) => {
        //   console.log(error);
          this.errorflag = error.response.data.message;
          this.$toasted.show(error.response.data.message);
          this.isloading = false;
        //   this.$router.go(this.$router.currentRoute)
        });
    },

    loadplan() {
      axios
        .get(`/api/subcategory?category_id=11`, {
          headers: {
            Authorization: `Bearer ${token}`,
            "Content-Type": "application/json",
          },
        })
        .then((response) => {
          // console.log(response.data);
          this.subcategories = response.data;

          this.showPlans();
        });
    },

    showPlans() {
      this.loading = true;

      var c = this.subcategories.data.filter((sub) => sub.title === "BULKSMS");

      var content = JSON.parse(c[0].products);
      console.log(content);

      this.form.amount = this.getUserLevel(content);
      console.log(this.form.amount);
      this.loading = false;
    },

    getUserLevel(content) {
      if (this.user.userlevel == 0) {
        return content.amount1;
      } else if (this.user.userlevel == 1) {
        return content.amount2;
      } else if (this.user.userlevel == 2) {
        return content.amount3;
      } else if (this.user.userlevel == 3) {
        return content.amount4;
      }
    },
  },
};
</script>

<style scoped>
.sms-import-option { padding: 12px 0; border-top: 1px solid var(--swift-border, #ddd); border-bottom: 1px solid var(--swift-border, #ddd); }
.sms-import-option summary { cursor: pointer; font-size: 14px; font-weight: 600; }
.sms-import-option summary span { font-weight: 400; }
.sms-import-fields { margin-top: 12px; }
.sms-import-fields small { display: block; margin-top: 8px; line-height: 1.5; }
.sms-guidelines { margin-top: 20px; }
.sms-guidelines ul { list-style-type: disc; padding-left: 22px; overflow: visible; }
.sms-guidelines li { float: none; border: 0; margin: 0 0 10px; border-radius: 0; line-height: 1.6; }
.sms-guidelines li:last-child { margin-bottom: 0; }
.sms-saved-list { display: flex; align-items: center; flex-wrap: wrap; gap: 10px; padding: 14px 0; border-bottom: 1px solid var(--swift-border, #ddd); }
.sms-saved-list > span { flex: 1; min-width: 140px; overflow-wrap: anywhere; }
.sms-saved-list small { display: block; }

img {
  width: 10vw;
  height: 10vw;
  padding: 10px;
}

input[type="radio"] {
  display: none;
}

img:hover {
  opacity: 0.6;
  cursor: pointer;
}

img:active {
  opacity: 0.4;
  cursor: pointer;
}

input[type="radio"]:checked + label > img {
  border: 2px solid #6777ef;
}

ul {
  list-style-type: none;
  margin: 0;
  padding: 0;
  overflow: hidden;
}

li {
  float: left;
  border: 2px solid #ccc;
  margin: 5px;
  border-radius: 10px;
}

li a {
  display: block;
  color: white;
  text-align: center;
  padding: 16px;
  text-decoration: none;
}

.centered {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
}
</style>
