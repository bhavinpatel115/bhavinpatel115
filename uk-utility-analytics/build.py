#!/usr/bin/env python3
"""Build the UK Utility Analytics static site.

Page bodies live in src/pages/*.html. Each starts with a JSON block inside an
HTML comment that holds the page's metadata. This script wraps every body in
the shared layout (head, SEO tags, schema, header, footer) and writes the
finished site to public/. Run:  python3 build.py
"""
import datetime
import html
import json
import pathlib
import re

# ---------------------------------------------------------------------------
# Site settings. Change these before launch (see QA-REPORT.md).
SITE_URL = "https://www.ukutilityanalytics.co.uk"  # TODO: live domain, no trailing slash
EMAIL = "hello@ukutilityanalytics.co.uk"            # TODO: real business email
FORM_ENDPOINT = ""                                  # TODO: form backend URL (e.g. Formspree); empty = mailto fallback
BUSINESS = "UK Utility Analytics"
# ---------------------------------------------------------------------------

ROOT = pathlib.Path(__file__).parent
SRC = ROOT / "src" / "pages"
OUT = ROOT / "public"
TODAY = datetime.date.today().isoformat()

SERVICES = [
    ("business-electricity-bill-checking", "Business electricity bill checking", "Half-hourly and non-half-hourly electricity invoices, line by line."),
    ("gas-invoice-validation", "Gas invoice validation", "Volume conversion, MPRN, standing charges, CCL and VAT."),
    ("contract-rate-verification", "Contract rate verification", "Unit rates and standing charges against your price confirmation."),
    ("mpan-mprn-checks", "MPAN & MPRN checks", "Supply numbers, meter reads, meter exchanges, EAC and AQ."),
    ("energy-billing-reconciliation", "Energy billing reconciliation", "Credit notes, rebills, VAT and historical invoice reviews."),
]

ICON = {
    "arrow": '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg>',
    "tick": '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12.5l4.5 4.5L19 7.5"/></svg>',
    "check": '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12.5l4.5 4.5L19 7.5"/></svg>',
    "mail": '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 7l9 6 9-6"/></svg>',
    "chev": '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 9l6 6 6-6"/></svg>',
    "menu": '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16"/></svg>',
    "info": '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 11v5M12 8h.01"/></svg>',
}


def url(slug):
    return f"{SITE_URL}/{slug + '/' if slug else ''}"


def path(slug):
    return f"/{slug + '/' if slug else ''}"


def esc(s):
    return html.escape(s, quote=True)


# ---------------------------------------------------------------------------
# shared components

def header(active):
    current = ' aria-current="page"'
    menu = "".join(
        f'<li><a href="{path(s)}"{current if s == active else ""}><b>{esc(t)}</b><span>{esc(d)}</span></a></li>'
        for s, t, d in SERVICES
    )
    msub = "".join(f'<li><a href="{path(s)}">{esc(t)}</a></li>' for s, t, _ in SERVICES)

    def cur(s):
        return ' aria-current="page"' if s == active else ""

    return f'''<a class="skip" href="#main">Skip to main content</a>
<header class="site-header">
  <div class="wrap header-in">
    <a class="brand" href="/" aria-label="{BUSINESS} home">
      <img src="/assets/img/logo-mark-240.webp" width="240" height="147" alt="">
      <span><span class="brand-name">UK Utility <span>Analytics</span></span><span class="brand-tag">Energy billing &middot; Validation</span></span>
    </a>
    <nav class="nav" aria-label="Main">
      <ul>
        <li class="has-menu">
          <button class="nav-btn" type="button" aria-expanded="false" aria-controls="services-menu">Services {ICON["chev"]}</button>
          <ul class="menu" id="services-menu">{menu}<li><a href="/#services"><b>All services</b><span>Overview of every check we offer.</span></a></li></ul>
        </li>
        <li><a href="/#how-it-works">How it works</a></li>
        <li><a href="/#faq">FAQ</a></li>
        <li><a href="/contact/"{cur("contact")}>Contact</a></li>
      </ul>
    </nav>
    <div class="header-cta"><a class="btn" href="/contact/">Request a quote {ICON["arrow"]}</a></div>
    <button class="menu-toggle" type="button" aria-expanded="false" aria-controls="mobile-nav" aria-label="Open menu">{ICON["menu"]}</button>
  </div>
</header>
<nav class="mobile-nav" id="mobile-nav" aria-label="Mobile">
  <ul>
    <li><a href="/">Home</a></li>
    <li><span class="label">Services</span><ul class="sub">{msub}</ul></li>
    <li><a href="/#how-it-works">How it works</a></li>
    <li><a href="/#faq">FAQ</a></li>
    <li><a href="/contact/">Contact</a></li>
  </ul>
  <a class="btn" href="/contact/">Request a quote {ICON["arrow"]}</a>
</nav>'''


