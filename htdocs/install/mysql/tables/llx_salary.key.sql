-- ============================================================================
-- Copyright (C) 2011-2018 Alexandre Spangaro   <aspangaro@open-dsi.fr>
-- Copyright (C) 2021      Gauthier VERDOL      <gauthier.verdol@atm-consulting.fr>
-- Copyright (C) 2023      Laurent Destailleur  <eldy@users.sourceforge.net>
--
-- This program is free software; you can redistribute it and/or modify
-- it under the terms of the GNU General Public License as published by
-- the Free Software Foundation; either version 3 of the License, or
-- (at your option) any later version.
--
-- This program is distributed in the hope that it will be useful,
-- but WITHOUT ANY WARRANTY; without even the implied warranty of
-- MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
-- ============================================================================


ALTER TABLE llx_salary ADD UNIQUE INDEX uk_salary_ref (ref, entity);

ALTER TABLE llx_salary ADD INDEX idx_salary_fk_user (fk_user);
ALTER TABLE llx_salary ADD INDEX idx_salary_fk_user_author (fk_user_author);
ALTER TABLE llx_salary ADD INDEX idx_salary_fk_user_modif (fk_user_modif);
ALTER TABLE llx_salary ADD INDEX idx_salary_fk_projet (fk_projet);
ALTER TABLE llx_salary ADD INDEX idx_salary_fk_bank (fk_bank);
ALTER TABLE llx_salary ADD INDEX idx_salary_fk_account (fk_account);
ALTER TABLE llx_salary ADD INDEX idx_salary_fk_typepayment (fk_typepayment);
ALTER TABLE llx_salary ADD INDEX idx_salary_entity (entity);

ALTER TABLE llx_salary ADD CONSTRAINT fk_salary_fk_user
    FOREIGN KEY (fk_user) REFERENCES llx_user (rowid);

ALTER TABLE llx_salary ADD CONSTRAINT fk_salary_fk_user_author
    FOREIGN KEY (fk_user_author) REFERENCES llx_user (rowid);

ALTER TABLE llx_salary ADD CONSTRAINT fk_salary_fk_user_modif
    FOREIGN KEY (fk_user_modif) REFERENCES llx_user (rowid);

ALTER TABLE llx_salary ADD CONSTRAINT fk_salary_fk_projet
    FOREIGN KEY (fk_projet) REFERENCES llx_projet (rowid);

ALTER TABLE llx_salary ADD CONSTRAINT fk_salary_fk_bank
    FOREIGN KEY (fk_bank) REFERENCES llx_bank (rowid);

ALTER TABLE llx_salary ADD CONSTRAINT fk_salary_fk_account
    FOREIGN KEY (fk_account) REFERENCES llx_bank_account (rowid);

ALTER TABLE llx_salary ADD CONSTRAINT fk_salary_fk_typepayment
    FOREIGN KEY (fk_typepayment) REFERENCES llx_c_paiement (id);
