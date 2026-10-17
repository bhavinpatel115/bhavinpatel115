# UK Utility Analytics: research, audit and design decisions

Prepared before the rebuild. Each finding is about the site as it stood at commit `1152bfd` (the single-page cinematic version).

## 1. Research summary

### Limits on this research
- The build sandbox could not open external pages directly. Competitor sites and Google Search Central were blocked by the network proxy, so the findings below come from web search results and their excerpts.
- No keyword volume tool was available. The Ahrefs connector failed to connect. Keyword choices rest on the language competitors and buyers use in search results, not on measured search volume. Validate them in Google Search Console and Keyword Planner after launch.

### UK competitors seen in search results
Trident Utilities, Catalyst Commercial, Smarter Business, West Mercia Energy (WME), Energy Brokers / Energy Solutions, Ameresco (AXIS), ista UK and TEC bureau services. Public bodies also tender for "bill validation services" on Find a Tender, for example Westminster City Council, Northern Gas Networks and London Fire Brigade.

What they do well:
- They give each service its own page (Trident has `/services/invoice-validation` and `/utility-invoice-validation`, and Energy Brokers has `/billvalidation`).
- They spell out the checks: line by line against contracted rates, consumption, capacity (kVA), non-commodity charges, VAT and CCL. TEC lists "Account Number Matches, MPAN/MPR Matches, Correct Supplier, Correct dates" plus read, tariff and levy checks.
- They answer commercial questions: fees (fixed fee or no win no fee), how far back a review goes, and who handles supplier queries.

Where they are weak, and where this site can win:
- They lean on unverified statistics ("1 in 3 invoices wrong", "3 to 5% overspend"). This site's brief forbids promised savings, so it competes on method, transparency and the deliverable (the exception report) instead.
- Most are brokers or energy consultancies that sell validation as an add-on. A specialist back-office validation provider that works *for* consultants, brokers and suppliers is a distinct position. Few results target "white-label" or "extra capacity" needs.

### Search intent and keyword map
"Bill validation" and "invoice validation" mean the same thing in UK usage. Tenders say "bill validation"; consultancies say "invoice validation". Both terms appear naturally across the site.

| URL | Primary intent / keyword | Secondary terms used naturally |
|---|---|---|
| `/` | energy invoice validation (UK) | energy bill validation, utility bill validation, billing analysis |
| `/business-electricity-bill-checking/` | business electricity bill checking | electricity bill validation, half-hourly, kVA, DUoS, TNUoS, non-commodity charges, CCL |
| `/gas-invoice-validation/` | gas invoice validation | gas bill validation, MPRN, calorific value, volume conversion, AQ |
| `/contract-rate-verification/` | contract rate verification | unit rate, standing charge, price confirmation, deemed rates, out-of-contract rates |
| `/mpan-mprn-checks/` | MPAN / MPRN checks | meter data checks, meter readings, EAC, AQ, meter exchange |
| `/energy-billing-reconciliation/` | energy billing reconciliation | credit notes, rebills, historical invoice reconciliation, VAT reconciliation, exception reports |
| `/contact/` | request a quote (transactional) | |

Each service page answers a distinct search with its own checks, inputs, outputs and FAQs, so none of them is a doorway or thin page. No location pages or blog were created, because no genuine content or local presence exists to support them.

### Google Search Central guidance applied
- People-first content: descriptive titles and headings with no exaggeration, and pages that answer the question a searcher has.
- FAQ rich results: Google limited them to government and health sites in August 2023. Secondary reports (TechWyse, Exchange4media) say they stopped appearing for all sites from 7 May 2026. `FAQPage` markup therefore earns nothing, so the visible FAQs stay for people and the markup is removed.
- Organization structured data has no required properties. `logo` and `contactPoint` are recommended, and it belongs on the home page. `BreadcrumbList` is used on inner pages.
- Crawlable links: every page is reachable through plain `<a href>` links in the header, footer, breadcrumbs and related-service blocks.

