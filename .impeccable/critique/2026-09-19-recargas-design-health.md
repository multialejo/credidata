# Critique baseline — Recargar créditos (fix-payphone)

- **Date**: 2026-09-19
- **Generator**: impeccable skills (design-review dual agent + detector)
- **Workflow**: dual-agent critique → approved plan (P0+P1+P2 full pass, split PayPhone/card, full amount tier)
- **Baseline Design Health Score**: **18/40 (Poor)** — main page good, round-trip status pages below floor
- **Detector**: 0 findings on all targets at baseline and after the pass

## Scope
- `resources/views/layouts/guest.blade.php`
- `resources/views/livewire/recargas.blade.php`
- `resources/views/livewire/pay-with-paypal.blade.php`
- `resources/views/livewire/pay-with-payphone.blade.php`
- `resources/views/recargas/{paypal,payphone}/{return,cancel}.blade.php`

## Approved decisions (user)
1. Full pass — P0+P1+P2.
2. Split PayPhone wallet vs card as distinct method cards.
3. Full amount tier: live `≈ N créditos` + chips `$10/$25/$50/$100` + order summary above CTA.

## Post-pass deliverables
- Contract strings and test assertions preserved byte-for-byte (verified statically, no local phpunit: Arch PHP lacks pdo_sqlite).
- Estimator parity: both providers now `floor` (was `round` in Payphone).
- Round-trip pages: brand status cards, neutral copy, retry only for authenticated users on `fallida`.
- Method cards: PayPal · PayPhone · Tarjeta · Transferencia (Próximamente); CTA disabled until amount ≥ min.