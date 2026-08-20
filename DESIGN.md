# DESIGN.md - KASAROSE storefront (kmarket)

> **Rebrand note (August 2026).** This storefront was launched as *KasaBazaar Market* and renamed to
> **KASAROSE**. The structural design decisions below (grid, type scale, component system, the "wake" motif)
> survived the rename unchanged. What moved: the colour palette, now sampled from the KASAROSE logo
> (`resources/brand/kasa.png` — blue `#055F9C`, red `#EF4136`) instead of the old KasaBazaar logo, and
> the brand assets in `public/images/brand/`. The Tailwind token *names* (`navy-*`, `accent`) were kept so the
> existing blade markup did not have to churn — read `navy-*` as "the KASAROSE blue scale", not as literal navy.

## Context (from discovery)

- Artifact type: marketplace (two-sided) — customer-facing storefront on top of a multi-vendor commerce API. Auth, browse/PDP, cart/checkout, and account are the core surfaces.
- Positioning: corporate-adjacent trust (logistics heritage, part of the KasaBazaar Group of Companies alongside KasaBazaar, RDD Shipping and Neoride Africa) expressed through a consumer-energetic storefront, not an admin back-office.
- Audience: everyday shoppers in Ghana buying from local vendors, plus prospective vendors evaluating whether to sell here. Primary action: find a product and complete a confident purchase; secondary action: a vendor signs up.
- Adjectives: trustworthy, efficient, energetic, approachable, modern.
- Visual word translations:
  - trustworthy -> deep brand blue as the dominant ink/surface color, generous proof (vendor names, reviews, delivery/return info near the decision point), no dark patterns.
  - efficient -> clear scan paths, tabular numerals on prices/orders, decisive primary-button hierarchy, real loading/empty/error states everywhere (this is also a functional bug fix, not just a look).
  - energetic -> a sharp, brighter red accent (not the muted admin-panel maroon) used sparingly for CTAs, sale badges, and the signature wake motif.
  - approachable -> warm stone neutrals instead of cold gray, humanist body type, rounded-but-restrained corners (never blob rounding).
  - modern -> Tailwind-native component system replacing the legacy jQuery/WooCommerce-style template; no skeuomorphism, no gradients-for-decoration.
- Aesthetic essence (3 words): Trusted, Swift, Local.
- Single-minded proposition: a marketplace confident enough to look like a real shipping-and-trade group built it, warm enough to feel like a local bazaar.
- Archetype: Hero (delivers reliably) tempered by Everyman (local, communal, unpretentious).
- References: admire Jumia/Airbnb-style marketplace card density and trust placement; avoid the luxury-restraint direction (too cold for a broad Ghanaian consumer marketplace) and avoid the source jQuery template's demo-stock-photo, coupon-countdown aesthetic entirely.
- Mode: light only (no dark mode requested; not worth the added surface area for this pass).
- Constraints: Laravel 12 + Livewire + Tailwind CSS v4 (already installed, previously unused by the storefront), Alpine.js (bundled with Livewire), no new JS framework, no new paid icon/font licenses. Brand colors and logo are fixed inputs (not free invention) — sampled directly from `public/images/brand/brand/kasa.png` (the KASAROSE mark), and deliberately distinct from the RDD Shipping admin panel's maroon (`#A0043C`/`#003151`).

## Aesthetic

- Direction: bespoke — "Waypoint". Closest catalog references are Swiss (grid discipline, restraint) and consumer marketplace craft (Airbnb-grade card/trust density), diverged toward warmth: Swiss's rigor kept, its coldness dropped for stone neutrals and a brighter accent.
- Defining trait: information is organized on a strict, generous grid with flat surfaces (border-only cards, no ambient card shadows) — hierarchy comes from type weight, spacing, and the single accent color, not from decoration.
- Signature move: the "wake" — echoing the red wedge that cuts through the KASAROSE **K** — recurs as a single thin red rule under key section headings, as the active-tab/active-nav indicator, and as the loading-bar treatment. Used exactly once per screen region, never as a repeated decorative pattern.

## Typography

