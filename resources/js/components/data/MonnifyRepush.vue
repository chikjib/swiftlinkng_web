<template>
  <div class="col-xl-8 col-lg-9">
    <div class="card mb-4">
      <div class="card-header py-3"><h1 class="h5 mb-0">Monnify Repush</h1></div>
      <div class="card-body">
        <p>Enter a successful Monnify transaction reference. The customer’s wallet will receive the settlement amount after fees, subject to existing funding recoveries.</p>
        <div v-if="success" class="alert alert-success" role="status">{{ success }}</div>
        <div v-if="error" class="alert alert-danger" role="alert">{{ error }}</div>
        <form @submit.prevent="repush">
          <div class="form-group">
            <label for="monnify-reference">Transaction reference</label>
            <input id="monnify-reference" v-model.trim="reference" class="form-control" type="text" required maxlength="255" :disabled="loading" placeholder="MNFY|67|20220725111957|000283" />
          </div>
          <button class="btn btn-primary" type="submit" :disabled="loading || !reference">{{ loading ? 'Repushing…' : 'Repush' }}</button>
        </form>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  data() { return { reference: '', loading: false, success: '', error: '' }; },
  methods: {
    async repush() {
      if (this.loading) return;
      this.loading = true;
      this.success = '';
      this.error = '';
      try {
        const { data } = await axios.post('/api/monnify/repush', { transaction_reference: this.reference }, {
          headers: { Authorization: `Bearer ${window.localStorage.getItem('token')}` },
        });
        this.success = `${data.message} ${data.data.email} credited ₦${Number(data.data.amount).toFixed(2)}. Reference: ${data.data.reference}`;
        this.reference = '';
      } catch (error) {
        const response = error.response?.data;
        this.error = response?.errors?.transaction_reference?.[0] || response?.message || 'Unable to complete repush. Please retry.';
      } finally {
        this.loading = false;
      }
    },
  },
};
</script>
