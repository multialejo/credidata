# Credidata Design System

This is the source of truth for every user-facing surface: web pages, Livewire states, authentication flows, admin tools and transactional emails. New views must follow this standard before introducing local styles.

## Direction

Credidata is an operations workspace, not a generic admin template. Use a warm paper canvas, deep ink surfaces, cobalt for primary actions, and coral or amber only for attention states. Favor clear grouping, generous breathing room, and compact data presentation where it helps scanning.

## Foundations

### Canvas and color

| Token | Value | Use |
|-------|-------|-----|
| `canvas` | `#f7f5ef` | Main application background |
| `surface` | `#ffffff` | Cards, forms and table surfaces |
| `ink` | `#14213d` | Headings, navigation and primary text |
| `cobalt` | `#3155d9` | Primary actions, links and selected states |
| `cobalt-dark` | `#2647c2` | Hover and pressed primary actions |
| `soft-cobalt` | `#e8edf9` | Informational surfaces and summaries |
| `muted` | `#475569` | Supporting text |
| `line` | `#e2e8f0` | Borders and separators |
| `success` | Emerald | Confirmed or completed states |
| `warning` | Amber | Attention and pending states |
| `danger` | Rose | Errors, rejection and destructive actions |

Do not introduce arbitrary gray, indigo or red families when a semantic token exists. Color never carries status alone; pair it with text and, when useful, an icon.

### Spacing and shape

- Use `rounded-2xl` for primary cards and `rounded-xl` for controls, alerts and smaller groups.
- Use `ui-card` for white elevated surfaces; avoid one-off shadows.
- Keep page content inside `ui-page-shell` and use `ui-page-header` for the title block.
- Keep controls at least 44px high for touch and keyboard use.
- Use generous separation between sections and tighter spacing inside related control groups.

## Typography

- Figtree is the primary typeface.
- Use strong, short headings and sentence-case labels.
- Use tabular or monospace text only for identifiers, API keys, and references.

- Use Figtree for all interface text. Use monospace only for API keys, references, IPs, code and other technical identifiers.
- Page headings use `ui-page-title`; supporting labels use `ui-eyebrow` only where the context benefits from a short category marker.
- Labels are sentence case and describe the content or action directly.
- Body text must remain readable at mobile widths; do not rely on truncation for essential information.

## Components

- Use `x-page-shell`, `x-page-header`, `x-data-table` and `x-alert` before creating local equivalents.
- Use `x-primary-button`, `x-secondary-button`, `x-text-input`, `x-input-label` and `x-input-error` for common form controls.
- Primary buttons are solid cobalt with a visible focus ring and a minimum 44px height.
- Secondary actions use an ink border and quiet background.
- Status badges must include readable text, not color alone.
- Empty, loading, success, error and disabled states must be explicit and recoverable.

## Page anatomy

1. The authenticated shell provides navigation and the warm canvas.
2. The page starts with one clear title and an optional short description.
3. The primary task appears before supporting information.
4. Related controls live in one card or section; avoid nested card forests.
5. Supporting guidance explains what happens next, not generic decoration.
6. Destructive actions are separated from primary actions and require confirmation.

## Forms and feedback

- Every input has a visible label, a useful `id`, and a nearby error or help message when needed.
- Use `ui-input` for text, select and textarea controls. Do not mix Breeze defaults with the visual system.
- Loading feedback must be visible for actions that take longer than a direct interaction; disabling alone is insufficient.
- Error copy names the problem and tells the user how to recover.
- Success feedback confirms the result and, when relevant, points to the next action.

## Tables

- Interactive tables use the `x-data-table` component or the `ui-data-table` classes. Email tables are a separate documentary variant.
- Use a white, rounded, subtly bordered container with a distinct header, optional Spanish-language filters/actions, a horizontally scrollable data region, and pagination at the footer.
- Table headers use compact uppercase labels; rows have a minimum readable density, subtle separators, and a hover state.
- Align numeric values to the right and use tabular numerals. Dates, references, API keys, IPs, and identifiers use the appropriate compact or monospaced treatment.
- Statuses must use a `ui-table-badge` with readable text as well as color.
- Keep server-side filtering and pagination in Livewire. Do not introduce DataTables.js unless a table has a dedicated local-data or JSON-endpoint use case.
- On mobile, the data region may scroll horizontally, while headings, filters, and primary actions remain understandable without it.

## Navigation

- Primary navigation is a fixed left sidebar (`w-64`) in ink `#14213d`, visible from the `lg` breakpoint up.
- Each nav item pairs a Heroicons outline icon (h-5, `stroke-width="1.5"`) with a label in sentence-case.
- The active item uses `bg-white/12 text-white` with a bold weight; inactive items use `text-slate-300` with `hover:bg-white/10 hover:text-white`.
- User identity is pinned to the sidebar footer with a chevron-down dropdown for Perfil / Cerrar sesión.
- On mobile (`<lg`): a thin ink header bar shows a hamburger icon and the Credidata logo; tapping it opens an off-canvas drawer from the left with a backdrop overlay.
- The drawer contains the same nav items, a close button, and the user section at the bottom.

## Responsive behavior

- Keep the primary action visible on small screens.
- On mobile, a sticky ink header replaces the sidebar; navigation slides in as a left-edge drawer with a dark backdrop.
- Data rows may scroll horizontally, but controls and headings must remain understandable without horizontal scrolling.

## Email variant

Emails reuse the same hierarchy and semantic palette but must be implemented as standalone documents with inline CSS and conservative table-based layout. They must work without JavaScript, external fonts or application navigation. Keep the subject and first paragraph explicit, use one primary action, and include a plain-text recovery path when the action depends on the web application.

## Accessibility checklist

- [ ] Keyboard focus is visible on every interactive element.
- [ ] Icon-only controls have an accessible name.
- [ ] Dialogs and drawers expose their state and support Escape where appropriate.
- [ ] Status is communicated with text, not color alone.
- [ ] Text and controls meet readable contrast requirements.
- [ ] Tables have captions, scoped headers and a usable mobile overflow strategy.
- [ ] Loading, empty and error states are announced or visibly understandable.

## New-view checklist

- [ ] Uses the shared page shell and title hierarchy.
- [ ] Uses shared tokens and components instead of local legacy classes.
- [ ] Defines loading, empty, success and error states.
- [ ] Works at mobile, tablet and desktop widths.
- [ ] Preserves Spanish terminology and existing route/Livewire behavior.
- [ ] Has keyboard, focus and screen-reader semantics reviewed.
