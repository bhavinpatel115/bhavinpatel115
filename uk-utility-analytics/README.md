# UK Utility Analytics: one-page website

Static, dependency-free one-pager (HTML + CSS + vanilla JS). The hero is a scroll-driven story: the invoice comes apart in 3D, each layer is checked, and the wrong charge is flagged. Phones and reduced-motion visitors get a composed still version. Upload the folder contents to any host (Hostinger, cPanel, Netlify, Vercel, GitHub Pages).

```
index.html      page, styles and scripts
assets/         logo (from brand PDF), favicon, apple-touch-icon, og-image
robots.txt
sitemap.xml
```

## Before going live

1. **Domain**: replace `https://www.ukutilityanalytics.co.uk/` in `index.html` (canonical, Open Graph, JSON-LD), `robots.txt` and `sitemap.xml`.
2. **Email**: replace `hello@ukutilityanalytics.co.uk` (contact section, mobile CTA, `CONTACT_EMAIL` in the script).
3. **Enquiry form**: set `data-endpoint` on `<form id="enquiry">` to a form backend (Formspree, Basin, Netlify Forms…). Without one, the form opens the visitor's email app with the enquiry pre-filled.
4. **Reviews**: the four testimonials are placeholders. Replace them with real client feedback (with permission). Fake reviews are unlawful in the UK (DMCC Act 2024). Once reviews are real, `Review` / `AggregateRating` schema can be added.
5. **Sample report**: the exception-report figures are labelled illustrative. Keep that label or swap in anonymised real data.
6. Submit `sitemap.xml` in Google Search Console and create a Google Business Profile.

## Optional upgrade

The 3D invoice hero is built in code. An AI-generated hero video (Higgsfield) can be added later once the account has credits.

## SEO included

- One `h1`, logical `h2`/`h3` outline, semantic landmarks, `lang="en-GB"`
- Title and meta description targeting "energy invoice validation", "bill validation UK", MPAN/MPRN, CCL, etc.
- JSON-LD: `ProfessionalService` (with service catalogue), `WebSite`, `FAQPage`
- Open Graph / Twitter cards, canonical, robots.txt, sitemap.xml
- Content visible without JavaScript; no framework, so the page is fast for Core Web Vitals
- Respects `prefers-reduced-motion`, keyboard accessible tabs/accordion/menu