- Display: **Space Grotesk** | source: Google Fonts (Fontshare also carries it) | license: OFL. Bold, geometric, confident letterforms that echo the angular KASAROSE wordmark. Used for H1-H3, hero copy, and prices.
- Body: **Plus Jakarta Sans** | source: Google Fonts | license: OFL. Humanist, warm, highly legible at small sizes for dense commerce UI (prices, meta, table cells).
- Mono: not used (no code/data-table-numerics context beyond prices, which use tabular figures in Plus Jakarta Sans, not a mono face).
- Scale: ratio 1.25 (Major Third), base 16px.

  | step | size | line-height | use |
  |------|------|-------------|-----|
  | display | 48px (36px mobile) | 1.1 | hero headline |
  | h1 | 36px (28px mobile) | 1.15 | page title |
  | h2 | 28px | 1.2 | section heading |
  | h3 | 22px | 1.3 | card/subsection heading |
  | body | 16px | 1.6 | paragraph, form text |
  | small | 14px | 1.5 | meta, captions, table cells |
  | micro | 12px | 1.4 | badges, timestamps |

- Weights: display 600/700, body 400/500/600 for emphasis. Measure: 65-75ch for prose (About/FAQ/Contact copy). Tracking: display headings get `tracking-tight` (-0.02em); micro/badge text gets `tracking-wide` (+0.04em) uppercase.

## Color

