-- ============================================================================
-- Copyright (C) 2004 Benoit Mortier       <benoit.mortier@opensides.be>
-- Copyright (C) 2010 Laurent Destailleur  <eldy@users.sourceforge.net>
--
-- This program is free software; you can redistribute it and/or modify
-- it under the terms of the GNU General Public License as published by
-- the Free Software Foundation; either version 3 of the License, or
-- (at your option) any later version.
--
-- This program is distributed in the hope that it will be useful,
-- but WITHOUT ANY WARRANTY; without even the implied warranty of
-- MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
-- GNU General Public License for more details.
--
-- You should have received a copy of the GNU General Public License
-- along with this program. If not, see <http://www.gnu.org/licenses/>.
--
-- ============================================================================

CREATE LANGUAGE plpgsql;


CREATE OR REPLACE FUNCTION UNIX_TIMESTAMP(TIMESTAMP WITHOUT TIME ZONE) RETURNS BIGINT LANGUAGE SQL IMMUTABLE STRICT AS 'SELECT EXTRACT(EPOCH FROM $1)::bigint;';

CREATE OR REPLACE FUNCTION UNIX_TIMESTAMP(TIMESTAMP WITH TIME ZONE) RETURNS BIGINT LANGUAGE SQL IMMUTABLE STRICT AS 'SELECT EXTRACT(EPOCH FROM $1)::bigint;';
 
CREATE OR REPLACE FUNCTION date_format(timestamp without time zone, text) RETURNS text AS $$ DECLARE i int := 1; temp text := ''; c text; n text; res text; BEGIN WHILE i <= pg_catalog.length($2) LOOP c := SUBSTRING ($2 FROM i FOR 1); IF c = '%' AND i != pg_catalog.length($2) THEN n := SUBSTRING ($2 FROM (i + 1) FOR 1); SELECT INTO res CASE WHEN n = 'a' THEN pg_catalog.to_char($1, 'Dy') WHEN n = 'b' THEN pg_catalog.to_char($1, 'Mon') WHEN n = 'c' THEN pg_catalog.to_char($1, 'FMMM') WHEN n = 'D' THEN pg_catalog.to_char($1, 'FMDDth') WHEN n = 'd' THEN pg_catalog.to_char($1, 'DD') WHEN n = 'e' THEN pg_catalog.to_char($1, 'FMDD') WHEN n = 'f' THEN pg_catalog.to_char($1, 'US') WHEN n = 'H' THEN pg_catalog.to_char($1, 'HH24') WHEN n = 'h' THEN pg_catalog.to_char($1, 'HH12') WHEN n = 'I' THEN pg_catalog.to_char($1, 'HH12') WHEN n = 'i' THEN pg_catalog.to_char($1, 'MI') WHEN n = 'j' THEN pg_catalog.to_char($1, 'DDD') WHEN n = 'k' THEN pg_catalog.to_char($1, 'FMHH24') WHEN n = 'l' THEN pg_catalog.to_char($1, 'FMHH12') WHEN n = 'M' THEN pg_catalog.to_char($1, 'FMMonth') WHEN n = 'm' THEN pg_catalog.to_char($1, 'MM') WHEN n = 'p' THEN pg_catalog.to_char($1, 'AM') WHEN n = 'r' THEN pg_catalog.to_char($1, 'HH12:MI:SS AM') WHEN n = 'S' THEN pg_catalog.to_char($1, 'SS') WHEN n = 's' THEN pg_catalog.to_char($1, 'SS') WHEN n = 'T' THEN pg_catalog.to_char($1, 'HH24:MI:SS') WHEN n = 'U' THEN pg_catalog.to_char($1, '?') WHEN n = 'u' THEN pg_catalog.to_char($1, '?') WHEN n = 'V' THEN pg_catalog.to_char($1, '?') WHEN n = 'v' THEN pg_catalog.to_char($1, '?') WHEN n = 'W' THEN pg_catalog.to_char($1, 'FMDay') WHEN n = 'w' THEN EXTRACT(DOW FROM $1)::text WHEN n = 'X' THEN pg_catalog.to_char($1, '?') WHEN n = 'x' THEN pg_catalog.to_char($1, '?') WHEN n = 'Y' THEN pg_catalog.to_char($1, 'YYYY') WHEN n = 'y' THEN pg_catalog.to_char($1, 'YY') WHEN n = '%' THEN pg_catalog.to_char($1, '%') ELSE NULL END; temp := temp operator(pg_catalog.||) res; i := i + 2; ELSE temp = temp operator(pg_catalog.||) c; i := i + 1; END IF; END LOOP; RETURN temp; END $$ IMMUTABLE STRICT LANGUAGE plpgsql;


