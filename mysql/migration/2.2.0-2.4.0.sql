--
-- $Id$
--
-- Attention à l ordre des requetes.
-- Ce fichier doit être chargé sur une version 2.2.0 
--

delete from llx_const where name='MAIN_GRAPH_LIBRARY' and (value like 'phplot%' or value like 'artichow%');
