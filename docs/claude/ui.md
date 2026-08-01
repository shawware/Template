# User Interface

## Delivery: CDN Only, No Build Step

Tailwind CSS and Alpine.js load from CDN. There is no Node dependency anywhere in the template — no `package.json`, no Vite, no `npm install` step in setup or deploy.

If a specific app later outgrows what CDN Tailwind can do (dynamic class safelisting gets unwieldy at real scale), that's a deliberate, one-time add-back for that app — `laravel new`'s own Vite scaffolding, or `composer require`-equivalent tooling — not something every app should start with and ignore.

## Alpine.js Component Pattern

For components with non-trivial logic, define the component as a named function in a `<script>` block and reference it from `x-data`, rather than inlining the logic in the attribute. Inline `x-data` breaks as soon as the JavaScript contains double quotes (string comparisons, escaped values), because the quotes terminate the HTML attribute prematurely.

```html
<script>
function myComponent(data) {
    return {
        // properties and methods — full JS syntax, no attribute-escaping concerns
    };
}
</script>

<div x-data="myComponent(@js($phpData))">
```

## Responsive Design

Mobile-first, using Tailwind's default breakpoints (`sm` 640px and up covers desktop nav; nothing below `sm` needs a custom breakpoint at this scale). No `screens` customization in the CDN `tailwind.config` — the defaults are sufficient.

- **Nav**: the desktop link row is `hidden sm:flex`; a hamburger button (`sm:hidden`) toggles a mobile panel via a top-level `x-data="{ mobileOpen: false }"` on `<nav>`, following the same `x-show` / `x-cloak` / `@click.outside` idiom as the existing Admin dropdown. Any new top-level nav link needs adding to both the desktop row and the mobile panel in `layouts/app.blade.php`.
- **Footer**: `flex-col sm:flex-row` so multi-item rows stack on narrow screens instead of clipping.
- **Tables**: wrap every `<table>` in its own `<div class="overflow-x-auto">` (inside the card's outer `overflow-hidden` wrapper) so wide tables scroll horizontally on narrow screens rather than being clipped. Apply this to any new admin/data table.
- **Multi-column forms**: use `grid-cols-1 sm:grid-cols-2` (or similar) rather than a fixed `grid-cols-N`, so fields stack on narrow screens.
- **Page titles**: every page's `<h1>` uses `text-heading text-xl font-semibold` — keep this consistent so heading hierarchy doesn't drift as pages are added. Sub-section `<h2>`s within a page use `text-lg font-semibold`.

## General UI Rules

- Keep it simple
- Menu at top
- Footer with environment + version indicator (`config('app.version')`)
- Version will be suffixed with the git commit hash

## Colour Theming

The UI supports switchable colour themes via CSS custom properties. **Never use raw Tailwind colour classes** (`text-gray-700`, `bg-indigo-600`, etc.) in templates — always the semantic token classes below. This is what makes adding a second theme later a CSS-variable change instead of a template rewrite.

Themes are CSS variable blocks defined in `layouts/app.blade.php`; the Tailwind CDN config maps token names to those variables. The active theme class is set on `<html>` by an inline script reading `localStorage`. Ship one theme (`theme-default`) to start — the token layer is the investment, not the theme count.

### Semantic Token Reference

| Class | Role |
| --- | --- |
| `bg-page` | Page/body background |
| `bg-surface` | Card and panel backgrounds |
| `bg-muted` | Section headers, form areas, group rows |
| `bg-nav` / `bg-nav-hover` | Nav bar, footer / nav item hover |
| `text-heading` | h1, h2, primary values |
| `text-body` | Main body text |
| `text-secondary` | Secondary/descriptive text |
| `text-label` | Form labels, metadata |
| `text-subtle` | Empty states, placeholders |
| `text-inverse` | Text on dark backgrounds (nav, footer) |
| `border-edge` | Card borders, form input borders |
| `border-divider` | Internal row dividers within cards |
| `border-separator` | Totals-row emphasis borders |
| `text-action` / `border-action` | Links, text buttons, active tab |
| `bg-btn` / `bg-btn-hover` | Primary button fill and hover |
| `ring-focus-ring` | Focus ring on all inputs |
| `bg-highlight` / `border-highlight-border` | Edit-row background and border |
| `bg-badge` / `text-badge-fg` | Inline badge |

**Exceptions — keep as raw Tailwind:** semantic status colours (`bg-red-50 text-red-700`, `bg-amber-50 text-amber-700`, `bg-green-50 text-green-700`) and chart dataset colours. These carry meaning independent of the active theme (error is always red-family) and shouldn't be re-themeable.

### Rules for New Templates

- `bg-surface` for card/panel wrappers: `<div class="bg-surface rounded-lg border border-edge shadow-sm">`
- `bg-nav` for dark section headers inside cards
- `bg-muted` for category/group headers within tables
- `text-heading` / `text-body` / `text-label` / `text-subtle` for text hierarchy
- `border-edge` for card borders and form inputs; `border-divider` for row dividers
- `bg-btn hover:bg-btn-hover` for primary buttons; `text-action hover:text-action-hover` for text links
- `ring-focus-ring` in `focus:ring-*` on all inputs and selects
