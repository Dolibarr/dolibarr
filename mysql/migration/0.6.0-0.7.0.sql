
--
-- Mise à jour de la version 0.6.0 à 0.7.0
--

alter table llx_propal add remise_percent real default 0;
alter table llx_facture add remise_percent real default 0;