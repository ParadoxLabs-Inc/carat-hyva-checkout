# ParadoxLabs_CaratHyvaCheckout Changelog

## 1.1.0 - Nov 23, 2026

- Added Hyva Checkout 3.x support (3.0.1+), native to Magewire 3; Hyva's v1bc module is not required.
- Now requires Hyva Checkout 1.3.6 or later on the 1.x line.
- Fixed an unavailable payment method (for example, missing API keys) breaking the payment step.

## 1.0.0 - Sep 15, 2026

- Initial release: Carat (Fiserv Commerce Hub) Checkout SDK hosted payment fields support for Hyva Checkout.
- Added stored card (vault) support, with CVV re-entry on stored cards.
- Added customer-account "My Payment Options" (paymentinfo) card management on Hyva themes: card list, delete, and a two-step (address, then Commerce Hub hosted fields) add/edit form.
- Added strict CSP and Alpine CSP compliance.
