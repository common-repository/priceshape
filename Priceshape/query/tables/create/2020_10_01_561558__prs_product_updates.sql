CREATE TABLE {{PRODUCT_UPDATES_TABLE}}
(
  id                    BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  priceshape_product_id BIGINT(20) UNSIGNED NOT NULL,
  field                 ENUM( {{UPDATING_FIELDS_LIST}} ) NOT NULL,
  value                 VARCHAR(255) NOT NULL,
  created_at            TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY {{PRODUCT_UPDATES_UNIQUE_TABLE}} (priceshape_product_id, field),
  CONSTRAINT {{PRODUCT_UPDATES_CONSTRAINT_TABLE}} FOREIGN KEY (priceshape_product_id) REFERENCES {{PRODUCTS_TABLE}}(id) ON DELETE CASCADE
  );