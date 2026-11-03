# Sitemap regression checks

Run `php tests/sitemap/run.php` from the repository root. PHP SimpleXML is required.

These checks invoke the real sitemap and SEO controllers with the real OpenCart URL builder and isolated catalog fixtures. No database, live website, image resize, email or external service is contacted.

Coverage includes sitemap indexing and 1000-product shards, canonical HTTPS URLs, contact and public blog content, XML escaping, original image paths, products without images, modification dates, disabled feeds, invalid input and public sitemap aliases with SEO enabled or disabled.

The public index is `/site-map.xml`, with `/site-map-pages.xml` and `/site-map-products-N.xml` children. The legacy `/sitemap.xml` index alias is also covered.
