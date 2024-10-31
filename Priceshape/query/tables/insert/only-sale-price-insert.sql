INSERT INTO {{POST_META_TABLE}} (post_id, meta_key, meta_value)
SELECT pr.product_id, '_sale_price', pru.value
FROM {{PRODUCT_UPDATES_TABLE}} as pru
INNER JOIN {{PRODUCTS_TABLE}} as pr
    ON pr.id = pru.priceshape_product_id
LEFT JOIN {{POST_META_TABLE}} as pm
    ON pm.post_id = pr.product_id AND pm.meta_key = '_sale_price'
WHERE pru.id IN ({{PREPARED_IDS}}) AND pm.meta_id IS NULL;