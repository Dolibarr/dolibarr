
--
-- Contenu de la table llx_c_ticketsup_type
--
INSERT INTO llx_c_ticketsup_type (rowid, code, pos, label, active, use_default, description) VALUES(1, 'COM', '10', 'Question commerciale', 1, 1, NULL);
INSERT INTO llx_c_ticketsup_type (rowid, code, pos, label, active, use_default, description) VALUES(2, 'INCIDENT', '20', 'Demande d''assistance', 1, 0, NULL);
INSERT INTO llx_c_ticketsup_type (rowid, code, pos, label, active, use_default, description) VALUES(3, 'PROJET', '30', 'Suivi projet', 1, 0, NULL);
INSERT INTO llx_c_ticketsup_type (rowid, code, pos, label, active, use_default, description) VALUES(4, 'OTHER', '40', 'Autre', 1, 0, NULL);

--
-- Contenu de la table llx_c_ticketsup_severity
--

INSERT INTO llx_c_ticketsup_severity (rowid, code, pos, label, color, active, use_default, description) VALUES(1, 'LOW', '10', 'Bas', '', 1, 0, NULL);
INSERT INTO llx_c_ticketsup_severity (rowid, code, pos, label, color, active, use_default, description) VALUES(2, 'NORMAL', '20', 'Normal', '', 1, 1, NULL);
INSERT INTO llx_c_ticketsup_severity (rowid, code, pos, label, color, active, use_default, description) VALUES(3, 'LOWHIGH', '30', 'Important', '', 1, 0, NULL);
INSERT INTO llx_c_ticketsup_severity (rowid, code, pos, label, color, active, use_default, description) VALUES(4, 'HIGH', '40', 'Critique / bloquant', '', 1, 0, NULL);
