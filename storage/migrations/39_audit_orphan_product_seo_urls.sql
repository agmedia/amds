-- Read-only SEO integrity audit. Change the oc_ prefix if the installation uses another prefix.
-- This migration intentionally does not delete data. Review its result before any cleanup.

-- Product SEO URLs whose referenced product no longer exists.
-- Restricting query to the exact product_id=<number> form leaves all other SEO entity types untouched.
SELECT
    su.seo_url_id,
    su.store_id,
    su.language_id,
    su.query,
    su.keyword
FROM oc_seo_url AS su
LEFT JOIN oc_product AS p
    ON p.product_id = CAST(SUBSTRING(su.query, CHAR_LENGTH('product_id=') + 1) AS UNSIGNED)
WHERE su.query REGEXP '^product_id=[0-9]+$'
  AND p.product_id IS NULL
ORDER BY su.store_id, su.language_id, su.keyword, su.seo_url_id;

-- Duplicate SEO keywords in the same store and language.
-- Includes every conflicting query so product URLs can be distinguished from other SEO entity types.
SELECT
    su.store_id,
    su.language_id,
    su.keyword,
    su.seo_url_id,
    su.query
FROM oc_seo_url AS su
INNER JOIN (
    SELECT store_id, language_id, keyword
    FROM oc_seo_url
    WHERE keyword <> ''
    GROUP BY store_id, language_id, keyword
    HAVING COUNT(*) > 1
) AS duplicates
    ON duplicates.store_id = su.store_id
   AND duplicates.language_id = su.language_id
   AND duplicates.keyword = su.keyword
WHERE su.keyword <> ''
ORDER BY su.store_id, su.language_id, su.keyword, su.seo_url_id;