### UK legal points that affect the site
- A form collecting personal data needs a privacy notice linked from the form, covering controller identity, purpose, lawful basis, recipients, retention, rights and the right to complain to the ICO. The ICO has a free generator for small organisations.
- Fake reviews are banned under the Digital Markets, Competition and Consumers Act 2024.

## 2. Audit of the previous build

| # | Finding | Severity | Fix in rebuild |
|---|---|---|---|
| 1 | Placeholder testimonials with invented roles and locations ("Independent consultancy, Midlands") were presented as client feedback. This is unlawful under DMCC 2024 and breaks the brief. | Critical | Removed. A documented slot is left for real, verifiable reviews. |
| 2 | Invented commitments: "We reply within one working day" and "No obligation" are not supplied business facts. | High | Removed. The copy says what will happen without promising a time. |
| 3 | Single URL targeting every keyword. There was no keyword-to-page mapping, so the site could only realistically rank for its brand. | High | Six indexable pages, each mapped to one intent. |
| 4 | The hero was a 460vh pinned scroll scene. Services only appeared after about four screens of scrolling. It hurts CRO and engagement, and keyboard and screen-reader users got hidden captions. | High | Normal-flow hero with the H1, value proposition, CTAs and a static-first illustration in the first viewport. |
| 5 | A full-screen canvas animation ran constantly (about 30fps) on every page view. That costs CPU and battery and adds nothing to trust. | Medium | Removed. Motion is now purposeful, short, runs once and respects reduced motion. |
| 6 | `FAQPage` schema earns no rich result (see above). | Low | Removed. Valid `Organization`, `WebSite`, `Service` and `BreadcrumbList` used instead. |
| 7 | No privacy notice, although the form collects personal data. | High (legal) | `/privacy/` added with clearly marked details the business must complete. It stays `noindex` until then. |
| 8 | The domain and email are placeholders. With no form backend, the form falls back to `mailto:`. | Launch blocker | Centralised in `build.py` (`SITE_URL`, `EMAIL`, `FORM_ENDPOINT`). Listed as launch requirements. |
| 9 | 98 KB of single-file HTML with inline CSS and JS. No caching between pages and hard to maintain. | Medium | Shared `site.css` and `site.js`, with pages generated from one template by `build.py`. |
| 10 | Inline style attributes on headings, so typography was not systematised. | Low | Design tokens and utility classes only. |
| 11 | Facts strip ("10 validation areas", "1→∞") read like marketing statistics. | Low | Replaced with factual scope statements. |
| 12 | No 404 page. | Low | `404.html` added. |

## 3. Design decisions

- **Direction, "the analyst's working paper":** the site looks like the work it sells. It uses a ledger-grid paper ground, crisp typesetting, monospaced figures and annotation marks: green ticks for verified lines, an orange circle for an exception, and a lime highlighter that sweeps across key phrases. It is premium through restraint and precision rather than effects, which suits a B2B audience buying accuracy.
- **Colour** comes from the logo: navy `#0B2B53` (ink and authority), blue `#1877C9` (links and data), green `#2F7D1F` (verified; AA-safe text shade) and lime `#8DC63F` (highlighter only, never text). One warm signal colour, `#C2410C`, is reserved for exceptions. All text pairs meet WCAG AA.
- **Type:** Schibsted Grotesk for headings and body (editorial, confident, variable weight) and IBM Plex Mono for figures, labels and annotations. That is two families, loaded with `display=swap`.
- **Imagery:** no stock photography, which would undermine trust for a data business. All illustrations are crafted HTML and SVG: an annotated invoice, an MPAN breakdown, the gas conversion formula and a sample exception report. Each one is labelled illustrative where it shows figures.
- **Motion:** each annotation draws once when it enters view and the highlighter sweeps once. Everything is static under `prefers-reduced-motion`.
- **Conversion:** one primary action ("Request a quote") in the header, hero, every service page and a closing band. There is a secondary path ("See what we check"). The form is short and shows inline errors next to each field. The contact form appears on the home page and on `/contact/`.
