# ParadoxLabs_CaratHyvaCheckout Changelog

## 1.1.0 - Jul 17, 2026

Add customer-account "My Payment Options" (paymentinfo) card management on Hyva themes, via the
shared `ParadoxLabs_TokenBaseHyvaCheckout` scaffold: card list, delete, and a two-step
(address then Commerce Hub hosted fields) add/edit form. Edit re-tokenizes and updates the
existing card in place. The Luma-only "My Payment Options" nav link is no longer removed on Hyva.

## 1.0.0 - Jul 17, 2026

Initial release: Carat (Fiserv Commerce Hub) Checkout SDK hosted payment fields support for Hyva
Checkout, with stored cards (vault), CVV re-entry on stored cards, and strict CSP/Alpine CSP
compliance. Customer-account payment options (paymentinfo) are not yet supported on Hyva; the
"My Payment Options" nav link is removed on Hyva themes.