- Strategy: brand-derived (not free invention) from the KASAROSE logo's blue + red. Sampled directly from `resources/brand/kasa.png`: the mark is `#055F9C` blue with an `#EF4136` red wedge. Pulled brighter than the RDD Shipping admin panel's muted maroon so the storefront reads as retail rather than back-office. Explicitly avoids the indigo/violet "AI slop" band — the accent sits in the red hue family (~28 deg), the ink in a blue hue family (~245 deg), high separation.
- Distribution: ~60% neutral (stone surfaces/backgrounds), ~30% brand blue (ink, headers, footer, nav), ~10% red accent (CTAs, sale badges, the wake motif, focus/active states).
- Palette (role -> OKLCH | hex). Token names retain the `navy-*` prefix from the pre-rebrand system; the values are the KASAROSE blue scale:
  - bg (page): oklch(98.23% 0.0029 84.56) | #FAF9F7
  - surface (cards/panels): oklch(100% 0 0) | #FFFFFF
  - surface-muted: oklch(95.56% 0.0057 84.57) | #F2F0EC
  - fg (body text): oklch(21.61% 0.0061 56.04) | #1C1917
  - muted (secondary text): oklch(55.34% 0.0116 58.07) | #78716C
  - border: oklch(91.95% 0.0087 84.57) | #E7E4DE
  - ink-deep / navy-950 (favicon bg, OG card bg, deepest text): oklch(22.67% 0.0531 244.57) | #021E33
  - ink / navy-900 (header, footer, primary headings on light, filled buttons): oklch(33.74% 0.0830 245.75) | #043A5F
  - ink-hover / navy-700: oklch(41.63% 0.1016 244.72) | #05507F
  - link / navy-500 (**the logo blue itself**): oklch(47.25% 0.1228 247.25) | #055F9C
  - navy-200 (muted brand tint, step numerals): oklch(83.54% 0.0535 237.11) | #A9CFE8
  - accent (**the logo red**, primary CTA, sale badge, wake motif): oklch(63.24% 0.2121 28.41) | #EF4136
  - accent-hover: oklch(55.74% 0.2026 29.02) | #D02A20
  - accent-soft (badge/alert backgrounds): oklch(95.60% 0.0187 25.60) | #FDECEA
  - accent-fg: white
  - success: oklch(62.71% 0.1699 149.21) | #16A34A
  - warning: oklch(66.58% 0.1574 58.32) | #D97706
  - error: same as accent-hover (#D02A20) so error states read as "the same red family," not a clashing third hue.
- Dark mode: out of scope for this pass (see Constraints).

## Spacing, radius, shadow

- Spacing base: 4px (Tailwind default `--spacing: 0.25rem`), used at the standard 1/2/3/4/6/8/12/16/24 step scale.
- Radius: two values only — `--radius-sm: 6px` (buttons, inputs, badges, chips) and `--radius-lg: 14px` (cards, panels, modals). Nothing above 16px; no blob rounding.
- Shadow approach: **defined edge, not soft elevation**, as the default (matches the Swiss-leaning "flat surfaces" defining trait). Resting cards/rows use a 1px `border` in `--color-border`, no shadow. Reserve a single soft shadow token (`--shadow-float: 0 8px 24px -8px rgb(2 30 51 / 0.20)`, brand-blue-tinted) exclusively for genuinely floating/overlaid surfaces that must read above content: dropdowns, the mobile nav drawer, modals, the sticky-header-on-scroll state, and toasts. Never combine a border and a shadow on the same element.

## Layout and composition

- Grid: 12-column with a 1280px max content width, 24px gutters (16px on mobile). Product grids step 2 -> 3 -> 4 columns by breakpoint (never more than 4 on desktop — keeps cards legible, matches marketplace-card density guidance over a cramped 5-6 column reflex).
- Spacing rhythm: tight within a card/form group (8-12px), generous between sections (64-96px on marketing-weight pages like Home/About, 32-48px on utility pages like Account/Checkout).
- Signature layout move: the homepage category strip and vendor/product grids break the plain centered-column reflex with a horizontal-scroll "rail" on mobile (native CSS scroll-snap, no JS carousel library) that becomes a static grid at `md:` and above — one component, two layouts, no Swiper dependency.
- Density: balanced — airier on Home/About (marketing-weight), denser on Account/Orders/Checkout (task-weight), per artifact-type guidance that a marketplace balances imagery against fast comparison.
- Responsive: mobile-first. Breakpoints follow Tailwind defaults (sm 640/md 768/lg 1024/xl 1280).

## Components and states

- Button hierarchy: primary = filled navy-900 (red-600 reserved for the single most important CTA per page, e.g. "Place Order", "Add to Cart" — not every button); secondary = navy-900 outline on white; tertiary = text-only navy-500 link style. Every button gets hover/active/focus-visible/disabled/loading states; loading = spinner + disabled, never a silent freeze (this directly fixes the "flat, no feedback" complaint).
- Inputs: label above field (not placeholder-as-label), `wire:model.live.debounce` where already used, inline error text in accent-700 below the field, focus ring = 2px accent ring with 2px offset.
- Tables (orders, cart line items): left-align text, right-align tabular-nums prices/quantities, hairline row dividers in `--color-border`, no zebra striping.
- Overlays: dropdowns/mobile drawer/toasts use `--shadow-float`, close on outside-click/Escape via Alpine, focus returns to the trigger on close.
- Empty/loading/error: every guarded component (see bug-fix list) renders a shared `<x-storefront.ui.empty-state>` (icon + message + action) for empty results and a friendly inline `<x-storefront.ui.alert variant="error">` for API failures — never a raw Livewire/Laravel error page.
- Focus ring: `outline-2 outline-offset-2 outline-[--color-accent]` app-wide; never removed without replacement.

## Motion

- Duration scale: fast 150ms (hover/focus), normal 200ms (dropdowns/toasts), slow 300ms (drawer/modal).
- Easing: `ease-out` for entrances, `ease-in-out` for toggles. No bounce/elastic easing anywhere.
- What animates: `transform` and `opacity` only (Alpine `x-transition`), never layout properties. `prefers-reduced-motion` disables transitions app-wide via a single CSS media guard.
- Signature motion: the "wake" underline animates in with a left-to-right `scaleX` reveal (150ms) the first time a section heading scrolls into view — subtle, on-brand, used once, not on every element.

## Iconography

- Set: hand-picked Heroicons **outline** subset (24px grid, 1.5px stroke, round joins), embedded as small Blade icon components — matches the icon language already used by the Filament admin (`Heroicon::` enum) for ecosystem consistency, at zero extra dependency weight (no FontAwesome vendor bundle to ship for the storefront anymore).

## Imagery and illustration

- Mode: real product photography (from the API/vendor uploads) and the brand mark only. No stock photography, no illustration set.
- Rules: product images sit on white/stone-50 surfaces with consistent aspect ratio and `object-fit: cover`; the homepage hero uses real admin-managed `Banner` images (reconnecting the currently-dead feature), falling back to a brand-designed static navy/red panel (not template stock photos) when no banner is active.
- Avoid: the current Wolmart demo imagery (stock TV/fashion photos) entirely; no gradient blobs, no corporate-Memphis illustration, no AI-image fingerprint.
- Text-over-image contrast: hero/banner copy sits on a navy scrim (`bg-navy-900/60`) or a solid navy panel beside the image, never raw text on unguarded photography.

## Dark mode

Out of scope for this pass (see Constraints).

## Accessibility

- Contrast: navy-900 on white and white on navy-900 both exceed AA for body text; red-600 on white passes AA for large text/UI (18px+/bold), red-700 used where small red text is needed against white to keep AA at normal sizes.
- Focus: visible 2px accent focus ring on every interactive element, never suppressed.
- Keyboard: all dropdowns/drawer/modals operable via keyboard (Alpine `@keydown.escape`, focus trap on the mobile drawer/modal).
- Targets: minimum 24px, 44px on primary mobile actions (add-to-cart, nav items).
- Color independence: sale badges/status pills pair color with a text label or icon, never color alone; form errors pair red with icon + text.
- Reduced motion: `prefers-reduced-motion` guard removes non-essential transitions.

## Tokens (source of truth)

```css
@theme {
  --font-display: "Space Grotesk", ui-sans-serif, system-ui, sans-serif;
  --font-sans: "Plus Jakarta Sans", ui-sans-serif, system-ui, sans-serif;

  --color-bg: oklch(98.23% 0.0029 84.56);        /* #FAF9F7 */
  --color-surface: oklch(100% 0 0);              /* #FFFFFF */
  --color-surface-muted: oklch(95.56% 0.0057 84.57); /* #F2F0EC */
  --color-fg: oklch(21.61% 0.0061 56.04);        /* #1C1917 */
  --color-muted: oklch(55.34% 0.0116 58.07);     /* #78716C */
  --color-border: oklch(91.95% 0.0087 84.57);    /* #E7E4DE */

  --color-navy-950: oklch(20.44% 0.0510 262.72); /* #0A162E */
  --color-navy-900: oklch(25.96% 0.0737 262.12); /* #0F2247 */
  --color-navy-700: oklch(35.09% 0.0842 259.11); /* #1E3A66 */
  --color-navy-500: oklch(45.90% 0.1001 259.89); /* #35578F */
  --color-navy-200: oklch(80.81% 0.0454 256.80); /* #AEC2DE */

  --color-accent: oklch(58.23% 0.2233 24.75);    /* #E11D2E */
  --color-accent-hover: oklch(51.59% 0.1994 24.98); /* #C01524 */
  --color-accent-soft: oklch(95.66% 0.0185 17.48);  /* #FDECEC */
  --color-accent-fg: oklch(100% 0 0);

  --color-success: oklch(62.71% 0.1699 149.21);  /* #16A34A */
  --color-warning: oklch(66.58% 0.1574 58.32);   /* #D97706 */
  --color-error: oklch(51.59% 0.1994 24.98);     /* same as accent-hover */

  --radius-sm: 6px;
  --radius-lg: 14px;
  --shadow-float: 0 8px 24px -8px rgb(15 34 71 / 0.18);

  --ease-out: cubic-bezier(0.16, 1, 0.3, 1);
}
```

- Adapter: Tailwind v4 `@theme` (see `resources/css/app.css`). `--font-sans` overrides the existing default token; `--font-display` is new.

## Cards and surfaces

- Cards/surfaces: border-only (`border border-[--color-border]`), `radius-lg`, standard padding 24px (16px mobile). No cards-in-cards — a card's internal groupings use dividers/spacing, not nested bordered boxes.

## Slop audit

- Date: 2026-08-10 | Result: pass (see notes).
- Notes: no Inter/Roboto/system as primary face (Space Grotesk + Plus Jakarta Sans); accent sits in red/orange-red, outside the indigo/violet band; no hero+3-cards+testimonials+CTA reflex (marketplace layout grammar used instead: category rail, product grids, trust strip); radius capped at 14px, no blob rounding; shadow approach is single (defined-edge default, soft elevation reserved for floating surfaces only) and never doubled with a border; icons are one coherent Heroicons-outline subset, not a mixed/default kit; imagery is real product photos + brand mark only, no stock/AI fingerprint; motion limited to transform/opacity under 300ms with a reduced-motion guard. Re-audit after Phase 2 component build for state-matrix completeness (loading/empty/error on every network-bound action) before calling the pass done.

## Changelog

- 2026-08-10: Initial system authored for the full storefront redesign + bug-fix pass. Brand colors and logo sourced from `public/images/Kasabazaar-logo.jpg`; palette tuned brighter/warmer than the sibling Filament admin panel per explicit product decision (see kmarket redesign plan).
- 2026-08-20: Rebrand from *KasaBazaar Market* to **KASAROSE**. Colour strategy unchanged in shape (blue ink + red accent, stone neutrals) but every brand value resampled from the new mark — the `navy-*` scale moved from hue ~262 to ~245 and now terminates in the logo blue `#055F9C` at `navy-500`; `accent` is now the logo red `#EF4136` exactly. Token *names* deliberately kept so blade markup did not churn. Structural decisions (grid, type scale, component system, the "wake" motif) survived untouched; the wake now reads as an echo of the red wedge in the K rather than a ship wake. Source art moved out of the web root to `resources/brand/` and the whole asset set became reproducible via `php artisan brand:build`. Five long-form legal pages added, introducing the only prose-weight surfaces on the site (`.legal-prose` in `resources/css/app.css`).
