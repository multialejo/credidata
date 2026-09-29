---
version: 1
slug: "resources-views-livewire-gestion-api-key-blade-php"
primary_target: "resources/views/livewire/gestion-api-key.blade.php"
related_targets: ["resources/views/livewire/gestion-clientes.blade.php"]
---

## Scope
Operate surface for authenticated client developers managing integration credentials.

## Audience and task
The client needs to create and maintain independent credentials for each connected application, inspect each credential's permissions and activity, and safely copy a new secret exactly once. Collaboration uses a separate key with only the contribution permission.

## Constraints
Preserve the established Credidata visual system and Spanish UI. Do not introduce a master key or let API credentials manage keys. Keep balances and client identity at client level. Secret hashes are never displayed; plaintext is shown only immediately after create or rotate.

## Direction contract
THESIS: A developer should be able to identify which application each credential serves and isolate its access without affecting sibling keys.

OWN-WORLD: Extend Credidata's established warm canvas, white work surfaces, deep ink typography, cobalt primary actions and semantic status colors; retain the existing page shell and shared controls.

STORY: Review the key inventory, create or edit an application's configuration, then copy the newly issued secret; rotate or revoke only the selected application's key.

FIRST VIEWPORT: Page title and concise isolation guidance lead into a clear create action; the inventory immediately shows application, status, prefix, last use, permissions and row-level lifecycle actions. The creation/configuration form appears inline above the inventory, and the one-time secret notice takes precedence after create or rotate.

FORM: Existing-surface extension, extending the current single-key settings page into an inventory and inline editor. Seed key: not applicable; this extension inherits the existing page and does not use the new-surface concept-seed round.

FINISH: unreviewed and undocumented is unfinished; this build ends with the finish review, the verdict, DESIGN.md, and every shipping raster carrying its provenance
