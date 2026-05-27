(function () {
  const panel = document.getElementById('crypto-payment');
  if (!panel) return;

  const rates = window.CRYPTO_RATES || {};
  const i18n = window.CRYPTO_I18N || {};
  const goodPage = document.querySelector('.good-page-wrap');
  const goodId = goodPage?.dataset.goodId || '';
  const goodSlug = goodPage?.dataset.goodSlug || '';

  let selectedPack = null;
  let selectedWallet = null;
  let selectedUsd = 0;

  const packNameEl = document.getElementById('crypto-pack-name');
  const packPriceEl = document.getElementById('crypto-pack-price');
  const details = document.getElementById('crypto-details');
  const amountDisplay = document.getElementById('crypto-amount-display');
  const symbolDisplay = document.getElementById('crypto-symbol-display');
  const usdEquiv = document.getElementById('crypto-usd-equiv');
  const addressDisplay = document.getElementById('crypto-address-display');
  const instructionAmount = document.getElementById('crypto-instruction-amount');

  const formPackId = document.getElementById('form_pack_id');
  const formCryptoId = document.getElementById('form_crypto_id');
  const formCryptoAmount = document.getElementById('form_crypto_amount');
  const formEmailHidden = document.getElementById('form_email_hidden');
  const formUseReferral = document.getElementById('form_use_referral');
  const useReferralCheckbox = document.getElementById('use_referral_balance');
  const confirmForm = document.getElementById('crypto-confirm-form');
  const cryptoWalletGrid = document.querySelector('.crypto-wallet-grid');
  const referralPayBox = document.querySelector('.referral-pay-box');

  function formatUsd(n) {
    return '$' + Number(n).toFixed(2);
  }

  function isReferralPay() {
    return useReferralCheckbox && useReferralCheckbox.checked;
  }

  function syncReferralMode() {
    const referral = isReferralPay();
    if (formUseReferral) {
      formUseReferral.value = referral ? '1' : '0';
    }
    if (cryptoWalletGrid) {
      cryptoWalletGrid.hidden = referral;
    }
    if (referralPayBox) {
      referralPayBox.classList.toggle('is-active', referral);
    }
    if (referral && details) {
      details.hidden = true;
      selectedWallet = null;
      if (formCryptoId) formCryptoId.value = '';
      if (formCryptoAmount) formCryptoAmount.value = '';
    }
  }

  function calcCryptoAmount(usd, wallet) {
    const cg = wallet.dataset.coingecko;
    const price = Number(rates[cg] || 0);
    const decimals = parseInt(wallet.dataset.decimals || '6', 10);
    if (!price || price <= 0) return '0';
    const amount = usd / price;
    let s = amount.toFixed(decimals);
    s = s.replace(/\.?0+$/, '');
    return s || '0';
  }

  function syncHiddenFields() {
    document.querySelectorAll('.crypto-field-input').forEach((input) => {
      const key = input.dataset.fieldKey;
      const hidden = document.querySelector('.crypto-field-hidden[data-field-key="' + key + '"]');
      if (hidden) hidden.value = input.value.trim();
    });
    const email = document.getElementById('crypto_email');
    if (email && formEmailHidden) formEmailHidden.value = email.value.trim();
    syncReferralMode();
  }

  function updatePaymentDetails() {
    if (!selectedWallet || !selectedPack || isReferralPay()) return;
    const amount = calcCryptoAmount(selectedUsd, selectedWallet);
    const symbol = selectedWallet.dataset.symbol;
    const name = selectedWallet.dataset.name;

    amountDisplay.textContent = amount;
    symbolDisplay.textContent = symbol;
    usdEquiv.textContent = '(' + formatUsd(selectedUsd) + ')';
    addressDisplay.textContent = selectedWallet.dataset.address;

    const tpl = i18n.sendExactly || 'Send exactly {amount} {symbol} worth of {name} to the address above';
    instructionAmount.textContent = tpl
      .replace('{amount}', amount)
      .replace('{symbol}', symbol)
      .replace('{name}', name);

    formCryptoId.value = selectedWallet.dataset.cryptoId;
    formCryptoAmount.value = amount;
    details.hidden = false;
  }

  function openPayment(packBtn) {
    selectedPack = packBtn;
    selectedUsd = parseFloat(packBtn.dataset.priceUsd || '0');
    selectedWallet = null;

    if (packNameEl) packNameEl.textContent = packBtn.dataset.packName || '—';
    if (packPriceEl) packPriceEl.textContent = formatUsd(selectedUsd);
    if (formPackId) formPackId.value = packBtn.dataset.packId || '';

    document.querySelectorAll('.crypto-wallet-card').forEach((c) => c.classList.remove('active'));
    document.querySelectorAll('.good-packs-grid .pack').forEach((p) => p.classList.remove('selected'));
    const item = packBtn.closest('.pack');
    if (item) item.classList.add('selected');

    if (details) details.hidden = true;
    panel.hidden = false;
    panel.classList.add('is-open');
    syncReferralMode();

    requestAnimationFrame(() => {
      panel.scrollIntoView({ behavior: 'smooth', block: 'start' });
    });
  }

  document.querySelectorAll('.btn-buy').forEach((btn) => {
    btn.addEventListener('click', () => openPayment(btn));
  });

  if (useReferralCheckbox) {
    useReferralCheckbox.addEventListener('change', syncReferralMode);
  }

  document.querySelectorAll('.crypto-wallet-card').forEach((card) => {
    card.addEventListener('click', () => {
      if (!selectedPack || isReferralPay()) return;
      document.querySelectorAll('.crypto-wallet-card').forEach((c) => c.classList.remove('active'));
      card.classList.add('active');
      selectedWallet = card;
      updatePaymentDetails();
    });
  });

  const copyBtn = document.getElementById('btn-copy-address');
  if (copyBtn) {
    copyBtn.addEventListener('click', async () => {
      const addr = addressDisplay?.textContent || '';
      if (!addr) return;
      try {
        await navigator.clipboard.writeText(addr);
        copyBtn.textContent = 'Copied!';
        setTimeout(() => {
          copyBtn.textContent = copyBtn.dataset.label || 'Copy';
        }, 2000);
      } catch (e) {
        /* ignore */
      }
    });
    copyBtn.dataset.label = copyBtn.textContent;
  }

  if (confirmForm) {
    confirmForm.addEventListener('submit', (e) => {
      syncHiddenFields();
      if (!selectedPack) {
        e.preventDefault();
        alert('Please select a pack.');
        return;
      }
      if (!isReferralPay() && !selectedWallet) {
        e.preventDefault();
        alert('Please select a pack and a cryptocurrency.');
        return;
      }
      const required = document.querySelectorAll('.crypto-field-input[required]');
      for (const input of required) {
        if (!input.value.trim()) {
          e.preventDefault();
          input.focus();
          return;
        }
      }
    });
  }

  if (window.location.hash === '#crypto-payment' && document.querySelector('.btn-buy')) {
    openPayment(document.querySelector('.btn-buy'));
  }
})();
