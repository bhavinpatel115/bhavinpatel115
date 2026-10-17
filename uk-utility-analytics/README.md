# UK Utility Analytics website

A static, multi-page website for UK energy invoice validation and billing analysis. It has no framework and no runtime dependencies.

```
build.py            site generator: settings, shared layout, SEO head, schema, header/footer, sitemap
src/pages/*.html    page content (JSON metadata in the first comment, then the page body)
public/             THE WEBSITE: upload the contents of this folder to your host
  assets/css/site.css   design system
  assets/js/site.js     navigation, reveal, forms, calculator (about 6 KB)
  assets/fonts/         self-hosted fonts (SIL Open Font License, licences included)
  assets/img/           logo, favicon, social image
docs/AUDIT.md       research, audit findings, keyword map and design decisions
QA-REPORT.md        what was tested, results, remaining issues, launch checklist
```

## Editing

1. Edit text in `src/pages/`, or change settings at the top of `build.py`.
2. Run `python3 build.py` (Python 3.8+, no packages needed). This regenerates `public/`, including `sitemap.xml` and `robots.txt`.
3. Preview with `cd public && python3 -m http.server 8000`, then open http://localhost:8000. Links are root-relative, so double-clicking the files will not navigate correctly.

Tokens available in page bodies: `{{form}}`, `{{cta}}`, `{{cta:Title|Text}}`, `{{related}}`, `{{notice}}`, `{{email}}` and `{{icon:name}}`.

## Adding a page

Create `src/pages/NN-name.html` with the metadata block (`slug`, `crumb`, `title`, `description`, and `service` for a service page), then rebuild. Add it to `SERVICES` in `build.py` if it should appear in the navigation and related links.

## Reviews

The site has no testimonials, because none were supplied. Add genuine, verifiable client feedback only, with permission. Fake or invented reviews are unlawful under the Digital Markets, Competition and Consumers Act 2024.
