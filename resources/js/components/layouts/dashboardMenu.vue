<template>
  <div class="swift-quick-grid">
    <a href="/dashboard/data" class="swift-quick-tile swift-service-data">
      <span class="swift-router-content"><span class="swift-quick-icon"><i class="fas fa-broadcast-tower"></i></span><strong>Data</strong></span>
    </a>

    <a href="/dashboard/airtime" class="swift-quick-tile swift-service-airtime">
      <span class="swift-router-content"><span class="swift-quick-icon"><i class="fas fa-phone"></i></span><strong>Airtime</strong></span>
    </a>

    <a href="/dashboard/talkmore" class="swift-quick-tile swift-service-talkmore">
      <span class="swift-router-content"><span class="swift-quick-icon"><i class="fas fa-comments"></i></span><strong>Talk More Airtime</strong></span>
    </a>

    <a href="/dashboard/electricity" class="swift-quick-tile swift-service-electricity">
      <span class="swift-router-content"><span class="swift-quick-icon"><i class="fas fa-lightbulb"></i></span><strong>Electricity</strong></span>
    </a>

    <a href="/dashboard/cable" class="swift-quick-tile swift-service-cable">
      <span class="swift-router-content"><span class="swift-quick-icon"><i class="fas fa-tv"></i></span><strong>Cable TV</strong></span>
    </a>

    <a href="/dashboard/exams" class="swift-quick-tile swift-service-exam">
      <span class="swift-router-content"><span class="swift-quick-icon"><i class="fas fa-graduation-cap"></i></span><strong>Exam PIN</strong></span>
    </a>

    <a href="/dashboard/airtime2cash" class="swift-quick-tile swift-service-cash">
      <span class="swift-router-content"><span class="swift-quick-icon swift-phone-cash">
        <i class="fas fa-mobile-alt"></i><small>₦</small>
      </span>
      <strong>Airtime 2 Cash</strong></span>
    </a>

    <button type="button" class="swift-quick-tile swift-service-more" aria-haspopup="dialog" @click="moreOpen = true">
      <span class="swift-router-content"><span class="swift-quick-icon"><i class="fas fa-ellipsis-h"></i></span><strong>More</strong></span>
    </button>

    <div v-if="moreOpen" class="swift-sheet-backdrop" role="presentation" @click.self="moreOpen = false">
      <section class="swift-bottom-sheet" role="dialog" aria-modal="true" aria-labelledby="more-services-title">
        <div class="swift-sheet-handle" aria-hidden="true"></div>
        <header class="swift-sheet-header">
          <h2 id="more-services-title">More Services</h2>
          <button type="button" aria-label="Close" @click="moreOpen = false">&times;</button>
        </header>
        <div class="swift-more-services">
          <a href="/dashboard/sms-disclaimer"><span><i class="fas fa-comment-dots"></i></span><strong>Bulk SMS</strong><i class="fas fa-chevron-right"></i></a>
          <a href="/dashboard/withdraw"><span><i class="fas fa-university"></i></span><strong>Withdraw to Bank</strong><i class="fas fa-chevron-right"></i></a>
          <a href="/dashboard/referrals"><span><i class="fas fa-exchange-alt"></i></span><strong>Transfer Bonus</strong><i class="fas fa-chevron-right"></i></a>
          <a href="/dashboard/profile"><span><i class="fas fa-user-cog"></i></span><strong>Profile &amp; Settings</strong><i class="fas fa-chevron-right"></i></a>
        </div>
      </section>
    </div>
  </div>
</template>

<script>
export default {
  name: 'DashboardMenu',
  data() {
    return { moreOpen: false };
  },
};
</script>
