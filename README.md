[![Latest Stable Version](https://poser.pugx.org/paradoxlabs/carat-hyva-checkout/v/stable)](https://packagist.org/packages/paradoxlabs/carat-hyva-checkout)
[![License](https://poser.pugx.org/paradoxlabs/carat-hyva-checkout/license)](https://packagist.org/packages/paradoxlabs/carat-hyva-checkout)
[![Total Downloads](https://poser.pugx.org/paradoxlabs/carat-hyva-checkout/downloads)](https://packagist.org/packages/paradoxlabs/carat-hyva-checkout)

<p align="center">
    <a href="https://www.paradoxlabs.com"><img alt="ParadoxLabs" src="https://paradoxlabs.com/wp-content/uploads/2020/02/pl-logo-canva-2.png" width="250"></a>
</p>

This module adds support for Hyva Checkout to our Carat (Fiserv Commerce Hub) payment method for Magento 2.

Requirements
============

* Adobe Commerce / Magento Open Source 2.4.6 – 2.4.9 (or equivalent version of Adobe Commerce Cloud), or Mage-OS 2.0 – 3.0
* PHP 8.1, 8.2, 8.3, 8.4, or 8.5
* Hyva Checkout (separate product and license), `hyva-themes/magento2-hyva-checkout` >= 1.3
* `hyva-themes/magento2-theme-module` >= 1.3.11
* `hyva-themes/magento2-payment-icons` >= 2.0
* `paradoxlabs/carat` ^2.1 (Commerce Hub Checkout SDK / SDCv2 integration)
* `paradoxlabs/tokenbase-hyva-checkout` ^1.0 (shared Hyva payment-options scaffold)

Features
========

* Place orders via Hyva Checkout, with Carat (Fiserv Commerce Hub) payment
* Embedded Commerce Hub Checkout SDK hosted payment fields (card number, name, expiration, CVV),
  rendered in Fiserv-hosted iframes — card data never touches the site
* Card entry is tokenized into a Commerce Hub payment session during the Hyva place-order
  validation stage; the server charges or stores the session by ID (source type PaymentSession)
* Supports stored cards (vault) via ParadoxLabs_TokenBase, including CVV re-entry on stored cards
  when Require CCV is enabled
* Customer-account "My Payment Options" (paymentinfo) card management on Hyva themes: card list,
  delete, and add/edit via the shared `ParadoxLabs_TokenBaseHyvaCheckout` two-step (address then
  hosted-fields) scaffold
* Strict CSP and Alpine CSP compliant, for Hyva Checkout 1.3+ nonce-based CSP

Architecture
============

* `Block\CheckoutTemplate` exposes the Carat checkout config (`Model\Config\CheckoutProvider`) to
  the payment form and scripts templates.
* `Magewire\Payment\Carat` owns the stored-card list and the place-order evaluation
  (`validateparadoxlabs_carat` client validator).
* `view/frontend/templates/checkout/scripts.phtml` registers a CSP-safe Alpine component: it loads
  the Checkout SDK, mounts the hosted fields (in a `wire:ignore` container so Magewire morphs never
  wipe the iframes), and on validate mints one-time credentials via `pdl_carat/secure/getParams`
  (which also persists the session id onto the quote payment), then submits the hosted form to
  tokenize into the payment session.
* `Magewire\Payment\PlaceOrderService` whitelists `{card_id, session_id, cc_cid, save}` from the
  client payload and assigns it to the quote payment via `importData()`, so the standard
  `payment_method_assign_data` observer chain runs before order placement.
* Payment sessions are amount-agnostic; the charge amount is set server-side at sale, so no
  client-side total-drift handling is required. A session cannot be reused after a failed charge —
  the `order:place:paradoxlabs_carat:error` event clears it and resets the form, and the validator
  additionally invalidates any previously submitted session before a retry.
* `PlaceOrderService::handleException()` accepts placement failures instead of rethrowing: the
  Magewire request completes normally (delivering the queued `order:place:*:error` browser
  events), `evaluateCompletion()` surfaces the real decline message through the messenger, and
  `canRedirect()` blocks the success-page redirect for the failed attempt.
* Customer-account payment options (paymentinfo) reuse the shared `ParadoxLabs_TokenBaseHyvaCheckout`
  scaffold: the `hyva_customer_paymentinfo_index_paradoxlabs_carat` layout retemplates the Luma
  method/cards/form blocks onto the shared Hyva templates and attaches
  `Block\Customer\PaymentPane` as the `payment_pane` child. The pane mounts the hosted fields when
  the scaffold dispatches `paradoxlabs_caratPaymentinfoAddressConfirmed`, tears them down on
  `...AddressEdit`, and on Save tokenizes the entered card into a Commerce Hub payment session via
  `pdl_carat/secure/getParams` (source=paymentinfo, so no quote session is touched), stashing the
  session id into `payment[session_id]` and submitting to the TokenBase Save controller. The
  Save controller loads the card by its hash (`id`), so edit re-tokenizes and updates the existing
  card in place rather than duplicating.

Installation and Usage
======================

In SSH at your Magento base directory, run:

    composer require paradoxlabs/carat-hyva-checkout
    php bin/magento module:enable ParadoxLabs_CaratHyvaCheckout
    php bin/magento setup:upgrade

## Applying Updates

In SSH at your Magento base directory, run:

    composer update paradoxlabs/carat-hyva-checkout
    php bin/magento setup:upgrade

These commands will download and apply any available updates to the module.

Pre-release Caveats / TODO
==========================

* **No runtime QA yet.** This module has not been exercised against a live Hyva Checkout install or
  the Commerce Hub CERT sandbox. Unit tests and static analysis pass, but end-to-end tokenization
  and order placement remain to be verified in a browser. Runtime QA checklist:
    * New-card tokenization and order placement (guest + logged in, save on/off)
    * Stored-card placement, with and without Require CCV
    * Verify the SDK `onFieldValidityChange` hook fires on valid→valid edits (e.g. editing the
      name-on-card field while the form stays valid) — if it does not, a previously tokenized
      session could carry pre-edit card data; if so, invalidate the session on a form-change
      signal that does fire
    * Placement failure path: session cleared, form reset, retry succeeds
    * Method switch away/back and billing-step round trips (hosted-field re-mount)
    * Customer-account "My Payment Options" (paymentinfo) on a Hyva theme:
        * Nav link present (only when every installed TokenBase gateway Hyva module has migrated)
        * Method tabs across 2+ active TokenBase methods; unsupported-method notice pane
        * Card list renders with expiry badge; delete-confirm modal deletes the card
        * Add card happy path: address step -> Confirm Address -> hosted fields mount -> enter
          card -> Save -> tokenize -> redirect with success message
        * Edit card: address prefilled, re-enter card, Save re-tokenizes and updates the existing
          card in place (no duplicate); verify the card's last4/expiry actually change
        * Region switcher (select vs. free-text) for US/CA/intl countries; optional-zip countries
        * SDK/tokenize failure path: error message shown, form stays usable for retry
* **Repository push pending.** The `m2-carat-hyva-checkout` remote repository does not exist yet.

License
=======

Apache-2.0. See LICENSE.md.
