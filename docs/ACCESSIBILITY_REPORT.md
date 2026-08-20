# Accessibility Report

## What's in place
- Semantic HTML structure (`<main>`, `<table>`, `<form>`, `<button>`) is used
  throughout the new pages rather than div-soup.
- Color is never the *only* signal — status badges pair color with text/icons
  (e.g. "✅ Verified" / "⚠️ Not verified", "🔒 enabled" / "🔓 disabled").
- All interactive elements (buttons, links) are real `<button>`/`<a>` tags,
  not clickable `<div>`s, so they're keyboard-focusable and screen-reader
  operable by default.
- Forms use `required`, `type="email"`, `type="password"`, `minlength`
  attributes, so browsers provide native validation feedback rather than
  everything being silently rejected server-side only.

## Honest gap: form labeling
This is the most important finding, and it's a real gap, not a
nitpick — **every new form in this round (login, register, checkout,
account security, profile) uses placeholder text instead of associated
`<label for="">` elements.** Placeholder-only forms are a well-known
accessibility failure: the label disappears the moment someone starts
typing, and screen readers handle placeholder-as-label inconsistently.

Example of the current pattern:
```html
<input type="email" name="email" placeholder="Email" required>
```
What it should be:
```html
<label for="email">Email</label>
<input type="email" id="email" name="email" required>
```

**Why this wasn't fixed in this round**: retrofitting proper `<label>`
elements across ~15 forms touches visual layout (labels need space and
consistent styling) in a way that's better done as a deliberate, visible
design pass rather than a silent backend change — the project's ground rule
was "never change the UI unless necessary," and this genuinely is a UI
change, just one worth making. It's the top item in the Future Roadmap.

## Other gaps worth tracking
- No `aria-live` region for dynamic content (e.g. the checkout OTP timer,
  the notification badge count) — screen reader users won't be told when
  these update.
- The 2FA QR code image (`account_security.php`) has an `alt` attribute but
  a screen-reader user obviously can't scan it — the manual-entry secret key
  fallback text is already shown next to it, which does make the feature
  usable without vision, just not elegantly announced.
- No skip-to-content link for keyboard users on the longer pages
  (dashboard, packages).
- Contrast ratios weren't audited — the new pages inherit the site's
  existing color variables rather than introducing new ones, so this is a
  site-wide question rather than something specific to this round's work.

## What this means practically
Nothing built this round is unusable with a keyboard or screen reader — every
form can be tabbed through and submitted, every button has a text label. The
gaps above are about making the experience *good*, not about broken/blocked
functionality.
