-- Keys for llx_indexadjustment (if not defined in main SQL)
-- Copyright (C) 2025 Florian Hödl <florian@hoedl.co>

-- Unique constraint on ref per entity (prevents duplicate adjustment references)
ALTER TABLE llx_indexadjustment ADD UNIQUE INDEX uk_indexadjustment_ref (ref, entity);

-- ALTER TABLE llx_indexadjustment ADD CONSTRAINT fk_indexadjustment_user_creat FOREIGN KEY (fk_user_creat) REFERENCES llx_user(rowid);
-- ALTER TABLE llx_indexadjustment ADD CONSTRAINT fk_indexadjustment_user_executed FOREIGN KEY (fk_user_executed) REFERENCES llx_user(rowid);
-- ALTER TABLE llx_indexadjustment ADD CONSTRAINT fk_indexadjustment_soc FOREIGN KEY (fk_soc) REFERENCES llx_societe(rowid);
