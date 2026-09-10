<template>
  <main class="swift-update-page" aria-labelledby="update-page-title">
    <header class="swift-update-page-header">
      <a href="/dashboard" class="swift-update-back" aria-label="Back to dashboard">
        <i class="fas fa-arrow-left"></i>
      </a>
      <div>
        <small>{{ update.eyebrow || 'Updates for You' }}</small>
        <h1 id="update-page-title">{{ update.title || 'Latest update' }}</h1>
      </div>
    </header>

    <section v-if="loading" class="swift-update-detail-card swift-update-loading" aria-live="polite">
      <span></span><span></span><span></span>
    </section>

    <section v-else class="swift-update-detail-card">
      <span class="swift-update-detail-icon"><i class="fas fa-bell"></i></span>
      <p v-if="update.message" class="swift-update-lead">{{ update.message }}</p>
      <div class="swift-update-body">{{ update.details || update.message || 'More information will be available soon.' }}</div>
      <a v-if="update.link" :href="update.link" target="_blank" rel="noopener" class="swift-update-primary">
        Learn more <i class="fas fa-arrow-right"></i>
      </a>
      <a href="/dashboard" class="swift-update-secondary">
        <i class="fas fa-home"></i> Back to dashboard
      </a>
    </section>
  </main>
</template>

<script>
export default {
  name: 'UpdateDetails',
  data() {
    return {
      loading: true,
      update: {
        eyebrow: 'Updates for You',
        title: 'Latest update',
        message: '',
        details: '',
        link: '',
      },
    };
  },
  mounted() {
    axios.get('/api/app/dashboard-update')
      .then(({ data }) => {
        if (data && data.data) this.update = { ...this.update, ...data.data };
      })
      .catch(() => this.$toasted.show('Unable to load this update right now.'))
      .finally(() => { this.loading = false; });
  },
};
</script>

<style scoped>
.swift-update-page { width: min(100%, 820px); min-height: calc(100dvh - 190px); margin: 0 auto; padding: 4px 0 36px; }
.swift-update-page-header { display: flex; align-items: center; gap: 16px; margin-bottom: 20px; }
.swift-update-page-header small { display: block; color: #e30613; font-weight: 800; }
.swift-update-page-header h1 { margin: 2px 0 0; color: var(--swift-ink, #202127); font-size: clamp(1.45rem, 4vw, 2rem); font-weight: 900; }
.swift-update-back { display: grid; width: 46px; height: 46px; flex: 0 0 46px; place-items: center; color: #e30613; background: #ffedef; border-radius: 14px; text-decoration: none; }
.swift-update-detail-card { padding: clamp(22px, 5vw, 42px); color: var(--swift-ink, #202127); background: var(--swift-card, #fff); border: 1px solid rgba(227, 6, 19, .13); border-radius: 24px; box-shadow: 0 18px 45px rgba(25, 25, 30, .08); }
.swift-update-detail-icon { display: grid; width: 64px; height: 64px; margin-bottom: 22px; place-items: center; color: #e30613; background: #ffedef; border-radius: 20px; font-size: 1.65rem; }
.swift-update-lead { margin: 0 0 18px; color: #e30613; font-size: clamp(1.05rem, 2vw, 1.22rem); font-weight: 850; }
.swift-update-body { min-height: 120px; white-space: pre-line; color: var(--swift-muted, #555861); font-size: 1rem; line-height: 1.75; }
.swift-update-primary, .swift-update-secondary { display: flex; min-height: 50px; align-items: center; justify-content: center; gap: 9px; margin-top: 24px; padding: 12px 20px; border-radius: 14px; font-weight: 850; text-decoration: none !important; }
.swift-update-primary { color: #fff !important; background: linear-gradient(135deg, #e30613, #b40008); }
.swift-update-secondary { color: #e30613 !important; border: 1px solid rgba(227, 6, 19, .3); }
.swift-update-loading span { display: block; height: 18px; margin: 14px 0; background: #f1ecee; border-radius: 8px; animation: pulse 1.2s ease-in-out infinite alternate; }
.swift-update-loading span:first-child { width: 55%; }
.swift-update-loading span:last-child { width: 78%; }
@keyframes pulse { to { opacity: .42; } }
@media (prefers-color-scheme: dark) {
  .swift-update-detail-card { color: #fff; background: #1b1d22; border-color: #3c2a2d; }
  .swift-update-page-header h1 { color: #fff; }
  .swift-update-body { color: #d7d7dc; }
  .swift-update-secondary { color: #fff !important; border-color: #63383d; }
  .swift-update-loading span { background: #32343a; }
}
</style>
