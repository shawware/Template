# User Interface

## Delivery: CDN Only, No Build Step

Tailwind CSS and Alpine.js load from a CDN. The template has no Node dependency anywhere. There is no `package.json`, no Vite, and no `npm install` step in setup or deploy.

A specific app may later outgrow what CDN Tailwind can do. For example, dynamic class safelisting can become unwieldy at real scale. If that happens, adding a build step back is a deliberate, one-time choice for that app. It could use `laravel new`'s own Vite scaffolding, or equivalent `composer require` tooling. It is not something every app should start with, or something to add without a clear need.

## Alpine.js Component Pattern

For components with non-trivial logic, define the component as a named function in a `<script>` block. Reference it from `x-data`, rather than writing the logic inline in the attribute. Inline `x-data` breaks as soon as the JavaScript contains double quotes, for example in string comparisons or escaped values. The quotes end the HTML attribute early.

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

## CSRF

Laravel's `VerifyCsrfToken` middleware is part of the `web` middleware group. It protects every POST, PUT, PATCH, and DELETE route by default. This protection is already active. It is not something that needs switching on as the app grows. Standard Blade `<form>`s only need `@csrf` inside them.

The gap is Alpine components that make their own `fetch` or `axios` call, instead of submitting a form. Blade's `@csrf` directive does not cover those. Read the token from the `<meta name="csrf-token" content="{{ csrf_token() }}">` tag in `layouts/app.blade.php`. Send it as the `X-CSRF-TOKEN` header:

```js
fetch('/some/endpoint', {
    method: 'POST',
    headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
    body: JSON.stringify(data),
});
```

Routes on `web.php` inherit CSRF protection automatically. This only needs handling as you add forms or AJAX calls; no action is needed per route. The one exception is inbound webhooks, for example a third-party service like Stripe posting to this app with no session or token to present. Webhook routes need an explicit CSRF exemption, added through `except` in `bootstrap/app.php`'s `validateCsrfTokens()` config. The template ships no webhook routes. Add the exemption when you build the first one, scoped to that specific route.

## Responsive Design

The design is mobile-first, and uses Tailwind's default breakpoints. The `sm` breakpoint, at 640px and up, covers desktop nav. Nothing below `sm` needs a custom breakpoint at this scale. The CDN `tailwind.config` has no `screens` customization; the defaults are enough.

- **Nav**: the desktop link row uses `hidden sm:flex`. A hamburger button, using `sm:hidden`, toggles a mobile panel. This works through a top-level `x-data="{ mobileOpen: false }"` on `<nav>`. It follows the same `x-show`, `x-cloak`, and `@click.outside` pattern as the existing Admin dropdown. When you add a new top-level nav link, add it to both the desktop row and the mobile panel in `layouts/app.blade.php`.
- **Footer**: use `flex-col sm:flex-row`, so multi-item rows stack on narrow screens instead of getting clipped.
- **Tables**: wrap every `<table>` in its own `<div class="overflow-x-auto">`, inside the card's outer `overflow-hidden` wrapper. This lets wide tables scroll horizontally on narrow screens, instead of being clipped. Apply this pattern to any new admin or data table.
- **Multi-column forms**: use `grid-cols-1 sm:grid-cols-2`, or a similar pattern, rather than a fixed `grid-cols-N`. This lets fields stack on narrow screens.
- **Page titles**: every page's `<h1>` uses `text-heading text-xl font-semibold`. Keep this consistent, so the heading hierarchy does not drift as you add pages. Sub-section `<h2>`s within a page use `text-lg font-semibold`.

## Printing

The app supports browser-native printing only, through Cmd/Ctrl+P. It has no in-app "Print" button. Layout chrome with no value on paper — nav, footer, and flash success or error banners — is hidden with Tailwind's built-in `print:hidden` variant. This variant is applied directly in `layouts/app.blade.php`. It compiles to a plain `@media print { display: none }` rule. It needs no custom `<style>` block, and no `tailwind.config` change. Page content, such as cards, tables, and forms, prints as-is. No per-page changes are needed for this.

## General UI Rules

- Keep it simple.
- Put the menu at the top.
- Add a footer with an environment and version indicator, using `config('app.version')`.
- Suffix the version with the git commit hash.

## Colour Theming

The UI supports switchable colour themes, through CSS custom properties. **Never use raw Tailwind colour classes**, such as `text-gray-700` or `bg-indigo-600`, in templates. Always use the semantic token classes below instead. This is what makes it possible to add a second theme later as a CSS-variable change, rather than a template rewrite.

Themes are CSS variable blocks, defined in `layouts/app.blade.php`, one block per theme class: `.theme-default`, `.theme-ocean`, and so on. The Tailwind CDN config maps token names to those variables once, regardless of how many themes exist. The template ships two themes: `theme-default`, and a second reference theme, `theme-ocean`. These two themes prove that the token layer can reskin the app with a CSS-only change. The token layer is the real investment, not the theme count. Treat `theme-ocean`'s values as a copyable example, rather than as a fixed pair.

### Adding a theme

1. Copy an existing theme's variable block in the `<style>` tag. Give it a new class name: `.theme-<name>`. You do not need a `tailwind.config` change, since the config already maps token names to `var(--color-*)`, and does not depend on how many blocks define those variables.
2. Add a button for it wherever the switcher lives, described below. The button should call `setTheme('theme-<name>')`.

### Switching themes

- `setTheme(name)` is defined once, at the end of `<body>` in `layouts/app.blade.php`. It sets `document.documentElement.className = name`, and saves the choice with `localStorage.setItem('theme', name)`.
- An inline script in `<head>` runs before Tailwind and Alpine load. It sets `document.documentElement.className` from `localStorage.getItem('theme')`, falling back to `theme-default`. This way, the correct theme applies before the first paint. Without it, the page would flash the default theme, then jump to the stored one.
- The switcher itself is a small Alpine dropdown, with a swatch and a label for each theme. One instance lives in the desktop nav, and one in the mobile panel. Both call the same `setTheme()`. Follow the existing Admin-dropdown `x-data="{ open: false }"` pattern when you add a switcher to a new nav.

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

**Exceptions — keep as raw Tailwind:** semantic status colours, such as `bg-red-50 text-red-700`, `bg-amber-50 text-amber-700`, and `bg-green-50 text-green-700`, and chart dataset colours. These colours carry meaning that does not depend on the active theme; an error is always red-family. Do not make these re-themeable.

### Rules for New Templates

- Use `bg-surface` for card and panel wrappers: `<div class="bg-surface rounded-lg border border-edge shadow-sm">`.
- Use `bg-nav` for dark section headers inside cards.
- Use `bg-muted` for category or group headers within tables.
- Use `text-heading`, `text-body`, `text-label`, and `text-subtle` for text hierarchy.
- Use `border-edge` for card borders and form inputs. Use `border-divider` for row dividers.
- Use `bg-btn hover:bg-btn-hover` for primary buttons. Use `text-action hover:text-action-hover` for text links.
- Use `ring-focus-ring` in `focus:ring-*` on all inputs and selects.