CREATE OR REPLACE FUNCTION YEAR(TIMESTAMP without TIME ZONE) RETURNS INTEGER AS $$ SELECT EXTRACT(YEAR FROM $1)::INTEGER; $$ LANGUAGE SQL IMMUTABLE;

CREATE OR REPLACE FUNCTION YEAR(TIMESTAMP WITH TIME ZONE) RETURNS INTEGER AS $$ SELECT EXTRACT(YEAR FROM $1)::INTEGER; $$ LANGUAGE SQL STABLE;

CREATE OR REPLACE FUNCTION YEAR(DATE) RETURNS INTEGER AS $$ SELECT EXTRACT(YEAR FROM $1)::INTEGER; $$ LANGUAGE SQL IMMUTABLE;


CREATE OR REPLACE FUNCTION MONTH(TIMESTAMP without TIME ZONE) RETURNS INTEGER AS $$ SELECT EXTRACT(MONTH FROM $1)::INTEGER; $$ LANGUAGE SQL IMMUTABLE;

CREATE OR REPLACE FUNCTION MONTH(TIMESTAMP WITH TIME ZONE) RETURNS INTEGER AS $$ SELECT EXTRACT(MONTH FROM $1)::INTEGER; $$ LANGUAGE SQL STABLE;

CREATE OR REPLACE FUNCTION MONTH(DATE) RETURNS INTEGER AS $$ SELECT EXTRACT(MONTH FROM $1)::INTEGER; $$ LANGUAGE SQL IMMUTABLE;


CREATE OR REPLACE FUNCTION DAY(TIMESTAMP without TIME ZONE) RETURNS INTEGER AS $$ SELECT EXTRACT(DAY FROM $1)::INTEGER; $$ LANGUAGE SQL IMMUTABLE;

CREATE OR REPLACE FUNCTION DAY(TIMESTAMP WITH TIME ZONE) RETURNS INTEGER AS $$ SELECT EXTRACT(DAY FROM $1)::INTEGER; $$ LANGUAGE SQL STABLE;

CREATE OR REPLACE FUNCTION DAY(DATE) RETURNS INTEGER AS $$ SELECT EXTRACT(DAY FROM $1)::INTEGER; $$ LANGUAGE SQL IMMUTABLE;

