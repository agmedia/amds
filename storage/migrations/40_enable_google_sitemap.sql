-- Enable the Google Sitemap feed for the main AMDS storefront.
-- Run as part of the Git release after deploying the sitemap code.
-- Change oc_ if the installation uses a different table prefix.

UPDATE oc_setting
SET code = 'feed_google_sitemap', value = '1', serialized = 0
WHERE store_id = 0 AND `key` = 'feed_google_sitemap_status';

INSERT INTO oc_setting (store_id, code, `key`, value, serialized)
SELECT 0, 'feed_google_sitemap', 'feed_google_sitemap_status', '1', 0
WHERE NOT EXISTS (
    SELECT 1 FROM oc_setting
    WHERE store_id = 0 AND `key` = 'feed_google_sitemap_status'
);

INSERT INTO oc_extension (type, code)
SELECT 'feed', 'google_sitemap'
WHERE NOT EXISTS (
    SELECT 1 FROM oc_extension WHERE type = 'feed' AND code = 'google_sitemap'
);
