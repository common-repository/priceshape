 CREATE TRIGGER `PRS_POSTMETA_AFTER_UPDATE`
            AFTER UPDATE ON `{{POST_META_TABLE}}`
            FOR EACH ROW 
                BEGIN
                    DECLARE PARENT_ID INT DEFAULT 0;
                    IF(NEW.meta_key = '_price' AND NEW.meta_value <> OLD.meta_value) THEN
                        SELECT post_parent INTO PARENT_ID FROM `{{POSTS_TABLE}}` WHERE id = NEW.post_id;
                        IF(PARENT_ID = 0) THEN
                            SET PARENT_ID = NEW.post_id;
                        ELSE
                            UPDATE `{{WC_PRODUCT_META_LOOKUP}}`
                            SET min_price = NEW.meta_value,
                                max_price = NEW.meta_value
                            WHERE product_id = NEW.post_id;
                        END IF;
                        UPDATE `{{WC_PRODUCT_META_LOOKUP}}` as pml
                            INNER JOIN (
                                SELECT 
                                    post_parent as id, 
                                    MAX(meta_value + 0) as max_price, 
                                    MIN(meta_value + 0) as min_price 
                                FROM `{{POSTS_TABLE}}` as p
                                INNER JOIN `{{POST_META_TABLE}}` as pm
                                    ON pm.post_id = p.id
                                    AND pm.meta_key = '_price' 
                                WHERE p.post_parent = PARENT_ID 
                                GROUP BY post_parent
                            ) as p 
                            ON pml.product_id = p.id 
                            SET pml.min_price = p.min_price, 
                                pml.max_price = p.max_price;
                        DELETE FROM `{{OPTIONS_TABLE}}`
                            WHERE `option_name` IN (
                                CONCAT('_transient_wc_var_prices_', NEW.post_id),
                                CONCAT('_transient_wc_var_prices_', PARENT_ID)
                            ) OR `option_name` IN (
                                CONCAT('_transient_timeout_wc_var_prices_', NEW.post_id),
                                CONCAT('_transient_timeout_wc_var_prices_', PARENT_ID)
                            );
                    END IF;
                    
                    IF(
                        (NEW.meta_key = '_price' AND NEW.meta_value <> OLD.meta_value) OR
                        (NEW.meta_key = '_regular_price' AND NEW.meta_value <> OLD.meta_value) OR
                        (NEW.meta_key = '_sale_price' AND NEW.meta_value <> OLD.meta_value) OR
                        (NEW.meta_key = '_wc_cog_cost' AND NEW.meta_value <> OLD.meta_value)
                    ) THEN
                        INSERT INTO `{{HISTORY_TABLE}}` (
                            `post_id`, `old_value`, `new_value`, `meta_key`, `origin`
                        ) VALUES (
                            NEW.post_id, OLD.meta_value, NEW.meta_value, NEW.meta_key, IF(@is_prs = 1, '{{ORIGIN_PRICESHAPE}}', '{{ORIGIN_WOOCOMMERCE}}')
                        );
                    END IF;
                END;

