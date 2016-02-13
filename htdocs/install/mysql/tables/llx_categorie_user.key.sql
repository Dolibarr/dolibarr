ALTER TABLE llx_categorie_user ADD INDEX  `idx_categorie_user_fk_categorie` (`fk_categorie`);
ALTER TABLE llx_categorie_user ADD INDEX  `idx_categorie_user_fk_user` (`fk_user`);
