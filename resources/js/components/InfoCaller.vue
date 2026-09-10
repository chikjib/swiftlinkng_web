<template>
  <div
    class="modal fade referral-promo-modal"
    id="infoModal"
    tabindex="-1"
    role="dialog"
    aria-labelledby="referralPromoTitle"
    aria-hidden="true"
  >
    <div class="modal-dialog modal-dialog-centered" role="document">
      <div class="modal-content referral-promo-card">
        <button type="button" class="promo-close" aria-label="Close" @click="close">
          <span aria-hidden="true">&times;</span>
        </button>

        <div class="promo-confetti promo-confetti-left">◆</div>
        <div class="promo-confetti promo-confetti-right">◆</div>
        <div class="promo-kicker">SWIFTLINK REFERRAL REWARDS</div>
        <h2 id="referralPromoTitle">Invite family<br />and friends</h2>
        <p class="promo-to-earn">TO EARN</p>
        <div class="promo-amount"><small>UP TO</small> ₦4,000</div>
        <p class="promo-bonus">BONUS AND MORE!</p>

        <div class="promo-gifts" aria-hidden="true">
          <span>🎁</span><span class="promo-main-gift">🎁</span><span>🎁</span>
        </div>

        <button type="button" class="promo-invite" @click="visitReferralProgram">
          <i class="fas fa-user-plus"></i>
          Invite now
        </button>
        <p class="promo-footnote">Earn ₦50 plus lifetime data-purchase commissions.</p>
      </div>
    </div>
  </div>
</template>

<script>
const VISITED_KEY = "swiftlink_referral_program_visited_at";
const REMINDER_AFTER_DAYS = 14;

export default {
  name: "InfoCaller",

  mounted() {
    if (this.shouldShow()) {
      this.$nextTick(() => $("#infoModal").modal("show"));
    }
  },

  beforeUnmount() {
    $("#infoModal").modal("hide");
  },

  methods: {
    shouldShow() {
      const visitedAt = Number(window.localStorage.getItem(VISITED_KEY) || 0);
      const reminderDelay = REMINDER_AFTER_DAYS * 24 * 60 * 60 * 1000;

      return !visitedAt || Date.now() - visitedAt >= reminderDelay;
    },

    close() {
      // Closing hides this appearance only. It will show on a later visit
      // until the customer opens the Referral Program.
      $("#infoModal").modal("hide");
    },

    visitReferralProgram() {
      window.localStorage.setItem(VISITED_KEY, String(Date.now()));
      $("#infoModal").modal("hide");
      this.$router.push("/dashboard/referrals");
    },
  },
};
</script>

<style scoped>
.referral-promo-modal {
  background: rgba(18, 3, 5, 0.72);
}

.referral-promo-modal .modal-dialog {
  max-width: 520px;
  padding: 18px;
}

.referral-promo-card {
  position: relative;
  overflow: hidden;
  border: 0;
  border-radius: 28px;
  padding: 42px 32px 28px;
  text-align: center;
  color: #211416;
  background:
    radial-gradient(circle at 15% 15%, rgba(255, 202, 126, 0.45), transparent 22%),
    linear-gradient(155deg, #ffffff 0%, #fff7f4 62%, #ffe4df 100%);
  box-shadow: 0 28px 80px rgba(52, 0, 8, 0.35);
}

.referral-promo-card::after {
  content: "";
  position: absolute;
  right: -90px;
  bottom: -105px;
  left: -90px;
  height: 220px;
  border-radius: 50% 50% 0 0;
  background: linear-gradient(135deg, #c90016, #f22434);
  z-index: 0;
}

.promo-close {
  position: absolute;
  z-index: 3;
  top: 16px;
  right: 16px;
  width: 44px;
  height: 44px;
  border: 4px solid #fff;
  border-radius: 50%;
  color: #fff;
  background: #cf001b;
  font-size: 29px;
  font-weight: 900;
  line-height: 30px;
  box-shadow: 0 5px 14px rgba(100, 0, 12, 0.28);
  cursor: pointer;
}

.promo-kicker {
  color: #7a5e62;
  font-size: 12px;
  font-weight: 800;
  letter-spacing: 0.16em;
}

.referral-promo-card h2 {
  margin: 10px 0 4px;
  color: #d00019;
  font-size: clamp(38px, 8vw, 58px);
  font-weight: 950;
  line-height: 0.92;
  letter-spacing: -0.045em;
  text-transform: uppercase;
}

.promo-to-earn {
  margin: 17px 0 0;
  font-weight: 900;
  letter-spacing: 0.22em;
}

.promo-amount {
  color: #d00019;
  font-size: clamp(48px, 11vw, 74px);
  font-weight: 950;
  line-height: 1;
}

.promo-amount small {
  display: inline-block;
  padding: 7px 9px;
  border-radius: 7px;
  color: #fff;
  background: #d00019;
  font-size: 13px;
  letter-spacing: 0.04em;
  vertical-align: middle;
}

.promo-bonus {
  margin: 5px 0 8px;
  color: #2b1e20;
  font-size: 23px;
  font-weight: 950;
}

.promo-gifts,
.promo-invite,
.promo-footnote {
  position: relative;
  z-index: 1;
}

.promo-gifts {
  display: flex;
  align-items: flex-end;
  justify-content: center;
  min-height: 105px;
  gap: 5px;
  font-size: 65px;
  line-height: 1;
}

.promo-main-gift {
  font-size: 94px;
}

.promo-invite {
  width: min(100%, 390px);
  margin-top: 3px;
  padding: 15px 22px;
  border: 4px solid rgba(255, 255, 255, 0.9);
  border-radius: 18px;
  color: #c90016;
  background: #fff;
  font-size: 23px;
  font-weight: 950;
  text-transform: uppercase;
  box-shadow: 0 8px 22px rgba(91, 0, 10, 0.28);
  cursor: pointer;
}

.promo-invite i {
  margin-right: 9px;
}

.promo-footnote {
  margin: 14px 0 0;
  color: #fff;
  font-size: 12px;
  font-weight: 700;
}

.promo-confetti {
  position: absolute;
  top: 78px;
  color: #e0001d;
  font-size: 25px;
  transform: rotate(25deg);
}

.promo-confetti-left { left: 30px; }
.promo-confetti-right { right: 30px; transform: rotate(-25deg); }

@media (max-width: 480px) {
  .referral-promo-card { padding: 40px 18px 24px; border-radius: 22px; }
  .promo-gifts { min-height: 88px; font-size: 50px; }
  .promo-main-gift { font-size: 76px; }
  .promo-bonus { font-size: 19px; }
}
</style>
