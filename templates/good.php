<?php
require_once __DIR__ . '/helpers.php';

$cryptoWallets = $cryptoWallets ?? [];
$cryptoRates = $cryptoRates ?? [];
$cryptoRatesJson = json_encode($cryptoRates, JSON_UNESCAPED_UNICODE);
?>
<div class="container container-good-limited good-page-wrap" data-good-id="<?= (int) $good['id'] ?>" data-good-slug="<?= e($good['slug']) ?>">
    <div class="good-limited">
        <div class="good-header">
            <div class="good-header-cover">
                <img src="<?= e(cover_src($good)) ?>" alt="<?= e(good_name($good)) ?>">
            </div>
            <div class="good-header-data">
                <h1><?= e(good_name($good)) ?></h1>
                <?php if ($good['instant']): ?><span class="badge"><?= e(t('instant')) ?></span><?php endif; ?>
                <?php if ($content && $content['short_description']): ?>
                    <p class="good-header-data-description text-muted"><?= e(trans_entity('good_content', (string) $good['id'], 'short_description', $content['short_description'])) ?></p>
                <?php endif; ?>
            </div>
        </div>

        <h2 id="buy-section"><?= e(t('buy')) ?></h2>
        <div class="good-packs-grid">
            <?php foreach ($packs as $pack): ?>
            <div class="pack<?= $pack['in_stock'] ? '' : ' pack--out' ?>"
                 data-pack-id="<?= (int) $pack['id'] ?>">
                <div class="pack-data">
                    <div class="pack-data-inner">
                        <div class="pack-name"><?= e(pack_name($pack)) ?></div>
                        <div class="pack-prices">
                            <div class="pack-prices_actual" data-usd="<?= e((string) $pack['price_usd']) ?>">
                                <?= price_usd((float) $pack['price_usd']) ?>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="pack-order">
                    <?php if ($pack['in_stock']): ?>
                    <button type="button"
                            class="btn btn-primary btn-buy"
                            data-pack-id="<?= (int) $pack['id'] ?>"
                            data-pack-name="<?= e(pack_name($pack)) ?>"
                            data-price-usd="<?= e((string) $pack['price_usd']) ?>">
                        <?= e(t('buy_now')) ?>
                    </button>
                    <?php else: ?>
                    <span class="text-muted"><?= e(t('out_of_stock')) ?></span>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <section id="crypto-payment" class="crypto-payment-panel" hidden>
            <div class="crypto-payment-inner">
                <h2><?= e(t('crypto_payment_title')) ?></h2>
                <p class="crypto-selected-pack">
                    <span><?= e(t('selected_item')) ?>:</span>
                    <strong id="crypto-pack-name">—</strong>
                    <span id="crypto-pack-price" class="crypto-usd-total">—</span>
                </p>

                <?php
                $refBalance = (float) ($ref_balance ?? 0);
                if ($refBalance > 0):
                ?>
                <div class="referral-pay-box form-group">
                    <label>
                        <input type="checkbox" name="use_referral_balance" id="use_referral_balance" value="1">
                        <?= e(t('pay_with_referral_balance')) ?>
                        (<?= price_usd($refBalance) ?> <?= e(t('available')) ?>)
                    </label>
                </div>
                <?php endif; ?>

                <?php if ($fields): ?>
                <div class="crypto-player-fields">
                    <h3><?= e(t('player_info')) ?></h3>
                    <?php foreach ($fields as $field): ?>
                    <div class="form-group">
                        <label for="crypto_f_<?= e($field['field_key']) ?>"><?= e(field_label($field)) ?></label>
                        <input type="text"
                               class="crypto-field-input form-control"
                               id="crypto_f_<?= e($field['field_key']) ?>"
                               data-field-key="<?= e($field['field_key']) ?>"
                               placeholder="<?= e($field['placeholder'] ?? '') ?>"
                               <?= !empty($field['required']) ? 'required' : '' ?>>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <div class="form-group">
                    <label for="crypto_email"><?= e(t('email')) ?> <small class="text-muted">(<?= e(t('optional')) ?>)</small></label>
                    <input type="email" id="crypto_email" class="form-control" name="email">
                </div>

                <p class="crypto-hint text-muted"><?= e(t('crypto_select_hint')) ?></p>
                <div class="crypto-wallet-grid">
                    <?php foreach ($cryptoWallets as $wallet): ?>
                    <button type="button"
                            class="crypto-wallet-card"
                            data-crypto-id="<?= e($wallet['id']) ?>"
                            data-symbol="<?= e($wallet['symbol']) ?>"
                            data-name="<?= e($wallet['name']) ?>"
                            data-network="<?= e($wallet['network']) ?>"
                            data-address="<?= e($wallet['address']) ?>"
                            data-coingecko="<?= e($wallet['coingecko_id']) ?>"
                            data-decimals="<?= (int) $wallet['decimals'] ?>">
                        <span class="crypto-wallet-icon"><?= e($wallet['icon']) ?></span>
                        <span class="crypto-wallet-title"><?= e($wallet['name']) ?></span>
                        <span class="crypto-wallet-network"><?= e($wallet['network']) ?></span>
                    </button>
                    <?php endforeach; ?>
                </div>

                <div id="crypto-details" class="crypto-details" hidden>
                    <div class="crypto-amount-box" id="crypto-crypto-box">
                        <span class="label"><?= e(t('send_exactly')) ?></span>
                        <strong id="crypto-amount-display" class="crypto-amount-value">—</strong>
                        <span id="crypto-symbol-display" class="crypto-symbol"></span>
                        <span class="crypto-usd-equiv" id="crypto-usd-equiv"></span>
                    </div>
                    <div class="crypto-address-box">
                        <span class="label"><?= e(t('wallet_address')) ?></span>
                        <code id="crypto-address-display" class="crypto-address"></code>
                        <button type="button" class="btn btn-secondary btn-copy" id="btn-copy-address"><?= e(t('copy_address')) ?></button>
                    </div>
                    <div class="crypto-instructions">
                        <h3><?= e(t('payment_instructions')) ?></h3>
                        <ol>
                            <li id="crypto-instruction-amount"><?= e(t('crypto_step_send')) ?></li>
                            <li><?= e(t('crypto_step_sent')) ?></li>
                            <li><?= e(t('crypto_step_verify')) ?></li>
                            <li><?= e(t('crypto_step_deliver')) ?></li>
                        </ol>
                    </div>

                    <form method="post" action="/buy/confirm" id="crypto-confirm-form">
                        <input type="hidden" name="pack_id" id="form_pack_id" value="">
                        <input type="hidden" name="good_id" value="<?= (int) $good['id'] ?>">
                        <input type="hidden" name="good_slug" value="<?= e($good['slug']) ?>">
                        <input type="hidden" name="crypto_id" id="form_crypto_id" value="">
                        <input type="hidden" name="crypto_amount" id="form_crypto_amount" value="">
                        <input type="hidden" name="use_referral_balance" id="form_use_referral" value="0">
                        <?php foreach ($fields as $field): ?>
                        <input type="hidden" name="fields[<?= e($field['field_key']) ?>]" class="crypto-field-hidden" data-field-key="<?= e($field['field_key']) ?>" value="">
                        <?php endforeach; ?>
                        <input type="hidden" name="email" id="form_email_hidden" value="">
                        <button type="submit" class="btn btn-primary btn-lg" id="crypto-submit-btn"><?= e(t('i_sent_payment')) ?></button>
                    </form>
                </div>
            </div>
        </section>

        <?php if ($content && $content['instruction_html']): ?>
        <section class="instructions">
            <?= trans_entity('good_content', (string) $good['id'], 'instruction_html', $content['instruction_html']) ?>
        </section>
        <?php endif; ?>
    </div>
</div>

<script>
window.CRYPTO_RATES = <?= $cryptoRatesJson ?>;
window.CRYPTO_I18N = {
    sendExactly: <?= json_encode(t('crypto_instruction_send')) ?>,
    worthOf: <?= json_encode(t('worth_of')) ?>,
};
</script>
<script src="/assets/good-buy.js"></script>
