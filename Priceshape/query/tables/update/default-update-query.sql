UPDATE {{POST_META_TABLE}} as pm
LEFT JOIN {{POST_META_TABLE}} as pmsp
   ON pm.post_id = pmsp.post_id AND
   pmsp.meta_key = '_sale_price'
INNER JOIN {{PRODUCTS_TABLE}} as pr
   ON pr.product_id = pm.post_id
INNER JOIN {{PRODUCT_UPDATES_TABLE}} as pru
   ON pr.id = pru.priceshape_product_id AND pru.id IN ({{PREPARED_IDS}})
SET pm.meta_value = CASE
   WHEN pm.meta_key = '_regular_price' THEN IF(pmsp.meta_value IS NULL OR pmsp.meta_value = '', pru.value, pm.meta_value)
   WHEN pm.meta_key = '_sale_price' THEN IF(pmsp.meta_value IS NOT NULL AND pmsp.meta_value <> '', pru.value, pmsp.meta_value)
   ELSE pru.value
END
WHERE (pm.meta_key IN ('_price', '_sale_price', '_regular_price') AND pru.field = '{{UPDATING_FIELD_PRICE}}') OR (pm.meta_key = '_wc_cog_cost' AND pru.field = '{{UPDATING_FIELD_COST}}');
