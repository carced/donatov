<?php require_once __DIR__ . '/helpers.php'; ?>
<section class="page-hero page-hero--compact">
    <h1><?= e(t('referral_program_title')) ?></h1>
    <p><?= e(t('referral_program_intro')) ?></p>
</section>

<div class="referral-dashboard" id="referral-panel">
    <div class="referral-stats-grid">
        <div class="referral-stat-card referral-stat-card--highlight">
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

    <div class="card-panel referral-link-box">
        <h2><?= e(t('referral_your_link')) ?></h2>
        <p class="text-muted"><?= e(t('referral_link_hint')) ?></p>
        <div class="referral-link-row">
            <input type="text" class="form-control" id="referral-url" readonly value="<?= e($referralUrl) ?>">
            <button type="button" class="btn btn-primary" id="btn-copy-referral"><?= e(t('copy_address')) ?></button>
        </div>
        <p class="referral-code-note"><?= e(t('referral_code')) ?>: <code><?= e($account['code']) ?></code></p>
    </div>

    <div class="card-panel referral-how">
        <h2><?= e(t('referral_how_it_works')) ?></h2>
        <ol class="steps-list">
            <li><?= e(t('referral_step_share')) ?></li>
            <li><?= e(t('referral_step_earn', ['amount' => price_usd($earnPerClick)])) ?></li>
            <li><?= e(t('referral_step_buy')) ?></li>
        </ol>
        <p class="text-muted referral-note"><?= e(t('referral_no_signup_note')) ?></p>
    </div>

    <?php if ($recentClicks): ?>
    <div class="card-panel referral-clicks">
        <h2><?= e(t('referral_recent_clicks')) ?></h2>
        <div class="table-wrap">
            <table class="data-table">
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
        </div>
    </div>
    <?php endif; ?>
</div>
<script>
(function () {
  const btn = document.getElementById('btn-copy-referral');
  const input = document.getElementById('referral-url');
  if (!btn || !input) return;
  const label = btn.textContent;
  btn.addEventListener('click', async () => {
    try {
      await navigator.clipboard.writeText(input.value);
      btn.textContent = '✓';
      setTimeout(() => { btn.textContent = label; }, 2000);
    } catch (e) { input.select(); document.execCommand('copy'); }
  });
})();
</script>
