# QA report: UK Utility Analytics rebuild

Tested locally with headless Chromium 141 (Playwright) and Lighthouse 13.5. Pages were served by a local static server from `public/`.

## What changed

- **Structure:** the single-page site became 7 indexable pages plus a privacy notice and a 404 page. Each page is mapped to one search intent (see `docs/AUDIT.md`):
  - `/` (energy invoice validation)
  - `/business-electricity-bill-checking/`
  - `/gas-invoice-validation/`
  - `/contract-rate-verification/`
  - `/mpan-mprn-checks/`
  - `/energy-billing-reconciliation/`
  - `/contact/`
- **Design:** new "analyst's working paper" direction. It uses a ledger-grid ground and the logo palette with AA-safe text shades. Type is Schibsted Grotesk and IBM Plex Mono. The signature element is an annotated invoice, with highlighter marks on key phrases. The MPAN breakdown, gas conversion formula, sample exception report and rate-check calculator are all built in code, with no stock imagery.
- **Content:** rewritten in British English. Every service and check in the original brief is covered. Each service page has its own checks, common issues, inputs, FAQs, related links and CTA.
- **Removed, because they were not supplied business facts:**
  - placeholder testimonials
  - "reply within one working day"
  - "no obligation"
  - statistic-style counters
  - `FAQPage` schema (Google no longer shows FAQ rich results)
- **Technical SEO:**
  - unique titles (≤60 characters) and meta descriptions (≤160 characters)
  - one H1 per page with a logical H2/H3 hierarchy
  - absolute canonical URLs
  - `ProfessionalService`, `WebSite`, `Service`, `WebPage` and `BreadcrumbList` JSON-LD
  - visible breadcrumbs and related-service internal links
  - generated `sitemap.xml` (indexable pages only) and `robots.txt`
  - `noindex` on the privacy and 404 pages
  - alt text on every meaningful image
- **Conversion:**
  - "Request a quote" appears in the header, the hero, every service page, a closing band and a mobile sticky bar
  - the enquiry form is on the home page and `/contact/`, with inline errors and focus on the first error
  - a link to the privacy notice sits beside the form
- **Performance:**
  - shared cached CSS and JS
  - self-hosted fonts with the main font preloaded, so no third-party requests and no data sent to Google Fonts
  - the always-running canvas animation and the 460vh pinned hero were removed

## Tests performed and results

| Area | Test | Result |
|---|---|---|
| Console / runtime | All 9 pages at 1440px and 390px | No console errors and no page errors |
| Assets | Every request on every page | No 4xx/5xx responses and no broken images |
| Links | All 21 unique internal links and anchors | All resolve (200, anchor present) |
| Headings | H1 count and level skips on every page | One H1 per page and no skipped levels |
| Forms | Labels, required validation, bad email, focus on first error | Pass |
| Forms | Valid submit with no backend opens `mailto:` and shows a confirmation | Pass |
| Forms | Valid submit with a backend (mocked 200) posts all fields, excludes the honeypot and shows a confirmation | Pass |
| Forms | Backend failure (mocked 500) shows an alert with the email fallback and re-enables the button | Pass |
| Forms | Honeypot filled | Submission blocked |
| Navigation | Services menu by keyboard (Enter/Esc) and mouse, `aria-current` | Pass |
| Navigation | Mobile menu open, links and CTA visibility; sticky CTA | Pass. One bug found and fixed: the mobile menu CTA text was invisible. |
| Calculator | £10.71 per invoice / £130.30 a year (standing charge); £374.40 (unit rate); "No exception" when prices match | Pass |
| Layout | Horizontal overflow at 390px and 1440px | None |
| Accessibility | Touch targets ≥24px, skip link, reduced motion, content visible without JavaScript | Pass |
| SEO | JSON-LD parses on every page; canonical, robots, title and description lengths | Pass |
| Lighthouse (mobile) | All 7 indexable pages | Performance 99, Accessibility 100, Best practices 100, SEO 100. LCP 1.8 to 2.1 s, CLS 0, TBT 0 ms |
| Lighthouse (desktop) | All 7 indexable pages | 100 in all four categories. LCP 0.4 to 0.5 s |
| Page weight | Total transfer per page | 150 to 206 KB |

Issues found during testing and fixed before delivery:
- the mobile menu CTA had invisible text
- a service card was orphaned on desktop
- there was excess space above the footer
- some titles and descriptions were too long
- the gas formula broke mid-term
- render-blocking Google Fonts gave a mobile performance score of 89 to 93; self-hosting raised it to 99

## Limitations of this QA

- Tested in Chromium only. Safari and Firefox were not available in the sandbox. The code uses standard, widely supported CSS and JS (CSS grid, `IntersectionObserver`, `<details>`), but check on a real iPhone and in Firefox before launch.
- Lighthouse ran on a local server, not on the production host. Re-measure on the live domain with PageSpeed Insights.
- Structured data was validated as JSON and against schema.org types, not with Google's Rich Results Test, which was not reachable from the sandbox. Run it after launch.
- Keyword choices are based on competitor and SERP language, with no measured search volumes (no keyword tool was available).

## Launch requirements (must be done before going live)

1. **Domain:** set `SITE_URL` in `build.py` to the live domain and rebuild. This updates canonicals, Open Graph, schema, sitemap and robots.
2. **Email:** set `EMAIL` in `build.py` to the real business email and rebuild. The current address is a placeholder.
3. **Form backend:** set `FORM_ENDPOINT` (for example a Formspree or Basin URL) so enquiries arrive without relying on the visitor's email app. Then send a real test enquiry.
4. **Privacy notice:** complete every `[bracketed]` item in `src/pages/90-privacy.html` (the ICO's free generator can help), remove `"noindex": true` and rebuild.
5. **Hosting:** upload the contents of `public/`. Configure the host to serve `404.html` for missing pages and to force HTTPS and one hostname (www or non-www, matching `SITE_URL`).
6. **Search Console:** verify the domain, submit `sitemap.xml` and check the Rich Results Test for the home page and one service page.

## Recommended next steps (not blocking)

- Add genuine client reviews or case studies with permission. They are the strongest trust signal still missing.
- Add verifiable company details (registered name, number, address) to the footer and the `ProfessionalService` schema.
- Publish in-depth guides only where there is real expertise to share (for example "How to read a half-hourly electricity invoice"). They would broaden organic reach without thin content.
