# Credidata Design System

## Direction

Credidata is an operations workspace, not a generic admin template. Use a warm paper canvas, deep ink surfaces, cobalt for primary actions, and coral or amber only for attention states. Favor clear grouping, generous breathing room, and compact data presentation where it helps scanning.

## Typography

- Figtree is the primary typeface.
- Use strong, short headings and sentence-case labels.
- Use tabular or monospace text only for identifiers, API keys, and references.

## Color

- Canvas: warm ivory `#f7f5ef`.
- Ink: deep navy `#14213d`.
- Primary: cobalt `#3155d9`.
- Positive: emerald tones.
- Attention: amber and coral tones.

## Components

- Use rounded cards with subtle borders instead of heavy shadows.
- Primary buttons are solid cobalt with a clear focus ring.
- Secondary actions use an ink border and quiet background.
- Status badges must include readable text, not color alone.
- Empty, loading, and error states should be explicit.

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
