# AMDS Google sitemap

The public entry point is `https://amds.hr/site-map.xml`. The site's existing
redirect sends the apex domain to the canonical `www.amds.hr` host. `robots.txt`
declares `https://www.amds.hr/site-map.xml` directly.

The index links to:

- `site-map-pages.xml`: home, contact, visible categories, brands, information
  pages, blog home, published blog posts and visible blog categories.
- `site-map-products-N.xml`: up to 1,000 visible products per file, ordered by
  product ID. The query matches the storefront's active status, positive stock,
  available date, language and store rules. It does not calculate prices, load
  reviews or resize images.

URLs use HTTPS and the storefront's SEO URL builder. Product URLs omit category
and manufacturer variants; category URLs match their leaf-only canonicals.
Products without images remain included. Original image URLs are encoded per
path segment. Product modification dates are emitted only when valid and not in
the future. Other pages omit modification dates rather than claim a crawl or
generation time as a content change. The feed is generated from current catalog
data on each request.

The reserved paths are resolved by `startup/google_sitemap` before the existing
SEO controller. The startup action is registered in `system/config/catalog.php`,
which OpenCart reads directly. This also works when an existing OpenCart
modification shadows the SEO controller; no modification cache refresh,
`.htaccess` change or SEO alias insertion is required. The previous `sitemap.xml` and
`index.php?route=extension/feed/google_sitemap` entry points remain compatible.
Disabled feeds and invalid product shard numbers return HTTP 404.

## Release

Commit and push the reviewed changes before changing production. The existing
production Git repository is `/home/amds/public_html`, on `main`. Fetch and
fast-forward the reviewed revision through the project's Git release procedure;
inspect and preserve server changes before updating. Do not copy individual
source files to the server.

After the PHP changes are present, run the tracked CLI command from the
repository root with the site's PHP CLI:

```sh
php bin/google-sitemap.php --check
php bin/google-sitemap.php --apply
php bin/google-sitemap.php --check
```

`--check` only reads the database and reports the default store's feed state as
JSON; an unconfigured or disabled feed is a successful check with
`"enabled": false`. `--apply` reads
`storage/migrations/40_enable_google_sitemap.sql`, uses the table prefix from
`upload/config.php`, applies every statement, and verifies that store 0 has one
enabled setting and exactly one feed registration. It exits unsuccessfully if
verification fails. Both modes omit database credentials from output.

The migration enables and registers the feed for store 0; rerunning it does not
duplicate settings or the extension registration. Explicit overrides belonging
to other stores are kept. The command requires the `mysqli` PHP extension and
the installation's existing `upload/config.php` configuration.

The release preserves existing OpenCart modification copies. Check that no
generated copy unexpectedly shadows the new `startup/google_sitemap` or feed
controller/model; do not hand-edit generated modification files.

Verify the public index, every referenced child sitemap and `robots.txt` after
release. Expect HTTP 200 and `application/xml; charset=UTF-8` for sitemap files,
valid XML, HTTPS URLs, no repeated page URLs and at most 1,000 products in each
product shard. Confirm the live homepage still works. Google Search Console can
then be given `https://www.amds.hr/site-map.xml`; code publication itself does
not submit the sitemap to Search Console.

## Verification

Run the offline regressions with `php tests/sitemap/run.php`.

For release verification, parse output generated with the actual catalog models
and validate the index and child files against the official sitemap and Google
image schemas. Check the enable migration in an isolated database, including a
rerun and an existing disabled setting. Never run fixture migration checks
against production tables.

References:

- <https://developers.google.com/search/docs/crawling-indexing/sitemaps/build-sitemap>
- <https://www.sitemaps.org/protocol.html>