def footer():
    svc = "".join(f'<li><a href="{path(s)}">{esc(t)}</a></li>' for s, t, _ in SERVICES)
    return f'''<footer class="site-footer">
  <div class="wrap">
    <div class="footer-grid">
      <div class="footer-brand">
        <img src="/assets/img/logo-full-900.webp" width="900" height="279" alt="{BUSINESS}: energy billing, validation, analytics" loading="lazy" decoding="async">
        <p>Specialist energy invoice validation and billing analysis for UK energy consultants, utility brokers, energy suppliers and businesses.</p>
      </div>
      <div>
        <h2>Services</h2>
        <ul>{svc}</ul>
      </div>
      <div>
        <h2>Company</h2>
        <ul>
          <li><a href="/#how-it-works">How it works</a></li>
          <li><a href="/#faq">FAQ</a></li>
          <li><a href="/contact/">Request a quote</a></li>
          <li><a href="mailto:{EMAIL}">{EMAIL}</a></li>
          <li><a href="/privacy/">Privacy notice</a></li>
        </ul>
      </div>
    </div>
    <div class="footer-legal">
      <p>Our services provide invoice review and analytical support based on the information made available to us. Findings depend on the completeness of the supporting records and should be confirmed with the relevant supplier or an authorised professional where necessary. We do not guarantee savings or refunds.</p>
      <p>&copy; <span data-year>{datetime.date.today().year}</span> {BUSINESS}</p>
    </div>
  </div>
</footer>
<div class="sticky-cta">
  <a class="btn" href="/contact/">Request a quote</a>
  <a class="btn btn-ghost" href="mailto:{EMAIL}" aria-label="Email {BUSINESS}">{ICON["mail"]}</a>
</div>'''


def form(heading_level=2, heading="Request a quote", form_id="enquiry"):
    opts_v = "".join(f"<option>{o}</option>" for o in ["1 to 50", "51 to 250", "251 to 1,000", "More than 1,000", "One-off or historical review"])
    opts_s = "".join(f"<option>{esc(t)}</option>" for _, t, _ in SERVICES) + "<option>Reporting &amp; supplier query support</option><option>Not sure yet, or several</option>"
    h = f"h{heading_level}"
    return f'''<form class="form" id="{form_id}" data-enquiry novalidate aria-labelledby="{form_id}-title">
  <div class="full"><{h} id="{form_id}-title">{heading}</{h}><p class="muted small" style="margin-top:6px">Tell us about your invoices and the checks you need. We will reply with a proposed scope and pricing.</p></div>
  <div class="field"><label for="{form_id}-name">Full name</label><input id="{form_id}-name" name="name" autocomplete="name" required><span class="error" id="{form_id}-name-e">Please enter your full name.</span></div>
  <div class="field"><label for="{form_id}-company">Company name</label><input id="{form_id}-company" name="company" autocomplete="organization" required><span class="error">Please enter your company name.</span></div>
  <div class="field"><label for="{form_id}-email">Business email</label><input id="{form_id}-email" name="email" type="email" autocomplete="email" required><span class="error">Please enter a valid email address.</span></div>
  <div class="field"><label for="{form_id}-phone">Contact number <span class="opt">(optional)</span></label><input id="{form_id}-phone" name="phone" type="tel" autocomplete="tel"></div>
  <div class="field"><label for="{form_id}-volume">Invoices per month</label><select id="{form_id}-volume" name="volume" required><option value="">Select a range</option>{opts_v}</select><span class="error">Please choose a range.</span></div>
  <div class="field"><label for="{form_id}-service">Required service</label><select id="{form_id}-service" name="service" required><option value="">Select a service</option>{opts_s}</select><span class="error">Please choose a service.</span></div>
  <div class="field full"><label for="{form_id}-message">Message <span class="opt">(optional)</span></label><textarea id="{form_id}-message" name="message" placeholder="Fuel types, number of sites or supplies, the data you can share, and any deadlines."></textarea></div>
  <div class="hp" aria-hidden="true"><label for="{form_id}-website">Leave this field empty</label><input id="{form_id}-website" name="company_website" tabindex="-1" autocomplete="off"></div>
  <div class="full"><button class="btn" type="submit">Submit enquiry {ICON["arrow"]}</button></div>
  <p class="form-legal full">We use these details only to respond to your enquiry. See our <a href="/privacy/">privacy notice</a>.</p>
  <div class="form-status" role="status" tabindex="-1"><span class="mark">{ICON["tick"]}</span><h3>Thank you</h3><p>Your enquiry has been sent. We will be in touch to discuss your requirements.</p></div>
</form>'''


