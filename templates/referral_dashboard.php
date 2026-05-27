<?php require_once __DIR__ . '/helpers.php'; ?>
<div class="referral-dashboard">
    <div class="referral-dashboard-header">
        <h1><?= e(t('referral_dashboard_title')) ?></h1>
        <form method="post" action="/referral/logout">
            <button type="submit" class="btn btn-secondary"><?= e(t('referral_logout')) ?></button>
        </form>
    </div>

    <div class="referral-stats-grid">
        <div class="referral-stat-card">
            <span class="referral-stat-label"><?= e(t('referral_balance')) ?></span>
            <strong class="referral-stat-value"><?= price_usd((float) $account['balance_usd']) ?></strong>
        </div>
        <div class="referral-stat-card">
            <span class="referral-stat-label"><?= e(t('referral_total_clicks')) ?></span>
            <strong class="referral-stat-value"><?= (int) $account['total_clicks'] ?></strong>
        </div>
        <div class="referral-stat-card">
            <span class="referral-stat-label"><?= e(t('referral_total_earned')) ?></span>
            <strong class="referral-stat-value"><?= price_usd((float) $account['total_earned_usd']) ?></strong>
        </div>
        <div class="referral-stat-card">
            <span class="referral-stat-label"><?= e(t('referral_per_click')) ?></span>
            <strong class="referral-stat-value"><?= price_usd($earnPerClick) ?></strong>
        </div>
    </div>

    <section class="referral-link-box">
        <h2><?= e(t('referral_your_link')) ?></h2>
        <p class="text-muted"><?= e(t('referral_link_hint')) ?></p>
        <div class="referral-link-row">
            <input type="text" class="form-control" id="referral-url" readonly value="<?= e($referralUrl) ?>">
            <button type="button" class="btn btn-secondary" id="btn-copy-referral"><?= e(t('copy_address')) ?></button>
        </div>
        <p class="text-muted referral-code"><?= e(t('referral_code')) ?>: <code><?= e($account['code']) ?></code></p>
    </section>

    <section class="referral-how">
        <h2><?= e(t('referral_how_it_works')) ?></h2>
        <ol>
            <li><?= e(t('referral_step_share')) ?></li>
            <li><?= e(t('referral_step_earn', ['amount' => price_usd($earnPerClick)])) ?></li>
            <li><?= e(t('referral_step_buy')) ?></li>
        </ol>
    </section>

    <?php if ($recentClicks): ?>
    <section class="referral-clicks">
        <h2><?= e(t('referral_recent_clicks')) ?></h2>
        <table class="checkout-table">
            <thead>
                <tr>
                    <th><?= e(t('referral_click_date')) ?></th>
                    <th><?= e(t('referral_earned')) ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recentClicks as $click): ?>
                <tr>
                    <td><?= e($click['click_date']) ?></td>
                    <td><?= price_usd((float) $click['amount_usd']) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </section>
    <?php endif; ?>
</div>
<script>
(function () {
  const btn = document.getElementById('btn-copy-referral');
  const input = document.getElementById('referral-url');
  if (!btn || !input) return;
  btn.addEventListener('click', async () => {
    try {
      await navigator.clipboard.writeText(input.value);
      btn.textContent = '✓';
      setTimeout(() => { btn.textContent = <?= json_encode(t('copy_address')) ?>; }, 2000);
    } catch (e) { input.select(); }
  });
})();
</script>
