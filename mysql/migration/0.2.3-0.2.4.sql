
--
-- Mise à jour de la version 0.2.3 à 0.2.4
--

--
-- Attention sur un catalogue produit important ce script peut-être long
-- à s'éxécuter
--

alter table llx_product modify tva_tx double default 19.6 ;