CREATE OR REPLACE FUNCTION dol_util_rebuild_sequences() RETURNS integer as $body$ DECLARE sequencedefs RECORD; c integer ; BEGIN FOR sequencedefs IN SELECT DISTINCT constraint_column_usage.table_name as tablename, constraint_column_usage.table_name as tablename, constraint_column_usage.column_name as columnname, replace(replace(columns.column_default,'''::regclass)',''),'nextval(''','') as sequencename from information_schema.constraint_column_usage, information_schema.columns, information_schema.sequences where constraint_column_usage.table_schema ='public' AND columns.table_schema = 'public' AND columns.table_name=constraint_column_usage.table_name AND constraint_column_usage.column_name IN ('rowid','id') AND constraint_column_usage.column_name = columns.column_name AND columns.column_default is not null AND replace(replace(columns.column_default,'''::regclass)',''),'nextval(''','')=sequence_name LOOP EXECUTE 'select max('||sequencedefs.columnname||') from ' || sequencedefs.tablename INTO c; IF c is null THEN c = 0; END IF; IF c is not null THEN c = c+ 1; END IF; EXECUTE 'alter sequence ' || sequencedefs.sequencename ||' restart  with ' || c; END LOOP; RETURN 1; END; $body$ LANGUAGE plpgsql;

CREATE OR REPLACE FUNCTION dol_util_triggerall(DoEnable boolean) RETURNS integer AS $BODY$ DECLARE mytables RECORD; BEGIN FOR mytables IN SELECT relname FROM pg_class WHERE relhastriggers IS TRUE AND relkind = 'r' AND NOT relname LIKE 'pg_%' LOOP IF DoEnable THEN EXECUTE 'ALTER TABLE ' || mytables.relname || ' ENABLE TRIGGER ALL'; ELSE  EXECUTE 'ALTER TABLE ' || mytables.relname || ' DISABLE TRIGGER ALL'; END IF; END LOOP; RETURN 1; END; $BODY$ LANGUAGE plpgsql;


-- Add triggers for timestamp fields
CREATE OR REPLACE FUNCTION update_modified_column_tms()	RETURNS TRIGGER AS $$ BEGIN NEW.tms = now(); RETURN NEW; END; $$ LANGUAGE plpgsql;
CREATE TRIGGER update_customer_modtime BEFORE UPDATE ON llx_accounting_account FOR EACH ROW EXECUTE PROCEDURE update_modified_column_tms();
CREATE TRIGGER update_customer_modtime BEFORE UPDATE ON llx_accounting_fiscalyear FOR EACH ROW EXECUTE PROCEDURE update_modified_column_tms();
CREATE TRIGGER update_customer_modtime BEFORE UPDATE ON llx_actioncomm FOR EACH ROW EXECUTE PROCEDURE update_modified_column_tms();
CREATE TRIGGER update_customer_modtime BEFORE UPDATE ON llx_actioncomm_extrafields FOR EACH ROW EXECUTE PROCEDURE update_modified_column_tms();
CREATE TRIGGER update_customer_modtime BEFORE UPDATE ON llx_adherent FOR EACH ROW EXECUTE PROCEDURE update_modified_column_tms();
CREATE TRIGGER update_customer_modtime BEFORE UPDATE ON llx_adherent_extrafields FOR EACH ROW EXECUTE PROCEDURE update_modified_column_tms();
CREATE TRIGGER update_customer_modtime BEFORE UPDATE ON llx_adherent_type FOR EACH ROW EXECUTE PROCEDURE update_modified_column_tms();
CREATE TRIGGER update_customer_modtime BEFORE UPDATE ON llx_adherent_type_extrafields FOR EACH ROW EXECUTE PROCEDURE update_modified_column_tms();
CREATE TRIGGER update_customer_modtime BEFORE UPDATE ON llx_bank FOR EACH ROW EXECUTE PROCEDURE update_modified_column_tms();
CREATE TRIGGER update_customer_modtime BEFORE UPDATE ON llx_bank_account FOR EACH ROW EXECUTE PROCEDURE update_modified_column_tms();
CREATE TRIGGER update_customer_modtime BEFORE UPDATE ON llx_bank_account_extrafields FOR EACH ROW EXECUTE PROCEDURE update_modified_column_tms();
CREATE TRIGGER update_customer_modtime BEFORE UPDATE ON llx_bordereau_cheque FOR EACH ROW EXECUTE PROCEDURE update_modified_column_tms();
CREATE TRIGGER update_customer_modtime BEFORE UPDATE ON llx_boxes_def FOR EACH ROW EXECUTE PROCEDURE update_modified_column_tms();
CREATE TRIGGER update_customer_modtime BEFORE UPDATE ON llx_c_email_templates FOR EACH ROW EXECUTE PROCEDURE update_modified_column_tms();
CREATE TRIGGER update_customer_modtime BEFORE UPDATE ON llx_c_field_list FOR EACH ROW EXECUTE PROCEDURE update_modified_column_tms();
CREATE TRIGGER update_customer_modtime BEFORE UPDATE ON llx_c_shipment_mode FOR EACH ROW EXECUTE PROCEDURE update_modified_column_tms();
CREATE TRIGGER update_customer_modtime BEFORE UPDATE ON llx_categories_extrafields FOR EACH ROW EXECUTE PROCEDURE update_modified_column_tms();
CREATE TRIGGER update_customer_modtime BEFORE UPDATE ON llx_chargesociales FOR EACH ROW EXECUTE PROCEDURE update_modified_column_tms();
CREATE TRIGGER update_customer_modtime BEFORE UPDATE ON llx_commande FOR EACH ROW EXECUTE PROCEDURE update_modified_column_tms();
CREATE TRIGGER update_customer_modtime BEFORE UPDATE ON llx_commande_extrafields FOR EACH ROW EXECUTE PROCEDURE update_modified_column_tms();
CREATE TRIGGER update_customer_modtime BEFORE UPDATE ON llx_commande_fournisseur FOR EACH ROW EXECUTE PROCEDURE update_modified_column_tms();
CREATE TRIGGER update_customer_modtime BEFORE UPDATE ON llx_commande_fournisseur_dispatch FOR EACH ROW EXECUTE PROCEDURE update_modified_column_tms();
CREATE TRIGGER update_customer_modtime BEFORE UPDATE ON llx_commande_fournisseur_extrafields FOR EACH ROW EXECUTE PROCEDURE update_modified_column_tms();
CREATE TRIGGER update_customer_modtime BEFORE UPDATE ON llx_commande_fournisseur_log FOR EACH ROW EXECUTE PROCEDURE update_modified_column_tms();
CREATE TRIGGER update_customer_modtime BEFORE UPDATE ON llx_commande_fournisseurdet_extrafields FOR EACH ROW EXECUTE PROCEDURE update_modified_column_tms();
CREATE TRIGGER update_customer_modtime BEFORE UPDATE ON llx_commandedet_extrafields FOR EACH ROW EXECUTE PROCEDURE update_modified_column_tms();
CREATE TRIGGER update_customer_modtime BEFORE UPDATE ON llx_const FOR EACH ROW EXECUTE PROCEDURE update_modified_column_tms();
CREATE TRIGGER update_customer_modtime BEFORE UPDATE ON llx_contrat FOR EACH ROW EXECUTE PROCEDURE update_modified_column_tms();
CREATE TRIGGER update_customer_modtime BEFORE UPDATE ON llx_contrat_extrafields FOR EACH ROW EXECUTE PROCEDURE update_modified_column_tms();
CREATE TRIGGER update_customer_modtime BEFORE UPDATE ON llx_contratdet FOR EACH ROW EXECUTE PROCEDURE update_modified_column_tms();
CREATE TRIGGER update_customer_modtime BEFORE UPDATE ON llx_contratdet_extrafields FOR EACH ROW EXECUTE PROCEDURE update_modified_column_tms();
CREATE TRIGGER update_customer_modtime BEFORE UPDATE ON llx_contratdet_log FOR EACH ROW EXECUTE PROCEDURE update_modified_column_tms();
CREATE TRIGGER update_customer_modtime BEFORE UPDATE ON llx_subscription FOR EACH ROW EXECUTE PROCEDURE update_modified_column_tms();
CREATE TRIGGER update_customer_modtime BEFORE UPDATE ON llx_cronjob FOR EACH ROW EXECUTE PROCEDURE update_modified_column_tms();
CREATE TRIGGER update_customer_modtime BEFORE UPDATE ON llx_deplacement FOR EACH ROW EXECUTE PROCEDURE update_modified_column_tms();
CREATE TRIGGER update_customer_modtime BEFORE UPDATE ON llx_don FOR EACH ROW EXECUTE PROCEDURE update_modified_column_tms();
CREATE TRIGGER update_customer_modtime BEFORE UPDATE ON llx_don_extrafields FOR EACH ROW EXECUTE PROCEDURE update_modified_column_tms();
CREATE TRIGGER update_customer_modtime BEFORE UPDATE ON llx_element_resources FOR EACH ROW EXECUTE PROCEDURE update_modified_column_tms();
CREATE TRIGGER update_customer_modtime BEFORE UPDATE ON llx_entrepot FOR EACH ROW EXECUTE PROCEDURE update_modified_column_tms();
CREATE TRIGGER update_customer_modtime BEFORE UPDATE ON llx_events FOR EACH ROW EXECUTE PROCEDURE update_modified_column_tms();
CREATE TRIGGER update_customer_modtime BEFORE UPDATE ON llx_expedition FOR EACH ROW EXECUTE PROCEDURE update_modified_column_tms();
CREATE TRIGGER update_customer_modtime BEFORE UPDATE ON llx_expensereport FOR EACH ROW EXECUTE PROCEDURE update_modified_column_tms();
CREATE TRIGGER update_customer_modtime BEFORE UPDATE ON llx_extrafields FOR EACH ROW EXECUTE PROCEDURE update_modified_column_tms();
CREATE TRIGGER update_customer_modtime BEFORE UPDATE ON llx_facture FOR EACH ROW EXECUTE PROCEDURE update_modified_column_tms();
CREATE TRIGGER update_customer_modtime BEFORE UPDATE ON llx_facture_extrafields FOR EACH ROW EXECUTE PROCEDURE update_modified_column_tms();
CREATE TRIGGER update_customer_modtime BEFORE UPDATE ON llx_facture_fourn FOR EACH ROW EXECUTE PROCEDURE update_modified_column_tms();
CREATE TRIGGER update_customer_modtime BEFORE UPDATE ON llx_facture_fourn_det_extrafields FOR EACH ROW EXECUTE PROCEDURE update_modified_column_tms();
CREATE TRIGGER update_customer_modtime BEFORE UPDATE ON llx_facture_fourn_extrafields FOR EACH ROW EXECUTE PROCEDURE update_modified_column_tms();
CREATE TRIGGER update_customer_modtime BEFORE UPDATE ON llx_facturedet_extrafields FOR EACH ROW EXECUTE PROCEDURE update_modified_column_tms();
CREATE TRIGGER update_customer_modtime BEFORE UPDATE ON llx_fichinter FOR EACH ROW EXECUTE PROCEDURE update_modified_column_tms();
CREATE TRIGGER update_customer_modtime BEFORE UPDATE ON llx_fichinter_extrafields FOR EACH ROW EXECUTE PROCEDURE update_modified_column_tms();
CREATE TRIGGER update_customer_modtime BEFORE UPDATE ON llx_fichinterdet_extrafields FOR EACH ROW EXECUTE PROCEDURE update_modified_column_tms();
CREATE TRIGGER update_customer_modtime BEFORE UPDATE ON llx_livraison FOR EACH ROW EXECUTE PROCEDURE update_modified_column_tms();
CREATE TRIGGER update_customer_modtime BEFORE UPDATE ON llx_loan FOR EACH ROW EXECUTE PROCEDURE update_modified_column_tms();
CREATE TRIGGER update_customer_modtime BEFORE UPDATE ON llx_localtax FOR EACH ROW EXECUTE PROCEDURE update_modified_column_tms();
CREATE TRIGGER update_customer_modtime BEFORE UPDATE ON llx_menu FOR EACH ROW EXECUTE PROCEDURE update_modified_column_tms();
CREATE TRIGGER update_customer_modtime BEFORE UPDATE ON llx_notify FOR EACH ROW EXECUTE PROCEDURE update_modified_column_tms();
CREATE TRIGGER update_customer_modtime BEFORE UPDATE ON llx_notify_def FOR EACH ROW EXECUTE PROCEDURE update_modified_column_tms();
CREATE TRIGGER update_customer_modtime BEFORE UPDATE ON llx_opensurvey_comments FOR EACH ROW EXECUTE PROCEDURE update_modified_column_tms();
CREATE TRIGGER update_customer_modtime BEFORE UPDATE ON llx_opensurvey_sondage FOR EACH ROW EXECUTE PROCEDURE update_modified_column_tms();
CREATE TRIGGER update_customer_modtime BEFORE UPDATE ON llx_opensurvey_user_studs FOR EACH ROW EXECUTE PROCEDURE update_modified_column_tms();
CREATE TRIGGER update_customer_modtime BEFORE UPDATE ON llx_paiement FOR EACH ROW EXECUTE PROCEDURE update_modified_column_tms();
CREATE TRIGGER update_customer_modtime BEFORE UPDATE ON llx_paiementcharge FOR EACH ROW EXECUTE PROCEDURE update_modified_column_tms();
CREATE TRIGGER update_customer_modtime BEFORE UPDATE ON llx_paiementfourn FOR EACH ROW EXECUTE PROCEDURE update_modified_column_tms();
CREATE TRIGGER update_customer_modtime BEFORE UPDATE ON llx_payment_donation FOR EACH ROW EXECUTE PROCEDURE update_modified_column_tms();
CREATE TRIGGER update_customer_modtime BEFORE UPDATE ON llx_payment_expensereport FOR EACH ROW EXECUTE PROCEDURE update_modified_column_tms();
CREATE TRIGGER update_customer_modtime BEFORE UPDATE ON llx_payment_loan FOR EACH ROW EXECUTE PROCEDURE update_modified_column_tms();
CREATE TRIGGER update_customer_modtime BEFORE UPDATE ON llx_payment_salary FOR EACH ROW EXECUTE PROCEDURE update_modified_column_tms();
CREATE TRIGGER update_customer_modtime BEFORE UPDATE ON llx_printing FOR EACH ROW EXECUTE PROCEDURE update_modified_column_tms();
CREATE TRIGGER update_customer_modtime BEFORE UPDATE ON llx_product FOR EACH ROW EXECUTE PROCEDURE update_modified_column_tms();
CREATE TRIGGER update_customer_modtime BEFORE UPDATE ON llx_product_batch FOR EACH ROW EXECUTE PROCEDURE update_modified_column_tms();
CREATE TRIGGER update_customer_modtime BEFORE UPDATE ON llx_product_customer_price FOR EACH ROW EXECUTE PROCEDURE update_modified_column_tms();
CREATE TRIGGER update_customer_modtime BEFORE UPDATE ON llx_product_extrafields FOR EACH ROW EXECUTE PROCEDURE update_modified_column_tms();
CREATE TRIGGER update_customer_modtime BEFORE UPDATE ON llx_product_fournisseur_price FOR EACH ROW EXECUTE PROCEDURE update_modified_column_tms();
CREATE TRIGGER update_customer_modtime BEFORE UPDATE ON llx_product_price FOR EACH ROW EXECUTE PROCEDURE update_modified_column_tms();
CREATE TRIGGER update_customer_modtime BEFORE UPDATE ON llx_product_stock FOR EACH ROW EXECUTE PROCEDURE update_modified_column_tms();
CREATE TRIGGER update_customer_modtime BEFORE UPDATE ON llx_projet FOR EACH ROW EXECUTE PROCEDURE update_modified_column_tms();
CREATE TRIGGER update_customer_modtime BEFORE UPDATE ON llx_projet_extrafields FOR EACH ROW EXECUTE PROCEDURE update_modified_column_tms();
CREATE TRIGGER update_customer_modtime BEFORE UPDATE ON llx_projet_task FOR EACH ROW EXECUTE PROCEDURE update_modified_column_tms();
CREATE TRIGGER update_customer_modtime BEFORE UPDATE ON llx_projet_task_extrafields FOR EACH ROW EXECUTE PROCEDURE update_modified_column_tms();
CREATE TRIGGER update_customer_modtime BEFORE UPDATE ON llx_propal FOR EACH ROW EXECUTE PROCEDURE update_modified_column_tms();
CREATE TRIGGER update_customer_modtime BEFORE UPDATE ON llx_propal_extrafields FOR EACH ROW EXECUTE PROCEDURE update_modified_column_tms();
CREATE TRIGGER update_customer_modtime BEFORE UPDATE ON llx_propal_merge_pdf_product FOR EACH ROW EXECUTE PROCEDURE update_modified_column_tms();
CREATE TRIGGER update_customer_modtime BEFORE UPDATE ON llx_propaldet_extrafields FOR EACH ROW EXECUTE PROCEDURE update_modified_column_tms();
CREATE TRIGGER update_customer_modtime BEFORE UPDATE ON llx_resource FOR EACH ROW EXECUTE PROCEDURE update_modified_column_tms();
CREATE TRIGGER update_customer_modtime BEFORE UPDATE ON llx_societe FOR EACH ROW EXECUTE PROCEDURE update_modified_column_tms();
CREATE TRIGGER update_customer_modtime BEFORE UPDATE ON llx_societe_address FOR EACH ROW EXECUTE PROCEDURE update_modified_column_tms();
CREATE TRIGGER update_customer_modtime BEFORE UPDATE ON llx_societe_extrafields FOR EACH ROW EXECUTE PROCEDURE update_modified_column_tms();
CREATE TRIGGER update_customer_modtime BEFORE UPDATE ON llx_societe_prices FOR EACH ROW EXECUTE PROCEDURE update_modified_column_tms();
CREATE TRIGGER update_customer_modtime BEFORE UPDATE ON llx_societe_remise FOR EACH ROW EXECUTE PROCEDURE update_modified_column_tms();
CREATE TRIGGER update_customer_modtime BEFORE UPDATE ON llx_societe_rib FOR EACH ROW EXECUTE PROCEDURE update_modified_column_tms();
CREATE TRIGGER update_customer_modtime BEFORE UPDATE ON llx_socpeople FOR EACH ROW EXECUTE PROCEDURE update_modified_column_tms();
CREATE TRIGGER update_customer_modtime BEFORE UPDATE ON llx_socpeople_extrafields FOR EACH ROW EXECUTE PROCEDURE update_modified_column_tms();
CREATE TRIGGER update_customer_modtime BEFORE UPDATE ON llx_stock_mouvement FOR EACH ROW EXECUTE PROCEDURE update_modified_column_tms();
CREATE TRIGGER update_customer_modtime BEFORE UPDATE ON llx_supplier_proposal FOR EACH ROW EXECUTE PROCEDURE update_modified_column_tms();
CREATE TRIGGER update_customer_modtime BEFORE UPDATE ON llx_supplier_proposal_extrafields FOR EACH ROW EXECUTE PROCEDURE update_modified_column_tms();
CREATE TRIGGER update_customer_modtime BEFORE UPDATE ON llx_supplier_proposaldet_extrafields FOR EACH ROW EXECUTE PROCEDURE update_modified_column_tms();
CREATE TRIGGER update_customer_modtime BEFORE UPDATE ON llx_tva FOR EACH ROW EXECUTE PROCEDURE update_modified_column_tms();
CREATE TRIGGER update_customer_modtime BEFORE UPDATE ON llx_user FOR EACH ROW EXECUTE PROCEDURE update_modified_column_tms();
CREATE TRIGGER update_customer_modtime BEFORE UPDATE ON llx_user_extrafields FOR EACH ROW EXECUTE PROCEDURE update_modified_column_tms();
CREATE TRIGGER update_customer_modtime BEFORE UPDATE ON llx_usergroup FOR EACH ROW EXECUTE PROCEDURE update_modified_column_tms();
CREATE TRIGGER update_customer_modtime BEFORE UPDATE ON llx_usergroup_extrafields FOR EACH ROW EXECUTE PROCEDURE update_modified_column_tms();

CREATE OR REPLACE FUNCTION update_modified_column_date_m()	RETURNS TRIGGER AS $$ BEGIN NEW.date_m = now(); RETURN NEW; END; $$ LANGUAGE plpgsql;
CREATE TRIGGER update_customer_modtime BEFORE UPDATE ON llx_ecm_directories FOR EACH ROW EXECUTE PROCEDURE update_modified_column_date_m();

CREATE OR REPLACE FUNCTION update_modified_column_date_price()	RETURNS TRIGGER AS $$ BEGIN NEW.date_price = now(); RETURN NEW; END; $$ LANGUAGE plpgsql;
CREATE TRIGGER update_customer_modtime BEFORE UPDATE ON llx_product_price_by_qty FOR EACH ROW EXECUTE PROCEDURE update_modified_column_date_price();
