SET @hr_language_id := (
    SELECT language_id
    FROM oc_language
    WHERE code = 'hr-hr'
    LIMIT 1
);

DELETE FROM oc_seo_url
WHERE query = 'account/return/add'
   OR keyword = 'obrazac-za-povrat';

INSERT INTO oc_seo_url (store_id, language_id, query, keyword)
VALUES (0, COALESCE(@hr_language_id, 2), 'account/return/add', 'obrazac-za-povrat');

UPDATE oc_setting
SET value = '1'
WHERE store_id = 0
  AND code = 'config'
  AND `key` = 'config_seo_url';

UPDATE oc_return_reason
SET name = 'Neispravno pri isporuci'
WHERE return_reason_id = 1
  AND language_id = COALESCE(@hr_language_id, 2);

UPDATE oc_return_reason
SET name = 'Primljen pogrešan artikl'
WHERE return_reason_id = 2
  AND language_id = COALESCE(@hr_language_id, 2);

UPDATE oc_return_reason
SET name = 'Greška u narudžbi'
WHERE return_reason_id = 3
  AND language_id = COALESCE(@hr_language_id, 2);

UPDATE oc_return_reason
SET name = 'Neispravan proizvod, molimo navedite detalje'
WHERE return_reason_id = 4
  AND language_id = COALESCE(@hr_language_id, 2);

UPDATE oc_return_reason
SET name = 'Ostalo, molimo navedite detalje'
WHERE return_reason_id = 5
  AND language_id = COALESCE(@hr_language_id, 2);