def cta(title="Need more energy invoice validation capacity?", text="Tell us about your monthly invoice volumes, data and validation scope. We will come back with a clear proposal and pricing."):
    return f'''<section class="section tight" aria-labelledby="cta-title">
  <div class="wrap">
    <div class="cta-band reveal">
      <div><h2 id="cta-title">{title}</h2><p style="margin-top:14px">{text}</p></div>
      <div class="btn-row"><a class="btn btn-light" href="/contact/">Request a quote {ICON["arrow"]}</a><a class="btn btn-outline-light" href="mailto:{EMAIL}">Email us</a></div>
    </div>
  </div>
</section>'''


def related(current):
    items = "".join(
        f'<a href="{path(s)}">{esc(t)}<span>{esc(d)}</span></a>' for s, t, d in SERVICES if s != current
    )
    return f'''<section class="section tight" aria-labelledby="related-title">
  <div class="wrap">
    <h2 id="related-title" style="font-size:clamp(1.5rem,2.4vw,2rem);margin-bottom:24px">Related services</h2>
    <nav class="related" aria-label="Related services">{items}</nav>
  </div>
</section>'''


def notice():
    return f'''<section aria-labelledby="notice-title" class="section tight" style="padding-top:0">
  <div class="wrap"><div class="notice">{ICON["info"]}<div><h2 id="notice-title">Important information</h2><p>Our services provide invoice review and analytical support based on the information made available to us. Findings are subject to the completeness of the supporting records and should be confirmed with the relevant supplier or authorised professional where necessary. We do not guarantee savings or refunds.</p></div></div></div>
</section>'''


def breadcrumbs(meta):
    if not meta["slug"] or meta.get("noindex"):
        return ""
    trail = [("Home", "/")]
    if meta.get("service"):
        trail.append(("Services", "/#services"))
    trail.append((meta["crumb"], None))
    lis = "".join(
        f'<li><a href="{h}">{esc(n)}</a></li>' if h else f'<li aria-current="page">{esc(n)}</li>' for n, h in trail
    )
    return f'<nav class="crumbs wrap" aria-label="Breadcrumb"><ol>{lis}</ol></nav>'


# ---------------------------------------------------------------------------
# structured data

ORG_ID = f"{SITE_URL}/#organization"


def schema(meta):
    graph = []
    if meta["slug"] == "":
        graph.append({
            "@type": "ProfessionalService",
            "@id": ORG_ID,
            "name": BUSINESS,
            "url": url(""),
            "logo": f"{SITE_URL}/assets/img/logo-full-900.webp",
            "image": f"{SITE_URL}/assets/img/og-image.png",
            "email": EMAIL,
            "description": "Specialist UK energy invoice validation, billing analysis and reconciliation for energy consultants, utility brokers, energy suppliers and businesses.",
            "slogan": "Smarter energy billing. Greater accuracy.",
            "areaServed": {"@type": "Country", "name": "United Kingdom"},
            "knowsAbout": ["Energy invoice validation", "Energy bill validation", "Business electricity bill checking", "Gas invoice validation", "Contract rate verification", "MPAN", "MPRN", "Estimated Annual Consumption", "Climate Change Levy", "Non-commodity charges", "Energy billing reconciliation"],
            "contactPoint": {"@type": "ContactPoint", "contactType": "sales", "email": EMAIL, "areaServed": "GB", "availableLanguage": "en-GB"},
            "hasOfferCatalog": {"@type": "OfferCatalog", "name": "Energy billing services", "itemListElement": [
                {"@type": "Offer", "itemOffered": {"@type": "Service", "name": t, "url": url(s)}} for s, t, _ in SERVICES
            ]},
        })
        graph.append({"@type": "WebSite", "@id": f"{SITE_URL}/#website", "url": url(""), "name": BUSINESS, "inLanguage": "en-GB", "publisher": {"@id": ORG_ID}})
    else:
        graph.append({"@type": "WebPage", "@id": url(meta["slug"]) + "#webpage", "url": url(meta["slug"]), "name": meta["title"], "description": meta["description"], "inLanguage": "en-GB", "isPartOf": {"@id": f"{SITE_URL}/#website"}})
        if meta.get("service"):
            graph.append({
                "@type": "Service",
                "@id": url(meta["slug"]) + "#service",
                "name": meta["crumb"],
                "serviceType": meta["service"],
                "description": meta["description"],
                "provider": {"@type": "ProfessionalService", "@id": ORG_ID, "name": BUSINESS, "url": url("")},
                "areaServed": {"@type": "Country", "name": "United Kingdom"},
                "url": url(meta["slug"]),
            })
        crumbs = [{"@type": "ListItem", "position": 1, "name": "Home", "item": url("")}]
        crumbs.append({"@type": "ListItem", "position": 2, "name": meta["crumb"]})
        graph.append({"@type": "BreadcrumbList", "itemListElement": crumbs})
    return json.dumps({"@context": "https://schema.org", "@graph": graph}, indent=1, ensure_ascii=False)


