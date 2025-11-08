# Addy Business Theme Tokens

This folder contains standalone design tokens for the Addy Business 2.1 design system. The files mirror the source-of-truth document supplied by design and can be imported without touching the existing UI code.

## Files

- `colors.js` – official brand, neutral, accent, and semantic colors.
- `typography.js` – font families, weights, and size scale.
- `spacing.js` – spacing rhythm and border radius values.
- `shadows.js` – standard elevation presets.
- `tailwind.extend.js` – Tailwind `theme.extend` fragment ready for integration.
- `index.js` – bundled `addyTheme` export plus design principles and guardrails.

Import these modules from `resources/theme` when you are ready to wire the tokens into real components or Tailwind config files.
