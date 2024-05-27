# CHANGELOG FACTURESITUATIONMIGRATION FOR [DOLIBARR ERP CRM](https://www.dolibarr.org)

## 0.3
NEW - Ajout entrées logs (dolibarr_situationmigration.log) - Plus de details avec FactureSituationMigration::log_detail = 1

## 0.2
FIX - La migration est faite sur l'entité en cours. chaque entité doit effectuer la migration.
FIX - Correction de la boucle du script de migration.
MAJ - Backup des lignes de factures de situation uniquement, les autres lignes ne sont pas impactées.
NEW - Paramètre cycle_limit dans la classe FactureSituationMigration pour limiter le nombre de cycles de factures à traiter, 50 par défaut.
NEW - Rollback fonctionnel.

## 0.1 - Initial version
