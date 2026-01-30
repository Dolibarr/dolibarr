-- Index Adjustment line records table
-- Copyright (C) 2024 Anexum GmbH

CREATE TABLE llx_indexadjustment_line (
    rowid               INTEGER AUTO_INCREMENT PRIMARY KEY,
    fk_indexadjustment  INTEGER NOT NULL,
    fk_contrat          INTEGER NOT NULL,
    fk_contratdet       INTEGER NOT NULL,

    -- Product info
    product_ref         VARCHAR(128),
    product_label       VARCHAR(255),
    qty                 DOUBLE(24,8) DEFAULT 1,

    -- Price Before
    subprice_before     DOUBLE(24,8) NOT NULL,
    total_ht_before     DOUBLE(24,8) NOT NULL,
    tva_tx              DOUBLE(6,3) DEFAULT 0,         -- Original VAT rate for rollback

    -- Price After
    subprice_after      DOUBLE(24,8) NOT NULL,
    total_ht_after      DOUBLE(24,8) NOT NULL,

    -- Change
    price_diff_ht       DOUBLE(24,8) NOT NULL,

    -- Rollback
    rollback_executed   TINYINT DEFAULT 0,
    rollback_date       DATETIME,
    fk_user_rollback    INTEGER,

    INDEX idx_indexadjustment_line_fk_indexadjustment (fk_indexadjustment),
    INDEX idx_indexadjustment_line_fk_contrat (fk_contrat),
    INDEX idx_indexadjustment_line_fk_contratdet (fk_contratdet),
    CONSTRAINT fk_indexadjustment_line_parent FOREIGN KEY (fk_indexadjustment) REFERENCES llx_indexadjustment(rowid) ON DELETE CASCADE
) ENGINE=InnoDB;