# ---------------------------------------------------------------------------

def layout(meta, body):
    slug = meta["slug"]
    robots = "noindex, follow" if meta.get("noindex") else "index, follow, max-image-preview:large, max-snippet:-1"
    canonical = "" if meta.get("no_canonical") else f'<link rel="canonical" href="{url(slug)}">'
    ld = "" if meta.get("noindex") else f'<script type="application/ld+json">\n{schema(meta)}\n</script>'
    return f'''<!doctype html>
<html lang="en-GB">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>{esc(meta["title"])}</title>
<meta name="description" content="{esc(meta["description"])}">
<meta name="robots" content="{robots}">
{canonical}
<meta name="theme-color" content="#0B2B53">
<link rel="icon" href="/assets/img/favicon-64.png" type="image/png">
<link rel="apple-touch-icon" href="/assets/img/apple-touch-icon.png">
<meta property="og:type" content="website">
<meta property="og:locale" content="en_GB">
<meta property="og:site_name" content="{BUSINESS}">
<meta property="og:title" content="{esc(meta["title"])}">
<meta property="og:description" content="{esc(meta["description"])}">
<meta property="og:url" content="{url(slug)}">
<meta property="og:image" content="{SITE_URL}/assets/img/og-image.png">
<meta property="og:image:width" content="1200">
<meta property="og:image:height" content="630">
<meta name="twitter:card" content="summary_large_image">
<link rel="preload" href="/assets/fonts/schibsted-grotesk-latin-wght-normal.woff2" as="font" type="font/woff2" crossorigin>
<link rel="stylesheet" href="/assets/css/site.css">
<script>document.documentElement.className+=' js';window.UUA={{email:{json.dumps(EMAIL)},formEndpoint:{json.dumps(FORM_ENDPOINT)}}};</script>
<script src="/assets/js/site.js" defer></script>
{ld}
</head>
<body>
{header(slug)}
<main id="main">
{breadcrumbs(meta)}
{body}
</main>
{footer()}
</body>
</html>
'''


def render_tokens(body, meta):
    body = body.replace("{{form}}", form())
    body = body.replace("{{form:h1}}", form(heading_level=2, heading="Your enquiry"))
    body = body.replace("{{notice}}", notice())
    body = body.replace("{{related}}", related(meta["slug"]))
    body = re.sub(r"\{\{cta(?::([^|}]*)\|([^}]*))?\}\}", lambda m: cta(m.group(1), m.group(2)) if m.group(1) else cta(), body)
    body = body.replace("{{email}}", EMAIL)
    for k, v in ICON.items():
        body = body.replace("{{icon:" + k + "}}", v)
    leftover = re.findall(r"\{\{[^}]+\}\}", body)
    if leftover:
        raise SystemExit(f"Unresolved tokens in {meta['slug'] or 'home'}: {leftover}")
    return body


def main():
    pages = []
    for f in sorted(SRC.glob("*.html")):
        text = f.read_text(encoding="utf-8")
        m = re.match(r"\s*<!--(.*?)-->\s*", text, re.S)
        meta = json.loads(m.group(1))
        body = render_tokens(text[m.end():], meta)
        out = OUT / (meta.get("file") or (meta["slug"] + "/index.html" if meta["slug"] else "index.html"))
        out.parent.mkdir(parents=True, exist_ok=True)
        out.write_text(layout(meta, body), encoding="utf-8")
        pages.append(meta)
        print(f"built {out.relative_to(ROOT)}")

    # sitemap: indexable pages only
    urls = [p for p in pages if not p.get("noindex")]
    urls.sort(key=lambda p: (p["slug"] != "", p.get("priority", 0) * -1))
    entries = "\n".join(f"  <url><loc>{url(p['slug'])}</loc><lastmod>{TODAY}</lastmod></url>" for p in urls)
    (OUT / "sitemap.xml").write_text(f'<?xml version="1.0" encoding="UTF-8"?>\n<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">\n{entries}\n</urlset>\n', encoding="utf-8")
    (OUT / "robots.txt").write_text(f"User-agent: *\nAllow: /\n\nSitemap: {SITE_URL}/sitemap.xml\n", encoding="utf-8")
    print(f"built sitemap.xml ({len(urls)} URLs) and robots.txt")


if __name__ == "__main__":
    main()
