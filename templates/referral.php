<?php require_once __DIR__ . '/helpers.php'; ?>
<div class="referral-page">
    <h1><?= e(t('referral_program_title')) ?></h1>
    <p class="text-muted"><?= e(t('referral_program_intro')) ?></p>

    <div class="referral-benefits">
        <ul>
            <li><?= e(t('referral_benefit_link')) ?></li>
            <li><?= e(t('referral_benefit_earn', ['amount' => '$' . number_format(\App\ReferralService::EARN_PER_CLICK, 2)])) ?></li>
            <li><?= e(t('referral_benefit_buy')) ?></li>
        </ul>
    </div>

    <div class="referral-auth-grid">
        <section class="referral-card">
            <h2><?= e(t('referral_register')) ?></h2>
            <form method="post" action="/referral/register" class="referral-form">
                <div class="form-group">
                    <label for="reg_email"><?= e(t('email')) ?></label>
                    <input type="email" id="reg_email" name="email" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="reg_password"><?= e(t('password')) ?></label>
                    <input type="password" id="reg_password" name="password" class="form-control" minlength="6" required>
                </div>
                <button type="submit" class="btn btn-primary"><?= e(t('referral_create_account')) ?></button>
            </form>
        </section>

        <section class="referral-card">
            <h2><?= e(t('login')) ?></h2>
            <form method="post" action="/referral/login" class="referral-form">
                <div class="form-group">
                    <label for="login_email"><?= e(t('email')) ?></label>
                    <input type="email" id="login_email" name="email" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="login_password"><?= e(t('password')) ?></label>
                    <input type="password" id="login_password" name="password" class="form-control" required>
                </div>
                <button type="submit" class="btn btn-primary"><?= e(t('login')) ?></button>
            </form>
        </section>
    </div>
</div>
