# Audit initial du fork Dolibarr

Date : 2026-10-25  
Dépôt : `amipm178/dolibarr`  
Branche de base : `develop`  
Portée : revue initiale des fichiers `README.md` et `composer.json`. Ce document n'est pas un audit exhaustif du code PHP.

## Résumé

Le dépôt est un fork public de Dolibarr ERP/CRM. L'objectif de cette première passe est de consigner les constats vérifiables et de proposer une démarche sûre avant toute modification fonctionnelle.

## Constats

### P1 — Métadonnées de compatibilité PHP incohérentes

- `README.md` annonce PHP >= 7.2.
- `composer.json` déclare PHP >= 5.5.0.

Ces déclarations se contredisent et peuvent induire en erreur les utilisateurs, les outils d'installation et les pipelines CI. Avant de changer la contrainte Composer, confirmer la version réelle du code et la matrice de compatibilité visée. Ne pas relever la contrainte uniquement pour la faire correspondre au README.

### P1 — Dépendances déclarées anciennes

Le fichier Composer consulté contient notamment PHPUnit 4, PHP_CodeSniffer 2, PHPExcel 1.8.1 et CKEditor 4.12.1. Ces versions sont anciennes et méritent une revue de sécurité et de maintenance.

La présence de ces versions dans le manifeste ne suffit pas à conclure qu'une vulnérabilité exploitable existe dans l'installation courante. Examiner le lockfile, les versions réellement embarquées, les usages et les contraintes de compatibilité avant toute mise à niveau. PHPExcel devrait notamment faire l'objet d'une étude de migration séparée.

### P2 — Couverture de validation à établir

Cette passe n'a pas exécuté les tests, l'analyse statique, les tests d'installation ni les tests d'intégration avec une base de données. La couverture actuelle n'est donc pas encore caractérisée.

## Plan d'action proposé

1. **Établir la baseline** : relever le commit de base, la version Dolibarr déclarée, les workflows CI, les fichiers de verrouillage Composer et les scripts de test.
2. **Sécurité** : examiner les dépendances et leurs versions effectives, les points d'entrée HTTP, les contrôles de droits, les usages de `GETPOST`, les requêtes SQL, les sorties HTML et les téléchargements de fichiers.
3. **Qualité PHP** : lancer les vérifications déjà prévues par le dépôt, puis compléter par du lint et de l'analyse statique compatibles avec la version PHP cible.
4. **Tests fonctionnels** : prioriser authentification/permissions, tiers, factures, commandes, stock et migrations de base de données.
5. **Corrections ciblées** : une anomalie par commit ou petit groupe cohérent, avec test de non-régression et description du risque.
6. **Livraison** : ouvrir une pull request depuis cette branche après revue des changements et des résultats de tests.

## Vérifications effectuées

- Lecture de `README.md`.
- Lecture de `composer.json`.
- Création d'une branche dédiée à partir de `develop`.
- Aucun changement applicatif effectué à cette étape.
- Aucun test automatisé ni scan de sécurité exécuté depuis cet environnement.

## Limites

Il s'agit d'un audit documentaire initial sur un échantillon de fichiers, pas d'une certification de sécurité. Les constats doivent être confirmés par une analyse du code, des dépendances effectivement installées et des tests exécutés dans un environnement adapté.
