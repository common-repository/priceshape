UPDATE {{POST_META_TABLE}} as pm
INNER JOIN {{PRODUCTS_TABLE}} as pr
    ON pr.product_id = pm.post_id
INNER JOIN {{PRODUCT_UPDATES_TABLE}} as pru
    ON pr.id = pru.priceshape_product_id AND pru.id IN ({{PREPARED_IDS}})
SET pm.meta_value = pru.value
WHERE
    (pm.meta_key IN ('_price', '_sale_price') AND pru.field = '{{UPDATING_FIELD_PRICE}}') OR
    (pm.meta_key = '_wc_cog_cost' AND pru.field = '{{UPDATING_FIELD_COST}}');